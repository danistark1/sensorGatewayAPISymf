# ************************************************************
# Sequel Pro SQL dump
# Version 5446
#
# https://www.sequelpro.com/
# https://github.com/sequelpro/sequelpro
#
# Host: 192.168.4.10 (MySQL 5.5.5-10.3.23-MariaDB-0+deb10u1)
# Database: weatherStation
# Generation Time: 2021-06-24 19:17:49 +0000
# ************************************************************


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
SET NAMES utf8mb4;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;


# Dump of table sensorConfiguration
# ------------------------------------------------------------

DROP TABLE IF EXISTS `sensorConfiguration`;

CREATE TABLE `sensorConfiguration` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `config_key` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `config_value` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `config_date` date NOT NULL,
  `config_type` varchar(25) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

LOCK TABLES `sensorConfiguration` WRITE;
/*!40000 ALTER TABLE `sensorConfiguration` DISABLE KEYS */;

INSERT INTO `sensorConfiguration` (`id`, `config_key`, `config_value`, `config_date`, `config_type`)
VALUES
	(3,'sensor-bedroom-upper-temperature','24','2021-03-14','thresholds'),
	(4,'sensor-bedroom-upper-humidity','50','2021-03-14','thresholds'),
	(5,'sensor-config-bedroom','6126','2021-03-14','sensor-config'),
	(6,'sensor-config-garage','8166','2021-03-14','sensor-config'),
	(7,'weatherReport-readingReportEnabled','0','2021-03-14','readings'),
	(8,'weatherReport-notificationsReportEnabled','1','2021-03-14','readings'),
	(9,'sensor-config-living_room','15043','2021-03-27','sensor-config'),
	(10,'weatherReport-fromEmail','','2021-03-27','readings'),
	(11,'weatherReport-toEmail','','2021-03-27','readings'),
	(12,'weatherReport-emailTitleDailyReport','Weather Station Report','2021-03-27','readings'),
	(13,'weatherReport-emailTitleNotifications','Weather Station notifications','2021-03-27','readings'),
	(16,'notification_weather-firstNotificationTime','06:00:00','2021-03-27','readings'),
	(17,'notification_weather-secondNotificationTime','19:00:00','2021-03-27','readings'),
	(18,'notification_weather-thirdNotificationTime','18:00:00','2021-03-27','readings'),
	(21,'weatherReport-disableEmails','0','2021-03-27','readings'),
	(22,'sensor-bedroom-lower-temperature','17','2021-03-27','thresholds'),
	(23,'sensor-bedroom-lower-humidity','30','2021-03-27','thresholds'),
	(24,'sensor-garage-lower-humidity','30','2021-03-27','thresholds'),
	(25,'sensor-living_room-lower-humidity','30','2021-03-27','thresholds'),
	(26,'sensor-outside-lower-humidity','30','2021-03-27','thresholds'),
	(27,'sensor-basement-lower-humidity','30','2021-03-27','thresholds'),
	(28,'sensor-basement-upper-humidity','55','2021-04-13','thresholds'),
	(29,'sensor-garage-upper-humidity','60','2021-03-27','thresholds'),
	(30,'sensor-living_room-upper-humidity','60','2021-03-27','thresholds'),
	(31,'sensor-outside-upper-humidity','90','2021-03-27','thresholds'),
	(32,'sensor-outside-upper-temperature','35','2021-03-27','thresholds'),
	(33,'sensor-garage-upper-temperature','35','2021-03-27','thresholds'),
	(34,'sensor-living_room-upper-temperature','25','2021-03-27','thresholds'),
	(35,'sensor-basement-upper-temperature','24','2021-03-27','thresholds'),
	(36,'sensor-basement-lower-temperature','15','2021-03-27','thresholds'),
	(37,'sensor-garage-lower-temperature','8','2021-03-27','thresholds'),
	(38,'sensor-living_room-lower-temperature','15','2021-03-27','thresholds'),
	(39,'sensor-outside-lower-temperature','-18','2021-03-27','thresholds'),
	(41,'sensor-config-basement','3026','2021-03-27','sensor-config'),
	(42,'sensor-config-outside','12154','2021-03-27','sensor-config'),
	(43,'pruning-report-interval','1','2021-03-27','pruning'),
	(44,'pruning-records-interval','1','2021-03-27','pruning'),
	(45,'pruning-logs-interval','1','2021-03-27','pruning'),
	(46,'application-timezone','America/Toronto','2021-03-27','app-config'),
	(47,'application-debug','1','2021-03-27','app-config'),
	(48,'application-version','2.0','2021-03-27','app-config'),
	(49,'sensorReport-moistureReportEnabled','0','2021-03-14','readings'),
	(50,'sensorReport-moistureNotificationsEnabled','1','2021-03-14','readings'),
	(51,'notification_moisture-firstNotificationTime','06:00:00','2021-03-27','readings'),
	(52,'notification_moisture-secondNotificationTime','19:00:00','2021-03-27','readings'),
	(53,'notification_moisture-thirdNotificationTime','18:00:00','2021-03-27','readings'),
	(54,'sensorReport-firstReportTime','06:00:00','2021-03-27','readings'),
	(55,'sensorReport-secondReportTime','19:00:00','2021-03-27','readings'),
	(56,'pruning-moisture-interval','2','2021-03-27','pruning'),
	(57,'sensor-11790-upper-moisture','90','2021-03-14','thresholds'),
	(58,'sensor-11790-lower-moisture','16','2021-03-14','thresholds'),
	(59,'sensor-24269-upper-moisture','90','2021-03-14','thresholds'),
	(60,'sensor-24269-lower-moisture','10','2021-03-14','thresholds'),
	(61,'sensor-00e232-lower-moisture','16','2021-03-14','thresholds'),
	(62,'sensor-00e232-upper-moisture','90','2021-03-14','thresholds');

/*!40000 ALTER TABLE `sensorConfiguration` ENABLE KEYS */;
UNLOCK TABLES;



/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;
/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
