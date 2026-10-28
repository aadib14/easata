<?php
session_start();
$conn = new mysqli("localhost", "root", "", "panganasa_db");
if ($conn->connect_error) die("Koneksi gagal: " . $conn->connect_error);

$sql = "SELECT c.*, u.nama AS nama_petani, 
        IFNULL(SUM(DISTINCT s.total_harga), 0) AS target_dana,
        IFNULL((SELECT SUM(nominal) FROM donations WHERE campaign_id = c.id), 0) AS terkumpul
        FROM campaigns c 
        LEFT JOIN users u ON c.petani_id = u.id
        LEFT JOIN saprodi s ON c.id = s.campaign_id 
        GROUP BY c.id";
$kampanye = $conn->query($sql);

$stat_total_dana = $conn->query("SELECT SUM(nominal) AS total FROM donations")->fetch_assoc()['total'] ?? 0;
$stat_lahan = $conn->query("SELECT COUNT(*) AS total FROM campaigns")->fetch_assoc()['total'] ?? 0;
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EASATA - Easy and Sustainable Access to Agriculture</title>
    
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

        .navbar-panganasa {
            background-color: #ffffff;
            border-bottom: 1px solid var(--pa-border);
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02);
        }

        .brand-icon {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, #10b981 0%, #047857 100%);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #ffffff;
            font-size: 20px;
            box-shadow: 0 4px 10px rgba(16, 185, 129, 0.25);
        }

        .hero-wrapper {
            background: linear-gradient(135deg, #022c22 0%, #064e3b 40%, #047857 100%);
            border-radius: 24px;
            padding: 48px 40px;
            color: #ffffff;
            box-shadow: 0 20px 40px -15px rgba(4, 120, 87, 0.3);
            position: relative;
            overflow: hidden;
        }

        .hero-badge {
            background: rgba(0, 0, 0, 0.3);
            backdrop-filter: blur(8px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: #ffffff;
            font-size: 12px;
            font-weight: 600;
            padding: 6px 16px;
            border-radius: 30px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 20px;
        }

        .hero-title { font-size: 2.25rem; font-weight: 800; line-height: 1.25; margin-bottom: 16px; }
        .hero-desc { font-size: 0.95rem; color: rgba(255, 255, 255, 0.85); line-height: 1.6; margin-bottom: 32px; }

        .stat-glass-card {
            background: rgba(255, 255, 255, 0.08);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.18);
            border-radius: 16px;
            padding: 16px;
            height: 100%;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .stat-label { font-size: 10px; text-transform: uppercase; color: rgba(255, 255, 255, 0.75); font-weight: 700; display: block; margin-bottom: 6px; }
        .stat-value { font-weight: 800; color: #ffffff; line-height: 1.2; margin: 0; }

        .category-pill {
            background: #ffffff;
            border: 1px solid var(--pa-border);
            color: #64748b;
            font-size: 13px;
            font-weight: 600;
            padding: 8px 20px;
            border-radius: 30px;
            transition: all 0.2s ease;
            cursor: pointer;
            user-select: none;
        }

        .category-pill:hover, .category-pill.active {
            background: var(--pa-primary);
            color: #ffffff;
            border-color: var(--pa-primary);
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.25);
        }

        .crowd-card {
            background: #ffffff;
            border: 1px solid var(--pa-border);
            border-radius: 20px;
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .crowd-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 20px 30px -10px rgba(0, 0, 0, 0.08) !important;
        }

        .crowd-card-img-wrap { position: relative; height: 195px; overflow: hidden; }
        .crowd-card-img-wrap img { width: 100%; height: 100%; object-fit: cover; }

        .tag-badge {
            position: absolute;
            top: 14px;
            left: 14px;
            background: rgba(15, 23, 42, 0.75);
            backdrop-filter: blur(8px);
            color: #ffffff;
            font-size: 10px;
            font-weight: 700;
            padding: 5px 12px;
            border-radius: 20px;
            text-transform: uppercase;
        }

        .avatar-thumb { width: 32px; height: 32px; border-radius: 50%; object-fit: cover; border: 2px solid #fff; }
        .progress-bar-fill { background: linear-gradient(90deg, #10b981 0%, #059669 100%); border-radius: 10px; }
        footer { background: #0f172a; color: #94a3b8; padding: 60px 0 30px 0; }
    </style>
</head>
<body>

    <!-- NAVBAR BRAND EASATA -->
    <nav class="navbar navbar-expand-lg navbar-panganasa sticky-top py-2">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center gap-2" href="index.php">
                <div class="brand-icon"><i class="bi bi-sprout-fill"></i></div>
                <div class="d-flex flex-column">
                    <span class="fw-extrabold text-dark fs-4 lh-1" style="font-weight: 800;">EAS<span class="text-success">ATA</span></span>
                    <small class="text-muted fw-bold" style="font-size: 8px; letter-spacing: 0.5px;">EASY & SUSTAINABLE ACCESS TO AGRICULTURE</small>
                </div>
            </a>

            <div class="d-flex align-items-center gap-2">
                <?php if(isset($_SESSION['user_id'])): ?>
                    <?php if(isset($_SESSION['role']) && $_SESSION['role'] == 'petani'): ?>
                        <a href="tambah_proyek.php" class="btn btn-outline-success fw-bold btn-sm rounded-pill px-3">
                            <i class="bi bi-plus-lg me-1"></i> Galang Saprodi
                        </a>
                    <?php endif; ?>

                    <a href="dashboard.php" class="btn btn-light btn-sm fw-semibold rounded-pill px-3 border">
                        <i class="bi bi-speedometer2 text-success me-1"></i> Dashboard
                    </a>

                    <div class="d-none d-md-flex align-items-center gap-2 bg-light px-3 py-1 rounded-pill border">
                        <span class="fw-bold small text-dark"><?= $_SESSION['nama'] ?></span>
                        <span class="badge bg-success bg-opacity-10 text-success text-capitalize small"><?= $_SESSION['role'] ?></span>
                    </div>

                    <a href="logout.php" class="btn btn-outline-danger fw-bold btn-sm rounded-pill px-3">
                        <i class="bi bi-box-arrow-right"></i>
                    </a>
                <?php else: ?>
                    <a href="login.php" class="btn btn-success fw-bold btn-sm rounded-pill px-4 shadow-sm">
                        <i class="bi bi-box-arrow-in-right me-1"></i> Masuk Akun
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <!-- HERO BANNER -->
    <section class="container my-4">
        <div class="hero-wrapper">
            <div class="row align-items-center g-4">
                <div class="col-lg-7">
                    <div class="hero-badge">
                        <i class="bi bi-shield-check text-warning"></i>
                        <span>Platform Non-Tunai (In-Kind) di Indonesia</span>
                    </div>

                    <h1 class="hero-title">Permodalan Lahan Tepat Sasaran, Langsung Berupa Pupuk & Benih.</h1>
                    <p class="hero-desc">Kami menjamin 100% donasi dan pendanaan Anda dikonversi menjadi sarana produksi pertanian fisik yang dikirim langsung ke lokasi lahan petani terverifikasi.</p>

                    <div class="row g-3">
                        <div class="col-4">
                            <div class="stat-glass-card">
                                <span class="stat-label">Total Penyaluran</span>
                                <span class="stat-value" style="font-size: 0.95rem;">Rp <?= number_format($stat_total_dana, 0, ',', '.') ?></span>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="stat-glass-card">
                                <span class="stat-label">Lahan Terbiayai</span>
                                <span class="stat-value" style="font-size: 1.1rem;"><?= $stat_lahan ?> Proyek</span>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="stat-glass-card">
                                <span class="stat-label">Transparansi</span>
                                <span class="stat-value" style="font-size: 1.05rem;">100% BAST</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-5 text-center d-none d-lg-block">
                    <div class="hero-img-box rounded-4 overflow-hidden border border-white border-opacity-25 shadow-lg">
                        <img src="https://images.unsplash.com/photo-1586771107445-d3ca888129ff?auto=format&fit=crop&w=600&q=80" alt="Petani EASATA" class="img-fluid" style="height: 320px; width: 100%; object-fit: cover;">
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- KATALOG & FILTER INTERAKTIF -->
    <main class="container my-5">
        
        <div class="row align-items-center mb-4 g-3">
            <div class="col-md-5">
                <h4 class="fw-bold mb-1 text-dark">Katalog Permodalan Lahan Aktif</h4>
                <p class="text-muted small mb-0">Pilih lahan garapan petani yang ingin Anda bantu musim tanam ini.</p>
            </div>

            <div class="col-md-7">
                <div class="input-group shadow-sm rounded-pill overflow-hidden border">
                    <span class="input-group-text bg-white border-0 ps-3"><i class="bi bi-search text-muted"></i></span>
                    <input type="text" id="inputSearch" class="form-control border-0 py-2 small" placeholder="Cari nama lahan, lokasi (Jember, Ambulu), atau nama petani...">
                </div>
            </div>
        </div>

        <div class="d-flex align-items-center gap-2 overflow-auto pb-3 mb-3">
            <span class="category-pill active" onclick="filterKategori('semua', this)">Semua</span>
            <span class="category-pill" onclick="filterKategori('padi & palawija', this)">Padi & Palawija</span>
            <span class="category-pill" onclick="filterKategori('hortikultura', this)">Hortikultura</span>
            <span class="category-pill" onclick="filterKategori('jagung', this)">Jagung</span>
        </div>

        <div class="row g-4" id="katalogGrid">
            <?php while($row = $kampanye->fetch_assoc()): 
                $target = $row['target_dana'];
                $terkumpul = $row['terkumpul'];
                $persen = ($target > 0) ? min(100, round(($terkumpul / $target) * 100)) : 0;
                $kategori_clean = strtolower($row['kategori'] ?? 'pertanian');
                $search_text = strtolower($row['judul_proyek'] . ' ' . ($row['nama_petani'] ?? '') . ' ' . ($row['lokasi'] ?? ''));
            ?>
            <div class="col-md-6 col-lg-4 campaign-item" data-category="<?= $kategori_clean ?>" data-search="<?= $search_text ?>">
                <a href="detail.php?id=<?= $row['id'] ?>" class="text-decoration-none text-dark">
                    <div class="crowd-card shadow-sm h-100">
                        <div class="crowd-card-img-wrap">
                            <img src="https://images.unsplash.com/photo-1595841696677-6489ff3f8cd1?auto=format&fit=crop&w=600&q=80" alt="Proyek Lahan">
                            <span class="tag-badge"><?= strtoupper($row['kategori'] ?? 'PERTANIAN') ?></span>
                        </div>

                        <div class="card-body p-4 d-flex flex-column">
                            <h6 class="fw-bold text-dark mb-3 lh-sm" style="height: 42px; overflow: hidden; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical;">
                                <?= $row['judul_proyek'] ?>
                            </h6>

                            <div class="d-flex align-items-center gap-2 mb-3">
                                <img src="<?= $row['foto_petani'] ?? 'https://images.unsplash.com/photo-1544005313-94ddf0286df2?auto=format&fit=crop&w=100&q=80' ?>" class="avatar-thumb" alt="Petani">
                                <div class="text-truncate">
                                    <span class="small fw-bold text-dark d-block leading-tight"><?= $row['nama_petani'] ?? 'Pak Slamet' ?></span>
                                    <small class="text-muted" style="font-size: 11px;"><i class="bi bi-geo-alt-fill text-danger me-1"></i><?= $row['lokasi'] ?? 'Jember' ?></small>
                                </div>
                                <i class="bi bi-patch-check-fill text-primary ms-auto fs-6" title="Petani Terverifikasi Lapangan"></i>
                            </div>

                            <div class="mt-auto pt-2">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <small class="text-muted fw-semibold" style="font-size: 11px;">Progres Pendanaan</small>
                                    <span class="fw-extrabold text-success small"><?= $persen ?>%</span>
                                </div>

                                <div class="progress mb-3" style="height: 7px; border-radius: 10px; background-color: #f1f5f9;">
                                    <div class="progress-bar progress-bar-fill" role="progressbar" style="width: <?= $persen ?>%"></div>
                                </div>

                                <div class="d-flex justify-content-between align-items-end pt-2 border-top">
                                    <div>
                                        <small class="text-muted d-block text-uppercase fw-bold" style="font-size: 10px;">Terkumpul</small>
                                        <span class="fw-bold text-success">Rp <?= number_format($terkumpul, 0, ',', '.') ?></span>
                                    </div>
                                    <div class="text-end">
                                        <small class="text-muted d-block text-uppercase fw-bold" style="font-size: 10px;">Target Paket</small>
                                        <span class="fw-bold text-dark">Rp <?= number_format($target, 0, ',', '.') ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </a>
            </div>
            <?php endwhile; ?>
        </div>

    </main>

    <!-- TRUST SECTION -->
    <section class="container my-5">
        <div class="bg-white border rounded-4 p-5 shadow-sm">
            <div class="text-center mb-5">
                <span class="badge bg-success bg-opacity-10 text-success fw-bold px-3 py-2 rounded-pill mb-2">Sistem Ekosistem Tertutup</span>
                <h4 class="fw-bold text-dark mb-1">Mengapa Permodalan EASATA Aman & Terpercaya?</h4>
                <p class="text-muted small">Mekanisme penyaluran langsung tanpa perantara uang tunai.</p>
            </div>

            <div class="row g-4 text-center">
                <div class="col-md-4">
                    <div class="bg-success bg-opacity-10 text-success rounded-4 p-3 d-inline-block mb-3 fs-3">
                        <i class="bi bi-box-seam"></i>
                    </div>
                    <h6 class="fw-bold mb-2 text-dark">Penyaluran 100% Non-Tunai</h6>
                    <p class="text-muted small mb-0">Dana terkumpul dialihkan langsung ke KUD/Supplier resmi untuk dibelikan bibit & pupuk fisik.</p>
                </div>

                <div class="col-md-4">
                    <div class="bg-success bg-opacity-10 text-success rounded-4 p-3 d-inline-block mb-3 fs-3">
                        <i class="bi bi-geo-alt"></i>
                    </div>
                    <h6 class="fw-bold mb-2 text-dark">Tracking Logistik GPS</h6>
                    <p class="text-muted small mb-0">Pengiriman barang dapat dipantau real-time hingga sampai ke tangan kelompok tani.</p>
                </div>

                <div class="col-md-4">
                    <div class="bg-success bg-opacity-10 text-success rounded-4 p-3 d-inline-block mb-3 fs-3">
                        <i class="bi bi-file-earmark-check"></i>
                    </div>
                    <h6 class="fw-bold mb-2 text-dark">Dokumen BAST Digital</h6>
                    <p class="text-muted small mb-0">Setiap penerimaan disertai foto Berita Acara Serah Terima (BAST) fisik berstempel.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- FOOTER EASATA -->
    <footer>
        <div class="container">
            <div class="row g-4 mb-5">
                <div class="col-lg-5">
                    <div class="d-flex align-items-center gap-2 mb-3">
                        <div class="brand-icon" style="width: 32px; height: 32px; font-size: 16px;">
                            <i class="bi bi-sprout-fill"></i>
                        </div>
                        <span class="fw-extrabold text-white fs-4">EAS<span class="text-success">ATA</span></span>
                    </div>
                    <p class="small text-secondary mb-3" style="max-width: 380px;">
                        EASATA (Easy and Sustainable Access to Agriculture) adalah platform crowdfunding permodalan pertanian berbasis in-kind yang menjamin efisiensi alokasi sarana produksi bagi petani Indonesia.
                    </p>
                    <small class="text-muted d-block">© 2026 EASATA Indonesia. Hak Cipta Dilindungi.</small>
                </div>

                <div class="col-6 col-lg-3 ms-auto">
                    <h6 class="fw-bold text-white mb-3 small text-uppercase">Navigasi Platform</h6>
                    <ul class="list-unstyled small d-flex flex-column gap-2">
                        <li><a href="index.php" class="text-decoration-none text-secondary">Katalog Proyek Lahan</a></li>
                        <li><a href="login.php" class="text-decoration-none text-secondary">Masuk Akun</a></li>
                        <li><a href="dashboard.php" class="text-decoration-none text-secondary">Dashboard Saya</a></li>
                    </ul>
                </div>

                <div class="col-6 col-lg-4">
                    <h6 class="fw-bold text-white mb-3 small text-uppercase">Sistem Keamanan</h6>
                    <ul class="list-unstyled small d-flex flex-column gap-2">
                        <li><span class="text-secondary"><i class="bi bi-shield-lock me-2 text-success"></i>Direct Supplier Escrow</span></li>
                        <li><span class="text-secondary"><i class="bi bi-patch-check me-2 text-success"></i>Verifikasi Lapangan KUD</span></li>
                        <li><span class="text-secondary"><i class="bi bi-qr-code-scan me-2 text-success"></i>QRIS Payment Gateway</span></li>
                    </ul>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let activeCategory = 'semua';

        function filterKategori(kat, btn) {
            activeCategory = kat;
            document.querySelectorAll('.category-pill').forEach(p => p.classList.remove('active'));
            btn.classList.add('active');
            applyFilter();
        }

        document.getElementById('inputSearch').addEventListener('input', applyFilter);

        function applyFilter() {
            const query = document.getElementById('inputSearch').value.toLowerCase().trim();
            const items = document.querySelectorAll('.campaign-item');

            items.forEach(item => {
                const itemCat = item.getAttribute('data-category');
                const itemSearch = item.getAttribute('data-search');

                const matchCat = (activeCategory === 'semua' || itemCat.includes(activeCategory));
                const matchSearch = (query === '' || itemSearch.includes(query));

                if (matchCat && matchSearch) {
                    item.style.display = 'block';
                } else {
                    item.style.display = 'none';
                }
            });
        }
    </script>
</body>
</html>