<?php

declare(strict_types=1);

namespace App\Security\Voter;

use App\Entity\PengajuanCuti;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class PengajuanCutiVoter extends Voter
{
    public const ATTR_VIEW = 'PENGAJUAN_CUTI_VIEW';
    public const ATTR_EDIT = 'PENGAJUAN_CUTI_EDIT';
    public const ATTR_APPROVE = 'PENGAJUAN_CUTI_APPROVE';
    public const ATTR_REJECT = 'PENGAJUAN_CUTI_REJECT';
    public const ATTR_CANCEL = 'PENGAJUAN_CUTI_CANCEL';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [
            self::ATTR_VIEW,
            self::ATTR_EDIT,
            self::ATTR_APPROVE,
            self::ATTR_REJECT,
            self::ATTR_CANCEL,
        ]) && $subject instanceof PengajuanCuti;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();
        
        // User harus login
        if (!$user instanceof User) {
            return false;
        }

        /** @var PengajuanCuti $pengajuan */
        $pengajuan = $subject;

        return match ($attribute) {
            self::ATTR_VIEW => $this->canView($pengajuan, $user),
            self::ATTR_EDIT => $this->canEdit($pengajuan, $user),
            self::ATTR_APPROVE => $this->canApprove($pengajuan, $user),
            self::ATTR_REJECT => $this->canReject($pengajuan, $user),
            self::ATTR_CANCEL => $this->canCancel($pengajuan, $user),
            default => false,
        };
    }

    private function canView(PengajuanCuti $pengajuan, User $user): bool
    {
        // Admin bisa lihat semua
        if ($this->hasRole($user, 'ROLE_ADMIN')) {
            return true;
        }

        // Pemohon bisa lihat pengajuan sendiri
        if ($pengajuan->getUser() === $user) {
            return true;
        }

        // Approver bisa lihat pengajuan di unit kerjanya
        if ($this->hasRole($user, 'ROLE_APPROVER')) {
            return $this->isSameUnit($pengajuan, $user);
        }

        return false;
    }

    private function canEdit(PengajuanCuti $pengajuan, User $user): bool
    {
        // Hanya pemohon yang bisa edit pengajuan sendiri
        if ($pengajuan->getUser() !== $user) {
            return false;
        }

        // Status harus draft atau ditolak
        return in_array($pengajuan->getStatus(), ['draft', 'ditolak']);
    }

    private function canApprove(PengajuanCuti $pengajuan, User $user): bool
    {
        // Admin bisa approve semua
        if ($this->hasRole($user, 'ROLE_ADMIN')) {
            return $this->canProcessPengajuan($pengajuan, $user);
        }

        // Approver bisa approve jika di unit kerja yang sama dan bukan pengajuan sendiri
        if ($this->hasRole($user, 'ROLE_APPROVER')) {
            return $this->canProcessPengajuan($pengajuan, $user) 
                && $this->isSameUnit($pengajuan, $user) 
                && $pengajuan->getUser() !== $user;
        }

        return false;
    }

    private function canReject(PengajuanCuti $pengajuan, User $user): bool
    {
        // Sama dengan approve
        return $this->canApprove($pengajuan, $user);
    }

    private function canCancel(PengajuanCuti $pengajuan, User $user): bool
    {
        // Hanya pemohon yang bisa cancel pengajuan sendiri
        if ($pengajuan->getUser() !== $user) {
            return false;
        }

        // Tidak bisa cancel jika sudah disetujui
        return !in_array($pengajuan->getStatus(), ['disetujui']);
    }

    private function canProcessPengajuan(PengajuanCuti $pengajuan, User $user): bool
    {
        // Status harus 'diajukan'
        return $pengajuan->getStatus() === 'diajukan';
    }

    private function isSameUnit(PengajuanCuti $pengajuan, User $user): bool
    {
        return $pengajuan->getUser()->getUnitKerja() === $user->getUnitKerja();
    }

    private function hasRole(User $user, string $role): bool
    {
        return in_array($role, $user->getRoles());
    }
}