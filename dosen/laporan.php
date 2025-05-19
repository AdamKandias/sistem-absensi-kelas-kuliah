<?php
session_start();
require_once '../config/koneksi.php';
require_once '../config/fungsi.php';
require_once __DIR__ . '/../library/fpdf/fpdf.php';

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

// Ambil kelas yang diajar oleh dosen untuk dropdown
$query_kelas = "
    SELECT k.*, mk.nama as nama_matkul, j.nama as nama_jurusan
    FROM kelas k
    JOIN mata_kuliah mk ON k.id_matkul = mk.id
    JOIN jurusan j ON k.id_jurusan = j.id
    WHERE k.id_dosen = ?
    ORDER BY k.tahun_ajaran DESC, k.semester DESC, mk.nama
";
$stmt_kelas = $koneksi->prepare($query_kelas);
$stmt_kelas->bind_param("i", $id_dosen_asli);
$stmt_kelas->execute();
$result_kelas = $stmt_kelas->get_result();

// Filter berdasarkan kelas
$id_kelas = isset($_GET['id_kelas']) ? bersihkan_input($_GET['id_kelas']) : null;

// Jika ada kelas yang dipilih, ambil data rekap kehadiran
if ($id_kelas) {
    // Cek apakah kelas ini milik dosen yang sedang login
    $query_cek_kelas = "SELECT id FROM kelas WHERE id = ? AND id_dosen = ?";
    $stmt_cek_kelas = $koneksi->prepare($query_cek_kelas);
    $stmt_cek_kelas->bind_param("ii", $id_kelas, $id_dosen_asli);
    $stmt_cek_kelas->execute();
    $result_cek_kelas = $stmt_cek_kelas->get_result();

    if ($result_cek_kelas->num_rows == 0) {
        $pesan_error = "Anda tidak memiliki akses ke kelas ini!";
        $id_kelas = null;
    } else {
        // Ambil informasi kelas
        $query_info_kelas = "
            SELECT k.*, mk.nama as nama_matkul, j.nama as nama_jurusan
            FROM kelas k
            JOIN mata_kuliah mk ON k.id_matkul = mk.id
            JOIN jurusan j ON k.id_jurusan = j.id
            WHERE k.id = ?
        ";
        $stmt_info_kelas = $koneksi->prepare($query_info_kelas);
        $stmt_info_kelas->bind_param("i", $id_kelas);
        $stmt_info_kelas->execute();
        $result_info_kelas = $stmt_info_kelas->get_result();
        $info_kelas = $result_info_kelas->fetch_assoc();

        // Ambil semua sesi absensi untuk kelas ini
        $query_sesi = "
            SELECT id, pertemuan, tanggal, materi
            FROM sesi_absensi
            WHERE id_kelas = ?
            ORDER BY pertemuan
        ";
        $stmt_sesi = $koneksi->prepare($query_sesi);
        $stmt_sesi->bind_param("i", $id_kelas);
        $stmt_sesi->execute();
        $result_sesi = $stmt_sesi->get_result();

        // Ambil semua mahasiswa di kelas ini
        $query_mahasiswa = "
            SELECT m.id, m.nim, m.nama, m.kelas_angkatan
            FROM mahasiswa m
            JOIN kelas_mahasiswa km ON m.id = km.id_mahasiswa
            WHERE km.id_kelas = ?
            ORDER BY m.nama
        ";
        $stmt_mahasiswa = $koneksi->prepare($query_mahasiswa);
        $stmt_mahasiswa->bind_param("i", $id_kelas);
        $stmt_mahasiswa->execute();
        $result_mahasiswa = $stmt_mahasiswa->get_result();

        // Ambil data kehadiran untuk semua mahasiswa dan semua sesi
        $query_kehadiran = "
            SELECT a.id_mahasiswa, a.id_sesi, a.status
            FROM absensi a
            JOIN sesi_absensi sa ON a.id_sesi = sa.id
            WHERE sa.id_kelas = ?
        ";
        $stmt_kehadiran = $koneksi->prepare($query_kehadiran);
        $stmt_kehadiran->bind_param("i", $id_kelas);
        $stmt_kehadiran->execute();
        $result_kehadiran = $stmt_kehadiran->get_result();

        // Buat array untuk menyimpan data kehadiran
        $kehadiran = [];
        while ($row = $result_kehadiran->fetch_assoc()) {
            $kehadiran[$row['id_mahasiswa']][$row['id_sesi']] = $row['status'];
        }

        // Hitung statistik kehadiran per mahasiswa
        $statistik_mahasiswa = [];
        $result_mahasiswa->data_seek(0); // Reset pointer
        while ($mahasiswa = $result_mahasiswa->fetch_assoc()) {
            $id_mahasiswa = $mahasiswa['id'];
            $statistik_mahasiswa[$id_mahasiswa] = [
                'hadir' => 0,
                'izin' => 0,
                'sakit' => 0,
                'alpa' => 0,
                'total' => 0,
                'persentase' => 0
            ];

            $result_sesi->data_seek(0); // Reset pointer
            $total_sesi = $result_sesi->num_rows;

            if ($total_sesi > 0) {
                while ($sesi = $result_sesi->fetch_assoc()) {
                    $id_sesi = $sesi['id'];
                    if (isset($kehadiran[$id_mahasiswa][$id_sesi])) {
                        $status = $kehadiran[$id_mahasiswa][$id_sesi];
                        $statistik_mahasiswa[$id_mahasiswa][$status]++;

                        if ($status == 'hadir' || $status == 'izin' || $status == 'sakit') {
                            $statistik_mahasiswa[$id_mahasiswa]['total']++;
                        }
                    }
                }

                // Hitung persentase kehadiran
                $statistik_mahasiswa[$id_mahasiswa]['persentase'] = ($statistik_mahasiswa[$id_mahasiswa]['total'] / $total_sesi) * 100;
            }
        }
    }
}

// Ubah bagian proses export ke PDF
if (isset($_GET['export']) && $_GET['export'] == 'pdf' && $id_kelas) {
    // Fungsi untuk menghasilkan PDF
    function generate_pdf($html, $filename)
    {
        global $info_kelas, $dosen, $result_mahasiswa, $statistik_mahasiswa, $result_sesi;

        // Buat class PDF dengan header dan footer
        class PDF extends FPDF
        {
            // Header halaman
            function Header()
            {
                // Logo (jika ada)
                // $this->Image('logo.png', 10, 6, 30);

                // Judul institusi
                $this->SetFont('Arial', 'B', 14);
                $this->Cell(0, 10, 'Kampus PENS', 0, 1, 'C');
                $this->SetFont('Arial', '', 12);
                $this->Cell(0, 6, 'FAKULTAS ILMU KOMPUTER', 0, 1, 'C');
                $this->Cell(0, 6, 'Jl. Pendidikan No. 123, Kota Surabaya, 12345', 0, 1, 'C');
                $this->Cell(0, 6, 'Telp. (021) 123-4567 | Email: info@pens.ac.id', 0, 1, 'C');

                // Garis pembatas
                $this->SetLineWidth(0.5);
                $this->Line(10, 38, 200, 38);
                $this->SetLineWidth(0.2);
                $this->Line(10, 39, 200, 39);

                // Spasi setelah header
                $this->Ln(10);
            }

            // Footer halaman
            function Footer()
            {
                // Posisi 1.5 cm dari bawah
                $this->SetY(-15);

                // Garis pembatas
                $this->SetLineWidth(0.2);
                $this->Line(10, $this->GetY(), 200, $this->GetY());

                // Arial italic 8
                $this->SetFont('Arial', 'I', 8);

                // Nomor halaman
                $this->Cell(0, 10, 'Halaman ' . $this->PageNo() . '/{nb}', 0, 0, 'C');
            }
        }

        // Inisialisasi PDF
        $pdf = new PDF();
        $pdf->AliasNbPages(); // Untuk mendapatkan total halaman
        $pdf->AddPage();

        // Judul dokumen
        $pdf->SetFont('Arial', 'B', 16);
        $pdf->Cell(0, 10, 'LAPORAN KEHADIRAN MAHASISWA', 0, 1, 'C');

        // Nomor dokumen
        $pdf->SetFont('Arial', 'I', 10);
        $pdf->Cell(0, 6, 'No. Dokumen: REG/' . date('Ymd') . '/' . rand(1000, 9999), 0, 1, 'C');

        // Spasi
        $pdf->Ln(5);

        // Informasi kelas
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Cell(0, 8, 'INFORMASI KELAS', 0, 1, 'L');
        $pdf->SetFont('Arial', '', 11);

        // Tabel info kelas dengan warna latar
        $pdf->SetFillColor(240, 240, 240);
        $pdf->Cell(40, 8, 'Kelas', 1, 0, 'L', true);
        $pdf->Cell(60, 8, $info_kelas['nama'], 1, 0, 'L');
        $pdf->Cell(40, 8, 'Mata Kuliah', 1, 0, 'L', true);
        $pdf->Cell(50, 8, $info_kelas['nama_matkul'], 1, 1, 'L');

        $pdf->Cell(40, 8, 'Jurusan', 1, 0, 'L', true);
        $pdf->Cell(60, 8, $info_kelas['nama_jurusan'], 1, 0, 'L');
        $pdf->Cell(40, 8, 'Tahun Ajaran', 1, 0, 'L', true);
        $pdf->Cell(50, 8, $info_kelas['tahun_ajaran'] . ' - ' . $info_kelas['semester'], 1, 1, 'L');

        $pdf->Cell(40, 8, 'Dosen', 1, 0, 'L', true);
        $pdf->Cell(150, 8, $dosen['nama'], 1, 1, 'L');

        // Spasi
        $pdf->Ln(5);

        // Judul tabel rekap
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Cell(0, 8, 'REKAP KEHADIRAN MAHASISWA', 0, 1, 'L');

        // Header tabel rekap dengan warna latar
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->SetFillColor(50, 50, 150);
        $pdf->SetTextColor(255, 255, 255);

        $header = ['No', 'NIM', 'Nama', 'Kelas', 'Hadir', 'Izin', 'Sakit', 'Alpa', 'Persentase'];
        $widths = [10, 25, 50, 20, 15, 15, 15, 15, 25];

        foreach ($header as $i => $col) {
            $pdf->Cell($widths[$i], 8, $col, 1, 0, 'C', true);
        }
        $pdf->Ln();

        // Data mahasiswa
        $pdf->SetFont('Arial', '', 10);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetFillColor(245, 245, 245);

        $no = 1;
        $result_mahasiswa->data_seek(0); // reset pointer
        $fill = false;

        while ($mahasiswa = $result_mahasiswa->fetch_assoc()) {
            $id = $mahasiswa['id'];
            $pdf->Cell($widths[0], 7, $no++, 1, 0, 'C', $fill);
            $pdf->Cell($widths[1], 7, $mahasiswa['nim'], 1, 0, 'L', $fill);
            $pdf->Cell($widths[2], 7, $mahasiswa['nama'], 1, 0, 'L', $fill);
            $pdf->Cell($widths[3], 7, $mahasiswa['kelas_angkatan'], 1, 0, 'C', $fill);
            $pdf->Cell($widths[4], 7, $statistik_mahasiswa[$id]['hadir'], 1, 0, 'C', $fill);
            $pdf->Cell($widths[5], 7, $statistik_mahasiswa[$id]['izin'], 1, 0, 'C', $fill);
            $pdf->Cell($widths[6], 7, $statistik_mahasiswa[$id]['sakit'], 1, 0, 'C', $fill);
            $pdf->Cell($widths[7], 7, $statistik_mahasiswa[$id]['alpa'], 1, 0, 'C', $fill);

            // Warna untuk persentase kehadiran
            $persentase = $statistik_mahasiswa[$id]['persentase'];
            if ($persentase >= 80) {
                $pdf->SetTextColor(0, 128, 0); // Hijau untuk kehadiran baik
            } elseif ($persentase >= 60) {
                $pdf->SetTextColor(255, 128, 0); // Oranye untuk kehadiran cukup
            } else {
                $pdf->SetTextColor(255, 0, 0); // Merah untuk kehadiran kurang
            }

            $pdf->Cell($widths[8], 7, number_format($persentase, 2) . '%', 1, 0, 'C', $fill);
            $pdf->SetTextColor(0, 0, 0); // Kembalikan warna teks

            $pdf->Ln();
            $fill = !$fill; // Alternasi warna baris
        }

        // Ringkasan statistik
        $pdf->Ln(10);
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Cell(0, 8, 'RINGKASAN STATISTIK', 0, 1, 'L');
        $pdf->SetFont('Arial', '', 11);

        // Hitung total
        $total_mahasiswa = $result_mahasiswa->num_rows;
        $hadir_baik = 0;
        $hadir_cukup = 0;
        $hadir_kurang = 0;

        $result_mahasiswa->data_seek(0);
        while ($mahasiswa = $result_mahasiswa->fetch_assoc()) {
            $id = $mahasiswa['id'];
            $persentase = $statistik_mahasiswa[$id]['persentase'];

            if ($persentase >= 80) {
                $hadir_baik++;
            } elseif ($persentase >= 60) {
                $hadir_cukup++;
            } else {
                $hadir_kurang++;
            }
        }

        // Tampilkan statistik
        $pdf->SetFillColor(240, 240, 240);
        $pdf->Cell(60, 8, 'Total Mahasiswa', 1, 0, 'L', true);
        $pdf->Cell(30, 8, $total_mahasiswa, 1, 1, 'C');

        $pdf->Cell(60, 8, 'Kehadiran Baik (>=80%)', 1, 0, 'L', true);
        $pdf->Cell(30, 8, $hadir_baik, 1, 0, 'C');
        $pdf->Cell(40, 8, 'Persentase', 1, 0, 'L', true);
        $pdf->Cell(30, 8, number_format(($hadir_baik / $total_mahasiswa) * 100, 2) . '%', 1, 1, 'C');

        $pdf->Cell(60, 8, 'Kehadiran Cukup (60-79%)', 1, 0, 'L', true);
        $pdf->Cell(30, 8, $hadir_cukup, 1, 0, 'C');
        $pdf->Cell(40, 8, 'Persentase', 1, 0, 'L', true);
        $pdf->Cell(30, 8, number_format(($hadir_cukup / $total_mahasiswa) * 100, 2) . '%', 1, 1, 'C');

        $pdf->Cell(60, 8, 'Kehadiran Kurang (<60%)', 1, 0, 'L', true);
        $pdf->Cell(30, 8, $hadir_kurang, 1, 0, 'C');
        $pdf->Cell(40, 8, 'Persentase', 1, 0, 'L', true);
        $pdf->Cell(30, 8, number_format(($hadir_kurang / $total_mahasiswa) * 100, 2) . '%', 1, 1, 'C');

        // Tanda tangan
        $pdf->Ln(15);
        $pdf->SetFont('Arial', '', 11);
        $pdf->Cell(120, 6, '', 0, 0);
        $pdf->Cell(70, 6, 'Kota Teknologi, ' . date('d F Y'), 0, 1, 'C');
        $pdf->Cell(120, 6, '', 0, 0);
        $pdf->Cell(70, 6, 'Dosen Pengampu,', 0, 1, 'C');

        $pdf->Ln(15); // Spasi untuk tanda tangan

        $pdf->Cell(120, 6, '', 0, 0);
        $pdf->SetFont('Arial', 'BU', 11);
        $pdf->Cell(70, 6, $dosen['nama'], 0, 1, 'C');
        $pdf->SetFont('Arial', '', 11);
        $pdf->Cell(120, 6, '', 0, 0);
        $pdf->Cell(70, 6, 'NIP: ' . (isset($dosen['nip']) ? $dosen['nip'] : '-'), 0, 1, 'C');

        // Output PDF
        $pdf->Output('D', $filename);
        exit;
    }

    // Panggil fungsi generate_pdf
    $filename = 'Laporan_Kehadiran_' . $info_kelas['nama'] . '_' . date('Ymd') . '.pdf';
    generate_pdf('', $filename);
    exit;
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Kehadiran - Sistem Monitoring Kehadiran Praktikum</title>
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
                <li><a href="index.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a href="kelas.php"><i class="fas fa-door-open"></i> Kelas Saya</a></li>
                <li><a href="absensi.php"><i class="fas fa-clipboard-check"></i> Kelola Absensi</a></li>
                <li><a href="laporan.php" class="active"><i class="fas fa-file-alt"></i> Laporan Kehadiran</a></li>
                <li><a href="profil.php"><i class="fas fa-user"></i> Profil Saya</a></li>
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
            <div class="dashboard-header">
                <h1>Laporan Kehadiran</h1>
            </div>

            <?php if (isset($pesan_error)): ?>
                <div class="alert alert-danger">
                    <?php echo $pesan_error; ?>
                </div>
            <?php endif; ?>

            <div class="card">
                <div class="card-header">
                    <h2>Pilih Kelas</h2>
                </div>
                <div class="card-body">
                    <form method="GET" action="">
                        <div class="row">
                            <div class="col-md-8">
                                <div class="form-group">
                                    <label for="id_kelas">Kelas</label>
                                    <select id="id_kelas" name="id_kelas" required>
                                        <option value="">Pilih Kelas</option>
                                        <?php
                                        $result_kelas->data_seek(0); // Reset pointer
                                        while ($kelas = $result_kelas->fetch_assoc()):
                                        ?>
                                            <option value="<?php echo $kelas['id']; ?>" <?php echo ($id_kelas == $kelas['id']) ? 'selected' : ''; ?>>
                                                <?php echo $kelas['nama'] . ' - ' . $kelas['nama_matkul'] . ' (' . $kelas['tahun_ajaran'] . ' - ' . $kelas['semester'] . ')'; ?>
                                            </option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>&nbsp;</label>
                                    <button type="submit" class="btn btn-primary btn-block">Tampilkan Laporan</button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <?php if ($id_kelas): ?>
                <div class="card">
                    <div class="card-header d-flex justify-content-between">
                        <h2>Rekap Kehadiran Mahasiswa</h2>
                        <a href="laporan.php?id_kelas=<?php echo $id_kelas; ?>&export=pdf" class="btn btn-primary" target="_blank">
                            <i class="fas fa-file-pdf"></i> Export PDF
                        </a>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <tr>
                                    <th>Kelas</th>
                                    <td><?php echo $info_kelas['nama']; ?></td>
                                    <th>Mata Kuliah</th>
                                    <td><?php echo $info_kelas['nama_matkul']; ?></td>
                                </tr>
                                <tr>
                                    <th>Jurusan</th>
                                    <td><?php echo $info_kelas['nama_jurusan']; ?></td>
                                    <th>Tahun Ajaran</th>
                                    <td><?php echo $info_kelas['tahun_ajaran'] . ' - ' . $info_kelas['semester']; ?></td>
                                </tr>
                            </table>
                        </div>

                        <div class="form-group mt-3">
                            <input type="text" id="filterInput" placeholder="Cari mahasiswa..." class="form-control">
                        </div>

                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>No</th>
                                        <th>NIM</th>
                                        <th>Nama</th>
                                        <th>Kelas</th>
                                        <th>Hadir</th>
                                        <th>Izin</th>
                                        <th>Sakit</th>
                                        <th>Alpa</th>
                                        <th>Persentase</th>
                                        <th>Detail</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $no = 1;
                                    $result_mahasiswa->data_seek(0); // Reset pointer
                                    while ($mahasiswa = $result_mahasiswa->fetch_assoc()):
                                        $id_mahasiswa = $mahasiswa['id'];
                                    ?>
                                        <tr>
                                            <td><?php echo $no++; ?></td>
                                            <td><?php echo $mahasiswa['nim']; ?></td>
                                            <td><?php echo $mahasiswa['nama']; ?></td>
                                            <td><?php echo $mahasiswa['kelas_angkatan']; ?></td>
                                            <td class="text-center"><?php echo $statistik_mahasiswa[$id_mahasiswa]['hadir']; ?></td>
                                            <td class="text-center"><?php echo $statistik_mahasiswa[$id_mahasiswa]['izin']; ?></td>
                                            <td class="text-center"><?php echo $statistik_mahasiswa[$id_mahasiswa]['sakit']; ?></td>
                                            <td class="text-center"><?php echo $statistik_mahasiswa[$id_mahasiswa]['alpa']; ?></td>
                                            <td class="text-center">
                                                <?php echo number_format($statistik_mahasiswa[$id_mahasiswa]['persentase'], 2); ?>%
                                            </td>
                                            <td>
                                                <a href="#" class="btn btn-sm btn-info" onclick="showDetail(<?php echo $id_mahasiswa; ?>, '<?php echo $mahasiswa['nama']; ?>')">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Tabel Detail Kehadiran Per Pertemuan -->
                <div class="card mt-4">
                    <div class="card-header">
                        <h2>Detail Kehadiran Per Pertemuan</h2>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-bordered">
                                <thead>
                                    <tr>
                                        <th>No</th>
                                        <th>NIM</th>
                                        <th>Nama</th>
                                        <?php
                                        $result_sesi->data_seek(0); // Reset pointer
                                        while ($sesi = $result_sesi->fetch_assoc()):
                                        ?>
                                            <th>P<?php echo $sesi['pertemuan']; ?></th>
                                        <?php endwhile; ?>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $no = 1;
                                    $result_mahasiswa->data_seek(0); // Reset pointer
                                    while ($mahasiswa = $result_mahasiswa->fetch_assoc()):
                                        $id_mahasiswa = $mahasiswa['id'];
                                    ?>
                                        <tr>
                                            <td><?php echo $no++; ?></td>
                                            <td><?php echo $mahasiswa['nim']; ?></td>
                                            <td><?php echo $mahasiswa['nama']; ?></td>
                                            <?php
                                            $result_sesi->data_seek(0); // Reset pointer
                                            while ($sesi = $result_sesi->fetch_assoc()):
                                                $id_sesi = $sesi['id'];
                                                $status = isset($kehadiran[$id_mahasiswa][$id_sesi]) ? $kehadiran[$id_mahasiswa][$id_sesi] : '-';

                                                // Tampilkan status dengan warna
                                                if ($status == 'hadir') {
                                                    echo '<td class="text-center"><span class="badge badge-success">H</span></td>';
                                                } elseif ($status == 'izin') {
                                                    echo '<td class="text-center"><span class="badge badge-warning">I</span></td>';
                                                } elseif ($status == 'sakit') {
                                                    echo '<td class="text-center"><span class="badge badge-info">S</span></td>';
                                                } elseif ($status == 'alpa') {
                                                    echo '<td class="text-center"><span class="badge badge-danger">A</span></td>';
                                                } else {
                                                    echo '<td class="text-center"><span class="badge badge-secondary">-</span></td>';
                                                }
                                            endwhile;
                                            ?>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-3">
                            <p><strong>Keterangan:</strong></p>
                            <p>
                                <span class="badge badge-success">H</span> = Hadir,
                                <span class="badge badge-warning">I</span> = Izin,
                                <span class="badge badge-info">S</span> = Sakit,
                                <span class="badge badge-danger">A</span> = Alpa,
                                <span class="badge badge-secondary">-</span> = Belum Ada Status
                            </p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Modal Detail Mahasiswa -->
    <div id="detailModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Detail Kehadiran <span id="mahasiswa_nama"></span></h2>
                <span class="close">&times;</span>
            </div>
            <div class="modal-body">
                <div id="detail_content">
                    <!-- Konten detail akan diisi oleh JavaScript -->
                </div>
            </div>
        </div>
    </div>

    <script src="../assets/js/script.js"></script>
    <script>
        // Modal Detail
        const modal = document.getElementById('detailModal');
        const closeBtn = document.getElementsByClassName('close')[0];

        closeBtn.onclick = function() {
            modal.style.display = "none";
        }

        window.onclick = function(event) {
            if (event.target == modal) {
                modal.style.display = "none";
            }
        }

        function showDetail(id_mahasiswa, nama) {
            document.getElementById('mahasiswa_nama').textContent = nama;

            // Buat tabel detail kehadiran
            let detailHTML = '<table class="table table-striped">';
            detailHTML += '<thead><tr><th>Pertemuan</th><th>Tanggal</th><th>Materi</th><th>Status</th></tr></thead>';
            detailHTML += '<tbody>';

            <?php
            if (isset($result_sesi)) {
                echo "const kehadiran = " . json_encode($kehadiran) . ";";
                echo "const sesiData = [";
                $result_sesi->data_seek(0); // Reset pointer
                while ($sesi = $result_sesi->fetch_assoc()) {
                    echo "{
                        id: " . $sesi['id'] . ",
                        pertemuan: " . $sesi['pertemuan'] . ",
                        tanggal: '" . format_tanggal_indonesia($sesi['tanggal']) . "',
                        materi: '" . $sesi['materi'] . "'
                    },";
                }
                echo "];";
            }
            ?>

            if (typeof sesiData !== 'undefined') {
                sesiData.forEach(sesi => {
                    let status = '-';
                    let statusClass = 'badge-secondary';

                    if (kehadiran[id_mahasiswa] && kehadiran[id_mahasiswa][sesi.id]) {
                        status = kehadiran[id_mahasiswa][sesi.id];

                        if (status === 'hadir') {
                            statusClass = 'badge-success';
                            status = 'Hadir';
                        } else if (status === 'izin') {
                            statusClass = 'badge-warning';
                            status = 'Izin';
                        } else if (status === 'sakit') {
                            statusClass = 'badge-info';
                            status = 'Sakit';
                        } else if (status === 'alpa') {
                            statusClass = 'badge-danger';
                            status = 'Alpa';
                        }
                    }

                    detailHTML += `<tr>
                        <td>Pertemuan ${sesi.pertemuan}</td>
                        <td>${sesi.tanggal}</td>
                        <td>${sesi.materi}</td>
                        <td><span class="badge ${statusClass}">${status}</span></td>
                    </tr>`;
                });
            }

            detailHTML += '</tbody></table>';

            document.getElementById('detail_content').innerHTML = detailHTML;
            modal.style.display = "block";
        }
    </script>
</body>

</html>