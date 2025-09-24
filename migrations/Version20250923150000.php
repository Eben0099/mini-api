<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250923150000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add verification and password reset fields to user table';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE user ADD is_verified TINYINT(1) NOT NULL DEFAULT 0');
        $this->addSql('ALTER TABLE user ADD verification_token VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE user ADD reset_token VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE user ADD reset_token_expires_at DATETIME DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE user DROP is_verified');
        $this->addSql('ALTER TABLE user DROP verification_token');
        $this->addSql('ALTER TABLE user DROP reset_token');
        $this->addSql('ALTER TABLE user DROP reset_token_expires_at');
    }
}
