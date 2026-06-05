<?php
require_once __DIR__ . '/../config/koneksi.php';

if (session_status() === PHP_SESSION_NONE) { session_start(); } 
if (!isset($_SESSION['username'])) { header('Location: login.php'); exit; }


$query_log = "
    SELECT 
        t.tanggal, 
        b.kode_barang, 
        b.nama_barang, 
        t.jenis_transaksi, 
        t.jumlah, 
        c1.nama_cabang as asal, 
        c2.nama_cabang as tujuan
    FROM transaksi t
    JOIN barang b ON t.barang_id = b.id
    LEFT JOIN cabang c1 ON t.cabang_asal_id = c1.id
    LEFT JOIN cabang c2 ON t.cabang_tujuan_id = c2.id
    ORDER BY t.tanggal DESC
";
$transaksi = mysqli_query($conn, $query_log);


$query_union = "SELECT * FROM v_komparasi_union ORDER BY kode_barang ASC";
$result_union = mysqli_query($conn, $query_union);
$union_data = [];
if($result_union) {
    while($row = mysqli_fetch_assoc($result_union)) { $union_data[] = $row; }
}

$query_intersect = "SELECT * FROM v_komparasi_intersect ORDER BY kode_barang ASC";
$result_intersect = mysqli_query($conn, $query_intersect);

$query_except = "SELECT * FROM v_komparasi_except ORDER BY kode_barang ASC";
$result_except = mysqli_query($conn, $query_except);



$frag_horizontal = mysqli_query($conn, "SELECT * FROM v_frag_transaksi_masuk ORDER BY tanggal DESC LIMIT 100");
$frag_vertical = mysqli_query($conn, "SELECT * FROM v_frag_transaksi_detail ORDER BY id DESC LIMIT 100");
$frag_campuran = mysqli_query($conn, "SELECT * FROM v_frag_transaksi_masuk_ringkas ORDER BY tanggal DESC LIMIT 100");

if (!function_exists('formatBytes')) {
    function formatBytes($bytes, $precision = 2) {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));
        return round($bytes, $precision) . ' ' . $units[$pow];
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Log & Analytics - SIGUDANG Premium</title>
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
            height: 100%;
        }
        .nav-pills .nav-link {
            border-radius: 12px;
            font-weight: 600;
            padding: 12px 24px;
            color: #475569;
            transition: all 0.3s ease;
            margin-right: 8px;
            background: rgba(255,255,255,0.5);
            border: 1px solid rgba(255,255,255,0.8);
        }
        
        .nav-pills .nav-link:hover {
            background: rgba(255,255,255,0.9);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        }
        .nav-pills .nav-link.active {
            background: linear-gradient(135deg, var(--primary), #3b82f6);
            color: white;
            box-shadow: 0 8px 16px -4px rgba(79, 70, 229, 0.3);
            border-color: transparent;
        }
        .nav-tabs { border-bottom: 2px solid #e2e8f0; }
        .nav-tabs .nav-link {
            border: none;
            color: #64748b;
            font-weight: 600;
            padding: 12px 20px;
            position: relative;
            background: transparent;
        }
        .nav-tabs .nav-link::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            width: 100%;
            height: 3px;
            background: transparent;
            border-radius: 3px 3px 0 0;
            transition: all 0.3s ease;
        }
        .nav-tabs .nav-link:hover { color: #334155; }
        .nav-tabs .nav-link.active { color: var(--primary); background: transparent;}
        .nav-tabs .nav-link.active::after { background: var(--primary); }
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
            font-weight: 500;
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
        .badge-soft-danger { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
        .badge-soft-primary { background: #e0e7ff; color: #3730a3; border: 1px solid #c7d2fe; }
        .badge-soft-dark { background: #f1f5f9; color: #334155; border: 1px solid #e2e8f0; }
    </style>
</head>
<body>
<div class="sidebar-container d-none d-md-flex">
    <div class="sidebar-logo">
        <h4 class="text-white fw-bold m-0 d-flex align-items-center" style="letter-spacing:-0.5px;">
            <div style="background: linear-gradient(135deg, #3b82f6, #8b5cf6); padding: 8px; border-radius: 12px; margin-right: 12px;">
                <i class="fa-solid fa-boxes-stacked text-white" style="font-size: 1.2rem;"></i>
            </div>
            SI GUDANG
        </h4>
    </div>
    <div class="flex-grow-1 overflow-auto mt-2 pb-4">
        <a class="nav-link" href="index.php"><i class="fa-solid fa-gauge-high"></i> Dashboard</a>
        <?php if ($_SESSION['role'] == 'Admin') : ?>
        <a class="nav-link" href="transaction.php"><i class="fa-solid fa-pen-to-square"></i> Transaction</a>
        <a class="nav-link" href="deadlock.php"><i class="fa-solid fa-arrows-spin"></i> Deadlock</a>
        <?php endif; ?>
        <a class="nav-link active" href="log.php"><i class="fa-solid fa-chart-bar"></i> Log & Analytics</a>
        <?php if ($_SESSION['role'] == 'Admin') : ?>
        <a class="nav-link" href="backup.php"><i class="fa-solid fa-database"></i> Backup DB</a>
        <?php endif; ?>
    </div>
</div>
<div class="main-wrapper">
    <div class="topbar-floating">
        <h5 class="topbar-title">Log & Analytics</h5>
        <div class="user-profile">
            <span><?= $_SESSION['username']; ?></span> 
            <a href="logout.php" class="btn-logout" title="Logout"><i class="fa-solid fa-power-off"></i></a>
        </div>
    </div>
    <ul class="nav nav-pills mb-4" id="logTab" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="transaksi-tab" data-bs-toggle="pill" data-bs-target="#transaksi" type="button" role="tab" aria-controls="transaksi" aria-selected="true"><i class="fa-solid fa-clock-rotate-left me-2"></i> Transaction Log</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="komparasi-tab" data-bs-toggle="pill" data-bs-target="#komparasi" type="button" role="tab" aria-controls="komparasi" aria-selected="false"><i class="fa-solid fa-layer-group me-2"></i> Analisis Persebaran Stok</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="frag-tab" data-bs-toggle="pill" data-bs-target="#frag" type="button" role="tab" aria-controls="frag" aria-selected="false"><i class="fa-solid fa-table-columns me-2"></i> Segmentasi Transaksi</button>
        </li>
    </ul>
    <div class="tab-content" id="logTabContent">
        <div class="tab-pane fade show active" id="transaksi" role="tabpanel" aria-labelledby="transaksi-tab">
            <div class="card-premium">
                <div class="table-responsive">
                    <table class="table-custom">
                        <thead>
                            <tr>
                                <th class="ps-4">Tanggal</th>
                                <th>Barang</th>
                                <th>Jenis</th>
                                <th>Rute (Asal &rarr; Tujuan)</th>
                                <th class="text-center">Kuantitas</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($log = mysqli_fetch_assoc($transaksi)) : ?>
                            <tr>
                                <td class="ps-4 small fw-semibold" style="color:#94a3b8;"><?= date('d M Y, H:i', strtotime($log['tanggal'])); ?></td>
                                <td class="fw-bold">
                                    <span class="badge-soft badge-soft-dark me-2"><?= $log['kode_barang']; ?></span> 
                                    <?= $log['nama_barang']; ?>
                                </td>
                                <td>
                                    <?php if($log['jenis_transaksi'] == 'masuk') echo '<span class="badge-soft badge-soft-success">Masuk</span>';
                                          elseif($log['jenis_transaksi'] == 'keluar') echo '<span class="badge-soft badge-soft-danger">Keluar</span>';
                                          else echo '<span class="badge-soft badge-soft-primary">Mutasi</span>'; ?>
                                </td>
                                <td class="small fw-semibold text-secondary">
                                    <?php if($log['jenis_transaksi'] == 'masuk') echo "Ke: <strong class='text-dark'>{$log['tujuan']}</strong>";
                                          elseif($log['jenis_transaksi'] == 'keluar') echo "Dari: <strong class='text-dark'>{$log['asal']}</strong>";
                                          else echo "<strong class='text-dark'>{$log['asal']}</strong> &rarr; <strong class='text-dark'>{$log['tujuan']}</strong>"; ?>
                                </td>
                                <td class="text-center fw-bolder fs-5 <?= $log['jenis_transaksi'] == 'masuk' ? 'text-success' : ($log['jenis_transaksi'] == 'keluar' ? 'text-danger' : 'text-primary'); ?>">
                                    <?= $log['jenis_transaksi'] == 'masuk' ? '+' : ($log['jenis_transaksi'] == 'keluar' ? '-' : '↻'); ?><?= $log['jumlah']; ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                            <?php if(mysqli_num_rows($transaksi) == 0) echo '<tr><td colspan="5" class="text-center text-muted p-5">Belum ada transaksi.</td></tr>'; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="tab-pane fade" id="komparasi" role="tabpanel" aria-labelledby="komparasi-tab">
            <ul class="nav nav-tabs fw-bold mb-4" id="setOpsTab" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="union-tab" data-bs-toggle="tab" data-bs-target="#union" type="button" role="tab" aria-controls="union" aria-selected="true"><i class="fa-solid fa-layer-group me-2 text-primary"></i> Semua Ketersediaan (Pusat & Cabang)</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="intersect-tab" data-bs-toggle="tab" data-bs-target="#intersect" type="button" role="tab" aria-controls="intersect" aria-selected="false"><i class="fa-solid fa-check-double me-2 text-success"></i> Stok Terdistribusi</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="except-tab" data-bs-toggle="tab" data-bs-target="#except" type="button" role="tab" aria-controls="except" aria-selected="false"><i class="fa-solid fa-not-equal me-2 text-danger"></i> Stok Eksklusif Pusat</button>
                </li>
            </ul>
            <div class="tab-content" id="setOpsTabContent">
                <div class="tab-pane fade show active" id="union" role="tabpanel" aria-labelledby="union-tab">
                    <div class="card-premium">
                        <div class="p-4 border-bottom">
                            <h6 class="fw-bold mb-0" style="color:var(--primary);">Semua Ketersediaan Stok di Seluruh Gudang</h6>
                        </div>
                        <div class="table-responsive">
                            <table class="table-custom">
                                <thead><tr><th class="ps-4">Kode Barang</th><th>Nama Barang</th><th>Sumber Lokasi</th></tr></thead>
                                <tbody>
                                    <?php foreach($union_data as $row) : ?>
                                    <tr>
                                        <td class="ps-4 fw-bold"><span class="badge-soft badge-soft-dark"><?= $row['kode_barang']; ?></span></td>
                                        <td class="fw-bold"><?= $row['nama_barang']; ?></td>
                                        <td><span class="badge-soft <?= $row['lokasi'] == 'Gudang Pusat' ? 'badge-soft-primary' : 'badge-soft-dark'; ?>"><?= $row['lokasi']; ?></span></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="tab-pane fade" id="intersect" role="tabpanel" aria-labelledby="intersect-tab">
                    <div class="card-premium">
                        <div class="p-4 border-bottom">
                            <h6 class="fw-bold mb-0 text-success">Barang yang Tersedia di Seluruh Gudang Pusat & Cabang</h6>
                        </div>
                        <div class="table-responsive">
                            <table class="table-custom">
                                <thead><tr><th class="ps-4">Kode Barang</th><th>Nama Barang</th></tr></thead>
                                <tbody>
                                    <?php while($row = mysqli_fetch_assoc($result_intersect)) : ?>
                                    <tr>
                                        <td class="ps-4 fw-bold"><span class="badge-soft badge-soft-dark"><?= $row['kode_barang']; ?></span></td>
                                        <td class="fw-bold"><?= $row['nama_barang']; ?></td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="tab-pane fade" id="except" role="tabpanel" aria-labelledby="except-tab">
                    <div class="card-premium">
                        <div class="p-4 border-bottom">
                            <h6 class="fw-bold mb-0 text-danger">Barang Eksklusif Hanya Tersedia di Gudang Pusat</h6>
                        </div>
                        <div class="table-responsive">
                            <table class="table-custom">
                                <thead><tr><th class="ps-4">Kode Barang</th><th>Nama Barang</th></tr></thead>
                                <tbody>
                                    <?php while($row = mysqli_fetch_assoc($result_except)) : ?>
                                    <tr>
                                        <td class="ps-4 fw-bold"><span class="badge-soft badge-soft-dark"><?= $row['kode_barang']; ?></span></td>
                                        <td class="fw-bold"><?= $row['nama_barang']; ?></td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="tab-pane fade" id="frag" role="tabpanel" aria-labelledby="frag-tab">
            <ul class="nav nav-tabs fw-bold mb-4" id="fragSubTab" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="horizontal-tab" data-bs-toggle="tab" data-bs-target="#horizontal" type="button" role="tab" aria-controls="horizontal" aria-selected="true"><i class="fa-solid fa-arrows-left-right me-2 text-primary"></i> Filter : Transaksi Masuk</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="vertical-tab" data-bs-toggle="tab" data-bs-target="#vertical" type="button" role="tab" aria-controls="vertical" aria-selected="false"><i class="fa-solid fa-arrows-up-down me-2 text-success"></i> Ringkasan Kuantitas</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="campuran-tab" data-bs-toggle="tab" data-bs-target="#campuran" type="button" role="tab" aria-controls="campuran" aria-selected="false"><i class="fa-solid fa-maximize me-2 text-warning"></i> Rekap Spesifik</button>
                </li>
            </ul>
            <div class="tab-content" id="fragSubTabContent">
                <div class="tab-pane fade show active" id="horizontal" role="tabpanel" aria-labelledby="horizontal-tab">
                    <div class="card-premium">
                        <div class="p-4 border-bottom">
                            <h6 class="fw-bold mb-0 text-primary">Data Log Transaksi Masuk</h6>
                        </div>
                        <div class="table-responsive">
                            <table class="table-custom">
                                <thead><tr><th class="ps-4">ID</th><th>Barang ID</th><th>Jenis</th><th>Jumlah</th><th>Tujuan</th><th>Tanggal</th></tr></thead>
                                <tbody>
                                    <?php while($row = mysqli_fetch_assoc($frag_horizontal)) : ?>
                                    <tr><td class="ps-4 fw-bold">#<?= $row['id']; ?></td><td class="fw-bold"><?= $row['barang_id']; ?></td><td><span class="badge-soft badge-soft-success"><?= $row['jenis_transaksi']; ?></span></td><td class="fw-bold"><?= $row['jumlah']; ?></td><td><?= $row['cabang_tujuan_id']; ?></td><td class="small fw-semibold text-secondary"><?= $row['tanggal']; ?></td></tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="tab-pane fade" id="vertical" role="tabpanel" aria-labelledby="vertical-tab">
                    <div class="card-premium">
                        <div class="p-4 border-bottom">
                            <h6 class="fw-bold mb-0 text-success">Ringkasan Kuantitas Transaksi Keseluruhan</h6>
                        </div>
                        <div class="table-responsive">
                            <table class="table-custom">
                                <thead><tr><th class="ps-4">ID</th><th>Barang ID</th><th>Jumlah</th></tr></thead>
                                <tbody>
                                    <?php while($row = mysqli_fetch_assoc($frag_vertical)) : ?>
                                    <tr><td class="ps-4 fw-bold">#<?= $row['id']; ?></td><td class="fw-bold"><?= $row['barang_id']; ?></td><td class="fw-bold fs-5"><?= $row['jumlah']; ?></td></tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="tab-pane fade" id="campuran" role="tabpanel" aria-labelledby="campuran-tab">
                    <div class="card-premium">
                        <div class="p-4 border-bottom">
                            <h6 class="fw-bold mb-0 text-warning">Laporan Rekap Spesifik Transaksi Masuk</h6>
                        </div>
                        <div class="table-responsive">
                            <table class="table-custom">
                                <thead><tr><th class="ps-4">ID</th><th>Barang ID</th><th>Jumlah</th><th>Tanggal</th></tr></thead>
                                <tbody>
                                    <?php while($row = mysqli_fetch_assoc($frag_campuran)) : ?>
                                    <tr><td class="ps-4 fw-bold">#<?= $row['id']; ?></td><td class="fw-bold"><?= $row['barang_id']; ?></td><td class="fw-bold text-success fs-5">+<?= $row['jumlah']; ?></td><td class="small fw-semibold text-secondary"><?= $row['tanggal']; ?></td></tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>