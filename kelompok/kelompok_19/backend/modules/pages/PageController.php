<?php
/**
 * Public Pages Controller
 * Handle API info endpoints
 */

class PageController extends Controller {
    
    /**
     * API info / root endpoint
     */
    public function index() {
        return $this->json([
            'success' => true,
            'message' => 'SiPEMAU API v1.0',
            'data' => [
                'name' => 'Sistem Pengaduan Mahasiswa Universitas Lampung',
                'version' => '1.0.0',
                'endpoints' => [
                    'auth' => '/register, /login, /logout',
                    'mahasiswa' => '/mahasiswa/*',
                    'petugas' => '/petugas/*',
                    'admin' => '/admin/*'
                ],
                'documentation' => BASE_URL . '/docs'
            ]
        ]);
    }
    
    /**
     * API documentation info
     */
    public function about() {
        return $this->json([
            'success' => true,
            'message' => 'API Documentation',
            'data' => [
                'description' => 'REST API untuk Sistem Pengaduan Mahasiswa Universitas Lampung',
                'features' => [
                    'Authentication & Authorization',
                    'Complaint Management',
                    'Role-based Access Control',
                    'File Upload Support'
                ],
                'tech_stack' => [
                    'PHP Native',
                    'MySQL/MariaDB',
                    'Session-based Auth'
                ]
            ]
        ]);
    }
    
    /**
     * 404 Not Found
     */
    public function notFound() {
        return $this->json([
            'success' => false,
            'message' => 'Endpoint tidak ditemukan'
        ], 404);
    }
    
    /**
     * 403 Unauthorized
     */
    public function unauthorized() {
        return $this->json([
            'success' => false,
            'message' => 'Akses ditolak. Anda tidak memiliki izin untuk mengakses resource ini.'
        ], 403);
    }
}
