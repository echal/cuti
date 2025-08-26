<?php

namespace App\Entity;

use App\Repository\JenisCutiRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: JenisCutiRepository::class)]
#[ORM\Table(name: 'jenis_cuti')]
#[ORM\HasLifecycleCallbacks]
class JenisCuti
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 10, unique: true)]
    private ?string $kode = null;

    #[ORM\Column(length: 255)]
    private ?string $nama = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $deskripsi = null;

    #[ORM\Column(nullable: true)]
    private ?int $durasiMax = null;

    #[ORM\Column(length: 500, nullable: true)]
    private ?string $dasarHukum = null;

    #[ORM\Column(length: 10)]
    private ?string $tersediUntuk = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\OneToMany(mappedBy: 'jenisCuti', targetEntity: PengajuanCuti::class)]
    private Collection $pengajuanCutis;

    public function __construct()
    {
        $this->pengajuanCutis = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getKode(): ?string
    {
        return $this->kode;
    }

    public function setKode(string $kode): static
    {
        $this->kode = $kode;

        return $this;
    }

    public function getNama(): ?string
    {
        return $this->nama;
    }

    public function setNama(string $nama): static
    {
        $this->nama = $nama;

        return $this;
    }

    public function getDeskripsi(): ?string
    {
        return $this->deskripsi;
    }

    public function setDeskripsi(?string $deskripsi): static
    {
        $this->deskripsi = $deskripsi;

        return $this;
    }

    public function getDurasiMax(): ?int
    {
        return $this->durasiMax;
    }

    public function setDurasiMax(?int $durasiMax): static
    {
        $this->durasiMax = $durasiMax;

        return $this;
    }

    public function getDasarHukum(): ?string
    {
        return $this->dasarHukum;
    }

    public function setDasarHukum(?string $dasarHukum): static
    {
        $this->dasarHukum = $dasarHukum;

        return $this;
    }

    public function getTersediUntuk(): ?string
    {
        return $this->tersediUntuk;
    }

    public function setTersediUntuk(string $tersediUntuk): static
    {
        $this->tersediUntuk = $tersediUntuk;

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

    /**
     * @return Collection<int, PengajuanCuti>
     */
    public function getPengajuanCutis(): Collection
    {
        return $this->pengajuanCutis;
    }

    public function addPengajuanCuti(PengajuanCuti $pengajuanCuti): static
    {
        if (!$this->pengajuanCutis->contains($pengajuanCuti)) {
            $this->pengajuanCutis->add($pengajuanCuti);
            $pengajuanCuti->setJenisCuti($this);
        }

        return $this;
    }

    public function removePengajuanCuti(PengajuanCuti $pengajuanCuti): static
    {
        if ($this->pengajuanCutis->removeElement($pengajuanCuti)) {
            if ($pengajuanCuti->getJenisCuti() === $this) {
                $pengajuanCuti->setJenisCuti(null);
            }
        }

        return $this;
    }

    public function isTersediaUntukPNS(): bool
    {
        return in_array($this->tersediUntuk, ['PNS', 'ALL']);
    }

    public function isTersediaUntukPPPK(): bool
    {
        return in_array($this->tersediUntuk, ['PPPK', 'ALL']);
    }

    public function isTersediaUntuk(string $statusKepegawaian): bool
    {
        return $this->tersediUntuk === 'ALL' || $this->tersediUntuk === $statusKepegawaian;
    }

    public function hasDurasiMax(): bool
    {
        return $this->durasiMax !== null;
    }

    #[ORM\PreUpdate]
    public function setUpdatedAtValue(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function __toString(): string
    {
        return sprintf('%s - %s', $this->kode, $this->nama);
    }
}