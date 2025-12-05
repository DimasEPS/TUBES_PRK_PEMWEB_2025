<?php
/**
 * Admin Controller
 * Handle dashboard dan manajemen data master
 */

class AdminController extends Controller {
    
    public function __construct() {
        parent::__construct();
        $this->requireAuth('ADMIN');
    }
    
    /**
     * Dashboard admin
     */
    public function dashboard() {
        // Get overall statistics
        $stmt = $this->db->query("
            SELECT 
                (SELECT COUNT(*) FROM complaints) as total_complaints,
                (SELECT COUNT(*) FROM complaints WHERE status = 'MENUNGGU') as menunggu,
                (SELECT COUNT(*) FROM complaints WHERE status = 'DIPROSES') as diproses,
                (SELECT COUNT(*) FROM complaints WHERE status = 'SELESAI') as selesai,
                (SELECT COUNT(*) FROM users WHERE role = 'MAHASISWA') as total_mahasiswa,
                (SELECT COUNT(*) FROM users WHERE role = 'PETUGAS') as total_petugas,
                (SELECT COUNT(*) FROM units WHERE is_active = 1) as total_units,
                (SELECT COUNT(*) FROM categories WHERE is_active = 1) as total_categories
        ");
        $stats = $stmt->fetch();
        
        // Get complaints by unit
        $stmt = $this->db->query("
            SELECT u.name as unit_name, COUNT(c.id) as total
            FROM units u
            LEFT JOIN categories cat ON u.id = cat.unit_id
            LEFT JOIN complaints c ON cat.id = c.category_id
            WHERE u.is_active = 1
            GROUP BY u.id, u.name
            ORDER BY total DESC
        ");
        $complaintsByUnit = $stmt->fetchAll();
        
        $this->view('admin/dashboard', [
            'stats' => $stats,
            'complaintsByUnit' => $complaintsByUnit
        ]);
    }
    
    // ==================== UNITS MANAGEMENT ====================
    
    /**
     * List units
     */
    public function listUnits() {
        $stmt = $this->db->query("
            SELECT u.*, 
                   (SELECT COUNT(*) FROM categories WHERE unit_id = u.id) as total_categories,
                   (SELECT COUNT(*) FROM petugas WHERE unit_id = u.id) as total_petugas
            FROM units u
            ORDER BY u.name
        ");
        $units = $stmt->fetchAll();
        
        $this->view('admin/units/list', ['units' => $units]);
    }
    
    /**
     * Create unit
     */
    public function createUnit() {
        $name = sanitize($this->post('name'));
        $description = sanitize($this->post('description'));
        $isActive = $this->post('is_active', 1);
        
        if (empty($name)) {
            $_SESSION['error'] = 'Nama unit harus diisi';
            $this->redirect('/admin/units');
        }
        
        try {
            $stmt = $this->db->prepare("
                INSERT INTO units (name, description, is_active)
                VALUES (?, ?, ?)
            ");
            $stmt->execute([$name, $description, $isActive]);
            
            $_SESSION['success'] = 'Unit berhasil ditambahkan';
        } catch (Exception $e) {
            $_SESSION['error'] = 'Gagal menambahkan unit: ' . $e->getMessage();
        }
        
        $this->redirect('/admin/units');
    }
    
    /**
     * Update unit
     */
    public function updateUnit($id) {
        $name = sanitize($this->post('name'));
        $description = sanitize($this->post('description'));
        $isActive = $this->post('is_active', 1);
        
        if (empty($name)) {
            $_SESSION['error'] = 'Nama unit harus diisi';
            $this->redirect('/admin/units');
        }
        
        try {
            $stmt = $this->db->prepare("
                UPDATE units 
                SET name = ?, description = ?, is_active = ?
                WHERE id = ?
            ");
            $stmt->execute([$name, $description, $isActive, $id]);
            
            $_SESSION['success'] = 'Unit berhasil diperbarui';
        } catch (Exception $e) {
            $_SESSION['error'] = 'Gagal memperbarui unit: ' . $e->getMessage();
        }
        
        $this->redirect('/admin/units');
    }
    
    /**
     * Delete unit
     */
    public function deleteUnit($id) {
        try {
            // Check if unit has categories
            $stmt = $this->db->prepare("SELECT COUNT(*) as total FROM categories WHERE unit_id = ?");
            $stmt->execute([$id]);
            $count = $stmt->fetch()['total'];
            
            if ($count > 0) {
                $_SESSION['error'] = 'Unit tidak dapat dihapus karena masih memiliki kategori';
                $this->redirect('/admin/units');
            }
            
            $stmt = $this->db->prepare("DELETE FROM units WHERE id = ?");
            $stmt->execute([$id]);
            
            $_SESSION['success'] = 'Unit berhasil dihapus';
        } catch (Exception $e) {
            $_SESSION['error'] = 'Gagal menghapus unit: ' . $e->getMessage();
        }
        
        $this->redirect('/admin/units');
    }
    
    // ==================== CATEGORIES MANAGEMENT ====================
    
    /**
     * List categories
     */
    public function listCategories() {
        $stmt = $this->db->query("
            SELECT c.*, u.name as unit_name,
                   (SELECT COUNT(*) FROM complaints WHERE category_id = c.id) as total_complaints
            FROM categories c
            JOIN units u ON c.unit_id = u.id
            ORDER BY c.name
        ");
        $categories = $stmt->fetchAll();
        
        $stmt = $this->db->query("SELECT id, name FROM units WHERE is_active = 1 ORDER BY name");
        $units = $stmt->fetchAll();
        
        $this->view('admin/categories/list', [
            'categories' => $categories,
            'units' => $units
        ]);
    }
    
    /**
     * Create category
     */
    public function createCategory() {
        $name = sanitize($this->post('name'));
        $description = sanitize($this->post('description'));
        $unitId = $this->post('unit_id');
        $isActive = $this->post('is_active', 1);
        
        $errors = [];
        if (empty($name)) $errors[] = 'Nama kategori harus diisi';
        if (empty($unitId)) $errors[] = 'Unit harus dipilih';
        
        if (!empty($errors)) {
            $_SESSION['error'] = implode('<br>', $errors);
            $this->redirect('/admin/categories');
        }
        
        try {
            $stmt = $this->db->prepare("
                INSERT INTO categories (name, description, unit_id, is_active)
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([$name, $description, $unitId, $isActive]);
            
            $_SESSION['success'] = 'Kategori berhasil ditambahkan';
        } catch (Exception $e) {
            $_SESSION['error'] = 'Gagal menambahkan kategori: ' . $e->getMessage();
        }
        
        $this->redirect('/admin/categories');
    }
    
    /**
     * Update category
     */
    public function updateCategory($id) {
        $name = sanitize($this->post('name'));
        $description = sanitize($this->post('description'));
        $unitId = $this->post('unit_id');
        $isActive = $this->post('is_active', 1);
        
        $errors = [];
        if (empty($name)) $errors[] = 'Nama kategori harus diisi';
        if (empty($unitId)) $errors[] = 'Unit harus dipilih';
        
        if (!empty($errors)) {
            $_SESSION['error'] = implode('<br>', $errors);
            $this->redirect('/admin/categories');
        }
        
        try {
            $stmt = $this->db->prepare("
                UPDATE categories 
                SET name = ?, description = ?, unit_id = ?, is_active = ?
                WHERE id = ?
            ");
            $stmt->execute([$name, $description, $unitId, $isActive, $id]);
            
            $_SESSION['success'] = 'Kategori berhasil diperbarui';
        } catch (Exception $e) {
            $_SESSION['error'] = 'Gagal memperbarui kategori: ' . $e->getMessage();
        }
        
        $this->redirect('/admin/categories');
    }
    
    /**
     * Delete category
     */
    public function deleteCategory($id) {
        try {
            // Check if category has complaints
            $stmt = $this->db->prepare("SELECT COUNT(*) as total FROM complaints WHERE category_id = ?");
            $stmt->execute([$id]);
            $count = $stmt->fetch()['total'];
            
            if ($count > 0) {
                $_SESSION['error'] = 'Kategori tidak dapat dihapus karena masih memiliki pengaduan';
                $this->redirect('/admin/categories');
            }
            
            $stmt = $this->db->prepare("DELETE FROM categories WHERE id = ?");
            $stmt->execute([$id]);
            
            $_SESSION['success'] = 'Kategori berhasil dihapus';
        } catch (Exception $e) {
            $_SESSION['error'] = 'Gagal menghapus kategori: ' . $e->getMessage();
        }
        
        $this->redirect('/admin/categories');
    }
    
    // ==================== PETUGAS MANAGEMENT ====================
    
    /**
     * List petugas
     */
    public function listPetugas() {
        $stmt = $this->db->query("
            SELECT u.*, p.jabatan, un.name as unit_name
            FROM users u
            JOIN petugas p ON u.id = p.id
            JOIN units un ON p.unit_id = un.id
            WHERE u.role = 'PETUGAS'
            ORDER BY u.name
        ");
        $petugas = $stmt->fetchAll();
        
        $stmt = $this->db->query("SELECT id, name FROM units WHERE is_active = 1 ORDER BY name");
        $units = $stmt->fetchAll();
        
        $this->view('admin/petugas/list', [
            'petugas' => $petugas,
            'units' => $units
        ]);
    }
    
    /**
     * Create petugas
     */
    public function createPetugas() {
        $name = sanitize($this->post('name'));
        $email = sanitize($this->post('email'));
        $password = $this->post('password');
        $unitId = $this->post('unit_id');
        $jabatan = sanitize($this->post('jabatan'));
        
        // Validation
        $errors = [];
        if (empty($name)) $errors[] = 'Nama harus diisi';
        if (empty($email) || !validateEmail($email)) $errors[] = 'Email tidak valid';
        if (strlen($password) < PASSWORD_MIN_LENGTH) {
            $errors[] = 'Password minimal ' . PASSWORD_MIN_LENGTH . ' karakter';
        }
        if (empty($unitId)) $errors[] = 'Unit harus dipilih';
        
        if (!empty($errors)) {
            $_SESSION['error'] = implode('<br>', $errors);
            $this->redirect('/admin/petugas');
        }
        
        // Check if email exists
        $stmt = $this->db->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $_SESSION['error'] = 'Email sudah terdaftar';
            $this->redirect('/admin/petugas');
        }
        
        try {
            $this->db->beginTransaction();
            
            // Insert user
            $stmt = $this->db->prepare("
                INSERT INTO users (name, email, password_hash, role)
                VALUES (?, ?, ?, 'PETUGAS')
            ");
            $stmt->execute([$name, $email, hashPassword($password)]);
            $userId = $this->db->lastInsertId();
            
            // Insert petugas
            $stmt = $this->db->prepare("
                INSERT INTO petugas (id, unit_id, jabatan)
                VALUES (?, ?, ?)
            ");
            $stmt->execute([$userId, $unitId, $jabatan]);
            
            $this->db->commit();
            
            $_SESSION['success'] = 'Petugas berhasil ditambahkan';
        } catch (Exception $e) {
            $this->db->rollBack();
            $_SESSION['error'] = 'Gagal menambahkan petugas: ' . $e->getMessage();
        }
        
        $this->redirect('/admin/petugas');
    }
    
    /**
     * Update petugas
     */
    public function updatePetugas($id) {
        $name = sanitize($this->post('name'));
        $email = sanitize($this->post('email'));
        $password = $this->post('password');
        $unitId = $this->post('unit_id');
        $jabatan = sanitize($this->post('jabatan'));
        
        // Validation
        $errors = [];
        if (empty($name)) $errors[] = 'Nama harus diisi';
        if (empty($email) || !validateEmail($email)) $errors[] = 'Email tidak valid';
        if (empty($unitId)) $errors[] = 'Unit harus dipilih';
        
        if (!empty($errors)) {
            $_SESSION['error'] = implode('<br>', $errors);
            $this->redirect('/admin/petugas');
        }
        
        try {
            $this->db->beginTransaction();
            
            // Update user
            if (!empty($password)) {
                $stmt = $this->db->prepare("
                    UPDATE users 
                    SET name = ?, email = ?, password_hash = ?
                    WHERE id = ?
                ");
                $stmt->execute([$name, $email, hashPassword($password), $id]);
            } else {
                $stmt = $this->db->prepare("
                    UPDATE users 
                    SET name = ?, email = ?
                    WHERE id = ?
                ");
                $stmt->execute([$name, $email, $id]);
            }
            
            // Update petugas
            $stmt = $this->db->prepare("
                UPDATE petugas 
                SET unit_id = ?, jabatan = ?
                WHERE id = ?
            ");
            $stmt->execute([$unitId, $jabatan, $id]);
            
            $this->db->commit();
            
            $_SESSION['success'] = 'Petugas berhasil diperbarui';
        } catch (Exception $e) {
            $this->db->rollBack();
            $_SESSION['error'] = 'Gagal memperbarui petugas: ' . $e->getMessage();
        }
        
        $this->redirect('/admin/petugas');
    }
    
    /**
     * Delete petugas
     */
    public function deletePetugas($id) {
        try {
            $stmt = $this->db->prepare("DELETE FROM users WHERE id = ? AND role = 'PETUGAS'");
            $stmt->execute([$id]);
            
            $_SESSION['success'] = 'Petugas berhasil dihapus';
        } catch (Exception $e) {
            $_SESSION['error'] = 'Gagal menghapus petugas: ' . $e->getMessage();
        }
        
        $this->redirect('/admin/petugas');
    }
}
