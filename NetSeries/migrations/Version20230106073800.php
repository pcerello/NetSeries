<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230106073800 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE message DROP FOREIGN KEY FK_B6BD307FA76ED395');
        $this->addSql('ALTER TABLE user2 DROP FOREIGN KEY FK_1558D4EF9425DC7F');
        $this->addSql('ALTER TABLE message_category2 DROP FOREIGN KEY FK_9C0906048ACF47B4');
        $this->addSql('ALTER TABLE message_category2 DROP FOREIGN KEY FK_9C090604537A1329');
        $this->addSql('DROP TABLE country2');
        $this->addSql('DROP TABLE message');
        $this->addSql('DROP TABLE user2');
        $this->addSql('DROP TABLE message_category2');
        $this->addSql('DROP TABLE category2');
        $this->addSql('DROP TABLE category');
        $this->addSql('ALTER TABLE actor_series DROP FOREIGN KEY FK_CD56D29B10DAF24A');
        $this->addSql('ALTER TABLE actor_series DROP FOREIGN KEY FK_CD56D29B5278319C');
        $this->addSql('ALTER TABLE actor_series ADD CONSTRAINT FK_CD56D29B10DAF24A FOREIGN KEY (actor_id) REFERENCES actor (id)');
        $this->addSql('ALTER TABLE actor_series ADD CONSTRAINT FK_CD56D29B5278319C FOREIGN KEY (series_id) REFERENCES series (id)');
        $this->addSql('ALTER TABLE country_series DROP FOREIGN KEY FK_7A68EA5E5278319C');
        $this->addSql('ALTER TABLE country_series DROP FOREIGN KEY FK_7A68EA5EF92F3E70');
        $this->addSql('ALTER TABLE country_series ADD CONSTRAINT FK_7A68EA5E5278319C FOREIGN KEY (series_id) REFERENCES series (id)');
        $this->addSql('ALTER TABLE country_series ADD CONSTRAINT FK_7A68EA5EF92F3E70 FOREIGN KEY (country_id) REFERENCES country (id)');
        $this->addSql('ALTER TABLE episode CHANGE season_id season_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE external_rating CHANGE series_id series_id INT DEFAULT NULL, CHANGE source_id source_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE genre_series DROP FOREIGN KEY FK_D1A3310D4296D31F');
        $this->addSql('ALTER TABLE genre_series DROP FOREIGN KEY FK_D1A3310D5278319C');
        $this->addSql('ALTER TABLE genre_series ADD CONSTRAINT FK_D1A3310D4296D31F FOREIGN KEY (genre_id) REFERENCES genre (id)');
        $this->addSql('ALTER TABLE genre_series ADD CONSTRAINT FK_D1A3310D5278319C FOREIGN KEY (series_id) REFERENCES series (id)');
        $this->addSql('ALTER TABLE rating CHANGE series_id series_id INT DEFAULT NULL, CHANGE user_id user_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE season CHANGE series_id series_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE user CHANGE admin admin TINYINT(1) NOT NULL');
        $this->addSql('ALTER TABLE user_series DROP FOREIGN KEY FK_5F421A105278319C');
        $this->addSql('ALTER TABLE user_series DROP FOREIGN KEY FK_5F421A10A76ED395');
        $this->addSql('ALTER TABLE user_series ADD CONSTRAINT FK_5F421A105278319C FOREIGN KEY (series_id) REFERENCES series (id)');
        $this->addSql('ALTER TABLE user_series ADD CONSTRAINT FK_5F421A10A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE user_episode DROP FOREIGN KEY FK_85A702D0362B62A0');
        $this->addSql('ALTER TABLE user_episode DROP FOREIGN KEY FK_85A702D0A76ED395');
        $this->addSql('ALTER TABLE user_episode ADD CONSTRAINT FK_85A702D0362B62A0 FOREIGN KEY (episode_id) REFERENCES episode (id)');
        $this->addSql('ALTER TABLE user_episode ADD CONSTRAINT FK_85A702D0A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE country2 (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE message (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, body LONGTEXT CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, creation_date DATETIME NOT NULL, INDEX IDX_B6BD307FA76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE user2 (id INT AUTO_INCREMENT NOT NULL, country2_id INT DEFAULT NULL, email VARCHAR(180) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, roles LONGTEXT CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci` COMMENT \'(DC2Type:json)\', password VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, nom VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, UNIQUE INDEX UNIQ_1558D4EFE7927C74 (email), INDEX IDX_1558D4EF9425DC7F (country2_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE message_category2 (message_id INT NOT NULL, category2_id INT NOT NULL, INDEX IDX_9C0906048ACF47B4 (category2_id), INDEX IDX_9C090604537A1329 (message_id), PRIMARY KEY(message_id, category2_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE category2 (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE category (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE message ADD CONSTRAINT FK_B6BD307FA76ED395 FOREIGN KEY (user_id) REFERENCES user2 (id)');
        $this->addSql('ALTER TABLE user2 ADD CONSTRAINT FK_1558D4EF9425DC7F FOREIGN KEY (country2_id) REFERENCES country2 (id)');
        $this->addSql('ALTER TABLE message_category2 ADD CONSTRAINT FK_9C0906048ACF47B4 FOREIGN KEY (category2_id) REFERENCES category2 (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE message_category2 ADD CONSTRAINT FK_9C090604537A1329 FOREIGN KEY (message_id) REFERENCES message (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE external_rating CHANGE series_id series_id INT NOT NULL, CHANGE source_id source_id INT NOT NULL');
        $this->addSql('ALTER TABLE country_series DROP FOREIGN KEY FK_7A68EA5EF92F3E70');
        $this->addSql('ALTER TABLE country_series DROP FOREIGN KEY FK_7A68EA5E5278319C');
        $this->addSql('ALTER TABLE country_series ADD CONSTRAINT FK_7A68EA5EF92F3E70 FOREIGN KEY (country_id) REFERENCES country (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE country_series ADD CONSTRAINT FK_7A68EA5E5278319C FOREIGN KEY (series_id) REFERENCES series (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE user_series DROP FOREIGN KEY FK_5F421A10A76ED395');
        $this->addSql('ALTER TABLE user_series DROP FOREIGN KEY FK_5F421A105278319C');
        $this->addSql('ALTER TABLE user_series ADD CONSTRAINT FK_5F421A10A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE user_series ADD CONSTRAINT FK_5F421A105278319C FOREIGN KEY (series_id) REFERENCES series (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE user CHANGE admin admin TINYINT(1) DEFAULT 0 NOT NULL');
        $this->addSql('ALTER TABLE user_episode DROP FOREIGN KEY FK_85A702D0A76ED395');
        $this->addSql('ALTER TABLE user_episode DROP FOREIGN KEY FK_85A702D0362B62A0');
        $this->addSql('ALTER TABLE user_episode ADD CONSTRAINT FK_85A702D0A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE user_episode ADD CONSTRAINT FK_85A702D0362B62A0 FOREIGN KEY (episode_id) REFERENCES episode (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE episode CHANGE season_id season_id INT NOT NULL');
        $this->addSql('ALTER TABLE actor_series DROP FOREIGN KEY FK_CD56D29B10DAF24A');
        $this->addSql('ALTER TABLE actor_series DROP FOREIGN KEY FK_CD56D29B5278319C');
        $this->addSql('ALTER TABLE actor_series ADD CONSTRAINT FK_CD56D29B10DAF24A FOREIGN KEY (actor_id) REFERENCES actor (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE actor_series ADD CONSTRAINT FK_CD56D29B5278319C FOREIGN KEY (series_id) REFERENCES series (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE season CHANGE series_id series_id INT NOT NULL');
        $this->addSql('ALTER TABLE rating CHANGE series_id series_id INT NOT NULL, CHANGE user_id user_id INT NOT NULL');
        $this->addSql('ALTER TABLE genre_series DROP FOREIGN KEY FK_D1A3310D4296D31F');
        $this->addSql('ALTER TABLE genre_series DROP FOREIGN KEY FK_D1A3310D5278319C');
        $this->addSql('ALTER TABLE genre_series ADD CONSTRAINT FK_D1A3310D4296D31F FOREIGN KEY (genre_id) REFERENCES genre (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE genre_series ADD CONSTRAINT FK_D1A3310D5278319C FOREIGN KEY (series_id) REFERENCES series (id) ON DELETE CASCADE');
    }
}
