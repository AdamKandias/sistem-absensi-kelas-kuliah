<?php
$judul_halaman = "Profil Saya";
require_once '../includes/header_dosen.php';

// Proses update profil
if (isset($_POST['update_profil'])) {
    $email = bersihkan_input($_POST['email']);
    $no_telp = bersihkan_input($_POST['no_telp']);
    $password_lama = $_POST['password_lama'];
    $password_baru = $_POST['password_baru'];
    $konfirmasi_password = $_POST['konfirmasi_password'];
    
    // Validasi email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $pesan_error = "Format email tidak valid!";
    } else {
        // Update email dan no_telp
        $query_update = "UPDATE dosen SET email = ?, no_telp = ? WHERE id = ?";
        $stmt_update = $koneksi->prepare($query_update);
        $stmt_update->bind_param("ssi", $email, $no_telp, $id_dosen_asli);
        $stmt_update->execute();
        
        // Jika ada password baru
        if (!empty($password_baru)) {
            // Cek password lama
            $query_cek_password = "SELECT password FROM pengguna WHERE id = ?";
            $stmt_cek_password = $koneksi->prepare($query_cek_password);
            $stmt_cek_password->bind_param("i", $id_dosen);
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
                $stmt_update_password->bind_param("si", $password_hash, $id_dosen);
                $stmt_update_password->execute();
                
                $pesan_sukses = "Profil berhasil diperbarui!";
            }
        } else {
            $pesan_sukses = "Profil berhasil diperbarui!";
        }
        
        // Refresh data dosen
        $stmt_dosen->execute();
        $result_dosen = $stmt_dosen->get_result();
        $dosen = $result_dosen->fetch_assoc();
    }
}
?>

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
                <h2>Informasi Dosen</h2>
            </div>
            <div class="card-body">
                <div class="text-center mb-4">
                    <i class="fas fa-chalkboard-teacher fa-5x" style="color: var(--warna-sekunder);"></i>
                </div>
                <table class="table">
                    <tr>
                        <th>NIP</th>
                        <td><?php echo $dosen['nip']; ?></td>
                    </tr>
                    <tr>
                        <th>Nama</th>
                        <td><?php echo $dosen['nama']; ?></td>
                    </tr>
                    <tr>
                        <th>Email</th>
                        <td><?php echo $dosen['email']; ?></td>
                    </tr>
                    <tr>
                        <th>No. Telepon</th>
                        <td><?php echo $dosen['no_telp']; ?></td>
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
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="email">Email</label>
                                <input type="email" id="email" name="email" value="<?php echo $dosen['email']; ?>" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="no_telp">Nomor Telepon</label>
                                <input type="text" id="no_telp" name="no_telp" value="<?php echo $dosen['no_telp']; ?>" required>
                            </div>
                        </div>
                    </div>
                    
                    <hr class="mt-4 mb-4">
                    <h3>Ubah Password</h3>
                    <p class="text-muted">Kosongkan jika tidak ingin mengubah password</p>
                    
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="password_lama">Password Lama</label>
                                <input type="password" id="password_lama" name="password_lama">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="password_baru">Password Baru</label>
                                <input type="password" id="password_baru" name="password_baru">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="konfirmasi_password">Konfirmasi Password Baru</label>
                                <input type="password" id="konfirmasi_password" name="konfirmasi_password">
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" name="update_profil" class="btn btn-primary">Simpan Perubahan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer_dosen.php'; ?>
