<?php
session_start();
require_once '../config/koneksi.php';
require_once '../config/fungsi.php';

// Cek apakah user adalah admin
cek_admin();

// Proses tambah mata kuliah
if (isset($_POST['tambah'])) {
    $kode = bersihkan_input($_POST['kode']);
    $nama = bersihkan_input($_POST['nama']);
    $sks = bersihkan_input($_POST['sks']);

    // Cek apakah kode mata kuliah sudah ada
    $cek_kode = $koneksi->prepare("SELECT id FROM mata_kuliah WHERE kode = ?");
    $cek_kode->bind_param("s", $kode);
    $cek_kode->execute();
    $hasil_cek = $cek_kode->get_result();

    if ($hasil_cek->num_rows > 0) {
        $pesan_error = "Kode mata kuliah sudah terdaftar!";
    } else {
        // Insert data mata kuliah
        $query = "INSERT INTO mata_kuliah (kode, nama, sks) VALUES (?, ?, ?)";
        $stmt = $koneksi->prepare($query);
        $stmt->bind_param("ssi", $kode, $nama, $sks);

        if ($stmt->execute()) {
            $pesan_sukses = "Data mata kuliah berhasil ditambahkan!";
        } else {
            $pesan_error = "Gagal menambahkan data mata kuliah: " . $koneksi->error;
        }
    }
}

// Proses edit mata kuliah
if (isset($_POST['edit'])) {
    $id = bersihkan_input($_POST['id']);
    $kode = bersihkan_input($_POST['kode']);
    $nama = bersihkan_input($_POST['nama']);
    $sks = bersihkan_input($_POST['sks']);

    // Cek apakah kode mata kuliah sudah ada (selain mata kuliah yang sedang diedit)
    $cek_kode = $koneksi->prepare("SELECT id FROM mata_kuliah WHERE kode = ? AND id != ?");
    $cek_kode->bind_param("si", $kode, $id);
    $cek_kode->execute();
    $hasil_cek = $cek_kode->get_result();

    if ($hasil_cek->num_rows > 0) {
        $pesan_error = "Kode mata kuliah sudah terdaftar!";
    } else {
        // Update data mata kuliah
        $query = "UPDATE mata_kuliah SET kode = ?, nama = ?, sks = ? WHERE id = ?";
        $stmt = $koneksi->prepare($query);
        $stmt->bind_param("ssii", $kode, $nama, $sks, $id);

        if ($stmt->execute()) {
            $pesan_sukses = "Data mata kuliah berhasil diperbarui!";
        } else {
            $pesan_error = "Gagal memperbarui data mata kuliah: " . $koneksi->error;
        }
    }
}

// Proses hapus mata kuliah
if (isset($_GET['hapus'])) {
    $id = bersihkan_input($_GET['hapus']);

    // Cek apakah mata kuliah digunakan di kelas
    $cek_kelas = $koneksi->prepare("SELECT id FROM kelas WHERE id_matkul = ?");
    $cek_kelas->bind_param("i", $id);
    $cek_kelas->execute();
    $hasil_cek = $cek_kelas->get_result();

    if ($hasil_cek->num_rows > 0) {
        $pesan_error = "Mata kuliah tidak dapat dihapus karena masih digunakan di kelas!";
    } else {
        // Hapus data mata kuliah
        $query = "DELETE FROM mata_kuliah WHERE id = ?";
        $stmt = $koneksi->prepare($query);
        $stmt->bind_param("i", $id);

        if ($stmt->execute()) {
            $pesan_sukses = "Data mata kuliah berhasil dihapus!";
        } else {
            $pesan_error = "Gagal menghapus data mata kuliah: " . $koneksi->error;
        }
    }
}

// Ambil data mata kuliah
$query_matkul = "SELECT * FROM mata_kuliah ORDER BY kode";
$result_matkul = $koneksi->query($query_matkul);
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Mata Kuliah - Sistem Monitoring Kehadiran Praktikum</title>
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
                <li><a href="kelas.php"><i class="fas fa-door-open"></i> Kelas</a></li>
                <li><a href="mata_kuliah.php" class="active"><i class="fas fa-book"></i> Mata Kuliah</a></li>
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
                <h1>Kelola Data Mata Kuliah</h1>
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
                    <h2>Tambah Mata Kuliah</h2>
                </div>
                <div class="card-body">
                    <form method="POST" action="">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="kode">Kode Mata Kuliah</label>
                                    <input type="text" id="kode" name="kode" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="nama">Nama Mata Kuliah</label>
                                    <input type="text" id="nama" name="nama" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="sks">SKS</label>
                                    <input type="number" id="sks" name="sks" min="1" max="6" required>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <button type="submit" name="tambah" class="btn btn-primary">Tambah Mata Kuliah</button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h2>Daftar Mata Kuliah</h2>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <input type="text" id="filterInput" placeholder="Cari mata kuliah..." class="form-control">
                    </div>

                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Kode</th>
                                    <th>Nama Mata Kuliah</th>
                                    <th>SKS</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $no = 1;
                                if ($result_matkul->num_rows > 0) {
                                    while ($row = $result_matkul->fetch_assoc()) {
                                        echo "<tr>";
                                        echo "<td>" . $no++ . "</td>";
                                        echo "<td>" . $row['kode'] . "</td>";
                                        echo "<td>" . $row['nama'] . "</td>";
                                        echo "<td>" . $row['sks'] . "</td>";
                                        echo "<td>
                                            <a href='#' class='btn btn-sm btn-info' onclick='editMatkul(" . $row['id'] . ", \"" . $row['kode'] . "\", \"" . $row['nama'] . "\", " . $row['sks'] . ")'><i class='fas fa-edit'></i></a>
                                            <a href='mata_kuliah.php?hapus=" . $row['id'] . "' class='btn btn-sm btn-danger btn-delete'><i class='fas fa-trash'></i></a>
                                        </td>";
                                        echo "</tr>";
                                    }
                                } else {
                                    echo "<tr><td colspan='5' class='text-center'>Belum ada data mata kuliah</td></tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Edit Mata Kuliah -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Edit Mata Kuliah</h2>
                <span class="close">&times;</span>
            </div>
            <div class="modal-body">
                <form method="POST" action="">
                    <input type="hidden" id="edit_id" name="id">

                    <div class="form-group">
                        <label for="edit_kode">Kode Mata Kuliah</label>
                        <input type="text" id="edit_kode" name="kode" required>
                    </div>

                    <div class="form-group">
                        <label for="edit_nama">Nama Mata Kuliah</label>
                        <input type="text" id="edit_nama" name="nama" required>
                    </div>

                    <div class="form-group">
                        <label for="edit_sks">SKS</label>
                        <input type="number" id="edit_sks" name="sks" min="1" max="6" required>
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

        function editMatkul(id, kode, nama, sks) {
            document.getElementById('edit_id').value = id;
            document.getElementById('edit_kode').value = kode;
            document.getElementById('edit_nama').value = nama;
            document.getElementById('edit_sks').value = sks;

            modal.style.display = "block";
        }
    </script>
</body>

</html>