<?php
session_start();
require_once '../config/koneksi.php';
require_once '../config/fungsi.php';

// Cek apakah user adalah admin
cek_admin();

// Proses tambah pengguna
if (isset($_POST['tambah'])) {
    $username = bersihkan_input($_POST['username']);
    $password = $_POST['password'];
    $nama = bersihkan_input($_POST['nama']);
    $peran = bersihkan_input($_POST['peran']);
    $id_referensi = bersihkan_input($_POST['id_referensi']);

    // Cek apakah username sudah ada
    $cek_username = $koneksi->prepare("SELECT id FROM pengguna WHERE username = ?");
    $cek_username->bind_param("s", $username);
    $cek_username->execute();
    $hasil_cek = $cek_username->get_result();

    if ($hasil_cek->num_rows > 0) {
        $pesan_error = "Username sudah terdaftar!";
    } else {
        // Hash password
        $password_hash = password_hash($password, PASSWORD_DEFAULT);

        // Insert data pengguna
        $query = "INSERT INTO pengguna (username, password, nama, peran, id_referensi) VALUES (?, ?, ?, ?, ?)";
        $stmt = $koneksi->prepare($query);
        $stmt->bind_param("ssssi", $username, $password_hash, $nama, $peran, $id_referensi);

        if ($stmt->execute()) {
            $pesan_sukses = "Data pengguna berhasil ditambahkan!";
        } else {
            $pesan_error = "Gagal menambahkan data pengguna: " . $koneksi->error;
        }
    }
}

// Proses edit pengguna
if (isset($_POST['edit'])) {
    $id = bersihkan_input($_POST['id']);
    $username = bersihkan_input($_POST['username']);
    $nama = bersihkan_input($_POST['nama']);
    $peran = bersihkan_input($_POST['peran']);
    $id_referensi = bersihkan_input($_POST['id_referensi']);
    $password = $_POST['password'];

    // Cek apakah username sudah ada (selain pengguna yang sedang diedit)
    $cek_username = $koneksi->prepare("SELECT id FROM pengguna WHERE username = ? AND id != ?");
    $cek_username->bind_param("si", $username, $id);
    $cek_username->execute();
    $hasil_cek = $cek_username->get_result();

    if ($hasil_cek->num_rows > 0) {
        $pesan_error = "Username sudah terdaftar!";
    } else {
        // Jika password diisi, update password juga
        if (!empty($password)) {
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            $query = "UPDATE pengguna SET username = ?, password = ?, nama = ?, peran = ?, id_referensi = ? WHERE id = ?";
            $stmt = $koneksi->prepare($query);
            $stmt->bind_param("ssssii", $username, $password_hash, $nama, $peran, $id_referensi, $id);
        } else {
            // Jika password tidak diisi, hanya update data lainnya
            $query = "UPDATE pengguna SET username = ?, nama = ?, peran = ?, id_referensi = ? WHERE id = ?";
            $stmt = $koneksi->prepare($query);
            $stmt->bind_param("sssii", $username, $nama, $peran, $id_referensi, $id);
        }

        if ($stmt->execute()) {
            $pesan_sukses = "Data pengguna berhasil diperbarui!";
        } else {
            $pesan_error = "Gagal memperbarui data pengguna: " . $koneksi->error;
        }
    }
}

// Proses reset password
if (isset($_GET['reset'])) {
    $id = bersihkan_input($_GET['reset']);

    // Ambil username untuk dijadikan password default
    $query_user = "SELECT username FROM pengguna WHERE id = ?";
    $stmt_user = $koneksi->prepare($query_user);
    $stmt_user->bind_param("i", $id);
    $stmt_user->execute();
    $result_user = $stmt_user->get_result();

    if ($result_user->num_rows > 0) {
        $user = $result_user->fetch_assoc();
        $username = $user['username'];

        // Reset password ke username
        $password_hash = password_hash($username, PASSWORD_DEFAULT);
        $query = "UPDATE pengguna SET password = ? WHERE id = ?";
        $stmt = $koneksi->prepare($query);
        $stmt->bind_param("si", $password_hash, $id);

        if ($stmt->execute()) {
            $pesan_sukses = "Password berhasil direset! Password baru adalah username pengguna.";
        } else {
            $pesan_error = "Gagal mereset password: " . $koneksi->error;
        }
    } else {
        $pesan_error = "Pengguna tidak ditemukan!";
    }
}

// Proses hapus pengguna
if (isset($_GET['hapus'])) {
    $id = bersihkan_input($_GET['hapus']);

    // Cek apakah pengguna adalah admin
    $query_cek = "SELECT peran FROM pengguna WHERE id = ?";
    $stmt_cek = $koneksi->prepare($query_cek);
    $stmt_cek->bind_param("i", $id);
    $stmt_cek->execute();
    $result_cek = $stmt_cek->get_result();
    $user = $result_cek->fetch_assoc();

    if ($user['peran'] == 'admin' && $_SESSION['user_id'] != $id) {
        $pesan_error = "Anda tidak dapat menghapus akun admin lain!";
    } else {
        // Hapus data pengguna
        $query = "DELETE FROM pengguna WHERE id = ?";
        $stmt = $koneksi->prepare($query);
        $stmt->bind_param("i", $id);

        if ($stmt->execute()) {
            $pesan_sukses = "Data pengguna berhasil dihapus!";
        } else {
            $pesan_error = "Gagal menghapus data pengguna: " . $koneksi->error;
        }
    }
}

// Ambil data dosen untuk dropdown
$query_dosen = "SELECT id, nama, nip FROM dosen ORDER BY nama";
$result_dosen = $koneksi->query($query_dosen);

// Ambil data mahasiswa untuk dropdown
$query_mahasiswa = "SELECT id, nama, nim FROM mahasiswa ORDER BY nama";
$result_mahasiswa = $koneksi->query($query_mahasiswa);

// Ambil data pengguna
$query_pengguna = "SELECT p.*, 
                    CASE 
                        WHEN p.peran = 'dosen' THEN d.nama 
                        WHEN p.peran = 'mahasiswa' THEN m.nama 
                        ELSE 'Admin' 
                    END as nama_lengkap,
                    CASE 
                        WHEN p.peran = 'dosen' THEN d.nip 
                        WHEN p.peran = 'mahasiswa' THEN m.nim 
                        ELSE '-' 
                    END as nomor_identitas
                FROM pengguna p
                LEFT JOIN dosen d ON p.id_referensi = d.id AND p.peran = 'dosen'
                LEFT JOIN mahasiswa m ON p.id_referensi = m.id AND p.peran = 'mahasiswa'
                ORDER BY p.peran, p.nama";
$result_pengguna = $koneksi->query($query_pengguna);
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Pengguna - Sistem Monitoring Kehadiran Praktikum</title>
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
                <li><a href="mata_kuliah.php"><i class="fas fa-book"></i> Mata Kuliah</a></li>
                <li><a href="pengguna.php" class="active"><i class="fas fa-users"></i> Pengguna</a></li>
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
                <h1>Kelola Data Pengguna</h1>
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
                    <h2>Tambah Pengguna</h2>
                </div>
                <div class="card-body">
                    <form method="POST" action="" id="formTambah">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="username">Username</label>
                                    <input type="text" id="username" name="username" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="password">Password</label>
                                    <input type="password" id="password" name="password" required>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="nama">Nama</label>
                                    <input type="text" id="nama" name="nama" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="peran">Peran</label>
                                    <select id="peran" name="peran" required onchange="toggleReferensi()">
                                        <option value="">Pilih Peran</option>
                                        <option value="admin">Admin</option>
                                        <option value="dosen">Dosen</option>
                                        <option value="mahasiswa">Mahasiswa</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="form-group" id="referensi_dosen" style="display: none;">
                            <label for="id_referensi_dosen">Dosen</label>
                            <select id="id_referensi_dosen" name="id_referensi_dosen">
                                <option value="">Pilih Dosen</option>
                                <?php while ($dosen = $result_dosen->fetch_assoc()): ?>
                                    <option value="<?php echo $dosen['id']; ?>"><?php echo $dosen['nip'] . ' - ' . $dosen['nama']; ?></option>
                                <?php endwhile; ?>
                                <?php $result_dosen->data_seek(0); // Reset pointer 
                                ?>
                            </select>
                        </div>

                        <div class="form-group" id="referensi_mahasiswa" style="display: none;">
                            <label for="id_referensi_mahasiswa">Mahasiswa</label>
                            <select id="id_referensi_mahasiswa" name="id_referensi_mahasiswa">
                                <option value="">Pilih Mahasiswa</option>
                                <?php while ($mahasiswa = $result_mahasiswa->fetch_assoc()): ?>
                                    <option value="<?php echo $mahasiswa['id']; ?>"><?php echo $mahasiswa['nim'] . ' - ' . $mahasiswa['nama']; ?></option>
                                <?php endwhile; ?>
                                <?php $result_mahasiswa->data_seek(0); // Reset pointer 
                                ?>
                            </select>
                        </div>

                        <input type="hidden" id="id_referensi" name="id_referensi" value="0">

                        <div class="form-group">
                            <button type="submit" name="tambah" class="btn btn-primary">Tambah Pengguna</button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h2>Daftar Pengguna</h2>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <input type="text" id="filterInput" placeholder="Cari pengguna..." class="form-control">
                    </div>

                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Username</th>
                                    <th>Nama</th>
                                    <th>Peran</th>
                                    <th>Nomor Identitas</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $no = 1;
                                if ($result_pengguna->num_rows > 0) {
                                    while ($row = $result_pengguna->fetch_assoc()) {
                                        echo "<tr>";
                                        echo "<td>" . $no++ . "</td>";
                                        echo "<td>" . $row['username'] . "</td>";
                                        echo "<td>" . $row['nama_lengkap'] . "</td>";
                                        echo "<td>";
                                        if ($row['peran'] == 'admin') {
                                            echo '<span class="badge badge-primary">Admin</span>';
                                        } elseif ($row['peran'] == 'dosen') {
                                            echo '<span class="badge badge-info">Dosen</span>';
                                        } elseif ($row['peran'] == 'mahasiswa') {
                                            echo '<span class="badge badge-success">Mahasiswa</span>';
                                        }
                                        echo "</td>";
                                        echo "<td>" . $row['nomor_identitas'] . "</td>";
                                        echo "<td>";

                                        // Edit button
                                        echo "<a href='#' class='btn btn-sm btn-info mr-1' onclick='editPengguna(" . $row['id'] . ", \"" . $row['username'] . "\", \"" . $row['nama'] . "\", \"" . $row['peran'] . "\", " . $row['id_referensi'] . ")'><i class='fas fa-edit'></i></a>";

                                        // Reset password button
                                        echo "<a href='pengguna.php?reset=" . $row['id'] . "' class='btn btn-sm btn-warning mr-1' onclick='return confirm(\"Apakah Anda yakin ingin mereset password pengguna ini?\")'><i class='fas fa-key'></i></a>";

                                        // Delete button (only for non-admin or current admin)
                                        if ($row['peran'] != 'admin' || $row['id'] == $_SESSION['user_id']) {
                                            echo "<a href='pengguna.php?hapus=" . $row['id'] . "' class='btn btn-sm btn-danger btn-delete'><i class='fas fa-trash'></i></a>";
                                        }

                                        echo "</td>";
                                        echo "</tr>";
                                    }
                                } else {
                                    echo "<tr><td colspan='6' class='text-center'>Belum ada data pengguna</td></tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Edit Pengguna -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Edit Pengguna</h2>
                <span class="close">&times;</span>
            </div>
            <div class="modal-body">
                <form method="POST" action="" id="formEdit">
                    <input type="hidden" id="edit_id" name="id">

                    <div class="form-group">
                        <label for="edit_username">Username</label>
                        <input type="text" id="edit_username" name="username" required>
                    </div>

                    <div class="form-group">
                        <label for="edit_password">Password (Kosongkan jika tidak ingin mengubah)</label>
                        <input type="password" id="edit_password" name="password">
                    </div>

                    <div class="form-group">
                        <label for="edit_nama">Nama</label>
                        <input type="text" id="edit_nama" name="nama" required>
                    </div>

                    <div class="form-group">
                        <label for="edit_peran">Peran</label>
                        <select id="edit_peran" name="peran" required onchange="toggleEditReferensi()">
                            <option value="">Pilih Peran</option>
                            <option value="admin">Admin</option>
                            <option value="dosen">Dosen</option>
                            <option value="mahasiswa">Mahasiswa</option>
                        </select>
                    </div>

                    <div class="form-group" id="edit_referensi_dosen" style="display: none;">
                        <label for="edit_id_referensi_dosen">Dosen</label>
                        <select id="edit_id_referensi_dosen" name="edit_id_referensi_dosen">
                            <option value="">Pilih Dosen</option>
                            <?php
                            $result_dosen->data_seek(0); // Reset pointer
                            while ($dosen = $result_dosen->fetch_assoc()):
                            ?>
                                <option value="<?php echo $dosen['id']; ?>"><?php echo $dosen['nip'] . ' - ' . $dosen['nama']; ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="form-group" id="edit_referensi_mahasiswa" style="display: none;">
                        <label for="edit_id_referensi_mahasiswa">Mahasiswa</label>
                        <select id="edit_id_referensi_mahasiswa" name="edit_id_referensi_mahasiswa">
                            <option value="">Pilih Mahasiswa</option>
                            <?php
                            $result_mahasiswa->data_seek(0); // Reset pointer
                            while ($mahasiswa = $result_mahasiswa->fetch_assoc()):
                            ?>
                                <option value="<?php echo $mahasiswa['id']; ?>"><?php echo $mahasiswa['nim'] . ' - ' . $mahasiswa['nama']; ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <input type="hidden" id="edit_id_referensi" name="id_referensi" value="0">

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

        // Toggle referensi dropdown based on peran
        function toggleReferensi() {
            const peran = document.getElementById('peran').value;
            const referensiDosen = document.getElementById('referensi_dosen');
            const referensiMahasiswa = document.getElementById('referensi_mahasiswa');
            const idReferensi = document.getElementById('id_referensi');

            referensiDosen.style.display = 'none';
            referensiMahasiswa.style.display = 'none';

            if (peran === 'dosen') {
                referensiDosen.style.display = 'block';
                document.getElementById('formTambah').onsubmit = function() {
                    idReferensi.value = document.getElementById('id_referensi_dosen').value;
                    return true;
                };
            } else if (peran === 'mahasiswa') {
                referensiMahasiswa.style.display = 'block';
                document.getElementById('formTambah').onsubmit = function() {
                    idReferensi.value = document.getElementById('id_referensi_mahasiswa').value;
                    return true;
                };
            } else {
                idReferensi.value = 0;
            }
        }

        // Toggle edit referensi dropdown based on peran
        function toggleEditReferensi() {
            const peran = document.getElementById('edit_peran').value;
            const referensiDosen = document.getElementById('edit_referensi_dosen');
            const referensiMahasiswa = document.getElementById('edit_referensi_mahasiswa');
            const idReferensi = document.getElementById('edit_id_referensi');

            referensiDosen.style.display = 'none';
            referensiMahasiswa.style.display = 'none';

            if (peran === 'dosen') {
                referensiDosen.style.display = 'block';
                document.getElementById('formEdit').onsubmit = function() {
                    idReferensi.value = document.getElementById('edit_id_referensi_dosen').value;
                    return true;
                };
            } else if (peran === 'mahasiswa') {
                referensiMahasiswa.style.display = 'block';
                document.getElementById('formEdit').onsubmit = function() {
                    idReferensi.value = document.getElementById('edit_id_referensi_mahasiswa').value;
                    return true;
                };
            } else {
                idReferensi.value = 0;
            }
        }

        function editPengguna(id, username, nama, peran, id_referensi) {
            document.getElementById('edit_id').value = id;
            document.getElementById('edit_username').value = username;
            document.getElementById('edit_nama').value = nama;
            document.getElementById('edit_peran').value = peran;
            document.getElementById('edit_id_referensi').value = id_referensi;

            // Show appropriate referensi dropdown
            if (peran === 'dosen') {
                document.getElementById('edit_referensi_dosen').style.display = 'block';
                document.getElementById('edit_id_referensi_dosen').value = id_referensi;
            } else if (peran === 'mahasiswa') {
                document.getElementById('edit_referensi_mahasiswa').style.display = 'block';
                document.getElementById('edit_id_referensi_mahasiswa').value = id_referensi;
            }

            modal.style.display = "block";
        }
    </script>
</body>

</html>