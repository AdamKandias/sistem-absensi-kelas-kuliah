<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
};
require_once 'config/koneksi.php';
require_once 'config/fungsi.php';

// Redirect ke halaman login jika belum login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Redirect ke halaman sesuai peran
$peran = $_SESSION['peran'];
if ($peran == 'admin') {
    header("Location: admin/index.php");
} elseif ($peran == 'dosen') {
    header("Location: dosen/index.php");
} elseif ($peran == 'mahasiswa') {
    header("Location: mahasiswa/index.php");
} else {
    header("Location: login.php");
}
exit();
