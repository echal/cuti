<?php

namespace App\Entity;

use App\Repository\DokumenCutiRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DokumenCutiRepository::class)]
#[ORM\Table(name: 'dokumen_cuti')]
#[ORM\HasLifecycleCallbacks]
class DokumenCuti
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $namaFile = null;

    #[ORM\Column(length: 500)]
    private ?string $pathFile = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $nomorDokumen = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\OneToOne(targetEntity: PengajuanCuti::class, inversedBy: 'dokumenCuti')]
    #[ORM\JoinColumn(nullable: false)]
    private ?PengajuanCuti $pengajuanCuti = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNamaFile(): ?string
    {
        return $this->namaFile;
    }

    public function setNamaFile(string $namaFile): static
    {
        $this->namaFile = $namaFile;

        return $this;
    }

    public function getPathFile(): ?string
    {
        return $this->pathFile;
    }

    public function setPathFile(string $pathFile): static
    {
        $this->pathFile = $pathFile;

        return $this;
    }

    public function getNomorDokumen(): ?string
    {
        return $this->nomorDokumen;
    }

    public function setNomorDokumen(?string $nomorDokumen): static
    {
        $this->nomorDokumen = $nomorDokumen;

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

    public function setPengajuanCuti(PengajuanCuti $pengajuanCuti): static
    {
        $this->pengajuanCuti = $pengajuanCuti;

        return $this;
    }

    public function getFileExtension(): string
    {
        return strtolower(pathinfo($this->namaFile, PATHINFO_EXTENSION));
    }

    public function getFileSize(): ?int
    {
        if (!$this->pathFile || !file_exists($this->pathFile)) {
            return null;
        }

        return filesize($this->pathFile);
    }

    public function getFormattedFileSize(): string
    {
        $size = $this->getFileSize();
        
        if ($size === null) {
            return 'Unknown';
        }

        $units = ['B', 'KB', 'MB', 'GB'];
        $unitIndex = 0;
        
        while ($size >= 1024 && $unitIndex < count($units) - 1) {
            $size /= 1024;
            $unitIndex++;
        }

        return round($size, 2) . ' ' . $units[$unitIndex];
    }

    public function isImageFile(): bool
    {
        $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp'];
        return in_array($this->getFileExtension(), $imageExtensions);
    }

    public function isPdfFile(): bool
    {
        return $this->getFileExtension() === 'pdf';
    }

    public function isDocumentFile(): bool
    {
        $documentExtensions = ['doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'rtf'];
        return in_array($this->getFileExtension(), $documentExtensions);
    }

    public function fileExists(): bool
    {
        return $this->pathFile && file_exists($this->pathFile);
    }

    public function getDownloadUrl(): string
    {
        return '/download/dokumen-cuti/' . $this->id;
    }

    public function getPreviewUrl(): ?string
    {
        if ($this->isImageFile() || $this->isPdfFile()) {
            return '/preview/dokumen-cuti/' . $this->id;
        }

        return null;
    }

    public function getFileIcon(): string
    {
        if ($this->isImageFile()) {
            return 'fas fa-image';
        }
        
        if ($this->isPdfFile()) {
            return 'fas fa-file-pdf';
        }
        
        if ($this->isDocumentFile()) {
            return 'fas fa-file-word';
        }
        
        return 'fas fa-file';
    }

    public function getFileIconColor(): string
    {
        if ($this->isImageFile()) {
            return 'text-success';
        }
        
        if ($this->isPdfFile()) {
            return 'text-danger';
        }
        
        if ($this->isDocumentFile()) {
            return 'text-primary';
        }
        
        return 'text-secondary';
    }

    public function getMimeType(): string
    {
        if (!$this->pathFile || !file_exists($this->pathFile)) {
            return 'application/octet-stream';
        }

        $mimeType = mime_content_type($this->pathFile);
        return $mimeType ?: 'application/octet-stream';
    }

    public function getBaseName(): string
    {
        return pathinfo($this->namaFile, PATHINFO_FILENAME);
    }

    #[ORM\PreUpdate]
    public function setUpdatedAtValue(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function __toString(): string
    {
        return $this->namaFile ?? 'Dokumen Cuti #' . $this->id;
    }
}