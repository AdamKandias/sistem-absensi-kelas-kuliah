<?php
date_default_timezone_set('Asia/Jakarta');
if (session_status() === PHP_SESSION_NONE) {
    session_start();
};
require_once '../config/koneksi.php';
require_once '../config/fungsi.php';

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

// Cek apakah ID sesi absensi ada
if (!isset($_GET['id'])) {
    header("Location: absensi.php");
    exit();
}

// Periksa dan tutup sesi yang sudah melewati waktu tutup
periksa_sesi_kadaluarsa($koneksi);

$id_sesi = bersihkan_input($_GET['id']);

// Ambil data sesi absensi
$query_sesi = "
    SELECT sa.*, k.nama as nama_kelas, mk.nama as nama_matkul, k.id_dosen
    FROM sesi_absensi sa
    JOIN kelas k ON sa.id_kelas = k.id
    JOIN mata_kuliah mk ON k.id_matkul = mk.id
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
if ($sesi['id_dosen'] != $id_dosen_asli) {
    header("Location: absensi.php");
    exit();
}

// Proses update status kehadiran
if (isset($_POST['update_kehadiran'])) {
    $id_mahasiswa = bersihkan_input($_POST['id_mahasiswa']);
    $status = bersihkan_input($_POST['status']);
    $keterangan = bersihkan_input($_POST['keterangan']);

    // Cek apakah mahasiswa sudah absen
    $query_cek = "SELECT id FROM absensi WHERE id_mahasiswa = ? AND id_sesi = ?";
    $stmt_cek = $koneksi->prepare($query_cek);
    $stmt_cek->bind_param("ii", $id_mahasiswa, $id_sesi);
    $stmt_cek->execute();
    $result_cek = $stmt_cek->get_result();

    if ($result_cek->num_rows > 0) {
        // Update data absensi
        $id_absensi = $result_cek->fetch_assoc()['id'];
        $query = "UPDATE absensi SET status = ?, keterangan = ? WHERE id = ?";
        $stmt = $koneksi->prepare($query);
        $stmt->bind_param("ssi", $status, $keterangan, $id_absensi);
    } else {
        // Insert data absensi baru
        $tanggal = date('Y-m-d');
        $query = "INSERT INTO absensi (id_mahasiswa, id_sesi, tanggal, status, keterangan) VALUES (?, ?, ?, ?, ?)";
        $stmt = $koneksi->prepare($query);
        $stmt->bind_param("iisss", $id_mahasiswa, $id_sesi, $tanggal, $status, $keterangan);
    }

    if ($stmt->execute()) {
        $pesan_sukses = "Status kehadiran berhasil diperbarui!";
    } else {
        $pesan_error = "Gagal memperbarui status kehadiran: " . $koneksi->error;
    }
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

// Hitung statistik kehadiran
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
$result_mahasiswa->data_seek(0); // Reset pointer kembali
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Absensi - Sistem Monitoring Kehadiran Praktikum</title>
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
                <li><a href="absensi.php" class="active"><i class="fas fa-clipboard-check"></i> Kelola Absensi</a></li>
                <li><a href="laporan.php"><i class="fas fa-file-alt"></i> Laporan Kehadiran</a></li>
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
                <h1>Detail Absensi</h1>
                <p>
                    <a href="absensi.php" class="btn btn-sm btn-secondary">
                        <i class="fas fa-arrow-left"></i> Kembali
                    </a>
                </p>
            </div>

            <?php if (isset($pesan_sukses)): ?>
                <div class="alert alert-success">
                    <?php echo $pesan_sukses; ?>
                </div>
            <?php endif; ?>

            <?php if (isset($pesan_error)): ?>
                <div class="alert alert-danger">
                    <?php echo $pesan_error; ?>
                </div>
            <?php endif; ?>

            <div class="card">
                <div class="card-header">
                    <h2>Informasi Sesi</h2>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table">
                                <tr>
                                    <th>Kelas</th>
                                    <td><?php echo $sesi['nama_kelas']; ?></td>
                                </tr>
                                <tr>
                                    <th>Mata Kuliah</th>
                                    <td><?php echo $sesi['nama_matkul']; ?></td>
                                </tr>
                                <tr>
                                    <th>Pertemuan</th>
                                    <td>Pertemuan <?php echo $sesi['pertemuan']; ?></td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table">
                                <tr>
                                    <th>Tanggal</th>
                                    <td><?php echo format_tanggal_indonesia($sesi['tanggal']); ?></td>
                                </tr>
                                <tr>
                                    <th>Materi</th>
                                    <td><?php echo $sesi['materi']; ?></td>
                                </tr>
                                <tr>
                                    <th>Status</th>
                                    <td>
                                        <?php if ($sesi['status'] == 'dibuka'): ?>
                                            <span class="badge badge-success">Dibuka</span>
                                        <?php else: ?>
                                            <span class="badge badge-secondary">Ditutup</span>
                                        <?php endif; ?>
                                    </td>
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
                                <h3><?php echo $total_mahasiswa; ?></h3>
                                <p>Total Mahasiswa</p>
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
                            <div class="mt-3">
                                <a href="cetak_absensi.php?id=<?php echo $id_sesi; ?>" class="btn btn-primary btn-block" target="_blank">
                                    <i class="fas fa-print"></i> Cetak Daftar Hadir
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header">
                            <h2>Daftar Kehadiran Mahasiswa</h2>
                        </div>
                        <div class="card-body">
                            <div class="form-group">
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
                                            <th>Status</th>
                                            <th>Aksi</th>
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
                                                echo "<td>" . status_kehadiran($row['status']) . "</td>";
                                                echo "<td>";
                                                if ($sesi['status'] == 'dibuka') {
                                                    echo "<a href='#' class='btn btn-sm btn-info' onclick='editKehadiran(" . $row['id'] . ", \"" . ($row['status'] ? $row['status'] : '') . "\", \"" . ($row['keterangan'] ? $row['keterangan'] : '') . "\")'><i class='fas fa-edit'></i></a>";
                                                } else {
                                                    echo "<span class='badge badge-secondary'>Sesi Ditutup</span>";
                                                }
                                                echo "</td>";
                                                echo "</tr>";
                                            }
                                        } else {
                                            echo "<tr><td colspan='6' class='text-center'>Belum ada mahasiswa yang terdaftar di kelas ini</td></tr>";
                                        }
                                        ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Edit Kehadiran -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Update Status Kehadiran</h2>
                <span class="close">&times;</span>
            </div>
            <div class="modal-body">
                <form method="POST" action="">
                    <input type="hidden" id="edit_id_mahasiswa" name="id_mahasiswa">

                    <div class="form-group">
                        <label for="edit_status">Status Kehadiran</label>
                        <select id="edit_status" name="status" required>
                            <option value="hadir">Hadir</option>
                            <option value="izin">Izin</option>
                            <option value="sakit">Sakit</option>
                            <option value="alpa">Alpa</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="edit_keterangan">Keterangan (opsional)</label>
                        <textarea id="edit_keterangan" name="keterangan" rows="3"></textarea>
                    </div>

                    <div class="form-group">
                        <button type="submit" name="update_kehadiran" class="btn btn-primary">Simpan Perubahan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="../assets/js/script.js"></script>
    <script>
        // Modal Edit
        const modal = document.getElementById('editModal');
        const closeBtn = document.getElementsByClassName('close')[0];

        closeBtn.onclick = function() {
            modal.style.display = "none";
        }

        window.onclick = function(event) {
            if (event.target == modal) {
                modal.style.display = "none";
            }
        }

        function editKehadiran(id_mahasiswa, status, keterangan) {
            document.getElementById('edit_id_mahasiswa').value = id_mahasiswa;

            if (status) {
                document.getElementById('edit_status').value = status;
            } else {
                document.getElementById('edit_status').value = 'hadir'; // Default
            }

            document.getElementById('edit_keterangan').value = keterangan || '';

            modal.style.display = "block";
        }
    </script>
</body>

</html>