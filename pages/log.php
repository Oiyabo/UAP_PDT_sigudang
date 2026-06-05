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


$query_utama = "SELECT DISTINCT b.kode_barang, b.nama_barang, 'Gudang Pusat' AS lokasi FROM stok_cabang sc JOIN barang b ON sc.barang_id = b.id WHERE sc.cabang_id = 1 AND sc.stok > 0";
$result_utama = mysqli_query($conn, $query_utama);

$query_cabang = "SELECT DISTINCT b.kode_barang, b.nama_barang, c.nama_cabang AS lokasi FROM stok_cabang sc JOIN barang b ON sc.barang_id = b.id JOIN cabang c ON sc.cabang_id = c.id WHERE sc.cabang_id != 1 AND sc.stok > 0";
$result_cabang = mysqli_query($conn, $query_cabang);

$union_data = [];
while($row = mysqli_fetch_assoc($result_utama)) { $union_data[] = $row; }
while($row = mysqli_fetch_assoc($result_cabang)) { $union_data[] = $row; }
usort($union_data, function($a, $b) { return strcmp($a['kode_barang'], $b['kode_barang']); });

$query_intersect = "
    SELECT DISTINCT b.kode_barang, b.nama_barang 
    FROM stok_cabang sc_pusat 
    JOIN barang b ON sc_pusat.barang_id = b.id
    WHERE sc_pusat.cabang_id = 1 AND sc_pusat.stok > 0
    AND b.id IN (SELECT barang_id FROM stok_cabang WHERE cabang_id != 1 AND stok > 0)
";
$result_intersect = mysqli_query($conn, $query_intersect);

$query_except = "
    SELECT DISTINCT b.kode_barang, b.nama_barang 
    FROM stok_cabang sc_pusat 
    JOIN barang b ON sc_pusat.barang_id = b.id
    WHERE sc_pusat.cabang_id = 1 AND sc_pusat.stok > 0
    AND b.id NOT IN (SELECT barang_id FROM stok_cabang WHERE cabang_id != 1 AND stok > 0)
";
$result_except = mysqli_query($conn, $query_except);



$pesan_frag = "";
if ($_SESSION['role'] == 'Admin') {
    if (isset($_POST['optimize_table'])) {
        $table_name = preg_replace('/[^a-zA-Z0-9_]/', '', $_POST['table_name']);
        if (!empty($table_name)) {
            mysqli_query($conn, "OPTIMIZE TABLE $table_name");
            $pesan_frag = "<div class='alert alert-success mt-3'><i class='fa-solid fa-check-circle me-2'></i>Tabel <strong>$table_name</strong> berhasil dioptimasi. Fragmentasi telah dibersihkan.</div>";
        }
    }

    if (isset($_POST['optimize_all'])) {
        $tables = [];
        $res = mysqli_query($conn, "SHOW TABLES");
        while ($row = mysqli_fetch_row($res)) {
            $tables[] = $row[0];
        }
        if (count($tables) > 0) {
            $table_list = implode(", ", $tables);
            mysqli_query($conn, "OPTIMIZE TABLE $table_list");
            $pesan_frag = "<div class='alert alert-success mt-3'><i class='fa-solid fa-check-circle me-2'></i>Seluruh tabel (".count($tables)." tabel) berhasil dioptimasi.</div>";
        }
    }

    if (isset($_POST['optimize_selected'])) {
        if (!empty($_POST['selected_tables'])) {
            $safe_tables = [];
            foreach ($_POST['selected_tables'] as $t) {
                $t_safe = preg_replace('/[^a-zA-Z0-9_]/', '', $t);
                if (!empty($t_safe)) $safe_tables[] = $t_safe;
            }
            if (count($safe_tables) > 0) {
                $table_list = implode(", ", $safe_tables);
                mysqli_query($conn, "OPTIMIZE TABLE $table_list");
                $pesan_frag = "<div class='alert alert-success mt-3'><i class='fa-solid fa-check-circle me-2'></i>Tabel (".implode(", ", $safe_tables).") berhasil dioptimasi.</div>";
            }
        } else {
            $pesan_frag = "<div class='alert alert-warning mt-3'><i class='fa-solid fa-triangle-exclamation me-2'></i>Pilih minimal satu tabel untuk dioptimasi.</div>";
        }
    }
}

$db_name = "db_gudang";
$query_frag = "
    SELECT 
        TABLE_NAME as table_name,
        ENGINE as engine,
        TABLE_ROWS as table_rows,
        DATA_LENGTH as data_length,
        INDEX_LENGTH as index_length,
        DATA_FREE as data_free
    FROM information_schema.TABLES
    WHERE TABLE_SCHEMA = '$db_name'
    ORDER BY DATA_FREE DESC
";
$tables_info = mysqli_query($conn, $query_frag);

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
            <?php if ($_SESSION['role'] == 'Admin') : ?>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="frag-tab" data-bs-toggle="pill" data-bs-target="#frag" type="button" role="tab" aria-controls="frag" aria-selected="false"><i class="fa-solid fa-server me-2"></i> Fragmentation</button>
            </li>
            <?php endif; ?>
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


            <?php if ($_SESSION['role'] == 'Admin') : ?>
            <div class="tab-pane fade" id="frag" role="tabpanel" aria-labelledby="frag-tab">
                <div class="glass-card mb-4 p-4 border-start border-warning border-4">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="fw-bold mb-2"><i class="fa-solid fa-broom text-warning me-2"></i>Optimasi Performa Database</h5>
                            <p class="text-muted small mb-0">Pilih kolom yang ingin ditampilkan dan baris tabel yang ingin dioptimasi.</p>
                        </div>
                        <div>
                            <form method="POST" onsubmit="return confirm('Optimasi seluruh tabel akan memakan waktu dan memberikan lock pada tabel selama proses. Lanjutkan?');" class="d-inline">
                                <button type="submit" name="optimize_all" class="btn btn-warning fw-bold"><i class="fa-solid fa-bolt me-2"></i>Optimize Semua</button>
                            </form>
                        </div>
                    </div>
                </div>

                <?= $pesan_frag; ?>

                <div class="glass-card mb-3 p-3">
                    <h6 class="fw-bold mb-2 small text-muted">Tampilkan/Sembunyikan Kolom:</h6>
                    <div class="d-flex flex-wrap gap-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input col-toggle" type="checkbox" id="colEngine" data-col="2" checked>
                            <label class="form-check-label small" for="colEngine">Engine</label>
                        </div>
                        <div class="form-check form-switch">
                            <input class="form-check-input col-toggle" type="checkbox" id="colRows" data-col="3" checked>
                            <label class="form-check-label small" for="colRows">Jumlah Baris</label>
                        </div>
                        <div class="form-check form-switch">
                            <input class="form-check-input col-toggle" type="checkbox" id="colData" data-col="4" checked>
                            <label class="form-check-label small" for="colData">Data Size</label>
                        </div>
                        <div class="form-check form-switch">
                            <input class="form-check-input col-toggle" type="checkbox" id="colIndex" data-col="5" checked>
                            <label class="form-check-label small" for="colIndex">Index Size</label>
                        </div>
                        <div class="form-check form-switch">
                            <input class="form-check-input col-toggle" type="checkbox" id="colFrag" data-col="6" checked>
                            <label class="form-check-label small" for="colFrag">Fragmentasi</label>
                        </div>
                    </div>
                </div>

                <form method="POST" id="formOptimizeSelected">
                    <div class="mb-3">
                        <button type="submit" name="optimize_selected" class="btn btn-sm btn-primary fw-bold" onclick="return confirm('Optimasi tabel terpilih?');"><i class="fa-solid fa-check-double me-2"></i>Optimize Terpilih</button>
                    </div>
                    <div class="glass-card p-0 overflow-auto">
                        <table class="table table-hover mb-0 align-middle" id="fragTable">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-4" style="width: 40px;">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="selectAllRows">
                                        </div>
                                    </th>
                                    <th>Nama Tabel</th>
                                    <th class="text-center col-engine">Engine</th>
                                    <th class="text-end col-rows">Jumlah Baris</th>
                                    <th class="text-end col-data">Data Size</th>
                                    <th class="text-end col-index">Index Size</th>
                                    <th class="text-end text-danger fw-bold col-frag">Fragmentasi</th>
                                    <th class="text-center pe-4">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $total_fragmented = 0;
                                while($row = mysqli_fetch_assoc($tables_info)) : 
                                    $total_fragmented += $row['data_free'];
                                    $is_fragmented = $row['data_free'] > 0;
                                ?>
                                <tr>
                                    <td class="ps-4">
                                        <div class="form-check">
                                            <input class="form-check-input row-checkbox" type="checkbox" name="selected_tables[]" value="<?= $row['table_name']; ?>">
                                        </div>
                                    </td>
                                    <td class="fw-medium"><i class="fa-solid fa-table border rounded p-1 text-secondary me-2"></i><?= $row['table_name']; ?></td>
                                    <td class="text-center col-engine"><span class="badge bg-light text-dark border"><?= $row['engine']; ?></span></td>
                                    <td class="text-end col-rows"><?= number_format($row['table_rows']); ?></td>
                                    <td class="text-end col-data"><?= formatBytes($row['data_length']); ?></td>
                                    <td class="text-end col-index"><?= formatBytes($row['index_length']); ?></td>
                                    <td class="text-end col-frag <?= $is_fragmented ? 'text-danger fw-bold' : 'text-success'; ?>">
                                        <?= $is_fragmented ? formatBytes($row['data_free']) : '0 B (Optimal)'; ?>
                                    </td>
                                    <td class="text-center pe-4">
                                        <button type="submit" formaction="?action=single" name="optimize_table" value="<?= $row['table_name']; ?>" class="btn btn-sm <?= $is_fragmented ? 'btn-primary' : 'btn-outline-secondary'; ?>" <?= !$is_fragmented ? 'disabled title="Sudah optimal"' : ''; ?> onclick="document.getElementById('singleTableInput').value='<?= $row['table_name']; ?>'; document.getElementById('singleTableForm').submit(); return false;">
                                            <i class="fa-solid fa-hammer me-1"></i> Optimize
                                        </button>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                            <?php if ($total_fragmented > 0): ?>
                            <tfoot class="table-warning">
                                <tr>
                                    <th colspan="6" class="text-end" id="fragFooterLabel">Total Ruang Terbuang (Fragmented):</th>
                                    <th class="text-end text-danger fw-bold col-frag"><?= formatBytes($total_fragmented); ?></th>
                                    <th></th>
                                </tr>
                            </tfoot>
                            <?php endif; ?>
                        </table>
                    </div>
                </form>
                
                <form method="POST" id="singleTableForm" style="display:none;">
                    <input type="hidden" name="table_name" id="singleTableInput">
                    <input type="hidden" name="optimize_table" value="1">
                </form>
            </div>
            <?php endif; ?>

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
