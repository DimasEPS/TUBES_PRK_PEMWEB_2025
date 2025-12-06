<?php
/**
 * Petugas Controller
 * Handle fitur dashboard dan tindak lanjut petugas
 */

class PetugasController extends Controller {
    
    public function __construct() {
        parent::__construct();
        $this->requireAuth('PETUGAS');
    }
    
    /**
     * Dashboard petugas
     */
    public function dashboard() {
        // Get statistics for this unit
        $stmt = $this->db->prepare("
            SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN c.status = 'MENUNGGU' THEN 1 ELSE 0 END) as menunggu,
                SUM(CASE WHEN c.status = 'DIPROSES' THEN 1 ELSE 0 END) as diproses,
                SUM(CASE WHEN c.status = 'SELESAI' THEN 1 ELSE 0 END) as selesai
            FROM complaints c
            JOIN categories cat ON c.category_id = cat.id
            WHERE cat.unit_id = ?
        ");
        $stmt->execute([$_SESSION['unit_id']]);
        $stats = $stmt->fetch();
        
        // Get recent complaints
        $stmt = $this->db->prepare("
            SELECT c.*, cat.name as category_name, u.name as mahasiswa_name, m.nim
            FROM complaints c
            JOIN categories cat ON c.category_id = cat.id
            JOIN mahasiswa m ON c.mahasiswa_id = m.id
            JOIN users u ON m.id = u.id
            WHERE cat.unit_id = ?
            ORDER BY c.created_at DESC
            LIMIT 10
        ");
        $stmt->execute([$_SESSION['unit_id']]);
        $recentComplaints = $stmt->fetchAll();
        
        return $this->json([
            'success' => true,
            'data' => [
                'stats' => $stats,
                'recent_complaints' => $recentComplaints
            ]
        ]);
    }
    
    /**
     * List complaints for this unit
     */
    public function listComplaints() {
        $page = $this->get('page', 1);
        $offset = ($page - 1) * ITEMS_PER_PAGE;
        $status = $this->get('status', '');
        $category = $this->get('category', '');
        
        // Build query
        $whereClauses = ["cat.unit_id = ?"];
        $params = [$_SESSION['unit_id']];
        
        if (!empty($status)) {
            $whereClauses[] = "c.status = ?";
            $params[] = $status;
        }
        
        if (!empty($category)) {
            $whereClauses[] = "c.category_id = ?";
            $params[] = $category;
        }
        
        $whereSQL = implode(' AND ', $whereClauses);
        
        // Get total count
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as total 
            FROM complaints c 
            JOIN categories cat ON c.category_id = cat.id
            WHERE $whereSQL
        ");
        $stmt->execute($params);
        $totalComplaints = $stmt->fetch()['total'];
        $totalPages = ceil($totalComplaints / ITEMS_PER_PAGE);
        
        // Get complaints
        $stmt = $this->db->prepare("
            SELECT c.*, cat.name as category_name, u.name as mahasiswa_name, m.nim
            FROM complaints c
            JOIN categories cat ON c.category_id = cat.id
            JOIN mahasiswa m ON c.mahasiswa_id = m.id
            JOIN users u ON m.id = u.id
            WHERE $whereSQL
            ORDER BY c.created_at DESC
            LIMIT ? OFFSET ?
        ");
        $params[] = ITEMS_PER_PAGE;
        $params[] = $offset;
        $stmt->execute($params);
        $complaints = $stmt->fetchAll();
        
        // Get categories for filter
        $stmt = $this->db->prepare("
            SELECT id, name FROM categories WHERE unit_id = ? AND is_active = 1
        ");
        $stmt->execute([$_SESSION['unit_id']]);
        $categories = $stmt->fetchAll();
        
        return $this->json([
            'success' => true,
            'data' => [
                'complaints' => $complaints,
                'categories' => $categories,
                'pagination' => [
                    'current_page' => $page,
                    'total_pages' => $totalPages,
                    'total_items' => $totalComplaints,
                    'per_page' => ITEMS_PER_PAGE
                ],
                'filter' => [
                    'status' => $status,
                    'category' => $category
                ]
            ]
        ]);
    }
    
    /**
     * Show complaint detail
     */
    public function detailComplaint($id) {
        $stmt = $this->db->prepare("
            SELECT c.*, cat.name as category_name, mu.name as mahasiswa_name, 
                   m.nim, mu.email as mahasiswa_email
            FROM complaints c
            JOIN categories cat ON c.category_id = cat.id
            JOIN mahasiswa m ON c.mahasiswa_id = m.id
            JOIN users mu ON m.id = mu.id
            WHERE c.id = ? AND cat.unit_id = ?
        ");
        $stmt->execute([$id, $_SESSION['unit_id']]);
        $complaint = $stmt->fetch();
        
        if (!$complaint) {
            return $this->json([
                'success' => false,
                'message' => 'Pengaduan tidak ditemukan'
            ], 404);
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
        
        return $this->json([
            'success' => true,
            'data' => [
                'complaint' => $complaint,
                'notes' => $notes
            ]
        ]);
    }
    
    /**
     * Update complaint status
     */
    public function updateStatus($complaintId) {
        $status = $this->input('status');
        
        // Validate status
        $allowedStatuses = ['MENUNGGU', 'DIPROSES', 'SELESAI'];
        if (!in_array($status, $allowedStatuses)) {
            return $this->json([
                'success' => false,
                'message' => 'Status tidak valid'
            ], 400);
        }
        
        // Verify complaint belongs to this unit
        $stmt = $this->db->prepare("
            SELECT c.id 
            FROM complaints c
            JOIN categories cat ON c.category_id = cat.id
            WHERE c.id = ? AND cat.unit_id = ?
        ");
        $stmt->execute([$complaintId, $_SESSION['unit_id']]);
        
        if (!$stmt->fetch()) {
            return $this->json([
                'success' => false,
                'message' => 'Pengaduan tidak ditemukan'
            ], 404);
        }
        
        try {
            // Update status
            $resolvedAt = ($status === 'SELESAI') ? date('Y-m-d H:i:s') : null;
            
            $stmt = $this->db->prepare("
                UPDATE complaints 
                SET status = ?, resolved_at = ?
                WHERE id = ?
            ");
            $stmt->execute([$status, $resolvedAt, $complaintId]);
            
            return $this->json([
                'success' => true,
                'message' => 'Status berhasil diperbarui',
                'data' => [
                    'status' => $status,
                    'resolved_at' => $resolvedAt
                ]
            ]);
            
        } catch (Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Gagal memperbarui status: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Add note to complaint
     */
    public function addNote($complaintId) {
        $note = sanitize($this->post('note'));
        
        if (empty($note)) {
            return $this->json([
                'success' => false,
                'message' => 'Catatan tidak boleh kosong'
            ], 400);
        }
        
        // Verify complaint belongs to this unit
        $stmt = $this->db->prepare("
            SELECT c.id 
            FROM complaints c
            JOIN categories cat ON c.category_id = cat.id
            WHERE c.id = ? AND cat.unit_id = ?
        ");
        $stmt->execute([$complaintId, $_SESSION['unit_id']]);
        
        if (!$stmt->fetch()) {
            return $this->json([
                'success' => false,
                'message' => 'Pengaduan tidak ditemukan'
            ], 404);
        }
        
        try {
            $stmt = $this->db->prepare("
                INSERT INTO complaint_notes (complaint_id, petugas_id, note)
                VALUES (?, ?, ?)
            ");
            $stmt->execute([$complaintId, $_SESSION['user_id'], $note]);
            
            $noteId = $this->db->lastInsertId();
            
            return $this->json([
                'success' => true,
                'message' => 'Catatan berhasil ditambahkan',
                'data' => [
                    'note_id' => $noteId
                ]
            ], 201);
            
        } catch (Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Gagal menambahkan catatan: ' . $e->getMessage()
            ], 500);
        }
    }
}
