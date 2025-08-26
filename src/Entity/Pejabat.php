<?php

namespace App\Entity;

use App\Repository\PejabatRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PejabatRepository::class)]
#[ORM\Table(name: 'pejabat')]
#[ORM\HasLifecycleCallbacks]
class Pejabat
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $nama = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $nip = null;

    #[ORM\Column(length: 255)]
    private ?string $jabatan = null;

    #[ORM\ManyToOne(targetEntity: UnitKerja::class, inversedBy: 'pejabats')]
    #[ORM\JoinColumn(nullable: true)]
    private ?UnitKerja $unitKerja = null;

    #[ORM\Column(length: 20)]
    private ?string $status = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $mulaiMenjabat = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $selesaiMenjabat = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\OneToMany(mappedBy: 'pejabatPenyetuju', targetEntity: PengajuanCuti::class)]
    private Collection $pengajuanCutisPenyetuju;

    #[ORM\OneToMany(mappedBy: 'pejabatAtasan', targetEntity: PengajuanCuti::class)]
    private Collection $pengajuanCutisAtasan;

    public function __construct()
    {
        $this->pengajuanCutisPenyetuju = new ArrayCollection();
        $this->pengajuanCutisAtasan = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getNip(): ?string
    {
        return $this->nip;
    }

    public function setNip(?string $nip): static
    {
        $this->nip = $nip;

        return $this;
    }

    public function getJabatan(): ?string
    {
        return $this->jabatan;
    }

    public function setJabatan(string $jabatan): static
    {
        $this->jabatan = $jabatan;

        return $this;
    }

    public function getUnitKerja(): ?UnitKerja
    {
        return $this->unitKerja;
    }

    public function setUnitKerja(?UnitKerja $unitKerja): static
    {
        $this->unitKerja = $unitKerja;

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

    public function getMulaiMenjabat(): ?\DateTimeInterface
    {
        return $this->mulaiMenjabat;
    }

    public function setMulaiMenjabat(\DateTimeInterface $mulaiMenjabat): static
    {
        $this->mulaiMenjabat = $mulaiMenjabat;

        return $this;
    }

    public function getSelesaiMenjabat(): ?\DateTimeInterface
    {
        return $this->selesaiMenjabat;
    }

    public function setSelesaiMenjabat(?\DateTimeInterface $selesaiMenjabat): static
    {
        $this->selesaiMenjabat = $selesaiMenjabat;

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
    public function getPengajuanCutisPenyetuju(): Collection
    {
        return $this->pengajuanCutisPenyetuju;
    }

    public function addPengajuanCutiPenyetuju(PengajuanCuti $pengajuanCuti): static
    {
        if (!$this->pengajuanCutisPenyetuju->contains($pengajuanCuti)) {
            $this->pengajuanCutisPenyetuju->add($pengajuanCuti);
            $pengajuanCuti->setPejabatPenyetuju($this);
        }

        return $this;
    }

    public function removePengajuanCutiPenyetuju(PengajuanCuti $pengajuanCuti): static
    {
        if ($this->pengajuanCutisPenyetuju->removeElement($pengajuanCuti)) {
            if ($pengajuanCuti->getPejabatPenyetuju() === $this) {
                $pengajuanCuti->setPejabatPenyetuju(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, PengajuanCuti>
     */
    public function getPengajuanCutisAtasan(): Collection
    {
        return $this->pengajuanCutisAtasan;
    }

    public function addPengajuanCutiAtasan(PengajuanCuti $pengajuanCuti): static
    {
        if (!$this->pengajuanCutisAtasan->contains($pengajuanCuti)) {
            $this->pengajuanCutisAtasan->add($pengajuanCuti);
            $pengajuanCuti->setPejabatAtasan($this);
        }

        return $this;
    }

    public function removePengajuanCutiAtasan(PengajuanCuti $pengajuanCuti): static
    {
        if ($this->pengajuanCutisAtasan->removeElement($pengajuanCuti)) {
            if ($pengajuanCuti->getPejabatAtasan() === $this) {
                $pengajuanCuti->setPejabatAtasan(null);
            }
        }

        return $this;
    }

    public function isAktif(): bool
    {
        return $this->status === 'aktif';
    }

    public function isMasihMenjabat(): bool
    {
        return $this->selesaiMenjabat === null || $this->selesaiMenjabat >= new \DateTime();
    }

    #[ORM\PreUpdate]
    public function setUpdatedAtValue(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function __toString(): string
    {
        return sprintf('%s - %s', $this->nama, $this->jabatan);
    }
}