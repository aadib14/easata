<?php
session_start();
$conn = new mysqli("localhost", "root", "", "panganasa_db");
if ($conn->connect_error) die("Koneksi gagal: " . $conn->connect_error);

$error = "";

// SIMULASI LOGIN CEPAT DEMO ACCONTS
if (isset($_GET['quick_login'])) {
    $role = $_GET['quick_login'];
    if ($role == 'petani') {
        $_SESSION['user_id'] = 1;
        $_SESSION['nama']    = 'Pak Slamet';
        $_SESSION['role']    = 'petani';
        $_SESSION['foto']    = 'https://images.unsplash.com/photo-1544005313-94ddf0286df2?auto=format&fit=crop&w=100&q=80';
    } else {
        $_SESSION['user_id'] = 2;
        $_SESSION['nama']    = 'Budi Santoso';
        $_SESSION['role']    = 'pendana';
        $_SESSION['foto']    = 'https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?auto=format&fit=crop&w=100&q=80';
    }
    header("Location: dashboard.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email    = $conn->real_escape_string($_POST['email']);
    $password = $_POST['password'];

    $sql = "SELECT * FROM users WHERE email = '$email'";
    $res = $conn->query($sql);

    if ($res && $res->num_rows > 0) {
        $user = $res->fetch_assoc();
        if ($password == $user['password'] || password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['nama']    = $user['nama'];
            $_SESSION['role']    = $user['role'];
            $_SESSION['foto']    = $user['foto'] ?? 'https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?auto=format&fit=crop&w=100&q=80';
            
            header("Location: dashboard.php");
            exit;
        } else {
            $error = "Kata sandi yang Anda masukkan salah.";
        }
    } else {
        $error = "Email tidak terdaftar di sistem EASATA.";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masuk Akun - EASATA</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

    <style>
        :root {
            --pa-primary: #10b981;
            --pa-primary-dark: #059669;
            --pa-dark: #0f172a;
            --pa-bg: #f8fafc;
            --pa-border: #e2e8f0;
        }

        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: var(--pa-bg); color: #1e293b; min-height: 100vh; display: flex; align-items: center; justify-content: center; }

        .brand-icon {
            width: 44px;
            height: 44px;
            background: linear-gradient(135deg, #10b981 0%, #047857 100%);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #ffffff;
            font-size: 22px;
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
        }

        .card-login {
            border: 1px solid var(--pa-border);
            border-radius: 24px;
            background: #ffffff;
            width: 100%;
            max-width: 440px;
        }

        .btn-demo-role {
            border: 1px dashed var(--pa-primary);
            background: rgba(16, 185, 129, 0.05);
            transition: all 0.2s ease;
        }

        .btn-demo-role:hover {
            background: rgba(16, 185, 129, 0.15);
        }
    </style>
</head>
<body>

    <div class="container py-5">
        <div class="card-login p-4 p-md-5 shadow-sm mx-auto">
            
            <!-- Logo EASATA Header -->
            <div class="text-center mb-4">
                <a href="index.php" class="d-inline-flex align-items-center gap-2 text-decoration-none mb-3">
                    <div class="brand-icon"><i class="bi bi-sprout-fill"></i></div>
                    <div class="d-flex flex-column text-start">
                        <span class="fw-extrabold text-dark fs-3 lh-1" style="font-weight: 800;">EAS<span class="text-success">ATA</span></span>
                        <small class="text-muted fw-bold" style="font-size: 8px; letter-spacing: 0.5px;">EASY & SUSTAINABLE ACCESS TO AGRICULTURE</small>
                    </div>
                </a>
                <h5 class="fw-bold text-dark mb-1">Selamat Datang Kembali</h5>
                <p class="text-muted small">Masuk untuk mengakses portal permodalan pertanian.</p>
            </div>

            <?php if($error): ?>
                <div class="alert alert-danger rounded-3 small py-2 px-3 mb-3"><?= $error ?></div>
            <?php endif; ?>

            <!-- Form Login -->
            <form action="" method="POST" class="mb-4">
                <div class="mb-3">
                    <label class="form-label fw-semibold small">Alamat Email</label>
                    <input type="email" name="email" class="form-control py-2" placeholder="nama@email.com" required>
                </div>

                <div class="mb-4">
                    <label class="form-label fw-semibold small">Kata Sandi</label>
                    <input type="password" name="password" class="form-control py-2" placeholder="••••••••" required>
                </div>

                <button type="submit" class="btn btn-success btn-lg w-100 fw-bold rounded-pill py-2 fs-6 shadow-sm mb-2">
                    Masuk Akun
                </button>
            </form>

            <div class="position-relative text-center mb-4">
                <hr class="text-muted">
                <span class="position-absolute top-50 start-50 translate-middle bg-white px-3 text-muted small" style="font-size: 11px;">ATAU DEMO INSTAN</span>
            </div>

            <!-- Tombol Quick Demo Login -->
            <div class="d-flex flex-column gap-2 mb-3">
                <a href="login.php?quick_login=petani" class="btn btn-demo-role text-start p-3 rounded-3 text-decoration-none d-flex align-items-center justify-content-between">
                    <div>
                        <span class="fw-bold text-success d-block mb-0" style="font-size: 13px;"><i class="bi bi-person-badge me-1"></i> Masuk Sebagai Petani Demo</span>
                        <small class="text-muted" style="font-size: 11px;">Akun Pak Slamet (Pengaju Permodalan)</small>
                    </div>
                    <i class="bi bi-arrow-right text-success"></i>
                </a>

                <a href="login.php?quick_login=pendana" class="btn btn-demo-role text-start p-3 rounded-3 text-decoration-none d-flex align-items-center justify-content-between">
                    <div>
                        <span class="fw-bold text-primary d-block mb-0" style="font-size: 13px;"><i class="bi bi-wallet2 me-1"></i> Masuk Sebagai Pendana Demo</span>
                        <small class="text-muted" style="font-size: 11px;">Akun Budi Santoso (Pemberi Modal)</small>
                    </div>
                    <i class="bi bi-arrow-right text-primary"></i>
                </a>
            </div>

            <div class="text-center pt-2 border-top">
                <a href="index.php" class="text-decoration-none text-muted small fw-semibold">
                    <i class="bi bi-arrow-left me-1"></i> Kembali ke Katalog Utama
                </a>
            </div>

        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>