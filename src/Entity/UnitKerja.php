<?php

namespace App\Entity;

use App\Repository\UnitKerjaRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UnitKerjaRepository::class)]
#[ORM\Table(name: 'unit_kerja')]
#[ORM\HasLifecycleCallbacks]
class UnitKerja
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 20, unique: true)]
    private ?string $kode = null;

    #[ORM\Column(length: 255)]
    private ?string $nama = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\OneToMany(mappedBy: 'unitKerja', targetEntity: User::class)]
    private Collection $users;

    #[ORM\OneToMany(mappedBy: 'unitKerja', targetEntity: Pejabat::class)]
    private Collection $pejabats;

    public function __construct()
    {
        $this->users = new ArrayCollection();
        $this->pejabats = new ArrayCollection();
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
     * @return Collection<int, User>
     */
    public function getUsers(): Collection
    {
        return $this->users;
    }

    public function addUser(User $user): static
    {
        if (!$this->users->contains($user)) {
            $this->users->add($user);
            $user->setUnitKerja($this);
        }

        return $this;
    }

    public function removeUser(User $user): static
    {
        if ($this->users->removeElement($user)) {
            if ($user->getUnitKerja() === $this) {
                $user->setUnitKerja(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Pejabat>
     */
    public function getPejabats(): Collection
    {
        return $this->pejabats;
    }

    public function addPejabat(Pejabat $pejabat): static
    {
        if (!$this->pejabats->contains($pejabat)) {
            $this->pejabats->add($pejabat);
            $pejabat->setUnitKerja($this);
        }

        return $this;
    }

    public function removePejabat(Pejabat $pejabat): static
    {
        if ($this->pejabats->removeElement($pejabat)) {
            if ($pejabat->getUnitKerja() === $this) {
                $pejabat->setUnitKerja(null);
            }
        }

        return $this;
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