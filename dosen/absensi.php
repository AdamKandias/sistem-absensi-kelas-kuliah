<?php
date_default_timezone_set('Asia/Jakarta');
if (session_status() === PHP_SESSION_NONE) {
    session_start();
};
require_once '../config/koneksi.php';
require_once '../config/fungsi.php';

// Cek apakah user adalah dosen
cek_dosen();

// Periksa dan tutup sesi yang sudah melewati waktu tutup
periksa_sesi_kadaluarsa($koneksi);

// Ambil data dosen
$id_dosen = $_SESSION['user_id'];
$query_dosen = "SELECT * FROM dosen WHERE id = (SELECT id_referensi FROM pengguna WHERE id = ?)";
$stmt_dosen = $koneksi->prepare($query_dosen);
$stmt_dosen->bind_param("i", $id_dosen);
$stmt_dosen->execute();
$result_dosen = $stmt_dosen->get_result();
$dosen = $result_dosen->fetch_assoc();
$id_dosen_asli = $dosen['id'];

$judul_halaman = "Kelola Absensi";
require_once '../includes/header_dosen.php';

// Proses buka sesi absensi
if (isset($_POST['buka_absensi'])) {
    $id_kelas = bersihkan_input($_POST['id_kelas']);
    $pertemuan = bersihkan_input($_POST['pertemuan']);
    $tanggal = bersihkan_input($_POST['tanggal']);
    $materi = bersihkan_input($_POST['materi']);
    $durasi = bersihkan_input($_POST['durasi']); // Durasi dalam jam

    // Cek apakah sudah ada sesi absensi untuk pertemuan ini
    $query_cek = "SELECT id FROM sesi_absensi WHERE id_kelas = ? AND pertemuan = ?";
    $stmt_cek = $koneksi->prepare($query_cek);
    $stmt_cek->bind_param("ii", $id_kelas, $pertemuan);
    $stmt_cek->execute();
    $result_cek = $stmt_cek->get_result();

    if ($result_cek->num_rows > 0) {
        $pesan_error = "Sesi absensi untuk pertemuan ini sudah pernah dibuka!";
    } else {
        // Hitung waktu tutup otomatis
        $waktu_sekarang = date('Y-m-d H:i:s');
        $waktu_tutup = date('Y-m-d H:i:s', strtotime("+{$durasi} hours"));

        // Buka sesi absensi baru
        $status = 'dibuka';
        $query = "INSERT INTO sesi_absensi (id_kelas, pertemuan, tanggal, materi, status, waktu_tutup) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $koneksi->prepare($query);
        $stmt->bind_param("iissss", $id_kelas, $pertemuan, $tanggal, $materi, $status, $waktu_tutup);

        if ($stmt->execute()) {
            $pesan_sukses = "Sesi absensi berhasil dibuka! Sesi akan otomatis ditutup pada " . date('d-m-Y H:i', strtotime($waktu_tutup));
        } else {
            $pesan_error = "Gagal membuka sesi absensi: " . $koneksi->error;
        }
    }
}

// Proses tutup sesi absensi (manual)
if (isset($_GET['tutup'])) {
    $id_sesi = bersihkan_input($_GET['tutup']);

    // Cek apakah sesi ini milik dosen yang sedang login
    $query_cek = "
        SELECT sa.id 
        FROM sesi_absensi sa
        JOIN kelas k ON sa.id_kelas = k.id
        WHERE sa.id = ? AND k.id_dosen = ?
    ";
    $stmt_cek = $koneksi->prepare($query_cek);
    $stmt_cek->bind_param("ii", $id_sesi, $id_dosen_asli);
    $stmt_cek->execute();
    $result_cek = $stmt_cek->get_result();

    if ($result_cek->num_rows == 0) {
        $pesan_error = "Anda tidak memiliki akses untuk menutup sesi ini!";
    } else {
        // Tutup sesi absensi
        $status = 'ditutup';
        $query = "UPDATE sesi_absensi SET status = ? WHERE id = ?";
        $stmt = $koneksi->prepare($query);
        $stmt->bind_param("si", $status, $id_sesi);

        if ($stmt->execute()) {
            $pesan_sukses = "Sesi absensi berhasil ditutup!";
        } else {
            $pesan_error = "Gagal menutup sesi absensi: " . $koneksi->error;
        }
    }
}

// Proses tutup sesi absensi (otomatis)
if (isset($_GET['auto_tutup'])) {
    $id_sesi = bersihkan_input($_GET['auto_tutup']);

    // Cek apakah sesi ini milik dosen yang sedang login
    $query_cek = "
        SELECT sa.id 
        FROM sesi_absensi sa
        JOIN kelas k ON sa.id_kelas = k.id
        WHERE sa.id = ? AND k.id_dosen = ? AND sa.status = 'dibuka'
    ";
    $stmt_cek = $koneksi->prepare($query_cek);
    $stmt_cek->bind_param("ii", $id_sesi, $id_dosen_asli);
    $stmt_cek->execute();
    $result_cek = $stmt_cek->get_result();

    if ($result_cek->num_rows > 0) {
        // Tutup sesi absensi
        $status = 'ditutup';
        $query = "UPDATE sesi_absensi SET status = ? WHERE id = ?";
        $stmt = $koneksi->prepare($query);
        $stmt->bind_param("si", $status, $id_sesi);

        if ($stmt->execute()) {
            $pesan_sukses = "Sesi absensi berhasil ditutup secara otomatis!";
        } else {
            $pesan_error = "Gagal menutup sesi absensi: " . $koneksi->error;
        }
    }
}

// Ambil kelas yang diajar oleh dosen untuk dropdown
$query_kelas = "
    SELECT k.*, mk.nama as nama_matkul, j.nama as nama_jurusan
    FROM kelas k
    JOIN mata_kuliah mk ON k.id_matkul = mk.id
    JOIN jurusan j ON k.id_jurusan = j.id
    WHERE k.id_dosen = ?
    ORDER BY k.tahun_ajaran DESC, k.semester DESC, mk.nama
";
$stmt_kelas = $koneksi->prepare($query_kelas);
$stmt_kelas->bind_param("i", $id_dosen_asli);
$stmt_kelas->execute();
$result_kelas = $stmt_kelas->get_result();

// Ambil semua sesi absensi yang dibuka oleh dosen
$query_sesi = "
    SELECT sa.*, k.nama as nama_kelas, mk.nama as nama_matkul
    FROM sesi_absensi sa
    JOIN kelas k ON sa.id_kelas = k.id
    JOIN mata_kuliah mk ON k.id_matkul = mk.id
    WHERE k.id_dosen = ?
    ORDER BY sa.tanggal DESC, sa.id DESC
";
$stmt_sesi = $koneksi->prepare($query_sesi);
$stmt_sesi->bind_param("i", $id_dosen_asli);
$stmt_sesi->execute();
$result_sesi = $stmt_sesi->get_result();
?>

<div class="dashboard-header">
    <h1>Kelola Absensi</h1>
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
        <h2>Buka Sesi Absensi Baru</h2>
    </div>
    <div class="card-body">
        <form method="POST" action="">
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="id_kelas">Kelas</label>
                        <select id="id_kelas" name="id_kelas" required>
                            <option value="">Pilih Kelas</option>
                            <?php
                            $result_kelas->data_seek(0); // Reset pointer
                            while ($kelas = $result_kelas->fetch_assoc()):
                            ?>
                                <option value="<?php echo $kelas['id']; ?>">
                                    <?php echo $kelas['nama'] . ' - ' . $kelas['nama_matkul'] . ' (' . $kelas['tahun_ajaran'] . ' - ' . $kelas['semester'] . ')'; ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="pertemuan">Pertemuan Ke</label>
                        <input type="number" id="pertemuan" name="pertemuan" min="1" max="16" required>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="tanggal">Tanggal</label>
                        <input type="date" id="tanggal" name="tanggal" required value="<?php echo date('Y-m-d'); ?>">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="durasi">Durasi (jam)</label>
                        <input type="number" id="durasi" name="durasi" min="1" max="24" value="2" required>
                        <small class="text-muted">Sesi akan otomatis ditutup setelah durasi ini</small>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="materi">Materi/Topik</label>
                        <input type="text" id="materi" name="materi" required>
                    </div>
                </div>
            </div>

            <div class="form-group">
                <button type="submit" name="buka_absensi" class="btn btn-primary">Buka Sesi Absensi</button>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h2>Daftar Sesi Absensi</h2>
    </div>
    <div class="card-body">
        <div class="form-group">
            <input type="text" id="filterInput" placeholder="Cari sesi absensi..." class="form-control">
        </div>

        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Tanggal</th>
                        <th>Kelas</th>
                        <th>Mata Kuliah</th>
                        <th>Pertemuan</th>
                        <th>Materi</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $no = 1;
                    if ($result_sesi->num_rows > 0) {
                        while ($row = $result_sesi->fetch_assoc()) {
                            echo "<tr>";
                            echo "<td>" . $no++ . "</td>";
                            echo "<td>" . format_tanggal_indonesia($row['tanggal']) . "</td>";
                            echo "<td>" . $row['nama_kelas'] . "</td>";
                            echo "<td>" . $row['nama_matkul'] . "</td>";
                            echo "<td>Pertemuan " . $row['pertemuan'] . "</td>";
                            echo "<td>" . $row['materi'] . "</td>";
                            echo "<td>";
                            if ($row['status'] == 'dibuka') {
                                echo '<span class="badge badge-success">Dibuka</span>';

                                // Tampilkan countdown jika ada waktu tutup
                                if (isset($row['waktu_tutup']) && $row['waktu_tutup'] > time()) {
                                    $remaining = strtotime($row['waktu_tutup']) - time();
                                    echo '<div class="countdown-container" data-auto-close data-close-time="' . $row['waktu_tutup'] . '" data-sesi-id="' . $row['id'] . '">';
                                    echo '<span class="countdown">' . gmdate("H:i:s", $remaining) . '</span>';
                                    echo '</div>';
                                }
                            } else {
                                echo '<span class="badge badge-secondary">Ditutup</span>';
                            }
                            echo "</td>";
                            echo "<td>";
                            echo "<a href='detail_absensi.php?id=" . $row['id'] . "' class='btn btn-sm btn-info mr-1'><i class='fas fa-eye'></i></a>";
                            if ($row['status'] == 'dibuka') {
                                echo "<a href='absensi.php?tutup=" . $row['id'] . "' class='btn btn-sm btn-warning'><i class='fas fa-lock'></i></a>";
                            }
                            echo "</td>";
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='8' class='text-center'>Belum ada sesi absensi</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once '../includes/footer_dosen.php'; ?>