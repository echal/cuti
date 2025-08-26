<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250825105457 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Tambah field telp ke tabel user dan perbaikan index konfigurasi';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX uniq_38096fc58a90aba9 ON konfigurasi');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_38096FC54E645A7E ON konfigurasi (`key`)');
        $this->addSql('ALTER TABLE user ADD telp VARCHAR(15) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX uniq_38096fc54e645a7e ON konfigurasi');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_38096FC58A90ABA9 ON konfigurasi (`key`)');
        $this->addSql('ALTER TABLE user DROP telp');
    }
}
