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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - SIGUDANG Premium</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
        .card-premium:hover {
            transform: translateY(-8px);
            box-shadow: 0 30px 60px -20px rgba(0,0,0,0.1);
            border-color: rgba(255,255,255,1);
        }
        .stat-card {
            padding: 24px;
            position: relative;
        }
        
        .stat-icon {
            position: absolute;
            right: -10px;
            bottom: -15px;
            font-size: 110px;
            opacity: 0.04;
            transform: rotate(-15deg);
            transition: all 0.5s ease;
        }
        .card-premium:hover .stat-icon {
            transform: rotate(0deg) scale(1.15);
            opacity: 0.08;
            color: var(--primary);
        }
        .stat-title {
            font-size: 0.9rem;
            font-weight: 600;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 8px;
        }
        .stat-value {
            font-size: 2.5rem;
            font-weight: 800;
            margin: 0;
            line-height: 1;
            letter-spacing: -1px;
        }
        .table-custom { margin: 0; border-collapse: separate; border-spacing: 0; width: 100%; }
        .table-custom thead th {
            border-bottom: 1px solid #e5e7eb;
            font-weight: 700;
            color: #6b7280;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.05em;
            padding: 20px 24px;
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
            padding: 8px 16px;
            border-radius: 10px;
            font-weight: 700;
            font-size: 0.75rem;
            letter-spacing: 0.5px;
        }
        .badge-soft-success { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
        .badge-soft-danger { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
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
            SIGUDANG
        </h4>
    </div>
    <div class="flex-grow-1 overflow-auto mt-2 pb-4">
        <a class="nav-link active" href="index.php"><i class="fa-solid fa-gauge-high"></i> Dashboard</a>
        <?php if ($_SESSION['role'] == 'Admin') : ?>
        <a class="nav-link" href="transaction.php"><i class="fa-solid fa-pen-to-square"></i> Transaction</a>
        <a class="nav-link" href="deadlock.php"><i class="fa-solid fa-arrows-spin"></i> Deadlock</a>
        <?php endif; ?>
        <a class="nav-link" href="log.php"><i class="fa-solid fa-chart-bar"></i> Log & Analytics</a>
        <?php if ($_SESSION['role'] == 'Admin') : ?>
        <a class="nav-link" href="backup.php"><i class="fa-solid fa-database"></i> Backup DB</a>
        <?php endif; ?>
    </div>
</div>
<div class="main-wrapper">
    <div class="topbar-floating">
        <h5 class="topbar-title">Dashboard Monitoring Multi-Cabang</h5>
        <div class="user-profile">
            <span><?= $_SESSION['username']; ?></span> 
            <a href="logout.php" class="btn-logout" title="Logout"><i class="fa-solid fa-power-off"></i></a>
        </div>
    </div>
    <div class="row g-4 mb-4">
        <div class="col-md-4">
            <div class="card-premium">
                <div class="stat-card">
                    <div class="stat-title">Tipe Barang Master</div>
                    <div class="stat-value text-primary"><?= $total_barang; ?></div>
                    <i class="fa-solid fa-box stat-icon"></i>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card-premium">
                <div class="stat-card">
                    <div class="stat-title">Total Stok Global</div>
                    <div class="stat-value text-success"><?= $total_stok ?: 0; ?></div>
                    <i class="fa-solid fa-layer-group stat-icon"></i>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card-premium">
                <div class="stat-card">
                    <div class="stat-title">Log Transaksi</div>
                    <div class="stat-value text-info"><?= $total_transaksi; ?></div>
                    <i class="fa-solid fa-clipboard-list stat-icon"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="card-premium mb-4">
        <div class="p-4 border-bottom" style="border-color: rgba(0,0,0,0.05) !important;">
            <h6 class="fw-bold m-0 text-secondary">Statistik Stok (Top 5)</h6>
        </div>
        <div class="p-4">
            <canvas id="stokChart" height="70"></canvas>
        </div>
    </div>
    <div class="card-premium">
        <div class="table-responsive">
            <table class="table-custom">
                <thead>
                    <tr>
                        <th class="ps-4">Kode</th>
                        <th>Nama Barang</th>
                        <th class="text-center">Total Stok Global</th>
                        <th class="text-center">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($row = mysqli_fetch_assoc($barang)) : ?>
                    <tr>
                        <td class="ps-4"><span class="badge-soft badge-soft-dark"><?= $row['kode_barang']; ?></span></td>
                        <td class="fw-bold"><?= $row['nama_barang']; ?></td>
                        <td class="text-center fw-bolder fs-5 text-dark"><?= $row['stok']; ?></td>
                        <td class="text-center">
                            <?= $row['stok'] < 30 ? '<span class="badge-soft badge-soft-danger">Kritis</span>' : '<span class="badge-soft badge-soft-success">Aman</span>'; ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<script>
    Chart.defaults.font.family = "'Plus Jakarta Sans', sans-serif";
    Chart.defaults.color = '#64748b';
    
    new Chart(document.getElementById('stokChart').getContext('2d'), {
        type: 'bar', 
        data: { 
            labels: <?= json_encode($chart_labels); ?>, 
            datasets: [{ 
                label: 'Stok Global', 
                data: <?= json_encode($chart_data); ?>, 
                backgroundColor: 'rgba(79, 70, 229, 0.8)',
                borderRadius: 8,
                borderSkipped: false,
                barPercentage: 0.6
            }] 
        },
        options: {
            plugins: {
                legend: { display: false }
            },
            scales: {
                y: {
                    grid: { color: 'rgba(0,0,0,0.05)', drawBorder: false },
                    border: { display: false }
                },
                x: {
                    grid: { display: false },
                    border: { display: false }
                }
            }
        }
    });
</script>
</body>
</html>