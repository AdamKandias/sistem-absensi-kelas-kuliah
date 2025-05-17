<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
};
require_once '../config/koneksi.php';
require_once '../config/fungsi.php';

// Cek apakah user adalah dosen
cek_dosen();

// Ambil data dosen
$id_dosen = $_SESSION['user_id'];
$query_dosen = "SELECT * FROM dosen WHERE id = (SELECT id_referensi FROM pengguna WHERE id = ?)";
$stmt_dosen = $koneksi->prepare($query_dosen);
$stmt_dosen->bind_param("i", $id_dosen);
$stmt_dosen->execute();
$result_dosen = $stmt_dosen->get_result();
$dosen = $result_dosen->fetch_assoc();
$id_dosen_asli = $dosen['id'];
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title><?php echo isset($judul_halaman) ? $judul_halaman : 'Panel Dosen'; ?> - Sistem Monitoring Kehadiran Praktikum</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
</head>

<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            <h3>Panel Dosen</h3>
        </div>
        <div class="sidebar-menu">
            <ul>
                <li><a href="index.php" <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'class="active"' : ''; ?>><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a href="kelas.php" <?php echo basename($_SERVER['PHP_SELF']) == 'kelas.php' ? 'class="active"' : ''; ?>><i class="fas fa-door-open"></i> Kelas Saya</a></li>
                <li><a href="absensi.php" <?php echo basename($_SERVER['PHP_SELF']) == 'absensi.php' || basename($_SERVER['PHP_SELF']) == 'detail_absensi.php' ? 'class="active"' : ''; ?>><i class="fas fa-clipboard-check"></i> Kelola Absensi</a></li>
                <li><a href="laporan.php" <?php echo basename($_SERVER['PHP_SELF']) == 'laporan.php' ? 'class="active"' : ''; ?>><i class="fas fa-file-alt"></i> Laporan Kehadiran</a></li>
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
                    <a href="#" class="nav-link">
                        <i class="fas fa-user"></i> <?php echo $_SESSION['nama']; ?>
                    </a>
                </div>
            </div>
        </div>

        <div class="container">