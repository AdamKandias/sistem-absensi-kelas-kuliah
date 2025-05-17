<?php
session_start();
require_once '../config/koneksi.php';
require_once '../config/fungsi.php';

// Cek apakah user adalah admin
cek_admin();

// Proses tambah kelas
if (isset($_POST['tambah'])) {
    $nama = bersihkan_input($_POST['nama']);
    $id_jurusan = bersihkan_input($_POST['id_jurusan']);
    $id_matkul = bersihkan_input($_POST['id_matkul']);
    $id_dosen = bersihkan_input($_POST['id_dosen']);
    $tahun_ajaran = bersihkan_input($_POST['tahun_ajaran']);
    $semester = bersihkan_input($_POST['semester']);

    // Insert data kelas
    $query = "INSERT INTO kelas (nama, id_jurusan, id_matkul, id_dosen, tahun_ajaran, semester) VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $koneksi->prepare($query);
    $stmt->bind_param("siiiss", $nama, $id_jurusan, $id_matkul, $id_dosen, $tahun_ajaran, $semester);

    if ($stmt->execute()) {
        $pesan_sukses = "Data kelas berhasil ditambahkan!";
    } else {
        $pesan_error = "Gagal menambahkan data kelas: " . $koneksi->error;
    }
}

// Proses edit kelas
if (isset($_POST['edit'])) {
    $id = bersihkan_input($_POST['id']);
    $nama = bersihkan_input($_POST['nama']);
    $id_jurusan = bersihkan_input($_POST['id_jurusan']);
    $id_matkul = bersihkan_input($_POST['id_matkul']);
    $id_dosen = bersihkan_input($_POST['id_dosen']);
    $tahun_ajaran = bersihkan_input($_POST['tahun_ajaran']);
    $semester = bersihkan_input($_POST['semester']);

    // Update data kelas
    $query = "UPDATE kelas SET nama = ?, id_jurusan = ?, id_matkul = ?, id_dosen = ?, tahun_ajaran = ?, semester = ? WHERE id = ?";
    $stmt = $koneksi->prepare($query);
    $stmt->bind_param("siiissi", $nama, $id_jurusan, $id_matkul, $id_dosen, $tahun_ajaran, $semester, $id);

    if ($stmt->execute()) {
        $pesan_sukses = "Data kelas berhasil diperbarui!";
    } else {
        $pesan_error = "Gagal memperbarui data kelas: " . $koneksi->error;
    }
}

// Proses hapus kelas
if (isset($_GET['hapus'])) {
    $id = bersihkan_input($_GET['hapus']);

    // Hapus data kelas
    $query = "DELETE FROM kelas WHERE id = ?";
    $stmt = $koneksi->prepare($query);
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        $pesan_sukses = "Data kelas berhasil dihapus!";
    } else {
        $pesan_error = "Gagal menghapus data kelas: " . $koneksi->error;
    }
}

// Ambil data jurusan untuk dropdown
$query_jurusan = "SELECT id, nama FROM jurusan ORDER BY nama";
$result_jurusan = $koneksi->query($query_jurusan);

// Ambil data mata kuliah untuk dropdown
$query_matkul = "SELECT id, nama, kode FROM mata_kuliah ORDER BY nama";
$result_matkul = $koneksi->query($query_matkul);

// Ambil data dosen untuk dropdown
$query_dosen = "SELECT id, nama, nip FROM dosen ORDER BY nama";
$result_dosen = $koneksi->query($query_dosen);

// Ambil data kelas
$query_kelas = "
    SELECT k.*, j.nama as nama_jurusan, mk.nama as nama_matkul, d.nama as nama_dosen
    FROM kelas k
    JOIN jurusan j ON k.id_jurusan = j.id
    JOIN mata_kuliah mk ON k.id_matkul = mk.id
    JOIN dosen d ON k.id_dosen = d.id
    ORDER BY k.tahun_ajaran DESC, k.semester DESC, k.nama
";
$result_kelas = $koneksi->query($query_kelas);
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Kelas - Sistem Monitoring Kehadiran Praktikum</title>
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
                <h1>Kelola Data Kelas</h1>
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
                    <h2>Tambah Kelas</h2>
                </div>
                <div class="card-body">
                    <form method="POST" action="">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="nama">Nama Kelas</label>
                                    <input type="text" id="nama" name="nama" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="id_jurusan">Jurusan</label>
                                    <select id="id_jurusan" name="id_jurusan" required>
                                        <option value="">Pilih Jurusan</option>
                                        <?php while ($jurusan = $result_jurusan->fetch_assoc()): ?>
                                            <option value="<?php echo $jurusan['id']; ?>"><?php echo $jurusan['nama']; ?></option>
                                        <?php endwhile; ?>
                                        <?php $result_jurusan->data_seek(0); // Reset pointer 
                                        ?>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="id_matkul">Mata Kuliah</label>
                                    <select id="id_matkul" name="id_matkul" required>
                                        <option value="">Pilih Mata Kuliah</option>
                                        <?php while ($matkul = $result_matkul->fetch_assoc()): ?>
                                            <option value="<?php echo $matkul['id']; ?>"><?php echo $matkul['kode'] . ' - ' . $matkul['nama']; ?></option>
                                        <?php endwhile; ?>
                                        <?php $result_matkul->data_seek(0); // Reset pointer 
                                        ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="id_dosen">Dosen</label>
                                    <select id="id_dosen" name="id_dosen" required>
                                        <option value="">Pilih Dosen</option>
                                        <?php while ($dosen = $result_dosen->fetch_assoc()): ?>
                                            <option value="<?php echo $dosen['id']; ?>"><?php echo $dosen['nip'] . ' - ' . $dosen['nama']; ?></option>
                                        <?php endwhile; ?>
                                        <?php $result_dosen->data_seek(0); // Reset pointer 
                                        ?>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="tahun_ajaran">Tahun Ajaran</label>
                                    <input type="text" id="tahun_ajaran" name="tahun_ajaran" placeholder="Contoh: 2023/2024" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="semester">Semester</label>
                                    <select id="semester" name="semester" required>
                                        <option value="">Pilih Semester</option>
                                        <option value="Ganjil">Ganjil</option>
                                        <option value="Genap">Genap</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <button type="submit" name="tambah" class="btn btn-primary">Tambah Kelas</button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h2>Daftar Kelas</h2>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <input type="text" id="filterInput" placeholder="Cari kelas..." class="form-control">
                    </div>

                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Nama Kelas</th>
                                    <th>Jurusan</th>
                                    <th>Mata Kuliah</th>
                                    <th>Dosen</th>
                                    <th>Tahun Ajaran</th>
                                    <th>Semester</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $no = 1;
                                if ($result_kelas->num_rows > 0) {
                                    while ($row = $result_kelas->fetch_assoc()) {
                                        echo "<tr>";
                                        echo "<td>" . $no++ . "</td>";
                                        echo "<td>" . $row['nama'] . "</td>";
                                        echo "<td>" . $row['nama_jurusan'] . "</td>";
                                        echo "<td>" . $row['nama_matkul'] . "</td>";
                                        echo "<td>" . $row['nama_dosen'] . "</td>";
                                        echo "<td>" . $row['tahun_ajaran'] . "</td>";
                                        echo "<td>" . $row['semester'] . "</td>";
                                        echo "<td>
                                            <a href='#' class='btn btn-sm btn-info' onclick='editKelas(" . $row['id'] . ", \"" . $row['nama'] . "\", " . $row['id_jurusan'] . ", " . $row['id_matkul'] . ", " . $row['id_dosen'] . ", \"" . $row['tahun_ajaran'] . "\", \"" . $row['semester'] . "\")'><i class='fas fa-edit'></i></a>
                                            <a href='kelas.php?hapus=" . $row['id'] . "' class='btn btn-sm btn-danger btn-delete'><i class='fas fa-trash'></i></a>
                                            <a href='detail_kelas.php?id=" . $row['id'] . "' class='btn btn-sm btn-primary'><i class='fas fa-eye'></i></a>
                                        </td>";
                                        echo "</tr>";
                                    }
                                } else {
                                    echo "<tr><td colspan='8' class='text-center'>Belum ada data kelas</td></tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Edit Kelas -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Edit Kelas</h2>
                <span class="close">&times;</span>
            </div>
            <div class="modal-body">
                <form method="POST" action="">
                    <input type="hidden" id="edit_id" name="id">

                    <div class="form-group">
                        <label for="edit_nama">Nama Kelas</label>
                        <input type="text" id="edit_nama" name="nama" required>
                    </div>

                    <div class="form-group">
                        <label for="edit_id_jurusan">Jurusan</label>
                        <select id="edit_id_jurusan" name="id_jurusan" required>
                            <option value="">Pilih Jurusan</option>
                            <?php
                            $result_jurusan->data_seek(0); // Reset pointer
                            while ($jurusan = $result_jurusan->fetch_assoc()):
                            ?>
                                <option value="<?php echo $jurusan['id']; ?>"><?php echo $jurusan['nama']; ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="edit_id_matkul">Mata Kuliah</label>
                        <select id="edit_id_matkul" name="id_matkul" required>
                            <option value="">Pilih Mata Kuliah</option>
                            <?php
                            $result_matkul->data_seek(0); // Reset pointer
                            while ($matkul = $result_matkul->fetch_assoc()):
                            ?>
                                <option value="<?php echo $matkul['id']; ?>"><?php echo $matkul['kode'] . ' - ' . $matkul['nama']; ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="edit_id_dosen">Dosen</label>
                        <select id="edit_id_dosen" name="id_dosen" required>
                            <option value="">Pilih Dosen</option>
                            <?php
                            $result_dosen->data_seek(0); // Reset pointer
                            while ($dosen = $result_dosen->fetch_assoc()):
                            ?>
                                <option value="<?php echo $dosen['id']; ?>"><?php echo $dosen['nip'] . ' - ' . $dosen['nama']; ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="edit_tahun_ajaran">Tahun Ajaran</label>
                        <input type="text" id="edit_tahun_ajaran" name="tahun_ajaran" required>
                    </div>

                    <div class="form-group">
                        <label for="edit_semester">Semester</label>
                        <select id="edit_semester" name="semester" required>
                            <option value="">Pilih Semester</option>
                            <option value="Ganjil">Ganjil</option>
                            <option value="Genap">Genap</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <button type="submit" name="edit" class="btn btn-primary">Simpan Perubahan</button>
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

        function editKelas(id, nama, id_jurusan, id_matkul, id_dosen, tahun_ajaran, semester) {
            document.getElementById('edit_id').value = id;
            document.getElementById('edit_nama').value = nama;
            document.getElementById('edit_id_jurusan').value = id_jurusan;
            document.getElementById('edit_id_matkul').value = id_matkul;
            document.getElementById('edit_id_dosen').value = id_dosen;
            document.getElementById('edit_tahun_ajaran').value = tahun_ajaran;
            document.getElementById('edit_semester').value = semester;

            modal.style.display = "block";
        }
    </script>
</body>

</html>