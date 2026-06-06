<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use App\Models\UserModel;
use App\Models\CvSectionModel;
use App\Models\CvEntryModel;
use App\Models\FacultyModel;
use App\Helpers\RoleHelper;

/**
 * EducationController
 * 
 * จัดการประวัติการศึกษาของ Users สำหรับ Admin
 * - แสดงรายชื่อ users ทั้งหมด
 * - แก้ไขประวัติการศึกษาผ่าน Modal
 */
class EducationController extends Controller
{
    protected $session;
    protected $userModel;
    protected $cvSectionModel;
    protected $cvEntryModel;
    protected $facultyModel;

    public function __construct()
    {
        $this->session = session();
        $this->userModel = new UserModel();
        $this->cvSectionModel = new CvSectionModel();
        $this->cvEntryModel = new CvEntryModel();
        $this->facultyModel = new FacultyModel();
    }

    /**
     * แสดงหน้าจัดการประวัติการศึกษา
     */
    public function index()
    {
        $userData = $this->session->get('user_data');
        $userRole = $userData['role'] ?? 'user';
        $managedFaculties = json_decode($userData['managed_faculties'] ?? '[]', true);
        $isFacultyAdmin = ($userRole === 'faculty_admin');

        // Get faculties for filter dropdown
        $facultyBuilder = $this->facultyModel->where('status', 1)->orderBy('name', 'ASC');
        // Faculty admin should only see faculties they manage
        if ($isFacultyAdmin && !empty($managedFaculties)) {
            $facultyBuilder = $facultyBuilder->whereIn('id', $managedFaculties);
        }
        $faculties = $facultyBuilder->findAll();

        return view('admin/education/index', [
            'title' => 'จัดการประวัติการศึกษา',
            'faculties' => $faculties,
            'userData' => $userData,
            'isFacultyAdmin' => $isFacultyAdmin,
            'managedFaculties' => $managedFaculties
        ]);
    }

    /**
     * ดึงรายชื่อ Users ทั้งหมด (AJAX)
     * Faculty Admin จะเห็นเฉพาะ users ใน faculty ที่ดูแล
     */
    public function getUsers()
    {
        $userData = $this->session->get('user_data');
        $userRole = $userData['role'] ?? 'user';
        $managedFaculties = json_decode($userData['managed_faculties'] ?? '[]', true);

        $facultyFilter = $this->request->getGet('faculty_id');
        $search = $this->request->getGet('search');

        // Build query
        $builder = $this->userModel->db->table('user');
        $builder->select([
            'user.email as uid',
            'user.email',
            'user.gf_name',
            'user.gl_name',
            'user.thai_name',
            'user.thai_lastname',
            'user.profile_picture',
            'user.faculty_id',
            'f.name as faculty_name',
            'COUNT(DISTINCT ce.id) as education_count'
        ]);
        $builder->join('faculties f', 'f.id = user.faculty_id', 'left');
        $builder->join('cv_sections cs', 'cs.owner_email_norm = user.email AND cs.type = "education"', 'left');
        $builder->join('cv_entries ce', 'ce.section_id = cs.id', 'left');
        $builder->where('user.active', 1);
        $builder->groupBy('user.email');
        $builder->orderBy('user.gf_name', 'ASC');

        // Faculty Admin filter
        if ($userRole === 'faculty_admin' && !empty($managedFaculties)) {
            $builder->whereIn('user.faculty_id', $managedFaculties);
        }

        // Faculty filter from dropdown
        if (!empty($facultyFilter)) {
            $builder->where('user.faculty_id', $facultyFilter);
        }

        // Search filter
        if (!empty($search)) {
            $builder->groupStart();
            $builder->like('user.gf_name', $search);
            $builder->orLike('user.gl_name', $search);
            $builder->orLike('user.thai_name', $search);
            $builder->orLike('user.thai_lastname', $search);
            $builder->orLike('user.email', $search);
            $builder->groupEnd();
        }

        $users = $builder->get()->getResultArray();

        return $this->response->setJSON([
            'success' => true,
            'data' => $users
        ]);
    }

    /**
     * ดึงประวัติการศึกษาของ User (AJAX)
     * จะสร้าง education section อัตโนมัติถ้ายังไม่มี
     */
    public function getEducation($userUid = null)
    {
        if (!$userUid) {
            // Email is sent via POST body because IIS rejects "@" in the URL path and
            // drops GET params on the index.php?/route form.
            $userUid = $this->request->getPost('email')
                ?? $this->request->getPost('user_uid')
                ?? $this->request->getGet('email')
                ?? $this->request->getGet('user_uid');
        }

        if (!$userUid) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'User ID is required'
            ]);
        }

        // Get user info
        $user = $this->userModel->find($userUid);
        if (!$user) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'User not found'
            ]);
        }

        // Ensure education section exists
        $section = $this->ensureEducationSection($userUid);

        // Get education entries
        $entries = $this->cvEntryModel
            ->where('section_id', $section['id'])
            ->orderBy('sort_order', 'ASC')
            ->orderBy('start_date', 'DESC')
            ->findAll();

        return $this->response->setJSON([
            'success' => true,
            'user' => [
                'uid' => $user['email'],
                'name' => trim(($user['gf_name'] ?? '') . ' ' . ($user['gl_name'] ?? '')),
                'thai_name' => trim(($user['thai_name'] ?? '') . ' ' . ($user['thai_lastname'] ?? '')),
                'email' => $user['email']
            ],
            'section' => $section,
            'entries' => $entries
        ]);
    }

    /**
     * บันทึก/แก้ไข Education Entry (AJAX)
     */
    public function saveEntry()
    {
        $json = $this->request->getJSON(true);
        if (!$json) {
            $json = $this->request->getPost();
        }

        $userUid = $json['user_uid'] ?? null;
        $entryId = $json['entry_id'] ?? null;

        if (!$userUid) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'User ID is required'
            ]);
        }

        // Get or create education section
        $section = $this->ensureEducationSection($userUid);

        // Prepare entry data
        $entryData = [
            'section_id' => $section['id'],
            'title' => trim($json['title'] ?? ''),
            'organization' => trim($json['organization'] ?? ''),
            'location' => trim($json['location'] ?? ''),
            'start_date' => $json['start_date'] ?: null,
            'end_date' => $json['end_date'] ?: null,
            'is_current' => ($json['is_current'] ?? false) ? 1 : 0,
            'description' => trim($json['description'] ?? ''),
            'metadata' => '[]',
            'sort_order' => (int)($json['sort_order'] ?? 0)
        ];

        if (empty($entryData['title'])) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'กรุณากรอกชื่อปริญญา/วุฒิการศึกษา'
            ]);
        }

        // Update or insert
        if ($entryId) {
            $existingEntry = $this->cvEntryModel->find($entryId);
            if (!$existingEntry || $existingEntry['section_id'] != $section['id']) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Entry not found or access denied'
                ]);
            }
            $entryData['id'] = $entryId;
        }

        $this->cvEntryModel->save($entryData);
        $savedId = $entryId ?: $this->cvEntryModel->getInsertID();
        $savedEntry = $this->cvEntryModel->find($savedId);

        return $this->response->setJSON([
            'success' => true,
            'message' => 'บันทึกข้อมูลสำเร็จ',
            'entry' => $savedEntry
        ]);
    }

    /**
     * ลบ Education Entry (AJAX)
     */
    public function deleteEntry($id = null)
    {
        if (!$id) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Entry ID is required'
            ]);
        }

        $entry = $this->cvEntryModel->find($id);
        if (!$entry) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Entry not found'
            ]);
        }

        // Verify this is an education entry
        $section = $this->cvSectionModel->find($entry['section_id']);
        if (!$section || $section['type'] !== 'education') {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Invalid entry'
            ]);
        }

        $this->cvEntryModel->delete($id);

        return $this->response->setJSON([
            'success' => true,
            'message' => 'ลบข้อมูลสำเร็จ'
        ]);
    }

    /**
     * หน้าสร้างรายงาน PDF
     */
    public function pdfReport()
    {
        $userData = $this->session->get('user_data');
        $faculties = $this->facultyModel->where('status', 1)->orderBy('name', 'ASC')->findAll();

        return view('admin/education/pdf_report', [
            'title' => 'รายงานประวัติการศึกษา',
            'faculties' => $faculties,
            'userData' => $userData
        ]);
    }

    /**
     * ดึงข้อมูลสำหรับรายงาน (AJAX)
     */
    public function getReportData()
    {
        $userData = $this->session->get('user_data');
        $userRole = $userData['role'] ?? 'user';
        $managedFaculties = json_decode($userData['managed_faculties'] ?? '[]', true);

        $facultyId = $this->request->getGet('faculty_id');

        // Build query to get users with education
        $builder = $this->userModel->db->table('user u');
        $builder->select([
            'u.email as uid',
            'u.email',
            'u.gf_name',
            'u.gl_name',
            'u.thai_name',
            'u.thai_lastname',
            'u.titleThai',
            'u.title',
            'u.faculty_id',
            'f.id as fid',
            'f.name as faculty_name'
        ]);
        $builder->join('faculties f', 'f.id = u.faculty_id', 'left');
        $builder->where('u.active', 1);
        $builder->orderBy('f.name', 'ASC');
        $builder->orderBy('u.thai_name', 'ASC');

        // Faculty Admin filter
        if ($userRole === 'faculty_admin' && !empty($managedFaculties)) {
            $builder->whereIn('u.faculty_id', $managedFaculties);
        }

        // Faculty filter
        if (!empty($facultyId)) {
            $builder->where('u.faculty_id', $facultyId);
        }

        $users = $builder->get()->getResultArray();

        // Get education entries for each user
        $reportData = [];
        $facultyGroups = [];

        foreach ($users as $user) {
            // Get education section
            $section = $this->cvSectionModel
                ->where('owner_email_norm', $user['email'])
                ->where('type', 'education')
                ->first();

            $educationEntries = [];
            if ($section) {
                $educationEntries = $this->cvEntryModel
                    ->where('section_id', $section['id'])
                    ->orderBy('end_date', 'DESC')
                    ->orderBy('start_date', 'DESC')
                    ->findAll();
            }

            // Include all users (with or without education)
            $facultyName = $user['faculty_name'] ?? 'ไม่ระบุคณะ';
            $fid = $user['fid'] ?? 0;

            if (!isset($facultyGroups[$fid])) {
                $facultyGroups[$fid] = [
                    'faculty_id' => $fid,
                    'faculty_name' => $facultyName,
                    'users' => []
                ];
            }

            // Find latest end date for sorting
            $latestEndDate = null;
            if (!empty($educationEntries)) {
                foreach ($educationEntries as $entry) {
                    if ($entry['is_current'] == 1) {
                        $latestEndDate = '9999-12-31'; // Current study = most recent
                        break;
                    }
                    if ($entry['end_date'] && (!$latestEndDate || $entry['end_date'] > $latestEndDate)) {
                        $latestEndDate = $entry['end_date'];
                    }
                }
            }

            $facultyGroups[$fid]['users'][] = [
                'uid' => $user['email'],
                'title' => $user['titleThai'] ?? $user['title'] ?? '',
                'thai_name' => trim(($user['thai_name'] ?? '') . ' ' . ($user['thai_lastname'] ?? '')),
                'eng_name' => trim(($user['gf_name'] ?? '') . ' ' . ($user['gl_name'] ?? '')),
                'email' => $user['email'],
                'education' => $educationEntries,
                'has_education' => !empty($educationEntries),
                'latest_end_date' => $latestEndDate
            ];
        }

        // Sort users within each faculty by latest_end_date DESC (most recent first, no education last)
        foreach ($facultyGroups as &$group) {
            usort($group['users'], function ($a, $b) {
                // Users with education come first
                if ($a['has_education'] && !$b['has_education']) return -1;
                if (!$a['has_education'] && $b['has_education']) return 1;

                // Both have education - sort by latest_end_date DESC
                if ($a['has_education'] && $b['has_education']) {
                    return strcmp($b['latest_end_date'] ?? '', $a['latest_end_date'] ?? '');
                }

                // Both don't have education - sort by name
                return strcmp($a['thai_name'], $b['thai_name']);
            });
        }
        unset($group);

        // Get statistics
        $totalUsers = count($users);
        $usersWithEducation = 0;
        foreach ($facultyGroups as $group) {
            foreach ($group['users'] as $u) {
                if ($u['has_education']) $usersWithEducation++;
            }
        }

        return $this->response->setJSON([
            'success' => true,
            'data' => array_values($facultyGroups),
            'statistics' => [
                'total_users' => $totalUsers,
                'users_with_education' => $usersWithEducation,
                'total_faculties' => count($facultyGroups)
            ],
            'generated_at' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * สร้าง Education Section ถ้ายังไม่มี
     */
    private function ensureEducationSection($userEmail)
    {
        $userEmail = \App\Libraries\UserIdentity::normalizeEmail((string) $userEmail);

        $section = $this->cvSectionModel
            ->where('owner_email_norm', $userEmail)
            ->where('type', 'education')
            ->first();

        if (!$section) {
            $this->cvSectionModel->insert([
                'owner_email_norm' => $userEmail,
                'type' => 'education',
                'title' => 'Education',
                'description' => 'ประวัติการศึกษา',
                'sort_order' => 0,
                'is_default' => 1
            ]);
            $section = $this->cvSectionModel->find($this->cvSectionModel->getInsertID());
        }

        return $section;
    }
}
