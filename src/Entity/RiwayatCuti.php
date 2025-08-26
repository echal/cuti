<?php

namespace App\Entity;

use App\Repository\RiwayatCutiRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: RiwayatCutiRepository::class)]
#[ORM\Table(name: 'riwayat_cuti')]
#[ORM\HasLifecycleCallbacks]
class RiwayatCuti
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 20)]
    #[Assert\NotBlank(message: 'Aksi harus diisi')]
    #[Assert\Length(max: 20, maxMessage: 'Aksi tidak boleh lebih dari {{ limit }} karakter')]
    private ?string $aksi = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private ?\DateTimeImmutable $tanggalAksi = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $catatan = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\ManyToOne(targetEntity: PengajuanCuti::class, inversedBy: 'riwayatCutis')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull(message: 'Pengajuan cuti harus diisi')]
    private ?PengajuanCuti $pengajuanCuti = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull(message: 'User harus diisi')]
    private ?User $user = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
        $this->tanggalAksi = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAksi(): ?string
    {
        return $this->aksi;
    }

    public function setAksi(string $aksi): static
    {
        $this->aksi = $aksi;

        return $this;
    }

    public function getTanggalAksi(): ?\DateTimeImmutable
    {
        return $this->tanggalAksi;
    }

    public function setTanggalAksi(\DateTimeImmutable $tanggalAksi): static
    {
        $this->tanggalAksi = $tanggalAksi;

        return $this;
    }

    public function getCatatan(): ?string
    {
        return $this->catatan;
    }

    public function setCatatan(?string $catatan): static
    {
        $this->catatan = $catatan;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function getPengajuanCuti(): ?PengajuanCuti
    {
        return $this->pengajuanCuti;
    }

    public function setPengajuanCuti(?PengajuanCuti $pengajuanCuti): static
    {
        $this->pengajuanCuti = $pengajuanCuti;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function isDiajukan(): bool
    {
        return $this->aksi === 'diajukan';
    }

    public function isDisetujui(): bool
    {
        return $this->aksi === 'disetujui';
    }

    public function isDitolak(): bool
    {
        return $this->aksi === 'ditolak';
    }

    public function isDibatalkan(): bool
    {
        return $this->aksi === 'dibatalkan';
    }

    public function getAksiLabel(): string
    {
        return match ($this->aksi) {
            'diajukan' => 'Diajukan',
            'disetujui' => 'Disetujui',
            'ditolak' => 'Ditolak',
            'dibatalkan' => 'Dibatalkan',
            default => ucfirst($this->aksi)
        };
    }

    public function getAksiBadgeClass(): string
    {
        return match ($this->aksi) {
            'diajukan' => 'badge-primary',
            'disetujui' => 'badge-success',
            'ditolak' => 'badge-danger',
            'dibatalkan' => 'badge-warning',
            default => 'badge-secondary'
        };
    }

    public function hasCatatan(): bool
    {
        return !empty(trim($this->catatan));
    }

    public function isSystemAction(): bool
    {
        return $this->aksi === 'diajukan';
    }

    public function isManualAction(): bool
    {
        return !$this->isSystemAction();
    }

    public function getTimeSinceAction(): string
    {
        $now = new \DateTimeImmutable();
        $diff = $now->diff($this->tanggalAksi);

        if ($diff->days > 0) {
            return $diff->days . ' hari yang lalu';
        } elseif ($diff->h > 0) {
            return $diff->h . ' jam yang lalu';
        } elseif ($diff->i > 0) {
            return $diff->i . ' menit yang lalu';
        } else {
            return 'Baru saja';
        }
    }

    #[ORM\PreUpdate]
    public function setUpdatedAtValue(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    public static function createRiwayat(PengajuanCuti $pengajuanCuti, User $user, string $aksi, ?string $catatan = null): self
    {
        $riwayat = new self();
        $riwayat->setPengajuanCuti($pengajuanCuti);
        $riwayat->setUser($user);
        $riwayat->setAksi($aksi);
        $riwayat->setCatatan($catatan);
        
        return $riwayat;
    }

    public function __toString(): string
    {
        return sprintf('%s oleh %s pada %s', 
            $this->getAksiLabel(),
            $this->user?->getNama(),
            $this->tanggalAksi?->format('d/m/Y H:i')
        );
    }
}