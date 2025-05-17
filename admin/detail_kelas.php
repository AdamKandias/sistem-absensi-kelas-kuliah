<?php
session_start();
require_once '../config/koneksi.php';
require_once '../config/fungsi.php';

// Cek apakah user adalah admin
cek_admin();

// Cek apakah ID kelas ada
if (!isset($_GET['id'])) {
    header("Location: kelas.php");
    exit();
}

$id_kelas = bersihkan_input($_GET['id']);

// Ambil data kelas
$query_kelas = "
    SELECT k.*, j.nama as nama_jurusan, mk.nama as nama_matkul, d.nama as nama_dosen
    FROM kelas k
    JOIN jurusan j ON k.id_juru  mk.nama as nama_matkul, d.nama as nama_dosen
    FROM kelas k
    JOIN jurusan j ON k.id_jurusan = j.id
    JOIN mata_kuliah mk ON k.id_matkul = mk.id
    JOIN dosen d ON k.id_dosen = d.id
    WHERE k.id = ?
";
$stmt_kelas = $koneksi->prepare($query_kelas);
$stmt_kelas->bind_param("i", $id_kelas);
$stmt_kelas->execute();
$result_kelas = $stmt_kelas->get_result();

// Cek apakah kelas ditemukan
if ($result_kelas->num_rows == 0) {
    header("Location: kelas.php");
    exit();
}

$kelas = $result_kelas->fetch_assoc();

// Proses tambah mahasiswa ke kelas
if (isset($_POST['tambah_mahasiswa'])) {
    $id_mahasiswa = bersihkan_input($_POST['id_mahasiswa']);

    // Cek apakah mahasiswa sudah terdaftar di kelas ini
    $query_cek = "SELECT id FROM kelas_mahasiswa WHERE id_kelas = ? AND id_mahasiswa = ?";
    $stmt_cek = $koneksi->prepare($query_cek);
    $stmt_cek->bind_param("ii", $id_kelas, $id_mahasiswa);
    $stmt_cek->execute();
    $result_cek = $stmt_cek->get_result();

    if ($result_cek->num_rows > 0) {
        $pesan_error = "Mahasiswa sudah terdaftar di kelas ini!";
    } else {
        // Tambahkan mahasiswa ke kelas
        $query = "INSERT INTO kelas_mahasiswa (id_kelas, id_mahasiswa) VALUES (?, ?)";
        $stmt = $koneksi->prepare($query);
        $stmt->bind_param("ii", $id_kelas, $id_mahasiswa);

        if ($stmt->execute()) {
            $pesan_sukses = "Mahasiswa berhasil ditambahkan ke kelas!";
        } else {
            $pesan_error = "Gagal menambahkan mahasiswa ke kelas: " . $koneksi->error;
        }
    }
}

// Proses hapus mahasiswa dari kelas
if (isset($_GET['hapus_mahasiswa'])) {
    $id_kelas_mahasiswa = bersihkan_input($_GET['hapus_mahasiswa']);

    // Hapus mahasiswa dari kelas
    $query = "DELETE FROM kelas_mahasiswa WHERE id = ?";
    $stmt = $koneksi->prepare($query);
    $stmt->bind_param("i", $id_kelas_mahasiswa);

    if ($stmt->execute()) {
        $pesan_sukses = "Mahasiswa berhasil dihapus dari kelas!";
    } else {
        $pesan_error = "Gagal menghapus mahasiswa dari kelas: " . $koneksi->error;
    }
}

// Ambil data mahasiswa yang belum terdaftar di kelas ini untuk dropdown
$query_mahasiswa_available = "
    SELECT m.id, m.nim, m.nama, j.nama as nama_jurusan
    FROM mahasiswa m
    JOIN jurusan j ON m.id_jurusan = j.id
    WHERE m.id NOT IN (
        SELECT id_mahasiswa FROM kelas_mahasiswa WHERE id_kelas = ?
    )
    ORDER BY m.nama
";
$stmt_mahasiswa_available = $koneksi->prepare($query_mahasiswa_available);
$stmt_mahasiswa_available->bind_param("i", $id_kelas);
$stmt_mahasiswa_available->execute();
$result_mahasiswa_available = $stmt_mahasiswa_available->get_result();

// Ambil data mahasiswa yang terdaftar di kelas ini
$query_mahasiswa = "
    SELECT km.id, m.nim, m.nama, m.kelas_angkatan, j.nama as nama_jurusan
    FROM kelas_mahasiswa km
    JOIN mahasiswa m ON km.id_mahasiswa = m.id
    JOIN jurusan j ON m.id_jurusan = j.id
    WHERE km.id_kelas = ?
    ORDER BY m.nama
";
$stmt_mahasiswa = $koneksi->prepare($query_mahasiswa);
$stmt_mahasiswa->bind_param("i", $id_kelas);
$stmt_mahasiswa->execute();
$result_mahasiswa = $stmt_mahasiswa->get_result();

// Ambil data sesi absensi untuk kelas ini
$query_sesi = "
    SELECT sa.*, COUNT(a.id) as jumlah_hadir
    FROM sesi_absensi sa
    LEFT JOIN absensi a ON sa.id = a.id_sesi AND a.status = 'hadir'
    WHERE sa.id_kelas = ?
    GROUP BY sa.id
    ORDER BY sa.pertemuan
";
$stmt_sesi = $koneksi->prepare($query_sesi);
$stmt_sesi->bind_param("i", $id_kelas);
$stmt_sesi->execute();
$result_sesi = $stmt_sesi->get_result();
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Kelas - Sistem Monitoring Kehadiran Praktikum</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
</head>

<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            <h3>Admin Panel</h3>
        </div>
        <div class="sidebar-menu">
            <ul>
                <li><a href="index.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a href="mahasiswa.php"><i class="fas fa-user-graduate"></i> Mahasiswa</a></li>
                <li><a href="dosen.php"><i class="fas fa-chalkboard-teacher"></i> Dosen</a></li>
                <li><a href="jurusan.php"><i class="fas fa-building"></i> Jurusan</a></li>
                <li><a href="kelas.php" class="active"><i class="fas fa-door-open"></i> Kelas</a></li>
                <li><a href="mata_kuliah.php"><i class="fas fa-book"></i> Mata Kuliah</a></li>
                <li><a href="pengguna.php"><i class="fas fa-users"></i> Pengguna</a></li>
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
                <h1>Detail Kelas</h1>
                <p>
                    <a href="kelas.php" class="btn btn-sm btn-secondary">
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
                                    <th>Jurusan</th>
                                    <td><?php echo $kelas['nama_jurusan']; ?></td>
                                </tr>
                                <tr>
                                    <th>Mata Kuliah</th>
                                    <td><?php echo $kelas['nama_matkul']; ?></td>
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
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h2>Tambah Mahasiswa ke Kelas</h2>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="">
                                <div class="form-group">
                                    <label for="id_mahasiswa">Pilih Mahasiswa</label>
                                    <select id="id_mahasiswa" name="id_mahasiswa" required>
                                        <option value="">Pilih Mahasiswa</option>
                                        <?php while ($mahasiswa = $result_mahasiswa_available->fetch_assoc()): ?>
                                            <option value="<?php echo $mahasiswa['id']; ?>">
                                                <?php echo $mahasiswa['nim'] . ' - ' . $mahasiswa['nama'] . ' (' . $mahasiswa['nama_jurusan'] . ')'; ?>
                                            </option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <button type="submit" name="tambah_mahasiswa" class="btn btn-primary">Tambah ke Kelas</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h2>Sesi Absensi</h2>
                        </div>
                        <div class="card-body">
                            <?php if ($result_sesi->num_rows > 0): ?>
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>Pertemuan</th>
                                                <th>Tanggal</th>
                                                <th>Materi</th>
                                                <th>Status</th>
                                                <th>Kehadiran</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php while ($sesi = $result_sesi->fetch_assoc()): ?>
                                                <tr>
                                                    <td>Pertemuan <?php echo $sesi['pertemuan']; ?></td>
                                                    <td><?php echo format_tanggal_indonesia($sesi['tanggal']); ?></td>
                                                    <td><?php echo $sesi['materi']; ?></td>
                                                    <td>
                                                        <?php if ($sesi['status'] == 'dibuka'): ?>
                                                            <span class="badge badge-success">Dibuka</span>
                                                        <?php else: ?>
                                                            <span class="badge badge-secondary">Ditutup</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <?php
                                                        $total_mahasiswa = $result_mahasiswa->num_rows;
                                                        $persentase = $total_mahasiswa > 0 ? ($sesi['jumlah_hadir'] / $total_mahasiswa) * 100 : 0;
                                                        echo $sesi['jumlah_hadir'] . '/' . $total_mahasiswa . ' (' . number_format($persentase, 1) . '%)';
                                                        ?>
                                                    </td>
                                                </tr>
                                            <?php endwhile; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <p class="text-center">Belum ada sesi absensi untuk kelas ini.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h2>Daftar Mahasiswa</h2>
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
                                    <th>Jurusan</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $no = 1;
                                $result_mahasiswa->data_seek(0); // Reset pointer
                                if ($result_mahasiswa->num_rows > 0) {
                                    while ($row = $result_mahasiswa->fetch_assoc()) {
                                        echo "<tr>";
                                        echo "<td>" . $no++ . "</td>";
                                        echo "<td>" . $row['nim'] . "</td>";
                                        echo "<td>" . $row['nama'] . "</td>";
                                        echo "<td>" . $row['kelas_angkatan'] . "</td>";
                                        echo "<td>" . $row['nama_jurusan'] . "</td>";
                                        echo "<td>
                                            <a href='detail_kelas.php?id=" . $id_kelas . "&hapus_mahasiswa=" . $row['id'] . "' class='btn btn-sm btn-danger btn-delete'><i class='fas fa-trash'></i> Hapus dari Kelas</a>
                                        </td>";
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

    <script src="../assets/js/script.js"></script>
</body>

</html>