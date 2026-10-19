<?php
session_start();
$conn = new mysqli("localhost", "root", "", "panganasa_db");
if ($conn->connect_error) die("Koneksi gagal: " . $conn->connect_error);

// 1. PROTEKSI LOGIN
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// 2. PROTEKSI ROLE: HANYA PENDANA YANG BOLEH MENDANAI
if (isset($_SESSION['role']) && $_SESSION['role'] === 'petani') {
    $campaign_id = isset($_GET['id']) ? (int)$_GET['id'] : 1;
    header("Location: detail.php?id=" . $campaign_id . "&error=petani_blocked");
    exit;
}

if (!isset($_GET['id'])) {
    header("Location: index.php");
    exit;
}

$campaign_id = (int)$_GET['id'];

// Ambil data kampanye & target
$sql = "SELECT c.*, u.nama AS nama_petani 
        FROM campaigns c 
        LEFT JOIN users u ON c.petani_id = u.id 
        WHERE c.id = $campaign_id";
$res = $conn->query($sql);
if ($res->num_rows == 0) die("Proyek tidak ditemukan.");
$kampanye = $res->fetch_assoc();

// Hitung Sisa Target Saprodi
$target_q = $conn->query("SELECT SUM(total_harga) AS total FROM saprodi WHERE campaign_id = $campaign_id");
$target_dana = $target_q->fetch_assoc()['total'] ?? 0;

$terkumpul_q = $conn->query("SELECT SUM(nominal) AS total FROM donations WHERE campaign_id = $campaign_id");
$terkumpul = $terkumpul_q->fetch_assoc()['total'] ?? 0;

$sisa_kebutuhan = max(0, $target_dana - $terkumpul);

// PROSES SIMULASI PEMBAYARAN
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nominal = (int)$_POST['nominal'];
    $nama_donatur = $_SESSION['nama'];
    
    if ($nominal >= 10000) {
        $sql_donasi = "INSERT INTO donations (campaign_id, nama_donatur, nominal, tanggal) 
                        VALUES ($campaign_id, '$nama_donatur', $nominal, NOW())";
        if ($conn->query($sql_donasi)) {
            header("Location: transparansi.php?id=" . $campaign_id . "&status=sukses");
            exit;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Penyaluran Modal - EASATA</title>
    
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

        .card-custom { border: 1px solid var(--pa-border); border-radius: 20px; background: #ffffff; }
        .btn-preset { border: 1px solid #cbd5e1; border-radius: 12px; padding: 12px; font-weight: 700; background: #fff; transition: all 0.2s; }
        .btn-preset:hover, .btn-preset.active { background: #10b981; color: #fff; border-color: #10b981; }
    </style>
</head>
<body>

    <!-- NAVBAR EASATA -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom sticky-top py-2">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center gap-2" href="index.php">
                <div class="brand-icon"><i class="bi bi-sprout-fill"></i></div>
                <div class="d-flex flex-column">
                    <span class="fw-extrabold text-dark fs-4 lh-1" style="font-weight: 800;">EAS<span class="text-success">ATA</span></span>
                    <small class="text-muted fw-bold" style="font-size: 8px; letter-spacing: 0.5px;">EASY & SUSTAINABLE ACCESS TO AGRICULTURE</small>
                </div>
            </a>
            <a href="detail.php?id=<?= $campaign_id ?>" class="btn btn-outline-secondary btn-sm fw-bold rounded-pill px-3">
                <i class="bi bi-x-lg me-1"></i> Batal
            </a>
        </div>
    </nav>

    <main class="container my-5" style="max-width: 640px;">
        <div class="card-custom p-4 shadow-sm">
            <span class="badge bg-success bg-opacity-10 text-success fw-bold px-3 py-2 rounded-pill mb-2">
                <i class="bi bi-shield-check me-1"></i> Penyaluran Modal Khusus Pendana
            </span>
            <h4 class="fw-extrabold text-dark mb-1">Konfirmasi Permodalan Saprodi</h4>
            <p class="text-muted small mb-4">Lahan: <strong><?= $kampanye['judul_proyek'] ?></strong></p>

            <form action="" method="POST" id="formBayar">
                
                <!-- Preset Nominal Interaktif -->
                <label class="form-label fw-bold small text-dark mb-2">Pilih Nominal Paket Saprodi:</label>
                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <button type="button" class="btn btn-preset w-100" onclick="setNominal(50000, this)">Rp 50.000</button>
                    </div>
                    <div class="col-6">
                        <button type="button" class="btn btn-preset w-100" onclick="setNominal(100000, this)">Rp 100.000</button>
                    </div>
                    <div class="col-6">
                        <button type="button" class="btn btn-preset w-100" onclick="setNominal(250000, this)">Rp 250.000</button>
                    </div>
                    <div class="col-6">
                        <button type="button" class="btn btn-preset w-100" onclick="setNominal(500000, this)">Rp 500.000</button>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label fw-bold small text-dark">Atau Masukkan Nominal Lain (Rp):</label>
                    <input type="number" name="nominal" id="inputNominal" class="form-control form-control-lg fw-bold text-success" value="100000" min="10000" required>
                    <small class="text-muted" style="font-size: 11px;">Minimal penyaluran modal Rp 10.000</small>
                </div>

                <!-- Metode Payment QRIS -->
                <div class="bg-light p-3 rounded-3 border mb-4 text-center">
                    <small class="text-muted d-block fw-bold mb-2">METODE SIMULASI INSTANT (QRIS / BANK)</small>
                    <img src="https://api.qrserver.com/v1/create-qr-code/?size=140x140&data=EASATA-SIMULATION" class="img-fluid border p-2 bg-white rounded-3 shadow-sm mb-2" alt="QRIS EASATA">
                    <small class="d-block text-success fw-bold"><i class="bi bi-check-circle-fill me-1"></i>Otomatis Terverifikasi Sistem Escrow EASATA</small>
                </div>

                <button type="submit" class="btn btn-success btn-lg w-100 fw-bold rounded-pill py-3 shadow-sm">
                    <i class="bi bi-qr-code-scan me-2"></i> Konfirmasi & Salurkan Modal
                </button>
            </form>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function setNominal(val, btn) {
            document.getElementById('inputNominal').value = val;
            document.querySelectorAll('.btn-preset').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
        }
    </script>
</body>
</html>