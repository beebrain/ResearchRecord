<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use App\Models\UserModel;
use App\Models\FacultyModel;
use App\Models\CurriculumModel;
use App\Models\PublicationModel;
use App\Helpers\RoleHelper;
use App\Libraries\UserIdentity;

/**
 * AdminDashboardController
 * 
 * จัดการ API สำหรับ Admin Dashboard
 * - แสดงสถิติข้อมูลสรุปแบบแยกตาม Role (Super Admin / Faculty Admin)
 * - แสดงข้อมูล Charts และ Tables
 */
class AdminDashboardController extends Controller
{
    protected $session;
    protected $userModel;
    protected $facultyModel;
    protected $curriculumModel;
    protected $publicationModel;
    protected $db;

    public function __construct()
    {
        $this->session = session();
        $this->userModel = new UserModel();
        $this->facultyModel = new FacultyModel();
        $this->curriculumModel = new CurriculumModel();
        $this->publicationModel = new PublicationModel();
        $this->db = \Config\Database::connect();
    }

    /**
     * Get current user and determine role/faculty scope
     */
    private function getUserContext()
    {
        $email = UserIdentity::sessionEmail();
        if ($email === '') {
            $userData = $this->session->get('user_data') ?? [];
            $email    = UserIdentity::normalizeEmail((string) ($userData['email'] ?? ''));
        }

        if ($email === '') {
            return null;
        }

        $user = $this->userModel->find($email);
        if (!$user) {
            return null;
        }

        $isSuperAdmin = $this->session->get('god_mode') === true || RoleHelper::isSuperAdmin($user);
        $isFacultyAdmin = RoleHelper::isFacultyAdmin($user);
        $facultyIds = [];

        if (!$isSuperAdmin) {
            if ($isFacultyAdmin) {
                $facultyIds = RoleHelper::getManagedFaculties($user);
            } elseif (RoleHelper::isDean($user)) {
                $facultyIds = RoleHelper::getDeanFaculties($user);
            } elseif (RoleHelper::isChair($user)) {
                $facultyIds = RoleHelper::getChairFaculties($user);
            }
        }

        return [
            'user' => $user,
            'userEmail' => $email,
            'userId' => $email,
            'isSuperAdmin' => $isSuperAdmin,
            'isFacultyAdmin' => $isFacultyAdmin,
            'facultyIds' => $facultyIds
        ];
    }

    /**
     * API: Get All Dashboard Statistics
     * Consolidated endpoint for all dashboard data
     */
    public function getStatistics()
    {
        try {
            $context = $this->getUserContext();
            if (!$context) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'User not authenticated'
                ]);
            }

            $facultyIds = $context['facultyIds'];
            $isSuperAdmin = $context['isSuperAdmin'];

            // Get all statistics
            $stats = $this->getBasicStats($facultyIds);
            $curriculaStats = $this->getCurriculaStats($facultyIds);
            $admissionStats = $this->getAdmissionStats($facultyIds);
            $educationStats = $this->getEducationStats($facultyIds);
            $publicationsThisYear = $this->getPublicationsThisYear($facultyIds);
            $activeFaculties = $isSuperAdmin ? $this->getActiveFacultiesCount() : count($facultyIds);

            $data = [
                'totalPublications' => $stats['total_publications'],
                'totalAuthors' => $stats['total_authors'],
                'activeFaculties' => $activeFaculties,
                'isSuperAdmin' => $isSuperAdmin,
                'curricula' => $curriculaStats,
                'admissionForms' => $admissionStats,
                'education' => $educationStats,
                'publicationsThisYear' => $publicationsThisYear
            ];

            return $this->response->setJSON([
                'success' => true,
                'data' => $data
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Dashboard getStatistics error: ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Error loading statistics'
            ]);
        }
    }

    /**
     * API: Get Dashboard Summary (Faculty Table, Recent Publications)
     */
    public function getSummary()
    {
        try {
            $context = $this->getUserContext();
            if (!$context) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'User not authenticated'
                ]);
            }

            $facultyIds = $context['facultyIds'];
            $isSuperAdmin = $context['isSuperAdmin'];

            $data = [
                'isSuperAdmin' => $isSuperAdmin,
                'facultySummary' => $this->getFacultySummary($facultyIds),
                'publicationTypes' => $this->getPublicationTypeStats($facultyIds),
                'recentPublications' => $this->getRecentPublications($facultyIds, 10)
            ];

            return $this->response->setJSON([
                'success' => true,
                'data' => $data
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Dashboard getSummary error: ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Error loading summary'
            ]);
        }
    }

    /**
     * API: Get Publication Types Distribution
     */
    public function getPublicationTypes()
    {
        try {
            $context = $this->getUserContext();
            if (!$context) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'User not authenticated'
                ]);
            }

            $data = $this->getPublicationTypeStats($context['facultyIds']);

            return $this->response->setJSON([
                'success' => true,
                'data' => $data
            ]);
        } catch (\Exception $e) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Error loading publication types'
            ]);
        }
    }

    /**
     * API: Get Admission Form Statistics
     */
    public function getAdmissionFormStats()
    {
        try {
            $context = $this->getUserContext();
            if (!$context) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'User not authenticated'
                ]);
            }

            $data = $this->getAdmissionStats($context['facultyIds']);

            return $this->response->setJSON([
                'success' => true,
                'data' => $data
            ]);
        } catch (\Exception $e) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Error loading admission stats'
            ]);
        }
    }

    /**
     * API: Get Education Statistics
     */
    public function getEducationStatsApi()
    {
        try {
            $context = $this->getUserContext();
            if (!$context) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'User not authenticated'
                ]);
            }

            $data = $this->getEducationStats($context['facultyIds']);

            return $this->response->setJSON([
                'success' => true,
                'data' => $data
            ]);
        } catch (\Exception $e) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Error loading education stats'
            ]);
        }
    }

    /**
     * API: Get Faculty Summary Table
     */
    public function getFacultySummaryTable()
    {
        try {
            $context = $this->getUserContext();
            if (!$context) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'User not authenticated'
                ]);
            }

            $data = $this->getFacultySummary($context['facultyIds']);

            return $this->response->setJSON([
                'success' => true,
                'data' => $data
            ]);
        } catch (\Exception $e) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Error loading faculty summary'
            ]);
        }
    }

    /**
     * API: Get Recent Publications
     */
    public function getRecentPublicationsApi()
    {
        try {
            $context = $this->getUserContext();
            if (!$context) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'User not authenticated'
                ]);
            }

            $limit = $this->request->getGet('limit') ?? 10;
            $data = $this->getRecentPublications($context['facultyIds'], (int)$limit);

            return $this->response->setJSON([
                'success' => true,
                'data' => $data
            ]);
        } catch (\Exception $e) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Error loading recent publications'
            ]);
        }
    }

    /**
     * API: Get Curriculum Readiness for Student Admission
     * ความพร้อมของหลักสูตรในการเปิดรับนักศึกษา
     */
    public function getCurriculumReadiness()
    {
        try {
            $context = $this->getUserContext();
            if (!$context) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'User not authenticated'
                ]);
            }

            $academicYear = $this->request->getGet('academic_year') ?? date('Y') + 543; // Default to current Thai year
            $data = $this->getCurriculumReadinessData($context['facultyIds'], (int)$academicYear);

            return $this->response->setJSON([
                'success' => true,
                'data' => $data
            ]);
        } catch (\Exception $e) {
            log_message('error', 'getCurriculumReadiness error: ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Error loading curriculum readiness'
            ]);
        }
    }

    /**
     * API: Get Curriculum Publication Summary
     * สรุปผลงานวิจัยตามหลักสูตร ในรอบ 5 ปี
     */
    public function getCurriculumPublications()
    {
        try {
            $context = $this->getUserContext();
            if (!$context) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'User not authenticated'
                ]);
            }

            $data = $this->getCurriculumPublicationData($context['facultyIds']);

            return $this->response->setJSON([
                'success' => true,
                'data' => $data
            ]);
        } catch (\Exception $e) {
            log_message('error', 'getCurriculumPublications error: ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Error loading curriculum publications'
            ]);
        }
    }

    /**
     * Get Curriculum Publication Data (5 years)
     */
    private function getCurriculumPublicationData(array $facultyIds = [])
    {
        $currentYear = (int)date('Y');
        $startYear = $currentYear - 4;

        // Get curricula with publication counts per year
        $builder = $this->db->table('curriculum c');
        $builder->select("
            c.id as curriculum_id,
            c.name as curriculum_name,
            c.code as curriculum_code,
            c.degree_level,
            f.id as faculty_id,
            f.name as faculty_name,
            f.code as faculty_code,
            (SELECT COUNT(DISTINCT tc2.teacher_email) 
             FROM teacher_curriculum tc2 
             WHERE tc2.curriculum_id = c.id AND tc2.status = 1) as teacher_count
        ");
        $builder->join('faculties f', 'f.id = c.faculty_id', 'inner');
        $builder->where('c.status', 1);
        $builder->where('f.status', 1);

        if (!empty($facultyIds)) {
            $builder->whereIn('c.faculty_id', $facultyIds);
        }

        $builder->orderBy('f.name', 'ASC');
        $builder->orderBy('c.name', 'ASC');

        $curricula = $builder->get()->getResultArray();

        // Get publications per curriculum per year
        $result = [];
        foreach ($curricula as $curriculum) {
            $yearlyData = $this->getCurriculumYearlyPublications($curriculum['curriculum_id'], $startYear, $currentYear);

            $curriculum['publications'] = $yearlyData['yearly'];
            $curriculum['total_publications'] = $yearlyData['total'];
            $curriculum['avg_per_year'] = $yearlyData['total'] > 0 ? round($yearlyData['total'] / 5, 1) : 0;

            // Calculate trend (comparing last 2 years)
            $lastYear = $yearlyData['yearly'][$currentYear] ?? 0;
            $prevYear = $yearlyData['yearly'][$currentYear - 1] ?? 0;
            $curriculum['trend'] = $lastYear - $prevYear;
            $curriculum['trend_percentage'] = $prevYear > 0 ? round((($lastYear - $prevYear) / $prevYear) * 100, 1) : 0;

            $result[] = $curriculum;
        }

        // Sort by total publications (descending)
        usort($result, function ($a, $b) {
            return $b['total_publications'] - $a['total_publications'];
        });

        // Get summary
        $summary = $this->getCurriculumPublicationSummary($result, $startYear, $currentYear);

        return [
            'curricula' => $result,
            'summary' => $summary,
            'years' => range($startYear, $currentYear)
        ];
    }

    /**
     * Get yearly publications for a curriculum
     * Uses publication_view with author_curriculum_ids for accurate counts
     */
    private function getCurriculumYearlyPublications(int $curriculumId, int $startYear, int $endYear)
    {
        $yearly = [];
        $total = 0;

        for ($year = $startYear; $year <= $endYear; $year++) {
            $yearly[$year] = 0;
        }

        // Query publications from publication_view where curriculum_id appears in author_curriculum_ids
        // Use FIND_IN_SET to check if curriculum_id is in the comma-separated list
        $query = $this->db->query("
            SELECT publication_year, COUNT(DISTINCT id) as count
            FROM publication_view
            WHERE FIND_IN_SET(?, author_curriculum_ids) > 0
              AND publication_year BETWEEN ? AND ?
              AND publication_year IS NOT NULL
            GROUP BY publication_year
        ", [$curriculumId, $startYear, $endYear]);

        $results = $query->getResultArray();

        foreach ($results as $row) {
            $year = (int)$row['publication_year'];
            $count = (int)$row['count'];
            if (isset($yearly[$year])) {
                $yearly[$year] = $count;
                $total += $count;
            }
        }

        return [
            'yearly' => $yearly,
            'total' => $total
        ];
    }

    /**
     * Get curriculum publication summary
     */
    private function getCurriculumPublicationSummary(array $curricula, int $startYear, int $endYear)
    {
        $totalCurricula = count($curricula);
        $curriculaWithPubs = 0;
        $totalPublications = 0;
        $yearlyTotals = [];

        for ($year = $startYear; $year <= $endYear; $year++) {
            $yearlyTotals[$year] = 0;
        }

        foreach ($curricula as $curriculum) {
            $totalPublications += $curriculum['total_publications'];
            if ($curriculum['total_publications'] > 0) {
                $curriculaWithPubs++;
            }
            foreach ($curriculum['publications'] as $year => $count) {
                if (isset($yearlyTotals[$year])) {
                    $yearlyTotals[$year] += $count;
                }
            }
        }

        $avgPerCurriculum = $totalCurricula > 0 ? round($totalPublications / $totalCurricula, 1) : 0;

        return [
            'total_curricula' => $totalCurricula,
            'curricula_with_publications' => $curriculaWithPubs,
            'curricula_without_publications' => $totalCurricula - $curriculaWithPubs,
            'total_publications' => $totalPublications,
            'average_per_curriculum' => $avgPerCurriculum,
            'yearly_totals' => $yearlyTotals,
            'coverage_percentage' => $totalCurricula > 0 ? round(($curriculaWithPubs / $totalCurricula) * 100, 1) : 0
        ];
    }

    /**
     * Get Curriculum Readiness Data
     */
    private function getCurriculumReadinessData(array $facultyIds = [], int $academicYear = 0)
    {
        if ($academicYear === 0) {
            $academicYear = date('Y') + 543;
        }

        $builder = $this->db->table('student_admission_forms saf');
        $builder->select("
            saf.id,
            saf.curriculum_id,
            saf.faculty_id,
            saf.curriculum_name,
            saf.academic_year,
            saf.teachers_status,
            saf.status,
            saf.admission_plan_count,
            saf.ministry_approval_date,
            saf.university_approval_date,
            saf.quality_assessment_result1,
            saf.quality_assessment_result2,
            saf.curriculum_head_approval_date,
            saf.dean_approval_date,
            c.name as curriculum_official_name,
            c.degree_level,
            f.name as faculty_name,
            f.code as faculty_code
        ");
        $builder->join('curriculum c', 'c.id = saf.curriculum_id', 'left');
        $builder->join('faculties f', 'f.id = saf.faculty_id', 'left');
        $builder->where('saf.academic_year', $academicYear);

        if (!empty($facultyIds)) {
            $builder->whereIn('saf.faculty_id', $facultyIds);
        }

        $builder->orderBy('f.name', 'ASC');
        $builder->orderBy('c.name', 'ASC');

        $results = $builder->get()->getResultArray();

        // Calculate readiness score and status for each curriculum
        $processedData = [];
        foreach ($results as $row) {
            $readiness = $this->calculateReadinessScore($row);
            $row['readiness_score'] = $readiness['score'];
            $row['readiness_status'] = $readiness['status'];
            $row['readiness_details'] = $readiness['details'];
            $processedData[] = $row;
        }

        // Get summary statistics
        $summary = $this->getReadinessSummary($processedData);

        return [
            'academic_year' => $academicYear,
            'curricula' => $processedData,
            'summary' => $summary
        ];
    }

    /**
     * Calculate readiness score for a curriculum
     */
    private function calculateReadinessScore(array $data)
    {
        $score = 0;
        $maxScore = 100;
        $details = [];

        // 1. Form Status (20 points)
        $statusPoints = 0;
        switch ($data['status']) {
            case 'approved':
                $statusPoints = 20;
                $details['form_status'] = ['status' => 'success', 'text' => 'อนุมัติแล้ว'];
                break;
            case 'submitted':
                $statusPoints = 15;
                $details['form_status'] = ['status' => 'warning', 'text' => 'รอพิจารณา'];
                break;
            case 'draft':
                $statusPoints = 5;
                $details['form_status'] = ['status' => 'info', 'text' => 'ร่าง'];
                break;
            case 'rejected':
                $statusPoints = 0;
                $details['form_status'] = ['status' => 'error', 'text' => 'ถูกปฏิเสธ'];
                break;
            default:
                $details['form_status'] = ['status' => 'info', 'text' => 'ไม่ระบุ'];
        }
        $score += $statusPoints;

        // 2. Teachers Status (20 points)
        if ($data['teachers_status'] === 'complete') {
            $score += 20;
            $details['teachers'] = ['status' => 'success', 'text' => 'ครบถ้วน'];
        } elseif ($data['teachers_status'] === 'incomplete') {
            $score += 10;
            $details['teachers'] = ['status' => 'warning', 'text' => 'ไม่ครบ'];
        } else {
            $details['teachers'] = ['status' => 'info', 'text' => 'ยังไม่ระบุ'];
        }

        // 3. Ministry Approval (15 points)
        if (!empty($data['ministry_approval_date'])) {
            $score += 15;
            $details['ministry'] = ['status' => 'success', 'text' => 'มีแล้ว'];
        } else {
            $details['ministry'] = ['status' => 'warning', 'text' => 'ยังไม่มี'];
        }

        // 4. University Approval (15 points)
        if (!empty($data['university_approval_date'])) {
            $score += 15;
            $details['university'] = ['status' => 'success', 'text' => 'มีแล้ว'];
        } else {
            $details['university'] = ['status' => 'warning', 'text' => 'ยังไม่มี'];
        }

        // 5. Quality Assessment (15 points)
        if (!empty($data['quality_assessment_result1']) || !empty($data['quality_assessment_result2'])) {
            $score += 15;
            $details['quality'] = ['status' => 'success', 'text' => 'มีผลประเมิน'];
        } else {
            $details['quality'] = ['status' => 'warning', 'text' => 'ยังไม่มี'];
        }

        // 6. Admission Plan (15 points)
        if (!empty($data['admission_plan_count']) && $data['admission_plan_count'] > 0) {
            $score += 15;
            $details['plan'] = ['status' => 'success', 'text' => 'มีแผนรับ ' . $data['admission_plan_count'] . ' คน'];
        } else {
            $details['plan'] = ['status' => 'warning', 'text' => 'ยังไม่ระบุ'];
        }

        // Determine overall status
        $status = 'not_ready';
        if ($score >= 80) {
            $status = 'ready';
        } elseif ($score >= 50) {
            $status = 'partial';
        }

        return [
            'score' => $score,
            'max_score' => $maxScore,
            'status' => $status,
            'details' => $details
        ];
    }

    /**
     * Get readiness summary statistics
     */
    private function getReadinessSummary(array $data)
    {
        $total = count($data);
        $ready = 0;
        $partial = 0;
        $notReady = 0;
        $avgScore = 0;

        foreach ($data as $item) {
            $avgScore += $item['readiness_score'];
            switch ($item['readiness_status']) {
                case 'ready':
                    $ready++;
                    break;
                case 'partial':
                    $partial++;
                    break;
                default:
                    $notReady++;
            }
        }

        return [
            'total' => $total,
            'ready' => $ready,
            'partial' => $partial,
            'not_ready' => $notReady,
            'average_score' => $total > 0 ? round($avgScore / $total, 1) : 0,
            'ready_percentage' => $total > 0 ? round(($ready / $total) * 100, 1) : 0
        ];
    }

    // ============================================================
    // PRIVATE HELPER METHODS
    // ============================================================

    /**
     * Get basic statistics (publications & authors)
     * Uses publication_view for accurate counts (same as AdminController)
     */
    private function getBasicStats(array $facultyIds = [])
    {
        // Count total publications from publication_view (same logic as AdminController)
        if (empty($facultyIds)) {
            // Super Admin: Count all publications from view
            $totalPublications = $this->db->table('publication_view')->countAll();
        } else {
            // Faculty Admin: Filter by faculty IDs using publication_view
            $totalPublications = $this->db->table('publication_view')
                ->whereIn('faculty_id', $facultyIds)
                ->countAllResults();
        }

        // Count total unique authors (same logic as AdminController)
        if (empty($facultyIds)) {
            // Super Admin: Count all authors
            $authorQuery = $this->db->query("
                SELECT COUNT(DISTINCT u.email) as count
                FROM user u
                INNER JOIN teacher_curriculum tc ON tc.teacher_email = u.email AND tc.status = 1
                INNER JOIN publication_authors pa ON (
                    pa.author_email = u.email
                    OR EXISTS (
                        SELECT 1 FROM authors a 
                        WHERE a.id = pa.author_id AND a.user_email = u.email
                    )
                )
                WHERE u.active = 1
            ");
        } else {
            // Faculty Admin: Filter by managed faculties (same as AdminController::getStatsByFaculties)
            $facultyIdsStr = implode(',', array_map('intval', $facultyIds));
            $authorQuery = $this->db->query("
                SELECT COUNT(DISTINCT u.email) as count
                FROM user u
                INNER JOIN teacher_curriculum tc ON tc.teacher_email = u.email AND tc.status = 1
                INNER JOIN curriculum c ON c.id = tc.curriculum_id AND c.faculty_id IN ({$facultyIdsStr})
                INNER JOIN publication_authors pa ON (
                    pa.author_email = u.email
                    OR EXISTS (
                        SELECT 1 FROM authors a 
                        WHERE a.id = pa.author_id AND a.user_email = u.email
                    )
                )
                INNER JOIN publication_view pv ON pv.id = pa.publication_id AND pv.faculty_id IN ({$facultyIdsStr})
                WHERE u.active = 1
            ");
        }

        $totalAuthors = $authorQuery->getRowArray()['count'] ?? 0;

        // Debug logging
        log_message('debug', "AdminDashboard - Publications count: {$totalPublications}, Authors count: {$totalAuthors}");

        return [
            'total_publications' => $totalPublications,
            'total_authors' => $totalAuthors
        ];
    }

    /**
     * Get active faculties count
     */
    private function getActiveFacultiesCount()
    {
        return $this->facultyModel->where('status', 1)->countAllResults();
    }

    /**
     * Get curricula statistics by degree level
     */
    private function getCurriculaStats(array $facultyIds = [])
    {
        $builder = $this->db->table('curriculum');
        $builder->select('degree_level, COUNT(*) as count');
        $builder->where('status', 1);

        if (!empty($facultyIds)) {
            $builder->whereIn('faculty_id', $facultyIds);
        }

        $builder->groupBy('degree_level');
        $results = $builder->get()->getResultArray();

        $stats = [
            'total' => 0,
            'bachelor' => 0,
            'master' => 0,
            'doctoral' => 0
        ];

        foreach ($results as $row) {
            $level = $row['degree_level'] ?? '';
            $count = (int)$row['count'];
            if (isset($stats[$level])) {
                $stats[$level] = $count;
            }
            $stats['total'] += $count;
        }

        return $stats;
    }

    /**
     * Get admission form statistics by status
     */
    private function getAdmissionStats(array $facultyIds = [])
    {
        $builder = $this->db->table('student_admission_forms');
        $builder->select('status, COUNT(*) as count');

        if (!empty($facultyIds)) {
            $builder->whereIn('faculty_id', $facultyIds);
        }

        $builder->groupBy('status');
        $results = $builder->get()->getResultArray();

        $stats = [
            'total' => 0,
            'draft' => 0,
            'submitted' => 0,
            'approved' => 0,
            'rejected' => 0
        ];

        foreach ($results as $row) {
            $status = $row['status'] ?? 'draft';
            $count = (int)$row['count'];
            if (isset($stats[$status])) {
                $stats[$status] = $count;
            }
            $stats['total'] += $count;
        }

        return $stats;
    }

    /**
     * Get education statistics (users with education history)
     */
    private function getEducationStats(array $facultyIds = [])
    {
        // Count total teachers
        $userBuilder = $this->db->table('user u');
        $userBuilder->select('u.email');
        $userBuilder->join('teacher_curriculum tc', 'tc.teacher_email = u.email AND tc.status = 1', 'inner');
        $userBuilder->where('u.active', 1);
        $userBuilder->distinct();

        if (!empty($facultyIds)) {
            $userBuilder->join('curriculum c', 'c.id = tc.curriculum_id', 'inner');
            $userBuilder->whereIn('c.faculty_id', $facultyIds);
        }

        $totalUsers = $userBuilder->countAllResults(false);

        // Count teachers with education entries
        $eduBuilder = $this->db->table('user u');
        $eduBuilder->select('u.email');
        $eduBuilder->join('teacher_curriculum tc', 'tc.teacher_email = u.email AND tc.status = 1', 'inner');
        $eduBuilder->join('cv_sections cs', "cs.owner_email_norm = u.email AND cs.type = 'education'", 'inner');
        $eduBuilder->join('cv_entries ce', 'ce.section_id = cs.id', 'inner');
        $eduBuilder->where('u.active', 1);
        $eduBuilder->distinct();

        if (!empty($facultyIds)) {
            $eduBuilder->join('curriculum c', 'c.id = tc.curriculum_id', 'inner');
            $eduBuilder->whereIn('c.faculty_id', $facultyIds);
        }

        $usersWithEducation = $eduBuilder->countAllResults();

        $percentage = $totalUsers > 0 ? round(($usersWithEducation / $totalUsers) * 100, 1) : 0;

        return [
            'total_users' => $totalUsers,
            'users_with_education' => $usersWithEducation,
            'percentage' => $percentage
        ];
    }

    /**
     * Get publications count for current year
     * Uses publication_view for consistency
     */
    private function getPublicationsThisYear(array $facultyIds = [])
    {
        $currentYear = date('Y');
        $builder = $this->db->table('publication_view');
        $builder->where('publication_year', $currentYear);

        if (!empty($facultyIds)) {
            $builder->whereIn('faculty_id', $facultyIds);
        }

        return $builder->countAllResults();
    }

    /**
     * Get publication type statistics for pie chart
     * Uses publication_view for consistency
     */
    private function getPublicationTypeStats(array $facultyIds = [])
    {
        $builder = $this->db->table('publication_view');
        $builder->select("COALESCE(NULLIF(publication_type, ''), 'other') as type, COUNT(*) as count");

        if (!empty($facultyIds)) {
            $builder->whereIn('faculty_id', $facultyIds);
        }

        $builder->groupBy('type');
        $builder->orderBy('count', 'DESC');

        $results = $builder->get()->getResultArray();

        // Format for chart
        $labels = [];
        $data = [];
        $typeNames = [
            'journal' => 'Journal Article',
            'book' => 'Book/Book Chapter',
            'proceedings' => 'Conference Proceedings',
            'thesis' => 'Thesis/Dissertation',
            'report' => 'Report',
            'other' => 'Other'
        ];

        foreach ($results as $row) {
            $type = $row['type'] ?? 'other';
            $labels[] = $typeNames[$type] ?? ucfirst($type);
            $data[] = (int)$row['count'];
        }

        return [
            'labels' => $labels,
            'data' => $data,
            'raw' => $results
        ];
    }

    /**
     * Get faculty summary for dashboard table
     * Uses publication_view for accurate publication counts
     */
    private function getFacultySummary(array $facultyIds = [])
    {
        $builder = $this->db->table('faculties f');
        $builder->select("
            f.id,
            f.name,
            f.code,
            (SELECT COUNT(*) FROM curriculum c WHERE c.faculty_id = f.id AND c.status = 1) as curricula_count,
            (SELECT COUNT(DISTINCT tc.teacher_email) FROM teacher_curriculum tc 
             INNER JOIN curriculum c ON c.id = tc.curriculum_id 
             WHERE c.faculty_id = f.id AND tc.status = 1) as teachers_count,
            (SELECT COUNT(DISTINCT pv.id) FROM publication_view pv 
             WHERE pv.faculty_id = f.id) as publications_count,
            (SELECT COUNT(*) FROM student_admission_forms saf WHERE saf.faculty_id = f.id) as admission_forms_count
        ");
        $builder->where('f.status', 1);

        if (!empty($facultyIds)) {
            $builder->whereIn('f.id', $facultyIds);
        }

        $builder->orderBy('f.name', 'ASC');
        return $builder->get()->getResultArray();
    }

    /**
     * Get recent publications
     * Uses publication_view for consistency
     */
    private function getRecentPublications(array $facultyIds = [], int $limit = 10)
    {
        $builder = $this->db->table('publication_view pv');
        $builder->select("
            pv.id,
            pv.title,
            pv.publication_type,
            pv.publication_year,
            pv.created_at,
            pv.created_by_name,
            pv.created_by_faculty_name as faculty_name,
            '' as curriculum_name
        ");

        if (!empty($facultyIds)) {
            $builder->whereIn('pv.faculty_id', $facultyIds);
        }

        $builder->orderBy('pv.created_at', 'DESC');
        $builder->limit($limit);

        return $builder->get()->getResultArray();
    }

    /**
     * Dashboard API - Get Publications List
     * Filters publications based on user role
     */
    public function getDashboardPublications()
    {
        try {
            $limit = $this->request->getGet('limit') ?? 1000; // Default to 1000 for DataTables

            $userData = $this->session->get('user_data') ?? [];
            $userEmail = UserIdentity::sessionEmail();
            if ($userEmail === '') {
                $userEmail = UserIdentity::normalizeEmail((string) ($userData['email'] ?? ''));
            }

            log_message('debug', 'getDashboardPublications - user_data: ' . json_encode($userData));

            if ($userEmail === '') {
                log_message('error', 'getDashboardPublications - No user email in session');
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'User not authenticated'
                ]);
            }

            log_message('debug', 'getDashboardPublications - userEmail: ' . $userEmail);

            // filter_user_id may be email (new PK) or legacy numeric id from old clients
            $filterUserId = $this->request->getGet('filter_user_id');
        if (!empty($filterUserId)) {
            $filterKey = (string) $filterUserId;
            if (str_contains($filterKey, '@')) {
                $targetEmail = UserIdentity::normalizeEmail($filterKey);
            } else {
                $targetUser  = $this->userModel->find($filterKey);
                $targetEmail = UserIdentity::normalizeEmail((string) ($targetUser['email'] ?? ''));
            }

            log_message('debug', 'getDashboardPublications - Filtering by author email: ' . $targetEmail);

            $publications = $this->publicationModel->getPublicationsByEmail($targetEmail, $limit);

            log_message('debug', 'getDashboardPublications - Returning ' . count($publications) . ' publications where user is author by email: ' . $targetEmail);
            return $this->response->setJSON([
                'success' => true,
                'data' => $this->publicationModel->attachAuthorsList($publications)
            ]);
        }

            // Get user from database
            $user = $this->userModel->find($userEmail);
            if (!$user) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'User not found'
                ]);
            }

            // Check user role - check both role field and is_admin flag
            $userRole = $user['role'] ?? null;
            $isAdmin = ($user['admin'] ?? 0) == 1 || ($user['is_admin'] ?? 0) == 1;
            $isGodMode = $this->session->get('god_mode') === true;

            $isSuperAdmin = $isGodMode || ($userRole === 'super_admin') || ($isAdmin && $userRole === null);
            $isFacultyAdmin = ($userRole === 'faculty_admin');
            $isDean = RoleHelper::isDean($user);
            $isChair = RoleHelper::isChair($user);
            $isRegularUser = !$isSuperAdmin && !$isFacultyAdmin && !$isDean && !$isChair;

            // Log for debugging
            log_message('debug', 'getDashboardPublications - User email: ' . $userEmail);
            log_message('debug', 'getDashboardPublications - Role: ' . ($userRole ?? 'null'));
            log_message('debug', 'getDashboardPublications - isAdmin: ' . ($isAdmin ? 'true' : 'false'));
            log_message('debug', 'getDashboardPublications - isSuperAdmin: ' . ($isSuperAdmin ? 'true' : 'false'));
            log_message('debug', 'getDashboardPublications - isFacultyAdmin: ' . ($isFacultyAdmin ? 'true' : 'false'));
            log_message('debug', 'getDashboardPublications - isDean: ' . ($isDean ? 'true' : 'false'));
            log_message('debug', 'getDashboardPublications - isChair: ' . ($isChair ? 'true' : 'false'));
            log_message('debug', 'getDashboardPublications - isRegularUser: ' . ($isRegularUser ? 'true' : 'false'));

            // Get publications based on role
            // IMPORTANT: Regular users ALWAYS see only their own publications (by author_email)
            if ($isRegularUser) {
                // Regular users see only publications where they are authors (using author_email match)
                log_message('debug', 'getDashboardPublications - Loading user publications by email for user: ' . $userEmail);
                $publications = $this->publicationModel->getPublicationsByEmail($user['email'] ?? '', $limit);
            } elseif ($isSuperAdmin) {
                // Super admin sees all publications.
                // Use the lightweight base-table query (not the heavy publication_view)
                // since author chips are attached separately via attachAuthorsList().
                log_message('debug', 'getDashboardPublications - Loading all publications (Super Admin, light)');
                $publications = $this->publicationModel->getAllPublicationsLight((int) $limit);
            } elseif ($isFacultyAdmin) {
                // Faculty admin sees only publications from their managed faculties
                $managedFaculties = RoleHelper::getManagedFaculties($user);
                log_message('debug', 'getDashboardPublications - Loading faculty publications (Faculty Admin)');
                $publications = $this->publicationModel->getPublicationsByFaculties($managedFaculties, $limit);
            } elseif ($isDean) {
                // Dean sees publications from their faculty
                $deanFaculties = RoleHelper::getDeanFaculties($user);
                log_message('debug', 'getDashboardPublications - Loading dean faculty publications');
                $publications = $this->publicationModel->getPublicationsByFaculties($deanFaculties, $limit);
            } elseif ($isChair) {
                // Chair sees publications from their curriculum's faculty
                $chairFaculties = RoleHelper::getChairFaculties($user);
                log_message('debug', 'getDashboardPublications - Loading chair faculty publications');
                $publications = $this->publicationModel->getPublicationsByFaculties($chairFaculties, $limit);
            } else {
                // Fallback: Default to user's own publications by email (safety)
                log_message('debug', 'getDashboardPublications - Fallback: Loading user publications by email');
                $publications = $this->publicationModel->getPublicationsByEmail($user['email'] ?? '', $limit);
            }

            log_message('debug', 'getDashboardPublications - Returning ' . count($publications) . ' publications');

            return $this->response->setJSON([
                'success' => true,
                'data' => $this->publicationModel->attachAuthorsList($publications)
            ]);
        } catch (\Exception $e) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Error loading publications: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Dashboard API - Get Faculty Data
     * Filters faculties based on user role
     */
    public function getFacultyData()
    {
        try {
            // Get current user
            $userId = $this->session->get('user_id');
            $user = $this->userModel->find($userId);

            if (!$user) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'User not found'
                ]);
            }

            // Get faculty data based on role
            if ($this->session->get('god_mode') === true || RoleHelper::isSuperAdmin($user)) {
                // Super admin sees all faculties
                $data = $this->facultyModel->getWithPublicationCount();
            } elseif (RoleHelper::isFacultyAdmin($user)) {
                // Faculty admin sees only their managed faculties
                $managedFaculties = RoleHelper::getManagedFaculties($user);
                if (!empty($managedFaculties)) {
                    $faculties = $this->facultyModel->whereIn('id', $managedFaculties)->findAll();
                    $data = [];
                    // Add publication count for each faculty using publication_view
                    foreach ($faculties as $faculty) {
                        $pubCount = $this->db->table('publication_view')
                            ->where('faculty_id', $faculty['id'])
                            ->countAllResults();

                        $data[] = [
                            'id' => $faculty['id'],
                            'faculty' => $faculty['name'],
                            'code' => $faculty['code'],
                            'count' => $pubCount
                        ];
                    }
                    // Sort by count descending
                    usort($data, function ($a, $b) {
                        return $b['count'] - $a['count'];
                    });
                } else {
                    $data = [];
                }
            } else {
                // Regular users see no faculties (or only their own faculty if needed)
                $data = [];
            }

            return $this->response->setJSON([
                'success' => true,
                'data' => $data
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Get faculty data error: ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Error loading faculty data: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Dashboard API - Get Year Data
     * Filters year data based on user role
     */
    public function getYearData()
    {
        try {
            // Get current user
            $userId = $this->session->get('user_id');
            $user = $this->userModel->find($userId);

            if (!$user) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'User not found'
                ]);
            }

            $db = \Config\Database::connect();
            $builder = $db->table('publication_view');

            // Filter based on role
            if ($this->session->get('god_mode') === true || RoleHelper::isSuperAdmin($user)) {
                // Super admin sees all years
                // No filter needed
            } elseif (RoleHelper::isFacultyAdmin($user)) {
                // Faculty admin sees years for their managed faculties
                $managedFaculties = RoleHelper::getManagedFaculties($user);
                if (!empty($managedFaculties)) {
                    $builder->whereIn('faculty_id', $managedFaculties);
                } else {
                    return $this->response->setJSON([
                        'success' => true,
                        'data' => []
                    ]);
                }
            } else {
                // Regular users see only their own publications (by author_email)
                $userEmail = UserIdentity::normalizeEmail((string) ($user['email'] ?? ''));
                $userEmails = $userEmail !== '' ? [$userEmail] : [];

                $authorEmails = $this->db->table('authors')
                    ->select('email')
                    ->where('user_email', $userEmail)
                    ->where('email IS NOT NULL')
                    ->where('email !=', '')
                    ->get()
                    ->getResultArray();

                foreach ($authorEmails as $authorEmail) {
                    $normalized = UserIdentity::normalizeEmail((string) ($authorEmail['email'] ?? ''));
                    if ($normalized !== '' && !in_array($normalized, $userEmails, true)) {
                        $userEmails[] = $normalized;
                    }
                }

                $publicationIds = $this->db->table('publication_authors pa')
                    ->select('pa.publication_id')
                    ->distinct()
                    ->join('authors a', 'pa.author_id = a.id', 'left');

                if (!empty($userEmails)) {
                    $publicationIds->groupStart()
                        ->whereIn('pa.author_email', $userEmails)
                        ->orWhereIn('a.email', $userEmails)
                        ->orWhereIn('a.user_email', $userEmails)
                        ->groupEnd();
                } else {
                    $publicationIds->where('1=0', null, false);
                }

                $ids = array_column($publicationIds->get()->getResultArray(), 'publication_id');

                if (!empty($ids)) {
                    $builder->groupStart()
                        ->whereIn('id', $ids)
                        ->orWhere('created_by_email', $userEmail)
                        ->groupEnd();
                } else {
                    $builder->where('created_by_email', $userEmail);
                }
            }

            $query = $builder->select('publication_year as year')
                ->select('COUNT(*) as count', false)
                ->where('publication_year IS NOT NULL')
                ->groupBy('publication_year')
                ->orderBy('publication_year', 'ASC')
                ->get();

            $data = $query->getResultArray();

            // Debug logging
            log_message('debug', 'Year Data SQL: ' . $db->getLastQuery());
            log_message('debug', 'Year Data returned ' . count($data) . ' records');

            return $this->response->setJSON([
                'success' => true,
                'data' => $data
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Get year data error: ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Error loading year data: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Dashboard API - Get Curriculum Data
     * Filters curriculum based on user role
     */
    public function getCurriculumData()
    {
        try {
            // Get current user
            $userId = $this->session->get('user_id');
            $user = $this->userModel->find($userId);

            if (!$user) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'User not found'
                ]);
            }

            $facultyFilter = $this->request->getGet('faculty_id');

            // Get curriculum data based on role
            if ($this->session->get('god_mode') === true || RoleHelper::isSuperAdmin($user)) {
                // Super Admin: See all curricula
                log_message('debug', 'getCurriculumData - Super Admin: Loading all curricula');
                $data = $this->curriculumModel->getWithPublicationStats($facultyFilter);
            } elseif (RoleHelper::isFacultyAdmin($user)) {
                // Faculty Admin: See only curricula in managed faculties
                $managedFaculties = RoleHelper::getManagedFaculties($user);
                log_message('debug', 'getCurriculumData - Faculty Admin: Loading curricula for faculties: ' . json_encode($managedFaculties));

                if (!empty($managedFaculties)) {
                    // Filter curricula by managed faculties
                    $allCurricula = $this->curriculumModel->getWithPublicationStats($facultyFilter);

                    // Filter to only show curricula in managed faculties
                    $data = array_filter($allCurricula, function ($curriculum) use ($managedFaculties) {
                        return in_array($curriculum['faculty_id'], $managedFaculties);
                    });

                    // Re-index array
                    $data = array_values($data);
                } else {
                    $data = [];
                }
            } else {
                // Regular User: See only curricula they teach in with publications they authored
                log_message('debug', 'getCurriculumData - Regular User: Loading curricula for user: ' . $userId);

                // Get user's curricula from teacher_curriculum table
                $userCurricula = $this->userModel->getTeacherCurriculums($userId);

                if (!empty($userCurricula)) {
                    $userCurriculumIds = array_column($userCurricula, 'curriculum_id');

                    // Get full curriculum data
                    $allCurricula = $this->curriculumModel->getWithPublicationStats($facultyFilter);

                    // Filter to only show user's curricula
                    $data = array_filter($allCurricula, function ($curriculum) use ($userCurriculumIds) {
                        return in_array($curriculum['id'], $userCurriculumIds);
                    });

                    // For regular users, also filter publications to only count their own
                    // Re-calculate publication counts for their curricula
                    foreach ($data as &$curriculum) {
                        // Count only publications where user is an author
                        $userPublications = $this->publicationModel->getPublicationsByAuthor($userId, 9999);
                        $curriculumPublications = array_filter($userPublications, function ($pub) use ($curriculum) {
                            return strpos($pub['author_curriculum'] ?? '', $curriculum['curriculum_name'] ?? $curriculum['name']) !== false;
                        });

                        $curriculum['publication_count'] = count($curriculumPublications);
                        $curriculum['author_count'] = 1; // Regular user only sees themselves
                    }

                    // Re-index array
                    $data = array_values($data);
                } else {
                    $data = [];
                }
            }

            log_message('debug', 'getCurriculumData - Returning ' . count($data) . ' curricula');

            return $this->response->setJSON([
                'success' => true,
                'data' => $data
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Get curriculum data error: ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Error loading curriculum data: ' . $e->getMessage()
            ]);
        }
    }
    /**
     * Dashboard API - Get Publications
     * Returns publications based on user role
     */
    public function getPublicationsApi()
    {
        try {
            $context = $this->getUserContext();
            if (!$context) {
                return $this->response->setJSON(['success' => false, 'message' => 'User not authenticated']);
            }

            $user = $context['user'];
            $userId = $context['userId'];
            $isSuperAdmin = $context['isSuperAdmin'];
            $isFacultyAdmin = $context['isFacultyAdmin'];
            $facultyIds = $context['facultyIds'];

            $limit = $this->request->getGet('limit') ?? 1000;
            $filterUserId = $this->request->getGet('filter_user_id');

            if ($isSuperAdmin) {
                // Super Admin: See all or filter by specific user
                if ($filterUserId) {
                    $targetUser = $this->userModel->find($filterUserId);
                    $data = $this->publicationModel->getPublicationsByEmail($targetUser['email'] ?? '', (int)$limit);
                } else {
                    $data = $this->publicationModel->getAllPublicationsWithAuthors((int)$limit);
                }
            } elseif ($isFacultyAdmin || !empty($facultyIds)) {
                // Faculty/Dean/Chair Admin: See publications in their scope
                // If filtering by specific user, use email match
                if ($filterUserId) {
                    $targetUser = $this->userModel->find($filterUserId);
                    $data = $this->publicationModel->getPublicationsByEmail($targetUser['email'] ?? '', (int)$limit);
                } else {
                    $data = $this->publicationModel->getPublicationsByFaculties($facultyIds, (int)$limit);
                }
            } else {
                // Regular User: Strict email match (use their own email)
                $data = $this->publicationModel->getPublicationsByEmail($user['email'] ?? '', (int)$limit);
            }

            return $this->response->setJSON([
                'success' => true,
                'data' => $data
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Dashboard getPublicationsApi error: ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Error loading publications'
            ]);
        }
    }
}
