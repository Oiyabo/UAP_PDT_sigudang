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

$statusFile = __DIR__ . "\\backup_status.json";

$timestamp = date('Y-m-d_H-i-s');
$nama_file = $backupDir . "backup_" . $timestamp . ".sql";
$command = "\"$mysqldumpPath\" -h $host -u $user $db > \"$nama_file\"";

echo "[" . date('Y-m-d H:i:s') . "] Memulai backup database...\n";

exec($command, $output, $return_var);

if ($return_var === 0) {
    $filesize = filesize($nama_file);
    $filesizeFormatted = formatBytes($filesize);
    
    $status = json_decode(file_get_contents($statusFile) ?: '{}', true);
    $status['last_backup'] = [
        'timestamp' => $timestamp,
        'file' => basename($nama_file),
        'size' => $filesize,
        'size_formatted' => $filesizeFormatted,
        'status' => 'success',
        'created_at' => date('Y-m-d H:i:s')
    ];
    file_put_contents($statusFile, json_encode($status, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    
    echo "[" . date('Y-m-d H:i:s') . "] ✓ Backup Berhasil!\n";
    echo "  File: " . basename($nama_file) . "\n";
    echo "  Ukuran: " . $filesizeFormatted . "\n";
    echo "  Lokasi: " . $nama_file . "\n";
    
    cleanOldBackups($backupDir, 30);
    
    exit(0);
} else {
    $status = json_decode(file_get_contents($statusFile) ?: '{}', true);
    $status['last_backup'] = [
        'status' => 'failed',
        'timestamp' => $timestamp,
        'error' => 'Gagal menjalankan mysqldump',
        'created_at' => date('Y-m-d H:i:s')
    ];
    file_put_contents($statusFile, json_encode($status, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    
    echo "[" . date('Y-m-d H:i:s') . "] ✗ Backup Gagal!\n";
    echo "  Error: Pastikan mysqldump terinstall di sistem Anda.\n";
    
    exit(1);
}

function formatBytes($bytes, $precision = 2) {
    $units = ['B', 'KB', 'MB', 'GB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= (1 << (10 * $pow));
    return round($bytes, $precision) . ' ' . $units[$pow];
}

function cleanOldBackups($backupDir, $days = 30) {
    $files = scandir($backupDir, SCANDIR_SORT_ASCENDING);
    $now = time();
    $maxAge = $days * 24 * 60 * 60;
    
    $deletedCount = 0;
    foreach ($files as $file) {
        if (preg_match('/^backup_\d{4}-\d{2}-\d{2}_\d{2}-\d{2}-\d{2}\.sql$/', $file)) {
            $filepath = $backupDir . $file;
            $fileAge = $now - filemtime($filepath);
            
            if ($fileAge > $maxAge) {
                unlink($filepath);
                $deletedCount++;
            }
        }
    }
    
    if ($deletedCount > 0) {
        echo "[" . date('Y-m-d H:i:s') . "] Cleanup: Dihapus $deletedCount backup lama\n";
    }
}
?>
