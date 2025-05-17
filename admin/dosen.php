<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
};
require_once '../config/koneksi.php';
require_once '../config/fungsi.php';

// Cek apakah user adalah admin
cek_admin();

// Proses tambah dosen
if (isset($_POST['tambah'])) {
    $nip = bersihkan_input($_POST['nip']);
    $nama = bersihkan_input($_POST['nama']);
    $email = bersihkan_input($_POST['email']);
    $no_telp = bersihkan_input($_POST['no_telp']);

    // Cek apakah NIP sudah ada
    $cek_nip = $koneksi->prepare("SELECT id FROM dosen WHERE nip = ?");
    $cek_nip->bind_param("s", $nip);
    $cek_nip->execute();
    $hasil_cek = $cek_nip->get_result();

    if ($hasil_cek->num_rows > 0) {
        $pesan_error = "NIP sudah terdaftar!";
    } else {
        // Insert data dosen
        $query = "INSERT INTO dosen (nip, nama, email, no_telp) VALUES (?, ?, ?, ?)";
        $stmt = $koneksi->prepare($query);
        $stmt->bind_param("ssss", $nip, $nama, $email, $no_telp);

        if ($stmt->execute()) {
            // Buat akun pengguna untuk dosen
            $username = $nip;
            $password = password_hash($nip, PASSWORD_DEFAULT); // Default password adalah NIP
            $peran = 'dosen';
            $id_dosen = $stmt->insert_id;

            $query_user = "INSERT INTO pengguna (username, password, nama, peran, id_referensi) VALUES (?, ?, ?, ?, ?)";
            $stmt_user = $koneksi->prepare($query_user);
            $stmt_user->bind_param("ssssi", $username, $password, $nama, $peran, $id_dosen);
            $stmt_user->execute();

            $pesan_sukses = "Data dosen berhasil ditambahkan!";
        } else {
            $pesan_error = "Gagal menambahkan data dosen: " . $koneksi->error;
        }
    }
}

// Proses edit dosen
if (isset($_POST['edit'])) {
    $id = bersihkan_input($_POST['id']);
    $nip = bersihkan_input($_POST['nip']);
    $nama = bersihkan_input($_POST['nama']);
    $email = bersihkan_input($_POST['email']);
    $no_telp = bersihkan_input($_POST['no_telp']);

    // Cek apakah NIP sudah ada (selain dosen yang sedang diedit)
    $cek_nip = $koneksi->prepare("SELECT id FROM dosen WHERE nip = ? AND id != ?");
    $cek_nip->bind_param("si", $nip, $id);
    $cek_nip->execute();
    $hasil_cek = $cek_nip->get_result();

    if ($hasil_cek->num_rows > 0) {
        $pesan_error = "NIP sudah terdaftar!";
    } else {
        // Update data dosen
        $query = "UPDATE dosen SET nip = ?, nama = ?, email = ?, no_telp = ? WHERE id = ?";
        $stmt = $koneksi->prepare($query);
        $stmt->bind_param("ssssi", $nip, $nama, $email, $no_telp, $id);

        if ($stmt->execute()) {
            // Update data pengguna
            $query_user = "UPDATE pengguna SET username = ?, nama = ? WHERE peran = 'dosen' AND id_referensi = ?";
            $stmt_user = $koneksi->prepare($query_user);
            $stmt_user->bind_param("ssi", $nip, $nama, $id);
            $stmt_user->execute();

            $pesan_sukses = "Data dosen berhasil diperbarui!";
        } else {
            $pesan_error = "Gagal memperbarui data dosen: " . $koneksi->error;
        }
    }
}

// Proses hapus dosen
if (isset($_GET['hapus'])) {
    $id = bersihkan_input($_GET['hapus']);

    // Hapus data pengguna terlebih dahulu
    $query_user = "DELETE FROM pengguna WHERE peran = 'dosen' AND id_referensi = ?";
    $stmt_user = $koneksi->prepare($query_user);
    $stmt_user->bind_param("i", $id);
    $stmt_user->execute();

    // Hapus data dosen
    $query = "DELETE FROM dosen WHERE id = ?";
    $stmt = $koneksi->prepare($query);
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        $pesan_sukses = "Data dosen berhasil dihapus!";
    } else {
        $pesan_error = "Gagal menghapus data dosen: " . $koneksi->error;
    }
}

// Ambil data dosen
$query_dosen = "SELECT * FROM dosen ORDER BY nama";
$result_dosen = $koneksi->query($query_dosen);
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Dosen - Sistem Monitoring Kehadiran Praktikum</title>
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
                <li><a href="dosen.php" class="active"><i class="fas fa-chalkboard-teacher"></i> Dosen</a></li>
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
                <h1>Kelola Data Dosen</h1>
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
                    <h2>Tambah Dosen</h2>
                </div>
                <div class="card-body">
                    <form method="POST" action="">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="nip">NIP</label>
                                    <input type="text" id="nip" name="nip" required>
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
                                    <label for="email">Email</label>
                                    <input type="email" id="email" name="email" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="no_telp">Nomor Telepon</label>
                                    <input type="text" id="no_telp" name="no_telp" required>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <button type="submit" name="tambah" class="btn btn-primary">Tambah Dosen</button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h2>Daftar Dosen</h2>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <input type="text" id="filterInput" placeholder="Cari dosen..." class="form-control">
                    </div>

                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>NIP</th>
                                    <th>Nama</th>
                                    <th>Email</th>
                                    <th>Nomor Telepon</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $no = 1;
                                if ($result_dosen->num_rows > 0) {
                                    while ($row = $result_dosen->fetch_assoc()) {
                                        echo "<tr>";
                                        echo "<td>" . $no++ . "</td>";
                                        echo "<td>" . $row['nip'] . "</td>";
                                        echo "<td>" . $row['nama'] . "</td>";
                                        echo "<td>" . $row['email'] . "</td>";
                                        echo "<td>" . $row['no_telp'] . "</td>";
                                        echo "<td>
                                            <a href='#' class='btn btn-sm btn-info' onclick='editDosen(" . $row['id'] . ", \"" . $row['nip'] . "\", \"" . $row['nama'] . "\", \"" . $row['email'] . "\", \"" . $row['no_telp'] . "\")'><i class='fas fa-edit'></i></a>
                                            <a href='dosen.php?hapus=" . $row['id'] . "' class='btn btn-sm btn-danger btn-delete'><i class='fas fa-trash'></i></a>
                                        </td>";
                                        echo "</tr>";
                                    }
                                } else {
                                    echo "<tr><td colspan='6' class='text-center'>Belum ada data dosen</td></tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Edit Dosen -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Edit Dosen</h2>
                <span class="close">&times;</span>
            </div>
            <div class="modal-body">
                <form method="POST" action="">
                    <input type="hidden" id="edit_id" name="id">

                    <div class="form-group">
                        <label for="edit_nip">NIP</label>
                        <input type="text" id="edit_nip" name="nip" required>
                    </div>

                    <div class="form-group">
                        <label for="edit_nama">Nama Lengkap</label>
                        <input type="text" id="edit_nama" name="nama" required>
                    </div>

                    <div class="form-group">
                        <label for="edit_email">Email</label>
                        <input type="email" id="edit_email" name="email" required>
                    </div>

                    <div class="form-group">
                        <label for="edit_no_telp">Nomor Telepon</label>
                        <input type="text" id="edit_no_telp" name="no_telp" required>
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

        function editDosen(id, nip, nama, email, no_telp) {
            document.getElementById('edit_id').value = id;
            document.getElementById('edit_nip').value = nip;
            document.getElementById('edit_nama').value = nama;
            document.getElementById('edit_email').value = email;
            document.getElementById('edit_no_telp').value = no_telp;

            modal.style.display = "block";
        }
    </script>
</body>

</html>