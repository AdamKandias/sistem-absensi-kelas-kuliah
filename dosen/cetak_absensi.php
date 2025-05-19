<?php
// Set zona waktu ke WIB (Asia/Jakarta)
date_default_timezone_set('Asia/Jakarta');

session_start();
require_once '../config/koneksi.php';
require_once '../config/fungsi.php';

// Cek apakah user adalah dosen
cek_dosen();

// Cek apakah ID sesi absensi ada
if (!isset($_GET['id'])) {
    header("Location: absensi.php");
    exit();
}

$id_sesi = bersihkan_input($_GET['id']);

// Ambil data sesi absensi
$query_sesi = "
    SELECT sa.*, k.nama as nama_kelas, mk.nama as nama_matkul, k.id_dosen, d.nama as nama_dosen
    FROM sesi_absensi sa
    JOIN kelas k ON sa.id_kelas = k.id
    JOIN mata_kuliah mk ON k.id_matkul = mk.id
    JOIN dosen d ON k.id_dosen = d.id
    WHERE sa.id = ?
";
$stmt_sesi = $koneksi->prepare($query_sesi);
$stmt_sesi->bind_param("i", $id_sesi);
$stmt_sesi->execute();
$result_sesi = $stmt_sesi->get_result();

// Cek apakah sesi absensi ditemukan
if ($result_sesi->num_rows == 0) {
    header("Location: absensi.php");
    exit();
}

$sesi = $result_sesi->fetch_assoc();

// Cek apakah sesi ini milik dosen yang sedang login
$id_dosen = $_SESSION['user_id'];
$query_dosen = "SELECT * FROM dosen WHERE id = (SELECT id_referensi FROM pengguna WHERE id = ?)";
$stmt_dosen = $koneksi->prepare($query_dosen);
$stmt_dosen->bind_param("i", $id_dosen);
$stmt_dosen->execute();
$result_dosen = $stmt_dosen->get_result();
$dosen = $result_dosen->fetch_assoc();
$id_dosen_asli = $dosen['id'];

if ($sesi['id_dosen'] != $id_dosen_asli) {
    header("Location: absensi.php");
    exit();
}

// Ambil data mahasiswa yang terdaftar di kelas ini
$query_mahasiswa = "
    SELECT m.id, m.nim, m.nama, m.kelas_angkatan, j.nama as nama_jurusan, a.status, a.keterangan
    FROM mahasiswa m
    JOIN kelas_mahasiswa km ON m.id = km.id_mahasiswa
    JOIN jurusan j ON m.id_jurusan = j.id
    LEFT JOIN absensi a ON m.id = a.id_mahasiswa AND a.id_sesi = ?
    WHERE km.id_kelas = ?
    ORDER BY m.nama
";
$stmt_mahasiswa = $koneksi->prepare($query_mahasiswa);
$stmt_mahasiswa->bind_param("ii", $id_sesi, $sesi['id_kelas']);
$stmt_mahasiswa->execute();
$result_mahasiswa = $stmt_mahasiswa->get_result();

// Menggunakan FPDF untuk membuat PDF
require_once __DIR__ . '/../library/fpdf/fpdf.php';

class PDF extends FPDF
{
    // Header halaman
    function Header()
    {

        // Font Arial bold 15
        $this->SetFont('Arial', 'B', 15);

        // Judul
        $this->Cell(0, 10, 'DAFTAR HADIR MAHASISWA', 0, 1, 'C');

        // Garis bawah
        $this->Line(10, 20, 200, 20);

        // Baris kosong
        $this->Ln(10);
    }

    // Footer halaman
    function Footer()
    {
        // Posisi 1.5 cm dari bawah
        $this->SetY(-15);

        // Arial italic 8
        $this->SetFont('Arial', 'I', 8);

        // Nomor halaman
        $this->Cell(0, 10, 'Halaman ' . $this->PageNo() . '/{nb}', 0, 0, 'C');
    }
}

// Inisialisasi PDF
$pdf = new PDF();
$pdf->AliasNbPages();
$pdf->AddPage();
$pdf->SetFont('Arial', '', 12);

// Informasi Kelas dan Sesi
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(0, 10, 'Informasi Sesi Absensi', 0, 1);
$pdf->SetFont('Arial', '', 12);

$pdf->Cell(40, 7, 'Kelas', 0);
$pdf->Cell(5, 7, ':', 0);
$pdf->Cell(0, 7, $sesi['nama_kelas'], 0, 1);

$pdf->Cell(40, 7, 'Mata Kuliah', 0);
$pdf->Cell(5, 7, ':', 0);
$pdf->Cell(0, 7, $sesi['nama_matkul'], 0, 1);

$pdf->Cell(40, 7, 'Dosen', 0);
$pdf->Cell(5, 7, ':', 0);
$pdf->Cell(0, 7, $sesi['nama_dosen'], 0, 1);

$pdf->Cell(40, 7, 'Pertemuan', 0);
$pdf->Cell(5, 7, ':', 0);
$pdf->Cell(0, 7, 'Pertemuan ' . $sesi['pertemuan'], 0, 1);

$pdf->Cell(40, 7, 'Tanggal', 0);
$pdf->Cell(5, 7, ':', 0);
$pdf->Cell(0, 7, format_tanggal_indonesia($sesi['tanggal']), 0, 1);

$pdf->Cell(40, 7, 'Materi', 0);
$pdf->Cell(5, 7, ':', 0);
$pdf->Cell(0, 7, $sesi['materi'], 0, 1);

$pdf->Cell(40, 7, 'Status', 0);
$pdf->Cell(5, 7, ':', 0);
$pdf->Cell(0, 7, ($sesi['status'] == 'dibuka' ? 'Dibuka' : 'Ditutup'), 0, 1);

// Baris kosong
$pdf->Ln(10);

// Tabel Daftar Hadir
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(0, 10, 'Daftar Hadir Mahasiswa', 0, 1);
$pdf->SetFont('Arial', '', 10);

// Header tabel
$pdf->SetFillColor(200, 220, 255);
$pdf->Cell(10, 7, 'No', 1, 0, 'C', true);
$pdf->Cell(30, 7, 'NIM', 1, 0, 'C', true);
$pdf->Cell(60, 7, 'Nama', 1, 0, 'C', true);
$pdf->Cell(30, 7, 'Kelas', 1, 0, 'C', true);
$pdf->Cell(30, 7, 'Status', 1, 0, 'C', true);
$pdf->Cell(30, 7, 'Keterangan', 1, 1, 'C', true);

// Isi tabel
$no = 1;
$result_mahasiswa->data_seek(0); // Reset pointer
while ($row = $result_mahasiswa->fetch_assoc()) {
    $pdf->Cell(10, 7, $no++, 1, 0, 'C');
    $pdf->Cell(30, 7, $row['nim'], 1, 0, 'L');
    $pdf->Cell(60, 7, $row['nama'], 1, 0, 'L');
    $pdf->Cell(30, 7, $row['kelas_angkatan'], 1, 0, 'C');

    // Status kehadiran
    $status = '-';
    if ($row['status'] == 'hadir') {
        $status = 'Hadir';
    } elseif ($row['status'] == 'izin') {
        $status = 'Izin';
    } elseif ($row['status'] == 'sakit') {
        $status = 'Sakit';
    } elseif ($row['status'] == 'alpa') {
        $status = 'Alpa';
    }

    $pdf->Cell(30, 7, $status, 1, 0, 'C');
    $pdf->Cell(30, 7, $row['keterangan'] ? $row['keterangan'] : '-', 1, 1, 'L');
}

// Statistik Kehadiran
$pdf->Ln(10);
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(0, 10, 'Statistik Kehadiran', 0, 1);
$pdf->SetFont('Arial', '', 10);

// Hitung statistik
$total_mahasiswa = $result_mahasiswa->num_rows;
$hadir = 0;
$izin = 0;
$sakit = 0;
$alpa = 0;

$result_mahasiswa->data_seek(0); // Reset pointer
while ($row = $result_mahasiswa->fetch_assoc()) {
    if ($row['status'] == 'hadir') {
        $hadir++;
    } elseif ($row['status'] == 'izin') {
        $izin++;
    } elseif ($row['status'] == 'sakit') {
        $sakit++;
    } elseif ($row['status'] == 'alpa') {
        $alpa++;
    }
}

$pdf->Cell(40, 7, 'Total Mahasiswa', 0);
$pdf->Cell(5, 7, ':', 0);
$pdf->Cell(0, 7, $total_mahasiswa, 0, 1);

$pdf->Cell(40, 7, 'Hadir', 0);
$pdf->Cell(5, 7, ':', 0);
$pdf->Cell(0, 7, $hadir . ' (' . ($total_mahasiswa > 0 ? round(($hadir / $total_mahasiswa) * 100, 2) : 0) . '%)', 0, 1);

$pdf->Cell(40, 7, 'Izin', 0);
$pdf->Cell(5, 7, ':', 0);
$pdf->Cell(0, 7, $izin . ' (' . ($total_mahasiswa > 0 ? round(($izin / $total_mahasiswa) * 100, 2) : 0) . '%)', 0, 1);

$pdf->Cell(40, 7, 'Sakit', 0);
$pdf->Cell(5, 7, ':', 0);
$pdf->Cell(0, 7, $sakit . ' (' . ($total_mahasiswa > 0 ? round(($sakit / $total_mahasiswa) * 100, 2) : 0) . '%)', 0, 1);

$pdf->Cell(40, 7, 'Alpa', 0);
$pdf->Cell(5, 7, ':', 0);
$pdf->Cell(0, 7, $alpa . ' (' . ($total_mahasiswa > 0 ? round(($alpa / $total_mahasiswa) * 100, 2) : 0) . '%)', 0, 1);

// Tanda tangan
$pdf->Ln(20);
$pdf->Cell(130, 7, '', 0);
$pdf->Cell(0, 7, 'Dosen Pengampu,', 0, 1, 'L');

$pdf->Ln(15);
$pdf->Cell(130, 7, '', 0);
$pdf->Cell(0, 7, $sesi['nama_dosen'], 0, 1, 'L');

// Output PDF
$pdf->Output('Daftar_Hadir_' . $sesi['nama_kelas'] . '_Pertemuan_' . $sesi['pertemuan'] . '.pdf', 'I');
