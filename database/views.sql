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
