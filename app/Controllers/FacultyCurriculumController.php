<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use App\Models\FacultyModel;
use App\Models\CurriculumModel;
use App\Models\PublicationModel;

class FacultyCurriculumController extends Controller
{
    protected $facultyModel;
    protected $curriculumModel;
    protected $publicationModel;
    protected $db;

    public function __construct()
    {
        $this->facultyModel = new FacultyModel();
        $this->curriculumModel = new CurriculumModel();
        $this->publicationModel = new PublicationModel();
        $this->db = \Config\Database::connect();
    }

    /**
     * Get publications by faculty
     */
    public function getByFaculty()
    {
        try {
            $facultyId = $this->request->getGet('faculty_id');

            $query = "
                SELECT 
                    f.id as faculty_id,
                    f.name as faculty_name,
                    COUNT(p.id) as publication_count
                FROM faculties f
                LEFT JOIN curriculum c ON c.faculty_id = f.id
                LEFT JOIN user u ON u.curriculum_id = c.id
                LEFT JOIN publications p ON p.created_by_email = u.email
                WHERE f.status = 'active'
            ";

            if ($facultyId) {
                $query .= " AND f.id = ?";
                $result = $this->db->query($query, [$facultyId])->getRowArray();
            } else {
                $query .= " GROUP BY f.id";
                $result = $this->db->query($query)->getResultArray();
            }

            return $this->response->setJSON([
                'success' => true,
                'data' => $result
            ]);
        } catch (\Exception $e) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Failed to load data'
            ]);
        }
    }

    /**
     * Get publications by curriculum
     */
    public function getByCurriculum()
    {
        try {
            $curriculumId = $this->request->getGet('curriculum_id');

            $query = "
                SELECT 
                    c.id as curriculum_id,
                    c.name as curriculum_name,
                    f.name as faculty_name,
                    COUNT(p.id) as publication_count
                FROM curriculum c
                JOIN faculties f ON f.id = c.faculty_id
                LEFT JOIN user u ON u.curriculum_id = c.id
                LEFT JOIN publications p ON p.created_by_email = u.email
                WHERE c.status = 'active'
            ";

            if ($curriculumId) {
                $query .= " AND c.id = ?";
                $result = $this->db->query($query, [$curriculumId])->getRowArray();
            } else {
                $query .= " GROUP BY c.id";
                $result = $this->db->query($query)->getResultArray();
            }

            return $this->response->setJSON([
                'success' => true,
                'data' => $result
            ]);
        } catch (\Exception $e) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Failed to load data'
            ]);
        }
    }

    /**
     * Get publication details
     */
    public function getPublications()
    {
        try {
            $facultyId = $this->request->getGet('faculty_id');
            $curriculumId = $this->request->getGet('curriculum_id');

            $query = "
                SELECT 
                    p.*,
                    u.gf_name,
                    u.gl_name,
                    c.name as curriculum_name,
                    f.name as faculty_name
                FROM publications p
                JOIN user u ON u.email = p.created_by_email
                JOIN curriculum c ON c.id = u.curriculum_id
                JOIN faculties f ON f.id = c.faculty_id
                WHERE 1=1
            ";

            $params = [];

            if ($facultyId) {
                $query .= " AND f.id = ?";
                $params[] = $facultyId;
            }

            if ($curriculumId) {
                $query .= " AND c.id = ?";
                $params[] = $curriculumId;
            }

            $query .= " ORDER BY p.created_at DESC";

            $result = $this->db->query($query, $params)->getResultArray();

            return $this->response->setJSON([
                'success' => true,
                'data' => $result
            ]);
        } catch (\Exception $e) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Failed to load publications'
            ]);
        }
    }

    /**
     * Get simple statistics
     */
    public function getStats()
    {
        try {
            $stats = $this->db->query("
                SELECT
                    COUNT(DISTINCT f.id) as total_faculties,
                    COUNT(DISTINCT c.id) as total_curriculum,
                    COUNT(DISTINCT p.id) as total_publications
                FROM faculties f
                LEFT JOIN curriculum c ON c.faculty_id = f.id
                LEFT JOIN user u ON u.curriculum_id = c.id
                LEFT JOIN publications p ON p.created_by_email = u.email
                WHERE f.status = 'active'
            ")->getRowArray();

            return $this->response->setJSON([
                'success' => true,
                'data' => $stats
            ]);
        } catch (\Exception $e) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Failed to load statistics'
            ]);
        }
    }

    /**
     * Get faculties with their curriculums (AJAX)
     */
    public function getWithCurriculums()
    {
        try {
            $faculties = $this->facultyModel->getWithCurriculums();

            return $this->response->setJSON([
                'success' => true,
                'data' => $faculties
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Get faculties with curriculums error: ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Failed to load faculties and curriculums'
            ]);
        }
    }
}
