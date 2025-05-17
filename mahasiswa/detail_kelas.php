<?php
$judul_halaman = "Detail Kelas";
require_once '../includes/header_mahasiswa.php';

// Cek apakah ID kelas ada
if (!isset($_GET['id'])) {
    header("Location: kelas.php");
    exit();
}

$id_kelas = bersihkan_input($_GET['id']);

// Cek apakah mahasiswa terdaftar di kelas ini
$query_cek = "
    SELECT id FROM kelas_mahasiswa 
    WHERE id_kelas = ? AND id_mahasiswa = ?
";
$stmt_cek = $koneksi->prepare($query_cek);
$stmt_cek->bind_param("ii", $id_kelas, $id_mahasiswa_asli);
$stmt_cek->execute();
$result_cek = $stmt_cek->get_result();

if ($result_cek->num_rows == 0) {
    header("Location: kelas.php");
    exit();
}

// Ambil data kelas
$query_kelas = "
    SELECT k.*, mk.nama as nama_matkul, mk.sks, j.nama as nama_jurusan, d.nama as nama_dosen, d.email as email_dosen
    FROM kelas k
    JOIN mata_kuliah mk ON k.id_matkul = mk.id
    JOIN jurusan j ON k.id_jurusan = j.id
    JOIN dosen d ON k.id_dosen = d.id
    WHERE k.id = ?
";
$stmt_kelas = $koneksi->prepare($query_kelas);
$stmt_kelas->bind_param("i", $id_kelas);
$stmt_kelas->execute();
$result_kelas = $stmt_kelas->get_result();
$kelas = $result_kelas->fetch_assoc();

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

// Ambil data kehadiran mahasiswa di kelas ini
$query_kehadiran = "
    SELECT a.id, a.id_sesi, a.status, a.keterangan, sa.pertemuan
    FROM absensi a
    JOIN sesi_absensi sa ON a.id_sesi = sa.id
    WHERE a.id_mahasiswa = ? AND sa.id_kelas = ?
";
$stmt_kehadiran = $koneksi->prepare($query_kehadiran);
$stmt_kehadiran->bind_param("ii", $id_mahasiswa_asli, $id_kelas);
$stmt_kehadiran->execute();
$result_kehadiran = $stmt_kehadiran->get_result();

// Buat array untuk menyimpan data kehadiran
$kehadiran = [];
while ($row = $result_kehadiran->fetch_assoc()) {
    $kehadiran[$row['id_sesi']] = $row;
}

// Hitung statistik kehadiran
$total_pertemuan = $result_sesi->num_rows;
$hadir = 0;
$izin = 0;
$sakit = 0;
$alpa = 0;
$belum_absen = 0;

$result_sesi->data_seek(0); // Reset pointer
while ($sesi = $result_sesi->fetch_assoc()) {
    if (isset($kehadiran[$sesi['id']])) {
        $status = $kehadiran[$sesi['id']]['status'];
        if ($status == 'hadir') $hadir++;
        elseif ($status == 'izin') $izin++;
        elseif ($status == 'sakit') $sakit++;
        elseif ($status == 'alpa') $alpa++;
    } else {
        $belum_absen++;
    }
}

// Hitung persentase kehadiran
$persentase = 0;
if ($total_pertemuan > 0) {
    $persentase = (($hadir + $izin + $sakit) / $total_pertemuan) * 100;
}
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
                    <tr>
                        <th>Jurusan</th>
                        <td><?php echo $kelas['nama_jurusan']; ?></td>
                    </tr>
                </table>
            </div>
            <div class="col-md-6">
                <table class="table">
                    <tr>
                        <th>Dosen</th>
                        <td><?php echo $kelas['nama_dosen']; ?></td>
                    </tr>
                    <tr>
                        <th>Email Dosen</th>
                        <td><?php echo $kelas['email_dosen']; ?></td>
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
    </div>
</div>

<div class="row">
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h2>Statistik Kehadiran</h2>
            </div>
            <div class="card-body">
                <div class="stat-card">
                    <h3><?php echo number_format($persentase, 2); ?>%</h3>
                    <p>Persentase Kehadiran</p>
                </div>
                
                <div class="row">
                    <div class="col-6">
                        <div class="stat-card">
                            <h3><?php echo $hadir; ?></h3>
                            <p>Hadir</p>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="stat-card">
                            <h3><?php echo $izin; ?></h3>
                            <p>Izin</p>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-6">
                        <div class="stat-card">
                            <h3><?php echo $sakit; ?></h3>
                            <p>Sakit</p>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="stat-card">
                            <h3><?php echo $alpa; ?></h3>
                            <p>Alpa</p>
                        </div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <h3><?php echo $total_pertemuan; ?></h3>
                    <p>Total Pertemuan</p>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h2>Riwayat Kehadiran</h2>
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
                                <th>Keterangan</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $result_sesi->data_seek(0); // Reset pointer
                            if ($result_sesi->num_rows > 0) {
                                while ($row = $result_sesi->fetch_assoc()) {
                                    echo "<tr>";
                                    echo "<td>Pertemuan " . $row['pertemuan'] . "</td>";
                                    echo "<td>" . format_tanggal_indonesia($row['tanggal']) . "</td>";
                                    echo "<td>" . $row['materi'] . "</td>";
                                    
                                    // Tampilkan status kehadiran
                                    if (isset($kehadiran[$row['id']])) {
                                        echo "<td>" . status_kehadiran($kehadiran[$row['id']]['status']) . "</td>";
                                        echo "<td>" . ($kehadiran[$row['id']]['keterangan'] ? $kehadiran[$row['id']]['keterangan'] : '-') . "</td>";
                                    } else {
                                        if ($row['status'] == 'dibuka') {
                                            echo "<td><span class='badge badge-warning'>Belum Absen</span></td>";
                                            echo "<td><a href='index.php' class='btn btn-sm btn-primary'>Absen Sekarang</a></td>";
                                        } else {
                                            echo "<td><span class='badge badge-secondary'>Tidak Hadir</span></td>";
                                            echo "<td>-</td>";
                                        }
                                    }
                                    
                                    echo "</tr>";
                                }
                            } else {
                                echo "<tr><td colspan='5' class='text-center'>Belum ada sesi pertemuan</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer_mahasiswa.php'; ?>
