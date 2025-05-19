-- Membuat database
CREATE DATABASE IF NOT EXISTS db_monitoring_kehadiran;
USE db_monitoring_kehadiran;

-- Tabel jurusan
CREATE TABLE jurusan (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(100) NOT NULL,
    kode VARCHAR(20) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Tabel dosen
CREATE TABLE dosen (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nip VARCHAR(50) NOT NULL,
    nama VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    no_telp VARCHAR(20) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Tabel mahasiswa
CREATE TABLE mahasiswa (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nim VARCHAR(20) NOT NULL,
    nama VARCHAR(100) NOT NULL,
    id_jurusan INT NOT NULL,
    angkatan INT NOT NULL,
    kelas_angkatan VARCHAR(10) NOT NULL,
    email VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (id_jurusan) REFERENCES jurusan(id) ON DELETE CASCADE
);

-- Tabel mata kuliah
CREATE TABLE mata_kuliah (
    id INT AUTO_INCREMENT PRIMARY KEY,
    kode VARCHAR(20) NOT NULL,
    nama VARCHAR(100) NOT NULL,
    sks INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Tabel kelas
CREATE TABLE kelas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(100) NOT NULL,
    id_jurusan INT NOT NULL,
    id_matkul INT NOT NULL,
    id_dosen INT NOT NULL,
    tahun_ajaran VARCHAR(9) NOT NULL,
    semester ENUM('Ganjil', 'Genap') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (id_jurusan) REFERENCES jurusan(id) ON DELETE CASCADE,
    FOREIGN KEY (id_matkul) REFERENCES mata_kuliah(id) ON DELETE CASCADE,
    FOREIGN KEY (id_dosen) REFERENCES dosen(id) ON DELETE CASCADE
);

-- Tabel kelas_mahasiswa (relasi many-to-many antara kelas dan mahasiswa)
CREATE TABLE kelas_mahasiswa (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_kelas INT NOT NULL,
    id_mahasiswa INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_kelas) REFERENCES kelas(id) ON DELETE CASCADE,
    FOREIGN KEY (id_mahasiswa) REFERENCES mahasiswa(id) ON DELETE CASCADE
);

-- Tabel sesi absensi
CREATE TABLE sesi_absensi (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_kelas INT NOT NULL,
    pertemuan INT NOT NULL,
    tanggal DATE NOT NULL,
    materi TEXT NOT NULL,
    status ENUM('dibuka', 'ditutup') NOT NULL DEFAULT 'dibuka',
    waktu_tutup DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (id_kelas) REFERENCES kelas(id) ON DELETE CASCADE
);

-- Tabel absensi
CREATE TABLE absensi (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_mahasiswa INT NOT NULL,
    id_sesi INT NOT NULL,
    tanggal DATE NOT NULL,
    status ENUM('hadir', 'izin', 'sakit', 'alpa') NOT NULL,
    keterangan TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (id_mahasiswa) REFERENCES mahasiswa(id) ON DELETE CASCADE,
    FOREIGN KEY (id_sesi) REFERENCES sesi_absensi(id) ON DELETE CASCADE
);

-- Tabel pengguna
CREATE TABLE pengguna (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL,
    password VARCHAR(255) NOT NULL,
    nama VARCHAR(100) NOT NULL,
    peran ENUM('admin', 'dosen', 'mahasiswa') NOT NULL,
    id_referensi INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Indeks untuk meningkatkan performa query
CREATE INDEX idx_absensi_mahasiswa ON absensi(id_mahasiswa);
CREATE INDEX idx_absensi_sesi ON absensi(id_sesi);
CREATE INDEX idx_sesi_kelas ON sesi_absensi(id_kelas);
CREATE INDEX idx_kelas_mahasiswa_kelas ON kelas_mahasiswa(id_kelas);
CREATE INDEX idx_kelas_mahasiswa_mahasiswa ON kelas_mahasiswa(id_mahasiswa);

-- Data awal untuk jurusan
INSERT INTO jurusan (nama, kode) VALUES
('Teknik Informatika', 'TI'),
('Sistem Informasi', 'SI'),
('Teknik Komputer', 'TK'),
('Teknik Elektro', 'TE');

-- Data awal untuk mata kuliah
INSERT INTO mata_kuliah (kode, nama, sks) VALUES
('IF001', 'Pemrograman Web', 3),
('IF002', 'Basis Data', 3),
('IF003', 'Algoritma dan Pemrograman', 4),
('IF004', 'Jaringan Komputer', 3),
('SI001', 'Analisis Sistem Informasi', 3),
('SI002', 'Manajemen Basis Data', 3);

-- Data awal untuk dosen
INSERT INTO dosen (nip, nama, email, no_telp) VALUES
('198501012010011001', 'Dr. Budi Santoso', 'budi.santoso@example.com', '081234567890'),
('198601022010012002', 'Dr. Siti Rahayu', 'siti.rahayu@example.com', '081234567891'),
('198702032010013003', 'Dr. Ahmad Hidayat', 'ahmad.hidayat@example.com', '081234567892');

-- Data awal untuk mahasiswa
INSERT INTO mahasiswa (nim, nama, id_jurusan, angkatan, kelas_angkatan, email) VALUES
('2020001', 'Andi Wijaya', 1, 2020, 'A', 'andi.wijaya@example.com'),
('2020002', 'Dewi Lestari', 1, 2020, 'A', 'dewi.lestari@example.com'),
('2020003', 'Eko Prasetyo', 1, 2020, 'B', 'eko.prasetyo@example.com'),
('2020004', 'Fitri Handayani', 2, 2020, 'A', 'fitri.handayani@example.com'),
('2020005', 'Galih Pratama', 2, 2020, 'A', 'galih.pratama@example.com'),
('2021001', 'Hadi Nugroho', 1, 2021, 'A', 'hadi.nugroho@example.com'),
('2021002', 'Indah Permata', 1, 2021, 'A', 'indah.permata@example.com'),
('2021003', 'Joko Susilo', 1, 2021, 'B', 'joko.susilo@example.com'),
('2021004', 'Kartika Sari', 2, 2021, 'A', 'kartika.sari@example.com'),
('2021005', 'Lukman Hakim', 2, 2021, 'A', 'lukman.hakim@example.com');

-- Data awal untuk kelas
INSERT INTO kelas (nama, id_jurusan, id_matkul, id_dosen, tahun_ajaran, semester) VALUES
('TI-A-2020', 1, 1, 1, '2023/2024', 'Ganjil'),
('TI-B-2020', 1, 1, 1, '2023/2024', 'Ganjil'),
('SI-A-2020', 2, 5, 2, '2023/2024', 'Ganjil'),
('TI-A-2021', 1, 3, 3, '2023/2024', 'Ganjil'),
('SI-A-2021', 2, 6, 2, '2023/2024', 'Ganjil');

-- Data awal untuk kelas_mahasiswa
INSERT INTO kelas_mahasiswa (id_kelas, id_mahasiswa) VALUES
(1, 1), (1, 2), (2, 3), (3, 4), (3, 5),
(4, 6), (4, 7), (4, 8), (5, 9), (5, 10);

-- Data awal untuk sesi absensi
INSERT INTO sesi_absensi (id_kelas, pertemuan, tanggal, materi, status) VALUES
(1, 1, '2023-09-05', 'Pengenalan HTML dan CSS', 'ditutup'),
(1, 2, '2023-09-12', 'JavaScript Dasar', 'ditutup'),
(1, 3, '2023-09-19', 'DOM Manipulation', 'dibuka'),
(2, 1, '2023-09-06', 'Pengenalan HTML dan CSS', 'ditutup'),
(2, 2, '2023-09-13', 'JavaScript Dasar', 'dibuka'),
(3, 1, '2023-09-07', 'Pengenalan Analisis Sistem', 'ditutup'),
(3, 2, '2023-09-14', 'Metode Pengumpulan Data', 'dibuka'),
(4, 1, '2023-09-08', 'Pengenalan Algoritma', 'ditutup'),
(4, 2, '2023-09-15', 'Struktur Data Dasar', 'dibuka'),
(5, 1, '2023-09-09', 'Pengenalan Database Management', 'ditutup');

-- Data awal untuk absensi
INSERT INTO absensi (id_mahasiswa, id_sesi, tanggal, status, keterangan) VALUES
(1, 1, '2023-09-05', 'hadir', NULL),
(2, 1, '2023-09-05', 'hadir', NULL),
(1, 2, '2023-09-12', 'hadir', NULL),
(2, 2, '2023-09-12', 'izin', 'Ada keperluan keluarga'),
(3, 4, '2023-09-06', 'hadir', NULL),
(3, 5, '2023-09-13', 'hadir', NULL),
(4, 6, '2023-09-07', 'hadir', NULL),
(5, 6, '2023-09-07', 'sakit', 'Demam'),
(6, 8, '2023-09-08', 'hadir', NULL),
(7, 8, '2023-09-08', 'hadir', NULL),
(8, 8, '2023-09-08', 'alpa', NULL),
(9, 10, '2023-09-09', 'hadir', NULL),
(10, 10, '2023-09-09', 'hadir', NULL);

-- Data awal untuk pengguna
-- Password default: username (sudah di-hash)
INSERT INTO pengguna (username, password, nama, peran, id_referensi) VALUES
('admin', '$2y$10$/FsHJ/h/dRwIiyo/JVvvU.Uy8xfwwzk0P0Fz6QyoepVHWPFB3U5H6', 'Administrator', 'admin', 0),
('198501012010011001', '$2y$10$CcOo86uXV0PQZfzGKqNjte7eHHGXRsxYyea0Ap5xNdPVhucaZm/D2', 'Dr. Budi Santoso', 'dosen', 1),
('198601022010012002', '$2y$10$CcOo86uXV0PQZfzGKqNjte7eHHGXRsxYyea0Ap5xNdPVhucaZm/D2', 'Dr. Siti Rahayu', 'dosen', 2),
('198702032010013003', '$2y$10$CcOo86uXV0PQZfzGKqNjte7eHHGXRsxYyea0Ap5xNdPVhucaZm/D2', 'Dr. Ahmad Hidayat', 'dosen', 3),
('2020001', '$2y$10$tbf7iYT53oF81iQl8tS6ge41Ajo7oepFPH6DVivbGozcpy26tiFnG', 'Andi Wijaya', 'mahasiswa', 1),
('2020002', '$2y$10$tbf7iYT53oF81iQl8tS6ge41Ajo7oepFPH6DVivbGozcpy26tiFnG', 'Dewi Lestari', 'mahasiswa', 2),
('2020003', '$2y$10$tbf7iYT53oF81iQl8tS6ge41Ajo7oepFPH6DVivbGozcpy26tiFnG', 'Eko Prasetyo', 'mahasiswa', 3),
('2020004', '$2y$10$tbf7iYT53oF81iQl8tS6ge41Ajo7oepFPH6DVivbGozcpy26tiFnG', 'Fitri Handayani', 'mahasiswa', 4),
('2020005', '$2y$10$tbf7iYT53oF81iQl8tS6ge41Ajo7oepFPH6DVivbGozcpy26tiFnG', 'Galih Pratama', 'mahasiswa', 5),
('2021001', '$2y$10$tbf7iYT53oF81iQl8tS6ge41Ajo7oepFPH6DVivbGozcpy26tiFnG', 'Hadi Nugroho', 'mahasiswa', 6),
('2021002', '$2y$10$tbf7iYT53oF81iQl8tS6ge41Ajo7oepFPH6DVivbGozcpy26tiFnG', 'Indah Permata', 'mahasiswa', 7),
('2021003', '$2y$10$tbf7iYT53oF81iQl8tS6ge41Ajo7oepFPH6DVivbGozcpy26tiFnG', 'Joko Susilo', 'mahasiswa', 8),
('2021004', '$2y$10$tbf7iYT53oF81iQl8tS6ge41Ajo7oepFPH6DVivbGozcpy26tiFnG', 'Kartika Sari', 'mahasiswa', 9),
('2021005', '$2y$10$tbf7iYT53oF81iQl8tS6ge41Ajo7oepFPH6DVivbGozcpy26tiFnG', 'Lukman Hakim', 'mahasiswa', 10);
