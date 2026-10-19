<?php
session_start();

// Proteksi Akses: Hanya Petani Login
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'petani') {
    header("Location: login.php");
    exit;
}

if (isset($_GET['id'])) {
    $conn = new mysqli("localhost", "root", "", "panganasa_db");
    if ($conn->connect_error) die("Koneksi gagal: " . $conn->connect_error);

    $campaign_id = (int)$_GET['id'];
    $petani_id   = $_SESSION['user_id'];

    // Pastikan katalog yang dihapus benar milik petani yang sedang login
    $check = $conn->query("SELECT id FROM campaigns WHERE id = $campaign_id AND petani_id = $petani_id");
    if ($check && $check->num_rows > 0) {
        // Hapus item saprodi, donasi, dan kampanye terkait
        $conn->query("DELETE FROM saprodi WHERE campaign_id = $campaign_id");
        $conn->query("DELETE FROM donations WHERE campaign_id = $campaign_id");
        $conn->query("DELETE FROM campaigns WHERE id = $campaign_id");
        
        header("Location: dashboard.php?status=deleted");
        exit;
    }
}

header("Location: dashboard.php");
exit;