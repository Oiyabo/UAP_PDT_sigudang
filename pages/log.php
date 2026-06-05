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
    <title>Log & Analytics - SIGUDANG</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style> body { font-family: 'Poppins', sans-serif; background-color: #f4f7f6; } .sidebar { background: #111827; min-height: 100vh; color: #9ca3af; } .sidebar .nav-link { color: #9ca3af; padding: 12px 20px; border-radius: 8px; font-weight: 500; margin-bottom: 5px; } .sidebar .nav-link:hover { color: #fff; background: rgba(255,255,255,0.05); } .sidebar .nav-link.active { background: #1f2937; color: #fff; } .topbar { background: #fff; box-shadow: 0 1px 3px 0 rgba(0,0,0,0.1); } .glass-card { background: #fff; border-radius: 12px; border: 1px solid #e5e7eb; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); }</style>
</head>
<body class="d-flex">
<div class="sidebar d-none d-md-block" style="width: 260px; flex-shrink:0;">
    <div class="p-4 border-bottom border-secondary mb-3"><h5 class="text-white fw-bold m-0"><i class="fa-solid fa-boxes-stacked text-primary me-2"></i> SIGUDANG</h5></div>
    <nav class="nav flex-column px-3">
        <a class="nav-link" href="index.php"><i class="fa-solid fa-gauge-high me-3"></i> Dashboard</a>
        <?php if ($_SESSION['role'] == 'Admin') : ?>
        <a class="nav-link" href="transaction.php"><i class="fa-solid fa-pen-to-square me-3"></i> Transaction</a>
        <a class="nav-link" href="deadlock.php"><i class="fa-solid fa-arrows-spin me-3"></i> Deadlock</a>
        <?php endif; ?>
        <a class="nav-link active" href="log.php"><i class="fa-solid fa-chart-bar me-3"></i> Log & Analytics</a>
        <?php if ($_SESSION['role'] == 'Admin') : ?>
        <a class="nav-link" href="backup.php"><i class="fa-solid fa-database me-3"></i> Backup Database</a>
        <?php endif; ?>
        <a class="nav-link text-danger mt-5" href="logout.php"><i class="fa-solid fa-sign-out-alt me-3"></i> Logout</a>
    </nav>
</div>
<div class="flex-grow-1" style="height: 100vh; overflow-y: auto;">
    <div class="topbar p-3 d-flex justify-content-between align-items-center px-4 mb-4 sticky-top">
        <h5 class="mb-0 fw-bold">Log & Analytics</h5>
        <div><span class="me-3 fw-bold"><?= $_SESSION['username']; ?></span> <a href="logout.php" class="btn btn-sm btn-outline-danger"><i class="fa-solid fa-sign-out-alt"></i></a></div>
    </div>
    <div class="container-fluid px-4 pb-4">

        <ul class="nav nav-pills mb-4" id="logTab" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="transaksi-tab" data-bs-toggle="pill" data-bs-target="#transaksi" type="button" role="tab" aria-controls="transaksi" aria-selected="true"><i class="fa-solid fa-clock-rotate-left me-2"></i> Transaction Log</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="komparasi-tab" data-bs-toggle="pill" data-bs-target="#komparasi" type="button" role="tab" aria-controls="komparasi" aria-selected="false"><i class="fa-solid fa-layer-group me-2"></i> Set Operations (Data Komparasi)</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="frag-tab" data-bs-toggle="pill" data-bs-target="#frag" type="button" role="tab" aria-controls="frag" aria-selected="false"><i class="fa-solid fa-table-columns me-2"></i> Data Fragmentation</button>
            </li>
        </ul>

        <div class="tab-content" id="logTabContent">
            

            <div class="tab-pane fade show active" id="transaksi" role="tabpanel" aria-labelledby="transaksi-tab">
                <div class="glass-card p-0">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light"><tr><th class="ps-4">Tanggal</th><th>Barang</th><th>Jenis</th><th>Rute (Asal &rarr; Tujuan)</th><th class="text-center">Kuantitas</th></tr></thead>
                        <tbody>
                            <?php while($log = mysqli_fetch_assoc($transaksi)) : ?>
                            <tr>
                                <td class="ps-4 small text-muted"><?= date('d M Y, H:i', strtotime($log['tanggal'])); ?></td>
                                <td class="fw-medium"><span class="badge bg-secondary me-2"><?= $log['kode_barang']; ?></span> <?= $log['nama_barang']; ?></td>
                                <td>
                                    <?php if($log['jenis_transaksi'] == 'masuk') echo '<span class="badge bg-success bg-opacity-10 text-success border border-success">Masuk</span>';
                                          elseif($log['jenis_transaksi'] == 'keluar') echo '<span class="badge bg-danger bg-opacity-10 text-danger border border-danger">Keluar</span>';
                                          else echo '<span class="badge bg-primary bg-opacity-10 text-primary border border-primary">Mutasi</span>'; ?>
                                </td>
                                <td class="small">
                                    <?php if($log['jenis_transaksi'] == 'masuk') echo "Ke: <strong>{$log['tujuan']}</strong>";
                                          elseif($log['jenis_transaksi'] == 'keluar') echo "Dari: <strong>{$log['asal']}</strong>";
                                          else echo "<strong>{$log['asal']}</strong> &rarr; <strong>{$log['tujuan']}</strong>"; ?>
                                </td>
                                <td class="text-center fw-bold fs-6 <?= $log['jenis_transaksi'] == 'masuk' ? 'text-success' : ($log['jenis_transaksi'] == 'keluar' ? 'text-danger' : 'text-primary'); ?>">
                                    <?= $log['jenis_transaksi'] == 'masuk' ? '+' : ($log['jenis_transaksi'] == 'keluar' ? '-' : '↻'); ?><?= $log['jumlah']; ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                            <?php if(mysqli_num_rows($transaksi) == 0) echo '<tr><td colspan="5" class="text-center text-muted p-3">Belum ada transaksi.</td></tr>'; ?>
                        </tbody>
                    </table>
                </div>
            </div>


            <div class="tab-pane fade" id="komparasi" role="tabpanel" aria-labelledby="komparasi-tab">
                <ul class="nav nav-tabs fw-bold mb-4" id="setOpsTab" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active text-primary" id="union-tab" data-bs-toggle="tab" data-bs-target="#union" type="button" role="tab" aria-controls="union" aria-selected="true"><i class="fa-solid fa-layer-group me-2"></i> Gabungan Data</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link text-success" id="intersect-tab" data-bs-toggle="tab" data-bs-target="#intersect" type="button" role="tab" aria-controls="intersect" aria-selected="false"><i class="fa-solid fa-check-double me-2"></i> Tersedia di Pusat & Cabang</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link text-danger" id="except-tab" data-bs-toggle="tab" data-bs-target="#except" type="button" role="tab" aria-controls="except" aria-selected="false"><i class="fa-solid fa-not-equal me-2"></i> Selisih Data (Pusat Only)</button>
                    </li>
                </ul>

                <div class="tab-content" id="setOpsTabContent">
                    <div class="tab-pane fade show active" id="union" role="tabpanel" aria-labelledby="union-tab">
                        <div class="glass-card p-4">
                            <h6 class="fw-bold mb-3 text-primary">Gabungan Seluruh Master Barang Aktif</h6>
                            <table class="table table-bordered table-hover">
                                <thead class="table-light"><tr><th>Kode Barang</th><th>Nama Barang</th><th>Sumber Lokasi</th></tr></thead>
                                <tbody>
                                    <?php foreach($union_data as $row) : ?>
                                    <tr><td><?= $row['kode_barang']; ?></td><td><?= $row['nama_barang']; ?></td><td><span class="badge <?= $row['lokasi'] == 'Gudang Pusat' ? 'bg-primary' : 'bg-secondary'; ?>"><?= $row['lokasi']; ?></span></td></tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="tab-pane fade" id="intersect" role="tabpanel" aria-labelledby="intersect-tab">
                        <div class="glass-card p-4">
                            <h6 class="fw-bold mb-3 text-success">Barang Tersedia di Pusat & Cabang</h6>
                            <table class="table table-bordered table-hover">
                                <thead class="table-light"><tr><th>Kode Barang</th><th>Nama Barang</th></tr></thead>
                                <tbody>
                                    <?php while($row = mysqli_fetch_assoc($result_intersect)) : ?>
                                    <tr><td><?= $row['kode_barang']; ?></td><td><?= $row['nama_barang']; ?></td></tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="tab-pane fade" id="except" role="tabpanel" aria-labelledby="except-tab">
                        <div class="glass-card p-4">
                            <h6 class="fw-bold mb-3 text-danger">Barang Menumpuk di Gudang Pusat (Pusat Only)</h6>
                            <table class="table table-bordered table-hover">
                                <thead class="table-light"><tr><th>Kode Barang</th><th>Nama Barang</th></tr></thead>
                                <tbody>
                                    <?php while($row = mysqli_fetch_assoc($result_except)) : ?>
                                    <tr><td><?= $row['kode_barang']; ?></td><td><?= $row['nama_barang']; ?></td></tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>


            <div class="tab-pane fade" id="frag" role="tabpanel" aria-labelledby="frag-tab">
                <ul class="nav nav-tabs fw-bold mb-4" id="fragSubTab" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active text-primary" id="horizontal-tab" data-bs-toggle="tab" data-bs-target="#horizontal" type="button" role="tab" aria-controls="horizontal" aria-selected="true"><i class="fa-solid fa-arrows-left-right me-2"></i> Horizontal</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link text-success" id="vertical-tab" data-bs-toggle="tab" data-bs-target="#vertical" type="button" role="tab" aria-controls="vertical" aria-selected="false"><i class="fa-solid fa-arrows-up-down me-2"></i> Vertikal</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link text-warning" id="campuran-tab" data-bs-toggle="tab" data-bs-target="#campuran" type="button" role="tab" aria-controls="campuran" aria-selected="false"><i class="fa-solid fa-maximize me-2"></i> Campuran</button>
                    </li>
                </ul>

                <div class="tab-content" id="fragSubTabContent">
                    <div class="tab-pane fade show active" id="horizontal" role="tabpanel" aria-labelledby="horizontal-tab">
                        <div class="glass-card p-4">
                            <h6 class="fw-bold mb-3 text-primary">Fragmentasi Horizontal (Transaksi Masuk Saja)</h6>
                            <table class="table table-bordered table-hover">
                                <thead class="table-light"><tr><th>ID</th><th>Barang ID</th><th>Jenis</th><th>Jumlah</th><th>Tujuan</th><th>Tanggal</th></tr></thead>
                                <tbody>
                                    <?php while($row = mysqli_fetch_assoc($frag_horizontal)) : ?>
                                    <tr><td><?= $row['id']; ?></td><td><?= $row['barang_id']; ?></td><td><?= $row['jenis_transaksi']; ?></td><td><?= $row['jumlah']; ?></td><td><?= $row['cabang_tujuan_id']; ?></td><td><?= $row['tanggal']; ?></td></tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="tab-pane fade" id="vertical" role="tabpanel" aria-labelledby="vertical-tab">
                        <div class="glass-card p-4">
                            <h6 class="fw-bold mb-3 text-success">Fragmentasi Vertikal (Detail Transaksi)</h6>
                            <table class="table table-bordered table-hover">
                                <thead class="table-light"><tr><th>ID</th><th>Barang ID</th><th>Jumlah</th></tr></thead>
                                <tbody>
                                    <?php while($row = mysqli_fetch_assoc($frag_vertical)) : ?>
                                    <tr><td><?= $row['id']; ?></td><td><?= $row['barang_id']; ?></td><td><?= $row['jumlah']; ?></td></tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="tab-pane fade" id="campuran" role="tabpanel" aria-labelledby="campuran-tab">
                        <div class="glass-card p-4">
                            <h6 class="fw-bold mb-3 text-warning">Fragmentasi Campuran (Ringkasan Transaksi Masuk)</h6>
                            <table class="table table-bordered table-hover">
                                <thead class="table-light"><tr><th>ID</th><th>Barang ID</th><th>Jumlah</th><th>Tanggal</th></tr></thead>
                                <tbody>
                                    <?php while($row = mysqli_fetch_assoc($frag_campuran)) : ?>
                                    <tr><td><?= $row['id']; ?></td><td><?= $row['barang_id']; ?></td><td><?= $row['jumlah']; ?></td><td><?= $row['tanggal']; ?></td></tr>
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
<script>
document.addEventListener('DOMContentLoaded', function() {

    const selectAll = document.getElementById('selectAllRows');
    const rowCheckboxes = document.querySelectorAll('.row-checkbox');
    if(selectAll) {
        selectAll.addEventListener('change', function() {
            rowCheckboxes.forEach(cb => cb.checked = selectAll.checked);
        });
        rowCheckboxes.forEach(cb => {
            cb.addEventListener('change', function() {
                if(!this.checked) selectAll.checked = false;
                else if(document.querySelectorAll('.row-checkbox:checked').length === rowCheckboxes.length) selectAll.checked = true;
            });
        });
    }


    const colToggles = document.querySelectorAll('.col-toggle');
    const table = document.getElementById('fragTable');
    if(colToggles.length > 0 && table) {
        colToggles.forEach(toggle => {
            toggle.addEventListener('change', function() {
                const colIndex = this.getAttribute('data-col');

                const cells = table.querySelectorAll(`tr > :nth-child(${parseInt(colIndex) + 1})`);
                cells.forEach(cell => {
                    cell.style.display = this.checked ? '' : 'none';
                });
                

                const footerLabel = document.getElementById('fragFooterLabel');
                if(footerLabel) {
                    let visibleCols = 2;
                    document.querySelectorAll('.col-toggle:checked').forEach(t => {
                        if (parseInt(t.getAttribute('data-col')) < 6) visibleCols++;
                    });
                    footerLabel.colSpan = visibleCols;
                }
            });

            toggle.dispatchEvent(new Event('change'));
        });
    }
});
</script>
</body>
</html>
