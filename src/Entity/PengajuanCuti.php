<?php

namespace App\Entity;

use App\Repository\PengajuanCutiRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use App\Validator\RequireMedicalLetter;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

#[ORM\Entity(repositoryClass: PengajuanCutiRepository::class)]
#[ORM\Table(name: 'pengajuan_cuti')]
#[ORM\HasLifecycleCallbacks]
#[RequireMedicalLetter]
#[Assert\Callback('validateDateRange')]
#[Assert\Callback('validateDuration')]
class PengajuanCuti
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $tanggalPengajuan = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    #[Assert\NotBlank(message: 'Tanggal mulai cuti harus diisi')]
    #[Assert\GreaterThanOrEqual('today', message: 'Tanggal mulai tidak boleh kurang dari hari ini')]
    private ?\DateTimeInterface $tanggalMulai = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    #[Assert\NotBlank(message: 'Tanggal selesai cuti harus diisi')]
    #[Assert\Expression(
        'this.getTanggalSelesai() >= this.getTanggalMulai()',
        message: 'Tanggal selesai harus sama atau setelah tanggal mulai'
    )]
    private ?\DateTimeInterface $tanggalSelesai = null;

    #[ORM\Column]
    private ?int $lamaCuti = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $alasan = null;

    #[ORM\Column(length: 20)]
    private ?string $status = 'draft';

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $filePendukung = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $nomorSurat = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank(message: 'Alamat selama menjalankan cuti harus diisi')]
    private ?string $alamatCuti = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'pengajuanCutis')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\ManyToOne(targetEntity: JenisCuti::class, inversedBy: 'pengajuanCutis')]
    #[ORM\JoinColumn(nullable: false)]
    private ?JenisCuti $jenisCuti = null;

    #[ORM\ManyToOne(targetEntity: Pejabat::class, inversedBy: 'pengajuanCutisPenyetuju')]
    #[ORM\JoinColumn(nullable: true)]
    private ?Pejabat $pejabatPenyetuju = null;

    #[ORM\ManyToOne(targetEntity: Pejabat::class, inversedBy: 'pengajuanCutisAtasan')]
    #[ORM\JoinColumn(nullable: true)]
    private ?Pejabat $pejabatAtasan = null;

    #[ORM\OneToMany(mappedBy: 'pengajuanCuti', targetEntity: RiwayatCuti::class, orphanRemoval: true)]
    private Collection $riwayatCutis;

    #[ORM\OneToOne(mappedBy: 'pengajuanCuti', targetEntity: DokumenCuti::class, cascade: ['persist', 'remove'])]
    private ?DokumenCuti $dokumenCuti = null;

    public function __construct()
    {
        $this->riwayatCutis = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
        $this->tanggalPengajuan = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTanggalPengajuan(): ?\DateTimeInterface
    {
        return $this->tanggalPengajuan;
    }

    public function setTanggalPengajuan(\DateTimeInterface $tanggalPengajuan): static
    {
        $this->tanggalPengajuan = $tanggalPengajuan;

        return $this;
    }

    public function getTanggalMulai(): ?\DateTimeInterface
    {
        return $this->tanggalMulai;
    }

    public function setTanggalMulai(\DateTimeInterface $tanggalMulai): static
    {
        $this->tanggalMulai = $tanggalMulai;

        return $this;
    }

    public function getTanggalSelesai(): ?\DateTimeInterface
    {
        return $this->tanggalSelesai;
    }

    public function setTanggalSelesai(\DateTimeInterface $tanggalSelesai): static
    {
        $this->tanggalSelesai = $tanggalSelesai;

        return $this;
    }

    public function getLamaCuti(): ?int
    {
        return $this->lamaCuti;
    }

    public function setLamaCuti(int $lamaCuti): static
    {
        $this->lamaCuti = $lamaCuti;

        return $this;
    }

    public function getAlasan(): ?string
    {
        return $this->alasan;
    }

    public function setAlasan(string $alasan): static
    {
        $this->alasan = $alasan;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getFilePendukung(): ?string
    {
        return $this->filePendukung;
    }

    public function setFilePendukung(?string $filePendukung): static
    {
        $this->filePendukung = $filePendukung;

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

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getJenisCuti(): ?JenisCuti
    {
        return $this->jenisCuti;
    }

    public function setJenisCuti(?JenisCuti $jenisCuti): static
    {
        $this->jenisCuti = $jenisCuti;

        return $this;
    }

    public function getPejabatPenyetuju(): ?Pejabat
    {
        return $this->pejabatPenyetuju;
    }

    public function setPejabatPenyetuju(?Pejabat $pejabatPenyetuju): static
    {
        $this->pejabatPenyetuju = $pejabatPenyetuju;

        return $this;
    }

    public function getPejabatAtasan(): ?Pejabat
    {
        return $this->pejabatAtasan;
    }

    public function setPejabatAtasan(?Pejabat $pejabatAtasan): static
    {
        $this->pejabatAtasan = $pejabatAtasan;

        return $this;
    }

    /**
     * @return Collection<int, RiwayatCuti>
     */
    public function getRiwayatCutis(): Collection
    {
        return $this->riwayatCutis;
    }

    public function addRiwayatCuti(RiwayatCuti $riwayatCuti): static
    {
        if (!$this->riwayatCutis->contains($riwayatCuti)) {
            $this->riwayatCutis->add($riwayatCuti);
            $riwayatCuti->setPengajuanCuti($this);
        }

        return $this;
    }

    public function removeRiwayatCuti(RiwayatCuti $riwayatCuti): static
    {
        if ($this->riwayatCutis->removeElement($riwayatCuti)) {
            if ($riwayatCuti->getPengajuanCuti() === $this) {
                $riwayatCuti->setPengajuanCuti(null);
            }
        }

        return $this;
    }

    public function getDokumenCuti(): ?DokumenCuti
    {
        return $this->dokumenCuti;
    }

    public function setDokumenCuti(DokumenCuti $dokumenCuti): static
    {
        if ($dokumenCuti->getPengajuanCuti() !== $this) {
            $dokumenCuti->setPengajuanCuti($this);
        }

        $this->dokumenCuti = $dokumenCuti;

        return $this;
    }

    /**
     * @deprecated Use CutiCalculator->hitungLamaCuti() with 'workday' mode instead
     * This method calculates calendar days including weekends
     */
    public function calculateLamaCuti(): void
    {
        if ($this->tanggalMulai && $this->tanggalSelesai) {
            $diff = $this->tanggalMulai->diff($this->tanggalSelesai);
            $this->lamaCuti = $diff->days + 1;
        }
    }

    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    public function isDiajukan(): bool
    {
        return $this->status === 'diajukan';
    }

    public function isDisetujui(): bool
    {
        return $this->status === 'disetujui';
    }

    public function isDitolak(): bool
    {
        return $this->status === 'ditolak';
    }

    public function isDibatalkan(): bool
    {
        return $this->status === 'dibatalkan';
    }

    public function canBeEdited(): bool
    {
        return in_array($this->status, ['draft', 'ditolak']);
    }

    public function canBeCancelled(): bool
    {
        return in_array($this->status, ['draft', 'diajukan']);
    }

    public function canBeApproved(): bool
    {
        return $this->status === 'diajukan';
    }

    public function isExpired(): bool
    {
        if (!$this->tanggalSelesai) {
            return false;
        }

        return $this->tanggalSelesai < new \DateTime();
    }

    public function getStatusBadgeClass(): string
    {
        return match ($this->status) {
            'draft' => 'badge-secondary',
            'diajukan' => 'badge-warning',
            'disetujui' => 'badge-success',
            'ditolak' => 'badge-danger',
            'dibatalkan' => 'badge-dark',
            default => 'badge-light'
        };
    }

    public function hasFilePendukung(): bool
    {
        return !empty($this->filePendukung);
    }

    public function getNomorSurat(): ?string
    {
        return $this->nomorSurat;
    }

    public function setNomorSurat(?string $nomorSurat): static
    {
        $this->nomorSurat = $nomorSurat;

        return $this;
    }

    public function getAlamatCuti(): ?string
    {
        return $this->alamatCuti;
    }

    public function setAlamatCuti(string $alamatCuti): static
    {
        $this->alamatCuti = $alamatCuti;

        return $this;
    }


    #[ORM\PreUpdate]
    public function setUpdatedAtValue(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function __toString(): string
    {
        return sprintf('Pengajuan Cuti %s - %s (%s)', 
            $this->jenisCuti?->getKode(), 
            $this->user?->getNama(),
            $this->status
        );
    }

    /**
     * Get alasan penolakan from riwayat cuti
     */
    public function getAlasanPenolakan(): ?string
    {
        // Cari riwayat dengan aksi "ditolak" yang terakhir
        $riwayatDitolak = null;
        foreach ($this->riwayatCutis as $riwayat) {
            if ($riwayat->getAksi() === 'ditolak') {
                // Ambil yang terakhir (paling baru)
                if ($riwayatDitolak === null || $riwayat->getTanggalAksi() > $riwayatDitolak->getTanggalAksi()) {
                    $riwayatDitolak = $riwayat;
                }
            }
        }
        
        return $riwayatDitolak ? $riwayatDitolak->getCatatan() : null;
    }

    /**
     * Validate date range
     */
    public function validateDateRange(ExecutionContextInterface $context): void
    {
        if (!$this->tanggalMulai || !$this->tanggalSelesai) {
            return;
        }

        if ($this->tanggalSelesai < $this->tanggalMulai) {
            $context->buildViolation('Tanggal selesai harus sama atau setelah tanggal mulai')
                ->atPath('tanggalSelesai')
                ->addViolation();
        }

        // Check if start date is not in the past (allow same day)
        $today = new \DateTime('today');
        if ($this->tanggalMulai < $today) {
            $context->buildViolation('Tanggal mulai tidak boleh kurang dari hari ini')
                ->atPath('tanggalMulai')
                ->addViolation();
        }
    }

    /**
     * Validate duration against jenis cuti max duration
     */
    public function validateDuration(ExecutionContextInterface $context): void
    {
        if (!$this->jenisCuti || !$this->lamaCuti) {
            return;
        }

        $maxDuration = $this->jenisCuti->getDurasiMax();
        if ($maxDuration && $this->lamaCuti > $maxDuration) {
            $context->buildViolation(sprintf(
                'Lama cuti (%d hari) melebihi batas maksimal untuk jenis cuti %s (%d hari)',
                $this->lamaCuti,
                $this->jenisCuti->getNama(),
                $maxDuration
            ))
                ->atPath('lamaCuti')
                ->addViolation();
        }
    }
}