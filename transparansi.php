<?php
session_start();
$conn = new mysqli("localhost", "root", "", "panganasa_db");
if ($conn->connect_error) die("Koneksi gagal: " . $conn->connect_error);

// Auto-fix DB: Tambah kolom status_logistik jika belum ada
$check_col = $conn->query("SHOW COLUMNS FROM campaigns LIKE 'status_logistik'");
if ($check_col && $check_col->num_rows == 0) {
    $conn->query("ALTER TABLE campaigns ADD COLUMN status_logistik INT NOT NULL DEFAULT 1 AFTER status");
}

if (!isset($_GET['id'])) {
    header("Location: index.php");
    exit;
}

$id = (int)$_GET['id'];

// Handling Update Status Simulasi (Khusus Demo / Admin / Petani)
if (isset($_POST['update_step'])) {
    $step_baru = (int)$_POST['update_step'];
    $conn->query("UPDATE campaigns SET status_logistik = $step_baru WHERE id = $id");
    header("Location: transparansi.php?id=$id&status_updated=true");
    exit;
}

$sql_campaign = "SELECT c.*, u.nama AS nama_petani 
                 FROM campaigns c 
                 LEFT JOIN users u ON c.petani_id = u.id 
                 WHERE c.id = $id";
$res_campaign = $conn->query($sql_campaign);

if ($res_campaign->num_rows == 0) {
    die("Data proyek tidak ditemukan.");
}

$kampanye = $res_campaign->fetch_assoc();
$status_logistik = (int)($kampanye['status_logistik'] ?? 1);

$saprodi_list = $conn->query("SELECT * FROM saprodi WHERE campaign_id = $id");

$target_query = $conn->query("SELECT SUM(total_harga) AS total FROM saprodi WHERE campaign_id = $id");
$target_dana = $target_query->fetch_assoc()['total'] ?? 0;

$terkumpul_query = $conn->query("SELECT SUM(nominal) AS total FROM donations WHERE campaign_id = $id");
$terkumpul = $terkumpul_query->fetch_assoc()['total'] ?? 0;

$persen = ($target_dana > 0) ? min(100, round(($terkumpul / $target_dana) * 100)) : 0;
$is_sukses_param = isset($_GET['status']) && $_GET['status'] == 'sukses';
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Transparansi Logistik & BAST - EASATA</title>
    
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

        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: var(--pa-gray-bg); color: #1e293b; }

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

        .card-custom { border: 1px solid var(--pa-border); border-radius: 20px; background: #ffffff; }
        
        .stepper-item { position: relative; padding-left: 48px; padding-bottom: 32px; }
        .stepper-item::before { content: ''; position: absolute; left: 17px; top: 36px; bottom: 0; width: 2px; background-color: #cbd5e1; }
        .stepper-item:last-child::before { display: none; }
        .stepper-icon { position: absolute; left: 0; top: 0; width: 36px; height: 36px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 16px; font-weight: bold; }
        .stepper-icon.completed { background-color: #10b981; color: #fff; }
        .stepper-icon.active { background-color: #0284c7; color: #fff; box-shadow: 0 0 0 5px rgba(2, 132, 199, 0.15); }
        .stepper-icon.pending { background-color: #e2e8f0; color: #94a3b8; }

        .bast-document { background: #ffffff; border: 2px dashed #cbd5e1; border-radius: 16px; position: relative; overflow: hidden; }
        .bast-stamp {
            position: absolute;
            right: 25px;
            bottom: 25px;
            width: 120px;
            height: 120px;
            border: 3px double #059669;
            border-radius: 50%;
            color: #059669;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            transform: rotate(-12deg);
            opacity: 0.85;
            font-weight: 800;
            text-align: center;
            line-height: 1.1;
            font-size: 11px;
            background: rgba(16, 185, 129, 0.04);
            pointer-events: none;
        }

        /* STYLING WIDGET TOGGLE SIMULASI (CLEAN UI) */
        .demo-trigger-btn {
            position: fixed;
            bottom: 24px;
            right: 24px;
            z-index: 9999;
            width: 48px;
            height: 48px;
            border-radius: 50%;
            background: linear-gradient(135deg, #10b981 0%, #047857 100%);
            color: #ffffff;
            border: none;
            box-shadow: 0 8px 20px rgba(16, 185, 129, 0.35);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            cursor: pointer;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .demo-trigger-btn:hover {
            transform: scale(1.08);
            box-shadow: 0 10px 25px rgba(16, 185, 129, 0.45);
        }

        .demo-panel-box {
            position: fixed;
            bottom: 84px;
            right: 24px;
            z-index: 9998;
            background: #ffffff;
            border: 1px solid var(--pa-border);
            border-radius: 20px;
            padding: 18px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            width: 300px;
            display: none;
            animation: slideUp 0.2s ease;
        }

        @keyframes slideUp {
            from { opacity: 0; transform: translateY(12px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @media print {
            body { background: #ffffff !important; }
            .no-print, nav, button, .alert, .demo-trigger-btn, .demo-panel-box { display: none !important; }
            .col-lg-7 { width: 100% !important; }
            .card-custom { border: none !important; box-shadow: none !important; }
            .bast-document { border: 2px solid #000 !important; }
        }
    </style>
</head>
<body>

    <!-- NAVBAR EASATA -->
    <nav class="navbar navbar-expand-lg navbar-panganasa sticky-top py-2 no-print">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center gap-2" href="index.php">
                <div class="brand-logo-icon"><i class="bi bi-sprout-fill"></i></div>
                <div class="d-flex flex-column">
                    <span class="fw-extrabold text-dark fs-4 lh-1" style="font-weight: 800;">EAS<span class="text-success">ATA</span></span>
                    <small class="text-muted fw-bold" style="font-size: 8px; letter-spacing: 0.5px;">EASY & SUSTAINABLE ACCESS TO AGRICULTURE</small>
                </div>
            </a>

            <div class="d-flex align-items-center gap-2">
                <a href="detail.php?id=<?= $id ?>" class="btn btn-outline-secondary btn-sm fw-bold rounded-pill px-3">
                    <i class="bi bi-arrow-left me-1"></i> Kembali ke Detail
                </a>
            </div>
        </div>
    </nav>

    <main class="container my-5">
        
        <?php if($is_sukses_param): ?>
            <div class="alert alert-success border-0 shadow-sm p-4 rounded-4 mb-4 d-flex align-items-center gap-3 no-print">
                <div class="bg-success text-white p-3 rounded-circle d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                    <i class="bi bi-check-lg fs-3"></i>
                </div>
                <div>
                    <h5 class="fw-bold mb-1 text-success-emphasis">Pendanaan Berhasil Disalurkan!</h5>
                    <p class="mb-0 small text-muted">Terima kasih! Kontribusi Anda telah dicatat di database dan langsung diteruskan ke KUD Supplier Mitra untuk pengadaan barang fisik.</p>
                </div>
            </div>
        <?php endif; ?>

        <div class="d-flex justify-content-between align-items-start mb-4 no-print flex-wrap gap-2">
            <div>
                <span class="badge bg-success bg-opacity-10 text-success fw-bold px-3 py-2 rounded-pill mb-2">
                    <i class="bi bi-shield-check me-1"></i> Audit & Transparansi Direct to Supplier
                </span>
                <h3 class="fw-extrabold text-dark mb-1">Laporan Penyaluran & Status BAST</h3>
                <p class="text-muted small mb-0">Proyek: <strong class="text-dark"><?= $kampanye['judul_proyek'] ?></strong> | Penerima: <strong class="text-dark"><?= $kampanye['nama_petani'] ?? 'Pak Slamet' ?></strong></p>
            </div>

            <button onclick="window.print()" class="btn btn-success fw-bold rounded-pill px-4 py-2 shadow-sm">
                <i class="bi bi-printer-fill me-2"></i> Cetak Bukti BAST (PDF)
            </button>
        </div>

        <div class="row g-4">
            
            <!-- STEPPER LOGISTIK DINAMIS BERDASARKAN STATUS LOGISTIK -->
            <div class="col-lg-5 no-print">
                <div class="card-custom p-4 shadow-sm h-100">
                    <h6 class="fw-bold text-dark border-bottom pb-3 mb-4"><i class="bi bi-truck text-success me-2"></i>Lacak Alur Logistik Saprodi</h6>

                    <!-- STEP 1 -->
                    <div class="stepper-item">
                        <div class="stepper-icon completed"><i class="bi bi-check-lg"></i></div>
                        <h6 class="fw-bold mb-1 text-dark">1. Permodalan Terkumpul</h6>
                        <small class="text-muted d-block mb-1" style="font-size: 12px;">Target paket dana <?= $persen ?>% terakumulasi via escrow EASATA.</small>
                        <span class="badge bg-success bg-opacity-10 text-success" style="font-size: 11px;"><i class="bi bi-check-circle-fill me-1"></i>Selesai</span>
                    </div>

                    <!-- STEP 2 -->
                    <div class="stepper-item">
                        <div class="stepper-icon <?= $status_logistik >= 2 ? 'completed' : 'active' ?>">
                            <i class="bi <?= $status_logistik >= 2 ? 'bi-check-lg' : 'bi-arrow-repeat' ?>"></i>
                        </div>
                        <h6 class="fw-bold mb-1 text-dark">2. PO ke Supplier & KUD Mitra</h6>
                        <small class="text-muted d-block mb-1" style="font-size: 12px;">Order resmi diterbitkan ke KUD Tani Makmur Jember.</small>
                        <span class="badge <?= $status_logistik >= 2 ? 'bg-success bg-opacity-10 text-success' : 'bg-primary bg-opacity-10 text-primary' ?>" style="font-size: 11px;">
                            <?= $status_logistik >= 2 ? 'PO Diterbitkan' : 'Sedang Diproses' ?>
                        </span>
                    </div>

                    <!-- STEP 3 -->
                    <div class="stepper-item">
                        <div class="stepper-icon <?= $status_logistik >= 3 ? 'completed' : ($status_logistik == 2 ? 'active' : 'pending') ?>">
                            <i class="bi <?= $status_logistik >= 3 ? 'bi-check-lg' : 'bi-truck' ?>"></i>
                        </div>
                        <h6 class="fw-bold mb-1 text-dark">3. Pengiriman Armada Logistik</h6>
                        <small class="text-muted d-block mb-1" style="font-size: 12px;">Pupuk NPK & Benih Padi dikirim ke lokasi lahan.</small>
                        <span class="badge <?= $status_logistik >= 3 ? 'bg-success bg-opacity-10 text-success' : ($status_logistik == 2 ? 'bg-primary bg-opacity-10 text-primary' : 'bg-secondary bg-opacity-10 text-secondary') ?>" style="font-size: 11px;">
                            <?= $status_logistik >= 3 ? 'Tiba di Lahan Garapan' : ($status_logistik == 2 ? 'Truk Dalam Perjalanan' : 'Menunggu PO KUD') ?>
                        </span>
                    </div>

                    <!-- STEP 4 -->
                    <div class="stepper-item">
                        <div class="stepper-icon <?= $status_logistik >= 4 ? 'completed' : 'pending' ?>">
                            <i class="bi <?= $status_logistik >= 4 ? 'bi-patch-check-fill' : 'bi-geo-alt' ?>"></i>
                        </div>
                        <h6 class="fw-bold mb-1 text-dark">4. Serah Terima & Verifikasi BAST</h6>
                        <small class="text-muted d-block mb-1" style="font-size: 12px;">Pemeriksaan fisik barang di lokasi lahan petani garapan.</small>
                        <span class="badge <?= $status_logistik >= 4 ? 'bg-success text-white' : 'bg-secondary bg-opacity-10 text-secondary' ?>" style="font-size: 11px;">
                            <?= $status_logistik >= 4 ? 'BAST Verified GPS' : 'Menunggu Penyerahan' ?>
                        </span>
                    </div>
                </div>
            </div>

            <!-- DOKUMEN BAST LAPANGAN -->
            <div class="col-lg-7">
                <div class="card-custom p-4 shadow-sm">
                    <div class="d-flex justify-content-between align-items-center border-bottom pb-3 mb-4 flex-wrap gap-2">
                        <div>
                            <h6 class="fw-bold text-dark mb-0"><i class="bi bi-file-earmark-text text-success me-2"></i>Berita Acara Serah Terima (BAST)</h6>
                            <small class="text-muted" style="font-size: 12px;">Dokumen Bukti Fisik Lapangan Terverifikasi GPS</small>
                        </div>
                        <span class="badge bg-success px-3 py-2">BAST-EASATA/2026/001</span>
                    </div>

                    <div class="bast-document p-4 mb-4">
                        <div class="text-center mb-4">
                            <h6 class="fw-extrabold text-uppercase text-dark mb-1" style="letter-spacing: 0.5px;">BERITA ACARA SERAH TERIMA BARANG SAPRODI</h6>
                            <small class="text-muted font-monospace" style="font-size: 11px;">No: BAST/EASATA-AMBULU/<?= date('Ym') ?>/01</small>
                        </div>

                        <p class="small text-secondary mb-3 lh-base">
                            Telah diserahterimakan dari mitra <strong>KUD Tani Makmur</strong> kepada Petani Garapan <strong><?= $kampanye['nama_petani'] ?? 'Pak Slamet' ?></strong> paket barang sarana produksi pertanian berupa:
                        </p>

                        <div class="table-responsive mb-3">
                            <table class="table table-sm table-bordered align-middle" style="font-size: 13px;">
                                <thead class="table-light">
                                    <tr>
                                        <th>Barang Fisik</th>
                                        <th class="text-center">Jumlah</th>
                                        <th class="text-end">Status Verifikasi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if($saprodi_list->num_rows > 0): ?>
                                        <?php 
                                        $saprodi_list->data_seek(0);
                                        while($sb = $saprodi_list->fetch_assoc()): 
                                        ?>
                                        <tr>
                                            <td class="fw-semibold text-dark"><?= $sb['nama_barang'] ?></td>
                                            <td class="text-center fw-bold"><?= $sb['jumlah'] ?> <?= $sb['satuan'] ?></td>
                                            <td class="text-end text-success fw-bold">
                                                <i class="bi bi-check-circle-fill me-1"></i>
                                                <?= $status_logistik >= 4 ? 'Terverifikasi Lapangan' : 'Menunggu Serah Terima' ?>
                                            </td>
                                        </tr>
                                        <?php endwhile; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>

                        <div class="bg-light p-3 rounded-3 border d-flex justify-content-between align-items-center small text-muted mb-3 flex-wrap gap-2" style="font-size: 12px;">
                            <span><i class="bi bi-geo-alt-fill text-danger me-1"></i>GPS: -8.3541° S, 113.6062° E (Ambulu, Jember)</span>
                            <span><i class="bi bi-calendar-check me-1"></i><?= date('d M Y') ?></span>
                        </div>

                        <!-- STEMPEL DIGITAL SESUAI STATUS -->
                        <div class="bast-stamp" style="opacity: <?= $status_logistik >= 4 ? '0.9' : '0.3' ?>;">
                            <span><?= $status_logistik >= 4 ? 'VERIFIED' : 'PENDING' ?></span>
                            <span>EASATA</span>
                            <span>IN-KIND</span>
                        </div>
                    </div>

                    <h6 class="fw-bold small text-dark mb-3 no-print"><i class="bi bi-camera me-2 text-success"></i>Lampiran Foto Fisik Lapangan:</h6>
                    <div class="row g-3 no-print">
                        <div class="col-sm-6">
                            <div class="border rounded-3 overflow-hidden bg-light p-1">
                                <img src="https://images.unsplash.com/photo-1592982537447-7440770cbfc9?auto=format&fit=crop&w=500&q=80" class="img-fluid rounded-2" alt="Pupuk Fisik" style="height: 140px; width: 100%; object-fit: cover;">
                                <small class="d-block text-muted text-center mt-2 fw-semibold" style="font-size: 11px;">Penerimaan Pupuk NPK di Lahan</small>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="border rounded-3 overflow-hidden bg-light p-1">
                                <img src="https://images.unsplash.com/photo-1586771107445-d3ca888129ff?auto=format&fit=crop&w=500&q=80" class="img-fluid rounded-2" alt="Serah Terima" style="height: 140px; width: 100%; object-fit: cover;">
                                <small class="d-block text-muted text-center mt-2 fw-semibold" style="font-size: 11px;">Penandatanganan Dokumen BAST</small>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>

    </main>

    <!-- TOMBOL TRIGGER BULAT MELAYANG (FLOATING ACTION BUTTON) -->
    <button type="button" class="demo-trigger-btn no-print" onclick="toggleDemoPanel()" title="Simulasi Status KUD (Demo)">
        <i class="bi bi-sliders"></i>
    </button>

    <!-- PANEL POPUP SIMULASI BISA DIBUKA-TUTUP (CLEAN WHITE UI) -->
    <div class="demo-panel-box no-print" id="demoPanel">
        <div class="d-flex align-items-center justify-content-between border-bottom pb-2 mb-3">
            <div class="d-flex align-items-center gap-2">
                <i class="bi bi-gear-wide-connected text-success"></i>
                <span class="fw-bold small text-dark">Simulasi Logistik KUD</span>
            </div>
            <button type="button" class="btn-close ms-auto" style="font-size: 10px;" onclick="toggleDemoPanel()"></button>
        </div>
        
        <p class="text-muted small mb-3" style="font-size: 11px;">Pilih status logistik untuk presentasi demo:</p>
        
        <form action="" method="POST" class="d-grid gap-2">
            <button type="submit" name="update_step" value="1" class="btn btn-sm <?= $status_logistik == 1 ? 'btn-success text-white fw-bold' : 'btn-outline-secondary' ?> text-start rounded-3 py-2" style="font-size: 11px;">
                <i class="bi bi-clock me-1"></i> 1. Modal Terkumpul (Pending PO)
            </button>
            <button type="submit" name="update_step" value="2" class="btn btn-sm <?= $status_logistik == 2 ? 'btn-success text-white fw-bold' : 'btn-outline-secondary' ?> text-start rounded-3 py-2" style="font-size: 11px;">
                <i class="bi bi-file-earmark-text me-1"></i> 2. PO Diterbitkan ke KUD
            </button>
            <button type="submit" name="update_step" value="3" class="btn btn-sm <?= $status_logistik == 3 ? 'btn-success text-white fw-bold' : 'btn-outline-secondary' ?> text-start rounded-3 py-2" style="font-size: 11px;">
                <i class="bi bi-truck me-1"></i> 3. Dalam Pengiriman Armada
            </button>
            <button type="submit" name="update_step" value="4" class="btn btn-sm <?= $status_logistik == 4 ? 'btn-success text-white fw-bold' : 'btn-outline-secondary' ?> text-start rounded-3 py-2" style="font-size: 11px;">
                <i class="bi bi-check-circle-fill me-1"></i> 4. BAST Selesai & Terverifikasi
            </button>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function toggleDemoPanel() {
            const panel = document.getElementById('demoPanel');
            if (panel.style.display === 'block') {
                panel.style.display = 'none';
            } else {
                panel.style.display = 'block';
            }
        }
    </script>
</body>
</html>