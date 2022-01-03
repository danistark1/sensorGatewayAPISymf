-- phpMyAdmin SQL Dump
-- version 4.6.6deb5
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Jan 03, 2022 at 11:53 AM
-- Server version: 10.3.29-MariaDB-0+deb10u1
-- PHP Version: 7.3.29-1~deb10u1

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `sensorGateway`
--

--
-- Dumping data for table `sensorConfiguration`
--

INSERT INTO `sensorConfiguration` (`id`, `config_key`, `config_value`, `config_date`, `config_type`, `attributes`) VALUES
(3, 'sensor-bedroom-upper-temperature', '26', '2021-03-14', 'thresholds', NULL),
(4, 'sensor-bedroom-upper-humidity', '65', '2021-03-14', 'thresholds', NULL),
(5, 'sensor-config-bedroom', '6126', '2021-03-14', 'sensor-config', NULL),
(6, 'sensor-config-garage', '8166', '2021-03-14', 'sensor-config', NULL),
(7, 'weatherReport-readingReportEnabled', '1', '2021-03-14', 'readings', NULL),
(8, 'weatherReport-notificationsReportEnabled', '1', '2021-03-14', 'readings', NULL),
(9, 'sensor-config-living_room', '15043', '2021-03-27', 'sensor-config', NULL),
(10, 'weatherReport-fromEmail', 'vantesla1@gmail.com', '2021-03-27', 'readings', NULL),
(11, 'weatherReport-toEmail', NULL, '2021-03-27', 'readings', '[\"danistark.ca@gmail.com\"]'),
(12, 'weatherReport-emailTitleDailyReport', 'Sensor Gateway Report', '2021-03-27', 'readings', NULL),
(13, 'weatherReport-emailTitleNotifications', 'Sensor Gateway Notifications', '2021-03-27', 'readings', NULL),
(16, 'report_type_notification-firstEmailTime', '06:00:00', '2021-03-27', 'readings', NULL),
(17, 'report_type_notification-secondEmailTime', '21:00:00', '2021-03-27', 'readings', NULL),
(18, 'report_type_notification-thirdEmailTime', '18:00:00', '2021-03-27', 'readings', NULL),
(21, 'weatherReport-disableEmails', '0', '2021-03-27', 'readings', NULL),
(22, 'sensor-bedroom-lower-temperature', '17', '2021-03-27', 'thresholds', NULL),
(23, 'sensor-bedroom-lower-humidity', '30', '2021-03-27', 'thresholds', NULL),
(24, 'sensor-garage-lower-humidity', '30', '2021-03-27', 'thresholds', NULL),
(25, 'sensor-living_room-lower-humidity', '30', '2021-03-27', 'thresholds', NULL),
(26, 'sensor-outside-lower-humidity', '30', '2021-03-27', 'thresholds', NULL),
(27, 'sensor-basement-lower-humidity', '30', '2021-03-27', 'thresholds', NULL),
(28, 'sensor-basement-upper-humidity', '65', '2021-04-13', 'thresholds', NULL),
(29, 'sensor-garage-upper-humidity', '65', '2021-03-27', 'thresholds', NULL),
(30, 'sensor-living_room-upper-humidity', '65', '2021-03-27', 'thresholds', NULL),
(31, 'sensor-outside-upper-humidity', '95', '2021-03-27', 'thresholds', NULL),
(32, 'sensor-outside-upper-temperature', '35', '2021-03-27', 'thresholds', NULL),
(33, 'sensor-garage-upper-temperature', '35', '2021-03-27', 'thresholds', NULL),
(34, 'sensor-living_room-upper-temperature', '26', '2021-03-27', 'thresholds', NULL),
(35, 'sensor-basement-upper-temperature', '26', '2021-03-27', 'thresholds', NULL),
(36, 'sensor-basement-lower-temperature', '15', '2021-03-27', 'thresholds', NULL),
(37, 'sensor-garage-lower-temperature', '8', '2021-03-27', 'thresholds', NULL),
(38, 'sensor-living_room-lower-temperature', '15', '2021-03-27', 'thresholds', NULL),
(39, 'sensor-outside-lower-temperature', '-18', '2021-03-27', 'thresholds', NULL),
(41, 'sensor-config-basement', '3026', '2021-03-27', 'sensor-config', NULL),
(42, 'sensor-config-outside', '12154', '2021-03-27', 'sensor-config', NULL),
(43, 'pruning-report-interval', '1', '2021-03-27', 'pruning', NULL),
(44, 'pruning-records-interval', '1', '2021-03-27', 'pruning', NULL),
(45, 'pruning-logs-interval', '60', '2021-03-27', 'pruning', NULL),
(46, 'application-timezone', 'America/Toronto', '2021-03-27', 'app-config', NULL),
(47, 'logging-enabled', '1', '2021-03-27', 'app-config', NULL),
(48, 'application-version', '2.0', '2021-03-27', 'app-config', NULL),
(49, 'sensorReport-moistureReportEnabled', '0', '2021-03-14', 'readings', NULL),
(50, 'sensorReport-moistureNotificationsEnabled', '0', '2021-03-14', 'readings', NULL),
(51, 'report_type_moisture-firstEmailTime', '06:00:00', '2021-03-27', 'readings', NULL),
(52, 'report_type_moisture-secondEmailTime', '19:00:00', '2021-03-27', 'readings', NULL),
(53, 'report_type_moisture-thirdEmailTime', '18:00:00', '2021-03-27', 'readings', NULL),
(54, 'report_type_report-firstEmailTime', '06:00:00', '2021-03-27', 'readings', NULL),
(55, 'report_type_report-secondEmailTime', '19:00:00', '2021-03-27', 'readings', NULL),
(56, 'pruning-moisture-interval', '1', '2021-03-27', 'pruning', NULL),
(57, 'sensor-11790-upper-moisture', '90', '2021-03-14', 'thresholds', NULL),
(58, 'sensor-11790-lower-moisture', '16', '2021-03-14', 'thresholds', NULL),
(59, 'sensor-24269-upper-moisture', '90', '2021-03-14', 'thresholds', NULL),
(60, 'sensor-24269-lower-moisture', '10', '2021-03-14', 'thresholds', NULL),
(61, 'sensor-00e232-lower-moisture', '16', '2021-03-14', 'thresholds', NULL),
(62, 'sensor-00e232-upper-moisture', '90', '2021-03-14', 'thresholds', NULL),
(63, 'email-logging-enabled', '1', '2021-10-30', 'app-config', NULL),
(64, 'email-logging-level', NULL, '2021-10-30', 'app-config', '[\"warning\",\"critical\",\"info\"]'),
(65, 'admin-email', 'danistark.ca@gmail.com', '2021-10-30', 'app-config', NULL),
(66, 'logging-level', NULL, '2021-11-02', 'app-config', '[\"critical\",\"debug\",\"warning\",\"info\"]'),
(139, 'resource-listener-lock', '0', '2021-11-07', 'app-config', NULL);

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
