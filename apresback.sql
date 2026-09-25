-- MySQL dump 10.13  Distrib 8.0.46, for Linux (x86_64)
--
-- Host: localhost    Database: apresbac
-- ------------------------------------------------------
-- Server version	8.0.46-0ubuntu0.24.04.4

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `admins`
--

DROP TABLE IF EXISTS `admins`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `admins` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nom` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `password_hash` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `reset_code` varchar(6) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `reset_code_expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `admins`
--

LOCK TABLES `admins` WRITE;
/*!40000 ALTER TABLE `admins` DISABLE KEYS */;
INSERT INTO `admins` VALUES (1,'Admin','REMPLACE_PAR_ADMIN_EMAIL','REMPLACE_PAR_ADMIN_PASSWORD_HASH',NULL,NULL,'2026-07-24 20:47:24'),(2,NULL,'elvisapovo04@gmail.com','$2b$10$J7THcPWMJYeWFKOe1jj7e.eaSUpNVlG0Sr5rM1WnNgPsVF.TjMiVe','363490','2026-07-24 21:05:48','2026-07-24 20:51:26');
/*!40000 ALTER TABLE `admins` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `formules`
--

DROP TABLE IF EXISTS `formules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `formules` (
  `id` int NOT NULL AUTO_INCREMENT,
  `code` enum('premium','vip','gold') COLLATE utf8mb4_unicode_ci NOT NULL,
  `nom` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `prix` int NOT NULL,
  `avantages` json NOT NULL,
  `places_totales` int NOT NULL,
  `places_restantes` int NOT NULL,
  `actif` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `formules`
--

LOCK TABLES `formules` WRITE;
/*!40000 ALTER TABLE `formules` DISABLE KEYS */;
INSERT INTO `formules` VALUES (1,'premium','premium',200,'[\"Espace de discussion WhatsApp privé\", \"Accompagnement concernant l’inscription\", \"Aides et conseils pour l’ouverture de compte bancaire\", \"Assistance complète pour la procédure de la demande d’allocation en ligne\", \"Suivi de la demande jusqu’au premier virement\", \"Assistance en cas de réclamation\"]',250,207,1),(2,'vip','VIP',1800,'[\"Tout Premium\", \"Session live avec un conseiller\", \"Simulation de dossier Parcoursup/APB\", \"Support prioritaire WhatsApp\"]',210,210,0),(3,'gold','Gold',50000,'[\"Tout VIP\", \"Accompagnement individuel 1-to-1\", \"Suivi jusqu’à l’inscription définitive\", \"Accès à vie à la communauté\"]',25,25,0);
/*!40000 ALTER TABLE `formules` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `newsletter_subscribers`
--

DROP TABLE IF EXISTS `newsletter_subscribers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `newsletter_subscribers` (
  `id` int NOT NULL AUTO_INCREMENT,
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `actif` tinyint(1) NOT NULL DEFAULT '1',
  `subscribed_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `unsubscribed_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `token` (`token`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `newsletter_subscribers`
--

LOCK TABLES `newsletter_subscribers` WRITE;
/*!40000 ALTER TABLE `newsletter_subscribers` DISABLE KEYS */;
/*!40000 ALTER TABLE `newsletter_subscribers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `newsletters_envoyees`
--

DROP TABLE IF EXISTS `newsletters_envoyees`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `newsletters_envoyees` (
  `id` int NOT NULL AUTO_INCREMENT,
  `sujet` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `nb_destinataires` int NOT NULL DEFAULT '0',
  `nb_echecs` int NOT NULL DEFAULT '0',
  `envoyee_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `newsletters_envoyees`
--

LOCK TABLES `newsletters_envoyees` WRITE;
/*!40000 ALTER TABLE `newsletters_envoyees` DISABLE KEYS */;
/*!40000 ALTER TABLE `newsletters_envoyees` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `paiements`
--

DROP TABLE IF EXISTS `paiements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `paiements` (
  `id` int NOT NULL AUTO_INCREMENT,
  `guest_token` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `formule_id` int DEFAULT NULL,
  `montant` int NOT NULL,
  `statut` enum('en_attente','reussi','echoue','annule') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'en_attente',
  `reference` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `saspay_session_id` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `code_accompagnement` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pdf_genere` tinyint(1) NOT NULL DEFAULT '0',
  `pdf_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `reference` (`reference`),
  UNIQUE KEY `uniq_code_accompagnement` (`code_accompagnement`),
  KEY `fk_paiements_formule` (`formule_id`),
  KEY `idx_guest_token` (`guest_token`),
  KEY `idx_paiements_reference` (`reference`),
  KEY `idx_paiements_saspay_session` (`saspay_session_id`),
  CONSTRAINT `fk_paiements_formule` FOREIGN KEY (`formule_id`) REFERENCES `formules` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `paiements`
--

LOCK TABLES `paiements` WRITE;
/*!40000 ALTER TABLE `paiements` DISABLE KEYS */;
INSERT INTO `paiements` VALUES (1,'8d123c95f19271d592a398fc422e5eaa',1,200,'en_attente','APB-AB86476A5740','42b5a53e-04a6-406f-8b44-200224956f46',NULL,0,NULL,'2026-09-24 01:39:01','2026-09-24 01:39:03'),(2,'83023fea70a54e5fe61468a350dc14ef',1,200,'en_attente','APB-05A705837BCD','d7ef783a-e563-432f-b856-9ec582cf359f',NULL,0,NULL,'2026-09-24 22:21:40','2026-09-24 22:21:41'),(3,'b603e4b706700d7fc9b6844df6c1058a',1,200,'reussi','APB-7EA4C201D585','443714c4-5fd5-4f13-bbfa-1afc4460c19d','APB-31961A',0,NULL,'2026-09-24 22:34:26','2026-09-24 22:38:43');
/*!40000 ALTER TABLE `paiements` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `profils_accompagnement`
--

DROP TABLE IF EXISTS `profils_accompagnement`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `profils_accompagnement` (
  `id` int NOT NULL AUTO_INCREMENT,
  `guest_token` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nom_complet` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `universite` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `numero_whatsapp` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_guest_token` (`guest_token`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `profils_accompagnement`
--

LOCK TABLES `profils_accompagnement` WRITE;
/*!40000 ALTER TABLE `profils_accompagnement` DISABLE KEYS */;
INSERT INTO `profils_accompagnement` VALUES (1,'8d123c95f19271d592a398fc422e5eaa','Ton chef','Top','ppelvis5@gmail.com','0153096255','2026-09-24 01:38:58'),(2,'83023fea70a54e5fe61468a350dc14ef','hilaire Dogue','Tech Solution','doguejunior9@gmail.com','+22954761759','2026-09-24 22:21:35'),(3,'b603e4b706700d7fc9b6844df6c1058a','hilaire Dogue','Tech Solution','doguejunior9@gmail.com','+22954761759','2026-09-24 22:34:22');
/*!40000 ALTER TABLE `profils_accompagnement` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `visites`
--

DROP TABLE IF EXISTS `visites`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `visites` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `session_id` varchar(64) NOT NULL,
  `date_visite` date NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_session_jour` (`session_id`,`date_visite`),
  KEY `idx_date_visite` (`date_visite`)
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `visites`
--

LOCK TABLES `visites` WRITE;
/*!40000 ALTER TABLE `visites` DISABLE KEYS */;
INSERT INTO `visites` VALUES (1,'c70507392f0fbf39aa0ddffcecccec79','2026-09-24','2026-09-24 02:06:44'),(2,'42a72a43c88b32cef458a8559fc6e5ba','2026-09-24','2026-09-24 02:37:15'),(6,'e2d6978da1595bafc2b9e40d8422d2b2','2026-09-24','2026-09-24 02:39:01'),(7,'155661c0cbbe499a22481aaf1000858b','2026-09-24','2026-09-24 02:39:01'),(8,'948d50a20255a4407f9cf2d76e27d732','2026-09-24','2026-09-24 02:39:01'),(9,'b026e931f907b21a9f597aea5fa301b1','2026-09-24','2026-09-24 23:20:49'),(14,'5e74bebb9ee5c816c762d36bf9f384b1','2026-09-24','2026-09-24 23:34:10'),(17,'c0389b7cbb47a8c0de585f3adf09220c','2026-09-25','2026-09-25 00:01:23'),(18,'5e9c39d9ff129d707bd158fa7b9348b4','2026-09-25','2026-09-25 00:25:10'),(19,'a79c01768b2740ea514f65cc3d2cde49','2026-09-25','2026-09-25 01:37:03'),(20,'f684b06ea12f80e4e0f7b03c4a7b3f4f','2026-09-25','2026-09-25 02:42:39');
/*!40000 ALTER TABLE `visites` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-09-25 12:11:02
