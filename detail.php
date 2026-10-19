<?php
session_start();
$conn = new mysqli("localhost", "root", "", "panganasa_db");
if ($conn->connect_error) die("Koneksi gagal: " . $conn->connect_error);

if (!isset($_GET['id'])) {
    header("Location: index.php");
    exit;
}

$id = (int)$_GET['id'];

// Ambil Detail Kampanye & Petani
$sql_campaign = "SELECT c.*, u.nama AS nama_petani, u.foto AS foto_petani_user 
                 FROM campaigns c 
                 LEFT JOIN users u ON c.petani_id = u.id 
                 WHERE c.id = $id";
$res_campaign = $conn->query($sql_campaign);

if ($res_campaign->num_rows == 0) {
    die("Proyek permodalan tidak ditemukan.");
}

$kampanye = $res_campaign->fetch_assoc();

// Ambil Daftar Barang Saprodi
$saprodi_list = $conn->query("SELECT * FROM saprodi WHERE campaign_id = $id");

// Hitung Total Target Saprodi
$target_query = $conn->query("SELECT SUM(total_harga) AS total FROM saprodi WHERE campaign_id = $id");
$target_dana = $target_query->fetch_assoc()['total'] ?? 0;

// Hitung Total Dana Terkumpul dari Tabel Donations
$terkumpul_query = $conn->query("SELECT SUM(nominal) AS total FROM donations WHERE campaign_id = $id");
$terkumpul = $terkumpul_query->fetch_assoc()['total'] ?? 0;

// Hitung Persentase
$persen = ($target_dana > 0) ? min(100, round(($terkumpul / $target_dana) * 100)) : 0;

// Ambil Riwayat Pendana
$donatur_list = $conn->query("SELECT * FROM donations WHERE campaign_id = $id ORDER BY id DESC");

$is_blocked_petani = isset($_GET['error']) && $_GET['error'] == 'petani_blocked';
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $kampanye['judul_proyek'] ?> - EASATA</title>
    
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

        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: var(--pa-bg); color: #1e293b; }
        .card-custom { border: 1px solid var(--pa-border); border-radius: 20px; background: #ffffff; }
        .sticky-widget { position: sticky; top: 90px; }
        .progress-custom { height: 10px; border-radius: 10px; background-color: #f1f5f9; }
        .nav-tabs .nav-link { color: #64748b; font-weight: 600; border: none; border-bottom: 3px solid transparent; }
        .nav-tabs .nav-link.active { color: #10b981; border-bottom-color: #10b981; background: transparent; }
        .brand-icon {
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
    </style>
</head>
<body>

    <!-- NAVBAR RESMI EASATA -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom sticky-top py-2">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center gap-2" href="index.php">
                <div class="brand-icon"><i class="bi bi-sprout-fill"></i></div>
                <div class="d-flex flex-column">
                    <span class="fw-extrabold text-dark fs-4 lh-1" style="font-weight: 800;">EAS<span class="text-success">ATA</span></span>
                    <small class="text-muted fw-bold" style="font-size: 8px; letter-spacing: 0.5px;">EASY & SUSTAINABLE ACCESS TO AGRICULTURE</small>
                </div>
            </a>
            <a href="index.php" class="btn btn-outline-secondary btn-sm fw-bold rounded-pill px-3">
                <i class="bi bi-arrow-left me-1"></i> Kembali ke Katalog
            </a>
        </div>
    </nav>

    <main class="container my-4">
        
        <?php if($is_blocked_petani): ?>
            <!-- Alert Peringatan Jika Petani Coba Mendanai -->
            <div class="alert alert-warning border-0 shadow-sm p-3 rounded-4 mb-4 d-flex align-items-center gap-3">
                <i class="bi bi-exclamation-triangle-fill fs-3 text-warning"></i>
                <div>
                    <h6 class="fw-bold mb-0 text-dark">Akses Dibatasi Peran Akun</h6>
                    <small class="text-muted">Akun Anda terdaftar sebagai <strong>Petani (Pengaju Lahan)</strong>. Fitur penyaluran modal hanya dapat dilakukan oleh akun dengan peran <strong>Pendana</strong>.</small>
                </div>
            </div>
        <?php endif; ?>

        <div class="row g-4">
            
            <!-- KOLOM KIRI: DETIL KAMPANYE -->
            <div class="col-lg-8">
                
                <h3 class="fw-extrabold text-dark mb-3"><?= $kampanye['judul_proyek'] ?></h3>

                <div class="card-custom overflow-hidden mb-4 shadow-sm">
                    <img src="https://images.unsplash.com/photo-1595841696677-6489ff3f8cd1?auto=format&fit=crop&w=1000&q=80" alt="Lahan Garapan" class="img-fluid w-100" style="max-height: 400px; object-fit: cover;">
                </div>

                <!-- Profile Petani -->
                <div class="card-custom p-3 p-md-4 mb-4 shadow-sm d-flex align-items-center justify-content-between flex-wrap gap-3">
                    <div class="d-flex align-items-center gap-3">
                        <img src="<?= $kampanye['foto_petani'] ?? 'https://images.unsplash.com/photo-1544005313-94ddf0286df2?auto=format&fit=crop&w=100&q=80' ?>" class="rounded-circle border" style="width: 50px; height: 50px; object-fit: cover;">
                        <div>
                            <div class="d-flex align-items-center gap-2">
                                <h6 class="fw-bold mb-0"><?= $kampanye['nama_petani'] ?? 'Pak Slamet' ?></h6>
                                <span class="badge bg-primary bg-opacity-10 text-primary small"><i class="bi bi-patch-check-fill"></i> Terverifikasi</span>
                            </div>
                            <small class="text-muted"><i class="bi bi-geo-alt-fill text-danger me-1"></i><?= $kampanye['lokasi'] ?? 'Jember' ?> | Luas: <?= $kampanye['luas_lahan'] ?? '1 Hektar' ?></small>
                        </div>
                    </div>
                    <span class="badge bg-success bg-opacity-10 text-success fw-bold px-3 py-2 rounded-pill">
                        Pengaju Lahan
                    </span>
                </div>

                <!-- TAB NAVIGASI -->
                <div class="card-custom p-4 shadow-sm">
                    <ul class="nav nav-tabs mb-4" id="detailTab" role="tablist">
                        <li class="nav-item">
                            <button class="nav-link active" id="saprodi-tab" data-bs-toggle="tab" data-bs-target="#saprodi" type="button"><i class="bi bi-box-seam me-2"></i>Paket Saprodi Fisik</button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link" id="cerita-tab" data-bs-toggle="tab" data-bs-target="#cerita" type="button"><i class="bi bi-journal-text me-2"></i>Cerita Lahan</button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link" id="donatur-tab" data-bs-toggle="tab" data-bs-target="#donatur" type="button"><i class="bi bi-people me-2"></i>Pendana (<?= $donatur_list->num_rows ?>)</button>
                        </li>
                    </ul>

                    <div class="tab-content" id="detailTabContent">
                        
                        <div class="tab-pane fade show active" id="saprodi" role="tabpanel">
                            <div class="alert alert-success bg-success bg-opacity-10 border-0 text-success small mb-3">
                                <i class="bi bi-info-circle-fill me-1"></i> Dana yang terkumpul akan langsung dikonversi menjadi item barang berikut dan dikirim oleh KUD Mitra ke lokasi lahan.
                            </div>
                            
                            <div class="table-responsive">
                                <table class="table align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Nama Item Saprodi</th>
                                            <th>Jumlah</th>
                                            <th class="text-end">Total Harga</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if($saprodi_list->num_rows > 0): ?>
                                            <?php while($s = $saprodi_list->fetch_assoc()): ?>
                                            <tr>
                                                <td class="fw-semibold text-dark">
                                                    <i class="bi bi-check-circle-fill text-success me-2"></i><?= $s['nama_barang'] ?>
                                                </td>
                                                <td><?= $s['jumlah'] ?> <?= $s['satuan'] ?></td>
                                                <td class="text-end fw-bold text-success">Rp <?= number_format($s['total_harga'], 0, ',', '.') ?></td>
                                            </tr>
                                            <?php endwhile; ?>
                                        <?php endif; ?>
                                    </tbody>
                                    <tfoot class="table-light border-top">
                                        <tr>
                                            <th colspan="2" class="fw-bold">Total Kebutuhan Paket</th>
                                            <th class="text-end fw-extrabold text-success fs-6">Rp <?= number_format($target_dana, 0, ',', '.') ?></th>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>

                        <div class="tab-pane fade" id="cerita" role="tabpanel">
                            <p class="lh-lg text-muted"><?= nl2br($kampanye['deskripsi'] ?? 'Lahan pertanian ini membutuhkan permodalan sarana produksi fisik berupa pupuk dan benih terverifikasi untuk mempersiapkan musim tanam baru.') ?></p>
                        </div>

                        <div class="tab-pane fade" id="donatur" role="tabpanel">
                            <ul class="list-group list-group-flush">
                                <?php if($donatur_list->num_rows > 0): ?>
                                    <?php while($d = $donatur_list->fetch_assoc()): ?>
                                    <li class="list-group-item d-flex justify-content-between align-items-center px-0 py-3">
                                        <div class="d-flex align-items-center gap-3">
                                            <div class="bg-success bg-opacity-10 text-success p-2 rounded-circle">
                                                <i class="bi bi-heart-fill"></i>
                                            </div>
                                            <div>
                                                <h6 class="fw-bold mb-0 text-dark"><?= $d['nama_donatur'] ?></h6>
                                                <small class="text-muted" style="font-size: 11px;"><?= date('d M Y, H:i', strtotime($d['tanggal'])) ?></small>
                                            </div>
                                        </div>
                                        <span class="fw-bold text-success">Rp <?= number_format($d['nominal'], 0, ',', '.') ?></span>
                                    </li>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <li class="list-group-item text-center text-muted py-4">Belum ada pendanaan disalurkan. Jadilah pendana pertama!</li>
                                <?php endif; ?>
                            </ul>
                        </div>

                    </div>
                </div>

            </div>

            <!-- KOLOM KANAN: WIDGET ACTION SESUAI ROLE -->
            <div class="col-lg-4">
                <div class="card-custom p-4 shadow-sm sticky-widget">
                    
                    <small class="text-muted d-block mb-1 font-monospace text-uppercase" style="font-size: 11px;">Terkumpul</small>
                    <h3 class="fw-extrabold text-success mb-2">Rp <?= number_format($terkumpul, 0, ',', '.') ?></h3>
                    
                    <div class="d-flex justify-content-between small text-muted mb-2">
                        <span>Target: <strong>Rp <?= number_format($target_dana, 0, ',', '.') ?></strong></span>
                        <span class="fw-bold text-dark"><?= $persen ?>%</span>
                    </div>

                    <div class="progress progress-custom mb-4">
                        <div class="progress-bar bg-success" style="width: <?= $persen ?>%"></div>
                    </div>

                    <div class="d-flex justify-content-between border-bottom pb-3 mb-4 text-center">
                        <div>
                            <small class="text-muted d-block" style="font-size: 11px;">Sisa Hari</small>
                            <span class="fw-bold text-dark"><?= $kampanye['sisa_hari'] ?? 14 ?> Hari</span>
                        </div>
                        <div class="border-start px-3">
                            <small class="text-muted d-block" style="font-size: 11px;">Skema Penyaluran</small>
                            <span class="fw-bold text-success">100% In-Kind</span>
                        </div>
                    </div>

                    <!-- KONDISI TOMBOL BERDASARKAN ROLE -->
                    <?php if(isset($_SESSION['role']) && $_SESSION['role'] === 'petani'): ?>
                        
                        <div class="bg-light p-3 rounded-3 border text-center mb-3">
                            <small class="text-muted d-block mb-1"><i class="bi bi-info-circle text-primary me-1"></i> Mode Akun Petani</small>
                            <span class="fw-bold text-dark small">Hanya akun Pendana yang dapat menyalurkan modal.</span>
                        </div>
                        <button class="btn btn-secondary btn-lg w-100 fw-bold rounded-pill py-3 mb-3" disabled>
                            <i class="bi bi-lock-fill me-2"></i> Pendanaan Dikunci
                        </button>

                    <?php else: ?>

                        <a href="bayar.php?id=<?= $id ?>" class="btn btn-success btn-lg w-100 fw-bold rounded-pill py-3 shadow-sm mb-3">
                            <i class="bi bi-wallet2 me-2"></i> Danai Lahan Ini
                        </a>

                    <?php endif; ?>

                    <a href="transparansi.php?id=<?= $id ?>" class="btn btn-outline-secondary w-100 fw-bold rounded-pill py-2 border">
                        <i class="bi bi-truck me-2"></i> Lacak Logistik & BAST
                    </a>

                </div>
            </div>

        </div>

    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>