<?php
date_default_timezone_set('Asia/Jakarta');
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

// Periksa dan tutup sesi yang sudah melewati waktu tutup
periksa_sesi_kadaluarsa($koneksi);

// Filter berdasarkan tanggal
$tanggal_filter = isset($_GET['tanggal']) ? bersihkan_input($_GET['tanggal']) : '';

// Ambil riwayat absensi
$query_riwayat = "
    SELECT a.id, a.tanggal, a.status, a.keterangan, sa.pertemuan, sa.materi, k.nama as nama_kelas, mk.nama as nama_matkul, d.nama as nama_dosen
    FROM absensi a
    JOIN sesi_absensi sa ON a.id_sesi = sa.id
    JOIN kelas k ON sa.id_kelas = k.id
    JOIN mata_kuliah mk ON k.id_matkul = mk.id
    JOIN dosen d ON k.id_dosen = d.id
    WHERE a.id_mahasiswa = ?
";

// Tambahkan filter tanggal jika ada
if (!empty($tanggal_filter)) {
    $query_riwayat .= " AND DATE(a.tanggal) = ?";
    $query_riwayat .= " ORDER BY a.tanggal DESC, sa.pertemuan";

    $stmt_riwayat = $koneksi->prepare($query_riwayat);
    $stmt_riwayat->bind_param("is", $id_mahasiswa_asli, $tanggal_filter);
} else {
    $query_riwayat .= " ORDER BY a.tanggal DESC, sa.pertemuan";

    $stmt_riwayat = $koneksi->prepare($query_riwayat);
    $stmt_riwayat->bind_param("i", $id_mahasiswa_asli);
}

$stmt_riwayat->execute();
$result_riwayat = $stmt_riwayat->get_result();

// Hitung statistik kehadiran
$query_statistik = "
    SELECT 
        COUNT(CASE WHEN a.status = 'hadir' THEN 1 END) as jumlah_hadir,
        COUNT(CASE WHEN a.status = 'izin' THEN 1 END) as jumlah_izin,
        COUNT(CASE WHEN a.status = 'sakit' THEN 1 END) as jumlah_sakit,
        COUNT(CASE WHEN a.status = 'alpa' THEN 1 END) as jumlah_alpa,
        COUNT(*) as total
    FROM absensi a
    WHERE a.id_mahasiswa = ?
";

$stmt_statistik = $koneksi->prepare($query_statistik);
$stmt_statistik->bind_param("i", $id_mahasiswa_asli);
$stmt_statistik->execute();
$result_statistik = $stmt_statistik->get_result();
$statistik = $result_statistik->fetch_assoc();

// Hitung persentase kehadiran
$total_absensi = $statistik['total'];
$jumlah_hadir = $statistik['jumlah_hadir'];
$jumlah_izin = $statistik['jumlah_izin'];
$jumlah_sakit = $statistik['jumlah_sakit'];
$jumlah_alpa = $statistik['jumlah_alpa'];

$persentase_kehadiran = 0;
if ($total_absensi > 0) {
    $persentase_kehadiran = (($jumlah_hadir + $jumlah_izin + $jumlah_sakit) / $total_absensi) * 100;
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kehadiran Saya - Sistem Monitoring Kehadiran Praktikum</title>
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
                <li><a href="index.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a href="kelas.php"><i class="fas fa-door-open"></i> Kelas Saya</a></li>
                <li><a href="kehadiran.php" class="active"><i class="fas fa-clipboard-check"></i> Kehadiran Saya</a></li>
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
                <h1>Kehadiran Saya</h1>
            </div>

            <div class="row">
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header">
                            <h2>Statistik Kehadiran</h2>
                        </div>
                        <div class="card-body">
                            <div class="stat-card">
                                <h3><?php echo number_format($persentase_kehadiran, 2); ?>%</h3>
                                <p>Persentase Kehadiran</p>
                            </div>

                            <div class="row">
                                <div class="col-6">
                                    <div class="stat-card">
                                        <h3><?php echo $jumlah_hadir; ?></h3>
                                        <p>Hadir</p>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="stat-card">
                                        <h3><?php echo $jumlah_izin; ?></h3>
                                        <p>Izin</p>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-6">
                                    <div class="stat-card">
                                        <h3><?php echo $jumlah_sakit; ?></h3>
                                        <p>Sakit</p>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="stat-card">
                                        <h3><?php echo $jumlah_alpa; ?></h3>
                                        <p>Alpa</p>
                                    </div>
                                </div>
                            </div>

                            <div class="stat-card">
                                <h3><?php echo $total_absensi; ?></h3>
                                <p>Total Pertemuan</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header">
                            <h2>Filter Kehadiran</h2>
                        </div>
                        <div class="card-body">
                            <form method="GET" action="">
                                <div class="row">
                                    <div class="col-md-8">
                                        <div class="form-group">
                                            <label for="tanggal_filter">Tanggal</label>
                                            <input type="date" id="tanggal_filter" name="tanggal" value="<?php echo $tanggal_filter; ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>&nbsp;</label>
                                            <button type="submit" class="btn btn-primary btn-block">Filter</button>
                                        </div>
                                    </div>
                                </div>
                            </form>

                            <?php if (!empty($tanggal_filter)): ?>
                                <div class="mt-2">
                                    <a href="kehadiran.php" class="btn btn-secondary">Reset Filter</a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mt-4">
                <div class="card-header">
                    <h2>Riwayat Kehadiran</h2>
                </div>
                <div class="card-body">
                    <?php if ($result_riwayat->num_rows > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>No</th>
                                        <th>Tanggal</th>
                                        <th>Kelas</th>
                                        <th>Mata Kuliah</th>
                                        <th>Pertemuan</th>
                                        <th>Materi</th>
                                        <th>Dosen</th>
                                        <th>Status</th>
                                        <th>Keterangan</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $no = 1;
                                    while ($row = $result_riwayat->fetch_assoc()):
                                    ?>
                                        <tr>
                                            <td><?php echo $no++; ?></td>
                                            <td><?php echo format_tanggal_indonesia($row['tanggal']); ?></td>
                                            <td><?php echo $row['nama_kelas']; ?></td>
                                            <td><?php echo $row['nama_matkul']; ?></td>
                                            <td>Pertemuan <?php echo $row['pertemuan']; ?></td>
                                            <td><?php echo $row['materi']; ?></td>
                                            <td><?php echo $row['nama_dosen']; ?></td>
                                            <td><?php echo status_kehadiran($row['status']); ?></td>
                                            <td><?php echo $row['keterangan'] ? $row['keterangan'] : '-'; ?></td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-center">Belum ada riwayat kehadiran<?php echo !empty($tanggal_filter) ? ' pada tanggal tersebut' : ''; ?>.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="../assets/js/script.js"></script>
</body>

</html>