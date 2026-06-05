<?php
header('Content-Type: application/json; charset=utf-8');
date_default_timezone_set('Asia/Jakarta');
require_once __DIR__ . '/../config/koneksi.php';

if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'Admin') {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Anda tidak memiliki akses']);
    exit;
}

$statusFile = __DIR__ . '/../backup_status.json';

if (!file_exists($statusFile)) {
    $initStatus = [
        'auto_backup_enabled' => false,
        'last_backup' => null,
        'backup_interval' => 180,
        'created_at' => date('Y-m-d H:i:s')
    ];
    file_put_contents($statusFile, json_encode($initStatus, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
}

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'get_status':
        getBackupStatus();
        break;
    
    case 'start_auto_backup':
        startAutoBackup();
        break;
    
    case 'stop_auto_backup':
        stopAutoBackup();
        break;
    
    case 'get_last_backup':
        getLastBackup();
        break;
    
    default:
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Action tidak dikenali']);
        break;
}

function getBackupStatus() {
    global $statusFile;
    
    $status = json_decode(file_get_contents($statusFile), true);
    
    echo json_encode([
        'status' => 'success',
        'data' => [
            'auto_backup_enabled' => $status['auto_backup_enabled'] ?? false,
            'last_backup' => $status['last_backup'] ?? null,
            'backup_interval' => $status['backup_interval'] ?? 180
        ]
    ]);
}

function startAutoBackup() {
    global $statusFile;
    
    $status = json_decode(file_get_contents($statusFile), true);
    
    if ($status['auto_backup_enabled'] ?? false) {
        echo json_encode([
            'status' => 'warning',
            'message' => 'Backup otomatis sudah berjalan'
        ]);
        return;
    }
    
    $status['auto_backup_enabled'] = true;
    $status['started_at'] = date('Y-m-d H:i:s');
    
    if (!file_put_contents($statusFile, json_encode($status, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES))) {
        http_response_code(500);
        echo json_encode([
            'status' => 'error',
            'message' => 'Gagal menulis status file. Pastikan folder backup writable.'
        ]);
        return;
    }
    
    echo json_encode([
        'status' => 'success',
        'message' => 'Backup otomatis telah diaktifkan. Task Scheduler akan menjalankan backup setiap 3 menit.',
        'data' => [
            'auto_backup_enabled' => true,
            'started_at' => date('Y-m-d H:i:s')
        ]
    ]);
}

function stopAutoBackup() {
    global $statusFile;
    
    $status = json_decode(file_get_contents($statusFile), true);
    
    $status['auto_backup_enabled'] = false;
    $status['stopped_at'] = date('Y-m-d H:i:s');
    file_put_contents($statusFile, json_encode($status, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    
    echo json_encode([
        'status' => 'success',
        'message' => 'Backup otomatis telah dihentikan',
        'data' => [
            'auto_backup_enabled' => false,
            'stopped_at' => date('Y-m-d H:i:s')
        ]
    ]);
}

function getLastBackup() {
    global $statusFile;
    
    $status = json_decode(file_get_contents($statusFile), true);
    
    $lastBackup = $status['last_backup'] ?? null;
    
    if ($lastBackup) {
        echo json_encode([
            'status' => 'success',
            'data' => $lastBackup
        ]);
    } else {
        echo json_encode([
            'status' => 'info',
            'message' => 'Belum ada backup',
            'data' => null
        ]);
    }
}
?>
