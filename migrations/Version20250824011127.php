<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250824011127 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE dokumen_cuti (id INT AUTO_INCREMENT NOT NULL, nama_file VARCHAR(255) NOT NULL, path_file VARCHAR(500) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, pengajuan_cuti_id INT NOT NULL, UNIQUE INDEX UNIQ_1ED7FDBB2224929F (pengajuan_cuti_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE hak_cuti (id INT AUTO_INCREMENT NOT NULL, tahun INT NOT NULL, hak_tahunan INT NOT NULL, terpakai INT NOT NULL, sisa INT NOT NULL, carry_over TINYINT(1) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, user_id INT NOT NULL, INDEX IDX_970913EDA76ED395 (user_id), UNIQUE INDEX user_tahun_unique (user_id, tahun), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE jenis_cuti (id INT AUTO_INCREMENT NOT NULL, kode VARCHAR(10) NOT NULL, nama VARCHAR(255) NOT NULL, deskripsi LONGTEXT DEFAULT NULL, durasi_max INT DEFAULT NULL, dasar_hukum VARCHAR(500) DEFAULT NULL, tersedi_untuk VARCHAR(10) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, UNIQUE INDEX UNIQ_DB9533B6B2A11877 (kode), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE pejabat (id INT AUTO_INCREMENT NOT NULL, nama VARCHAR(255) NOT NULL, nip VARCHAR(255) DEFAULT NULL, jabatan VARCHAR(255) NOT NULL, status VARCHAR(20) NOT NULL, mulai_menjabat DATE NOT NULL, selesai_menjabat DATE DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, unit_kerja_id INT DEFAULT NULL, INDEX IDX_19F89EBBCBE1A536 (unit_kerja_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE pengajuan_cuti (id INT AUTO_INCREMENT NOT NULL, tanggal_pengajuan DATE NOT NULL, tanggal_mulai DATE NOT NULL, tanggal_selesai DATE NOT NULL, lama_cuti INT NOT NULL, alasan LONGTEXT NOT NULL, status VARCHAR(20) NOT NULL, file_pendukung VARCHAR(255) DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, user_id INT NOT NULL, jenis_cuti_id INT NOT NULL, pejabat_penyetuju_id INT DEFAULT NULL, pejabat_atasan_id INT DEFAULT NULL, INDEX IDX_22B482DBA76ED395 (user_id), INDEX IDX_22B482DBD39C45B2 (jenis_cuti_id), INDEX IDX_22B482DB5746B162 (pejabat_penyetuju_id), INDEX IDX_22B482DB24C27A6F (pejabat_atasan_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE riwayat_cuti (id INT AUTO_INCREMENT NOT NULL, aksi VARCHAR(20) NOT NULL, tanggal_aksi DATETIME NOT NULL, catatan LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, pengajuan_cuti_id INT NOT NULL, user_id INT NOT NULL, INDEX IDX_770A37C02224929F (pengajuan_cuti_id), INDEX IDX_770A37C0A76ED395 (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE unit_kerja (id INT AUTO_INCREMENT NOT NULL, kode VARCHAR(20) NOT NULL, nama VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, UNIQUE INDEX UNIQ_5DD8A6A6B2A11877 (kode), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE user (id INT AUTO_INCREMENT NOT NULL, nip VARCHAR(255) DEFAULT NULL, nama VARCHAR(255) NOT NULL, jenis_kelamin VARCHAR(1) NOT NULL, status_kepegawaian VARCHAR(10) NOT NULL, jabatan VARCHAR(255) NOT NULL, tmt_cpns DATE DEFAULT NULL, tmt_pns DATE DEFAULT NULL, jumlah_anak INT NOT NULL, status_pegawai VARCHAR(20) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, unit_kerja_id INT NOT NULL, UNIQUE INDEX UNIQ_8D93D64959329EEA (nip), INDEX IDX_8D93D649CBE1A536 (unit_kerja_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE dokumen_cuti ADD CONSTRAINT FK_1ED7FDBB2224929F FOREIGN KEY (pengajuan_cuti_id) REFERENCES pengajuan_cuti (id)');
        $this->addSql('ALTER TABLE hak_cuti ADD CONSTRAINT FK_970913EDA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE pejabat ADD CONSTRAINT FK_19F89EBBCBE1A536 FOREIGN KEY (unit_kerja_id) REFERENCES unit_kerja (id)');
        $this->addSql('ALTER TABLE pengajuan_cuti ADD CONSTRAINT FK_22B482DBA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE pengajuan_cuti ADD CONSTRAINT FK_22B482DBD39C45B2 FOREIGN KEY (jenis_cuti_id) REFERENCES jenis_cuti (id)');
        $this->addSql('ALTER TABLE pengajuan_cuti ADD CONSTRAINT FK_22B482DB5746B162 FOREIGN KEY (pejabat_penyetuju_id) REFERENCES pejabat (id)');
        $this->addSql('ALTER TABLE pengajuan_cuti ADD CONSTRAINT FK_22B482DB24C27A6F FOREIGN KEY (pejabat_atasan_id) REFERENCES pejabat (id)');
        $this->addSql('ALTER TABLE riwayat_cuti ADD CONSTRAINT FK_770A37C02224929F FOREIGN KEY (pengajuan_cuti_id) REFERENCES pengajuan_cuti (id)');
        $this->addSql('ALTER TABLE riwayat_cuti ADD CONSTRAINT FK_770A37C0A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE user ADD CONSTRAINT FK_8D93D649CBE1A536 FOREIGN KEY (unit_kerja_id) REFERENCES unit_kerja (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE dokumen_cuti DROP FOREIGN KEY FK_1ED7FDBB2224929F');
        $this->addSql('ALTER TABLE hak_cuti DROP FOREIGN KEY FK_970913EDA76ED395');
        $this->addSql('ALTER TABLE pejabat DROP FOREIGN KEY FK_19F89EBBCBE1A536');
        $this->addSql('ALTER TABLE pengajuan_cuti DROP FOREIGN KEY FK_22B482DBA76ED395');
        $this->addSql('ALTER TABLE pengajuan_cuti DROP FOREIGN KEY FK_22B482DBD39C45B2');
        $this->addSql('ALTER TABLE pengajuan_cuti DROP FOREIGN KEY FK_22B482DB5746B162');
        $this->addSql('ALTER TABLE pengajuan_cuti DROP FOREIGN KEY FK_22B482DB24C27A6F');
        $this->addSql('ALTER TABLE riwayat_cuti DROP FOREIGN KEY FK_770A37C02224929F');
        $this->addSql('ALTER TABLE riwayat_cuti DROP FOREIGN KEY FK_770A37C0A76ED395');
        $this->addSql('ALTER TABLE user DROP FOREIGN KEY FK_8D93D649CBE1A536');
        $this->addSql('DROP TABLE dokumen_cuti');
        $this->addSql('DROP TABLE hak_cuti');
        $this->addSql('DROP TABLE jenis_cuti');
        $this->addSql('DROP TABLE pejabat');
        $this->addSql('DROP TABLE pengajuan_cuti');
        $this->addSql('DROP TABLE riwayat_cuti');
        $this->addSql('DROP TABLE unit_kerja');
        $this->addSql('DROP TABLE user');
    }
}
