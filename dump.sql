-- MySQL dump 10.13  Distrib 5.7.25, for Linux (x86_64)
--
-- Host: localhost    Database: testtask
-- ------------------------------------------------------
-- Server version	5.7.25-0ubuntu0.16.04.2

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
-- Table structure for table `cms3_apiship_orders`
--

DROP TABLE IF EXISTS `cms3_apiship_orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cms3_apiship_orders` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `number` int(11) unsigned NOT NULL,
  `umi_order_ref_number` int(11) unsigned NOT NULL,
  `provider_order_ref_number` varchar(255) DEFAULT NULL,
  `status` enum('pending','delivered','delivering','deliveryCanceled','lost','notApplicable','onPointIn','onPointOut','onWay','partialReturn','problem','readyForRecipient','returned','returnedFromDelivery','returning','returnReady','unknown','uploaded','uploading','uploadingError') NOT NULL,
  PRIMARY KEY (`id`),
  KEY `number` (`number`),
  KEY `umi_order_ref_number` (`umi_order_ref_number`),
  KEY `provider_order_ref_number` (`provider_order_ref_number`),
  KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cms3_apiship_orders`
--

LOCK TABLES `cms3_apiship_orders` WRITE;
/*!40000 ALTER TABLE `cms3_apiship_orders` DISABLE KEYS */;
/*!40000 ALTER TABLE `cms3_apiship_orders` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cms3_appointment_employee_schedule`
--

DROP TABLE IF EXISTS `cms3_appointment_employee_schedule`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cms3_appointment_employee_schedule` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `employee_id` int(11) NOT NULL,
  `day` enum('0','1','2','3','4','5','6') NOT NULL,
  `time_start` time NOT NULL,
  `time_end` time NOT NULL,
  PRIMARY KEY (`id`),
  KEY `employee_id` (`employee_id`),
  CONSTRAINT `employee schedule to employees` FOREIGN KEY (`employee_id`) REFERENCES `cms3_appointment_employees` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cms3_appointment_employee_schedule`
--

LOCK TABLES `cms3_appointment_employee_schedule` WRITE;
/*!40000 ALTER TABLE `cms3_appointment_employee_schedule` DISABLE KEYS */;
/*!40000 ALTER TABLE `cms3_appointment_employee_schedule` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cms3_appointment_employees`
--

DROP TABLE IF EXISTS `cms3_appointment_employees`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cms3_appointment_employees` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `photo` varchar(500) NOT NULL,
  `description` mediumtext NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cms3_appointment_employees`
--

LOCK TABLES `cms3_appointment_employees` WRITE;
/*!40000 ALTER TABLE `cms3_appointment_employees` DISABLE KEYS */;
/*!40000 ALTER TABLE `cms3_appointment_employees` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cms3_appointment_employees_services`
--

DROP TABLE IF EXISTS `cms3_appointment_employees_services`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cms3_appointment_employees_services` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `employee_id` int(11) NOT NULL,
  `service_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `employee_id and service_id` (`employee_id`,`service_id`),
  KEY `employees services to services` (`service_id`),
  CONSTRAINT `employees services to employees` FOREIGN KEY (`employee_id`) REFERENCES `cms3_appointment_employees` (`id`) ON DELETE CASCADE,
  CONSTRAINT `employees services to services` FOREIGN KEY (`service_id`) REFERENCES `cms3_appointment_services` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cms3_appointment_employees_services`
--

LOCK TABLES `cms3_appointment_employees_services` WRITE;
/*!40000 ALTER TABLE `cms3_appointment_employees_services` DISABLE KEYS */;
/*!40000 ALTER TABLE `cms3_appointment_employees_services` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cms3_appointment_orders`
--

DROP TABLE IF EXISTS `cms3_appointment_orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cms3_appointment_orders` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `service_id` int(11) unsigned NOT NULL,
  `employee_id` int(11) unsigned DEFAULT NULL,
  `create_date` int(11) unsigned NOT NULL,
  `date` int(11) unsigned NOT NULL,
  `time` time NOT NULL,
  `phone` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `comment` mediumtext,
  `status_id` enum('1','2','3') NOT NULL,
  PRIMARY KEY (`id`),
  KEY `create_date` (`create_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cms3_appointment_orders`
--

LOCK TABLES `cms3_appointment_orders` WRITE;
/*!40000 ALTER TABLE `cms3_appointment_orders` DISABLE KEYS */;
/*!40000 ALTER TABLE `cms3_appointment_orders` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cms3_appointment_service_groups`
--

DROP TABLE IF EXISTS `cms3_appointment_service_groups`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cms3_appointment_service_groups` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cms3_appointment_service_groups`
--

LOCK TABLES `cms3_appointment_service_groups` WRITE;
/*!40000 ALTER TABLE `cms3_appointment_service_groups` DISABLE KEYS */;
/*!40000 ALTER TABLE `cms3_appointment_service_groups` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cms3_appointment_services`
--

DROP TABLE IF EXISTS `cms3_appointment_services`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cms3_appointment_services` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `group_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `time` time NOT NULL,
  `price` double NOT NULL,
  PRIMARY KEY (`id`),
  KEY `services to service_groups` (`group_id`),
  CONSTRAINT `services to service_groups` FOREIGN KEY (`group_id`) REFERENCES `cms3_appointment_service_groups` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cms3_appointment_services`
--

LOCK TABLES `cms3_appointment_services` WRITE;
/*!40000 ALTER TABLE `cms3_appointment_services` DISABLE KEYS */;
/*!40000 ALTER TABLE `cms3_appointment_services` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cms3_cluster_nodes`
--

DROP TABLE IF EXISTS `cms3_cluster_nodes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cms3_cluster_nodes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `node_ip` varchar(16) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  KEY `node_id` (`id`),
  KEY `node_ip` (`node_ip`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cms3_cluster_nodes`
--

LOCK TABLES `cms3_cluster_nodes` WRITE;
/*!40000 ALTER TABLE `cms3_cluster_nodes` DISABLE KEYS */;
/*!40000 ALTER TABLE `cms3_cluster_nodes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cms3_cluster_nodes_cache_keys`
--

DROP TABLE IF EXISTS `cms3_cluster_nodes_cache_keys`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cms3_cluster_nodes_cache_keys` (
  `node_id` int(11) DEFAULT NULL,
  `key` varchar(255) NOT NULL DEFAULT '',
  KEY `node_id` (`node_id`),
  KEY `key` (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cms3_cluster_nodes_cache_keys`
--

LOCK TABLES `cms3_cluster_nodes_cache_keys` WRITE;
/*!40000 ALTER TABLE `cms3_cluster_nodes_cache_keys` DISABLE KEYS */;
/*!40000 ALTER TABLE `cms3_cluster_nodes_cache_keys` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cms3_data_cache`
--

DROP TABLE IF EXISTS `cms3_data_cache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cms3_data_cache` (
  `key` varchar(255) NOT NULL,
  `value` mediumtext NOT NULL,
  `create_time` int(11) NOT NULL,
  `expire_time` int(11) NOT NULL,
  `entry_time` int(11) NOT NULL,
  `entries_number` int(11) NOT NULL,
  PRIMARY KEY (`key`),
  KEY `Life time` (`expire_time`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cms3_data_cache`
--

LOCK TABLES `cms3_data_cache` WRITE;
/*!40000 ALTER TABLE `cms3_data_cache` DISABLE KEYS */;
INSERT INTO `cms3_data_cache` VALUES ('b1a423234910c5db3684d68c7ffd94dc','s:207:\"{\"_browser_name\":\"Chrome\",\"_version\":\"72.0.3626.121\",\"_platform\":\"Apple\",\"_os\":\"unknown\",\"_is_aol\":false,\"_is_mobile\":false,\"_is_tablet\":false,\"_is_robot\":false,\"_is_facebook\":false,\"_aol_version\":\"unknown\"}\";',1553122425,1616280825,0,0);
/*!40000 ALTER TABLE `cms3_data_cache` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cms3_domain_mirrows`
--

DROP TABLE IF EXISTS `cms3_domain_mirrows`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cms3_domain_mirrows` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `host` varchar(64) DEFAULT NULL,
  `rel` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `host` (`host`),
  KEY `Domain to mirrows relation_FK` (`rel`),
  CONSTRAINT `FK_Domain to mirrows relation` FOREIGN KEY (`rel`) REFERENCES `cms3_domains` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cms3_domain_mirrows`
--

LOCK TABLES `cms3_domain_mirrows` WRITE;
/*!40000 ALTER TABLE `cms3_domain_mirrows` DISABLE KEYS */;
/*!40000 ALTER TABLE `cms3_domain_mirrows` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cms3_domains`
--

DROP TABLE IF EXISTS `cms3_domains`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cms3_domains` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `host` varchar(64) NOT NULL,
  `is_default` tinyint(1) DEFAULT NULL,
  `default_lang_id` int(10) unsigned DEFAULT NULL,
  `use_ssl` tinyint(1) DEFAULT NULL,
  `favicon` mediumtext,
  PRIMARY KEY (`id`),
  UNIQUE KEY `host` (`host`),
  KEY `Domain to default language relation_FK` (`default_lang_id`),
  CONSTRAINT `FK_Domain to default language relation` FOREIGN KEY (`default_lang_id`) REFERENCES `cms3_langs` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cms3_domains`
--

LOCK TABLES `cms3_domains` WRITE;
/*!40000 ALTER TABLE `cms3_domains` DISABLE KEYS */;
INSERT INTO `cms3_domains` VALUES (1,'testtask.madex.pro',1,1,0,NULL);
/*!40000 ALTER TABLE `cms3_domains` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cms3_emarket_top`
--

DROP TABLE IF EXISTS `cms3_emarket_top`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cms3_emarket_top` (
  `id` int(11) NOT NULL,
  `date` bigint(20) NOT NULL,
  `title` varchar(255) NOT NULL,
  `amount` int(11) NOT NULL,
  `total_price` double NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cms3_emarket_top`
--

LOCK TABLES `cms3_emarket_top` WRITE;
/*!40000 ALTER TABLE `cms3_emarket_top` DISABLE KEYS */;
/*!40000 ALTER TABLE `cms3_emarket_top` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cms3_fields_controller`
--

DROP TABLE IF EXISTS `cms3_fields_controller`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cms3_fields_controller` (
  `ord` int(11) DEFAULT NULL,
  `field_id` int(10) unsigned DEFAULT NULL,
  `group_id` int(10) unsigned DEFAULT NULL,
  KEY `rel to field_FK` (`field_id`),
  KEY `rel to field group_FK` (`group_id`),
  KEY `ord` (`ord`),
  CONSTRAINT `FK_rel to field` FOREIGN KEY (`field_id`) REFERENCES `cms3_object_fields` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `FK_rel to field group` FOREIGN KEY (`group_id`) REFERENCES `cms3_object_field_groups` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cms3_fields_controller`
--

LOCK TABLES `cms3_fields_controller` WRITE;
/*!40000 ALTER TABLE `cms3_fields_controller` DISABLE KEYS */;
INSERT INTO `cms3_fields_controller` VALUES (5,1,1),(5,2,2),(10,3,2),(15,4,2),(20,5,2),(25,6,2),(30,7,2),(5,8,3),(10,9,3),(15,10,3),(5,11,4),(10,12,4),(15,13,4),(20,14,4),(5,15,5),(10,16,5),(5,17,6),(10,18,6),(15,19,6),(20,20,6),(5,21,7),(10,22,7),(5,23,8),(5,2,9),(10,3,9),(15,6,9),(20,5,9),(25,7,9),(30,24,9),(5,8,10),(10,9,10),(15,10,10),(5,11,11),(10,12,11),(15,13,11),(20,14,11),(5,15,12),(10,16,12),(5,17,13),(10,18,13),(15,19,13),(20,20,13),(5,21,14),(10,22,14),(5,25,15),(10,26,15),(15,27,15),(20,28,15),(5,29,16),(5,30,17),(5,31,18),(5,31,19),(5,32,20),(5,32,21),(5,33,22),(5,34,23),(5,35,24),(10,36,24),(15,37,24),(20,38,24),(25,39,24),(5,40,25),(5,41,26),(5,42,27),(10,43,27),(15,44,27),(20,45,27),(5,46,28),(10,47,28),(15,48,28),(20,49,28),(25,50,28),(5,51,29),(10,52,29),(15,53,29),(20,54,29),(25,55,29),(30,56,29),(5,57,30),(10,52,30),(15,58,30),(20,59,30),(25,55,30),(5,57,31),(10,52,31),(15,58,31),(20,59,31),(25,55,31),(5,34,32),(10,60,32),(5,61,33),(10,62,33),(15,63,33),(20,64,33),(5,65,34),(5,66,35),(10,67,35),(15,68,35),(20,69,35),(5,70,36),(5,71,37),(5,72,38),(5,73,39),(10,74,39),(15,75,39),(5,76,40),(10,77,40),(15,78,40),(5,73,42),(10,74,42),(15,75,42),(5,79,43),(5,80,44),(10,81,44),(15,82,44),(20,83,44),(25,84,44),(30,85,44),(35,86,44),(5,87,45),(5,88,46),(10,89,46),(15,90,46),(20,91,46),(25,92,46),(5,93,47),(10,94,47),(15,95,47),(20,96,47),(25,97,47),(30,98,47),(35,99,47),(40,100,47),(45,101,47),(50,102,47),(55,103,47),(60,104,47),(65,105,47),(70,106,47),(75,107,47),(80,108,47),(5,109,48),(5,110,49),(5,40,50),(10,111,50),(5,112,51),(10,113,51),(15,114,51),(5,115,52),(10,116,52),(15,117,52),(5,40,53),(10,111,53),(5,118,54),(10,119,54),(15,120,54),(20,49,54),(25,121,54),(5,122,55),(10,123,55),(15,124,55),(5,125,56),(10,126,56),(15,127,56),(20,128,56),(25,129,56),(30,130,56),(35,131,56),(5,132,57),(10,133,57),(15,134,57),(5,135,58),(10,136,58),(15,137,58),(20,138,58),(25,139,58),(30,140,58),(35,141,58),(40,142,58),(45,143,58),(5,40,59),(10,111,59),(5,144,60),(10,145,60),(15,146,60),(20,147,60),(25,148,60),(30,149,60),(35,150,60),(40,151,60),(45,152,60),(50,153,60),(55,154,60),(60,155,60),(65,156,60),(70,157,60),(75,158,60),(80,159,60),(5,160,61),(10,161,61),(15,162,61),(20,163,61),(25,164,61),(30,165,61),(35,166,61),(5,167,62),(10,168,62),(15,169,62),(20,170,62),(25,171,62),(30,172,62),(35,173,62),(40,174,62),(5,175,63),(10,176,63),(15,177,63),(20,178,63),(25,179,63),(30,180,63),(5,181,64),(10,182,64),(15,183,64),(20,184,64),(25,185,64),(30,186,64),(35,187,64),(40,188,64),(45,189,64),(50,190,64),(55,191,64),(60,192,64),(65,193,64),(70,194,64),(75,195,64),(80,196,64),(85,197,64),(90,198,64),(5,199,65),(10,200,65),(5,201,66),(5,202,67),(5,203,68),(10,204,68),(15,205,68),(20,206,68),(25,207,68),(30,208,68),(35,209,68),(40,210,68),(45,211,68),(50,212,68),(55,213,68),(60,214,68),(5,215,69),(10,216,69),(15,217,69),(20,218,69),(25,219,69),(30,220,69),(35,221,69),(40,222,69),(45,223,69),(50,224,69),(55,225,69),(60,226,69),(65,227,69),(70,228,69),(5,229,70),(10,230,70),(15,231,70),(20,232,70),(5,233,72),(10,234,72),(5,235,73),(5,236,74),(10,65,74),(5,70,75),(10,237,75),(5,238,76),(10,239,76),(5,2,77),(10,3,77),(15,6,77),(20,5,77),(25,7,77),(30,240,77),(35,4,77),(5,241,78),(10,242,78),(15,243,78),(20,244,78),(25,245,78),(5,8,79),(10,9,79),(15,10,79),(5,11,80),(10,12,80),(15,13,80),(20,14,80),(5,246,81),(10,247,81),(5,248,82),(5,15,83),(10,16,83),(5,17,84),(10,18,84),(15,19,84),(20,20,84),(5,21,85),(10,22,85),(5,2,86),(10,3,86),(15,6,86),(20,5,86),(25,7,86),(30,4,86),(5,8,87),(10,9,87),(15,10,87),(5,11,88),(10,12,88),(15,13,88),(20,14,88),(5,15,89),(10,16,89),(5,17,90),(10,18,90),(15,19,90),(20,20,90),(5,21,91),(10,22,91),(5,249,92),(10,250,92),(15,251,92),(20,252,92),(25,253,92),(30,254,92),(35,255,92),(40,256,92),(5,2,93),(10,3,93),(15,257,93),(20,5,93),(25,6,93),(30,7,93),(35,258,93),(5,8,94),(10,9,94),(15,10,94),(5,11,95),(10,12,95),(15,13,95),(20,14,95),(5,15,96),(10,16,96),(5,259,97),(10,260,97),(15,261,97),(20,262,97),(25,263,97),(5,4,98),(10,264,98),(15,243,98),(5,15,99),(10,16,99),(5,265,100),(5,2,101),(10,3,101),(15,4,101),(20,5,101),(25,6,101),(30,7,101),(35,243,101),(5,8,102),(10,9,102),(15,10,102),(5,11,103),(10,12,103),(15,13,103),(20,14,103),(5,15,104),(10,16,104),(5,17,105),(10,18,105),(15,19,105),(20,20,105),(5,21,106),(10,22,106),(5,266,107),(5,265,108),(5,2,109),(10,3,109),(15,6,109),(20,5,109),(25,7,109),(30,267,109),(35,268,109),(40,269,109),(45,270,109),(5,8,110),(10,9,110),(15,10,110),(5,11,111),(10,12,111),(15,13,111),(20,14,111),(5,15,112),(10,16,112),(5,17,113),(10,18,113),(15,19,113),(20,20,113),(5,21,114),(10,22,114),(5,2,115),(10,3,115),(15,6,115),(20,7,115),(25,5,115),(30,269,115),(35,270,115),(5,8,116),(10,9,116),(15,10,116),(5,11,117),(10,12,117),(15,13,117),(20,14,117),(5,243,118),(10,264,118),(15,271,118),(5,15,119),(10,16,119),(5,17,120),(10,18,120),(15,19,120),(20,20,120),(5,21,121),(10,22,121),(5,2,122),(10,3,122),(15,6,122),(20,5,122),(25,7,122),(5,8,123),(10,9,123),(15,10,123),(5,11,124),(10,12,124),(15,13,124),(20,14,124),(5,272,125),(10,264,125),(5,15,126),(10,16,126),(5,17,127),(10,18,127),(15,19,127),(20,20,127),(5,21,128),(10,22,128),(5,2,129),(10,3,129),(15,6,129),(20,5,129),(25,7,129),(5,8,130),(10,9,130),(15,10,130),(5,11,131),(10,12,131),(15,13,131),(20,14,131),(5,272,132),(10,243,132),(15,264,132),(5,15,133),(10,16,133),(5,17,134),(10,18,134),(15,19,134),(20,20,134),(5,21,135),(10,22,135),(5,265,136),(5,273,137),(10,274,137),(5,2,138),(10,3,138),(15,6,138),(20,5,138),(25,7,138),(5,8,139),(10,9,139),(15,10,139),(5,11,140),(10,12,140),(15,13,140),(20,14,140),(5,275,141),(10,276,141),(15,243,141),(20,277,141),(25,278,141),(5,15,142),(10,16,142),(5,17,143),(10,18,143),(15,19,143),(20,20,143),(5,21,144),(10,22,144),(5,2,145),(10,3,145),(15,4,145),(20,5,145),(25,6,145),(30,7,145),(5,8,146),(10,9,146),(15,10,146),(5,11,147),(10,12,147),(15,13,147),(20,14,147),(5,15,148),(10,16,148),(5,17,149),(10,18,149),(15,19,149),(20,20,149),(5,21,150),(10,22,150),(5,279,151),(5,280,152),(10,281,152),(15,282,152),(20,283,152),(5,284,153),(10,285,153),(15,286,153),(20,287,153),(5,288,154),(10,289,154),(15,290,154),(20,291,154),(25,292,154),(5,293,155),(5,294,156),(5,295,157),(10,296,157),(15,297,157),(5,2,158),(10,3,158),(15,5,158),(20,6,158),(25,7,158),(5,8,159),(10,9,159),(15,10,159),(5,11,160),(10,12,160),(15,13,160),(20,14,160),(5,267,161),(10,298,161),(15,299,161),(5,15,162),(10,16,162),(5,17,163),(10,18,163),(15,19,163),(20,20,163),(5,21,164),(10,22,164),(5,2,165),(10,3,165),(15,5,165),(20,6,165),(25,7,165),(5,8,166),(10,9,166),(15,10,166),(5,11,167),(10,12,167),(15,13,167),(20,14,167),(5,300,168),(10,267,168),(15,298,168),(20,299,168),(5,15,169),(10,16,169),(5,17,170),(10,18,170),(15,19,170),(20,20,170),(5,21,171),(10,22,171),(5,2,172),(10,3,172),(15,4,172),(20,5,172),(25,6,172),(30,7,172),(5,8,173),(10,9,173),(15,10,173),(5,11,174),(10,12,174),(15,13,174),(20,14,174),(5,15,175),(10,16,175),(5,17,176),(10,18,176),(15,19,176),(20,20,176),(5,21,177),(10,22,177),(5,2,178),(10,3,178),(15,4,178),(20,5,178),(25,6,178),(30,7,178),(5,8,179),(10,9,179),(15,10,179),(5,11,180),(10,12,180),(15,13,180),(20,14,180),(5,15,181),(10,16,181),(5,17,182),(10,18,182),(15,19,182),(20,20,182),(5,21,183),(10,22,183),(5,2,184),(10,3,184),(15,5,184),(20,6,184),(25,7,184),(30,276,184),(35,301,184),(40,264,184),(45,243,184),(5,8,185),(10,9,185),(15,10,185),(5,11,186),(10,12,186),(15,13,186),(20,14,186),(5,17,187),(10,18,187),(15,19,187),(20,20,187),(5,21,188),(10,22,188),(5,265,189),(5,302,190),(10,303,190),(15,304,190),(20,305,190),(25,306,190),(30,307,190),(5,308,191),(10,309,191),(5,310,192),(10,311,192),(15,312,192),(5,313,193),(10,314,193),(15,315,193),(5,316,194),(10,317,194),(15,318,194),(20,319,194),(5,320,195),(10,321,195),(15,322,195),(20,323,195),(25,324,195),(5,325,196),(10,326,196),(15,327,196),(5,2,197),(10,3,197),(15,6,197),(20,5,197),(25,7,197),(5,8,198),(10,9,198),(15,10,198),(5,11,199),(10,12,199),(15,13,199),(20,14,199),(5,267,200),(10,328,200),(5,15,201),(10,16,201),(5,17,202),(10,18,202),(15,19,202),(20,20,202),(5,21,203),(10,22,203),(5,329,204),(10,330,204),(15,331,204),(20,332,204),(25,333,204),(5,2,205),(10,3,205),(15,6,205),(20,5,205),(25,7,205),(30,334,205),(5,8,206),(10,9,206),(15,10,206),(5,11,207),(10,14,207),(5,335,208),(10,336,208),(15,337,208),(20,338,208),(5,339,210),(10,340,210),(15,341,210),(5,342,211),(10,343,211),(5,15,212),(10,16,212),(5,17,213),(10,18,213),(15,19,213),(20,20,213),(5,21,214),(10,22,214),(5,47,215),(10,46,215),(15,48,215),(20,344,215),(25,345,215),(30,346,215),(35,347,215),(5,49,216),(10,50,216),(15,348,216),(5,349,217),(5,350,218),(5,65,219),(10,351,219),(5,70,220),(10,352,220),(5,70,221),(10,353,221),(15,354,221),(5,70,222),(10,355,222),(15,356,222),(5,70,223),(10,357,223),(15,358,223),(5,70,224),(10,359,224),(5,70,225),(10,360,225),(5,135,226),(10,136,226),(15,137,226),(20,138,226),(25,139,226),(30,140,226),(35,141,226),(40,142,226),(45,143,226),(5,135,227),(10,136,227),(15,138,227),(20,139,227),(25,140,227),(30,141,227),(35,142,227),(40,143,227),(5,361,228),(10,137,228),(5,135,229),(10,136,229),(15,138,229),(20,139,229),(25,140,229),(30,141,229),(35,142,229),(40,143,229),(5,362,230),(10,363,230),(5,135,231),(10,136,231),(15,138,231),(20,141,231),(25,142,231),(30,143,231),(5,364,232),(10,365,232),(15,366,232),(20,367,232),(25,368,232),(30,369,232),(35,370,232),(40,371,232),(45,139,232),(50,140,232),(5,115,233),(10,116,233),(15,117,233),(5,372,234),(10,373,234),(15,374,234),(20,375,234),(25,376,234),(30,377,234),(5,115,235),(10,116,235),(15,117,235),(5,378,236),(10,379,236),(15,380,236),(20,381,236),(5,115,237),(10,116,237),(15,117,237),(5,115,238),(10,116,238),(15,117,238),(5,382,239),(10,383,239),(15,384,239),(20,385,239),(25,380,239),(5,115,240),(10,116,240),(15,117,240),(5,386,241),(10,387,241),(5,115,242),(10,116,242),(15,117,242),(5,388,243),(10,389,243),(15,390,243),(20,391,243),(25,392,243),(30,393,243),(35,394,243),(40,395,243),(45,376,243),(50,396,243),(5,115,244),(10,116,244),(15,117,244),(5,397,245),(10,398,245),(15,399,245),(20,400,245),(25,401,245),(30,402,245),(35,380,245),(5,115,246),(10,116,246),(15,117,246),(5,403,247),(10,404,247),(15,405,247),(5,115,248),(10,406,248),(15,407,248),(20,387,248),(25,408,248),(30,116,248),(35,117,248),(5,115,249),(10,116,249),(15,117,249),(5,409,250),(10,410,250),(15,411,250),(20,412,250),(25,413,250),(5,115,251),(10,116,251),(15,117,251),(5,414,252),(10,415,252),(15,416,252),(20,417,252),(25,408,252),(30,380,252),(5,115,253),(10,116,253),(15,117,253),(5,385,254),(10,418,254),(15,419,254),(20,420,254),(5,115,255),(10,116,255),(15,117,255),(5,421,256),(10,422,256),(15,380,256),(20,381,256),(5,423,257),(10,424,257),(15,425,257),(5,426,258),(10,427,258),(5,428,259),(10,429,259),(5,430,260),(10,431,260),(15,432,260),(20,433,260),(25,434,260),(30,435,260),(5,436,261),(10,437,261),(15,438,261),(5,439,262),(10,440,262),(15,441,262),(20,442,262),(25,443,262),(5,444,263),(10,445,263),(5,446,264),(5,426,265),(10,427,265),(5,447,266),(10,448,266),(15,449,266),(20,450,266),(5,428,267),(10,429,267),(5,430,268),(10,431,268),(15,432,268),(20,433,268),(25,434,268),(30,435,268),(5,436,269),(10,437,269),(15,438,269),(5,439,270),(10,440,270),(15,441,270),(20,442,270),(25,443,270),(5,444,271),(10,445,271),(5,446,272),(5,426,273),(10,427,273),(5,451,274),(10,448,274),(15,449,274),(20,452,274),(5,428,275),(10,429,275),(5,430,276),(10,431,276),(15,432,276),(20,433,276),(25,434,276),(30,435,276),(5,436,277),(10,437,277),(15,438,277),(5,439,278),(10,440,278),(15,441,278),(20,442,278),(25,443,278),(5,444,279),(10,445,279),(5,446,280),(5,426,281),(10,427,281),(5,453,282),(5,428,283),(10,429,283),(5,430,284),(10,431,284),(15,432,284),(20,433,284),(25,434,284),(30,435,284),(5,436,285),(10,437,285),(15,438,285),(5,439,286),(10,440,286),(15,441,286),(20,442,286),(25,443,286),(5,444,287),(10,445,287),(5,446,288),(5,454,289),(10,455,289),(5,456,290),(10,457,290),(15,458,290),(20,459,290),(25,460,290),(30,461,290),(5,462,291),(10,463,291),(15,464,291),(20,465,291),(25,466,291),(5,2,292),(10,3,292),(15,5,292),(20,6,292),(25,7,292),(30,4,292),(5,8,293),(10,9,293),(15,10,293),(5,11,294),(10,12,294),(15,13,294),(20,14,294),(5,467,295),(10,468,295),(5,15,296),(10,16,296),(5,17,297),(10,18,297),(15,19,297),(20,20,297),(5,21,298),(10,22,298),(5,469,299),(10,470,299),(15,471,299),(5,43,300),(5,43,301),(5,2,302),(10,3,302),(15,4,302),(20,5,302),(25,6,302),(30,7,302),(5,8,303),(10,9,303),(15,10,303),(5,11,304),(10,12,304),(15,13,304),(20,14,304),(5,15,305),(10,16,305),(5,17,306),(10,18,306),(15,19,306),(20,20,306),(5,21,307),(10,22,307),(5,472,308),(10,473,308),(15,474,308),(20,475,308),(25,476,308),(30,477,308),(35,478,308),(40,479,308),(45,480,308),(50,481,308),(55,482,308),(40,483,77);
/*!40000 ALTER TABLE `cms3_fields_controller` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cms3_hierarchy`
--

DROP TABLE IF EXISTS `cms3_hierarchy`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cms3_hierarchy` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `rel` int(10) unsigned NOT NULL,
  `type_id` int(10) unsigned NOT NULL,
  `lang_id` int(10) unsigned NOT NULL,
  `domain_id` int(10) unsigned NOT NULL,
  `obj_id` int(10) unsigned NOT NULL,
  `ord` int(11) DEFAULT '0',
  `tpl_id` int(10) unsigned DEFAULT NULL,
  `alt_name` varchar(128) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT NULL,
  `is_deleted` tinyint(1) DEFAULT NULL,
  `is_visible` tinyint(1) DEFAULT NULL,
  `updatetime` int(11) DEFAULT NULL,
  `is_default` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `types rels_FK` (`type_id`),
  KEY `Prefix from lang_id_FK` (`lang_id`),
  KEY `Domain from domain_id relation_FK` (`domain_id`),
  KEY `hierarchy to plain object image_FK` (`obj_id`),
  KEY `Getting template data_FK` (`tpl_id`),
  KEY `is_default` (`is_default`),
  KEY `alt_name` (`alt_name`),
  KEY `is_deleted` (`is_deleted`),
  KEY `is_active` (`is_active`),
  KEY `ord` (`ord`),
  KEY `rel` (`rel`),
  KEY `updatetime` (`updatetime`),
  KEY `is_visible` (`is_visible`),
  CONSTRAINT `FK_Domain from domain_id relation` FOREIGN KEY (`domain_id`) REFERENCES `cms3_domains` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `FK_Getting template data` FOREIGN KEY (`tpl_id`) REFERENCES `cms3_templates` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `FK_Prefix from lang_id` FOREIGN KEY (`lang_id`) REFERENCES `cms3_langs` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `FK_hierarchy to plain object image` FOREIGN KEY (`obj_id`) REFERENCES `cms3_objects` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `FK_types rels` FOREIGN KEY (`type_id`) REFERENCES `cms3_hierarchy_types` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cms3_hierarchy`
--

LOCK TABLES `cms3_hierarchy` WRITE;
/*!40000 ALTER TABLE `cms3_hierarchy` DISABLE KEYS */;
INSERT INTO `cms3_hierarchy` VALUES (1,0,30,1,1,623,1,1,'index',1,0,1,1553122592,1),(2,0,1,1,1,624,2,1,'blog',1,0,0,1553123288,0),(3,2,29,1,1,625,5,1,'centralnyj-apogej-predposylki-i-razvitie',1,0,0,1553123963,0),(4,2,29,1,1,629,4,1,'mezhplanetnyj-godovoj-parallaks-metodologiya-i-osobennosti',1,0,0,1553124103,0),(5,2,29,1,1,630,3,1,'pochemu-parallelna-letuchaya-ryba',1,0,0,1553124270,0),(6,2,29,1,1,631,2,1,'pochemu-potencialno-ppotoplanetnoe-oblako',1,0,0,1553124252,0),(7,2,29,1,1,632,6,1,'pochemu-potencialno-ppotoplanetnoe-oblako1',1,0,0,1553124385,0),(8,2,29,1,1,633,7,1,'pochemu-parallelna-letuchaya-ryba1',1,0,0,1553124416,0),(9,2,29,1,1,634,8,1,'mezhplanetnyj-godovoj-parallaks-metodologiya-i-osobennosti1',1,0,0,1553124437,0),(10,2,29,1,1,635,9,1,'pochemu-parallelna-letuchaya-ryba2',1,0,0,1553124487,0);
/*!40000 ALTER TABLE `cms3_hierarchy` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cms3_hierarchy_relations`
--

DROP TABLE IF EXISTS `cms3_hierarchy_relations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cms3_hierarchy_relations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `rel_id` int(10) unsigned DEFAULT NULL,
  `child_id` int(10) unsigned DEFAULT NULL,
  `level` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `rel_id` (`rel_id`),
  KEY `child_id` (`child_id`),
  KEY `level` (`level`),
  CONSTRAINT `Hierarchy relation by child_id` FOREIGN KEY (`child_id`) REFERENCES `cms3_hierarchy` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `Hierarchy relation by rel_id` FOREIGN KEY (`rel_id`) REFERENCES `cms3_hierarchy` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=27 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cms3_hierarchy_relations`
--

LOCK TABLES `cms3_hierarchy_relations` WRITE;
/*!40000 ALTER TABLE `cms3_hierarchy_relations` DISABLE KEYS */;
INSERT INTO `cms3_hierarchy_relations` VALUES (1,NULL,1,0),(2,NULL,2,0),(5,NULL,3,1),(6,2,3,1),(9,NULL,4,1),(10,2,4,1),(13,NULL,5,1),(14,2,5,1),(17,NULL,6,1),(18,2,6,1),(19,NULL,7,1),(20,2,7,1),(21,NULL,8,1),(22,2,8,1),(23,NULL,9,1),(24,2,9,1),(25,NULL,10,1),(26,2,10,1);
/*!40000 ALTER TABLE `cms3_hierarchy_relations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cms3_hierarchy_types`
--

DROP TABLE IF EXISTS `cms3_hierarchy_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cms3_hierarchy_types` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(48) NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `ext` varchar(48) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `name` (`name`,`ext`),
  KEY `title` (`title`),
  KEY `ext` (`ext`)
) ENGINE=InnoDB AUTO_INCREMENT=68 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cms3_hierarchy_types`
--

LOCK TABLES `cms3_hierarchy_types` WRITE;
/*!40000 ALTER TABLE `cms3_hierarchy_types` DISABLE KEYS */;
INSERT INTO `cms3_hierarchy_types` VALUES (1,'news','i18n::hierarchy-type-news-rubric','rubric'),(2,'emarket','i18n::hierarchy-type-emarket-currency','currency'),(3,'emarket','i18n::hierarchy-type-emarket-discount_type','discount_type'),(4,'emarket','i18n::hierarchy-type-emarket-discount_modificator_type','discount_modificator_type'),(5,'emarket','i18n::hierarchy-type-emarket-discount_rule_type','discount_rule_type'),(6,'social_networks','i18n::hierarchy-type-social_networks-networks','network'),(7,'social_networks','i18n::hierarchy-type-social_networks-vkontakte','vkontakte'),(8,'users','i18n::hierarchy-type-users-users','users'),(9,'emarket','i18n::hierarchy-type-eshop-address','delivery_address'),(10,'emarket','i18n::hierarchy-type-emarket-item_type','item_type'),(11,'emarket','i18n::hierarchy-type-emarket-discount','discount'),(12,'emarket','i18n::hierarchy-type-emarket-item_option','item_option'),(13,'emarket','i18n::hierarchy-type-emarket-order_item','order_item'),(14,'emarket','i18n::hierarchy-type-emarket-order_status','order_status'),(15,'emarket','i18n::hierarchy-type-emarket-payment_type','payment_type'),(16,'emarket','i18n::hierarchy-type-emarket-payment','payment'),(17,'emarket','i18n::hierarchy-type-emarket-payment_status','order_payment_status'),(18,'emarket','i18n::hierarchy-type-emarket-legal_person','legal_person'),(19,'emarket','i18n::hierarchy-type-emarket-delivery_type','delivery_type'),(20,'emarket','i18n::hierarchy-type-emarket-delivery','delivery'),(21,'emarket','i18n::hierarchy-type-emarket-delivery_status','order_delivery_status'),(22,'emarket','i18n::hierarchy-type-emarket-order','order'),(23,'users','i18n::hierarchy-type-users-user','user'),(24,'emarket','i18n::hierarchy-type-emarket-store','store'),(25,'emarket','i18n::hierarchy-type-emarket-discount_modificator','discount_modificator'),(26,'emarket','i18n::hierarchy-type-emarket-discount_rule','discount_rule'),(27,'menu','i18n::hierarchy-type-menu-item_element','item_element'),(28,'news','i18n::hierarchy-type-news-subject','subject'),(29,'news','i18n::hierarchy-type-news-item','item'),(30,'content','i18n::hierarchy-type-content-page',''),(31,'content','i18n::hierarchy-type-content-ticket','ticket'),(32,'blogs20','i18n::hierarchy-type-blogs-blog','blog'),(33,'users','i18n::hierarchy-type-users-author','author'),(34,'blogs20','i18n::hierarchy-type-blogs20-comment','comment'),(35,'blogs20','i18n::hierarchy-type-blogs20-post','post'),(36,'forum','i18n::hierarchy-type-forum-conf','conf'),(37,'forum','i18n::hierarchy-type-forum-topic','topic'),(38,'forum','i18n::hierarchy-type-forum-message','message'),(39,'comments','i18n::hierarchy-type-comments-comment','comment'),(40,'vote','i18n::hierarchy-type-vote-poll_item','poll_item'),(41,'vote','i18n::hierarchy-type-vote-poll','poll'),(42,'webforms','i18n::hierarchy-type-webforms-page','page'),(43,'webforms','i18n::hierarchy-type-webforms-form','form'),(44,'webforms','i18n::hierarchy-type-webforms-template','template'),(45,'webforms','i18n::hierarchy-type-webforms-address','address'),(46,'photoalbum','i18n::hierarchy-type-photoalbum-album','album'),(47,'photoalbum','i18n::hierarchy-type-photoalbum-photo','photo'),(48,'faq','i18n::hierarchy-type-faq-project','project'),(49,'faq','i18n::hierarchy-type-faq-category','category'),(50,'faq','i18n::hierarchy-type-faq-question','question'),(51,'dispatches','i18n::hierarchy-type-dispatches-dispatch','dispatch'),(52,'dispatches','i18n::hierarchy-type-dispatches-release','release'),(53,'dispatches','i18n::hierarchy-type-dispatches-message','message'),(54,'dispatches','i18n::hierarchy-type-dispatches-subscriber','subscriber'),(55,'catalog','i18n::hierarchy-type-catalog-category','category'),(56,'catalog','i18n::hierarchy-type-catalog-object','object'),(57,'emarket','i18n::hierarchy-type-emarket-unregistered_customer','customer'),(58,'banners','i18n::hierarchy-type-banners-place','place'),(59,'banners','i18n::hierarchy-type-banners-banner','banner'),(60,'users','i18n::hierarchy-type-users-avatar','avatar'),(61,'exchange','i18n::hierarchy-type-exchange-data_exchange_export','export'),(62,'exchange','i18n::hierarchy-type-exchange-data_exchange_import','import'),(63,'filemanager','i18n::hierarchy-type-filemanager-shared_file','shared_file'),(64,'umiSettings','i18n::hierarchy-type-umiSettings-setting','settings'),(65,'umiStub','i18n::hierarchy-type-umiStub-ip-blacklist','ip-blacklist'),(66,'umiStub','i18n::hierarchy-type-umiStub-ip-whitelist','ip-whitelist'),(67,'appointment','i18n::hierarchy-type-appointment-page','page');
/*!40000 ALTER TABLE `cms3_hierarchy_types` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cms3_hieratical`
--

DROP TABLE IF EXISTS `cms3_hieratical`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cms3_hieratical` (
  `id` int(10) unsigned NOT NULL,
  `rel` int(10) unsigned NOT NULL,
  `type_id` int(11) NOT NULL,
  `lang_id` int(11) NOT NULL,
  `domain_id` int(11) NOT NULL,
  `obj_id` int(10) unsigned NOT NULL,
  `ord` int(11) DEFAULT NULL,
  `tpl_id` int(10) unsigned DEFAULT NULL,
  `alt_name` varchar(128) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=cp1251;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cms3_hieratical`
--

LOCK TABLES `cms3_hieratical` WRITE;
/*!40000 ALTER TABLE `cms3_hieratical` DISABLE KEYS */;
/*!40000 ALTER TABLE `cms3_hieratical` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cms3_hieratical_types`
--

DROP TABLE IF EXISTS `cms3_hieratical_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cms3_hieratical_types` (
  `id` int(11) NOT NULL,
  `codename` varchar(48) NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=cp1251;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cms3_hieratical_types`
--

LOCK TABLES `cms3_hieratical_types` WRITE;
/*!40000 ALTER TABLE `cms3_hieratical_types` DISABLE KEYS */;
/*!40000 ALTER TABLE `cms3_hieratical_types` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cms3_import_apiship_orders`
--

DROP TABLE IF EXISTS `cms3_import_apiship_orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cms3_import_apiship_orders` (
  `external_id` int(10) unsigned NOT NULL,
  `internal_id` int(10) unsigned NOT NULL,
  `source_id` int(10) unsigned NOT NULL,
  KEY `external_id` (`external_id`,`source_id`),
  KEY `internal_id` (`internal_id`,`source_id`),
  KEY `source_id` (`source_id`),
  CONSTRAINT `cms3_import_apiship_orders_ibfk_1` FOREIGN KEY (`internal_id`) REFERENCES `cms3_apiship_orders` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `cms3_import_apiship_orders_ibfk_2` FOREIGN KEY (`source_id`) REFERENCES `cms3_import_sources` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cms3_import_apiship_orders`
--

LOCK TABLES `cms3_import_apiship_orders` WRITE;
/*!40000 ALTER TABLE `cms3_import_apiship_orders` DISABLE KEYS */;
/*!40000 ALTER TABLE `cms3_import_apiship_orders` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cms3_import_appointment_employee_schedule`
--

DROP TABLE IF EXISTS `cms3_import_appointment_employee_schedule`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cms3_import_appointment_employee_schedule` (
  `external_id` int(11) NOT NULL,
  `internal_id` int(11) NOT NULL,
  `source_id` int(10) unsigned NOT NULL,
  KEY `external_id` (`external_id`,`source_id`),
  KEY `internal_id` (`internal_id`,`source_id`),
  KEY `source_id` (`source_id`),
  CONSTRAINT `cms3_import_appointment_employee_schedule_ibfk_1` FOREIGN KEY (`internal_id`) REFERENCES `cms3_appointment_employee_schedule` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `cms3_import_appointment_employee_schedule_ibfk_2` FOREIGN KEY (`source_id`) REFERENCES `cms3_import_sources` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cms3_import_appointment_employee_schedule`
--

LOCK TABLES `cms3_import_appointment_employee_schedule` WRITE;
/*!40000 ALTER TABLE `cms3_import_appointment_employee_schedule` DISABLE KEYS */;
/*!40000 ALTER TABLE `cms3_import_appointment_employee_schedule` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cms3_import_appointment_employees`
--

DROP TABLE IF EXISTS `cms3_import_appointment_employees`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cms3_import_appointment_employees` (
  `external_id` int(11) NOT NULL,
  `internal_id` int(11) NOT NULL,
  `source_id` int(10) unsigned NOT NULL,
  KEY `external_id` (`external_id`,`source_id`),
  KEY `internal_id` (`internal_id`,`source_id`),
  KEY `source_id` (`source_id`),
  CONSTRAINT `cms3_import_appointment_employees_ibfk_1` FOREIGN KEY (`internal_id`) REFERENCES `cms3_appointment_employees` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `cms3_import_appointment_employees_ibfk_2` FOREIGN KEY (`source_id`) REFERENCES `cms3_import_sources` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cms3_import_appointment_employees`
--

LOCK TABLES `cms3_import_appointment_employees` WRITE;
/*!40000 ALTER TABLE `cms3_import_appointment_employees` DISABLE KEYS */;
/*!40000 ALTER TABLE `cms3_import_appointment_employees` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cms3_import_appointment_employees_services`
--

DROP TABLE IF EXISTS `cms3_import_appointment_employees_services`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cms3_import_appointment_employees_services` (
  `external_id` int(11) NOT NULL,
  `internal_id` int(11) NOT NULL,
  `source_id` int(10) unsigned NOT NULL,
  KEY `external_id` (`external_id`,`source_id`),
  KEY `internal_id` (`internal_id`,`source_id`),
  KEY `source_id` (`source_id`),
  CONSTRAINT `cms3_import_appointment_employees_services_ibfk_1` FOREIGN KEY (`internal_id`) REFERENCES `cms3_appointment_employees_services` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `cms3_import_appointment_employees_services_ibfk_2` FOREIGN KEY (`source_id`) REFERENCES `cms3_import_sources` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cms3_import_appointment_employees_services`
--

LOCK TABLES `cms3_import_appointment_employees_services` WRITE;
/*!40000 ALTER TABLE `cms3_import_appointment_employees_services` DISABLE KEYS */;
/*!40000 ALTER TABLE `cms3_import_appointment_employees_services` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cms3_import_appointment_orders`
--

DROP TABLE IF EXISTS `cms3_import_appointment_orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cms3_import_appointment_orders` (
  `external_id` int(11) NOT NULL,
  `internal_id` int(11) NOT NULL,
  `source_id` int(10) unsigned NOT NULL,
  KEY `external_id` (`external_id`,`source_id`),
  KEY `internal_id` (`internal_id`,`source_id`),
  KEY `source_id` (`source_id`),
  CONSTRAINT `cms3_import_appointment_orders_ibfk_1` FOREIGN KEY (`internal_id`) REFERENCES `cms3_appointment_orders` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `cms3_import_appointment_orders_ibfk_2` FOREIGN KEY (`source_id`) REFERENCES `cms3_import_sources` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cms3_import_appointment_orders`
--

LOCK TABLES `cms3_import_appointment_orders` WRITE;
/*!40000 ALTER TABLE `cms3_import_appointment_orders` DISABLE KEYS */;
/*!40000 ALTER TABLE `cms3_import_appointment_orders` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cms3_import_appointment_service_groups`
--

DROP TABLE IF EXISTS `cms3_import_appointment_service_groups`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cms3_import_appointment_service_groups` (
  `external_id` int(11) NOT NULL,
  `internal_id` int(11) NOT NULL,
  `source_id` int(10) unsigned NOT NULL,
  KEY `external_id` (`external_id`,`source_id`),
  KEY `internal_id` (`internal_id`,`source_id`),
  KEY `source_id` (`source_id`),
  CONSTRAINT `cms3_import_appointment_service_groups_ibfk_1` FOREIGN KEY (`internal_id`) REFERENCES `cms3_appointment_service_groups` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `cms3_import_appointment_service_groups_ibfk_2` FOREIGN KEY (`source_id`) REFERENCES `cms3_import_sources` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cms3_import_appointment_service_groups`
--

LOCK TABLES `cms3_import_appointment_service_groups` WRITE;
/*!40000 ALTER TABLE `cms3_import_appointment_service_groups` DISABLE KEYS */;
/*!40000 ALTER TABLE `cms3_import_appointment_service_groups` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cms3_import_appointment_services`
--

DROP TABLE IF EXISTS `cms3_import_appointment_services`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cms3_import_appointment_services` (
  `external_id` int(11) NOT NULL,
  `internal_id` int(11) NOT NULL,
  `source_id` int(10) unsigned NOT NULL,
  KEY `external_id` (`external_id`,`source_id`),
  KEY `internal_id` (`internal_id`,`source_id`),
  KEY `source_id` (`source_id`),
  CONSTRAINT `cms3_import_appointment_services_ibfk_1` FOREIGN KEY (`internal_id`) REFERENCES `cms3_appointment_services` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `cms3_import_appointment_services_ibfk_2` FOREIGN KEY (`source_id`) REFERENCES `cms3_import_sources` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cms3_import_appointment_services`
--

LOCK TABLES `cms3_import_appointment_services` WRITE;
/*!40000 ALTER TABLE `cms3_import_appointment_services` DISABLE KEYS */;
/*!40000 ALTER TABLE `cms3_import_appointment_services` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cms3_import_domain_mirrors`
--

DROP TABLE IF EXISTS `cms3_import_domain_mirrors`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cms3_import_domain_mirrors` (
  `source_id` int(10) unsigned NOT NULL,
  `old_id` varchar(255) NOT NULL,
  `new_id` int(10) unsigned NOT NULL,
  KEY `source_id` (`source_id`,`old_id`,`new_id`),
  KEY `old_id` (`old_id`,`new_id`),
  KEY `new_id` (`new_id`),
  CONSTRAINT `FK_DomainMirrorSourceId_To_Source` FOREIGN KEY (`source_id`) REFERENCES `cms3_import_sources` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `FK_NewId_To_DomainMirrorId` FOREIGN KEY (`new_id`) REFERENCES `cms3_domain_mirrows` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cms3_import_domain_mirrors`
--

LOCK TABLES `cms3_import_domain_mirrors` WRITE;
/*!40000 ALTER TABLE `cms3_import_domain_mirrors` DISABLE KEYS */;
/*!40000 ALTER TABLE `cms3_import_domain_mirrors` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cms3_import_domains`
--

DROP TABLE IF EXISTS `cms3_import_domains`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cms3_import_domains` (
  `source_id` int(10) unsigned NOT NULL,
  `old_id` varchar(255) NOT NULL,
  `new_id` int(10) unsigned NOT NULL,
  KEY `source_id` (`source_id`,`old_id`,`new_id`),
  KEY `old_id` (`old_id`,`new_id`),
  KEY `new_id` (`new_id`),
  CONSTRAINT `FK_DomainSourceId_To_Source` FOREIGN KEY (`source_id`) REFERENCES `cms3_import_sources` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `FK_NewId_To_DomainId` FOREIGN KEY (`new_id`) REFERENCES `cms3_domains` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cms3_import_domains`
--

LOCK TABLES `cms3_import_domains` WRITE;
/*!40000 ALTER TABLE `cms3_import_domains` DISABLE KEYS */;
INSERT INTO `cms3_import_domains` VALUES (1,'1',1);
/*!40000 ALTER TABLE `cms3_import_domains` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cms3_import_fields`
--

DROP TABLE IF EXISTS `cms3_import_fields`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cms3_import_fields` (
  `source_id` int(10) unsigned NOT NULL,
  `field_name` varchar(64) NOT NULL,
  `type_id` int(10) unsigned NOT NULL,
  `new_id` int(10) unsigned NOT NULL,
  KEY `source_id` (`source_id`),
  KEY `type_id` (`type_id`),
  KEY `field_name` (`field_name`),
  KEY `new_id` (`new_id`),
  CONSTRAINT `FK_FieldSourceId_To_Source` FOREIGN KEY (`source_id`) REFERENCES `cms3_import_sources` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `FK_NewFieldId_To_ObjectTypeId` FOREIGN KEY (`type_id`) REFERENCES `cms3_import_types` (`new_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `FK_NewId_To_ObjectTypeFieldId` FOREIGN KEY (`new_id`) REFERENCES `cms3_object_fields` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cms3_import_fields`
--

LOCK TABLES `cms3_import_fields` WRITE;
/*!40000 ALTER TABLE `cms3_import_fields` DISABLE KEYS */;
INSERT INTO `cms3_import_fields` VALUES (1,'publish_status_id',2,1),(1,'title',3,2),(1,'h1',3,3),(1,'content',3,4),(1,'meta_descriptions',3,5),(1,'meta_keywords',3,6),(1,'tags',3,7),(1,'menu_pic_ua',3,8),(1,'menu_pic_a',3,9),(1,'header_pic',3,10),(1,'robots_deny',3,11),(1,'show_submenu',3,12),(1,'is_expanded',3,13),(1,'is_unindexed',3,14),(1,'rate_voters',3,15),(1,'rate_sum',3,16),(1,'expiration_date',3,17),(1,'notification_date',3,18),(1,'publish_comments',3,19),(1,'publish_status',3,20),(1,'locktime',3,21),(1,'lockuser',3,22),(1,'charset',6,23),(1,'title',7,2),(1,'h1',7,3),(1,'meta_keywords',7,6),(1,'meta_descriptions',7,5),(1,'tags',7,7),(1,'readme',7,24),(1,'menu_pic_ua',7,8),(1,'menu_pic_a',7,9),(1,'header_pic',7,10),(1,'robots_deny',7,11),(1,'show_submenu',7,12),(1,'is_expanded',7,13),(1,'is_unindexed',7,14),(1,'rate_voters',7,15),(1,'rate_sum',7,16),(1,'expiration_date',7,17),(1,'notification_date',7,18),(1,'publish_comments',7,19),(1,'publish_status',7,20),(1,'locktime',7,21),(1,'lockuser',7,22),(1,'rss_type',8,25),(1,'url',8,26),(1,'charset_id',8,27),(1,'news_rubric',8,28),(1,'quality_value',9,29),(1,'country_iso_code',10,30),(1,'identifier',14,31),(1,'identifier',15,31),(1,'number',16,32),(1,'number',17,32),(1,'social_id',19,33),(1,'codename',20,34),(1,'codename',21,35),(1,'nominal',21,36),(1,'rate',21,37),(1,'prefix',21,38),(1,'suffix',21,39),(1,'codename',22,40),(1,'platform_identificator',23,41),(1,'active',24,42),(1,'domain_id',24,43),(1,'token',24,44),(1,'platform',24,45),(1,'lname',25,46),(1,'fname',25,47),(1,'father_name',25,48),(1,'email',25,49),(1,'phone',25,50),(1,'yandex_id',27,51),(1,'robokassa_id',27,52),(1,'payonline_id',27,53),(1,'payanyway_id',27,54),(1,'sberbank_id',27,55),(1,'tax',27,56),(1,'yandex_id',28,57),(1,'robokassa_id',28,52),(1,'payanyway_id',28,58),(1,'payonline_id',28,59),(1,'sberbank_id',28,55),(1,'yandex_id',29,57),(1,'robokassa_id',29,52),(1,'payanyway_id',29,58),(1,'payonline_id',29,59),(1,'sberbank_id',29,55),(1,'codename',30,34),(1,'description',30,60),(1,'modificator_codename',31,61),(1,'modificator_type_id',31,62),(1,'modificator_discount_types',31,63),(1,'modificator_type_guid',31,64),(1,'modificator_type_id',32,65),(1,'rule_codename',33,66),(1,'rule_type_id',33,67),(1,'rule_discount_types',33,68),(1,'rule_type_guid',33,69),(1,'rule_type_id',34,70),(1,'sid',35,71),(1,'sid',36,72),(1,'social_id',37,73),(1,'template_id',37,74),(1,'domain_id',37,75),(1,'nazvanie_sajta',38,76),(1,'is_iframe_enabled',38,77),(1,'iframe_pages',38,78),(1,'social_id',38,73),(1,'template_id',38,74),(1,'domain_id',38,75),(1,'nazvanie',39,79),(1,'country',40,80),(1,'index',40,81),(1,'region',40,82),(1,'city',40,83),(1,'street',40,84),(1,'house',40,85),(1,'flat',40,86),(1,'class_name',41,87),(1,'discount_type_id',42,88),(1,'discount_modificator_id',42,89),(1,'discount_rules_id',42,90),(1,'is_active',42,91),(1,'description',42,92),(1,'item_amount',44,93),(1,'item_price',44,94),(1,'item_actual_price',44,95),(1,'item_total_original_price',44,96),(1,'item_total_price',44,97),(1,'item_type_id',44,98),(1,'item_link',44,99),(1,'item_discount_id',44,100),(1,'item_discount_value',44,101),(1,'weight',44,102),(1,'width',44,103),(1,'height',44,104),(1,'length',44,105),(1,'tax_rate_id',44,106),(1,'payment_mode',44,107),(1,'payment_subject',44,108),(1,'options',44,109),(1,'trade_offer',44,110),(1,'codename',45,40),(1,'priority',45,111),(1,'class_name',46,112),(1,'payment_type_id',46,113),(1,'payment_type_guid',46,114),(1,'payment_type_id',47,115),(1,'disabled',47,116),(1,'domain_id_list',47,117),(1,'codename',48,40),(1,'priority',48,111),(1,'contact_person',49,118),(1,'phone_number',49,119),(1,'fax',49,120),(1,'email',49,49),(1,'name',49,121),(1,'legal_address',49,122),(1,'defacto_address',49,123),(1,'post_address',49,124),(1,'inn',49,125),(1,'account',49,126),(1,'bank',49,127),(1,'bank_account',49,128),(1,'bik',49,129),(1,'ogrn',49,130),(1,'kpp',49,131),(1,'class_name',50,132),(1,'delivery_type_id',50,133),(1,'delivery_type_guid',50,134),(1,'description',51,135),(1,'delivery_type_id',51,136),(1,'price',51,137),(1,'tax_rate_id',51,138),(1,'disabled',51,139),(1,'domain_id_list',51,140),(1,'payment_mode',51,141),(1,'payment_subject',51,142),(1,'disabled_types_of_payment',51,143),(1,'codename',52,40),(1,'priority',52,111),(1,'order_items',53,144),(1,'number',53,145),(1,'social_order_id',53,146),(1,'yandex_order_id',53,147),(1,'customer_id',53,148),(1,'domain_id',53,149),(1,'manager_id',53,150),(1,'status_id',53,151),(1,'total_original_price',53,152),(1,'total_price',53,153),(1,'total_amount',53,154),(1,'status_change_date',53,155),(1,'order_date',53,156),(1,'order_discount_value',53,157),(1,'is_reserved',53,158),(1,'service_info',53,159),(1,'credit-status',53,160),(1,'contractsigningdeadline',53,161),(1,'contractdeliverydeadline',53,162),(1,'banksigningappointmenttime',53,163),(1,'isconfirmed',53,164),(1,'signingtype',53,165),(1,'beingprocessed',53,166),(1,'http_referer',53,167),(1,'http_target',53,168),(1,'source_domain',53,169),(1,'utm_medium',53,170),(1,'utm_term',53,171),(1,'utm_campaign',53,172),(1,'utm_content',53,173),(1,'order_create_date',53,174),(1,'payment_id',53,175),(1,'payment_name',53,176),(1,'payment_status_id',53,177),(1,'payment_date',53,178),(1,'payment_document_num',53,179),(1,'legal_person',53,180),(1,'delivery_id',53,181),(1,'delivery_name',53,182),(1,'delivery_status_id',53,183),(1,'delivery_address',53,184),(1,'delivery_date',53,185),(1,'pickup_date',53,186),(1,'delivery_provider',53,187),(1,'delivery_tariff',53,188),(1,'delivery_type',53,189),(1,'pickup_type',53,190),(1,'delivery_price',53,191),(1,'delivery_point_in',53,192),(1,'delivery_point_out',53,193),(1,'total_weight',53,194),(1,'total_width',53,195),(1,'total_height',53,196),(1,'total_length',53,197),(1,'delivery_allow_date',53,198),(1,'order_discount_id',53,199),(1,'bonus',53,200),(1,'need_export',53,201),(1,'purchaser_one_click',53,202),(1,'login',54,203),(1,'password',54,204),(1,'groups',54,205),(1,'e-mail',54,206),(1,'activate_code',54,207),(1,'loginza',54,208),(1,'is_activated',54,209),(1,'last_request_time',54,210),(1,'subscribed_pages',54,211),(1,'rated_pages',54,212),(1,'is_online',54,213),(1,'messages_count',54,214),(1,'orders_refs',54,215),(1,'delivery_addresses',54,216),(1,'user_dock',54,217),(1,'preffered_currency',54,218),(1,'user_settings_data',54,219),(1,'last_order',54,220),(1,'bonus',54,221),(1,'legal_persons',54,222),(1,'spent_bonus',54,223),(1,'filemanager_directory',54,224),(1,'appended_file_extensions',54,225),(1,'register_date',54,226),(1,'tickets_color',54,227),(1,'favorite_domain_list',54,228),(1,'lname',54,229),(1,'fname',54,230),(1,'father_name',54,231),(1,'phone',54,232),(1,'referer',54,233),(1,'target',54,234),(1,'primary',55,235),(1,'proc',56,236),(1,'modificator_type_id',56,65),(1,'rule_type_id',57,70),(1,'users',57,237),(1,'menu_id',58,238),(1,'menuhierarchy',58,239),(1,'title',60,2),(1,'h1',60,3),(1,'meta_keywords',60,6),(1,'meta_descriptions',60,5),(1,'tags',60,7),(1,'anons',60,240),(1,'content',60,4),(1,'source',60,241),(1,'source_url',60,242),(1,'publish_time',60,243),(1,'begin_time',60,244),(1,'end_time',60,245),(1,'menu_pic_ua',60,8),(1,'menu_pic_a',60,9),(1,'header_pic',60,10),(1,'robots_deny',60,11),(1,'show_submenu',60,12),(1,'is_expanded',60,13),(1,'is_unindexed',60,14),(1,'anons_pic',60,246),(1,'publish_pic',60,247),(1,'subjects',60,248),(1,'rate_voters',60,15),(1,'rate_sum',60,16),(1,'expiration_date',60,17),(1,'notification_date',60,18),(1,'publish_comments',60,19),(1,'publish_status',60,20),(1,'locktime',60,21),(1,'lockuser',60,22),(1,'title',61,2),(1,'h1',61,3),(1,'meta_keywords',61,6),(1,'meta_descriptions',61,5),(1,'tags',61,7),(1,'content',61,4),(1,'menu_pic_ua',61,8),(1,'menu_pic_a',61,9),(1,'header_pic',61,10),(1,'robots_deny',61,11),(1,'show_submenu',61,12),(1,'is_expanded',61,13),(1,'is_unindexed',61,14),(1,'rate_voters',61,15),(1,'rate_sum',61,16),(1,'expiration_date',61,17),(1,'notification_date',61,18),(1,'publish_comments',61,19),(1,'publish_status',61,20),(1,'locktime',61,21),(1,'lockuser',61,22),(1,'user_id',62,249),(1,'message',62,250),(1,'x',62,251),(1,'y',62,252),(1,'width',62,253),(1,'height',62,254),(1,'create_time',62,255),(1,'url',62,256),(1,'title',63,2),(1,'h1',63,3),(1,'description',63,257),(1,'meta_descriptions',63,5),(1,'meta_keywords',63,6),(1,'tags',63,7),(1,'friendlist',63,258),(1,'menu_pic_ua',63,8),(1,'menu_pic_a',63,9),(1,'header_pic',63,10),(1,'robots_deny',63,11),(1,'show_submenu',63,12),(1,'is_expanded',63,13),(1,'is_unindexed',63,14),(1,'rate_voters',63,15),(1,'rate_sum',63,16),(1,'is_registrated',64,259),(1,'user_id',64,260),(1,'nickname',64,261),(1,'email',64,262),(1,'ip',64,263),(1,'content',65,4),(1,'author_id',65,264),(1,'publish_time',65,243),(1,'rate_voters',65,15),(1,'rate_sum',65,16),(1,'is_spam',65,265),(1,'title',66,2),(1,'h1',66,3),(1,'content',66,4),(1,'meta_descriptions',66,5),(1,'meta_keywords',66,6),(1,'tags',66,7),(1,'publish_time',66,243),(1,'menu_pic_ua',66,8),(1,'menu_pic_a',66,9),(1,'header_pic',66,10),(1,'robots_deny',66,11),(1,'show_submenu',66,12),(1,'is_expanded',66,13),(1,'is_unindexed',66,14),(1,'rate_voters',66,15),(1,'rate_sum',66,16),(1,'expiration_date',66,17),(1,'notification_date',66,18),(1,'publish_comments',66,19),(1,'publish_status',66,20),(1,'locktime',66,21),(1,'lockuser',66,22),(1,'only_for_friends',66,266),(1,'is_spam',66,265),(1,'title',67,2),(1,'h1',67,3),(1,'meta_keywords',67,6),(1,'meta_descriptions',67,5),(1,'tags',67,7),(1,'descr',67,267),(1,'topics_count',67,268),(1,'messages_count',67,269),(1,'last_message',67,270),(1,'menu_pic_ua',67,8),(1,'menu_pic_a',67,9),(1,'header_pic',67,10),(1,'robots_deny',67,11),(1,'show_submenu',67,12),(1,'is_expanded',67,13),(1,'is_unindexed',67,14),(1,'rate_voters',67,15),(1,'rate_sum',67,16),(1,'expiration_date',67,17),(1,'notification_date',67,18),(1,'publish_comments',67,19),(1,'publish_status',67,20),(1,'locktime',67,21),(1,'lockuser',67,22),(1,'title',68,2),(1,'h1',68,3),(1,'meta_keywords',68,6),(1,'tags',68,7),(1,'meta_descriptions',68,5),(1,'messages_count',68,269),(1,'last_message',68,270),(1,'menu_pic_ua',68,8),(1,'menu_pic_a',68,9),(1,'header_pic',68,10),(1,'robots_deny',68,11),(1,'show_submenu',68,12),(1,'is_expanded',68,13),(1,'is_unindexed',68,14),(1,'publish_time',68,243),(1,'author_id',68,264),(1,'last_post_time',68,271),(1,'rate_voters',68,15),(1,'rate_sum',68,16),(1,'expiration_date',68,17),(1,'notification_date',68,18),(1,'publish_comments',68,19),(1,'publish_status',68,20),(1,'locktime',68,21),(1,'lockuser',68,22),(1,'title',69,2),(1,'h1',69,3),(1,'meta_keywords',69,6),(1,'meta_descriptions',69,5),(1,'tags',69,7),(1,'menu_pic_ua',69,8),(1,'menu_pic_a',69,9),(1,'header_pic',69,10),(1,'robots_deny',69,11),(1,'show_submenu',69,12),(1,'is_expanded',69,13),(1,'is_unindexed',69,14),(1,'message',69,272),(1,'author_id',69,264),(1,'rate_voters',69,15),(1,'rate_sum',69,16),(1,'expiration_date',69,17),(1,'notification_date',69,18),(1,'publish_comments',69,19),(1,'publish_status',69,20),(1,'locktime',69,21),(1,'lockuser',69,22),(1,'title',70,2),(1,'h1',70,3),(1,'meta_keywords',70,6),(1,'meta_descriptions',70,5),(1,'tags',70,7),(1,'menu_pic_ua',70,8),(1,'menu_pic_a',70,9),(1,'header_pic',70,10),(1,'robots_deny',70,11),(1,'show_submenu',70,12),(1,'is_expanded',70,13),(1,'is_unindexed',70,14),(1,'message',70,272),(1,'publish_time',70,243),(1,'author_id',70,264),(1,'rate_voters',70,15),(1,'rate_sum',70,16),(1,'expiration_date',70,17),(1,'notification_date',70,18),(1,'publish_comments',70,19),(1,'publish_status',70,20),(1,'locktime',70,21),(1,'lockuser',70,22),(1,'is_spam',70,265),(1,'count',71,273),(1,'poll_rel',71,274),(1,'title',72,2),(1,'h1',72,3),(1,'meta_keywords',72,6),(1,'meta_descriptions',72,5),(1,'tags',72,7),(1,'menu_pic_ua',72,8),(1,'menu_pic_a',72,9),(1,'header_pic',72,10),(1,'robots_deny',72,11),(1,'show_submenu',72,12),(1,'is_expanded',72,13),(1,'is_unindexed',72,14),(1,'is_closed',72,275),(1,'question',72,276),(1,'publish_time',72,243),(1,'answers',72,277),(1,'total_count',72,278),(1,'rate_voters',72,15),(1,'rate_sum',72,16),(1,'expiration_date',72,17),(1,'notification_date',72,18),(1,'publish_comments',72,19),(1,'publish_status',72,20),(1,'locktime',72,21),(1,'lockuser',72,22),(1,'title',73,2),(1,'h1',73,3),(1,'content',73,4),(1,'meta_descriptions',73,5),(1,'meta_keywords',73,6),(1,'tags',73,7),(1,'menu_pic_ua',73,8),(1,'menu_pic_a',73,9),(1,'header_pic',73,10),(1,'robots_deny',73,11),(1,'show_submenu',73,12),(1,'is_expanded',73,13),(1,'is_unindexed',73,14),(1,'rate_voters',73,15),(1,'rate_sum',73,16),(1,'expiration_date',73,17),(1,'notification_date',73,18),(1,'publish_comments',73,19),(1,'publish_status',73,20),(1,'locktime',73,21),(1,'lockuser',73,22),(1,'form_id',73,279),(1,'destination_address',74,280),(1,'sender_ip',74,281),(1,'sending_time',74,282),(1,'wf_message',74,283),(1,'from_email_template',75,284),(1,'from_template',75,285),(1,'subject_template',75,286),(1,'master_template',75,287),(1,'autoreply_from_email_template',75,288),(1,'autoreply_from_template',75,289),(1,'autoreply_subject_template',75,290),(1,'autoreply_email_recipient',75,291),(1,'autoreply_template',75,292),(1,'posted_message',75,293),(1,'form_id',75,294),(1,'address_description',76,295),(1,'address_list',76,296),(1,'form_id',76,297),(1,'title',77,2),(1,'h1',77,3),(1,'meta_descriptions',77,5),(1,'meta_keywords',77,6),(1,'tags',77,7),(1,'menu_pic_ua',77,8),(1,'menu_pic_a',77,9),(1,'header_pic',77,10),(1,'robots_deny',77,11),(1,'show_submenu',77,12),(1,'is_expanded',77,13),(1,'is_unindexed',77,14),(1,'descr',77,267),(1,'create_time',77,298),(1,'user_id',77,299),(1,'rate_voters',77,15),(1,'rate_sum',77,16),(1,'expiration_date',77,17),(1,'notification_date',77,18),(1,'publish_comments',77,19),(1,'publish_status',77,20),(1,'locktime',77,21),(1,'lockuser',77,22),(1,'title',78,2),(1,'h1',78,3),(1,'meta_descriptions',78,5),(1,'meta_keywords',78,6),(1,'tags',78,7),(1,'menu_pic_ua',78,8),(1,'menu_pic_a',78,9),(1,'header_pic',78,10),(1,'robots_deny',78,11),(1,'show_submenu',78,12),(1,'is_expanded',78,13),(1,'is_unindexed',78,14),(1,'photo',78,300),(1,'descr',78,267),(1,'create_time',78,298),(1,'user_id',78,299),(1,'rate_voters',78,15),(1,'rate_sum',78,16),(1,'expiration_date',78,17),(1,'notification_date',78,18),(1,'publish_comments',78,19),(1,'publish_status',78,20),(1,'locktime',78,21),(1,'lockuser',78,22),(1,'title',79,2),(1,'h1',79,3),(1,'content',79,4),(1,'meta_descriptions',79,5),(1,'meta_keywords',79,6),(1,'tags',79,7),(1,'menu_pic_ua',79,8),(1,'menu_pic_a',79,9),(1,'header_pic',79,10),(1,'robots_deny',79,11),(1,'show_submenu',79,12),(1,'is_expanded',79,13),(1,'is_unindexed',79,14),(1,'rate_voters',79,15),(1,'rate_sum',79,16),(1,'expiration_date',79,17),(1,'notification_date',79,18),(1,'publish_comments',79,19),(1,'publish_status',79,20),(1,'locktime',79,21),(1,'lockuser',79,22),(1,'title',80,2),(1,'h1',80,3),(1,'content',80,4),(1,'meta_descriptions',80,5),(1,'meta_keywords',80,6),(1,'tags',80,7),(1,'menu_pic_ua',80,8),(1,'menu_pic_a',80,9),(1,'header_pic',80,10),(1,'robots_deny',80,11),(1,'show_submenu',80,12),(1,'is_expanded',80,13),(1,'is_unindexed',80,14),(1,'rate_voters',80,15),(1,'rate_sum',80,16),(1,'expiration_date',80,17),(1,'notification_date',80,18),(1,'publish_comments',80,19),(1,'publish_status',80,20),(1,'locktime',80,21),(1,'lockuser',80,22),(1,'title',81,2),(1,'h1',81,3),(1,'meta_descriptions',81,5),(1,'meta_keywords',81,6),(1,'tags',81,7),(1,'question',81,276),(1,'answer',81,301),(1,'author_id',81,264),(1,'publish_time',81,243),(1,'menu_pic_ua',81,8),(1,'menu_pic_a',81,9),(1,'header_pic',81,10),(1,'robots_deny',81,11),(1,'show_submenu',81,12),(1,'is_expanded',81,13),(1,'is_unindexed',81,14),(1,'expiration_date',81,17),(1,'notification_date',81,18),(1,'publish_comments',81,19),(1,'publish_status',81,20),(1,'locktime',81,21),(1,'lockuser',81,22),(1,'is_spam',81,265),(1,'disp_last_release',82,302),(1,'disp_description',82,303),(1,'forced_subscribers',82,304),(1,'news_relation',82,305),(1,'is_active',82,306),(1,'load_from_forum',82,307),(1,'days',82,308),(1,'hours',82,309),(1,'status',83,310),(1,'date',83,311),(1,'disp_reference',83,312),(1,'header',84,313),(1,'body',84,314),(1,'release_reference',84,315),(1,'attach_file',84,316),(1,'msg_date',84,317),(1,'short_body',84,318),(1,'new_relation',84,319),(1,'lname',85,320),(1,'fname',85,321),(1,'father_name',85,322),(1,'gender',85,323),(1,'uid',85,324),(1,'subscriber_dispatches',85,325),(1,'sent_release_list',85,326),(1,'subscribe_date',85,327),(1,'title',86,2),(1,'h1',86,3),(1,'meta_keywords',86,6),(1,'meta_descriptions',86,5),(1,'tags',86,7),(1,'menu_pic_ua',86,8),(1,'menu_pic_a',86,9),(1,'header_pic',86,10),(1,'robots_deny',86,11),(1,'show_submenu',86,12),(1,'is_expanded',86,13),(1,'is_unindexed',86,14),(1,'descr',86,267),(1,'social_category_vkontakte',86,328),(1,'rate_voters',86,15),(1,'rate_sum',86,16),(1,'expiration_date',86,17),(1,'notification_date',86,18),(1,'publish_comments',86,19),(1,'publish_status',86,20),(1,'locktime',86,21),(1,'lockuser',86,22),(1,'index_source',86,329),(1,'index_state',86,330),(1,'index_date',86,331),(1,'index_choose',86,332),(1,'index_level',86,333),(1,'title',87,2),(1,'h1',87,3),(1,'meta_keywords',87,6),(1,'meta_descriptions',87,5),(1,'tags',87,7),(1,'date_create_object',87,334),(1,'menu_pic_ua',87,8),(1,'menu_pic_a',87,9),(1,'header_pic',87,10),(1,'robots_deny',87,11),(1,'is_unindexed',87,14),(1,'tax_rate_id',87,335),(1,'price',87,336),(1,'payment_mode',87,337),(1,'payment_subject',87,338),(1,'stores_state',87,339),(1,'reserved',87,340),(1,'common_quantity',87,341),(1,'trade_offer_image',87,342),(1,'trade_offer_list',87,343),(1,'rate_voters',87,15),(1,'rate_sum',87,16),(1,'expiration_date',87,17),(1,'notification_date',87,18),(1,'publish_comments',87,19),(1,'publish_status',87,20),(1,'locktime',87,21),(1,'lockuser',87,22),(1,'fname',88,47),(1,'lname',88,46),(1,'father_name',88,48),(1,'preffered_currency',88,344),(1,'last_order',88,345),(1,'bonus',88,346),(1,'spent_bonus',88,347),(1,'email',88,49),(1,'phone',88,50),(1,'ip',88,348),(1,'delivery_addresses',88,349),(1,'legal_persons',88,350),(1,'modificator_type_id',89,65),(1,'size',89,351),(1,'rule_type_id',90,70),(1,'catalog_items',90,352),(1,'rule_type_id',91,70),(1,'start_date',91,353),(1,'end_date',91,354),(1,'rule_type_id',92,70),(1,'minimum',92,355),(1,'maximum',92,356),(1,'rule_type_id',93,70),(1,'minimal',93,357),(1,'maximum',93,358),(1,'rule_type_id',94,70),(1,'user_groups',94,359),(1,'rule_type_id',95,70),(1,'related_items',95,360),(1,'description',96,135),(1,'delivery_type_id',96,136),(1,'price',96,137),(1,'tax_rate_id',96,138),(1,'disabled',96,139),(1,'domain_id_list',96,140),(1,'payment_mode',96,141),(1,'payment_subject',96,142),(1,'disabled_types_of_payment',96,143),(1,'description',97,135),(1,'delivery_type_id',97,136),(1,'tax_rate_id',97,138),(1,'disabled',97,139),(1,'domain_id_list',97,140),(1,'payment_mode',97,141),(1,'payment_subject',97,142),(1,'disabled_types_of_payment',97,143),(1,'order_min_price',97,361),(1,'price',97,137),(1,'description',98,135),(1,'delivery_type_id',98,136),(1,'tax_rate_id',98,138),(1,'disabled',98,139),(1,'domain_id_list',98,140),(1,'payment_mode',98,141),(1,'payment_subject',98,142),(1,'disabled_types_of_payment',98,143),(1,'viewpost',98,362),(1,'zip_code',98,363),(1,'description',99,135),(1,'delivery_type_id',99,136),(1,'tax_rate_id',99,138),(1,'payment_mode',99,141),(1,'payment_subject',99,142),(1,'disabled_types_of_payment',99,143),(1,'login',99,364),(1,'password',99,365),(1,'dev_mode',99,366),(1,'keep_log',99,367),(1,'providers',99,368),(1,'delivery_types',99,369),(1,'pickup_types',99,370),(1,'settings',99,371),(1,'disabled',99,139),(1,'domain_id_list',99,140),(1,'payment_type_id',100,115),(1,'disabled',100,116),(1,'domain_id_list',100,117),(1,'reciever',100,372),(1,'reciever_inn',100,373),(1,'reciever_account',100,374),(1,'reciever_bank',100,375),(1,'bik',100,376),(1,'reciever_bank_account',100,377),(1,'payment_type_id',101,115),(1,'disabled',101,116),(1,'domain_id_list',101,117),(1,'merchant_id',101,378),(1,'private_key',101,379),(1,'receipt_data_send_enable',101,380),(1,'keep_log',101,381),(1,'payment_type_id',102,115),(1,'disabled',102,116),(1,'domain_id_list',102,117),(1,'payment_type_id',103,115),(1,'disabled',103,116),(1,'domain_id_list',103,117),(1,'login',103,382),(1,'password1',103,383),(1,'password2',103,384),(1,'test_mode',103,385),(1,'receipt_data_send_enable',103,380),(1,'payment_type_id',104,115),(1,'disabled',104,116),(1,'domain_id_list',104,117),(1,'eshopid',104,386),(1,'secretkey',104,387),(1,'payment_type_id',105,115),(1,'disabled',105,116),(1,'domain_id_list',105,117),(1,'name',105,388),(1,'legal_address',105,389),(1,'phone_number',105,390),(1,'inn',105,391),(1,'kpp',105,392),(1,'account',105,393),(1,'bank',105,394),(1,'bank_account',105,395),(1,'bik',105,376),(1,'sign_image',105,396),(1,'payment_type_id',106,115),(1,'disabled',106,116),(1,'domain_id_list',106,117),(1,'mnt_system_url',106,397),(1,'mnt_id',106,398),(1,'mnt_success_url',106,399),(1,'mnt_fail_url',106,400),(1,'mnt_data_integrity_code',106,401),(1,'mnt_test_mode',106,402),(1,'receipt_data_send_enable',106,380),(1,'payment_type_id',107,115),(1,'disabled',107,116),(1,'domain_id_list',107,117),(1,'project',107,403),(1,'key',107,404),(1,'source',107,405),(1,'payment_type_id',108,115),(1,'partnerId',108,406),(1,'apiKey',108,407),(1,'secretKey',108,387),(1,'demo_mode',108,408),(1,'disabled',108,116),(1,'domain_id_list',108,117),(1,'payment_type_id',109,115),(1,'disabled',109,116),(1,'domain_id_list',109,117),(1,'merchant_id',109,409),(1,'product_id',109,410),(1,'ok_url',109,411),(1,'secret_word',109,412),(1,'ko_url',109,413),(1,'payment_type_id',110,115),(1,'disabled',110,116),(1,'domain_id_list',110,117),(1,'shop_id',110,414),(1,'scid',110,415),(1,'bank_id',110,416),(1,'shop_password',110,417),(1,'demo_mode',110,408),(1,'receipt_data_send_enable',110,380),(1,'payment_type_id',111,115),(1,'disabled',111,116),(1,'domain_id_list',111,117),(1,'test_mode',111,385),(1,'paypalemail',111,418),(1,'return_success',111,419),(1,'cancel_return',111,420),(1,'payment_type_id',112,115),(1,'disabled',112,116),(1,'domain_id_list',112,117),(1,'shop_id',112,421),(1,'secret_key',112,422),(1,'receipt_data_send_enable',112,380),(1,'keep_log',112,381),(1,'id',113,423),(1,'descr',113,424),(1,'is_show_rand_banner',113,425),(1,'is_active',114,426),(1,'tags',114,427),(1,'url',114,428),(1,'open_in_new_window',114,429),(1,'views_count',114,430),(1,'clicks_count',114,431),(1,'max_views',114,432),(1,'show_start_date',114,433),(1,'show_till_date',114,434),(1,'user_tags',114,435),(1,'view_pages',114,436),(1,'place',114,437),(1,'not_view_pages',114,438),(1,'time_targeting_by_month_days',114,439),(1,'time_targeting_by_month',114,440),(1,'time_targeting_by_week_days',114,441),(1,'time_targeting_by_hours',114,442),(1,'time_targeting_is_active',114,443),(1,'city_targeting_city',114,444),(1,'city_targeting_is_active',114,445),(1,'priority',114,446),(1,'is_active',115,426),(1,'tags',115,427),(1,'image',115,447),(1,'width',115,448),(1,'height',115,449),(1,'alt',115,450),(1,'url',115,428),(1,'open_in_new_window',115,429),(1,'views_count',115,430),(1,'clicks_count',115,431),(1,'max_views',115,432),(1,'show_start_date',115,433),(1,'show_till_date',115,434),(1,'user_tags',115,435),(1,'view_pages',115,436),(1,'place',115,437),(1,'not_view_pages',115,438),(1,'time_targeting_by_month_days',115,439),(1,'time_targeting_by_month',115,440),(1,'time_targeting_by_week_days',115,441),(1,'time_targeting_by_hours',115,442),(1,'time_targeting_is_active',115,443),(1,'city_targeting_city',115,444),(1,'city_targeting_is_active',115,445),(1,'priority',115,446),(1,'is_active',116,426),(1,'tags',116,427),(1,'swf',116,451),(1,'width',116,448),(1,'height',116,449),(1,'swf_quality',116,452),(1,'url',116,428),(1,'open_in_new_window',116,429),(1,'views_count',116,430),(1,'clicks_count',116,431),(1,'max_views',116,432),(1,'show_start_date',116,433),(1,'show_till_date',116,434),(1,'user_tags',116,435),(1,'view_pages',116,436),(1,'place',116,437),(1,'not_view_pages',116,438),(1,'time_targeting_by_month_days',116,439),(1,'time_targeting_by_month',116,440),(1,'time_targeting_by_week_days',116,441),(1,'time_targeting_by_hours',116,442),(1,'time_targeting_is_active',116,443),(1,'city_targeting_city',116,444),(1,'city_targeting_is_active',116,445),(1,'priority',116,446),(1,'is_active',117,426),(1,'tags',117,427),(1,'html_content',117,453),(1,'url',117,428),(1,'open_in_new_window',117,429),(1,'views_count',117,430),(1,'clicks_count',117,431),(1,'max_views',117,432),(1,'show_start_date',117,433),(1,'show_till_date',117,434),(1,'user_tags',117,435),(1,'view_pages',117,436),(1,'place',117,437),(1,'not_view_pages',117,438),(1,'time_targeting_by_month_days',117,439),(1,'time_targeting_by_month',117,440),(1,'time_targeting_by_week_days',117,441),(1,'time_targeting_by_hours',117,442),(1,'time_targeting_is_active',117,443),(1,'city_targeting_city',117,444),(1,'city_targeting_is_active',117,445),(1,'priority',117,446),(1,'picture',118,454),(1,'is_hidden',118,455),(1,'format',119,456),(1,'elements',119,457),(1,'excluded_elements',119,458),(1,'cache_time',119,459),(1,'source_name',119,460),(1,'encoding_export',119,461),(1,'format',120,462),(1,'file',120,463),(1,'elements',120,464),(1,'encoding_import',120,465),(1,'source_name',120,466),(1,'title',121,2),(1,'h1',121,3),(1,'meta_descriptions',121,5),(1,'meta_keywords',121,6),(1,'tags',121,7),(1,'content',121,4),(1,'menu_pic_ua',121,8),(1,'menu_pic_a',121,9),(1,'header_pic',121,10),(1,'robots_deny',121,11),(1,'show_submenu',121,12),(1,'is_expanded',121,13),(1,'is_unindexed',121,14),(1,'fs_file',121,467),(1,'downloads_counter',121,468),(1,'rate_voters',121,15),(1,'rate_sum',121,16),(1,'expiration_date',121,17),(1,'notification_date',121,18),(1,'publish_comments',121,19),(1,'publish_status',121,20),(1,'locktime',121,21),(1,'lockuser',121,22),(1,'custom_id',122,469),(1,'lang_id',122,470),(1,'domain_id',122,471),(1,'domain_id',123,43),(1,'domain_id',124,43),(1,'title',125,2),(1,'h1',125,3),(1,'content',125,4),(1,'meta_descriptions',125,5),(1,'meta_keywords',125,6),(1,'tags',125,7),(1,'menu_pic_ua',125,8),(1,'menu_pic_a',125,9),(1,'header_pic',125,10),(1,'robots_deny',125,11),(1,'show_submenu',125,12),(1,'is_expanded',125,13),(1,'is_unindexed',125,14),(1,'rate_voters',125,15),(1,'rate_sum',125,16),(1,'expiration_date',125,17),(1,'notification_date',125,18),(1,'publish_comments',125,19),(1,'publish_status',125,20),(1,'locktime',125,21),(1,'lockuser',125,22),(1,'appoint_service_choice_title',125,472),(1,'appoint_hint_step_text',125,473),(1,'appoint_personal_step_title',125,474),(1,'appoint_personal_choice_title',125,475),(1,'appoint_dont_care_button',125,476),(1,'appoint_dont_care_hint',125,477),(1,'appoint_date_step_title',125,478),(1,'appoint_date_choice_title',125,479),(1,'appoint_confirm_step_title',125,480),(1,'appoint_book_time_button',125,481),(1,'appoint_book_time_hint',125,482);
/*!40000 ALTER TABLE `cms3_import_fields` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cms3_import_groups`
--

DROP TABLE IF EXISTS `cms3_import_groups`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cms3_import_groups` (
  `source_id` int(10) unsigned NOT NULL,
  `group_name` varchar(48) NOT NULL,
  `type_id` int(10) unsigned NOT NULL,
  `new_id` int(10) unsigned NOT NULL,
  KEY `source_id` (`source_id`),
  KEY `type_id` (`type_id`),
  KEY `group_name` (`group_name`),
  KEY `new_id` (`new_id`),
  CONSTRAINT `FK_GroupSourceId_To_Source` FOREIGN KEY (`source_id`) REFERENCES `cms3_import_sources` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `FK_NewGroupId_To_ObjectTypeId` FOREIGN KEY (`type_id`) REFERENCES `cms3_import_types` (`new_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `FK_NewId_To_ObjectTypeGroupId` FOREIGN KEY (`new_id`) REFERENCES `cms3_object_field_groups` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cms3_import_groups`
--

LOCK TABLES `cms3_import_groups` WRITE;
/*!40000 ALTER TABLE `cms3_import_groups` DISABLE KEYS */;
INSERT INTO `cms3_import_groups` VALUES (1,'svojstva_statusa_stranicy',2,1),(1,'common',3,2),(1,'menu_view',3,3),(1,'more_params',3,4),(1,'rate_props',3,5),(1,'svojstva_publikacii',3,6),(1,'locks',3,7),(1,'common',6,8),(1,'common',7,9),(1,'menu_view',7,10),(1,'more_params',7,11),(1,'rate_voters',7,12),(1,'svojstva_publikacii',7,13),(1,'locks',7,14),(1,'common',8,15),(1,'props',9,16),(1,'params_more',10,17),(1,'common',14,18),(1,'common',15,19),(1,'additional',16,20),(1,'additional',17,21),(1,'params',19,22),(1,'common',20,23),(1,'props_currency',21,24),(1,'credit_status_props',22,25),(1,'common',23,26),(1,'common',24,27),(1,'personal',25,28),(1,'common',27,29),(1,'common',28,30),(1,'common',29,31),(1,'discount_type_props',30,32),(1,'discount_modificator_type_props',31,33),(1,'discount_modificator_props',32,34),(1,'discount_rule_type_props',33,35),(1,'discount_rule_props',34,36),(1,'common',35,37),(1,'common',36,38),(1,'network_system_props',37,39),(1,'props',38,40),(1,'pages',38,41),(1,'network_system_props',38,42),(1,'svojstva_gruppy_polzovatelej',39,43),(1,'common',40,44),(1,'item_type_props',41,45),(1,'discount_props',42,46),(1,'item_props',44,47),(1,'item_optioned_props',44,48),(1,'trade_offers',44,49),(1,'order_status_props',45,50),(1,'payment_type_props',46,51),(1,'payment_props',47,52),(1,'order_status_props',48,53),(1,'general',49,54),(1,'addresses',49,55),(1,'payment',49,56),(1,'delivery_type_props',50,57),(1,'delivery_description_props',51,58),(1,'order_status_props',52,59),(1,'order_props',53,60),(1,'order_credit_props',53,61),(1,'statistic_info',53,62),(1,'order_payment_props',53,63),(1,'order_delivery_props',53,64),(1,'order_discount_props',53,65),(1,'integration_date',53,66),(1,'purchase_one_click',53,67),(1,'idetntify_data',54,68),(1,'more_info',54,69),(1,'short_info',54,70),(1,'delivery',54,71),(1,'statistic_info',54,72),(1,'store_props',55,73),(1,'discount_modificator_props',56,74),(1,'discount_rule_props',57,75),(1,'common',58,76),(1,'common',60,77),(1,'item_props',60,78),(1,'menu_view',60,79),(1,'more_params',60,80),(1,'news_images',60,81),(1,'subjects_block',60,82),(1,'rate_voters',60,83),(1,'svojstva_publikacii',60,84),(1,'locks',60,85),(1,'common',61,86),(1,'menu_view',61,87),(1,'more_params',61,88),(1,'rate_voters',61,89),(1,'svojstva_publikacii',61,90),(1,'locks',61,91),(1,'props',62,92),(1,'common',63,93),(1,'menu_view',63,94),(1,'more_params',63,95),(1,'rate_props',63,96),(1,'props',64,97),(1,'common',65,98),(1,'rate_props',65,99),(1,'antispam',65,100),(1,'common',66,101),(1,'menu_view',66,102),(1,'more_params',66,103),(1,'rate_props',66,104),(1,'svojstva_publikacii',66,105),(1,'locks',66,106),(1,'privacy',66,107),(1,'antispam',66,108),(1,'common',67,109),(1,'menu_view',67,110),(1,'more_params',67,111),(1,'rate_voters',67,112),(1,'svojstva_publikacii',67,113),(1,'locks',67,114),(1,'common',68,115),(1,'menu_view',68,116),(1,'more_params',68,117),(1,'topic_props',68,118),(1,'rate_voters',68,119),(1,'svojstva_publikacii',68,120),(1,'locks',68,121),(1,'common',69,122),(1,'menu_view',69,123),(1,'more_params',69,124),(1,'message_props',69,125),(1,'rate_voters',69,126),(1,'svojstva_publikacii',69,127),(1,'locks',69,128),(1,'common',70,129),(1,'menu_view',70,130),(1,'more_params',70,131),(1,'comment_props',70,132),(1,'rate_voters',70,133),(1,'svojstva_publikacii',70,134),(1,'locks',70,135),(1,'antispam',70,136),(1,'common_props',71,137),(1,'common',72,138),(1,'menu_view',72,139),(1,'more_params',72,140),(1,'poll_props',72,141),(1,'rate_voters',72,142),(1,'svojstva_publikacii',72,143),(1,'locks',72,144),(1,'common',73,145),(1,'menu_view',73,146),(1,'more_params',73,147),(1,'rate_props',73,148),(1,'svojstva_publikacii',73,149),(1,'locks',73,150),(1,'binding',73,151),(1,'sendingdata',74,152),(1,'templates',75,153),(1,'auto_reply',75,154),(1,'messages',75,155),(1,'binding',75,156),(1,'list',76,157),(1,'common',77,158),(1,'menu_view',77,159),(1,'more_params',77,160),(1,'album_props',77,161),(1,'rate_voters',77,162),(1,'svojstva_publikacii',77,163),(1,'locks',77,164),(1,'common',78,165),(1,'menu_view',78,166),(1,'more_params',78,167),(1,'photo_props',78,168),(1,'rate_voters',78,169),(1,'svojstva_publikacii',78,170),(1,'locks',78,171),(1,'common',79,172),(1,'menu_view',79,173),(1,'more_params',79,174),(1,'rate_voters',79,175),(1,'svojstva_publikacii',79,176),(1,'locks',79,177),(1,'common',80,178),(1,'menu_view',80,179),(1,'more_params',80,180),(1,'rate_voters',80,181),(1,'svojstva_publikacii',80,182),(1,'locks',80,183),(1,'common',81,184),(1,'menu_view',81,185),(1,'more_params',81,186),(1,'svojstva_publikacii',81,187),(1,'locks',81,188),(1,'antispam',81,189),(1,'grp_disp_props',82,190),(1,'auto_settings',82,191),(1,'grp_disp_release_props',83,192),(1,'grp_disp_msg_props',84,193),(1,'grp_disp_msg_extended',84,194),(1,'grp_sbs_props',85,195),(1,'grp_sbs_extended',85,196),(1,'common',86,197),(1,'menu_view',86,198),(1,'more_params',86,199),(1,'dopolnitelno',86,200),(1,'rate_voters',86,201),(1,'svojstva_publikacii',86,202),(1,'locks',86,203),(1,'filter_index',86,204),(1,'common',87,205),(1,'menu_view',87,206),(1,'more_params',87,207),(1,'cenovye_svojstva',87,208),(1,'catalog_option_props',87,209),(1,'catalog_stores_props',87,210),(1,'trade_offers',87,211),(1,'rate_voters',87,212),(1,'svojstva_publikacii',87,213),(1,'locks',87,214),(1,'personal_info',88,215),(1,'contact_props',88,216),(1,'delivery',88,217),(1,'yuridicheskie_dannye',88,218),(1,'discount_modificator_props',89,219),(1,'discount_rule_props',90,220),(1,'discount_rule_props',91,221),(1,'discount_rule_props',92,222),(1,'discount_rule_props',93,223),(1,'discount_rule_props',94,224),(1,'discount_rule_props',95,225),(1,'delivery_description_props',96,226),(1,'delivery_description_props',97,227),(1,'delivery_courier_props',97,228),(1,'delivery_description_props',98,229),(1,'settings',98,230),(1,'delivery_description_props',99,231),(1,'settings',99,232),(1,'payment_props',100,233),(1,'settings',100,234),(1,'payment_props',101,235),(1,'settings',101,236),(1,'payment_props',102,237),(1,'payment_props',103,238),(1,'settings',103,239),(1,'payment_props',104,240),(1,'settings',104,241),(1,'payment_props',105,242),(1,'organization',105,243),(1,'payment_props',106,244),(1,'settings',106,245),(1,'payment_props',107,246),(1,'settings',107,247),(1,'payment_props',108,248),(1,'payment_props',109,249),(1,'settings',109,250),(1,'payment_props',110,251),(1,'settings',110,252),(1,'payment_props',111,253),(1,'settings',111,254),(1,'payment_props',112,255),(1,'settings',112,256),(1,'common_props',113,257),(1,'common',114,258),(1,'redirect_props',114,259),(1,'view_params',114,260),(1,'view_pages',114,261),(1,'time_targeting',114,262),(1,'city_targeting',114,263),(1,'view_settings',114,264),(1,'common',115,265),(1,'banner_custom_props',115,266),(1,'redirect_props',115,267),(1,'view_params',115,268),(1,'view_pages',115,269),(1,'time_targeting',115,270),(1,'city_targeting',115,271),(1,'view_settings',115,272),(1,'common',116,273),(1,'banner_custom_props',116,274),(1,'redirect_props',116,275),(1,'view_params',116,276),(1,'view_pages',116,277),(1,'time_targeting',116,278),(1,'city_targeting',116,279),(1,'view_settings',116,280),(1,'common',117,281),(1,'banner_custom_props',117,282),(1,'redirect_props',117,283),(1,'view_params',117,284),(1,'view_pages',117,285),(1,'time_targeting',117,286),(1,'city_targeting',117,287),(1,'view_settings',117,288),(1,'svojstva',118,289),(1,'common',119,290),(1,'common',120,291),(1,'common',121,292),(1,'menu_view',121,293),(1,'more_params',121,294),(1,'fs_file_props',121,295),(1,'rate_voters',121,296),(1,'svojstva_publikacii',121,297),(1,'locks',121,298),(1,'common',122,299),(1,'common',123,300),(1,'common',124,301),(1,'common',125,302),(1,'menu_view',125,303),(1,'more_params',125,304),(1,'rate_props',125,305),(1,'svojstva_publikacii',125,306),(1,'locks',125,307),(1,'appointment',125,308);
/*!40000 ALTER TABLE `cms3_import_groups` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cms3_import_langs`
--

DROP TABLE IF EXISTS `cms3_import_langs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cms3_import_langs` (
  `source_id` int(10) unsigned NOT NULL,
  `old_id` varchar(255) NOT NULL,
  `new_id` int(10) unsigned NOT NULL,
  KEY `source_id` (`source_id`,`old_id`,`new_id`),
  KEY `old_id` (`old_id`,`new_id`),
  KEY `new_id` (`new_id`),
  CONSTRAINT `FK_LangSourceId_To_Source` FOREIGN KEY (`source_id`) REFERENCES `cms3_import_sources` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `FK_NewId_To_LangId` FOREIGN KEY (`new_id`) REFERENCES `cms3_langs` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cms3_import_langs`
--

LOCK TABLES `cms3_import_langs` WRITE;
/*!40000 ALTER TABLE `cms3_import_langs` DISABLE KEYS */;
INSERT INTO `cms3_import_langs` VALUES (1,'1',1),(1,'7',2);
/*!40000 ALTER TABLE `cms3_import_langs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cms3_import_links`
--

DROP TABLE IF EXISTS `cms3_import_links`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cms3_import_links` (
  `external_id` int(10) unsigned NOT NULL,
  `internal_id` int(10) unsigned NOT NULL,
  `source_id` int(10) unsigned NOT NULL,
  KEY `external_id` (`external_id`,`source_id`),
  KEY `internal_id` (`internal_id`,`source_id`),
  KEY `source_id` (`source_id`),
  CONSTRAINT `cms3_import_links_ibfk_1` FOREIGN KEY (`internal_id`) REFERENCES `cms3_links` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `cms3_import_links_ibfk_2` FOREIGN KEY (`source_id`) REFERENCES `cms3_import_sources` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cms3_import_links`
--

LOCK TABLES `cms3_import_links` WRITE;
/*!40000 ALTER TABLE `cms3_import_links` DISABLE KEYS */;
/*!40000 ALTER TABLE `cms3_import_links` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cms3_import_links_sources`
--

DROP TABLE IF EXISTS `cms3_import_links_sources`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cms3_import_links_sources` (
  `external_id` int(11) unsigned NOT NULL,
  `internal_id` int(11) unsigned NOT NULL,
  `source_id` int(10) unsigned NOT NULL,
  KEY `external_id` (`external_id`,`source_id`),
  KEY `internal_id` (`internal_id`,`source_id`),
  KEY `source_id` (`source_id`),
  CONSTRAINT `cms3_import_links_sources_ibfk_1` FOREIGN KEY (`internal_id`) REFERENCES `cms3_links_sources` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `cms3_import_links_sources_ibfk_2` FOREIGN KEY (`source_id`) REFERENCES `cms3_import_sources` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cms3_import_links_sources`
--

LOCK TABLES `cms3_import_links_sources` WRITE;
/*!40000 ALTER TABLE `cms3_import_links_sources` DISABLE KEYS */;
/*!40000 ALTER TABLE `cms3_import_links_sources` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cms3_import_mail_notifications`
--

DROP TABLE IF EXISTS `cms3_import_mail_notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cms3_import_mail_notifications` (
  `external_id` int(11) unsigned NOT NULL,
  `internal_id` int(11) unsigned NOT NULL,
  `source_id` int(10) unsigned NOT NULL,
  KEY `external_id` (`external_id`,`source_id`),
  KEY `internal_id` (`internal_id`,`source_id`),
  KEY `source_id` (`source_id`),
  CONSTRAINT `cms3_import_mail_notifications_ibfk_1` FOREIGN KEY (`internal_id`) REFERENCES `cms3_mail_notifications` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `cms3_import_mail_notifications_ibfk_2` FOREIGN KEY (`source_id`) REFERENCES `cms3_import_sources` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cms3_import_mail_notifications`
--

LOCK TABLES `cms3_import_mail_notifications` WRITE;
/*!40000 ALTER TABLE `cms3_import_mail_notifications` DISABLE KEYS */;
INSERT INTO `cms3_import_mail_notifications` VALUES (1,1,1),(2,2,1),(3,3,1),(4,4,1),(5,5,1),(6,6,1),(7,7,1),(8,8,1),(9,9,1),(10,10,1),(11,11,1),(12,12,1),(13,13,1),(14,14,1),(15,15,1),(16,16,1),(17,17,1),(18,18,1),(19,19,1),(20,20,1),(21,21,1),(22,22,1);
/*!40000 ALTER TABLE `cms3_import_mail_notifications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cms3_import_mail_templates`
--

DROP TABLE IF EXISTS `cms3_import_mail_templates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cms3_import_mail_templates` (
  `external_id` int(10) unsigned NOT NULL,
  `internal_id` int(10) unsigned NOT NULL,
  `source_id` int(10) unsigned NOT NULL,
  KEY `external_id` (`external_id`,`source_id`),
  KEY `internal_id` (`internal_id`,`source_id`),
  KEY `source_id` (`source_id`),
  CONSTRAINT `cms3_import_mail_templates_ibfk_1` FOREIGN KEY (`internal_id`) REFERENCES `cms3_mail_templates` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `cms3_import_mail_templates_ibfk_2` FOREIGN KEY (`source_id`) REFERENCES `cms3_import_sources` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cms3_import_mail_templates`
--

LOCK TABLES `cms3_import_mail_templates` WRITE;
/*!40000 ALTER TABLE `cms3_import_mail_templates` DISABLE KEYS */;
INSERT INTO `cms3_import_mail_templates` VALUES (1,1,1),(2,2,1),(3,3,1),(4,4,1),(5,5,1),(6,6,1),(7,7,1),(8,8,1),(9,9,1),(10,10,1),(11,11,1),(12,12,1),(13,13,1),(14,14,1),(15,15,1),(16,16,1),(17,17,1),(18,18,1),(19,19,1),(20,20,1),(21,21,1),(22,22,1),(23,23,1),(24,24,1),(25,25,1),(26,26,1),(27,27,1),(28,28,1),(29,29,1),(30,30,1),(31,31,1),(32,32,1),(33,33,1),(34,34,1),(35,35,1),(36,36,1),(37,37,1),(38,38,1),(39,39,1),(40,40,1),(41,41,1),(42,42,1),(43,43,1),(44,44,1),(45,45,1),(46,46,1),(47,47,1),(48,48,1),(49,49,1);
/*!40000 ALTER TABLE `cms3_import_mail_templates` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cms3_import_mail_variables`
--

DROP TABLE IF EXISTS `cms3_import_mail_variables`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cms3_import_mail_variables` (
  `external_id` int(10) unsigned NOT NULL,
  `internal_id` int(10) unsigned NOT NULL,
  `source_id` int(10) unsigned NOT NULL,
  KEY `external_id` (`external_id`,`source_id`),
  KEY `internal_id` (`internal_id`,`source_id`),
  KEY `source_id` (`source_id`),
  CONSTRAINT `cms3_import_mail_variables_ibfk_1` FOREIGN KEY (`internal_id`) REFERENCES `cms3_mail_variables` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `cms3_import_mail_variables_ibfk_2` FOREIGN KEY (`source_id`) REFERENCES `cms3_import_sources` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cms3_import_mail_variables`
--

LOCK TABLES `cms3_import_mail_variables` WRITE;
/*!40000 ALTER TABLE `cms3_import_mail_variables` DISABLE KEYS */;
INSERT INTO `cms3_import_mail_variables` VALUES (1,1,1),(2,2,1),(3,3,1),(4,4,1),(5,5,1),(6,6,1),(7,7,1),(8,8,1),(9,9,1),(10,10,1),(11,11,1),(12,12,1),(13,13,1),(14,14,1),(15,15,1),(16,16,1),(17,17,1),(18,18,1),(19,19,1),(20,20,1),(21,21,1),(22,22,1),(23,23,1),(24,24,1),(25,25,1),(26,26,1),(27,27,1),(28,28,1),(29,29,1),(30,30,1),(31,31,1),(32,32,1),(33,33,1),(34,34,1),(35,35,1),(36,36,1),(37,37,1),(38,38,1),(39,39,1),(40,40,1),(41,41,1),(42,42,1),(43,43,1),(44,44,1),(45,45,1),(46,46,1),(47,47,1),(48,48,1),(49,49,1),(50,50,1),(51,51,1),(52,52,1),(53,53,1),(54,54,1),(55,55,1),(56,56,1),(57,57,1),(58,58,1),(59,59,1),(60,60,1),(61,61,1),(62,62,1),(63,63,1),(64,64,1),(65,65,1),(66,66,1),(67,67,1),(68,68,1),(69,69,1),(70,70,1),(71,71,1),(72,72,1),(73,73,1),(74,74,1),(75,75,1),(76,76,1),(77,77,1),(78,78,1),(79,79,1),(80,80,1),(81,81,1),(82,82,1),(83,83,1),(84,84,1),(85,85,1),(86,86,1),(87,87,1),(88,88,1),(89,89,1),(90,90,1),(91,91,1),(92,92,1),(93,93,1),(94,94,1),(95,95,1),(96,96,1),(97,97,1),(98,98,1),(99,99,1),(100,100,1),(101,101,1),(102,102,1),(103,103,1),(104,104,1),(105,105,1),(106,106,1),(107,107,1),(108,108,1),(109,109,1),(110,110,1),(111,111,1),(112,112,1),(113,113,1),(114,114,1),(115,115,1),(116,116,1),(117,117,1),(118,118,1),(119,119,1),(120,120,1),(121,121,1),(122,122,1),(123,123,1),(124,124,1),(125,125,1),(126,126,1),(127,127,1),(128,128,1),(129,129,1),(130,130,1),(131,131,1),(132,132,1),(133,133,1),(134,134,1),(135,135,1),(136,136,1),(137,137,1),(138,138,1),(139,139,1),(140,140,1),(141,141,1),(142,142,1),(143,143,1),(144,144,1),(145,145,1),(146,146,1),(147,147,1),(148,148,1),(149,149,1),(150,150,1),(151,151,1),(152,152,1),(153,153,1),(154,154,1),(155,155,1),(156,156,1),(157,157,1),(158,158,1),(159,159,1),(160,160,1),(161,161,1),(162,162,1),(163,163,1),(164,164,1),(165,165,1),(166,166,1),(167,167,1),(168,168,1),(169,169,1),(170,170,1),(171,171,1),(172,172,1),(173,173,1),(174,174,1),(175,175,1),(176,176,1),(177,177,1),(178,178,1),(179,179,1),(180,180,1);
/*!40000 ALTER TABLE `cms3_import_mail_variables` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cms3_import_objects`
--

DROP TABLE IF EXISTS `cms3_import_objects`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cms3_import_objects` (
  `source_id` int(10) unsigned NOT NULL,
  `old_id` varchar(255) NOT NULL,
  `new_id` int(10) unsigned NOT NULL,
  KEY `source_id` (`source_id`,`old_id`,`new_id`),
  KEY `old_id` (`old_id`,`new_id`),
  KEY `new_id` (`new_id`),
  CONSTRAINT `FK_NewId_To_ObjectsId` FOREIGN KEY (`new_id`) REFERENCES `cms3_objects` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `FK_ObjectSourceId_To_Source` FOREIGN KEY (`source_id`) REFERENCES `cms3_import_sources` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cms3_import_objects`
--

LOCK TABLES `cms3_import_objects` WRITE;
/*!40000 ALTER TABLE `cms3_import_objects` DISABLE KEYS */;
INSERT INTO `cms3_import_objects` VALUES (1,'14',182),(1,'15',181),(1,'2373',618),(1,'2374',619),(1,'2376',2),(1,'2377',3),(1,'25974',6),(1,'25975',7),(1,'25976',8),(1,'26315',9),(1,'26341',10),(1,'26905',11),(1,'26906',12),(1,'26907',13),(1,'26908',14),(1,'26909',15),(1,'26910',16),(1,'26911',17),(1,'26912',18),(1,'26913',19),(1,'26914',20),(1,'26915',21),(1,'26916',22),(1,'26917',23),(1,'26918',24),(1,'26919',25),(1,'26920',26),(1,'26921',27),(1,'26922',28),(1,'26923',29),(1,'26924',30),(1,'26925',31),(1,'26926',32),(1,'26927',33),(1,'26928',34),(1,'26929',35),(1,'26930',36),(1,'26931',37),(1,'26932',38),(1,'26933',39),(1,'26934',40),(1,'26935',41),(1,'26936',42),(1,'26937',43),(1,'26938',44),(1,'26939',45),(1,'26940',46),(1,'26941',47),(1,'26942',48),(1,'26943',49),(1,'26944',50),(1,'26945',51),(1,'26946',52),(1,'26947',53),(1,'26948',54),(1,'26949',55),(1,'26950',56),(1,'26951',57),(1,'26952',58),(1,'26953',59),(1,'26954',60),(1,'26955',61),(1,'26956',62),(1,'26957',63),(1,'26958',64),(1,'26959',65),(1,'26960',66),(1,'26961',67),(1,'26962',68),(1,'26963',69),(1,'26964',70),(1,'26965',71),(1,'26966',72),(1,'26967',73),(1,'26968',74),(1,'26969',75),(1,'26970',76),(1,'26971',77),(1,'26972',78),(1,'26973',79),(1,'26974',80),(1,'26975',81),(1,'26976',82),(1,'26977',83),(1,'26978',84),(1,'26979',85),(1,'26980',86),(1,'26981',87),(1,'26982',88),(1,'26983',89),(1,'26984',90),(1,'26985',91),(1,'26986',92),(1,'26987',93),(1,'26988',94),(1,'26989',95),(1,'26990',96),(1,'26991',97),(1,'26992',98),(1,'26993',99),(1,'26994',100),(1,'26995',101),(1,'26996',102),(1,'26997',103),(1,'26998',104),(1,'26999',105),(1,'27000',106),(1,'27001',107),(1,'27002',108),(1,'27003',109),(1,'27004',110),(1,'27005',111),(1,'27006',112),(1,'27007',113),(1,'27008',114),(1,'27009',115),(1,'27010',116),(1,'27011',117),(1,'27012',118),(1,'27013',119),(1,'27014',120),(1,'27015',121),(1,'27016',122),(1,'27017',123),(1,'27018',124),(1,'27019',125),(1,'27020',126),(1,'27021',127),(1,'27022',128),(1,'27023',129),(1,'27024',130),(1,'27025',131),(1,'27026',132),(1,'27027',133),(1,'27028',134),(1,'27029',135),(1,'27030',136),(1,'27031',137),(1,'27032',138),(1,'27033',139),(1,'27034',140),(1,'27035',141),(1,'27036',142),(1,'27037',143),(1,'27038',144),(1,'27039',145),(1,'27040',146),(1,'27041',147),(1,'27042',148),(1,'27043',149),(1,'27044',150),(1,'27045',151),(1,'27046',152),(1,'27047',153),(1,'27048',154),(1,'27049',155),(1,'27050',156),(1,'27051',157),(1,'27052',158),(1,'27053',159),(1,'27054',160),(1,'27055',161),(1,'27056',162),(1,'27057',163),(1,'27058',164),(1,'27059',165),(1,'27060',166),(1,'27061',167),(1,'27062',168),(1,'27063',169),(1,'27064',170),(1,'27065',171),(1,'27066',172),(1,'27067',173),(1,'27068',174),(1,'27069',175),(1,'27070',176),(1,'27071',177),(1,'27072',178),(1,'27085',564),(1,'27086',569),(1,'27087',574),(1,'27131',179),(1,'27132',180),(1,'27135',604),(1,'27136',184),(1,'27147',185),(1,'27150',186),(1,'27180',187),(1,'27181',188),(1,'27226',620),(1,'27227',621),(1,'27228',622),(1,'27230',189),(1,'27233',190),(1,'27236',191),(1,'27258',192),(1,'27259',193),(1,'27260',194),(1,'27261',195),(1,'27262',196),(1,'27263',197),(1,'27264',198),(1,'27377',199),(1,'27378',200),(1,'27379',201),(1,'27380',202),(1,'27381',203),(1,'27382',204),(1,'27383',205),(1,'27393',206),(1,'27394',207),(1,'27395',208),(1,'27396',209),(1,'27397',210),(1,'27398',211),(1,'27438',605),(1,'27456',212),(1,'27457',213),(1,'27458',214),(1,'27459',606),(1,'27461',607),(1,'27462',610),(1,'27463',611),(1,'27464',612),(1,'27465',613),(1,'27466',614),(1,'27470',598),(1,'27471',600),(1,'27472',570),(1,'27473',579),(1,'27474',575),(1,'27475',583),(1,'27476',591),(1,'27477',215),(1,'27478',216),(1,'27479',217),(1,'27480',218),(1,'27481',219),(1,'27486',220),(1,'27487',221),(1,'27488',222),(1,'27489',223),(1,'27490',224),(1,'27491',225),(1,'27492',226),(1,'27493',227),(1,'27494',228),(1,'27495',229),(1,'27496',230),(1,'27497',231),(1,'27498',232),(1,'27499',233),(1,'27500',234),(1,'27501',235),(1,'27502',236),(1,'27503',237),(1,'27504',238),(1,'27505',239),(1,'27506',240),(1,'27507',241),(1,'27508',242),(1,'27509',243),(1,'27510',244),(1,'27511',245),(1,'27512',246),(1,'27513',247),(1,'27514',248),(1,'27515',249),(1,'27516',250),(1,'27517',251),(1,'27518',252),(1,'27519',253),(1,'27520',608),(1,'27521',615),(1,'27522',609),(1,'27523',616),(1,'2780',4),(1,'2781',5),(1,'27889',254),(1,'27890',255),(1,'27891',256),(1,'27892',257),(1,'27893',258),(1,'27894',259),(1,'27895',260),(1,'27896',261),(1,'27897',262),(1,'27898',263),(1,'27899',264),(1,'27900',265),(1,'27901',266),(1,'27902',267),(1,'27903',268),(1,'27904',269),(1,'27905',270),(1,'27906',271),(1,'27907',272),(1,'27908',273),(1,'27909',274),(1,'27910',275),(1,'27911',276),(1,'27912',277),(1,'27913',278),(1,'27915',1),(1,'27922',279),(1,'27926',280),(1,'27927',281),(1,'27928',183),(1,'27929',282),(1,'27930',283),(1,'27931',284),(1,'27932',285),(1,'27933',286),(1,'27934',287),(1,'27935',288),(1,'27936',289),(1,'27937',290),(1,'27938',291),(1,'27939',292),(1,'27940',293),(1,'27941',294),(1,'27942',295),(1,'27943',617),(1,'27944',296),(1,'27945',297),(1,'27946',298),(1,'27947',299),(1,'27949',300),(1,'27950',301),(1,'27951',302),(1,'27952',303),(1,'27955',304),(1,'27956',305),(1,'27957',306),(1,'27958',307),(1,'27959',308),(1,'27960',309),(1,'27961',565),(1,'27962',571),(1,'27963',576),(1,'27964',580),(1,'27965',584),(1,'27966',587),(1,'27967',310),(1,'27968',311),(1,'27969',312),(1,'27970',313),(1,'27971',314),(1,'27972',315),(1,'27973',316),(1,'27974',317),(1,'27975',318),(1,'27976',319),(1,'27977',320),(1,'27978',321),(1,'27979',322),(1,'27980',323),(1,'27981',324),(1,'27982',325),(1,'27983',326),(1,'27984',327),(1,'27985',328),(1,'27986',329),(1,'27987',330),(1,'27988',331),(1,'27989',332),(1,'27990',333),(1,'27991',334),(1,'27992',335),(1,'27993',336),(1,'27994',337),(1,'27995',338),(1,'27996',339),(1,'27997',340),(1,'27998',341),(1,'27999',342),(1,'28000',343),(1,'28001',344),(1,'28002',345),(1,'28003',346),(1,'28004',347),(1,'28005',348),(1,'28006',349),(1,'28007',350),(1,'28008',351),(1,'28009',352),(1,'28010',353),(1,'28011',354),(1,'28012',355),(1,'28013',356),(1,'28014',357),(1,'28015',358),(1,'28016',359),(1,'28017',360),(1,'28018',361),(1,'28019',362),(1,'28020',363),(1,'28021',364),(1,'28022',365),(1,'28023',366),(1,'28024',367),(1,'28025',368),(1,'28026',369),(1,'28027',370),(1,'28028',371),(1,'28029',372),(1,'28030',373),(1,'28031',374),(1,'28032',375),(1,'28033',376),(1,'28034',377),(1,'28035',378),(1,'28036',379),(1,'28037',380),(1,'28038',381),(1,'28039',382),(1,'28040',383),(1,'28041',384),(1,'28042',385),(1,'28043',386),(1,'28044',387),(1,'28045',388),(1,'28046',389),(1,'28047',390),(1,'28048',391),(1,'28049',392),(1,'28050',393),(1,'28051',394),(1,'28052',395),(1,'28053',396),(1,'28054',397),(1,'28055',398),(1,'28056',399),(1,'28057',400),(1,'28058',401),(1,'28059',402),(1,'28060',403),(1,'28061',404),(1,'28062',405),(1,'28063',406),(1,'28064',407),(1,'28065',408),(1,'28066',409),(1,'28067',410),(1,'28068',411),(1,'28069',412),(1,'28070',413),(1,'28071',414),(1,'28072',415),(1,'28073',416),(1,'28074',417),(1,'28075',418),(1,'28076',419),(1,'28077',420),(1,'28078',421),(1,'28079',422),(1,'28080',423),(1,'28081',424),(1,'28082',425),(1,'28083',426),(1,'28084',427),(1,'28085',428),(1,'28086',429),(1,'28087',430),(1,'28088',431),(1,'28089',432),(1,'28090',433),(1,'28091',434),(1,'28092',435),(1,'28093',436),(1,'28094',437),(1,'28095',438),(1,'28096',439),(1,'28097',440),(1,'28098',441),(1,'28099',442),(1,'28100',443),(1,'28101',444),(1,'28102',445),(1,'28103',446),(1,'28104',447),(1,'28105',448),(1,'28106',449),(1,'28107',450),(1,'28108',451),(1,'28109',452),(1,'28110',453),(1,'28111',454),(1,'28112',455),(1,'28113',456),(1,'28114',457),(1,'28115',458),(1,'28116',459),(1,'28117',460),(1,'28118',461),(1,'28119',462),(1,'28120',463),(1,'28121',464),(1,'28122',465),(1,'28123',466),(1,'28124',467),(1,'28125',468),(1,'28126',469),(1,'28127',470),(1,'28128',471),(1,'28129',472),(1,'28130',473),(1,'28131',474),(1,'28132',475),(1,'28133',476),(1,'28134',477),(1,'28135',478),(1,'28136',479),(1,'28137',480),(1,'28138',481),(1,'28139',482),(1,'28140',483),(1,'28141',484),(1,'28142',485),(1,'28143',486),(1,'28144',487),(1,'28145',488),(1,'28146',489),(1,'28147',490),(1,'28148',491),(1,'28149',492),(1,'28150',493),(1,'28151',494),(1,'28152',495),(1,'28153',496),(1,'28154',497),(1,'28155',498),(1,'28156',499),(1,'28157',500),(1,'28158',501),(1,'28159',502),(1,'28160',503),(1,'28161',504),(1,'28162',505),(1,'28163',506),(1,'28164',507),(1,'28165',508),(1,'28166',509),(1,'28167',510),(1,'28168',511),(1,'28169',512),(1,'28170',513),(1,'28171',514),(1,'28172',515),(1,'28173',516),(1,'28174',517),(1,'28175',518),(1,'28176',519),(1,'28177',520),(1,'28178',521),(1,'28179',522),(1,'28180',523),(1,'28181',524),(1,'28182',525),(1,'28183',526),(1,'28184',527),(1,'28185',528),(1,'28186',529),(1,'28187',530),(1,'28188',531),(1,'28189',532),(1,'28190',533),(1,'28191',534),(1,'28192',535),(1,'28193',536),(1,'28194',537),(1,'28195',538),(1,'28196',539),(1,'28197',540),(1,'28198',541),(1,'28199',542),(1,'28200',543),(1,'28201',544),(1,'28202',545),(1,'28203',546),(1,'28204',547),(1,'28205',548),(1,'28206',549),(1,'28207',550),(1,'28208',551),(1,'28209',552),(1,'28210',553),(1,'28211',554),(1,'28212',555),(1,'28213',556),(1,'28214',557),(1,'28215',558),(1,'28216',559),(1,'28217',560),(1,'28218',561),(1,'28219',566),(1,'28220',588),(1,'28221',594),(1,'28222',596),(1,'28223',562),(1,'28224',563),(1,'28225',567),(1,'28226',572),(1,'28227',577),(1,'28228',581),(1,'28229',585),(1,'28230',589),(1,'28231',592),(1,'28232',595),(1,'28233',597),(1,'28234',599),(1,'28235',601),(1,'28236',602),(1,'28237',603),(1,'28238',568),(1,'28239',573),(1,'28240',578),(1,'28241',582),(1,'28242',586),(1,'28243',590),(1,'28244',593);
/*!40000 ALTER TABLE `cms3_import_objects` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cms3_import_offer_list`
--

DROP TABLE IF EXISTS `cms3_import_offer_list`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cms3_import_offer_list` (
  `external_id` varchar(255) NOT NULL,
  `internal_id` int(10) unsigned NOT NULL,
  `source_id` int(10) unsigned NOT NULL,
  KEY `external_id` (`external_id`,`source_id`),
  KEY `internal_id` (`internal_id`,`source_id`),
  KEY `source_id` (`source_id`),
  CONSTRAINT `internal id to offer` FOREIGN KEY (`internal_id`) REFERENCES `cms3_offer_list` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `offer source id to import source` FOREIGN KEY (`source_id`) REFERENCES `cms3_import_sources` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cms3_import_offer_list`
--

LOCK TABLES `cms3_import_offer_list` WRITE;
/*!40000 ALTER TABLE `cms3_import_offer_list` DISABLE KEYS */;
/*!40000 ALTER TABLE `cms3_import_offer_list` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cms3_import_offer_price_list`
--

DROP TABLE IF EXISTS `cms3_import_offer_price_list`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cms3_import_offer_price_list` (
  `external_id` varchar(255) NOT NULL,
  `internal_id` int(10) unsigned NOT NULL,
  `source_id` int(10) unsigned NOT NULL,
  KEY `external_id` (`external_id`,`source_id`),
  KEY `internal_id` (`internal_id`,`source_id`),
  KEY `source_id` (`source_id`),
  CONSTRAINT `internal id to price` FOREIGN KEY (`internal_id`) REFERENCES `cms3_offer_price_list` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `price source id to import source` FOREIGN KEY (`source_id`) REFERENCES `cms3_import_sources` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cms3_import_offer_price_list`
--

LOCK TABLES `cms3_import_offer_price_list` WRITE;
/*!40000 ALTER TABLE `cms3_import_offer_price_list` DISABLE KEYS */;
/*!40000 ALTER TABLE `cms3_import_offer_price_list` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cms3_import_offer_price_type_list`
--

DROP TABLE IF EXISTS `cms3_import_offer_price_type_list`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cms3_import_offer_price_type_list` (
  `external_id` varchar(255) NOT NULL,
  `internal_id` int(10) unsigned NOT NULL,
  `source_id` int(10) unsigned NOT NULL,
  KEY `external_id` (`external_id`,`source_id`),
  KEY `internal_id` (`internal_id`,`source_id`),
  KEY `source_id` (`source_id`),
  CONSTRAINT `internal id to price type` FOREIGN KEY (`internal_id`) REFERENCES `cms3_offer_price_type_list` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `price type source id to import source` FOREIGN KEY (`source_id`) REFERENCES `cms3_import_sources` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cms3_import_offer_price_type_list`
--

LOCK TABLES `cms3_import_offer_price_type_list` WRITE;
/*!40000 ALTER TABLE `cms3_import_offer_price_type_list` DISABLE KEYS */;
INSERT INTO `cms3_import_offer_price_type_list` VALUES ('1',1,1);
/*!40000 ALTER TABLE `cms3_import_offer_price_type_list` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cms3_import_redirects`
--

DROP TABLE IF EXISTS `cms3_import_redirects`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cms3_import_redirects` (
  `external_id` int(11) NOT NULL,
  `internal_id` int(11) NOT NULL,
  `source_id` int(10) unsigned NOT NULL,
  KEY `external_id` (`external_id`,`source_id`),
  KEY `internal_id` (`internal_id`,`source_id`),
  KEY `source_id` (`source_id`),
  CONSTRAINT `cms3_import_redirects_ibfk_1` FOREIGN KEY (`internal_id`) REFERENCES `cms3_redirects` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `cms3_import_redirects_ibfk_2` FOREIGN KEY (`source_id`) REFERENCES `cms3_import_sources` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cms3_import_redirects`
--

LOCK TABLES `cms3_import_redirects` WRITE;
/*!40000 ALTER TABLE `cms3_import_redirects` DISABLE KEYS */;
/*!40000 ALTER TABLE `cms3_import_redirects` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cms3_import_relations`
--

DROP TABLE IF EXISTS `cms3_import_relations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cms3_import_relations` (
  `source_id` int(10) unsigned NOT NULL,
  `old_id` varchar(255) NOT NULL,
  `new_id` int(10) unsigned NOT NULL,
  KEY `source_id` (`source_id`,`old_id`,`new_id`),
  KEY `old_id` (`old_id`,`new_id`),
  KEY `new_id` (`new_id`),
  CONSTRAINT `FK_NewId_To_HierarchyId` FOREIGN KEY (`new_id`) REFERENCES `cms3_hierarchy` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `FK_SourceId_To_Source` FOREIGN KEY (`source_id`) REFERENCES `cms3_import_sources` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cms3_import_relations`
--

LOCK TABLES `cms3_import_relations` WRITE;
/*!40000 ALTER TABLE `cms3_import_relations` DISABLE KEYS */;
/*!40000 ALTER TABLE `cms3_import_relations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cms3_import_restrictions`
--

DROP TABLE IF EXISTS `cms3_import_restrictions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cms3_import_restrictions` (
  `source_id` int(10) unsigned NOT NULL,
  `old_id` varchar(255) NOT NULL,
  `new_id` int(10) unsigned NOT NULL,
  KEY `source_id` (`source_id`,`old_id`,`new_id`),
  KEY `old_id` (`old_id`,`new_id`),
  KEY `new_id` (`new_id`),
  CONSTRAINT `FK_NewId_To_RestrictionId` FOREIGN KEY (`new_id`) REFERENCES `cms3_object_fields_restrictions` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `FK_RestrictionSourceId_To_Source` FOREIGN KEY (`source_id`) REFERENCES `cms3_import_sources` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cms3_import_restrictions`
--

LOCK TABLES `cms3_import_restrictions` WRITE;
/*!40000 ALTER TABLE `cms3_import_restrictions` DISABLE KEYS */;
INSERT INTO `cms3_import_restrictions` VALUES (1,'1',6),(1,'2',3),(1,'3',4),(1,'4',1),(1,'5',2),(1,'7',5);
/*!40000 ALTER TABLE `cms3_import_restrictions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cms3_import_sliders`
--

DROP TABLE IF EXISTS `cms3_import_sliders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cms3_import_sliders` (
  `external_id` int(10) unsigned NOT NULL,
  `internal_id` int(10) unsigned NOT NULL,
  `source_id` int(10) unsigned NOT NULL,
  KEY `external_id` (`external_id`,`source_id`),
  KEY `internal_id` (`internal_id`,`source_id`),
  KEY `source_id` (`source_id`),
  CONSTRAINT `cms3_import_sliders_ibfk_1` FOREIGN KEY (`internal_id`) REFERENCES `cms3_sliders` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `cms3_import_sliders_ibfk_2` FOREIGN KEY (`source_id`) REFERENCES `cms3_import_sources` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cms3_import_sliders`
--

LOCK TABLES `cms3_import_sliders` WRITE;
/*!40000 ALTER TABLE `cms3_import_sliders` DISABLE KEYS */;
/*!40000 ALTER TABLE `cms3_import_sliders` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cms3_import_slides`
--

DROP TABLE IF EXISTS `cms3_import_slides`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cms3_import_slides` (
  `external_id` int(10) unsigned NOT NULL,
  `internal_id` int(10) unsigned NOT NULL,
  `source_id` int(10) unsigned NOT NULL,
  KEY `external_id` (`external_id`,`source_id`),
  KEY `internal_id` (`internal_id`,`source_id`),
  KEY `source_id` (`source_id`),
  CONSTRAINT `cms3_import_slides_ibfk_1` FOREIGN KEY (`internal_id`) REFERENCES `cms3_slides` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `cms3_import_slides_ibfk_2` FOREIGN KEY (`source_id`) REFERENCES `cms3_import_sources` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cms3_import_slides`
--

LOCK TABLES `cms3_import_slides` WRITE;
/*!40000 ALTER TABLE `cms3_import_slides` DISABLE KEYS */;
/*!40000 ALTER TABLE `cms3_import_slides` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cms3_import_sources`
--

DROP TABLE IF EXISTS `cms3_import_sources`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cms3_import_sources` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `source_name` varchar(128) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `source_name` (`source_name`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cms3_import_sources`
--

LOCK TABLES `cms3_import_sources` WRITE;
/*!40000 ALTER TABLE `cms3_import_sources` DISABLE KEYS */;
INSERT INTO `cms3_import_sources` VALUES (1,'system');
/*!40000 ALTER TABLE `cms3_import_sources` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cms3_import_stock_balance_list`
--

DROP TABLE IF EXISTS `cms3_import_stock_balance_list`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cms3_import_stock_balance_list` (
  `external_id` varchar(255) NOT NULL,
  `internal_id` int(10) unsigned NOT NULL,
  `source_id` int(10) unsigned NOT NULL,
  KEY `external_id` (`external_id`,`source_id`),
  KEY `internal_id` (`internal_id`,`source_id`),
  KEY `source_id` (`source_id`),
  CONSTRAINT `internal id to stock balance` FOREIGN KEY (`internal_id`) REFERENCES `cms3_stock_balance_list` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `stock balance source id to import source` FOREIGN KEY (`source_id`) REFERENCES `cms3_import_sources` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cms3_import_stock_balance_list`
--

LOCK TABLES `cms3_import_stock_balance_list` WRITE;
/*!40000 ALTER TABLE `cms3_import_stock_balance_list` DISABLE KEYS */;
/*!40000 ALTER TABLE `cms3_import_stock_balance_list` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cms3_import_templates`
--

DROP TABLE IF EXISTS `cms3_import_templates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cms3_import_templates` (
  `source_id` int(10) unsigned NOT NULL,
  `old_id` varchar(255) NOT NULL,
  `new_id` int(10) unsigned NOT NULL,
  KEY `source_id` (`source_id`,`old_id`,`new_id`),
  KEY `old_id` (`old_id`,`new_id`),
  KEY `new_id` (`new_id`),
  CONSTRAINT `FK_NewId_To_TemplateId` FOREIGN KEY (`new_id`) REFERENCES `cms3_templates` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `FK_TemplateSourceId_To_Source` FOREIGN KEY (`source_id`) REFERENCES `cms3_import_sources` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cms3_import_templates`
--

LOCK TABLES `cms3_import_templates` WRITE;
/*!40000 ALTER TABLE `cms3_import_templates` DISABLE KEYS */;
/*!40000 ALTER TABLE `cms3_import_templates` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cms3_import_types`
--

DROP TABLE IF EXISTS `cms3_import_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cms3_import_types` (
  `source_id` int(10) unsigned NOT NULL,
  `old_id` varchar(255) NOT NULL,
  `new_id` int(10) unsigned NOT NULL,
  KEY `source_id` (`source_id`,`old_id`,`new_id`),
  KEY `old_id` (`old_id`,`new_id`),
  KEY `new_id` (`new_id`),
  CONSTRAINT `FK_NewId_To_ObjectTypeId` FOREIGN KEY (`new_id`) REFERENCES `cms3_object_types` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `FK_TypeSourceId_To_Source` FOREIGN KEY (`source_id`) REFERENCES `cms3_import_sources` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cms3_import_types`
--

LOCK TABLES `cms3_import_types` WRITE;
/*!40000 ALTER TABLE `cms3_import_types` DISABLE KEYS */;
INSERT INTO `cms3_import_types` VALUES (1,'10',61),(1,'11',87),(1,'18',7),(1,'21',5),(1,'22',8),(1,'23',60),(1,'34',59),(1,'39',71),(1,'4',54),(1,'40',72),(1,'5',114),(1,'6',39),(1,'648',67),(1,'649',68),(1,'650',69),(1,'651',70),(1,'671',113),(1,'672',115),(1,'673',116),(1,'674',9),(1,'675',117),(1,'680',82),(1,'681',83),(1,'682',84),(1,'683',85),(1,'688',64),(1,'689',62),(1,'693',10),(1,'696',77),(1,'697',78),(1,'699',79),(1,'7',1),(1,'700',80),(1,'701',81),(1,'702',121),(1,'739',11),(1,'740',74),(1,'741',75),(1,'742',76),(1,'743',118),(1,'745',2),(1,'746',63),(1,'747',65),(1,'748',66),(1,'750',73),(1,'751',45),(1,'752',52),(1,'753',48),(1,'754',55),(1,'755',53),(1,'756',44),(1,'757',43),(1,'760',88),(1,'762',30),(1,'765',42),(1,'767',32),(1,'768',56),(1,'770',31),(1,'772',33),(1,'773',34),(1,'777',90),(1,'779',41),(1,'780',21),(1,'781',51),(1,'782',50),(1,'783',96),(1,'784',97),(1,'787',46),(1,'788',47),(1,'791',100),(1,'792',12),(1,'793',13),(1,'794',91),(1,'795',92),(1,'796',93),(1,'797',94),(1,'798',57),(1,'799',95),(1,'8',4),(1,'800',89),(1,'801',101),(1,'802',102),(1,'803',40),(1,'804',119),(1,'805',120),(1,'806',35),(1,'807',36),(1,'808',98),(1,'809',14),(1,'810',15),(1,'812',103),(1,'813',104),(1,'814',16),(1,'815',17),(1,'816',105),(1,'817',49),(1,'818',18),(1,'819',123),(1,'820',19),(1,'822',37),(1,'823',38),(1,'824',106),(1,'826',107),(1,'827',20),(1,'828',108),(1,'829',22),(1,'830',23),(1,'831',24),(1,'832',6),(1,'833',109),(1,'834',110),(1,'837',111),(1,'838',25),(1,'839',58),(1,'840',26),(1,'841',125),(1,'842',99),(1,'843',122),(1,'844',27),(1,'845',112),(1,'846',28),(1,'847',29),(1,'848',124),(1,'9',86),(1,'{root-pages-type}',3);
/*!40000 ALTER TABLE `cms3_import_types` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cms3_langs`
--

DROP TABLE IF EXISTS `cms3_langs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cms3_langs` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `prefix` varchar(16) NOT NULL,
  `title` varchar(255) NOT NULL,
  `is_default` tinyint(1) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `prefix` (`prefix`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cms3_langs`
--

LOCK TABLES `cms3_langs` WRITE;
/*!40000 ALTER TABLE `cms3_langs` DISABLE KEYS */;
INSERT INTO `cms3_langs` VALUES (1,'ru','Русский',1),(2,'en','English',0);
/*!40000 ALTER TABLE `cms3_langs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cms3_links`
--

DROP TABLE IF EXISTS `cms3_links`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cms3_links` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `address` varchar(1024) NOT NULL,
  `address_hash` varchar(32) NOT NULL,
  `place` varchar(255) NOT NULL,
  `broken` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `address_hash` (`address_hash`),
  KEY `broken` (`broken`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cms3_links`
--

LOCK TABLES `cms3_links` WRITE;
/*!40000 ALTER TABLE `cms3_links` DISABLE KEYS */;
/*!40000 ALTER TABLE `cms3_links` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cms3_links_sources`
--

DROP TABLE IF EXISTS `cms3_links_sources`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cms3_links_sources` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `link_id` int(11) unsigned NOT NULL,
  `place` varchar(255) NOT NULL,
  `type` enum('object','template') NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `link_source` (`link_id`,`place`),
  CONSTRAINT `source link_id` FOREIGN KEY (`link_id`) REFERENCES `cms3_links` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cms3_links_sources`
--

LOCK TABLES `cms3_links_sources` WRITE;
/*!40000 ALTER TABLE `cms3_links_sources` DISABLE KEYS */;
/*!40000 ALTER TABLE `cms3_links_sources` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cms3_mail_notifications`
--

DROP TABLE IF EXISTS `cms3_mail_notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cms3_mail_notifications` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `lang_id` int(10) unsigned NOT NULL,
  `domain_id` int(10) unsigned NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `module` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name-domain-lang` (`name`,`domain_id`,`lang_id`),
  KEY `lang_id` (`lang_id`),
  KEY `domain_id` (`domain_id`),
  KEY `name` (`name`),
  CONSTRAINT `notification to domain` FOREIGN KEY (`domain_id`) REFERENCES `cms3_domains` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `notification to lang` FOREIGN KEY (`lang_id`) REFERENCES `cms3_langs` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=23 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cms3_mail_notifications`
--

LOCK TABLES `cms3_mail_notifications` WRITE;
/*!40000 ALTER TABLE `cms3_mail_notifications` DISABLE KEYS */;
INSERT INTO `cms3_mail_notifications` VALUES (1,1,1,'notification-new-record-admin','appointment'),(2,1,1,'notification-new-record-user','appointment'),(3,1,1,'notification-record-status-changed-user','appointment'),(4,1,1,'notification-banners-expiration-date','banners'),(5,1,1,'notification-blogs-post-comment','blogs20'),(6,1,1,'notification-blogs-comment-comment','blogs20'),(7,1,1,'notification-content-expiration-date','content'),(8,1,1,'notification-content-unpublish-page','content'),(9,1,1,'notification-dispatches-release','dispatches'),(10,1,1,'notification-dispatches-subscribe','dispatches'),(11,1,1,'notification-emarket-status-change','emarket'),(12,1,1,'notification-emarket-new-order','emarket'),(13,1,1,'notification-emarket-invoice','emarket'),(14,1,1,'notification-faq-answer','faq'),(15,1,1,'notification-faq-confirm-user','faq'),(16,1,1,'notification-faq-confirm-admin','faq'),(17,1,1,'notification-forum-new-message','forum'),(18,1,1,'notification-users-new-registration-admin','users'),(19,1,1,'notification-users-restore-password','users'),(20,1,1,'notification-users-registered','users'),(21,1,1,'notification-users-registered-no-activation','users'),(22,1,1,'notification-users-new-password','users');
/*!40000 ALTER TABLE `cms3_mail_notifications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cms3_mail_templates`
--

DROP TABLE IF EXISTS `cms3_mail_templates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cms3_mail_templates` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `notification_id` int(11) unsigned NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `type` varchar(255) DEFAULT NULL,
  `content` mediumtext,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name-notification` (`name`,`notification_id`),
  KEY `name` (`name`),
  KEY `notification_id` (`notification_id`),
  CONSTRAINT `mail template to notification` FOREIGN KEY (`notification_id`) REFERENCES `cms3_mail_notifications` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=50 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cms3_mail_templates`
--

LOCK TABLES `cms3_mail_templates` WRITE;
/*!40000 ALTER TABLE `cms3_mail_templates` DISABLE KEYS */;
INSERT INTO `cms3_mail_templates` VALUES (1,1,'new-record-admin-notify-content','content','<table style=\"max-width: 400px; width: 100%;\">\n  <tbody>\n    <tr>\n      <td>Имя</td>\n      <td>%name%</td>\n    </tr>\n    <tr>\n      <td>Телефон</td>\n      <td>%phone%</td>\n    </tr>\n    <tr>\n      <td>E-Mail</td>\n      <td>%email%</td>\n    </tr>\n    <tr>\n      <td>Комментарий</td>\n      <td>%comment%</td>\n    </tr>\n    <tr>\n      <td>Дата</td>\n      <td>%date%</td>\n    </tr>\n    <tr>\n      <td>Время</td>\n      <td>%time%</td>\n    </tr>\n    <tr>\n      <td>Услуга</td>\n      <td>%service%</td>\n    </tr>\n    <tr>\n      <td>Категория</td>\n      <td>%category%</td>\n    </tr>\n    <tr>\n      <td>Специалист</td>\n      <td>%specialist%</td>\n  </tr>\n  </tbody>\n</table>'),(2,1,'new-record-admin-notify-subject','subject','Запись на приём \\ %category% \\ %service% \\'),(3,2,'new-record-user-notify-content','content','<table style=\"max-width: 400px; width: 100%;\">\n  <tbody>\n    <tr>\n      <td>Дата</td>\n      <td>%date%</td>\n    </tr>\n    <tr>\n      <td>Время</td>\n      <td>%time%</td>\n    </tr>\n    <tr>\n      <td>Услуга</td>\n      <td>%service%</td>\n    </tr>\n    <tr>\n      <td>Категория</td>\n      <td>%category%</td>\n    </tr>\n    <tr>\n      <td>Специалист</td>\n      <td>%specialist%</td>\n    </tr>\n  </tbody>\n</table>'),(4,2,'new-record-user-notify-subject','subject','Вы записались на приём \\ %category% \\ %service% \\'),(5,3,'record-status-changed-user-notify-content','content','<table style=\"max-width: 400px; width: 100%;\">\n  <tbody>\n    <tr>\n      <td>Группа</td>\n      <td>%category%</td>\n    </tr>\n    <tr>\n      <td>Услуга</td>\n      <td>%service%</td>\n    </tr>\n    <tr>\n      <td>Дата</td>\n      <td>%date%&nbsp;</td>\n    </tr>\n    <tr>\n      <td>Время</td>\n      <td>&nbsp;%time%</td>\n    </tr>\n    <tr>\n      <td>Специалист</td>\n      <td>%specialist%</td>\n    </tr>\n    <tr>\n      <td>Статус</td>\n      <td>%new-status%</td>\n    </tr>\n  </tbody>\n</table>'),(6,3,'record-status-changed-user-notify-subject','subject','Ваша заявка на запись \\ %category% \\ %service% \\ была обновлена'),(7,4,'banners-expiration-date-subject','subject','Оповещение о приближении окончания показа баннеров'),(8,4,'banners-expiration-date-content','content','У следующих баннеров истекает срок показа:<br>%parse.banners-expiration-date-item.items%'),(9,4,'banners-expiration-date-item','item','%bannerName% %tillDate% Ссылка для редактирования: <a href=\"%link%\">%link%</a><br>'),(10,5,'blogs-post-comment-subject','subject','Новый ответ на Вашу публикацию'),(11,5,'blogs-post-comment-content','content','%name%, на Вашу публикацию получен новый ответ.<br>\nПосмотреть его Вы можете, перейдя по ссылке:<br><a href=\"%link%\">%link%</a>'),(12,6,'blogs-comment-comment-subject','subject','Новый ответ на Ваш комментарий'),(13,6,'blogs-comment-comment-content','content','%name%, на Ваш комментарий получен новый ответ.<br>\nПосмотреть его Вы можете, перейдя по ссылке:<br><a href=\"%link%\">%link%</a>'),(14,7,'content-expiration-date-subject','subject','Оповещение о приближении окончания публикации'),(15,7,'content-expiration-date-content','content','У страницы <a href=\"%page_link%\">%page_header%</a><br>приближается время потери актуальности<br>Комментарии к публикации: <br><p>%publish_comments%</p>'),(16,8,'content-unpublish-page-subject','subject','Оповещение о снятии страницы с публикации'),(17,8,'content-unpublish-page-content','content','Страница <a href=\"%page_link%\">%page_header%</a><br> снята с публикации<br>\n    Комментарии к публикации: <br />\n    <p>%publish_comments%</p>'),(18,9,'dispatches-release-subject','subject','Новая рассылка: %header%'),(19,9,'dispatches-release-content','content','%parse.dispatches-release-message.messages%<hr><b>Отписаться:</b> Отписаться от рассылки можно <a href=\"%unsubscribe_link%\">по этой ссылке</a>'),(20,9,'dispatches-release-message','message','<h3>%header%</h3>%body%<hr>'),(21,10,'dispatches-subscribe-subject','subject','Подписка на рассылку'),(22,10,'dispatches-subscribe-content','content','<p>Доброго времени суток!</p><p>Вы подписались на рассылку.</p><p>Если вы не хотите ее получать, перейдите по этой ссылке: <a href=\"%unsubscribe_link%\">%unsubscribe_link%</a></p>'),(23,11,'emarket-status-notification-subject','subject','%header%'),(24,11,'emarket-status-notification-content','content','<p>Ваш заказ #%order_number% %status%</p>\n<div>\n  <hr/>\n  <p>Товары:</p>\n  %parse.emarket-status-notification-item.items%\n  <hr/>\n  <p>Всего товаров: %total_amount% шт.</p>\n  <p>На сумму: %total_price% %suffix%. </p>\n</div>\nПосмотреть историю заказов вы можете в своем <a href=\"http://%domain%/emarket/personal/\">личном кабинете</a>.'),(25,11,'emarket-status-notification-receipt','content','<p>Ваш заказ #%order_number% %status%</p>\n<div>\n  <hr/>\n  <p>Товары:</p>\n  %parse.emarket-status-notification-item.items%\n  <hr/>\n  <p>Всего товаров: %total_amount% шт.</p>\n  <p>На сумму: %total_price% %suffix%. </p>\n</div>\n    Посмотреть историю заказов вы можете в своем <a href=\"http://%domain%/emarket/personal/\">личном кабинете</a>.\n    <br/><br/>\n    Квитанцию на оплату вы можете получить, перейдя по <a href=\"http://%domain%/emarket/receipt/%order_id%/%receipt_signature%/\">этой ссылке</a>.'),(26,11,'emarket-status-notification-item','content','<p><a href=\"%link%\">%name%</a></p>\n<p>Цена:  %price% %suffix%. Количество: %amount% шт.</p>'),(27,12,'emarket-neworder-notification-subject','subject','%header%'),(28,12,'emarket-neworder-notification-content','content','Поступил новый заказ #%order_number% (<a href=\"http://%domain%/admin/emarket/order_edit/%order_id%/\">Просмотр</a>)\n    <br/><br/>\n<div>\n  <hr/>\n  <p>Товары:</p>\n  %parse.emarket-neworder-notification-item.items%\n  <hr/>\n  <p>Всего товаров: %total_amount% шт.</p>\n  <p>На сумму: %total_price% %suffix%. </p>\n</div>\n\n<div>\n  <p>Информация о покупателе:</p>\n  <p>Имя: %first_name%</p>\n  <p>Фамилия: %last_name%</p>\n  <p>email: %email%</p>\n  <p>Телефон: %phone%</p>\n\n  <p>Способ доставки: %delivery%</p>\n  <p>Адрес доставки: %address%</p>\n</div>\n\n  <p>Способ оплаты: %payment_type%</p>\n  <p>Статус оплаты: %payment_status%</p>'),(29,12,'emarket-neworder-notification-item','content','<p><a href=\"%link%\">%name%</a></p>\n<p>Цена:  %price% %suffix%. Количество: %amount% шт.</p>'),(30,13,'emarket-invoice-subject','subject','На сайте %domain% успешно сформирован счет'),(31,13,'emarket-invoice-content','content','Вы можете распечатать счет для юридических лиц, перейдя по следующей ссылке:\n    <p>\n      <a href=\"http://%domain%%invoice_link%\">http://%domain%%invoice_link%</a>\n    </p>'),(32,14,'faq-answer-subject','subject','[#%ticket%] Ответ на Ваш вопрос'),(33,14,'faq-answer-content','content','Здравствуйте, <br /><br />\n\nОтвет на Ваш вопрос Вы можете прочитать по следующему адресу:<br />\n<a href=\"%question_link%\">%question_link%</a><br />\n\n<br /><hr />\nС уважением, <br />\nАдминистрация сайта <b>%domain%</b>'),(34,15,'faq-confirm-user-subject','subject','Спасибо за Ваш вопрос'),(35,15,'faq-confirm-user-content','content','Вашему вопросу присвоен тикет #%ticket% <br />\nМы ответим Вам в ближайшее время.<br />\n<br /><hr />\nС уважением, <br />\nАдминистрация сайта <b>%domain%</b>'),(36,16,'faq-confirm-admin-subject','subject','Новый вопрос в FAQ'),(37,16,'faq-confirm-admin-content','content','В базу знаний поступил новый вопрос:<br />\n<a href=\"%question_link%\">%question_link%</a><br />\n<hr />\n%question%\n<hr />'),(38,17,'forum-new-message-subject','subject','Новое сообщение на форуме'),(39,17,'forum-new-message-content','content','<h1>%h1%</h1>\n%message%'),(40,18,'users-new-registration-admin-subject','subject','Зарегистрировался новый пользователь'),(41,18,'users-new-registration-admin-content','content','<p>Зарегистрировался новый пользователь \"%login%\".</p>'),(42,19,'users-restore-password-subject','subject','Восстановление пароля'),(43,19,'users-restore-password-content','content','<p>\n    Здравствуйте!<br />\n    Кто-то, возможно Вы, пытается восстановить пароль для пользователя \"%login%\" на сайте <a href=\"http://%domain%\">%domain%</a>.\n  </p>\n\n\n  <p>\n    Если это не Вы, просто проигнорируйте данное письмо.\n  </p>\n\n  <p>\n    Если Вы действительно хотите восстановить пароль, кликните по этой ссылке:<br />\n    <a href=\"%restore_link%\">%restore_link%</a>\n  </p>\n\n  <p>\n    С уважением,<br />\n    <b>Администрация сайта <a href=\"http://%domain%\">%domain%</a></b>\n  </p>'),(44,20,'users-registered-subject','subject','Регистрация на UMI.CMS Demo Site'),(45,20,'users-registered-content','content','<p>\n    Здравствуйте, %lname% %fname% %father_name%, <br />\n    Вы зарегистрировались на сайте <a href=\"http://%domain%\">%domain%</a>.\n  </p>\n\n\n  <p>\n    Логин: %login%<br />\n    Пароль: %password%\n  </p>\n\n\n  <p>\n    <div class=\"notice\">\n      Чтобы активировать Ваш аккаунт, необходимо перейти по ссылке, либо скопировать ее в адресную строку браузера:<br />\n      <a href=\"%activate_link%\">%activate_link%</a>\n    </div>\n  </p>'),(46,21,'users-registered-no-activation-subject','subject','Регистрация на сайте %domain%'),(47,21,'users-registered-no-activation-content','content','<p>\n    Здравствуйте, %lname% %fname% %father_name%, <br />\n    Вы зарегистрировались на сайте <a href=\"http://%domain%\">%domain%</a>.\n  </p>\n  <p>\n    Логин: %login%<br />\n    Пароль: %password%\n  </p>'),(48,22,'users-new-password-subject','subject','Новый пароль для сайта'),(49,22,'users-new-password-content','content','<p>\n    Здравствуйте!<br />\n\n    Ваш новый пароль от сайта <a href=\"http://%domain%\">%domain%</a>.\n  </p>\n\n\n  <p>\n    Логин:  %login%<br />\n    Пароль: %password%\n  </p>\n\n  <p>\n    С уважением,<br />\n    <b>Администрация сайта <a href=\"http://%domain%\">%domain%</a></b>\n  </p>');
/*!40000 ALTER TABLE `cms3_mail_templates` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cms3_mail_variables`
--

DROP TABLE IF EXISTS `cms3_mail_variables`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cms3_mail_variables` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `template_id` int(11) unsigned NOT NULL,
  `variable` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `field_name` (`variable`),
  KEY `template_name` (`template_id`),
  CONSTRAINT `mail variable to template` FOREIGN KEY (`template_id`) REFERENCES `cms3_mail_templates` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=181 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cms3_mail_variables`
--

LOCK TABLES `cms3_mail_variables` WRITE;
/*!40000 ALTER TABLE `cms3_mail_variables` DISABLE KEYS */;
INSERT INTO `cms3_mail_variables` VALUES (1,1,'category'),(2,1,'service'),(3,1,'name'),(4,1,'phone'),(5,1,'email'),(6,1,'comment'),(7,1,'date'),(8,1,'time'),(9,1,'specialist'),(10,2,'category'),(11,2,'service'),(12,3,'category'),(13,3,'service'),(14,3,'date'),(15,3,'time'),(16,3,'specialist'),(17,4,'category'),(18,4,'service'),(19,5,'category'),(20,5,'service'),(21,5,'date'),(22,5,'time'),(23,5,'specialist'),(24,5,'new-status'),(25,6,'category'),(26,6,'service'),(27,8,'parse.banners-expiration-date-item.items'),(28,9,'bannerName'),(29,9,'tillDate'),(30,9,'link'),(31,10,'name'),(32,10,'link'),(33,11,'name'),(34,11,'link'),(35,12,'name'),(36,12,'link'),(37,13,'name'),(38,13,'link'),(39,15,'page_link'),(40,15,'page_header'),(41,15,'publish_comments'),(42,17,'page_link'),(43,17,'page_header'),(44,17,'publish_comments'),(45,18,'header'),(46,19,'header'),(47,19,'parse.dispatches-release-message.messages'),(48,19,'unsubscribe_link'),(49,20,'header'),(50,20,'id'),(51,20,'body'),(52,22,'unsubscribe_link'),(53,23,'header'),(54,24,'order_id'),(55,24,'order_name'),(56,24,'order_number'),(57,24,'domain'),(58,24,'total_amount'),(59,24,'total_price'),(60,24,'suffix'),(61,24,'parse.emarket-status-notification-item.items'),(62,24,'status'),(63,24,'personal_params'),(64,25,'order_id'),(65,25,'order_name'),(66,25,'order_number'),(67,25,'domain'),(68,25,'total_amount'),(69,25,'total_price'),(70,25,'suffix'),(71,25,'parse.emarket-status-notification-item.items'),(72,25,'status'),(73,25,'personal_params'),(74,25,'receipt_signature'),(75,26,'link'),(76,26,'name'),(77,26,'price'),(78,26,'suffix'),(79,26,'amount'),(80,27,'header'),(81,28,'order_id'),(82,28,'order_name'),(83,28,'order_number'),(84,28,'domain'),(85,28,'total_amount'),(86,28,'total_price'),(87,28,'suffix'),(88,28,'parse.emarket-neworder-notification-item.items'),(89,28,'payment_type'),(90,28,'payment_status'),(91,28,'first_name'),(92,28,'last_name'),(93,28,'email'),(94,28,'phone'),(95,28,'delivery'),(96,28,'address'),(97,29,'link'),(98,29,'name'),(99,29,'price'),(100,29,'suffix'),(101,29,'amount'),(102,30,'domain'),(103,30,'invoice_link'),(104,31,'domain'),(105,31,'invoice_link'),(106,32,'domain'),(107,32,'element_id'),(108,32,'author_id'),(109,32,'question_link'),(110,32,'ticket'),(111,33,'domain'),(112,33,'element_id'),(113,33,'author_id'),(114,33,'question_link'),(115,33,'ticket'),(116,34,'domain'),(117,34,'question'),(118,34,'ticket'),(119,35,'domain'),(120,35,'question'),(121,35,'ticket'),(122,36,'domain'),(123,36,'question'),(124,36,'question_link'),(125,37,'domain'),(126,37,'question'),(127,37,'question_link'),(128,39,'h1'),(129,39,'message'),(130,39,'unsubscribe_link'),(131,40,'user_id'),(132,40,'login'),(133,41,'user_id'),(134,41,'login'),(135,42,'domain'),(136,42,'restore_link'),(137,42,'email'),(138,42,'login'),(139,43,'domain'),(140,43,'restore_link'),(141,43,'email'),(142,43,'login'),(143,44,'user_id'),(144,44,'domain'),(145,44,'login'),(146,44,'activate_link'),(147,44,'password'),(148,44,'lname'),(149,44,'fname'),(150,44,'father_name'),(151,45,'user_id'),(152,45,'domain'),(153,45,'login'),(154,45,'activate_link'),(155,45,'password'),(156,45,'lname'),(157,45,'fname'),(158,45,'father_name'),(159,46,'user_id'),(160,46,'domain'),(161,46,'login'),(162,46,'activate_link'),(163,46,'password'),(164,46,'lname'),(165,46,'fname'),(166,46,'father_name'),(167,47,'user_id'),(168,47,'domain'),(169,47,'login'),(170,47,'activate_link'),(171,47,'password'),(172,47,'lname'),(173,47,'fname'),(174,47,'father_name'),(175,48,'domain'),(176,48,'login'),(177,48,'password'),(178,49,'domain'),(179,49,'login'),(180,49,'password');
/*!40000 ALTER TABLE `cms3_mail_variables` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cms3_messages`
--

DROP TABLE IF EXISTS `cms3_messages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cms3_messages` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `content` mediumtext NOT NULL,
  `sender_id` int(10) unsigned DEFAULT NULL,
  `create_time` int(11) NOT NULL,
  `type` enum('private','sys-event','sys-log') NOT NULL,
  `priority` int(11) DEFAULT '0',
  `is_sended` int(11) DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `title` (`title`),
  KEY `create_time` (`create_time`),
  KEY `priority` (`priority`),
  KEY `type` (`type`),
  KEY `is_sended` (`is_sended`),
  KEY `FK_Messages to user relation` (`sender_id`),
  CONSTRAINT `FK_Messages to user relation` FOREIGN KEY (`sender_id`) REFERENCES `cms3_objects` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cms3_messages`
--

LOCK TABLES `cms3_messages` WRITE;
/*!40000 ALTER TABLE `cms3_messages` DISABLE KEYS */;
/*!40000 ALTER TABLE `cms3_messages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cms3_messages_inbox`
--

DROP TABLE IF EXISTS `cms3_messages_inbox`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cms3_messages_inbox` (
  `message_id` int(10) unsigned DEFAULT NULL,
  `recipient_id` int(10) unsigned DEFAULT NULL,
  `is_opened` int(11) DEFAULT '0',
  KEY `message_id` (`message_id`),
  KEY `recipient_id` (`recipient_id`),
  KEY `is_opened` (`is_opened`),
  KEY `FK_MessagesInbox to Messages` (`message_id`),
  KEY `FK_MessagesInbox to User` (`recipient_id`),
  CONSTRAINT `FK_MessagesInbox to Messages` FOREIGN KEY (`message_id`) REFERENCES `cms3_messages` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `FK_MessagesInbox to User` FOREIGN KEY (`recipient_id`) REFERENCES `cms3_objects` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cms3_messages_inbox`
--

LOCK TABLES `cms3_messages_inbox` WRITE;
/*!40000 ALTER TABLE `cms3_messages_inbox` DISABLE KEYS */;
/*!40000 ALTER TABLE `cms3_messages_inbox` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cms3_object_content`
--

DROP TABLE IF EXISTS `cms3_object_content`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cms3_object_content` (
  `obj_id` int(10) unsigned DEFAULT NULL,
  `field_id` int(10) unsigned DEFAULT NULL,
  `int_val` bigint(20) DEFAULT NULL,
  `varchar_val` varchar(255) DEFAULT NULL,
  `text_val` mediumtext,
  `rel_val` int(10) unsigned DEFAULT NULL,
  `tree_val` int(10) unsigned DEFAULT NULL,
  `float_val` double DEFAULT NULL,
  KEY `Content to object relation_FK` (`obj_id`),
  KEY `Contents field id relation_FK` (`field_id`),
  KEY `Relation value reference_FK` (`rel_val`),
  KEY `content2tree_FK` (`tree_val`),
  KEY `int_val` (`int_val`),
  KEY `varchar_val` (`varchar_val`),
  KEY `float_val` (`float_val`),
  KEY `text_val` (`text_val`(8)),
  KEY `K_Complex_FieldIdAndRelVal` (`field_id`,`rel_val`),
  KEY `K_Complex_FieldIdAndTreeVal` (`field_id`,`tree_val`),
  KEY `K_Complex_ObjIdAndFieldId` (`obj_id`,`field_id`),
  CONSTRAINT `FK_Content to object relation` FOREIGN KEY (`obj_id`) REFERENCES `cms3_objects` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `FK_Contents field id relation` FOREIGN KEY (`field_id`) REFERENCES `cms3_object_fields` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `FK_Relation value reference` FOREIGN KEY (`rel_val`) REFERENCES `cms3_objects` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `FK_content2tree` FOREIGN KEY (`tree_val`) REFERENCES `cms3_hierarchy` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cms3_object_content`
--

LOCK TABLES `cms3_object_content` WRITE;
/*!40000 ALTER TABLE `cms3_object_content` DISABLE KEYS */;
INSERT INTO `cms3_object_content` VALUES (1,76,NULL,'Демонстрационный магазин UMI.CMS',NULL,NULL,NULL,NULL),(1,77,1,NULL,NULL,NULL,NULL,NULL),(1,73,NULL,'vkontakte',NULL,NULL,NULL,NULL),(6,29,NULL,'low',NULL,NULL,NULL,NULL),(7,29,NULL,'normal',NULL,NULL,NULL,NULL),(8,29,NULL,'high',NULL,NULL,NULL,NULL),(9,30,NULL,'US',NULL,NULL,NULL,NULL),(10,30,NULL,'RU',NULL,NULL,NULL,NULL),(179,34,NULL,'item',NULL,NULL,NULL,NULL),(179,60,NULL,'Скидки этого типа могут быть применены к товарам, либо разделам в каталоге',NULL,NULL,NULL,NULL),(180,34,NULL,'order',NULL,NULL,NULL,NULL),(180,60,NULL,'Данный тип скидок используется для расчета итоговой цены заказа',NULL,NULL,NULL,NULL),(181,79,NULL,'Супервайзеры',NULL,NULL,NULL,NULL),(182,203,NULL,'admin',NULL,NULL,NULL,NULL),(182,209,1,NULL,NULL,NULL,NULL,NULL),(182,210,1445595536,NULL,NULL,NULL,NULL,NULL),(182,214,0,NULL,NULL,NULL,NULL,NULL),(182,217,NULL,'content,users,emarket,catalog,data,trash',NULL,NULL,NULL,NULL),(182,231,NULL,'UMI CMS 2',NULL,NULL,NULL,NULL),(183,34,NULL,'bonus',NULL,NULL,NULL,NULL),(183,60,NULL,'Зачисление бонусов за оплаченный заказ',NULL,NULL,NULL,NULL),(184,61,NULL,'proc',NULL,NULL,NULL,NULL),(184,62,56,NULL,NULL,NULL,NULL,NULL),(184,64,NULL,'emarket-discountmodificator-768',NULL,NULL,NULL,NULL),(185,235,1,NULL,NULL,NULL,NULL,NULL),(186,66,NULL,'items',NULL,NULL,NULL,NULL),(186,69,NULL,'emarket-discountrule-777',NULL,NULL,NULL,NULL),(187,87,NULL,'digital',NULL,NULL,NULL,NULL),(188,87,NULL,'optioned',NULL,NULL,NULL,NULL),(189,132,NULL,'self',NULL,NULL,NULL,NULL),(189,134,NULL,'emarket-delivery-783',NULL,NULL,NULL,NULL),(190,132,NULL,'courier',NULL,NULL,NULL,NULL),(190,134,NULL,'emarket-delivery-784',NULL,NULL,NULL,NULL),(191,112,NULL,'receipt',NULL,NULL,NULL,NULL),(191,114,NULL,'emarket-payment-791',NULL,NULL,NULL,NULL),(192,40,NULL,'canceled',NULL,NULL,NULL,NULL),(192,111,50,NULL,NULL,NULL,NULL,NULL),(193,40,NULL,'rejected',NULL,NULL,NULL,NULL),(193,111,40,NULL,NULL,NULL,NULL,NULL),(194,40,NULL,'payment',NULL,NULL,NULL,NULL),(194,111,60,NULL,NULL,NULL,NULL,NULL),(195,40,NULL,'delivery',NULL,NULL,NULL,NULL),(195,111,70,NULL,NULL,NULL,NULL,NULL),(196,40,NULL,'waiting',NULL,NULL,NULL,NULL),(196,111,100,NULL,NULL,NULL,NULL,NULL),(197,40,NULL,'accepted',NULL,NULL,NULL,NULL),(197,111,80,NULL,NULL,NULL,NULL,NULL),(198,40,NULL,'ready',NULL,NULL,NULL,NULL),(198,111,30,NULL,NULL,NULL,NULL,NULL),(199,40,NULL,'waiting_shipping',NULL,NULL,NULL,NULL),(199,111,40,NULL,NULL,NULL,NULL,NULL),(200,40,NULL,'shipping',NULL,NULL,NULL,NULL),(200,111,50,NULL,NULL,NULL,NULL,NULL),(201,40,NULL,'ready',NULL,NULL,NULL,NULL),(201,111,60,NULL,NULL,NULL,NULL,NULL),(202,40,NULL,'initialized',NULL,NULL,NULL,NULL),(202,111,40,NULL,NULL,NULL,NULL,NULL),(203,40,NULL,'validated',NULL,NULL,NULL,NULL),(203,111,60,NULL,NULL,NULL,NULL,NULL),(204,40,NULL,'declined',NULL,NULL,NULL,NULL),(204,111,70,NULL,NULL,NULL,NULL,NULL),(205,40,NULL,'accepted',NULL,NULL,NULL,NULL),(205,111,50,NULL,NULL,NULL,NULL,NULL),(206,66,NULL,'dateRange',NULL,NULL,NULL,NULL),(206,69,NULL,'emarket-discountrule-794',NULL,NULL,NULL,NULL),(207,66,NULL,'orderPrice',NULL,NULL,NULL,NULL),(207,69,NULL,'emarket-discountrule-795',NULL,NULL,NULL,NULL),(208,66,NULL,'allOrdersPrices',NULL,NULL,NULL,NULL),(208,69,NULL,'emarket-discountrule-796',NULL,NULL,NULL,NULL),(209,66,NULL,'userGroups',NULL,NULL,NULL,NULL),(209,69,NULL,'emarket-discountrule-797',NULL,NULL,NULL,NULL),(210,66,NULL,'users',NULL,NULL,NULL,NULL),(210,67,57,NULL,NULL,NULL,NULL,NULL),(210,69,NULL,'emarket-discountrule-798',NULL,NULL,NULL,NULL),(211,66,NULL,'relatedItems',NULL,NULL,NULL,NULL),(211,69,NULL,'emarket-discountrule-799',NULL,NULL,NULL,NULL),(212,61,NULL,'absolute',NULL,NULL,NULL,NULL),(212,64,NULL,'emarket-discountmodificator-800',NULL,NULL,NULL,NULL),(213,112,NULL,'payonline',NULL,NULL,NULL,NULL),(213,114,NULL,'emarket-payment-801',NULL,NULL,NULL,NULL),(214,112,NULL,'courier',NULL,NULL,NULL,NULL),(214,114,NULL,'emarket-payment-802',NULL,NULL,NULL,NULL),(215,31,1,NULL,NULL,NULL,NULL,NULL),(216,31,2,NULL,NULL,NULL,NULL,NULL),(217,31,3,NULL,NULL,NULL,NULL,NULL),(218,31,4,NULL,NULL,NULL,NULL,NULL),(219,132,NULL,'russianpost',NULL,NULL,NULL,NULL),(219,134,NULL,'emarket-delivery-808',NULL,NULL,NULL,NULL),(220,112,NULL,'robox',NULL,NULL,NULL,NULL),(220,114,NULL,'emarket-payment-812',NULL,NULL,NULL,NULL),(221,112,NULL,'rbk',NULL,NULL,NULL,NULL),(221,114,NULL,'emarket-payment-813',NULL,NULL,NULL,NULL),(222,32,0,NULL,NULL,NULL,NULL,NULL),(223,32,1,NULL,NULL,NULL,NULL,NULL),(224,32,2,NULL,NULL,NULL,NULL,NULL),(225,32,3,NULL,NULL,NULL,NULL,NULL),(226,32,4,NULL,NULL,NULL,NULL,NULL),(227,32,5,NULL,NULL,NULL,NULL,NULL),(228,32,6,NULL,NULL,NULL,NULL,NULL),(229,32,7,NULL,NULL,NULL,NULL,NULL),(230,32,8,NULL,NULL,NULL,NULL,NULL),(231,32,9,NULL,NULL,NULL,NULL,NULL),(232,32,10,NULL,NULL,NULL,NULL,NULL),(233,32,11,NULL,NULL,NULL,NULL,NULL),(234,32,12,NULL,NULL,NULL,NULL,NULL),(235,32,13,NULL,NULL,NULL,NULL,NULL),(236,32,14,NULL,NULL,NULL,NULL,NULL),(237,32,15,NULL,NULL,NULL,NULL,NULL),(238,32,16,NULL,NULL,NULL,NULL,NULL),(239,32,17,NULL,NULL,NULL,NULL,NULL),(240,32,18,NULL,NULL,NULL,NULL,NULL),(241,32,19,NULL,NULL,NULL,NULL,NULL),(242,32,20,NULL,NULL,NULL,NULL,NULL),(243,32,21,NULL,NULL,NULL,NULL,NULL),(244,32,22,NULL,NULL,NULL,NULL,NULL),(245,32,23,NULL,NULL,NULL,NULL,NULL),(246,32,1,NULL,NULL,NULL,NULL,NULL),(247,32,2,NULL,NULL,NULL,NULL,NULL),(248,32,3,NULL,NULL,NULL,NULL,NULL),(249,32,4,NULL,NULL,NULL,NULL,NULL),(250,32,5,NULL,NULL,NULL,NULL,NULL),(251,32,6,NULL,NULL,NULL,NULL,NULL),(252,32,7,NULL,NULL,NULL,NULL,NULL),(253,112,NULL,'invoice',NULL,NULL,NULL,NULL),(253,114,NULL,'emarket-payment-816',NULL,NULL,NULL,NULL),(254,33,1,NULL,NULL,NULL,NULL,NULL),(255,33,2,NULL,NULL,NULL,NULL,NULL),(256,33,3,NULL,NULL,NULL,NULL,NULL),(257,33,4,NULL,NULL,NULL,NULL,NULL),(258,33,5,NULL,NULL,NULL,NULL,NULL),(259,33,7,NULL,NULL,NULL,NULL,NULL),(260,33,8,NULL,NULL,NULL,NULL,NULL),(261,33,9,NULL,NULL,NULL,NULL,NULL),(262,33,10,NULL,NULL,NULL,NULL,NULL),(263,33,11,NULL,NULL,NULL,NULL,NULL),(264,33,12,NULL,NULL,NULL,NULL,NULL),(265,33,13,NULL,NULL,NULL,NULL,NULL),(266,33,14,NULL,NULL,NULL,NULL,NULL),(267,33,15,NULL,NULL,NULL,NULL,NULL),(268,33,16,NULL,NULL,NULL,NULL,NULL),(269,33,17,NULL,NULL,NULL,NULL,NULL),(270,33,18,NULL,NULL,NULL,NULL,NULL),(271,33,19,NULL,NULL,NULL,NULL,NULL),(272,33,20,NULL,NULL,NULL,NULL,NULL),(273,33,21,NULL,NULL,NULL,NULL,NULL),(279,112,NULL,'payanyway',NULL,NULL,NULL,NULL),(279,114,NULL,'emarket-payment-payanyway',NULL,NULL,NULL,NULL),(280,112,NULL,'dengionline',NULL,NULL,NULL,NULL),(280,114,NULL,'emarket-payment-dengionline',NULL,NULL,NULL,NULL),(281,40,NULL,'editing',NULL,NULL,NULL,NULL),(281,111,90,NULL,NULL,NULL,NULL,NULL),(282,34,NULL,'bank',NULL,NULL,NULL,NULL),(283,34,NULL,'partner',NULL,NULL,NULL,NULL),(284,112,NULL,'kupivkredit',NULL,NULL,NULL,NULL),(284,114,NULL,'emarket-payment-kvk',NULL,NULL,NULL,NULL),(285,40,NULL,'new',NULL,NULL,NULL,NULL),(286,40,NULL,'hol',NULL,NULL,NULL,NULL),(287,40,NULL,'ver',NULL,NULL,NULL,NULL),(288,40,NULL,'rej',NULL,NULL,NULL,NULL),(289,40,NULL,'can',NULL,NULL,NULL,NULL),(290,40,NULL,'ovr',NULL,NULL,NULL,NULL),(291,40,NULL,'agr',NULL,NULL,NULL,NULL),(292,40,NULL,'app',NULL,NULL,NULL,NULL),(293,40,NULL,'prr',NULL,NULL,NULL,NULL),(294,40,NULL,'pvr',NULL,NULL,NULL,NULL),(295,40,NULL,'fap',NULL,NULL,NULL,NULL),(296,41,NULL,'android',NULL,NULL,NULL,NULL),(297,41,NULL,'ios',NULL,NULL,NULL,NULL),(298,40,NULL,'not_defined',NULL,NULL,NULL,NULL),(298,111,30,NULL,NULL,NULL,NULL,NULL),(299,40,NULL,'not_defined',NULL,NULL,NULL,NULL),(299,111,30,NULL,NULL,NULL,NULL,NULL),(300,23,NULL,'cp1251',NULL,NULL,NULL,NULL),(301,23,NULL,'utf-8',NULL,NULL,NULL,NULL),(302,112,NULL,'acquiropay',NULL,NULL,NULL,NULL),(302,114,NULL,'emarket-payment-acquiropay',NULL,NULL,NULL,NULL),(303,112,NULL,'yandex30',NULL,NULL,NULL,NULL),(303,114,NULL,'emarket-payment-yandex30',NULL,NULL,NULL,NULL),(304,112,NULL,'paypal',NULL,NULL,NULL,NULL),(304,114,NULL,'emarket-payment-paypal',NULL,NULL,NULL,NULL),(307,132,NULL,'ApiShip',NULL,NULL,NULL,NULL),(307,134,NULL,'emarket-delivery-842',NULL,NULL,NULL,NULL),(308,40,NULL,'canceled',NULL,NULL,NULL,NULL),(308,111,50,NULL,NULL,NULL,NULL,NULL),(309,40,NULL,'return',NULL,NULL,NULL,NULL),(309,111,60,NULL,NULL,NULL,NULL,NULL),(310,30,NULL,'AU',NULL,NULL,NULL,NULL),(311,30,NULL,'AT',NULL,NULL,NULL,NULL),(312,30,NULL,'AZ',NULL,NULL,NULL,NULL),(313,30,NULL,'AX',NULL,NULL,NULL,NULL),(314,30,NULL,'AL',NULL,NULL,NULL,NULL),(315,30,NULL,'DZ',NULL,NULL,NULL,NULL),(316,30,NULL,'AS',NULL,NULL,NULL,NULL),(317,30,NULL,'AI',NULL,NULL,NULL,NULL),(318,30,NULL,'AO',NULL,NULL,NULL,NULL),(319,30,NULL,'AD',NULL,NULL,NULL,NULL),(320,30,NULL,'AQ',NULL,NULL,NULL,NULL),(321,30,NULL,'AG',NULL,NULL,NULL,NULL),(322,30,NULL,'AR',NULL,NULL,NULL,NULL),(323,30,NULL,'AM',NULL,NULL,NULL,NULL),(324,30,NULL,'AW',NULL,NULL,NULL,NULL),(325,30,NULL,'AF',NULL,NULL,NULL,NULL),(326,30,NULL,'BS',NULL,NULL,NULL,NULL),(327,30,NULL,'BD',NULL,NULL,NULL,NULL),(328,30,NULL,'BB',NULL,NULL,NULL,NULL),(329,30,NULL,'BH',NULL,NULL,NULL,NULL),(330,30,NULL,'BY',NULL,NULL,NULL,NULL),(331,30,NULL,'BZ',NULL,NULL,NULL,NULL),(332,30,NULL,'BE',NULL,NULL,NULL,NULL),(333,30,NULL,'BJ',NULL,NULL,NULL,NULL),(334,30,NULL,'BM',NULL,NULL,NULL,NULL),(335,30,NULL,'BG',NULL,NULL,NULL,NULL),(336,30,NULL,'BO',NULL,NULL,NULL,NULL),(337,30,NULL,'BQ',NULL,NULL,NULL,NULL),(338,30,NULL,'BA',NULL,NULL,NULL,NULL),(339,30,NULL,'BW',NULL,NULL,NULL,NULL),(340,30,NULL,'BR',NULL,NULL,NULL,NULL),(341,30,NULL,'IO',NULL,NULL,NULL,NULL),(342,30,NULL,'BN',NULL,NULL,NULL,NULL),(343,30,NULL,'BF',NULL,NULL,NULL,NULL),(344,30,NULL,'BI',NULL,NULL,NULL,NULL),(345,30,NULL,'BT',NULL,NULL,NULL,NULL),(346,30,NULL,'VU',NULL,NULL,NULL,NULL),(347,30,NULL,'VA',NULL,NULL,NULL,NULL),(348,30,NULL,'GB',NULL,NULL,NULL,NULL),(349,30,NULL,'HU',NULL,NULL,NULL,NULL),(350,30,NULL,'VE',NULL,NULL,NULL,NULL),(351,30,NULL,'VG',NULL,NULL,NULL,NULL),(352,30,NULL,'VI',NULL,NULL,NULL,NULL),(353,30,NULL,'UM',NULL,NULL,NULL,NULL),(354,30,NULL,'TL',NULL,NULL,NULL,NULL),(355,30,NULL,'VN',NULL,NULL,NULL,NULL),(356,30,NULL,'GA',NULL,NULL,NULL,NULL),(357,30,NULL,'HT',NULL,NULL,NULL,NULL),(358,30,NULL,'GY',NULL,NULL,NULL,NULL),(359,30,NULL,'GM',NULL,NULL,NULL,NULL),(360,30,NULL,'GH',NULL,NULL,NULL,NULL),(361,30,NULL,'GP',NULL,NULL,NULL,NULL),(362,30,NULL,'GT',NULL,NULL,NULL,NULL),(363,30,NULL,'GN',NULL,NULL,NULL,NULL),(364,30,NULL,'GW',NULL,NULL,NULL,NULL),(365,30,NULL,'DE',NULL,NULL,NULL,NULL),(366,30,NULL,'GG',NULL,NULL,NULL,NULL),(367,30,NULL,'GI',NULL,NULL,NULL,NULL),(368,30,NULL,'HN',NULL,NULL,NULL,NULL),(369,30,NULL,'HK',NULL,NULL,NULL,NULL),(370,30,NULL,'GD',NULL,NULL,NULL,NULL),(371,30,NULL,'GL',NULL,NULL,NULL,NULL),(372,30,NULL,'GR',NULL,NULL,NULL,NULL),(373,30,NULL,'GE',NULL,NULL,NULL,NULL),(374,30,NULL,'GU',NULL,NULL,NULL,NULL),(375,30,NULL,'DK',NULL,NULL,NULL,NULL),(376,30,NULL,'JE',NULL,NULL,NULL,NULL),(377,30,NULL,'DJ',NULL,NULL,NULL,NULL),(378,30,NULL,'DG',NULL,NULL,NULL,NULL),(379,30,NULL,'DM',NULL,NULL,NULL,NULL),(380,30,NULL,'DO',NULL,NULL,NULL,NULL),(381,30,NULL,'EG',NULL,NULL,NULL,NULL),(382,30,NULL,'ZM',NULL,NULL,NULL,NULL),(383,30,NULL,'EH',NULL,NULL,NULL,NULL),(384,30,NULL,'ZW',NULL,NULL,NULL,NULL),(385,30,NULL,'IL',NULL,NULL,NULL,NULL),(386,30,NULL,'IN',NULL,NULL,NULL,NULL),(387,30,NULL,'ID',NULL,NULL,NULL,NULL),(388,30,NULL,'JO',NULL,NULL,NULL,NULL),(389,30,NULL,'IQ',NULL,NULL,NULL,NULL),(390,30,NULL,'IR',NULL,NULL,NULL,NULL),(391,30,NULL,'IE',NULL,NULL,NULL,NULL),(392,30,NULL,'IS',NULL,NULL,NULL,NULL),(393,30,NULL,'ES',NULL,NULL,NULL,NULL),(394,30,NULL,'IT',NULL,NULL,NULL,NULL),(395,30,NULL,'YE',NULL,NULL,NULL,NULL),(396,30,NULL,'CV',NULL,NULL,NULL,NULL),(397,30,NULL,'KZ',NULL,NULL,NULL,NULL),(398,30,NULL,'KY',NULL,NULL,NULL,NULL),(399,30,NULL,'KH',NULL,NULL,NULL,NULL),(400,30,NULL,'CM',NULL,NULL,NULL,NULL),(401,30,NULL,'CA',NULL,NULL,NULL,NULL),(402,30,NULL,'IC',NULL,NULL,NULL,NULL),(403,30,NULL,'QA',NULL,NULL,NULL,NULL),(404,30,NULL,'KE',NULL,NULL,NULL,NULL),(405,30,NULL,'CY',NULL,NULL,NULL,NULL),(406,30,NULL,'KG',NULL,NULL,NULL,NULL),(407,30,NULL,'KI',NULL,NULL,NULL,NULL),(408,30,NULL,'CN',NULL,NULL,NULL,NULL),(409,30,NULL,'KP',NULL,NULL,NULL,NULL),(410,30,NULL,'CC',NULL,NULL,NULL,NULL),(411,30,NULL,'CO',NULL,NULL,NULL,NULL),(412,30,NULL,'KM',NULL,NULL,NULL,NULL),(413,30,NULL,'CG',NULL,NULL,NULL,NULL),(414,30,NULL,'CD',NULL,NULL,NULL,NULL),(415,30,NULL,'XK',NULL,NULL,NULL,NULL),(416,30,NULL,'CR',NULL,NULL,NULL,NULL),(417,30,NULL,'CI',NULL,NULL,NULL,NULL),(418,30,NULL,'CU',NULL,NULL,NULL,NULL),(419,30,NULL,'KW',NULL,NULL,NULL,NULL),(420,30,NULL,'CW',NULL,NULL,NULL,NULL),(421,30,NULL,'LA',NULL,NULL,NULL,NULL),(422,30,NULL,'LV',NULL,NULL,NULL,NULL),(423,30,NULL,'LS',NULL,NULL,NULL,NULL),(424,30,NULL,'LR',NULL,NULL,NULL,NULL),(425,30,NULL,'LB',NULL,NULL,NULL,NULL),(426,30,NULL,'LY',NULL,NULL,NULL,NULL),(427,30,NULL,'LT',NULL,NULL,NULL,NULL),(428,30,NULL,'LI',NULL,NULL,NULL,NULL),(429,30,NULL,'LU',NULL,NULL,NULL,NULL),(430,30,NULL,'MU',NULL,NULL,NULL,NULL),(431,30,NULL,'MR',NULL,NULL,NULL,NULL),(432,30,NULL,'MG',NULL,NULL,NULL,NULL),(433,30,NULL,'YT',NULL,NULL,NULL,NULL),(434,30,NULL,'MO',NULL,NULL,NULL,NULL),(435,30,NULL,'MK',NULL,NULL,NULL,NULL),(436,30,NULL,'MW',NULL,NULL,NULL,NULL),(437,30,NULL,'MY',NULL,NULL,NULL,NULL),(438,30,NULL,'ML',NULL,NULL,NULL,NULL),(439,30,NULL,'MV',NULL,NULL,NULL,NULL),(440,30,NULL,'MT',NULL,NULL,NULL,NULL),(441,30,NULL,'MA',NULL,NULL,NULL,NULL),(442,30,NULL,'MQ',NULL,NULL,NULL,NULL),(443,30,NULL,'MH',NULL,NULL,NULL,NULL),(444,30,NULL,'MX',NULL,NULL,NULL,NULL),(445,30,NULL,'MZ',NULL,NULL,NULL,NULL),(446,30,NULL,'MD',NULL,NULL,NULL,NULL),(447,30,NULL,'MC',NULL,NULL,NULL,NULL),(448,30,NULL,'MN',NULL,NULL,NULL,NULL),(449,30,NULL,'MS',NULL,NULL,NULL,NULL),(450,30,NULL,'MM',NULL,NULL,NULL,NULL),(451,30,NULL,'NA',NULL,NULL,NULL,NULL),(452,30,NULL,'NR',NULL,NULL,NULL,NULL),(453,30,NULL,'NP',NULL,NULL,NULL,NULL),(454,30,NULL,'NE',NULL,NULL,NULL,NULL),(455,30,NULL,'NG',NULL,NULL,NULL,NULL),(456,30,NULL,'NL',NULL,NULL,NULL,NULL),(457,30,NULL,'NI',NULL,NULL,NULL,NULL),(458,30,NULL,'NU',NULL,NULL,NULL,NULL),(459,30,NULL,'NZ',NULL,NULL,NULL,NULL),(460,30,NULL,'NC',NULL,NULL,NULL,NULL),(461,30,NULL,'NO',NULL,NULL,NULL,NULL),(462,30,NULL,'AC',NULL,NULL,NULL,NULL),(463,30,NULL,'IM',NULL,NULL,NULL,NULL),(464,30,NULL,'NF',NULL,NULL,NULL,NULL),(465,30,NULL,'CX',NULL,NULL,NULL,NULL),(466,30,NULL,'SH',NULL,NULL,NULL,NULL),(467,30,NULL,'CK',NULL,NULL,NULL,NULL),(468,30,NULL,'TC',NULL,NULL,NULL,NULL),(469,30,NULL,'AE',NULL,NULL,NULL,NULL),(470,30,NULL,'OM',NULL,NULL,NULL,NULL),(471,30,NULL,'PK',NULL,NULL,NULL,NULL),(472,30,NULL,'PW',NULL,NULL,NULL,NULL),(473,30,NULL,'PS',NULL,NULL,NULL,NULL),(474,30,NULL,'PA',NULL,NULL,NULL,NULL),(475,30,NULL,'PG',NULL,NULL,NULL,NULL),(476,30,NULL,'PY',NULL,NULL,NULL,NULL),(477,30,NULL,'PE',NULL,NULL,NULL,NULL),(478,30,NULL,'PN',NULL,NULL,NULL,NULL),(479,30,NULL,'PL',NULL,NULL,NULL,NULL),(480,30,NULL,'PT',NULL,NULL,NULL,NULL),(481,30,NULL,'PR',NULL,NULL,NULL,NULL),(482,30,NULL,'KR',NULL,NULL,NULL,NULL),(483,30,NULL,'RE',NULL,NULL,NULL,NULL),(484,30,NULL,'RW',NULL,NULL,NULL,NULL),(485,30,NULL,'RO',NULL,NULL,NULL,NULL),(486,30,NULL,'SV',NULL,NULL,NULL,NULL),(487,30,NULL,'WS',NULL,NULL,NULL,NULL),(488,30,NULL,'SM',NULL,NULL,NULL,NULL),(489,30,NULL,'ST',NULL,NULL,NULL,NULL),(490,30,NULL,'SA',NULL,NULL,NULL,NULL),(491,30,NULL,'SZ',NULL,NULL,NULL,NULL),(492,30,NULL,'MP',NULL,NULL,NULL,NULL),(493,30,NULL,'SC',NULL,NULL,NULL,NULL),(494,30,NULL,'BL',NULL,NULL,NULL,NULL),(495,30,NULL,'MF',NULL,NULL,NULL,NULL),(496,30,NULL,'PM',NULL,NULL,NULL,NULL),(497,30,NULL,'SN',NULL,NULL,NULL,NULL),(498,30,NULL,'VC',NULL,NULL,NULL,NULL),(499,30,NULL,'KN',NULL,NULL,NULL,NULL),(500,30,NULL,'LC',NULL,NULL,NULL,NULL),(501,30,NULL,'RS',NULL,NULL,NULL,NULL),(502,30,NULL,'EA',NULL,NULL,NULL,NULL),(503,30,NULL,'SG',NULL,NULL,NULL,NULL),(504,30,NULL,'SX',NULL,NULL,NULL,NULL),(505,30,NULL,'SY',NULL,NULL,NULL,NULL),(506,30,NULL,'SK',NULL,NULL,NULL,NULL),(507,30,NULL,'SI',NULL,NULL,NULL,NULL),(508,30,NULL,'SB',NULL,NULL,NULL,NULL),(509,30,NULL,'SO',NULL,NULL,NULL,NULL),(510,30,NULL,'SD',NULL,NULL,NULL,NULL),(511,30,NULL,'SR',NULL,NULL,NULL,NULL),(512,30,NULL,'SL',NULL,NULL,NULL,NULL),(513,30,NULL,'TJ',NULL,NULL,NULL,NULL),(514,30,NULL,'TH',NULL,NULL,NULL,NULL),(515,30,NULL,'TW',NULL,NULL,NULL,NULL),(516,30,NULL,'TZ',NULL,NULL,NULL,NULL),(517,30,NULL,'TG',NULL,NULL,NULL,NULL),(518,30,NULL,'TK',NULL,NULL,NULL,NULL),(519,30,NULL,'TO',NULL,NULL,NULL,NULL),(520,30,NULL,'TT',NULL,NULL,NULL,NULL),(521,30,NULL,'TA',NULL,NULL,NULL,NULL),(522,30,NULL,'TV',NULL,NULL,NULL,NULL),(523,30,NULL,'TN',NULL,NULL,NULL,NULL),(524,30,NULL,'TM',NULL,NULL,NULL,NULL),(525,30,NULL,'TR',NULL,NULL,NULL,NULL),(526,30,NULL,'UG',NULL,NULL,NULL,NULL),(527,30,NULL,'UZ',NULL,NULL,NULL,NULL),(528,30,NULL,'UA',NULL,NULL,NULL,NULL),(529,30,NULL,'WF',NULL,NULL,NULL,NULL),(530,30,NULL,'UY',NULL,NULL,NULL,NULL),(531,30,NULL,'FO',NULL,NULL,NULL,NULL),(532,30,NULL,'FM',NULL,NULL,NULL,NULL),(533,30,NULL,'FJ',NULL,NULL,NULL,NULL),(534,30,NULL,'PH',NULL,NULL,NULL,NULL),(535,30,NULL,'FI',NULL,NULL,NULL,NULL),(536,30,NULL,'FK',NULL,NULL,NULL,NULL),(537,30,NULL,'FR',NULL,NULL,NULL,NULL),(538,30,NULL,'GF',NULL,NULL,NULL,NULL),(539,30,NULL,'PF',NULL,NULL,NULL,NULL),(540,30,NULL,'TF',NULL,NULL,NULL,NULL),(541,30,NULL,'HR',NULL,NULL,NULL,NULL),(542,30,NULL,'CF',NULL,NULL,NULL,NULL),(543,30,NULL,'TD',NULL,NULL,NULL,NULL),(544,30,NULL,'ME',NULL,NULL,NULL,NULL),(545,30,NULL,'CZ',NULL,NULL,NULL,NULL),(546,30,NULL,'CL',NULL,NULL,NULL,NULL),(547,30,NULL,'CH',NULL,NULL,NULL,NULL),(548,30,NULL,'SE',NULL,NULL,NULL,NULL),(549,30,NULL,'SJ',NULL,NULL,NULL,NULL),(550,30,NULL,'LK',NULL,NULL,NULL,NULL),(551,30,NULL,'EC',NULL,NULL,NULL,NULL),(552,30,NULL,'GQ',NULL,NULL,NULL,NULL),(553,30,NULL,'ER',NULL,NULL,NULL,NULL),(554,30,NULL,'EE',NULL,NULL,NULL,NULL),(555,30,NULL,'ET',NULL,NULL,NULL,NULL),(556,30,NULL,'ZA',NULL,NULL,NULL,NULL),(557,30,NULL,'GS',NULL,NULL,NULL,NULL),(558,30,NULL,'SS',NULL,NULL,NULL,NULL),(559,30,NULL,'JM',NULL,NULL,NULL,NULL),(560,30,NULL,'JP',NULL,NULL,NULL,NULL),(561,112,NULL,'YandexKassa',NULL,NULL,NULL,NULL),(561,114,NULL,'emarket-payment-yandex-kassa',NULL,NULL,NULL,NULL),(562,87,NULL,'custom',NULL,NULL,NULL,NULL),(563,87,NULL,'TradeOffer',NULL,NULL,NULL,NULL),(564,1,NULL,'page_status_publish',NULL,NULL,NULL,NULL),(565,51,1,NULL,NULL,NULL,NULL,NULL),(565,52,NULL,'none',NULL,NULL,NULL,NULL),(565,53,NULL,'none',NULL,NULL,NULL,NULL),(565,54,1105,NULL,NULL,NULL,NULL,NULL),(565,55,0,NULL,NULL,NULL,NULL,NULL),(565,56,NULL,'none',NULL,NULL,NULL,NULL),(566,31,3000,NULL,NULL,NULL,NULL,NULL),(567,57,NULL,'commodity',NULL,NULL,NULL,NULL),(567,52,NULL,'commodity',NULL,NULL,NULL,NULL),(567,58,NULL,'commodity',NULL,NULL,NULL,NULL),(567,59,1,NULL,NULL,NULL,NULL,NULL),(567,55,1,NULL,NULL,NULL,NULL,NULL),(568,57,NULL,'full_prepayment',NULL,NULL,NULL,NULL),(568,52,NULL,'full_prepayment',NULL,NULL,NULL,NULL),(568,58,NULL,'full_prepayment',NULL,NULL,NULL,NULL),(568,59,1,NULL,NULL,NULL,NULL,NULL),(568,55,1,NULL,NULL,NULL,NULL,NULL),(569,1,NULL,'page_status_unpublish',NULL,NULL,NULL,NULL),(570,31,3010,NULL,NULL,NULL,NULL,NULL),(571,51,2,NULL,NULL,NULL,NULL,NULL),(571,52,NULL,'vat0',NULL,NULL,NULL,NULL),(571,53,NULL,'vat0',NULL,NULL,NULL,NULL),(571,54,1104,NULL,NULL,NULL,NULL,NULL),(571,55,1,NULL,NULL,NULL,NULL,NULL),(571,56,NULL,'0',NULL,NULL,NULL,NULL),(572,57,NULL,'excise',NULL,NULL,NULL,NULL),(572,52,NULL,'excise',NULL,NULL,NULL,NULL),(572,58,NULL,'excise',NULL,NULL,NULL,NULL),(572,59,2,NULL,NULL,NULL,NULL,NULL),(572,55,2,NULL,NULL,NULL,NULL,NULL),(573,57,NULL,'partial_prepayment',NULL,NULL,NULL,NULL),(573,52,NULL,'prepayment',NULL,NULL,NULL,NULL),(573,58,NULL,'prepayment',NULL,NULL,NULL,NULL),(573,59,2,NULL,NULL,NULL,NULL,NULL),(573,55,2,NULL,NULL,NULL,NULL,NULL),(574,1,NULL,'page_status_preunpublish',NULL,NULL,NULL,NULL),(575,31,3020,NULL,NULL,NULL,NULL,NULL),(576,51,3,NULL,NULL,NULL,NULL,NULL),(576,52,NULL,'vat10',NULL,NULL,NULL,NULL),(576,53,NULL,'vat10',NULL,NULL,NULL,NULL),(576,54,1103,NULL,NULL,NULL,NULL,NULL),(576,55,2,NULL,NULL,NULL,NULL,NULL),(576,56,NULL,'10',NULL,NULL,NULL,NULL),(577,57,NULL,'job',NULL,NULL,NULL,NULL),(577,52,NULL,'job',NULL,NULL,NULL,NULL),(577,58,NULL,'job',NULL,NULL,NULL,NULL),(577,59,3,NULL,NULL,NULL,NULL,NULL),(577,55,3,NULL,NULL,NULL,NULL,NULL),(578,57,NULL,'advance',NULL,NULL,NULL,NULL),(578,52,NULL,'advance',NULL,NULL,NULL,NULL),(578,58,NULL,'advance',NULL,NULL,NULL,NULL),(578,59,3,NULL,NULL,NULL,NULL,NULL),(578,55,3,NULL,NULL,NULL,NULL,NULL),(579,31,16010,NULL,NULL,NULL,NULL,NULL),(580,51,4,NULL,NULL,NULL,NULL,NULL),(580,52,NULL,'vat20',NULL,NULL,NULL,NULL),(580,53,NULL,'vat20',NULL,NULL,NULL,NULL),(580,54,1102,NULL,NULL,NULL,NULL,NULL),(580,55,6,NULL,NULL,NULL,NULL,NULL),(580,56,NULL,'20',NULL,NULL,NULL,NULL),(581,57,NULL,'service',NULL,NULL,NULL,NULL),(581,52,NULL,'service',NULL,NULL,NULL,NULL),(581,58,NULL,'service',NULL,NULL,NULL,NULL),(581,59,4,NULL,NULL,NULL,NULL,NULL),(581,55,4,NULL,NULL,NULL,NULL,NULL),(582,57,NULL,'full_payment',NULL,NULL,NULL,NULL),(582,52,NULL,'full_payment',NULL,NULL,NULL,NULL),(582,58,NULL,'full_payment',NULL,NULL,NULL,NULL),(582,59,4,NULL,NULL,NULL,NULL,NULL),(582,55,4,NULL,NULL,NULL,NULL,NULL),(583,31,16020,NULL,NULL,NULL,NULL,NULL),(584,51,5,NULL,NULL,NULL,NULL,NULL),(584,52,NULL,'vat110',NULL,NULL,NULL,NULL),(584,53,NULL,'vat110',NULL,NULL,NULL,NULL),(584,54,1107,NULL,NULL,NULL,NULL,NULL),(584,55,4,NULL,NULL,NULL,NULL,NULL),(584,56,NULL,'10/110',NULL,NULL,NULL,NULL),(585,57,NULL,'gambling_bet',NULL,NULL,NULL,NULL),(585,52,NULL,'gambling_bet',NULL,NULL,NULL,NULL),(585,58,NULL,'gambling_bet',NULL,NULL,NULL,NULL),(585,59,5,NULL,NULL,NULL,NULL,NULL),(585,55,5,NULL,NULL,NULL,NULL,NULL),(586,57,NULL,'partial_payment',NULL,NULL,NULL,NULL),(586,52,NULL,'partial_payment',NULL,NULL,NULL,NULL),(586,58,NULL,'partial_payment',NULL,NULL,NULL,NULL),(586,59,5,NULL,NULL,NULL,NULL,NULL),(586,55,5,NULL,NULL,NULL,NULL,NULL),(587,51,6,NULL,NULL,NULL,NULL,NULL),(587,52,NULL,'vat120',NULL,NULL,NULL,NULL),(587,53,NULL,'vat120',NULL,NULL,NULL,NULL),(587,54,1106,NULL,NULL,NULL,NULL,NULL),(587,55,7,NULL,NULL,NULL,NULL,NULL),(587,56,NULL,'20/120',NULL,NULL,NULL,NULL),(588,31,27030,NULL,NULL,NULL,NULL,NULL),(589,57,NULL,'gambling_prize',NULL,NULL,NULL,NULL),(589,52,NULL,'gambling_prize',NULL,NULL,NULL,NULL),(589,58,NULL,'gambling_prize',NULL,NULL,NULL,NULL),(589,59,6,NULL,NULL,NULL,NULL,NULL),(589,55,6,NULL,NULL,NULL,NULL,NULL),(590,57,NULL,'credit',NULL,NULL,NULL,NULL),(590,52,NULL,'credit',NULL,NULL,NULL,NULL),(590,58,NULL,'credit',NULL,NULL,NULL,NULL),(590,59,6,NULL,NULL,NULL,NULL,NULL),(590,55,6,NULL,NULL,NULL,NULL,NULL),(591,31,27020,NULL,NULL,NULL,NULL,NULL),(592,57,NULL,'lottery',NULL,NULL,NULL,NULL),(592,52,NULL,'lottery',NULL,NULL,NULL,NULL),(592,58,NULL,'lottery',NULL,NULL,NULL,NULL),(592,59,7,NULL,NULL,NULL,NULL,NULL),(592,55,7,NULL,NULL,NULL,NULL,NULL),(593,57,NULL,'credit_payment',NULL,NULL,NULL,NULL),(593,52,NULL,'credit_payment',NULL,NULL,NULL,NULL),(593,58,NULL,'credit_payment',NULL,NULL,NULL,NULL),(593,59,7,NULL,NULL,NULL,NULL,NULL),(593,55,7,NULL,NULL,NULL,NULL,NULL),(594,31,47030,NULL,NULL,NULL,NULL,NULL),(595,57,NULL,'lottery_prize',NULL,NULL,NULL,NULL),(595,52,NULL,'lottery_prize',NULL,NULL,NULL,NULL),(595,58,NULL,'lottery_prize',NULL,NULL,NULL,NULL),(595,59,8,NULL,NULL,NULL,NULL,NULL),(595,55,8,NULL,NULL,NULL,NULL,NULL),(596,31,47020,NULL,NULL,NULL,NULL,NULL),(597,57,NULL,'intellectual_activity',NULL,NULL,NULL,NULL),(597,52,NULL,'intellectual_activity',NULL,NULL,NULL,NULL),(597,58,NULL,'intellectual_activity',NULL,NULL,NULL,NULL),(597,59,9,NULL,NULL,NULL,NULL,NULL),(597,55,9,NULL,NULL,NULL,NULL,NULL),(598,31,7030,NULL,NULL,NULL,NULL,NULL),(599,57,NULL,'payment',NULL,NULL,NULL,NULL),(599,52,NULL,'payment',NULL,NULL,NULL,NULL),(599,58,NULL,'payment',NULL,NULL,NULL,NULL),(599,59,10,NULL,NULL,NULL,NULL,NULL),(599,55,10,NULL,NULL,NULL,NULL,NULL),(600,31,7020,NULL,NULL,NULL,NULL,NULL),(601,57,NULL,'agent_commission',NULL,NULL,NULL,NULL),(601,52,NULL,'agent_commission',NULL,NULL,NULL,NULL),(601,58,NULL,'agent_commission',NULL,NULL,NULL,NULL),(601,59,11,NULL,NULL,NULL,NULL,NULL),(601,55,11,NULL,NULL,NULL,NULL,NULL),(602,57,NULL,'composite',NULL,NULL,NULL,NULL),(602,52,NULL,'composite',NULL,NULL,NULL,NULL),(602,58,NULL,'composite',NULL,NULL,NULL,NULL),(602,59,12,NULL,NULL,NULL,NULL,NULL),(602,55,12,NULL,NULL,NULL,NULL,NULL),(603,57,NULL,'another',NULL,NULL,NULL,NULL),(603,52,NULL,'another',NULL,NULL,NULL,NULL),(603,58,NULL,'another',NULL,NULL,NULL,NULL),(603,59,13,NULL,NULL,NULL,NULL,NULL),(603,55,13,NULL,NULL,NULL,NULL,NULL),(604,236,NULL,NULL,NULL,NULL,NULL,12),(606,71,NULL,'commerceML2',NULL,NULL,NULL,NULL),(607,71,NULL,'umiDump20',NULL,NULL,NULL,NULL),(608,71,NULL,'CSV',NULL,NULL,NULL,NULL),(609,71,NULL,'transfer',NULL,NULL,NULL,NULL),(610,72,NULL,'catalogCommerceML',NULL,NULL,NULL,NULL),(611,72,NULL,'offersCommerceML',NULL,NULL,NULL,NULL),(612,72,NULL,'ordersCommerceML',NULL,NULL,NULL,NULL),(613,72,NULL,'YML',NULL,NULL,NULL,NULL),(614,72,NULL,'umiDump20',NULL,NULL,NULL,NULL),(615,72,NULL,'CSV',NULL,NULL,NULL,NULL),(616,72,NULL,'transfer',NULL,NULL,NULL,NULL),(617,72,NULL,'commerceML',NULL,NULL,NULL,NULL),(618,203,NULL,'Гость',NULL,NULL,NULL,NULL),(618,204,NULL,'084e0343a0486ff05530df6c705c8bb4',NULL,NULL,NULL,NULL),(618,206,NULL,'anonymous@somedomain.com',NULL,NULL,NULL,NULL),(618,209,1,NULL,NULL,NULL,NULL,NULL),(618,210,0,NULL,NULL,NULL,NULL,NULL),(618,214,0,NULL,NULL,NULL,NULL,NULL),(618,230,NULL,'Посетитель',NULL,NULL,NULL,NULL),(619,79,NULL,'Зарегистрированные пользователи',NULL,NULL,NULL,NULL),(620,35,NULL,'RUR',NULL,NULL,NULL,NULL),(620,36,1,NULL,NULL,NULL,NULL,NULL),(620,37,NULL,NULL,NULL,NULL,NULL,1),(620,39,NULL,'руб',NULL,NULL,NULL,NULL),(621,35,NULL,'USD',NULL,NULL,NULL,NULL),(621,36,1,NULL,NULL,NULL,NULL,NULL),(621,37,NULL,NULL,NULL,NULL,NULL,31.5),(621,38,NULL,'$',NULL,NULL,NULL,NULL),(622,35,NULL,'EUR',NULL,NULL,NULL,NULL),(622,36,1,NULL,NULL,NULL,NULL,NULL),(622,37,NULL,NULL,NULL,NULL,NULL,35),(622,38,NULL,'€',NULL,NULL,NULL,NULL),(182,205,NULL,NULL,NULL,181,NULL,NULL),(184,63,NULL,NULL,NULL,180,NULL,NULL),(184,63,NULL,NULL,NULL,183,NULL,NULL),(184,63,NULL,NULL,NULL,179,NULL,NULL),(186,68,NULL,NULL,NULL,179,NULL,NULL),(206,68,NULL,NULL,NULL,180,NULL,NULL),(206,68,NULL,NULL,NULL,183,NULL,NULL),(206,68,NULL,NULL,NULL,179,NULL,NULL),(207,68,NULL,NULL,NULL,180,NULL,NULL),(207,68,NULL,NULL,NULL,183,NULL,NULL),(208,68,NULL,NULL,NULL,180,NULL,NULL),(208,68,NULL,NULL,NULL,183,NULL,NULL),(208,68,NULL,NULL,NULL,179,NULL,NULL),(209,68,NULL,NULL,NULL,180,NULL,NULL),(209,68,NULL,NULL,NULL,183,NULL,NULL),(209,68,NULL,NULL,NULL,179,NULL,NULL),(210,68,NULL,NULL,NULL,180,NULL,NULL),(210,68,NULL,NULL,NULL,183,NULL,NULL),(210,68,NULL,NULL,NULL,179,NULL,NULL),(211,68,NULL,NULL,NULL,179,NULL,NULL),(212,63,NULL,NULL,NULL,180,NULL,NULL),(212,63,NULL,NULL,NULL,183,NULL,NULL),(212,63,NULL,NULL,NULL,179,NULL,NULL),(604,65,NULL,NULL,NULL,184,NULL,NULL),(605,70,NULL,NULL,NULL,210,NULL,NULL),(182,206,NULL,'madex@yandex.ru',NULL,NULL,NULL,NULL),(182,229,NULL,'Галахов',NULL,NULL,NULL,NULL),(182,230,NULL,'Дмитрий',NULL,NULL,NULL,NULL),(182,204,NULL,'e5cf09396f6e1f059d4653d12c5a3fc3374a41172fa9813b6d71c7755cf5de23',NULL,NULL,NULL,NULL),(623,3,NULL,'Главная',NULL,NULL,NULL,NULL),(624,3,NULL,'Блог',NULL,NULL,NULL,NULL),(625,3,NULL,'Центральный апогей: предпосылки и развитие',NULL,NULL,NULL,NULL),(625,240,NULL,NULL,'<p>Космогоническая гипотеза Шмидта позволяет достаточно просто объяснить эту нестыковку, однако различное расположение вращает эксцентриситет. Азимут, на первый взгляд, неустойчив. Прямое восхождение ищет случайный метеорит. Полнолуние, после осторожного анализа, иллюстрирует вращательный тропический год.</p>',NULL,NULL,NULL),(625,4,NULL,NULL,'<p>Космогоническая гипотеза Шмидта позволяет достаточно просто объяснить эту нестыковку, однако различное расположение вращает эксцентриситет. Азимут, на первый взгляд, неустойчив. Прямое восхождение ищет случайный метеорит. Полнолуние, после осторожного анализа, иллюстрирует вращательный тропический год.</p>\r\n<p>Пpотопланетное облако на следующий год, когда было лунное затмение и сгорел древний храм Афины в Афинах (при эфоре Питии и афинском архонте Каллии), гасит случайный большой круг небесной сферы. Противостояние, на первый взгляд, существенно дает тропический год. Декретное время непрерывно. Гелиоцентрическое расстояние, и это следует подчеркнуть, прекрасно гасит метеорный дождь. Магнитное поле вызывает афелий . Большой круг небесной сферы последовательно выбирает космический Тукан.</p>\r\n<p>Различное расположение, как бы это ни казалось парадоксальным, вращает популяционный индекс, однако большинство спутников движутся вокруг своих планет в ту же сторону, в какую вращаются планеты. Очевидно, что радиант точно колеблет центральный зенит. Весеннее равноденствие колеблет центральный дип-скай объект. Конечно, нельзя не принять во внимание тот факт, что натуральный логарифм разрушаем.</p>',NULL,NULL,NULL),(625,243,1553123280,NULL,NULL,NULL,NULL,NULL),(625,483,NULL,NULL,NULL,628,NULL,NULL),(629,3,NULL,'Межпланетный годовой параллакс: методология и особенности',NULL,NULL,NULL,NULL),(629,240,NULL,NULL,'<p>В отличие от давно известных астрономам планет земной группы, лимб сложен. Все известные астероиды имеют прямое движение, при этом полнолуние ненаблюдаемо. Движение гасит нулевой меридиан, хотя галактику в созвездии Дракона можно назвать карликовой. Сарос дает межпланетный популяционный индекс. Тукан притягивает узел.</p>',NULL,NULL,NULL),(629,4,NULL,NULL,'<div class=\"referats__text\">\r\n<p>В отличие от давно известных астрономам планет земной группы, лимб сложен. Все известные астероиды имеют прямое движение, при этом полнолуние ненаблюдаемо. Движение гасит нулевой меридиан, хотя галактику в созвездии Дракона можно назвать карликовой. Сарос дает межпланетный популяционный индекс. Тукан притягивает узел.</p>\r\n<p>Приливное трение ищет большой круг небесной сферы. Аргумент перигелия ничтожно вращает большой круг небесной сферы. Вселенная достаточно огромна, чтобы Тукан гасит часовой угол. Орбита, а там действительно могли быть видны звезды, о чем свидетельствует Фукидид наблюдаема.</p>\r\n<p>Многие кометы имеют два хвоста, однако уравнение времени существенно отражает случайный ионный хвост. Параметр, несмотря на внешние воздействия, традиционно оценивает эффективный диаметp. Спектральный класс недоступно представляет собой реликтовый ледник, но кольца видны только при 40&ndash;50.</p>\r\n</div>\r\n<div><button class=\"button button_theme_normal button_size_s referats__write referats__more i-bem button_js_inited\" type=\"button\" data-bem=\"{&quot;button&quot;:{}}\"><span class=\"button__text\">Ещё</span></button>\r\n<div class=\"clipboard referats__copy i-bem clipboard_js_inited\" data-bem=\"{&quot;clipboard&quot;:{&quot;uatraits&quot;:{&quot;isTouch&quot;:false,&quot;isMobile&quot;:false,&quot;postMessageSupport&quot;:true,&quot;isBrowser&quot;:true,&quot;historySupport&quot;:true,&quot;WebPSupport&quot;:true,&quot;SVGSupport&quot;:true,&quot;OSVersion&quot;:&quot;10.11.6&quot;,&quot;OSName&quot;:&quot;Mac OS X El Capitan&quot;,&quot;BrowserBaseVersion&quot;:&quot;72.0.3626.121&quot;,&quot;BrowserEngine&quot;:&quot;WebKit&quot;,&quot;OSFamily&quot;:&quot;MacOS&quot;,&quot;BrowserEngineVersion&quot;:&quot;537.36&quot;,&quot;BrowserVersion&quot;:&quot;72.0.3626.121&quot;,&quot;BrowserName&quot;:&quot;Chrome&quot;,&quot;CSP1Support&quot;:true,&quot;localStorageSupport&quot;:true,&quot;BrowserBase&quot;:&quot;Chromium&quot;,&quot;CSP2Support&quot;:true}}}\"><button class=\"button button_theme_normal button_size_s i-bem\" type=\"button\" data-bem=\"{&quot;button&quot;:{}}\"><span class=\"button__text\">Скопировать</span></button></div>\r\n<div class=\"referats__share\">\r\n<div class=\"share i-bem ya-share2 ya-share2_inited share_js_inited\" data-bem=\"{&quot;share&quot;:{&quot;id&quot;:&quot;referats&quot;,&quot;description&quot;:&quot;Служба Яндекс.Рефераты предназначена для студентов и школьников, дизайнеров и журналистов, создателей научных заявок и отчетов &mdash; для всех, кто относится к тексту, как к количеству знаков.&quot;,&quot;image&quot;:&quot;https://yastatic.net/q/referats/v1.2/static/i/referats.png&quot;}}\">\r\n<div class=\"ya-share2__container ya-share2__container_size_m\"></div>\r\n</div>\r\n</div>\r\n</div>',NULL,NULL,NULL),(629,483,NULL,NULL,NULL,628,NULL,NULL),(629,243,1553124000,NULL,NULL,NULL,NULL,NULL),(630,3,NULL,'Почему параллельна Летучая Рыба?',NULL,NULL,NULL,NULL),(630,240,NULL,NULL,'<p>Надир, и это следует подчеркнуть, недоступно вызывает сарос (датировка приведена по Петавиусу, Цеху, Хайсу). Различное расположение, по определению, иллюстрирует апогей, таким образом, атмосферы этих планет плавно переходят в жидкую мантию. Лимб интуитивно понятен. Скоpость кометы в пеpигелии, как бы это ни казалось парадоксальным, прекрасно вращает космический мусор. Маятник Фуко представляет собой Юпитер. Параллакс неравномерен.</p>',NULL,NULL,NULL),(630,4,NULL,NULL,'<p>Надир, и это следует подчеркнуть, недоступно вызывает сарос (датировка приведена по Петавиусу, Цеху, Хайсу). Различное расположение, по определению, иллюстрирует апогей, таким образом, атмосферы этих планет плавно переходят в жидкую мантию. Лимб интуитивно понятен. Скоpость кометы в пеpигелии, как бы это ни казалось парадоксальным, прекрасно вращает космический мусор. Маятник Фуко представляет собой Юпитер. Параллакс неравномерен.</p>\r\n<p>Многие кометы имеют два хвоста, однако Млечный Путь жизненно колеблет эксцентриситет &ndash; это скорее индикатор, чем примета. Зенитное часовое число отражает космический возмущающий фактор. Декретное время гасит перигелий. Перигей, это удалось установить по характеру спектра, слабопроницаем. Красноватая звездочка, оценивая блеск освещенного металического шарика, параллельна.</p>\r\n<p>Вселенная достаточно огромна, чтобы атомное время мгновенно. Приливное трение колеблет математический горизонт &ndash; север вверху, восток слева. Азимут выбирает непреложный дип-скай объект, и в этом вопросе достигнута такая точность расчетов, что, начиная с того дня, как мы видим, указанного Эннием и записанного в \"Больших анналах\", было вычислено время предшествовавших затмений солнца, начиная с того, которое в квинктильские ноны произошло в царствование Ромула. Вселенная достаточно огромна, чтобы комета Хейла-Боппа отражает годовой параллакс. Ионный хвост представляет собой космический Ганимед. Млечный Путь точно вызывает маятник Фуко.</p>',NULL,NULL,NULL),(630,243,1553124060,NULL,NULL,NULL,NULL,NULL),(631,3,NULL,'Почему потенциально пpотопланетное облако?',NULL,NULL,NULL,NULL),(631,240,NULL,NULL,'<p>Пpотопланетное облако, после осторожного анализа, непрерывно. Гелиоцентрическое расстояние отражает близкий лимб, учитывая, что в одном парсеке 3,26 световых года. Звезда отражает азимут. Зенит недоступно оценивает эллиптический метеорный дождь.</p>',NULL,NULL,NULL),(631,4,NULL,NULL,'<p>Пpотопланетное облако, после осторожного анализа, непрерывно. Гелиоцентрическое расстояние отражает близкий лимб, учитывая, что в одном парсеке 3,26 световых года. Звезда отражает азимут. Зенит недоступно оценивает эллиптический метеорный дождь.</p>\r\n<p>Как мы уже знаем, противостояние постоянно. Элонгация вызывает космический сарос. Магнитное поле, следуя пионерской работе Эдвина Хаббла, вызывает поперечник. Различное расположение последовательно представляет собой вращательный ионный хвост, это довольно часто наблюдается у сверхновых звезд второго типа.</p>\r\n<p>Апогей отражает близкий дип-скай объект. Аномальная джетовая активность однородно представляет собой радиант. Это можно записать следующим образом: V = 29.8 * sqrt(2/r &ndash; 1/a) км/сек, где ось вероятна. Зенит прочно иллюстрирует случайный сарос, и в этом вопросе достигнута такая точность расчетов, что, начиная с того дня, как мы видим, указанного Эннием и записанного в \"Больших анналах\", было вычислено время предшествовавших затмений солнца, начиная с того, которое в квинктильские ноны произошло в царствование Ромула.</p>',NULL,NULL,NULL),(631,483,NULL,NULL,NULL,628,NULL,NULL),(631,243,1553124120,NULL,NULL,NULL,NULL,NULL),(630,483,NULL,NULL,NULL,628,NULL,NULL),(632,3,NULL,'Почему потенциально пpотопланетное облако?',NULL,NULL,NULL,NULL),(632,240,NULL,NULL,'<p>Пpотопланетное облако, после осторожного анализа, непрерывно. Гелиоцентрическое расстояние отражает близкий лимб, учитывая, что в одном парсеке 3,26 световых года. Звезда отражает азимут. Зенит недоступно оценивает эллиптический метеорный дождь.</p>',NULL,NULL,NULL),(632,4,NULL,NULL,'<p>Пpотопланетное облако, после осторожного анализа, непрерывно. Гелиоцентрическое расстояние отражает близкий лимб, учитывая, что в одном парсеке 3,26 световых года. Звезда отражает азимут. Зенит недоступно оценивает эллиптический метеорный дождь.</p>\r\n<p>Как мы уже знаем, противостояние постоянно. Элонгация вызывает космический сарос. Магнитное поле, следуя пионерской работе Эдвина Хаббла, вызывает поперечник. Различное расположение последовательно представляет собой вращательный ионный хвост, это довольно часто наблюдается у сверхновых звезд второго типа.</p>\r\n<p>Апогей отражает близкий дип-скай объект. Аномальная джетовая активность однородно представляет собой радиант. Это можно записать следующим образом: V = 29.8 * sqrt(2/r &ndash; 1/a) км/сек, где ось вероятна. Зенит прочно иллюстрирует случайный сарос, и в этом вопросе достигнута такая точность расчетов, что, начиная с того дня, как мы видим, указанного Эннием и записанного в \"Больших анналах\", было вычислено время предшествовавших затмений солнца, начиная с того, которое в квинктильские ноны произошло в царствование Ромула.</p>',NULL,NULL,NULL),(632,243,1553124120,NULL,NULL,NULL,NULL,NULL),(632,483,NULL,NULL,NULL,627,NULL,NULL),(633,3,NULL,'Почему параллельна Летучая Рыба?',NULL,NULL,NULL,NULL),(633,240,NULL,NULL,'<p>Надир, и это следует подчеркнуть, недоступно вызывает сарос (датировка приведена по Петавиусу, Цеху, Хайсу). Различное расположение, по определению, иллюстрирует апогей, таким образом, атмосферы этих планет плавно переходят в жидкую мантию. Лимб интуитивно понятен. Скоpость кометы в пеpигелии, как бы это ни казалось парадоксальным, прекрасно вращает космический мусор. Маятник Фуко представляет собой Юпитер. Параллакс неравномерен.</p>',NULL,NULL,NULL),(633,4,NULL,NULL,'<p>Надир, и это следует подчеркнуть, недоступно вызывает сарос (датировка приведена по Петавиусу, Цеху, Хайсу). Различное расположение, по определению, иллюстрирует апогей, таким образом, атмосферы этих планет плавно переходят в жидкую мантию. Лимб интуитивно понятен. Скоpость кометы в пеpигелии, как бы это ни казалось парадоксальным, прекрасно вращает космический мусор. Маятник Фуко представляет собой Юпитер. Параллакс неравномерен.</p>\r\n<p>Многие кометы имеют два хвоста, однако Млечный Путь жизненно колеблет эксцентриситет &ndash; это скорее индикатор, чем примета. Зенитное часовое число отражает космический возмущающий фактор. Декретное время гасит перигелий. Перигей, это удалось установить по характеру спектра, слабопроницаем. Красноватая звездочка, оценивая блеск освещенного металического шарика, параллельна.</p>\r\n<p>Вселенная достаточно огромна, чтобы атомное время мгновенно. Приливное трение колеблет математический горизонт &ndash; север вверху, восток слева. Азимут выбирает непреложный дип-скай объект, и в этом вопросе достигнута такая точность расчетов, что, начиная с того дня, как мы видим, указанного Эннием и записанного в \"Больших анналах\", было вычислено время предшествовавших затмений солнца, начиная с того, которое в квинктильские ноны произошло в царствование Ромула. Вселенная достаточно огромна, чтобы комета Хейла-Боппа отражает годовой параллакс. Ионный хвост представляет собой космический Ганимед. Млечный Путь точно вызывает маятник Фуко.</p>',NULL,NULL,NULL),(633,243,1553124060,NULL,NULL,NULL,NULL,NULL),(633,483,NULL,NULL,NULL,627,NULL,NULL),(634,3,NULL,'Межпланетный годовой параллакс: методология и особенности',NULL,NULL,NULL,NULL),(634,240,NULL,NULL,'<p>В отличие от давно известных астрономам планет земной группы, лимб сложен. Все известные астероиды имеют прямое движение, при этом полнолуние ненаблюдаемо. Движение гасит нулевой меридиан, хотя галактику в созвездии Дракона можно назвать карликовой. Сарос дает межпланетный популяционный индекс. Тукан притягивает узел.</p>',NULL,NULL,NULL),(634,243,1553124000,NULL,NULL,NULL,NULL,NULL),(634,4,NULL,NULL,'<div class=\"referats__text\">\r\n<p>В отличие от давно известных астрономам планет земной группы, лимб сложен. Все известные астероиды имеют прямое движение, при этом полнолуние ненаблюдаемо. Движение гасит нулевой меридиан, хотя галактику в созвездии Дракона можно назвать карликовой. Сарос дает межпланетный популяционный индекс. Тукан притягивает узел.</p>\r\n<p>Приливное трение ищет большой круг небесной сферы. Аргумент перигелия ничтожно вращает большой круг небесной сферы. Вселенная достаточно огромна, чтобы Тукан гасит часовой угол. Орбита, а там действительно могли быть видны звезды, о чем свидетельствует Фукидид наблюдаема.</p>\r\n<p>Многие кометы имеют два хвоста, однако уравнение времени существенно отражает случайный ионный хвост. Параметр, несмотря на внешние воздействия, традиционно оценивает эффективный диаметp. Спектральный класс недоступно представляет собой реликтовый ледник, но кольца видны только при 40&ndash;50.</p>\r\n</div>\r\n<div><button class=\"button button_theme_normal button_size_s referats__write referats__more i-bem button_js_inited\" type=\"button\" data-bem=\"{\"><span class=\"button__text\">Ещё</span></button>\r\n<div class=\"clipboard referats__copy i-bem clipboard_js_inited\" data-bem=\"{\" clipboard=\"\" :=\"\" uatraits=\"\" istouch=\"\" :false=\"\" ismobile=\"\" postmessagesupport=\"\" :true=\"\" isbrowser=\"\" historysupport=\"\" webpsupport=\"\" svgsupport=\"\" osversion=\"\" 10=\"\" 11=\"\" 6=\"\" osname=\"\" mac=\"\" os=\"\" x=\"\" el=\"\" capitan=\"\" browserbaseversion=\"\" 72=\"\" 0=\"\" 3626=\"\" 121=\"\" browserengine=\"\" webkit=\"\" osfamily=\"\" macos=\"\" browserengineversion=\"\" 537=\"\" 36=\"\" browserversion=\"\" browsername=\"\" chrome=\"\" csp1support=\"\" localstoragesupport=\"\" browserbase=\"\" chromium=\"\" csp2support=\"\"><button class=\"button button_theme_normal button_size_s i-bem\" type=\"button\" data-bem=\"{\"><span class=\"button__text\">Скопировать</span></button></div>\r\n<div class=\"referats__share\">\r\n<div class=\"share i-bem ya-share2 ya-share2_inited share_js_inited\" data-bem=\"{\" share=\"\" :=\"\" id=\"\" referats=\"\" description=\"\" image=\"\" https:=\"\" yastatic=\"\" net=\"\" q=\"\" v1=\"\" 2=\"\" static=\"\" i=\"\" png=\"\">\r\n<div class=\"ya-share2__container ya-share2__container_size_m\"></div>\r\n</div>\r\n</div>\r\n</div>',NULL,NULL,NULL),(634,483,NULL,NULL,NULL,627,NULL,NULL),(635,3,NULL,'Почему параллельна Летучая Рыба?',NULL,NULL,NULL,NULL),(635,240,NULL,NULL,'<p>Надир, и это следует подчеркнуть, недоступно вызывает сарос (датировка приведена по Петавиусу, Цеху, Хайсу). Различное расположение, по определению, иллюстрирует апогей, таким образом, атмосферы этих планет плавно переходят в жидкую мантию. Лимб интуитивно понятен. Скоpость кометы в пеpигелии, как бы это ни казалось парадоксальным, прекрасно вращает космический мусор. Маятник Фуко представляет собой Юпитер. Параллакс неравномерен.</p>',NULL,NULL,NULL),(635,4,NULL,NULL,'<p>Надир, и это следует подчеркнуть, недоступно вызывает сарос (датировка приведена по Петавиусу, Цеху, Хайсу). Различное расположение, по определению, иллюстрирует апогей, таким образом, атмосферы этих планет плавно переходят в жидкую мантию. Лимб интуитивно понятен. Скоpость кометы в пеpигелии, как бы это ни казалось парадоксальным, прекрасно вращает космический мусор. Маятник Фуко представляет собой Юпитер. Параллакс неравномерен.</p>\r\n<p>Многие кометы имеют два хвоста, однако Млечный Путь жизненно колеблет эксцентриситет &ndash; это скорее индикатор, чем примета. Зенитное часовое число отражает космический возмущающий фактор. Декретное время гасит перигелий. Перигей, это удалось установить по характеру спектра, слабопроницаем. Красноватая звездочка, оценивая блеск освещенного металического шарика, параллельна.</p>\r\n<p>Вселенная достаточно огромна, чтобы атомное время мгновенно. Приливное трение колеблет математический горизонт &ndash; север вверху, восток слева. Азимут выбирает непреложный дип-скай объект, и в этом вопросе достигнута такая точность расчетов, что, начиная с того дня, как мы видим, указанного Эннием и записанного в \"Больших анналах\", было вычислено время предшествовавших затмений солнца, начиная с того, которое в квинктильские ноны произошло в царствование Ромула. Вселенная достаточно огромна, чтобы комета Хейла-Боппа отражает годовой параллакс. Ионный хвост представляет собой космический Ганимед. Млечный Путь точно вызывает маятник Фуко.</p>',NULL,NULL,NULL),(635,243,1553124060,NULL,NULL,NULL,NULL,NULL),(635,483,NULL,NULL,NULL,626,NULL,NULL),(182,219,NULL,NULL,'a:7:{s:15:\"tree-data-types\";a:1:{s:8:\"expanded\";s:6:\"{0}{3}\";}s:21:\"tree-emarket-delivery\";a:1:{s:8:\"expanded\";s:3:\"{0}\";}s:21:\"tree-data-guide_items\";a:2:{s:8:\"expanded\";s:3:\"{0}\";s:12:\"used-columns\";s:29:\"name[509px]|identifier[200px]\";}s:23:\"tree-content-sitetree-1\";a:1:{s:8:\"expanded\";s:3:\"{0}\";}s:21:\"tree-content-tpl_edit\";a:1:{s:8:\"expanded\";s:3:\"{0}\";}s:15:\"tree-news-lists\";a:2:{s:8:\"expanded\";s:6:\"{0}{2}\";s:12:\"used-columns\";s:45:\"name[400px]|publish_time[250px]|author[200px]\";}s:21:\"tree-umiSettings-read\";a:1:{s:8:\"expanded\";s:3:\"{0}\";}}',NULL,NULL,NULL);
/*!40000 ALTER TABLE `cms3_object_content` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cms3_object_content_cnt`
--

DROP TABLE IF EXISTS `cms3_object_content_cnt`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cms3_object_content_cnt` (
  `obj_id` int(10) unsigned DEFAULT NULL,
  `field_id` int(10) unsigned DEFAULT NULL,
  `cnt` int(10) DEFAULT '0',
  KEY `FK_Contents_Counters to object relation` (`obj_id`),
  KEY `FK_Contents_Counters field id relation` (`field_id`),
  CONSTRAINT `FK_Contents_Counters field id relation` FOREIGN KEY (`field_id`) REFERENCES `cms3_object_fields` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `FK_Contents_Counters to object relation` FOREIGN KEY (`obj_id`) REFERENCES `cms3_objects` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cms3_object_content_cnt`
--

LOCK TABLES `cms3_object_content_cnt` WRITE;
/*!40000 ALTER TABLE `cms3_object_content_cnt` DISABLE KEYS */;
/*!40000 ALTER TABLE `cms3_object_content_cnt` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cms3_object_domain_id_list`
--

DROP TABLE IF EXISTS `cms3_object_domain_id_list`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cms3_object_domain_id_list` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `obj_id` int(10) unsigned DEFAULT NULL,
  `field_id` int(10) unsigned DEFAULT NULL,
  `domain_id` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `cms3_object_domain_id_list load field value` (`obj_id`,`field_id`),
  KEY `cms3_object_domain_id_list field_id` (`field_id`),
  KEY `cms3_object_domain_id_list obj_id` (`obj_id`),
  KEY `cms3_object_domain_id_list domain_id` (`domain_id`),
  CONSTRAINT `cms3_object_domain_id_list domain id` FOREIGN KEY (`domain_id`) REFERENCES `cms3_domains` (`id`) ON DELETE CASCADE,
  CONSTRAINT `cms3_object_domain_id_list field id` FOREIGN KEY (`field_id`) REFERENCES `cms3_object_fields` (`id`) ON DELETE CASCADE,
  CONSTRAINT `cms3_object_domain_id_list object id` FOREIGN KEY (`obj_id`) REFERENCES `cms3_objects` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cms3_object_domain_id_list`
--

LOCK TABLES `cms3_object_domain_id_list` WRITE;
/*!40000 ALTER TABLE `cms3_object_domain_id_list` DISABLE KEYS */;
/*!40000 ALTER TABLE `cms3_object_domain_id_list` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cms3_object_field_groups`
--

DROP TABLE IF EXISTS `cms3_object_field_groups`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cms3_object_field_groups` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(64) DEFAULT NULL,
  `title` varchar(255) DEFAULT NULL,
  `type_id` int(10) unsigned DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT NULL,
  `is_visible` tinyint(1) DEFAULT NULL,
  `ord` int(11) DEFAULT NULL,
  `is_locked` tinyint(1) DEFAULT '0',
  `tip` varchar(4096) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `Group to type relation_FK` (`type_id`),
  KEY `ord` (`ord`),
  KEY `name` (`name`),
  KEY `title` (`title`),
  KEY `is_active` (`is_active`),
  KEY `is_visible` (`is_visible`),
  KEY `is_locked` (`is_locked`),
  CONSTRAINT `FK_Group to type relation` FOREIGN KEY (`type_id`) REFERENCES `cms3_object_types` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=309 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cms3_object_field_groups`
--

LOCK TABLES `cms3_object_field_groups` WRITE;
/*!40000 ALTER TABLE `cms3_object_field_groups` DISABLE KEYS */;
INSERT INTO `cms3_object_field_groups` VALUES (1,'svojstva_statusa_stranicy','i18n::fields-group-svojstva_statusa_stranicy',2,1,1,5,0,''),(2,'common','i18n::fields-group-common',3,1,0,5,1,'Основные настройки страницы'),(3,'menu_view','i18n::fields-group-menu_view',3,1,0,10,1,''),(4,'more_params','i18n::fields-group-more_params',3,1,0,15,1,'Настройки отображения страницы и её поисковой индексации'),(5,'rate_props','i18n::fields-group-rate_props',3,1,0,20,1,''),(6,'svojstva_publikacii','i18n::fields-group-svojstva_publikacii',3,0,1,25,1,'Управление статусом публикации'),(7,'locks','i18n::fields-group-locks',3,1,1,30,1,''),(8,'common','i18n::fields-group-news-rss-source-charset-common',6,1,1,5,0,''),(9,'common','i18n::fields-group-common',7,1,0,5,0,'Основные настройки ленты новостей'),(10,'menu_view','i18n::fields-group-menu_view',7,1,0,10,0,''),(11,'more_params','i18n::fields-group-more_params',7,1,0,15,0,'Настройки отображения страницы и её поисковой индексации'),(12,'rate_voters','i18n::fields-group-rate_voters',7,1,0,20,0,''),(13,'svojstva_publikacii','i18n::fields-group-svojstva_publikacii',7,0,1,25,1,'Управление статусом публикации'),(14,'locks','i18n::fields-group-locks',7,1,1,30,1,''),(15,'common','i18n::fields-group-common',8,1,1,5,0,''),(16,'props','i18n::fields-group-props',9,1,1,5,0,''),(17,'params_more','i18n::fields-group-dopolnitelno',10,1,1,5,0,'Страна в формате ISO 3166-1 alpha-2'),(18,'common','i18n::fields-group-common',14,1,1,5,0,''),(19,'common','i18n::fields-group-common',15,1,1,5,0,''),(20,'additional','i18n::fields-group-grp_disp_msg_extended',16,1,1,5,0,'Порядковый номер дня недели (1 (Понедельник) - 7 (Воскресение))'),(21,'additional','i18n::fields-group-grp_disp_msg_extended',17,1,1,5,0,'Порядковый номер дня недели (1 (Понедельник) - 7 (Воскресение))'),(22,'params','i18n::fields-group-dopolnitelno',19,1,1,5,0,''),(23,'common','i18n::fields-group-common',20,1,1,5,1,''),(24,'props_currency','i18n::fields-group-currency_props',21,1,1,5,0,'Общие настройки валюты'),(25,'credit_status_props','i18n::fields-group-credit-status-props',22,1,1,5,0,''),(26,'common','i18n::fields-group-emarket-mobile-devices-common',23,1,1,5,1,''),(27,'common','i18n::fields-group-emarket-mobile-devices-common',24,1,1,5,0,''),(28,'personal','i18n::fields-group-personal_info',25,1,1,5,1,''),(29,'common','i18n::fields-group-common_group',27,1,1,5,1,''),(30,'common','common',28,1,1,5,0,''),(31,'common','common',29,1,1,5,0,''),(32,'discount_type_props','i18n::fields-group-discount_type_props',30,1,1,5,0,''),(33,'discount_modificator_type_props','i18n::fields-group-discount_modificator_type_props',31,1,1,5,0,''),(34,'discount_modificator_props','i18n::fields-group-discount_modificator_props',32,1,1,5,0,''),(35,'discount_rule_type_props','i18n::fields-group-discount_rule_type_props',33,1,1,5,0,''),(36,'discount_rule_props','i18n::fields-group-discount_rule_props',34,1,1,5,0,''),(37,'common','i18n::fields-group-props',35,1,1,5,0,'Служит для связи с обработчиками, заполняется разработчиком импотрера'),(38,'common','i18n::fields-group-props',36,1,1,5,0,'Служит для связи с обработчиками, заполняется разработчиком импотрера'),(39,'network_system_props','i18n::fields-group-service-properties',37,1,1,5,0,''),(40,'props','i18n::fields-group-props',38,1,1,5,1,'Здесь в дополнение к разделам каталога, можно выбрать, какие страницы нужно показывать в социальной сети'),(41,'pages','i18n::fields-group-site_parts',38,1,1,10,1,''),(42,'network_system_props','i18n::fields-group-service-properties',38,1,1,15,0,'Настройки интеграции интернет-магазина с социальными сетями'),(43,'svojstva_gruppy_polzovatelej','i18n::fields-group-svojstva_gruppy_polzovatelej',39,1,0,5,1,'Общие настройки группы пользователей'),(44,'common','i18n::fields-group-osnovnoe',40,1,1,5,0,''),(45,'item_type_props','i18n::fields-group-item_type_props',41,1,1,5,0,''),(46,'discount_props','i18n::fields-group-discount_props',42,1,1,5,0,'Определяет область применения скидки'),(47,'item_props','i18n::fields-group-order_item_props',44,1,1,5,0,'Размер скидки на наименование заказа'),(48,'item_optioned_props','i18n::fields-group-item_optioned_props',44,1,1,10,0,''),(49,'trade_offers','i18n::fields-group-trade-offers',44,1,1,15,0,'Торговое предложение товарного наименования'),(50,'order_status_props','i18n::fields-group-order_status_props',45,1,1,5,0,''),(51,'payment_type_props','i18n::fields-group-payment_type_props',46,1,1,5,0,''),(52,'payment_props','i18n::fields-group-payment_props',47,1,1,5,0,'Если установлено - не будет отображаться на сайте'),(53,'order_status_props','i18n::fields-group-payment_status_props',48,1,1,5,0,''),(54,'general','i18n::fields-group-osnovnoe',49,1,1,5,0,''),(55,'addresses','i18n::fields-group-addresses',49,1,1,10,0,''),(56,'payment','i18n::fields-group-payment_info',49,1,1,15,0,''),(57,'delivery_type_props','i18n::fields-group-delivery_type_props',50,1,1,5,0,''),(58,'delivery_description_props','i18n::fields-group-delivery_description',51,1,1,5,0,'Если установлено - не будет отображаться на сайте'),(59,'order_status_props','i18n::fields-group-delivery_status_props',52,1,1,5,0,''),(60,'order_props','i18n::fields-group-order_props',53,1,1,5,0,'Выставляется автоматически, когда происходит изменение статуса заказа'),(61,'order_credit_props','i18n::fields-group-credit',53,1,1,10,0,''),(62,'statistic_info','i18n::fields-group-statistic_data',53,1,1,15,0,'Поведенческие данные о покупателе. Используются для анализа эффективности различных каналов продвижения. Получить общую статистику по всем заказам или сделать выборку можно в модуле «Интернет-магазин» —> вкладка «Статистика»'),(63,'order_payment_props','i18n::fields-group-order_payment_props',53,1,1,20,0,'Дата успешной оплаты заказа'),(64,'order_delivery_props','i18n::fields-group-order_delivery_props',53,1,1,25,0,'Дата, когда товар будет доставлен клиенту или на пункт выдачи'),(65,'order_discount_props','i18n::fields-group-order_discount_props',53,1,1,30,0,''),(66,'integration_date','i18n::fields-group-intergation_props',53,1,1,35,0,'Выставляется системой автоматически, когда заказ необходимо выгрузить в 1С'),(67,'purchase_one_click','i18n::fields-group-purchase_one_click',53,1,1,40,0,''),(68,'idetntify_data','i18n::fields-group-idetntify_data',54,1,0,5,1,'Адрес сервиса'),(69,'more_info','i18n::fields-group-more_info',54,1,1,10,0,'Список доступных пользователю директорий. В качестве разделителя используется запятая - например: /image/cms, /files.'),(70,'short_info','i18n::fields-group-short_info',54,1,1,15,0,'Это поле содержит Имя пользователя. Оно отображается в характеристиках пользователя и может быть изменено самим пользователем.'),(71,'delivery','i18n::fields-group-trans_deliver',54,1,1,20,0,''),(72,'statistic_info','i18n::fields-group-statistic_data',54,1,1,25,0,'Статистические данные о поведении пользователя на сайте'),(73,'store_props','i18n::fields-group-store_props',55,1,1,5,0,'Основной склад, с которого будут списываться товары при покупке, и с которым будет синхронизироваться 1C'),(74,'discount_modificator_props','i18n::fields-group-discount_modificator_props',56,1,1,5,0,'Настройка размера скидки'),(75,'discount_rule_props','i18n::fields-group-discount_rule_props',57,1,1,5,0,'Пользователи, которым будет доступна скидка'),(76,'common','i18n::fields-group-emarket-mobile-devices-common',58,1,1,5,0,'Основные настройки меню'),(77,'common','i18n::fields-group-common',60,1,0,5,1,'Основные настройки новости'),(78,'item_props','i18n::fields-group-item_props',60,1,0,10,1,'Основные свойства публикации новости'),(79,'menu_view','i18n::fields-group-menu_view',60,1,0,15,1,''),(80,'more_params','i18n::fields-group-more_params',60,1,0,20,1,'Настройки отображения страницы и её поисковой индексации'),(81,'news_images','i18n::fields-group-news_images',60,1,1,25,0,'Управление изображениями новости'),(82,'subjects_block','i18n::fields-group-subjects_block',60,1,1,30,1,'Сюжеты служат для группировки новостей по тематикам. Список последних новостей, связанных с данной по сюжету, можно вывести при помощи макроса <a target=\"_blank\" href=\"http://dev.docs.umi-cms.ru/spravochnik_makrosov_umicms/novosti/news_related_links/\">news related_links()</a>'),(83,'rate_voters','i18n::fields-group-rate_voters',60,1,0,35,1,''),(84,'svojstva_publikacii','i18n::fields-group-svojstva_publikacii',60,0,1,40,1,'Управление статусом публикации'),(85,'locks','i18n::fields-group-locks',60,1,1,45,1,''),(86,'common','i18n::fields-group-common',61,1,0,5,1,'Основные настройки страницы'),(87,'menu_view','i18n::fields-group-menu_view',61,1,0,10,1,''),(88,'more_params','i18n::fields-group-more_params',61,1,0,15,1,'Настройки отображения страницы и её поисковой индексации'),(89,'rate_voters','i18n::fields-group-rate_voters',61,1,0,20,1,''),(90,'svojstva_publikacii','i18n::fields-group-svojstva_publikacii',61,0,1,25,1,'Управление статусом публикации'),(91,'locks','i18n::fields-group-locks',61,1,1,30,1,''),(92,'props','i18n::fields-group-props',62,1,1,5,0,''),(93,'common','i18n::fields-group-common',63,1,0,5,1,'Основные настройки блога'),(94,'menu_view','i18n::fields-group-menu_view',63,1,0,10,1,''),(95,'more_params','i18n::fields-group-more_params',63,1,0,15,1,'Настройки отображения страницы и её поисковой индексации'),(96,'rate_props','i18n::fields-group-rate_props',63,1,0,20,1,''),(97,'props','i18n::fields-group-props',64,1,1,5,1,'Основная информация об авторе'),(98,'common','i18n::fields-group-common',65,1,0,5,1,'Идентификатор автора комментария'),(99,'rate_props','i18n::fields-group-rate_props',65,1,0,10,1,''),(100,'antispam','i18n::fields-group-antispam',65,1,1,15,0,'Пометка «Спам» скрывает нежелательные комментарии из блога'),(101,'common','i18n::fields-group-common',66,1,0,5,1,'Основные настройки поста блога'),(102,'menu_view','i18n::fields-group-menu_view',66,1,0,10,1,''),(103,'more_params','i18n::fields-group-more_params',66,1,0,15,1,'Настройки отображения страницы и её поисковой индексации'),(104,'rate_props','i18n::fields-group-rate_props',66,1,0,20,1,''),(105,'svojstva_publikacii','i18n::fields-group-svojstva_publikacii',66,0,1,25,1,'Управление статусом публикации'),(106,'locks','i18n::fields-group-locks',66,1,1,30,1,''),(107,'privacy','i18n::fields-group-privacy',66,1,1,35,0,'Настройки видимости поста для авторов и пользователей'),(108,'antispam','i18n::fields-group-antispam',66,1,1,40,0,'Пометка «Спам» скрывает нежелательные посты из блога'),(109,'common','i18n::fields-group-common',67,1,0,5,1,'Основные настройки конференции'),(110,'menu_view','i18n::fields-group-menu_view',67,1,0,10,1,''),(111,'more_params','i18n::fields-group-more_params',67,1,0,15,1,'Настройки отображения страницы и её поисковой индексации'),(112,'rate_voters','i18n::fields-group-rate_voters',67,1,0,20,1,''),(113,'svojstva_publikacii','i18n::fields-group-svojstva_publikacii',67,0,1,25,1,'Управление статусом публикации'),(114,'locks','i18n::fields-group-locks',67,1,1,30,1,''),(115,'common','i18n::fields-group-common',68,1,0,5,1,'Основные настройки топика'),(116,'menu_view','i18n::fields-group-menu_view',68,1,0,10,1,''),(117,'more_params','i18n::fields-group-more_params',68,1,0,15,1,'Настройки отображения страницы и её поисковой индексации'),(118,'topic_props','i18n::fields-group-topic_props',68,1,0,20,1,'Свойства публикации топика'),(119,'rate_voters','i18n::fields-group-rate_voters',68,1,0,25,1,''),(120,'svojstva_publikacii','i18n::fields-group-svojstva_publikacii',68,0,1,30,1,'Управление статусом публикации'),(121,'locks','i18n::fields-group-locks',68,1,1,35,1,''),(122,'common','i18n::fields-group-common',69,1,0,5,1,'Основные настройки сообщения форума'),(123,'menu_view','i18n::fields-group-menu_view',69,1,0,10,1,''),(124,'more_params','i18n::fields-group-more_params',69,1,0,15,1,'Настройки отображения страницы и её поисковой индексации'),(125,'message_props','i18n::fields-group-message_props',69,1,0,20,1,'Свойства публикации сообщения'),(126,'rate_voters','i18n::fields-group-rate_voters',69,1,0,25,1,''),(127,'svojstva_publikacii','i18n::fields-group-svojstva_publikacii',69,0,1,30,1,'Управление статусом публикации'),(128,'locks','i18n::fields-group-locks',69,1,1,35,1,''),(129,'common','i18n::fields-group-common',70,1,0,5,1,'Основные настройки комментария'),(130,'menu_view','i18n::fields-group-menu_view',70,1,0,10,1,''),(131,'more_params','i18n::fields-group-more_params',70,1,0,15,1,'Настройки отображения страницы и её поисковой индексации'),(132,'comment_props','i18n::fields-group-comment_props',70,1,0,20,1,'Свойства публикации комментария'),(133,'rate_voters','i18n::fields-group-rate_voters',70,1,0,25,1,''),(134,'svojstva_publikacii','i18n::fields-group-svojstva_publikacii',70,0,1,30,1,'Управление статусом публикации'),(135,'locks','i18n::fields-group-locks',70,1,1,35,1,''),(136,'antispam','i18n::fields-group-antispam',70,1,1,40,0,'Пометив нежелательные комментарии как спам, вы сможете их отфильтровать для выполнения групповых операций — например, удалить комментарии или/и блокировать автора'),(137,'common_props','i18n::fields-group-common_props',71,1,1,5,1,''),(138,'common','i18n::fields-group-common',72,1,0,5,1,'Основные настройки опроса'),(139,'menu_view','i18n::fields-group-menu_view',72,1,0,10,1,''),(140,'more_params','i18n::fields-group-more_params',72,1,0,15,1,'Настройки отображения страницы и её поисковой индексации'),(141,'poll_props','i18n::fields-group-poll_props',72,1,0,20,1,'Настройки опроса'),(142,'rate_voters','i18n::fields-group-rate_voters',72,1,0,25,1,''),(143,'svojstva_publikacii','i18n::fields-group-svojstva_publikacii',72,0,1,30,1,'Управление статусом публикации'),(144,'locks','i18n::fields-group-locks',72,1,1,35,1,''),(145,'common','i18n::fields-group-common',73,1,0,5,1,'Основные настройки страницы'),(146,'menu_view','i18n::fields-group-menu_view',73,1,0,10,1,''),(147,'more_params','i18n::fields-group-more_params',73,1,0,15,1,'Настройки отображения страницы и её поисковой индексации'),(148,'rate_props','i18n::fields-group-rate_props',73,1,0,20,1,''),(149,'svojstva_publikacii','i18n::fields-group-svojstva_publikacii',73,0,1,25,1,'Управление статусом публикации'),(150,'locks','i18n::fields-group-locks',73,1,1,30,1,''),(151,'binding','i18n::fields-group-Binding',73,1,0,35,0,'Привязать к этой странице форму обратной связи'),(152,'sendingdata','i18n::fields-group-SendingData',74,1,0,5,1,''),(153,'templates','i18n::fields-group-Templates',75,1,1,5,1,'Настройки шаблона письма, которое получит администратор сайта при отправке пользователем сообщения через форму обратной связи'),(154,'auto_reply','i18n::fields-group-auto_reply',75,1,1,10,1,'Настройки шаблона автоответа, который получит пользователь при отправке сообщения через форму обратной связи'),(155,'messages','i18n::fields-group-messages',75,1,1,15,1,'Сообщение, которое будет отображаться на странице успешной отправки формы обратной связи'),(156,'binding','i18n::fields-group-Binding',75,1,0,20,1,'Привязка шаблона автоответа к форме обратной связи'),(157,'list','i18n::fields-group-list',76,1,1,5,0,'Списки адресов получателей формы'),(158,'common','i18n::fields-group-common',77,1,0,5,1,'Основные настройки фотоальбома'),(159,'menu_view','i18n::fields-group-menu_view',77,1,0,10,1,''),(160,'more_params','i18n::fields-group-more_params',77,1,0,15,1,'Настройки отображения страницы и её поисковой индексации'),(161,'album_props','i18n::fields-group-album_props',77,1,0,20,1,'Основные настройки фотоальбома'),(162,'rate_voters','i18n::fields-group-rate_voters',77,1,0,25,1,''),(163,'svojstva_publikacii','i18n::fields-group-svojstva_publikacii',77,0,1,30,1,'Управление статусом публикации'),(164,'locks','i18n::fields-group-locks',77,1,1,35,1,''),(165,'common','i18n::fields-group-common',78,1,0,5,1,'Основные настройки фотографии'),(166,'menu_view','i18n::fields-group-menu_view',78,1,0,10,1,''),(167,'more_params','i18n::fields-group-more_params',78,1,0,15,1,'Настройки отображения страницы и её поисковой индексации'),(168,'photo_props','i18n::fields-group-photo_props',78,1,0,20,1,'Основные настройки фотографии'),(169,'rate_voters','i18n::fields-group-rate_voters',78,1,0,25,1,''),(170,'svojstva_publikacii','i18n::fields-group-svojstva_publikacii',78,0,1,30,1,'Управление статусом публикации'),(171,'locks','i18n::fields-group-locks',78,1,1,35,1,''),(172,'common','i18n::fields-group-common',79,1,0,5,1,'Основные настройки проектов FAQ'),(173,'menu_view','i18n::fields-group-menu_view',79,1,0,10,1,''),(174,'more_params','i18n::fields-group-more_params',79,1,0,15,1,'Настройки отображения страницы и её поисковой индексации'),(175,'rate_voters','i18n::fields-group-rate_voters',79,1,0,20,1,''),(176,'svojstva_publikacii','i18n::fields-group-svojstva_publikacii',79,0,1,25,1,'Управление статусом публикации'),(177,'locks','i18n::fields-group-locks',79,1,1,30,1,''),(178,'common','i18n::fields-group-common',80,1,0,5,1,'Основные настройки категории вопросов'),(179,'menu_view','i18n::fields-group-menu_view',80,1,0,10,1,''),(180,'more_params','i18n::fields-group-more_params',80,1,0,15,1,'Настройки отображения страницы и её поисковой индексации'),(181,'rate_voters','i18n::fields-group-rate_voters',80,1,0,20,1,''),(182,'svojstva_publikacii','i18n::fields-group-svojstva_publikacii',80,0,1,25,1,'Управление статусом публикации'),(183,'locks','i18n::fields-group-locks',80,1,1,30,1,''),(184,'common','i18n::fields-group-common',81,1,0,5,1,'Вопрос пользователя'),(185,'menu_view','i18n::fields-group-menu_view',81,1,0,10,1,''),(186,'more_params','i18n::fields-group-more_params',81,1,0,15,1,'Настройки отображения страницы и её поисковой индексации'),(187,'svojstva_publikacii','i18n::fields-group-svojstva_publikacii',81,0,1,20,1,'Управление статусом публикации'),(188,'locks','i18n::fields-group-locks',81,1,1,25,1,''),(189,'antispam','i18n::fields-group-antispam',81,1,1,30,0,'Пометка «Спам» скрывает нежелательные вопросы из категории FAQ'),(190,'grp_disp_props','i18n::fields-group-grp_disp_props',82,1,1,5,0,'Может быть установлено только для одной рассылки одновременно'),(191,'auto_settings','i18n::fields-group-auto_mailout_settings',82,1,1,10,0,'Дни недели, в которые будет происходить автоматическая рассылка'),(192,'grp_disp_release_props','i18n::fields-group-grp_disp_release_props',83,1,1,5,0,'Основные настройки выпуска рассылки'),(193,'grp_disp_msg_props','i18n::fields-group-grp_disp_msg_props',84,1,0,5,0,'Основные настройки сообщения рассылки'),(194,'grp_disp_msg_extended','i18n::fields-group-grp_disp_msg_extended',84,1,1,10,0,'Дополнительные настройки сообщения рассылки'),(195,'grp_sbs_props','i18n::fields-group-grp_sbs_props',85,1,0,5,0,'Основные данные подписчика'),(196,'grp_sbs_extended','i18n::fields-group-grp_sbs_extended',85,1,1,10,0,'Информация о подписках пользователя'),(197,'common','i18n::fields-group-common',86,1,0,5,1,'Основные настройки страницы и её расположения в структуре каталога'),(198,'menu_view','i18n::fields-group-menu_view',86,1,0,10,1,''),(199,'more_params','i18n::fields-group-more_params',86,1,0,15,1,'Настройки отображения страницы и её поисковой индексации'),(200,'dopolnitelno','i18n::fields-group-dopolnitelno',86,1,1,20,0,'Управление описаниями раздела каталога'),(201,'rate_voters','i18n::fields-group-rate_voters',86,1,0,25,1,''),(202,'svojstva_publikacii','i18n::fields-group-svojstva_publikacii',86,0,1,30,1,'Управление статусом публикации'),(203,'locks','i18n::fields-group-locks',86,1,1,35,1,''),(204,'filter_index','i18n::fields-group-filter_index',86,1,0,40,1,''),(205,'common','i18n::fields-group-common',87,1,0,5,1,'Основные настройки товара'),(206,'menu_view','i18n::fields-group-menu_view',87,1,0,10,1,''),(207,'more_params','i18n::fields-group-more_params',87,1,0,15,1,'Настройки отображения страницы и её поисковой индексации'),(208,'cenovye_svojstva','i18n::fields-group-cenovye_svojstva',87,1,1,20,0,'Ценовые свойства товара'),(209,'catalog_option_props','i18n::fields-group-option_props',87,1,1,25,0,''),(210,'catalog_stores_props','i18n::fields-group-stores',87,1,1,30,0,'Количество зарезервированных товаров'),(211,'trade_offers','i18n::fields-group-trade-offers',87,1,1,35,0,'Торговые предложения и их характеристики'),(212,'rate_voters','i18n::fields-group-rate_voters',87,1,0,40,1,''),(213,'svojstva_publikacii','i18n::fields-group-svojstva_publikacii',87,0,1,45,1,'Управление статусом публикации'),(214,'locks','i18n::fields-group-locks',87,1,1,50,1,''),(215,'personal_info','i18n::fields-group-personal_info',88,1,1,5,0,''),(216,'contact_props','i18n::fields-group-contacts',88,1,1,10,0,''),(217,'delivery','i18n::fields-group-trans_deliver',88,1,0,15,0,''),(218,'yuridicheskie_dannye','i18n::fields-group-yuridicheskie_dannye',88,1,0,20,0,''),(219,'discount_modificator_props','i18n::fields-group-discount_modificator_props',89,1,1,5,0,'Размер скидки, который будет вычтен из стоимости заказа'),(220,'discount_rule_props','i18n::fields-group-discount_rule_props',90,1,1,5,0,'К каким товарам/разделам каталога применять скидку?'),(221,'discount_rule_props','i18n::fields-group-discount_rule_props',91,1,1,5,0,'Определяет, с какого момента скидка будет действительна'),(222,'discount_rule_props','i18n::fields-group-discount_rule_props',92,1,1,5,0,'Минимальная стоимость заказа, когда действует скидка'),(223,'discount_rule_props','i18n::fields-group-discount_rule_props',93,1,1,5,0,'Минимальная сумма стоимости заказов действия скидки'),(224,'discount_rule_props','i18n::fields-group-discount_rule_props',94,1,1,5,0,'Список групп пользователей, которым будет доступна эта скидка'),(225,'discount_rule_props','i18n::fields-group-discount_rule_props',95,1,1,5,0,'Список товаров, которые должен положить в корзину пользователь, чтобы пойдействовала скидка'),(226,'delivery_description_props','i18n::fields-group-delivery_description',96,1,1,5,0,'Если установлено - не будет отображаться на сайте'),(227,'delivery_description_props','i18n::fields-group-delivery_description',97,1,1,5,0,'Если установлено - не будет отображаться на сайте'),(228,'delivery_courier_props','i18n::fields-group-delivery_courier_props',97,1,1,10,0,'Стоимость заказа, после которой доставка производится бесплатно'),(229,'delivery_description_props','i18n::fields-group-delivery_description',98,1,1,5,0,'Если установлено - не будет отображаться на сайте'),(230,'settings','i18n::fields-group-settings',98,1,1,10,0,'Настройки этого способа доставки Почтой России'),(231,'delivery_description_props','i18n::fields-group-delivery_description',99,1,1,5,0,'Общие настройки способа доставки'),(232,'settings','i18n::fields-group-settings',99,1,1,10,1,'Если установлено - не будет отображаться на сайте'),(233,'payment_props','i18n::fields-group-payment_props',100,1,1,5,0,'Если установлено - не будет отображаться на сайте'),(234,'settings','i18n::fields-group-parameters',100,1,1,10,0,''),(235,'payment_props','i18n::fields-group-payment_props',101,1,1,5,0,'Если установлено - не будет отображаться на сайте'),(236,'settings','i18n::fields-group-parameters',101,1,1,10,0,'Выдается при регистрации в PayOnline System'),(237,'payment_props','i18n::fields-group-payment_props',102,1,1,5,0,'Если установлено - не будет отображаться на сайте'),(238,'payment_props','i18n::fields-group-payment_props',103,1,1,5,0,'Если установлено - не будет отображаться на сайте'),(239,'settings','i18n::fields-group-parameters',103,1,1,10,0,'используется интерфейсом инициализации оплаты'),(240,'payment_props','i18n::fields-group-payment_props',104,1,1,5,0,'Если установлено - не будет отображаться на сайте'),(241,'settings','i18n::fields-group-parameters',104,1,1,10,0,'Номер сайта продавца, на который покупатель должен совершить платеж'),(242,'payment_props','i18n::fields-group-payment_props',105,1,1,5,0,'Если установлено - не будет отображаться на сайте'),(243,'organization','i18n::fields-group-organization_data',105,1,1,10,0,''),(244,'payment_props','i18n::fields-group-payment_props',106,1,1,5,0,'Если установлено - не будет отображаться на сайте'),(245,'settings','i18n::fields-group-parameters',106,1,1,10,0,'Введите demo.moneta.ru, если у вас включен тестовый режим, либо www.payanyway.ru, если включен боевой режим.'),(246,'payment_props','i18n::fields-group-payment_props',107,1,1,5,0,'Если установлено - не будет отображаться на сайте'),(247,'settings','i18n::fields-group-parameters',107,1,1,10,0,'Настройки способа оплаты данного типа'),(248,'payment_props','i18n::fields-group-payment_props',108,1,1,5,0,'Включить демо-режим'),(249,'payment_props','i18n::fields-group-payment_props',109,1,1,5,0,'Если установлено - не будет отображаться на сайте'),(250,'settings','i18n::fields-group-parameters',109,1,1,10,0,'ID магазина'),(251,'payment_props','i18n::fields-group-payment_props',110,1,1,5,0,'Если установлено - не будет отображаться на сайте'),(252,'settings','i18n::fields-group-parameters',110,1,1,10,0,'Номер магазина в ЦПП. Выдается ЦПП'),(253,'payment_props','i18n::fields-group-payment_props',111,1,1,5,1,'Если установлено - не будет отображаться на сайте'),(254,'settings','i18n::fields-group-parameters',111,1,1,10,1,'Настройки способа оплаты данного типа'),(255,'payment_props','i18n::fields-group-payment_props',112,1,1,5,1,'Если установлено - не будет отображаться на сайте'),(256,'settings','i18n::fields-group-parameters',112,1,1,10,1,'Идентификатор вашего магазина в Яндекс.Кассе'),(257,'common_props','i18n::fields-group-common_props',113,1,1,5,1,''),(258,'common','i18n::fields-group-common',114,1,0,5,1,'Основные настройки баннера'),(259,'redirect_props','i18n::fields-group-redirect_props',114,1,0,10,1,'Настройки перехода с баннера'),(260,'view_params','i18n::fields-group-view_params',114,1,0,15,1,'Статистика и ограничения баннера'),(261,'view_pages','i18n::fields-group-view_pages',114,1,1,20,1,'Где будет отображаться баннер'),(262,'time_targeting','i18n::fields-group-time_targeting',114,1,1,25,1,'Таргетинг показов баннера по временным параметрам'),(263,'city_targeting','i18n::fields-group-city_targeting',114,0,1,30,1,''),(264,'view_settings','i18n::fields-group-privacy',114,1,1,35,0,'Настройки отображения баннера'),(265,'common','i18n::fields-group-common',115,1,0,5,1,'Основные настройки баннера'),(266,'banner_custom_props','i18n::fields-group-banner_custom_props',115,1,1,10,1,'Графические настройки баннера'),(267,'redirect_props','i18n::fields-group-redirect_props',115,1,0,15,1,'Настройки перехода с баннера'),(268,'view_params','i18n::fields-group-view_params',115,1,0,20,1,'Статистика и ограничения баннера'),(269,'view_pages','i18n::fields-group-view_pages',115,1,1,25,1,'Где будет отображаться баннер'),(270,'time_targeting','i18n::fields-group-time_targeting',115,1,1,30,1,'Таргетинг показов баннера по временным параметрам'),(271,'city_targeting','i18n::fields-group-city_targeting',115,0,1,35,1,''),(272,'view_settings','i18n::fields-group-privacy',115,1,1,40,0,'Настройки отображения баннера'),(273,'common','i18n::fields-group-common',116,1,0,5,1,''),(274,'banner_custom_props','i18n::fields-group-banner_custom_props',116,1,1,10,1,'Графические настройки баннера'),(275,'redirect_props','i18n::fields-group-redirect_props',116,1,0,15,1,'Настройки перехода с баннера'),(276,'view_params','i18n::fields-group-view_params',116,1,0,20,1,'Статистика и ограничения баннера'),(277,'view_pages','i18n::fields-group-view_pages',116,1,1,25,1,'Где будет отображаться баннер'),(278,'time_targeting','i18n::fields-group-time_targeting',116,1,1,30,1,'Таргетинг показов баннера по временным параметрам'),(279,'city_targeting','i18n::fields-group-city_targeting',116,0,1,35,1,''),(280,'view_settings','i18n::fields-group-privacy',116,1,1,40,0,'Настройки отображения баннера'),(281,'common','i18n::fields-group-common',117,1,0,5,1,'Основные настройки баннера'),(282,'banner_custom_props','i18n::fields-group-banner_custom_props',117,1,1,10,1,'Содержимое баннера'),(283,'redirect_props','i18n::fields-group-redirect_props',117,1,0,15,1,'Настройки перехода с баннера'),(284,'view_params','i18n::fields-group-view_params',117,1,0,20,1,'Статистика и ограничения баннера'),(285,'view_pages','i18n::fields-group-view_pages',117,1,1,25,1,'Где будет отображаться баннер'),(286,'time_targeting','i18n::fields-group-time_targeting',117,1,1,30,1,'Таргетинг показов баннера по временным параметрам'),(287,'city_targeting','i18n::fields-group-city_targeting',117,0,1,35,1,''),(288,'view_settings','i18n::fields-group-privacy',117,1,1,40,0,'Настройки отображения баннера'),(289,'svojstva','i18n::fields-group-props',118,1,1,5,0,''),(290,'common','i18n::fields-group-props',119,1,1,5,0,'Формат, в который будут перобразованы данные из UMI.CMS'),(291,'common','i18n::fields-group-props',120,1,1,5,0,'Формат импотрируемых данных'),(292,'common','i18n::fields-group-common',121,1,0,5,1,'Основные настройки страницы скачиваемого файла'),(293,'menu_view','i18n::fields-group-menu_view',121,1,0,10,1,''),(294,'more_params','i18n::fields-group-more_params',121,1,0,15,1,'Настройки отображения страницы и её поисковой индексации'),(295,'fs_file_props','i18n::fields-group-fs_file_props',121,1,0,20,1,'Свойства скачиваемого файла'),(296,'rate_voters','i18n::fields-group-rate_voters',121,1,0,25,1,''),(297,'svojstva_publikacii','i18n::fields-group-svojstva_publikacii',121,0,1,30,1,'Управление статусом публикации'),(298,'locks','i18n::fields-group-locks',121,1,1,35,1,''),(299,'common','i18n::fields-group-common_group',122,1,0,5,1,''),(300,'common','i18n::fields-group-common',123,1,1,5,1,''),(301,'common','i18n::fields-group-common',124,1,1,5,1,''),(302,'common','i18n::fields-group-common',125,1,0,5,1,''),(303,'menu_view','i18n::fields-group-menu_view',125,1,0,10,1,''),(304,'more_params','i18n::fields-group-more_params',125,1,0,15,1,''),(305,'rate_props','i18n::fields-group-rate_props',125,1,0,20,1,''),(306,'svojstva_publikacii','i18n::fields-group-svojstva_publikacii',125,0,1,25,1,''),(307,'locks','i18n::fields-group-locks',125,1,1,30,1,''),(308,'appointment','i18n::fields-group-appointment',125,1,1,35,1,'');
/*!40000 ALTER TABLE `cms3_object_field_groups` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cms3_object_field_types`
--

DROP TABLE IF EXISTS `cms3_object_field_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cms3_object_field_types` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(64) DEFAULT NULL,
  `data_type` enum('int','string','text','relation','file','img_file','swf_file','bool','date','boolean','wysiwyg','password','tags','symlink','price','formula','float','counter','optioned','video_file','color','link_to_object_type','multiple_image','domain_id','domain_id_list','offer_id_list','offer_id') DEFAULT NULL,
  `is_multiple` tinyint(1) DEFAULT '0',
  `is_unsigned` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `name` (`name`),
  KEY `data_type` (`data_type`),
  KEY `is_multiple` (`is_multiple`),
  KEY `is_unsigned` (`is_unsigned`)
) ENGINE=InnoDB AUTO_INCREMENT=27 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cms3_object_field_types`
--

LOCK TABLES `cms3_object_field_types` WRITE;
/*!40000 ALTER TABLE `cms3_object_field_types` DISABLE KEYS */;
INSERT INTO `cms3_object_field_types` VALUES (1,'i18n::field-type-boolean','boolean',0,0),(2,'i18n::field-type-color','color',0,0),(3,'i18n::field-type-counter','counter',0,0),(4,'i18n::field-type-date','date',0,0),(5,'i18n::field-type-domain-id','domain_id',0,0),(6,'i18n::field-type-domain-id-list','domain_id_list',1,0),(7,'i18n::field-type-file','file',0,0),(8,'i18n::field-type-float','float',0,0),(9,'i18n::field-type-img_file','img_file',0,0),(10,'i18n::field-type-int','int',0,0),(11,'i18n::field-type-link-to-object-type','link_to_object_type',0,0),(12,'i18n::field-type-multiple-image','multiple_image',1,0),(13,'i18n::field-type-offer-id','offer_id',0,0),(14,'i18n::field-type-offer-id-list','offer_id_list',1,0),(15,'i18n::field-type-optioned','optioned',1,0),(16,'i18n::field-type-password','password',0,0),(17,'i18n::field-type-price','price',0,0),(18,'i18n::field-type-relation','relation',0,0),(19,'i18n::field-type-relation-multiple','relation',1,0),(20,'i18n::field-type-string','string',0,0),(21,'i18n::field-type-swf_file','swf_file',0,0),(22,'i18n::field-type-symlink-multiple','symlink',1,0),(23,'i18n::field-type-tags-multiple','tags',1,0),(24,'i18n::field-type-text','text',0,0),(25,'i18n::field-type-video','video_file',0,0),(26,'i18n::field-type-wysiwyg','wysiwyg',0,0);
/*!40000 ALTER TABLE `cms3_object_field_types` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cms3_object_fields`
--

DROP TABLE IF EXISTS `cms3_object_fields`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cms3_object_fields` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(64) DEFAULT NULL,
  `title` varchar(255) DEFAULT NULL,
  `is_locked` tinyint(1) DEFAULT '0',
  `field_type_id` int(10) unsigned DEFAULT NULL,
  `is_inheritable` tinyint(1) DEFAULT '0',
  `is_visible` tinyint(1) DEFAULT '1',
  `guide_id` int(10) unsigned DEFAULT NULL,
  `in_search` tinyint(1) DEFAULT '1',
  `in_filter` tinyint(1) DEFAULT '1',
  `tip` varchar(255) DEFAULT NULL,
  `is_required` tinyint(1) DEFAULT NULL,
  `restriction_id` int(10) unsigned DEFAULT NULL,
  `sortable` tinyint(4) DEFAULT '0',
  `is_system` tinyint(1) DEFAULT '0',
  `is_important` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `Field to field type relation_FK` (`field_type_id`),
  KEY `FK_Reference_25` (`guide_id`),
  KEY `name` (`name`),
  KEY `title` (`title`),
  KEY `is_locked` (`is_locked`),
  KEY `is_inheritable` (`is_inheritable`),
  KEY `is_visible` (`is_visible`),
  KEY `in_search` (`in_search`),
  KEY `in_filter` (`in_filter`),
  KEY `tip` (`tip`),
  KEY `is_required` (`is_required`),
  KEY `restriction_id` (`restriction_id`),
  KEY `sortable` (`sortable`),
  KEY `is_system` (`is_system`),
  KEY `is_important` (`is_important`),
  CONSTRAINT `FK_Field to field guide relation` FOREIGN KEY (`guide_id`) REFERENCES `cms3_object_types` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `FK_Field to field type relation` FOREIGN KEY (`field_type_id`) REFERENCES `cms3_object_field_types` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `FK_Field to restriction relation` FOREIGN KEY (`restriction_id`) REFERENCES `cms3_object_fields_restrictions` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=484 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cms3_object_fields`
--

LOCK TABLES `cms3_object_fields` WRITE;
/*!40000 ALTER TABLE `cms3_object_fields` DISABLE KEYS */;
INSERT INTO `cms3_object_fields` VALUES (1,'publish_status_id','i18n::field-publish_status_id',1,20,0,1,NULL,0,0,'',0,NULL,0,0,0),(2,'title','i18n::field-title',1,20,1,1,NULL,1,0,'',0,NULL,0,0,0),(3,'h1','i18n::field-h1',1,20,1,1,NULL,1,0,'',0,NULL,0,0,1),(4,'content','i18n::field-content',1,26,0,1,NULL,1,0,'',0,NULL,0,0,1),(5,'meta_descriptions','i18n::field-meta_descriptions',1,20,1,1,NULL,1,0,'',0,NULL,0,0,0),(6,'meta_keywords','i18n::field-meta_keywords',1,20,1,1,NULL,1,0,'',0,NULL,0,0,0),(7,'tags','i18n::field-tags',1,23,0,1,NULL,0,0,'',0,NULL,0,0,0),(8,'menu_pic_ua','i18n::field-menu_pic_ua',1,9,0,1,NULL,0,0,'',0,NULL,0,0,0),(9,'menu_pic_a','i18n::field-menu_pic_a',1,9,0,1,NULL,0,0,'',0,NULL,0,0,0),(10,'header_pic','i18n::field-header_pic',1,9,0,1,NULL,0,0,'',0,NULL,0,0,0),(11,'robots_deny','i18n::field-robots_deny',1,1,1,1,NULL,0,0,'',0,NULL,0,0,0),(12,'show_submenu','i18n::field-show_submenu',1,1,1,1,NULL,0,0,'',0,NULL,0,0,0),(13,'is_expanded','i18n::field-is_expanded',1,1,1,1,NULL,0,0,'',0,NULL,0,0,0),(14,'is_unindexed','i18n::field-is_unindexed',1,1,1,1,NULL,0,0,'',0,NULL,0,0,0),(15,'rate_voters','i18n::field-rate_voters',1,10,0,0,NULL,0,0,'',0,NULL,0,1,0),(16,'rate_sum','i18n::field-rate_sum',1,10,0,0,NULL,0,0,'',0,NULL,0,1,0),(17,'expiration_date','i18n::field-expiration_date',1,4,0,1,NULL,0,0,'',0,NULL,0,0,0),(18,'notification_date','i18n::field-notification_date',1,4,0,1,NULL,0,0,'',0,NULL,0,0,0),(19,'publish_comments','i18n::field-publish_comments',1,24,0,1,NULL,0,0,'',0,NULL,0,0,0),(20,'publish_status','i18n::field-publish-status',1,18,0,1,2,0,0,'',0,NULL,0,0,0),(21,'locktime','i18n::field-locktime',1,4,0,1,NULL,0,0,'',0,NULL,0,1,0),(22,'lockuser','i18n::field-lockuser',1,10,0,1,NULL,0,0,'',0,NULL,0,1,0),(23,'charset','i18n::field-rss-source-charset',0,20,0,0,NULL,0,0,'',0,NULL,0,0,0),(24,'readme','i18n::field-description',1,24,0,1,NULL,0,0,'',0,NULL,0,0,0),(25,'rss_type','i18n::field-rss_type',1,18,0,1,5,0,0,'',0,NULL,0,0,0),(26,'url','i18n::field-url',1,20,0,1,NULL,0,0,'',0,NULL,0,0,1),(27,'charset_id','i18n::field-rss-source-charset-relation',0,18,0,1,6,0,0,'',0,NULL,0,0,0),(28,'news_rubric','i18n::field-news_rubric',1,19,0,1,7,0,0,'',0,NULL,0,0,0),(29,'quality_value','i18n::field-quality_value',1,20,0,1,NULL,0,0,'',0,NULL,0,0,0),(30,'country_iso_code','i18n::field-country_code',1,20,0,1,NULL,0,0,'Страна в формате ISO 3166-1 alpha-2',0,NULL,0,0,0),(31,'identifier','i18n::field-sid',0,10,0,1,NULL,0,0,'',1,NULL,0,0,1),(32,'number','i18n::field-index_number',0,10,0,0,NULL,0,0,'Порядковый номер дня недели (1 (Понедельник) - 7 (Воскресение))',0,NULL,0,0,0),(33,'social_id','i18n::field-social_id',1,10,0,1,NULL,0,0,'',1,NULL,0,0,0),(34,'codename','i18n::field-string_id',0,20,0,1,NULL,0,0,'',1,NULL,0,0,1),(35,'codename','i18n::field-currency_id',0,20,0,1,NULL,0,0,'',1,NULL,0,0,1),(36,'nominal','i18n::field-nominal',0,10,0,1,NULL,0,0,'',1,NULL,0,0,1),(37,'rate','i18n::field-rate',0,8,0,1,NULL,0,0,'',1,NULL,0,0,1),(38,'prefix','i18n::field-prefix',0,20,0,1,NULL,0,0,'',0,NULL,0,0,0),(39,'suffix','i18n::field-suffix',0,20,0,1,NULL,0,0,'',0,NULL,0,0,0),(40,'codename','i18n::field-status_code',0,20,0,1,21,0,0,'',1,NULL,0,0,1),(41,'platform_identificator','i18n::field-emarket-mobile-platform_identificator',1,20,0,1,NULL,0,0,'',1,NULL,0,0,0),(42,'active','i18n::field-emarket-mobile-device-active',0,1,0,1,NULL,0,0,'',0,NULL,0,0,0),(43,'domain_id','i18n::field-domain_name',1,5,0,1,NULL,0,0,'',0,NULL,0,0,0),(44,'token','i18n::field-emarket-mobile-device-token',0,20,0,1,NULL,0,0,'',0,NULL,0,0,0),(45,'platform','i18n::field-emarket-mobile-platform',0,18,0,1,23,0,0,'',0,NULL,0,0,0),(46,'lname','i18n::field-lname',0,20,0,1,NULL,0,0,'',1,NULL,0,0,1),(47,'fname','i18n::field-fname',0,20,0,1,NULL,0,0,'',1,NULL,0,0,1),(48,'father_name','i18n::field-father_name',0,20,0,1,NULL,0,0,'',0,NULL,0,0,0),(49,'email','i18n::field-e-mail',0,20,0,1,NULL,0,0,'',1,3,0,0,1),(50,'phone','i18n::field-phone_n',0,20,0,1,NULL,0,0,'',1,NULL,0,0,1),(51,'yandex_id','i18n::field-id-for-yandex-kassa',1,10,0,1,NULL,0,0,'',1,NULL,0,1,1),(52,'robokassa_id','i18n::field-id-for-robokassa',1,20,0,1,NULL,0,0,'',1,NULL,0,1,1),(53,'payonline_id','i18n::field-id-for-payonline',1,20,0,1,NULL,0,0,'',1,NULL,0,1,1),(54,'payanyway_id','i18n::field-id-for-payanyway',1,10,0,1,NULL,0,0,'',1,NULL,0,1,1),(55,'sberbank_id','i18n::field-id-for-sberbank',1,10,0,1,NULL,0,0,'',1,NULL,0,1,1),(56,'tax','i18n::field-tax',1,20,0,1,NULL,0,0,'',0,NULL,0,1,1),(57,'yandex_id','i18n::field-id-for-yandex-kassa',1,20,0,1,NULL,0,0,'',1,NULL,0,1,1),(58,'payanyway_id','i18n::field-id-for-payanyway',1,20,0,1,NULL,0,0,'',1,NULL,0,1,1),(59,'payonline_id','i18n::field-id-for-payonline',1,10,0,1,NULL,0,0,'',1,NULL,0,1,1),(60,'description','i18n::field-description',0,20,0,1,NULL,0,0,'',0,NULL,0,0,1),(61,'modificator_codename','i18n::field-sid',0,20,0,1,NULL,0,0,'',1,NULL,0,0,1),(62,'modificator_type_id','i18n::field-modificator_type_id',0,11,0,1,NULL,0,0,'',0,NULL,0,0,1),(63,'modificator_discount_types','i18n::field-modificator_discount_types',0,19,0,1,30,0,0,'',0,NULL,0,0,1),(64,'modificator_type_guid','i18n::field-delivery_type_guid',0,20,0,1,NULL,0,0,'',0,NULL,0,0,1),(65,'modificator_type_id','i18n::field-modificator_type',0,18,0,1,31,0,0,'',1,NULL,0,0,1),(66,'rule_codename','i18n::field-sid',0,20,0,1,NULL,0,0,'',1,NULL,0,0,1),(67,'rule_type_id','i18n::field-modificator_type_id',0,11,0,1,NULL,0,0,'',0,NULL,0,0,1),(68,'rule_discount_types','i18n::field-modificator_discount_types',0,19,0,1,30,0,0,'',1,NULL,0,0,1),(69,'rule_type_guid','i18n::field-delivery_type_guid',0,20,0,1,NULL,0,0,'',0,NULL,0,0,1),(70,'rule_type_id','i18n::field-rule_type',0,18,0,1,33,0,0,'',1,NULL,0,0,1),(71,'sid','i18n::field-sid',0,20,0,0,NULL,0,0,'Служит для связи с обработчиками, заполняется разработчиком импотрера',1,NULL,0,0,1),(72,'sid','i18n::field-sid',0,20,0,0,NULL,0,0,'Служит для связи с обработчиками, заполняется разработчиком импотрера',1,NULL,0,0,1),(73,'social_id','i18n::field-social_network_id',0,20,0,1,NULL,0,0,'',1,NULL,0,1,0),(74,'template_id','i18n::field-template_id',1,10,0,0,NULL,0,0,'',0,NULL,0,1,0),(75,'domain_id','i18n::field-domain_name',1,5,0,0,NULL,0,0,'',0,NULL,0,1,0),(76,'nazvanie_sajta','i18n::field-site_name',0,20,0,1,NULL,0,0,'',1,NULL,0,0,0),(77,'is_iframe_enabled','i18n::field-is_iframe_enabled',1,1,0,1,NULL,0,0,'',0,NULL,0,0,0),(78,'iframe_pages','i18n::field-iframe_pages',1,22,0,0,NULL,0,0,'Здесь в дополнение к разделам каталога, можно выбрать, какие страницы нужно показывать в социальной сети',0,NULL,0,0,0),(79,'nazvanie','i18n::field-name',1,20,0,0,NULL,0,0,'',0,NULL,0,0,1),(80,'country','i18n::field-country',0,18,0,1,10,0,0,'',1,NULL,0,0,0),(81,'index','i18n::field-post_index',0,10,0,1,NULL,0,0,'',1,NULL,0,0,1),(82,'region','i18n::field-geographic_area',0,20,0,1,NULL,0,0,'',0,NULL,0,0,0),(83,'city','i18n::field-city',0,20,0,1,NULL,0,0,'',1,NULL,0,0,1),(84,'street','i18n::field-street',0,20,0,1,NULL,0,0,'',1,NULL,0,0,1),(85,'house','i18n::field-house',0,20,0,1,NULL,0,0,'',1,NULL,0,0,1),(86,'flat','i18n::field-appartment',0,20,0,1,NULL,0,0,'',0,NULL,0,0,1),(87,'class_name','i18n::field-item_type_id',0,20,0,1,NULL,0,0,'',1,NULL,0,0,1),(88,'discount_type_id','i18n::field-discount_type',0,18,0,1,30,0,0,'Определяет область применения скидки',1,NULL,0,0,1),(89,'discount_modificator_id','i18n::field-price_modificator',0,18,0,1,12,0,0,'Определяет, по какому принципу будет пересчитываться цена',0,NULL,0,0,1),(90,'discount_rules_id','i18n::field-validation_rules',0,19,0,1,13,0,0,'Определяют ограничения по применению скидки',0,NULL,0,0,1),(91,'is_active','i18n::field-is_active',1,1,0,1,NULL,0,0,'',0,NULL,0,0,1),(92,'description','i18n::field-description',0,24,0,1,NULL,0,0,'',0,NULL,0,0,1),(93,'item_amount','i18n::field-item_amount',0,10,0,1,NULL,0,0,'',0,NULL,0,0,1),(94,'item_price','i18n::field-item_price',0,8,0,1,NULL,0,0,'',0,NULL,0,0,1),(95,'item_actual_price','i18n::field-item_actual_price',0,8,0,1,NULL,0,0,'',0,NULL,0,0,1),(96,'item_total_original_price','i18n::field-item_total_original_price',0,17,0,1,NULL,0,0,'',0,NULL,0,0,1),(97,'item_total_price','i18n::field-item_total_price',0,17,0,1,NULL,0,0,'',0,NULL,0,0,1),(98,'item_type_id','i18n::field-item_type',0,18,0,1,41,0,0,'',0,NULL,0,0,1),(99,'item_link','i18n::field-item_link',0,22,0,1,NULL,0,0,'',0,NULL,0,0,1),(100,'item_discount_id','i18n::field-catalog_object_discount',0,18,0,1,42,0,0,'',0,NULL,0,0,1),(101,'item_discount_value','i18n::field-order-item-discount-value',1,17,0,1,NULL,0,0,'i18n::field-order-item-discount-value',0,NULL,0,0,1),(102,'weight','i18n::field-weight',1,8,0,1,NULL,0,0,'',0,NULL,0,0,1),(103,'width','i18n::field-width',1,8,0,1,NULL,0,0,'',0,NULL,0,0,1),(104,'height','i18n::field-height',1,8,0,1,NULL,0,0,'',0,NULL,0,0,1),(105,'length','i18n::field-order-item-length',1,8,0,1,NULL,0,0,'',0,NULL,0,0,1),(106,'tax_rate_id','i18n::field-tax-rate-id',1,18,0,1,27,0,0,'',0,NULL,0,0,1),(107,'payment_mode','i18n::field-payment_mode',1,18,0,1,29,0,0,'',0,NULL,0,0,1),(108,'payment_subject','i18n::field-payment_subject',1,18,0,1,28,0,0,'',0,NULL,0,0,1),(109,'options','i18n::field-item_options',0,15,0,1,43,0,0,'',0,NULL,0,0,1),(110,'trade_offer','i18n::field-trade-offer',1,13,0,0,NULL,0,0,'',0,NULL,0,0,1),(111,'priority','i18n::field-order-status-priority',0,10,0,0,NULL,0,0,'',0,NULL,0,0,0),(112,'class_name','i18n::field-payment_type_id',0,20,0,1,NULL,0,0,'',1,NULL,0,0,1),(113,'payment_type_id','i18n::field-modificator_type_id',0,11,0,1,NULL,0,0,'',0,NULL,0,0,1),(114,'payment_type_guid','i18n::field-delivery_type_guid',0,20,0,1,NULL,0,0,'',0,NULL,0,0,1),(115,'payment_type_id','i18n::field-payment_type',1,18,0,1,46,0,0,'',1,NULL,0,0,1),(116,'disabled','i18n::field-disabled',1,1,0,0,NULL,0,0,'Если установлено - не будет отображаться на сайте',0,NULL,0,0,1),(117,'domain_id_list','i18n::field-valid-domain-list',1,6,0,1,NULL,0,0,'Если применимо для всех доменов - оставьте список пустым',0,NULL,0,0,1),(118,'contact_person','i18n::field-contact_person',0,20,0,1,NULL,0,0,'',0,NULL,0,0,1),(119,'phone_number','i18n::field-phone_number',0,20,0,1,NULL,0,0,'',0,NULL,0,0,1),(120,'fax','i18n::field-fax',0,20,0,1,NULL,0,0,'',0,NULL,0,0,1),(121,'name','i18n::field-organisation_name',0,20,0,1,NULL,0,0,'',1,NULL,0,0,1),(122,'legal_address','i18n::field-legal_address',0,20,0,1,NULL,0,0,'',0,NULL,0,0,1),(123,'defacto_address','i18n::field-defacto_address',0,20,0,1,NULL,0,0,'',0,NULL,0,0,1),(124,'post_address','i18n::field-post_address',0,20,0,1,NULL,0,0,'',0,NULL,0,0,1),(125,'inn','i18n::field-TIN',0,20,0,1,NULL,0,0,'',0,NULL,0,0,1),(126,'account','i18n::field-account',0,20,0,1,NULL,0,0,'',0,NULL,0,0,1),(127,'bank','i18n::field-bank',0,20,0,1,NULL,0,0,'',0,NULL,0,0,0),(128,'bank_account','i18n::field-bank_account',0,20,0,1,NULL,0,0,'',0,NULL,0,0,0),(129,'bik','i18n::field-bik',0,20,0,1,NULL,0,0,'',0,NULL,0,0,1),(130,'ogrn','i18n::field-ogrn',0,20,0,1,NULL,0,0,'',0,NULL,0,0,1),(131,'kpp','i18n::field-RRC',0,20,0,1,NULL,0,0,'',0,NULL,0,0,1),(132,'class_name','i18n::field-delivery_type_id',0,20,0,1,NULL,0,0,'',1,NULL,0,0,1),(133,'delivery_type_id','i18n::field-modificator_type_id',0,11,0,1,NULL,0,0,'',0,NULL,0,0,1),(134,'delivery_type_guid','i18n::field-delivery_type_guid',0,20,0,1,NULL,0,0,'',0,NULL,0,0,1),(135,'description','i18n::field-delivery_description',0,24,0,1,NULL,0,0,'',0,NULL,0,0,1),(136,'delivery_type_id','i18n::field-delivery_type',0,18,0,0,50,0,0,'',1,NULL,0,0,1),(137,'price','i18n::field-delivery_price',0,17,0,1,NULL,0,0,'',0,NULL,0,0,1),(138,'tax_rate_id','i18n::field-tax-rate-id',1,18,0,1,27,0,0,'',0,NULL,0,0,1),(139,'disabled','i18n::field-disabled',1,1,0,0,NULL,0,0,'Если установлено - не будет отображаться на сайте',0,NULL,0,0,1),(140,'domain_id_list','i18n::field-valid-domain-list',1,6,0,1,NULL,0,0,'Если применимо для всех доменов - оставьте список пустым',0,NULL,0,0,1),(141,'payment_mode','i18n::field-payment_mode',1,18,0,1,29,0,0,'',0,NULL,0,0,1),(142,'payment_subject','i18n::field-payment_subject',1,18,0,1,28,0,0,'',0,NULL,0,0,1),(143,'disabled_types_of_payment','i18n::field-disabled_types_of_payment',1,19,0,1,47,0,0,'',0,NULL,0,0,1),(144,'order_items','i18n::field-order_items',0,19,0,1,44,0,0,'',0,NULL,0,0,1),(145,'number','i18n::field-order_number',0,10,0,1,NULL,0,0,'',0,NULL,0,0,1),(146,'social_order_id','i18n::field-social_order_id',1,10,0,1,NULL,0,0,'',0,NULL,0,0,0),(147,'yandex_order_id','i18n::field-yandex_order_id',1,10,0,1,NULL,0,0,'',0,NULL,0,0,0),(148,'customer_id','i18n::field-customer',0,18,0,1,54,0,0,'',0,NULL,0,0,1),(149,'domain_id','i18n::field-domain_name',0,5,0,1,NULL,0,0,'',0,NULL,0,0,0),(150,'manager_id','i18n::field-manager',0,18,0,1,54,0,0,'',0,NULL,0,0,1),(151,'status_id','i18n::field-order_status',0,18,0,1,45,0,0,'',0,NULL,0,0,1),(152,'total_original_price','i18n::field-order_original_price',0,17,0,1,NULL,0,0,'',0,NULL,0,0,1),(153,'total_price','i18n::field-total_price',0,17,0,1,NULL,0,0,'',0,NULL,0,0,1),(154,'total_amount','i18n::field-total_amount',0,10,0,1,NULL,0,0,'',0,NULL,0,0,1),(155,'status_change_date','i18n::field-status_change_date',0,4,0,1,NULL,0,0,'Выставляется автоматически, когда происходит изменение статуса заказа',0,NULL,0,0,0),(156,'order_date','i18n::field-order_date',0,4,0,1,NULL,0,0,'Дата, когда заказ был оформлен покупателем',0,NULL,0,0,1),(157,'order_discount_value','i18n::field-order-discount-value',1,17,0,1,NULL,0,0,'i18n::field-order-discount-value',0,NULL,0,0,1),(158,'is_reserved','i18n::field-is_reserved',0,1,0,0,NULL,0,0,'',0,NULL,0,1,0),(159,'service_info','i18n::field-order_service_info',0,24,0,1,NULL,0,0,'',0,NULL,0,0,0),(160,'credit-status','i18n::field-kvk-CreditStatus',0,18,0,1,22,0,1,'',0,NULL,0,0,0),(161,'contractsigningdeadline','i18n::field-kvk-ContractSigningDeadline',0,4,0,1,NULL,0,1,'',0,NULL,0,0,0),(162,'contractdeliverydeadline','i18n::field-kvk-ContractDeliveryDeadline',0,4,0,1,NULL,0,1,'',0,NULL,0,0,0),(163,'banksigningappointmenttime','i18n::field-kvk-BankSigningAppointmentTime',0,4,0,1,NULL,0,1,'',0,NULL,0,0,0),(164,'isconfirmed','i18n::field-kvk-IsConfirmed',0,1,0,1,NULL,0,1,'',0,NULL,0,0,0),(165,'signingtype','i18n::field-kvk-SigningType',0,18,0,1,20,0,1,'',0,NULL,0,0,0),(166,'beingprocessed','i18n::field-kvk-BeingProcessed',0,1,0,1,NULL,0,1,'',0,NULL,0,1,0),(167,'http_referer','i18n::field-referer',0,24,0,1,NULL,0,0,'',0,NULL,0,0,0),(168,'http_target','i18n::field-target',0,24,0,1,NULL,0,0,'',0,NULL,0,0,0),(169,'source_domain','i18n::field-source_domain',1,20,0,0,NULL,0,0,'',0,NULL,0,0,0),(170,'utm_medium','i18n::field-utm_medium',1,20,0,0,NULL,0,0,'',0,NULL,0,0,0),(171,'utm_term','i18n::field-utm_term',1,20,0,0,NULL,0,0,'',0,NULL,0,0,0),(172,'utm_campaign','i18n::field-utm_campaign',1,20,0,0,NULL,0,0,'',0,NULL,0,0,0),(173,'utm_content','i18n::field-utm_content',1,20,0,0,NULL,0,0,'',0,NULL,0,0,0),(174,'order_create_date','i18n::field-order_create_date',1,4,0,0,NULL,0,0,'',0,NULL,0,0,0),(175,'payment_id','i18n::field-payment_id',0,18,0,1,47,0,0,'',0,NULL,0,0,1),(176,'payment_name','i18n::field-payment-name',1,20,0,1,NULL,0,0,'',0,NULL,0,0,1),(177,'payment_status_id','i18n::field-payment_status',0,18,0,1,48,0,0,'',0,NULL,0,0,1),(178,'payment_date','i18n::field-payment_date',0,4,0,1,NULL,0,0,'Дата успешной оплаты заказа',0,NULL,0,0,0),(179,'payment_document_num','i18n::field-payment_document_number',0,20,0,1,NULL,0,0,'Заполняется системой автоматически',0,NULL,0,0,0),(180,'legal_person','i18n::field-legal-body',1,18,0,1,49,0,0,'',0,NULL,0,0,1),(181,'delivery_id','i18n::field-delivery_id',0,18,0,1,51,0,0,'',0,NULL,0,0,1),(182,'delivery_name','i18n::field-delivery-name',1,20,0,1,NULL,0,0,'',0,NULL,0,0,1),(183,'delivery_status_id','i18n::field-delivery_status',0,18,0,1,52,0,0,'',0,NULL,0,0,1),(184,'delivery_address','i18n::field-destination_address',0,18,0,1,40,0,0,'',0,NULL,0,0,1),(185,'delivery_date','i18n::field-delivery-date',1,4,0,1,NULL,0,0,'Дата, когда товар будет доставлен клиенту или на пункт выдачи',0,NULL,0,0,1),(186,'pickup_date','i18n::field-pickup-date',1,4,0,1,NULL,0,0,'Дата, когда товар будет доставлен на пункт приема или когда нужно его забрать со склада',0,NULL,0,0,1),(187,'delivery_provider','i18n::field-delivery-provider',1,20,0,1,NULL,0,0,'i18n::field-delivery-provider',0,NULL,0,0,1),(188,'delivery_tariff','i18n::field-delivery-tariff',1,20,0,1,NULL,0,0,'i18n::field-delivery-tariff',0,NULL,0,0,1),(189,'delivery_type','i18n::field-delivery-type',1,20,0,1,NULL,0,0,'Тип доставки товара клиенту',0,NULL,0,0,1),(190,'pickup_type','i18n::field-pickup-type',1,20,0,1,NULL,0,0,'Тип доставки товара поставщику',0,NULL,0,0,1),(191,'delivery_price','i18n::field-delivery_price',0,17,0,1,NULL,0,0,'',0,NULL,0,0,1),(192,'delivery_point_in','i18n::field-delivery-point-in-id',1,20,0,1,NULL,0,0,'Точка приема товара',0,NULL,0,0,1),(193,'delivery_point_out','i18n::field-delivery-point-out-id',1,20,0,1,NULL,0,0,'Точка выдачи товара',0,NULL,0,0,1),(194,'total_weight','i18n::field-order-total-weight',1,8,0,1,NULL,0,0,'field-order-total-weight-tip',0,NULL,0,0,1),(195,'total_width','i18n::field-order-book-total-width',1,8,0,1,NULL,0,0,'field-order-book-total-width-tip',0,NULL,0,0,0),(196,'total_height','i18n::field-order-total-height',1,8,0,1,NULL,0,0,'field-order-total-height-tip',0,NULL,0,0,0),(197,'total_length','i18n::field-order-total-length',1,8,0,1,NULL,0,0,'field-order-total-length-tip',0,NULL,0,0,0),(198,'delivery_allow_date','i18n::field-delivery_allow_date',0,4,0,1,NULL,0,0,'Заполняется системой автоматически, когда возможна доставка и заказ успешно оплачен',0,NULL,0,0,0),(199,'order_discount_id','i18n::field-order_discount',0,18,0,1,42,0,0,'',0,NULL,0,0,1),(200,'bonus','i18n::field-spent_bonus',1,17,0,0,NULL,0,0,'',0,NULL,0,0,0),(201,'need_export','i18n::field-export_1C',0,1,0,1,NULL,0,0,'Выставляется системой автоматически, когда заказ необходимо выгрузить в 1С',0,NULL,0,0,0),(202,'purchaser_one_click','i18n::field-customer',1,18,0,1,25,0,0,'',0,NULL,0,0,1),(203,'login','i18n::field-login',1,20,0,1,NULL,0,0,'',1,NULL,0,0,1),(204,'password','i18n::field-password',1,16,0,1,NULL,0,0,'',0,NULL,0,0,0),(205,'groups','i18n::field-groups',1,19,0,0,39,0,0,'',0,NULL,0,0,1),(206,'e-mail','i18n::field-e-mail',1,20,0,1,NULL,0,0,'',1,NULL,0,0,1),(207,'activate_code','i18n::field-activate_code',1,20,0,0,NULL,0,0,'',0,NULL,0,1,0),(208,'loginza','i18n::field-loginza',1,20,0,1,NULL,0,0,'Адрес сервиса',0,NULL,0,0,0),(209,'is_activated','i18n::field-is_activated',1,1,0,0,NULL,0,0,'',0,NULL,0,0,1),(210,'last_request_time','i18n::field-last_request_time',1,10,0,0,NULL,0,0,'',0,NULL,0,1,0),(211,'subscribed_pages','i18n::field-subscribed_pages',1,22,0,0,NULL,0,0,'',0,NULL,0,1,0),(212,'rated_pages','i18n::field-rated_pages',1,22,0,0,NULL,0,0,'',0,NULL,0,1,0),(213,'is_online','i18n::field-is_online',1,1,0,0,NULL,0,0,'',0,NULL,0,1,0),(214,'messages_count','i18n::field-messages_count',1,10,0,0,NULL,0,0,'',0,NULL,0,1,0),(215,'orders_refs','i18n::field-orders_refs',1,19,0,0,NULL,0,0,'',0,NULL,0,1,0),(216,'delivery_addresses','i18n::field-delivery_addresses',1,19,0,1,40,0,0,'',0,NULL,0,1,0),(217,'user_dock','i18n::field-user_dock',1,20,0,0,NULL,0,0,'',0,NULL,0,1,0),(218,'preffered_currency','i18n::field-preffered_currency',1,18,0,0,21,0,0,'',0,NULL,0,0,0),(219,'user_settings_data','i18n::field-user_settings_data',1,24,0,0,NULL,0,0,'',0,NULL,0,1,0),(220,'last_order','i18n::field-last_order',1,15,0,0,53,0,0,'',0,NULL,0,1,0),(221,'bonus','i18n::field-bonus',1,17,0,0,NULL,0,0,'',0,NULL,0,0,0),(222,'legal_persons','i18n::field-legal_persons',1,19,0,1,49,0,0,'',0,NULL,0,1,1),(223,'spent_bonus','i18n::field-spent_bonus',1,17,0,0,NULL,0,0,'',0,NULL,0,0,0),(224,'filemanager_directory','i18n::field-filemanager-directory',1,20,0,0,NULL,0,0,'Список доступных пользователю директорий. В качестве разделителя используется запятая - например: /image/cms, /files.',0,NULL,0,0,0),(225,'appended_file_extensions','i18n::field-appended-file-extensions',1,20,0,0,NULL,0,0,'Список дополнительных расширений файлов, которые пользователь может загружать на сервер. В качестве разделителя используется запятая - например: cdr, mid, midi.',0,NULL,0,0,0),(226,'register_date','i18n::field-register_date',0,4,0,0,NULL,0,0,'',0,NULL,0,0,0),(227,'tickets_color','i18n::field-tickets_color',0,2,0,0,NULL,0,0,'Фоновый цвет заметок',0,NULL,0,0,0),(228,'favorite_domain_list','i18n::field-favorite_domain_list',0,6,0,0,NULL,0,0,'Если заполнено - для пользователя будут выводиться только эти домены в административной панели во вкладке \"Структура сайта\".',0,NULL,0,0,0),(229,'lname','i18n::field-lname',1,20,0,1,NULL,0,0,'',0,NULL,0,0,1),(230,'fname','i18n::field-fname',1,20,0,1,NULL,0,0,'Это поле содержит Имя пользователя. Оно отображается в характеристиках пользователя и может быть изменено самим пользователем.',1,NULL,0,0,1),(231,'father_name','i18n::field-father_name',1,20,0,1,NULL,0,0,'',0,NULL,0,0,0),(232,'phone','i18n::field-phone_n',1,20,0,1,NULL,0,0,'',0,NULL,0,0,1),(233,'referer','i18n::field-referer',0,24,0,0,NULL,0,0,'',0,NULL,0,0,0),(234,'target','i18n::field-target',0,24,0,0,NULL,0,0,'',0,NULL,0,0,0),(235,'primary','i18n::field-primary_store',0,1,0,1,NULL,0,0,'Основной склад, с которого будут списываться товары при покупке, и с которым будет синхронизироваться 1C',0,NULL,0,0,1),(236,'proc','i18n::field-proc',0,8,0,1,NULL,0,0,'',1,NULL,0,0,1),(237,'users','i18n::field-users',0,19,0,1,54,0,0,'Пользователи, которым будет доступна скидка',1,NULL,0,0,1),(238,'menu_id','i18n::field-sid',0,20,0,1,NULL,0,0,'',0,NULL,0,0,1),(239,'menuhierarchy','i18n::field-menuhierarchy',0,24,0,1,NULL,0,0,'',0,NULL,0,0,1),(240,'anons','i18n::field-anons',1,26,0,1,NULL,1,0,'',0,NULL,0,0,1),(241,'source','i18n::field-source',1,20,0,1,NULL,0,0,'',0,NULL,0,0,0),(242,'source_url','i18n::field-source_url',1,20,0,1,NULL,0,0,'',0,NULL,0,0,0),(243,'publish_time','i18n::field-publish_time',1,4,0,1,NULL,0,1,'',0,NULL,0,0,1),(244,'begin_time','i18n::field-begin_time',1,4,0,1,NULL,0,0,'',0,NULL,0,0,0),(245,'end_time','i18n::field-end_time',1,4,0,1,NULL,0,0,'',0,NULL,0,0,0),(246,'anons_pic','i18n::field-anons_pic',1,9,0,1,NULL,0,0,'',0,NULL,0,0,1),(247,'publish_pic','i18n::field-publish_pic',1,9,0,1,NULL,0,0,'',0,NULL,0,0,1),(248,'subjects','i18n::field-subjects',1,19,0,1,59,0,0,'',0,NULL,0,0,0),(249,'user_id','i18n::field-user_id',1,18,0,1,54,0,0,'',0,NULL,0,1,0),(250,'message','i18n::field-message',1,24,0,1,NULL,1,0,'',0,NULL,0,0,1),(251,'x','i18n::field-x',1,10,0,1,NULL,0,0,'',0,NULL,0,1,0),(252,'y','i18n::field-y',1,10,0,1,NULL,0,0,'',0,NULL,0,1,0),(253,'width','i18n::field-width',1,10,0,1,NULL,0,0,'',0,NULL,0,1,0),(254,'height','i18n::field-height',1,10,0,1,NULL,0,0,'',0,NULL,0,1,0),(255,'create_time','i18n::field-gen_time',1,4,0,1,NULL,0,0,'',0,NULL,0,1,0),(256,'url','i18n::field-url',1,20,0,1,NULL,0,0,'',0,NULL,0,1,1),(257,'description','i18n::field-description',1,20,0,1,NULL,0,1,'',0,NULL,0,0,1),(258,'friendlist','i18n::field-friendlist',1,19,0,1,54,0,1,'',0,NULL,0,0,0),(259,'is_registrated','i18n::field-is_registrated',1,1,0,1,NULL,0,0,'',0,NULL,0,0,0),(260,'user_id','i18n::field-user_id',1,18,0,1,54,0,0,'',0,NULL,0,0,0),(261,'nickname','i18n::field-nickname',1,20,0,1,NULL,0,0,'',0,NULL,0,0,1),(262,'email','i18n::field-e-mail',1,20,0,1,NULL,0,0,'',0,NULL,0,0,1),(263,'ip','i18n::field-ip',1,20,0,1,NULL,0,0,'',0,NULL,0,0,0),(264,'author_id','i18n::field-author',1,18,1,0,64,0,1,'Идентификатор автора комментария',0,NULL,0,0,0),(265,'is_spam','i18n::field-is_spam',0,1,0,1,NULL,0,0,'',0,NULL,0,0,1),(266,'only_for_friends','i18n::field-only_for_friends',1,1,0,1,NULL,0,1,'',0,NULL,0,0,0),(267,'descr','i18n::field-description',1,26,0,1,NULL,1,0,'',0,NULL,0,0,1),(268,'topics_count','i18n::field-topics_count',1,10,0,0,NULL,0,0,'',0,NULL,0,0,0),(269,'messages_count','i18n::field-messages_count',1,10,0,0,NULL,0,0,'',0,NULL,0,0,0),(270,'last_message','i18n::field-last_message',1,22,0,0,NULL,0,0,'',0,NULL,0,0,0),(271,'last_post_time','i18n::field-last_post_time',1,10,0,0,NULL,0,0,'',0,NULL,0,0,0),(272,'message','i18n::field-message',1,24,0,1,NULL,1,0,'',0,NULL,0,0,1),(273,'count','i18n::field-count',1,10,0,1,NULL,0,0,'',0,NULL,0,0,0),(274,'poll_rel','i18n::field-poll_rel',1,18,0,0,72,0,0,'',0,NULL,0,0,0),(275,'is_closed','i18n::field-is_closed',1,1,0,1,NULL,0,0,'',0,NULL,0,0,1),(276,'question','i18n::field-question',1,24,0,1,NULL,1,0,'Вопрос пользователя',0,NULL,0,0,1),(277,'answers','i18n::field-answers',1,19,0,1,71,0,0,'',0,NULL,0,0,1),(278,'total_count','i18n::field-total_count',1,10,0,1,NULL,0,0,'',0,NULL,0,0,1),(279,'form_id','i18n::field-form_id',1,11,0,0,NULL,0,1,'',0,NULL,0,0,1),(280,'destination_address','i18n::field-destination_address',1,20,0,0,NULL,0,0,'',0,NULL,0,0,1),(281,'sender_ip','i18n::field-sender_ip',1,20,0,0,NULL,0,0,'',0,NULL,0,0,0),(282,'sending_time','i18n::field-sending_time',1,4,0,0,NULL,0,0,'',0,NULL,0,0,0),(283,'wf_message','option-message',0,26,0,1,NULL,0,0,'',0,5,0,0,0),(284,'from_email_template','i18n::field-from_email_template',1,20,0,1,NULL,0,0,'',0,NULL,0,0,1),(285,'from_template','i18n::field-from_template',1,20,0,1,NULL,0,0,'',0,NULL,0,0,1),(286,'subject_template','i18n::field-subject_template',1,20,0,1,NULL,0,0,'',0,NULL,0,0,1),(287,'master_template','i18n::field-master_template',1,26,0,1,NULL,0,0,'',0,NULL,0,0,1),(288,'autoreply_from_email_template','i18n::field-autoreply_from_email_template',1,20,0,1,NULL,0,0,'',0,NULL,0,0,1),(289,'autoreply_from_template','i18n::field-autoreply_from_template',1,20,0,1,NULL,0,0,'',0,NULL,0,0,1),(290,'autoreply_subject_template','i18n::field-autoreply_subject_template',1,20,0,1,NULL,0,0,'',0,NULL,0,0,1),(291,'autoreply_email_recipient','i18n::field-autoreply_email_recipient',0,20,0,1,NULL,0,0,'',0,NULL,0,0,1),(292,'autoreply_template','i18n::field-autoreply_template',1,26,0,1,NULL,0,0,'',0,NULL,0,0,1),(293,'posted_message','i18n::field-message_sent',1,26,0,1,NULL,0,0,'',0,NULL,0,0,0),(294,'form_id','i18n::field-form_id',1,11,0,0,NULL,0,0,'',0,NULL,0,0,1),(295,'address_description','i18n::field-description',1,20,0,1,NULL,0,0,'',1,NULL,0,0,0),(296,'address_list','i18n::field-address_list',1,20,0,1,NULL,0,0,'',1,NULL,0,0,1),(297,'form_id','i18n::field-form_id',1,20,0,0,NULL,0,0,'',0,NULL,0,1,1),(298,'create_time','i18n::field-gen_time',1,4,0,1,NULL,0,0,'',0,NULL,0,0,0),(299,'user_id','i18n::field-user_id',1,18,0,0,54,0,0,'',0,NULL,0,0,0),(300,'photo','i18n::field-photo',1,9,0,1,NULL,0,0,'',0,NULL,0,0,1),(301,'answer','i18n::field-answer',1,24,0,1,NULL,0,0,'',0,NULL,0,0,1),(302,'disp_last_release','i18n::field-disp_last_release',1,4,0,1,NULL,0,1,'',0,NULL,0,0,1),(303,'disp_description','i18n::field-description',1,24,0,1,NULL,0,0,'',0,NULL,0,0,0),(304,'forced_subscribers','i18n::field-forced_subscribers',1,22,0,1,NULL,0,1,'',0,NULL,0,0,1),(305,'news_relation','i18n::field-news_relation',1,18,0,1,7,0,1,'',0,NULL,0,0,0),(306,'is_active','i18n::field-is_active',1,1,0,1,NULL,0,0,'',0,NULL,0,0,1),(307,'load_from_forum','i18n::field-load_from_forum',0,1,0,1,NULL,0,0,'Может быть установлено только для одной рассылки одновременно',0,NULL,0,0,0),(308,'days','i18n::field-days',0,19,0,0,17,0,0,'Дни недели, в которые будет происходить автоматическая рассылка',0,NULL,0,0,0),(309,'hours','i18n::field-hours',0,19,0,0,16,0,0,'Часы, в которые будет происходить автоматическая рассылка',0,NULL,0,0,0),(310,'status','i18n::field-status',1,1,0,1,NULL,0,1,'',0,NULL,0,0,1),(311,'date','i18n::field-date',1,4,0,1,NULL,0,1,'',0,NULL,0,0,1),(312,'disp_reference','i18n::field-disp_reference',1,10,0,0,NULL,0,1,'',0,NULL,0,0,0),(313,'header','i18n::field-header',1,20,0,0,NULL,1,0,'',0,NULL,0,0,1),(314,'body','i18n::field-body',1,26,0,1,NULL,0,0,'',0,NULL,0,0,1),(315,'release_reference','i18n::field-release_reference',1,10,0,0,NULL,0,1,'',0,NULL,0,0,0),(316,'attach_file','i18n::field-attach_file',1,7,0,1,NULL,0,0,'',0,NULL,0,0,0),(317,'msg_date','i18n::field-msg_date',1,4,0,1,NULL,0,1,'',0,NULL,0,0,0),(318,'short_body','i18n::field-short_body',1,26,0,1,NULL,0,1,'',0,NULL,0,0,1),(319,'new_relation','i18n::field-new_relation',1,22,0,0,NULL,0,1,'',0,NULL,0,0,0),(320,'lname','i18n::field-lname',1,20,0,1,NULL,0,0,'',0,NULL,0,0,1),(321,'fname','i18n::field-fname',1,20,0,1,NULL,0,0,'',0,NULL,0,0,1),(322,'father_name','i18n::field-father_name',1,20,0,1,NULL,0,0,'',0,NULL,0,0,0),(323,'gender','i18n::field-gender',1,18,0,1,4,0,0,'',0,NULL,0,0,0),(324,'uid','i18n::field-uid',1,18,0,0,54,0,1,'',0,NULL,0,0,0),(325,'subscriber_dispatches','i18n::field-subscriber_dispatches',1,19,0,1,82,0,1,'',0,NULL,0,0,1),(326,'sent_release_list','i18n::field-sent_release_list',1,19,0,1,83,0,0,'',0,NULL,0,0,1),(327,'subscribe_date','i18n::field-subscribe_date',1,4,0,0,NULL,0,0,'',0,NULL,0,0,0),(328,'social_category_vkontakte','i18n::field-social_categories_vkontakte',1,18,0,1,19,0,0,'',0,NULL,0,0,0),(329,'index_source','i18n::field-index_source',0,10,0,1,NULL,0,0,'',0,NULL,0,1,0),(330,'index_state','i18n::field-index_state',0,8,0,1,NULL,0,0,'',0,NULL,0,1,0),(331,'index_date','i18n::field-index_date',0,4,0,1,NULL,0,0,'',0,NULL,0,1,0),(332,'index_choose','i18n::field-index_choose',0,1,0,1,NULL,0,0,'',0,NULL,0,1,0),(333,'index_level','i18n::field-index_level',0,10,0,1,NULL,0,0,'',0,NULL,0,1,0),(334,'date_create_object','i18n::field-date_create_object',1,4,0,0,NULL,0,0,'',0,NULL,0,0,0),(335,'tax_rate_id','i18n::field-tax-rate-id',1,18,0,1,27,0,0,'',0,NULL,0,0,1),(336,'price','i18n::field-cena',1,17,0,1,NULL,0,1,'',0,NULL,0,0,1),(337,'payment_mode','i18n::field-payment_mode',1,18,0,1,29,0,0,'',0,NULL,0,0,1),(338,'payment_subject','i18n::field-payment_subject',1,18,0,1,28,0,0,'',0,NULL,0,0,1),(339,'stores_state','i18n::field-stores_state',0,15,0,1,55,0,0,'',0,NULL,0,0,1),(340,'reserved','i18n::field-reserved',0,10,0,0,NULL,0,0,'i18n::field-tip-number-reserved-items',0,NULL,0,1,0),(341,'common_quantity','i18n::field-common-quantity',0,10,0,1,NULL,0,0,'',0,NULL,0,0,0),(342,'trade_offer_image','i18n::field-izobrazhenie',1,9,0,1,NULL,0,0,'',0,NULL,0,0,1),(343,'trade_offer_list','i18n::field-trade-offer-list',1,14,0,0,NULL,0,0,'',0,NULL,0,0,1),(344,'preffered_currency','i18n::field-preffered_currency',0,18,0,0,21,0,0,'',0,NULL,0,0,0),(345,'last_order','i18n::field-last_order',1,15,0,0,53,0,0,'',0,NULL,0,1,0),(346,'bonus','i18n::field-bonus',1,17,0,0,NULL,0,0,'',0,NULL,0,0,0),(347,'spent_bonus','i18n::field-spent_bonus',1,17,0,0,NULL,0,0,'',0,NULL,0,0,0),(348,'ip','i18n::field-ip_address',0,20,0,0,NULL,0,0,'',0,NULL,0,0,0),(349,'delivery_addresses','i18n::field-delivery_addresses',0,19,0,1,40,0,0,'',0,NULL,0,0,1),(350,'legal_persons','i18n::field-legal_persons',1,19,0,1,49,0,0,'',0,NULL,0,0,1),(351,'size','i18n::field-discount_size',0,8,0,1,NULL,0,0,'Размер скидки, который будет вычтен из стоимости заказа',1,NULL,0,0,1),(352,'catalog_items','i18n::field-goods',0,22,0,1,NULL,0,0,'',0,NULL,0,0,1),(353,'start_date','i18n::field-discount_start',0,4,0,1,NULL,0,0,'Определяет, с какого момента скидка будет действительна',0,NULL,0,0,0),(354,'end_date','i18n::field-discount_end',0,4,0,1,NULL,0,0,'Определяет, когда заканчивается срок действия скидки',0,NULL,0,0,0),(355,'minimum','i18n::field-minimal_order_price',0,8,0,1,NULL,0,0,'Минимальная стоимость заказа, когда действует скидка',0,NULL,0,0,1),(356,'maximum','i18n::field-maximal_order_price',0,8,0,1,NULL,0,0,'Максимальная стоимость заказа, когда действует скидка',0,NULL,0,0,1),(357,'minimal','i18n::field-minimal_summ',0,8,0,1,NULL,0,0,'Минимальная сумма стоимости заказов действия скидки',0,NULL,0,0,1),(358,'maximum','i18n::field-maximal_summ',0,8,0,1,NULL,0,0,'Максимальная сумма стоимости заказов действия скидки',0,NULL,0,0,1),(359,'user_groups','i18n::field-groups',0,19,0,1,39,0,0,'Список групп пользователей, которым будет доступна эта скидка',1,NULL,0,0,1),(360,'related_items','i18n::field-connected_items',0,22,0,1,NULL,0,0,'Список товаров, которые должен положить в корзину пользователь, чтобы пойдействовала скидка',1,NULL,0,0,1),(361,'order_min_price','i18n::field-order_min_price',0,8,0,1,NULL,0,0,'Стоимость заказа, после которой доставка производится бесплатно',0,NULL,0,0,1),(362,'viewpost','i18n::field-viewpost',0,18,0,1,14,0,0,'',1,NULL,0,0,0),(363,'zip_code','i18n::field-departure_city_zip_code',0,20,0,1,NULL,0,0,'',1,NULL,0,0,0),(364,'login','i18n::field-login',1,20,0,1,NULL,0,0,'',0,NULL,0,0,1),(365,'password','i18n::field-password',1,16,0,1,NULL,0,0,'',0,NULL,0,0,1),(366,'dev_mode','i18n::field-apiship-dev-mode',1,1,0,1,NULL,0,0,'',0,NULL,0,0,1),(367,'keep_log','i18n::field-keep-log',1,1,0,1,NULL,0,0,'',0,NULL,0,0,1),(368,'providers','i18n::field-apiship-providers',1,20,0,1,NULL,0,0,'',0,NULL,0,0,1),(369,'delivery_types','Типы доставки',1,20,0,1,NULL,0,0,'',0,NULL,0,0,1),(370,'pickup_types','i18n::field-apiship-pickup-types',1,20,0,1,NULL,0,0,'',0,NULL,0,0,1),(371,'settings','i18n::field-apiship-settings',1,24,0,1,NULL,0,0,'',0,NULL,0,0,1),(372,'reciever','i18n::field-reciever',0,20,0,1,NULL,0,0,'',1,NULL,0,0,0),(373,'reciever_inn','i18n::field-reciever_inn',0,20,0,1,NULL,0,0,'',1,NULL,0,0,1),(374,'reciever_account','i18n::field-reciever_account',0,20,0,1,NULL,0,0,'',1,NULL,0,0,1),(375,'reciever_bank','i18n::field-reciever_bank',0,20,0,1,NULL,0,0,'',1,NULL,0,0,1),(376,'bik','i18n::field-bik',0,20,0,1,NULL,0,0,'',0,NULL,0,0,1),(377,'reciever_bank_account','i18n::field-reciever_bank_account',0,20,0,1,NULL,0,0,'',1,NULL,0,0,0),(378,'merchant_id','i18n::field-merchant_id',0,20,0,1,NULL,0,0,'Выдается при регистрации в PayOnline System',1,NULL,0,0,0),(379,'private_key','i18n::field-secret-key',0,20,0,1,NULL,0,0,'Выдается при регистрации в PayOnline System',1,NULL,0,0,0),(380,'receipt_data_send_enable','i18n::field-receipt-data-send-enable',1,1,0,1,NULL,0,0,'Используется для реализации ФЗ-54, требует заполнения ставки НДС у товаров и доставок',0,NULL,0,0,1),(381,'keep_log','i18n::field-keep-log',1,1,0,1,NULL,0,0,'',0,NULL,0,0,1),(382,'login','i18n::field-login',0,20,0,1,NULL,0,0,'',1,NULL,0,0,1),(383,'password1','i18n::field-password1',0,20,0,1,NULL,0,0,'используется интерфейсом инициализации оплаты',1,NULL,0,0,0),(384,'password2','i18n::field-password2',0,20,0,1,NULL,0,0,'используется интерфейсом оповещения о платеже, XML-интерфейсах',1,NULL,0,0,0),(385,'test_mode','i18n::field-test_mode',1,1,0,1,NULL,0,0,'',0,NULL,0,0,1),(386,'eshopid','i18n::field-eshopid',0,20,0,1,NULL,0,0,'Номер сайта продавца, на который покупатель должен совершить платеж',1,NULL,0,0,0),(387,'secretkey','i18n::field-secret-key',0,20,0,1,NULL,0,0,'Строка символов, добавляемая к реквизитам платежа, высылаемым продавцу вместе с оповещением',1,NULL,0,0,0),(388,'name','i18n::field-name',0,20,0,1,NULL,0,0,'',1,NULL,0,0,1),(389,'legal_address','i18n::field-legal_address',0,20,0,1,NULL,0,0,'',0,NULL,0,0,1),(390,'phone_number','i18n::field-phone_number',0,20,0,1,NULL,0,0,'',0,NULL,0,0,1),(391,'inn','i18n::field-TIN',0,20,0,1,NULL,0,0,'',0,NULL,0,0,1),(392,'kpp','i18n::field-RRC',0,20,0,1,NULL,0,0,'',0,NULL,0,0,1),(393,'account','i18n::field-account',0,20,0,1,NULL,0,0,'',0,NULL,0,0,0),(394,'bank','i18n::field-receiver_bank',0,20,0,1,NULL,0,0,'',0,NULL,0,0,0),(395,'bank_account','i18n::field-bank_account_number',0,20,0,1,NULL,0,0,'',0,NULL,0,0,0),(396,'sign_image','i18n::field-sign_image',0,9,0,1,NULL,0,0,'',0,NULL,0,0,0),(397,'mnt_system_url','i18n::field-mnt_system_url',1,20,0,1,NULL,0,0,'Введите demo.moneta.ru, если у вас включен тестовый режим, либо www.payanyway.ru, если включен боевой режим.',1,NULL,0,0,1),(398,'mnt_id','i18n::field-mnt_id',1,20,0,1,NULL,0,0,'',1,NULL,0,0,1),(399,'mnt_success_url','i18n::field-successful_pay_url',1,20,0,1,NULL,0,0,'',1,NULL,0,0,1),(400,'mnt_fail_url','i18n::field-failed_pay_url',1,20,0,1,NULL,0,0,'',0,NULL,0,0,1),(401,'mnt_data_integrity_code','i18n::field-data_integrity_code',1,20,0,1,NULL,0,0,'',1,NULL,0,0,1),(402,'mnt_test_mode','i18n::field-test_mode',1,1,0,1,NULL,0,0,'',0,NULL,0,0,1),(403,'project','i18n::field-project-id',1,20,0,1,NULL,0,0,'',1,NULL,0,0,0),(404,'key','i18n::field-secret-key',1,20,0,1,NULL,0,0,'',1,NULL,0,0,0),(405,'source','i18n::field-owner-id',1,20,0,1,NULL,0,0,'',1,NULL,0,0,0),(406,'partnerid','i18n::field-mnt_id',0,20,0,1,NULL,0,0,'',1,NULL,0,0,0),(407,'apikey','i18n::field-kvk-apiKey',0,20,0,1,NULL,0,0,'',1,NULL,0,0,0),(408,'demo_mode','i18n::field-demo_mode',0,1,0,1,NULL,0,0,'Включить демо-режим',0,NULL,0,0,0),(409,'merchant_id','i18n::field-emarket-id',0,20,0,1,NULL,0,0,'i18n::field-emarket-id',1,NULL,0,0,0),(410,'product_id','i18n::field-product_id',0,20,0,1,NULL,0,0,'i18n::field-product_id',1,NULL,0,0,0),(411,'ok_url','i18n::field-successful_pay_url',0,20,0,1,NULL,0,0,'i18n::field-successful_pay_url',0,NULL,0,0,0),(412,'secret_word','i18n::field-secret_word',0,20,0,1,NULL,0,0,'i18n::field-secret_word',1,NULL,0,0,0),(413,'ko_url','i18n::field-failed_pay_url',0,20,0,1,NULL,0,0,'i18n::field-failed_pay_url',0,NULL,0,0,0),(414,'shop_id','i18n::field-shop_id',0,20,0,1,NULL,0,0,'Номер магазина в ЦПП. Выдается ЦПП',1,NULL,0,0,0),(415,'scid','i18n::field-scid',0,20,0,1,NULL,0,0,'Номер витрины магазина в ЦПП. Выдается ЦПП',1,NULL,0,0,0),(416,'bank_id','i18n::field-bank_id',0,20,0,1,NULL,0,0,'Код процессингового центра \"Яндекс.Деньги\"',0,NULL,0,0,0),(417,'shop_password','i18n::field-shop_password',0,20,0,1,NULL,0,0,'Секретный пароль (20 случайных символов), указывается при заключении договора',1,NULL,0,0,0),(418,'paypalemail','i18n::field-paypalemail',1,20,0,1,NULL,0,0,'',1,NULL,0,0,0),(419,'return_success','i18n::field-successful_pay_url',1,20,0,1,NULL,0,0,'',1,NULL,0,0,0),(420,'cancel_return','i18n::field-failed_pay_url',1,20,0,1,NULL,0,0,'',1,NULL,0,0,0),(421,'shop_id','i18n::field-mnt_id',1,20,0,1,NULL,0,0,'Идентификатор вашего магазина в Яндекс.Кассе',1,NULL,0,0,1),(422,'secret_key','i18n::field-secret-key',1,20,0,1,NULL,0,0,'Выпустить секретный ключ можно в личном кабинете Яндекс.Кассы, в разделе \"Настройки\"',1,NULL,0,0,1),(423,'id','i18n::field-id',1,20,0,1,NULL,0,0,'',0,NULL,0,0,1),(424,'descr','i18n::field-description',1,24,0,1,NULL,0,0,'',0,NULL,0,0,1),(425,'is_show_rand_banner','i18n::field-is_show_rand_banner',1,1,0,1,NULL,0,1,'',0,NULL,0,0,1),(426,'is_active','i18n::field-is_active',1,1,0,1,NULL,0,0,'',0,NULL,0,0,1),(427,'tags','i18n::field-tags',1,23,0,1,NULL,0,0,'',0,NULL,0,0,0),(428,'url','i18n::field-url',1,20,0,1,NULL,0,0,'',0,NULL,0,0,1),(429,'open_in_new_window','i18n::field-open_in_new_window',1,1,0,1,NULL,0,0,'',0,NULL,0,0,0),(430,'views_count','i18n::field-views_count',1,10,0,1,NULL,0,0,'',0,NULL,0,0,0),(431,'clicks_count','i18n::field-clicks_count',1,10,0,1,NULL,0,0,'',0,NULL,0,0,0),(432,'max_views','i18n::field-max_views',1,10,0,1,NULL,0,0,'',0,NULL,0,0,0),(433,'show_start_date','i18n::field-show_start_date',1,4,1,1,NULL,0,1,'',0,NULL,0,0,0),(434,'show_till_date','i18n::field-show_till_date',1,4,1,1,NULL,0,0,'',0,NULL,0,0,0),(435,'user_tags','i18n::field-user_tags',1,23,0,1,NULL,0,0,'',0,NULL,0,0,0),(436,'view_pages','i18n::field-view_pages',1,22,0,1,NULL,0,0,'',0,NULL,0,0,0),(437,'place','i18n::field-place',1,19,0,1,113,0,0,'',0,NULL,0,0,1),(438,'not_view_pages','i18n::field-not_view_pages',1,22,0,1,NULL,0,0,'',0,NULL,0,0,0),(439,'time_targeting_by_month_days','i18n::field-time_targeting_by_month_days',1,20,0,1,NULL,0,0,'',0,NULL,0,0,0),(440,'time_targeting_by_month','i18n::field-time_targeting_by_month',1,20,0,1,NULL,0,0,'',0,NULL,0,0,0),(441,'time_targeting_by_week_days','i18n::field-time_targeting_by_week_days',1,20,0,1,NULL,0,0,'',0,NULL,0,0,0),(442,'time_targeting_by_hours','i18n::field-time_targeting_by_hours',1,20,0,1,NULL,0,0,'',0,NULL,0,0,0),(443,'time_targeting_is_active','i18n::field-time_targeting_is_active',1,1,1,1,NULL,0,0,'',0,NULL,0,0,0),(444,'city_targeting_city','i18n::field-city',1,18,0,1,11,0,0,'',0,NULL,0,0,0),(445,'city_targeting_is_active','i18n::field-city_targeting_is_active',1,1,0,1,NULL,0,0,'',0,NULL,0,0,0),(446,'priority','i18n::field-order-status-priority',0,10,0,1,NULL,0,0,'',0,NULL,0,0,0),(447,'image','i18n::field-izobrazhenie',1,9,0,1,NULL,0,0,'',0,NULL,0,0,1),(448,'width','i18n::field-width',1,10,0,1,NULL,0,0,'',0,NULL,0,0,0),(449,'height','i18n::field-height',1,10,0,1,NULL,0,0,'',0,NULL,0,0,0),(450,'alt','i18n::field-alt',1,20,0,1,NULL,0,0,'',0,NULL,0,0,0),(451,'swf','i18n::field-swf',1,21,0,1,NULL,0,0,'',0,NULL,0,0,1),(452,'swf_quality','i18n::field-swf_quality',1,18,0,1,9,0,0,'',0,NULL,0,0,0),(453,'html_content','i18n::field-html_content',1,26,0,1,NULL,0,0,'',0,NULL,0,0,1),(454,'picture','i18n::field-picture',0,9,0,1,NULL,0,0,'',0,NULL,0,0,1),(455,'is_hidden','i18n::field-is_hidden',0,1,0,1,NULL,0,0,'',0,NULL,0,0,0),(456,'format','i18n::field-export_format',0,18,0,0,36,0,0,'Формат, в который будут перобразованы данные из UMI.CMS',1,NULL,0,0,1),(457,'elements','i18n::field-included_site_sections',0,22,0,0,NULL,0,0,'Если формат экспорта поддерживает данную функцию, будут экспортированы только выбранные разделы',0,NULL,0,0,1),(458,'excluded_elements','i18n::field-excluded_elements',0,22,0,0,NULL,0,1,'Если формат экспорта поддерживает данную функцию, выбранные разделы экспортированы не будут',0,NULL,0,0,1),(459,'cache_time','i18n::field-cache_time',0,10,0,0,NULL,0,0,'Если задано, то результат будет закеширован на указанное кол-во минут',0,NULL,0,0,0),(460,'source_name','i18n::field-source_name',0,20,0,0,NULL,0,0,'Здесь можно задать имя ресурса для обмена данными, чтобы внешние идентификаторы сущностей были привязаны к нему.',0,NULL,0,0,0),(461,'encoding_export','i18n::field-encoding_import',0,18,0,0,26,0,0,'Кодировка, в которой будут экспортированы данные',0,NULL,0,0,0),(462,'format','i18n::field-data_format',0,18,0,0,35,0,0,'Формат импотрируемых данных',1,NULL,0,0,1),(463,'file','i18n::field-data_file',0,7,0,0,NULL,0,0,'',1,NULL,0,0,1),(464,'elements','i18n::field-site_section_export',0,22,0,0,NULL,0,0,'Если формат импорта поддерживает данную функцию, данные будут импортированы в указанный раздел',0,NULL,0,0,1),(465,'encoding_import','i18n::field-encoding_import',0,18,0,0,26,0,0,'Кодировка импортируемых данных',0,NULL,0,0,0),(466,'source_name','i18n::field-source_name',0,20,0,0,NULL,0,0,'Здесь можно задать имя ресурса для обмена данными, чтобы внешние идентификаторы сущностей были привязаны к нему.',0,NULL,0,0,0),(467,'fs_file','i18n::field-fs_file',1,7,0,1,NULL,0,0,'',0,NULL,0,0,1),(468,'downloads_counter','i18n::field-downloads_counter',1,10,0,1,NULL,0,0,'',0,NULL,0,0,0),(469,'custom_id','i18n::field-sid',1,20,0,0,NULL,0,0,'',0,NULL,0,0,1),(470,'lang_id','i18n::field-lang_id',1,10,0,0,NULL,0,0,'',1,NULL,0,0,1),(471,'domain_id','i18n::field-domain_name',1,5,0,0,NULL,0,0,'',1,NULL,0,0,1),(472,'appoint_service_choice_title','i18n::field-appoint-service-choice-title',1,20,0,1,NULL,0,0,'',0,NULL,0,0,1),(473,'appoint_hint_step_text','i18n::field-appoint-hint-step-text',1,20,0,1,NULL,0,0,'',0,NULL,0,0,1),(474,'appoint_personal_step_title','i18n::field-appoint-personal-step-title',1,20,0,1,NULL,0,0,'',0,NULL,0,0,1),(475,'appoint_personal_choice_title','i18n::field-appoint-personal-choice-title',1,20,0,1,NULL,0,0,'',0,NULL,0,0,1),(476,'appoint_dont_care_button','i18n::field-appoint-dont-care-button',1,20,0,1,NULL,0,0,'',0,NULL,0,0,1),(477,'appoint_dont_care_hint','i18n::field-appoint-dont-care-hint',1,20,0,1,NULL,0,0,'',0,NULL,0,0,1),(478,'appoint_date_step_title','i18n::field-appoint-date-step-title',1,20,0,1,NULL,0,0,'',0,NULL,0,0,1),(479,'appoint_date_choice_title','i18n::field-appoint-date-choice-title',1,20,0,1,NULL,0,0,'',0,NULL,0,0,1),(480,'appoint_confirm_step_title','i18n::field-appoint-confirm-step-title',1,20,0,1,NULL,0,0,'',0,NULL,0,0,1),(481,'appoint_book_time_button','i18n::field-appoint-book-time-button',1,20,0,1,NULL,0,0,'',0,NULL,0,0,1),(482,'appoint_book_time_hint','i18n::field-appoint-book-time-hint',1,20,0,1,NULL,0,0,'',0,NULL,0,0,1),(483,'author','i18n::field-author',0,18,0,1,126,0,0,'',0,NULL,0,0,0);
/*!40000 ALTER TABLE `cms3_object_fields` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cms3_object_fields_restrictions`
--

DROP TABLE IF EXISTS `cms3_object_fields_restrictions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cms3_object_fields_restrictions` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `class_prefix` varchar(64) DEFAULT NULL,
  `title` varchar(255) DEFAULT NULL,
  `field_type_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `Field restriction to field type relation_FK` (`field_type_id`),
  CONSTRAINT `FK_Field restriction to field type relation` FOREIGN KEY (`field_type_id`) REFERENCES `cms3_object_field_types` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cms3_object_fields_restrictions`
--

LOCK TABLES `cms3_object_fields_restrictions` WRITE;
/*!40000 ALTER TABLE `cms3_object_fields_restrictions` DISABLE KEYS */;
INSERT INTO `cms3_object_fields_restrictions` VALUES (1,'systemDomain','Домен системы',10),(2,'objectType','Тип данных',10),(3,'email','E-mail',20),(4,'httpUrl','Web-адрес',20),(5,'webFormMessage','Сообщение вебформы',26),(6,'discount','Размер скидки',8);
/*!40000 ALTER TABLE `cms3_object_fields_restrictions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cms3_object_images`
--

DROP TABLE IF EXISTS `cms3_object_images`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cms3_object_images` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `obj_id` int(10) unsigned DEFAULT NULL,
  `field_id` int(10) unsigned DEFAULT NULL,
  `src` varchar(500) DEFAULT NULL,
  `alt` varchar(255) DEFAULT NULL,
  `title` varchar(255) DEFAULT NULL,
  `ord` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `load field value` (`obj_id`,`field_id`),
  KEY `field_id` (`field_id`),
  KEY `obj_id` (`obj_id`),
  KEY `src` (`src`(255)),
  KEY `alt` (`alt`),
  KEY `ord` (`ord`),
  CONSTRAINT `object field content to field` FOREIGN KEY (`field_id`) REFERENCES `cms3_object_fields` (`id`) ON DELETE CASCADE,
  CONSTRAINT `object field content to object` FOREIGN KEY (`obj_id`) REFERENCES `cms3_objects` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cms3_object_images`
--

LOCK TABLES `cms3_object_images` WRITE;
/*!40000 ALTER TABLE `cms3_object_images` DISABLE KEYS */;
INSERT INTO `cms3_object_images` VALUES (1,625,246,'./images/cms/data/b048adbe43457545381673e7cf2d9d51.jpg','','',1),(2,629,246,'./images/cms/data/17b0dde2b78fe50319181b74ebd5d8d3.jpg','','',1),(3,630,246,'./images/cms/data/aleksandr-kuricyn-i-shvarcenegger.jpg','','',1),(4,631,246,'./images/cms/data/e3d71ca4-96c7-40cb-a126-d6953fb3c788.jpg','','',1),(6,632,246,'./images/cms/data/714c9d2a045fd64730d692e3f419cc61.jpg','','',1),(8,633,246,'./images/cms/data/007.png','','',1),(10,634,246,'./images/cms/data/23705de020d91f66a81b18dbf12544a8.jpg','','',1),(12,635,246,'./images/cms/data/avatar_107_max.jpg','','',1);
/*!40000 ALTER TABLE `cms3_object_images` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cms3_object_offer_id_list`
--

DROP TABLE IF EXISTS `cms3_object_offer_id_list`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cms3_object_offer_id_list` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `obj_id` int(10) unsigned NOT NULL,
  `field_id` int(10) unsigned NOT NULL,
  `offer_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `cms3_object_offer_id_list load field value` (`obj_id`,`field_id`),
  KEY `cms3_object_offer_id_list field_id` (`field_id`),
  KEY `cms3_object_offer_id_list obj_id` (`obj_id`),
  KEY `cms3_object_offer_id_list offer_id` (`offer_id`),
  CONSTRAINT `cms3_object_offer_id_list field id` FOREIGN KEY (`field_id`) REFERENCES `cms3_object_fields` (`id`) ON DELETE CASCADE,
  CONSTRAINT `cms3_object_offer_id_list object id` FOREIGN KEY (`obj_id`) REFERENCES `cms3_objects` (`id`) ON DELETE CASCADE,
  CONSTRAINT `cms3_object_offer_id_list offer id` FOREIGN KEY (`offer_id`) REFERENCES `cms3_offer_list` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cms3_object_offer_id_list`
--

LOCK TABLES `cms3_object_offer_id_list` WRITE;
/*!40000 ALTER TABLE `cms3_object_offer_id_list` DISABLE KEYS */;
/*!40000 ALTER TABLE `cms3_object_offer_id_list` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cms3_object_type_tree`
--

DROP TABLE IF EXISTS `cms3_object_type_tree`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cms3_object_type_tree` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `parent_id` int(10) unsigned DEFAULT NULL,
  `child_id` int(10) unsigned DEFAULT NULL,
  `level` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique parent-child object type relation` (`parent_id`,`child_id`),
  KEY `Object type id from child_id` (`child_id`),
  CONSTRAINT `Object type id from child_id` FOREIGN KEY (`child_id`) REFERENCES `cms3_object_types` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `Object type id from parent_id` FOREIGN KEY (`parent_id`) REFERENCES `cms3_object_types` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=226 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cms3_object_type_tree`
--

LOCK TABLES `cms3_object_type_tree` WRITE;
/*!40000 ALTER TABLE `cms3_object_type_tree` DISABLE KEYS */;
INSERT INTO `cms3_object_type_tree` VALUES (1,NULL,1,0),(2,NULL,2,1),(3,1,2,1),(4,NULL,3,0),(5,NULL,4,1),(6,1,4,1),(7,NULL,5,1),(8,1,5,1),(9,NULL,6,1),(10,1,6,1),(11,NULL,7,1),(12,3,7,1),(13,NULL,8,1),(14,1,8,1),(15,NULL,9,1),(16,1,9,1),(17,NULL,10,1),(18,1,10,1),(19,NULL,11,1),(20,1,11,1),(21,NULL,12,1),(22,1,12,1),(23,NULL,13,1),(24,1,13,1),(25,NULL,14,1),(26,1,14,1),(27,NULL,15,1),(28,1,15,1),(29,NULL,16,1),(30,1,16,1),(31,NULL,17,1),(32,1,17,1),(33,NULL,18,1),(34,1,18,1),(35,NULL,19,1),(36,1,19,1),(37,NULL,20,1),(38,1,20,1),(39,NULL,21,0),(40,NULL,22,1),(41,1,22,1),(42,NULL,23,1),(43,1,23,1),(44,NULL,24,1),(45,1,24,1),(46,NULL,25,1),(47,1,25,1),(48,NULL,26,1),(49,1,26,1),(50,NULL,27,1),(51,1,27,1),(52,NULL,28,1),(53,1,28,1),(54,NULL,29,1),(55,1,29,1),(56,NULL,30,1),(57,1,30,1),(58,NULL,31,1),(59,1,31,1),(60,NULL,32,0),(61,NULL,33,1),(62,1,33,1),(63,NULL,34,0),(64,NULL,35,0),(65,NULL,36,0),(66,NULL,37,1),(67,3,37,1),(68,NULL,38,2),(69,3,38,2),(70,37,38,2),(71,NULL,39,0),(72,NULL,40,1),(73,1,40,1),(74,NULL,41,1),(75,1,41,1),(76,NULL,42,0),(77,NULL,43,1),(78,1,43,1),(79,NULL,44,1),(80,1,44,1),(81,NULL,45,1),(82,1,45,1),(83,NULL,46,1),(84,1,46,1),(85,NULL,47,0),(86,NULL,48,1),(87,1,48,1),(88,NULL,49,1),(89,1,49,1),(90,NULL,50,1),(91,1,50,1),(92,NULL,51,0),(93,NULL,52,1),(94,1,52,1),(95,NULL,53,0),(96,NULL,54,0),(97,NULL,55,1),(98,1,55,1),(99,NULL,56,1),(100,32,56,1),(101,NULL,57,1),(102,34,57,1),(103,NULL,58,0),(104,NULL,59,1),(105,1,59,1),(106,NULL,60,1),(107,3,60,1),(108,NULL,61,1),(109,3,61,1),(110,NULL,62,0),(111,NULL,63,1),(112,3,63,1),(113,NULL,64,0),(114,NULL,65,1),(115,3,65,1),(116,NULL,66,1),(117,3,66,1),(118,NULL,67,1),(119,3,67,1),(120,NULL,68,1),(121,3,68,1),(122,NULL,69,1),(123,3,69,1),(124,NULL,70,1),(125,3,70,1),(126,NULL,71,0),(127,NULL,72,1),(128,3,72,1),(129,NULL,73,1),(130,3,73,1),(131,NULL,74,0),(132,NULL,75,0),(133,NULL,76,0),(134,NULL,77,1),(135,3,77,1),(136,NULL,78,1),(137,3,78,1),(138,NULL,79,1),(139,3,79,1),(140,NULL,80,1),(141,3,80,1),(142,NULL,81,1),(143,3,81,1),(144,NULL,82,0),(145,NULL,83,0),(146,NULL,84,0),(147,NULL,85,0),(148,NULL,86,1),(149,3,86,1),(150,NULL,87,1),(151,3,87,1),(152,NULL,88,1),(153,1,88,1),(154,NULL,89,1),(155,32,89,1),(156,NULL,90,1),(157,34,90,1),(158,NULL,91,1),(159,34,91,1),(160,NULL,92,1),(161,34,92,1),(162,NULL,93,1),(163,34,93,1),(164,NULL,94,1),(165,34,94,1),(166,NULL,95,1),(167,34,95,1),(168,NULL,96,1),(169,51,96,1),(170,NULL,97,1),(171,51,97,1),(172,NULL,98,1),(173,51,98,1),(174,NULL,99,1),(175,51,99,1),(176,NULL,100,1),(177,47,100,1),(178,NULL,101,1),(179,47,101,1),(180,NULL,102,1),(181,47,102,1),(182,NULL,103,1),(183,47,103,1),(184,NULL,104,1),(185,47,104,1),(186,NULL,105,1),(187,47,105,1),(188,NULL,106,1),(189,47,106,1),(190,NULL,107,1),(191,47,107,1),(192,NULL,108,1),(193,47,108,1),(194,NULL,109,1),(195,47,109,1),(196,NULL,110,1),(197,47,110,1),(198,NULL,111,1),(199,47,111,1),(200,NULL,112,1),(201,47,112,1),(202,NULL,113,1),(203,1,113,1),(204,NULL,114,0),(205,NULL,115,1),(206,114,115,1),(207,NULL,116,1),(208,114,116,1),(209,NULL,117,1),(210,114,117,1),(211,NULL,118,1),(212,1,118,1),(213,NULL,119,0),(214,NULL,120,0),(215,NULL,121,1),(216,3,121,1),(217,NULL,122,0),(218,NULL,123,1),(219,1,123,1),(220,NULL,124,1),(221,1,124,1),(222,NULL,125,1),(223,3,125,1),(224,NULL,126,1),(225,1,126,1);
/*!40000 ALTER TABLE `cms3_object_type_tree` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cms3_object_types`
--

DROP TABLE IF EXISTS `cms3_object_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cms3_object_types` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `guid` varchar(64) DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `is_locked` tinyint(1) DEFAULT '0',
  `parent_id` int(10) unsigned DEFAULT NULL,
  `is_guidable` tinyint(1) DEFAULT '0',
  `is_public` tinyint(1) DEFAULT '0',
  `hierarchy_type_id` int(10) unsigned DEFAULT NULL,
  `sortable` tinyint(4) DEFAULT '0',
  `domain_id` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `hierarchy_type_id` (`hierarchy_type_id`),
  KEY `parent_id` (`parent_id`),
  KEY `is_public` (`is_public`),
  KEY `name` (`name`),
  KEY `is_locked` (`is_locked`),
  KEY `is_guidable` (`is_guidable`),
  KEY `guid` (`guid`),
  KEY `cms3_object_types domain id` (`domain_id`),
  CONSTRAINT `cms3_object_types domain id` FOREIGN KEY (`domain_id`) REFERENCES `cms3_domains` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=127 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cms3_object_types`
--

LOCK TABLES `cms3_object_types` WRITE;
/*!40000 ALTER TABLE `cms3_object_types` DISABLE KEYS */;
INSERT INTO `cms3_object_types` VALUES (1,'root-guides-type','i18n::object-type-spravochniki',1,0,0,0,0,0,NULL),(2,'de8627f75ba1abcfafd00a0e75ad189105cfdc21','i18n::object-type-status_stranicy',1,1,1,1,0,0,NULL),(3,'root-pages-type','i18n::object-type-razdel_sajta',1,0,0,0,0,0,NULL),(4,'fe5dbbcea5ce7e2988b8c69bcfdfde8904aabc1f','i18n::object-type-pol',1,1,1,0,0,0,NULL),(5,'472b07b9fcf2c2451e8781e944bf5f77cd8457c8','i18n::object-type-tip_rss',1,1,1,1,0,0,NULL),(6,'news-rss-source-charset','i18n::object-type-news-rss-source-charset',1,1,1,1,0,0,NULL),(7,'news-rubric','i18n::object-type-news-rubric',1,3,1,0,1,0,NULL),(8,'12c6fc06c99a462375eeb3f43dfd832b08ca9e17','i18n::object-type-rss-lenta',1,1,1,1,0,0,NULL),(9,'banners-banner-swf-quality','i18n::object-type-kachestvo_fleshki',1,1,1,0,0,0,NULL),(10,'d69b923df6140a16aefc89546a384e0493641fbe','i18n::object-type-strany',1,1,1,1,0,0,NULL),(11,'sytem-citylist','i18n::object-type-spisok_gorodov_dlya_geo',1,1,1,0,0,0,NULL),(12,'96e388c0b3b7fd874b48621e850335a8f06ca58d','i18n::object-type-price_modifier',1,1,1,1,0,0,NULL),(13,'6fe3dfe314684a658c1b19ca7a8e3abd29afe23e','i18n::object-type-validation_rule',1,1,1,1,0,0,NULL),(14,'cd8b7a4b8bb9bbf442a9d50fa465fe0e9d868a13','i18n::object-type-dispatch_type',1,1,1,0,0,0,NULL),(15,'a1496d4ad0a359b6fe93d819e4a2141bd9d9ac35','i18n::object-type-carriage_type',1,1,1,0,0,0,NULL),(16,'c9264fc806cdb67dc2080db570871067a6134c2d','i18n::object-type-hours',1,1,1,0,0,0,NULL),(17,'d528edaa45e66e08a9ece98272130b42e77cef55','i18n::object-type-days',1,1,1,0,0,0,NULL),(18,'blacklist','i18n::object-type-blacklist',1,1,1,1,0,0,NULL),(19,'social_categories_vkontakte','i18n::object-type-social_categories_vkontakte',1,1,1,1,0,0,NULL),(20,'emarket-payment-signing-types','i18n::object-type-signingtypes',0,1,1,1,0,0,NULL),(21,'emarket-currency','i18n::object-type-valyuta',1,0,1,1,2,0,NULL),(22,'emarket-order-credit-status','i18n::object-type-creditstatuses',0,1,1,1,0,0,NULL),(23,'emarket-mobile-platform','i18n::object-type-mobile-platform',1,1,1,1,0,0,NULL),(24,'emarket-mobile-devices','i18n::object-type-mobile-devices',1,1,1,1,0,0,NULL),(25,'emarket-purchase-oneclick','i18n::object-type-emarket-purchase-oneclick',1,1,1,0,0,0,NULL),(26,'exchange-encodings','i18n::object-type-exchange-encodings',0,1,1,1,0,0,NULL),(27,'tax-rate-guide','i18n::object-type-tax-rate',1,1,1,0,0,0,NULL),(28,'payment_subject','Предмет расчета',0,1,1,0,0,0,NULL),(29,'payment_mode','Способ расчета',0,1,1,0,0,0,NULL),(30,'emarket-discounttype','i18n::object-type-discounttype',1,1,1,1,3,0,NULL),(31,'emarket-discountmodificatortype','i18n::object-type-discountmodificatortype',1,1,1,1,4,0,NULL),(32,'81755a2845e39420c81902a3ce83dff1cfc782e7','i18n::object-type-discount_price_modifier',1,0,0,0,0,0,NULL),(33,'emarket-discountruletype','i18n::object-type-discountruletype',1,1,1,1,5,0,NULL),(34,'190c4a70068f9453e2320b650e94869a1306adb0','i18n::object-type-discountrules',1,0,0,0,0,0,NULL),(35,'exchange-format-import','i18n::object-type-import_format',1,0,1,0,0,0,NULL),(36,'exchange-format-export','i18n::object-type-export_format',1,0,1,0,0,0,NULL),(37,'social_networks-network','i18n::object-type-network',1,3,1,1,6,0,NULL),(38,'social_networks-network-vkontakte','i18n::object-type-vkontakte',1,37,0,0,7,0,NULL),(39,'users-users','i18n::object-type-users-users',1,0,1,0,8,0,NULL),(40,'emarket-deliveryaddress','i18n::object-type-eshop-address',1,1,1,0,9,0,NULL),(41,'emarket-itemtype','i18n::object-type-itemtype',1,1,1,1,10,0,NULL),(42,'emarket-discount','i18n::object-type-discount',1,0,1,1,11,0,NULL),(43,'emarket-itemoption','i18n::object-type-itemoption',1,1,1,1,12,0,NULL),(44,'emarket-orderitem','i18n::object-type-eshop-order_item',1,1,1,0,13,0,NULL),(45,'emarket-orderstatus','i18n::object-type-eshop-order_status',1,1,1,0,14,0,NULL),(46,'emarket-paymenttype','i18n::object-type-paymenttype',1,1,1,0,15,0,NULL),(47,'emarket-payment','i18n::object-type-payment',1,0,1,0,16,0,NULL),(48,'emarket-orderpaymentstatus','i18n::object-type-orderpaymentstatus',1,1,1,0,17,0,NULL),(49,'emarket-legalperson','i18n::object-type-legalperson',1,1,1,0,18,0,NULL),(50,'emarket-deliverytype','i18n::object-type-deliverytype',1,1,1,1,19,0,NULL),(51,'emarket-delivery','i18n::object-type-delivery',1,0,1,0,20,0,NULL),(52,'emarket-orderdeliverystatus','i18n::object-type-orderdeliverystatus',1,1,1,0,21,0,NULL),(53,'emarket-order','i18n::object-type-order',1,0,1,0,22,0,NULL),(54,'users-user','i18n::object-type-users-user',1,0,1,0,23,0,NULL),(55,'emarket-store','i18n::object-type-store',1,1,1,1,24,0,NULL),(56,'emarket-discountmodificator-768','i18n::object-type-order_summ_percent',1,32,1,1,25,0,NULL),(57,'emarket-discountrule-798','i18n::object-type-users_discount',1,34,0,0,26,0,NULL),(58,'menu-menu','i18n::object-type-menu',0,0,0,0,27,0,NULL),(59,'news-subject','i18n::object-type-news-subject',1,1,1,1,28,0,NULL),(60,'news-item','i18n::object-type-news-item',1,3,0,0,29,0,NULL),(61,'content-page','i18n::object-type-content-',1,3,0,0,30,0,NULL),(62,'content-ticket','i18n::object-type-content-ticket',1,0,0,0,31,0,NULL),(63,'blogs20-blog','i18n::object-type-blogs-blog',1,3,1,0,32,0,NULL),(64,'users-author','i18n::object-type-users-author',1,0,1,0,33,0,NULL),(65,'blogs20-comment','i18n::object-type-blogs20-comment',1,3,1,0,34,0,NULL),(66,'blogs20-post','i18n::object-type-blogs20-post',1,3,1,0,35,0,NULL),(67,'forum-conf','i18n::object-type-forum-conf',1,3,0,0,36,0,NULL),(68,'forum-topic','i18n::object-type-forum-topic',1,3,0,0,37,0,NULL),(69,'forum-message','i18n::object-type-forum-message',1,3,0,0,38,0,NULL),(70,'comments-comment','i18n::object-type-comments-comment',1,3,0,0,39,0,NULL),(71,'vote-pollitem','i18n::object-type-vote-poll_item',1,0,1,0,40,0,NULL),(72,'vote-poll','i18n::object-type-vote-poll',1,3,0,0,41,0,NULL),(73,'webforms-page','i18n::object-type-webforms-page',1,3,1,0,42,0,NULL),(74,'webforms-form','i18n::object-type-webforms-form',1,0,0,0,43,0,NULL),(75,'webforms-template','i18n::object-type-webforms-template',1,0,0,0,44,0,NULL),(76,'webforms-address','i18n::object-type-webforms-address',1,0,0,0,45,0,NULL),(77,'photoalbum-album','i18n::object-type-photoalbum-album',1,3,0,0,46,0,NULL),(78,'photoalbum-photo','i18n::object-type-photoalbum-photo',1,3,0,0,47,0,NULL),(79,'faq-project','i18n::object-type-faq-project',1,3,0,1,48,0,NULL),(80,'faq-category','i18n::object-type-faq-category',1,3,0,1,49,0,NULL),(81,'faq-question','i18n::object-type-faq-question',1,3,0,0,50,0,NULL),(82,'dispatches-dispatch','i18n::object-type-dispatches-dispatch',1,0,1,0,51,0,NULL),(83,'dispatches-release','i18n::object-type-dispatches-release',1,0,1,0,52,0,NULL),(84,'dispatches-message','i18n::object-type-dispatches-message',1,0,1,0,53,0,NULL),(85,'dispatches-subscriber','i18n::object-type-dispatches-subscriber',1,0,1,0,54,0,NULL),(86,'catalog-category','i18n::object-type-catalog-category',1,3,0,0,55,0,NULL),(87,'catalog-object','i18n::object-type-catalog-object',1,3,0,0,56,0,NULL),(88,'emarket-customer','i18n::object-type-customer',1,1,1,1,57,0,NULL),(89,'emarket-discountmodificator-800','i18n::object-type-fixed_discount',1,32,1,1,25,0,NULL),(90,'emarket-discountrule-777','i18n::object-type-special_items_discount',1,34,0,0,26,0,NULL),(91,'emarket-discountrule-794','i18n::object-type-time_interval',1,34,0,0,26,0,NULL),(92,'emarket-discountrule-795','i18n::object-type-order_summ',1,34,0,0,26,0,NULL),(93,'emarket-discountrule-796','i18n::object-type-customer_summ',1,34,0,0,26,0,NULL),(94,'emarket-discountrule-797','i18n::object-type-users_group_discount',1,34,0,0,26,0,NULL),(95,'emarket-discountrule-799','i18n::object-type-related_items_discount',1,34,0,0,26,0,NULL),(96,'emarket-delivery-783','i18n::object-type-pickup',1,51,0,0,20,0,NULL),(97,'emarket-delivery-784','i18n::object-type-courier_deliver_spb',1,51,0,0,20,0,NULL),(98,'emarket-delivery-808','i18n::object-type-mail',1,51,0,0,20,0,NULL),(99,'emarket-delivery-842','i18n::object-type-apiship',1,51,0,0,20,0,NULL),(100,'emarket-payment-791','i18n::object-type-sales_draft',1,47,0,0,16,0,NULL),(101,'emarket-payment-801','i18n::object-type-pay_online',1,47,0,0,16,0,NULL),(102,'emarket-payment-802','i18n::object-type-courier',1,47,0,0,16,0,NULL),(103,'emarket-payment-812','i18n::object-type-robokassa',1,47,0,0,16,0,NULL),(104,'emarket-payment-813','i18n::object-type-rbk_money',1,47,0,0,16,0,NULL),(105,'emarket-payment-816','i18n::object-type-legal_person_account',1,47,0,0,16,0,NULL),(106,'emarket-payment-payanyway','i18n::object-type-payanyway',1,47,0,0,16,0,NULL),(107,'emarket-payment-dengionline','i18n::object-type-dengionline',1,47,0,0,16,0,NULL),(108,'emarket-payment-kvk','КупиВКредит',1,47,0,0,16,0,NULL),(109,'emarket-payment-acquiropay','i18n::object-type-emarket-payment-acquiropay',1,47,0,0,16,0,NULL),(110,'emarket-payment-yandex30','i18n::object-type-emarket-payment-yandex30',1,47,0,0,16,0,NULL),(111,'emarket-payment-paypal','i18n::object-type-emarket-payment-paypal',1,47,0,0,16,0,NULL),(112,'emarket-payment-yandex-kassa','i18n::object-type-emarket-payment-yandex-kassa',1,47,0,0,16,0,NULL),(113,'banners-place','i18n::object-type-banners-place',1,1,1,0,58,0,NULL),(114,'banners-banner','i18n::object-type-banners-banner',1,0,0,0,59,0,NULL),(115,'banners-banner-image','i18n::object-type-banners-banner-image',1,114,0,0,59,0,NULL),(116,'banners-banner-swf','i18n::object-type-banners-banner-swf',1,114,0,0,59,0,NULL),(117,'banners-banner-html','i18n::object-type-banners-banner-html',1,114,0,0,59,0,NULL),(118,'users-avatar','i18n::object-type-users-avatar',1,1,1,1,60,0,NULL),(119,'exchange-export','i18n::object-type-export',1,0,0,0,61,0,NULL),(120,'exchange-import','i18n::object-type-import',1,0,0,0,62,0,NULL),(121,'filemanager-sharedfile','i18n::object-type-filemanager-shared_file',1,3,0,0,63,0,NULL),(122,'root-settings-type','i18n::object-type-root-settings-type',1,0,0,0,64,0,NULL),(123,'ip-blacklist','i18n::object-type-ip_blacklist',1,1,1,1,65,0,NULL),(124,'ip-whitelist','i18n::object-type-ip_whitelist',1,1,1,1,66,0,NULL),(125,'appointment-page','Страница с записью на прием',1,3,0,0,67,0,NULL),(126,'','Справочник для поля \"Автор\"',0,1,1,1,0,0,NULL);
/*!40000 ALTER TABLE `cms3_object_types` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cms3_objects`
--

DROP TABLE IF EXISTS `cms3_objects`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cms3_objects` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `guid` varchar(64) DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `is_locked` tinyint(1) DEFAULT NULL,
  `type_id` int(10) unsigned DEFAULT NULL,
  `owner_id` int(10) unsigned DEFAULT NULL,
  `ord` int(10) unsigned DEFAULT '0',
  `updatetime` int(11) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `Object to type relation_FK` (`type_id`),
  KEY `name` (`name`),
  KEY `owner_id` (`owner_id`),
  KEY `is_locked` (`is_locked`),
  KEY `ord` (`ord`),
  KEY `guid` (`guid`),
  KEY `updatetime` (`updatetime`),
  CONSTRAINT `FK_Object to type relation` FOREIGN KEY (`type_id`) REFERENCES `cms3_object_types` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=636 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cms3_objects`
--

LOCK TABLES `cms3_objects` WRITE;
/*!40000 ALTER TABLE `cms3_objects` DISABLE KEYS */;
INSERT INTO `cms3_objects` VALUES (1,'social_networks-network-27915','i18n::object-vkontakte',1,38,0,1,1553121555),(2,'84a36e2847c33ac03a7223b57b0c864b80ab26c8','i18n::object-rss',0,5,0,1,1553121555),(3,'a35ff773f425e44df36c1cc68a415d92318b19ac','i18n::object-atom',0,5,0,2,1553121555),(4,'e99ecbbec4c871f3fb63c3cc85796e177d017614','i18n::object-male',0,4,0,1,1553121555),(5,'7b04a4565f37a07f1c2ee54be8286017de6c56df','i18n::object-female',0,4,0,2,1553121555),(6,'swf-banner-quality-low','i18n::object-low',0,9,0,1,1553121555),(7,'swf-banner-quality-medium','i18n::object-medium',0,9,0,2,1553121555),(8,'swf-banner-quality-high','i18n::object-height',0,9,0,3,1553121555),(9,'a1e3ae17e80ba2b4a3ddb1b855430346f74b8d48','i18n::object-usa',1,10,0,1,1553121555),(10,'e9aa8c23a339224b25945aa9e99f09f578bdd483','i18n::object-russia',1,10,0,2,1553121555),(11,'sytem-citylist-26905','Москва',0,11,0,1,1553121555),(12,'sytem-citylist-26906','Санкт-Петербург',0,11,0,2,1553121555),(13,'sytem-citylist-26907','Новосибирск',0,11,0,3,1553121555),(14,'sytem-citylist-26908','Екатеринбург',0,11,0,4,1553121555),(15,'sytem-citylist-26909','Нижний Новгород',0,11,0,5,1553121555),(16,'sytem-citylist-26910','Самара',0,11,0,6,1553121555),(17,'sytem-citylist-26911','Омск',0,11,0,7,1553121555),(18,'sytem-citylist-26912','Казань',0,11,0,8,1553121555),(19,'sytem-citylist-26913','Челябинск',0,11,0,9,1553121555),(20,'sytem-citylist-26914','Ростов-на-Дону',0,11,0,10,1553121555),(21,'sytem-citylist-26915','Уфа',0,11,0,11,1553121555),(22,'sytem-citylist-26916','Пермь',0,11,0,12,1553121555),(23,'sytem-citylist-26917','Волгоград',0,11,0,13,1553121555),(24,'sytem-citylist-26918','Красноярск',0,11,0,14,1553121555),(25,'sytem-citylist-26919','Саратов',0,11,0,15,1553121555),(26,'sytem-citylist-26920','Воронеж',0,11,0,16,1553121555),(27,'sytem-citylist-26921','Краснодар',0,11,0,17,1553121555),(28,'sytem-citylist-26922','Тольятти',0,11,0,18,1553121555),(29,'sytem-citylist-26923','Ижевск',0,11,0,19,1553121555),(30,'sytem-citylist-26924','Ульяновск',0,11,0,20,1553121555),(31,'sytem-citylist-26925','Ярославль',0,11,0,21,1553121555),(32,'sytem-citylist-26926','Барнаул',0,11,0,22,1553121555),(33,'sytem-citylist-26927','Владивосток',0,11,0,23,1553121555),(34,'sytem-citylist-26928','Хабаровск',0,11,0,24,1553121555),(35,'sytem-citylist-26929','Иркутск',0,11,0,25,1553121555),(36,'sytem-citylist-26930','Новокузнецк',0,11,0,26,1553121555),(37,'sytem-citylist-26931','Тюмень',0,11,0,27,1553121555),(38,'sytem-citylist-26932','Оренбург',0,11,0,28,1553121555),(39,'sytem-citylist-26933','Кемерово',0,11,0,29,1553121555),(40,'sytem-citylist-26934','Рязань',0,11,0,30,1553121555),(41,'sytem-citylist-26935','Пенза',0,11,0,31,1553121555),(42,'sytem-citylist-26936','Набережные Челны',0,11,0,32,1553121555),(43,'sytem-citylist-26937','Тула',0,11,0,33,1553121555),(44,'sytem-citylist-26938','Липецк',0,11,0,34,1553121555),(45,'sytem-citylist-26939','Астрахань',0,11,0,35,1553121555),(46,'sytem-citylist-26940','Томск',0,11,0,36,1553121555),(47,'sytem-citylist-26941','Махачкала',0,11,0,37,1553121555),(48,'sytem-citylist-26942','Киров',0,11,0,38,1553121555),(49,'sytem-citylist-26943','Чебоксары',0,11,0,39,1553121555),(50,'sytem-citylist-26944','Калининград',0,11,0,40,1553121555),(51,'sytem-citylist-26945','Брянск',0,11,0,41,1553121555),(52,'sytem-citylist-26946','Магнитогорск',0,11,0,42,1553121555),(53,'sytem-citylist-26947','Иваново',0,11,0,43,1553121555),(54,'sytem-citylist-26948','Курск',0,11,0,44,1553121555),(55,'sytem-citylist-26949','Тверь',0,11,0,45,1553121555),(56,'sytem-citylist-26950','Нижний Тагил',0,11,0,46,1553121555),(57,'sytem-citylist-26951','Ставрополь',0,11,0,47,1553121555),(58,'sytem-citylist-26952','Архангельск',0,11,0,48,1553121555),(59,'sytem-citylist-26953','Белгород',0,11,0,49,1553121555),(60,'sytem-citylist-26954','Улан-Удэ',0,11,0,50,1553121555),(61,'sytem-citylist-26955','Владимир',0,11,0,51,1553121555),(62,'sytem-citylist-26956','Сочи',0,11,0,52,1553121555),(63,'sytem-citylist-26957','Калуга',0,11,0,53,1553121555),(64,'sytem-citylist-26958','Курган',0,11,0,54,1553121555),(65,'sytem-citylist-26959','Орёл',0,11,0,55,1553121555),(66,'sytem-citylist-26960','Смоленск',0,11,0,56,1553121555),(67,'sytem-citylist-26961','Мурманск',0,11,0,57,1553121555),(68,'sytem-citylist-26962','Владикавказ',0,11,0,58,1553121555),(69,'sytem-citylist-26963','Череповец',0,11,0,59,1553121555),(70,'sytem-citylist-26964','Волжский',0,11,0,60,1553121555),(71,'sytem-citylist-26965','Чита',0,11,0,61,1553121555),(72,'sytem-citylist-26966','Саранск',0,11,0,62,1553121555),(73,'sytem-citylist-26967','Сургут',0,11,0,63,1553121555),(74,'sytem-citylist-26968','Вологда',0,11,0,64,1553121555),(75,'sytem-citylist-26969','Тамбов',0,11,0,65,1553121555),(76,'sytem-citylist-26970','Кострома',0,11,0,66,1553121555),(77,'sytem-citylist-26971','Комсомольск-на-Амуре',0,11,0,67,1553121555),(78,'sytem-citylist-26972','Нальчик',0,11,0,68,1553121555),(79,'sytem-citylist-26973','Петрозаводск',0,11,0,69,1553121555),(80,'sytem-citylist-26974','Стерлитамак',0,11,0,70,1553121555),(81,'sytem-citylist-26975','Таганрог',0,11,0,71,1553121555),(82,'sytem-citylist-26976','Братск',0,11,0,72,1553121555),(83,'sytem-citylist-26977','Дзержинск',0,11,0,73,1553121555),(84,'sytem-citylist-26978','Йошкар-Ола',0,11,0,74,1553121555),(85,'sytem-citylist-26979','Орск',0,11,0,75,1553121555),(86,'sytem-citylist-26980','Шахты',0,11,0,76,1553121555),(87,'sytem-citylist-26981','Якутск',0,11,0,77,1553121555),(88,'sytem-citylist-26982','Ангарск',0,11,0,78,1553121555),(89,'sytem-citylist-26983','Нижневартовск',0,11,0,79,1553121555),(90,'sytem-citylist-26984','Сыктывкар',0,11,0,80,1553121555),(91,'sytem-citylist-26985','Новороссийск',0,11,0,81,1553121555),(92,'sytem-citylist-26986','Нижнекамск',0,11,0,82,1553121555),(93,'sytem-citylist-26987','Бийск',0,11,0,83,1553121555),(94,'sytem-citylist-26988','Грозный',0,11,0,84,1553121555),(95,'sytem-citylist-26989','Старый Оскол',0,11,0,85,1553121555),(96,'sytem-citylist-26990','Великий Новгород',0,11,0,86,1553121555),(97,'sytem-citylist-26991','Прокопьевск',0,11,0,87,1553121555),(98,'sytem-citylist-26992','Рыбинск',0,11,0,88,1553121555),(99,'sytem-citylist-26993','Норильск',0,11,0,89,1553121555),(100,'sytem-citylist-26994','Благовещенск',0,11,0,90,1553121555),(101,'sytem-citylist-26995','Энгельс',0,11,0,91,1553121555),(102,'sytem-citylist-26996','Балаково',0,11,0,92,1553121555),(103,'sytem-citylist-26997','Петропавловск-Камчатский',0,11,0,93,1553121555),(104,'sytem-citylist-26998','Псков',0,11,0,94,1553121555),(105,'sytem-citylist-26999','Северодвинск',0,11,0,95,1553121555),(106,'sytem-citylist-27000','Армавир',0,11,0,96,1553121555),(107,'sytem-citylist-27001','Златоуст',0,11,0,97,1553121555),(108,'sytem-citylist-27002','Балашиха',0,11,0,98,1553121555),(109,'sytem-citylist-27003','Каменск-Уральский',0,11,0,99,1553121555),(110,'sytem-citylist-27004','Химки',0,11,0,100,1553121555),(111,'sytem-citylist-27005','Сызрань',0,11,0,101,1553121555),(112,'sytem-citylist-27006','Подольск',0,11,0,102,1553121555),(113,'sytem-citylist-27007','Новочеркасск',0,11,0,103,1553121555),(114,'sytem-citylist-27008','Королёв',0,11,0,104,1553121555),(115,'sytem-citylist-27009','Южно-Сахалинск',0,11,0,105,1553121555),(116,'sytem-citylist-27010','Волгодонск',0,11,0,106,1553121555),(117,'sytem-citylist-27011','Находка',0,11,0,107,1553121555),(118,'sytem-citylist-27012','Березники',0,11,0,108,1553121555),(119,'sytem-citylist-27013','Абакан',0,11,0,109,1553121555),(120,'sytem-citylist-27014','Мытищи',0,11,0,110,1553121555),(121,'sytem-citylist-27015','Люберцы',0,11,0,111,1553121555),(122,'sytem-citylist-27016','Рубцовск',0,11,0,112,1553121555),(123,'sytem-citylist-27017','Майкоп',0,11,0,113,1553121555),(124,'sytem-citylist-27018','Салават',0,11,0,114,1553121555),(125,'sytem-citylist-27019','Уссурийск',0,11,0,115,1553121555),(126,'sytem-citylist-27020','Миасс',0,11,0,116,1553121555),(127,'sytem-citylist-27021','Ковров',0,11,0,117,1553121556),(128,'sytem-citylist-27022','Коломна',0,11,0,118,1553121556),(129,'sytem-citylist-27023','Электросталь',0,11,0,119,1553121556),(130,'sytem-citylist-27024','Альметьевск',0,11,0,120,1553121556),(131,'sytem-citylist-27025','Пятигорск',0,11,0,121,1553121556),(132,'sytem-citylist-27026','Копейск',0,11,0,122,1553121556),(133,'sytem-citylist-27027','Первоуральск',0,11,0,123,1553121556),(134,'sytem-citylist-27028','Назрань',0,11,0,124,1553121556),(135,'sytem-citylist-27029','Одинцово',0,11,0,125,1553121556),(136,'sytem-citylist-27030','Невинномысск',0,11,0,126,1553121556),(137,'sytem-citylist-27031','Кисловодск',0,11,0,127,1553121556),(138,'sytem-citylist-27032','Димитровград',0,11,0,128,1553121556),(139,'sytem-citylist-27033','Хасавюрт',0,11,0,129,1553121556),(140,'sytem-citylist-27034','Новочебоксарск',0,11,0,130,1553121556),(141,'sytem-citylist-27035','Новомосковск',0,11,0,131,1553121556),(142,'sytem-citylist-27036','Серпухов',0,11,0,132,1553121556),(143,'sytem-citylist-27037','Орехово-Зуево',0,11,0,133,1553121556),(144,'sytem-citylist-27038','Муром',0,11,0,134,1553121556),(145,'sytem-citylist-27039','Камышин',0,11,0,135,1553121556),(146,'sytem-citylist-27040','Железнодорожный',0,11,0,136,1553121556),(147,'sytem-citylist-27041','Нефтекамск',0,11,0,137,1553121556),(148,'sytem-citylist-27042','Новый Уренгой',0,11,0,138,1553121556),(149,'sytem-citylist-27043','Черкесск',0,11,0,139,1553121556),(150,'sytem-citylist-27044','Ногинск',0,11,0,140,1553121556),(151,'sytem-citylist-27045','Новошахтинск',0,11,0,141,1553121556),(152,'sytem-citylist-27046','Нефтеюганск',0,11,0,142,1553121556),(153,'sytem-citylist-27047','Щёлково',0,11,0,143,1553121556),(154,'sytem-citylist-27048','Елец',0,11,0,144,1553121556),(155,'sytem-citylist-27049','Ачинск',0,11,0,145,1553121556),(156,'sytem-citylist-27050','Новокуйбышевск',0,11,0,146,1553121556),(157,'sytem-citylist-27051','Сергиев Посад',0,11,0,147,1553121556),(158,'sytem-citylist-27052','Ноябрьск',0,11,0,148,1553121556),(159,'sytem-citylist-27053','Кызыл',0,11,0,149,1553121556),(160,'sytem-citylist-27054','Дербент',0,11,0,150,1553121556),(161,'sytem-citylist-27055','Октябрьский',0,11,0,151,1553121556),(162,'sytem-citylist-27056','Северск',0,11,0,152,1553121556),(163,'sytem-citylist-27057','Ленинск-Кузнецкий',0,11,0,153,1553121556),(164,'sytem-citylist-27058','Арзамас',0,11,0,154,1553121556),(165,'sytem-citylist-27059','Обнинск',0,11,0,155,1553121556),(166,'sytem-citylist-27060','Ухта',0,11,0,156,1553121556),(167,'sytem-citylist-27061','Междуреченск',0,11,0,157,1553121556),(168,'sytem-citylist-27062','Киселёвск',0,11,0,158,1553121556),(169,'sytem-citylist-27063','Новотроицк',0,11,0,159,1553121556),(170,'sytem-citylist-27064','Батайск',0,11,0,160,1553121556),(171,'sytem-citylist-27065','Элиста',0,11,0,161,1553121556),(172,'sytem-citylist-27066','Артём',0,11,0,162,1553121556),(173,'sytem-citylist-27067','Жуковский',0,11,0,163,1553121556),(174,'sytem-citylist-27068','Великие Луки',0,11,0,164,1553121556),(175,'sytem-citylist-27069','Канск',0,11,0,165,1553121556),(176,'sytem-citylist-27070','Магадан',0,11,0,166,1553121556),(177,'sytem-citylist-27071','Тобольск',0,11,0,167,1553121556),(178,'sytem-citylist-27072','Глазов',0,11,0,168,1553121556),(179,'emarket-discounttype-27131','i18n::object-catalog_item_discount',0,30,0,1,1553121556),(180,'emarket-discounttype-27132','i18n::object-order_discount',0,30,0,2,1553121556),(181,'users-users-15','i18n::object-supervajzery',1,39,0,1,1553121556),(182,'system-supervisor','admin',1,54,0,1,1553124560),(183,'emarket-discounttype-bonus','i18n::object-bonus-discount',0,30,0,3,1553121556),(184,'emarket-discountmodificatortype-27136','i18n::object-summ_percent',0,31,0,1,1553121561),(185,'emarket-store-27147','i18n::object-main_store',0,55,0,1,1553121556),(186,'emarket-discountruletype-27150','i18n::object-specify_items',0,33,0,1,1553121561),(187,'emarket-itemtype-27180','i18n::object-digital',1,41,0,1,1553121556),(188,'emarket-itemtype-27181','i18n::object-complex',1,41,0,2,1553121556),(189,'emarket-deliverytype-27230','i18n::object-pickup',0,50,0,1,1553121556),(190,'emarket-deliverytype-27233','i18n::object-courier_delivery',0,50,0,2,1553121556),(191,'emarket-paymenttype-27236','i18n::object-sales_draft',1,46,0,1,1553121556),(192,'emarket-orderstatus-27258','i18n::object-otmenen',1,45,0,1,1553121556),(193,'emarket-orderstatus-27259','i18n::object-otklonen',1,45,0,2,1553121556),(194,'emarket-orderstatus-27260','i18n::object-oplachivaetsya',1,45,0,3,1553121556),(195,'emarket-orderstatus-27261','i18n::object-dostavlyaetsya',1,45,0,4,1553121556),(196,'emarket-orderstatus-27262','i18n::object-ozhidaet_proverki',1,45,0,5,1553121556),(197,'emarket-orderstatus-27263','i18n::object-prinyat',1,45,0,6,1553121556),(198,'emarket-orderstatus-27264','i18n::object-gotov',1,45,0,7,1553121556),(199,'emarket-orderdeliverystatus-27377','i18n::object-ojidaet_otgruzki',1,52,0,1,1553121556),(200,'emarket-orderdeliverystatus-27378','i18n::object-dostavlyaetsya',1,52,0,2,1553121556),(201,'emarket-orderdeliverystatus-27379','i18n::object-dostavlen',1,52,0,3,1553121556),(202,'emarket-orderpaymentstatus-27380','i18n::object-inicialisirovana',1,48,0,1,1553121556),(203,'emarket-orderpaymentstatus-27381','i18n::object-podtverjdena',1,48,0,2,1553121556),(204,'emarket-orderpaymentstatus-27382','i18n::object-otklonena',1,48,0,3,1553121556),(205,'emarket-orderpaymentstatus-27383','i18n::object-prinyata',1,48,0,4,1553121556),(206,'emarket-discountruletype-27393','i18n::object-time_interval_discount',0,33,0,2,1553121561),(207,'emarket-discountruletype-27394','i18n::object-order_summ_discount',0,33,0,3,1553121561),(208,'emarket-discountruletype-27395','i18n::object-user_summ_discount',0,33,0,4,1553121561),(209,'emarket-discountruletype-27396','i18n::object-user_group_discount',0,33,0,5,1553121561),(210,'emarket-discountruletype-27397','i18n::object-users_discount',0,33,0,6,1553121561),(211,'emarket-discountruletype-27398','i18n::object-related_items_discount',0,33,0,7,1553121561),(212,'emarket-discountmodificatortype-27456','i18n::object-fixed_modifier',0,31,0,2,1553121561),(213,'emarket-paymenttype-27457','i18n::object-payonline_system',1,46,0,2,1553121556),(214,'emarket-paymenttype-27458','i18n::object-to_courier',1,46,0,3,1553121556),(215,'399872db6f3d1341ef99b406aa2a9e515292b0c9','object-surface',0,15,0,1,1553121556),(216,'417baf8cefb99325510d31e974835254c980828b','object-air',0,15,0,2,1553121556),(217,'76377e05d0ffd4b0f6f0e72a45645f4be10f1c66','object-composite',0,15,0,3,1553121556),(218,'df383879afa5ac2e221b8fa0b0f2a6467da2886f','object-accelerated',0,15,0,4,1553121556),(219,'emarket-deliverytype-27481','i18n::object-russian_post',0,50,0,3,1553121556),(220,'emarket-paymenttype-27486','i18n::object-robokassa',1,46,0,4,1553121556),(221,'emarket-paymenttype-27487','i18n::object-rbk_money',1,46,0,5,1553121556),(222,'6bc46e77b86f1420917bee7a0e2154b34cdaad61','00:00',0,16,0,1,1553121556),(223,'1648322caec238f02862b0449a33b58245a9d6ce','01:00',0,16,0,2,1553121556),(224,'da2e75029f33e530c848c3aa89690ec07dd414b2','02:00',0,16,0,3,1553121556),(225,'8794b39a7bd4fe275575b6b864cf1fcca4d6d93b','03:00',0,16,0,4,1553121556),(226,'2054dced2668a57484cb2aa2498def91c22320ae','04:00',0,16,0,5,1553121556),(227,'14aa300dbddcacb6c76a4b5a364a034b6128693a','05:00',0,16,0,6,1553121557),(228,'5dc9eb3a83efd4d3302570742365c0186386947d','06:00',0,16,0,7,1553121557),(229,'8e0da95ba94e4757f3cc0f24bb0955069eb0f771','07:00',0,16,0,8,1553121557),(230,'23b3d29bb04eec144896f7f983b2f66611fe1435','08:00',0,16,0,9,1553121557),(231,'fd1f432dc313a02bcbcc9f405d8e9d121b01ba8d','09:00',0,16,0,10,1553121557),(232,'fffaba271c93a300f405a329f303686a9450bf5b','10:00',0,16,0,11,1553121557),(233,'a86f018536b8cb5896cdb631c8da8f10f0253fd9','11:00',0,16,0,12,1553121557),(234,'8d0a7e8844fd4b2eea8da19a39b81b048ce713d0','12:00',0,16,0,13,1553121557),(235,'22df1963ca47cc9ae5f0228f56ceeff467a2a280','13:00',0,16,0,14,1553121557),(236,'a03164eed7751779efd5d55464af6ae13fc4696e','14:00',0,16,0,15,1553121557),(237,'de1581726146cac70c29f0db6043eaa552da041f','15:00',0,16,0,16,1553121557),(238,'76ef341932f74678306044a0fa3e0105f5564492','16:00',0,16,0,17,1553121557),(239,'0db9ccf6183c19890acb33bc83c6167c7e941a5b','17:00',0,16,0,18,1553121557),(240,'e7951bd7de49615dc83491a195b47b61e82263bf','18:00',0,16,0,19,1553121557),(241,'70df85f9ac44cb7c7598b6ef28a50ddaa21d3937','19:00',0,16,0,20,1553121557),(242,'c60043f184d65a3101c6df21a087bbf99875a60b','20:00',0,16,0,21,1553121557),(243,'0837bbc4bf13fa667b3397def81d3a95a22f0739','21:00',0,16,0,22,1553121557),(244,'4f4c3308c188af2a2e08d59aecfaa2690fcf9981','22:00',0,16,0,23,1553121557),(245,'873f75be11e53b76dea6a438a97d3167d0aeb95c','23:00',0,16,0,24,1553121557),(246,'0a6697c2e0b67a404a645c2dd03f846e55afd981','i18n::object-monday',0,17,0,1,1553121557),(247,'4ba74364fd714bc12a8e8943cc6a36a26eaa36df','i18n::object-tuesday',0,17,0,2,1553121557),(248,'95b836e6799c016df64fdbab8d40d1c2b60173b3','i18n::object-wednesday',0,17,0,3,1553121557),(249,'a9bbb4de15c70fc416f13be9760ef33c3b2c6d67','i18n::object-thursday',0,17,0,4,1553121557),(250,'14a13a85a4e99c4f6c2fa9f42c4ff765e14415c3','i18n::object-friday',0,17,0,5,1553121557),(251,'bd7e2b0388c70b3ae4f64fe0bf5533f16e814704','i18n::object-saturday',0,17,0,6,1553121557),(252,'31586aa19a50a89a33e4d37a5d200671252fbd60','i18n::object-sunday',0,17,0,7,1553121557),(253,'emarket-paymenttype-27519','i18n::object-legal_bodies_account',1,46,0,6,1553121557),(254,'social_categories-27889','i18n::object-social_categories_other',1,19,0,1,1553121557),(255,'social_categories-27890','i18n::object-social_categories_electronics',1,19,0,2,1553121557),(256,'social_categories-27891','i18n::object-social_categories_mobile_phones',1,19,0,3,1553121557),(257,'social_categories-27892','i18n::object-social_categories_cameras',1,19,0,4,1553121557),(258,'social_categories-27893','i18n::object-social_categories_computers',1,19,0,5,1553121557),(259,'social_categories-27894','i18n::object-social_categories_books',1,19,0,6,1553121557),(260,'social_categories-27895','i18n::object-social_categories_furniture',1,19,0,7,1553121557),(261,'social_categories-27896','i18n::object-social_categories_clothes_shoes',1,19,0,8,1553121557),(262,'social_categories-27897','i18n::object-social_categories_sports_equipment',1,19,0,9,1553121557),(263,'social_categories-27898','i18n::object-social_categories_instruments',1,19,0,10,1553121557),(264,'social_categories-27899','i18n::object-social_categories_flowers',1,19,0,11,1553121557),(265,'social_categories-27900','i18n::object-social_categories_cosmetics',1,19,0,12,1553121557),(266,'social_categories-27901','i18n::object-social_categories_souvenirs_toys',1,19,0,13,1553121557),(267,'social_categories-27902','i18n::object-social_categories_food',1,19,0,14,1553121557),(268,'social_categories-27903','i18n::object-social_categories_audio',1,19,0,15,1553121557),(269,'social_categories-27904','i18n::object-social_categories_video',1,19,0,16,1553121557),(270,'social_categories-27905','i18n::object-social_categories_software_games',1,19,0,17,1553121557),(271,'social_categories-27906','i18n::object-social_categories_office_goods',1,19,0,18,1553121557),(272,'social_categories-27907','i18n::object-social_categories_sewing_goods',1,19,0,19,1553121557),(273,'social_categories-27908','i18n::object-social_categories_baby_goods',1,19,0,20,1553121557),(274,'social_categories-27909','i18n::object-social_categories_optics_goods',1,19,0,21,1553121557),(275,'social_categories-27910','i18n::object-social_categories_perfumery',1,19,0,22,1553121557),(276,'social_categories-27911','i18n::object-social_categories_bags_accessories',1,19,0,23,1553121557),(277,'social_categories-27912','i18n::object-social_categories_household_goods',1,19,0,24,1553121557),(278,'social_categories-27913','--',1,19,0,25,1553121557),(279,'emarket-paymenttype-payanyway','i18n::object-payanyway',1,46,0,7,1553121557),(280,'emarket-paymenttype-dengionline','i18n::object-money_online',1,46,0,8,1553121557),(281,'emarket-orderstatus-editing','i18n::object-orderstatus-editing',1,45,0,8,1553121557),(282,'emarket-signing-type-bank','i18n::object-signingtype-bank',0,20,0,1,1553121557),(283,'emarket-signing-type-partner','i18n::object-signingtype-partner',0,20,0,2,1553121557),(284,'emarket-paymenttype-kvk','i18n::object-paymenttype-kvk',1,46,0,9,1553121557),(285,'emarket-order-credit-status-27932','i18n::object-credit-status-new',0,22,0,1,1553121557),(286,'emarket-order-credit-status-27933','i18n::object-credit-status-hol',0,22,0,2,1553121557),(287,'emarket-order-credit-status-27934','i18n::object-credit-status-ver',0,22,0,3,1553121557),(288,'emarket-order-credit-status-27935','i18n::object-credit-status-rej',0,22,0,4,1553121557),(289,'emarket-order-credit-status-27936','i18n::object-credit-status-can',0,22,0,5,1553121557),(290,'emarket-order-credit-status-27937','i18n::object-credit-status-ovr',0,22,0,6,1553121557),(291,'emarket-order-credit-status-27938','i18n::object-credit-status-agr',0,22,0,7,1553121557),(292,'emarket-order-credit-status-27939','i18n::object-credit-status-app',0,22,0,8,1553121557),(293,'emarket-order-credit-status-27940','i18n::object-credit-status-prr',0,22,0,9,1553121557),(294,'emarket-order-credit-status-27941','i18n::object-credit-status-pvr',0,22,0,10,1553121557),(295,'emarket-order-credit-status-27942','i18n::object-credit-status-fap',0,22,0,11,1553121557),(296,'emarket-mobile-platform-27944','i18n::object-android',0,23,0,1,1553121557),(297,'emarket-mobile-platform-27945','i18n::object-ios',0,23,0,2,1553121557),(298,'emarket-order-payment-status-default','i18n::object-order-payment-status-default',1,48,0,5,1553121557),(299,'emarket-order-delivery-status-default','i18n::object-order-payment-status-default',1,52,0,4,1553121557),(300,'news-rss-charset-27949','i18n::object-windows_1251',0,6,0,1,1553121557),(301,'news-rss-charset-27950','i18n::object-utf_8',0,6,0,2,1553121557),(302,'emarket-paymenttype-acquiropay','i18n::object-acquiropay',1,46,0,10,1553121557),(303,'emarket-paymenttype-yandex30','i18n::object-paymenttype-yandex30',1,46,0,11,1553121557),(304,'emarket-paymenttype-paypal','i18n::object-paymenttype-paypal',1,46,0,12,1553121557),(305,'exchange-encoding-windows-1251','Windows-1251',0,26,0,1,1553121557),(306,'exchange-encoding-utf-8','UTF-8',0,26,0,2,1553121557),(307,'emarket-deliverytype-27958','i18n::object-type-apiship',1,50,0,4,1553121557),(308,'emarket-orderdeliverystatus-27959','i18n::object-otmenen',1,52,0,5,1553121557),(309,'emarket-orderdeliverystatus-27960','i18n::object-return',1,52,0,6,1553121557),(310,'country-AU','i18n::object-country-AU',1,10,0,3,1553121557),(311,'country-AT','i18n::object-country-AT',1,10,0,4,1553121557),(312,'country-AZ','i18n::object-country-AZ',1,10,0,5,1553121557),(313,'country-AX','i18n::object-country-AX',1,10,0,6,1553121557),(314,'country-AL','i18n::object-country-AL',1,10,0,7,1553121557),(315,'country-DZ','i18n::object-country-DZ',1,10,0,8,1553121557),(316,'country-AS','i18n::object-country-AS',1,10,0,9,1553121557),(317,'country-AI','i18n::object-country-AI',1,10,0,10,1553121557),(318,'country-AO','i18n::object-country-AO',1,10,0,11,1553121557),(319,'country-AD','i18n::object-country-AD',1,10,0,12,1553121557),(320,'country-AQ','i18n::object-country-AQ',1,10,0,13,1553121557),(321,'country-AG','i18n::object-country-AG',1,10,0,14,1553121557),(322,'country-AR','i18n::object-country-AR',1,10,0,15,1553121557),(323,'country-AM','i18n::object-country-AM',1,10,0,16,1553121557),(324,'country-AW','i18n::object-country-AW',1,10,0,17,1553121557),(325,'country-AF','i18n::object-country-AF',1,10,0,18,1553121557),(326,'country-BS','i18n::object-country-BS',1,10,0,19,1553121557),(327,'country-BD','i18n::object-country-BD',1,10,0,20,1553121557),(328,'country-BB','i18n::object-country-BB',1,10,0,21,1553121557),(329,'country-BH','i18n::object-country-BH',1,10,0,22,1553121557),(330,'country-BY','i18n::object-country-BY',1,10,0,23,1553121557),(331,'country-BZ','i18n::object-country-BZ',1,10,0,24,1553121557),(332,'country-BE','i18n::object-country-BE',1,10,0,25,1553121557),(333,'country-BJ','i18n::object-country-BJ',1,10,0,26,1553121557),(334,'country-BM','i18n::object-country-BM',1,10,0,27,1553121557),(335,'country-BG','i18n::object-country-BG',1,10,0,28,1553121557),(336,'country-BO','i18n::object-country-BO',1,10,0,29,1553121557),(337,'country-BQ','i18n::object-country-BQ',1,10,0,30,1553121557),(338,'country-BA','i18n::object-country-BA',1,10,0,31,1553121557),(339,'country-BW','i18n::object-country-BW',1,10,0,32,1553121557),(340,'country-BR','i18n::object-country-BR',1,10,0,33,1553121557),(341,'country-IO','i18n::object-country-IO',1,10,0,34,1553121557),(342,'country-BN','i18n::object-country-BN',1,10,0,35,1553121557),(343,'country-BF','i18n::object-country-BF',1,10,0,36,1553121557),(344,'country-BI','i18n::object-country-BI',1,10,0,37,1553121557),(345,'country-BT','i18n::object-country-BT',1,10,0,38,1553121558),(346,'country-VU','i18n::object-country-VU',1,10,0,39,1553121558),(347,'country-VA','i18n::object-country-VA',1,10,0,40,1553121558),(348,'country-GB','i18n::object-country-GB',1,10,0,41,1553121558),(349,'country-HU','i18n::object-country-HU',1,10,0,42,1553121558),(350,'country-VE','i18n::object-country-VE',1,10,0,43,1553121558),(351,'country-VG','i18n::object-country-VG',1,10,0,44,1553121558),(352,'country-VI','i18n::object-country-VI',1,10,0,45,1553121558),(353,'country-UM','i18n::object-country-UM',1,10,0,46,1553121558),(354,'country-TL','i18n::object-country-TL',1,10,0,47,1553121558),(355,'country-VN','i18n::object-country-VN',1,10,0,48,1553121558),(356,'country-GA','i18n::object-country-GA',1,10,0,49,1553121558),(357,'country-HT','i18n::object-country-HT',1,10,0,50,1553121558),(358,'country-GY','i18n::object-country-GY',1,10,0,51,1553121558),(359,'country-GM','i18n::object-country-GM',1,10,0,52,1553121558),(360,'country-GH','i18n::object-country-GH',1,10,0,53,1553121558),(361,'country-GP','i18n::object-country-GP',1,10,0,54,1553121558),(362,'country-GT','i18n::object-country-GT',1,10,0,55,1553121558),(363,'country-GN','i18n::object-country-GN',1,10,0,56,1553121558),(364,'country-GW','i18n::object-country-GW',1,10,0,57,1553121558),(365,'country-DE','i18n::object-country-DE',1,10,0,58,1553121558),(366,'country-GG','i18n::object-country-GG',1,10,0,59,1553121558),(367,'country-GI','i18n::object-country-GI',1,10,0,60,1553121558),(368,'country-HN','i18n::object-country-HN',1,10,0,61,1553121558),(369,'country-HK','i18n::object-country-HK',1,10,0,62,1553121558),(370,'country-GD','i18n::object-country-GD',1,10,0,63,1553121558),(371,'country-GL','i18n::object-country-GL',1,10,0,64,1553121558),(372,'country-GR','i18n::object-country-GR',1,10,0,65,1553121558),(373,'country-GE','i18n::object-country-GE',1,10,0,66,1553121558),(374,'country-GU','i18n::object-country-GU',1,10,0,67,1553121558),(375,'country-DK','i18n::object-country-DK',1,10,0,68,1553121558),(376,'country-JE','i18n::object-country-JE',1,10,0,69,1553121558),(377,'country-DJ','i18n::object-country-DJ',1,10,0,70,1553121558),(378,'country-DG','i18n::object-country-DG',1,10,0,71,1553121558),(379,'country-DM','i18n::object-country-DM',1,10,0,72,1553121558),(380,'country-DO','i18n::object-country-DO',1,10,0,73,1553121558),(381,'country-EG','i18n::object-country-EG',1,10,0,74,1553121558),(382,'country-ZM','i18n::object-country-ZM',1,10,0,75,1553121558),(383,'country-EH','i18n::object-country-EH',1,10,0,76,1553121558),(384,'country-ZW','i18n::object-country-ZW',1,10,0,77,1553121558),(385,'country-IL','i18n::object-country-IL',1,10,0,78,1553121558),(386,'country-IN','i18n::object-country-IN',1,10,0,79,1553121558),(387,'country-ID','i18n::object-country-ID',1,10,0,80,1553121558),(388,'country-JO','i18n::object-country-JO',1,10,0,81,1553121558),(389,'country-IQ','i18n::object-country-IQ',1,10,0,82,1553121558),(390,'country-IR','i18n::object-country-IR',1,10,0,83,1553121558),(391,'country-IE','i18n::object-country-IE',1,10,0,84,1553121558),(392,'country-IS','i18n::object-country-IS',1,10,0,85,1553121558),(393,'country-ES','i18n::object-country-ES',1,10,0,86,1553121558),(394,'country-IT','i18n::object-country-IT',1,10,0,87,1553121558),(395,'country-YE','i18n::object-country-YE',1,10,0,88,1553121558),(396,'country-CV','i18n::object-country-CV',1,10,0,89,1553121558),(397,'country-KZ','i18n::object-country-KZ',1,10,0,90,1553121558),(398,'country-KY','i18n::object-country-KY',1,10,0,91,1553121558),(399,'country-KH','i18n::object-country-KH',1,10,0,92,1553121558),(400,'country-CM','i18n::object-country-CM',1,10,0,93,1553121558),(401,'country-CA','i18n::object-country-CA',1,10,0,94,1553121558),(402,'country-IC','i18n::object-country-IC',1,10,0,95,1553121558),(403,'country-QA','i18n::object-country-QA',1,10,0,96,1553121558),(404,'country-KE','i18n::object-country-KE',1,10,0,97,1553121558),(405,'country-CY','i18n::object-country-CY',1,10,0,98,1553121558),(406,'country-KG','i18n::object-country-KG',1,10,0,99,1553121558),(407,'country-KI','i18n::object-country-KI',1,10,0,100,1553121558),(408,'country-CN','i18n::object-country-CN',1,10,0,101,1553121558),(409,'country-KP','i18n::object-country-KP',1,10,0,102,1553121558),(410,'country-CC','i18n::object-country-CC',1,10,0,103,1553121558),(411,'country-CO','i18n::object-country-CO',1,10,0,104,1553121558),(412,'country-KM','i18n::object-country-KM',1,10,0,105,1553121558),(413,'country-CG','i18n::object-country-CG',1,10,0,106,1553121558),(414,'country-CD','i18n::object-country-CD',1,10,0,107,1553121558),(415,'country-XK','i18n::object-country-XK',1,10,0,108,1553121558),(416,'country-CR','i18n::object-country-CR',1,10,0,109,1553121558),(417,'country-CI','i18n::object-country-CI',1,10,0,110,1553121558),(418,'country-CU','i18n::object-country-CU',1,10,0,111,1553121558),(419,'country-KW','i18n::object-country-KW',1,10,0,112,1553121558),(420,'country-CW','i18n::object-country-CW',1,10,0,113,1553121558),(421,'country-LA','i18n::object-country-LA',1,10,0,114,1553121558),(422,'country-LV','i18n::object-country-LV',1,10,0,115,1553121558),(423,'country-LS','i18n::object-country-LS',1,10,0,116,1553121558),(424,'country-LR','i18n::object-country-LR',1,10,0,117,1553121558),(425,'country-LB','i18n::object-country-LB',1,10,0,118,1553121558),(426,'country-LY','i18n::object-country-LY',1,10,0,119,1553121558),(427,'country-LT','i18n::object-country-LT',1,10,0,120,1553121558),(428,'country-LI','i18n::object-country-LI',1,10,0,121,1553121558),(429,'country-LU','i18n::object-country-LU',1,10,0,122,1553121558),(430,'country-MU','i18n::object-country-MU',1,10,0,123,1553121558),(431,'country-MR','i18n::object-country-MR',1,10,0,124,1553121558),(432,'country-MG','i18n::object-country-MG',1,10,0,125,1553121558),(433,'country-YT','i18n::object-country-YT',1,10,0,126,1553121558),(434,'country-MO','i18n::object-country-MO',1,10,0,127,1553121558),(435,'country-MK','i18n::object-country-MK',1,10,0,128,1553121558),(436,'country-MW','i18n::object-country-MW',1,10,0,129,1553121558),(437,'country-MY','i18n::object-country-MY',1,10,0,130,1553121558),(438,'country-ML','i18n::object-country-ML',1,10,0,131,1553121558),(439,'country-MV','i18n::object-country-MV',1,10,0,132,1553121558),(440,'country-MT','i18n::object-country-MT',1,10,0,133,1553121558),(441,'country-MA','i18n::object-country-MA',1,10,0,134,1553121558),(442,'country-MQ','i18n::object-country-MQ',1,10,0,135,1553121558),(443,'country-MH','i18n::object-country-MH',1,10,0,136,1553121558),(444,'country-MX','i18n::object-country-MX',1,10,0,137,1553121558),(445,'country-MZ','i18n::object-country-MZ',1,10,0,138,1553121558),(446,'country-MD','i18n::object-country-MD',1,10,0,139,1553121558),(447,'country-MC','i18n::object-country-MC',1,10,0,140,1553121558),(448,'country-MN','i18n::object-country-MN',1,10,0,141,1553121558),(449,'country-MS','i18n::object-country-MS',1,10,0,142,1553121558),(450,'country-MM','i18n::object-country-MM',1,10,0,143,1553121558),(451,'country-NA','i18n::object-country-NA',1,10,0,144,1553121558),(452,'country-NR','i18n::object-country-NR',1,10,0,145,1553121558),(453,'country-NP','i18n::object-country-NP',1,10,0,146,1553121558),(454,'country-NE','i18n::object-country-NE',1,10,0,147,1553121558),(455,'country-NG','i18n::object-country-NG',1,10,0,148,1553121558),(456,'country-NL','i18n::object-country-NL',1,10,0,149,1553121558),(457,'country-NI','i18n::object-country-NI',1,10,0,150,1553121558),(458,'country-NU','i18n::object-country-NU',1,10,0,151,1553121558),(459,'country-NZ','i18n::object-country-NZ',1,10,0,152,1553121558),(460,'country-NC','i18n::object-country-NC',1,10,0,153,1553121558),(461,'country-NO','i18n::object-country-NO',1,10,0,154,1553121558),(462,'country-AC','i18n::object-country-AC',1,10,0,155,1553121558),(463,'country-IM','i18n::object-country-IM',1,10,0,156,1553121558),(464,'country-NF','i18n::object-country-NF',1,10,0,157,1553121558),(465,'country-CX','i18n::object-country-CX',1,10,0,158,1553121558),(466,'country-SH','i18n::object-country-SH',1,10,0,159,1553121558),(467,'country-CK','i18n::object-country-CK',1,10,0,160,1553121558),(468,'country-TC','i18n::object-country-TC',1,10,0,161,1553121558),(469,'country-AE','i18n::object-country-AE',1,10,0,162,1553121558),(470,'country-OM','i18n::object-country-OM',1,10,0,163,1553121558),(471,'country-PK','i18n::object-country-PK',1,10,0,164,1553121558),(472,'country-PW','i18n::object-country-PW',1,10,0,165,1553121558),(473,'country-PS','i18n::object-country-PS',1,10,0,166,1553121558),(474,'country-PA','i18n::object-country-PA',1,10,0,167,1553121558),(475,'country-PG','i18n::object-country-PG',1,10,0,168,1553121558),(476,'country-PY','i18n::object-country-PY',1,10,0,169,1553121559),(477,'country-PE','i18n::object-country-PE',1,10,0,170,1553121559),(478,'country-PN','i18n::object-country-PN',1,10,0,171,1553121559),(479,'country-PL','i18n::object-country-PL',1,10,0,172,1553121559),(480,'country-PT','i18n::object-country-PT',1,10,0,173,1553121559),(481,'country-PR','i18n::object-country-PR',1,10,0,174,1553121559),(482,'country-KR','i18n::object-country-KR',1,10,0,175,1553121559),(483,'country-RE','i18n::object-country-RE',1,10,0,176,1553121559),(484,'country-RW','i18n::object-country-RW',1,10,0,177,1553121559),(485,'country-RO','i18n::object-country-RO',1,10,0,178,1553121559),(486,'country-SV','i18n::object-country-SV',1,10,0,179,1553121559),(487,'country-WS','i18n::object-country-WS',1,10,0,180,1553121559),(488,'country-SM','i18n::object-country-SM',1,10,0,181,1553121559),(489,'country-ST','i18n::object-country-ST',1,10,0,182,1553121559),(490,'country-SA','i18n::object-country-SA',1,10,0,183,1553121559),(491,'country-SZ','i18n::object-country-SZ',1,10,0,184,1553121559),(492,'country-MP','i18n::object-country-MP',1,10,0,185,1553121559),(493,'country-SC','i18n::object-country-SC',1,10,0,186,1553121559),(494,'country-BL','i18n::object-country-BL',1,10,0,187,1553121559),(495,'country-MF','i18n::object-country-MF',1,10,0,188,1553121559),(496,'country-PM','i18n::object-country-PM',1,10,0,189,1553121559),(497,'country-SN','i18n::object-country-SN',1,10,0,190,1553121559),(498,'country-VC','i18n::object-country-VC',1,10,0,191,1553121559),(499,'country-KN','i18n::object-country-KN',1,10,0,192,1553121559),(500,'country-LC','i18n::object-country-LC',1,10,0,193,1553121559),(501,'country-RS','i18n::object-country-RS',1,10,0,194,1553121559),(502,'country-EA','i18n::object-country-EA',1,10,0,195,1553121559),(503,'country-SG','i18n::object-country-SG',1,10,0,196,1553121559),(504,'country-SX','i18n::object-country-SX',1,10,0,197,1553121559),(505,'country-SY','i18n::object-country-SY',1,10,0,198,1553121559),(506,'country-SK','i18n::object-country-SK',1,10,0,199,1553121559),(507,'country-SI','i18n::object-country-SI',1,10,0,200,1553121559),(508,'country-SB','i18n::object-country-SB',1,10,0,201,1553121559),(509,'country-SO','i18n::object-country-SO',1,10,0,202,1553121559),(510,'country-SD','i18n::object-country-SD',1,10,0,203,1553121559),(511,'country-SR','i18n::object-country-SR',1,10,0,204,1553121559),(512,'country-SL','i18n::object-country-SL',1,10,0,205,1553121559),(513,'country-TJ','i18n::object-country-TJ',1,10,0,206,1553121559),(514,'country-TH','i18n::object-country-TH',1,10,0,207,1553121559),(515,'country-TW','i18n::object-country-TW',1,10,0,208,1553121559),(516,'country-TZ','i18n::object-country-TZ',1,10,0,209,1553121559),(517,'country-TG','i18n::object-country-TG',1,10,0,210,1553121559),(518,'country-TK','i18n::object-country-TK',1,10,0,211,1553121559),(519,'country-TO','i18n::object-country-TO',1,10,0,212,1553121559),(520,'country-TT','i18n::object-country-TT',1,10,0,213,1553121559),(521,'country-TA','i18n::object-country-TA',1,10,0,214,1553121559),(522,'country-TV','i18n::object-country-TV',1,10,0,215,1553121559),(523,'country-TN','i18n::object-country-TN',1,10,0,216,1553121559),(524,'country-TM','i18n::object-country-TM',1,10,0,217,1553121559),(525,'country-TR','i18n::object-country-TR',1,10,0,218,1553121559),(526,'country-UG','i18n::object-country-UG',1,10,0,219,1553121559),(527,'country-UZ','i18n::object-country-UZ',1,10,0,220,1553121559),(528,'country-UA','i18n::object-country-UA',1,10,0,221,1553121559),(529,'country-WF','i18n::object-country-WF',1,10,0,222,1553121559),(530,'country-UY','i18n::object-country-UY',1,10,0,223,1553121559),(531,'country-FO','i18n::object-country-FO',1,10,0,224,1553121559),(532,'country-FM','i18n::object-country-FM',1,10,0,225,1553121559),(533,'country-FJ','i18n::object-country-FJ',1,10,0,226,1553121559),(534,'country-PH','i18n::object-country-PH',1,10,0,227,1553121559),(535,'country-FI','i18n::object-country-FI',1,10,0,228,1553121559),(536,'country-FK','i18n::object-country-FK',1,10,0,229,1553121559),(537,'country-FR','i18n::object-country-FR',1,10,0,230,1553121559),(538,'country-GF','i18n::object-country-GF',1,10,0,231,1553121559),(539,'country-PF','i18n::object-country-PF',1,10,0,232,1553121559),(540,'country-TF','i18n::object-country-TF',1,10,0,233,1553121559),(541,'country-HR','i18n::object-country-HR',1,10,0,234,1553121559),(542,'country-CF','i18n::object-country-CF',1,10,0,235,1553121559),(543,'country-TD','i18n::object-country-TD',1,10,0,236,1553121559),(544,'country-ME','i18n::object-country-ME',1,10,0,237,1553121559),(545,'country-CZ','i18n::object-country-CZ',1,10,0,238,1553121559),(546,'country-CL','i18n::object-country-CL',1,10,0,239,1553121559),(547,'country-CH','i18n::object-country-CH',1,10,0,240,1553121559),(548,'country-SE','i18n::object-country-SE',1,10,0,241,1553121559),(549,'country-SJ','i18n::object-country-SJ',1,10,0,242,1553121559),(550,'country-LK','i18n::object-country-LK',1,10,0,243,1553121559),(551,'country-EC','i18n::object-country-EC',1,10,0,244,1553121559),(552,'country-GQ','i18n::object-country-GQ',1,10,0,245,1553121559),(553,'country-ER','i18n::object-country-ER',1,10,0,246,1553121559),(554,'country-EE','i18n::object-country-EE',1,10,0,247,1553121559),(555,'country-ET','i18n::object-country-ET',1,10,0,248,1553121559),(556,'country-ZA','i18n::object-country-ZA',1,10,0,249,1553121559),(557,'country-GS','i18n::object-country-GS',1,10,0,250,1553121559),(558,'country-SS','i18n::object-country-SS',1,10,0,251,1553121559),(559,'country-JM','i18n::object-country-JM',1,10,0,252,1553121559),(560,'country-JP','i18n::object-country-JP',1,10,0,253,1553121559),(561,'emarket-payment-type-yandex-kassa','i18n::object-payment-type-yandex-kassa',1,46,0,13,1553121559),(562,'emarket-item-type-custom','Пользовательский',1,41,0,3,1553121559),(563,'emarket-item-type-trade-offer','Торговое предложение',1,41,0,4,1553121559),(564,'25ec3f9da5444fe6a125910137ec28200d4eaaa8','i18n::object-status-publish',0,2,0,1,1553121559),(565,'tax-rate-27961','Без НДС',1,27,0,1,1553121559),(566,'russianpost_wrapper_simple','i18n::object-wrapper_simple',0,14,0,1,1553121559),(567,'payment-subject-28225','Товар',1,28,0,1,1553121559),(568,'payment-mode-28238','Полная предоплата',1,29,0,1,1553121559),(569,'8a6f804b3690f0592a3f17ed980a9df5f16bacd8','i18n::object-status-unpublish',0,2,0,2,1553121559),(570,'russianpost_registered_wrapper','i18n::object-registered_wrapper',0,14,0,2,1553121559),(571,'tax-rate-27962','НДС по ставке 0%',1,27,0,2,1553121559),(572,'payment-subject-28226','Подакцизный товар',1,28,0,2,1553121559),(573,'payment-mode-28239','Частичная предоплата',1,29,0,2,1553121559),(574,'f4df5d14f5a1aeeebfe3db75b73e57fef8bcc4f2','i18n::object-status-preunpublish',0,2,0,3,1553121559),(575,'russianpost_wrapper_with_declared_value','i18n::object-wrapper_with_declared_value',0,14,0,3,1553121559),(576,'tax-rate-27963','НДС по ставке 10%',1,27,0,3,1553121559),(577,'payment-subject-28227','Работа',1,28,0,3,1553121559),(578,'payment-mode-28240','Аванс',1,29,0,3,1553121559),(579,'russianpost_registered_wrapper_first_class','i18n::object-registered_wrapper_first_class',0,14,0,4,1553121559),(580,'tax-rate-27964','НДС по ставке 20%',1,27,0,4,1553121559),(581,'payment-subject-28228','Услуга',1,28,0,4,1553121559),(582,'payment-mode-28241','Полный расчет',1,29,0,4,1553121559),(583,'russianpost_wrapper_first_class_with_declared_value','i18n::object-wrapper_first_class_with_declared_value',0,14,0,5,1553121559),(584,'tax-rate-27965','НДС по расчетной ставке 10/110',1,27,0,5,1553121559),(585,'payment-subject-28229','Ставка в азартной игре',1,28,0,5,1553121559),(586,'payment-mode-28242','Частичный расчет и кредит',1,29,0,5,1553121559),(587,'tax-rate-27966','НДС  по расчетной ставке 20/120',1,27,0,6,1553121559),(588,'russianpost_parcel','i18n::object-parcel',0,14,0,6,1553121559),(589,'payment-subject-28230','Выигрыш в азартной игре',1,28,0,6,1553121559),(590,'payment-mode-28243','Кредит',1,29,0,6,1553121559),(591,'russianpost_parcel_with_declared_value','i18n::object-parcel_with_declared_value',0,14,0,7,1553121559),(592,'payment-subject-28231','Лотерейный билет',1,28,0,7,1553121559),(593,'payment-mode-28244','Выплата по кредиту',1,29,0,7,1553121559),(594,'russianpost_parcel_first_class','i18n::object-parcel_first_class',0,14,0,8,1553121559),(595,'payment-subject-28232','Выигрыш в лотерею',1,28,0,8,1553121559),(596,'russianpost_parcel_first_class_with_declared_value','i18n::object-parcel_first_class_with_declared_value',0,14,0,9,1553121559),(597,'payment-subject-28233','Результаты интеллектуальной деятельности',1,28,0,9,1553121559),(598,'russianpost_ems_standart','i18n::object-ems_standart',0,14,0,10,1553121559),(599,'payment-subject-28234','Платеж',1,28,0,10,1553121559),(600,'russianpost_ems_declared_value','i18n::object-ems_declared_value',0,14,0,11,1553121559),(601,'payment-subject-28235','Агентское вознаграждение',1,28,0,11,1553121559),(602,'payment-subject-28236','Несколько вариантов',1,28,0,12,1553121559),(603,'payment-subject-28237','i18n::object-social_categories_other',1,28,0,13,1553121559),(604,'emarket-discountmodificator-768-27135','i18n::object-test_percent_modifier',0,56,0,1,1553121561),(605,'emarket-discountrule-798-27438','i18n::object-users',0,57,0,1,1553121561),(606,'3fb6d39f5279c04f1bfec5a7cc13783a45d00141','i18n::object-commerceml_data_format',0,35,0,1,1553121560),(607,'2c4eff97ef278f12c4461309e84dd0627bd4a37b','i18n::object-umiDump_data_format',0,35,0,2,1553121560),(608,'23abbfa28d922d786d39218e3aa26719ad16ee47','i18n::object-csv_dataformat',0,35,0,3,1553121560),(609,'cdc4a1f4e0ee63b2359d3dec91efe33d2a296c92','i18n::object-umi_export_umiDump',0,35,0,4,1553121560),(610,'2ca45ca1c710cf65f451f098f4bf683082566200','i18n::object-commerceml_catalog',0,36,0,1,1553121560),(611,'681665ea8b72237d1677dfaf7339ef7a7ec40269','i18n::object-commerceml_offer_list',0,36,0,2,1553121560),(612,'8e9874cd7a1b20f4b00c95fd7126f2112101c2ac','i18n::object-commerceml_order_list',0,36,0,3,1553121560),(613,'ff6c38d4ab12cda6c035cf36a4afb829049fbf21','i18n::object-yml_catalog',0,36,0,4,1553121560),(614,'b8c554e9ce8127f2405c189857cfd6831dcc2f5d','i18n::object-umiDump_data_format',0,36,0,5,1553121560),(615,'de2d91f2111e74d1fab49ffed3220fc4b1d51d42','i18n::object-csv_dataformat',0,36,0,6,1553121560),(616,'ccc9bf34f683f8e4ecf2ffe2910f3d8cda2b6852','i18n::object-umi_export_umiDump',0,36,0,7,1553121560),(617,'exchange-export-commerceml','i18n::object-catalog_export',0,36,0,8,1553121560),(618,'system-guest','i18n::object-guest',1,54,0,2,1553121560),(619,'users-users-2374','i18n::object-zaregistrirovannye_pol_zovateli',1,39,0,2,1553121560),(620,'emarket-currency-27226','i18n::object-rur',0,21,0,1,1553121560),(621,'emarket-currency-27227','i18n::object-usd',0,21,0,2,1553121560),(622,'emarket-currency-27228','i18n::object-euro',0,21,0,3,1553121560),(623,'','Главная',0,61,182,2,1553122592),(624,'','i18n::object-type-blogs-blog',0,7,182,3,1553123288),(625,'','Центральный апогей: предпосылки и развитие',0,60,182,4,1553123963),(626,'','Том Круз',0,126,182,1,1553123716),(627,'','Дэниэл Крейг',0,126,182,2,1553123739),(628,'','Александр Невский',0,126,182,3,1553123759),(629,'','Межпланетный годовой параллакс: методология и особенности',0,60,182,5,1553124103),(630,'','Почему параллельна Летучая Рыба?',0,60,182,6,1553124270),(631,'','Почему потенциально пpотопланетное облако?',0,60,182,7,1553124252),(632,'','Почему потенциально пpотопланетное облако?',0,60,182,7,1553124385),(633,'','Почему параллельна Летучая Рыба?',0,60,182,6,1553124416),(634,'','Межпланетный годовой параллакс: методология и особенности',0,60,182,5,1553124437),(635,'','Почему параллельна Летучая Рыба?',0,60,182,6,1553124487);
/*!40000 ALTER TABLE `cms3_objects` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cms3_objects_expiration`
--

DROP TABLE IF EXISTS `cms3_objects_expiration`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cms3_objects_expiration` (
  `obj_id` int(10) unsigned NOT NULL,
  `entrytime` int(10) unsigned NOT NULL,
  `expire` int(10) unsigned NOT NULL,
  PRIMARY KEY (`obj_id`),
  KEY `FK_ObjectsExpire to objects` (`obj_id`),
  KEY `entrytime` (`entrytime`,`expire`),
  CONSTRAINT `FK_ObjectsExpire to objects` FOREIGN KEY (`obj_id`) REFERENCES `cms3_objects` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cms3_objects_expiration`
--

LOCK TABLES `cms3_objects_expiration` WRITE;
/*!40000 ALTER TABLE `cms3_objects_expiration` DISABLE KEYS */;
/*!40000 ALTER TABLE `cms3_objects_expiration` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cms3_offer_list`
--

DROP TABLE IF EXISTS `cms3_offer_list`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cms3_offer_list` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `type_id` int(10) unsigned NOT NULL,
  `data_object_id` int(10) unsigned DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `vendor_code` varchar(255) DEFAULT NULL,
  `bar_code` varchar(255) DEFAULT NULL,
  `total_count` bigint(20) unsigned DEFAULT '0',
  `is_active` tinyint(1) DEFAULT '1',
  `order` bigint(20) unsigned DEFAULT '0',
  `weight` bigint(20) unsigned DEFAULT '0',
  `width` bigint(20) unsigned DEFAULT '0',
  `length` bigint(20) unsigned DEFAULT '0',
  `height` bigint(20) unsigned DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `vendor_code` (`vendor_code`),
  KEY `offer to type id` (`type_id`),
  KEY `offer to data object id` (`data_object_id`),
  CONSTRAINT `offer to data object id` FOREIGN KEY (`data_object_id`) REFERENCES `cms3_objects` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `offer to type id` FOREIGN KEY (`type_id`) REFERENCES `cms3_object_types` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cms3_offer_list`
--

LOCK TABLES `cms3_offer_list` WRITE;
/*!40000 ALTER TABLE `cms3_offer_list` DISABLE KEYS */;
/*!40000 ALTER TABLE `cms3_offer_list` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cms3_offer_price_list`
--

DROP TABLE IF EXISTS `cms3_offer_price_list`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cms3_offer_price_list` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `value` double unsigned NOT NULL,
  `offer_id` int(10) unsigned NOT NULL,
  `currency_id` int(10) unsigned NOT NULL,
  `type_id` int(10) unsigned NOT NULL,
  `is_main` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `offer price to offer` (`offer_id`),
  KEY `offer price to currency` (`currency_id`),
  KEY `offer price to type` (`type_id`),
  CONSTRAINT `offer price to currency` FOREIGN KEY (`currency_id`) REFERENCES `cms3_objects` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `offer price to offer` FOREIGN KEY (`offer_id`) REFERENCES `cms3_offer_list` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `offer price to type` FOREIGN KEY (`type_id`) REFERENCES `cms3_offer_price_type_list` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cms3_offer_price_list`
--

LOCK TABLES `cms3_offer_price_list` WRITE;
/*!40000 ALTER TABLE `cms3_offer_price_list` DISABLE KEYS */;
/*!40000 ALTER TABLE `cms3_offer_price_list` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cms3_offer_price_type_list`
--

DROP TABLE IF EXISTS `cms3_offer_price_type_list`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cms3_offer_price_type_list` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `title` varchar(255) NOT NULL,
  `is_default` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cms3_offer_price_type_list`
--

LOCK TABLES `cms3_offer_price_type_list` WRITE;
/*!40000 ALTER TABLE `cms3_offer_price_type_list` DISABLE KEYS */;
INSERT INTO `cms3_offer_price_type_list` VALUES (1,'default','Основная',1);
/*!40000 ALTER TABLE `cms3_offer_price_type_list` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cms3_permissions`
--

DROP TABLE IF EXISTS `cms3_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cms3_permissions` (
  `level` tinyint(4) DEFAULT NULL,
  `owner_id` int(10) unsigned DEFAULT NULL,
  `rel_id` int(10) unsigned DEFAULT NULL,
  KEY `owner reference_FK` (`owner_id`),
  KEY `rel reference_FK` (`rel_id`),
  KEY `level` (`level`),
  CONSTRAINT `FK_owner reference` FOREIGN KEY (`owner_id`) REFERENCES `cms3_objects` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `FK_rel reference` FOREIGN KEY (`rel_id`) REFERENCES `cms3_hierarchy` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cms3_permissions`
--

LOCK TABLES `cms3_permissions` WRITE;
/*!40000 ALTER TABLE `cms3_permissions` DISABLE KEYS */;
INSERT INTO `cms3_permissions` VALUES (1,618,1),(1,618,2),(1,618,3),(1,618,4),(1,618,5),(1,618,6),(1,618,7),(1,618,8),(1,618,9),(1,618,10);
/*!40000 ALTER TABLE `cms3_permissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cms3_redirects`
--

DROP TABLE IF EXISTS `cms3_redirects`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cms3_redirects` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `source` text NOT NULL,
  `target` text NOT NULL,
  `status` int(10) unsigned DEFAULT '301',
  `made_by_user` tinyint(1) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `source` (`source`(255))
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cms3_redirects`
--

LOCK TABLES `cms3_redirects` WRITE;
/*!40000 ALTER TABLE `cms3_redirects` DISABLE KEYS */;
/*!40000 ALTER TABLE `cms3_redirects` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cms3_search`
--

DROP TABLE IF EXISTS `cms3_search`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cms3_search` (
  `rel_id` int(10) unsigned NOT NULL,
  `indextime` int(11) DEFAULT NULL,
  `lang_id` int(11) DEFAULT NULL,
  `domain_id` int(11) DEFAULT NULL,
  `type_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`rel_id`),
  KEY `lang_id + domain_id + type_id_FK` (`lang_id`,`domain_id`,`type_id`),
  KEY `domain_id` (`domain_id`,`type_id`),
  KEY `indextime` (`indextime`),
  KEY `type_id` (`type_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cms3_search`
--

LOCK TABLES `cms3_search` WRITE;
/*!40000 ALTER TABLE `cms3_search` DISABLE KEYS */;
INSERT INTO `cms3_search` VALUES (1,1553122592,1,1,30),(2,1553123288,1,1,1),(3,1553123963,1,1,29),(4,1553124103,1,1,29),(5,1553124270,1,1,29),(6,1553124252,1,1,29),(7,1553124385,1,1,29),(8,1553124416,1,1,29),(9,1553124437,1,1,29),(10,1553124488,1,1,29);
/*!40000 ALTER TABLE `cms3_search` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cms3_search_index`
--

DROP TABLE IF EXISTS `cms3_search_index`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cms3_search_index` (
  `rel_id` int(10) unsigned DEFAULT NULL,
  `weight` int(11) DEFAULT NULL,
  `word_id` int(10) unsigned DEFAULT NULL,
  `tf` float DEFAULT NULL,
  KEY `pages to index_FK` (`rel_id`),
  KEY `word index_FK` (`word_id`),
  KEY `weight` (`weight`),
  KEY `tf` (`tf`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cms3_search_index`
--

LOCK TABLES `cms3_search_index` WRITE;
/*!40000 ALTER TABLE `cms3_search_index` DISABLE KEYS */;
INSERT INTO `cms3_search_index` VALUES (1,5,1,1),(2,5,2,1),(3,7,3,0.0324074),(3,5,4,0.0231481),(3,5,5,0.0231481),(3,5,6,0.0231481),(3,2,7,0.00925926),(3,2,8,0.00925926),(3,2,9,0.00925926),(3,2,10,0.00925926),(3,2,11,0.00925926),(3,2,12,0.00925926),(3,2,13,0.00925926),(3,2,14,0.00925926),(3,2,15,0.00925926),(3,3,16,0.0138889),(3,3,17,0.0138889),(3,3,18,0.0138889),(3,3,19,0.0138889),(3,2,20,0.00925926),(3,2,21,0.00925926),(3,3,22,0.0138889),(3,3,23,0.0138889),(3,2,24,0.00925926),(3,2,25,0.00925926),(3,2,26,0.00925926),(3,2,27,0.00925926),(3,3,28,0.0138889),(3,2,29,0.00925926),(3,2,30,0.00925926),(3,2,31,0.00925926),(3,2,32,0.00925926),(3,2,33,0.00925926),(3,2,34,0.00925926),(3,2,35,0.00925926),(3,3,36,0.0138889),(3,4,37,0.0185185),(3,1,38,0.00462963),(3,1,39,0.00462963),(3,1,40,0.00462963),(3,1,41,0.00462963),(3,1,42,0.00462963),(3,1,43,0.00462963),(3,1,44,0.00462963),(3,1,45,0.00462963),(3,1,46,0.00462963),(3,1,47,0.00462963),(3,1,48,0.00462963),(3,1,49,0.00462963),(3,1,50,0.00462963),(3,1,51,0.00462963),(3,1,52,0.00462963),(3,1,53,0.00462963),(3,1,54,0.00462963),(3,1,55,0.00462963),(3,2,56,0.00925926),(3,2,57,0.00925926),(3,2,58,0.00925926),(3,2,59,0.00925926),(3,2,60,0.00925926),(3,1,61,0.00462963),(3,1,62,0.00462963),(3,1,63,0.00462963),(3,1,64,0.00462963),(3,1,65,0.00462963),(3,1,66,0.00462963),(3,1,67,0.00462963),(3,1,68,0.00462963),(3,2,69,0.00925926),(3,1,70,0.00462963),(3,1,71,0.00462963),(3,1,72,0.00462963),(3,1,73,0.00462963),(3,1,74,0.00462963),(3,1,75,0.00462963),(3,1,76,0.00462963),(3,1,77,0.00462963),(3,1,78,0.00462963),(3,1,79,0.00462963),(3,1,80,0.00462963),(3,1,81,0.00462963),(3,1,82,0.00462963),(3,1,83,0.00462963),(3,1,84,0.00462963),(3,1,85,0.00462963),(3,1,86,0.00462963),(3,1,87,0.00462963),(3,1,88,0.00462963),(3,1,89,0.00462963),(3,1,90,0.00462963),(3,1,91,0.00462963),(3,1,92,0.00462963),(3,1,93,0.00462963),(3,1,94,0.00462963),(3,1,95,0.00462963),(3,1,96,0.00462963),(3,1,97,0.00462963),(3,1,98,0.00462963),(3,2,99,0.00925926),(3,1,100,0.00462963),(3,1,101,0.00462963),(3,2,102,0.00925926),(3,1,103,0.00462963),(3,1,104,0.00462963),(3,1,105,0.00462963),(3,1,106,0.00462963),(3,1,107,0.00462963),(3,1,108,0.00462963),(3,1,109,0.00462963),(3,1,110,0.00462963),(3,1,111,0.00462963),(3,1,112,0.00462963),(3,1,113,0.00462963),(3,1,114,0.00462963),(3,1,115,0.00462963),(3,1,116,0.00462963),(4,7,117,0.0325581),(4,5,118,0.0232558),(4,5,119,0.0232558),(4,5,120,0.0232558),(4,5,121,0.0232558),(4,2,122,0.00930233),(4,2,123,0.00930233),(4,2,124,0.00930233),(4,2,125,0.00930233),(4,2,93,0.00930233),(4,2,126,0.00930233),(4,2,127,0.00930233),(4,2,128,0.00930233),(4,2,129,0.00930233),(4,2,130,0.00930233),(4,2,131,0.00930233),(4,2,132,0.00930233),(4,3,133,0.0139535),(4,2,25,0.00930233),(4,4,134,0.0186047),(4,3,50,0.0139535),(4,2,135,0.00930233),(4,2,30,0.00930233),(4,2,136,0.00930233),(4,3,56,0.0139535),(4,2,137,0.00930233),(4,2,138,0.00930233),(4,2,139,0.00930233),(4,2,140,0.00930233),(4,2,141,0.00930233),(4,2,142,0.00930233),(4,2,143,0.00930233),(4,2,144,0.00930233),(4,2,145,0.00930233),(4,2,146,0.00930233),(4,2,63,0.00930233),(4,2,86,0.00930233),(4,2,87,0.00930233),(4,3,82,0.0139535),(4,2,147,0.00930233),(4,2,148,0.00930233),(4,1,149,0.00465116),(4,1,150,0.00465116),(4,1,27,0.00465116),(4,2,57,0.00930233),(4,2,58,0.00930233),(4,2,59,0.00930233),(4,2,60,0.00930233),(4,1,151,0.00465116),(4,1,152,0.00465116),(4,1,153,0.00465116),(4,1,19,0.00465116),(4,1,154,0.00465116),(4,1,11,0.00465116),(4,1,155,0.00465116),(4,1,156,0.00465116),(4,1,157,0.00465116),(4,1,158,0.00465116),(4,1,159,0.00465116),(4,1,160,0.00465116),(4,1,161,0.00465116),(4,1,162,0.00465116),(4,1,163,0.00465116),(4,2,164,0.00930233),(4,1,165,0.00465116),(4,1,166,0.00465116),(4,1,167,0.00465116),(4,1,168,0.00465116),(4,1,169,0.00465116),(4,1,170,0.00465116),(4,1,171,0.00465116),(4,1,172,0.00465116),(4,1,173,0.00465116),(4,1,16,0.00465116),(4,1,174,0.00465116),(4,1,175,0.00465116),(4,1,62,0.00465116),(4,1,176,0.00465116),(4,1,28,0.00465116),(4,1,177,0.00465116),(4,1,178,0.00465116),(4,1,179,0.00465116),(4,1,180,0.00465116),(4,1,181,0.00465116),(4,1,182,0.00465116),(4,1,183,0.00465116),(4,1,184,0.00465116),(4,1,185,0.00465116),(4,1,186,0.00465116),(4,1,187,0.00465116),(4,1,188,0.00465116),(4,1,189,0.00465116),(4,1,190,0.00465116),(4,1,191,0.00465116),(4,1,192,0.00465116),(4,1,193,0.00465116),(4,1,194,0.00465116),(4,1,195,0.00465116),(4,1,196,0.00465116),(4,1,197,0.00465116),(4,1,198,0.00465116),(6,5,199,0.0243902),(6,5,289,0.0243902),(6,7,38,0.0341463),(6,7,39,0.0341463),(6,2,31,0.0097561),(6,2,32,0.0097561),(6,2,33,0.0097561),(6,2,66,0.0097561),(6,2,67,0.0097561),(6,2,68,0.0097561),(6,5,176,0.0243902),(6,3,290,0.0146341),(6,2,128,0.0097561),(6,2,291,0.0097561),(6,3,99,0.0146341),(6,2,292,0.0097561),(6,2,293,0.0097561),(6,2,294,0.0097561),(6,2,295,0.0097561),(6,2,296,0.0097561),(6,2,297,0.0097561),(6,2,21,0.0097561),(6,3,103,0.0146341),(6,2,189,0.0097561),(6,2,184,0.0097561),(6,2,298,0.0097561),(6,2,73,0.0097561),(6,2,74,0.0097561),(6,2,83,0.0097561),(6,1,299,0.00487805),(6,1,300,0.00487805),(6,1,61,0.00487805),(6,1,301,0.00487805),(6,1,302,0.00487805),(6,2,77,0.0097561),(6,1,81,0.00487805),(6,2,146,0.0097561),(6,1,75,0.00487805),(6,1,76,0.00487805),(6,1,303,0.00487805),(6,1,304,0.00487805),(6,1,305,0.00487805),(6,1,306,0.00487805),(6,1,307,0.00487805),(6,1,308,0.00487805),(6,1,17,0.00487805),(6,1,18,0.00487805),(6,1,79,0.00487805),(6,2,190,0.0097561),(6,2,191,0.0097561),(6,1,35,0.00487805),(6,1,177,0.00487805),(6,1,178,0.00487805),(6,2,69,0.0097561),(6,1,309,0.00487805),(6,1,310,0.00487805),(6,1,311,0.00487805),(6,1,312,0.00487805),(6,1,313,0.00487805),(6,1,314,0.00487805),(6,1,315,0.00487805),(6,1,4,0.00487805),(6,1,106,0.00487805),(6,1,107,0.00487805),(6,1,316,0.00487805),(6,1,317,0.00487805),(6,1,318,0.00487805),(6,1,319,0.00487805),(6,1,100,0.00487805),(6,1,143,0.00487805),(6,1,320,0.00487805),(6,1,321,0.00487805),(6,1,211,0.00487805),(6,1,322,0.00487805),(6,1,323,0.00487805),(6,1,230,0.00487805),(6,1,324,0.00487805),(6,1,325,0.00487805),(6,1,326,0.00487805),(6,1,327,0.00487805),(6,1,328,0.00487805),(6,1,34,0.00487805),(6,1,28,0.00487805),(6,1,135,0.00487805),(6,1,262,0.00487805),(6,1,263,0.00487805),(6,1,264,0.00487805),(6,1,265,0.00487805),(6,1,266,0.00487805),(6,2,267,0.0097561),(6,2,268,0.0097561),(6,1,269,0.00487805),(6,1,270,0.00487805),(6,1,271,0.00487805),(6,1,272,0.00487805),(6,1,273,0.00487805),(6,1,274,0.00487805),(6,1,275,0.00487805),(6,1,42,0.00487805),(6,1,276,0.00487805),(6,1,65,0.00487805),(6,1,277,0.00487805),(6,1,278,0.00487805),(6,1,279,0.00487805),(6,1,280,0.00487805),(6,1,281,0.00487805),(6,1,282,0.00487805),(6,1,283,0.00487805),(6,1,284,0.00487805),(6,1,285,0.00487805),(5,5,199,0.0182482),(5,6,200,0.0218978),(5,5,201,0.0182482),(5,5,202,0.0182482),(5,2,203,0.00729927),(5,6,69,0.0218978),(5,2,70,0.00729927),(5,2,71,0.00729927),(5,2,189,0.00729927),(5,3,77,0.0109489),(5,2,146,0.00729927),(5,2,204,0.00729927),(5,2,205,0.00729927),(5,2,206,0.00729927),(5,2,207,0.00729927),(5,2,208,0.00729927),(5,2,17,0.00729927),(5,2,18,0.00729927),(5,2,209,0.00729927),(5,2,34,0.00729927),(5,2,4,0.00729927),(5,2,210,0.00729927),(5,2,211,0.00729927),(5,2,212,0.00729927),(5,2,213,0.00729927),(5,2,93,0.00729927),(5,2,214,0.00729927),(5,2,215,0.00729927),(5,2,216,0.00729927),(5,2,217,0.00729927),(5,2,128,0.00729927),(5,2,218,0.00729927),(5,2,219,0.00729927),(5,2,220,0.00729927),(5,3,171,0.0109489),(5,2,221,0.00729927),(5,3,83,0.0109489),(5,2,84,0.00729927),(5,2,85,0.00729927),(5,2,72,0.00729927),(5,2,19,0.00729927),(5,4,81,0.0145985),(5,2,222,0.00729927),(5,3,223,0.0109489),(5,3,224,0.0109489),(5,3,190,0.0109489),(5,3,191,0.0109489),(5,2,225,0.00729927),(5,3,119,0.0109489),(5,2,226,0.00729927),(5,1,170,0.00364964),(5,1,133,0.00364964),(5,1,172,0.00364964),(5,1,173,0.00364964),(5,1,16,0.00364964),(5,2,227,0.00729927),(5,2,228,0.00729927),(5,1,229,0.00364964),(5,2,102,0.00729927),(5,1,20,0.00364964),(5,2,230,0.00729927),(5,1,231,0.00364964),(5,1,232,0.00364964),(5,1,166,0.00364964),(5,1,233,0.00364964),(5,1,234,0.00364964),(5,1,235,0.00364964),(5,1,236,0.00364964),(5,2,176,0.00729927),(5,1,237,0.00364964),(5,1,238,0.00364964),(5,1,64,0.00364964),(5,3,65,0.0109489),(5,1,56,0.00364964),(5,1,239,0.00364964),(5,1,240,0.00364964),(5,1,241,0.00364964),(5,1,242,0.00364964),(5,1,243,0.00364964),(5,1,244,0.00364964),(5,1,245,0.00364964),(5,1,246,0.00364964),(5,1,247,0.00364964),(5,1,248,0.00364964),(5,1,249,0.00364964),(5,1,250,0.00364964),(5,1,251,0.00364964),(5,1,252,0.00364964),(5,2,154,0.00729927),(5,2,11,0.00729927),(5,2,155,0.00729927),(5,2,156,0.00729927),(5,1,253,0.00364964),(5,1,254,0.00364964),(5,1,149,0.00364964),(5,1,150,0.00364964),(5,1,255,0.00364964),(5,1,256,0.00364964),(5,1,257,0.00364964),(5,1,258,0.00364964),(5,1,259,0.00364964),(5,1,260,0.00364964),(5,1,21,0.00364964),(5,1,80,0.00364964),(5,1,261,0.00364964),(5,1,106,0.00364964),(5,1,107,0.00364964),(5,1,135,0.00364964),(5,1,262,0.00364964),(5,1,263,0.00364964),(5,1,264,0.00364964),(5,1,265,0.00364964),(5,1,266,0.00364964),(5,1,99,0.00364964),(5,2,267,0.00729927),(5,2,268,0.00729927),(5,1,269,0.00364964),(5,1,270,0.00364964),(5,1,271,0.00364964),(5,1,272,0.00364964),(5,1,273,0.00364964),(5,1,274,0.00364964),(5,1,275,0.00364964),(5,1,42,0.00364964),(5,1,276,0.00364964),(5,1,277,0.00364964),(5,1,278,0.00364964),(5,1,279,0.00364964),(5,1,280,0.00364964),(5,1,281,0.00364964),(5,1,282,0.00364964),(5,1,283,0.00364964),(5,1,284,0.00364964),(5,1,285,0.00364964),(5,1,286,0.00364964),(5,1,287,0.00364964),(5,1,118,0.00364964),(5,1,177,0.00364964),(5,1,178,0.00364964),(5,1,288,0.00364964),(5,1,101,0.00364964),(7,5,199,0.0243902),(7,5,289,0.0243902),(7,7,38,0.0341463),(7,7,39,0.0341463),(7,2,31,0.0097561),(7,2,32,0.0097561),(7,2,33,0.0097561),(7,2,66,0.0097561),(7,2,67,0.0097561),(7,2,68,0.0097561),(7,5,176,0.0243902),(7,3,290,0.0146341),(7,2,128,0.0097561),(7,2,291,0.0097561),(7,3,99,0.0146341),(7,2,292,0.0097561),(7,2,293,0.0097561),(7,2,294,0.0097561),(7,2,295,0.0097561),(7,2,296,0.0097561),(7,2,297,0.0097561),(7,2,21,0.0097561),(7,3,103,0.0146341),(7,2,189,0.0097561),(7,2,184,0.0097561),(7,2,298,0.0097561),(7,2,73,0.0097561),(7,2,74,0.0097561),(7,2,83,0.0097561),(7,1,299,0.00487805),(7,1,300,0.00487805),(7,1,61,0.00487805),(7,1,301,0.00487805),(7,1,302,0.00487805),(7,2,77,0.0097561),(7,1,81,0.00487805),(7,2,146,0.0097561),(7,1,75,0.00487805),(7,1,76,0.00487805),(7,1,303,0.00487805),(7,1,304,0.00487805),(7,1,305,0.00487805),(7,1,306,0.00487805),(7,1,307,0.00487805),(7,1,308,0.00487805),(7,1,17,0.00487805),(7,1,18,0.00487805),(7,1,79,0.00487805),(7,2,190,0.0097561),(7,2,191,0.0097561),(7,1,35,0.00487805),(7,1,177,0.00487805),(7,1,178,0.00487805),(7,2,69,0.0097561),(7,1,309,0.00487805),(7,1,310,0.00487805),(7,1,311,0.00487805),(7,1,312,0.00487805),(7,1,313,0.00487805),(7,1,314,0.00487805),(7,1,315,0.00487805),(7,1,4,0.00487805),(7,1,106,0.00487805),(7,1,107,0.00487805),(7,1,316,0.00487805),(7,1,317,0.00487805),(7,1,318,0.00487805),(7,1,319,0.00487805),(7,1,100,0.00487805),(7,1,143,0.00487805),(7,1,320,0.00487805),(7,1,321,0.00487805),(7,1,211,0.00487805),(7,1,322,0.00487805),(7,1,323,0.00487805),(7,1,230,0.00487805),(7,1,324,0.00487805),(7,1,325,0.00487805),(7,1,326,0.00487805),(7,1,327,0.00487805),(7,1,328,0.00487805),(7,1,34,0.00487805),(7,1,28,0.00487805),(7,1,135,0.00487805),(7,1,262,0.00487805),(7,1,263,0.00487805),(7,1,264,0.00487805),(7,1,265,0.00487805),(7,1,266,0.00487805),(7,2,267,0.0097561),(7,2,268,0.0097561),(7,1,269,0.00487805),(7,1,270,0.00487805),(7,1,271,0.00487805),(7,1,272,0.00487805),(7,1,273,0.00487805),(7,1,274,0.00487805),(7,1,275,0.00487805),(7,1,42,0.00487805),(7,1,276,0.00487805),(7,1,65,0.00487805),(7,1,277,0.00487805),(7,1,278,0.00487805),(7,1,279,0.00487805),(7,1,280,0.00487805),(7,1,281,0.00487805),(7,1,282,0.00487805),(7,1,283,0.00487805),(7,1,284,0.00487805),(7,1,285,0.00487805),(8,5,199,0.0182482),(8,6,200,0.0218978),(8,5,201,0.0182482),(8,5,202,0.0182482),(8,2,203,0.00729927),(8,6,69,0.0218978),(8,2,70,0.00729927),(8,2,71,0.00729927),(8,2,189,0.00729927),(8,3,77,0.0109489),(8,2,146,0.00729927),(8,2,204,0.00729927),(8,2,205,0.00729927),(8,2,206,0.00729927),(8,2,207,0.00729927),(8,2,208,0.00729927),(8,2,17,0.00729927),(8,2,18,0.00729927),(8,2,209,0.00729927),(8,2,34,0.00729927),(8,2,4,0.00729927),(8,2,210,0.00729927),(8,2,211,0.00729927),(8,2,212,0.00729927),(8,2,213,0.00729927),(8,2,93,0.00729927),(8,2,214,0.00729927),(8,2,215,0.00729927),(8,2,216,0.00729927),(8,2,217,0.00729927),(8,2,128,0.00729927),(8,2,218,0.00729927),(8,2,219,0.00729927),(8,2,220,0.00729927),(8,3,171,0.0109489),(8,2,221,0.00729927),(8,3,83,0.0109489),(8,2,84,0.00729927),(8,2,85,0.00729927),(8,2,72,0.00729927),(8,2,19,0.00729927),(8,4,81,0.0145985),(8,2,222,0.00729927),(8,3,223,0.0109489),(8,3,224,0.0109489),(8,3,190,0.0109489),(8,3,191,0.0109489),(8,2,225,0.00729927),(8,3,119,0.0109489),(8,2,226,0.00729927),(8,1,170,0.00364964),(8,1,133,0.00364964),(8,1,172,0.00364964),(8,1,173,0.00364964),(8,1,16,0.00364964),(8,2,227,0.00729927),(8,2,228,0.00729927),(8,1,229,0.00364964),(8,2,102,0.00729927),(8,1,20,0.00364964),(8,2,230,0.00729927),(8,1,231,0.00364964),(8,1,232,0.00364964),(8,1,166,0.00364964),(8,1,233,0.00364964),(8,1,234,0.00364964),(8,1,235,0.00364964),(8,1,236,0.00364964),(8,2,176,0.00729927),(8,1,237,0.00364964),(8,1,238,0.00364964),(8,1,64,0.00364964),(8,3,65,0.0109489),(8,1,56,0.00364964),(8,1,239,0.00364964),(8,1,240,0.00364964),(8,1,241,0.00364964),(8,1,242,0.00364964),(8,1,243,0.00364964),(8,1,244,0.00364964),(8,1,245,0.00364964),(8,1,246,0.00364964),(8,1,247,0.00364964),(8,1,248,0.00364964),(8,1,249,0.00364964),(8,1,250,0.00364964),(8,1,251,0.00364964),(8,1,252,0.00364964),(8,2,154,0.00729927),(8,2,11,0.00729927),(8,2,155,0.00729927),(8,2,156,0.00729927),(8,1,253,0.00364964),(8,1,254,0.00364964),(8,1,149,0.00364964),(8,1,150,0.00364964),(8,1,255,0.00364964),(8,1,256,0.00364964),(8,1,257,0.00364964),(8,1,258,0.00364964),(8,1,259,0.00364964),(8,1,260,0.00364964),(8,1,21,0.00364964),(8,1,80,0.00364964),(8,1,261,0.00364964),(8,1,106,0.00364964),(8,1,107,0.00364964),(8,1,135,0.00364964),(8,1,262,0.00364964),(8,1,263,0.00364964),(8,1,264,0.00364964),(8,1,265,0.00364964),(8,1,266,0.00364964),(8,1,99,0.00364964),(8,2,267,0.00729927),(8,2,268,0.00729927),(8,1,269,0.00364964),(8,1,270,0.00364964),(8,1,271,0.00364964),(8,1,272,0.00364964),(8,1,273,0.00364964),(8,1,274,0.00364964),(8,1,275,0.00364964),(8,1,42,0.00364964),(8,1,276,0.00364964),(8,1,277,0.00364964),(8,1,278,0.00364964),(8,1,279,0.00364964),(8,1,280,0.00364964),(8,1,281,0.00364964),(8,1,282,0.00364964),(8,1,283,0.00364964),(8,1,284,0.00364964),(8,1,285,0.00364964),(8,1,286,0.00364964),(8,1,287,0.00364964),(8,1,118,0.00364964),(8,1,177,0.00364964),(8,1,178,0.00364964),(8,1,288,0.00364964),(8,1,101,0.00364964),(9,7,117,0.0325581),(9,5,118,0.0232558),(9,5,119,0.0232558),(9,5,120,0.0232558),(9,5,121,0.0232558),(9,2,122,0.00930233),(9,2,123,0.00930233),(9,2,124,0.00930233),(9,2,125,0.00930233),(9,2,93,0.00930233),(9,2,126,0.00930233),(9,2,127,0.00930233),(9,2,128,0.00930233),(9,2,129,0.00930233),(9,2,130,0.00930233),(9,2,131,0.00930233),(9,2,132,0.00930233),(9,3,133,0.0139535),(9,2,25,0.00930233),(9,4,134,0.0186047),(9,3,50,0.0139535),(9,2,135,0.00930233),(9,2,30,0.00930233),(9,2,136,0.00930233),(9,3,56,0.0139535),(9,2,137,0.00930233),(9,2,138,0.00930233),(9,2,139,0.00930233),(9,2,140,0.00930233),(9,2,141,0.00930233),(9,2,142,0.00930233),(9,2,143,0.00930233),(9,2,144,0.00930233),(9,2,145,0.00930233),(9,2,146,0.00930233),(9,2,63,0.00930233),(9,2,86,0.00930233),(9,2,87,0.00930233),(9,3,82,0.0139535),(9,2,147,0.00930233),(9,2,148,0.00930233),(9,1,149,0.00465116),(9,1,150,0.00465116),(9,1,27,0.00465116),(9,2,57,0.00930233),(9,2,58,0.00930233),(9,2,59,0.00930233),(9,2,60,0.00930233),(9,1,151,0.00465116),(9,1,152,0.00465116),(9,1,153,0.00465116),(9,1,19,0.00465116),(9,1,154,0.00465116),(9,1,11,0.00465116),(9,1,155,0.00465116),(9,1,156,0.00465116),(9,1,157,0.00465116),(9,1,158,0.00465116),(9,1,159,0.00465116),(9,1,160,0.00465116),(9,1,161,0.00465116),(9,1,162,0.00465116),(9,1,163,0.00465116),(9,2,164,0.00930233),(9,1,165,0.00465116),(9,1,166,0.00465116),(9,1,167,0.00465116),(9,1,168,0.00465116),(9,1,169,0.00465116),(9,1,170,0.00465116),(9,1,171,0.00465116),(9,1,172,0.00465116),(9,1,173,0.00465116),(9,1,16,0.00465116),(9,1,174,0.00465116),(9,1,175,0.00465116),(9,1,62,0.00465116),(9,1,176,0.00465116),(9,1,28,0.00465116),(9,1,177,0.00465116),(9,1,178,0.00465116),(9,1,179,0.00465116),(9,1,180,0.00465116),(9,1,181,0.00465116),(9,1,182,0.00465116),(9,1,183,0.00465116),(9,1,184,0.00465116),(9,1,185,0.00465116),(9,1,186,0.00465116),(9,1,187,0.00465116),(9,1,188,0.00465116),(9,1,189,0.00465116),(9,1,190,0.00465116),(9,1,191,0.00465116),(9,1,192,0.00465116),(9,1,193,0.00465116),(9,1,194,0.00465116),(9,1,195,0.00465116),(9,1,196,0.00465116),(9,1,197,0.00465116),(9,1,198,0.00465116),(10,5,199,0.0182482),(10,6,200,0.0218978),(10,5,201,0.0182482),(10,5,202,0.0182482),(10,2,203,0.00729927),(10,6,69,0.0218978),(10,2,70,0.00729927),(10,2,71,0.00729927),(10,2,189,0.00729927),(10,3,77,0.0109489),(10,2,146,0.00729927),(10,2,204,0.00729927),(10,2,205,0.00729927),(10,2,206,0.00729927),(10,2,207,0.00729927),(10,2,208,0.00729927),(10,2,17,0.00729927),(10,2,18,0.00729927),(10,2,209,0.00729927),(10,2,34,0.00729927),(10,2,4,0.00729927),(10,2,210,0.00729927),(10,2,211,0.00729927),(10,2,212,0.00729927),(10,2,213,0.00729927),(10,2,93,0.00729927),(10,2,214,0.00729927),(10,2,215,0.00729927),(10,2,216,0.00729927),(10,2,217,0.00729927),(10,2,128,0.00729927),(10,2,218,0.00729927),(10,2,219,0.00729927),(10,2,220,0.00729927),(10,3,171,0.0109489),(10,2,221,0.00729927),(10,3,83,0.0109489),(10,2,84,0.00729927),(10,2,85,0.00729927),(10,2,72,0.00729927),(10,2,19,0.00729927),(10,4,81,0.0145985),(10,2,222,0.00729927),(10,3,223,0.0109489),(10,3,224,0.0109489),(10,3,190,0.0109489),(10,3,191,0.0109489),(10,2,225,0.00729927),(10,3,119,0.0109489),(10,2,226,0.00729927),(10,1,170,0.00364964),(10,1,133,0.00364964),(10,1,172,0.00364964),(10,1,173,0.00364964),(10,1,16,0.00364964),(10,2,227,0.00729927),(10,2,228,0.00729927),(10,1,229,0.00364964),(10,2,102,0.00729927),(10,1,20,0.00364964),(10,2,230,0.00729927),(10,1,231,0.00364964),(10,1,232,0.00364964),(10,1,166,0.00364964),(10,1,233,0.00364964),(10,1,234,0.00364964),(10,1,235,0.00364964),(10,1,236,0.00364964),(10,2,176,0.00729927),(10,1,237,0.00364964),(10,1,238,0.00364964),(10,1,64,0.00364964),(10,3,65,0.0109489),(10,1,56,0.00364964),(10,1,239,0.00364964),(10,1,240,0.00364964),(10,1,241,0.00364964),(10,1,242,0.00364964),(10,1,243,0.00364964),(10,1,244,0.00364964),(10,1,245,0.00364964),(10,1,246,0.00364964),(10,1,247,0.00364964),(10,1,248,0.00364964),(10,1,249,0.00364964),(10,1,250,0.00364964),(10,1,251,0.00364964),(10,1,252,0.00364964),(10,2,154,0.00729927),(10,2,11,0.00729927),(10,2,155,0.00729927),(10,2,156,0.00729927),(10,1,253,0.00364964),(10,1,254,0.00364964),(10,1,149,0.00364964),(10,1,150,0.00364964),(10,1,255,0.00364964),(10,1,256,0.00364964),(10,1,257,0.00364964),(10,1,258,0.00364964),(10,1,259,0.00364964),(10,1,260,0.00364964),(10,1,21,0.00364964),(10,1,80,0.00364964),(10,1,261,0.00364964),(10,1,106,0.00364964),(10,1,107,0.00364964),(10,1,135,0.00364964),(10,1,262,0.00364964),(10,1,263,0.00364964),(10,1,264,0.00364964),(10,1,265,0.00364964),(10,1,266,0.00364964),(10,1,99,0.00364964),(10,2,267,0.00729927),(10,2,268,0.00729927),(10,1,269,0.00364964),(10,1,270,0.00364964),(10,1,271,0.00364964),(10,1,272,0.00364964),(10,1,273,0.00364964),(10,1,274,0.00364964),(10,1,275,0.00364964),(10,1,42,0.00364964),(10,1,276,0.00364964),(10,1,277,0.00364964),(10,1,278,0.00364964),(10,1,279,0.00364964),(10,1,280,0.00364964),(10,1,281,0.00364964),(10,1,282,0.00364964),(10,1,283,0.00364964),(10,1,284,0.00364964),(10,1,285,0.00364964),(10,1,286,0.00364964),(10,1,287,0.00364964),(10,1,118,0.00364964),(10,1,177,0.00364964),(10,1,178,0.00364964),(10,1,288,0.00364964),(10,1,101,0.00364964);
/*!40000 ALTER TABLE `cms3_search_index` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cms3_search_index_words`
--

DROP TABLE IF EXISTS `cms3_search_index_words`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cms3_search_index_words` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `word` varchar(64) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `word` (`word`)
) ENGINE=InnoDB AUTO_INCREMENT=329 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cms3_search_index_words`
--

LOCK TABLES `cms3_search_index_words` WRITE;
/*!40000 ALTER TABLE `cms3_search_index_words` DISABLE KEYS */;
INSERT INTO `cms3_search_index_words` VALUES (322,'29.8'),(294,'3,26'),(196,'40&ndash'),(230,'ndash'),(323,'sqrt'),(21,'азимут'),(318,'активность'),(33,'анализа'),(275,'анналах'),(316,'аномальная'),(4,'апогей'),(151,'аргумент'),(54,'архонте'),(132,'астероиды'),(125,'астрономам'),(212,'атмосферы'),(253,'атомное'),(78,'афелий'),(49,'афинах'),(53,'афинском'),(48,'афины'),(249,'блеск'),(290,'близкий'),(2,'блог'),(88,'большинство'),(274,'больших'),(57,'большой'),(42,'было'),(163,'быть'),(258,'вверху'),(327,'вероятна'),(104,'весеннее'),(23,'взгляд'),(270,'видим'),(164,'видны'),(181,'внешние'),(111,'внимание'),(182,'воздействия'),(237,'возмущающий'),(91,'вокруг'),(262,'вопросе'),(259,'восток'),(26,'восхождение'),(19,'вращает'),(35,'вращательный'),(96,'вращаются'),(175,'времени'),(65,'время'),(130,'все'),(154,'вселенная'),(314,'второго'),(80,'выбирает'),(77,'вызывает'),(276,'вычислено'),(140,'галактику'),(288,'ганимед'),(56,'гасит'),(325,'где'),(67,'гелиоцентрическое'),(8,'гипотеза'),(1,'главная'),(37,'год'),(296,'года'),(118,'годовой'),(256,'горизонт'),(127,'группы'),(123,'давно'),(63,'дает'),(204,'датировка'),(172,'два'),(134,'движение'),(90,'движутся'),(161,'действительно'),(64,'декретное'),(317,'джетовая'),(186,'диаметp'),(106,'дип-скай'),(269,'дня'),(309,'довольно'),(74,'дождь'),(11,'достаточно'),(263,'достигнута'),(142,'дракона'),(46,'древний'),(197,'ещё'),(216,'жидкую'),(229,'жизненно'),(273,'записанного'),(320,'записать'),(44,'затмение'),(278,'затмений'),(313,'звезд'),(297,'звезда'),(247,'звездочка'),(165,'звезды'),(126,'земной'),(103,'зенит'),(234,'зенитное'),(300,'знаем'),(131,'известные'),(124,'известных'),(34,'иллюстрирует'),(133,'имеют'),(87,'индекс'),(232,'индикатор'),(218,'интуитивно'),(177,'ионный'),(27,'ищет'),(84,'казалось'),(83,'как'),(95,'какую'),(55,'каллии'),(145,'карликовой'),(281,'квинктильские'),(188,'класс'),(41,'когда'),(102,'колеблет'),(194,'кольца'),(286,'комета'),(171,'кометы'),(108,'конечно'),(81,'космический'),(7,'космогоническая'),(280,'которое'),(246,'красноватая'),(58,'круг'),(193,'ледник'),(201,'летучая'),(128,'лимб'),(115,'логарифм'),(43,'лунное'),(75,'магнитное'),(217,'мантию'),(255,'математический'),(223,'маятник'),(254,'мгновенно'),(117,'межпланетный'),(138,'меридиан'),(251,'металического'),(29,'метеорит'),(73,'метеорный'),(120,'методология'),(227,'млечный'),(170,'многие'),(162,'могли'),(143,'можно'),(222,'мусор'),(169,'наблюдаема'),(311,'наблюдается'),(203,'надир'),(144,'назвать'),(114,'натуральный'),(267,'начиная'),(59,'небесной'),(189,'недоступно'),(109,'нельзя'),(136,'ненаблюдаемо'),(261,'непреложный'),(66,'непрерывно'),(226,'неравномерен'),(180,'несмотря'),(15,'нестыковку'),(24,'неустойчив'),(153,'ничтожно'),(282,'ноны'),(137,'нулевой'),(39,'облако'),(211,'образом'),(107,'объект'),(13,'объяснить'),(155,'огромна'),(16,'однако'),(292,'одном'),(319,'однородно'),(209,'определению'),(159,'орбита'),(250,'освещенного'),(121,'особенности'),(32,'осторожного'),(326,'ось'),(122,'отличие'),(176,'отражает'),(184,'оценивает'),(248,'оценивая'),(98,'очевидно'),(38,'пpотопланетное'),(85,'парадоксальным'),(119,'параллакс'),(200,'параллельна'),(179,'параметр'),(293,'парсеке'),(221,'пеpигелии'),(22,'первый'),(215,'переходят'),(240,'перигей'),(239,'перигелий'),(152,'перигелия'),(206,'петавиусу'),(304,'пионерской'),(52,'питии'),(214,'плавно'),(93,'планет'),(97,'планеты'),(71,'подчеркнуть'),(10,'позволяет'),(76,'поле'),(30,'полнолуние'),(219,'понятен'),(308,'поперечник'),(86,'популяционный'),(31,'после'),(79,'последовательно'),(301,'постоянно'),(289,'потенциально'),(199,'почему'),(5,'предпосылки'),(190,'представляет'),(277,'предшествовавших'),(72,'прекрасно'),(50,'при'),(205,'приведена'),(149,'приливное'),(233,'примета'),(110,'принять'),(147,'притягивает'),(283,'произошло'),(12,'просто'),(61,'противостояние'),(328,'прочно'),(25,'прямое'),(228,'путь'),(305,'работе'),(105,'равноденствие'),(100,'радиант'),(6,'развитие'),(17,'различное'),(116,'разрушаем'),(18,'расположение'),(68,'расстояние'),(266,'расчетов'),(192,'реликтовый'),(285,'ромула'),(202,'рыба'),(146,'сарос'),(312,'сверхновых'),(295,'световых'),(167,'свидетельствует'),(92,'своих'),(45,'сгорел'),(257,'север'),(324,'сек'),(220,'скоpость'),(198,'скопировать'),(231,'скорее'),(245,'слабопроницаем'),(260,'слева'),(70,'следует'),(40,'следующий'),(321,'следующим'),(303,'следуя'),(129,'сложен'),(28,'случайный'),(191,'собой'),(141,'созвездии'),(279,'солнца'),(244,'спектра'),(187,'спектральный'),(89,'спутников'),(94,'сторону'),(62,'существенно'),(60,'сферы'),(264,'такая'),(210,'таким'),(160,'там'),(315,'типа'),(268,'того'),(195,'только'),(112,'тот'),(101,'точно'),(265,'точность'),(183,'традиционно'),(150,'трение'),(36,'тропический'),(82,'тукан'),(158,'угол'),(241,'удалось'),(299,'уже'),(148,'узел'),(271,'указанного'),(174,'уравнение'),(242,'установить'),(291,'учитывая'),(113,'факт'),(238,'фактор'),(168,'фукидид'),(224,'фуко'),(307,'хаббла'),(208,'хайсу'),(243,'характеру'),(178,'хвост'),(173,'хвоста'),(287,'хейла-боппа'),(139,'хотя'),(47,'храм'),(284,'царствование'),(3,'центральный'),(207,'цеху'),(235,'часовое'),(157,'часовой'),(310,'часто'),(166,'чем'),(236,'число'),(99,'что'),(156,'чтобы'),(252,'шарика'),(9,'шмидта'),(306,'эдвина'),(20,'эксцентриситет'),(298,'эллиптический'),(302,'элонгация'),(272,'эннием'),(213,'этих'),(69,'это'),(135,'этом'),(14,'эту'),(51,'эфоре'),(185,'эффективный'),(225,'юпитер');
/*!40000 ALTER TABLE `cms3_search_index_words` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cms3_sliders`
--

DROP TABLE IF EXISTS `cms3_sliders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cms3_sliders` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `domain_id` int(11) unsigned NOT NULL,
  `language_id` int(11) unsigned NOT NULL,
  `sliding_speed` int(11) unsigned DEFAULT NULL,
  `sliding_delay` int(11) unsigned DEFAULT NULL,
  `sliding_loop_enable` tinyint(1) DEFAULT '0',
  `sliding_auto_play_enable` tinyint(1) DEFAULT '0',
  `sliders_random_order_enable` tinyint(1) DEFAULT '0',
  `slides_count` int(11) unsigned DEFAULT '0',
  `custom_id` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `id from domains` (`domain_id`),
  KEY `id from languages` (`language_id`),
  CONSTRAINT `id from domains` FOREIGN KEY (`domain_id`) REFERENCES `cms3_domains` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `id from languages` FOREIGN KEY (`language_id`) REFERENCES `cms3_langs` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cms3_sliders`
--

LOCK TABLES `cms3_sliders` WRITE;
/*!40000 ALTER TABLE `cms3_sliders` DISABLE KEYS */;
/*!40000 ALTER TABLE `cms3_sliders` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cms3_slides`
--

DROP TABLE IF EXISTS `cms3_slides`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cms3_slides` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `slider_id` int(11) unsigned NOT NULL,
  `is_active` tinyint(1) DEFAULT '0',
  `title` varchar(1024) DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `text` mediumtext,
  `link` varchar(1024) DEFAULT NULL,
  `open_in_new_tab` tinyint(1) DEFAULT '1',
  `order` int(11) unsigned DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `slider_id and is_active with order` (`slider_id`,`is_active`,`order`),
  CONSTRAINT `id from sliders` FOREIGN KEY (`slider_id`) REFERENCES `cms3_sliders` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cms3_slides`
--

LOCK TABLES `cms3_slides` WRITE;
/*!40000 ALTER TABLE `cms3_slides` DISABLE KEYS */;
/*!40000 ALTER TABLE `cms3_slides` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cms3_stock_balance_list`
--

DROP TABLE IF EXISTS `cms3_stock_balance_list`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cms3_stock_balance_list` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `offer_id` int(10) unsigned NOT NULL,
  `stock_id` int(10) unsigned NOT NULL,
  `value` bigint(20) unsigned DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `stock balance to offer` (`offer_id`),
  KEY `stock balance to stock` (`stock_id`),
  CONSTRAINT `stock balance to offer` FOREIGN KEY (`offer_id`) REFERENCES `cms3_offer_list` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `stock balance to stock` FOREIGN KEY (`stock_id`) REFERENCES `cms3_objects` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cms3_stock_balance_list`
--

LOCK TABLES `cms3_stock_balance_list` WRITE;
/*!40000 ALTER TABLE `cms3_stock_balance_list` DISABLE KEYS */;
/*!40000 ALTER TABLE `cms3_stock_balance_list` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cms3_templates`
--

DROP TABLE IF EXISTS `cms3_templates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cms3_templates` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(128) DEFAULT NULL,
  `filename` varchar(64) DEFAULT NULL,
  `type` varchar(64) DEFAULT NULL,
  `domain_id` int(10) unsigned DEFAULT NULL,
  `lang_id` int(10) unsigned DEFAULT NULL,
  `title` varchar(128) DEFAULT NULL,
  `is_default` tinyint(1) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `Template - Lang_FK` (`lang_id`),
  KEY `Templates - domains_FK` (`domain_id`),
  KEY `is_default` (`is_default`),
  KEY `filename` (`filename`),
  KEY `title` (`title`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cms3_templates`
--

LOCK TABLES `cms3_templates` WRITE;
/*!40000 ALTER TABLE `cms3_templates` DISABLE KEYS */;
INSERT INTO `cms3_templates` VALUES (1,'default','layout.phtml','php',1,1,'default',1);
/*!40000 ALTER TABLE `cms3_templates` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cms_backup`
--

DROP TABLE IF EXISTS `cms_backup`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cms_backup` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `ctime` int(11) DEFAULT NULL,
  `changed_module` varchar(128) DEFAULT NULL,
  `changed_method` varchar(128) DEFAULT NULL,
  `param` text,
  `param0` mediumtext,
  `user_id` int(11) DEFAULT NULL,
  `is_active` int(11) DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cms_backup`
--

LOCK TABLES `cms_backup` WRITE;
/*!40000 ALTER TABLE `cms_backup` DISABLE KEYS */;
INSERT INTO `cms_backup` VALUES (1,1553122592,'content','add','1','a:17:{s:4:\"path\";s:40:\"YWRtaW4vY29udGVudC9hZGQvMC9wYWdlL2RvLw==\";s:7:\"referer\";s:68:\"aHR0cDovL3Rlc3R0YXNrLm1hZGV4LnByby9hZG1pbi9jb250ZW50L3NpdGV0cmVlLw==\";s:6:\"domain\";s:24:\"dGVzdHRhc2subWFkZXgucHJv\";s:16:\"permissions-sent\";s:4:\"MQ==\";s:6:\"active\";s:4:\"MQ==\";s:4:\"name\";s:20:\"0JPQu9Cw0LLQvdCw0Y8=\";s:8:\"alt-name\";s:8:\"aW5kZXg=\";s:4:\"data\";a:1:{i:623;a:13:{s:5:\"title\";s:0:\"\";s:2:\"h1\";s:14:\"Главная\";s:13:\"meta_keywords\";s:0:\"\";s:17:\"meta_descriptions\";s:0:\"\";s:4:\"tags\";s:0:\"\";s:7:\"content\";s:0:\"\";s:11:\"menu_pic_ua\";s:0:\"\";s:10:\"menu_pic_a\";s:0:\"\";s:10:\"header_pic\";s:0:\"\";s:11:\"robots_deny\";s:1:\"0\";s:12:\"show_submenu\";s:1:\"0\";s:11:\"is_expanded\";s:1:\"0\";s:12:\"is_unindexed\";s:1:\"0\";}}s:11:\"template-id\";s:4:\"MQ==\";s:10:\"is-visible\";s:4:\"MQ==\";s:10:\"is-default\";s:4:\"MQ==\";s:10:\"perms_read\";a:1:{s:12:\"system-guest\";s:1:\"1\";}s:4:\"csrf\";s:44:\"NjVhMTE2ZGNmY2MyZjkzYzFjYTA3MTk2MTljNWFlNWI=\";s:8:\"pre_lang\";s:0:\"\";s:6:\"param0\";s:4:\"MA==\";s:6:\"param1\";s:8:\"cGFnZQ==\";s:6:\"param2\";s:4:\"ZG8=\";}',182,1),(2,1553123288,'news','add','2','a:17:{s:4:\"path\";s:36:\"YWRtaW4vbmV3cy9hZGQvMC9ydWJyaWMvZG8v\";s:7:\"referer\";s:60:\"aHR0cDovL3Rlc3R0YXNrLm1hZGV4LnByby9hZG1pbi9uZXdzL2xpc3RzLw==\";s:6:\"domain\";s:24:\"dGVzdHRhc2subWFkZXgucHJv\";s:16:\"permissions-sent\";s:4:\"MQ==\";s:6:\"active\";s:4:\"MQ==\";s:4:\"name\";s:12:\"0JHQu9C+0LM=\";s:8:\"alt-name\";s:8:\"YmxvZw==\";s:4:\"data\";a:1:{i:624;a:13:{s:5:\"title\";s:0:\"\";s:2:\"h1\";s:8:\"Блог\";s:13:\"meta_keywords\";s:0:\"\";s:17:\"meta_descriptions\";s:0:\"\";s:4:\"tags\";s:0:\"\";s:6:\"readme\";s:0:\"\";s:11:\"menu_pic_ua\";s:0:\"\";s:10:\"menu_pic_a\";s:0:\"\";s:10:\"header_pic\";s:0:\"\";s:11:\"robots_deny\";s:1:\"0\";s:12:\"show_submenu\";s:1:\"0\";s:11:\"is_expanded\";s:1:\"0\";s:12:\"is_unindexed\";s:1:\"0\";}}s:11:\"template-id\";s:4:\"MQ==\";s:10:\"is-visible\";s:4:\"MA==\";s:10:\"is-default\";s:4:\"MA==\";s:10:\"perms_read\";a:1:{s:12:\"system-guest\";s:1:\"1\";}s:4:\"csrf\";s:44:\"NjVhMTE2ZGNmY2MyZjkzYzFjYTA3MTk2MTljNWFlNWI=\";s:8:\"pre_lang\";s:0:\"\";s:6:\"param0\";s:4:\"MA==\";s:6:\"param1\";s:8:\"cnVicmlj\";s:6:\"param2\";s:4:\"ZG8=\";}',182,1),(3,1553123370,'news','add','3','a:17:{s:4:\"path\";s:36:\"YWRtaW4vbmV3cy9hZGQvMi9pdGVtL2RvLw==\";s:7:\"referer\";s:60:\"aHR0cDovL3Rlc3R0YXNrLm1hZGV4LnByby9hZG1pbi9uZXdzL2xpc3RzLw==\";s:6:\"domain\";s:24:\"dGVzdHRhc2subWFkZXgucHJv\";s:16:\"permissions-sent\";s:4:\"MQ==\";s:6:\"active\";s:4:\"MQ==\";s:4:\"name\";s:108:\"0KbQtdC90YLRgNCw0LvRjNC90YvQuSDQsNC/0L7Qs9C10Lk6INC/0YDQtdC00L/QvtGB0YvQu9C60Lgg0Lgg0YDQsNC30LLQuNGC0LjQtQ==\";s:8:\"alt-name\";s:56:\"Y2VudHJhbG55ai1hcG9nZWotcHJlZHBvc3lsa2ktaS1yYXp2aXRpZQ==\";s:4:\"data\";a:1:{i:625;a:22:{s:5:\"title\";s:0:\"\";s:2:\"h1\";s:79:\"Центральный апогей: предпосылки и развитие\";s:13:\"meta_keywords\";s:0:\"\";s:17:\"meta_descriptions\";s:0:\"\";s:4:\"tags\";s:0:\"\";s:5:\"anons\";s:573:\"<p>Космогоническая гипотеза Шмидта позволяет достаточно просто объяснить эту нестыковку, однако различное расположение вращает эксцентриситет. Азимут, на первый взгляд, неустойчив. Прямое восхождение ищет случайный метеорит. Полнолуние, после осторожного анализа, иллюстрирует вращательный тропический год.</p>\";s:7:\"content\";s:2212:\"<p>Космогоническая гипотеза Шмидта позволяет достаточно просто объяснить эту нестыковку, однако различное расположение вращает эксцентриситет. Азимут, на первый взгляд, неустойчив. Прямое восхождение ищет случайный метеорит. Полнолуние, после осторожного анализа, иллюстрирует вращательный тропический год.</p>\r\n<p>Пpотопланетное облако на следующий год, когда было лунное затмение и сгорел древний храм Афины в Афинах (при эфоре Питии и афинском архонте Каллии), гасит случайный большой круг небесной сферы. Противостояние, на первый взгляд, существенно дает тропический год. Декретное время непрерывно. Гелиоцентрическое расстояние, и это следует подчеркнуть, прекрасно гасит метеорный дождь. Магнитное поле вызывает афелий . Большой круг небесной сферы последовательно выбирает космический Тукан.</p>\r\n<p>Различное расположение, как бы это ни казалось парадоксальным, вращает популяционный индекс, однако большинство спутников движутся вокруг своих планет в ту же сторону, в какую вращаются планеты. Очевидно, что радиант точно колеблет центральный зенит. Весеннее равноденствие колеблет центральный дип-скай объект. Конечно, нельзя не принять во внимание тот факт, что натуральный логарифм разрушаем.</p>\";s:6:\"source\";s:0:\"\";s:10:\"source_url\";s:0:\"\";s:12:\"publish_time\";s:16:\"2019-03-21 02:08\";s:10:\"begin_time\";s:0:\"\";s:8:\"end_time\";s:0:\"\";s:11:\"menu_pic_ua\";s:0:\"\";s:10:\"menu_pic_a\";s:0:\"\";s:10:\"header_pic\";s:0:\"\";s:11:\"robots_deny\";s:1:\"0\";s:12:\"show_submenu\";s:1:\"0\";s:11:\"is_expanded\";s:1:\"0\";s:12:\"is_unindexed\";s:1:\"0\";s:9:\"anons_pic\";s:0:\"\";s:11:\"publish_pic\";s:0:\"\";s:8:\"subjects\";a:1:{i:0;s:0:\"\";}}}s:11:\"template-id\";s:4:\"MQ==\";s:10:\"is-visible\";s:4:\"MA==\";s:10:\"is-default\";s:4:\"MA==\";s:10:\"perms_read\";a:1:{s:12:\"system-guest\";s:1:\"1\";}s:4:\"csrf\";s:44:\"NjVhMTE2ZGNmY2MyZjkzYzFjYTA3MTk2MTljNWFlNWI=\";s:8:\"pre_lang\";s:0:\"\";s:6:\"param0\";s:4:\"Mg==\";s:6:\"param1\";s:8:\"aXRlbQ==\";s:6:\"param2\";s:4:\"ZG8=\";}',182,0),(4,1553123764,'news','edit','3','a:16:{s:4:\"path\";s:28:\"YWRtaW4vbmV3cy9lZGl0LzMvZG8v\";s:7:\"referer\";s:68:\"aHR0cDovL3Rlc3R0YXNrLm1hZGV4LnByby9hZG1pbi9jb250ZW50L3NpdGV0cmVlLw==\";s:6:\"domain\";s:24:\"dGVzdHRhc2subWFkZXgucHJv\";s:16:\"permissions-sent\";s:4:\"MQ==\";s:6:\"active\";s:4:\"MQ==\";s:4:\"name\";s:108:\"0KbQtdC90YLRgNCw0LvRjNC90YvQuSDQsNC/0L7Qs9C10Lk6INC/0YDQtdC00L/QvtGB0YvQu9C60Lgg0Lgg0YDQsNC30LLQuNGC0LjQtQ==\";s:8:\"alt-name\";s:56:\"Y2VudHJhbG55ai1hcG9nZWotcHJlZHBvc3lsa2ktaS1yYXp2aXRpZQ==\";s:4:\"data\";a:1:{i:625;a:23:{s:5:\"title\";s:0:\"\";s:2:\"h1\";s:79:\"Центральный апогей: предпосылки и развитие\";s:13:\"meta_keywords\";s:0:\"\";s:17:\"meta_descriptions\";s:0:\"\";s:4:\"tags\";s:0:\"\";s:6:\"author\";s:3:\"628\";s:5:\"anons\";s:573:\"<p>Космогоническая гипотеза Шмидта позволяет достаточно просто объяснить эту нестыковку, однако различное расположение вращает эксцентриситет. Азимут, на первый взгляд, неустойчив. Прямое восхождение ищет случайный метеорит. Полнолуние, после осторожного анализа, иллюстрирует вращательный тропический год.</p>\";s:7:\"content\";s:2212:\"<p>Космогоническая гипотеза Шмидта позволяет достаточно просто объяснить эту нестыковку, однако различное расположение вращает эксцентриситет. Азимут, на первый взгляд, неустойчив. Прямое восхождение ищет случайный метеорит. Полнолуние, после осторожного анализа, иллюстрирует вращательный тропический год.</p>\r\n<p>Пpотопланетное облако на следующий год, когда было лунное затмение и сгорел древний храм Афины в Афинах (при эфоре Питии и афинском архонте Каллии), гасит случайный большой круг небесной сферы. Противостояние, на первый взгляд, существенно дает тропический год. Декретное время непрерывно. Гелиоцентрическое расстояние, и это следует подчеркнуть, прекрасно гасит метеорный дождь. Магнитное поле вызывает афелий . Большой круг небесной сферы последовательно выбирает космический Тукан.</p>\r\n<p>Различное расположение, как бы это ни казалось парадоксальным, вращает популяционный индекс, однако большинство спутников движутся вокруг своих планет в ту же сторону, в какую вращаются планеты. Очевидно, что радиант точно колеблет центральный зенит. Весеннее равноденствие колеблет центральный дип-скай объект. Конечно, нельзя не принять во внимание тот факт, что натуральный логарифм разрушаем.</p>\";s:6:\"source\";s:0:\"\";s:10:\"source_url\";s:0:\"\";s:12:\"publish_time\";s:19:\"2019-03-21 02:08:00\";s:10:\"begin_time\";s:0:\"\";s:8:\"end_time\";s:0:\"\";s:11:\"menu_pic_ua\";s:0:\"\";s:10:\"menu_pic_a\";s:0:\"\";s:10:\"header_pic\";s:0:\"\";s:11:\"robots_deny\";s:1:\"0\";s:12:\"show_submenu\";s:1:\"0\";s:11:\"is_expanded\";s:1:\"0\";s:12:\"is_unindexed\";s:1:\"0\";s:9:\"anons_pic\";s:0:\"\";s:11:\"publish_pic\";s:0:\"\";s:8:\"subjects\";a:1:{i:0;s:0:\"\";}}}s:11:\"template-id\";s:4:\"MQ==\";s:10:\"is-visible\";s:4:\"MA==\";s:10:\"is-default\";s:4:\"MA==\";s:10:\"perms_read\";a:1:{s:12:\"system-guest\";s:1:\"1\";}s:4:\"csrf\";s:44:\"NjVhMTE2ZGNmY2MyZjkzYzFjYTA3MTk2MTljNWFlNWI=\";s:8:\"pre_lang\";s:0:\"\";s:6:\"param0\";s:4:\"Mw==\";s:6:\"param1\";s:4:\"ZG8=\";}',182,0),(5,1553123963,'news','edit','3','a:16:{s:4:\"path\";s:28:\"YWRtaW4vbmV3cy9lZGl0LzMvZG8v\";s:7:\"referer\";s:68:\"aHR0cDovL3Rlc3R0YXNrLm1hZGV4LnByby9hZG1pbi9jb250ZW50L3NpdGV0cmVlLw==\";s:6:\"domain\";s:24:\"dGVzdHRhc2subWFkZXgucHJv\";s:16:\"permissions-sent\";s:4:\"MQ==\";s:6:\"active\";s:4:\"MQ==\";s:4:\"name\";s:108:\"0KbQtdC90YLRgNCw0LvRjNC90YvQuSDQsNC/0L7Qs9C10Lk6INC/0YDQtdC00L/QvtGB0YvQu9C60Lgg0Lgg0YDQsNC30LLQuNGC0LjQtQ==\";s:8:\"alt-name\";s:56:\"Y2VudHJhbG55ai1hcG9nZWotcHJlZHBvc3lsa2ktaS1yYXp2aXRpZQ==\";s:4:\"data\";a:2:{i:625;a:23:{s:5:\"title\";s:0:\"\";s:2:\"h1\";s:79:\"Центральный апогей: предпосылки и развитие\";s:13:\"meta_keywords\";s:0:\"\";s:17:\"meta_descriptions\";s:0:\"\";s:4:\"tags\";s:0:\"\";s:6:\"author\";s:3:\"628\";s:5:\"anons\";s:573:\"<p>Космогоническая гипотеза Шмидта позволяет достаточно просто объяснить эту нестыковку, однако различное расположение вращает эксцентриситет. Азимут, на первый взгляд, неустойчив. Прямое восхождение ищет случайный метеорит. Полнолуние, после осторожного анализа, иллюстрирует вращательный тропический год.</p>\";s:7:\"content\";s:2212:\"<p>Космогоническая гипотеза Шмидта позволяет достаточно просто объяснить эту нестыковку, однако различное расположение вращает эксцентриситет. Азимут, на первый взгляд, неустойчив. Прямое восхождение ищет случайный метеорит. Полнолуние, после осторожного анализа, иллюстрирует вращательный тропический год.</p>\r\n<p>Пpотопланетное облако на следующий год, когда было лунное затмение и сгорел древний храм Афины в Афинах (при эфоре Питии и афинском архонте Каллии), гасит случайный большой круг небесной сферы. Противостояние, на первый взгляд, существенно дает тропический год. Декретное время непрерывно. Гелиоцентрическое расстояние, и это следует подчеркнуть, прекрасно гасит метеорный дождь. Магнитное поле вызывает афелий . Большой круг небесной сферы последовательно выбирает космический Тукан.</p>\r\n<p>Различное расположение, как бы это ни казалось парадоксальным, вращает популяционный индекс, однако большинство спутников движутся вокруг своих планет в ту же сторону, в какую вращаются планеты. Очевидно, что радиант точно колеблет центральный зенит. Весеннее равноденствие колеблет центральный дип-скай объект. Конечно, нельзя не принять во внимание тот факт, что натуральный логарифм разрушаем.</p>\";s:6:\"source\";s:0:\"\";s:10:\"source_url\";s:0:\"\";s:12:\"publish_time\";s:19:\"2019-03-21 02:08:00\";s:10:\"begin_time\";s:0:\"\";s:8:\"end_time\";s:0:\"\";s:11:\"menu_pic_ua\";s:0:\"\";s:10:\"menu_pic_a\";s:0:\"\";s:10:\"header_pic\";s:0:\"\";s:11:\"robots_deny\";s:1:\"0\";s:12:\"show_submenu\";s:1:\"0\";s:11:\"is_expanded\";s:1:\"0\";s:12:\"is_unindexed\";s:1:\"0\";s:9:\"anons_pic\";s:54:\"./images/cms/data/b048adbe43457545381673e7cf2d9d51.jpg\";s:11:\"publish_pic\";s:0:\"\";s:8:\"subjects\";a:1:{i:0;s:0:\"\";}}s:6:\"images\";a:1:{i:246;a:2:{s:3:\"alt\";s:0:\"\";s:5:\"title\";s:0:\"\";}}}s:11:\"template-id\";s:4:\"MQ==\";s:10:\"is-visible\";s:4:\"MA==\";s:10:\"is-default\";s:4:\"MA==\";s:10:\"perms_read\";a:1:{s:12:\"system-guest\";s:1:\"1\";}s:4:\"csrf\";s:44:\"NjVhMTE2ZGNmY2MyZjkzYzFjYTA3MTk2MTljNWFlNWI=\";s:8:\"pre_lang\";s:0:\"\";s:6:\"param0\";s:4:\"Mw==\";s:6:\"param1\";s:4:\"ZG8=\";}',182,1),(6,1553124103,'news','add','4','a:17:{s:4:\"path\";s:36:\"YWRtaW4vbmV3cy9hZGQvMi9pdGVtL2RvLw==\";s:7:\"referer\";s:68:\"aHR0cDovL3Rlc3R0YXNrLm1hZGV4LnByby9hZG1pbi9jb250ZW50L3NpdGV0cmVlLw==\";s:6:\"domain\";s:24:\"dGVzdHRhc2subWFkZXgucHJv\";s:16:\"permissions-sent\";s:4:\"MQ==\";s:6:\"active\";s:4:\"MQ==\";s:4:\"name\";s:144:\"0JzQtdC20L/Qu9Cw0L3QtdGC0L3Ri9C5INCz0L7QtNC+0LLQvtC5INC/0LDRgNCw0LvQu9Cw0LrRgTog0LzQtdGC0L7QtNC+0LvQvtCz0LjRjyDQuCDQvtGB0L7QsdC10L3QvdC+0YHRgtC4\";s:8:\"alt-name\";s:80:\"bWV6aHBsYW5ldG55ai1nb2Rvdm9qLXBhcmFsbGFrcy1tZXRvZG9sb2dpeWEtaS1vc29iZW5ub3N0aQ==\";s:4:\"data\";a:2:{s:6:\"images\";a:1:{i:246;a:2:{s:3:\"alt\";s:0:\"\";s:5:\"title\";s:0:\"\";}}i:629;a:23:{s:5:\"title\";s:0:\"\";s:2:\"h1\";s:108:\"Межпланетный годовой параллакс: методология и особенности\";s:13:\"meta_keywords\";s:0:\"\";s:17:\"meta_descriptions\";s:0:\"\";s:4:\"tags\";s:0:\"\";s:6:\"author\";s:3:\"628\";s:5:\"anons\";s:597:\"<p>В отличие от давно известных астрономам планет земной группы, лимб сложен. Все известные астероиды имеют прямое движение, при этом полнолуние ненаблюдаемо. Движение гасит нулевой меридиан, хотя галактику в созвездии Дракона можно назвать карликовой. Сарос дает межпланетный популяционный индекс. Тукан притягивает узел.</p>\";s:7:\"content\";s:3682:\"<div class=\"referats__text\">\r\n<p>В отличие от давно известных астрономам планет земной группы, лимб сложен. Все известные астероиды имеют прямое движение, при этом полнолуние ненаблюдаемо. Движение гасит нулевой меридиан, хотя галактику в созвездии Дракона можно назвать карликовой. Сарос дает межпланетный популяционный индекс. Тукан притягивает узел.</p>\r\n<p>Приливное трение ищет большой круг небесной сферы. Аргумент перигелия ничтожно вращает большой круг небесной сферы. Вселенная достаточно огромна, чтобы Тукан гасит часовой угол. Орбита, а там действительно могли быть видны звезды, о чем свидетельствует Фукидид наблюдаема.</p>\r\n<p>Многие кометы имеют два хвоста, однако уравнение времени существенно отражает случайный ионный хвост. Параметр, несмотря на внешние воздействия, традиционно оценивает эффективный диаметp. Спектральный класс недоступно представляет собой реликтовый ледник, но кольца видны только при 40&ndash;50.</p>\r\n</div>\r\n<div><button class=\"button button_theme_normal button_size_s referats__write referats__more i-bem button_js_inited\" type=\"button\" data-bem=\"{&quot;button&quot;:{}}\"><span class=\"button__text\">Ещё</span></button>\r\n<div class=\"clipboard referats__copy i-bem clipboard_js_inited\" data-bem=\"{&quot;clipboard&quot;:{&quot;uatraits&quot;:{&quot;isTouch&quot;:false,&quot;isMobile&quot;:false,&quot;postMessageSupport&quot;:true,&quot;isBrowser&quot;:true,&quot;historySupport&quot;:true,&quot;WebPSupport&quot;:true,&quot;SVGSupport&quot;:true,&quot;OSVersion&quot;:&quot;10.11.6&quot;,&quot;OSName&quot;:&quot;Mac OS X El Capitan&quot;,&quot;BrowserBaseVersion&quot;:&quot;72.0.3626.121&quot;,&quot;BrowserEngine&quot;:&quot;WebKit&quot;,&quot;OSFamily&quot;:&quot;MacOS&quot;,&quot;BrowserEngineVersion&quot;:&quot;537.36&quot;,&quot;BrowserVersion&quot;:&quot;72.0.3626.121&quot;,&quot;BrowserName&quot;:&quot;Chrome&quot;,&quot;CSP1Support&quot;:true,&quot;localStorageSupport&quot;:true,&quot;BrowserBase&quot;:&quot;Chromium&quot;,&quot;CSP2Support&quot;:true}}}\"><button class=\"button button_theme_normal button_size_s i-bem\" type=\"button\" data-bem=\"{&quot;button&quot;:{}}\"><span class=\"button__text\">Скопировать</span></button></div>\r\n<div class=\"referats__share\">\r\n<div class=\"share i-bem ya-share2 ya-share2_inited share_js_inited\" data-bem=\"{&quot;share&quot;:{&quot;id&quot;:&quot;referats&quot;,&quot;description&quot;:&quot;Служба Яндекс.Рефераты предназначена для студентов и школьников, дизайнеров и журналистов, создателей научных заявок и отчетов &mdash; для всех, кто относится к тексту, как к количеству знаков.&quot;,&quot;image&quot;:&quot;https://yastatic.net/q/referats/v1.2/static/i/referats.png&quot;}}\">\r\n<div class=\"ya-share2__container ya-share2__container_size_m\"></div>\r\n</div>\r\n</div>\r\n</div>\";s:6:\"source\";s:0:\"\";s:10:\"source_url\";s:0:\"\";s:12:\"publish_time\";s:16:\"2019-03-21 02:20\";s:10:\"begin_time\";s:0:\"\";s:8:\"end_time\";s:0:\"\";s:11:\"menu_pic_ua\";s:0:\"\";s:10:\"menu_pic_a\";s:0:\"\";s:10:\"header_pic\";s:0:\"\";s:11:\"robots_deny\";s:1:\"0\";s:12:\"show_submenu\";s:1:\"0\";s:11:\"is_expanded\";s:1:\"0\";s:12:\"is_unindexed\";s:1:\"0\";s:9:\"anons_pic\";s:54:\"./images/cms/data/17b0dde2b78fe50319181b74ebd5d8d3.jpg\";s:11:\"publish_pic\";s:0:\"\";s:8:\"subjects\";a:1:{i:0;s:0:\"\";}}}s:11:\"template-id\";s:4:\"MQ==\";s:10:\"is-visible\";s:4:\"MA==\";s:10:\"is-default\";s:4:\"MA==\";s:10:\"perms_read\";a:1:{s:12:\"system-guest\";s:1:\"1\";}s:4:\"csrf\";s:44:\"NjVhMTE2ZGNmY2MyZjkzYzFjYTA3MTk2MTljNWFlNWI=\";s:8:\"pre_lang\";s:0:\"\";s:6:\"param0\";s:4:\"Mg==\";s:6:\"param1\";s:8:\"aXRlbQ==\";s:6:\"param2\";s:4:\"ZG8=\";}',182,1),(7,1553124140,'news','add','5','a:17:{s:4:\"path\";s:36:\"YWRtaW4vbmV3cy9hZGQvMi9pdGVtL2RvLw==\";s:7:\"referer\";s:68:\"aHR0cDovL3Rlc3R0YXNrLm1hZGV4LnByby9hZG1pbi9jb250ZW50L3NpdGV0cmVlLw==\";s:6:\"domain\";s:24:\"dGVzdHRhc2subWFkZXgucHJv\";s:16:\"permissions-sent\";s:4:\"MQ==\";s:6:\"active\";s:4:\"MQ==\";s:4:\"name\";s:80:\"0J/QvtGH0LXQvNGDINC/0LDRgNCw0LvQu9C10LvRjNC90LAg0JvQtdGC0YPRh9Cw0Y8g0KDRi9Cx0LA/\";s:8:\"alt-name\";s:44:\"cG9jaGVtdS1wYXJhbGxlbG5hLWxldHVjaGF5YS1yeWJh\";s:4:\"data\";a:1:{i:630;a:23:{s:5:\"title\";s:0:\"\";s:2:\"h1\";s:60:\"Почему параллельна Летучая Рыба?\";s:13:\"meta_keywords\";s:0:\"\";s:17:\"meta_descriptions\";s:0:\"\";s:4:\"tags\";s:0:\"\";s:6:\"author\";s:0:\"\";s:5:\"anons\";s:802:\"<p>Надир, и это следует подчеркнуть, недоступно вызывает сарос (датировка приведена по Петавиусу, Цеху, Хайсу). Различное расположение, по определению, иллюстрирует апогей, таким образом, атмосферы этих планет плавно переходят в жидкую мантию. Лимб интуитивно понятен. Скоpость кометы в пеpигелии, как бы это ни казалось парадоксальным, прекрасно вращает космический мусор. Маятник Фуко представляет собой Юпитер. Параллакс неравномерен.</p>\";s:7:\"content\";s:2701:\"<p>Надир, и это следует подчеркнуть, недоступно вызывает сарос (датировка приведена по Петавиусу, Цеху, Хайсу). Различное расположение, по определению, иллюстрирует апогей, таким образом, атмосферы этих планет плавно переходят в жидкую мантию. Лимб интуитивно понятен. Скоpость кометы в пеpигелии, как бы это ни казалось парадоксальным, прекрасно вращает космический мусор. Маятник Фуко представляет собой Юпитер. Параллакс неравномерен.</p>\r\n<p>Многие кометы имеют два хвоста, однако Млечный Путь жизненно колеблет эксцентриситет &ndash; это скорее индикатор, чем примета. Зенитное часовое число отражает космический возмущающий фактор. Декретное время гасит перигелий. Перигей, это удалось установить по характеру спектра, слабопроницаем. Красноватая звездочка, оценивая блеск освещенного металического шарика, параллельна.</p>\r\n<p>Вселенная достаточно огромна, чтобы атомное время мгновенно. Приливное трение колеблет математический горизонт &ndash; север вверху, восток слева. Азимут выбирает непреложный дип-скай объект, и в этом вопросе достигнута такая точность расчетов, что, начиная с того дня, как мы видим, указанного Эннием и записанного в \"Больших анналах\", было вычислено время предшествовавших затмений солнца, начиная с того, которое в квинктильские ноны произошло в царствование Ромула. Вселенная достаточно огромна, чтобы комета Хейла-Боппа отражает годовой параллакс. Ионный хвост представляет собой космический Ганимед. Млечный Путь точно вызывает маятник Фуко.</p>\";s:6:\"source\";s:0:\"\";s:10:\"source_url\";s:0:\"\";s:12:\"publish_time\";s:16:\"2019-03-21 02:21\";s:10:\"begin_time\";s:0:\"\";s:8:\"end_time\";s:0:\"\";s:11:\"menu_pic_ua\";s:0:\"\";s:10:\"menu_pic_a\";s:0:\"\";s:10:\"header_pic\";s:0:\"\";s:11:\"robots_deny\";s:1:\"0\";s:12:\"show_submenu\";s:1:\"0\";s:11:\"is_expanded\";s:1:\"0\";s:12:\"is_unindexed\";s:1:\"0\";s:9:\"anons_pic\";s:0:\"\";s:11:\"publish_pic\";s:0:\"\";s:8:\"subjects\";a:1:{i:0;s:0:\"\";}}}s:11:\"template-id\";s:4:\"MQ==\";s:10:\"is-visible\";s:4:\"MA==\";s:10:\"is-default\";s:4:\"MA==\";s:10:\"perms_read\";a:1:{s:12:\"system-guest\";s:1:\"1\";}s:4:\"csrf\";s:44:\"NjVhMTE2ZGNmY2MyZjkzYzFjYTA3MTk2MTljNWFlNWI=\";s:8:\"pre_lang\";s:0:\"\";s:6:\"param0\";s:4:\"Mg==\";s:6:\"param1\";s:8:\"aXRlbQ==\";s:6:\"param2\";s:4:\"ZG8=\";}',182,0),(8,1553124155,'news','edit','5','a:16:{s:4:\"path\";s:28:\"YWRtaW4vbmV3cy9lZGl0LzUvZG8v\";s:7:\"referer\";s:68:\"aHR0cDovL3Rlc3R0YXNrLm1hZGV4LnByby9hZG1pbi9jb250ZW50L3NpdGV0cmVlLw==\";s:6:\"domain\";s:24:\"dGVzdHRhc2subWFkZXgucHJv\";s:16:\"permissions-sent\";s:4:\"MQ==\";s:6:\"active\";s:4:\"MQ==\";s:4:\"name\";s:80:\"0J/QvtGH0LXQvNGDINC/0LDRgNCw0LvQu9C10LvRjNC90LAg0JvQtdGC0YPRh9Cw0Y8g0KDRi9Cx0LA/\";s:8:\"alt-name\";s:44:\"cG9jaGVtdS1wYXJhbGxlbG5hLWxldHVjaGF5YS1yeWJh\";s:4:\"data\";a:2:{i:630;a:23:{s:5:\"title\";s:0:\"\";s:2:\"h1\";s:60:\"Почему параллельна Летучая Рыба?\";s:13:\"meta_keywords\";s:0:\"\";s:17:\"meta_descriptions\";s:0:\"\";s:4:\"tags\";s:0:\"\";s:6:\"author\";s:0:\"\";s:5:\"anons\";s:802:\"<p>Надир, и это следует подчеркнуть, недоступно вызывает сарос (датировка приведена по Петавиусу, Цеху, Хайсу). Различное расположение, по определению, иллюстрирует апогей, таким образом, атмосферы этих планет плавно переходят в жидкую мантию. Лимб интуитивно понятен. Скоpость кометы в пеpигелии, как бы это ни казалось парадоксальным, прекрасно вращает космический мусор. Маятник Фуко представляет собой Юпитер. Параллакс неравномерен.</p>\";s:7:\"content\";s:2701:\"<p>Надир, и это следует подчеркнуть, недоступно вызывает сарос (датировка приведена по Петавиусу, Цеху, Хайсу). Различное расположение, по определению, иллюстрирует апогей, таким образом, атмосферы этих планет плавно переходят в жидкую мантию. Лимб интуитивно понятен. Скоpость кометы в пеpигелии, как бы это ни казалось парадоксальным, прекрасно вращает космический мусор. Маятник Фуко представляет собой Юпитер. Параллакс неравномерен.</p>\r\n<p>Многие кометы имеют два хвоста, однако Млечный Путь жизненно колеблет эксцентриситет &ndash; это скорее индикатор, чем примета. Зенитное часовое число отражает космический возмущающий фактор. Декретное время гасит перигелий. Перигей, это удалось установить по характеру спектра, слабопроницаем. Красноватая звездочка, оценивая блеск освещенного металического шарика, параллельна.</p>\r\n<p>Вселенная достаточно огромна, чтобы атомное время мгновенно. Приливное трение колеблет математический горизонт &ndash; север вверху, восток слева. Азимут выбирает непреложный дип-скай объект, и в этом вопросе достигнута такая точность расчетов, что, начиная с того дня, как мы видим, указанного Эннием и записанного в \"Больших анналах\", было вычислено время предшествовавших затмений солнца, начиная с того, которое в квинктильские ноны произошло в царствование Ромула. Вселенная достаточно огромна, чтобы комета Хейла-Боппа отражает годовой параллакс. Ионный хвост представляет собой космический Ганимед. Млечный Путь точно вызывает маятник Фуко.</p>\";s:6:\"source\";s:0:\"\";s:10:\"source_url\";s:0:\"\";s:12:\"publish_time\";s:19:\"2019-03-21 02:21:00\";s:10:\"begin_time\";s:0:\"\";s:8:\"end_time\";s:0:\"\";s:11:\"menu_pic_ua\";s:0:\"\";s:10:\"menu_pic_a\";s:0:\"\";s:10:\"header_pic\";s:0:\"\";s:11:\"robots_deny\";s:1:\"0\";s:12:\"show_submenu\";s:1:\"0\";s:11:\"is_expanded\";s:1:\"0\";s:12:\"is_unindexed\";s:1:\"0\";s:9:\"anons_pic\";s:55:\"./images/cms/data/aleksandr-kuricyn-i-shvarcenegger.jpg\";s:11:\"publish_pic\";s:0:\"\";s:8:\"subjects\";a:1:{i:0;s:0:\"\";}}s:6:\"images\";a:1:{i:246;a:2:{s:3:\"alt\";s:0:\"\";s:5:\"title\";s:0:\"\";}}}s:11:\"template-id\";s:4:\"MQ==\";s:10:\"is-visible\";s:4:\"MA==\";s:10:\"is-default\";s:4:\"MA==\";s:10:\"perms_read\";a:1:{s:12:\"system-guest\";s:1:\"1\";}s:4:\"csrf\";s:44:\"NjVhMTE2ZGNmY2MyZjkzYzFjYTA3MTk2MTljNWFlNWI=\";s:8:\"pre_lang\";s:0:\"\";s:6:\"param0\";s:4:\"NQ==\";s:6:\"param1\";s:4:\"ZG8=\";}',182,1),(9,1553124238,'news','add','6','a:17:{s:4:\"path\";s:36:\"YWRtaW4vbmV3cy9hZGQvMi9pdGVtL2RvLw==\";s:7:\"referer\";s:68:\"aHR0cDovL3Rlc3R0YXNrLm1hZGV4LnByby9hZG1pbi9jb250ZW50L3NpdGV0cmVlLw==\";s:6:\"domain\";s:24:\"dGVzdHRhc2subWFkZXgucHJv\";s:16:\"permissions-sent\";s:4:\"MQ==\";s:6:\"active\";s:4:\"MQ==\";s:4:\"name\";s:108:\"0J/QvtGH0LXQvNGDINC/0L7RgtC10L3RhtC40LDQu9GM0L3QviDQv3DQvtGC0L7Qv9C70LDQvdC10YLQvdC+0LUg0L7QsdC70LDQutC+Pw==\";s:8:\"alt-name\";s:56:\"cG9jaGVtdS1wb3RlbmNpYWxuby1wcG90b3BsYW5ldG5vZS1vYmxha28=\";s:4:\"data\";a:1:{i:631;a:23:{s:5:\"title\";s:0:\"\";s:2:\"h1\";s:79:\"Почему потенциально пpотопланетное облако?\";s:13:\"meta_keywords\";s:0:\"\";s:17:\"meta_descriptions\";s:0:\"\";s:4:\"tags\";s:0:\"\";s:6:\"author\";s:3:\"628\";s:5:\"anons\";s:457:\"<p>Пpотопланетное облако, после осторожного анализа, непрерывно. Гелиоцентрическое расстояние отражает близкий лимб, учитывая, что в одном парсеке 3,26 световых года. Звезда отражает азимут. Зенит недоступно оценивает эллиптический метеорный дождь.</p>\";s:7:\"content\";s:1984:\"<p>Пpотопланетное облако, после осторожного анализа, непрерывно. Гелиоцентрическое расстояние отражает близкий лимб, учитывая, что в одном парсеке 3,26 световых года. Звезда отражает азимут. Зенит недоступно оценивает эллиптический метеорный дождь.</p>\r\n<p>Как мы уже знаем, противостояние постоянно. Элонгация вызывает космический сарос. Магнитное поле, следуя пионерской работе Эдвина Хаббла, вызывает поперечник. Различное расположение последовательно представляет собой вращательный ионный хвост, это довольно часто наблюдается у сверхновых звезд второго типа.</p>\r\n<p>Апогей отражает близкий дип-скай объект. Аномальная джетовая активность однородно представляет собой радиант. Это можно записать следующим образом: V = 29.8 * sqrt(2/r &ndash; 1/a) км/сек, где ось вероятна. Зенит прочно иллюстрирует случайный сарос, и в этом вопросе достигнута такая точность расчетов, что, начиная с того дня, как мы видим, указанного Эннием и записанного в \"Больших анналах\", было вычислено время предшествовавших затмений солнца, начиная с того, которое в квинктильские ноны произошло в царствование Ромула.</p>\";s:6:\"source\";s:0:\"\";s:10:\"source_url\";s:0:\"\";s:12:\"publish_time\";s:16:\"2019-03-21 02:22\";s:10:\"begin_time\";s:0:\"\";s:8:\"end_time\";s:0:\"\";s:11:\"menu_pic_ua\";s:0:\"\";s:10:\"menu_pic_a\";s:0:\"\";s:10:\"header_pic\";s:0:\"\";s:11:\"robots_deny\";s:1:\"0\";s:12:\"show_submenu\";s:1:\"0\";s:11:\"is_expanded\";s:1:\"0\";s:12:\"is_unindexed\";s:1:\"0\";s:9:\"anons_pic\";s:0:\"\";s:11:\"publish_pic\";s:0:\"\";s:8:\"subjects\";a:1:{i:0;s:0:\"\";}}}s:11:\"template-id\";s:4:\"MQ==\";s:10:\"is-visible\";s:4:\"MA==\";s:10:\"is-default\";s:4:\"MA==\";s:10:\"perms_read\";a:1:{s:12:\"system-guest\";s:1:\"1\";}s:4:\"csrf\";s:44:\"NjVhMTE2ZGNmY2MyZjkzYzFjYTA3MTk2MTljNWFlNWI=\";s:8:\"pre_lang\";s:0:\"\";s:6:\"param0\";s:4:\"Mg==\";s:6:\"param1\";s:8:\"aXRlbQ==\";s:6:\"param2\";s:4:\"ZG8=\";}',182,0),(10,1553124252,'news','edit','6','a:16:{s:4:\"path\";s:28:\"YWRtaW4vbmV3cy9lZGl0LzYvZG8v\";s:7:\"referer\";s:68:\"aHR0cDovL3Rlc3R0YXNrLm1hZGV4LnByby9hZG1pbi9jb250ZW50L3NpdGV0cmVlLw==\";s:6:\"domain\";s:24:\"dGVzdHRhc2subWFkZXgucHJv\";s:16:\"permissions-sent\";s:4:\"MQ==\";s:6:\"active\";s:4:\"MQ==\";s:4:\"name\";s:108:\"0J/QvtGH0LXQvNGDINC/0L7RgtC10L3RhtC40LDQu9GM0L3QviDQv3DQvtGC0L7Qv9C70LDQvdC10YLQvdC+0LUg0L7QsdC70LDQutC+Pw==\";s:8:\"alt-name\";s:56:\"cG9jaGVtdS1wb3RlbmNpYWxuby1wcG90b3BsYW5ldG5vZS1vYmxha28=\";s:4:\"data\";a:2:{i:631;a:23:{s:5:\"title\";s:0:\"\";s:2:\"h1\";s:79:\"Почему потенциально пpотопланетное облако?\";s:13:\"meta_keywords\";s:0:\"\";s:17:\"meta_descriptions\";s:0:\"\";s:4:\"tags\";s:0:\"\";s:6:\"author\";s:3:\"628\";s:5:\"anons\";s:457:\"<p>Пpотопланетное облако, после осторожного анализа, непрерывно. Гелиоцентрическое расстояние отражает близкий лимб, учитывая, что в одном парсеке 3,26 световых года. Звезда отражает азимут. Зенит недоступно оценивает эллиптический метеорный дождь.</p>\";s:7:\"content\";s:1984:\"<p>Пpотопланетное облако, после осторожного анализа, непрерывно. Гелиоцентрическое расстояние отражает близкий лимб, учитывая, что в одном парсеке 3,26 световых года. Звезда отражает азимут. Зенит недоступно оценивает эллиптический метеорный дождь.</p>\r\n<p>Как мы уже знаем, противостояние постоянно. Элонгация вызывает космический сарос. Магнитное поле, следуя пионерской работе Эдвина Хаббла, вызывает поперечник. Различное расположение последовательно представляет собой вращательный ионный хвост, это довольно часто наблюдается у сверхновых звезд второго типа.</p>\r\n<p>Апогей отражает близкий дип-скай объект. Аномальная джетовая активность однородно представляет собой радиант. Это можно записать следующим образом: V = 29.8 * sqrt(2/r &ndash; 1/a) км/сек, где ось вероятна. Зенит прочно иллюстрирует случайный сарос, и в этом вопросе достигнута такая точность расчетов, что, начиная с того дня, как мы видим, указанного Эннием и записанного в \"Больших анналах\", было вычислено время предшествовавших затмений солнца, начиная с того, которое в квинктильские ноны произошло в царствование Ромула.</p>\";s:6:\"source\";s:0:\"\";s:10:\"source_url\";s:0:\"\";s:12:\"publish_time\";s:19:\"2019-03-21 02:22:00\";s:10:\"begin_time\";s:0:\"\";s:8:\"end_time\";s:0:\"\";s:11:\"menu_pic_ua\";s:0:\"\";s:10:\"menu_pic_a\";s:0:\"\";s:10:\"header_pic\";s:0:\"\";s:11:\"robots_deny\";s:1:\"0\";s:12:\"show_submenu\";s:1:\"0\";s:11:\"is_expanded\";s:1:\"0\";s:12:\"is_unindexed\";s:1:\"0\";s:9:\"anons_pic\";s:58:\"./images/cms/data/e3d71ca4-96c7-40cb-a126-d6953fb3c788.jpg\";s:11:\"publish_pic\";s:0:\"\";s:8:\"subjects\";a:1:{i:0;s:0:\"\";}}s:6:\"images\";a:1:{i:246;a:2:{s:3:\"alt\";s:0:\"\";s:5:\"title\";s:0:\"\";}}}s:11:\"template-id\";s:4:\"MQ==\";s:10:\"is-visible\";s:4:\"MA==\";s:10:\"is-default\";s:4:\"MA==\";s:10:\"perms_read\";a:1:{s:12:\"system-guest\";s:1:\"1\";}s:4:\"csrf\";s:44:\"NjVhMTE2ZGNmY2MyZjkzYzFjYTA3MTk2MTljNWFlNWI=\";s:8:\"pre_lang\";s:0:\"\";s:6:\"param0\";s:4:\"Ng==\";s:6:\"param1\";s:4:\"ZG8=\";}',182,1),(11,1553124270,NULL,NULL,'5','1553124270',182,0),(12,1553124385,'news','edit','7','a:16:{s:4:\"path\";s:28:\"YWRtaW4vbmV3cy9lZGl0LzcvZG8v\";s:7:\"referer\";s:68:\"aHR0cDovL3Rlc3R0YXNrLm1hZGV4LnByby9hZG1pbi9jb250ZW50L3NpdGV0cmVlLw==\";s:6:\"domain\";s:24:\"dGVzdHRhc2subWFkZXgucHJv\";s:16:\"permissions-sent\";s:4:\"MQ==\";s:6:\"active\";s:4:\"MQ==\";s:4:\"name\";s:108:\"0J/QvtGH0LXQvNGDINC/0L7RgtC10L3RhtC40LDQu9GM0L3QviDQv3DQvtGC0L7Qv9C70LDQvdC10YLQvdC+0LUg0L7QsdC70LDQutC+Pw==\";s:8:\"alt-name\";s:56:\"cG9jaGVtdS1wb3RlbmNpYWxuby1wcG90b3BsYW5ldG5vZS1vYmxha28x\";s:4:\"data\";a:2:{i:632;a:23:{s:5:\"title\";s:0:\"\";s:2:\"h1\";s:79:\"Почему потенциально пpотопланетное облако?\";s:13:\"meta_keywords\";s:0:\"\";s:17:\"meta_descriptions\";s:0:\"\";s:4:\"tags\";s:0:\"\";s:6:\"author\";s:3:\"627\";s:5:\"anons\";s:457:\"<p>Пpотопланетное облако, после осторожного анализа, непрерывно. Гелиоцентрическое расстояние отражает близкий лимб, учитывая, что в одном парсеке 3,26 световых года. Звезда отражает азимут. Зенит недоступно оценивает эллиптический метеорный дождь.</p>\";s:7:\"content\";s:1984:\"<p>Пpотопланетное облако, после осторожного анализа, непрерывно. Гелиоцентрическое расстояние отражает близкий лимб, учитывая, что в одном парсеке 3,26 световых года. Звезда отражает азимут. Зенит недоступно оценивает эллиптический метеорный дождь.</p>\r\n<p>Как мы уже знаем, противостояние постоянно. Элонгация вызывает космический сарос. Магнитное поле, следуя пионерской работе Эдвина Хаббла, вызывает поперечник. Различное расположение последовательно представляет собой вращательный ионный хвост, это довольно часто наблюдается у сверхновых звезд второго типа.</p>\r\n<p>Апогей отражает близкий дип-скай объект. Аномальная джетовая активность однородно представляет собой радиант. Это можно записать следующим образом: V = 29.8 * sqrt(2/r &ndash; 1/a) км/сек, где ось вероятна. Зенит прочно иллюстрирует случайный сарос, и в этом вопросе достигнута такая точность расчетов, что, начиная с того дня, как мы видим, указанного Эннием и записанного в \"Больших анналах\", было вычислено время предшествовавших затмений солнца, начиная с того, которое в квинктильские ноны произошло в царствование Ромула.</p>\";s:6:\"source\";s:0:\"\";s:10:\"source_url\";s:0:\"\";s:12:\"publish_time\";s:19:\"2019-03-21 02:22:00\";s:10:\"begin_time\";s:0:\"\";s:8:\"end_time\";s:0:\"\";s:11:\"menu_pic_ua\";s:0:\"\";s:10:\"menu_pic_a\";s:0:\"\";s:10:\"header_pic\";s:0:\"\";s:11:\"robots_deny\";s:1:\"0\";s:12:\"show_submenu\";s:1:\"0\";s:11:\"is_expanded\";s:1:\"0\";s:12:\"is_unindexed\";s:1:\"0\";s:9:\"anons_pic\";s:54:\"./images/cms/data/714c9d2a045fd64730d692e3f419cc61.jpg\";s:11:\"publish_pic\";s:0:\"\";s:8:\"subjects\";a:1:{i:0;s:0:\"\";}}s:6:\"images\";a:1:{i:246;a:2:{s:3:\"alt\";s:0:\"\";s:5:\"title\";s:0:\"\";}}}s:11:\"template-id\";s:4:\"MQ==\";s:10:\"is-visible\";s:4:\"MA==\";s:10:\"is-default\";s:4:\"MA==\";s:10:\"perms_read\";a:1:{s:12:\"system-guest\";s:1:\"1\";}s:4:\"csrf\";s:44:\"NjVhMTE2ZGNmY2MyZjkzYzFjYTA3MTk2MTljNWFlNWI=\";s:8:\"pre_lang\";s:0:\"\";s:6:\"param0\";s:4:\"Nw==\";s:6:\"param1\";s:4:\"ZG8=\";}',182,1),(13,1553124416,'news','edit','8','a:16:{s:4:\"path\";s:28:\"YWRtaW4vbmV3cy9lZGl0LzgvZG8v\";s:7:\"referer\";s:68:\"aHR0cDovL3Rlc3R0YXNrLm1hZGV4LnByby9hZG1pbi9jb250ZW50L3NpdGV0cmVlLw==\";s:6:\"domain\";s:24:\"dGVzdHRhc2subWFkZXgucHJv\";s:16:\"permissions-sent\";s:4:\"MQ==\";s:6:\"active\";s:4:\"MQ==\";s:4:\"name\";s:80:\"0J/QvtGH0LXQvNGDINC/0LDRgNCw0LvQu9C10LvRjNC90LAg0JvQtdGC0YPRh9Cw0Y8g0KDRi9Cx0LA/\";s:8:\"alt-name\";s:48:\"cG9jaGVtdS1wYXJhbGxlbG5hLWxldHVjaGF5YS1yeWJhMQ==\";s:4:\"data\";a:2:{i:633;a:23:{s:5:\"title\";s:0:\"\";s:2:\"h1\";s:60:\"Почему параллельна Летучая Рыба?\";s:13:\"meta_keywords\";s:0:\"\";s:17:\"meta_descriptions\";s:0:\"\";s:4:\"tags\";s:0:\"\";s:6:\"author\";s:3:\"627\";s:5:\"anons\";s:802:\"<p>Надир, и это следует подчеркнуть, недоступно вызывает сарос (датировка приведена по Петавиусу, Цеху, Хайсу). Различное расположение, по определению, иллюстрирует апогей, таким образом, атмосферы этих планет плавно переходят в жидкую мантию. Лимб интуитивно понятен. Скоpость кометы в пеpигелии, как бы это ни казалось парадоксальным, прекрасно вращает космический мусор. Маятник Фуко представляет собой Юпитер. Параллакс неравномерен.</p>\";s:7:\"content\";s:2701:\"<p>Надир, и это следует подчеркнуть, недоступно вызывает сарос (датировка приведена по Петавиусу, Цеху, Хайсу). Различное расположение, по определению, иллюстрирует апогей, таким образом, атмосферы этих планет плавно переходят в жидкую мантию. Лимб интуитивно понятен. Скоpость кометы в пеpигелии, как бы это ни казалось парадоксальным, прекрасно вращает космический мусор. Маятник Фуко представляет собой Юпитер. Параллакс неравномерен.</p>\r\n<p>Многие кометы имеют два хвоста, однако Млечный Путь жизненно колеблет эксцентриситет &ndash; это скорее индикатор, чем примета. Зенитное часовое число отражает космический возмущающий фактор. Декретное время гасит перигелий. Перигей, это удалось установить по характеру спектра, слабопроницаем. Красноватая звездочка, оценивая блеск освещенного металического шарика, параллельна.</p>\r\n<p>Вселенная достаточно огромна, чтобы атомное время мгновенно. Приливное трение колеблет математический горизонт &ndash; север вверху, восток слева. Азимут выбирает непреложный дип-скай объект, и в этом вопросе достигнута такая точность расчетов, что, начиная с того дня, как мы видим, указанного Эннием и записанного в \"Больших анналах\", было вычислено время предшествовавших затмений солнца, начиная с того, которое в квинктильские ноны произошло в царствование Ромула. Вселенная достаточно огромна, чтобы комета Хейла-Боппа отражает годовой параллакс. Ионный хвост представляет собой космический Ганимед. Млечный Путь точно вызывает маятник Фуко.</p>\";s:6:\"source\";s:0:\"\";s:10:\"source_url\";s:0:\"\";s:12:\"publish_time\";s:19:\"2019-03-21 02:21:00\";s:10:\"begin_time\";s:0:\"\";s:8:\"end_time\";s:0:\"\";s:11:\"menu_pic_ua\";s:0:\"\";s:10:\"menu_pic_a\";s:0:\"\";s:10:\"header_pic\";s:0:\"\";s:11:\"robots_deny\";s:1:\"0\";s:12:\"show_submenu\";s:1:\"0\";s:11:\"is_expanded\";s:1:\"0\";s:12:\"is_unindexed\";s:1:\"0\";s:9:\"anons_pic\";s:25:\"./images/cms/data/007.png\";s:11:\"publish_pic\";s:0:\"\";s:8:\"subjects\";a:1:{i:0;s:0:\"\";}}s:6:\"images\";a:1:{i:246;a:2:{s:3:\"alt\";s:0:\"\";s:5:\"title\";s:0:\"\";}}}s:11:\"template-id\";s:4:\"MQ==\";s:10:\"is-visible\";s:4:\"MA==\";s:10:\"is-default\";s:4:\"MA==\";s:10:\"perms_read\";a:1:{s:12:\"system-guest\";s:1:\"1\";}s:4:\"csrf\";s:44:\"NjVhMTE2ZGNmY2MyZjkzYzFjYTA3MTk2MTljNWFlNWI=\";s:8:\"pre_lang\";s:0:\"\";s:6:\"param0\";s:4:\"OA==\";s:6:\"param1\";s:4:\"ZG8=\";}',182,1),(14,1553124437,'news','edit','9','a:16:{s:4:\"path\";s:28:\"YWRtaW4vbmV3cy9lZGl0LzkvZG8v\";s:7:\"referer\";s:68:\"aHR0cDovL3Rlc3R0YXNrLm1hZGV4LnByby9hZG1pbi9jb250ZW50L3NpdGV0cmVlLw==\";s:6:\"domain\";s:24:\"dGVzdHRhc2subWFkZXgucHJv\";s:16:\"permissions-sent\";s:4:\"MQ==\";s:6:\"active\";s:4:\"MQ==\";s:4:\"name\";s:144:\"0JzQtdC20L/Qu9Cw0L3QtdGC0L3Ri9C5INCz0L7QtNC+0LLQvtC5INC/0LDRgNCw0LvQu9Cw0LrRgTog0LzQtdGC0L7QtNC+0LvQvtCz0LjRjyDQuCDQvtGB0L7QsdC10L3QvdC+0YHRgtC4\";s:8:\"alt-name\";s:80:\"bWV6aHBsYW5ldG55ai1nb2Rvdm9qLXBhcmFsbGFrcy1tZXRvZG9sb2dpeWEtaS1vc29iZW5ub3N0aTE=\";s:4:\"data\";a:2:{i:634;a:23:{s:5:\"title\";s:0:\"\";s:2:\"h1\";s:108:\"Межпланетный годовой параллакс: методология и особенности\";s:13:\"meta_keywords\";s:0:\"\";s:17:\"meta_descriptions\";s:0:\"\";s:4:\"tags\";s:0:\"\";s:6:\"author\";s:3:\"627\";s:5:\"anons\";s:597:\"<p>В отличие от давно известных астрономам планет земной группы, лимб сложен. Все известные астероиды имеют прямое движение, при этом полнолуние ненаблюдаемо. Движение гасит нулевой меридиан, хотя галактику в созвездии Дракона можно назвать карликовой. Сарос дает межпланетный популяционный индекс. Тукан притягивает узел.</p>\";s:7:\"content\";s:2942:\"<div class=\"referats__text\">\r\n<p>В отличие от давно известных астрономам планет земной группы, лимб сложен. Все известные астероиды имеют прямое движение, при этом полнолуние ненаблюдаемо. Движение гасит нулевой меридиан, хотя галактику в созвездии Дракона можно назвать карликовой. Сарос дает межпланетный популяционный индекс. Тукан притягивает узел.</p>\r\n<p>Приливное трение ищет большой круг небесной сферы. Аргумент перигелия ничтожно вращает большой круг небесной сферы. Вселенная достаточно огромна, чтобы Тукан гасит часовой угол. Орбита, а там действительно могли быть видны звезды, о чем свидетельствует Фукидид наблюдаема.</p>\r\n<p>Многие кометы имеют два хвоста, однако уравнение времени существенно отражает случайный ионный хвост. Параметр, несмотря на внешние воздействия, традиционно оценивает эффективный диаметp. Спектральный класс недоступно представляет собой реликтовый ледник, но кольца видны только при 40&ndash;50.</p>\r\n</div>\r\n<div><button class=\"button button_theme_normal button_size_s referats__write referats__more i-bem button_js_inited\" type=\"button\" data-bem=\"{\"><span class=\"button__text\">Ещё</span></button>\r\n<div class=\"clipboard referats__copy i-bem clipboard_js_inited\" data-bem=\"{\" clipboard=\"\" :=\"\" uatraits=\"\" istouch=\"\" :false=\"\" ismobile=\"\" postmessagesupport=\"\" :true=\"\" isbrowser=\"\" historysupport=\"\" webpsupport=\"\" svgsupport=\"\" osversion=\"\" 10=\"\" 11=\"\" 6=\"\" osname=\"\" mac=\"\" os=\"\" x=\"\" el=\"\" capitan=\"\" browserbaseversion=\"\" 72=\"\" 0=\"\" 3626=\"\" 121=\"\" browserengine=\"\" webkit=\"\" osfamily=\"\" macos=\"\" browserengineversion=\"\" 537=\"\" 36=\"\" browserversion=\"\" browsername=\"\" chrome=\"\" csp1support=\"\" localstoragesupport=\"\" browserbase=\"\" chromium=\"\" csp2support=\"\"><button class=\"button button_theme_normal button_size_s i-bem\" type=\"button\" data-bem=\"{\"><span class=\"button__text\">Скопировать</span></button></div>\r\n<div class=\"referats__share\">\r\n<div class=\"share i-bem ya-share2 ya-share2_inited share_js_inited\" data-bem=\"{\" share=\"\" :=\"\" id=\"\" referats=\"\" description=\"\" image=\"\" https:=\"\" yastatic=\"\" net=\"\" q=\"\" v1=\"\" 2=\"\" static=\"\" i=\"\" png=\"\">\r\n<div class=\"ya-share2__container ya-share2__container_size_m\"></div>\r\n</div>\r\n</div>\r\n</div>\";s:6:\"source\";s:0:\"\";s:10:\"source_url\";s:0:\"\";s:12:\"publish_time\";s:19:\"2019-03-21 02:20:00\";s:10:\"begin_time\";s:0:\"\";s:8:\"end_time\";s:0:\"\";s:11:\"menu_pic_ua\";s:0:\"\";s:10:\"menu_pic_a\";s:0:\"\";s:10:\"header_pic\";s:0:\"\";s:11:\"robots_deny\";s:1:\"0\";s:12:\"show_submenu\";s:1:\"0\";s:11:\"is_expanded\";s:1:\"0\";s:12:\"is_unindexed\";s:1:\"0\";s:9:\"anons_pic\";s:54:\"./images/cms/data/23705de020d91f66a81b18dbf12544a8.jpg\";s:11:\"publish_pic\";s:0:\"\";s:8:\"subjects\";a:1:{i:0;s:0:\"\";}}s:6:\"images\";a:1:{i:246;a:2:{s:3:\"alt\";s:0:\"\";s:5:\"title\";s:0:\"\";}}}s:11:\"template-id\";s:4:\"MQ==\";s:10:\"is-visible\";s:4:\"MA==\";s:10:\"is-default\";s:4:\"MA==\";s:10:\"perms_read\";a:1:{s:12:\"system-guest\";s:1:\"1\";}s:4:\"csrf\";s:44:\"NjVhMTE2ZGNmY2MyZjkzYzFjYTA3MTk2MTljNWFlNWI=\";s:8:\"pre_lang\";s:0:\"\";s:6:\"param0\";s:4:\"OQ==\";s:6:\"param1\";s:4:\"ZG8=\";}',182,1),(15,1553124487,'news','edit','10','a:16:{s:4:\"path\";s:32:\"YWRtaW4vbmV3cy9lZGl0LzEwL2RvLw==\";s:7:\"referer\";s:68:\"aHR0cDovL3Rlc3R0YXNrLm1hZGV4LnByby9hZG1pbi9jb250ZW50L3NpdGV0cmVlLw==\";s:6:\"domain\";s:24:\"dGVzdHRhc2subWFkZXgucHJv\";s:16:\"permissions-sent\";s:4:\"MQ==\";s:6:\"active\";s:4:\"MQ==\";s:4:\"name\";s:80:\"0J/QvtGH0LXQvNGDINC/0LDRgNCw0LvQu9C10LvRjNC90LAg0JvQtdGC0YPRh9Cw0Y8g0KDRi9Cx0LA/\";s:8:\"alt-name\";s:48:\"cG9jaGVtdS1wYXJhbGxlbG5hLWxldHVjaGF5YS1yeWJhMg==\";s:4:\"data\";a:2:{i:635;a:23:{s:5:\"title\";s:0:\"\";s:2:\"h1\";s:60:\"Почему параллельна Летучая Рыба?\";s:13:\"meta_keywords\";s:0:\"\";s:17:\"meta_descriptions\";s:0:\"\";s:4:\"tags\";s:0:\"\";s:6:\"author\";s:3:\"626\";s:5:\"anons\";s:802:\"<p>Надир, и это следует подчеркнуть, недоступно вызывает сарос (датировка приведена по Петавиусу, Цеху, Хайсу). Различное расположение, по определению, иллюстрирует апогей, таким образом, атмосферы этих планет плавно переходят в жидкую мантию. Лимб интуитивно понятен. Скоpость кометы в пеpигелии, как бы это ни казалось парадоксальным, прекрасно вращает космический мусор. Маятник Фуко представляет собой Юпитер. Параллакс неравномерен.</p>\";s:7:\"content\";s:2701:\"<p>Надир, и это следует подчеркнуть, недоступно вызывает сарос (датировка приведена по Петавиусу, Цеху, Хайсу). Различное расположение, по определению, иллюстрирует апогей, таким образом, атмосферы этих планет плавно переходят в жидкую мантию. Лимб интуитивно понятен. Скоpость кометы в пеpигелии, как бы это ни казалось парадоксальным, прекрасно вращает космический мусор. Маятник Фуко представляет собой Юпитер. Параллакс неравномерен.</p>\r\n<p>Многие кометы имеют два хвоста, однако Млечный Путь жизненно колеблет эксцентриситет &ndash; это скорее индикатор, чем примета. Зенитное часовое число отражает космический возмущающий фактор. Декретное время гасит перигелий. Перигей, это удалось установить по характеру спектра, слабопроницаем. Красноватая звездочка, оценивая блеск освещенного металического шарика, параллельна.</p>\r\n<p>Вселенная достаточно огромна, чтобы атомное время мгновенно. Приливное трение колеблет математический горизонт &ndash; север вверху, восток слева. Азимут выбирает непреложный дип-скай объект, и в этом вопросе достигнута такая точность расчетов, что, начиная с того дня, как мы видим, указанного Эннием и записанного в \"Больших анналах\", было вычислено время предшествовавших затмений солнца, начиная с того, которое в квинктильские ноны произошло в царствование Ромула. Вселенная достаточно огромна, чтобы комета Хейла-Боппа отражает годовой параллакс. Ионный хвост представляет собой космический Ганимед. Млечный Путь точно вызывает маятник Фуко.</p>\";s:6:\"source\";s:0:\"\";s:10:\"source_url\";s:0:\"\";s:12:\"publish_time\";s:19:\"2019-03-21 02:21:00\";s:10:\"begin_time\";s:0:\"\";s:8:\"end_time\";s:0:\"\";s:11:\"menu_pic_ua\";s:0:\"\";s:10:\"menu_pic_a\";s:0:\"\";s:10:\"header_pic\";s:0:\"\";s:11:\"robots_deny\";s:1:\"0\";s:12:\"show_submenu\";s:1:\"0\";s:11:\"is_expanded\";s:1:\"0\";s:12:\"is_unindexed\";s:1:\"0\";s:9:\"anons_pic\";s:36:\"./images/cms/data/avatar_107_max.jpg\";s:11:\"publish_pic\";s:0:\"\";s:8:\"subjects\";a:1:{i:0;s:0:\"\";}}s:6:\"images\";a:1:{i:246;a:2:{s:3:\"alt\";s:0:\"\";s:5:\"title\";s:0:\"\";}}}s:11:\"template-id\";s:4:\"MQ==\";s:10:\"is-visible\";s:4:\"MA==\";s:10:\"is-default\";s:4:\"MA==\";s:10:\"perms_read\";a:1:{s:12:\"system-guest\";s:1:\"1\";}s:4:\"csrf\";s:44:\"NjVhMTE2ZGNmY2MyZjkzYzFjYTA3MTk2MTljNWFlNWI=\";s:8:\"pre_lang\";s:0:\"\";s:6:\"param0\";s:4:\"MTA=\";s:6:\"param1\";s:4:\"ZG8=\";}',182,1);
/*!40000 ALTER TABLE `cms_backup` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cms_permissions`
--

DROP TABLE IF EXISTS `cms_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cms_permissions` (
  `module` varchar(64) DEFAULT NULL,
  `method` varchar(64) DEFAULT NULL,
  `owner_id` int(10) unsigned DEFAULT NULL,
  `allow` tinyint(4) DEFAULT '1',
  KEY `module` (`module`),
  KEY `method` (`method`),
  KEY `owner_id` (`owner_id`),
  KEY `allow` (`allow`),
  CONSTRAINT `FK_PermissionOwnerId_To_ObjectId` FOREIGN KEY (`owner_id`) REFERENCES `cms3_objects` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cms_permissions`
--

LOCK TABLES `cms_permissions` WRITE;
/*!40000 ALTER TABLE `cms_permissions` DISABLE KEYS */;
INSERT INTO `cms_permissions` VALUES ('domain','1',618,1),('content','content',618,1),('users','registrate',618,1),('data','2373',618,1),('data','main',618,1),('catalog','view',618,1),('news','view',618,1),('vote','poll',618,1),('vote','post',618,1),('search','search',618,1),('forum','view',618,1),('banners','insert',618,1),('banners','go_to',618,1),('dispatches','subscribe',618,1),('comments','insert',618,1),('webforms','add',618,1),('webforms','insert',618,1),('webforms','messages',618,1),('stat','tagsCloud',618,1),('faq','projects',618,1),('faq','post_question',618,1),('photoalbum','albums',618,1),('filemanager','list_files',618,1),('filemanager','download',618,1),('seo','guest',618,1),('blogs20','common',618,1),('emarket','purchasing',618,1),('emarket','compare',618,1),('emarket','personal',618,1),('exchange','auto',618,1),('exchange','get_export',618,1),('social_networks','view',618,1),('menu','view',618,1),('appointment','enroll',618,1),('umiSliders','view',618,1),('umiSettings','read',618,1),('umiStub','stub',618,1),('config','cron_http_execute',618,1),('users','settings',619,1),('blogs20','add',619,1);
/*!40000 ALTER TABLE `cms_permissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cms_reg`
--

DROP TABLE IF EXISTS `cms_reg`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cms_reg` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `var` varchar(48) NOT NULL,
  `val` varchar(255) DEFAULT NULL,
  `rel` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `var` (`var`),
  KEY `rel` (`rel`,`var`)
) ENGINE=InnoDB AUTO_INCREMENT=311 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cms_reg`
--

LOCK TABLES `cms_reg` WRITE;
/*!40000 ALTER TABLE `cms_reg` DISABLE KEYS */;
INSERT INTO `cms_reg` VALUES (1,'modules','',0),(2,'events','events',1),(3,'name','events',2),(4,'title','Events',2),(5,'description','Events',2),(6,'filename','modules/events/class.php',2),(7,'config','1',2),(8,'ico','ico_events',2),(9,'default_method','getUserSettings',2),(10,'default_method_admin','last',2),(11,'max-days-storing-events','30',2),(12,'collect-events','0',2),(13,'menu','menu',1),(14,'name','menu',13),(15,'filename','modules/menu/class.php',13),(16,'config','0',13),(17,'ico','ico_menu',13),(18,'default_method','show',13),(19,'default_method_admin','lists',13),(20,'per_page','10',13),(21,'news','news',1),(22,'name','news',21),(23,'filename','modules/news/class.php',21),(24,'config','1',21),(25,'default_method','archive',21),(26,'default_method_admin','lists',21),(27,'per_page','10',21),(28,'content','content',1),(29,'name','content',28),(30,'filename','modules/content/class.php',28),(31,'config','1',28),(32,'default_method','content',28),(33,'default_method_admin','sitetree',28),(34,'blogs20','blogs20',1),(35,'verison','2.0.0.0',34),(36,'name','blogs20',34),(37,'filename','modules/blogs20/class.php',34),(38,'config','1',34),(39,'ico','ico_blogs20',34),(40,'default_method','blogsList',34),(41,'default_method_admin','posts',34),(42,'paging','',34),(43,'blogs','10',42),(44,'posts','10',42),(45,'comments','50',42),(46,'blogs_per_user','5',34),(47,'allow_guest_comments','1',34),(48,'notifications','',34),(49,'on_comment_add','1',48),(50,'forum','forum',1),(51,'name','forum',50),(52,'filename','modules/forum/class.php',50),(53,'config','1',50),(54,'default_method','show',50),(55,'default_method_admin','confs_list',50),(56,'need_moder','0',50),(57,'allow_guest','0',50),(58,'per_page','25',50),(59,'sort_by_last_message','0',50),(60,'dispatch_id','26073',50),(61,'comments','comments',1),(62,'name','comments',61),(63,'title','Комментарии',61),(64,'filename','modules/comments/class.php',61),(65,'config','1',61),(66,'default_method','void_func',61),(67,'default_method_admin','view_comments',61),(68,'per_page','10',61),(69,'moderated','0',61),(70,'guest_posting','0',61),(71,'allow_guest','1',61),(72,'default_comments','1',61),(73,'vkontakte','0',61),(74,'vk_per_page','0',61),(75,'vk_width','0',61),(76,'vk_api','',61),(77,'vk_extend','0',61),(78,'facebook','0',61),(79,'fb_per_page','0',61),(80,'fb_width','0',61),(81,'fb_colorscheme','light',61),(82,'vote','vote',1),(83,'name','vote',82),(84,'filename','modules/vote/class.php',82),(85,'config','1',82),(86,'default_method','insertlast',82),(87,'default_method_admin','lists',82),(88,'webforms','webforms',1),(89,'name','webforms',88),(90,'filename','webforms/class.php',88),(91,'config','0',88),(92,'default_method','insert',88),(93,'default_method_admin','addresses',88),(94,'imported','1',88),(95,'photoalbum','photoalbum',1),(96,'name','photoalbum',95),(97,'filename','modules/photoalbum/class.php',95),(98,'config','1',95),(99,'default_method','albums',95),(100,'default_method_admin','lists',95),(101,'per_page','10',95),(102,'faq','faq',1),(103,'name','faq',102),(104,'filename','modules/faq/class.php',102),(105,'config','1',102),(106,'default_method','project',102),(107,'default_method_admin','projects_list',102),(108,'per_page','10',102),(109,'disable_new_question_notification','0',102),(110,'dispatches','dispatches',1),(111,'name','dispatches',110),(112,'filename','dispatches/class.php',110),(113,'config','1',110),(114,'default_method','subscribe',110),(115,'default_method_admin','lists',110),(116,'catalog','catalog',1),(117,'name','catalog',116),(118,'title','Каталог',116),(119,'filename','modules/catalog/class.php',116),(120,'config','1',116),(121,'default_method','category',116),(122,'default_method_admin','tree',116),(123,'per_page','25',116),(124,'emarket','emarket',1),(125,'version','2.8.0.5',124),(126,'version_line','pro',124),(127,'name','emarket',124),(128,'title','Интернет-магазин',124),(129,'filename','modules/emarket/class.php',124),(130,'config','1',124),(131,'ico','ico_eshop',124),(132,'default_method_admin','orders',124),(133,'enable-discounts','1',124),(134,'enable-currency','1',124),(135,'enable-stores','1',124),(136,'enable-payment','1',124),(137,'enable-delivery','1',124),(138,'social_vkontakte_merchant_id','',124),(139,'social_vkontakte_key','',124),(140,'social_vkontakte_wishlist','1',124),(141,'social_vkontakte_order','1',124),(142,'social_vkontakte_testmode','1',124),(143,'delivery-with-address','0',124),(144,'default-store-city','Санкт-Петербург',124),(145,'default-store-contact-email','hariton.moiseevich@umisoft.ru',124),(146,'default-store-contact-full-name','Харитон Моисеевич',124),(147,'default-store-contact-phone','88123090315',124),(148,'default-store-country-code','RU',124),(149,'default-store-house-number','25ж',124),(150,'default-store-region','Санкт-Петербург',124),(151,'default-store-street','Красного курсанта',124),(152,'order-defaultHeight','60',124),(153,'order-defaultLength','40',124),(154,'order-defaultWidth','40',124),(155,'order-defaultWeight','1000',124),(156,'default-store-index','197110',124),(157,'banners','banners',1),(158,'name','banners',157),(159,'filename','banners/class.php',157),(160,'config','1',157),(161,'default_method','insert_banner',157),(162,'default_method_admin','lists',157),(163,'users','users',1),(164,'name','users',163),(165,'filename','modules/users/class.php',163),(166,'config','1',163),(167,'default_method','auth',163),(168,'default_method_admin','users_list_all',163),(169,'def_group','619',163),(170,'pages_permissions_changing_enabled_on_add','1',163),(171,'pages_permissions_changing_enabled_on_edit','0',163),(172,'stat','stat',1),(173,'name','stat',172),(174,'filename','modules/stat/class.php',172),(175,'config','1',172),(176,'default_method','sess_refresh',172),(177,'default_method_admin','yandexMetric',172),(178,'collect','0',172),(179,'delete_after','30',172),(180,'items_per_page','100',172),(181,'seo','seo',1),(182,'name','seo',181),(183,'filename','modules/seo/class.php',181),(184,'config','1',181),(185,'default_method','show',181),(186,'default_method_admin','webmaster',181),(187,'megaindex-login','megaindex@umisoft.ru',181),(188,'megaindex-password','et676e5rj',181),(189,'exchange','exchange',1),(190,'name','exchange',189),(191,'filename','modules/exchange/class.php',189),(192,'config','1',189),(193,'ico','exchange',189),(194,'default_method','import',189),(195,'default_method_admin','import',189),(196,'social_networks','social_networks',1),(197,'version','1',196),(198,'name','social_networks',196),(199,'title','Интеграция с социальными сетями',196),(200,'filename','modules/social_networks/class.php',196),(201,'config','0',196),(202,'ico','ico_social_networks',196),(203,'default_method','vkontakte',196),(204,'default_method_admin','vkontakte',196),(205,'config','config',1),(206,'name','config',205),(207,'filename','modules/config/class.php',205),(208,'config','0',205),(209,'default_method','test',205),(210,'default_method_admin','main',205),(211,'tickets','tickets',1),(212,'name','tickets',211),(213,'filename','modules/tickets/class.php',211),(214,'config','0',211),(215,'ico','ico_tickets',211),(216,'default_method','',211),(217,'default_method_admin','tickets',211),(218,'data','data',1),(219,'name','data',218),(220,'filename','modules/data/class.php',218),(221,'config','1',218),(222,'default_method','test',218),(223,'default_method_admin','types',218),(224,'autoupdate','autoupdate',1),(225,'name','autoupdate',224),(226,'filename','modules/autoupdate/class.php',224),(227,'config','0',224),(228,'default_method','updateall',224),(229,'default_method_admin','versions',224),(230,'backup','backup',1),(231,'name','backup',230),(232,'title','Backups',230),(233,'filename','modules/backup/class.php',230),(234,'config','1',230),(235,'default_method','temp_method',230),(236,'default_method_admin','snapshots',230),(237,'max_timelimit','30',230),(238,'max_save_actions','50',230),(239,'enabled','1',230),(240,'search','search',1),(241,'name','search',240),(242,'filename','modules/search/class.php',240),(243,'default_method','search_do',240),(244,'default_method_admin','index_control',240),(245,'per_page','10',240),(246,'one_iteration_index','5',240),(247,'config','1',240),(248,'filemanager','filemanager',1),(249,'name','filemanager',248),(250,'description','Управление файловой системой.',248),(251,'filename','modules/filemanager/class.php',248),(252,'config','0',248),(253,'default_method','list_files',248),(254,'default_method_admin','shared_files',248),(255,'umiRedirects','umiRedirects',1),(256,'config','1',255),(257,'name','umiRedirects',255),(258,'default_method','empty',255),(259,'default_method_admin','lists',255),(260,'umiSliders','umiSliders',1),(261,'config','1',260),(262,'name','umiSliders',260),(263,'default_method','empty',260),(264,'default_method_admin','getSliders',260),(265,'default_sliding_speed','1',260),(266,'default_sliding_delay','1',260),(267,'default_sliding_slides_count','10',260),(268,'umiNotifications','umiNotifications',1),(269,'default_method','empty',268),(270,'default_method_admin','notifications',268),(271,'config','0',268),(272,'trash','trash',1),(273,'config','0',272),(274,'default_method','empty',272),(275,'default_method_admin','trash',272),(276,'umiSettings','umiSettings',1),(277,'default_method','empty',276),(278,'default_method_admin','read',276),(279,'umiStub','umiStub',1),(280,'name','umiStub',279),(281,'config','0',279),(282,'default_method','empty',279),(283,'default_method_admin','stub',279),(284,'appointment','appointment',1),(285,'config','1',284),(286,'name','appointment',284),(287,'work-time-0','',284),(288,'work-time-1','',284),(289,'work-time-2','',284),(290,'work-time-3','',284),(291,'work-time-4','',284),(292,'work-time-5','',284),(293,'work-time-6','',284),(294,'default_method','page',284),(295,'default_method_admin','orders',284),(296,'settings','',0),(297,'keycode','38C0CBFAE82-67A05B67333-02A4FA6E46B',296),(298,'system_edition','commerce_enc',296),(299,'previous_edition','commerce_enc',296),(300,'system_version','20',296),(301,'system_build','87973',296),(302,'last_updated','1553121667',296),(303,'system_build','87973',224),(304,'install','1553121667',296),(305,'guest_id','618',163),(306,'create','1553121667',296),(307,'umiMessages','',0),(308,'lastConnectTime','1553122441',307),(309,'lastMessageId','0',307),(310,'last_mess_time','1553122443',296);
/*!40000 ALTER TABLE `cms_reg` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cms_sitemap`
--

DROP TABLE IF EXISTS `cms_sitemap`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cms_sitemap` (
  `id` int(11) NOT NULL,
  `domain_id` int(10) unsigned NOT NULL,
  `link` varchar(1024) NOT NULL,
  `sort` tinyint(4) NOT NULL,
  `priority` double NOT NULL DEFAULT '0',
  `dt` datetime NOT NULL,
  `level` int(4) unsigned NOT NULL,
  `lang_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `__sort` (`sort`),
  KEY `__domain_id` (`domain_id`),
  KEY `__domain_id__sort` (`domain_id`,`sort`),
  KEY `__domain_id__level` (`domain_id`,`level`),
  KEY `lang_id from cms3_langs` (`lang_id`),
  CONSTRAINT `domain_id from cms3_domains` FOREIGN KEY (`domain_id`) REFERENCES `cms3_domains` (`id`) ON DELETE CASCADE,
  CONSTRAINT `lang_id from cms3_langs` FOREIGN KEY (`lang_id`) REFERENCES `cms3_langs` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cms_sitemap`
--

LOCK TABLES `cms_sitemap` WRITE;
/*!40000 ALTER TABLE `cms_sitemap` DISABLE KEYS */;
INSERT INTO `cms_sitemap` VALUES (1,1,'http://testtask.madex.pro/',1,1,'2019-03-21 01:56:32',0,1),(2,1,'http://testtask.madex.pro/blog/',12,1,'2019-03-21 02:08:08',1,1),(3,1,'http://testtask.madex.pro/blog/centralnyj-apogej-predposylki-i-razvitie/',16,0.5,'2019-03-21 02:19:23',2,1),(4,1,'http://testtask.madex.pro/blog/mezhplanetnyj-godovoj-parallaks-metodologiya-i-osobennosti/',10,0.5,'2019-03-21 02:21:43',2,1),(5,1,'http://testtask.madex.pro/blog/pochemu-parallelna-letuchaya-ryba/',3,0.5,'2019-03-21 02:24:30',2,1),(6,1,'http://testtask.madex.pro/blog/pochemu-potencialno-ppotoplanetnoe-oblako/',0,0.5,'2019-03-21 02:24:12',2,1),(7,1,'http://testtask.madex.pro/blog/pochemu-potencialno-ppotoplanetnoe-oblako1/',0,0.5,'2019-03-21 02:26:25',2,1),(8,1,'http://testtask.madex.pro/blog/pochemu-parallelna-letuchaya-ryba1/',2,0.5,'2019-03-21 02:26:56',2,1),(9,1,'http://testtask.madex.pro/blog/mezhplanetnyj-godovoj-parallaks-metodologiya-i-osobennosti1/',3,0.5,'2019-03-21 02:27:17',2,1),(10,1,'http://testtask.madex.pro/blog/pochemu-parallelna-letuchaya-ryba2/',15,0.5,'2019-03-21 02:28:07',2,1);
/*!40000 ALTER TABLE `cms_sitemap` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cms_stat_domains`
--

DROP TABLE IF EXISTS `cms_stat_domains`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cms_stat_domains` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `entrytime` int(11) DEFAULT NULL,
  `refer_domain` text,
  `sess_id` text,
  PRIMARY KEY (`id`),
  KEY `sess_id` (`sess_id`(4))
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cms_stat_domains`
--

LOCK TABLES `cms_stat_domains` WRITE;
/*!40000 ALTER TABLE `cms_stat_domains` DISABLE KEYS */;
/*!40000 ALTER TABLE `cms_stat_domains` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cms_stat_entry_points`
--

DROP TABLE IF EXISTS `cms_stat_entry_points`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cms_stat_entry_points` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) DEFAULT NULL,
  `description` text,
  `url` text,
  `host_id` int(11) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `url` (`url`(1)),
  KEY `host_id` (`host_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cms_stat_entry_points`
--

LOCK TABLES `cms_stat_entry_points` WRITE;
/*!40000 ALTER TABLE `cms_stat_entry_points` DISABLE KEYS */;
/*!40000 ALTER TABLE `cms_stat_entry_points` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cms_stat_entry_points_events`
--

DROP TABLE IF EXISTS `cms_stat_entry_points_events`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cms_stat_entry_points_events` (
  `entry_point_id` int(11) unsigned DEFAULT NULL,
  `event_id` int(11) unsigned DEFAULT NULL,
  KEY `entry_point_id` (`entry_point_id`),
  KEY `event_id` (`event_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cms_stat_entry_points_events`
--

LOCK TABLES `cms_stat_entry_points_events` WRITE;
/*!40000 ALTER TABLE `cms_stat_entry_points_events` DISABLE KEYS */;
/*!40000 ALTER TABLE `cms_stat_entry_points_events` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cms_stat_events`
--

DROP TABLE IF EXISTS `cms_stat_events`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cms_stat_events` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `description` text,
  `name` varchar(255) DEFAULT NULL,
  `type` tinyint(4) DEFAULT NULL,
  `profit` float(9,2) DEFAULT '0.00',
  `host_id` int(11) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `name` (`name`,`type`),
  KEY `host_id` (`host_id`),
  KEY `type` (`type`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cms_stat_events`
--

LOCK TABLES `cms_stat_events` WRITE;
/*!40000 ALTER TABLE `cms_stat_events` DISABLE KEYS */;
/*!40000 ALTER TABLE `cms_stat_events` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cms_stat_events_collected`
--

DROP TABLE IF EXISTS `cms_stat_events_collected`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cms_stat_events_collected` (
  `event_id` int(11) unsigned DEFAULT NULL,
  `hit_id` int(11) unsigned DEFAULT NULL,
  KEY `event_id` (`event_id`,`hit_id`),
  KEY `hit_id` (`hit_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cms_stat_events_collected`
--

LOCK TABLES `cms_stat_events_collected` WRITE;
/*!40000 ALTER TABLE `cms_stat_events_collected` DISABLE KEYS */;
/*!40000 ALTER TABLE `cms_stat_events_collected` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cms_stat_events_rel`
--

DROP TABLE IF EXISTS `cms_stat_events_rel`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cms_stat_events_rel` (
  `metaevent_id` int(11) unsigned DEFAULT NULL,
  `event_id` int(11) unsigned DEFAULT NULL,
  UNIQUE KEY `metaevent_id` (`metaevent_id`,`event_id`),
  KEY `event_id` (`event_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cms_stat_events_rel`
--

LOCK TABLES `cms_stat_events_rel` WRITE;
/*!40000 ALTER TABLE `cms_stat_events_rel` DISABLE KEYS */;
/*!40000 ALTER TABLE `cms_stat_events_rel` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cms_stat_events_urls`
--

DROP TABLE IF EXISTS `cms_stat_events_urls`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cms_stat_events_urls` (
  `event_id` int(11) unsigned DEFAULT NULL,
  `page_id` int(11) unsigned DEFAULT NULL,
  UNIQUE KEY `event_id` (`event_id`,`page_id`),
  KEY `page_id` (`page_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cms_stat_events_urls`
--

LOCK TABLES `cms_stat_events_urls` WRITE;
/*!40000 ALTER TABLE `cms_stat_events_urls` DISABLE KEYS */;
/*!40000 ALTER TABLE `cms_stat_events_urls` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cms_stat_finders`
--

DROP TABLE IF EXISTS `cms_stat_finders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cms_stat_finders` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `bot_name` text,
  `pattern` text,
  `alias` text,
  `domain` text,
  `utf` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cms_stat_finders`
--

LOCK TABLES `cms_stat_finders` WRITE;
/*!40000 ALTER TABLE `cms_stat_finders` DISABLE KEYS */;
/*!40000 ALTER TABLE `cms_stat_finders` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cms_stat_hits`
--

DROP TABLE IF EXISTS `cms_stat_hits`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cms_stat_hits` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `page_id` int(11) unsigned DEFAULT NULL,
  `date` datetime DEFAULT NULL,
  `hour` tinyint(8) DEFAULT NULL,
  `day_of_week` tinyint(1) DEFAULT NULL,
  `day` tinyint(4) DEFAULT NULL,
  `month` tinyint(4) DEFAULT NULL,
  `year` int(11) unsigned DEFAULT NULL,
  `path_id` int(11) unsigned DEFAULT NULL,
  `number_in_path` int(11) unsigned DEFAULT NULL,
  `week` tinyint(4) unsigned DEFAULT NULL,
  `prev_page_id` int(11) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `day_of_week` (`day_of_week`),
  KEY `date` (`date`,`day_of_week`,`day`,`month`),
  KEY `day` (`day`,`month`,`date`,`day_of_week`),
  KEY `page_id` (`page_id`,`date`),
  KEY `date_level` (`date`,`number_in_path`),
  KEY `date_prev_page_level` (`date`,`prev_page_id`,`number_in_path`),
  KEY `path_id_level` (`path_id`,`number_in_path`,`prev_page_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cms_stat_hits`
--

LOCK TABLES `cms_stat_hits` WRITE;
/*!40000 ALTER TABLE `cms_stat_hits` DISABLE KEYS */;
/*!40000 ALTER TABLE `cms_stat_hits` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cms_stat_holidays`
--

DROP TABLE IF EXISTS `cms_stat_holidays`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cms_stat_holidays` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` char(255) DEFAULT NULL,
  `day` tinyint(2) DEFAULT NULL,
  `month` tinyint(4) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `day_month` (`day`,`month`,`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cms_stat_holidays`
--

LOCK TABLES `cms_stat_holidays` WRITE;
/*!40000 ALTER TABLE `cms_stat_holidays` DISABLE KEYS */;
/*!40000 ALTER TABLE `cms_stat_holidays` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cms_stat_pages`
--

DROP TABLE IF EXISTS `cms_stat_pages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cms_stat_pages` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `uri` text,
  `host_id` int(11) unsigned DEFAULT NULL,
  `section` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `section` (`section`),
  KEY `uri` (`uri`(4)),
  KEY `host_id` (`host_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cms_stat_pages`
--

LOCK TABLES `cms_stat_pages` WRITE;
/*!40000 ALTER TABLE `cms_stat_pages` DISABLE KEYS */;
/*!40000 ALTER TABLE `cms_stat_pages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cms_stat_paths`
--

DROP TABLE IF EXISTS `cms_stat_paths`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cms_stat_paths` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) unsigned DEFAULT NULL,
  `date` datetime DEFAULT NULL,
  `source_id` int(11) unsigned DEFAULT NULL,
  `host_id` int(11) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `source_id` (`source_id`),
  KEY `user_id` (`user_id`),
  KEY `id_host` (`id`,`host_id`),
  KEY `date_host_id` (`date`,`host_id`,`user_id`),
  KEY `host_id` (`host_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cms_stat_paths`
--

LOCK TABLES `cms_stat_paths` WRITE;
/*!40000 ALTER TABLE `cms_stat_paths` DISABLE KEYS */;
/*!40000 ALTER TABLE `cms_stat_paths` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cms_stat_phrases`
--

DROP TABLE IF EXISTS `cms_stat_phrases`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cms_stat_phrases` (
  `phrase` text,
  `domain` text,
  `finder_id` int(11) DEFAULT NULL,
  `entrytime` int(11) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cms_stat_phrases`
--

LOCK TABLES `cms_stat_phrases` WRITE;
/*!40000 ALTER TABLE `cms_stat_phrases` DISABLE KEYS */;
/*!40000 ALTER TABLE `cms_stat_phrases` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cms_stat_sites`
--

DROP TABLE IF EXISTS `cms_stat_sites`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cms_stat_sites` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` char(255) DEFAULT NULL,
  `group_id` int(11) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `name` (`name`),
  KEY `group_id` (`group_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cms_stat_sites`
--

LOCK TABLES `cms_stat_sites` WRITE;
/*!40000 ALTER TABLE `cms_stat_sites` DISABLE KEYS */;
/*!40000 ALTER TABLE `cms_stat_sites` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cms_stat_sites_groups`
--

DROP TABLE IF EXISTS `cms_stat_sites_groups`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cms_stat_sites_groups` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` char(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cms_stat_sites_groups`
--

LOCK TABLES `cms_stat_sites_groups` WRITE;
/*!40000 ALTER TABLE `cms_stat_sites_groups` DISABLE KEYS */;
/*!40000 ALTER TABLE `cms_stat_sites_groups` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cms_stat_sources`
--

DROP TABLE IF EXISTS `cms_stat_sources`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cms_stat_sources` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `src_type` tinyint(4) unsigned DEFAULT NULL,
  `concrete_src_id` int(11) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `src_type` (`src_type`,`concrete_src_id`,`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cms_stat_sources`
--

LOCK TABLES `cms_stat_sources` WRITE;
/*!40000 ALTER TABLE `cms_stat_sources` DISABLE KEYS */;
/*!40000 ALTER TABLE `cms_stat_sources` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cms_stat_sources_coupon`
--

DROP TABLE IF EXISTS `cms_stat_sources_coupon`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cms_stat_sources_coupon` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `number` varchar(255) DEFAULT NULL,
  `profit` float(9,2) DEFAULT NULL,
  `descript` text,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cms_stat_sources_coupon`
--

LOCK TABLES `cms_stat_sources_coupon` WRITE;
/*!40000 ALTER TABLE `cms_stat_sources_coupon` DISABLE KEYS */;
/*!40000 ALTER TABLE `cms_stat_sources_coupon` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cms_stat_sources_coupon_events`
--

DROP TABLE IF EXISTS `cms_stat_sources_coupon_events`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cms_stat_sources_coupon_events` (
  `coupon_id` int(11) unsigned DEFAULT NULL,
  `event_id` int(11) unsigned DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cms_stat_sources_coupon_events`
--

LOCK TABLES `cms_stat_sources_coupon_events` WRITE;
/*!40000 ALTER TABLE `cms_stat_sources_coupon_events` DISABLE KEYS */;
/*!40000 ALTER TABLE `cms_stat_sources_coupon_events` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cms_stat_sources_openstat`
--

DROP TABLE IF EXISTS `cms_stat_sources_openstat`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cms_stat_sources_openstat` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `service_id` int(11) unsigned DEFAULT '0',
  `campaign_id` int(11) unsigned DEFAULT '0',
  `ad_id` int(11) unsigned DEFAULT NULL,
  `source_id` int(11) unsigned DEFAULT NULL,
  `path_id` int(11) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `source_id` (`source_id`)
) ENGINE=MyISAM DEFAULT CHARSET=cp1251;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cms_stat_sources_openstat`
--

LOCK TABLES `cms_stat_sources_openstat` WRITE;
/*!40000 ALTER TABLE `cms_stat_sources_openstat` DISABLE KEYS */;
/*!40000 ALTER TABLE `cms_stat_sources_openstat` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cms_stat_sources_openstat_ad`
--

DROP TABLE IF EXISTS `cms_stat_sources_openstat_ad`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cms_stat_sources_openstat_ad` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` char(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=cp1251;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cms_stat_sources_openstat_ad`
--

LOCK TABLES `cms_stat_sources_openstat_ad` WRITE;
/*!40000 ALTER TABLE `cms_stat_sources_openstat_ad` DISABLE KEYS */;
/*!40000 ALTER TABLE `cms_stat_sources_openstat_ad` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cms_stat_sources_openstat_campaign`
--

DROP TABLE IF EXISTS `cms_stat_sources_openstat_campaign`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cms_stat_sources_openstat_campaign` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` char(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `name` (`name`)
) ENGINE=MyISAM DEFAULT CHARSET=cp1251;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cms_stat_sources_openstat_campaign`
--

LOCK TABLES `cms_stat_sources_openstat_campaign` WRITE;
/*!40000 ALTER TABLE `cms_stat_sources_openstat_campaign` DISABLE KEYS */;
/*!40000 ALTER TABLE `cms_stat_sources_openstat_campaign` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cms_stat_sources_openstat_service`
--

DROP TABLE IF EXISTS `cms_stat_sources_openstat_service`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cms_stat_sources_openstat_service` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` char(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `name` (`name`)
) ENGINE=MyISAM DEFAULT CHARSET=cp1251;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cms_stat_sources_openstat_service`
--

LOCK TABLES `cms_stat_sources_openstat_service` WRITE;
/*!40000 ALTER TABLE `cms_stat_sources_openstat_service` DISABLE KEYS */;
/*!40000 ALTER TABLE `cms_stat_sources_openstat_service` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cms_stat_sources_openstat_source`
--

DROP TABLE IF EXISTS `cms_stat_sources_openstat_source`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cms_stat_sources_openstat_source` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` char(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `name` (`name`)
) ENGINE=MyISAM DEFAULT CHARSET=cp1251;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cms_stat_sources_openstat_source`
--

LOCK TABLES `cms_stat_sources_openstat_source` WRITE;
/*!40000 ALTER TABLE `cms_stat_sources_openstat_source` DISABLE KEYS */;
/*!40000 ALTER TABLE `cms_stat_sources_openstat_source` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cms_stat_sources_pr`
--

DROP TABLE IF EXISTS `cms_stat_sources_pr`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cms_stat_sources_pr` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) DEFAULT NULL,
  `description` text,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cms_stat_sources_pr`
--

LOCK TABLES `cms_stat_sources_pr` WRITE;
/*!40000 ALTER TABLE `cms_stat_sources_pr` DISABLE KEYS */;
/*!40000 ALTER TABLE `cms_stat_sources_pr` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cms_stat_sources_pr_events`
--

DROP TABLE IF EXISTS `cms_stat_sources_pr_events`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cms_stat_sources_pr_events` (
  `pr_id` int(11) unsigned DEFAULT NULL,
  `event_id` int(11) unsigned DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cms_stat_sources_pr_events`
--

LOCK TABLES `cms_stat_sources_pr_events` WRITE;
/*!40000 ALTER TABLE `cms_stat_sources_pr_events` DISABLE KEYS */;
/*!40000 ALTER TABLE `cms_stat_sources_pr_events` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cms_stat_sources_pr_sites`
--

DROP TABLE IF EXISTS `cms_stat_sources_pr_sites`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cms_stat_sources_pr_sites` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `pr_id` int(11) unsigned DEFAULT NULL,
  `url` text,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cms_stat_sources_pr_sites`
--

LOCK TABLES `cms_stat_sources_pr_sites` WRITE;
/*!40000 ALTER TABLE `cms_stat_sources_pr_sites` DISABLE KEYS */;
/*!40000 ALTER TABLE `cms_stat_sources_pr_sites` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cms_stat_sources_search`
--

DROP TABLE IF EXISTS `cms_stat_sources_search`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cms_stat_sources_search` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `engine_id` int(11) unsigned DEFAULT NULL,
  `text_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `engine_id` (`engine_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cms_stat_sources_search`
--

LOCK TABLES `cms_stat_sources_search` WRITE;
/*!40000 ALTER TABLE `cms_stat_sources_search` DISABLE KEYS */;
/*!40000 ALTER TABLE `cms_stat_sources_search` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cms_stat_sources_search_engines`
--

DROP TABLE IF EXISTS `cms_stat_sources_search_engines`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cms_stat_sources_search_engines` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` char(255) DEFAULT NULL,
  `url_mask` char(255) DEFAULT NULL,
  `varname` char(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cms_stat_sources_search_engines`
--

LOCK TABLES `cms_stat_sources_search_engines` WRITE;
/*!40000 ALTER TABLE `cms_stat_sources_search_engines` DISABLE KEYS */;
/*!40000 ALTER TABLE `cms_stat_sources_search_engines` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cms_stat_sources_search_queries`
--

DROP TABLE IF EXISTS `cms_stat_sources_search_queries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cms_stat_sources_search_queries` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `text` text,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cms_stat_sources_search_queries`
--

LOCK TABLES `cms_stat_sources_search_queries` WRITE;
/*!40000 ALTER TABLE `cms_stat_sources_search_queries` DISABLE KEYS */;
/*!40000 ALTER TABLE `cms_stat_sources_search_queries` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cms_stat_sources_sites`
--

DROP TABLE IF EXISTS `cms_stat_sources_sites`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cms_stat_sources_sites` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `uri` text,
  `domain` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `domain` (`domain`),
  KEY `uri` (`uri`(255)),
  KEY `id_domain` (`id`,`domain`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cms_stat_sources_sites`
--

LOCK TABLES `cms_stat_sources_sites` WRITE;
/*!40000 ALTER TABLE `cms_stat_sources_sites` DISABLE KEYS */;
/*!40000 ALTER TABLE `cms_stat_sources_sites` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cms_stat_sources_sites_domains`
--

DROP TABLE IF EXISTS `cms_stat_sources_sites_domains`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cms_stat_sources_sites_domains` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` char(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cms_stat_sources_sites_domains`
--

LOCK TABLES `cms_stat_sources_sites_domains` WRITE;
/*!40000 ALTER TABLE `cms_stat_sources_sites_domains` DISABLE KEYS */;
/*!40000 ALTER TABLE `cms_stat_sources_sites_domains` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cms_stat_sources_ticket`
--

DROP TABLE IF EXISTS `cms_stat_sources_ticket`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cms_stat_sources_ticket` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` char(255) DEFAULT NULL,
  `url` char(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cms_stat_sources_ticket`
--

LOCK TABLES `cms_stat_sources_ticket` WRITE;
/*!40000 ALTER TABLE `cms_stat_sources_ticket` DISABLE KEYS */;
/*!40000 ALTER TABLE `cms_stat_sources_ticket` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cms_stat_users`
--

DROP TABLE IF EXISTS `cms_stat_users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cms_stat_users` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `session_id` varchar(32) DEFAULT NULL,
  `first_visit` datetime DEFAULT NULL,
  `login` varchar(255) DEFAULT NULL,
  `os_id` int(11) unsigned DEFAULT NULL,
  `browser_id` int(11) unsigned DEFAULT NULL,
  `ip` varchar(15) DEFAULT NULL,
  `location` text,
  `js_version` varchar(5) DEFAULT NULL,
  `host_id` int(11) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `first_visit` (`first_visit`),
  KEY `session_id` (`session_id`),
  KEY `host_id` (`host_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cms_stat_users`
--

LOCK TABLES `cms_stat_users` WRITE;
/*!40000 ALTER TABLE `cms_stat_users` DISABLE KEYS */;
/*!40000 ALTER TABLE `cms_stat_users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cms_stat_users_browsers`
--

DROP TABLE IF EXISTS `cms_stat_users_browsers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cms_stat_users_browsers` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` char(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cms_stat_users_browsers`
--

LOCK TABLES `cms_stat_users_browsers` WRITE;
/*!40000 ALTER TABLE `cms_stat_users_browsers` DISABLE KEYS */;
/*!40000 ALTER TABLE `cms_stat_users_browsers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cms_stat_users_os`
--

DROP TABLE IF EXISTS `cms_stat_users_os`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cms_stat_users_os` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` char(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cms_stat_users_os`
--

LOCK TABLES `cms_stat_users_os` WRITE;
/*!40000 ALTER TABLE `cms_stat_users_os` DISABLE KEYS */;
/*!40000 ALTER TABLE `cms_stat_users_os` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cms_webforms`
--

DROP TABLE IF EXISTS `cms_webforms`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cms_webforms` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(255) DEFAULT '',
  `descr` varchar(255) DEFAULT '',
  PRIMARY KEY (`id`),
  KEY `email` (`email`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cms_webforms`
--

LOCK TABLES `cms_webforms` WRITE;
/*!40000 ALTER TABLE `cms_webforms` DISABLE KEYS */;
/*!40000 ALTER TABLE `cms_webforms` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `goog-malware-shavar-a-hosts`
--

DROP TABLE IF EXISTS `goog-malware-shavar-a-hosts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `goog-malware-shavar-a-hosts` (
  `ID` int(255) NOT NULL AUTO_INCREMENT,
  `Hostkey` varchar(8) NOT NULL,
  `Chunknum` int(255) NOT NULL,
  `Count` varchar(2) NOT NULL DEFAULT '0',
  `FullHash` varchar(70) NOT NULL,
  PRIMARY KEY (`ID`),
  KEY `Hostkey` (`Hostkey`),
  KEY `Hostkey_2` (`Hostkey`),
  KEY `Hostkey_3` (`Hostkey`),
  KEY `Hostkey_4` (`Hostkey`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `goog-malware-shavar-a-hosts`
--

LOCK TABLES `goog-malware-shavar-a-hosts` WRITE;
/*!40000 ALTER TABLE `goog-malware-shavar-a-hosts` DISABLE KEYS */;
/*!40000 ALTER TABLE `goog-malware-shavar-a-hosts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `goog-malware-shavar-a-index`
--

DROP TABLE IF EXISTS `goog-malware-shavar-a-index`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `goog-malware-shavar-a-index` (
  `ChunkNum` int(255) NOT NULL AUTO_INCREMENT,
  `Chunklen` int(255) NOT NULL DEFAULT '0',
  PRIMARY KEY (`ChunkNum`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `goog-malware-shavar-a-index`
--

LOCK TABLES `goog-malware-shavar-a-index` WRITE;
/*!40000 ALTER TABLE `goog-malware-shavar-a-index` DISABLE KEYS */;
/*!40000 ALTER TABLE `goog-malware-shavar-a-index` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `goog-malware-shavar-a-prefixes`
--

DROP TABLE IF EXISTS `goog-malware-shavar-a-prefixes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `goog-malware-shavar-a-prefixes` (
  `ID` int(255) NOT NULL AUTO_INCREMENT,
  `Hostkey` varchar(8) NOT NULL,
  `Prefix` varchar(255) NOT NULL,
  `FullHash` varchar(70) NOT NULL,
  PRIMARY KEY (`ID`),
  KEY `Hostkey` (`Hostkey`),
  KEY `Hostkey_2` (`Hostkey`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `goog-malware-shavar-a-prefixes`
--

LOCK TABLES `goog-malware-shavar-a-prefixes` WRITE;
/*!40000 ALTER TABLE `goog-malware-shavar-a-prefixes` DISABLE KEYS */;
/*!40000 ALTER TABLE `goog-malware-shavar-a-prefixes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `goog-malware-shavar-s-hosts`
--

DROP TABLE IF EXISTS `goog-malware-shavar-s-hosts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `goog-malware-shavar-s-hosts` (
  `ID` int(255) NOT NULL AUTO_INCREMENT,
  `Hostkey` varchar(8) NOT NULL,
  `Chunknum` int(255) NOT NULL,
  `Count` varchar(2) NOT NULL DEFAULT '0',
  `FullHash` varchar(70) NOT NULL,
  PRIMARY KEY (`ID`),
  KEY `Hostkey` (`Hostkey`),
  KEY `Hostkey_2` (`Hostkey`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `goog-malware-shavar-s-hosts`
--

LOCK TABLES `goog-malware-shavar-s-hosts` WRITE;
/*!40000 ALTER TABLE `goog-malware-shavar-s-hosts` DISABLE KEYS */;
/*!40000 ALTER TABLE `goog-malware-shavar-s-hosts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `goog-malware-shavar-s-index`
--

DROP TABLE IF EXISTS `goog-malware-shavar-s-index`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `goog-malware-shavar-s-index` (
  `ChunkNum` int(255) NOT NULL AUTO_INCREMENT,
  `Chunklen` int(255) NOT NULL DEFAULT '0',
  PRIMARY KEY (`ChunkNum`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `goog-malware-shavar-s-index`
--

LOCK TABLES `goog-malware-shavar-s-index` WRITE;
/*!40000 ALTER TABLE `goog-malware-shavar-s-index` DISABLE KEYS */;
/*!40000 ALTER TABLE `goog-malware-shavar-s-index` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `goog-malware-shavar-s-prefixes`
--

DROP TABLE IF EXISTS `goog-malware-shavar-s-prefixes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `goog-malware-shavar-s-prefixes` (
  `ID` int(255) NOT NULL AUTO_INCREMENT,
  `Hostkey` varchar(8) NOT NULL,
  `AddChunkNum` varchar(8) NOT NULL,
  `Prefix` varchar(255) NOT NULL,
  `FullHash` varchar(70) NOT NULL,
  PRIMARY KEY (`ID`),
  KEY `Hostkey` (`Hostkey`),
  KEY `Hostkey_2` (`Hostkey`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `goog-malware-shavar-s-prefixes`
--

LOCK TABLES `goog-malware-shavar-s-prefixes` WRITE;
/*!40000 ALTER TABLE `goog-malware-shavar-s-prefixes` DISABLE KEYS */;
/*!40000 ALTER TABLE `goog-malware-shavar-s-prefixes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `googpub-phish-shavar-a-hosts`
--

DROP TABLE IF EXISTS `googpub-phish-shavar-a-hosts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `googpub-phish-shavar-a-hosts` (
  `ID` int(255) NOT NULL AUTO_INCREMENT,
  `Hostkey` varchar(8) NOT NULL,
  `Chunknum` int(255) NOT NULL,
  `Count` varchar(2) NOT NULL DEFAULT '0',
  `FullHash` varchar(70) NOT NULL,
  PRIMARY KEY (`ID`),
  KEY `Hostkey` (`Hostkey`),
  KEY `Hostkey_2` (`Hostkey`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `googpub-phish-shavar-a-hosts`
--

LOCK TABLES `googpub-phish-shavar-a-hosts` WRITE;
/*!40000 ALTER TABLE `googpub-phish-shavar-a-hosts` DISABLE KEYS */;
/*!40000 ALTER TABLE `googpub-phish-shavar-a-hosts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `googpub-phish-shavar-a-index`
--

DROP TABLE IF EXISTS `googpub-phish-shavar-a-index`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `googpub-phish-shavar-a-index` (
  `ChunkNum` int(255) NOT NULL AUTO_INCREMENT,
  `Chunklen` int(255) NOT NULL DEFAULT '0',
  PRIMARY KEY (`ChunkNum`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `googpub-phish-shavar-a-index`
--

LOCK TABLES `googpub-phish-shavar-a-index` WRITE;
/*!40000 ALTER TABLE `googpub-phish-shavar-a-index` DISABLE KEYS */;
/*!40000 ALTER TABLE `googpub-phish-shavar-a-index` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `googpub-phish-shavar-a-prefixes`
--

DROP TABLE IF EXISTS `googpub-phish-shavar-a-prefixes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `googpub-phish-shavar-a-prefixes` (
  `ID` int(255) NOT NULL AUTO_INCREMENT,
  `Hostkey` varchar(8) NOT NULL,
  `Prefix` varchar(255) NOT NULL,
  `FullHash` varchar(70) NOT NULL,
  PRIMARY KEY (`ID`),
  KEY `Hostkey` (`Hostkey`),
  KEY `Hostkey_2` (`Hostkey`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `googpub-phish-shavar-a-prefixes`
--

LOCK TABLES `googpub-phish-shavar-a-prefixes` WRITE;
/*!40000 ALTER TABLE `googpub-phish-shavar-a-prefixes` DISABLE KEYS */;
/*!40000 ALTER TABLE `googpub-phish-shavar-a-prefixes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `googpub-phish-shavar-s-hosts`
--

DROP TABLE IF EXISTS `googpub-phish-shavar-s-hosts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `googpub-phish-shavar-s-hosts` (
  `ID` int(255) NOT NULL AUTO_INCREMENT,
  `Hostkey` varchar(8) NOT NULL,
  `Chunknum` int(255) NOT NULL,
  `Count` varchar(2) NOT NULL DEFAULT '0',
  `FullHash` varchar(70) NOT NULL,
  PRIMARY KEY (`ID`),
  KEY `Hostkey` (`Hostkey`),
  KEY `Hostkey_2` (`Hostkey`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `googpub-phish-shavar-s-hosts`
--

LOCK TABLES `googpub-phish-shavar-s-hosts` WRITE;
/*!40000 ALTER TABLE `googpub-phish-shavar-s-hosts` DISABLE KEYS */;
/*!40000 ALTER TABLE `googpub-phish-shavar-s-hosts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `googpub-phish-shavar-s-index`
--

DROP TABLE IF EXISTS `googpub-phish-shavar-s-index`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `googpub-phish-shavar-s-index` (
  `ChunkNum` int(255) NOT NULL AUTO_INCREMENT,
  `Chunklen` int(255) NOT NULL DEFAULT '0',
  PRIMARY KEY (`ChunkNum`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `googpub-phish-shavar-s-index`
--

LOCK TABLES `googpub-phish-shavar-s-index` WRITE;
/*!40000 ALTER TABLE `googpub-phish-shavar-s-index` DISABLE KEYS */;
/*!40000 ALTER TABLE `googpub-phish-shavar-s-index` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `googpub-phish-shavar-s-prefixes`
--

DROP TABLE IF EXISTS `googpub-phish-shavar-s-prefixes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `googpub-phish-shavar-s-prefixes` (
  `ID` int(255) NOT NULL AUTO_INCREMENT,
  `Hostkey` varchar(8) NOT NULL,
  `AddChunkNum` varchar(8) NOT NULL,
  `Prefix` varchar(255) NOT NULL,
  `FullHash` varchar(70) NOT NULL,
  PRIMARY KEY (`ID`),
  KEY `Hostkey` (`Hostkey`),
  KEY `Hostkey_2` (`Hostkey`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `googpub-phish-shavar-s-prefixes`
--

LOCK TABLES `googpub-phish-shavar-s-prefixes` WRITE;
/*!40000 ALTER TABLE `googpub-phish-shavar-s-prefixes` DISABLE KEYS */;
/*!40000 ALTER TABLE `googpub-phish-shavar-s-prefixes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `questions`
--

DROP TABLE IF EXISTS `questions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `questions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(48) DEFAULT NULL,
  `message` varchar(140) DEFAULT NULL,
  `type` int(11) DEFAULT '0',
  `cdate` int(11) DEFAULT NULL,
  `autor_id` int(11) DEFAULT NULL,
  `rel` int(11) DEFAULT '0',
  `rate` int(11) DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `questions`
--

LOCK TABLES `questions` WRITE;
/*!40000 ALTER TABLE `questions` DISABLE KEYS */;
/*!40000 ALTER TABLE `questions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `umi_event_feeds`
--

DROP TABLE IF EXISTS `umi_event_feeds`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `umi_event_feeds` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `date` bigint(20) DEFAULT NULL,
  `params` mediumtext,
  `type_id` varchar(255) NOT NULL,
  `element_id` int(11) DEFAULT NULL,
  `object_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `umi_event_feeds`
--

LOCK TABLES `umi_event_feeds` WRITE;
/*!40000 ALTER TABLE `umi_event_feeds` DISABLE KEYS */;
/*!40000 ALTER TABLE `umi_event_feeds` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `umi_event_types`
--

DROP TABLE IF EXISTS `umi_event_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `umi_event_types` (
  `id` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `umi_event_types`
--

LOCK TABLES `umi_event_types` WRITE;
/*!40000 ALTER TABLE `umi_event_types` DISABLE KEYS */;
/*!40000 ALTER TABLE `umi_event_types` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `umi_event_user_history`
--

DROP TABLE IF EXISTS `umi_event_user_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `umi_event_user_history` (
  `user_id` int(11) NOT NULL,
  `event_id` int(11) NOT NULL,
  `read` tinyint(1) NOT NULL DEFAULT '1'
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `umi_event_user_history`
--

LOCK TABLES `umi_event_user_history` WRITE;
/*!40000 ALTER TABLE `umi_event_user_history` DISABLE KEYS */;
/*!40000 ALTER TABLE `umi_event_user_history` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `umi_event_users`
--

DROP TABLE IF EXISTS `umi_event_users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `umi_event_users` (
  `id` int(11) unsigned NOT NULL,
  `last_check_in` bigint(20) DEFAULT NULL,
  `settings` mediumtext,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `umi_event_users`
--

LOCK TABLES `umi_event_users` WRITE;
/*!40000 ALTER TABLE `umi_event_users` DISABLE KEYS */;
INSERT INTO `umi_event_users` VALUES (182,1553122443,'a:0:{}');
/*!40000 ALTER TABLE `umi_event_users` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2019-03-20 23:34:04
