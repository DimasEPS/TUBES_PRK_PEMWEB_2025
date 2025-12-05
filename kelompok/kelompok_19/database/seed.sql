-- Insert default admin account
-- Password: password (harus diganti setelah login pertama)

INSERT INTO users (name, email, password_hash, role) 
VALUES ('Administrator', 'admin@sipemau.ac.id', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'ADMIN');

SET @admin_id = LAST_INSERT_ID();

INSERT INTO admin (id, level) 
VALUES (@admin_id, 'superadmin');

-- Insert sample units
INSERT INTO units (name, description, is_active) VALUES
('Biro Akademik dan Kemahasiswaan', 'Menangani masalah akademik dan kemahasiswaan', 1),
('Biro Umum dan Keuangan', 'Menangani masalah administrasi umum dan keuangan', 1),
('Unit Teknologi Informasi', 'Menangani masalah sistem informasi dan IT', 1),
('Unit Perpustakaan', 'Menangani layanan perpustakaan', 1),
('Unit Kesehatan', 'Menangani layanan kesehatan mahasiswa', 1);

-- Insert sample categories
INSERT INTO categories (unit_id, name, description, is_active) VALUES
(1, 'Masalah KRS', 'Kendala pengisian KRS, pembatalan mata kuliah, dll', 1),
(1, 'Masalah Nilai', 'Komplain nilai, keberatan nilai, dll', 1),
(1, 'Administrasi Akademik', 'Surat keterangan, legalisir, transkrip, dll', 1),
(2, 'Pembayaran UKT', 'Masalah pembayaran, cicilan, keringanan UKT', 1),
(2, 'Fasilitas Kampus', 'Kerusakan fasilitas, kebersihan, parkir, dll', 1),
(3, 'SISTER/Portal', 'Kendala akses SISTER, portal mahasiswa', 1),
(3, 'Email Kampus', 'Masalah email institusi', 1),
(4, 'Layanan Perpustakaan', 'Peminjaman buku, akses digital, dll', 1),
(5, 'Layanan Kesehatan', 'Keluhan kesehatan, obat, rujukan', 1);

-- Insert sample petugas for Unit TI
INSERT INTO users (name, email, password_hash, role) 
VALUES ('Budi Santoso', 'budi@sipemau.ac.id', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'PETUGAS');

SET @petugas_id = LAST_INSERT_ID();

INSERT INTO petugas (id, unit_id, jabatan) 
VALUES (@petugas_id, 3, 'Staff IT Support');

-- Insert sample mahasiswa
INSERT INTO users (name, email, password_hash, role) 
VALUES ('John Doe', 'john@student.unila.ac.id', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'MAHASISWA');

SET @mahasiswa_id = LAST_INSERT_ID();

INSERT INTO mahasiswa (id, nim) 
VALUES (@mahasiswa_id, '2011521001');

-- Insert sample complaint
INSERT INTO complaints (mahasiswa_id, category_id, title, description, status) 
VALUES 
(@mahasiswa_id, 6, 'Tidak bisa login ke SISTER', 'Saya sudah mencoba login berkali-kali namun selalu muncul error. Mohon bantuannya.', 'MENUNGGU'),
(@mahasiswa_id, 1, 'KRS tidak bisa disimpan', 'Saat menyimpan KRS muncul error 500. Mohon segera ditangani karena deadline KRS sudah dekat.', 'MENUNGGU');
