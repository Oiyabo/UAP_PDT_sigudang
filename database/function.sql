DELIMITER $$
CREATE FUNCTION `cek_status_stok` (`jumlah_stok` INT) RETURNS VARCHAR(30) CHARSET utf8mb4 DETERMINISTIC 
BEGIN
    IF jumlah_stok < 20 THEN
        RETURN 'Kritis (Butuh Restock)';
    ELSE
        RETURN 'Aman';
    END IF;
END$$
DELIMITER ;
