# ⚽⚾ Sportzone (Proyek UAP)
Proyek ini merupakan sistem perbankan sederhana yang dibangun menggunakan PHP dan MySQL. Tujuannya sebagai platform penyewaan dan manajemen fasilitas olahraga untuk komunitas dengan memanfaatkan transaction, procedure, function, trigger, dan backup database + task scheduler. Sistem ini juga dilengkapi mekanisme backup otomatis untuk menjaga keamanan data jika terjadi hal yang tidak diinginkan.
<img src="https://github.com/slabkim/sportzone/blob/main/imgAset/dashboard.png" >

<h1>📌Detail Konsep</h1>
👣Stored procedure bertindak seperti SOP internal yang menetapkan alur eksekusi berbagai operasi penting di sistem . Procedure ini disimpan langsung di lapisan database, sehingga dapat menjamin konsistensi, efisiensi, dan keamanan eksekusi, terutama dalam sistem terdistribusi atau multi-user.
<img src="https://github.com/slabkim/sportzone/blob/main/imgAset/routine.png" >
<img src="https://github.com/slabkim/sportzone/blob/main/imgAset/trigger.png" >
Beberapa procedure, function dan trigger yang digunakan:

`booking.php`


`AddBooking (p_user_id, p_facility_id, p_booking_date, p_booking_time)` : Menambahkan booking pada fasilitas.

```
$stmt = mysqli_prepare($conn, 'CALL AddBooking(?, ?, ?, ?)');
            mysqli_stmt_bind_param($stmt, 'iiss', $_SESSION['user_id'], $facility_id, $booking_date, $booking_time);
            mysqli_stmt_execute($stmt);
            $success = "Booking successful!";
```
 

`IsFacilityAvailable(p_facility_id, p_booking_date, p_booking_time)`
  Mengecek apakah suatu fasilitas tersedia pada tanggal dan waktu tertentu.  
  Function ini mengembalikan nilai `TRUE` jika fasilitas tersedia (belum dibooking dengan status `confirmed`) dan `FALSE` jika tidak tersedia.

```
// Memanggil fungsi IsFacilityAvailable untuk mengecek ketersediaan fasilitas
$stmt = mysqli_prepare($conn, 'SELECT IsFacilityAvailable(?, ?, ?) AS available');
        mysqli_stmt_bind_param($stmt, 'iss', $facility_id, $booking_date, $booking_time);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($result);

$result = $stmt->fetch();
$isAvailable = $result['available'];
```
`trg_update_facility_availability` : Trigger ini bertujuan untuk menjaga konsistensi ketersediaan fasilitas berdasarkan status booking:
- Jika booking dikonfirmasi, maka fasilitas terkait tidak lagi tersedia.
- Jika booking dibatalkan, maka fasilitas tersebut kembali tersedia.

``` 
BEGIN
    IF NEW.status = 'confirmed' THEN
        UPDATE facilities SET available = FALSE WHERE id = NEW.facility_id;
    ELSEIF NEW.status = 'cancelled' THEN
        UPDATE facilities SET available = TRUE WHERE id = NEW.facility_id;
    END IF;
END
```

## 💾 Backup Otomatis

Untuk menjaga ketersediaan dan keamanan data, sistem ini dilengkapi fitur backup otomatis menggunakan `mysqldump` dan *task scheduler*. Backup dilakukan oleh admin dan hasilnya disimpan dengan nama file yang mencakup *timestamp*, sehingga mudah ditelusuri. Semua file disimpan di direktori `storage/backups`.

Backup dilakukan melalui file `backup.php` dan hanya dapat diakses oleh pengguna dengan role **admin**.

---

### 📄 backup.php

```php
<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "Debug: backup.php script started.<br>";

date_default_timezone_set('Asia/Jakarta');

require_once __DIR__ . '/config/init.php';
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

$date = date('Y-m-d_H-i-s');
$backup_dir = __DIR__ . '/storage/backups/';
if (!is_dir($backup_dir)) {
    mkdir($backup_dir, 0755, true);
}
$backupFile = $backup_dir . "sportzone_backup_$date.sql";

$mysqldump_path = "C:\\laragon\\bin\\mysql\\mysql-8.0.30-winx64\\bin\\mysqldump.exe";
$db_user = 'root';
$db_pass = '';
$db_name = 'sportzone';

$command = "\"$mysqldump_path\" -u $db_user " .
    ($db_pass ? "-p$db_pass " : "") .
    "$db_name --result-file=\"$backupFile\" 2>&1";

exec($command, $output, $return_var);

$message = "Command executed with return code: $return_var\nOutput:\n" . implode("\n", $output);

if ($return_var === 0 && filesize($backupFile) > 0) {
    $message .= "\nBackup successfully saved to: $backupFile";
    $status = 'success';
} else {
    $message .= "\nBackup failed.";
    $status = 'fail';
}

header("Location: backup_list.php?status=$status&message=" . urlencode($message));
exit;
?>
```
