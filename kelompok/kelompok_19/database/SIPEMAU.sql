-- --------------------------------------------------------------------
-- SQL schema (run on MySQL 8.x)
-- --------------------------------------------------------------------

SET NAMES utf8mb4;
SET time_zone = '+00:00';

CREATE DATABASE IF NOT EXISTS sipemau_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE sipemau_db;

DROP TABLE IF EXISTS complaint_notes;
DROP TABLE IF EXISTS complaints;
DROP TABLE IF EXISTS categories;
DROP TABLE IF EXISTS petugas;
DROP TABLE IF EXISTS admin;
DROP TABLE IF EXISTS mahasiswa;
DROP TABLE IF EXISTS units;
DROP TABLE IF EXISTS users;

CREATE TABLE users (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(100) NOT NULL COMMENT 'Nama lengkap pengguna',
  email VARCHAR(100) NOT NULL COMMENT 'Email login unik',
  password_hash VARCHAR(255) NOT NULL COMMENT 'Hash password (bcrypt/argon2)',
  role ENUM('MAHASISWA','PETUGAS','ADMIN') NOT NULL COMMENT 'Peran akses',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Waktu pembuatan akun',
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Waktu pembaruan terakhir',
  PRIMARY KEY (id),
  UNIQUE KEY uq_users_email (email),
  INDEX idx_users_role (role),
  CHECK (role IN ('MAHASISWA','PETUGAS','ADMIN'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Master akun pengguna';

CREATE TABLE mahasiswa (
  id INT UNSIGNED NOT NULL COMMENT 'Share PK dengan users',
  nim VARCHAR(20) NOT NULL COMMENT 'Nomor Induk Mahasiswa',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Waktu pembuatan profil',
  PRIMARY KEY (id),
  UNIQUE KEY uq_mahasiswa_nim (nim),
  CONSTRAINT fk_mahasiswa_user FOREIGN KEY (id) REFERENCES users(id)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Profil khusus mahasiswa';

CREATE TABLE units (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(100) NOT NULL COMMENT 'Nama unit kampus',
  description TEXT NULL COMMENT 'Deskripsi unit',
  is_active TINYINT(1) NOT NULL DEFAULT 1 COMMENT 'Status aktif',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Waktu pembuatan data',
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Waktu pembaruan data',
  PRIMARY KEY (id),
  UNIQUE KEY uq_units_name (name),
  INDEX idx_units_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Master unit penerima laporan';

CREATE TABLE petugas (
  id INT UNSIGNED NOT NULL COMMENT 'Share PK dengan users',
  unit_id INT UNSIGNED NOT NULL COMMENT 'Unit tugas',
  jabatan VARCHAR(100) NULL COMMENT 'Jabatan petugas',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Waktu pembuatan profil',
  PRIMARY KEY (id),
  KEY idx_petugas_unit (unit_id),
  CONSTRAINT fk_petugas_user FOREIGN KEY (id) REFERENCES users(id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_petugas_unit FOREIGN KEY (unit_id) REFERENCES units(id)
    ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Profil petugas unit';

CREATE TABLE admin (
  id INT UNSIGNED NOT NULL COMMENT 'Share PK dengan users',
  level VARCHAR(50) NOT NULL DEFAULT 'superadmin' COMMENT 'Level admin',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Waktu pembuatan profil',
  PRIMARY KEY (id),
  CONSTRAINT fk_admin_user FOREIGN KEY (id) REFERENCES users(id)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Profil admin sistem';

CREATE TABLE categories (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  unit_id INT UNSIGNED NOT NULL COMMENT 'Unit penanggung jawab kategori',
  name VARCHAR(100) NOT NULL COMMENT 'Nama kategori laporan',
  description TEXT NULL COMMENT 'Deskripsi kategori',
  is_active TINYINT(1) NOT NULL DEFAULT 1 COMMENT 'Status aktif',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Waktu pembuatan data',
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Waktu pembaruan data',
  PRIMARY KEY (id),
  UNIQUE KEY uq_categories_name (name),
  KEY idx_categories_unit (unit_id),
  KEY idx_categories_active (is_active),
  CONSTRAINT fk_categories_unit FOREIGN KEY (unit_id) REFERENCES units(id)
    ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Kategori laporan mahasiswa';

CREATE TABLE complaints (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  mahasiswa_id INT UNSIGNED NOT NULL COMMENT 'Pelapor',
  category_id INT UNSIGNED NOT NULL COMMENT 'Kategori laporan',
  title VARCHAR(150) NOT NULL COMMENT 'Judul laporan',
  description TEXT NOT NULL COMMENT 'Isi laporan',
  evidence_path VARCHAR(255) NULL COMMENT 'Path bukti (opsional)',
  status ENUM('MENUNGGU','DIPROSES','SELESAI') NOT NULL DEFAULT 'MENUNGGU' COMMENT 'Status pemrosesan',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Waktu pengajuan',
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Waktu pembaruan status',
  resolved_at DATETIME NULL COMMENT 'Waktu selesai (jika ada)',
  PRIMARY KEY (id),
  KEY idx_complaints_mahasiswa (mahasiswa_id),
  KEY idx_complaints_category (category_id),
  KEY idx_complaints_status (status),
  CONSTRAINT fk_complaints_mahasiswa FOREIGN KEY (mahasiswa_id) REFERENCES mahasiswa(id)
    ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_complaints_category FOREIGN KEY (category_id) REFERENCES categories(id)
    ON DELETE RESTRICT ON UPDATE CASCADE,
  CHECK (status IN ('MENUNGGU','DIPROSES','SELESAI'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Transaksi pengaduan mahasiswa';

CREATE TABLE complaint_notes (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  complaint_id INT UNSIGNED NOT NULL COMMENT 'Referensi pengaduan',
  petugas_id INT UNSIGNED NOT NULL COMMENT 'Petugas penindak lanjut',
  note TEXT NOT NULL COMMENT 'Catatan tindak lanjut',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Waktu penambahan catatan',
  PRIMARY KEY (id),
  KEY idx_notes_complaint (complaint_id),
  KEY idx_notes_petugas (petugas_id),
  CONSTRAINT fk_notes_complaint FOREIGN KEY (complaint_id) REFERENCES complaints(id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_notes_petugas FOREIGN KEY (petugas_id) REFERENCES petugas(id)
    ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Catatan tindak lanjut oleh petugas';

-- End of schema
