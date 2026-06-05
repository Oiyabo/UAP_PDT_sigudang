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
