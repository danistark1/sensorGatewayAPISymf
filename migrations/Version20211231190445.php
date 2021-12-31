<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20211231190445 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE sensorConfiguration (id INT AUTO_INCREMENT NOT NULL, config_key VARCHAR(200) NOT NULL, config_value VARCHAR(250) DEFAULT NULL, config_date DATE NOT NULL, config_type VARCHAR(25) NOT NULL, attributes LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:json)\', PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE sensorEntity (id INT AUTO_INCREMENT NOT NULL, room VARCHAR(255) NOT NULL, temperature DOUBLE PRECISION NOT NULL, humidity DOUBLE PRECISION NOT NULL, station_id INT NOT NULL, insert_date_time DATETIME NOT NULL, battery_status INT DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE sensorHistoricReadings (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(100) NOT NULL, lowest_reading DOUBLE PRECISION DEFAULT NULL, highest_reading DOUBLE PRECISION DEFAULT NULL, type VARCHAR(100) NOT NULL, insert_date_time DATE DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE sensorLogger (id INT AUTO_INCREMENT NOT NULL, message VARCHAR(255) DEFAULT NULL, context LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:array)\', level INT DEFAULT NULL, level_name VARCHAR(255) DEFAULT NULL, extra LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:array)\', insert_date_time DATETIME DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE sensorMoisture (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(25) NOT NULL, sensorID VARCHAR(25) NOT NULL, sensorReading VARCHAR(20) NOT NULL, insertDateTime DATETIME NOT NULL, batteryStatus VARCHAR(10) DEFAULT NULL, sensorLocation VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE sensorReport (id INT AUTO_INCREMENT NOT NULL, email_body LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:array)\', last_sent_date DATE DEFAULT NULL, last_sent_time TIME DEFAULT NULL, last_sent_counter INT DEFAULT NULL, report_type VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE sensorConfiguration');
        $this->addSql('DROP TABLE sensorEntity');
        $this->addSql('DROP TABLE sensorHistoricReadings');
        $this->addSql('DROP TABLE sensorLogger');
        $this->addSql('DROP TABLE sensorMoisture');
        $this->addSql('DROP TABLE sensorReport');
    }
}
