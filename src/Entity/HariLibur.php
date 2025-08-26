<?php

namespace App\Entity;

use App\Repository\HariLiburRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: HariLiburRepository::class)]
#[ORM\Table(name: 'hari_libur')]
#[ORM\HasLifecycleCallbacks]
class HariLibur
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    #[Assert\NotBlank(message: 'Tanggal libur harus diisi')]
    private ?\DateTimeInterface $tanggal = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank(message: 'Keterangan hari libur harus diisi')]
    #[Assert\Length(
        max: 100,
        maxMessage: 'Keterangan tidak boleh lebih dari {{ limit }} karakter'
    )]
    private ?string $keterangan = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private ?\DateTimeImmutable $updatedAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTanggal(): ?\DateTimeInterface
    {
        return $this->tanggal;
    }

    public function setTanggal(\DateTimeInterface $tanggal): static
    {
        $this->tanggal = $tanggal;

        return $this;
    }

    public function getKeterangan(): ?string
    {
        return $this->keterangan;
    }

    public function setKeterangan(string $keterangan): static
    {
        $this->keterangan = $keterangan;

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

    #[ORM\PreUpdate]
    public function setUpdatedAtValue(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function __toString(): string
    {
        return sprintf('%s - %s', 
            $this->tanggal ? $this->tanggal->format('d/m/Y') : '',
            $this->keterangan ?: ''
        );
    }
}
