<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
};
require_once '../config/koneksi.php';
require_once '../config/fungsi.php';

// Cek apakah user adalah mahasiswa
cek_mahasiswa();

// Ambil data mahasiswa
$id_mahasiswa = $_SESSION['user_id'];
$query_mahasiswa = "SELECT * FROM mahasiswa WHERE id = (SELECT id_referensi FROM pengguna WHERE id = ?)";
$stmt_mahasiswa = $koneksi->prepare($query_mahasiswa);
$stmt_mahasiswa->bind_param("i", $id_mahasiswa);
$stmt_mahasiswa->execute();
$result_mahasiswa = $stmt_mahasiswa->get_result();
$mahasiswa = $result_mahasiswa->fetch_assoc();
$id_mahasiswa_asli = $mahasiswa['id'];

// Ambil data jurusan
$query_jurusan = "SELECT nama FROM jurusan WHERE id = ?";
$stmt_jurusan = $koneksi->prepare($query_jurusan);
$stmt_jurusan->bind_param("i", $mahasiswa['id_jurusan']);
$stmt_jurusan->execute();
$result_jurusan = $stmt_jurusan->get_result();
$jurusan = $result_jurusan->fetch_assoc();

// Proses update profil
if (isset($_POST['update_profil'])) {
    $email = bersihkan_input($_POST['email']);
    $password_lama = $_POST['password_lama'];
    $password_baru = $_POST['password_baru'];
    $konfirmasi_password = $_POST['konfirmasi_password'];

    // Validasi email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $pesan_error = "Format email tidak valid!";
    } else {
        // Update email
        $query_update_email = "UPDATE mahasiswa SET email = ? WHERE id = ?";
        $stmt_update_email = $koneksi->prepare($query_update_email);
        $stmt_update_email->bind_param("si", $email, $id_mahasiswa_asli);
        $stmt_update_email->execute();

        // Jika ada password baru
        if (!empty($password_baru)) {
            // Cek password lama
            $query_cek_password = "SELECT password FROM pengguna WHERE id = ?";
            $stmt_cek_password = $koneksi->prepare($query_cek_password);
            $stmt_cek_password->bind_param("i", $id_mahasiswa);
            $stmt_cek_password->execute();
            $result_cek_password = $stmt_cek_password->get_result();
            $data_password = $result_cek_password->fetch_assoc();

            if (!password_verify($password_lama, $data_password['password'])) {
                $pesan_error = "Password lama tidak sesuai!";
            } elseif ($password_baru != $konfirmasi_password) {
                $pesan_error = "Konfirmasi password tidak sesuai!";
            } else {
                // Update password
                $password_hash = password_hash($password_baru, PASSWORD_DEFAULT);
                $query_update_password = "UPDATE pengguna SET password = ? WHERE id = ?";
                $stmt_update_password = $koneksi->prepare($query_update_password);
                $stmt_update_password->bind_param("si", $password_hash, $id_mahasiswa);
                $stmt_update_password->execute();

                $pesan_sukses = "Profil berhasil diperbarui!";
            }
        } else {
            $pesan_sukses = "Email berhasil diperbarui!";
        }

        // Refresh data mahasiswa
        $stmt_mahasiswa->execute();
        $result_mahasiswa = $stmt_mahasiswa->get_result();
        $mahasiswa = $result_mahasiswa->fetch_assoc();
    }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil Saya - Sistem Monitoring Kehadiran Praktikum</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
</head>

<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            <h3>Panel Mahasiswa</h3>
        </div>
        <div class="sidebar-menu">
            <ul>
                <li><a href="index.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a href="kelas.php"><i class="fas fa-door-open"></i> Kelas Saya</a></li>
                <li><a href="kehadiran.php"><i class="fas fa-clipboard-check"></i> Kehadiran Saya</a></li>
                <li><a href="profil.php" class="active"><i class="fas fa-user"></i> Profil Saya</a></li>
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
                <h1>Profil Saya</h1>
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

            <div class="row">
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header">
                            <h2>Informasi Mahasiswa</h2>
                        </div>
                        <div class="card-body">
                            <div class="text-center mb-4">
                                <i class="fas fa-user-graduate fa-5x" style="color: var(--warna-sekunder);"></i>
                            </div>
                            <table class="table">
                                <tr>
                                    <th>NIM</th>
                                    <td><?php echo $mahasiswa['nim']; ?></td>
                                </tr>
                                <tr>
                                    <th>Nama</th>
                                    <td><?php echo $mahasiswa['nama']; ?></td>
                                </tr>
                                <tr>
                                    <th>Jurusan</th>
                                    <td><?php echo $jurusan['nama']; ?></td>
                                </tr>
                                <tr>
                                    <th>Angkatan</th>
                                    <td><?php echo $mahasiswa['angkatan']; ?></td>
                                </tr>
                                <tr>
                                    <th>Kelas</th>
                                    <td><?php echo $mahasiswa['kelas_angkatan']; ?></td>
                                </tr>
                                <tr>
                                    <th>Email</th>
                                    <td><?php echo $mahasiswa['email']; ?></td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header">
                            <h2>Edit Profil</h2>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="">
                                <div class="form-group">
                                    <label for="email">Email</label>
                                    <input type="email" id="email" name="email" value="<?php echo $mahasiswa['email']; ?>" required>
                                </div>

                                <hr class="mt-4 mb-4">
                                <h3>Ubah Password</h3>
                                <p class="text-muted">Kosongkan jika tidak ingin mengubah password</p>

                                <div class="form-group">
                                    <label for="password_lama">Password Lama</label>
                                    <input type="password" id="password_lama" name="password_lama">
                                </div>

                                <div class="form-group">
                                    <label for="password_baru">Password Baru</label>
                                    <input type="password" id="password_baru" name="password_baru">
                                </div>

                                <div class="form-group">
                                    <label for="konfirmasi_password">Konfirmasi Password Baru</label>
                                    <input type="password" id="konfirmasi_password" name="konfirmasi_password">
                                </div>

                                <div class="form-group">
                                    <button type="submit" name="update_profil" class="btn btn-primary">Simpan Perubahan</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="../assets/js/script.js"></script>
</body>

</html>