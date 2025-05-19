<?php
// Konfigurasi database
$host = 'localhost';
$user = 'root';
$pass = '';
$db   = 'db_monitoring_kehadiran';

// Membuat koneksi
$koneksi = new mysqli($host, $user, $pass, $db);

// Cek koneksi
if ($koneksi->connect_error) {
    die("Koneksi gagal: " . $koneksi->connect_error);
}

// Set charset ke utf8
$koneksi->set_charset("utf8");
