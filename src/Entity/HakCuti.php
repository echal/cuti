<?php

namespace App\Entity;

use App\Repository\HakCutiRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

#[ORM\Entity(repositoryClass: HakCutiRepository::class)]
#[ORM\Table(name: 'hak_cuti')]
#[ORM\HasLifecycleCallbacks]
#[ORM\UniqueConstraint(name: 'user_tahun_unique', columns: ['user_id', 'tahun'])]
#[Assert\Callback('validateConsistency')]
class HakCuti
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?int $tahun = null;

    #[ORM\Column]
    private ?int $hakTahunan = 12;

    #[ORM\Column]
    private ?int $terpakai = 0;

    #[ORM\Column]
    private ?int $sisa = null;

    #[ORM\Column]
    private ?bool $carryOver = false;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'hakCutis')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
        $this->calculateSisa();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTahun(): ?int
    {
        return $this->tahun;
    }

    public function setTahun(int $tahun): static
    {
        $this->tahun = $tahun;
        $this->calculateSisa();

        return $this;
    }

    public function getHakTahunan(): ?int
    {
        return $this->hakTahunan;
    }

    public function setHakTahunan(int $hakTahunan): static
    {
        $this->hakTahunan = $hakTahunan;
        $this->calculateSisa();

        return $this;
    }

    public function getTerpakai(): ?int
    {
        return $this->terpakai;
    }

    public function setTerpakai(int $terpakai): static
    {
        $this->terpakai = $terpakai;
        $this->calculateSisa();

        return $this;
    }

    public function getSisa(): ?int
    {
        return $this->sisa;
    }

    public function setSisa(int $sisa): static
    {
        $this->sisa = $sisa;

        return $this;
    }

    public function isCarryOver(): ?bool
    {
        return $this->carryOver;
    }

    public function setCarryOver(bool $carryOver): static
    {
        $this->carryOver = $carryOver;

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

    public function addTerpakai(int $hari): static
    {
        $this->terpakai += $hari;
        $this->calculateSisa();

        return $this;
    }

    public function removeTerpakai(int $hari): static
    {
        $this->terpakai = max(0, $this->terpakai - $hari);
        $this->calculateSisa();

        return $this;
    }

    public function calculateSisa(): void
    {
        if ($this->hakTahunan !== null && $this->terpakai !== null) {
            $this->sisa = $this->hakTahunan - $this->terpakai;
        }
    }

    public function isCutiAvailable(int $jumlahHari): bool
    {
        return $this->sisa >= $jumlahHari;
    }

    public function getPersentaseTerpakai(): float
    {
        if ($this->hakTahunan === 0) {
            return 0;
        }
        
        return ($this->terpakai / $this->hakTahunan) * 100;
    }

    public function isHakCutiHabis(): bool
    {
        return $this->sisa <= 0;
    }

    #[ORM\PrePersist]
    #[ORM\PreUpdate]
    public function updateSisaBeforeSave(): void
    {
        $this->calculateSisa();
    }

    #[ORM\PreUpdate]
    public function setUpdatedAtValue(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function validateConsistency(ExecutionContextInterface $context): void
    {
        if ($this->hakTahunan === null || $this->terpakai === null) {
            return;
        }

        $expectedSisa = $this->hakTahunan - $this->terpakai;
        
        if ($this->sisa !== $expectedSisa) {
            $context->buildViolation('Sisa cuti tidak konsisten dengan perhitungan (Hak Tahunan - Terpakai)')
                ->atPath('sisa')
                ->addViolation();
        }

        if ($this->terpakai < 0) {
            $context->buildViolation('Cuti terpakai tidak boleh negatif')
                ->atPath('terpakai')
                ->addViolation();
        }

        if ($this->hakTahunan < 0) {
            $context->buildViolation('Hak cuti tahunan tidak boleh negatif')
                ->atPath('hakTahunan')
                ->addViolation();
        }

        if ($this->terpakai > $this->hakTahunan) {
            $context->buildViolation('Cuti terpakai tidak boleh melebihi hak tahunan')
                ->atPath('terpakai')
                ->addViolation();
        }
    }

    public function __toString(): string
    {
        return sprintf('Hak Cuti %d - %s (Sisa: %d)', $this->tahun, $this->user?->getNama(), $this->sisa);
    }
}