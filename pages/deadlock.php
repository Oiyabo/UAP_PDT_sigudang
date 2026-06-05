<?php
require_once __DIR__ . '/../config/koneksi.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); } 
if (!isset($_SESSION['username'])) { header('Location: login.php'); exit; }
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Simulasi Deadlock - SIGUDANG Premium</title>
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
        .btn-primary-custom {
            background: linear-gradient(135deg, var(--primary), #3b82f6);
            color: white;
            border-radius: 12px;
            border: none;
            padding: 14px 30px;
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
        .thread-box { 
            border: 2px solid #cbd5e1; 
            border-radius: 16px; 
            padding: 24px 15px; 
            background: #ffffff; 
            min-height: 140px; 
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275); 
            box-shadow: 0 10px 25px rgba(0,0,0,0.03);
            position: relative;
            overflow: hidden;
        }
        
        .resource-box { 
            border: 2px dashed #94a3b8; 
            border-radius: 16px; 
            padding: 20px 15px; 
            background: #f8fafc; 
            text-align: center; 
            font-weight: 700; 
            color: #475569;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275); 
        }
        .log-container {
            background: #0f172a;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 15px 30px rgba(0,0,0,0.15);
            height: 100%;
        }
        .log-header {
            background: linear-gradient(to right, #1e293b, #0f172a);
            padding: 12px 20px;
            border-bottom: 1px solid #334155;
            display: flex;
            align-items: center;
        }
        .log-dots {
            display: flex;
            gap: 6px;
            margin-right: 15px;
        }
        
        .log-dot {
            width: 12px;
            height: 12px;
            border-radius: 50%;
        }
        .log-dot.red { background: #ef4444; }
        .log-dot.yellow { background: #eab308; }
        .log-dot.green { background: #22c55e; }
        .log-box { 
            color: #4ade80; 
            font-family: 'Consolas', 'Courier New', monospace; 
            padding: 20px; 
            height: 300px; 
            overflow-y: auto; 
            font-size: 0.9rem;
            line-height: 1.6;
        }
        
        .log-box::-webkit-scrollbar { width: 8px; }
        .log-box::-webkit-scrollbar-thumb { background: #334155; border-radius: 4px; }
        .log-box::-webkit-scrollbar-track { background: transparent; }
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
        <a class="nav-link active" href="deadlock.php"><i class="fa-solid fa-arrows-spin"></i> Deadlock</a>
        <?php endif; ?>
        <a class="nav-link" href="log.php"><i class="fa-solid fa-chart-bar"></i> Log & Analytics</a>
        <?php if ($_SESSION['role'] == 'Admin') : ?>
        <a class="nav-link" href="backup.php"><i class="fa-solid fa-database"></i> Backup DB</a>
        <?php endif; ?>
    </div>
</div>
<div class="main-wrapper">
    <div class="topbar-floating">
        <h5 class="topbar-title">Simulasi Visual Deadlock</h5>
        <div class="user-profile">
            <span><?= $_SESSION['username']; ?></span> 
            <a href="logout.php" class="btn-logout" title="Logout"><i class="fa-solid fa-power-off"></i></a>
        </div>
    </div>
    <div class="card-premium mb-4">
        <div class="card-body p-4 p-md-5 text-center">
            <div style="display:inline-block; background: #e0e7ff; color: var(--primary); padding: 15px; border-radius: 50%; margin-bottom: 20px;">
                <i class="fa-solid fa-arrows-spin fa-2x fa-spin-pulse"></i>
            </div>
            <h4 class="fw-bold mb-3">Simulasikan Kejadian Deadlock</h4>
            <p class="text-secondary mb-4 mx-auto" style="max-width: 600px; line-height: 1.6;">
                Klik tombol di bawah ini untuk melihat secara visual bagaimana dua transaksi dapat saling mengunci (berpotensi menunggu tak berkesudahan) dan menyebabkan sistem me-rollback otomatis untuk mencegah deadlock.
            </p>
            <button id="btnSimulasi" class="btn btn-primary-custom btn-lg">
                <i class="fa-solid fa-play me-2"></i> Jalankan Simulasi
            </button>
        </div>
    </div>
    <div class="row g-4" id="simulasiArea" style="display: none;">
        <div class="col-lg-7">
            <div class="card-premium">
                <div class="card-body p-4 p-md-5">
                    <div class="row text-center mb-5 align-items-center">
                        <div class="col-5">
                            <div id="threadA" class="thread-box">
                                <div style="position:absolute; top:0; left:0; width:100%; height:4px; background: #3b82f6;"></div>
                                <h6 class="fw-bold text-primary mb-3">Transaksi A</h6>
                                <div id="statusA" class="small fw-medium text-secondary">Menunggu instruksi...</div>
                            </div>
                        </div>
                        <div class="col-2 d-flex flex-column justify-content-center">
                            <i class="fa-solid fa-arrow-right-arrow-left fa-2x text-slate-300 opacity-25"></i>
                        </div>
                        <div class="col-5">
                            <div id="threadB" class="thread-box">
                                <div style="position:absolute; top:0; left:0; width:100%; height:4px; background: #ef4444;"></div>
                                <h6 class="fw-bold text-danger mb-3">Transaksi B</h6>
                                <div id="statusB" class="small fw-medium text-secondary">Menunggu instruksi...</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row text-center">
                        <div class="col-5">
                            <div id="res1" class="resource-box">
                                <i class="fa-solid fa-database d-block mb-2 fs-4"></i>
                                Row 1 <br><span class="fw-normal small">(Stok Barang X)</span>
                            </div>
                        </div>
                        <div class="col-2"></div>
                        <div class="col-5">
                            <div id="res2" class="resource-box">
                                <i class="fa-solid fa-database d-block mb-2 fs-4"></i>
                                Row 2 <br><span class="fw-normal small">(Stok Barang Y)</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="log-container">
                <div class="log-header">
                    <div class="log-dots">
                        <div class="log-dot red"></div>
                        <div class="log-dot yellow"></div>
                        <div class="log-dot green"></div>
                    </div>
                    <span class="text-white small fw-bold font-monospace"><i class="fa-solid fa-terminal me-2 opacity-50"></i>System Log Output</span>
                </div>
                <div id="systemLog" class="log-box"></div>
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
        p.style.marginBottom = '6px';
        p.innerHTML = `<span style="color:#64748b;">[${new Date().toLocaleTimeString()}]</span> <span style="color:${isError ? '#f87171' : '#4ade80'};">${isError ? 'ERROR: ' : '> '}</span> ${msg}`;
        systemLog.appendChild(p);
        systemLog.scrollTop = systemLog.scrollHeight;
    }
    function resetSimulasi() {
        systemLog.innerHTML = '';
        statusA.innerHTML = 'Menunggu instruksi...';
        statusB.innerHTML = 'Menunggu instruksi...';
        
        res1.style.borderColor = '#94a3b8';
        res1.style.backgroundColor = '#f8fafc';
        res1.style.color = '#475569';
        
        res2.style.borderColor = '#94a3b8';
        res2.style.backgroundColor = '#f8fafc';
        res2.style.color = '#475569';
        threadA.style.borderColor = '#cbd5e1';
        threadA.style.boxShadow = '0 10px 25px rgba(0,0,0,0.03)';
        threadA.style.transform = 'scale(1)';
        
        threadB.style.borderColor = '#cbd5e1';
        threadB.style.boxShadow = '0 10px 25px rgba(0,0,0,0.03)';
        threadB.style.transform = 'scale(1)';
    }
    const sleep = (ms) => new Promise(resolve => setTimeout(resolve, ms));
    btnSimulasi.addEventListener('click', async function() {
        btnSimulasi.disabled = true;
        btnSimulasi.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-2"></i> Simulasi Berjalan...';
        simulasiArea.style.display = 'flex';
        resetSimulasi();
        addLog('Memulai Simulasi Deadlock...', false);
        await sleep(1000);
        statusA.innerHTML = '<span class="text-primary fw-bold">Mengunci Row 1...</span>';
        addLog('Transaksi A: Menjalankan SELECT ... FOR UPDATE pada Row 1');
        await sleep(1000);
        res1.style.borderColor = '#3b82f6';
        res1.style.backgroundColor = '#eff6ff';
        res1.style.color = '#1d4ed8';
        threadA.style.borderColor = '#3b82f6';
        threadA.style.transform = 'scale(1.05)';
        threadA.style.boxShadow = '0 15px 35px rgba(59, 130, 246, 0.15)';
        statusA.innerHTML = '<span class="text-success fw-bold"><i class="fa-solid fa-lock"></i> Row 1 Terkunci</span>';
        addLog('Transaksi A: Berhasil mengunci Row 1');
        await sleep(1500);
        statusB.innerHTML = '<span class="text-danger fw-bold">Mengunci Row 2...</span>';
        addLog('Transaksi B: Menjalankan SELECT ... FOR UPDATE pada Row 2');
        await sleep(1000);
        res2.style.borderColor = '#ef4444';
        res2.style.backgroundColor = '#fef2f2';
        res2.style.color = '#b91c1c';
        threadB.style.borderColor = '#ef4444';
        threadB.style.transform = 'scale(1.05)';
        threadB.style.boxShadow = '0 15px 35px rgba(239, 68, 68, 0.15)';
        statusB.innerHTML = '<span class="text-success fw-bold"><i class="fa-solid fa-lock"></i> Row 2 Terkunci</span>';
        addLog('Transaksi B: Berhasil mengunci Row 2');
        await sleep(1500);
        statusA.innerHTML = '<span class="text-warning fw-bold"><i class="fa-solid fa-hourglass-half fa-spin"></i> Menunggu Row 2...</span>';
        addLog('Transaksi A: Mencoba mengunci Row 2 (Menunggu Transaksi B...)');
        await sleep(1500);
        statusB.innerHTML = '<span class="text-warning fw-bold"><i class="fa-solid fa-hourglass-half fa-spin"></i> Menunggu Row 1...</span>';
        addLog('Transaksi B: Mencoba mengunci Row 1 (Menunggu Transaksi A...)');
        await sleep(1500);
        addLog('Deadlock detected!', true);
        addLog('DBMS menggagalkan (Rollback) salah satu transaksi untuk memecah kebuntuan.', true);
        
        threadB.style.borderColor = '#f87171';
        threadB.style.backgroundColor = '#fef2f2';
        threadB.style.transform = 'scale(0.95)';
        statusB.innerHTML = '<span class="text-danger fw-bold"><i class="fa-solid fa-triangle-exclamation"></i> Kena Rollback</span>';
        
        res2.style.borderColor = '#3b82f6';
        res2.style.backgroundColor = '#eff6ff';
        res2.style.color = '#1d4ed8';
        statusA.innerHTML = '<span class="text-success fw-bold"><i class="fa-solid fa-check-double"></i> Row 1 & 2 Terkunci <br> &#10145; Commit</span>';
        addLog('Transaksi A: Mendapat Lock Row 2 dan melakukan Commit.');
        btnSimulasi.disabled = false;
        btnSimulasi.innerHTML = '<i class="fa-solid fa-rotate-right me-2"></i> Ulangi Simulasi Deadlock';
    });
</script>
</body>
</html>