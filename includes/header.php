<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
};
require_once 'config/koneksi.php';
require_once 'config/fungsi.php';

// Redirect ke halaman dashboard jika sudah login
if (isset($_SESSION['user_id'])) {
    $peran = $_SESSION['peran'];
    if ($peran == 'admin') {
        header("Location: admin/index.php");
    } elseif ($peran == 'dosen') {
        header("Location: dosen/index.php");
    } elseif ($peran == 'mahasiswa') {
        header("Location: mahasiswa/index.php");
    }
    exit();
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($judul_halaman) ? $judul_halaman : 'Selamat Datang'; ?> - Sistem Monitoring Kehadiran Praktikum</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
</head>

<body>