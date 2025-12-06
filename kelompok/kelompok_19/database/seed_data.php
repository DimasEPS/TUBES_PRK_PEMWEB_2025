<?php
/**
 * Seed Database with Sample Data
 * Run: php seed_data.php
 */

// Load .env helper
$envFile = __DIR__ . '/../backend/.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0 || strpos($line, '=') === false) {
            continue;
        }
        list($key, $value) = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value);
        if (preg_match('/^(["\'])(.*)\1$/', $value, $matches)) {
            $value = $matches[2];
        }
        if (!array_key_exists($key, $_ENV)) {
            $_ENV[$key] = $value;
            putenv("$key=$value");
        }
    }
}

function env($key, $default = null) {
    return $_ENV[$key] ?? getenv($key) ?: $default;
}

require_once __DIR__ . '/../backend/config/database.php';
require_once __DIR__ . '/../backend/core/Helper.php';

$db = Database::getInstance()->getConnection();

echo "=== SEEDING DATABASE ===\n\n";

try {
    $db->beginTransaction();
    
    // 1. Insert Admin
    echo "1. Creating admin account...\n";
    $stmt = $db->prepare("INSERT INTO users (name, email, password_hash, role) VALUES (?, ?, ?, 'ADMIN')");
    $stmt->execute(['Administrator', 'admin@sipemau.ac.id', hashPassword('password')]);
    $adminId = $db->lastInsertId();
    
    $stmt = $db->prepare("INSERT INTO admin (id, level) VALUES (?, 'superadmin')");
    $stmt->execute([$adminId]);
    echo "   ✓ Admin created (ID: $adminId)\n\n";
    
    // 2. Insert Units
    echo "2. Creating units...\n";
    $units = [
        ['Biro Akademik dan Kemahasiswaan', 'Menangani masalah akademik dan kemahasiswaan'],
        ['Biro Umum dan Keuangan', 'Menangani masalah administrasi umum dan keuangan'],
        ['Unit Teknologi Informasi', 'Menangani masalah sistem informasi dan IT'],
        ['Unit Perpustakaan', 'Menangani layanan perpustakaan'],
        ['Unit Kesehatan', 'Menangani layanan kesehatan mahasiswa']
    ];
    
    $unitIds = [];
    foreach ($units as $unit) {
        $stmt = $db->prepare("INSERT INTO units (name, description, is_active) VALUES (?, ?, 1)");
        $stmt->execute($unit);
        $unitIds[] = $db->lastInsertId();
        echo "   ✓ {$unit[0]}\n";
    }
    echo "\n";
    
    // 3. Insert Categories
    echo "3. Creating categories...\n";
    $categories = [
        [$unitIds[0], 'Masalah KRS', 'Kendala pengisian KRS, pembatalan mata kuliah'],
        [$unitIds[0], 'Masalah Nilai', 'Komplain nilai, keberatan nilai'],
        [$unitIds[0], 'Administrasi Akademik', 'Surat keterangan, legalisir, transkrip'],
        [$unitIds[1], 'Pembayaran UKT', 'Masalah pembayaran, cicilan, keringanan UKT'],
        [$unitIds[1], 'Fasilitas Kampus', 'Kerusakan fasilitas, kebersihan, parkir'],
        [$unitIds[2], 'SISTER/Portal', 'Kendala akses SISTER, portal mahasiswa'],
        [$unitIds[2], 'Email Kampus', 'Masalah email institusi'],
        [$unitIds[3], 'Layanan Perpustakaan', 'Peminjaman buku, akses digital'],
        [$unitIds[4], 'Layanan Kesehatan', 'Keluhan kesehatan, obat, rujukan']
    ];
    
    $categoryIds = [];
    foreach ($categories as $cat) {
        $stmt = $db->prepare("INSERT INTO categories (unit_id, name, description, is_active) VALUES (?, ?, ?, 1)");
        $stmt->execute($cat);
        $categoryIds[] = $db->lastInsertId();
        echo "   ✓ {$cat[1]}\n";
    }
    echo "\n";
    
    // 4. Insert Petugas
    echo "4. Creating petugas account...\n";
    $stmt = $db->prepare("INSERT INTO users (name, email, password_hash, role) VALUES (?, ?, ?, 'PETUGAS')");
    $stmt->execute(['Budi Santoso', 'budi@sipemau.ac.id', hashPassword('password')]);
    $petugasId = $db->lastInsertId();
    
    $stmt = $db->prepare("INSERT INTO petugas (id, unit_id, jabatan) VALUES (?, ?, 'Staff IT Support')");
    $stmt->execute([$petugasId, $unitIds[2]]); // Unit TI
    echo "   ✓ Petugas created (ID: $petugasId, Unit: TI)\n\n";
    
    // 5. Insert Mahasiswa
    echo "5. Creating mahasiswa accounts...\n";
    
    // John Doe
    $stmt = $db->prepare("INSERT INTO users (name, email, password_hash, role) VALUES (?, ?, ?, 'MAHASISWA')");
    $stmt->execute(['John Doe', 'john@student.unila.ac.id', hashPassword('password')]);
    $mahasiswaId1 = $db->lastInsertId();
    
    $stmt = $db->prepare("INSERT INTO mahasiswa (id, nim) VALUES (?, ?)");
    $stmt->execute([$mahasiswaId1, '2011521001']);
    echo "   ✓ John Doe (ID: $mahasiswaId1, NIM: 2011521001)\n";
    
    // Jane Smith
    $stmt = $db->prepare("INSERT INTO users (name, email, password_hash, role) VALUES (?, ?, ?, 'MAHASISWA')");
    $stmt->execute(['Jane Smith', 'jane@student.unila.ac.id', hashPassword('password')]);
    $mahasiswaId2 = $db->lastInsertId();
    
    $stmt = $db->prepare("INSERT INTO mahasiswa (id, nim) VALUES (?, ?)");
    $stmt->execute([$mahasiswaId2, '2011521002']);
    echo "   ✓ Jane Smith (ID: $mahasiswaId2, NIM: 2011521002)\n\n";
    
    // 6. Insert Sample Complaints
    echo "6. Creating sample complaints...\n";
    
    $complaints = [
        [
            $mahasiswaId1, 
            $categoryIds[5], // SISTER/Portal
            'Tidak bisa login ke SISTER', 
            'Saya sudah mencoba login berkali-kali namun selalu muncul error "Invalid credentials". Mohon bantuannya.',
            'MENUNGGU'
        ],
        [
            $mahasiswaId1,
            $categoryIds[0], // Masalah KRS
            'KRS tidak bisa disimpan',
            'Saat menyimpan KRS muncul error 500. Mohon segera ditangani karena deadline KRS sudah dekat.',
            'MENUNGGU'
        ],
        [
            $mahasiswaId2,
            $categoryIds[3], // Pembayaran UKT
            'Belum bisa cicilan UKT',
            'Saya ingin mengajukan cicilan UKT tapi sistem belum bisa diakses.',
            'MENUNGGU'
        ]
    ];
    
    foreach ($complaints as $complaint) {
        $stmt = $db->prepare("
            INSERT INTO complaints (mahasiswa_id, category_id, title, description, status) 
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute($complaint);
        echo "   ✓ {$complaint[2]}\n";
    }
    
    $db->commit();
    
    echo "\n=== SEEDING COMPLETED ===\n\n";
    echo "Default Accounts:\n";
    echo "┌─────────────┬──────────────────────────────┬──────────┐\n";
    echo "│ Role        │ Email                        │ Password │\n";
    echo "├─────────────┼──────────────────────────────┼──────────┤\n";
    echo "│ Admin       │ admin@sipemau.ac.id          │ password │\n";
    echo "│ Petugas     │ budi@sipemau.ac.id           │ password │\n";
    echo "│ Mahasiswa   │ john@student.unila.ac.id     │ password │\n";
    echo "│ Mahasiswa   │ jane@student.unila.ac.id     │ password │\n";
    echo "└─────────────┴──────────────────────────────┴──────────┘\n\n";
    
    // Show summary
    $stmt = $db->query("SELECT COUNT(*) as total FROM users");
    $totalUsers = $stmt->fetch()['total'];
    
    $stmt = $db->query("SELECT COUNT(*) as total FROM complaints");
    $totalComplaints = $stmt->fetch()['total'];
    
    $stmt = $db->query("SELECT COUNT(*) as total FROM units");
    $totalUnits = $stmt->fetch()['total'];
    
    $stmt = $db->query("SELECT COUNT(*) as total FROM categories");
    $totalCategories = $stmt->fetch()['total'];
    
    echo "Summary:\n";
    echo "  • Users: $totalUsers\n";
    echo "  • Units: $totalUnits\n";
    echo "  • Categories: $totalCategories\n";
    echo "  • Complaints: $totalComplaints\n";
    
} catch (Exception $e) {
    $db->rollBack();
    echo "\n❌ ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
