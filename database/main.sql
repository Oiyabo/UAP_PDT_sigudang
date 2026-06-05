DROP DATABASE IF EXISTS `db_gudang`;
CREATE DATABASE `db_gudang`;
USE `db_gudang`;

-- Tables

CREATE TABLE `barang` (
  `id` int NOT NULL AUTO_INCREMENT,
  `kode_barang` varchar(20) NOT NULL,
  `nama_barang` varchar(100) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `kode_barang` (`kode_barang`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `cabang` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nama_cabang` varchar(100) NOT NULL,
  `lokasi` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(50) NOT NULL,
  `role` enum('Admin','Petugas') NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Functions

DELIMITER $$
CREATE FUNCTION `cek_status_stok` (`jumlah_stok` INT) RETURNS VARCHAR(30) CHARSET utf8mb4 DETERMINISTIC 
BEGIN
    IF jumlah_stok < 20 THEN
        RETURN 'Kritis (Butuh Restock)';
    ELSE
        RETURN 'Aman';
    END IF;
END$$

DROP FUNCTION IF EXISTS `CekStokCabang`$$
CREATE FUNCTION `CekStokCabang`(p_barang_id INT, p_cabang_id INT) RETURNS INT
DETERMINISTIC
BEGIN
    DECLARE v_stok INT DEFAULT 0;
    SELECT stok INTO v_stok FROM stok_cabang WHERE barang_id = p_barang_id AND cabang_id = p_cabang_id;
    RETURN IFNULL(v_stok, 0);
END$$

DELIMITER ;

-- Procedures

DELIMITER $$
DROP PROCEDURE IF EXISTS `CatatTransaksi`$$
CREATE PROCEDURE `CatatTransaksi`(
    IN p_barang_id INT,
    IN p_jenis ENUM('masuk', 'keluar', 'mutasi'),
    IN p_jumlah INT,
    IN p_cabang_asal INT,
    IN p_cabang_tujuan INT
)
BEGIN
    DECLARE v_stok_asal INT DEFAULT 0;

    IF p_jenis = 'keluar' OR p_jenis = 'mutasi' THEN
        SET v_stok_asal = CekStokCabang(p_barang_id, p_cabang_asal);
        
        IF v_stok_asal < p_jumlah THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Stok di cabang asal tidak mencukupi untuk melakukan transaksi ini.';
        END IF;
    END IF;

    INSERT INTO transaksi (barang_id, jenis_transaksi, jumlah, cabang_asal_id, cabang_tujuan_id)
    VALUES (p_barang_id, p_jenis, p_jumlah, p_cabang_asal, p_cabang_tujuan);
END$$

DELIMITER ;

-- Triggers

DELIMITER $$
CREATE TRIGGER `update_stok_otomatis` AFTER INSERT ON `transaksi` FOR EACH ROW 
BEGIN
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
END$$
DELIMITER ;

-- Views

DROP VIEW IF EXISTS `v_riwayat_transaksi`;

CREATE VIEW `v_riwayat_transaksi` AS 
SELECT 
  `t`.`id` AS `id`, 
  `t`.`tanggal` AS `tanggal`, 
  `b`.`kode_barang` AS `kode_barang`, 
  `b`.`nama_barang` AS `nama_barang`, 
  `t`.`jenis_transaksi` AS `jenis_transaksi`, 
  `t`.`jumlah` AS `jumlah` 
FROM `transaksi` `t` 
JOIN `barang` `b` ON `t`.`barang_id` = `b`.`id`;

DROP VIEW IF EXISTS `v_komparasi_union`;
CREATE VIEW `v_komparasi_union` AS
SELECT DISTINCT b.kode_barang, b.nama_barang, 'Gudang Pusat' AS lokasi 
FROM stok_cabang sc JOIN barang b ON sc.barang_id = b.id WHERE sc.cabang_id = 1 AND sc.stok > 0
UNION
SELECT DISTINCT b.kode_barang, b.nama_barang, c.nama_cabang AS lokasi 
FROM stok_cabang sc JOIN barang b ON sc.barang_id = b.id JOIN cabang c ON sc.cabang_id = c.id WHERE sc.cabang_id != 1 AND sc.stok > 0;

DROP VIEW IF EXISTS `v_komparasi_intersect`;
CREATE VIEW `v_komparasi_intersect` AS
SELECT DISTINCT b.kode_barang, b.nama_barang 
FROM stok_cabang sc_pusat 
JOIN barang b ON sc_pusat.barang_id = b.id
WHERE sc_pusat.cabang_id = 1 AND sc_pusat.stok > 0
AND b.id IN (SELECT barang_id FROM stok_cabang WHERE cabang_id != 1 AND stok > 0);

DROP VIEW IF EXISTS `v_komparasi_except`;
CREATE VIEW `v_komparasi_except` AS
SELECT DISTINCT b.kode_barang, b.nama_barang 
FROM stok_cabang sc_pusat 
JOIN barang b ON sc_pusat.barang_id = b.id
WHERE sc_pusat.cabang_id = 1 AND sc_pusat.stok > 0
AND b.id NOT IN (SELECT barang_id FROM stok_cabang WHERE cabang_id != 1 AND stok > 0);

DROP VIEW IF EXISTS `v_frag_transaksi_masuk`;
CREATE VIEW `v_frag_transaksi_masuk` AS
SELECT * FROM transaksi WHERE jenis_transaksi = 'masuk';

DROP VIEW IF EXISTS `v_frag_transaksi_detail`;
CREATE VIEW `v_frag_transaksi_detail` AS
SELECT id, barang_id, jumlah FROM transaksi;

DROP VIEW IF EXISTS `v_frag_transaksi_masuk_ringkas`;
CREATE VIEW `v_frag_transaksi_masuk_ringkas` AS
SELECT id, barang_id, jumlah, tanggal 
FROM transaksi 
WHERE jenis_transaksi = 'masuk';

-- Payload

INSERT INTO `barang` (`id`, `kode_barang`, `nama_barang`) VALUES
(1, 'BRG-001', 'Kardus Polos Besar'),
(2, 'BRG-002', 'Lakban Bening Tebal'),
(3, 'BRG-003', 'Bubble Wrap 50m'),
(4, 'BRG-004', 'Isolasi Kertas'),
(5, 'BRG-005', 'Plastik HD Hitam Besar'),
(6, 'BRG-006', 'Tali Rafia 1kg'),
(7, 'BRG-007', 'Gunting Kertas Besar'),
(8, 'BRG-008', 'Cutter Serbaguna'),
(9, 'BRG-009', 'Isi Staples Tembak'),
(10, 'BRG-010', 'Staples Tembak Heavy Duty'),
(11, 'BRG-011', 'Spidol Permanen Hitam'),
(12, 'BRG-012', 'Label Pengiriman (Thermal)'),
(13, 'BRG-013', 'Plastik Zipper 20x30'),
(14, 'BRG-014', 'Karung Goni 50kg'),
(15, 'BRG-015', 'Pallet Kayu Standar'),
(16, 'BRG-016', 'Sarung Tangan Karet'),
(17, 'BRG-017', 'Masker Anti Debu'),
(18, 'BRG-018', 'Helm Proyek Kuning');

INSERT INTO `cabang` (`id`, `nama_cabang`, `lokasi`) VALUES
(1, 'Gudang Pusat (Jakarta)', 'Jl. Sudirman No. 1, Jakarta'),
(2, 'Cabang Bandung', 'Jl. Asia Afrika No. 10, Bandung'),
(3, 'Cabang Surabaya', 'Jl. Pemuda No. 45, Surabaya'),
(4, 'Cabang Medan', 'Jl. Gatot Subroto, Medan'),
(5, 'Cabang Makassar', 'Jl. AP Pettarani, Makassar');

INSERT INTO `stok_cabang` (`id`, `barang_id`, `cabang_id`, `stok`) VALUES
(1, 1, 1, 208),
(2, 13, 1, 215),
(3, 13, 5, 107),
(4, 2, 1, 899),
(5, 11, 1, 207),
(6, 11, 5, 295),
(7, 11, 4, 491),
(8, 6, 1, 121),
(9, 6, 3, 103),
(10, 6, 4, 355),
(11, 6, 2, 4),
(12, 15, 1, 446),
(13, 10, 1, 1778),
(14, 9, 1, 76),
(15, 9, 5, 79),
(16, 9, 3, 218),
(17, 5, 4, 802),
(18, 5, 1, 292),
(19, 7, 1, 883),
(20, 7, 2, 236),
(21, 7, 5, 56),
(22, 3, 5, 56),
(23, 3, 1, 1154),
(24, 3, 3, 106),
(25, 12, 1, 764),
(26, 4, 1, 2015),
(27, 4, 5, 254),
(28, 8, 1, 1048),
(29, 14, 2, 83),
(30, 18, 4, 15);

INSERT INTO `transaksi` (`id`, `barang_id`, `jenis_transaksi`, `jumlah`, `cabang_asal_id`, `cabang_tujuan_id`, `tanggal`) VALUES
(1, 1, 'masuk', 153, NULL, 1, '2026-04-06 05:56:45'),
(2, 13, 'masuk', 215, NULL, 1, '2026-04-07 19:39:50'),
(3, 2, 'masuk', 426, NULL, 1, '2026-04-08 05:34:04'),
(4, 11, 'masuk', 402, NULL, 1, '2026-04-08 15:53:10'),
(5, 6, 'masuk', 202, NULL, 1, '2026-04-10 07:31:03'),
(6, 6, 'mutasi', 103, 1, 3, '2026-04-10 16:50:58'),
(7, 15, 'masuk', 446, NULL, 1, '2026-04-12 18:26:10'),
(8, 10, 'masuk', 54, NULL, 1, '2026-04-13 05:47:27'),
(9, 9, 'masuk', 155, NULL, 1, '2026-04-13 15:42:05'),
(10, 5, 'masuk', 385, NULL, 4, '2026-04-14 15:56:26'),
(11, 2, 'masuk', 380, NULL, 1, '2026-04-15 19:04:57'),
(12, 7, 'masuk', 218, NULL, 1, '2026-04-16 08:31:38'),
(13, 3, 'masuk', 56, NULL, 5, '2026-04-16 16:26:43'),
(14, 6, 'masuk', 398, NULL, 1, '2026-04-17 19:10:13'),
(15, 12, 'masuk', 274, NULL, 1, '2026-04-19 07:39:55'),
(16, 11, 'masuk', 60, NULL, 5, '2026-04-20 08:39:54'),
(17, 11, 'masuk', 235, NULL, 5, '2026-04-21 05:59:21'),
(18, 3, 'masuk', 185, NULL, 1, '2026-04-21 16:07:33'),
(19, 4, 'masuk', 189, NULL, 1, '2026-04-22 06:00:41'),
(20, 4, 'masuk', 157, NULL, 1, '2026-04-23 05:19:19'),
(21, 9, 'mutasi', 79, 1, 5, '2026-04-23 17:26:27'),
(22, 7, 'masuk', 236, NULL, 2, '2026-04-24 15:45:31'),
(23, 7, 'masuk', 492, NULL, 1, '2026-04-25 08:31:55'),
(24, 7, 'mutasi', 56, 1, 5, '2026-04-25 20:22:08'),
(25, 10, 'masuk', 310, NULL, 1, '2026-04-26 05:52:09'),
(26, 1, 'masuk', 55, NULL, 1, '2026-04-26 19:26:42'),
(27, 4, 'mutasi', 120, 1, 5, '2026-04-27 17:47:54'),
(28, 8, 'masuk', 355, NULL, 1, '2026-04-28 04:14:54'),
(29, 4, 'masuk', 373, NULL, 1, '2026-04-28 16:29:52'),
(30, 10, 'masuk', 496, NULL, 1, '2026-04-29 08:19:05'),
(31, 7, 'masuk', 229, NULL, 1, '2026-04-29 19:20:47'),
(32, 3, 'masuk', 362, NULL, 1, '2026-04-30 04:34:09'),
(33, 3, 'masuk', 492, NULL, 1, '2026-05-01 05:28:57'),
(34, 8, 'masuk', 222, NULL, 1, '2026-05-01 17:36:44'),
(35, 12, 'masuk', 162, NULL, 1, '2026-05-02 05:06:04'),
(36, 3, 'masuk', 221, NULL, 1, '2026-05-03 07:36:38'),
(37, 4, 'masuk', 73, NULL, 1, '2026-05-04 04:04:32'),
(38, 10, 'masuk', 365, NULL, 1, '2026-05-05 04:01:07'),
(39, 12, 'masuk', 58, NULL, 1, '2026-05-05 17:22:04'),
(40, 4, 'masuk', 134, NULL, 5, '2026-05-06 07:27:19'),
(41, 5, 'masuk', 410, NULL, 1, '2026-05-06 19:25:36'),
(42, 6, 'mutasi', 495, 1, 4, '2026-05-07 04:46:35'),
(43, 13, 'masuk', 107, NULL, 5, '2026-05-08 07:12:45'),
(44, 6, 'masuk', 271, NULL, 2, '2026-05-09 06:00:49'),
(45, 14, 'masuk', 83, NULL, 2, '2026-05-11 18:45:07'),
(46, 4, 'masuk', 291, NULL, 1, '2026-05-12 08:48:30'),
(47, 11, 'masuk', 296, NULL, 1, '2026-05-13 08:42:08'),
(48, 4, 'masuk', 298, NULL, 1, '2026-05-13 18:31:51'),
(49, 8, 'masuk', 326, NULL, 1, '2026-05-14 08:12:47'),
(50, 4, 'masuk', 397, NULL, 1, '2026-05-15 08:26:02'),
(51, 2, 'masuk', 93, NULL, 1, '2026-05-15 18:13:56'),
(52, 6, 'masuk', 119, NULL, 1, '2026-05-16 05:34:28'),
(53, 11, 'mutasi', 491, 1, 4, '2026-05-17 05:27:43'),
(54, 10, 'masuk', 488, NULL, 1, '2026-05-17 20:09:13'),
(55, 5, 'masuk', 299, NULL, 1, '2026-05-19 18:43:54'),
(56, 8, 'masuk', 145, NULL, 1, '2026-05-20 07:56:32'),
(57, 3, 'mutasi', 106, 1, 3, '2026-05-20 19:57:28'),
(58, 9, 'masuk', 218, NULL, 3, '2026-05-21 07:05:19'),
(59, 6, 'keluar', 140, 4, NULL, '2026-05-21 19:56:11'),
(60, 10, 'masuk', 65, NULL, 1, '2026-05-22 06:49:02'),
(61, 6, 'keluar', 267, 2, NULL, '2026-05-22 18:40:40'),
(62, 12, 'masuk', 270, NULL, 1, '2026-05-23 04:34:34'),
(63, 4, 'masuk', 357, NULL, 1, '2026-05-23 18:01:16'),
(64, 5, 'mutasi', 417, 1, 4, '2026-05-25 05:59:05');

INSERT INTO `users` (`id`, `username`, `password`, `role`) VALUES
(1, 'admin', '123', 'Admin'),
(2, 'manager_pusat', '123', 'Admin'),
(3, 'petugas_bdg', '123', 'Petugas'),
(4, 'petugas_sby', '123', 'Petugas'),
(5, 'petugas_mdn', '123', 'Petugas');

source routines.sql;
