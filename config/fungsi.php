<?php
date_default_timezone_set('Asia/Jakarta');
// Fungsi untuk membersihkan input
function bersihkan_input($data)
{
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Fungsi untuk mengecek apakah user adalah admin
function cek_admin()
{
    if (!isset($_SESSION['peran']) || $_SESSION['peran'] != 'admin') {
        header("Location: ../login.php");
        exit();
    }
}

// Fungsi untuk mengecek apakah user adalah dosen
function cek_dosen()
{
    if (!isset($_SESSION['peran']) || $_SESSION['peran'] != 'dosen') {
        header("Location: ../login.php");
        exit();
    }
}

// Fungsi untuk mengecek apakah user adalah mahasiswa
function cek_mahasiswa()
{
    if (!isset($_SESSION['peran']) || $_SESSION['peran'] != 'mahasiswa') {
        header("Location: ../login.php");
        exit();
    }
}

// Fungsi untuk mendapatkan nama hari dalam bahasa Indonesia
function nama_hari($tanggal)
{
    $hari = date('l', strtotime($tanggal));

    switch ($hari) {
        case 'Sunday':
            return 'Minggu';
        case 'Monday':
            return 'Senin';
        case 'Tuesday':
            return 'Selasa';
        case 'Wednesday':
            return 'Rabu';
        case 'Thursday':
            return 'Kamis';
        case 'Friday':
            return 'Jumat';
        case 'Saturday':
            return 'Sabtu';
        default:
            return '';
    }
}

// Fungsi untuk mendapatkan nama bulan dalam bahasa Indonesia
function nama_bulan($tanggal)
{
    $bulan = date('n', strtotime($tanggal));

    switch ($bulan) {
        case 1:
            return 'Januari';
        case 2:
            return 'Februari';
        case 3:
            return 'Maret';
        case 4:
            return 'April';
        case 5:
            return 'Mei';
        case 6:
            return 'Juni';
        case 7:
            return 'Juli';
        case 8:
            return 'Agustus';
        case 9:
            return 'September';
        case 10:
            return 'Oktober';
        case 11:
            return 'November';
        case 12:
            return 'Desember';
        default:
            return '';
    }
}

// Fungsi untuk format tanggal Indonesia
function format_tanggal_indonesia($tanggal)
{
    $tanggal_obj = strtotime($tanggal);
    $hari = date('d', $tanggal_obj);
    $bulan = nama_bulan($tanggal);
    $tahun = date('Y', $tanggal_obj);

    return "$hari $bulan $tahun";
}

// Fungsi untuk mengecek apakah absensi sudah dibuka oleh dosen
function cek_absensi_dibuka($id_kelas, $pertemuan, $koneksi)
{
    $query = "SELECT * FROM sesi_absensi WHERE id_kelas = ? AND pertemuan = ? AND status = 'dibuka'";
    $stmt = $koneksi->prepare($query);
    $stmt->bind_param("ii", $id_kelas, $pertemuan);
    $stmt->execute();
    $hasil = $stmt->get_result();

    return $hasil->num_rows > 0;
}

// Fungsi untuk mengecek apakah mahasiswa sudah absen pada sesi tertentu
function cek_sudah_absen($id_mahasiswa, $id_sesi, $koneksi)
{
    $query = "SELECT * FROM absensi WHERE id_mahasiswa = ? AND id_sesi = ?";
    $stmt = $koneksi->prepare($query);
    $stmt->bind_param("ii", $id_mahasiswa, $id_sesi);
    $stmt->execute();
    $hasil = $stmt->get_result();

    return $hasil->num_rows > 0;
}

// Fungsi untuk mendapatkan status kehadiran
function status_kehadiran($status)
{
    switch ($status) {
        case 'hadir':
            return '<span class="badge badge-success">Hadir</span>';
        case 'izin':
            return '<span class="badge badge-warning">Izin</span>';
        case 'sakit':
            return '<span class="badge badge-info">Sakit</span>';
        case 'alpa':
            return '<span class="badge badge-danger">Alpa</span>';
        default:
            return '<span class="badge badge-secondary">-</span>';
    }
}

// Fungsi untuk memeriksa dan menutup sesi yang sudah melewati waktu tutup
function periksa_sesi_kadaluarsa($koneksi)
{
    // Update status sesi yang sudah melewati waktu_tutup
    $query = "
        UPDATE sesi_absensi 
        SET status = 'ditutup' 
        WHERE status = 'dibuka' AND waktu_tutup IS NOT NULL AND waktu_tutup <= NOW()
    ";

    $koneksi->query($query);

    // Return jumlah sesi yang diperbarui (opsional)
    return $koneksi->affected_rows;
}
