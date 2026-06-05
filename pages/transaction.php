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

    mysqli_query($conn, "START TRANSACTION"); 
    
    try {
        if ($jenis == 'mutasi') {
            if ($asal_id == 'NULL' || $tujuan_id == 'NULL') throw new Exception("Cabang Asal dan Tujuan wajib diisi untuk mutasi.");
            if ($asal_id == $tujuan_id) throw new Exception("Cabang Asal dan Tujuan tidak boleh sama.");
            
            $min_id = min($asal_id, $tujuan_id);
            $max_id = max($asal_id, $tujuan_id);
            mysqli_query($conn, "SELECT stok FROM stok_cabang WHERE barang_id = $barang_id AND cabang_id = $min_id FOR UPDATE");
            mysqli_query($conn, "SELECT stok FROM stok_cabang WHERE barang_id = $barang_id AND cabang_id = $max_id FOR UPDATE");
            
            $cek = mysqli_query($conn, "SELECT stok FROM stok_cabang WHERE barang_id = $barang_id AND cabang_id = $asal_id");
            $stok_asal = ($r = mysqli_fetch_assoc($cek)) ? $r['stok'] : 0;
            if ($stok_asal < $jumlah) throw new Exception("Stok di Cabang Asal tidak mencukupi.");
            
        } elseif ($jenis == 'keluar') {
            if ($asal_id == 'NULL') throw new Exception("Cabang Asal wajib diisi.");
            $tujuan_id = 'NULL';
            mysqli_query($conn, "SELECT stok FROM stok_cabang WHERE barang_id = $barang_id AND cabang_id = $asal_id FOR UPDATE");
            $cek = mysqli_query($conn, "SELECT stok FROM stok_cabang WHERE barang_id = $barang_id AND cabang_id = $asal_id");
            $stok_asal = ($r = mysqli_fetch_assoc($cek)) ? $r['stok'] : 0;
            if ($stok_asal < $jumlah) throw new Exception("Stok tidak mencukupi.");
            
        } elseif ($jenis == 'masuk') {
            if ($tujuan_id == 'NULL') throw new Exception("Cabang Tujuan wajib diisi.");
            $asal_id = 'NULL';
            mysqli_query($conn, "SELECT stok FROM stok_cabang WHERE barang_id = $barang_id AND cabang_id = $tujuan_id FOR UPDATE");
        }

        $simpan = mysqli_query($conn, "INSERT INTO transaksi (barang_id, jenis_transaksi, jumlah, cabang_asal_id, cabang_tujuan_id) VALUES ($barang_id, '$jenis', $jumlah, $asal_id, $tujuan_id)");
        
        if ($simpan) {
            mysqli_query($conn, "COMMIT");
            $pesan = "<div class='alert alert-success mt-3'>Transaksi berhasil (Committed)! Stok diperbarui.</div>";
        } else { throw new Exception("Gagal simpan transaksi: " . mysqli_error($conn)); }
        
    } catch (Exception $e) {
        mysqli_query($conn, "ROLLBACK");
        $pesan = "<div class='alert alert-danger mt-3'>Transaksi dibatalkan (Rollback): " . $e->getMessage() . "</div>";
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
    <title>Tambah Transaksi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style> body { font-family: 'Poppins', sans-serif; background-color: #f4f7f6; } .sidebar { background: #111827; min-height: 100vh; color: #9ca3af; } .sidebar .nav-link { color: #9ca3af; padding: 12px 20px; border-radius: 8px; font-weight: 500; margin-bottom: 5px; } .sidebar .nav-link:hover { color: #fff; background: rgba(255,255,255,0.05); } .sidebar .nav-link.active { background: #1f2937; color: #fff; } .topbar { background: #fff; box-shadow: 0 1px 3px 0 rgba(0,0,0,0.1); }</style>
</head>
<body class="d-flex">
<div class="sidebar d-none d-md-block" style="width: 260px; flex-shrink:0;">
    <div class="p-4 border-bottom border-secondary mb-3"><h5 class="text-white fw-bold m-0"><i class="fa-solid fa-boxes-stacked text-primary me-2"></i> SIGUDANG</h5></div>
    <nav class="nav flex-column px-3">
        <a class="nav-link" href="index.php"><i class="fa-solid fa-gauge-high me-3"></i> Dashboard</a>
        <?php if ($_SESSION['role'] == 'Admin') : ?>
        <a class="nav-link active" href="transaction.php"><i class="fa-solid fa-pen-to-square me-3"></i> Transaction</a>
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
    <div class="topbar p-3 d-flex justify-content-between align-items-center px-4 mb-4 sticky-top">
        <h5 class="mb-0 fw-bold">Formulir Transaksi</h5>
        <div><span class="me-3 fw-bold"><?= $_SESSION['username']; ?></span> <a href="logout.php" class="btn btn-sm btn-outline-danger"><i class="fa-solid fa-sign-out-alt"></i></a></div>
    </div>
    <div class="container-fluid px-4 pb-4">
        <div class="card border-0 shadow-sm" style="max-width: 600px; margin: auto;">
            <div class="card-body p-4">
                <h5 class="fw-bold mb-4">Input Barang / Mutasi Antar Cabang</h5>
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Jenis Pergerakan</label>
                        <select name="jenis_transaksi" id="jenis_transaksi" class="form-select" required onchange="toggleCabang()">
                            <option value="masuk">➕ Barang Masuk (Inbound)</option>
                            <option value="keluar">➖ Barang Keluar (Outbound)</option>
                            <option value="mutasi">🔄 Mutasi Antar Cabang</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Pilih Barang</label>
                        <select name="barang_id" class="form-select" required>
                            <option value="">-- Pilih Barang --</option>
                            <?php while($b = mysqli_fetch_assoc($daftar_barang)) : ?>
                                <option value="<?= $b['id']; ?>">[<?= $b['kode_barang']; ?>] <?= $b['nama_barang']; ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6" id="box_asal" style="display: none;">
                            <label class="form-label fw-semibold text-danger">Cabang Asal</label>
                            <select name="cabang_asal" id="cabang_asal" class="form-select">
                                <option value="">-- Pilih Asal --</option>
                                <?php foreach($cabang_arr as $c) : ?>
                                    <option value="<?= $c['id']; ?>"><?= $c['nama_cabang']; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6" id="box_tujuan">
                            <label class="form-label fw-semibold text-success">Cabang Tujuan</label>
                            <select name="cabang_tujuan" id="cabang_tujuan" class="form-select">
                                <option value="">-- Pilih Tujuan --</option>
                                <?php foreach($cabang_arr as $c) : ?>
                                    <option value="<?= $c['id']; ?>"><?= $c['nama_cabang']; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-semibold">Jumlah (Kuantitas)</label>
                        <input type="number" name="jumlah" min="1" class="form-control" required>
                    </div>
                    <button type="submit" name="simpan_transaksi" class="btn btn-primary w-100 fw-bold py-2">Proses (Transact)</button>
                </form>
                <?= $pesan; ?>
            </div>
        </div>
    </div>
</div>
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