<?php
$judul_halaman = "Login";
require_once 'includes/header.php';

$pesan_error = '';

// Proses login
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = bersihkan_input($_POST['username']);
    $password = $_POST['password'];
    
    if (empty($username) || empty($password)) {
        $pesan_error = "Username dan password harus diisi!";
    } else {
        // Query untuk mencari user
        $query = "SELECT * FROM pengguna WHERE username = ?";
        $stmt = $koneksi->prepare($query);
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $hasil = $stmt->get_result();
        
        if ($hasil->num_rows == 1) {
            $user = $hasil->fetch_assoc();
            // Verifikasi password
            if (password_verify($password, $user['password'])) {
                // Set session
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['nama'] = $user['nama'];
                $_SESSION['peran'] = $user['peran'];
                
                // Redirect sesuai peran
                header("Location: index.php");
                exit();
            } else {
                $pesan_error = "Password salah!";
            }
        } else {
            $pesan_error = "Username tidak ditemukan!";
        }
    }
}
?>

<div class="container login-container">
    <div class="login-card">
        <div class="login-header">
            <h1>Sistem Monitoring Kehadiran Praktikum</h1>
            <p>Silakan login untuk melanjutkan</p>
        </div>
        
        <?php if (!empty($pesan_error)): ?>
            <div class="alert alert-error">
                <?php echo $pesan_error; ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="login.php" class="login-form">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required>
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>
            
            <div class="form-group">
                <button type="submit" class="btn btn-primary btn-block">Login</button>
            </div>
        </form>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
