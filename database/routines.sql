DELIMITER $$

-- --------------------------------------------------------
-- 1. FUNCTION: CekStokCabang
-- Mengembalikan jumlah stok dari barang dan cabang spesifik
-- --------------------------------------------------------
DROP FUNCTION IF EXISTS `CekStokCabang`$$
CREATE FUNCTION `CekStokCabang`(p_barang_id INT, p_cabang_id INT) RETURNS INT
DETERMINISTIC
BEGIN
    DECLARE v_stok INT DEFAULT 0;
    SELECT stok INTO v_stok FROM stok_cabang WHERE barang_id = p_barang_id AND cabang_id = p_cabang_id;
    RETURN IFNULL(v_stok, 0);
END$$

-- --------------------------------------------------------
-- 2. PROCEDURE: CatatTransaksi
-- Mencatat transaksi baru dengan validasi stok sebelumnya
-- --------------------------------------------------------
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

    -- Lakukan pengecekan ketersediaan stok khusus untuk barang keluar atau mutasi
    IF p_jenis = 'keluar' OR p_jenis = 'mutasi' THEN
        -- Memanggil function CekStokCabang
        SET v_stok_asal = CekStokCabang(p_barang_id, p_cabang_asal);
        
        -- Validasi stok
        IF v_stok_asal < p_jumlah THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Stok di cabang asal tidak mencukupi untuk melakukan transaksi ini.';
        END IF;
    END IF;

    -- Catat transaksi ke tabel transaksi
    -- Catatan: Setelah INSERT ini, Trigger `update_stok_otomatis` akan langsung tereksekusi
    INSERT INTO transaksi (barang_id, jenis_transaksi, jumlah, cabang_asal_id, cabang_tujuan_id)
    VALUES (p_barang_id, p_jenis, p_jumlah, p_cabang_asal, p_cabang_tujuan);

END$$

DELIMITER ;
