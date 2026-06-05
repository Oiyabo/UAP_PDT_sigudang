<?php
require_once __DIR__ . '/../config/koneksi.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); } 
if (!isset($_SESSION['username'])) { header('Location: login.php'); exit; }
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Simulasi Deadlock - SIGUDANG</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style> 
        body { font-family: 'Poppins', sans-serif; background-color: #f4f7f6; } 
        .sidebar { background: #111827; min-height: 100vh; color: #9ca3af; } 
        .sidebar .nav-link { color: #9ca3af; padding: 12px 20px; border-radius: 8px; font-weight: 500; margin-bottom: 5px; } 
        .sidebar .nav-link:hover { color: #fff; background: rgba(255,255,255,0.05); } 
        .sidebar .nav-link.active { background: #1f2937; color: #fff; } 
        .topbar { background: #fff; box-shadow: 0 1px 3px 0 rgba(0,0,0,0.1); }
        .thread-box { border: 2px solid #dee2e6; border-radius: 8px; padding: 15px; background: #fff; min-height: 120px; transition: all 0.3s ease; }
        .resource-box { border: 2px dashed #adb5bd; border-radius: 8px; padding: 15px; background: #f8f9fa; text-align: center; font-weight: 600; transition: all 0.3s ease; }
        .log-box { background: #212529; color: #32cd32; font-family: monospace; padding: 15px; border-radius: 8px; height: 200px; overflow-y: auto; }
        .arrow { display: none; font-size: 24px; text-align: center; color: #6c757d; }
    </style>
</head>
<body class="d-flex">
<div class="sidebar d-none d-md-block" style="width: 260px; flex-shrink:0;">
    <div class="p-4 border-bottom border-secondary mb-3"><h5 class="text-white fw-bold m-0"><i class="fa-solid fa-boxes-stacked text-primary me-2"></i> SIGUDANG</h5></div>
    <nav class="nav flex-column px-3">
        <a class="nav-link" href="index.php"><i class="fa-solid fa-gauge-high me-3"></i> Dashboard</a>
        <?php if ($_SESSION['role'] == 'Admin') : ?>
        <a class="nav-link" href="transaction.php"><i class="fa-solid fa-pen-to-square me-3"></i> Transaction</a>
        <a class="nav-link active" href="deadlock.php"><i class="fa-solid fa-arrows-spin me-3"></i> Deadlock</a>
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
        <h5 class="mb-0 fw-bold">Simulasi Visual Deadlock</h5>
        <div><span class="me-3 fw-bold"><?= $_SESSION['username']; ?></span> <a href="logout.php" class="btn btn-sm btn-outline-danger"><i class="fa-solid fa-sign-out-alt"></i></a></div>
    </div>
    <div class="container-fluid px-4 pb-4">
        
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body p-4 text-center">
                <h5 class="fw-bold mb-3"><i class="fa-solid fa-play-circle text-primary me-2"></i>Simulasikan Kejadian Deadlock</h5>
                <p class="text-muted small mb-4">Klik tombol di bawah ini untuk melihat secara visual bagaimana dua transaksi dapat saling mengunci dan menyebabkan deadlock pada database.</p>
                <button id="btnSimulasi" class="btn btn-primary fw-bold px-5 py-2 btn-lg">Jalankan Simulasi Deadlock</button>
            </div>
        </div>

        <div class="row" id="simulasiArea" style="display: none;">
            <div class="col-md-8">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body p-4">
                        <div class="row text-center mb-4">
                            <div class="col-5">
                                <div id="threadA" class="thread-box">
                                    <h6 class="fw-bold text-primary">Transaksi A</h6>
                                    <div id="statusA" class="small text-muted mt-2">Menunggu instruksi...</div>
                                </div>
                            </div>
                            <div class="col-2 d-flex flex-column justify-content-center">
                                <i class="fa-solid fa-arrow-right-arrow-left fa-2x text-muted"></i>
                            </div>
                            <div class="col-5">
                                <div id="threadB" class="thread-box">
                                    <h6 class="fw-bold text-danger">Transaksi B</h6>
                                    <div id="statusB" class="small text-muted mt-2">Menunggu instruksi...</div>
                                </div>
                            </div>
                        </div>
                        <div class="row text-center">
                            <div class="col-5">
                                <div id="res1" class="resource-box">
                                    <i class="fa-solid fa-database me-2"></i>Row 1 (Stok Barang X)
                                </div>
                            </div>
                            <div class="col-2"></div>
                            <div class="col-5">
                                <div id="res2" class="resource-box">
                                    <i class="fa-solid fa-database me-2"></i>Row 2 (Stok Barang Y)
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body p-0">
                        <div class="bg-dark text-white p-2 rounded-top fw-bold small"><i class="fa-solid fa-terminal me-2"></i>System Log</div>
                        <div id="systemLog" class="log-box rounded-bottom rounded-0"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    const btnSimulasi = document.getElementById('btnSimulasi');
    const simulasiArea = document.getElementById('simulasiArea');
    const statusA = document.getElementById('statusA');
    const statusB = document.getElementById('statusB');
    const res1 = document.getElementById('res1');
    const res2 = document.getElementById('res2');
    const threadA = document.getElementById('threadA');
    const threadB = document.getElementById('threadB');
    const systemLog = document.getElementById('systemLog');

    function addLog(msg, isError = false) {
        const p = document.createElement('div');
        p.innerHTML = `> ${msg}`;
        if(isError) p.style.color = '#ff4d4d';
        systemLog.appendChild(p);
        systemLog.scrollTop = systemLog.scrollHeight;
    }

    function resetSimulasi() {
        systemLog.innerHTML = '';
        statusA.innerHTML = 'Menunggu instruksi...';
        statusB.innerHTML = 'Menunggu instruksi...';
        
        res1.style.borderColor = '#adb5bd';
        res1.style.backgroundColor = '#f8f9fa';
        res1.style.color = '#212529';
        
        res2.style.borderColor = '#adb5bd';
        res2.style.backgroundColor = '#f8f9fa';
        res2.style.color = '#212529';

        threadA.style.borderColor = '#dee2e6';
        threadB.style.borderColor = '#dee2e6';
    }

    const sleep = (ms) => new Promise(resolve => setTimeout(resolve, ms));

    btnSimulasi.addEventListener('click', async function() {
        btnSimulasi.disabled = true;
        btnSimulasi.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-2"></i>Simulasi Berjalan...';
        simulasiArea.style.display = 'flex';
        resetSimulasi();

        addLog('Memulai Simulasi Deadlock...');
        await sleep(1000);

        statusA.innerHTML = '<span class="text-primary fw-bold">Mengunci Row 1...</span>';
        addLog('Transaksi A: Menjalankan SELECT ... FOR UPDATE pada Row 1');
        await sleep(1000);
        res1.style.borderColor = '#0d6efd';
        res1.style.backgroundColor = '#cfe2ff';
        res1.style.color = '#084298';
        threadA.style.borderColor = '#0d6efd';
        statusA.innerHTML = '<span class="text-success fw-bold"><i class="fa-solid fa-lock"></i> Row 1 Terkunci</span>';
        addLog('Transaksi A: Berhasil mengunci Row 1');
        await sleep(1500);

        statusB.innerHTML = '<span class="text-danger fw-bold">Mengunci Row 2...</span>';
        addLog('Transaksi B: Menjalankan SELECT ... FOR UPDATE pada Row 2');
        await sleep(1000);
        res2.style.borderColor = '#dc3545';
        res2.style.backgroundColor = '#f8d7da';
        res2.style.color = '#842029';
        threadB.style.borderColor = '#dc3545';
        statusB.innerHTML = '<span class="text-success fw-bold"><i class="fa-solid fa-lock"></i> Row 2 Terkunci</span>';
        addLog('Transaksi B: Berhasil mengunci Row 2');
        await sleep(1500);

        statusA.innerHTML = '<span class="text-warning fw-bold"><i class="fa-solid fa-hourglass-half"></i> Menunggu Row 2...</span>';
        addLog('Transaksi A: Mencoba mengunci Row 2 (Menunggu Transaksi B...)');
        await sleep(1500);

        statusB.innerHTML = '<span class="text-warning fw-bold"><i class="fa-solid fa-hourglass-half"></i> Menunggu Row 1...</span>';
        addLog('Transaksi B: Mencoba mengunci Row 1 (Menunggu Transaksi A...)');
        await sleep(1500);

        addLog('SYSTEM ERROR: Deadlock detected!', true);
        addLog('DBMS menggagalkan (Rollback) salah satu transaksi untuk memecah kebuntuan.', true);
        
        threadB.style.borderColor = '#dc3545';
        threadB.style.backgroundColor = '#f8d7da';
        statusB.innerHTML = '<span class="text-danger fw-bold"><i class="fa-solid fa-triangle-exclamation"></i> Kena Rollback (Deadlock)</span>';
        
        res2.style.borderColor = '#0d6efd';
        res2.style.backgroundColor = '#cfe2ff';
        res2.style.color = '#084298';
        statusA.innerHTML = '<span class="text-success fw-bold"><i class="fa-solid fa-check-double"></i> Row 1 & 2 Terkunci -> Commit</span>';
        addLog('Transaksi A: Mendapat Lock Row 2 dan melakukan Commit.');

        btnSimulasi.disabled = false;
        btnSimulasi.innerHTML = 'Ulangi Simulasi Deadlock';
    });
</script>
</body>
</html>