# 📦 Si-Gudang (Proyek UAP)
Proyek ini merupakan sistem inventaris dan manajemen gudang sederhana yang dibangun menggunakan PHP dan MySQL. Tujuannya sebagai platform pencatatan transaksi masuk, keluar, dan mutasi barang antar cabang dengan memanfaatkan Database Views, SQL Joins & Set Operations, Deadlock Management,procedure,  function, trigger, dan backup database + task scheduler. Sistem ini dilengkapi dengan interface yang bersih untuk memudahkan pemantauan stok secara langsung.

<h1>📌 Detail Konsep</h1>

<img src="https://via.placeholder.com/800x400.png?text=Dashboard+Si-Gudang" alt="Dashboard" />

## ⚙️ Trigger
Trigger ini bertujuan untuk menjaga konsistensi jumlah stok di tabel `stok_cabang` secara real-time setiap kali ada rekaman baru yang masuk di tabel `transaksi`.
`update_stok_otomatis` : 
- Jika transaksi **masuk**, maka stok di cabang tujuan akan bertambah.
- Jika transaksi **keluar**, maka stok di cabang asal akan berkurang.
- Jika transaksi **mutasi**, stok di cabang asal berkurang, dan stok di cabang tujuan bertambah.

```sql
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
```

## 🧩 Fragmentasi Data (Menggunakan View)
Untuk mempermudah pengelolaan dan analisis data yang besar, sistem ini menggunakan simulasi fragmentasi data menggunakan **View**. Terdapat tiga jenis fragmentasi yang diterapkan pada data transaksi:

1. **Fragmentasi Horizontal**: Memisahkan baris data berdasarkan kriteria tertentu (Contoh: Hanya transaksi barang masuk).
```sql
CREATE VIEW `v_frag_transaksi_masuk` AS
SELECT * FROM transaksi WHERE jenis_transaksi = 'masuk';
```

2. **Fragmentasi Vertikal**: Memisahkan kolom data yang spesifik untuk ringkasan (Contoh: Menampilkan informasi kuantitas transaksi saja).
```sql
CREATE VIEW `v_frag_transaksi_detail` AS
SELECT id, barang_id, jumlah FROM transaksi;
```

3. **Fragmentasi Campuran**: Kombinasi filter baris dan filter kolom sekaligus (Contoh: Rekap khusus untuk kuantitas transaksi masuk).
```sql
CREATE VIEW `v_frag_transaksi_masuk_ringkas` AS
SELECT id, barang_id, jumlah, tanggal 
FROM transaksi 
WHERE jenis_transaksi = 'masuk';
```


## 💾 Backup Otomatis & Task Scheduler

Untuk menjaga ketersediaan dan keamanan data, sistem ini dilengkapi fitur backup otomatis menggunakan `mysqldump` yang dapat diintegrasikan dengan *Task Scheduler* di Windows. Backup akan otomatis menyimpan file SQL dengan nama yang mencakup *timestamp*, dan sistem akan otomatis membersihkan file backup yang lebih tua dari 30 hari.

### 📄 auto_backup.php

```php
<?php
date_default_timezone_set('Asia/Jakarta');

if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    die("Script ini hanya dapat dijalankan dari Command Line");
}

require_once __DIR__ . '/config/koneksi.php';

$mysqldumpPath = "C:\\laragon\\bin\\mysql\\mysql-8.0.30-winx64\\bin\\mysqldump.exe";
$host = "localhost";
$user = "root";
$pass = "";
$db = "db_gudang";

$backupDir = __DIR__ . "\\backup\\";
if (!is_dir($backupDir)) {
    mkdir($backupDir, 0777, true);
}

$timestamp = date('Y-m-d_H-i-s');
$nama_file = $backupDir . "backup_" . $timestamp . ".sql";
$command = "\"$mysqldumpPath\" -h $host -u $user $db > \"$nama_file\"";

echo "[" . date('Y-m-d H:i:s') . "] Memulai backup database...\n";

// Eksekusi mysqldump
exec($command, $output, $return_var);

if ($return_var === 0) {
    echo "[" . date('Y-m-d H:i:s') . "] ✓ Backup Berhasil!\n";
    echo "  Lokasi: " . $nama_file . "\n";
    
    // Auto cleanup backup lama (30 hari)
    cleanOldBackups($backupDir, 30);
    exit(0);
} else {
    echo "[" . date('Y-m-d H:i:s') . "] ✗ Backup Gagal!\n";
    exit(1);
}

function cleanOldBackups($backupDir, $days = 30) {
    // ... logic penghapusan file lama
}
?>
```

Untuk menjadwalkan ini, admin hanya perlu membuat *Task* di **Windows Task Scheduler** yang mengeksekusi PHP CLI:
`C:\laragon\bin\php\php-8.1.10\php.exe d:\laragon\www\UAP_PDT_sigudang\auto_backup.php`
secara otomatis (misalnya setiap hari jam 12 malam).