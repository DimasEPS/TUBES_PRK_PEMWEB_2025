<?php
/**
 * Mahasiswa Controller
 * Handle fitur pengaduan mahasiswa
 */

class MahasiswaController extends Controller {
    
    public function __construct() {
        parent::__construct();
        $this->requireAuth('MAHASISWA');
    }
    
    /**
     * Dashboard mahasiswa
     */
    public function dashboard() {
        // Get statistics
        $stmt = $this->db->prepare("
            SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN status = 'MENUNGGU' THEN 1 ELSE 0 END) as menunggu,
                SUM(CASE WHEN status = 'DIPROSES' THEN 1 ELSE 0 END) as diproses,
                SUM(CASE WHEN status = 'SELESAI' THEN 1 ELSE 0 END) as selesai
            FROM complaints 
            WHERE mahasiswa_id = ?
        ");
        $stmt->execute([$_SESSION['user_id']]);
        $stats = $stmt->fetch();
        
        // Get recent complaints
        $stmt = $this->db->prepare("
            SELECT c.*, cat.name as category_name, u.name as unit_name
            FROM complaints c
            JOIN categories cat ON c.category_id = cat.id
            JOIN units u ON cat.unit_id = u.id
            WHERE c.mahasiswa_id = ?
            ORDER BY c.created_at DESC
            LIMIT 5
        ");
        $stmt->execute([$_SESSION['user_id']]);
        $recentComplaints = $stmt->fetchAll();
        
        $this->view('mahasiswa/dashboard', [
            'stats' => $stats,
            'recentComplaints' => $recentComplaints
        ]);
    }
    
    /**
     * List all complaints
     */
    public function listComplaints() {
        $page = $this->get('page', 1);
        $offset = ($page - 1) * ITEMS_PER_PAGE;
        $status = $this->get('status', '');
        
        // Build query
        $whereClauses = ["c.mahasiswa_id = ?"];
        $params = [$_SESSION['user_id']];
        
        if (!empty($status)) {
            $whereClauses[] = "c.status = ?";
            $params[] = $status;
        }
        
        $whereSQL = implode(' AND ', $whereClauses);
        
        // Get total count
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as total 
            FROM complaints c 
            WHERE $whereSQL
        ");
        $stmt->execute($params);
        $totalComplaints = $stmt->fetch()['total'];
        $totalPages = ceil($totalComplaints / ITEMS_PER_PAGE);
        
        // Get complaints
        $stmt = $this->db->prepare("
            SELECT c.*, cat.name as category_name, u.name as unit_name
            FROM complaints c
            JOIN categories cat ON c.category_id = cat.id
            JOIN units u ON cat.unit_id = u.id
            WHERE $whereSQL
            ORDER BY c.created_at DESC
            LIMIT ? OFFSET ?
        ");
        $params[] = ITEMS_PER_PAGE;
        $params[] = $offset;
        $stmt->execute($params);
        $complaints = $stmt->fetchAll();
        
        $this->view('mahasiswa/complaints', [
            'complaints' => $complaints,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'status' => $status
        ]);
    }
    
    /**
     * Show complaint detail
     */
    public function detailComplaint($id) {
        $stmt = $this->db->prepare("
            SELECT c.*, cat.name as category_name, u.name as unit_name
            FROM complaints c
            JOIN categories cat ON c.category_id = cat.id
            JOIN units u ON cat.unit_id = u.id
            WHERE c.id = ? AND c.mahasiswa_id = ?
        ");
        $stmt->execute([$id, $_SESSION['user_id']]);
        $complaint = $stmt->fetch();
        
        if (!$complaint) {
            $_SESSION['error'] = 'Pengaduan tidak ditemukan';
            $this->redirect('/mahasiswa/complaints');
        }
        
        // Get notes
        $stmt = $this->db->prepare("
            SELECT cn.*, u.name as petugas_name, p.jabatan
            FROM complaint_notes cn
            JOIN petugas p ON cn.petugas_id = p.id
            JOIN users u ON p.id = u.id
            WHERE cn.complaint_id = ?
            ORDER BY cn.created_at DESC
        ");
        $stmt->execute([$id]);
        $notes = $stmt->fetchAll();
        
        $this->view('mahasiswa/detail', [
            'complaint' => $complaint,
            'notes' => $notes
        ]);
    }
    
    /**
     * Show create complaint form
     */
    public function showCreateComplaint() {
        // Get active categories
        $stmt = $this->db->query("
            SELECT c.*, u.name as unit_name 
            FROM categories c 
            JOIN units u ON c.unit_id = u.id 
            WHERE c.is_active = 1
            ORDER BY c.name
        ");
        $categories = $stmt->fetchAll();
        
        $this->view('mahasiswa/create', [
            'categories' => $categories
        ]);
    }
    
    /**
     * Store new complaint
     */
    public function storeComplaint() {
        $title = sanitize($this->post('title'));
        $description = sanitize($this->post('description'));
        $categoryId = $this->post('category_id');
        
        // Validation
        $errors = [];
        if (empty($title)) $errors[] = 'Judul harus diisi';
        if (empty($description)) $errors[] = 'Deskripsi harus diisi';
        if (empty($categoryId)) $errors[] = 'Kategori harus dipilih';
        
        if (!empty($errors)) {
            $_SESSION['error'] = implode('<br>', $errors);
            $this->redirect('/mahasiswa/complaints/create');
        }
        
        try {
            // Handle file upload
            $evidencePath = null;
            if (isset($_FILES['evidence']) && $_FILES['evidence']['error'] !== UPLOAD_ERR_NO_FILE) {
                $evidencePath = uploadFile($_FILES['evidence']);
            }
            
            // Insert complaint
            $stmt = $this->db->prepare("
                INSERT INTO complaints (mahasiswa_id, category_id, title, description, evidence_path, status)
                VALUES (?, ?, ?, ?, ?, 'MENUNGGU')
            ");
            $stmt->execute([
                $_SESSION['user_id'],
                $categoryId,
                $title,
                $description,
                $evidencePath
            ]);
            
            $_SESSION['success'] = 'Pengaduan berhasil dikirim';
            $this->redirect('/mahasiswa/complaints');
            
        } catch (Exception $e) {
            if (isset($evidencePath)) {
                deleteFile($evidencePath);
            }
            $_SESSION['error'] = 'Gagal mengirim pengaduan: ' . $e->getMessage();
            $this->redirect('/mahasiswa/complaints/create');
        }
    }
}
