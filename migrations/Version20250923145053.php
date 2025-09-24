<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250923145053 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE media DROP FOREIGN KEY FK_6A2CA10C4066877A');
        $this->addSql('DROP INDEX IDX_6A2CA10C4066877A ON media');
        $this->addSql('ALTER TABLE media ADD original_name VARCHAR(255) NOT NULL, ADD size_bytes INT NOT NULL, DROP salon_id, DROP filename, DROP original_filename, DROP size, DROP type, DROP is_active, CHANGE stylist_id stylist_id INT NOT NULL, CHANGE mime_type mime_type VARCHAR(100) NOT NULL, CHANGE path path VARCHAR(500) NOT NULL');
        $this->addSql('ALTER TABLE review DROP INDEX IDX_794381C63301C60, ADD UNIQUE INDEX UNIQ_794381C63301C60 (booking_id)');
        $this->addSql('ALTER TABLE review DROP FOREIGN KEY FK_794381C6A76ED395');
        $this->addSql('ALTER TABLE review DROP FOREIGN KEY FK_794381C64C91BDE4');
        $this->addSql('DROP INDEX IDX_794381C6A76ED395 ON review');
        $this->addSql('DROP INDEX IDX_794381C64C91BDE4 ON review');
        $this->addSql('ALTER TABLE review ADD client_id INT NOT NULL, ADD status VARCHAR(20) NOT NULL, DROP user_id, DROP salon_id, DROP is_verified, DROP is_active, CHANGE stylist_id stylist_id INT NOT NULL, CHANGE booking_id booking_id INT NOT NULL, CHANGE updated_at updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE review ADD CONSTRAINT FK_794381C619EB6921 FOREIGN KEY (client_id) REFERENCES `user` (id)');
        $this->addSql('CREATE INDEX IDX_794381C619EB6921 ON review (client_id)');
        $this->addSql('ALTER TABLE salon ADD owner_id INT NOT NULL, ADD slug VARCHAR(255) NOT NULL, ADD lat DOUBLE PRECISION DEFAULT NULL, ADD lng DOUBLE PRECISION DEFAULT NULL, DROP description, DROP postal_code, DROP country, DROP phone, DROP email, DROP website, DROP opening_time, DROP closing_time, DROP is_active, CHANGE address address LONGTEXT NOT NULL, CHANGE working_days open_hours JSON NOT NULL');
        $this->addSql('ALTER TABLE salon ADD CONSTRAINT FK_F268F4177E3C61F9 FOREIGN KEY (owner_id) REFERENCES `user` (id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_F268F417989D9B62 ON salon (slug)');
        $this->addSql('CREATE INDEX IDX_F268F4177E3C61F9 ON salon (owner_id)');
        $this->addSql('ALTER TABLE service DROP FOREIGN KEY FK_E19D9AD24066877A');
        $this->addSql('DROP INDEX IDX_E19D9AD24066877A ON service');
        $this->addSql('ALTER TABLE service ADD duration_minutes INT NOT NULL, ADD price_cents INT NOT NULL, DROP stylist_id, DROP price, DROP duration, DROP category, DROP is_active');
        $this->addSql('ALTER TABLE stylist ADD user_id INT NOT NULL, ADD languages VARCHAR(255) NOT NULL, ADD skills VARCHAR(255) DEFAULT NULL, DROP first_name, DROP last_name, DROP email, DROP phone, DROP bio, DROP specialties, DROP rating, DROP experience_years, DROP is_active');
        $this->addSql('ALTER TABLE stylist ADD CONSTRAINT FK_4111FFA5A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)');
        $this->addSql('CREATE INDEX IDX_4111FFA5A76ED395 ON stylist (user_id)');
        $this->addSql('ALTER TABLE user ADD email_verified_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE waitlist_entry DROP FOREIGN KEY FK_67447574A76ED395');
        $this->addSql('ALTER TABLE waitlist_entry DROP FOREIGN KEY FK_674475748D6C9A53');
        $this->addSql('DROP INDEX IDX_67447574A76ED395 ON waitlist_entry');
        $this->addSql('DROP INDEX IDX_674475748D6C9A53 ON waitlist_entry');
        $this->addSql('ALTER TABLE waitlist_entry ADD salon_id INT NOT NULL, ADD client_id INT NOT NULL, ADD desired_start_range_start DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', ADD desired_start_range_end DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', DROP user_id, DROP preferred_stylist_id, DROP preferred_date, DROP preferred_time, DROP position, DROP is_active');
        $this->addSql('ALTER TABLE waitlist_entry ADD CONSTRAINT FK_674475744C91BDE4 FOREIGN KEY (salon_id) REFERENCES salon (id)');
        $this->addSql('ALTER TABLE waitlist_entry ADD CONSTRAINT FK_6744757419EB6921 FOREIGN KEY (client_id) REFERENCES `user` (id)');
        $this->addSql('CREATE INDEX IDX_674475744C91BDE4 ON waitlist_entry (salon_id)');
        $this->addSql('CREATE INDEX IDX_6744757419EB6921 ON waitlist_entry (client_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE media ADD salon_id INT DEFAULT NULL, ADD original_filename VARCHAR(255) NOT NULL, ADD size INT DEFAULT NULL, ADD type VARCHAR(50) NOT NULL, ADD is_active TINYINT(1) NOT NULL, DROP size_bytes, CHANGE stylist_id stylist_id INT DEFAULT NULL, CHANGE path path VARCHAR(255) DEFAULT NULL, CHANGE mime_type mime_type VARCHAR(10) NOT NULL, CHANGE original_name filename VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE review DROP INDEX UNIQ_794381C63301C60, ADD INDEX IDX_794381C63301C60 (booking_id)');
        $this->addSql('ALTER TABLE review DROP FOREIGN KEY FK_794381C619EB6921');
        $this->addSql('DROP INDEX IDX_794381C619EB6921 ON review');
        $this->addSql('ALTER TABLE review ADD salon_id INT NOT NULL, ADD is_verified TINYINT(1) NOT NULL, ADD is_active TINYINT(1) NOT NULL, DROP status, CHANGE booking_id booking_id INT DEFAULT NULL, CHANGE stylist_id stylist_id INT DEFAULT NULL, CHANGE updated_at updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE client_id user_id INT NOT NULL');
        $this->addSql('ALTER TABLE review ADD CONSTRAINT FK_794381C6A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE review ADD CONSTRAINT FK_794381C64C91BDE4 FOREIGN KEY (salon_id) REFERENCES salon (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('CREATE INDEX IDX_794381C6A76ED395 ON review (user_id)');
        $this->addSql('CREATE INDEX IDX_794381C64C91BDE4 ON review (salon_id)');
        $this->addSql('ALTER TABLE salon DROP FOREIGN KEY FK_F268F4177E3C61F9');
        $this->addSql('DROP INDEX UNIQ_F268F417989D9B62 ON salon');
        $this->addSql('DROP INDEX IDX_F268F4177E3C61F9 ON salon');
        $this->addSql('ALTER TABLE salon ADD description LONGTEXT DEFAULT NULL, ADD postal_code VARCHAR(20) NOT NULL, ADD country VARCHAR(100) NOT NULL, ADD phone VARCHAR(20) DEFAULT NULL, ADD email VARCHAR(180) DEFAULT NULL, ADD website VARCHAR(255) DEFAULT NULL, ADD opening_time TIME NOT NULL, ADD closing_time TIME NOT NULL, ADD is_active TINYINT(1) NOT NULL, DROP owner_id, DROP slug, DROP lat, DROP lng, CHANGE address address VARCHAR(255) NOT NULL, CHANGE open_hours working_days JSON NOT NULL');
        $this->addSql('ALTER TABLE service ADD stylist_id INT NOT NULL, ADD price NUMERIC(8, 2) NOT NULL, ADD duration INT NOT NULL, ADD category VARCHAR(50) NOT NULL, ADD is_active TINYINT(1) NOT NULL, DROP duration_minutes, DROP price_cents');
        $this->addSql('ALTER TABLE service ADD CONSTRAINT FK_E19D9AD24066877A FOREIGN KEY (stylist_id) REFERENCES stylist (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('CREATE INDEX IDX_E19D9AD24066877A ON service (stylist_id)');
        $this->addSql('ALTER TABLE stylist DROP FOREIGN KEY FK_4111FFA5A76ED395');
        $this->addSql('DROP INDEX IDX_4111FFA5A76ED395 ON stylist');
        $this->addSql('ALTER TABLE stylist ADD first_name VARCHAR(100) NOT NULL, ADD last_name VARCHAR(100) NOT NULL, ADD email VARCHAR(180) DEFAULT NULL, ADD phone VARCHAR(20) DEFAULT NULL, ADD bio LONGTEXT DEFAULT NULL, ADD specialties JSON NOT NULL, ADD rating NUMERIC(3, 1) DEFAULT NULL, ADD experience_years INT DEFAULT NULL, ADD is_active TINYINT(1) NOT NULL, DROP user_id, DROP languages, DROP skills');
        $this->addSql('ALTER TABLE `user` DROP email_verified_at');
        $this->addSql('ALTER TABLE waitlist_entry DROP FOREIGN KEY FK_674475744C91BDE4');
        $this->addSql('ALTER TABLE waitlist_entry DROP FOREIGN KEY FK_6744757419EB6921');
        $this->addSql('DROP INDEX IDX_674475744C91BDE4 ON waitlist_entry');
        $this->addSql('DROP INDEX IDX_6744757419EB6921 ON waitlist_entry');
        $this->addSql('ALTER TABLE waitlist_entry ADD user_id INT NOT NULL, ADD preferred_stylist_id INT DEFAULT NULL, ADD preferred_date DATETIME NOT NULL, ADD preferred_time TIME NOT NULL, ADD position INT NOT NULL, ADD is_active TINYINT(1) NOT NULL, DROP salon_id, DROP client_id, DROP desired_start_range_start, DROP desired_start_range_end');
        $this->addSql('ALTER TABLE waitlist_entry ADD CONSTRAINT FK_67447574A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE waitlist_entry ADD CONSTRAINT FK_674475748D6C9A53 FOREIGN KEY (preferred_stylist_id) REFERENCES stylist (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('CREATE INDEX IDX_67447574A76ED395 ON waitlist_entry (user_id)');
        $this->addSql('CREATE INDEX IDX_674475748D6C9A53 ON waitlist_entry (preferred_stylist_id)');
    }
}
