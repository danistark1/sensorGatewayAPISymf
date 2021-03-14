<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210313222656 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE sensorEntity (id INT AUTO_INCREMENT NOT NULL, room VARCHAR(255) NOT NULL, temperature DOUBLE PRECISION NOT NULL, humidity DOUBLE PRECISION NOT NULL, station_id INT NOT NULL, insert_date_time DATETIME NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE weatherConfiguration (id INT AUTO_INCREMENT NOT NULL, config_key VARCHAR(200) NOT NULL, config_value VARCHAR(50) DEFAULT NULL, config_date DATE NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE weatherLogger (id INT AUTO_INCREMENT NOT NULL, message VARCHAR(255) DEFAULT NULL, context LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:array)\', level INT DEFAULT NULL, level_name VARCHAR(255) DEFAULT NULL, extra LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:array)\', insert_date_time DATETIME DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE weatherReport (id INT AUTO_INCREMENT NOT NULL, email_body VARCHAR(255) DEFAULT NULL, last_sent_date DATE DEFAULT NULL, last_sent_time TIME DEFAULT NULL, last_sent_counter INT DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE sensorEntity');
        $this->addSql('DROP TABLE weatherConfiguration');
        $this->addSql('DROP TABLE weatherLogger');
        $this->addSql('DROP TABLE weatherReport');
    }
}
