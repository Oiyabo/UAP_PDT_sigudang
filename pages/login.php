<?php
require_once __DIR__ . '/../config/koneksi.php';

if (session_status() === PHP_SESSION_NONE) { session_start(); }

if (isset($_SESSION['username'])) {
    header('Location: index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = mysqli_real_escape_string($conn, $_POST['password']);
    
    $query = "SELECT * FROM users WHERE username = '$username' AND password = '$password'";
    $result = mysqli_query($conn, $query);
    
    if (mysqli_num_rows($result) === 1) {
        $user = mysqli_fetch_assoc($result);
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];
        header('Location: index.php');
        exit;
    } else {
        $error = 'Username atau password salah!';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - SIGUDANG Premium</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style> 
        body { 
            font-family: 'Plus Jakarta Sans', sans-serif; 
            background-color: #0f172a;
            background-image: 
                radial-gradient(at 0% 0%, hsla(253,16%,7%,1) 0, transparent 50%), 
                radial-gradient(at 50% 0%, hsla(225,39%,30%,1) 0, transparent 50%), 
                radial-gradient(at 100% 0%, hsla(339,49%,30%,1) 0, transparent 50%);
            height: 100vh; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            margin: 0;
            overflow: hidden;
        } 
        
        .shape {
            position: absolute;
            filter: blur(60px);
            z-index: 0;
            animation: float 10s ease-in-out infinite alternate;
        }
        .shape-1 {
            width: 400px; height: 400px;
            background: rgba(56, 189, 248, 0.4);
            top: -100px; left: -100px;
            border-radius: 40% 60% 70% 30% / 40% 50% 60% 50%;
        }
        .shape-2 {
            width: 500px; height: 500px;
            background: rgba(129, 140, 248, 0.4);
            bottom: -150px; right: -100px;
            border-radius: 60% 40% 30% 70% / 60% 30% 70% 40%;
            animation-delay: -5s;
        }
        @keyframes float {
            0% { transform: translateY(0) rotate(0deg) scale(1); }
            100% { transform: translateY(50px) rotate(20deg) scale(1.1); }
        }
        .login-wrapper {
            position: relative;
            z-index: 10;
            width: 100%;
            max-width: 450px;
            padding: 20px;
        }
        .glass-card { 
            background: rgba(255, 255, 255, 0.03); 
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-radius: 24px; 
            border: 1px solid rgba(255, 255, 255, 0.1); 
            box-shadow: 0 25px 50px -12px rgba(0,0,0,0.5); 
            padding: 3rem 2.5rem; 
            transition: transform 0.4s ease, box-shadow 0.4s ease;
            color: #fff;
        }
        .glass-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 30px 60px -15px rgba(0,0,0,0.6);
            border: 1px solid rgba(255, 255, 255, 0.2); 
        }
        .logo-icon {
            font-size: 2.5rem;
            background: linear-gradient(135deg, #38bdf8, #818cf8);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 10px;
        }
        .form-control {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: #fff;
            border-radius: 12px;
            padding: 0.8rem 1.2rem;
            transition: all 0.3s;
        }
        .form-control:focus {
            background: rgba(255, 255, 255, 0.1);
            border-color: #38bdf8;
            box-shadow: 0 0 0 4px rgba(56, 189, 248, 0.15);
            color: #fff;
        }
        
        .form-control::placeholder {
            color: rgba(255,255,255,0.4);
        }
        .form-label {
            font-weight: 500;
            letter-spacing: 0.5px;
            font-size: 0.85rem;
            color: #94a3b8;
            text-transform: uppercase;
        }
        .btn-login {
            background: linear-gradient(135deg, #3b82f6, #4f46e5);
            border: none;
            border-radius: 12px;
            padding: 0.8rem;
            font-weight: 700;
            letter-spacing: 1px;
            color: white;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px -5px rgba(79, 70, 229, 0.5);
            color: white;
        }
        
        .alert-danger {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: #fca5a5;
            border-radius: 12px;
        }
    </style>
</head>
<body>
    <div class="shape shape-1"></div>
    <div class="shape shape-2"></div>
    <div class="login-wrapper">
        <div class="glass-card">
            <div class="text-center mb-5">
                <i class="fa-solid fa-boxes-stacked logo-icon"></i>
                <h3 class="fw-bold mb-1" style="letter-spacing: -0.5px;">SI GUDANG</h3>
                <p class="text-secondary mb-0" style="color: #94a3b8 !important;">Enterprise Warehouse System</p>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-danger py-3 small d-flex align-items-center">
                    <i class="fa-solid fa-circle-exclamation me-2"></i> <?= $error ?>
                </div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="mb-4">
                    <label class="form-label">Username</label>
                    <input type="text" name="username" class="form-control" placeholder="Enter your username" required autofocus>
                </div>
                <div class="mb-5">
                    <label class="form-label">Password</label>
                    <input type="password" name="password" class="form-control" placeholder="••••••••" required>
                </div>
                <button type="submit" class="btn btn-login w-100">
                    SIGN IN <i class="fa-solid fa-arrow-right ms-2"></i>
                </button>
            </form>
        </div>
    </div>
</body>
</html>