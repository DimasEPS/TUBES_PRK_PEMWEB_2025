<?php
/**
 * Auth Controller
 * Handle authentication dan registrasi
 */

class AuthController extends Controller {
    
    /**
     * Show login page
     */
    public function showLogin() {
        if (isset($_SESSION['user_id'])) {
            $this->redirectByRole();
        }
        $this->view('auth/login');
    }
    
    /**
     * Process login
     */
    public function login() {
        $email = sanitize($this->post('email'));
        $password = $this->post('password');
        
        if (empty($email) || empty($password)) {
            $_SESSION['error'] = 'Email dan password harus diisi';
            $this->redirect('/login');
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
            $_SESSION['error'] = 'Email atau password salah';
            $this->redirect('/login');
        }
        
        // Set session
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['name'] = $user['name'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['role'] = $user['role'];
        
        // Get additional info based on role
        if ($user['role'] === 'MAHASISWA') {
            $stmt = $this->db->prepare("SELECT nim FROM mahasiswa WHERE id = ?");
            $stmt->execute([$user['id']]);
            $mahasiswa = $stmt->fetch();
            $_SESSION['nim'] = $mahasiswa['nim'];
        } elseif ($user['role'] === 'PETUGAS') {
            $stmt = $this->db->prepare("SELECT unit_id, jabatan FROM petugas WHERE id = ?");
            $stmt->execute([$user['id']]);
            $petugas = $stmt->fetch();
            $_SESSION['unit_id'] = $petugas['unit_id'];
            $_SESSION['jabatan'] = $petugas['jabatan'];
        }
        
        $_SESSION['success'] = 'Login berhasil!';
        $this->redirectByRole();
    }
    
    /**
     * Show register page (mahasiswa only)
     */
    public function showRegister() {
        if (isset($_SESSION['user_id'])) {
            $this->redirectByRole();
        }
        $this->view('auth/register');
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
            $_SESSION['error'] = implode('<br>', $errors);
            $this->redirect('/register');
        }
        
        // Check if email exists
        $stmt = $this->db->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $_SESSION['error'] = 'Email sudah terdaftar';
            $this->redirect('/register');
        }
        
        // Check if NIM exists
        $stmt = $this->db->prepare("SELECT id FROM mahasiswa WHERE nim = ?");
        $stmt->execute([$nim]);
        if ($stmt->fetch()) {
            $_SESSION['error'] = 'NIM sudah terdaftar';
            $this->redirect('/register');
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
            
            $_SESSION['success'] = 'Registrasi berhasil! Silakan login';
            $this->redirect('/login');
            
        } catch (Exception $e) {
            $this->db->rollBack();
            $_SESSION['error'] = 'Terjadi kesalahan: ' . $e->getMessage();
            $this->redirect('/register');
        }
    }
    
    /**
     * Logout
     */
    public function logout() {
        session_destroy();
        $this->redirect('/');
    }
    
    /**
     * Redirect based on user role
     */
    private function redirectByRole() {
        switch ($_SESSION['role']) {
            case 'MAHASISWA':
                $this->redirect('/mahasiswa/dashboard');
                break;
            case 'PETUGAS':
                $this->redirect('/petugas/dashboard');
                break;
            case 'ADMIN':
                $this->redirect('/admin/dashboard');
                break;
            default:
                $this->redirect('/');
        }
    }
}
