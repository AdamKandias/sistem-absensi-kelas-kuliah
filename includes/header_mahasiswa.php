<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
};
require_once '../config/koneksi.php';
require_once '../config/fungsi.php';

// Cek apakah user adalah mahasiswa
cek_mahasiswa();

// Ambil data mahasiswa
$id_mahasiswa = $_SESSION['user_id'];
$query_mahasiswa = "SELECT * FROM mahasiswa WHERE id = (SELECT id_referensi FROM pengguna WHERE id = ?)";
$stmt_mahasiswa = $koneksi->prepare($query_mahasiswa);
$stmt_mahasiswa->bind_param("i", $id_mahasiswa);
$stmt_mahasiswa->execute();
$result_mahasiswa = $stmt_mahasiswa->get_result();
$mahasiswa = $result_mahasiswa->fetch_assoc();
$id_mahasiswa_asli = $mahasiswa['id'];
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title><?php echo isset($judul_halaman) ? $judul_halaman : 'Panel Mahasiswa'; ?> - Sistem Monitoring Kehadiran Praktikum</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
</head>

<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            <h3>Panel Mahasiswa</h3>
        </div>
        <div class="sidebar-menu">
            <ul>
                <li><a href="index.php" <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'class="active"' : ''; ?>><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a href="kelas.php" <?php echo basename($_SERVER['PHP_SELF']) == 'kelas.php' ? 'class="active"' : ''; ?>><i class="fas fa-door-open"></i> Kelas Saya</a></li>
                <li><a href="kehadiran.php" <?php echo basename($_SERVER['PHP_SELF']) == 'kehadiran.php' ? 'class="active"' : ''; ?>><i class="fas fa-clipboard-check"></i> Kehadiran Saya</a></li>
                <li><a href="profil.php" <?php echo basename($_SERVER['PHP_SELF']) == 'profil.php' ? 'class="active"' : ''; ?>><i class="fas fa-user"></i> Profil Saya</a></li>
                <li><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </div>
    </div>

    <!-- Content -->
    <div class="content-wrapper">
        <div class="navbar">
            <button class="navbar-toggler sidebar-toggle">
                <i class="fas fa-bars"></i>
            </button>
            <div class="navbar-brand">
                Sistem Monitoring Kehadiran Praktikum
            </div>
            <div class="navbar-nav">
                <div class="nav-item">
                    <a href="profil.php" class="nav-link">
                        <i class="fas fa-user"></i> <?php echo $_SESSION['nama']; ?>
                    </a>
                </div>
            </div>
        </div>

        <div class="container">