-- MySQL dump 10.13  Distrib 8.0.30, for Win64 (x86_64)
--
-- Host: localhost    Database: db_gudang
-- ------------------------------------------------------
-- Server version	8.0.30

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
-- Table structure for table `barang`
--

DROP TABLE IF EXISTS `barang`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `barang` (
  `id` int NOT NULL AUTO_INCREMENT,
  `kode_barang` varchar(20) NOT NULL,
  `nama_barang` varchar(100) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `kode_barang` (`kode_barang`)
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `barang`
--

LOCK TABLES `barang` WRITE;
/*!40000 ALTER TABLE `barang` DISABLE KEYS */;
INSERT INTO `barang` VALUES (1,'BRG-001','Kardus Polos Besar'),(2,'BRG-002','Lakban Bening Tebal'),(3,'BRG-003','Bubble Wrap 50m'),(4,'BRG-004','Isolasi Kertas'),(5,'BRG-005','Plastik HD Hitam Besar'),(6,'BRG-006','Tali Rafia 1kg'),(7,'BRG-007','Gunting Kertas Besar'),(8,'BRG-008','Cutter Serbaguna'),(9,'BRG-009','Isi Staples Tembak'),(10,'BRG-010','Staples Tembak Heavy Duty'),(11,'BRG-011','Spidol Permanen Hitam'),(12,'BRG-012','Label Pengiriman (Thermal)'),(13,'BRG-013','Plastik Zipper 20x30'),(14,'BRG-014','Karung Goni 50kg'),(15,'BRG-015','Pallet Kayu Standar'),(16,'BRG-016','Sarung Tangan Karet'),(17,'BRG-017','Masker Anti Debu'),(18,'BRG-018','Helm Proyek Kuning');
/*!40000 ALTER TABLE `barang` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cabang`
--

DROP TABLE IF EXISTS `cabang`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cabang` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nama_cabang` varchar(100) NOT NULL,
  `lokasi` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cabang`
--

LOCK TABLES `cabang` WRITE;
/*!40000 ALTER TABLE `cabang` DISABLE KEYS */;
INSERT INTO `cabang` VALUES (1,'Gudang Pusat (Jakarta)','Jl. Sudirman No. 1, Jakarta'),(2,'Cabang Bandung','Jl. Asia Afrika No. 10, Bandung'),(3,'Cabang Surabaya','Jl. Pemuda No. 45, Surabaya'),(4,'Cabang Medan','Jl. Gatot Subroto, Medan'),(5,'Cabang Makassar','Jl. AP Pettarani, Makassar');
/*!40000 ALTER TABLE `cabang` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `stok_cabang`
--

DROP TABLE IF EXISTS `stok_cabang`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `stok_cabang` (
  `id` int NOT NULL AUTO_INCREMENT,
  `barang_id` int NOT NULL,
  `cabang_id` int NOT NULL,
  `stok` int DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `barang_id` (`barang_id`,`cabang_id`),
  KEY `cabang_id` (`cabang_id`),
  CONSTRAINT `stok_cabang_ibfk_1` FOREIGN KEY (`barang_id`) REFERENCES `barang` (`id`) ON DELETE CASCADE,
  CONSTRAINT `stok_cabang_ibfk_2` FOREIGN KEY (`cabang_id`) REFERENCES `cabang` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=93 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `stok_cabang`
--

LOCK TABLES `stok_cabang` WRITE;
/*!40000 ALTER TABLE `stok_cabang` DISABLE KEYS */;
INSERT INTO `stok_cabang` VALUES (1,1,1,416),(2,13,1,430),(3,13,5,214),(4,2,1,1798),(5,11,1,414),(6,11,5,590),(7,11,4,982),(8,6,1,242),(9,6,3,206),(10,6,4,710),(11,6,2,8),(12,15,1,892),(13,10,1,3556),(14,9,1,152),(15,9,5,158),(16,9,3,436),(17,5,4,1604),(18,5,1,584),(19,7,1,1766),(20,7,2,472),(21,7,5,112),(22,3,5,112),(23,3,1,2308),(24,3,3,212),(25,12,1,1528),(26,4,1,4030),(27,4,5,508),(28,8,1,2096),(29,14,2,166),(30,18,4,15);
/*!40000 ALTER TABLE `stok_cabang` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `transaksi`
--

DROP TABLE IF EXISTS `transaksi`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `transaksi` (
  `id` int NOT NULL AUTO_INCREMENT,
  `barang_id` int NOT NULL,
  `jenis_transaksi` enum('masuk','keluar','mutasi') NOT NULL,
  `jumlah` int NOT NULL,
  `cabang_asal_id` int DEFAULT NULL,
  `cabang_tujuan_id` int DEFAULT NULL,
  `tanggal` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `barang_id` (`barang_id`),
  KEY `cabang_asal_id` (`cabang_asal_id`),
  KEY `cabang_tujuan_id` (`cabang_tujuan_id`),
  CONSTRAINT `transaksi_ibfk_1` FOREIGN KEY (`barang_id`) REFERENCES `barang` (`id`) ON DELETE CASCADE,
  CONSTRAINT `transaksi_ibfk_2` FOREIGN KEY (`cabang_asal_id`) REFERENCES `cabang` (`id`) ON DELETE SET NULL,
  CONSTRAINT `transaksi_ibfk_3` FOREIGN KEY (`cabang_tujuan_id`) REFERENCES `cabang` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=65 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `transaksi`
--

LOCK TABLES `transaksi` WRITE;
/*!40000 ALTER TABLE `transaksi` DISABLE KEYS */;
INSERT INTO `transaksi` VALUES (1,1,'masuk',153,NULL,1,'2026-04-05 22:56:45'),(2,13,'masuk',215,NULL,1,'2026-04-07 12:39:50'),(3,2,'masuk',426,NULL,1,'2026-04-07 22:34:04'),(4,11,'masuk',402,NULL,1,'2026-04-08 08:53:10'),(5,6,'masuk',202,NULL,1,'2026-04-10 00:31:03'),(6,6,'mutasi',103,1,3,'2026-04-10 09:50:58'),(7,15,'masuk',446,NULL,1,'2026-04-12 11:26:10'),(8,10,'masuk',54,NULL,1,'2026-04-12 22:47:27'),(9,9,'masuk',155,NULL,1,'2026-04-13 08:42:05'),(10,5,'masuk',385,NULL,4,'2026-04-14 08:56:26'),(11,2,'masuk',380,NULL,1,'2026-04-15 12:04:57'),(12,7,'masuk',218,NULL,1,'2026-04-16 01:31:38'),(13,3,'masuk',56,NULL,5,'2026-04-16 09:26:43'),(14,6,'masuk',398,NULL,1,'2026-04-17 12:10:13'),(15,12,'masuk',274,NULL,1,'2026-04-19 00:39:55'),(16,11,'masuk',60,NULL,5,'2026-04-20 01:39:54'),(17,11,'masuk',235,NULL,5,'2026-04-20 22:59:21'),(18,3,'masuk',185,NULL,1,'2026-04-21 09:07:33'),(19,4,'masuk',189,NULL,1,'2026-04-21 23:00:41'),(20,4,'masuk',157,NULL,1,'2026-04-22 22:19:19'),(21,9,'mutasi',79,1,5,'2026-04-23 10:26:27'),(22,7,'masuk',236,NULL,2,'2026-04-24 08:45:31'),(23,7,'masuk',492,NULL,1,'2026-04-25 01:31:55'),(24,7,'mutasi',56,1,5,'2026-04-25 13:22:08'),(25,10,'masuk',310,NULL,1,'2026-04-25 22:52:09'),(26,1,'masuk',55,NULL,1,'2026-04-26 12:26:42'),(27,4,'mutasi',120,1,5,'2026-04-27 10:47:54'),(28,8,'masuk',355,NULL,1,'2026-04-27 21:14:54'),(29,4,'masuk',373,NULL,1,'2026-04-28 09:29:52'),(30,10,'masuk',496,NULL,1,'2026-04-29 01:19:05'),(31,7,'masuk',229,NULL,1,'2026-04-29 12:20:47'),(32,3,'masuk',362,NULL,1,'2026-04-29 21:34:09'),(33,3,'masuk',492,NULL,1,'2026-04-30 22:28:57'),(34,8,'masuk',222,NULL,1,'2026-05-01 10:36:44'),(35,12,'masuk',162,NULL,1,'2026-05-01 22:06:04'),(36,3,'masuk',221,NULL,1,'2026-05-03 00:36:38'),(37,4,'masuk',73,NULL,1,'2026-05-03 21:04:32'),(38,10,'masuk',365,NULL,1,'2026-05-04 21:01:07'),(39,12,'masuk',58,NULL,1,'2026-05-05 10:22:04'),(40,4,'masuk',134,NULL,5,'2026-05-06 00:27:19'),(41,5,'masuk',410,NULL,1,'2026-05-06 12:25:36'),(42,6,'mutasi',495,1,4,'2026-05-06 21:46:35'),(43,13,'masuk',107,NULL,5,'2026-05-08 00:12:45'),(44,6,'masuk',271,NULL,2,'2026-05-08 23:00:49'),(45,14,'masuk',83,NULL,2,'2026-05-11 11:45:07'),(46,4,'masuk',291,NULL,1,'2026-05-12 01:48:30'),(47,11,'masuk',296,NULL,1,'2026-05-13 01:42:08'),(48,4,'masuk',298,NULL,1,'2026-05-13 11:31:51'),(49,8,'masuk',326,NULL,1,'2026-05-14 01:12:47'),(50,4,'masuk',397,NULL,1,'2026-05-15 01:26:02'),(51,2,'masuk',93,NULL,1,'2026-05-15 11:13:56'),(52,6,'masuk',119,NULL,1,'2026-05-15 22:34:28'),(53,11,'mutasi',491,1,4,'2026-05-16 22:27:43'),(54,10,'masuk',488,NULL,1,'2026-05-17 13:09:13'),(55,5,'masuk',299,NULL,1,'2026-05-19 11:43:54'),(56,8,'masuk',145,NULL,1,'2026-05-20 00:56:32'),(57,3,'mutasi',106,1,3,'2026-05-20 12:57:28'),(58,9,'masuk',218,NULL,3,'2026-05-21 00:05:19'),(59,6,'keluar',140,4,NULL,'2026-05-21 12:56:11'),(60,10,'masuk',65,NULL,1,'2026-05-21 23:49:02'),(61,6,'keluar',267,2,NULL,'2026-05-22 11:40:40'),(62,12,'masuk',270,NULL,1,'2026-05-22 21:34:34'),(63,4,'masuk',357,NULL,1,'2026-05-23 11:01:16'),(64,5,'mutasi',417,1,4,'2026-05-24 22:59:05');
/*!40000 ALTER TABLE `transaksi` ENABLE KEYS */;
UNLOCK TABLES;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `update_stok_otomatis` AFTER INSERT ON `transaksi` FOR EACH ROW BEGIN
    IF NEW.jenis_transaksi = 'masuk' THEN
        INSERT INTO stok_cabang (barang_id, cabang_id, stok) 
        VALUES (NEW.barang_id, NEW.cabang_tujuan_id, NEW.jumlah)
        ON DUPLICATE KEY UPDATE stok = stok + NEW.jumlah;
    ELSEIF NEW.jenis_transaksi = 'keluar' THEN
        UPDATE stok_cabang SET stok = stok - NEW.jumlah 
        WHERE barang_id = NEW.barang_id AND cabang_id = NEW.cabang_asal_id;
    ELSEIF NEW.jenis_transaksi = 'mutasi' THEN
        UPDATE stok_cabang SET stok = stok - NEW.jumlah 
        WHERE barang_id = NEW.barang_id AND cabang_id = NEW.cabang_asal_id;
        INSERT INTO stok_cabang (barang_id, cabang_id, stok) 
        VALUES (NEW.barang_id, NEW.cabang_tujuan_id, NEW.jumlah)
        ON DUPLICATE KEY UPDATE stok = stok + NEW.jumlah;
    END IF;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(50) NOT NULL,
  `role` enum('Admin','Petugas') NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'admin','123','Admin'),(2,'manager_pusat','123','Admin'),(3,'petugas_bdg','123','Petugas'),(4,'petugas_sby','123','Petugas'),(5,'petugas_mdn','123','Petugas');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Temporary view structure for view `v_frag_transaksi_detail`
--

DROP TABLE IF EXISTS `v_frag_transaksi_detail`;
/*!50001 DROP VIEW IF EXISTS `v_frag_transaksi_detail`*/;
SET @saved_cs_client     = @@character_set_client;
/*!50503 SET character_set_client = utf8mb4 */;
/*!50001 CREATE VIEW `v_frag_transaksi_detail` AS SELECT 
 1 AS `id`,
 1 AS `barang_id`,
 1 AS `jumlah`*/;
SET character_set_client = @saved_cs_client;

--
-- Temporary view structure for view `v_frag_transaksi_masuk`
--

DROP TABLE IF EXISTS `v_frag_transaksi_masuk`;
/*!50001 DROP VIEW IF EXISTS `v_frag_transaksi_masuk`*/;
SET @saved_cs_client     = @@character_set_client;
/*!50503 SET character_set_client = utf8mb4 */;
/*!50001 CREATE VIEW `v_frag_transaksi_masuk` AS SELECT 
 1 AS `id`,
 1 AS `barang_id`,
 1 AS `jenis_transaksi`,
 1 AS `jumlah`,
 1 AS `cabang_asal_id`,
 1 AS `cabang_tujuan_id`,
 1 AS `tanggal`*/;
SET character_set_client = @saved_cs_client;

--
-- Temporary view structure for view `v_frag_transaksi_masuk_ringkas`
--

DROP TABLE IF EXISTS `v_frag_transaksi_masuk_ringkas`;
/*!50001 DROP VIEW IF EXISTS `v_frag_transaksi_masuk_ringkas`*/;
SET @saved_cs_client     = @@character_set_client;
/*!50503 SET character_set_client = utf8mb4 */;
/*!50001 CREATE VIEW `v_frag_transaksi_masuk_ringkas` AS SELECT 
 1 AS `id`,
 1 AS `barang_id`,
 1 AS `jumlah`,
 1 AS `tanggal`*/;
SET character_set_client = @saved_cs_client;

--
-- Temporary view structure for view `v_komparasi_except`
--

DROP TABLE IF EXISTS `v_komparasi_except`;
/*!50001 DROP VIEW IF EXISTS `v_komparasi_except`*/;
SET @saved_cs_client     = @@character_set_client;
/*!50503 SET character_set_client = utf8mb4 */;
/*!50001 CREATE VIEW `v_komparasi_except` AS SELECT 
 1 AS `kode_barang`,
 1 AS `nama_barang`*/;
SET character_set_client = @saved_cs_client;

--
-- Temporary view structure for view `v_komparasi_intersect`
--

DROP TABLE IF EXISTS `v_komparasi_intersect`;
/*!50001 DROP VIEW IF EXISTS `v_komparasi_intersect`*/;
SET @saved_cs_client     = @@character_set_client;
/*!50503 SET character_set_client = utf8mb4 */;
/*!50001 CREATE VIEW `v_komparasi_intersect` AS SELECT 
 1 AS `kode_barang`,
 1 AS `nama_barang`*/;
SET character_set_client = @saved_cs_client;

--
-- Temporary view structure for view `v_komparasi_union`
--

DROP TABLE IF EXISTS `v_komparasi_union`;
/*!50001 DROP VIEW IF EXISTS `v_komparasi_union`*/;
SET @saved_cs_client     = @@character_set_client;
/*!50503 SET character_set_client = utf8mb4 */;
/*!50001 CREATE VIEW `v_komparasi_union` AS SELECT 
 1 AS `kode_barang`,
 1 AS `nama_barang`,
 1 AS `lokasi`*/;
SET character_set_client = @saved_cs_client;

--
-- Temporary view structure for view `v_riwayat_transaksi`
--

DROP TABLE IF EXISTS `v_riwayat_transaksi`;
/*!50001 DROP VIEW IF EXISTS `v_riwayat_transaksi`*/;
SET @saved_cs_client     = @@character_set_client;
/*!50503 SET character_set_client = utf8mb4 */;
/*!50001 CREATE VIEW `v_riwayat_transaksi` AS SELECT 
 1 AS `id`,
 1 AS `tanggal`,
 1 AS `kode_barang`,
 1 AS `nama_barang`,
 1 AS `jenis_transaksi`,
 1 AS `jumlah`*/;
SET character_set_client = @saved_cs_client;

--
-- Final view structure for view `v_frag_transaksi_detail`
--

/*!50001 DROP VIEW IF EXISTS `v_frag_transaksi_detail`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_unicode_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `v_frag_transaksi_detail` AS select `transaksi`.`id` AS `id`,`transaksi`.`barang_id` AS `barang_id`,`transaksi`.`jumlah` AS `jumlah` from `transaksi` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `v_frag_transaksi_masuk`
--

/*!50001 DROP VIEW IF EXISTS `v_frag_transaksi_masuk`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_unicode_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `v_frag_transaksi_masuk` AS select `transaksi`.`id` AS `id`,`transaksi`.`barang_id` AS `barang_id`,`transaksi`.`jenis_transaksi` AS `jenis_transaksi`,`transaksi`.`jumlah` AS `jumlah`,`transaksi`.`cabang_asal_id` AS `cabang_asal_id`,`transaksi`.`cabang_tujuan_id` AS `cabang_tujuan_id`,`transaksi`.`tanggal` AS `tanggal` from `transaksi` where (`transaksi`.`jenis_transaksi` = 'masuk') */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `v_frag_transaksi_masuk_ringkas`
--

/*!50001 DROP VIEW IF EXISTS `v_frag_transaksi_masuk_ringkas`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_unicode_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `v_frag_transaksi_masuk_ringkas` AS select `transaksi`.`id` AS `id`,`transaksi`.`barang_id` AS `barang_id`,`transaksi`.`jumlah` AS `jumlah`,`transaksi`.`tanggal` AS `tanggal` from `transaksi` where (`transaksi`.`jenis_transaksi` = 'masuk') */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `v_komparasi_except`
--

/*!50001 DROP VIEW IF EXISTS `v_komparasi_except`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_unicode_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `v_komparasi_except` AS select distinct `b`.`kode_barang` AS `kode_barang`,`b`.`nama_barang` AS `nama_barang` from (`stok_cabang` `sc_pusat` join `barang` `b` on((`sc_pusat`.`barang_id` = `b`.`id`))) where ((`sc_pusat`.`cabang_id` = 1) and (`sc_pusat`.`stok` > 0) and `b`.`id` in (select `stok_cabang`.`barang_id` from `stok_cabang` where ((`stok_cabang`.`cabang_id` <> 1) and (`stok_cabang`.`stok` > 0))) is false) */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `v_komparasi_intersect`
--

/*!50001 DROP VIEW IF EXISTS `v_komparasi_intersect`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_unicode_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `v_komparasi_intersect` AS select distinct `b`.`kode_barang` AS `kode_barang`,`b`.`nama_barang` AS `nama_barang` from (`stok_cabang` `sc_pusat` join `barang` `b` on((`sc_pusat`.`barang_id` = `b`.`id`))) where ((`sc_pusat`.`cabang_id` = 1) and (`sc_pusat`.`stok` > 0) and `b`.`id` in (select `stok_cabang`.`barang_id` from `stok_cabang` where ((`stok_cabang`.`cabang_id` <> 1) and (`stok_cabang`.`stok` > 0)))) */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `v_komparasi_union`
--

/*!50001 DROP VIEW IF EXISTS `v_komparasi_union`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_unicode_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `v_komparasi_union` AS select distinct `b`.`kode_barang` AS `kode_barang`,`b`.`nama_barang` AS `nama_barang`,'Gudang Pusat' AS `lokasi` from (`stok_cabang` `sc` join `barang` `b` on((`sc`.`barang_id` = `b`.`id`))) where ((`sc`.`cabang_id` = 1) and (`sc`.`stok` > 0)) union select distinct `b`.`kode_barang` AS `kode_barang`,`b`.`nama_barang` AS `nama_barang`,`c`.`nama_cabang` AS `lokasi` from ((`stok_cabang` `sc` join `barang` `b` on((`sc`.`barang_id` = `b`.`id`))) join `cabang` `c` on((`sc`.`cabang_id` = `c`.`id`))) where ((`sc`.`cabang_id` <> 1) and (`sc`.`stok` > 0)) */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `v_riwayat_transaksi`
--

/*!50001 DROP VIEW IF EXISTS `v_riwayat_transaksi`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_unicode_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `v_riwayat_transaksi` AS select `t`.`id` AS `id`,`t`.`tanggal` AS `tanggal`,`b`.`kode_barang` AS `kode_barang`,`b`.`nama_barang` AS `nama_barang`,`t`.`jenis_transaksi` AS `jenis_transaksi`,`t`.`jumlah` AS `jumlah` from (`transaksi` `t` join `barang` `b` on((`t`.`barang_id` = `b`.`id`))) */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-06-05 20:57:02
