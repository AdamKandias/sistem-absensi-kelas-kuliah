<?php
$judul_halaman = "Kelola Jurusan";
require_once '../includes/header_admin.php';

// Proses tambah jurusan
if (isset($_POST['tambah'])) {
    $nama = bersihkan_input($_POST['nama']);
    $kode = bersihkan_input($_POST['kode']);
    
    // Cek apakah kode jurusan sudah ada
    $cek_kode = $koneksi->prepare("SELECT id FROM jurusan WHERE kode = ?");
    $cek_kode->bind_param("s", $kode);
    $cek_kode->execute();
    $hasil_cek = $cek_kode->get_result();
    
    if ($hasil_cek->num_rows > 0) {
        $pesan_error = "Kode jurusan sudah terdaftar!";
    } else {
        // Insert data jurusan
        $query = "INSERT INTO jurusan (nama, kode) VALUES (?, ?)";
        $stmt = $koneksi->prepare($query);
        $stmt->bind_param("ss", $nama, $kode);
        
        if ($stmt->execute()) {
            $pesan_sukses = "Data jurusan berhasil ditambahkan!";
        } else {
            $pesan_error = "Gagal menambahkan data jurusan: " . $koneksi->error;
        }
    }
}

// Proses edit jurusan
if (isset($_POST['edit'])) {
    $id = bersihkan_input($_POST['id']);
    $nama = bersihkan_input($_POST['nama']);
    $kode = bersihkan_input($_POST['kode']);
    
    // Cek apakah kode jurusan sudah ada (selain jurusan yang sedang diedit)
    $cek_kode = $koneksi->prepare("SELECT id FROM jurusan WHERE kode = ? AND id != ?");
    $cek_kode->bind_param("si", $kode, $id);
    $cek_kode->execute();
    $hasil_cek = $cek_kode->get_result();
    
    if ($hasil_cek->num_rows > 0) {
        $pesan_error = "Kode jurusan sudah terdaftar!";
    } else {
        // Update data jurusan
        $query = "UPDATE jurusan SET nama = ?, kode = ? WHERE id = ?";
        $stmt = $koneksi->prepare($query);
        $stmt->bind_param("ssi", $nama, $kode, $id);
        
        if ($stmt->execute()) {
            $pesan_sukses = "Data jurusan berhasil diperbarui!";
        } else {
            $pesan_error = "Gagal memperbarui data jurusan: " . $koneksi->error;
        }
    }
}

// Proses hapus jurusan
if (isset($_GET['hapus'])) {
    $id = bersihkan_input($_GET['hapus']);
    
    // Cek apakah jurusan masih digunakan
    $query_cek = "SELECT id FROM mahasiswa WHERE id_jurusan = ? UNION SELECT id FROM kelas WHERE id_jurusan = ?";
    $stmt_cek = $koneksi->prepare($query_cek);
    $stmt_cek->bind_param("ii", $id, $id);
    $stmt_cek->execute();
    $result_cek = $stmt_cek->get_result();
    
    if ($result_cek->num_rows > 0) {
        $pesan_error = "Jurusan tidak dapat dihapus karena masih digunakan!";
    } else {
        // Hapus data jurusan
        $query = "DELETE FROM jurusan WHERE id = ?";
        $stmt = $koneksi->prepare($query);
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            $pesan_sukses = "Data jurusan berhasil dihapus!";
        } else {
            $pesan_error = "Gagal menghapus data jurusan: " . $koneksi->error;
        }
    }
}

// Ambil data jurusan
$query_jurusan = "SELECT * FROM jurusan ORDER BY nama";
$result_jurusan = $koneksi->query($query_jurusan);
?>

<div class="dashboard-header">
    <h1>Kelola Data Jurusan</h1>
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
        <h2>Tambah Jurusan</h2>
    </div>
    <div class="card-body">
        <form method="POST" action="">
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="nama">Nama Jurusan</label>
                        <input type="text" id="nama" name="nama" required>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="kode">Kode Jurusan</label>
                        <input type="text" id="kode" name="kode" required>
                    </div>
                </div>
            </div>
            
            <div class="form-group">
                <button type="submit" name="tambah" class="btn btn-primary">Tambah Jurusan</button>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h2>Daftar Jurusan</h2>
    </div>
    <div class="card-body">
        <div class="form-group">
            <input type="text" id="filterInput" placeholder="Cari jurusan..." class="form-control">
        </div>
        
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Kode</th>
                        <th>Nama Jurusan</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $no = 1;
                    if ($result_jurusan->num_rows > 0) {
                        while ($row = $result_jurusan->fetch_assoc()) {
                            echo "<tr>";
                            echo "<td>" . $no++ . "</td>";
                            echo "<td>" . $row['kode'] . "</td>";
                            echo "<td>" . $row['nama'] . "</td>";
                            echo "<td>
                                <a href='#' class='btn btn-sm btn-info' onclick='editJurusan(" . $row['id'] . ", \"" . $row['nama'] . "\", \"" . $row['kode'] . "\")'><i class='fas fa-edit'></i></a>
                                <a href='jurusan.php?hapus=" . $row['id'] . "' class='btn btn-sm btn-danger btn-delete'><i class='fas fa-trash'></i></a>
                            </td>";
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='4' class='text-center'>Belum ada data jurusan</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Edit Jurusan -->
<div id="editModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Edit Jurusan</h2>
            <span class="close">&times;</span>
        </div>
        <div class="modal-body">
            <form method="POST" action="">
                <input type="hidden" id="edit_id" name="id">
                
                <div class="form-group">
                    <label for="edit_nama">Nama Jurusan</label>
                    <input type="text" id="edit_nama" name="nama" required>
                </div>
                
                <div class="form-group">
                    <label for="edit_kode">Kode Jurusan</label>
                    <input type="text" id="edit_kode" name="kode" required>
                </div>
                
                <div class="form-group">
                    <button type="submit" name="edit" class="btn btn-primary">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</div>

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
    
    function editJurusan(id, nama, kode) {
        document.getElementById('edit_id').value = id;
        document.getElementById('edit_nama').value = nama;
        document.getElementById('edit_kode').value = kode;
        
        modal.style.display = "block";
    }
</script>

<?php require_once '../includes/footer_admin.php'; ?>
