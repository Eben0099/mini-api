<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250923143903 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE availability_exception (id INT AUTO_INCREMENT NOT NULL, salon_id INT NOT NULL, stylist_id INT DEFAULT NULL, start_date DATETIME NOT NULL, end_date DATETIME NOT NULL, reason VARCHAR(255) NOT NULL, is_recurring TINYINT(1) NOT NULL, recurring_pattern JSON DEFAULT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_5E25FAB4C91BDE4 (salon_id), INDEX IDX_5E25FAB4066877A (stylist_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE booking (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, salon_id INT NOT NULL, stylist_id INT NOT NULL, service_id INT NOT NULL, booking_date DATETIME NOT NULL, start_time DATETIME NOT NULL, end_time DATETIME NOT NULL, status VARCHAR(20) NOT NULL, notes LONGTEXT DEFAULT NULL, total_price NUMERIC(8, 2) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_E00CEDDEA76ED395 (user_id), INDEX IDX_E00CEDDE4C91BDE4 (salon_id), INDEX IDX_E00CEDDE4066877A (stylist_id), INDEX IDX_E00CEDDEED5CA9E6 (service_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE media (id INT AUTO_INCREMENT NOT NULL, salon_id INT DEFAULT NULL, stylist_id INT DEFAULT NULL, filename VARCHAR(255) NOT NULL, original_filename VARCHAR(255) NOT NULL, mime_type VARCHAR(10) NOT NULL, path VARCHAR(255) DEFAULT NULL, size INT DEFAULT NULL, type VARCHAR(50) NOT NULL, is_active TINYINT(1) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_6A2CA10C4C91BDE4 (salon_id), INDEX IDX_6A2CA10C4066877A (stylist_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE review (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, salon_id INT NOT NULL, stylist_id INT DEFAULT NULL, booking_id INT DEFAULT NULL, rating INT NOT NULL, comment LONGTEXT DEFAULT NULL, is_verified TINYINT(1) NOT NULL, is_active TINYINT(1) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_794381C6A76ED395 (user_id), INDEX IDX_794381C64C91BDE4 (salon_id), INDEX IDX_794381C64066877A (stylist_id), INDEX IDX_794381C63301C60 (booking_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE salon (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, address VARCHAR(255) NOT NULL, city VARCHAR(100) NOT NULL, postal_code VARCHAR(20) NOT NULL, country VARCHAR(100) NOT NULL, phone VARCHAR(20) DEFAULT NULL, email VARCHAR(180) DEFAULT NULL, website VARCHAR(255) DEFAULT NULL, opening_time TIME NOT NULL, closing_time TIME NOT NULL, working_days JSON NOT NULL, is_active TINYINT(1) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE service (id INT AUTO_INCREMENT NOT NULL, salon_id INT NOT NULL, stylist_id INT NOT NULL, name VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, price NUMERIC(8, 2) NOT NULL, duration INT NOT NULL, category VARCHAR(50) NOT NULL, is_active TINYINT(1) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_E19D9AD24C91BDE4 (salon_id), INDEX IDX_E19D9AD24066877A (stylist_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE stylist (id INT AUTO_INCREMENT NOT NULL, salon_id INT NOT NULL, first_name VARCHAR(100) NOT NULL, last_name VARCHAR(100) NOT NULL, email VARCHAR(180) DEFAULT NULL, phone VARCHAR(20) DEFAULT NULL, bio LONGTEXT DEFAULT NULL, specialties JSON NOT NULL, rating NUMERIC(3, 1) DEFAULT NULL, experience_years INT DEFAULT NULL, is_active TINYINT(1) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_4111FFA54C91BDE4 (salon_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE `user` (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(180) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, first_name VARCHAR(100) NOT NULL, last_name VARCHAR(100) NOT NULL, phone VARCHAR(20) DEFAULT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', UNIQUE INDEX UNIQ_8D93D649E7927C74 (email), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE waitlist_entry (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, service_id INT NOT NULL, preferred_stylist_id INT DEFAULT NULL, preferred_date DATETIME NOT NULL, preferred_time TIME NOT NULL, position INT NOT NULL, is_active TINYINT(1) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_67447574A76ED395 (user_id), INDEX IDX_67447574ED5CA9E6 (service_id), INDEX IDX_674475748D6C9A53 (preferred_stylist_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', available_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', delivered_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_75EA56E0FB7336F0 (queue_name), INDEX IDX_75EA56E0E3BD61CE (available_at), INDEX IDX_75EA56E016BA31DB (delivered_at), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE availability_exception ADD CONSTRAINT FK_5E25FAB4C91BDE4 FOREIGN KEY (salon_id) REFERENCES salon (id)');
        $this->addSql('ALTER TABLE availability_exception ADD CONSTRAINT FK_5E25FAB4066877A FOREIGN KEY (stylist_id) REFERENCES stylist (id)');
        $this->addSql('ALTER TABLE booking ADD CONSTRAINT FK_E00CEDDEA76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE booking ADD CONSTRAINT FK_E00CEDDE4C91BDE4 FOREIGN KEY (salon_id) REFERENCES salon (id)');
        $this->addSql('ALTER TABLE booking ADD CONSTRAINT FK_E00CEDDE4066877A FOREIGN KEY (stylist_id) REFERENCES stylist (id)');
        $this->addSql('ALTER TABLE booking ADD CONSTRAINT FK_E00CEDDEED5CA9E6 FOREIGN KEY (service_id) REFERENCES service (id)');
        $this->addSql('ALTER TABLE media ADD CONSTRAINT FK_6A2CA10C4C91BDE4 FOREIGN KEY (salon_id) REFERENCES salon (id)');
        $this->addSql('ALTER TABLE media ADD CONSTRAINT FK_6A2CA10C4066877A FOREIGN KEY (stylist_id) REFERENCES stylist (id)');
        $this->addSql('ALTER TABLE review ADD CONSTRAINT FK_794381C6A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE review ADD CONSTRAINT FK_794381C64C91BDE4 FOREIGN KEY (salon_id) REFERENCES salon (id)');
        $this->addSql('ALTER TABLE review ADD CONSTRAINT FK_794381C64066877A FOREIGN KEY (stylist_id) REFERENCES stylist (id)');
        $this->addSql('ALTER TABLE review ADD CONSTRAINT FK_794381C63301C60 FOREIGN KEY (booking_id) REFERENCES booking (id)');
        $this->addSql('ALTER TABLE service ADD CONSTRAINT FK_E19D9AD24C91BDE4 FOREIGN KEY (salon_id) REFERENCES salon (id)');
        $this->addSql('ALTER TABLE service ADD CONSTRAINT FK_E19D9AD24066877A FOREIGN KEY (stylist_id) REFERENCES stylist (id)');
        $this->addSql('ALTER TABLE stylist ADD CONSTRAINT FK_4111FFA54C91BDE4 FOREIGN KEY (salon_id) REFERENCES salon (id)');
        $this->addSql('ALTER TABLE waitlist_entry ADD CONSTRAINT FK_67447574A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE waitlist_entry ADD CONSTRAINT FK_67447574ED5CA9E6 FOREIGN KEY (service_id) REFERENCES service (id)');
        $this->addSql('ALTER TABLE waitlist_entry ADD CONSTRAINT FK_674475748D6C9A53 FOREIGN KEY (preferred_stylist_id) REFERENCES stylist (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE availability_exception DROP FOREIGN KEY FK_5E25FAB4C91BDE4');
        $this->addSql('ALTER TABLE availability_exception DROP FOREIGN KEY FK_5E25FAB4066877A');
        $this->addSql('ALTER TABLE booking DROP FOREIGN KEY FK_E00CEDDEA76ED395');
        $this->addSql('ALTER TABLE booking DROP FOREIGN KEY FK_E00CEDDE4C91BDE4');
        $this->addSql('ALTER TABLE booking DROP FOREIGN KEY FK_E00CEDDE4066877A');
        $this->addSql('ALTER TABLE booking DROP FOREIGN KEY FK_E00CEDDEED5CA9E6');
        $this->addSql('ALTER TABLE media DROP FOREIGN KEY FK_6A2CA10C4C91BDE4');
        $this->addSql('ALTER TABLE media DROP FOREIGN KEY FK_6A2CA10C4066877A');
        $this->addSql('ALTER TABLE review DROP FOREIGN KEY FK_794381C6A76ED395');
        $this->addSql('ALTER TABLE review DROP FOREIGN KEY FK_794381C64C91BDE4');
        $this->addSql('ALTER TABLE review DROP FOREIGN KEY FK_794381C64066877A');
        $this->addSql('ALTER TABLE review DROP FOREIGN KEY FK_794381C63301C60');
        $this->addSql('ALTER TABLE service DROP FOREIGN KEY FK_E19D9AD24C91BDE4');
        $this->addSql('ALTER TABLE service DROP FOREIGN KEY FK_E19D9AD24066877A');
        $this->addSql('ALTER TABLE stylist DROP FOREIGN KEY FK_4111FFA54C91BDE4');
        $this->addSql('ALTER TABLE waitlist_entry DROP FOREIGN KEY FK_67447574A76ED395');
        $this->addSql('ALTER TABLE waitlist_entry DROP FOREIGN KEY FK_67447574ED5CA9E6');
        $this->addSql('ALTER TABLE waitlist_entry DROP FOREIGN KEY FK_674475748D6C9A53');
        $this->addSql('DROP TABLE availability_exception');
        $this->addSql('DROP TABLE booking');
        $this->addSql('DROP TABLE media');
        $this->addSql('DROP TABLE review');
        $this->addSql('DROP TABLE salon');
        $this->addSql('DROP TABLE service');
        $this->addSql('DROP TABLE stylist');
        $this->addSql('DROP TABLE `user`');
        $this->addSql('DROP TABLE waitlist_entry');
        $this->addSql('DROP TABLE messenger_messages');
    }
}
