<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250825094754 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Tambah field nomor_surat dan alamat_cuti ke tabel pengajuan_cuti, serta tabel konfigurasi untuk data kepala kantor';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE konfigurasi (id INT AUTO_INCREMENT NOT NULL, `key` VARCHAR(50) NOT NULL, value LONGTEXT NOT NULL, description VARCHAR(100) DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, UNIQUE INDEX UNIQ_38096FC58A90ABA9 (`key`), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE pengajuan_cuti ADD nomor_surat VARCHAR(100) DEFAULT NULL, ADD alamat_cuti LONGTEXT NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE konfigurasi');
        $this->addSql('ALTER TABLE pengajuan_cuti DROP nomor_surat, DROP alamat_cuti');
    }
}
