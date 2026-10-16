<?php
session_start();

// Proteksi Akses: Cek apakah user sudah login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Proteksi Akses: Cek apakah role user adalah petani
if ($_SESSION['role'] !== 'petani') {
    header("Location: login.php");
    exit;
}

$conn = new mysqli("localhost", "root", "", "panganasa_db");
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

// AUTO-FIX DATABASE: Tambahkan kolom 'harga_satuan' otomatis jika belum ada di tabel saprodi
$check_col =$conn->query("SHOW COLUMNS FROM saprodi LIKE 'harga_satuan'");
if ($check_col && $check_col->num_rows == 0) {$conn->query("ALTER TABLE saprodi ADD COLUMN harga_satuan INT NOT NULL DEFAULT 0 AFTER satuan");
}

$error = "";

// Proses Simpan Pengajuan Lahan & Rincian Saprodi
if ($_SERVER['REQUEST_METHOD'] == 'POST') {$petani_id    = $_SESSION['user_id'];$judul        = $conn->real_escape_string($_POST['judul_proyek']);
    $kategori     =$conn->real_escape_string($_POST['kategori']);$luas_lahan   = $conn->real_escape_string($_POST['luas_lahan']);
    $lokasi       =$conn->real_escape_string($_POST['lokasi']);$durasi       = (int)$_POST['sisa_hari'];$deskripsi    = $conn->real_escape_string($_POST['deskripsi']);

    // 1. Simpan data kampanye utama
    $sql_campaign = "INSERT INTO campaigns (petani_id, judul_proyek, kategori, luas_lahan, lokasi, sisa_hari, deskripsi, status) 
                    VALUES ($petani_id, '$judul', '$kategori', '$luas_lahan', '$lokasi', $durasi, '$deskripsi', 'aktif')";

    if ($conn->query($sql_campaign)) {
        $new_campaign_id =$conn->insert_id;

        // 2. Simpan rincian barang saprodi fisik
        if (isset($_POST['nama_barang'])) {
            if (is_array($_POST['nama_barang'])) {
                $nama_barang =$_POST['nama_barang'];
                $jumlah      =$_POST['jumlah'];
                $satuan      =$_POST['satuan'];
                $harga       =$_POST['harga_satuan'];

                for ($i = 0; $i < count($nama_barang); $i++) {$item_nama   = $conn->real_escape_string($nama_barang[$i]);$item_qty    = (int)$jumlah[$i];
                    $item_satuan =$conn->real_escape_string($satuan[$i]);
                    $item_harga  = (int)$harga[$i];$total_harga = $item_qty * $item_harga;

                    if (!empty($item_nama)) {
                        if ($total_harga > 0) {$sql_saprodi = "INSERT INTO saprodi (campaign_id, nama_barang, jumlah, satuan, harga_satuan, total_harga) 
                                            VALUES ($new_campaign_id, '$item_nama', $item_qty, '$item_satuan', $item_harga,$total_harga)";
                            $conn->query($sql_saprodi);
                        }
                    }
                }
            }
        }

        header("Location: detail.php?id=" . $new_campaign_id);
        exit;
    } else {
        $error = "Gagal menyimpan pengajuan lahan: " . $conn->error;
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengajuan Permodalan Lahan Baru - PanganAsa</title>
    
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

        body { 
            font-family: 'Plus Jakarta Sans', sans-serif; 
            background-color: var(--pa-bg); 
            color: #1e293b; 
        }

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

        .card-custom { 
            border: 1px solid var(--pa-border); 
            border-radius: 20px; 
            background: #ffffff; 
        }

        .sticky-summary {
            position: sticky;
            top: 90px;
        }
    </style>
</head>
<body>

    <!-- NAVBAR RESMI -->
    <nav class="navbar navbar-expand-lg navbar-panganasa sticky-top py-2">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center gap-2" href="index.php">
                <div class="brand-icon">
                    <i class="bi bi-sprout-fill"></i>
                </div>
                <div class="d-flex flex-column">
                    <span class="fw-extrabold text-dark fs-4 lh-1" style="font-weight: 800;">Pangan<span class="text-success">Asa</span></span>
                    <small class="text-muted fw-bold" style="font-size: 9px; letter-spacing: 1px;">FINTECH AGRI CROWDFUNDING</small>
                </div>
            </a>

            <div class="d-flex align-items-center gap-2">
                <a href="dashboard.php" class="btn btn-outline-secondary btn-sm fw-bold rounded-pill px-3">
                    <i class="bi bi-arrow-left me-1"></i> Batal & Kembali
                </a>
            </div>
        </div>
    </nav>

    <main class="container my-5" style="max-width: 1080px;">
        
        <div class="mb-4">
            <span class="badge bg-success bg-opacity-10 text-success fw-bold px-3 py-2 rounded-pill mb-2">
                <i class="bi bi-patch-check-fill me-1"></i> Portal Mitra Petani Terverifikasi
            </span>
            <h3 class="fw-extrabold text-dark mb-1">Formulir Pengajuan Permodalan Saprodi</h3>
            <p class="text-muted small mb-0">Isi rincian kebutuhan pupuk, benih, dan sarana produksi fisik untuk musim tanam mendatang.</p>
        </div>

        <?php if($error): ?>
            <div class="alert alert-danger rounded-3 mb-4"><?= $error ?></div>
        <?php endif; ?>

        <form action="" method="POST" id="formPengajuan">
            <div class="row g-4">
                
                <!-- INFORMASI LAHAN & RINCIAN SAPRODI -->
                <div class="col-lg-8">
                    
                    <!-- 1. Profil Lahan Tanam -->
                    <div class="card-custom p-4 shadow-sm mb-4">
                        <h6 class="fw-bold text-dark border-bottom pb-3 mb-4">
                            <i class="bi bi-geo-alt text-success me-2"></i>1. Informasi Lahan Tanam
                        </h6>

                        <div class="mb-3">
                            <label class="form-label fw-semibold small">Judul Pengajuan Permodalan</label>
                            <input type="text" name="judul_proyek" class="form-control" placeholder="Contoh: Permodalan Benih Padi Inpari 32 Lahan Garapan Ambulu" required>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold small">Kategori Komoditas</label>
                                <select name="kategori" class="form-select" required>
                                    <option value="Padi & Palawija">Padi & Palawija</option>
                                    <option value="Hortikultura">Hortikultura</option>
                                    <option value="Jagung">Jagung</option>
                                    <option value="Perkebunan">Perkebunan</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold small">Luas Lahan Garapan</label>
                                <input type="text" name="luas_lahan" class="form-control" placeholder="Contoh: 1.5 Hektar" required>
                            </div>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold small">Lokasi Lahan (Kecamatan / Kab)</label>
                                <input type="text" name="lokasi" class="form-control" placeholder="Contoh: Ambulu, Jember" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold small">Durasi Penggalangan (Hari)</label>
                                <input type="number" name="sisa_hari" class="form-control" value="30" min="7" max="60" required>
                            </div>
                        </div>

                        <div class="mb-0">
                            <label class="form-label fw-semibold small">Deskripsi & Cerita Pengajuan Lahan</label>
                            <textarea name="deskripsi" class="form-control" rows="3" placeholder="Ceritakan kondisi lahan, potensi hasil panen, dan mengapa pendana harus membantu permodalan Anda..." required></textarea>
                        </div>
                    </div>

                    <!-- 2. Rincian Barang Saprodi Fisik -->
                    <div class="card-custom p-4 shadow-sm">
                        <div class="d-flex justify-content-between align-items-center border-bottom pb-3 mb-4">
                            <div>
                                <h6 class="fw-bold text-dark mb-0"><i class="bi bi-box-seam text-success me-2"></i>2. Rincian Item Barang Saprodi</h6>
                                <small class="text-muted" style="font-size: 11px;">Barang fisik yang akan dibeli langsung dari supplier/KUD mitra</small>
                            </div>
                            <button type="button" class="btn btn-outline-success btn-sm fw-bold rounded-pill" id="btnAddRow">
                                <i class="bi bi-plus-lg me-1"></i> Tambah Item
                            </button>
                        </div>

                        <div class="table-responsive mb-3">
                            <table class="table align-middle" id="tableSaprodi">
                                <thead class="table-light small text-uppercase text-muted" style="font-size: 11px;">
                                    <tr>
                                        <th style="width: 35%;">Nama Barang Saprodi</th>
                                        <th style="width: 15%;">Jumlah</th>
                                        <th style="width: 15%;">Satuan</th>
                                        <th style="width: 25%;">Harga Satuan (Rp)</th>
                                        <th style="width: 10%;">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody id="saprodiContainer">
                                    <tr class="saprodi-row">
                                        <td><input type="text" name="nama_barang[]" class="form-control form-control-sm" placeholder="Benih Padi Inpari 32" required></td>
                                        <td><input type="number" name="jumlah[]" class="form-control form-control-sm qty-input" value="10" min="1" required></td>
                                        <td><input type="text" name="satuan[]" class="form-control form-control-sm" value="Kg" required></td>
                                        <td><input type="number" name="harga_satuan[]" class="form-control form-control-sm price-input" value="50000" step="500" required></td>
                                        <td class="text-center"><button type="button" class="btn btn-outline-danger btn-sm border-0 btn-remove-row"><i class="bi bi-trash"></i></button></td>
                                    </tr>
                                    <tr class="saprodi-row">
                                        <td><input type="text" name="nama_barang[]" class="form-control form-control-sm" placeholder="Pupuk NPK Phonska" required></td>
                                        <td><input type="number" name="jumlah[]" class="form-control form-control-sm qty-input" value="5" min="1" required></td>
                                        <td><input type="text" name="satuan[]" class="form-control form-control-sm" value="Sak" required></td>
                                        <td><input type="number" name="harga_satuan[]" class="form-control form-control-sm price-input" value="180000" step="500" required></td>
                                        <td class="text-center"><button type="button" class="btn btn-outline-danger btn-sm border-0 btn-remove-row"><i class="bi bi-trash"></i></button></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                </div>

                <!-- RINGKASAN ANGGARAN AUTOMATIS -->
                <div class="col-lg-4">
                    <div class="card-custom p-4 shadow-sm sticky-summary">
                        <small class="text-muted d-block text-uppercase fw-bold mb-1" style="font-size: 11px;">Estimasi Total Target Dana</small>
                        <h2 class="fw-extrabold text-success mb-3" id="displayTotalTarget">Rp 1.400.000</h2>
                        
                        <div class="bg-light p-3 rounded-3 border mb-4" style="font-size: 12px;">
                            <div class="d-flex justify-content-between mb-1">
                                <span class="text-muted">Status Skema:</span>
                                <span class="fw-bold text-dark">100% In-Kind</span>
                            </div>
                            <div class="d-flex justify-content-between mb-1">
                                <span class="text-muted">Penyalur Resmi:</span>
                                <span class="fw-bold text-dark">KUD Mitra Jember</span>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span class="text-muted">Audit BAST:</span>
                                <span class="fw-bold text-success"><i class="bi bi-check-circle-fill me-1"></i>Otomatis</span>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-success btn-lg w-100 fw-bold rounded-pill py-3 shadow-sm mb-2">
                            <i class="bi bi-send-fill me-2"></i> Kirim Pengajuan Lahan
                        </button>
                        <small class="text-muted d-block text-center" style="font-size: 11px;">Pengajuan akan langsung ditayangkan di katalog publik.</small>
                    </div>
                </div>

            </div>
        </form>

    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        function hitungTotalSaprodi() {
            let total = 0;
            const rows = document.querySelectorAll('.saprodi-row');
            
            rows.forEach(function(row) {
                const qtyElem = row.querySelector('.qty-input');
                const priceElem = row.querySelector('.price-input');
                
                const qtyVal = qtyElem ? parseFloat(qtyElem.value) : 0;
                const qty = isNaN(qtyVal) ? 0 : qtyVal;
                
                const priceVal = priceElem ? parseFloat(priceElem.value) : 0;
                const price = isNaN(priceVal) ? 0 : priceVal;
                
                total += (qty * price);
            });

            document.getElementById('displayTotalTarget').innerText = 'Rp ' + total.toLocaleString('id-ID');
        }

        document.addEventListener('input', function(e) {
            if (e.target.classList.contains('qty-input') || e.target.classList.contains('price-input')) {
                hitungTotalSaprodi();
            }
        });

        document.getElementById('btnAddRow').addEventListener('click', function() {
            const container = document.getElementById('saprodiContainer');
            const newRow = document.createElement('tr');
            newRow.className = 'saprodi-row';
            newRow.innerHTML = `
                <td><input type="text" name="nama_barang[]" class="form-control form-control-sm" placeholder="Nama item pupuk/benih" required></td>
                <td><input type="number" name="jumlah[]" class="form-control form-control-sm qty-input" value="1" min="1" required></td>
                <td><input type="text" name="satuan[]" class="form-control form-control-sm" value="Kg" required></td>
                <td><input type="number" name="harga_satuan[]" class="form-control form-control-sm price-input" value="50000" step="500" required></td>
                <td class="text-center"><button type="button" class="btn btn-outline-danger btn-sm border-0 btn-remove-row"><i class="bi bi-trash"></i></button></td>
            `;
            container.appendChild(newRow);
            hitungTotalSaprodi();
        });

        document.addEventListener('click', function(e) {
            if (e.target.closest('.btn-remove-row')) {
                const rows = document.querySelectorAll('.saprodi-row');
                if (rows.length > 1) {
                    e.target.closest('.saprodi-row').remove();
                    hitungTotalSaprodi();
                } else {
                    alert('Minimal harus ada 1 item barang saprodi.');
                }
            }
        });

        hitungTotalSaprodi();
    </script>
</body>
</html>