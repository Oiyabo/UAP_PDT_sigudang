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
        $pesan = "✓ Backup berhasil dibuat! File: " . basename($nama_file);
        $tipe_pesan = "success";
    } else {
        $pesan = "✗ Backup gagal! Pastikan mysqldump terinstall di sistem Anda.";
        $tipe_pesan = "danger";
    }
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
    <title>Backup Database - SIGUDANG</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { 
            font-family: 'Poppins', sans-serif; 
            background-color: #f4f7f6; 
            display: flex; 
            margin: 0; 
            min-height: 100vh; 
        }

        .sidebar { 
            background: #111827; 
            color: #9ca3af; 
            width: 260px; 
            min-width: 260px;
            flex: 0 0 260px;
            height: 100vh; 
            position: sticky; 
            top: 0; 
            z-index: 1000;
        }

        .main-content {
            flex: 1; 
            min-width: 0;
            overflow-y: auto;
            height: 100vh;
        }

        .nav-link { color: #9ca3af; padding: 12px 20px; border-radius: 8px; font-weight: 500; transition: 0.2s; } 
        .nav-link.active { background: #1f2937; color: #fff; } 
        .topbar { background: #fff; box-shadow: 0 1px 3px 0 rgba(0,0,0,0.1); }
        .backup-card { transition: transform 0.2s; }
        .backup-card:hover { transform: translateY(-2px); }
    </style>
</head>
<body class="d-flex">
<div class="sidebar d-none d-md-block" style="width: 260px;">
    <div class="p-4 border-bottom border-secondary mb-3"><h5 class="text-white fw-bold m-0"><i class="fa-solid fa-boxes-stacked text-primary me-2"></i> SIGUDANG</h5></div>
    <nav class="nav flex-column px-3">
        <a class="nav-link mb-2" href="index.php"><i class="fa-solid fa-gauge-high me-3"></i> Dashboard</a>
        <?php if ($_SESSION['role'] == 'Admin') : ?>
            <a class="nav-link mb-2" href="transaction.php"><i class="fa-solid fa-pen-to-square me-3"></i> Transaction</a>
            <a class="nav-link mb-2" href="deadlock.php"><i class="fa-solid fa-arrows-spin me-3"></i> Deadlock</a>
        <?php endif; ?>
        <a class="nav-link mb-2" href="log.php"><i class="fa-solid fa-chart-bar me-3"></i> Log & Analytics</a>
        <?php if ($_SESSION['role'] == 'Admin') : ?>
            <a class="nav-link active mb-2" href="backup.php"><i class="fa-solid fa-database me-3"></i> Backup Database</a>
        <?php endif; ?>
        <a class="nav-link text-danger mt-5" href="logout.php"><i class="fa-solid fa-sign-out-alt me-3"></i> Logout</a>
    </nav>
</div>
<div class="flex-grow-1">
    <div class="topbar p-3 d-flex justify-content-between align-items-center mb-4 px-4">
        <h5 class="mb-0 fw-bold">Manajemen Backup Database</h5>
        <div><span class="me-3 fw-bold"><?= $_SESSION['username']; ?></span> <a href="logout.php" class="btn btn-sm btn-outline-danger"><i class="fa-solid fa-sign-out-alt"></i></a></div>
    </div>
    <div class="container-fluid px-4 pb-4">
        <?php if ($pesan): ?>
            <div class="alert alert-<?= $tipe_pesan; ?> alert-dismissible fade show border-0 shadow-sm" role="alert">
                <i class="fa-solid fa-<?= $tipe_pesan == 'success' ? 'circle-check' : 'circle-exclamation'; ?> me-2"></i>
                <?= $pesan; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="card border-0 shadow-sm mb-4" id="autoBackupCard">
            <div class="card-body p-4">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h5 class="fw-bold mb-2"><i class="fa-solid fa-clock text-warning me-2"></i> Backup Otomatis Berkala</h5>
                        <p class="text-muted small mb-3">Aktifkan fitur ini untuk melakukan backup database secara otomatis setiap 3 menit.</p>
                        
                        <div id="statusAutoBackup" class="mb-0">
                            <?php if ($autoBackupStatus['auto_backup_enabled']): ?>
                                <div class="alert alert-info mb-0 py-2">
                                    <i class="fa-solid fa-check-circle me-2"></i>
                                    <strong>Status:</strong> Backup otomatis sedang <span class="badge bg-success">AKTIF</span>
                                    <br>
                                    <small class="text-muted d-block mt-1">
                                        Dimulai: <?= date('d M Y H:i:s', strtotime($autoBackupStatus['started_at'])); ?>
                                        <?php if ($autoBackupStatus['last_backup']): ?>
                                            <br>Backup terakhir: <?= $autoBackupStatus['last_backup']['created_at'] ?? 'N/A'; ?>
                                        <?php endif; ?>
                                    </small>
                                </div>
                            <?php else: ?>
                                <div class="alert alert-secondary mb-0 py-2">
                                    <i class="fa-solid fa-pause-circle me-2"></i>
                                    <strong>Status:</strong> Backup otomatis <span class="badge bg-secondary">NONAKTIF</span>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="col-md-4 text-md-end mt-3 mt-md-0">
                        <div id="autoBackupButtons">
                            <?php if ($autoBackupStatus['auto_backup_enabled']): ?>
                                <button type="button" class="btn btn-danger fw-bold px-4" id="stopAutoBackupBtn" onclick="stopAutoBackup()">
                                    <i class="fa-solid fa-stop me-2"></i> Stop Backup Otomatis
                                </button>
                            <?php else: ?>
                                <button type="button" class="btn btn-warning fw-bold px-4" id="startAutoBackupBtn" onclick="startAutoBackup()">
                                    <i class="fa-solid fa-play me-2"></i> Mulai Backup Otomatis
                                </button>
                            <?php endif; ?>
                        </div>
                        <small class="d-block text-muted mt-2">
                            <i class="fa-solid fa-info-circle me-1"></i> Task Scheduler akan menjalankan backup
                        </small>
                    </div>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body p-4">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h5 class="fw-bold mb-2"><i class="fa-solid fa-plus-circle text-success me-2"></i> Buat Backup Baru</h5>
                        <p class="text-muted small mb-0">Klik tombol di bawah untuk membuat backup database. File akan disimpan dan dapat diunduh.</p>
                    </div>
                    <div class="col-md-4 text-md-end mt-3 mt-md-0">
                        <form method="POST" class="d-inline">
                            <button type="submit" name="buat_backup" class="btn btn-success fw-bold px-4">
                                <i class="fa-solid fa-database me-2"></i> Buat Backup Sekarang
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-header bg-light border-bottom py-3">
                <h5 class="mb-0 fw-bold"><i class="fa-solid fa-list me-2"></i> Daftar File Backup (<?= count($backups); ?> file)</h5>
            </div>
            <div class="card-body p-0">
                <?php if (count($backups) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
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
                                        <i class="fa-solid fa-file-code text-info me-2"></i>
                                        <span class="fw-medium"><?= htmlspecialchars($backup['nama']); ?></span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-secondary"><?= $backup['ukuran_format']; ?></span>
                                    </td>
                                    <td class="text-center small text-muted">
                                        <?= date('d M Y, H:i', $backup['tanggal']); ?>
                                    </td>
                                    <td class="text-end pe-4">
                                        <a href="backup.php?download=<?= urlencode($backup['nama']); ?>" class="btn btn-sm btn-primary" title="Download">
                                            <i class="fa-solid fa-download"></i> Download
                                        </a>
                                        <a href="backup.php?delete=<?= urlencode($backup['nama']); ?>" class="btn btn-sm btn-danger" onclick="return confirm('Yakin hapus file ini?')" title="Delete">
                                            <i class="fa-solid fa-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center py-5">
                        <i class="fa-solid fa-inbox text-muted" style="font-size: 3rem;"></i>
                        <p class="text-muted mt-3">Belum ada file backup. Klik tombol "Buat Backup Sekarang" untuk membuat backup pertama.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="card border-0 shadow-sm mt-4 bg-light">
            <div class="card-body">
                <h6 class="fw-bold mb-2"><i class="fa-solid fa-circle-info text-info me-2"></i> Informasi</h6>
                <ul class="mb-0 small">
                    <li>Backup database disimpan dalam format <code>.sql</code></li>
                    <li>File backup dapat diunduh dan digunakan untuk restore database</li>
                    <li>Hanya Admin yang dapat mengakses halaman ini</li>
                    <li>Pastikan backup directory sudah di-create terlebih dahulu di folder root</li>
                </ul>
            </div>
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
                            <div class="alert alert-info mb-0 py-2">
                                <i class="fa-solid fa-check-circle me-2"></i>
                                <strong>Status:</strong> Backup otomatis sedang <span class="badge bg-success">AKTIF</span>
                                <br>
                                <small class="text-muted d-block mt-1">
                                    <?php if ($autoBackupStatus['last_backup']): ?>
                                        Backup terakhir: ${data.data.last_backup?.created_at || 'N/A'}
                                    <?php else: ?>
                                        Menunggu backup pertama...
                                    <?php endif; ?>
                                </small>
                            </div>
                        `;
                        document.getElementById('autoBackupButtons').innerHTML = `
                            <button type="button" class="btn btn-danger fw-bold px-4" onclick="stopAutoBackup()">
                                <i class="fa-solid fa-stop me-2"></i> Stop Backup Otomatis
                            </button>
                        `;
                    } else {
                        statusDiv.innerHTML = `
                            <div class="alert alert-secondary mb-0 py-2">
                                <i class="fa-solid fa-pause-circle me-2"></i>
                                <strong>Status:</strong> Backup otomatis <span class="badge bg-secondary">NONAKTIF</span>
                            </div>
                        `;
                        document.getElementById('autoBackupButtons').innerHTML = `
                            <button type="button" class="btn btn-warning fw-bold px-4" onclick="startAutoBackup()">
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

        fetch('../api/backup_control.php?action=start_auto_backup', {
            method: 'GET'
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                showAlert('success', 'Backup Otomatis Dimulai', 'Backup akan berjalan setiap 3 menit. Pastikan Task Scheduler sudah dikonfigurasi.');
                setTimeout(() => location.reload(), 2000);
            } else if (data.status === 'warning') {
                showAlert('warning', 'Peringatan', data.message);
                btn.disabled = false;
                btn.innerHTML = '<i class="fa-solid fa-play me-2"></i> Mulai Backup Otomatis';
            } else {
                showAlert('danger', 'Error', data.message);
                btn.disabled = false;
                btn.innerHTML = '<i class="fa-solid fa-play me-2"></i> Mulai Backup Otomatis';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('danger', 'Error', 'Terjadi kesalahan saat memulai backup otomatis');
            btn.disabled = false;
            btn.innerHTML = '<i class="fa-solid fa-play me-2"></i> Mulai Backup Otomatis';
        });
    }

    function stopAutoBackup() {
        if (confirm('Yakin ingin menghentikan backup otomatis?')) {
            const btn = event.target.closest('button');
            btn.disabled = true;
            btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-2"></i> Menghentikan...';

            fetch('../api/backup_control.php?action=stop_auto_backup', {
                method: 'GET'
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    showAlert('success', 'Backup Otomatis Dihentikan', data.message);
                    setTimeout(() => location.reload(), 2000);
                } else {
                    showAlert('danger', 'Error', data.message);
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fa-solid fa-stop me-2"></i> Stop Backup Otomatis';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showAlert('danger', 'Error', 'Terjadi kesalahan saat menghentikan backup otomatis');
                btn.disabled = false;
                btn.innerHTML = '<i class="fa-solid fa-stop me-2"></i> Stop Backup Otomatis';
            });
        }
    }

    function showAlert(type, title, message) {
        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${type} alert-dismissible fade show border-0 shadow-sm`;
        alertDiv.role = 'alert';
        alertDiv.innerHTML = `
            <i class="fa-solid fa-${type === 'success' ? 'circle-check' : (type === 'warning' ? 'triangle-exclamation' : 'circle-exclamation')} me-2"></i>
            <strong>${title}:</strong> ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        `;
        
        const container = document.querySelector('.container-fluid');
        container.insertBefore(alertDiv, container.firstChild);
    }
</script>
</body>
</html>