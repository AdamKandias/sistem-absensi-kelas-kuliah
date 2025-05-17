<?php
$judul_halaman = "Dashboard Admin";
require_once '../includes/header_admin.php';

// Ambil statistik untuk dashboard
$query_jumlah_mahasiswa = "SELECT COUNT(*) as total FROM mahasiswa";
$query_jumlah_dosen = "SELECT COUNT(*) as total FROM dosen";
$query_jumlah_kelas = "SELECT COUNT(*) as total FROM kelas";
$query_jumlah_jurusan = "SELECT COUNT(*) as total FROM jurusan";

$result_mahasiswa = $koneksi->query($query_jumlah_mahasiswa);
$result_dosen = $koneksi->query($query_jumlah_dosen);
$result_kelas = $koneksi->query($query_jumlah_kelas);
$result_jurusan = $koneksi->query($query_jumlah_jurusan);

$jumlah_mahasiswa = $result_mahasiswa->fetch_assoc()['total'];
$jumlah_dosen = $result_dosen->fetch_assoc()['total'];
$jumlah_kelas = $result_kelas->fetch_assoc()['total'];
$jumlah_jurusan = $result_jurusan->fetch_assoc()['total'];

// Ambil data absensi terbaru
$query_absensi_terbaru = "
    SELECT a.id, a.tanggal, a.status, m.nama as nama_mahasiswa, k.nama as nama_kelas, mk.nama as nama_matkul
    FROM absensi a
    JOIN mahasiswa m ON a.id_mahasiswa = m.id
    JOIN sesi_absensi sa ON a.id_sesi = sa.id
    JOIN kelas k ON sa.id_kelas = k.id
    JOIN mata_kuliah mk ON k.id_matkul = mk.id
    ORDER BY a.tanggal DESC
    LIMIT 10
";
$result_absensi_terbaru = $koneksi->query($query_absensi_terbaru);
?>

<div class="dashboard-header">
    <h1>Dashboard Admin</h1>
    <p>Selamat datang, <?php echo $_SESSION['nama']; ?>!</p>
</div>

<div class="dashboard-stats">
    <div class="stat-card">
        <i class="fas fa-user-graduate fa-3x mb-3" style="color: var(--warna-sekunder);"></i>
        <h3><?php echo $jumlah_mahasiswa; ?></h3>
        <p>Total Mahasiswa</p>
    </div>
    <div class="stat-card">
        <i class="fas fa-chalkboard-teacher fa-3x mb-3" style="color: var(--warna-sekunder);"></i>
        <h3><?php echo $jumlah_dosen; ?></h3>
        <p>Total Dosen</p>
    </div>
    <div class="stat-card">
        <i class="fas fa-door-open fa-3x mb-3" style="color: var(--warna-sekunder);"></i>
        <h3><?php echo $jumlah_kelas; ?></h3>
        <p>Total Kelas</p>
    </div>
    <div class="stat-card">
        <i class="fas fa-building fa-3x mb-3" style="color: var(--warna-sekunder);"></i>
        <h3><?php echo $jumlah_jurusan; ?></h3>
        <p>Total Jurusan</p>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h2>Absensi Terbaru</h2>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Tanggal</th>
                        <th>Mahasiswa</th>
                        <th>Kelas</th>
                        <th>Mata Kuliah</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $no = 1;
                    if ($result_absensi_terbaru->num_rows > 0) {
                        while ($row = $result_absensi_terbaru->fetch_assoc()) {
                            echo "<tr>";
                            echo "<td>" . $no++ . "</td>";
                            echo "<td>" . format_tanggal_indonesia($row['tanggal']) . "</td>";
                            echo "<td>" . $row['nama_mahasiswa'] . "</td>";
                            echo "<td>" . $row['nama_kelas'] . "</td>";
                            echo "<td>" . $row['nama_matkul'] . "</td>";
                            echo "<td>" . status_kehadiran($row['status']) . "</td>";
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='6' class='text-center'>Belum ada data absensi</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once '../includes/footer_admin.php'; ?>
