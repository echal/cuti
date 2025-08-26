<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: 'user')]
#[ORM\HasLifecycleCallbacks]
#[Assert\Callback('validateNipNipt')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 18, unique: true)]
    #[Assert\NotBlank(message: 'NIP harus diisi')]
    #[Assert\Regex(
        pattern: '/^\d{18}$/',
        message: 'NIP harus berupa 18 digit angka'
    )]
    private ?string $nip = null;

    #[ORM\Column(length: 255)]
    private ?string $nama = null;

    #[ORM\Column(length: 255, unique: true)]
    private ?string $email = null;

    #[ORM\Column(type: 'json')]
    private array $roles = [];

    #[ORM\Column(length: 255)]
    private ?string $password = null;

    #[ORM\Column(length: 1)]
    private ?string $jenisKelamin = null;

    #[ORM\Column(length: 10)]
    private ?string $statusKepegawaian = null;


    #[ORM\Column(length: 255)]
    private ?string $jabatan = null;

    #[ORM\ManyToOne(targetEntity: UnitKerja::class, inversedBy: 'users')]
    #[ORM\JoinColumn(nullable: false)]
    private ?UnitKerja $unitKerja = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $tmtCpns = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $tmtPns = null;

    #[ORM\Column]
    private ?int $jumlahAnak = 0;

    #[ORM\Column(length: 20)]
    private ?string $statusPegawai = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $golongan = null;

    #[ORM\Column(length: 15, nullable: true)]
    #[Assert\Regex(
        pattern: '/^(\+62|0)[0-9]{8,13}$/',
        message: 'Format nomor telepon tidak valid. Gunakan format Indonesia: 08xxxxxxxxx atau +628xxxxxxxxx'
    )]
    private ?string $telp = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: HakCuti::class, orphanRemoval: true)]
    private Collection $hakCutis;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: PengajuanCuti::class, orphanRemoval: true)]
    private Collection $pengajuanCutis;

    public function __construct()
    {
        $this->hakCutis = new ArrayCollection();
        $this->pengajuanCutis = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getNama(): ?string
    {
        return $this->nama;
    }

    public function setNama(string $nama): static
    {
        $this->nama = $nama;

        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    public function getJenisKelamin(): ?string
    {
        return $this->jenisKelamin;
    }

    public function setJenisKelamin(string $jenisKelamin): static
    {
        $this->jenisKelamin = $jenisKelamin;

        return $this;
    }

    public function getStatusKepegawaian(): ?string
    {
        return $this->statusKepegawaian;
    }

    public function setStatusKepegawaian(string $statusKepegawaian): static
    {
        $this->statusKepegawaian = $statusKepegawaian;

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

    public function getTmtCpns(): ?\DateTimeInterface
    {
        return $this->tmtCpns;
    }

    public function setTmtCpns(?\DateTimeInterface $tmtCpns): static
    {
        $this->tmtCpns = $tmtCpns;

        return $this;
    }

    public function getTmtPns(): ?\DateTimeInterface
    {
        return $this->tmtPns;
    }

    public function setTmtPns(?\DateTimeInterface $tmtPns): static
    {
        $this->tmtPns = $tmtPns;

        return $this;
    }

    public function getJumlahAnak(): ?int
    {
        return $this->jumlahAnak;
    }

    public function setJumlahAnak(int $jumlahAnak): static
    {
        $this->jumlahAnak = $jumlahAnak;

        return $this;
    }

    public function getStatusPegawai(): ?string
    {
        return $this->statusPegawai;
    }

    public function setStatusPegawai(string $statusPegawai): static
    {
        $this->statusPegawai = $statusPegawai;

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
     * @return Collection<int, HakCuti>
     */
    public function getHakCutis(): Collection
    {
        return $this->hakCutis;
    }

    public function addHakCuti(HakCuti $hakCuti): static
    {
        if (!$this->hakCutis->contains($hakCuti)) {
            $this->hakCutis->add($hakCuti);
            $hakCuti->setUser($this);
        }

        return $this;
    }

    public function removeHakCuti(HakCuti $hakCuti): static
    {
        if ($this->hakCutis->removeElement($hakCuti)) {
            if ($hakCuti->getUser() === $this) {
                $hakCuti->setUser(null);
            }
        }

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
            $pengajuanCuti->setUser($this);
        }

        return $this;
    }

    public function removePengajuanCuti(PengajuanCuti $pengajuanCuti): static
    {
        if ($this->pengajuanCutis->removeElement($pengajuanCuti)) {
            if ($pengajuanCuti->getUser() === $this) {
                $pengajuanCuti->setUser(null);
            }
        }

        return $this;
    }

    #[ORM\PreUpdate]
    public function setUpdatedAtValue(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    // UserInterface methods
    public function getUserIdentifier(): string
    {
        return (string) $this->nip;
    }

    public function getUsername(): string
    {
        return $this->getUserIdentifier();
    }

    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    public function setRoles(array $roles): self
    {
        $this->roles = $roles;

        return $this;
    }

    public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    /**
     * Validate NIP/NIPT based on status kepegawaian
     */
    public function validateNipNipt(ExecutionContextInterface $context): void
    {
        if (!$this->statusKepegawaian || !$this->nip) {
            return;
        }

        // Both PNS and PPPK must have valid NIP format (18 digits)
        if (!preg_match('/^\d{18}$/', $this->nip)) {
            $context->buildViolation('NIP harus berupa 18 digit angka')
                ->atPath('nip')
                ->addViolation();
        }
    }

    /**
     * Get identifier for display (NIP)
     */
    public function getIdentifier(): ?string
    {
        return $this->nip;
    }

    /**
     * Get identifier type
     */
    public function getIdentifierType(): string
    {
        return $this->nip ? 'NIP' : 'ID';
    }

    /**
     * Get masa kerja berdasarkan TMT CPNS
     */
    public function getMasaKerja(): ?string
    {
        if (!$this->tmtCpns) {
            return null;
        }

        $now = new \DateTime();
        $diff = $this->tmtCpns->diff($now);
        
        return $diff->y . ' tahun ' . $diff->m . ' bulan';
    }

    public function getGolongan(): ?string
    {
        return $this->golongan;
    }

    public function setGolongan(?string $golongan): static
    {
        $this->golongan = $golongan;

        return $this;
    }

    public function getTelp(): ?string
    {
        return $this->telp;
    }

    public function setTelp(?string $telp): static
    {
        $this->telp = $telp;

        return $this;
    }
}