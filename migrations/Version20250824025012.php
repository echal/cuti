<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250824025012 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        
        // Tambah kolom email dan roles sebagai nullable dulu
        $this->addSql('ALTER TABLE user ADD email VARCHAR(255) DEFAULT NULL, ADD roles JSON NOT NULL DEFAULT \'["ROLE_USER"]\' ');
        
        // Update email dengan nilai default berdasarkan NIP
        $this->addSql('UPDATE user SET email = CONCAT(nip, "@kemenag.go.id") WHERE email IS NULL');
        
        // Ubah email menjadi NOT NULL
        $this->addSql('ALTER TABLE user MODIFY email VARCHAR(255) NOT NULL');
        
        // Buat unique index
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8D93D649E7927C74 ON user (email)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX UNIQ_8D93D649E7927C74 ON user');
        $this->addSql('ALTER TABLE user DROP email, DROP roles');
    }
}
