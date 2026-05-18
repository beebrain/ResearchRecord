<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use App\Models\UserModel;
use App\Models\PublicationModel;
use App\Models\FacultyModel;
use App\Models\CurriculumModel;

/**
 * ApiController
 * 
 * Dedicated controller for public API endpoints used by external systems
 * and internal AJAX calls.
 */
class ApiController extends Controller
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
     * Get all faculty members (teachers) for search listing
     * Route: GET /faculty-search/teachers
     */
    public function getTeachers()
    {
        try {
            $facultyId = $this->request->getGet('faculty_id');
            $curriculumId = $this->request->getGet('curriculum_id');
            $search = $this->request->getGet('search');

            // Build query to get active teachers
            $builder = $this->db->table('user u');
            $builder->select('
                u.uid,
                u.email,
                u.title,
                u.titleThai,
                u.gf_name,
                u.gl_name,
                u.thai_name,
                u.thai_lastname,
                u.profile_picture,
                u.user_type,
                f.id as faculty_id,
                f.name as faculty_name,
                c.id as curriculum_id,
                c.name as curriculum_name,
                (SELECT COUNT(DISTINCT pa.publication_id) 
                 FROM publication_authors pa 
                 WHERE pa.author_email = u.email OR pa.uid = u.uid) as publication_count
            ')
            ->join('curriculum c', 'u.curriculum_id = c.id', 'left')
            ->join('faculties f', 'c.faculty_id = f.id', 'left')
            ->where('u.active', 1)
            ->where('u.user_type', 'TEACHER')
            ->orderBy('u.thai_name', 'ASC')
            ->orderBy('u.gf_name', 'ASC');

            // Apply filters
            if (!empty($facultyId)) {
                $builder->where('f.id', $facultyId);
            }

            if (!empty($curriculumId)) {
                $builder->where('c.id', $curriculumId);
            }

            if (!empty($search)) {
                $builder->groupStart()
                    ->like('u.thai_name', $search)
                    ->orLike('u.thai_lastname', $search)
                    ->orLike('u.gf_name', $search)
                    ->orLike('u.gl_name', $search)
                    ->orLike('u.email', $search)
                ->groupEnd();
            }

            $teachers = $builder->get()->getResultArray();

            // Format response
            $formattedTeachers = array_map(function($teacher) {
                $thaiFullName = trim(($teacher['titleThai'] ?? '') . ' ' . ($teacher['thai_name'] ?? '') . ' ' . ($teacher['thai_lastname'] ?? ''));
                $engFullName = trim(($teacher['title'] ?? '') . ' ' . ($teacher['gf_name'] ?? '') . ' ' . ($teacher['gl_name'] ?? ''));
                
                return [
                    'uid' => $teacher['uid'],
                    'email' => $teacher['email'],
                    'name_thai' => !empty(trim($thaiFullName)) ? $thaiFullName : $engFullName,
                    'name_english' => $engFullName,
                    'profile_picture' => str_replace('https:', 'http:', $teacher['profile_picture'] ?? ''),
                    'faculty' => $teacher['faculty_name'] ?? 'ไม่ระบุ',
                    'faculty_id' => $teacher['faculty_id'],
                    'curriculum' => $teacher['curriculum_name'] ?? 'ไม่ระบุ',
                    'curriculum_id' => $teacher['curriculum_id'],
                    'publication_count' => (int)($teacher['publication_count'] ?? 0)
                ];
            }, $teachers);

            return $this->response->setJSON([
                'success' => true,
                'data' => $formattedTeachers,
                'total' => count($formattedTeachers)
            ]);

        } catch (\Exception $e) {
            log_message('error', 'ApiController::getTeachers error: ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการดึงข้อมูลอาจารย์'
            ]);
        }
    }

    /**
     * Get publication details for a specific teacher
     * Route: GET /faculty-search/publications/{uid}
     */
    public function getTeacherPublications($uid = null)
    {
        if (empty($uid)) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'กรุณาระบุรหัสอาจารย์'
            ]);
        }

        try {
            // Get teacher info
            $teacher = $this->userModel->find($uid);
            if (!$teacher) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'ไม่พบข้อมูลอาจารย์'
                ]);
            }

            // Get publications using the existing model method
            $publications = $this->publicationModel->getPublicationsByAuthor($uid);

            // Get teacher's curriculum and faculty
            $curriculum = null;
            $faculty = null;
            if (!empty($teacher['curriculum_id'])) {
                $curriculum = $this->curriculumModel->find($teacher['curriculum_id']);
                if ($curriculum && !empty($curriculum['faculty_id'])) {
                    $faculty = $this->facultyModel->find($curriculum['faculty_id']);
                }
            }

            $thaiFullName = trim(($teacher['titleThai'] ?? '') . ' ' . ($teacher['thai_name'] ?? '') . ' ' . ($teacher['thai_lastname'] ?? ''));
            $engFullName = trim(($teacher['title'] ?? '') . ' ' . ($teacher['gf_name'] ?? '') . ' ' . ($teacher['gl_name'] ?? ''));

            return $this->response->setJSON([
                'success' => true,
                'teacher' => [
                    'uid' => $teacher['uid'],
                    'email' => $teacher['email'],
                    'name_thai' => !empty(trim($thaiFullName)) ? $thaiFullName : $engFullName,
                    'name_english' => $engFullName,
                    'profile_picture' => str_replace('https:', 'http:', $teacher['profile_picture'] ?? ''),
                    'faculty' => $faculty['name'] ?? 'ไม่ระบุ',
                    'curriculum' => $curriculum['name'] ?? 'ไม่ระบุ'
                ],
                'publications' => $publications,
                'total' => count($publications)
            ]);

        } catch (\Exception $e) {
            log_message('error', 'ApiController::getTeacherPublications error: ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการดึงข้อมูลผลงาน'
            ]);
        }
    }

    /**
     * Get faculties list for filter dropdown
     */
    public function getFaculties()
    {
        try {
            $faculties = $this->facultyModel
                ->where('status', 1)
                ->orderBy('name', 'ASC')
                ->findAll();

            return $this->response->setJSON([
                'success' => true,
                'data' => $faculties
            ]);
        } catch (\Exception $e) {
            log_message('error', 'ApiController::getFaculties error: ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการดึงข้อมูลคณะ'
            ]);
        }
    }

    /**
     * Get curricula list by faculty for filter dropdown
     */
    public function getCurricula()
    {
        try {
            $facultyId = $this->request->getGet('faculty_id');

            $builder = $this->curriculumModel
                ->where('status', 1)
                ->orderBy('name', 'ASC');

            if (!empty($facultyId)) {
                $builder->where('faculty_id', $facultyId);
            }

            $curricula = $builder->findAll();

            return $this->response->setJSON([
                'success' => true,
                'data' => $curricula
            ]);
        } catch (\Exception $e) {
            log_message('error', 'ApiController::getCurricula error: ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการดึงข้อมูลหลักสูตร'
            ]);
        }
    }

    /**
     * API: Get publications by email
     * Route: GET /api/public/publications-by-email
     */
    public function apiGetPublicationsByEmail()
    {
        // Set CORS headers
        $this->response->setHeader('Access-Control-Allow-Origin', '*');
        $this->response->setHeader('Access-Control-Allow-Methods', 'GET, OPTIONS');
        $this->response->setHeader('Access-Control-Allow-Headers', 'Content-Type');

        if ($this->request->getMethod() === 'options') {
            return $this->response->setStatusCode(200);
        }

        try {
            $email = $this->request->getGet('email');

            if (empty($email)) {
                return $this->response->setStatusCode(400)->setJSON([
                    'success' => false,
                    'error' => 'MISSING_EMAIL',
                    'message' => 'Email parameter is required'
                ]);
            }

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return $this->response->setStatusCode(400)->setJSON([
                    'success' => false,
                    'error' => 'INVALID_EMAIL',
                    'message' => 'Invalid email format'
                ]);
            }

            // Find user by email
            $user = $this->userModel->where('email', $email)->first();

            // Also check in authors table for secondary emails
            if (!$user) {
                $author = $this->db->table('authors')
                    ->where('email', $email)
                    ->get()
                    ->getRowArray();

                if ($author && !empty($author['user_uid'])) {
                    $user = $this->userModel->find($author['user_uid']);
                }
            }

            if (!$user) {
                return $this->response->setStatusCode(404)->setJSON([
                    'success' => false,
                    'error' => 'USER_NOT_FOUND',
                    'message' => 'No user found with the provided email'
                ]);
            }

            // Get publications for this user
            $publications = $this->publicationModel->getPublicationsByAuthor($user['uid']);

            // Get curriculum and faculty info
            $curriculum = null;
            $faculty = null;
            if (!empty($user['curriculum_id'])) {
                $curriculum = $this->curriculumModel->find($user['curriculum_id']);
                if ($curriculum && !empty($curriculum['faculty_id'])) {
                    $faculty = $this->facultyModel->find($curriculum['faculty_id']);
                }
            }

            $thaiFullName = trim(($user['titleThai'] ?? '') . ' ' . ($user['thai_name'] ?? '') . ' ' . ($user['thai_lastname'] ?? ''));
            $engFullName = trim(($user['title'] ?? '') . ' ' . ($user['gf_name'] ?? '') . ' ' . ($user['gl_name'] ?? ''));

            $formattedPublications = array_map(function($pub) {
                return [
                    'id' => $pub['id'],
                    'title' => $pub['title'],
                    'abstract' => $pub['abstract'] ?? null,
                    'publication_type' => $pub['publication_type'],
                    'source' => $pub['source'],
                    'publication_year' => $pub['publication_year'],
                    'publication_year_be' => $pub['publication_year'] ? (int)$pub['publication_year'] + 543 : null,
                    'publication_month' => $pub['publication_month'],
                    'volume' => $pub['volume'] ?? null,
                    'pages' => $pub['pages'] ?? null,
                    'doi' => $pub['doi'] ?? null,
                    'isbn' => $pub['isbn'] ?? null,
                    'keywords' => $pub['keywords'] ?? null,
                    'authors' => $pub['authors'] ?? $pub['authors_names_thai'] ?? $pub['authors_names_en'] ?? null,
                    'authors_thai' => $pub['authors_names_thai'] ?? null,
                    'authors_english' => $pub['authors_names_en'] ?? null,
                    'created_at' => $pub['created_at']
                ];
            }, $publications);

            return $this->response->setJSON([
                'success' => true,
                'teacher' => [
                    'uid' => $user['uid'],
                    'email' => $user['email'],
                    'name_thai' => !empty(trim($thaiFullName)) ? $thaiFullName : $engFullName,
                    'name_english' => $engFullName,
                    'faculty' => $faculty['name'] ?? null,
                    'curriculum' => $curriculum['name'] ?? null
                ],
                'publications' => $formattedPublications,
                'total' => count($formattedPublications),
                'retrieved_at' => date('Y-m-d H:i:s')
            ]);

        } catch (\Exception $e) {
            log_message('error', 'ApiController::apiGetPublicationsByEmail error: ' . $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON([
                'success' => false,
                'error' => 'SERVER_ERROR',
                'message' => 'An error occurred while processing the request'
            ]);
        }
    }

    /**
     * API: Search teachers
     * Route: GET /api/public/search-teachers
     */
    public function apiSearchTeachers()
    {
        // Set CORS headers
        $this->response->setHeader('Access-Control-Allow-Origin', '*');
        $this->response->setHeader('Access-Control-Allow-Methods', 'GET, OPTIONS');
        $this->response->setHeader('Access-Control-Allow-Headers', 'Content-Type');

        if ($this->request->getMethod() === 'options') {
            return $this->response->setStatusCode(200);
        }

        try {
            $search = $this->request->getGet('q');
            $limit = (int)($this->request->getGet('limit') ?? 20);
            $limit = min($limit, 100);

            if (empty($search) || strlen($search) < 2) {
                return $this->response->setStatusCode(400)->setJSON([
                    'success' => false,
                    'error' => 'SEARCH_TOO_SHORT',
                    'message' => 'Search query must be at least 2 characters'
                ]);
            }

            $builder = $this->db->table('user u');
            $builder->select('
                u.uid,
                u.email,
                u.title,
                u.titleThai,
                u.gf_name,
                u.gl_name,
                u.thai_name,
                u.thai_lastname,
                f.name as faculty_name,
                c.name as curriculum_name
            ')
            ->join('curriculum c', 'u.curriculum_id = c.id', 'left')
            ->join('faculties f', 'c.faculty_id = f.id', 'left')
            ->where('u.active', 1)
            ->where('u.user_type', 'TEACHER')
            ->groupStart()
                ->like('u.thai_name', $search)
                ->orLike('u.thai_lastname', $search)
                ->orLike('u.gf_name', $search)
                ->orLike('u.gl_name', $search)
            ->groupEnd()
            ->orderBy('u.thai_name', 'ASC')
            ->limit($limit);

            $teachers = $builder->get()->getResultArray();

            $formattedTeachers = array_map(function($teacher) {
                $thaiFullName = trim(($teacher['titleThai'] ?? '') . ' ' . ($teacher['thai_name'] ?? '') . ' ' . ($teacher['thai_lastname'] ?? ''));
                $engFullName = trim(($teacher['title'] ?? '') . ' ' . ($teacher['gf_name'] ?? '') . ' ' . ($teacher['gl_name'] ?? ''));
                
                return [
                    'uid' => $teacher['uid'],
                    'email' => $teacher['email'],
                    'name_thai' => !empty(trim($thaiFullName)) ? $thaiFullName : $engFullName,
                    'name_english' => $engFullName,
                    'faculty' => $teacher['faculty_name'] ?? null,
                    'curriculum' => $teacher['curriculum_name'] ?? null
                ];
            }, $teachers);

            return $this->response->setJSON([
                'success' => true,
                'data' => $formattedTeachers,
                'total' => count($formattedTeachers)
            ]);

        } catch (\Exception $e) {
            log_message('error', 'ApiController::apiSearchTeachers error: ' . $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON([
                'success' => false,
                'error' => 'SERVER_ERROR',
                'message' => 'An error occurred while processing the request'
            ]);
        }
    }

    /**
     * API: Get personnel for a specific faculty
     * Route: GET /api/public/faculty-personnel
     */
    public function apiGetFacultyPersonnel()
    {
        // Set CORS headers
        $this->response->setHeader('Access-Control-Allow-Origin', '*');
        $this->response->setHeader('Access-Control-Allow-Methods', 'GET, OPTIONS');
        $this->response->setHeader('Access-Control-Allow-Headers', 'Content-Type');

        if ($this->request->getMethod() === 'options') {
            return $this->response->setStatusCode(200);
        }

        try {
            $facultyId = $this->request->getGet('faculty_id');
            $facultyCode = $this->request->getGet('faculty_code');

            if (empty($facultyId) && empty($facultyCode)) {
                return $this->response->setStatusCode(400)->setJSON([
                    'success' => false,
                    'error' => 'MISSING_PARAMETER',
                    'message' => 'faculty_id or faculty_code parameter is required'
                ]);
            }

            // Find faculty
            $faculty = null;
            if (!empty($facultyId)) {
                $faculty = $this->facultyModel->find($facultyId);
            } else {
                $faculty = $this->facultyModel->where('code', $facultyCode)->first();
            }

            if (!$faculty) {
                return $this->response->setStatusCode(404)->setJSON([
                    'success' => false,
                    'error' => 'FACULTY_NOT_FOUND',
                    'message' => 'Faculty not found'
                ]);
            }

            $facultyId = $faculty['id'];

            // Get all curricula for this faculty to identify chairs
            $curricula = $this->curriculumModel->where('faculty_id', $facultyId)->where('status', 1)->findAll();
            $chairsMap = [];
            foreach ($curricula as $cur) {
                if (!empty($cur['chair_id'])) {
                    if (!isset($chairsMap[$cur['chair_id']])) {
                        $chairsMap[$cur['chair_id']] = [];
                    }
                    $chairsMap[$cur['chair_id']][] = $cur['name'];
                }
            }

            $builder = $this->db->table('user u');
            $builder->select('u.*, c.name as curriculum_name')
                ->join('curriculum c', 'u.curriculum_id = c.id', 'left')
                ->where('u.active', 1)
                ->where('u.user_type', 'TEACHER')
                ->groupStart()
                    ->where('u.faculty_id', $facultyId)
                    ->orWhere('c.faculty_id', $facultyId)
                ->groupEnd()
                ->orderBy('u.thai_name', 'ASC');

            $teachers = $builder->get()->getResultArray();

            $personnel = [];
            foreach ($teachers as $teacher) {
                $positions = [];
                
                if ($teacher['uid'] == $faculty['dean_id']) {
                    $positions[] = 'คณบดี' . (empty($faculty['name']) ? '' : $faculty['name']);
                }

                if (isset($chairsMap[$teacher['uid']])) {
                    foreach ($chairsMap[$teacher['uid']] as $curName) {
                        $positions[] = 'ประธานหลักสูตร' . $curName;
                    }
                }

                if (empty($positions)) {
                    $positions[] = 'อาจารย์';
                }

                $thaiFullName = trim(($teacher['titleThai'] ?? '') . ' ' . ($teacher['thai_name'] ?? '') . ' ' . ($teacher['thai_lastname'] ?? ''));
                $engFullName = trim(($teacher['title'] ?? '') . ' ' . ($teacher['gf_name'] ?? '') . ' ' . ($teacher['gl_name'] ?? ''));

                $personnel[] = [
                    'uid' => $teacher['uid'],
                    'email' => $teacher['email'],
                    'name_thai' => !empty(trim($thaiFullName)) ? $thaiFullName : $engFullName,
                    'name_english' => $engFullName,
                    'profile_picture' => str_replace('https:', 'http:', $teacher['profile_picture'] ?? ''),
                    'curriculum' => $teacher['curriculum_name'] ?? null,
                    'positions' => $positions,
                    'primary_position' => $positions[0]
                ];
            }

            return $this->response->setJSON([
                'success' => true,
                'faculty' => [
                    'id' => $faculty['id'],
                    'name' => $faculty['name'],
                    'code' => $faculty['code']
                ],
                'personnel' => $personnel,
                'total' => count($personnel),
                'retrieved_at' => date('Y-m-d H:i:s')
            ]);

        } catch (\Exception $e) {
            log_message('error', 'ApiController::apiGetFacultyPersonnel error: ' . $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON([
                'success' => false,
                'error' => 'SERVER_ERROR',
                'message' => 'An error occurred while processing the request'
            ]);
        }
    }
}
