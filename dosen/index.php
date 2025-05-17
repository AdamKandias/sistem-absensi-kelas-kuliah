<?php
$judul_halaman = "Dashboard Dosen";
require_once '../includes/header_dosen.php';

// Ambil kelas yang diajar oleh dosen
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

// Ambil sesi absensi terbaru
$query_sesi = "
    SELECT sa.*, k.nama as nama_kelas, mk.nama as nama_matkul
    FROM sesi_absensi sa
    JOIN kelas k ON sa.id_kelas = k.id
    JOIN mata_kuliah mk ON k.id_matkul = mk.id
    WHERE k.id_dosen = ?
    ORDER BY sa.tanggal DESC
    LIMIT 5
";
$stmt_sesi = $koneksi->prepare($query_sesi);
$stmt_sesi->bind_param("i", $id_dosen_asli);
$stmt_sesi->execute();
$result_sesi = $stmt_sesi->get_result();
?>

<div class="dashboard-header">
    <h1>Dashboard Dosen</h1>
    <p>Selamat datang, <?php echo $_SESSION['nama']; ?>!</p>
</div>

<div class="dashboard-stats">
    <div class="stat-card">
        <i class="fas fa-door-open fa-3x mb-3" style="color: var(--warna-sekunder);"></i>
        <h3><?php echo $result_kelas->num_rows; ?></h3>
        <p>Kelas Diampu</p>
    </div>
    <div class="stat-card">
        <i class="fas fa-clipboard-check fa-3x mb-3" style="color: var(--warna-sekunder);"></i>
        <?php
        // Hitung jumlah sesi absensi yang telah dibuka
        $query_jumlah_sesi = "
            SELECT COUNT(*) as total
            FROM sesi_absensi sa
            JOIN kelas k ON sa.id_kelas = k.id
            WHERE k.id_dosen = ?
        ";
        $stmt_jumlah_sesi = $koneksi->prepare($query_jumlah_sesi);
        $stmt_jumlah_sesi->bind_param("i", $id_dosen_asli);
        $stmt_jumlah_sesi->execute();
        $result_jumlah_sesi = $stmt_jumlah_sesi->get_result();
        $jumlah_sesi = $result_jumlah_sesi->fetch_assoc()['total'];
        ?>
        <h3><?php echo $jumlah_sesi; ?></h3>
        <p>Sesi Absensi</p>
    </div>
    <div class="stat-card">
        <i class="fas fa-user-graduate fa-3x mb-3" style="color: var(--warna-sekunder);"></i>
        <?php
        // Hitung jumlah mahasiswa yang terdaftar di kelas yang diampu
        $query_jumlah_mahasiswa = "
            SELECT COUNT(DISTINCT km.id_mahasiswa) as total
            FROM kelas_mahasiswa km
            JOIN kelas k ON km.id_kelas = k.id
            WHERE k.id_dosen = ?
        ";
        $stmt_jumlah_mahasiswa = $koneksi->prepare($query_jumlah_mahasiswa);
        $stmt_jumlah_mahasiswa->bind_param("i", $id_dosen_asli);
        $stmt_jumlah_mahasiswa->execute();
        $result_jumlah_mahasiswa = $stmt_jumlah_mahasiswa->get_result();
        $jumlah_mahasiswa = $result_jumlah_mahasiswa->fetch_assoc()['total'];
        ?>
        <h3><?php echo $jumlah_mahasiswa; ?></h3>
        <p>Total Mahasiswa</p>
    </div>
    <div class="stat-card">
        <i class="fas fa-calendar-alt fa-3x mb-3" style="color: var(--warna-sekunder);"></i>
        <?php
        // Hitung jumlah sesi absensi yang aktif saat ini
        $query_sesi_aktif = "
            SELECT COUNT(*) as total
            FROM sesi_absensi sa
            JOIN kelas k ON sa.id_kelas = k.id
            WHERE k.id_dosen = ? AND sa.status = 'dibuka'
        ";
        $stmt_sesi_aktif = $koneksi->prepare($query_sesi_aktif);
        $stmt_sesi_aktif->bind_param("i", $id_dosen_asli);
        $stmt_sesi_aktif->execute();
        $result_sesi_aktif = $stmt_sesi_aktif->get_result();
        $sesi_aktif = $result_sesi_aktif->fetch_assoc()['total'];
        ?>
        <h3><?php echo $sesi_aktif; ?></h3>
        <p>Sesi Aktif</p>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h2>Kelas Yang Diampu</h2>
            </div>
            <div class="card-body">
                <?php if ($result_kelas->num_rows > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Nama Kelas</th>
                                    <th>Mata Kuliah</th>
                                    <th>Jurusan</th>
                                    <th>Tahun Ajaran</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($kelas = $result_kelas->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo $kelas['nama']; ?></td>
                                        <td><?php echo $kelas['nama_matkul']; ?></td>
                                        <td><?php echo $kelas['nama_jurusan']; ?></td>
                                        <td><?php echo $kelas['tahun_ajaran'] . ' - ' . $kelas['semester']; ?></td>
                                        <td>
                                            <a href="detail_kelas.php?id=<?php echo $kelas['id']; ?>" class="btn btn-sm btn-info">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-center">Belum ada kelas yang diampu.</p>
                <?php endif; ?>
                <div class="mt-3">
                    <a href="kelas.php" class="btn btn-primary">Lihat Semua Kelas</a>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h2>Sesi Absensi Terbaru</h2>
            </div>
            <div class="card-body">
                <?php if ($result_sesi->num_rows > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Tanggal</th>
                                    <th>Kelas</th>
                                    <th>Pertemuan</th>
                                    <th>Status</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($sesi = $result_sesi->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo format_tanggal_indonesia($sesi['tanggal']); ?></td>
                                        <td><?php echo $sesi['nama_kelas'] . ' - ' . $sesi['nama_matkul']; ?></td>
                                        <td>Pertemuan <?php echo $sesi['pertemuan']; ?></td>
                                        <td>
                                            <?php if ($sesi['status'] == 'dibuka'): ?>
                                                <span class="badge badge-success">Dibuka</span>
                                            <?php else: ?>
                                                <span class="badge badge-secondary">Ditutup</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <a href="detail_absensi.php?id=<?php echo $sesi['id']; ?>" class="btn btn-sm btn-info">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-center">Belum ada sesi absensi yang dibuka.</p>
                <?php endif; ?>
                <div class="mt-3">
                    <a href="absensi.php" class="btn btn-primary">Kelola Absensi</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer_dosen.php'; ?>
