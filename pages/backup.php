<?php
include '../config/koneksi.php';
date_default_timezone_set('Asia/Jakarta');
if (session_status() === PHP_SESSION_NONE) { session_start(); } 
if (!isset($_SESSION['username'])) { header('Location: login.php'); exit; }

$backupDir = __DIR__ . "/../backup/";
if (!is_dir($backupDir)) { 
    mkdir($backupDir, 0777, true); 
}

$pesan = "";
$tipe_pesan = "";

if (isset($_POST['buat_backup'])) {
    $mysqldumpPath = "C:\\laragon\\bin\\mysql\\mysql-8.0.30-winx64\\bin\\mysqldump.exe";
    
    $host = "localhost";
    $user = "root";
    $pass = "";
    $db   = "db_gudang";
    
    $nama_file = $backupDir . "backup_" . date('Y-m-d_H-i-s') . ".sql";
    $command = "\"$mysqldumpPath\" -h $host -u $user $db > \"$nama_file\"";
    
    exec($command, $output, $return_var);
    
    if ($return_var === 0) {
        // Redirect ke GET request untuk menghindari POST resubmit saat refresh
        header('Location: backup.php?backup_success=' . urlencode(basename($nama_file)));
        exit;
    } else {
        $pesan = "✗ Backup gagal! Pastikan mysqldump terinstall di sistem Anda.";
        $tipe_pesan = "danger";
    }
}

if (isset($_GET['backup_success'])) {
    $pesan = "✓ Backup berhasil dibuat! File: " . htmlspecialchars($_GET['backup_success']);
    $tipe_pesan = "success";
}

if (isset($_GET['download'])) {
    $filename = basename($_GET['download']);
    $filepath = $backupDir . $filename;
    
    if (file_exists($filepath) && strpos($filename, '..') === false && preg_match('/^backup_\d{4}-\d{2}-\d{2}_\d{2}-\d{2}-\d{2}\.sql$/', $filename)) {
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . filesize($filepath));
        readfile($filepath);
        exit;
    } else {
        $pesan = "✗ File tidak ditemukan atau tidak valid!";
        $tipe_pesan = "danger";
    }
}

if (isset($_GET['delete'])) {
    $filename = basename($_GET['delete']);
    $filepath = $backupDir . $filename;
    
    if (file_exists($filepath) && strpos($filename, '..') === false && preg_match('/^backup_\d{4}-\d{2}-\d{2}_\d{2}-\d{2}-\d{2}\.sql$/', $filename)) {
        if (unlink($filepath)) {
            $pesan = "✓ Backup berhasil dihapus!";
            $tipe_pesan = "success";
        } else {
            $pesan = "✗ Gagal menghapus backup!";
            $tipe_pesan = "danger";
        }
    }
}

$backups = [];
if (is_dir($backupDir)) {
    $files = scandir($backupDir, SCANDIR_SORT_DESCENDING);
    foreach ($files as $file) {
        if (preg_match('/^backup_\d{4}-\d{2}-\d{2}_\d{2}-\d{2}-\d{2}\.sql$/', $file)) {
            $filepath = $backupDir . $file;
            $backups[] = [
                'nama' => $file,
                'ukuran' => filesize($filepath),
                'tanggal' => filemtime($filepath),
                'ukuran_format' => formatBytes(filesize($filepath))
            ];
        }
    }
}

$statusFile = __DIR__ . "/../backup_status.json";
$autoBackupStatus = [
    'auto_backup_enabled' => false,
    'last_backup' => null
];

if (file_exists($statusFile)) {
    $statusData = json_decode(file_get_contents($statusFile), true);
    $autoBackupStatus['auto_backup_enabled'] = $statusData['auto_backup_enabled'] ?? false;
    $autoBackupStatus['last_backup'] = $statusData['last_backup'] ?? null;
    $autoBackupStatus['started_at'] = $statusData['started_at'] ?? null;
    $autoBackupStatus['stopped_at'] = $statusData['stopped_at'] ?? null;
}

function formatBytes($bytes, $precision = 2) {
    $units = ['B', 'KB', 'MB', 'GB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= (1 << (10 * $pow));
    return round($bytes, $precision) . ' ' . $units[$pow];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Backup Database - SIGUDANG Premium</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #4f46e5;
            --primary-hover: #4338ca;
            --bg-color: #f3f4f6;
            --sidebar-bg: rgba(17, 24, 39, 0.95);
            --sidebar-width: 280px;
            --card-bg: rgba(255, 255, 255, 0.85);
        }
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: var(--bg-color);
            background-image: 
                radial-gradient(at 40% 20%, hsla(228,100%,74%,0.15) 0px, transparent 50%),
                radial-gradient(at 80% 0%, hsla(189,100%,56%,0.15) 0px, transparent 50%),
                radial-gradient(at 0% 50%, hsla(355,100%,93%,0.15) 0px, transparent 50%);
            background-attachment: fixed;
            margin: 0;
            min-height: 100vh;
            color: #1f2937;
        }
        .sidebar-container {
            position: fixed;
            top: 20px;
            bottom: 20px;
            left: 20px;
            width: var(--sidebar-width);
            background: var(--sidebar-bg);
            border-radius: 24px;
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            z-index: 1000;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }
        .sidebar-logo {
            padding: 30px 24px 20px;
            background: linear-gradient(to bottom, rgba(0,0,0,0.2), transparent);
        }
        .nav-link {
            color: #9ca3af;
            padding: 14px 24px;
            margin: 8px 16px;
            border-radius: 14px;
            font-weight: 600;
            display: flex;
            align-items: center;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
        }
        .nav-link i {
            width: 24px;
            font-size: 1.1rem;
            margin-right: 12px;
            transition: transform 0.3s ease;
        }
        .nav-link:hover {
            color: #fff;
            background: rgba(255, 255, 255, 0.06);
            transform: translateX(4px);
        }
        
        .nav-link:hover i { transform: scale(1.1); color: #60a5fa; }
        .nav-link.active {
            color: #fff;
            background: linear-gradient(135deg, var(--primary), #3b82f6);
            box-shadow: 0 10px 20px -5px rgba(79, 70, 229, 0.4);
        }
        .nav-link.active::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            height: 100%;
            width: 4px;
            background: #fff;
            border-radius: 4px 0 0 4px;
        }
        .main-wrapper {
            margin-left: calc(var(--sidebar-width) + 40px);
            padding: 20px 20px 20px 0;
            min-height: 100vh;
        }
        .topbar-floating {
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.5);
            border-radius: 20px;
            padding: 16px 28px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px -10px rgba(0, 0, 0, 0.05);
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 20px;
            z-index: 999;
        }
        .topbar-title {
            font-size: 1.25rem;
            font-weight: 800;
            background: linear-gradient(135deg, #1e293b, #334155);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin: 0;
            letter-spacing: -0.5px;
        }
        .user-profile {
            display: flex;
            align-items: center;
            gap: 15px;
            background: #fff;
            padding: 6px 6px 6px 20px;
            border-radius: 50px;
            box-shadow: 0 4px 10px -1px rgba(0,0,0,0.05);
            border: 1px solid #f3f4f6;
        }
        .user-profile span {
            font-weight: 700;
            color: #374151;
            font-size: 0.95rem;
        }
        .btn-logout {
            background: #fee2e2;
            color: #ef4444;
            border: none;
            width: 38px;
            height: 38px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
            text-decoration: none;
        }
        .btn-logout:hover {
            background: #ef4444;
            color: #fff;
            transform: rotate(10deg) scale(1.05);
        }
        .card-premium {
            background: var(--card-bg);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255,255,255,0.6);
            border-radius: 24px;
            box-shadow: 0 20px 40px -15px rgba(0,0,0,0.05);
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            overflow: hidden;
        }
        .table-custom { margin: 0; border-collapse: separate; border-spacing: 0; width: 100%; }
        .table-custom thead th {
            border-bottom: 1px solid #e5e7eb;
            font-weight: 700;
            color: #6b7280;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.05em;
            padding: 16px 24px;
            background: rgba(249, 250, 251, 0.5);
        }
        .table-custom tbody td {
            padding: 16px 24px;
            border-bottom: 1px solid #f3f4f6;
            vertical-align: middle;
            font-weight: 600;
            color: #374151;
        }
        .table-custom tbody tr:hover td { background: rgba(79, 70, 229, 0.03); }
        .table-custom tbody tr:last-child td { border-bottom: none; }
        .badge-soft {
            padding: 8px 12px;
            border-radius: 8px;
            font-weight: 700;
            font-size: 0.75rem;
            letter-spacing: 0.5px;
            display: inline-block;
        }
        .badge-soft-success { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
        .badge-soft-secondary { background: #f1f5f9; color: #475569; border: 1px solid #e2e8f0; }
        .btn-custom {
            border-radius: 12px;
            padding: 12px 24px;
            font-weight: 700;
            letter-spacing: 0.5px;
            transition: all 0.3s ease;
            border: none;
        }
        
        .btn-success-custom {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
            box-shadow: 0 8px 16px -4px rgba(16, 185, 129, 0.3);
        }
        
        .btn-success-custom:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 20px -4px rgba(16, 185, 129, 0.4);
            color: white;
        }
        .btn-danger-custom {
            background: linear-gradient(135deg, #ef4444, #dc2626);
            color: white;
            box-shadow: 0 8px 16px -4px rgba(239, 68, 68, 0.3);
        }
        
        .btn-danger-custom:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 20px -4px rgba(239, 68, 68, 0.4);
            color: white;
        }
        .btn-warning-custom {
            background: linear-gradient(135deg, #f59e0b, #d97706);
            color: white;
            box-shadow: 0 8px 16px -4px rgba(245, 158, 11, 0.3);
        }
        
        .btn-warning-custom:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 20px -4px rgba(245, 158, 11, 0.4);
            color: white;
        }
        .btn-action {
            border-radius: 10px;
            padding: 8px 14px;
            font-size: 0.85rem;
            font-weight: 600;
            border: none;
            transition: all 0.2s;
        }
        .btn-action-primary { background: #eff6ff; color: #2563eb; }
        .btn-action-primary:hover { background: #2563eb; color: white; }
        
        .btn-action-danger { background: #fef2f2; color: #dc2626; }
        .btn-action-danger:hover { background: #dc2626; color: white; }
        .alert-custom {
            border-radius: 16px;
            border: none;
            padding: 16px 20px;
            font-weight: 600;
        }
        .alert-custom-success { background: #dcfce7; color: #166534; border-left: 4px solid #22c55e; }
        .alert-custom-danger { background: #fef2f2; color: #991b1b; border-left: 4px solid #ef4444; }
    </style>
</head>
<body>
<div class="sidebar-container d-none d-md-flex">
    <div class="sidebar-logo">
        <h4 class="text-white fw-bold m-0 d-flex align-items-center" style="letter-spacing:-0.5px;">
            <div style="background: linear-gradient(135deg, #3b82f6, #8b5cf6); padding: 8px; border-radius: 12px; margin-right: 12px;">
                <i class="fa-solid fa-boxes-stacked text-white" style="font-size: 1.2rem;"></i>
            </div>
            SIGUDANG
        </h4>
    </div>
    <div class="flex-grow-1 overflow-auto mt-2 pb-4">
        <a class="nav-link" href="index.php"><i class="fa-solid fa-gauge-high"></i> Dashboard</a>
        <?php if ($_SESSION['role'] == 'Admin') : ?>
        <a class="nav-link" href="transaction.php"><i class="fa-solid fa-pen-to-square"></i> Transaction</a>
        <a class="nav-link" href="deadlock.php"><i class="fa-solid fa-arrows-spin"></i> Deadlock</a>
        <?php endif; ?>
        <a class="nav-link" href="log.php"><i class="fa-solid fa-chart-bar"></i> Log & Analytics</a>
        <?php if ($_SESSION['role'] == 'Admin') : ?>
        <a class="nav-link active" href="backup.php"><i class="fa-solid fa-database"></i> Backup DB</a>
        <?php endif; ?>
    </div>
</div>
<div class="main-wrapper">
    <div class="topbar-floating">
        <h5 class="topbar-title">Manajemen Backup Database</h5>
        <div class="user-profile">
            <span><?= $_SESSION['username']; ?></span> 
            <a href="logout.php" class="btn-logout" title="Logout"><i class="fa-solid fa-power-off"></i></a>
        </div>
    </div>
    <?php if ($pesan): ?>
        <div class="alert alert-custom alert-custom-<?= $tipe_pesan; ?> alert-dismissible fade show shadow-sm mb-4" role="alert">
            <i class="fa-solid fa-<?= $tipe_pesan == 'success' ? 'circle-check' : 'circle-exclamation'; ?> me-2 fs-5 align-middle"></i>
            <?= $pesan; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    <div class="row g-4 mb-4">
        <div class="col-lg-7">
            <div class="card-premium h-100" id="autoBackupCard">
                <div class="p-4 p-md-5">
                    <div class="d-flex align-items-center justify-content-between mb-4">
                        <div class="d-flex align-items-center">
                            <div style="background: #fef3c7; color: #d97706; padding: 12px; border-radius: 14px; margin-right: 16px;">
                                <i class="fa-solid fa-clock fs-4"></i>
                            </div>
                            <div>
                                <h5 class="fw-bold mb-1" style="color: #1e293b;">Backup Otomatis</h5>
                                <p class="text-secondary mb-0 small">Jalankan secara berkala setiap 3 menit</p>
                            </div>
                        </div>
                    </div>
                    
                    <div id="statusAutoBackup" class="mb-4">
                        <?php if ($autoBackupStatus['auto_backup_enabled']): ?>
                            <div class="p-3" style="background: rgba(22, 101, 52, 0.05); border-radius: 16px; border: 1px dashed #22c55e;">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <i class="fa-solid fa-circle-check text-success me-2"></i>
                                        <strong class="text-success">Status: AKTIF</strong>
                                    </div>
                                    <div class="text-end small text-secondary">
                                        Dimulai: <?= date('d M, H:i', strtotime($autoBackupStatus['started_at'])); ?>
                                    </div>
                                </div>
                                <?php if ($autoBackupStatus['last_backup']): ?>
                                    <div class="mt-2 small text-secondary">
                                        <i class="fa-solid fa-clock-rotate-left me-1"></i> Terakhir: <?= $autoBackupStatus['last_backup']['created_at'] ?? 'N/A'; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php else: ?>
                            <div class="p-3" style="background: #f8fafc; border-radius: 16px; border: 1px dashed #cbd5e1;">
                                <i class="fa-solid fa-pause-circle text-secondary me-2"></i>
                                <strong class="text-secondary">Status: NONAKTIF</strong>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div id="autoBackupButtons">
                        <?php if ($autoBackupStatus['auto_backup_enabled']): ?>
                            <button type="button" class="btn-custom btn-danger-custom w-100" id="stopAutoBackupBtn" onclick="stopAutoBackup()">
                                <i class="fa-solid fa-stop me-2"></i> Stop Backup Otomatis
                            </button>
                        <?php else: ?>
                            <button type="button" class="btn-custom btn-warning-custom w-100" id="startAutoBackupBtn" onclick="startAutoBackup()">
                                <i class="fa-solid fa-play me-2"></i> Mulai Backup Otomatis
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-5">
            <div class="card-premium h-100">
                <div class="p-4 p-md-5 d-flex flex-column justify-content-center text-center h-100">
                    <div style="background: #dcfce7; color: #166534; padding: 16px; border-radius: 50%; margin: 0 auto 20px; width:60px; height:60px; display:flex; align-items:center; justify-content:center;">
                        <i class="fa-solid fa-database fs-3"></i>
                    </div>
                    <h5 class="fw-bold mb-2">Buat Backup Manual</h5>
                    <p class="text-secondary small mb-4">Simpan kondisi database saat ini secara instan untuk keamanan.</p>
                    <form method="POST">
                        <button type="submit" name="buat_backup" class="btn-custom btn-success-custom w-100">
                            <i class="fa-solid fa-download me-2"></i> Backup Sekarang
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <div class="card-premium">
        <div class="p-4 border-bottom d-flex justify-content-between align-items-center">
            <h6 class="fw-bold m-0" style="color:var(--primary);"><i class="fa-solid fa-list me-2"></i> Daftar File Backup (<?= count($backups); ?>)</h6>
        </div>
        <div class="table-responsive">
            <?php if (count($backups) > 0): ?>
                <table class="table-custom">
                    <thead>
                        <tr>
                            <th class="ps-4">Nama File</th>
                            <th class="text-center">Ukuran</th>
                            <th class="text-center">Tanggal</th>
                            <th class="text-end pe-4">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($backups as $backup): ?>
                        <tr>
                            <td class="ps-4">
                                <i class="fa-solid fa-file-code text-primary me-3 fs-5 align-middle"></i>
                                <span class="fw-bold"><?= htmlspecialchars($backup['nama']); ?></span>
                            </td>
                            <td class="text-center">
                                <span class="badge-soft badge-soft-secondary"><?= $backup['ukuran_format']; ?></span>
                            </td>
                            <td class="text-center small fw-semibold text-secondary">
                                <?= date('d M Y, H:i', $backup['tanggal']); ?>
                            </td>
                            <td class="text-end pe-4">
                                <a href="backup.php?download=<?= urlencode($backup['nama']); ?>" class="btn-action btn-action-primary me-2" title="Download">
                                    <i class="fa-solid fa-download"></i>
                                </a>
                                <a href="backup.php?delete=<?= urlencode($backup['nama']); ?>" class="btn-action btn-action-danger" onclick="return confirm('Yakin hapus file backup ini?')" title="Delete">
                                    <i class="fa-solid fa-trash"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="text-center py-5">
                    <div style="font-size: 4rem; color: #cbd5e1; margin-bottom: 1rem;"><i class="fa-solid fa-box-open"></i></div>
                    <h6 class="fw-bold text-secondary">Belum ada file backup</h6>
                    <p class="text-muted small">Mulai dengan menekan tombol "Backup Sekarang"</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    setInterval(function() {
        if (document.getElementById('autoBackupCard')) {
            fetch('../api/backup_control.php?action=get_status')
                .then(response => response.json())
                .then(data => {
                    const statusDiv = document.getElementById('statusAutoBackup');
                    if (data.data && data.data.auto_backup_enabled) {
                        statusDiv.innerHTML = `
                            <div class="p-3" style="background: rgba(22, 101, 52, 0.05); border-radius: 16px; border: 1px dashed #22c55e;">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <i class="fa-solid fa-circle-check text-success me-2"></i>
                                        <strong class="text-success">Status: AKTIF</strong>
                                    </div>
                                </div>
                                <div class="mt-2 small text-secondary">
                                    <i class="fa-solid fa-clock-rotate-left me-1"></i> Terakhir: ${data.data.last_backup?.created_at || 'Menunggu...'}
                                </div>
                            </div>
                        `;
                        document.getElementById('autoBackupButtons').innerHTML = `
                            <button type="button" class="btn-custom btn-danger-custom w-100" onclick="stopAutoBackup()">
                                <i class="fa-solid fa-stop me-2"></i> Stop Backup Otomatis
                            </button>
                        `;
                    } else {
                        statusDiv.innerHTML = `
                            <div class="p-3" style="background: #f8fafc; border-radius: 16px; border: 1px dashed #cbd5e1;">
                                <i class="fa-solid fa-pause-circle text-secondary me-2"></i>
                                <strong class="text-secondary">Status: NONAKTIF</strong>
                            </div>
                        `;
                        document.getElementById('autoBackupButtons').innerHTML = `
                            <button type="button" class="btn-custom btn-warning-custom w-100" onclick="startAutoBackup()">
                                <i class="fa-solid fa-play me-2"></i> Mulai Backup Otomatis
                            </button>
                        `;
                    }
                })
                .catch(error => console.error('Error:', error));
        }
    }, 10000);
    function startAutoBackup() {
        const btn = event.target.closest('button');
        btn.disabled = true;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-2"></i> Memulai...';
        fetch('../api/backup_control.php?action=start_auto_backup', { method: 'GET' })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') { setTimeout(() => location.reload(), 500); } 
            else { btn.disabled = false; btn.innerHTML = '<i class="fa-solid fa-play me-2"></i> Mulai Backup Otomatis'; alert(data.message); }
        })
        .catch(error => { console.error('Error:', error); btn.disabled = false; });
    }
    function stopAutoBackup() {
        if (confirm('Yakin ingin menghentikan backup otomatis?')) {
            const btn = event.target.closest('button');
            btn.disabled = true;
            btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-2"></i> Menghentikan...';
            fetch('../api/backup_control.php?action=stop_auto_backup', { method: 'GET' })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') { setTimeout(() => location.reload(), 500); } 
                else { btn.disabled = false; btn.innerHTML = '<i class="fa-solid fa-stop me-2"></i> Stop Backup Otomatis'; alert(data.message); }
            })
            .catch(error => { console.error('Error:', error); btn.disabled = false; });
        }
    }
</script>
</body>
</html>