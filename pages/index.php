<?php
require_once __DIR__ . '/../config/koneksi.php';

if (session_status() === PHP_SESSION_NONE) { session_start(); } 
if (!isset($_SESSION['username'])) { header('Location: login.php'); exit; }


$total_barang = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM barang"))['total'];
$total_stok = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(stok) as total FROM stok_cabang"))['total'];
$total_transaksi = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM transaksi"))['total'];


$barang = mysqli_query($conn, "SELECT b.kode_barang, b.nama_barang, COALESCE(SUM(sc.stok), 0) AS stok FROM barang b LEFT JOIN stok_cabang sc ON b.id = sc.barang_id GROUP BY b.id");


$chart_query = mysqli_query($conn, "SELECT b.nama_barang, COALESCE(SUM(sc.stok), 0) AS stok FROM barang b LEFT JOIN stok_cabang sc ON b.id = sc.barang_id GROUP BY b.id LIMIT 5");
$chart_labels = []; $chart_data = [];
while($row = mysqli_fetch_assoc($chart_query)) { $chart_labels[] = $row['nama_barang']; $chart_data[] = $row['stok']; }
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Dashboard - SIGUDANG</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style> body { font-family: 'Poppins', sans-serif; background-color: #f4f7f6; } .sidebar { background: #111827; min-height: 100vh; color: #9ca3af; } .sidebar .nav-link { color: #9ca3af; padding: 12px 20px; border-radius: 8px; font-weight: 500; margin-bottom: 5px; } .sidebar .nav-link:hover { color: #fff; background: rgba(255,255,255,0.05); } .sidebar .nav-link.active { background: #1f2937; color: #fff; } .topbar { background: #fff; box-shadow: 0 1px 3px 0 rgba(0,0,0,0.1); } </style>
</head>
<body class="d-flex">
<div class="sidebar d-none d-md-block" style="width: 260px; flex-shrink:0;">
    <div class="p-4 border-bottom border-secondary mb-3"><h5 class="text-white fw-bold m-0"><i class="fa-solid fa-boxes-stacked text-primary me-2"></i> SIGUDANG</h5></div>
    <nav class="nav flex-column px-3">
        <a class="nav-link active" href="index.php"><i class="fa-solid fa-gauge-high me-3"></i> Dashboard</a>
        <?php if ($_SESSION['role'] == 'Admin') : ?>
        <a class="nav-link" href="transaction.php"><i class="fa-solid fa-pen-to-square me-3"></i> Transaction</a>
        <a class="nav-link" href="deadlock.php"><i class="fa-solid fa-arrows-spin me-3"></i> Deadlock</a>
        <?php endif; ?>
        <a class="nav-link" href="log.php"><i class="fa-solid fa-chart-bar me-3"></i> Log & Analytics</a>
        <?php if ($_SESSION['role'] == 'Admin') : ?>
        <a class="nav-link" href="backup.php"><i class="fa-solid fa-database me-3"></i> Backup Database</a>
        <?php endif; ?>
        <a class="nav-link text-danger mt-5" href="logout.php"><i class="fa-solid fa-sign-out-alt me-3"></i> Logout</a>
    </nav>
</div>
<div class="flex-grow-1" style="height: 100vh; overflow-y: auto;">
    <div class="topbar p-3 d-flex justify-content-between align-items-center mb-4 px-4 sticky-top">
        <h5 class="mb-0 fw-bold">Dashboard Monitoring Multi-Cabang</h5>
        <div><span class="me-3 fw-bold"><?= $_SESSION['username']; ?></span> <a href="logout.php" class="btn btn-sm btn-outline-danger"><i class="fa-solid fa-sign-out-alt"></i></a></div>
    </div>
    <div class="container-fluid px-4 pb-4">
        <div class="row mb-4">
            <div class="col-md-4"><div class="card border-0 shadow-sm"><div class="card-body">Tipe Barang Master<h3 class="fw-bold m-0 text-primary"><?= $total_barang; ?></h3></div></div></div>
            <div class="col-md-4"><div class="card border-0 shadow-sm"><div class="card-body">Total Stok Global<h3 class="fw-bold m-0 text-success"><?= $total_stok ?: 0; ?></h3></div></div></div>
            <div class="col-md-4"><div class="card border-0 shadow-sm"><div class="card-body">Log Transaksi Keseluruhan<h3 class="fw-bold m-0 text-info"><?= $total_transaksi; ?></h3></div></div></div>
        </div>
        <div class="card border-0 shadow-sm mb-4"><div class="card-body"><canvas id="stokChart" height="70"></canvas></div></div>
        <div class="card border-0 shadow-sm">
            <div class="card-body p-0">
                <table class="table table-hover mb-0 align-middle">
                    <thead class="table-light"><tr><th class="ps-4">Kode</th><th>Nama Barang</th><th class="text-center">Total Stok Global</th><th class="text-center">Status</th></tr></thead>
                    <tbody>
                        <?php while($row = mysqli_fetch_assoc($barang)) : ?>
                        <tr>
                            <td class="ps-4"><span class="badge bg-light text-dark border"><?= $row['kode_barang']; ?></span></td>
                            <td class="fw-medium"><?= $row['nama_barang']; ?></td>
                            <td class="text-center fw-bold fs-6"><?= $row['stok']; ?></td>
                            <td class="text-center">
                                <?= $row['stok'] < 30 ? '<span class="badge bg-danger">Kritis</span>' : '<span class="badge bg-success">Aman</span>'; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<script>
    new Chart(document.getElementById('stokChart').getContext('2d'), {
        type: 'bar', data: { labels: <?= json_encode($chart_labels); ?>, datasets: [{ label: 'Stok Global', data: <?= json_encode($chart_data); ?>, backgroundColor: 'rgba(59, 130, 246, 0.5)' }] }
    });
</script>
</body>
</html>
