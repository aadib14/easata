<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$conn = new mysqli("localhost", "root", "", "panganasa_db");
if ($conn->connect_error) die("Koneksi gagal: " . $conn->connect_error);

$user_id = $_SESSION['user_id'];
$role    = $_SESSION['role'];
$nama    = $_SESSION['nama'];

// Data Khusus PETANI
if ($role == 'petani') {
    $sql = "SELECT c.*, 
            IFNULL(SUM(DISTINCT s.total_harga), 0) AS target_dana,
            IFNULL((SELECT SUM(nominal) FROM donations WHERE campaign_id = c.id), 0) AS terkumpul
            FROM campaigns c 
            LEFT JOIN saprodi s ON c.id = s.campaign_id 
            WHERE c.petani_id = $user_id
            GROUP BY c.id";
    $proyek_petani = $conn->query($sql);
    
    // Stats Petani
    $total_lahan = $proyek_petani->num_rows;
    $stat_terkumpul = $conn->query("SELECT SUM(d.nominal) AS total FROM donations d JOIN campaigns c ON d.campaign_id = c.id WHERE c.petani_id = $user_id")->fetch_assoc()['total'] ?? 0;
} 

// Data Khusus PENDANA
if ($role == 'pendana') {
    $sql_donasi = "SELECT d.*, c.judul_proyek, c.lokasi, c.kategori 
                  FROM donations d 
                  JOIN campaigns c ON d.campaign_id = c.id 
                  ORDER BY d.id DESC";
    $riwayat_donasi = $conn->query($sql_donasi);

    $total_donasi_query = $conn->query("SELECT SUM(nominal) AS total FROM donations");
    $total_donasi = $total_donasi_query->fetch_assoc()['total'] ?? 0;
    
    $total_proyek_dibantu = $conn->query("SELECT COUNT(DISTINCT campaign_id) AS total FROM donations")->fetch_assoc()['total'] ?? 0;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard <?= ucfirst($role) ?> - PanganAsa</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

    <style>
        :root {
            --pa-primary: #10b981;
            --pa-primary-dark: #059669;
            --pa-dark: #0f172a;
            --pa-gray-bg: #f8fafc;
            --pa-border: #e2e8f0;
        }

        body { 
            font-family: 'Plus Jakarta Sans', sans-serif; 
            background-color: var(--pa-gray-bg); 
            color: #1e293b; 
        }

        .navbar-panganasa {
            background-color: #ffffff;
            border-bottom: 1px solid var(--pa-border);
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.03);
        }

        .brand-logo-icon {
            width: 38px;
            height: 38px;
            background: linear-gradient(135deg, #10b981 0%, #047857 100%);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #ffffff;
            font-size: 20px;
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
        }

        .card-custom { 
            border: 1px solid var(--pa-border); 
            border-radius: 20px; 
            background: #ffffff; 
        }

        .progress-custom {
            height: 8px;
            border-radius: 10px;
            background-color: #f1f5f9;
        }

        .stat-widget {
            background: #ffffff;
            border: 1px solid var(--pa-border);
            border-radius: 16px;
            padding: 24px;
            transition: all 0.2s ease;
        }

        .stat-widget:hover {
            border-color: #cbd5e1;
            box-shadow: 0 10px 20px rgba(0,0,0,0.03);
        }

        .avatar-user-lg {
            width: 64px;
            height: 64px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid #ffffff;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
        }
    </style>
</head>
<body>

    <!-- NAVBAR RESMI PANGANASA -->
    <nav class="navbar navbar-expand-lg navbar-panganasa sticky-top py-2">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center gap-2" href="index.php">
                <div class="brand-logo-icon">
                    <i class="bi bi-sprout-fill"></i>
                </div>
                <div class="d-flex flex-column">
                    <span class="fw-extrabold text-dark fs-4 lh-1" style="font-weight: 800;">Pangan<span class="text-success">Asa</span></span>
                    <small class="text-muted fw-bold" style="font-size: 9px; letter-spacing: 1px;">FINTECH AGRI CROWDFUNDING</small>
                </div>
            </a>

            <div class="d-flex align-items-center gap-2">
                <a href="index.php" class="btn btn-light btn-sm fw-semibold rounded-pill px-3 border me-1">
                    <i class="bi bi-grid me-1"></i> Katalog Lahan
                </a>
                <a href="logout.php" class="btn btn-outline-danger btn-sm fw-bold rounded-pill px-3">
                    <i class="bi bi-box-arrow-right me-1"></i> Keluar
                </a>
            </div>
        </div>
    </nav>

    <main class="container my-5">
        
        <!-- HEADER USER CARD -->
        <div class="card-custom p-4 shadow-sm mb-4">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                <div class="d-flex align-items-center gap-3">
                    <img src="<?= $_SESSION['foto'] ?>" class="avatar-user-lg">
                    <div>
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <h4 class="fw-bold text-dark mb-0"><?= $nama ?></h4>
                            <span class="badge bg-success bg-opacity-10 text-success text-capitalize fw-bold px-3 py-1 rounded-pill" style="font-size: 11px;">
                                <i class="bi bi-patch-check-fill me-1"></i><?= $role ?> Terverifikasi
                            </span>
                        </div>
                        <small class="text-muted">ID Pengguna: #PA-USER-00<?= $user_id ?> | Status Akun: Aktif & Terdaftar Lapangan</small>
                    </div>
                </div>

                <?php if($role == 'petani'): ?>
                    <a href="tambah_proyek.php" class="btn btn-success fw-bold rounded-pill px-4 py-2 shadow-sm">
                        <i class="bi bi-plus-lg me-1"></i> Pengajuan Lahan Baru
                    </a>
                <?php else: ?>
                    <a href="index.php" class="btn btn-success fw-bold rounded-pill px-4 py-2 shadow-sm">
                        <i class="bi bi-heart-fill me-1"></i> Danai Lahan Lainnya
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <!-- ==================== DASHBOARD KHUSUS PETANI ==================== -->
        <?php if($role == 'petani'): ?>
            
            <!-- Ringkasan Statistik Petani -->
            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <div class="stat-widget shadow-sm">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <small class="text-muted fw-bold text-uppercase" style="font-size: 11px;">Total Permodalan Terkumpul</small>
                            <span class="badge bg-success bg-opacity-10 text-success rounded-circle p-2"><i class="bi bi-cash-stack"></i></span>
                        </div>
                        <h3 class="fw-extrabold text-success mb-0">Rp <?= number_format($stat_terkumpul, 0, ',', '.') ?></h3>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-widget shadow-sm">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <small class="text-muted fw-bold text-uppercase" style="font-size: 11px;">Total Lahan Garapan</small>
                            <span class="badge bg-primary bg-opacity-10 text-primary rounded-circle p-2"><i class="bi bi-journals"></i></span>
                        </div>
                        <h3 class="fw-extrabold text-dark mb-0"><?= $total_lahan ?> Proyek Aktif</h3>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-widget shadow-sm">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <small class="text-muted fw-bold text-uppercase" style="font-size: 11px;">Skema Penyaluran Saprodi</small>
                            <span class="badge bg-warning bg-opacity-10 text-warning rounded-circle p-2"><i class="bi bi-box-seam"></i></span>
                        </div>
                        <h3 class="fw-extrabold text-dark mb-0">100% In-Kind</h3>
                    </div>
                </div>
            </div>

            <!-- Daftar Proyek Lahan Petani -->
            <h5 class="fw-bold text-dark mb-3">Lahan & Permodalan Saprodi Saya</h5>
            <div class="row g-4">
                <?php if($proyek_petani->num_rows > 0): ?>
                    <?php 
                    $proyek_petani->data_seek(0);
                    while($p = $proyek_petani->fetch_assoc()): 
                        $persen = ($p['target_dana'] > 0) ? min(100, round(($p['terkumpul'] / $p['target_dana']) * 100)) : 0;
                    ?>
                    <div class="col-md-6">
                        <div class="card-custom p-4 shadow-sm h-100 d-flex flex-column">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="badge bg-light text-dark border fw-semibold"><?= $p['kategori'] ?></span>
                                <span class="badge bg-success text-uppercase" style="font-size: 10px;"><?= $p['status'] ?></span>
                            </div>
                            <h6 class="fw-bold text-dark mb-3"><?= $p['judul_proyek'] ?></h6>
                            
                            <div class="mt-auto">
                                <div class="d-flex justify-content-between small text-muted mb-1">
                                    <span>Terkumpul: <strong class="text-success">Rp <?= number_format($p['terkumpul'], 0, ',', '.') ?></strong></span>
                                    <span>Target: <strong>Rp <?= number_format($p['target_dana'], 0, ',', '.') ?></strong></span>
                                </div>
                                
                                <div class="progress progress-custom mb-3">
                                    <div class="progress-bar bg-success" style="width: <?= $persen ?>%"></div>
                                </div>

                                <a href="transparansi.php?id=<?= $p['id'] ?>" class="btn btn-outline-success btn-sm w-100 fw-bold rounded-pill py-2">
                                    <i class="bi bi-truck me-1"></i> Pantau Status Logistik & BAST
                                </a>
                            </div>
                        </div>
                    </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="col-12 text-center py-5 bg-white rounded-4 border">
                        <i class="bi bi-sprout fs-1 text-muted mb-2 d-block"></i>
                        <p class="text-muted mb-3">Anda belum memiliki pengajuan permodalan lahan aktif.</p>
                        <a href="tambah_proyek.php" class="btn btn-success fw-bold rounded-pill px-4">Ajukan Permodalan Sekarang</a>
                    </div>
                <?php endif; ?>
            </div>

        <?php endif; ?>

        <!-- ==================== DASHBOARD KHUSUS PENDANA ==================== -->
        <?php if($role == 'pendana'): ?>
            
            <!-- Ringkasan Statistik Pendana -->
            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <div class="stat-widget shadow-sm">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <small class="text-muted fw-bold text-uppercase" style="font-size: 11px;">Total Kontribusi Pendanaan</small>
                            <span class="badge bg-success bg-opacity-10 text-success rounded-circle p-2"><i class="bi bi-wallet2"></i></span>
                        </div>
                        <h3 class="fw-extrabold text-success mb-0">Rp <?= number_format($total_donasi, 0, ',', '.') ?></h3>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-widget shadow-sm">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <small class="text-muted fw-bold text-uppercase" style="font-size: 11px;">Jumlah Lahan Dibantu</small>
                            <span class="badge bg-primary bg-opacity-10 text-primary rounded-circle p-2"><i class="bi bi-geo-alt"></i></span>
                        </div>
                        <h3 class="fw-extrabold text-dark mb-0"><?= $total_proyek_dibantu ?> Lahan Petani</h3>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-widget shadow-sm">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <small class="text-muted fw-bold text-uppercase" style="font-size: 11px;">Model Penyaluran</small>
                            <span class="badge bg-info bg-opacity-10 text-info rounded-circle p-2"><i class="bi bi-shield-check"></i></span>
                        </div>
                        <h3 class="fw-extrabold text-dark mb-0">Direct Supplier</h3>
                    </div>
                </div>
            </div>

            <!-- Tabel Riwayat Portofolio Pendanaan -->
            <h5 class="fw-bold text-dark mb-3">Riwayat Portofolio Pendanaan Saya</h5>
            <div class="card-custom p-4 shadow-sm">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr class="small text-uppercase text-muted" style="font-size: 11px;">
                                <th>Proyek Lahan Tanam</th>
                                <th>Tanggal Transaksi</th>
                                <th>Nominal Disalurkan</th>
                                <th class="text-end">Dokumen Audit</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if($riwayat_donasi->num_rows > 0): ?>
                                <?php while($d = $riwayat_donasi->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <span class="fw-bold text-dark d-block"><?= $d['judul_proyek'] ?></span>
                                        <small class="text-muted" style="font-size: 11px;"><i class="bi bi-geo-alt me-1 text-danger"></i><?= $d['lokasi'] ?></small>
                                    </td>
                                    <td class="small text-muted"><?= date('d M Y, H:i', strtotime($d['tanggal'])) ?> WIB</td>
                                    <td class="fw-extrabold text-success">Rp <?= number_format($d['nominal'], 0, ',', '.') ?></td>
                                    <td class="text-end">
                                        <a href="transparansi.php?id=<?= $d['campaign_id'] ?>" class="btn btn-outline-success btn-sm rounded-pill px-3 fw-semibold">
                                            <i class="bi bi-file-earmark-check me-1"></i> Resi & BAST
                                        </a>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="text-center py-4 text-muted">Belum ada riwayat pendanaan. Mari bantu petani pertama Anda di katalog!</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        <?php endif; ?>

    </main>

    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>