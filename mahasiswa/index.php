<?php
date_default_timezone_set('Asia/Jakarta');
$judul_halaman = "Dashboard Mahasiswa";
require_once '../includes/header_mahasiswa.php';

// Ambil data jurusan
$query_jurusan = "SELECT nama FROM jurusan WHERE id = ?";
$stmt_jurusan = $koneksi->prepare($query_jurusan);
$stmt_jurusan->bind_param("i", $mahasiswa['id_jurusan']);
$stmt_jurusan->execute();
$result_jurusan = $stmt_jurusan->get_result();
$jurusan = $result_jurusan->fetch_assoc();

// Periksa dan tutup sesi yang sudah melewati waktu tutup
periksa_sesi_kadaluarsa($koneksi);

// Ambil kelas yang diikuti oleh mahasiswa
$query_kelas = "
    SELECT k.*, mk.nama as nama_matkul, d.nama as nama_dosen
    FROM kelas k
    JOIN kelas_mahasiswa km ON k.id = km.id_kelas
    JOIN mata_kuliah mk ON k.id_matkul = mk.id
    JOIN dosen d ON k.id_dosen = d.id
    WHERE km.id_mahasiswa = ?
    ORDER BY k.tahun_ajaran DESC, k.semester DESC, mk.nama
";
$stmt_kelas = $koneksi->prepare($query_kelas);
$stmt_kelas->bind_param("i", $id_mahasiswa_asli);
$stmt_kelas->execute();
$result_kelas = $stmt_kelas->get_result();

// Ambil sesi absensi yang aktif
$query_sesi_aktif = "
    SELECT sa.id, sa.pertemuan, sa.tanggal, sa.materi, k.nama as nama_kelas, mk.nama as nama_matkul, d.nama as nama_dosen
    FROM sesi_absensi sa
    JOIN kelas k ON sa.id_kelas = k.id
    JOIN mata_kuliah mk ON k.id_matkul = mk.id
    JOIN dosen d ON k.id_dosen = d.id
    JOIN kelas_mahasiswa km ON k.id = km.id_kelas
    WHERE km.id_mahasiswa = ? AND sa.status = 'dibuka'
    ORDER BY sa.tanggal DESC
";
$stmt_sesi_aktif = $koneksi->prepare($query_sesi_aktif);
$stmt_sesi_aktif->bind_param("i", $id_mahasiswa_asli);
$stmt_sesi_aktif->execute();
$result_sesi_aktif = $stmt_sesi_aktif->get_result();

// Ambil riwayat absensi terbaru
$query_riwayat = "
    SELECT a.id, a.tanggal, a.status, sa.pertemuan, k.nama as nama_kelas, mk.nama as nama_matkul
    FROM absensi a
    JOIN sesi_absensi sa ON a.id_sesi = sa.id
    JOIN kelas k ON sa.id_kelas = k.id
    JOIN mata_kuliah mk ON k.id_matkul = mk.id
    WHERE a.id_mahasiswa = ?
    ORDER BY a.tanggal DESC
    LIMIT 5
";
$stmt_riwayat = $koneksi->prepare($query_riwayat);
$stmt_riwayat->bind_param("i", $id_mahasiswa_asli);
$stmt_riwayat->execute();
$result_riwayat = $stmt_riwayat->get_result();

// Proses absensi
if (isset($_POST['absen'])) {
    $id_sesi = bersihkan_input($_POST['id_sesi']);
    $status = bersihkan_input($_POST['status']);
    $keterangan = bersihkan_input($_POST['keterangan']);

    // Cek apakah sudah absen
    $query_cek = "SELECT id FROM absensi WHERE id_mahasiswa = ? AND id_sesi = ?";
    $stmt_cek = $koneksi->prepare($query_cek);
    $stmt_cek->bind_param("ii", $id_mahasiswa_asli, $id_sesi);
    $stmt_cek->execute();
    $result_cek = $stmt_cek->get_result();

    if ($result_cek->num_rows > 0) {
        $pesan_error = "Anda sudah melakukan absensi untuk sesi ini!";
    } else {
        // Cek apakah sesi masih dibuka
        $query_cek_sesi = "SELECT id FROM sesi_absensi WHERE id = ? AND status = 'dibuka'";
        $stmt_cek_sesi = $koneksi->prepare($query_cek_sesi);
        $stmt_cek_sesi->bind_param("i", $id_sesi);
        $stmt_cek_sesi->execute();
        $result_cek_sesi = $stmt_cek_sesi->get_result();

        if ($result_cek_sesi->num_rows == 0) {
            $pesan_error = "Sesi absensi sudah ditutup!";
        } else {
            // Simpan absensi
            $tanggal = date('Y-m-d');
            $query = "INSERT INTO absensi (id_mahasiswa, id_sesi, tanggal, status, keterangan) VALUES (?, ?, ?, ?, ?)";
            $stmt = $koneksi->prepare($query);
            $stmt->bind_param("iisss", $id_mahasiswa_asli, $id_sesi, $tanggal, $status, $keterangan);

            if ($stmt->execute()) {
                $pesan_sukses = "Absensi berhasil disimpan!";

                // Refresh data sesi aktif
                $stmt_sesi_aktif->execute();
                $result_sesi_aktif = $stmt_sesi_aktif->get_result();

                // Refresh riwayat absensi
                $stmt_riwayat->execute();
                $result_riwayat = $stmt_riwayat->get_result();
            } else {
                $pesan_error = "Gagal menyimpan absensi: " . $koneksi->error;
            }
        }
    }
}
?>

<div class="dashboard-header">
    <h1>Dashboard Mahasiswa</h1>
    <p>Selamat datang, <?php echo $_SESSION['nama']; ?>!</p>
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
                <h2>Profil Mahasiswa</h2>
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
                <div class="mt-3">
                    <a href="profil.php" class="btn btn-primary btn-block">Edit Profil</a>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h2>Sesi Absensi Aktif</h2>
            </div>
            <div class="card-body">
                <?php if ($result_sesi_aktif->num_rows > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Tanggal</th>
                                    <th>Kelas</th>
                                    <th>Mata Kuliah</th>
                                    <th>Pertemuan</th>
                                    <th>Dosen</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($sesi = $result_sesi_aktif->fetch_assoc()): ?>
                                    <?php
                                    // Cek apakah sudah absen
                                    $query_cek_absen = "SELECT id FROM absensi WHERE id_mahasiswa = ? AND id_sesi = ?";
                                    $stmt_cek_absen = $koneksi->prepare($query_cek_absen);
                                    $stmt_cek_absen->bind_param("ii", $id_mahasiswa_asli, $sesi['id']);
                                    $stmt_cek_absen->execute();
                                    $result_cek_absen = $stmt_cek_absen->get_result();
                                    $sudah_absen = $result_cek_absen->num_rows > 0;
                                    ?>
                                    <tr>
                                        <td><?php echo format_tanggal_indonesia($sesi['tanggal']); ?></td>
                                        <td><?php echo $sesi['nama_kelas']; ?></td>
                                        <td><?php echo $sesi['nama_matkul']; ?></td>
                                        <td>Pertemuan <?php echo $sesi['pertemuan']; ?></td>
                                        <td><?php echo $sesi['nama_dosen']; ?></td>
                                        <td>
                                            <?php if ($sudah_absen): ?>
                                                <span class="badge badge-success">Sudah Absen</span>
                                            <?php else: ?>
                                                <a href="#" class="btn btn-sm btn-primary" onclick="bukaFormAbsen(<?php echo $sesi['id']; ?>, '<?php echo $sesi['nama_matkul']; ?>', <?php echo $sesi['pertemuan']; ?>)">
                                                    Absen Sekarang
                                                </a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-center">Tidak ada sesi absensi yang aktif saat ini.</p>
                <?php endif; ?>
            </div>
        </div>

        <div class="card mt-4">
            <div class="card-header">
                <h2>Riwayat Absensi Terbaru</h2>
            </div>
            <div class="card-body">
                <?php if ($result_riwayat->num_rows > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Tanggal</th>
                                    <th>Kelas</th>
                                    <th>Mata Kuliah</th>
                                    <th>Pertemuan</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($riwayat = $result_riwayat->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo format_tanggal_indonesia($riwayat['tanggal']); ?></td>
                                        <td><?php echo $riwayat['nama_kelas']; ?></td>
                                        <td><?php echo $riwayat['nama_matkul']; ?></td>
                                        <td>Pertemuan <?php echo $riwayat['pertemuan']; ?></td>
                                        <td><?php echo status_kehadiran($riwayat['status']); ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-3">
                        <a href="kehadiran.php" class="btn btn-primary">Lihat Semua Riwayat</a>
                    </div>
                <?php else: ?>
                    <p class="text-center">Belum ada riwayat absensi.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="card mt-4">
    <div class="card-header">
        <h2>Kelas Yang Diikuti</h2>
    </div>
    <div class="card-body">
        <?php if ($result_kelas->num_rows > 0): ?>
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Nama Kelas</th>
                            <th>Mata Kuliah</th>
                            <th>Dosen</th>
                            <th>Tahun Ajaran</th>
                            <th>Semester</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($kelas = $result_kelas->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $kelas['nama']; ?></td>
                                <td><?php echo $kelas['nama_matkul']; ?></td>
                                <td><?php echo $kelas['nama_dosen']; ?></td>
                                <td><?php echo $kelas['tahun_ajaran']; ?></td>
                                <td><?php echo $kelas['semester']; ?></td>
                                <td>
                                    <a href="detail_kelas.php?id=<?php echo $kelas['id']; ?>" class="btn btn-sm btn-info">
                                        <i class="fas fa-eye"></i> Detail
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p class="text-center">Anda belum terdaftar di kelas manapun.</p>
        <?php endif; ?>
    </div>
</div>

<!-- Modal Absensi -->
<div id="absensiModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Form Absensi <span id="matkul_info"></span></h2>
            <span class="close">&times;</span>
        </div>
        <div class="modal-body">
            <form method="POST" action="">
                <input type="hidden" id="id_sesi" name="id_sesi">

                <div class="form-group">
                    <label for="status">Status Kehadiran</label>
                    <select id="status" name="status" required>
                        <option value="hadir">Hadir</option>
                        <option value="izin">Izin</option>
                        <option value="sakit">Sakit</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="keterangan">Keterangan (opsional)</label>
                    <textarea id="keterangan" name="keterangan" rows="3"></textarea>
                </div>

                <div class="form-group">
                    <button type="submit" name="absen" class="btn btn-primary">Kirim Absensi</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // Modal Absensi
    const modal = document.getElementById('absensiModal');
    const closeBtn = document.getElementsByClassName('close')[0];

    closeBtn.onclick = function() {
        modal.style.display = "none";
    }

    window.onclick = function(event) {
        if (event.target == modal) {
            modal.style.display = "none";
        }
    }

    function bukaFormAbsen(id_sesi, nama_matkul, pertemuan) {
        document.getElementById('id_sesi').value = id_sesi;
        document.getElementById('matkul_info').textContent = nama_matkul + ' - Pertemuan ' + pertemuan;

        modal.style.display = "block";
    }
</script>

<?php require_once '../includes/footer_mahasiswa.php'; ?>