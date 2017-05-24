-- MySQL dump 10.13  Distrib 5.7.18, for Linux (x86_64)
--
-- Host: localhost    Database: btpost
-- ------------------------------------------------------
-- Server version	5.7.18-0ubuntu0.16.04.1

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `awb_batches`
--

DROP TABLE IF EXISTS `awb_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `awb_batches` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `courier_company_id` int(11) DEFAULT NULL,
  `account_id` int(11) DEFAULT NULL,
  `total_count` int(11) DEFAULT NULL,
  `valid_count` int(11) DEFAULT '0',
  `invalid_count` int(11) DEFAULT '0',
  `available_count` int(11) DEFAULT '0',
  `assigned_count` int(11) DEFAULT '0',
  `failed_count` int(11) DEFAULT '0',
  `status` enum('PENDING','PROCESSING','PROCESSED','UPDATING') NOT NULL DEFAULT 'PENDING',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=162 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `awb_batches`
--

--
-- Table structure for table `awb_batches_courier_services`
--

DROP TABLE IF EXISTS `awb_batches_courier_services`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `awb_batches_courier_services` (
  `courier_service_account_id` int(11) unsigned NOT NULL,
  `awb_batch_id` int(11) unsigned NOT NULL,
  PRIMARY KEY (`courier_service_account_id`,`awb_batch_id`),
  KEY `awb_batch_id` (`awb_batch_id`),
  CONSTRAINT `awb_batches_courier_services_ibfk_1` FOREIGN KEY (`courier_service_account_id`) REFERENCES `courier_service_accounts` (`id`),
  CONSTRAINT `awb_batches_courier_services_ibfk_2` FOREIGN KEY (`awb_batch_id`) REFERENCES `awb_batches` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `awb_batches_courier_services`
--

--
-- Table structure for table `courier_companies`
--

DROP TABLE IF EXISTS `courier_companies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `courier_companies` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(50) DEFAULT NULL,
  `short_code` varchar(6) DEFAULT NULL,
  `comments` text,
  `logo_url` varchar(200) DEFAULT NULL,
  `status` enum('ACTIVE','INACTIVE') NOT NULL DEFAULT 'ACTIVE',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=35 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `courier_companies`
--


--
-- Table structure for table `courier_references_awbs`
--

DROP TABLE IF EXISTS `courier_references_awbs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `courier_references_awbs` (
  `awb` int(11) unsigned DEFAULT NULL,
  `courier_id` varchar(40) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `courier_references_awbs`
--



--
-- Table structure for table `courier_service_accounts`
--

DROP TABLE IF EXISTS `courier_service_accounts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `courier_service_accounts` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `account_id` int(11) unsigned NOT NULL,
  `courier_service_id` int(11) unsigned NOT NULL,
  `credentials` text,
  `pincodes` text,
  `status` enum('ACTIVE','INACTIVE') NOT NULL DEFAULT 'ACTIVE',
  `awb_batch_mode` enum('USER','ADMIN') DEFAULT 'ADMIN',
  PRIMARY KEY (`id`),
  KEY `courier_service_id` (`courier_service_id`),
  CONSTRAINT `courier_service_accounts_ibfk_1` FOREIGN KEY (`courier_service_id`) REFERENCES `courier_services` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `courier_service_accounts`
--



--
-- Table structure for table `courier_services`
--

DROP TABLE IF EXISTS `courier_services`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `courier_services` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `courier_company_id` int(11) unsigned NOT NULL,
  `credentials_required_json` text,
  `pincodes` text,
  `settings` text,
  `status` enum('ACTIVE','INACTIVE') NOT NULL DEFAULT 'ACTIVE',
  `service_type` varchar(50) NOT NULL,
  `order_type` enum('prepaid','cod') DEFAULT NULL,
  `class_name` varchar(32) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `courier_company_id` (`courier_company_id`),
  CONSTRAINT `courier_services_ibfk_1` FOREIGN KEY (`courier_company_id`) REFERENCES `courier_companies` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=28 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `courier_services`
--

--
-- Table structure for table `shipment_details`
--

DROP TABLE IF EXISTS `shipment_details`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `shipment_details` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `order_ref` varchar(50) NOT NULL,
  `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `last_updated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `date_entry` date NOT NULL,
  `courier_service_account_id` int(11) unsigned NOT NULL,
  `courier_service_reference_number` int(18) unsigned NOT NULL,
  `courier_service_details` text,
  `order_meta` text,
  `status` varchar(20) DEFAULT NULL,
  `shipment_type` enum('FORWARD','REVERSE') DEFAULT 'FORWARD',
  PRIMARY KEY (`id`),
  KEY `courier_service_account_id` (`courier_service_account_id`),
  CONSTRAINT `shipment_details_ibfk_1` FOREIGN KEY (`courier_service_account_id`) REFERENCES `courier_service_accounts` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=98 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `shipment_details`
--

/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2017-05-24 20:52:08
