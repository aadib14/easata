<?php
session_start();
$conn = new mysqli("localhost", "root", "", "panganasa_db");
if ($conn->connect_error) die("Koneksi gagal: " . $conn->connect_error);

if (!isset($_GET['id']) || !isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$campaign_id = (int)$_GET['id'];
$user_id     = $_SESSION['user_id'];
$user_nama   = $_SESSION['nama'];

// Ambil detail proyek & total donasi pendana ini
$sql = "SELECT c.*, u.nama AS nama_petani, 
        (SELECT SUM(nominal) FROM donations WHERE campaign_id = $campaign_id AND nama_donatur = '$user_nama') AS total_kontribusi
        FROM campaigns c 
        LEFT JOIN users u ON c.petani_id = u.id 
        WHERE c.id = $campaign_id";

$res = $conn->query($sql);
if ($res->num_rows == 0) die("Data sertifikat tidak ditemukan.");
$data = $res->fetch_assoc();

$total_kontribusi = $data['total_kontribusi'] ?? 0;

if ($total_kontribusi == 0) {
    die("Anda belum terdaftar sebagai pendana pada proyek permodalan lahan ini.");
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sertifikat Pendana - EASATA</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Playfair+Display:wght@700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #f8fafc; color: #1e293b; }
        
        .certificate-wrapper {
            background: #ffffff;
            border: 12px solid #047857;
            border-radius: 12px;
            padding: 50px;
            position: relative;
            box-shadow: 0 20px 40px rgba(0,0,0,0.08);
            max-width: 900px;
            margin: 0 auto;
        }

        .cert-header-title {
            font-family: 'Playfair Display', serif;
            font-size: 2.2rem;
            font-weight: 800;
            color: #064e3b;
            letter-spacing: 2px;
        }

        .cert-stamp {
            position: absolute;
            right: 50px;
            bottom: 40px;
            width: 130px;
            height: 130px;
            border: 3px double #059669;
            border-radius: 50%;
            color: #059669;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            transform: rotate(-10deg);
            opacity: 0.9;
            font-weight: 800;
            text-align: center;
            line-height: 1.1;
            font-size: 11px;
            background: rgba(16, 185, 129, 0.03);
        }

        @media print {
            body { background: #ffffff !important; }
            .no-print { display: none !important; }
            .certificate-wrapper { border-width: 8px !important; box-shadow: none !important; padding: 30px !important; }
        }
    </style>
</head>
<body>

    <div class="container my-4 no-print text-center">
        <button onclick="window.print()" class="btn btn-success fw-bold rounded-pill px-4 py-2 me-2">
            <i class="bi bi-printer-fill me-2"></i> Cetak / Simpan PDF
        </button>
        <a href="dashboard.php" class="btn btn-outline-secondary fw-bold rounded-pill px-4 py-2">
            <i class="bi bi-arrow-left me-1"></i> Kembali ke Dashboard
        </a>
    </div>

    <!-- TAMPILAN SERTIFIKAT DOKUMEN -->
    <div class="container my-3">
        <div class="certificate-wrapper text-center">
            
            <div class="d-flex align-items-center justify-content-center gap-2 mb-3">
                <div class="bg-success text-white p-2 rounded-3 fs-4"><i class="bi bi-sprout-fill"></i></div>
                <span class="fw-extrabold text-dark fs-3" style="font-weight: 800;">EAS<span class="text-success">ATA</span></span>
            </div>

            <small class="text-uppercase fw-bold text-success d-block mb-1" style="letter-spacing: 2px;">Easy & Sustainable Access to Agriculture</small>
            <h2 class="cert-header-title text-uppercase mb-4">SERTIFIKAT KONTRIBUSI DANA</h2>
            
            <p class="text-muted mb-1" style="font-size: 14px;">Diberikan dengan bangga kepada:</p>
            <h1 class="fw-extrabold text-dark border-bottom pb-2 d-inline-block px-5 mb-4" style="font-weight: 800; color: #0f172a;"><?= strtoupper($user_nama) ?></h1>

            <p class="lh-lg text-secondary px-md-5 mb-4" style="font-size: 15px;">
                Atas partisipasi aktif dan komitmen berkelanjutan dalam menyalurkan modal usaha tani sebesar <br>
                <strong class="text-success fs-4">Rp <?= number_format($total_kontribusi, 0, ',', '.') ?></strong> <br>
                berbasis 100% Non-Tunai (In-Kind Saprodi) untuk mendukung pengadaan pupuk dan benih fisik pada proyek: <br>
                <strong class="text-dark fs-5">"<?= $data['judul_proyek'] ?>"</strong> <br>
                Lokasi Lahan: <strong><?= $data['lokasi'] ?></strong> | Petani Garapan: <strong><?= $data['nama_petani'] ?></strong>
            </p>

            <div class="row align-items-center mt-5 pt-3 border-top text-start">
                <div class="col-7">
                    <small class="text-muted d-block" style="font-size: 11px;">Nomor Verifikasi Digital:</small>
                    <span class="font-monospace fw-bold text-dark" style="font-size: 12px;">EASATA-CERT/<?= date('Ym') ?>/00<?= $campaign_id ?>/<?= $user_id ?></span>
                    <small class="text-muted d-block mt-1" style="font-size: 11px;"><i class="bi bi-shield-check text-success me-1"></i>Terverifikasi Escrow System & BAST Lapangan</small>
                </div>
                <div class="col-5 text-end pe-5">
                    <small class="text-muted d-block mb-1" style="font-size: 11px;">Jember, <?= date('d F Y') ?></small>
                    <span class="fw-bold text-dark d-block" style="font-size: 13px;">Tim Pengelola EASATA</span>
                </div>
            </div>

            <!-- STEMPEL DIGITAL -->
            <div class="cert-stamp">
                <span>OFFICIAL</span>
                <span>EASATA</span>
                <span>CERTIFIED</span>
            </div>

        </div>
    </div>

</body>
</html>