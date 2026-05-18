<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use App\Models\CurriculumModel;
use App\Models\FacultyModel;

class CurriculumController extends Controller
{
    protected $curriculumModel;
    protected $facultyModel;
    protected $session;

    public function __construct()
    {
        $this->curriculumModel = new CurriculumModel();
        $this->facultyModel = new FacultyModel();
        $this->session = session();
    }

    /**
     * Get curriculum list (AJAX)
     */
    public function index()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setJSON(['success' => false, 'message' => 'Invalid request']);
        }

        try {
            $facultyId = $this->request->getGet('faculty_id');

            if ($facultyId) {
                $curriculum = $this->curriculumModel->getByFaculty($facultyId);
            } else {
                $curriculum = $this->curriculumModel->getWithFaculty();
            }

            return $this->response->setJSON([
                'success' => true,
                'data' => $curriculum
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Get curriculum error: ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Failed to load curriculum'
            ]);
        }
    }

    /**
     * Get curriculum for dropdown
     */
    public function getForSelect()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setJSON(['success' => false, 'message' => 'Invalid request']);
        }

        try {
            $facultyId = $this->request->getGet('faculty_id');
            $curriculum = $this->curriculumModel->getForSelect($facultyId);

            return $this->response->setJSON([
                'success' => true,
                'data' => $curriculum
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Get curriculum select error: ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Failed to load curriculum options'
            ]);
        }
    }

    /**
     * Get curriculum by faculty
     */
    public function getByFaculty()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setJSON(['success' => false, 'message' => 'Invalid request']);
        }

        try {
            $facultyId = $this->request->getGet('faculty_id');

            if (!$facultyId) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Faculty ID required'
                ]);
            }

            $curriculum = $this->curriculumModel->getByFaculty($facultyId);

            return $this->response->setJSON([
                'success' => true,
                'data' => $curriculum
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Get curriculum by faculty error: ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Failed to load curriculum'
            ]);
        }
    }

    /**
     * Get curriculum statistics
     */
    public function getStats()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setJSON(['success' => false, 'message' => 'Invalid request']);
        }

        try {
            $stats = $this->curriculumModel->getStats();

            return $this->response->setJSON([
                'success' => true,
                'data' => $stats
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Get curriculum stats error: ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Failed to load statistics'
            ]);
        }
    }

    /**
     * Get curriculum with publication statistics for dashboard
     */
    public function getWithPublicationStats()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setJSON(['success' => false, 'message' => 'Invalid request']);
        }

        try {
            $facultyId = $this->request->getGet('faculty_id');
            $data = $this->curriculumModel->getWithPublicationStats($facultyId);

            return $this->response->setJSON([
                'success' => true,
                'data' => $data
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Get curriculum publication stats error: ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Failed to load curriculum statistics: ' . $e->getMessage()
            ]);
        }
    }
}
