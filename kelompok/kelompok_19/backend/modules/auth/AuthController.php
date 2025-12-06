<?php
/**
 * Auth Controller
 * Handle authentication dan registrasi
 */

class AuthController extends Controller {
    
    /**
     * Process login
     */
    public function login() {
        $email = sanitize($this->post('email'));
        $password = $this->post('password');
        
        if (empty($email) || empty($password)) {
            return $this->json([
                'success' => false,
                'message' => 'Email dan password harus diisi'
            ], 400);
        }
        
        // Get user
        $stmt = $this->db->prepare("
            SELECT id, name, email, password_hash, role 
            FROM users 
            WHERE email = ?
        ");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if (!$user || !verifyPassword($password, $user['password_hash'])) {
            return $this->json([
                'success' => false,
                'message' => 'Email atau password salah'
            ], 401);
        }
        
        // Set session
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['name'] = $user['name'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['role'] = $user['role'];
        
        $userData = [
            'id' => $user['id'],
            'name' => $user['name'],
            'email' => $user['email'],
            'role' => $user['role']
        ];
        
        // Get additional info based on role
        if ($user['role'] === 'MAHASISWA') {
            $stmt = $this->db->prepare("SELECT nim FROM mahasiswa WHERE id = ?");
            $stmt->execute([$user['id']]);
            $mahasiswa = $stmt->fetch();
            $_SESSION['nim'] = $mahasiswa['nim'];
            $userData['nim'] = $mahasiswa['nim'];
        } elseif ($user['role'] === 'PETUGAS') {
            $stmt = $this->db->prepare("SELECT unit_id, jabatan FROM petugas WHERE id = ?");
            $stmt->execute([$user['id']]);
            $petugas = $stmt->fetch();
            $_SESSION['unit_id'] = $petugas['unit_id'];
            $_SESSION['jabatan'] = $petugas['jabatan'];
            $userData['unit_id'] = $petugas['unit_id'];
            $userData['jabatan'] = $petugas['jabatan'];
        }
        
        return $this->json([
            'success' => true,
            'message' => 'Login berhasil',
            'data' => $userData
        ]);
    }
    
    /**
     * Process registration
     */
    public function register() {
        $name = sanitize($this->post('name'));
        $nim = sanitize($this->post('nim'));
        $email = sanitize($this->post('email'));
        $password = $this->post('password');
        $confirmPassword = $this->post('confirm_password');
        
        // Validation
        $errors = [];
        
        if (empty($name)) $errors[] = 'Nama harus diisi';
        if (empty($nim)) $errors[] = 'NIM harus diisi';
        if (empty($email) || !validateEmail($email)) $errors[] = 'Email tidak valid';
        if (strlen($password) < PASSWORD_MIN_LENGTH) {
            $errors[] = 'Password minimal ' . PASSWORD_MIN_LENGTH . ' karakter';
        }
        if ($password !== $confirmPassword) $errors[] = 'Konfirmasi password tidak cocok';
        
        if (!empty($errors)) {
            return $this->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $errors
            ], 400);
        }
        
        // Check if email exists
        $stmt = $this->db->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            return $this->json([
                'success' => false,
                'message' => 'Email sudah terdaftar'
            ], 409);
        }
        
        // Check if NIM exists
        $stmt = $this->db->prepare("SELECT id FROM mahasiswa WHERE nim = ?");
        $stmt->execute([$nim]);
        if ($stmt->fetch()) {
            return $this->json([
                'success' => false,
                'message' => 'NIM sudah terdaftar'
            ], 409);
        }
        
        try {
            $this->db->beginTransaction();
            
            // Insert user
            $stmt = $this->db->prepare("
                INSERT INTO users (name, email, password_hash, role) 
                VALUES (?, ?, ?, 'MAHASISWA')
            ");
            $stmt->execute([$name, $email, hashPassword($password)]);
            $userId = $this->db->lastInsertId();
            
            // Insert mahasiswa
            $stmt = $this->db->prepare("
                INSERT INTO mahasiswa (id, nim) 
                VALUES (?, ?)
            ");
            $stmt->execute([$userId, $nim]);
            
            $this->db->commit();
            
            return $this->json([
                'success' => true,
                'message' => 'Registrasi berhasil',
                'data' => [
                    'id' => $userId,
                    'name' => $name,
                    'email' => $email,
                    'nim' => $nim
                ]
            ], 201);
            
        } catch (Exception $e) {
            $this->db->rollBack();
            return $this->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Logout
     */
    public function logout() {
        session_destroy();
        return $this->json([
            'success' => true,
            'message' => 'Logout berhasil'
        ]);
    }
}
