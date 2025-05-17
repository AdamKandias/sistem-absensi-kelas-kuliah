<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
};
require_once '../config/koneksi.php';
require_once '../config/fungsi.php';

// Cek apakah user adalah admin
cek_admin();

// Proses tambah mahasiswa
if (isset($_POST['tambah'])) {
    $nim = bersihkan_input($_POST['nim']);
    $nama = bersihkan_input($_POST['nama']);
    $id_jurusan = bersihkan_input($_POST['id_jurusan']);
    $angkatan = bersihkan_input($_POST['angkatan']);
    $kelas_angkatan = bersihkan_input($_POST['kelas_angkatan']);
    $email = bersihkan_input($_POST['email']);

    // Cek apakah NIM sudah ada
    $cek_nim = $koneksi->prepare("SELECT id FROM mahasiswa WHERE nim = ?");
    $cek_nim->bind_param("s", $nim);
    $cek_nim->execute();
    $hasil_cek = $cek_nim->get_result();

    if ($hasil_cek->num_rows > 0) {
        $pesan_error = "NIM sudah terdaftar!";
    } else {
        // Insert data mahasiswa
        $query = "INSERT INTO mahasiswa (nim, nama, id_jurusan, angkatan, kelas_angkatan, email) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $koneksi->prepare($query);
        $stmt->bind_param("ssiiss", $nim, $nama, $id_jurusan, $angkatan, $kelas_angkatan, $email);

        if ($stmt->execute()) {
            // Buat akun pengguna untuk mahasiswa
            $username = $nim;
            $password = password_hash($nim, PASSWORD_DEFAULT); // Default password adalah NIM
            $peran = 'mahasiswa';
            $id_mahasiswa = $stmt->insert_id;

            $query_user = "INSERT INTO pengguna (username, password, nama, peran, id_referensi) VALUES (?, ?, ?, ?, ?)";
            $stmt_user = $koneksi->prepare($query_user);
            $stmt_user->bind_param("ssssi", $username, $password, $nama, $peran, $id_mahasiswa);
            $stmt_user->execute();

            $pesan_sukses = "Data mahasiswa berhasil ditambahkan!";
        } else {
            $pesan_error = "Gagal menambahkan data mahasiswa: " . $koneksi->error;
        }
    }
}

// Proses edit mahasiswa
if (isset($_POST['edit'])) {
    $id = bersihkan_input($_POST['id']);
    $nim = bersihkan_input($_POST['nim']);
    $nama = bersihkan_input($_POST['nama']);
    $id_jurusan = bersihkan_input($_POST['id_jurusan']);
    $angkatan = bersihkan_input($_POST['angkatan']);
    $kelas_angkatan = bersihkan_input($_POST['kelas_angkatan']);
    $email = bersihkan_input($_POST['email']);

    // Cek apakah NIM sudah ada (selain mahasiswa yang sedang diedit)
    $cek_nim = $koneksi->prepare("SELECT id FROM mahasiswa WHERE nim = ? AND id != ?");
    $cek_nim->bind_param("si", $nim, $id);
    $cek_nim->execute();
    $hasil_cek = $cek_nim->get_result();

    if ($hasil_cek->num_rows > 0) {
        $pesan_error = "NIM sudah terdaftar!";
    } else {
        // Update data mahasiswa
        $query = "UPDATE mahasiswa SET nim = ?, nama = ?, id_jurusan = ?, angkatan = ?, kelas_angkatan = ?, email = ? WHERE id = ?";
        $stmt = $koneksi->prepare($query);
        $stmt->bind_param("ssiissi", $nim, $nama, $id_jurusan, $angkatan, $kelas_angkatan, $email, $id);

        if ($stmt->execute()) {
            // Update data pengguna
            $query_user = "UPDATE pengguna SET username = ?, nama = ? WHERE peran = 'mahasiswa' AND id_referensi = ?";
            $stmt_user = $koneksi->prepare($query_user);
            $stmt_user->bind_param("ssi", $nim, $nama, $id);
            $stmt_user->execute();

            $pesan_sukses = "Data mahasiswa berhasil diperbarui!";
        } else {
            $pesan_error = "Gagal memperbarui data mahasiswa: " . $koneksi->error;
        }
    }
}

// Proses hapus mahasiswa
if (isset($_GET['hapus'])) {
    $id = bersihkan_input($_GET['hapus']);

    // Hapus data pengguna terlebih dahulu
    $query_user = "DELETE FROM pengguna WHERE peran = 'mahasiswa' AND id_referensi = ?";
    $stmt_user = $koneksi->prepare($query_user);
    $stmt_user->bind_param("i", $id);
    $stmt_user->execute();

    // Hapus data mahasiswa
    $query = "DELETE FROM mahasiswa WHERE id = ?";
    $stmt = $koneksi->prepare($query);
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        $pesan_sukses = "Data mahasiswa berhasil dihapus!";
    } else {
        $pesan_error = "Gagal menghapus data mahasiswa: " . $koneksi->error;
    }
}

// Ambil data jurusan untuk dropdown
$query_jurusan = "SELECT id, nama FROM jurusan ORDER BY nama";
$result_jurusan = $koneksi->query($query_jurusan);

// Ambil data mahasiswa
$query_mahasiswa = "
    SELECT m.*, j.nama as nama_jurusan 
    FROM mahasiswa m
    JOIN jurusan j ON m.id_jurusan = j.id
    ORDER BY m.nama
";
$result_mahasiswa = $koneksi->query($query_mahasiswa);
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Mahasiswa - Sistem Monitoring Kehadiran Praktikum</title>
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
                <li><a href="mahasiswa.php" class="active"><i class="fas fa-user-graduate"></i> Mahasiswa</a></li>
                <li><a href="dosen.php"><i class="fas fa-chalkboard-teacher"></i> Dosen</a></li>
                <li><a href="jurusan.php"><i class="fas fa-building"></i> Jurusan</a></li>
                <li><a href="kelas.php"><i class="fas fa-door-open"></i> Kelas</a></li>
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
                <h1>Kelola Data Mahasiswa</h1>
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
                    <h2>Tambah Mahasiswa</h2>
                </div>
                <div class="card-body">
                    <form method="POST" action="">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="nim">NIM</label>
                                    <input type="text" id="nim" name="nim" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="nama">Nama Lengkap</label>
                                    <input type="text" id="nama" name="nama" required>
                                </div>
                            </div>
                        </div>

                        <div class="row">
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
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="angkatan">Angkatan</label>
                                    <input type="number" id="angkatan" name="angkatan" min="2000" max="2100" required>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="kelas_angkatan">Kelas Angkatan</label>
                                    <input type="text" id="kelas_angkatan" name="kelas_angkatan" placeholder="Contoh: A, B, C" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="email">Email</label>
                                    <input type="email" id="email" name="email" required>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <button type="submit" name="tambah" class="btn btn-primary">Tambah Mahasiswa</button>
                        </div>
                    </form>
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
                                    <th>Jurusan</th>
                                    <th>Angkatan</th>
                                    <th>Kelas</th>
                                    <th>Email</th>
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
                                        echo "<td>" . $row['nama_jurusan'] . "</td>";
                                        echo "<td>" . $row['angkatan'] . "</td>";
                                        echo "<td>" . $row['kelas_angkatan'] . "</td>";
                                        echo "<td>" . $row['email'] . "</td>";
                                        echo "<td>
                                            <a href='#' class='btn btn-sm btn-info' onclick='editMahasiswa(" . $row['id'] . ", \"" . $row['nim'] . "\", \"" . $row['nama'] . "\", " . $row['id_jurusan'] . ", " . $row['angkatan'] . ", \""  . "\", \"" . $row['nama'] . "\", " . $row['id_jurusan'] . ", " . $row['angkatan'] . ", \"" . $row['kelas_angkatan'] . "\", \"" . $row['email'] . "\")'><i class='fas fa-edit'></i></a>
                                            <a href='mahasiswa.php?hapus=" . $row['id'] . "' class='btn btn-sm btn-danger btn-delete'><i class='fas fa-trash'></i></a>
                                        </td>";
                                        echo "</tr>";
                                    }
                                } else {
                                    echo "<tr><td colspan='8' class='text-center'>Belum ada data mahasiswa</td></tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Edit Mahasiswa -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Edit Mahasiswa</h2>
                <span class="close">&times;</span>
            </div>
            <div class="modal-body">
                <form method="POST" action="">
                    <input type="hidden" id="edit_id" name="id">

                    <div class="form-group">
                        <label for="edit_nim">NIM</label>
                        <input type="text" id="edit_nim" name="nim" required>
                    </div>

                    <div class="form-group">
                        <label for="edit_nama">Nama Lengkap</label>
                        <input type="text" id="edit_nama" name="nama" required>
                    </div>

                    <div class="form-group">
                        <label for="edit_id_jurusan">Jurusan</label>
                        <select id="edit_id_jurusan" name="id_jurusan" required>
                            <option value="">Pilih Jurusan</option>
                            <?php while ($jurusan = $result_jurusan->fetch_assoc()): ?>
                                <option value="<?php echo $jurusan['id']; ?>"><?php echo $jurusan['nama']; ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="edit_angkatan">Angkatan</label>
                        <input type="number" id="edit_angkatan" name="angkatan" min="2000" max="2100" required>
                    </div>

                    <div class="form-group">
                        <label for="edit_kelas_angkatan">Kelas Angkatan</label>
                        <input type="text" id="edit_kelas_angkatan" name="kelas_angkatan" required>
                    </div>

                    <div class="form-group">
                        <label for="edit_email">Email</label>
                        <input type="email" id="edit_email" name="email" required>
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

        function editMahasiswa(id, nim, nama, id_jurusan, angkatan, kelas_angkatan, email) {
            document.getElementById('edit_id').value = id;
            document.getElementById('edit_nim').value = nim;
            document.getElementById('edit_nama').value = nama;
            document.getElementById('edit_id_jurusan').value = id_jurusan;
            document.getElementById('edit_angkatan').value = angkatan;
            document.getElementById('edit_kelas_angkatan').value = kelas_angkatan;
            document.getElementById('edit_email').value = email;

            modal.style.display = "block";
        }
    </script>
</body>

</html>