<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use App\Models\UserModel;
use App\Models\PublicationModel;
use App\Models\FacultyModel;
use App\Models\CurriculumModel;

/**
 * FacultySearchController
 * 
 * Public controller for searching faculty/professor publications.
 * Provides both web interface and API endpoints for external systems.
 * 
 * Features:
 * 1. Public search page for browsing faculty list and their publications
 * 2. API endpoint for external systems to retrieve publications by email
 */
class FacultySearchController extends Controller
{
    protected $userModel;
    protected $publicationModel;
    protected $facultyModel;
    protected $curriculumModel;
    protected $session;
    protected $db;

    public function __construct()
    {
        $this->userModel = new UserModel();
        $this->publicationModel = new PublicationModel();
        $this->facultyModel = new FacultyModel();
        $this->curriculumModel = new CurriculumModel();
        $this->session = \Config\Services::session();
        $this->db = \Config\Database::connect();
    }

    /**
     * Public faculty search page
     * Route: GET /faculty-search
     * 
     * Displays a searchable list of all faculty members (teachers)
     * with links to view their individual publications
     */
    public function index()
    {
        $data = [
            'title' => 'ค้นหาผลงานอาจารย์ - Faculty Publication Search',
            'description' => 'ค้นหาผลงานวิจัยและบทความของอาจารย์ในมหาวิทยาลัย'
        ];

        return view('faculty_search/index', $data);
    }
}
