<?php
$judul_halaman = "Kelas Saya";
require_once '../includes/header_mahasiswa.php';

// Ambil kelas yang diikuti oleh mahasiswa
$query_kelas = "
    SELECT k.*, mk.nama as nama_matkul, mk.sks, j.nama as nama_jurusan, d.nama as nama_dosen
    FROM kelas k
    JOIN kelas_mahasiswa km ON k.id = km.id_kelas
    JOIN mata_kuliah mk ON k.id_matkul = mk.id
    JOIN jurusan j ON k.id_jurusan = j.id
    JOIN dosen d ON k.id_dosen = d.id
    WHERE km.id_mahasiswa = ?
    ORDER BY k.tahun_ajaran DESC, k.semester DESC, mk.nama
";
$stmt_kelas = $koneksi->prepare($query_kelas);
$stmt_kelas->bind_param("i", $id_mahasiswa_asli);
$stmt_kelas->execute();
$result_kelas = $stmt_kelas->get_result();

// Filter berdasarkan tahun ajaran dan semester
$tahun_ajaran_filter = isset($_GET['tahun_ajaran']) ? bersihkan_input($_GET['tahun_ajaran']) : '';
$semester_filter = isset($_GET['semester']) ? bersihkan_input($_GET['semester']) : '';

// Ambil daftar tahun ajaran untuk filter
$query_tahun_ajaran = "
    SELECT DISTINCT k.tahun_ajaran 
    FROM kelas k
    JOIN kelas_mahasiswa km ON k.id = km.id_kelas
    WHERE km.id_mahasiswa = ? 
    ORDER BY k.tahun_ajaran DESC
";
$stmt_tahun_ajaran = $koneksi->prepare($query_tahun_ajaran);
$stmt_tahun_ajaran->bind_param("i", $id_mahasiswa_asli);
$stmt_tahun_ajaran->execute();
$result_tahun_ajaran = $stmt_tahun_ajaran->get_result();
?>

<div class="dashboard-header">
    <h1>Kelas Saya</h1>
</div>

<div class="card">
    <div class="card-header">
        <h2>Filter Kelas</h2>
    </div>
    <div class="card-body">
        <form method="GET" action="">
            <div class="row">
                <div class="col-md-5">
                    <div class="form-group">
                        <label for="tahun_ajaran">Tahun Ajaran</label>
                        <select id="tahun_ajaran" name="tahun_ajaran">
                            <option value="">Semua Tahun Ajaran</option>
                            <?php while ($tahun = $result_tahun_ajaran->fetch_assoc()): ?>
                                <option value="<?php echo $tahun['tahun_ajaran']; ?>" <?php echo ($tahun_ajaran_filter == $tahun['tahun_ajaran']) ? 'selected' : ''; ?>>
                                    <?php echo $tahun['tahun_ajaran']; ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>
                <div class="col-md-5">
                    <div class="form-group">
                        <label for="semester">Semester</label>
                        <select id="semester" name="semester">
                            <option value="">Semua Semester</option>
                            <option value="Ganjil" <?php echo ($semester_filter == 'Ganjil') ? 'selected' : ''; ?>>Ganjil</option>
                            <option value="Genap" <?php echo ($semester_filter == 'Genap') ? 'selected' : ''; ?>>Genap</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <label>&nbsp;</label>
                        <button type="submit" class="btn btn-primary btn-block">Filter</button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h2>Daftar Kelas</h2>
    </div>
    <div class="card-body">
        <div class="form-group">
            <input type="text" id="filterInput" placeholder="Cari kelas..." class="form-control">
        </div>
        
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Nama Kelas</th>
                        <th>Mata Kuliah</th>
                        <th>SKS</th>
                        <th>Dosen</th>
                        <th>Tahun Ajaran</th>
                        <th>Semester</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $no = 1;
                    $filtered_results = [];
                    
                    // Filter hasil berdasarkan parameter
                    if ($result_kelas->num_rows > 0) {
                        $result_kelas->data_seek(0); // Reset pointer
                        while ($row = $result_kelas->fetch_assoc()) {
                            $include = true;
                            
                            if (!empty($tahun_ajaran_filter) && $row['tahun_ajaran'] != $tahun_ajaran_filter) {
                                $include = false;
                            }
                            
                            if (!empty($semester_filter) && $row['semester'] != $semester_filter) {
                                $include = false;
                            }
                            
                            if ($include) {
                                $filtered_results[] = $row;
                            }
                        }
                    }
                    
                    if (count($filtered_results) > 0) {
                        foreach ($filtered_results as $row) {
                            echo "<tr>";
                            echo "<td>" . $no++ . "</td>";
                            echo "<td>" . $row['nama'] . "</td>";
                            echo "<td>" . $row['nama_matkul'] . "</td>";
                            echo "<td>" . $row['sks'] . "</td>";
                            echo "<td>" . $row['nama_dosen'] . "</td>";
                            echo "<td>" . $row['tahun_ajaran'] . "</td>";
                            echo "<td>" . $row['semester'] . "</td>";
                            echo "<td>
                                <a href='detail_kelas.php?id=" . $row['id'] . "' class='btn btn-sm btn-info'><i class='fas fa-eye'></i></a>
                            </td>";
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='8' class='text-center'>Tidak ada kelas yang ditemukan</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once '../includes/footer_mahasiswa.php'; ?>
