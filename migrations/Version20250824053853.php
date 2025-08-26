<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250824053853 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // Fix user data for security implementation
        
        // Update empty emails dengan NIP-based email
        $this->addSql("UPDATE user SET email = CONCAT(nip, '@kemenag.go.id') WHERE email IS NULL OR email = ''");
        
        // Create unique index for email (skip if already exists)
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8D93D649E7927C74 ON user (email)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX UNIQ_8D93D649E7927C74 ON user');
    }
}
