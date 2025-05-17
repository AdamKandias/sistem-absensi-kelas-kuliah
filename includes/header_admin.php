<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
};
require_once '../config/koneksi.php';
require_once '../config/fungsi.php';

// Cek apakah user adalah admin
cek_admin();
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title><?php echo isset($judul_halaman) ? $judul_halaman : 'Admin Panel'; ?> - Sistem Monitoring Kehadiran Praktikum</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
</head>

<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            <h3>Admin Panel</h3>
        </div>
        <div class="sidebar-menu">
            <ul>
                <li><a href="index.php" <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'class="active"' : ''; ?>><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a href="mahasiswa.php" <?php echo basename($_SERVER['PHP_SELF']) == 'mahasiswa.php' ? 'class="active"' : ''; ?>><i class="fas fa-user-graduate"></i> Mahasiswa</a></li>
                <li><a href="dosen.php" <?php echo basename($_SERVER['PHP_SELF']) == 'dosen.php' ? 'class="active"' : ''; ?>><i class="fas fa-chalkboard-teacher"></i> Dosen</a></li>
                <li><a href="jurusan.php" <?php echo basename($_SERVER['PHP_SELF']) == 'jurusan.php' ? 'class="active"' : ''; ?>><i class="fas fa-building"></i> Jurusan</a></li>
                <li><a href="kelas.php" <?php echo basename($_SERVER['PHP_SELF']) == 'kelas.php' ? 'class="active"' : ''; ?>><i class="fas fa-door-open"></i> Kelas</a></li>
                <li><a href="mata_kuliah.php" <?php echo basename($_SERVER['PHP_SELF']) == 'mata_kuliah.php' ? 'class="active"' : ''; ?>><i class="fas fa-book"></i> Mata Kuliah</a></li>
                <li><a href="pengguna.php" <?php echo basename($_SERVER['PHP_SELF']) == 'pengguna.php' ? 'class="active"' : ''; ?>><i class="fas fa-users"></i> Pengguna</a></li>
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