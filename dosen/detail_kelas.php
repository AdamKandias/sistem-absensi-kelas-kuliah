<?php
$judul_halaman = "Detail Kelas";
require_once '../includes/header_dosen.php';

// Cek apakah ID kelas ada
if (!isset($_GET['id'])) {
    header("Location: kelas.php");
    exit();
}

$id_kelas = bersihkan_input($_GET['id']);

// Ambil data kelas
$query_kelas = "
    SELECT k.*, mk.nama as nama_matkul, mk.sks, j.nama as nama_jurusan
    FROM kelas k
    JOIN mata_kuliah mk ON k.id_matkul = mk.id
    JOIN jurusan j ON k.id_jurusan = j.id
    WHERE k.id = ? AND k.id_dosen = ?
";
$stmt_kelas = $koneksi->prepare($query_kelas);
$stmt_kelas->bind_param("ii", $id_kelas, $id_dosen_asli);
$stmt_kelas->execute();
$result_kelas = $stmt_kelas->get_result();

// Cek apakah kelas ditemukan dan milik dosen yang sedang login
if ($result_kelas->num_rows == 0) {
    header("Location: kelas.php");
    exit();
}

$kelas = $result_kelas->fetch_assoc();

// Ambil daftar mahasiswa di kelas ini
$query_mahasiswa = "
    SELECT m.id, m.nim, m.nama, m.kelas_angkatan, j.nama as nama_jurusan
    FROM mahasiswa m
    JOIN kelas_mahasiswa km ON m.id = km.id_mahasiswa
    JOIN jurusan j ON m.id_jurusan = j.id
    WHERE km.id_kelas = ?
    ORDER BY m.nama
";
$stmt_mahasiswa = $koneksi->prepare($query_mahasiswa);
$stmt_mahasiswa->bind_param("i", $id_kelas);
$stmt_mahasiswa->execute();
$result_mahasiswa = $stmt_mahasiswa->get_result();

// Ambil daftar sesi absensi untuk kelas ini
$query_sesi = "
    SELECT id, pertemuan, tanggal, materi, status
    FROM sesi_absensi
    WHERE id_kelas = ?
    ORDER BY pertemuan
";
$stmt_sesi = $koneksi->prepare($query_sesi);
$stmt_sesi->bind_param("i", $id_kelas);
$stmt_sesi->execute();
$result_sesi = $stmt_sesi->get_result();
?>

<div class="dashboard-header">
    <h1>Detail Kelas</h1>
    <p>
        <a href="kelas.php" class="btn btn-sm btn-secondary">
            <i class="fas fa-arrow-left"></i> Kembali ke Daftar Kelas
        </a>
    </p>
</div>

<div class="card">
    <div class="card-header">
        <h2>Informasi Kelas</h2>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <table class="table">
                    <tr>
                        <th>Nama Kelas</th>
                        <td><?php echo $kelas['nama']; ?></td>
                    </tr>
                    <tr>
                        <th>Mata Kuliah</th>
                        <td><?php echo $kelas['nama_matkul']; ?></td>
                    </tr>
                    <tr>
                        <th>SKS</th>
                        <td><?php echo $kelas['sks']; ?></td>
                    </tr>
                </table>
            </div>
            <div class="col-md-6">
                <table class="table">
                    <tr>
                        <th>Jurusan</th>
                        <td><?php echo $kelas['nama_jurusan']; ?></td>
                    </tr>
                    <tr>
                        <th>Tahun Ajaran</th>
                        <td><?php echo $kelas['tahun_ajaran']; ?></td>
                    </tr>
                    <tr>
                        <th>Semester</th>
                        <td><?php echo $kelas['semester']; ?></td>
                    </tr>
                </table>
            </div>
        </div>
        
        <div class="mt-3">
            <a href="absensi.php" class="btn btn-primary">
                <i class="fas fa-clipboard-check"></i> Kelola Absensi
            </a>
            <a href="laporan.php?id_kelas=<?php echo $id_kelas; ?>" class="btn btn-info">
                <i class="fas fa-file-alt"></i> Lihat Laporan Kehadiran
            </a>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h2>Daftar Mahasiswa</h2>
            </div>
            <div class="card-body">
                <div class="form-group">
                    <input type="text" id="filterMahasiswa" placeholder="Cari mahasiswa..." class="form-control">
                </div>
                
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>NIM</th>
                                <th>Nama</th>
                                <th>Kelas</th>
                                <th>Jurusan</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $no = 1;
                            if ($result_mahasiswa->num_rows > 0) {
                                while ($row = $result_mahasiswa->fetch_assoc()) {
                                    echo "<tr>";
                                    echo "<td>" . $no++ . "</td>";
                                    echo "<td>" . $row['nim'] . "</td>";
                                    echo "<td>" . $row['nama'] . "</td>";
                                    echo "<td>" . $row['kelas_angkatan'] . "</td>";
                                    echo "<td>" . $row['nama_jurusan'] . "</td>";
                                    echo "</tr>";
                                }
                            } else {
                                echo "<tr><td colspan='5' class='text-center'>Belum ada mahasiswa yang terdaftar di kelas ini</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h2>Sesi Pertemuan</h2>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Pertemuan</th>
                                <th>Tanggal</th>
                                <th>Materi</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            if ($result_sesi->num_rows > 0) {
                                while ($row = $result_sesi->fetch_assoc()) {
                                    echo "<tr>";
                                    echo "<td>Pertemuan " . $row['pertemuan'] . "</td>";
                                    echo "<td>" . format_tanggal_indonesia($row['tanggal']) . "</td>";
                                    echo "<td>" . $row['materi'] . "</td>";
                                    echo "<td>";
                                    if ($row['status'] == 'dibuka') {
                                        echo '<span class="badge badge-success">Dibuka</span>';
                                    } else {
                                        echo '<span class="badge badge-secondary">Ditutup</span>';
                                    }
                                    echo "</td>";
                                    echo "<td>
                                        <a href='detail_absensi.php?id=" . $row['id'] . "' class='btn btn-sm btn-info'><i class='fas fa-eye'></i></a>
                                    </td>";
                                    echo "</tr>";
                                }
                            } else {
                                echo "<tr><td colspan='5' class='text-center'>Belum ada sesi pertemuan</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
                
                <div class="mt-3">
                    <a href="absensi.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Buka Sesi Baru
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Filter untuk tabel mahasiswa
    document.getElementById('filterMahasiswa').addEventListener('keyup', function() {
        const filterValue = this.value.toLowerCase();
        const table = this.closest('.card-body').querySelector('table');
        const rows = table.querySelectorAll('tbody tr');
        
        rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            if (text.indexOf(filterValue) > -1) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    });
</script>

<?php require_once '../includes/footer_dosen.php'; ?>
