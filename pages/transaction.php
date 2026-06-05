<?php
require_once __DIR__ . '/../config/koneksi.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); } 
if (!isset($_SESSION['username'])) { header('Location: login.php'); exit; }

$pesan = "";
if (isset($_POST['simpan_transaksi'])) {
    $barang_id = (int)$_POST['barang_id'];
    $jenis = $_POST['jenis_transaksi'];
    $jumlah = (int)$_POST['jumlah'];
    
    $asal_id = !empty($_POST['cabang_asal']) ? (int)$_POST['cabang_asal'] : 'NULL';
    $tujuan_id = !empty($_POST['cabang_tujuan']) ? (int)$_POST['cabang_tujuan'] : 'NULL';

    try {
        if ($jenis == 'mutasi') {
            if ($asal_id == 'NULL' || $tujuan_id == 'NULL') throw new Exception("Cabang Asal dan Tujuan wajib diisi untuk mutasi.");
            if ($asal_id == $tujuan_id) throw new Exception("Cabang Asal dan Tujuan tidak boleh sama.");
        } elseif ($jenis == 'keluar') {
            if ($asal_id == 'NULL') throw new Exception("Cabang Asal wajib diisi.");
            $tujuan_id = 'NULL';
        } elseif ($jenis == 'masuk') {
            if ($tujuan_id == 'NULL') throw new Exception("Cabang Tujuan wajib diisi.");
            $asal_id = 'NULL';
        }

        // Memanggil Stored Procedure yang ada di Database
        $query_call = "CALL CatatTransaksi($barang_id, '$jenis', $jumlah, $asal_id, $tujuan_id)";
        $eksekusi = mysqli_query($conn, $query_call);
        
        if ($eksekusi) {
            $pesan = "<div class='alert alert-success mt-3'>Transaksi berhasil (via Procedure)! Stok otomatis diperbarui.</div>";
        } else {
            // Error dari SIGNAL SQLSTATE 45000 akan ditangkap di sini
            throw new Exception("Gagal simpan transaksi: " . mysqli_error($conn));
        }
        
    } catch (Exception $e) {
        $pesan = "<div class='alert alert-danger mt-3'>Transaksi dibatalkan: " . $e->getMessage() . "</div>";
    }
}

$daftar_barang = mysqli_query($conn, "SELECT id, nama_barang, kode_barang FROM barang");
$daftar_cabang = mysqli_query($conn, "SELECT id, nama_cabang FROM cabang");
$cabang_arr = [];
while($c = mysqli_fetch_assoc($daftar_cabang)) { $cabang_arr[] = $c; }
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transaksi - SIGUDANG Premium</title>
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
        .form-label {
            font-weight: 600;
            color: #475569;
            font-size: 0.9rem;
            margin-bottom: 8px;
        }
        
        .form-select, .form-control {
            border-radius: 12px;
            padding: 12px 16px;
            border: 1px solid #cbd5e1;
            background-color: rgba(255,255,255,0.9);
            box-shadow: 0 2px 4px rgba(0,0,0,0.02);
            transition: all 0.3s ease;
        }
        
        .form-select:focus, .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.1);
        }
        .btn-primary-custom {
            background: linear-gradient(135deg, var(--primary), #3b82f6);
            color: white;
            border-radius: 12px;
            border: none;
            padding: 14px 20px;
            font-weight: 700;
            letter-spacing: 0.5px;
            box-shadow: 0 8px 16px -4px rgba(79, 70, 229, 0.3);
            transition: all 0.3s ease;
        }
        
        .btn-primary-custom:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 20px -4px rgba(79, 70, 229, 0.4);
            color: white;
        }
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
        <a class="nav-link active" href="transaction.php"><i class="fa-solid fa-pen-to-square"></i> Transaction</a>
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
        <h5 class="topbar-title">Formulir Transaksi</h5>
        <div class="user-profile">
            <span><?= $_SESSION['username']; ?></span> 
            <a href="logout.php" class="btn-logout" title="Logout"><i class="fa-solid fa-power-off"></i></a>
        </div>
    </div>
    <div class="d-flex justify-content-center">
        <div class="card-premium" style="width: 100%; max-width: 600px;">
            <div class="p-4 p-md-5">
                <div class="d-flex align-items-center mb-4">
                    <div style="background: #e0e7ff; color: var(--primary); padding: 12px; border-radius: 14px; margin-right: 16px;">
                        <i class="fa-solid fa-truck-fast fs-4"></i>
                    </div>
                    <div>
                        <h4 class="fw-bold mb-1" style="color: #1e293b;">Input Transaksi</h4>
                        <p class="text-secondary mb-0 small">Masukkan data barang masuk, keluar, atau mutasi cabang</p>
                    </div>
                </div>
                <form method="POST">
                    <div class="mb-4">
                        <label class="form-label">Jenis Pergerakan</label>
                        <select name="jenis_transaksi" id="jenis_transaksi" class="form-select" required onchange="toggleCabang()">
                            <option value="masuk">➕ Barang Masuk (Inbound)</option>
                            <option value="keluar">➖ Barang Keluar (Outbound)</option>
                            <option value="mutasi">🔄 Mutasi Antar Cabang</option>
                        </select>
                    </div>
                    
                    <div class="mb-4">
                        <label class="form-label">Pilih Barang</label>
                        <select name="barang_id" class="form-select" required>
                            <option value="">-- Pilih Barang --</option>
                            <?php while($b = mysqli_fetch_assoc($daftar_barang)) : ?>
                                <option value="<?= $b['id']; ?>">[<?= $b['kode_barang']; ?>] <?= $b['nama_barang']; ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <div class="row mb-4">
                        <div class="col-md-6" id="box_asal" style="display: none;">
                            <label class="form-label text-danger">Cabang Asal</label>
                            <select name="cabang_asal" id="cabang_asal" class="form-select">
                                <option value="">-- Pilih Asal --</option>
                                <?php foreach($cabang_arr as $c) : ?>
                                    <option value="<?= $c['id']; ?>"><?= $c['nama_cabang']; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6" id="box_tujuan">
                            <label class="form-label text-success">Cabang Tujuan</label>
                            <select name="cabang_tujuan" id="cabang_tujuan" class="form-select">
                                <option value="">-- Pilih Tujuan --</option>
                                <?php foreach($cabang_arr as $c) : ?>
                                    <option value="<?= $c['id']; ?>"><?= $c['nama_cabang']; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="mb-5">
                        <label class="form-label">Jumlah (Kuantitas)</label>
                        <input type="number" name="jumlah" min="1" class="form-control" required placeholder="0">
                    </div>
                    
                    <button type="submit" name="simpan_transaksi" class="btn btn-primary-custom w-100">
                        Proses Transaksi <i class="fa-solid fa-paper-plane ms-2"></i>
                    </button>
                </form>
                <?= $pesan; ?>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function toggleCabang() {
    let j = document.getElementById('jenis_transaksi').value;
    let bAsal = document.getElementById('box_asal');
    let bTujuan = document.getElementById('box_tujuan');
    let sAsal = document.getElementById('cabang_asal');
    let sTujuan = document.getElementById('cabang_tujuan');
    
    if (j == 'masuk') {
        bAsal.style.display = 'none'; sAsal.required = false;
        bTujuan.style.display = 'block'; sTujuan.required = true; bTujuan.className = 'col-md-12';
    } else if (j == 'keluar') {
        bAsal.style.display = 'block'; sAsal.required = true; bAsal.className = 'col-md-12';
        bTujuan.style.display = 'none'; sTujuan.required = false;
    } else {
        bAsal.style.display = 'block'; sAsal.required = true; bAsal.className = 'col-md-6';
        bTujuan.style.display = 'block'; sTujuan.required = true; bTujuan.className = 'col-md-6';
    }
}
toggleCabang();
</script>
</body>
</html>