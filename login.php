<?php
session_start();
$conn = new mysqli("localhost", "root", "", "panganasa_db");

$error = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $conn->real_escape_string($_POST['email']);
    $password = $_POST['password'];

    $result = $conn->query("SELECT * FROM users WHERE email = '$email'");
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        
        // Verifikasi password sederhana (pada sistem produksi, gunakan password_verify)
        if ($password === $user['password']) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['nama']    = $user['nama'];
            $_SESSION['role']    = $user['role'];
            $_SESSION['foto']    = $user['foto'];

            header("Location: index.php");
            exit;
        } else {
            $error = "Password yang Anda masukkan salah.";
        }
    } else {
        $error = "Email tidak terdaftar dalam sistem.";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masuk Akun - PanganAsa</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #f8f9fa; color: #2d3748; }
        .card-custom { border: none; border-radius: 16px; background: #ffffff; }
    </style>
</head>
<body class="d-flex align-items-center min-vh-100 py-5">

    <main class="container" style="max-width: 440px;">
        <div class="text-center mb-4">
            <a href="index.php" class="text-decoration-none text-success fs-3 fw-bold">
                <i class="bi bi-sprout-fill"></i> PanganAsa
            </a>
            <p class="text-muted small mt-1">Platform Permodalan In-Kind Pertanian</p>
        </div>

        <div class="card card-custom shadow-sm p-4 p-md-5">
            <h5 class="fw-bold mb-1">Selamat Datang Kembali</h5>
            <p class="text-muted small mb-4">Masuk untuk mengelola pendanaan & lahan garapan.</p>

            <?php if($error): ?>
                <div class="alert alert-danger py-2 small rounded-3 mb-3"><?= $error ?></div>
            <?php endif; ?>

            <form action="" method="POST">
                <div class="mb-3">
                    <label class="form-label fw-semibold small">Alamat Email</label>
                    <input type="email" name="email" class="form-control" placeholder="nama@email.com" required>
                </div>

                <div class="mb-4">
                    <label class="form-label fw-semibold small">Password</label>
                    <input type="password" name="password" class="form-control" placeholder="••••••••" required>
                </div>

                <button type="submit" class="btn btn-success btn-lg w-100 fw-bold rounded-3 py-2 shadow-sm mb-3">
                    Masuk Sekarang
                </button>
            </form>

            <div class="border-top pt-3 text-center">
                <small class="text-muted d-block mb-2">Gunakan Akun Pengujian Demo:</small>
                <div class="bg-light p-2 rounded text-start style='font-size: 11px;'">
                    <strong>Petani:</strong> <code>petani@panganasa.com</code> | <code>12345</code><br>
                    <strong>Pendana:</strong> <code>pendana@panganasa.com</code> | <code>12345</code>
                </div>
            </div>
        </div>
    </main>

</body>
</html>