-- MySQL dump 10.13  Distrib 8.0.46, for Linux (x86_64)
--
-- Host: localhost    Database: apresbac
-- ------------------------------------------------------
-- Server version	8.0.46-0ubuntu0.24.04.3

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
INSERT INTO `admins` VALUES (1,'Admin','REMPLACE_PAR_ADMIN_EMAIL','REMPLACE_PAR_ADMIN_PASSWORD_HASH',NULL,NULL,'2026-07-24 21:47:24'),(2,NULL,'elvisapovo04@gmail.com','$2b$10$J7THcPWMJYeWFKOe1jj7e.eaSUpNVlG0Sr5rM1WnNgPsVF.TjMiVe','363490','2026-07-24 22:05:48','2026-07-24 21:51:26');
/*!40000 ALTER TABLE `admins` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `candidats`
--

DROP TABLE IF EXISTS `candidats`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `candidats` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `nom` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `prenom` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `serie` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `telephone` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `mention` enum('Passable','Assez Bien','Bien','Très Bien','Excellent') COLLATE utf8mb4_unicode_ci NOT NULL,
  `formule_id` int NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `formule_id` (`formule_id`),
  CONSTRAINT `candidats_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `candidats_ibfk_2` FOREIGN KEY (`formule_id`) REFERENCES `formules` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `candidats`
--

LOCK TABLES `candidats` WRITE;
/*!40000 ALTER TABLE `candidats` DISABLE KEYS */;
/*!40000 ALTER TABLE `candidats` ENABLE KEYS */;
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
INSERT INTO `formules` VALUES (1,'premium','premium',100,'[\"Espace de discussion WhatsApp privé\", \"Toutes les infos des universités Bénin\", \"Choix de filières personnalisés\", \"Actualités par mail\", \"Conseil et orientation réelle\", \"Aide pour remplir le formulaire de choix de filière en ligne\", \"Suivi jusqu\'à validation du choix en ligne\"]',210,208,1),(2,'vip','VIP',1800,'[\"Tout Premium\", \"Session live avec un conseiller\", \"Simulation de dossier Parcoursup/APB\", \"Support prioritaire WhatsApp\"]',210,210,0),(3,'gold','Gold',50000,'[\"Tout VIP\", \"Accompagnement individuel 1-to-1\", \"Suivi jusqu’à l’inscription définitive\", \"Accès à vie à la communauté\"]',25,25,0);
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
  `candidat_id` int DEFAULT NULL,
  `user_id` int DEFAULT NULL,
  `formule_id` int DEFAULT NULL,
  `fedapay_transaction_id` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `montant` int NOT NULL,
  `statut` enum('en_attente','reussi','echoue','annule') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'en_attente',
  `reference` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `pdf_genere` tinyint(1) NOT NULL DEFAULT '0',
  `pdf_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `reference` (`reference`),
  KEY `candidat_id` (`candidat_id`),
  KEY `fk_paiements_user` (`user_id`),
  KEY `fk_paiements_formule` (`formule_id`),
  CONSTRAINT `fk_paiements_formule` FOREIGN KEY (`formule_id`) REFERENCES `formules` (`id`),
  CONSTRAINT `fk_paiements_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `paiements_ibfk_1` FOREIGN KEY (`candidat_id`) REFERENCES `candidats` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `paiements`
--

LOCK TABLES `paiements` WRITE;
/*!40000 ALTER TABLE `paiements` DISABLE KEYS */;
INSERT INTO `paiements` VALUES (1,NULL,2,2,'112139034',2400,'echoue','APB-2D559868D6B0',0,NULL,'2026-07-23 03:53:53','2026-07-23 03:54:18'),(2,NULL,2,1,'112139039',990,'echoue','APB-5B53823C07D9',0,NULL,'2026-07-23 03:59:52','2026-07-23 04:00:00'),(3,NULL,3,1,'112148067',990,'en_attente','APB-9AE9CA052524',0,NULL,'2026-07-23 17:06:45','2026-07-23 17:06:47'),(4,NULL,3,2,'112148095',2400,'en_attente','APB-7BB4D0B863D6',0,NULL,'2026-07-23 17:08:32','2026-07-23 17:08:34'),(5,NULL,3,1,'112165238',990,'en_attente','APB-506E9DCAB8B1',0,NULL,'2026-07-24 20:12:20','2026-07-24 20:12:22'),(6,NULL,2,1,'112167800',990,'en_attente','APB-4B8F953743C4',0,NULL,'2026-07-24 23:47:50','2026-07-24 23:47:52'),(7,NULL,3,1,'112168034',982,'en_attente','APB-CABF8938F17B',0,NULL,'2026-07-25 00:34:59','2026-07-25 00:35:01'),(8,NULL,2,1,'112168172',100,'echoue','APB-DA334FF42606',0,NULL,'2026-07-25 01:20:49','2026-07-25 01:20:58'),(9,NULL,4,1,'112168205',100,'echoue','APB-C50B5117549C',0,NULL,'2026-07-25 01:31:16','2026-07-25 01:31:22'),(10,NULL,3,1,'112168218',100,'en_attente','APB-D1BBB511D8BA',0,NULL,'2026-07-25 01:38:16','2026-07-25 01:38:17'),(11,NULL,3,1,'112168243',100,'reussi','APB-B18C7881E0B8',0,NULL,'2026-07-25 01:54:45','2026-07-25 01:55:58'),(12,NULL,3,1,'112171220',100,'reussi','APB-AFAAACCABFF1',0,NULL,'2026-07-25 11:01:57','2026-07-25 11:03:29'),(13,NULL,2,1,'112171891',100,'echoue','APB-C4BBFF6E1CC5',0,NULL,'2026-07-25 11:55:20','2026-07-25 11:55:47'),(14,NULL,2,1,'112171902',100,'echoue','APB-0B7F5BA80A3F',0,NULL,'2026-07-25 11:55:54','2026-07-25 11:56:05');
/*!40000 ALTER TABLE `paiements` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `profils_orientation`
--

DROP TABLE IF EXISTS `profils_orientation`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `profils_orientation` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `nom` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `prenom` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `serie` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `age` int NOT NULL,
  `mention` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `moyenne` decimal(4,2) NOT NULL,
  `profession_reve` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ecole_reve` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `profils_orientation_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `profils_orientation`
--

LOCK TABLES `profils_orientation` WRITE;
/*!40000 ALTER TABLE `profils_orientation` DISABLE KEYS */;
INSERT INTO `profils_orientation` VALUES (1,3,'Jérôme','Elvis','A1',18,'Assez Bien',13.00,'Orateur',NULL,'2026-07-25 01:58:37'),(2,3,'AZOUMA','Jean','A1',19,'Bien',12.00,'Chef','Epitec','2026-07-25 11:04:45'),(3,3,'AZOUMA','Jean','A1',19,'Bien',12.00,'Chef','Epitec','2026-07-25 11:05:44');
/*!40000 ALTER TABLE `profils_orientation` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nom_complet` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `prenom` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `age` int DEFAULT NULL,
  `google_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password_hash` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `serie` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `mention` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `moyenne` decimal(4,2) DEFAULT NULL,
  `profession_reve` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ecole_reve` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `numero_telephone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `code_accompagnement` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `auth_provider` enum('google','email') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'email',
  `email_verified` tinyint(1) NOT NULL DEFAULT '0',
  `verification_code` varchar(6) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `verification_code_expires_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `reset_code` varchar(6) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `reset_code_expires_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `google_id` (`google_id`),
  UNIQUE KEY `code_accompagnement` (`code_accompagnement`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,NULL,NULL,NULL,NULL,'doguehilaire@gmail.com','$2y$10$9i/yBfJMltm5dGzunzKMSOCWvgz2joZjXAI/hJgWID911qkd.C2/C',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'email',0,NULL,NULL,'2026-07-23 02:08:51',NULL,NULL),(2,NULL,NULL,NULL,NULL,'doguevictorhugo@gmail.com','$2y$10$4KP7L/lmap6WgjaG1Mop7.Vxfy3xBXuK/zW04ObzAva4czNms.btm',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'email',1,NULL,NULL,'2026-07-23 02:57:57',NULL,NULL),(3,'Jean AZOUMA','Jean',19,NULL,'ppelvis5@gmail.com','$2y$10$oHOI7kB0i2W9tnASs.6l7erSF6NajnK47H7XHn5gsQdA61PUXKXnK','A1','Bien',12.00,'Chef','Epitec',NULL,'APB-BF55BA','email',1,NULL,NULL,'2026-07-23 16:21:56',NULL,NULL),(4,'Dogue hilaire',NULL,NULL,NULL,'prezio566@gmail.com','$2y$10$KPEt00.yWINwUA9CiHRDQudpzY4.MXbPTgjTnkFJMSp3szKRt/k52','D',NULL,NULL,NULL,NULL,NULL,NULL,'email',1,NULL,NULL,'2026-07-24 04:54:17',NULL,NULL),(5,'Tresor Sylvain Apovo',NULL,NULL,NULL,'tresorsylvainapovo@gmail.com','$2y$10$1LAKWuicFSzb6BbewIwTie95NSYgFXSgv0cxKlhcFqtaQwwaB4qz2','A2',NULL,NULL,NULL,NULL,NULL,NULL,'email',0,'929026','2026-07-25 11:59:38','2026-07-25 11:44:38',NULL,NULL);
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
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
) ENGINE=InnoDB AUTO_INCREMENT=156 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `visites`
--

LOCK TABLES `visites` WRITE;
/*!40000 ALTER TABLE `visites` DISABLE KEYS */;
INSERT INTO `visites` VALUES (1,'f155040d55b182f27e1db09a487ee3c6','2026-07-24','2026-07-24 22:58:56'),(26,'d2e5e45b2023ccf7da0b83283d791cb4','2026-07-24','2026-07-24 23:46:07'),(29,'ffe99be5c21836aaec55a192e59b46f7','2026-07-25','2026-07-25 00:27:40'),(30,'0659591780edab6c8f57ed24a266cfa1','2026-07-25','2026-07-25 00:27:43'),(31,'a386a988d34265d084c42d64a54e14a7','2026-07-25','2026-07-25 00:27:45'),(32,'d2e5e45b2023ccf7da0b83283d791cb4','2026-07-25','2026-07-25 00:28:04'),(33,'f7bcac24829517b9451fecebee74e25d','2026-07-25','2026-07-25 00:29:15'),(42,'7f6fffb43c3f5a185eed00c60cfd0aa1','2026-07-25','2026-07-25 00:52:17'),(66,'f57def4c650f5f7d37a4fd77515d198e','2026-07-25','2026-07-25 01:27:58'),(74,'5b81190022e28482ba097bd867d653b9','2026-07-25','2026-07-25 01:34:32'),(76,'93987b5db5ac13c99d00ffd851735106','2026-07-25','2026-07-25 01:34:42'),(77,'d9a26b0d2c02330df798b6ac3559d5cb','2026-07-25','2026-07-25 01:34:42'),(78,'513c9daf1d00b187217be4a2f3791435','2026-07-25','2026-07-25 01:34:42'),(81,'865a3a8b825392b6f1536f59eee17d32','2026-07-25','2026-07-25 01:34:57'),(82,'f2bf7107b46aab989ef8eae31c75680b','2026-07-25','2026-07-25 01:34:58'),(83,'9f2a32001d04162268ff664e3c7ddc34','2026-07-25','2026-07-25 01:34:58'),(87,'8558d8d17551119cede4f6a87baac040','2026-07-25','2026-07-25 01:36:05'),(90,'3ef5e98972f935f1c9f12a5106ba75e5','2026-07-25','2026-07-25 02:20:29'),(95,'efb83a3e0ad7a5c9628e2e4576668294','2026-07-25','2026-07-25 02:20:58'),(101,'d7969a512cfbfdcb5f181023ef9d0612','2026-07-25','2026-07-25 02:37:42'),(105,'6ecdaefd7efc9902f31ac20e34eecdd0','2026-07-25','2026-07-25 02:54:14'),(110,'8877c74e075292925e2bf8b50a6b3ad8','2026-07-25','2026-07-25 02:59:30'),(113,'5ddc8d110d208bc9ecec44adf5f1e0db','2026-07-25','2026-07-25 03:01:22'),(115,'b58ec7ca757542b6b57484aebaba806c','2026-07-25','2026-07-25 10:50:32'),(116,'28c5c8c8430cbf2c5f54d856ee5e35cd','2026-07-25','2026-07-25 10:50:44'),(117,'efbe9727a610de378a75e22c75cc53a9','2026-07-25','2026-07-25 10:50:46'),(118,'8d19d27a337300cf77ec259f59d8db66','2026-07-25','2026-07-25 12:00:53'),(122,'50634aad0ac0643648ddcd1eae48a272','2026-07-25','2026-07-25 12:01:54'),(123,'82fac51da3d500adcacc7ae1e2c8fa43','2026-07-25','2026-07-25 12:01:55'),(124,'a4da6892304831bfb1090991eb775d8b','2026-07-25','2026-07-25 12:01:55'),(126,'77ba2a25595013873d9d97728ecaef13','2026-07-25','2026-07-25 12:06:01'),(127,'0406a8fc5a409df05cdfcad86873ce01','2026-07-25','2026-07-25 12:17:26'),(128,'88c032dd5ab6163c1ee0e5ca03ab6892','2026-07-25','2026-07-25 12:41:44'),(129,'f82cd3793f37e00686d05f338a3e4b62','2026-07-25','2026-07-25 12:42:34'),(132,'0da1d95bcdf4c3b2cf116e1a8a2da7e2','2026-07-25','2026-07-25 12:44:15'),(133,'adf8a53d70d5da9f228bf07b7a635115','2026-07-25','2026-07-25 12:44:16'),(134,'95f4b38beef943ae4f83b212b01a62da','2026-07-25','2026-07-25 12:44:16'),(137,'21374219f976f789a68cfe09813178c7','2026-07-25','2026-07-25 12:54:09'),(146,'92885b4c28aaa97d25faba3f79d30e18','2026-07-25','2026-07-25 13:03:34'),(147,'29aab02f9c2e77aea823ef6380222e3a','2026-07-25','2026-07-25 13:06:36'),(148,'c5adf970b64a9a70969f534339f4c709','2026-07-25','2026-07-25 13:06:39'),(149,'45505cd04a63d8062c91ece5d2dd3966','2026-07-25','2026-07-25 16:02:36'),(152,'71a9dccdb04b30672ec366d38138a6d4','2026-07-25','2026-07-25 16:52:25'),(154,'60417b2c08f333401c7f4c6a207068f9','2026-07-25','2026-07-25 23:57:03'),(155,'8e6afc0565f532e7e26d50f5e49d1c2e','2026-07-26','2026-07-26 07:25:43');
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

-- Dump completed on 2026-07-26  8:06:06
