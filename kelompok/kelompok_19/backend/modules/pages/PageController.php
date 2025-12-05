<?php
/**
 * Public Pages Controller
 * Handle landing page, about page, etc
 */

class PageController extends Controller {
    
    /**
     * Landing page
     */
    public function index() {
        $this->view('pages/index');
    }
    
    /**
     * About page
     */
    public function about() {
        $this->view('pages/about');
    }
    
    /**
     * 404 Page
     */
    public function notFound() {
        http_response_code(404);
        $this->view('pages/404');
    }
    
    /**
     * Unauthorized page
     */
    public function unauthorized() {
        http_response_code(403);
        $this->view('pages/403');
    }
}
