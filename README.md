# 🌾 EASATA - Easy and Sustainable Access to Agriculture

**EASATA** adalah platform permodalan pertanian berbasis *In-Kind Crowdfunding* (Non-Tunai) pertama di Indonesia. Platform ini menjamin 100% dana dari pendana tidak disalurkan dalam bentuk uang tunai, melainkan langsung dikonversi menjadi paket sarana produksi pertanian (Saprodi) fisik seperti pupuk dan benih yang dikirim oleh KUD/Supplier mitra ke lokasi lahan petani terverifikasi.

---

## 🚀 Fitur Utama & Keunggulan

1. **Skema Penyaluran 100% In-Kind (Non-Tunai)**
   - Mencegah penyalahgunaan modal usaha tani untuk kebutuhan konsumtif.
   - Escrow tertutup langsung ke penyedia barang (KUD / Supplier resmi).

2. **Strict Role-Based Access Control (RBAC)**
   - **Petani**: Mengajukan kebutuhan lahan, merinci item Saprodi, dan memantau status logistik pupuk/benih.
   - **Pendana**: Memilih proyek lahan, melakukan simulasi pembayaran QRIS, dan menerima sertifikat BAST digital.
   - *Security Guard*: Otomatis mengunci dan melarang akun Petani melakukan pendanaan (mencegah *logic bug*).

3. **Audit Transparansi & Logistik Real-Time**
   - *Stepper Logistik*: Melacak alur dari penggalangan, penerbitan PO ke KUD, pengiriman armada, hingga serah terima.
   - *BAST Digital*: Berita Acara Serah Terima otomatis dengan koordinat lokasi GPS dan bukti foto serah terima lapangan.

4. **UI/UX Modern & Interaktif**
   - *Live Search & Category Filter*: Pencarian proyek lahan secara *real-time* tanpa *reload* halaman.
   - *Budget Calculator JS*: Kalkulasi target anggaran otomatis saat petani menambah rincian barang.
   - Responsif dengan desain standar *fintech national* (Bootstrap 5 & Glassmorphism design).

---

## 🛠️ Tumpukan Teknologi (Tech Stack)

- **Backend**: PHP Native (MySQLi)
- **Database**: MySQL (`panganasa_db` / `easata_db`)
- **Frontend**: HTML5, CSS3, Bootstrap 5.3, Bootstrap Icons
- **Interaktivitas**: Vanilla JavaScript (DOM manipulation, Real-time Search, Budget Calculator)