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

require_once __DIR__ . '/../library/fpdf/fpdf.php';

// Fungsi untuk menghasilkan PDF
function generate_pdf($html, $filename)
{
    global $info_kelas, $dosen, $result_mahasiswa, $statistik_mahasiswa;

    $pdf = new FPDF();
    $pdf->AddPage();
    $pdf->SetFont('Arial', 'B', 14);

    // Judul
    $pdf->Cell(0, 10, 'Laporan Kehadiran Mahasiswa', 0, 1, 'C');

    // Spasi
    $pdf->Ln(5);
    $pdf->SetFont('Arial', '', 12);

    // Tabel info kelas
    $pdf->Cell(40, 8, 'Kelas', 1);
    $pdf->Cell(60, 8, $info_kelas['nama'], 1);
    $pdf->Cell(40, 8, 'Mata Kuliah', 1);
    $pdf->Cell(50, 8, $info_kelas['nama_matkul'], 1);
    $pdf->Ln();
    $pdf->Cell(40, 8, 'Jurusan', 1);
    $pdf->Cell(60, 8, $info_kelas['nama_jurusan'], 1);
    $pdf->Cell(40, 8, 'Tahun Ajaran', 1);
    $pdf->Cell(50, 8, $info_kelas['tahun_ajaran'] . ' - ' . $info_kelas['semester'], 1);
    $pdf->Ln();
    $pdf->Cell(40, 8, 'Dosen', 1);
    $pdf->Cell(150, 8, $dosen['nama'], 1);
    $pdf->Ln(10);

    // Header tabel rekap
    $pdf->SetFont('Arial', 'B', 11);
    $header = ['No', 'NIM', 'Nama', 'Kelas', 'Hadir', 'Izin', 'Sakit', 'Alpa', 'Persentase'];
    $widths = [10, 25, 40, 20, 15, 15, 15, 15, 25];

    foreach ($header as $i => $col) {
        $pdf->Cell($widths[$i], 8, $col, 1, 0, 'C');
    }
    $pdf->Ln();

    // Data mahasiswa
    $pdf->SetFont('Arial', '', 10);
    $no = 1;
    $result_mahasiswa->data_seek(0); // reset pointer
    while ($mahasiswa = $result_mahasiswa->fetch_assoc()) {
        $id = $mahasiswa['id'];
        $pdf->Cell($widths[0], 8, $no++, 1, 0, 'C');
        $pdf->Cell($widths[1], 8, $mahasiswa['nim'], 1);
        $pdf->Cell($widths[2], 8, $mahasiswa['nama'], 1);
        $pdf->Cell($widths[3], 8, $mahasiswa['kelas_angkatan'], 1, 0, 'C');
        $pdf->Cell($widths[4], 8, $statistik_mahasiswa[$id]['hadir'], 1, 0, 'C');
        $pdf->Cell($widths[5], 8, $statistik_mahasiswa[$id]['izin'], 1, 0, 'C');
        $pdf->Cell($widths[6], 8, $statistik_mahasiswa[$id]['sakit'], 1, 0, 'C');
        $pdf->Cell($widths[7], 8, $statistik_mahasiswa[$id]['alpa'], 1, 0, 'C');
        $pdf->Cell($widths[8], 8, number_format($statistik_mahasiswa[$id]['persentase'], 2) . '%', 1, 0, 'C');
        $pdf->Ln();
    }

    // Footer
    $pdf->Ln(10);
    $pdf->SetFont('Arial', 'I', 10);
    $pdf->Cell(0, 10, 'Dicetak pada: ' . date('d-m-Y H:i:s'), 0, 0, 'R');

    $pdf->Output('D', $filename);
    exit;
}
