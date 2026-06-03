<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use App\Models\PublicationModel;
use App\Models\AuthorModel;
use App\Models\UserModel;
use App\Models\UserProfileModel;
use App\Models\CvSectionModel;
use App\Models\CvEntryModel;
use App\Models\PublicationAuthorModel;
use App\Helpers\RoleHelper;
use App\Libraries\UserIdentity;

class DashboardController extends Controller
{
    protected $publicationModel;
    protected $authorModel;
    protected $userModel;
    protected $userProfileModel;
    protected $session;
    protected $cvSectionModel;
    protected $cvEntryModel;
    protected $publicationAuthorModel;
    protected $db;

    public function __construct()
    {
        $this->publicationModel = new PublicationModel();
        $this->authorModel = new AuthorModel();
        $this->userModel = new UserModel();
        $this->userProfileModel = new UserProfileModel();
        $this->cvSectionModel = new CvSectionModel();
        $this->cvEntryModel = new CvEntryModel();
        $this->publicationAuthorModel = new PublicationAuthorModel();
        $this->session = session();
        $this->db = \Config\Database::connect();
    }

    private function currentUserEmail(array $userData): string
    {
        $email = UserIdentity::normalizeEmail((string) ($userData['email'] ?? ''));
        if ($email !== '') {
            return $email;
        }

        $uid = $userData['uid'] ?? null;
        if ($uid === null || $uid === '') {
            return '';
        }

        $row = $this->userModel->find($uid);

        return UserIdentity::normalizeEmail((string) ($row['email'] ?? ''));
    }

    private function sessionOwnerEmail(?array $userData = null): string
    {
        $userData = $userData ?? $this->session->get('user_data') ?? [];

        $email = $this->currentUserEmail($userData);
        if ($email !== '') {
            return $email;
        }

        return UserIdentity::sessionEmail();
    }

    private function applyCvOwnerFilter($model, $userUid, string $email)
    {
        $email = UserIdentity::normalizeEmail($email);
        if ($email !== '' && $this->db->fieldExists('owner_email', 'cv_sections')) {
            return $model->where('owner_email', $email);
        }
        if ($email !== '' && $this->db->fieldExists('owner_email_norm', 'cv_sections')) {
            return $model->where('owner_email_norm', $email);
        }

        return $model->where('1=0', null, false);
    }

    private function sectionBelongsToUser(?array $section, $userUid, string $email): bool
    {
        if (!$section) {
            return false;
        }

        if ($email !== '' && !empty($section['owner_email_norm'])) {
            return UserIdentity::normalizeEmail((string) $section['owner_email_norm']) === $email;
        }

        return (string) ($section['user_uid'] ?? '') === (string) $userUid;
    }

    private function withCvOwnerEmail(array $data, string $email): array
    {
        if ($email !== '' && $this->db->fieldExists('owner_email_norm', 'cv_sections')) {
            $data['owner_email_norm'] = $email;
        }

        return $data;
    }

    /**
     * Initialize filters for authentication
     */
    public function initController(\CodeIgniter\HTTP\RequestInterface $request, \CodeIgniter\HTTP\ResponseInterface $response, \Psr\Log\LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);

        // Apply auth filter to all methods
        $this->helpers = array_merge($this->helpers, ['form', 'url']);
    }

    /**
     * Main Dashboard - Academic Publication Management
     */
    public function index()
    {
        // Authentication check
        if (!$this->session->get('logged_in')) {
            if ($this->request->isAJAX()) {
                return $this->response->setJSON(['success' => false, 'message' => 'Not authenticated'])->setStatusCode(401);
            }
            return redirect()->to('/login');
        }

        $userData   = $this->session->get('user_data') ?? [];
        $ownerEmail = $this->currentUserEmail($userData) ?: UserIdentity::sessionEmail();
        $userId     = $ownerEmail;

        // Get user's publications with statistics - email identity only
        $publications = $ownerEmail !== ''
            ? $this->publicationModel->getPublicationsByCanonicalEmail($ownerEmail)
            : [];
        $totalPublications = count($publications);

        // Calculate statistics
        $currentYear = date('Y');
        $thisYearPublications = array_filter($publications, function ($pub) use ($currentYear) {
            return $pub['publication_year'] == $currentYear;
        });

        $publicationTypes = [];
        foreach ($publications as $pub) {
            $type = $pub['publication_type'];
            $publicationTypes[$type] = ($publicationTypes[$type] ?? 0) + 1;
        }

        // Get unique authors count
        $uniqueAuthors = $this->authorModel->getUserUniqueAuthors($userId) ?? [];

        // Get recent publications (last 5)
        $recentPublications = array_slice($publications, 0, 5);

        // Prepare years for filter dropdown
        $years = array_unique(array_column($publications, 'publication_year'));
        $years = array_filter($years);
        rsort($years);

        // Prepare publication stats by type
        $publicationsByType = [];
        foreach ($publicationTypes as $type => $count) {
            $publicationsByType[] = [
                'publication_type' => $type,
                'count' => $count
            ];
        }

        // Prepare publication stats by year
        $publicationsByYear = [];
        foreach ($years as $year) {
            $count = count(array_filter($publications, function ($pub) use ($year) {
                return $pub['publication_year'] == $year;
            }));
            $publicationsByYear[] = [
                'publication_year' => $year,
                'count' => $count
            ];
        }

        // Get user profile data
        $userProfile = $this->userProfileModel->getOrCreate($ownerEmail) ?? [];

        // Get CV sections with entries for dashboard display
        $cvSections = $this->applyCvOwnerFilter($this->cvSectionModel, $userId, $ownerEmail)
            ->orderBy('sort_order', 'ASC')
            ->findAll();

        foreach ($cvSections as &$section) {
            $entries = $this->cvEntryModel
                ->where('section_id', $section['id'])
                ->orderBy('sort_order', 'ASC')
                ->findAll();
            $section['entries'] = $entries;
        }

        // Get full user data from database for admin check
        $user = $this->userModel->find($userId);
        if ($user) {
            // Merge database user data with session data (database data takes precedence)
            $user = array_merge($userData, $user);
        } else {
            $user = $userData;
        }

        // Check if user is admin (super_admin, faculty_admin, dean, chair, or has admin flags)
        // This logic is redundant with the view but kept here for the API response
        $userRole = $user['role'] ?? null;
        $isAdminFlag = ($user['admin'] ?? 0) == 1 || ($user['is_admin'] ?? 0) == 1;
        $isGodMode = $this->session->get('god_mode') === true;

        $isSuperAdmin = ($userRole === 'super_admin') || ($isAdminFlag && $userRole !== 'faculty_admin') || $isGodMode;
        $isFacultyAdmin = ($userRole === 'faculty_admin');
        
        // Use RoleHelper for more complex checks
        $isDean = RoleHelper::isDean($user);
        $isChair = RoleHelper::isChair($user);

        $showAdminButton = $isSuperAdmin || $isFacultyAdmin || $isDean || $isChair;

        $extraData = [
            'show_admin_button' => $showAdminButton,
            'is_super_admin' => $isSuperAdmin,
            'is_faculty_admin' => $isFacultyAdmin,
            'is_dean' => $isDean,
            'is_chair' => $isChair,
        ];

        $data = [
            'title' => 'Academic Research Dashboard',
            'user' => $user,
            'user_profile' => $userProfile,
            'cv_sections' => $cvSections,
            'user_stats' => [
                'publications' => $totalPublications,
                'authors' => count($uniqueAuthors)
            ],
            'publication_stats' => [
                'by_type' => $publicationsByType,
                'by_year' => $publicationsByYear
            ],
            'statistics' => [
                'total_publications' => $totalPublications,
                'this_year_count' => count($thisYearPublications),
                'total_authors' => count($uniqueAuthors),
                'total_categories' => count($publicationTypes)
            ],
            'publications' => $publications,
            'recent_publications' => $recentPublications,
            'publication_types' => $publicationTypes,
            'available_years' => $years,
            'current_year' => $currentYear,
            'extra' => $extraData
        ];

        if ($this->request->isAJAX()) {
            return $this->response->setJSON(['success' => true, 'data' => $data]);
        }

        return view('dashboard/index', $data);
    }

    /**
     * Academic CV view for logged-in users
     */
    public function cv()
    {
        if (!$this->session->get('logged_in')) {
            return redirect()->to('/login');
        }

        $userData = $this->session->get('user_data') ?? [];
        $ownerEmail = $this->sessionOwnerEmail($userData);

        // Use getPublicationsByAuthor to match by author_email (same condition as publications/index page)
        $publications = $this->publicationModel->getPublicationsByAuthor($ownerEmail);
        $totalPublications = count($publications);

        $uniqueAuthors = $this->authorModel->getUserUniqueAuthors($ownerEmail) ?? [];
        $recentPublications = array_slice($publications, 0, 6);
        $featuredPublications = array_slice($publications, 0, 3);

        $years = array_filter(array_column($publications, 'publication_year'));
        $activeYears = count(array_unique($years));

        $timeline = [];
        foreach ($publications as $pub) {
            $year = $pub['publication_year'] ?? date('Y', strtotime($pub['created_at'] ?? 'now'));
            if (!$year) {
                continue;
            }
            if (!isset($timeline[$year])) {
                $timeline[$year] = 0;
            }
            $timeline[$year]++;
        }
        krsort($timeline);
        $timelineData = [];
        foreach ($timeline as $year => $count) {
            $timelineData[] = [
                'year' => $year,
                'count' => $count
            ];
        }

        // Get user profile data from user_profile table
        $userProfile = $this->userProfileModel->getOrCreate($ownerEmail) ?? [];

        $expertise = [];
        if (!empty($userProfile['expertise'])) {
            $expertise = array_values(array_filter(array_map('trim', explode(',', $userProfile['expertise']))));
        }

        $socialLinks = array_filter([
            'Google Scholar' => $userProfile['google_scholar'] ?? null,
            'ORCID' => $userProfile['orcid'] ?? null,
            'Scopus' => $userProfile['scopus'] ?? null,
            'ResearchGate' => $userProfile['researchgate'] ?? null,
            'LinkedIn' => $userProfile['linkedin'] ?? null
        ]);

        // Get CV sections with entries for display
        $cvSections = $this->applyCvOwnerFilter($this->cvSectionModel, $ownerEmail, $ownerEmail)
            ->orderBy('sort_order', 'ASC')
            ->findAll();

        foreach ($cvSections as &$section) {
            $entries = $this->cvEntryModel
                ->where('section_id', $section['id'])
                ->orderBy('sort_order', 'ASC')
                ->orderBy('start_date', 'DESC')
                ->findAll();
            $section['entries'] = $entries;
        }

        $data = [
            'title' => 'Academic CV',
            'user' => $userData,
            'user_profile' => $userProfile,
            'cv_sections' => $cvSections,
            'cv_stats' => [
                'total_publications' => $totalPublications,
                'unique_authors' => count($uniqueAuthors),
                'active_years' => $activeYears ?: 1
            ],
            'all_publications' => $publications,
            'timeline' => $timelineData,
            'expertise' => $expertise,
            'professional_summary' => $userProfile['bio'] ?? 'Researcher with a passion for advancing knowledge through collaborative projects and impactful publications.',
            'social_links' => $socialLinks
        ];

        return view('dashboard/cv', $data);
    }

    /**
     * User settings page for managing CV/profile information
     */
    public function settings()
    {
        if (!$this->session->get('logged_in')) {
            return redirect()->to('/login');
        }

        $userData = $this->session->get('user_data') ?? [];
        $ownerEmail = $this->sessionOwnerEmail($userData);

        // Get user profile data from user_profile table
        $userProfile = $this->userProfileModel->getOrCreate($ownerEmail) ?? [];

        $socialLinks = [
            'google_scholar' => $userProfile['google_scholar'] ?? '',
            'orcid' => $userProfile['orcid'] ?? '',
            'scopus' => $userProfile['scopus'] ?? '',
            'researchgate' => $userProfile['researchgate'] ?? '',
            'linkedin' => $userProfile['linkedin'] ?? ''
        ];

        // No longer auto-creating default sections - users create their own or import from ORCID

        $data = [
            'title' => 'ตั้งค่าโปรไฟล์',
            'user' => $userData,
            'user_profile' => $userProfile,
            'expertise' => $userProfile['expertise'] ?? '',
            'bio' => $userProfile['bio'] ?? '',
            'social_links' => $socialLinks
        ];

        return view('dashboard/settings', $data);
    }

    /**
     * CV Management page - manage CV sections and entries
     */
    public function cvManage()
    {
        if (!$this->session->get('logged_in')) {
            return redirect()->to('/login');
        }

        $userData = $this->session->get('user_data') ?? [];
        $ownerEmail = $this->sessionOwnerEmail($userData);

        // No longer auto-creating default sections - users create their own or import from ORCID

        // Get CV sections with entries
        $cvSections = $this->applyCvOwnerFilter($this->cvSectionModel, $ownerEmail, $ownerEmail)
            ->orderBy('sort_order', 'ASC')
            ->orderBy('id', 'ASC')
            ->findAll();

        foreach ($cvSections as &$section) {
            $entries = $this->cvEntryModel
                ->where('section_id', $section['id'])
                ->orderBy('sort_order', 'ASC')
                ->orderBy('start_date', 'DESC')
                ->findAll();

            foreach ($entries as &$entry) {
                if (is_string($entry['metadata'])) {
                    $decoded = json_decode($entry['metadata'], true);
                    $entry['metadata'] = $decoded !== null ? $decoded : [];
                } elseif (!is_array($entry['metadata'])) {
                    $entry['metadata'] = [];
                }
            }

            $section['entries'] = $entries;
        }

        $data = [
            'title' => 'จัดการ CV',
            'user' => $userData,
            'cv_sections' => $cvSections
        ];

        return view('dashboard/cv-manage', $data);
    }

    /**
     * ORCID Sync page - sync publications and profile from ORCID
     */
    public function orcidPage()
    {
        if (!$this->session->get('logged_in')) {
            return redirect()->to('/login');
        }

        $userData = $this->session->get('user_data') ?? [];
        $ownerEmail = $this->sessionOwnerEmail($userData);

        // Get user profile data
        $userProfile = $this->userProfileModel->getOrCreate($ownerEmail) ?? [];

        // Get publications count
        $publications = $this->publicationModel->getPublicationsByAuthor($ownerEmail);

        $data = [
            'title' => 'ORCID Sync',
            'user' => $userData,
            'user_profile' => $userProfile,
            'publications_count' => count($publications)
        ];

        return view('dashboard/orcid', $data);
    }

    /**
     * Create or update CV section (topics)
     */
    public function saveCvSection()
    {
        if (!$this->session->get('logged_in')) {
            return redirect()->to('/login');
        }

        $userData = $this->session->get('user_data');
        $title = trim($this->request->getPost('title') ?? '');
        $type = $this->request->getPost('type') ?? 'custom';
        $description = trim($this->request->getPost('description') ?? '');

        if ($title === '') {
            return redirect()->back()->withInput()->with('error', 'กรุณากรอกชื่อหัวข้อ');
        }

        $allowedTypes = ['education', 'work', 'experience', 'funding', 'custom'];
        if (!in_array($type, $allowedTypes, true)) {
            $type = 'custom';
        }

        $sortOrder = (int) ($this->request->getPost('sort_order') ?? 0);

        $isAjax = $this->request->isAJAX();

        $sectionData = $this->withCvOwnerEmail([
            'type' => $type,
            'title' => $title,
            'description' => $description ?: null,
            'sort_order' => $sortOrder,
            'is_default' => $type === 'custom' ? 0 : 1
        ], $this->currentUserEmail($userData));

        $this->cvSectionModel->insert($sectionData);

        if ($isAjax) {
            $sectionId = $this->cvSectionModel->getInsertID();
            $section = $this->cvSectionModel->find($sectionId);
            return $this->response->setJSON([
                'success' => true,
                'message' => 'เพิ่มหัวข้อใหม่เรียบร้อยแล้ว',
                'section' => $section
            ]);
        }

        return redirect()->back()->with('success', 'เพิ่มหัวข้อใหม่เรียบร้อยแล้ว');
    }

    /**
     * Reorder CV sections (AJAX)
     */
    public function reorderCvSections()
    {
        if (!$this->session->get('logged_in')) {
            return $this->response->setJSON(['success' => false, 'message' => 'Not authenticated']);
        }

        try {
            $userData = $this->session->get('user_data');
            $ownerEmail = $this->sessionOwnerEmail($userData);

            $orderJson = $this->request->getPost('order');
            $order = json_decode($orderJson, true);

            if (empty($order)) {
                return $this->response->setJSON(['success' => false, 'message' => 'ไม่พบข้อมูลลำดับ']);
            }

            foreach ($order as $item) {
                $sectionId = $item['id'];
                $sortOrder = $item['order'];

                // Verify ownership
                $section = $this->cvSectionModel->find($sectionId);
                if ($this->sectionBelongsToUser($section, $ownerEmail, $ownerEmail)) {
                    $this->cvSectionModel->update($sectionId, ['sort_order' => $sortOrder]);
                }
            }

            return $this->response->setJSON([
                'success' => true,
                'message' => 'บันทึกลำดับเรียบร้อยแล้ว'
            ]);
        } catch (\Exception $e) {
            log_message('error', 'reorderCvSections error: ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Delete CV section (except education)
     */
    public function deleteCvSection($sectionId)
    {
        if (!$this->session->get('logged_in')) {
            return $this->response->setJSON(['success' => false, 'message' => 'Not authenticated']);
        }

        try {
            $userData = $this->session->get('user_data') ?? [];
            $ownerEmail = $this->sessionOwnerEmail($userData);

            $section = $this->cvSectionModel->find($sectionId);

            if (!$section) {
                return $this->response->setJSON(['success' => false, 'message' => 'ไม่พบหัวข้อ (not found)']);
            }

            if (!$this->sectionBelongsToUser($section, $ownerEmail, $ownerEmail)) {
                return $this->response->setJSON(['success' => false, 'message' => 'ไม่พบหัวข้อ (permission)']);
            }

            // Delete all entries in this section first
            $this->cvEntryModel->where('section_id', $sectionId)->delete();

            // Delete the section
            $this->cvSectionModel->delete($sectionId);

            return $this->response->setJSON([
                'success' => true,
                'message' => 'ลบหัวข้อเรียบร้อยแล้ว'
            ]);
        } catch (\Exception $e) {
            log_message('error', 'deleteCvSection error: ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Reorder CV entries within a section (AJAX)
     */
    public function reorderCvEntries()
    {
        if (!$this->session->get('logged_in')) {
            return $this->response->setJSON(['success' => false, 'message' => 'Not authenticated']);
        }

        try {
            $userData = $this->session->get('user_data') ?? [];
            $ownerEmail = $this->sessionOwnerEmail($userData);

            $sectionId = $this->request->getPost('section_id');
            $orderJson = $this->request->getPost('order');
            $order = json_decode($orderJson, true);

            if (empty($order) || empty($sectionId)) {
                return $this->response->setJSON(['success' => false, 'message' => 'ไม่พบข้อมูลลำดับ']);
            }

            // Verify section ownership
            $section = $this->cvSectionModel->find($sectionId);
            if (!$this->sectionBelongsToUser($section, $ownerEmail, $ownerEmail)) {
                return $this->response->setJSON(['success' => false, 'message' => 'ไม่มีสิทธิ์']);
            }

            foreach ($order as $item) {
                $entryId = $item['id'];
                $sortOrder = $item['order'];

                // Verify entry belongs to this section
                $entry = $this->cvEntryModel->find($entryId);
                if ($entry && $entry['section_id'] == $sectionId) {
                    $this->cvEntryModel->update($entryId, ['sort_order' => $sortOrder]);
                }
            }

            return $this->response->setJSON([
                'success' => true,
                'message' => 'บันทึกลำดับเรียบร้อยแล้ว'
            ]);
        } catch (\Exception $e) {
            log_message('error', 'reorderCvEntries error: ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Create or update CV entry
     */
    public function saveCvEntry()
    {
        if (!$this->session->get('logged_in')) {
            return redirect()->to('/login');
        }

        $userData = $this->session->get('user_data') ?? [];
        $ownerEmail = $this->sessionOwnerEmail($userData);
        $entryId = $this->request->getPost('entry_id');
        $sectionId = $this->request->getPost('section_id');

        if (!$sectionId) {
            return redirect()->back()->with('error', 'ไม่พบหัวข้อที่ต้องการบันทึก');
        }

        $section = $this->cvSectionModel->find($sectionId);
        if (!$this->sectionBelongsToUser($section, $ownerEmail, $ownerEmail)) {
            return redirect()->back()->with('error', 'ไม่สามารถเข้าถึงหัวข้อได้');
        }

        $title = trim($this->request->getPost('entry_title') ?? '');
        if ($title === '') {
            return redirect()->back()->with('error', 'กรุณากรอกชื่อรายการ');
        }

        $metadata = [];
        $extra = trim($this->request->getPost('extra_info') ?? '');
        $amount = trim($this->request->getPost('funding_amount') ?? '');
        if ($extra !== '') {
            $metadata['extra_info'] = $extra;
        }
        if ($amount !== '') {
            $metadata['amount'] = $amount;
        }

        // Ensure metadata is always a valid JSON string, never null
        $metadataJson = !empty($metadata) ? json_encode($metadata, JSON_UNESCAPED_UNICODE) : '[]';

        $entryData = [
            'section_id' => $sectionId,
            'title' => $title,
            'organization' => trim($this->request->getPost('organization') ?? ''),
            'location' => trim($this->request->getPost('location') ?? ''),
            'start_date' => $this->request->getPost('start_date') ?: null,
            'end_date' => $this->request->getPost('end_date') ?: null,
            'is_current' => $this->request->getPost('is_current') ? 1 : 0,
            'description' => trim($this->request->getPost('entry_description') ?? ''),
            'metadata' => $metadataJson,
            'sort_order' => (int) ($this->request->getPost('entry_sort_order') ?? 0)
        ];

        if ($entryId) {
            $existingEntry = $this->cvEntryModel->find($entryId);
            if (!$existingEntry) {
                return redirect()->back()->with('error', 'ไม่พบรายการที่ต้องการแก้ไข');
            }

            // Ensure ownership
            $existingSection = $this->cvSectionModel->find($existingEntry['section_id']);
            if (!$this->sectionBelongsToUser($existingSection, '', $ownerEmail)) {
                return redirect()->back()->with('error', 'ไม่สามารถแก้ไขรายการนี้ได้');
            }

            $entryData['id'] = $entryId;
        }

        $this->cvEntryModel->save($entryData);
        $savedId = $entryId ?: $this->cvEntryModel->getInsertID();

        if ($this->request->isAJAX()) {
            $savedEntry = $this->cvEntryModel->find($savedId);
            return $this->response->setJSON([
                'success' => true,
                'message' => 'บันทึกข้อมูลสำเร็จ',
                'entry' => $savedEntry
            ]);
        }

        return redirect()->back()->with('success', 'บันทึกข้อมูลสำเร็จ');
    }

    /**
     * Get CV entry data for editing
     */
    public function getCvEntry($entryId = null)
    {
        if (!$this->session->get('logged_in')) {
            if ($this->request->isAJAX()) {
                return $this->response->setJSON(['success' => false, 'message' => 'Unauthorized']);
            }
            return redirect()->to('/login');
        }

        if (!$entryId) {
            if ($this->request->isAJAX()) {
                return $this->response->setJSON(['success' => false, 'message' => 'Entry ID required']);
            }
            return redirect()->back()->with('error', 'ไม่พบรายการที่ต้องการ');
        }

        $userData = $this->session->get('user_data');
        $ownerEmail = $this->currentUserEmail($userData);
        $entry = $this->cvEntryModel->find($entryId);

        if (!$entry) {
            if ($this->request->isAJAX()) {
                return $this->response->setJSON(['success' => false, 'message' => 'Entry not found']);
            }
            return redirect()->back()->with('error', 'ไม่พบรายการที่ต้องการ');
        }

        $section = $this->cvSectionModel->find($entry['section_id']);
        if (!$this->sectionBelongsToUser($section, '', $ownerEmail)) {
            if ($this->request->isAJAX()) {
                return $this->response->setJSON(['success' => false, 'message' => 'Access denied']);
            }
            return redirect()->back()->with('error', 'ไม่สามารถเข้าถึงรายการนี้ได้');
        }

        // Handle metadata - it should already be decoded by json-array cast, but handle edge cases
        if (is_string($entry['metadata'])) {
            $decoded = json_decode($entry['metadata'], true);
            $entry['metadata'] = $decoded !== null ? $decoded : [];
        } elseif (!is_array($entry['metadata'])) {
            $entry['metadata'] = [];
        }

        if ($this->request->isAJAX()) {
            return $this->response->setJSON([
                'success' => true,
                'entry' => $entry
            ]);
        }

        return redirect()->back();
    }

    /**
     * Delete CV entry
     */
    public function deleteCvEntry($entryId = null)
    {
        if (!$this->session->get('logged_in')) {
            return redirect()->to('/login');
        }

        if (!$entryId) {
            return redirect()->back()->with('error', 'ไม่พบรายการที่ต้องการลบ');
        }

        $userData = $this->session->get('user_data');
        $ownerEmail = $this->currentUserEmail($userData);
        $entry = $this->cvEntryModel->find($entryId);
        if (!$entry) {
            return redirect()->back()->with('error', 'ไม่พบรายการที่ต้องการลบ');
        }

        $section = $this->cvSectionModel->find($entry['section_id']);
        if (!$this->sectionBelongsToUser($section, '', $ownerEmail)) {
            return redirect()->back()->with('error', 'ไม่สามารถลบรายการนี้ได้');
        }

        $this->cvEntryModel->delete($entryId);

        if ($this->request->isAJAX()) {
            return $this->response->setJSON([
                'success' => true,
                'message' => 'ลบรายการเรียบร้อยแล้ว'
            ]);
        }

        return redirect()->back()->with('success', 'ลบรายการเรียบร้อยแล้ว');
    }

    /**
     * Save profile summary (bio & expertise)
     */
    public function saveProfileSummary()
    {
        if (!$this->session->get('logged_in')) {
            return redirect()->to('/login');
        }

        $userData = $this->session->get('user_data') ?? [];
        $ownerEmail = $this->sessionOwnerEmail($userData);

        $bio = trim($this->request->getPost('bio') ?? '');
        $expertise = trim($this->request->getPost('expertise') ?? '');

        $updateData = [
            'bio' => $bio,
            'expertise' => $expertise
        ];

        $this->userProfileModel->updateByUserEmail($ownerEmail, $updateData);

        // Update session data if needed (optional, for backward compatibility)
        if (isset($userData['bio'])) {
            $userData['bio'] = $bio;
        }
        if (isset($userData['expertise'])) {
            $userData['expertise'] = $expertise;
        }
        $this->session->set('user_data', $userData);

        if ($this->request->isAJAX()) {
            return $this->response->setJSON([
                'success' => true,
                'message' => 'บันทึกข้อมูลโปรไฟล์สำเร็จ',
                'data' => $updateData
            ]);
        }

        return redirect()->back()->with('success', 'บันทึกข้อมูลโปรไฟล์สำเร็จ');
    }

    /**
     * Save contact & academic profile links
     */
    public function saveContactInfo()
    {
        if (!$this->session->get('logged_in')) {
            return redirect()->to('/login');
        }

        $userData = $this->session->get('user_data') ?? [];
        $ownerEmail = $this->sessionOwnerEmail($userData);

        $updateData = [
            'phone' => trim($this->request->getPost('phone') ?? ''),
            'institution' => trim($this->request->getPost('institution') ?? ''),
            'google_scholar' => trim($this->request->getPost('google_scholar') ?? ''),
            'orcid' => trim($this->request->getPost('orcid') ?? ''),
            'scopus' => trim($this->request->getPost('scopus') ?? ''),
            'researchgate' => trim($this->request->getPost('researchgate') ?? ''),
            'linkedin' => trim($this->request->getPost('linkedin') ?? ''),
        ];

        $this->userProfileModel->updateByUserEmail($ownerEmail, $updateData);

        // Update session data if needed (optional, for backward compatibility)
        foreach ($updateData as $key => $value) {
            if (isset($userData[$key])) {
                $userData[$key] = $value;
            }
        }
        $this->session->set('user_data', $userData);

        if ($this->request->isAJAX()) {
            return $this->response->setJSON([
                'success' => true,
                'message' => 'บันทึกข้อมูลติดต่อสำเร็จ',
                'data' => $updateData
            ]);
        }

        return redirect()->back()->with('success', 'บันทึกข้อมูลติดต่อสำเร็จ');
    }

    /**
     * Sync data from ORCID iD
     * Fetches researcher data directly from ORCID Public API
     */
    public function syncOrcid()
    {
        if (!$this->session->get('logged_in')) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Unauthorized'
            ])->setStatusCode(401);
        }

        try {
            $input = $this->request->getJSON(true);
            $orcidId = $input['orcid_id'] ?? null;

            // Validate ORCID format
            if (!$orcidId || !preg_match('/^[0-9]{4}-[0-9]{4}-[0-9]{4}-[0-9]{3}[0-9X]$/', $orcidId)) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'รูปแบบ ORCID iD ไม่ถูกต้อง (ตัวอย่าง: 0000-0001-2345-6789)'
                ]);
            }

            // Call ngrok ORCID API endpoint
            $apiUrl = config(\Config\N8n::class)->syncOrcidUrl($orcidId);

            try {
                $result = $this->makeHttpGetRequest($apiUrl, [
                    'Accept: application/json',
                    'ngrok-skip-browser-warning: true'
                ]);
                $response = $result['response'];
                $httpCode = $result['httpCode'];
            } catch (\Exception $e) {
                log_message('error', 'ORCID API Request Error: ' . $e->getMessage());
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'ไม่สามารถเชื่อมต่อกับ ORCID API ได้: ' . $e->getMessage()
                ]);
            }

            if ($httpCode !== 200) {
                log_message('error', 'ORCID API HTTP Error: ' . $httpCode . ' Response: ' . $response);
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'ORCID API Error (HTTP ' . $httpCode . ')'
                ]);
            }

            $rawData = json_decode($response, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                log_message('error', 'ORCID API JSON Error: ' . json_last_error_msg());
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'ไม่สามารถอ่านข้อมูลจาก ORCID API ได้'
                ]);
            }

            // Parse ngrok ORCID response structure
            // ngrok returns: { personal_info: {...}, orcid_items: [...] }
            $orcidData = is_array($rawData) && isset($rawData[0]) ? $rawData[0] : $rawData;

            $personalInfo = $orcidData['personal_info'] ?? [];
            $orcidItems = $orcidData['orcid_items'] ?? [];

            // Extract information
            $firstName = $personalInfo['first_name'] ?? '';
            $lastName = $personalInfo['last_name'] ?? '';
            $fullName = trim($firstName . ' ' . $lastName);
            $biography = $personalInfo['biography'] ?? '';

            // Get works (publications) from orcid_items
            $worksRaw = array_filter($orcidItems, fn($item) => ($item['category'] ?? '') === 'work');
            $works = [];

            foreach ($worksRaw as $item) {
                $works[] = [
                    'put_code' => $item['put_code'] ?? null,
                    'title' => $item['title'] ?? '',
                    'type' => $item['type'] ?? '',
                    'journal' => $item['journal'] ?? '',
                    'pub_year' => isset($item['pub_year']) ? (int)$item['pub_year'] : null,
                    'url' => $item['url'] ?? ''
                ];
            }
            $worksCount = count($works);

            // Get employments
            $employmentsRaw = array_filter($orcidItems, fn($item) => ($item['category'] ?? '') === 'employment');
            $employments = [];
            $currentEmployment = '';

            foreach ($employmentsRaw as $emp) {
                if (($emp['end_date'] ?? '') === 'Present' || empty($emp['end_date'])) {
                    $currentEmployment = $emp['organization'] ?? '';
                }
                $employments[] = $emp;
            }

            // Get education
            $educationsGroup = array_filter($orcidItems, fn($item) => ($item['category'] ?? '') === 'education');

            // Save ORCID data to user profile
            $userData = $this->session->get('user_data') ?? [];
            $ownerEmail = $this->sessionOwnerEmail($userData);

            if ($ownerEmail !== '') {
                $profileData = [
                    'orcid' => 'https://orcid.org/' . $orcidId,
                    'orcid_id' => $orcidId,
                    'orcid_data' => $response,
                    'orcid_synced_at' => date('Y-m-d H:i:s')
                ];

                if ($currentEmployment && empty($userData['institution'] ?? '')) {
                    $profileData['institution'] = $currentEmployment;
                }

                $this->userProfileModel->updateByUserEmail($ownerEmail, $profileData);

                log_message('info', 'ORCID data synced for user: ' . $ownerEmail . ' ORCID: ' . $orcidId);
            }

            // Process and import publications (check for duplicates using put_code)
            $importedCount = 0;
            $skippedCount = 0;
            $errorCount = 0;

            if ($ownerEmail !== '' && !empty($works)) {
                foreach ($works as $work) {
                    $workUrl = $work['url'] ?? '';
                    $workTitle = $work['title'] ?? '';
                    $workYear = isset($work['pub_year']) ? (int)$work['pub_year'] : null;
                    $workType = $work['type'] ?? '';
                    $workJournal = $work['journal'] ?? '';
                    $putCode = $work['put_code'] ?? null;

                    // Skip if no title
                    if (empty($workTitle)) {
                        $errorCount++;
                        continue;
                    }

                    // Extract DOI from URL if available
                    $doi = '';
                    if ($workUrl && preg_match('/10\.\d{4,}\/[^\s]+/', $workUrl, $matches)) {
                        $doi = $matches[0];
                    }

                    // Check if publication already exists using DOI (PRIMARY identifier)
                    $existingPub = null;

                    // Check by DOI first (most reliable)
                    if ($doi) {
                        $existingPub = $this->publicationModel
                            ->where('doi', $doi)
                            ->first();
                    }

                    // If not found by DOI, check by ORCID put_code
                    if (!$existingPub && $putCode) {
                        $existingPub = $this->publicationModel
                            ->where('orcid_put_code', $putCode)
                            ->first();
                    }

                    // If not found by DOI/put_code, check by URL
                    if (!$existingPub && $workUrl) {
                        $existingPub = $this->publicationModel
                            ->where('ref_url', $workUrl)
                            ->first();
                    }

                    // If not found, check by title + year (less reliable)
                    if (!$existingPub && $workTitle && $workYear) {
                        $existingPub = $this->publicationModel
                            ->where('title', $workTitle)
                            ->where('publication_year', $workYear)
                            ->first();
                    }

                    if ($existingPub) {
                        // Publication already exists - skip
                        $skippedCount++;
                        log_message('info', 'ORCID Import: Skipped duplicate (DOI: ' . $doi . ') - ' . $workTitle);
                        continue;
                    }

                    // Map ORCID type to our publication type
                    $publicationType = 'journal-article'; // default
                    if (stripos($workType, 'conference') !== false) {
                        $publicationType = 'conference-paper';
                    } elseif (stripos($workType, 'book') !== false) {
                        $publicationType = 'book';
                    } elseif (stripos($workType, 'chapter') !== false) {
                        $publicationType = 'book-chapter';
                    }

                    // Create new publication
                    try {
                        $pubData = [
                            'title' => $workTitle,
                            'publication_type' => $publicationType,
                            'source' => $workJournal ?: null,
                            'publication_year' => $workYear,
                            'doi' => $doi ?: null,
                            'ref_url' => $workUrl ?: null,
                            'created_by_email' => $ownerEmail,
                            'approve' => 0, // Pending approval
                            'notes' => 'Imported from ORCID iD: ' . $orcidId,
                            'orcid_put_code' => $putCode
                        ];

                        $publicationId = $this->publicationModel->insert($pubData);

                        if ($publicationId) {
                            // Add user as author
                            $user = $this->userModel->find($ownerEmail);
                            if ($user && !empty($user['email'])) {
                                // Check if author exists
                                $author = $this->authorModel->where('email', $user['email'])->first();

                                if (!$author) {
                                    $authorData = [
                                        'email' => $user['email'],
                                        'user_email' => $ownerEmail,
                                        'created_by_email' => $ownerEmail,
                                    ];
                                    $authorId = $this->authorModel->insert($authorData);
                                } else {
                                    $authorId = $author['id'];
                                }

                                if ($authorId) {
                                    // Link author to publication
                                    $authorName = trim(($user['gf_name'] ?? '') . ' ' . ($user['gl_name'] ?? ''));
                                    if (empty($authorName)) {
                                        $authorName = trim(($user['thai_name'] ?? '') . ' ' . ($user['thai_lastname'] ?? ''));
                                    }
                                    if (empty($authorName)) {
                                        $authorName = $user['email'];
                                    }

                                    $this->db->table('publication_authors')->insert([
                                        'publication_id' => $publicationId,
                                        'author_id' => $authorId,
                                        'author_name' => $authorName,
                                        'author_email' => $user['email'],
                                        'author_order' => 1,
                                    ]);
                                }
                            }

                            $importedCount++;
                            log_message('info', 'ORCID Import: Created publication - ' . $workTitle);
                        }
                    } catch (\Exception $e) {
                        $errorCount++;
                        log_message('error', 'ORCID Import Error: ' . $e->getMessage() . ' - ' . $workTitle);
                    }
                }
            }

            // Prepare response data - include education and employment for CV saving
            $responseData = [
                'orcid_id' => $orcidId,
                'name' => $fullName,
                'first_name' => $firstName,
                'last_name' => $lastName,
                'biography' => $biography,
                'affiliation' => $currentEmployment,
                'works_count' => $worksCount,
                'publications_imported' => $importedCount,
                'publications_skipped' => $skippedCount,
                'publications_errors' => $errorCount,
                'employments_count' => count($employments),
                'education_count' => count($educationsGroup),
                // Include full data for CV saving
                'education' => array_values($educationsGroup),
                'employments' => $employments
            ];

            $message = 'ดึงข้อมูล ORCID สำเร็จ (พบ ' . $worksCount . ' ผลงาน';
            if ($importedCount > 0) {
                $message .= ', นำเข้า ' . $importedCount . ' ผลงาน';
            }
            if ($skippedCount > 0) {
                $message .= ', ข้าม ' . $skippedCount . ' ผลงานที่มีอยู่แล้ว';
            }
            $message .= ')';

            return $this->response->setJSON([
                'success' => true,
                'message' => $message,
                'data' => $responseData
            ]);
        } catch (\Exception $e) {
            log_message('error', 'ORCID Sync Exception: ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Make HTTP GET request with cURL or file_get_contents fallback
     */
    private function makeHttpGetRequest($url, $headers = [], $timeout = 60)
    {
        // Try cURL first if curl_exec is available
        if (function_exists('curl_init') && function_exists('curl_exec')) {
            $ch = \curl_init();
            \curl_setopt($ch, CURLOPT_URL, $url);
            \curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            \curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
            \curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            if (!empty($headers)) {
                \curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            }

            $response = \curl_exec($ch);
            $httpCode = \curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = \curl_error($ch);
            \curl_close($ch);

            if ($curlError) {
                throw new \Exception('cURL Error: ' . $curlError);
            }

            return ['response' => $response, 'httpCode' => $httpCode];
        }

        // Fallback to file_get_contents
        $contextOptions = [
            'http' => [
                'method' => 'GET',
                'header' => implode("\r\n", $headers),
                'timeout' => $timeout,
                'ignore_errors' => true
            ],
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            ]
        ];

        $context = stream_context_create($contextOptions);
        $response = file_get_contents($url, false, $context);

        if ($response === false) {
            $error = error_get_last();
            throw new \Exception('HTTP request failed: ' . ($error['message'] ?? 'Unknown error'));
        }

        // Get HTTP response code
        $httpCode = 200;
        if (isset($http_response_header)) {
            foreach ($http_response_header as $header) {
                if (preg_match('/HTTP\/\d\.\d\s+(\d+)/', $header, $matches)) {
                    $httpCode = (int)$matches[1];
                    break;
                }
            }
        }

        return ['response' => $response, 'httpCode' => $httpCode];
    }

    /**
     * Make HTTP POST request with cURL or file_get_contents fallback
     */
    private function makeHttpPostRequest($url, $postData, $headers = [])
    {
        // Try cURL first if curl_exec is available
        if (function_exists('curl_init') && function_exists('curl_exec')) {
            $ch = \curl_init();
            \curl_setopt($ch, CURLOPT_URL, $url);
            \curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            \curl_setopt($ch, CURLOPT_POST, true);
            \curl_setopt($ch, CURLOPT_TIMEOUT, 60);
            \curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

            if (!empty($postData)) {
                if (is_array($postData)) {
                    \curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
                } else {
                    \curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
                }
            }

            if (!empty($headers)) {
                \curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            }

            $response = \curl_exec($ch);
            $httpCode = \curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = \curl_error($ch);
            \curl_close($ch);

            if ($curlError) {
                throw new \Exception('cURL Error: ' . $curlError);
            }

            return ['response' => $response, 'httpCode' => $httpCode];
        }

        // Fallback to file_get_contents
        $postString = is_array($postData) ? json_encode($postData) : $postData;
        $defaultHeaders = [
            'Content-Type: application/json',
            'Accept: application/json'
        ];
        $allHeaders = array_merge($defaultHeaders, $headers);
        $allHeaders[] = 'Content-Length: ' . strlen($postString);

        $contextOptions = [
            'http' => [
                'method' => 'POST',
                'header' => implode("\r\n", $allHeaders),
                'content' => $postString,
                'timeout' => 60,
                'ignore_errors' => true
            ],
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            ]
        ];

        $context = stream_context_create($contextOptions);
        $response = file_get_contents($url, false, $context);

        if ($response === false) {
            $error = error_get_last();
            throw new \Exception('HTTP request failed: ' . ($error['message'] ?? 'Unknown error'));
        }

        // Get HTTP response code
        $httpCode = 200;
        if (isset($http_response_header)) {
            foreach ($http_response_header as $header) {
                if (preg_match('/HTTP\/\d\.\d\s+(\d+)/', $header, $matches)) {
                    $httpCode = (int)$matches[1];
                    break;
                }
            }
        }

        return ['response' => $response, 'httpCode' => $httpCode];
    }

    /**
     * Fetch publication details by DOI using ngrok AI API
     * Uses same endpoint as publication-ai.js: /webhook/extract-article
     * Returns detailed publication info including all authors
     */
    private function fetchPublicationByDoi($doi)
    {
        if (empty($doi)) {
            return null;
        }

        try {
            // Build DOI URL
            $doiUrl = 'https://doi.org/' . $doi;

            // Call ngrok AI API (same endpoint as publication-ai.js)
            $apiUrl = config(\Config\N8n::class)->extractArticleUrl();

            try {
                $result = $this->makeHttpPostRequest($apiUrl, ['url' => $doiUrl], [
                    'Content-Type: application/json',
                    'Accept: application/json',
                    'ngrok-skip-browser-warning: true'
                ]);
                $response = $result['response'];
                $httpCode = $result['httpCode'];
            } catch (\Exception $e) {
                log_message('warning', 'DOI API Error for ' . $doi . ': ' . $e->getMessage());
                return null;
            }

            if ($httpCode !== 200) {
                log_message('warning', 'DOI API Error for ' . $doi . ': HTTP ' . $httpCode);
                return null;
            }

            $data = json_decode($response, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                log_message('warning', 'DOI API JSON Error for ' . $doi);
                return null;
            }

            // Parse response - the API returns { output: { ... } }
            $output = $data['output'] ?? $data;

            // Transform response to our format (similar to transformAIResponse in publication-ai.js)
            // Use !empty() check because API may return empty strings "" which ?? won't fallback from
            $pubData = [
                'title' => !empty($output['title_th']) ? $output['title_th'] : (!empty($output['title_en']) ? $output['title_en'] : ''),
                'title_en' => $output['title_en'] ?? '',
                'title_th' => $output['title_th'] ?? '',
                'type' => $output['type'] ?? 'journal-article',
                'source' => !empty($output['journalname']) ? $output['journalname'] : (!empty($output['conference_name_th']) ? $output['conference_name_th'] : ($output['conference_name_en'] ?? '')),
                'journal' => $output['journalname'] ?? '',
                'year' => !empty($output['year_en']) ? $output['year_en'] : ($output['year_th'] ?? null),
                'month' => !empty($output['month_en']) ? $output['month_en'] : ($output['month_th'] ?? null),
                'abstract' => !empty($output['abstract_th']) ? $output['abstract_th'] : (!empty($output['abstract_en']) ? $output['abstract_en'] : ''),
                'volume' => $output['volume'] ?? '',
                'issue' => $output['issue'] ?? '',
                'pages' => $output['pages'] ?? '',
                'doi' => $output['doi'] ?? $doi,
                'keywords' => !empty($output['keywords_th']) ? $output['keywords_th'] : ($output['keywords_en'] ?? []),
                'authors' => []
            ];

            // Transform authors - merge English and Thai author names
            // Note: We don't get email from API, we'll match by name to get email from database
            $authorsEn = $output['authors_en'] ?? [];
            $authorsTh = $output['authors_th'] ?? [];
            $maxAuthors = max(count($authorsEn), count($authorsTh));

            for ($i = 0; $i < $maxAuthors; $i++) {
                $enName = isset($authorsEn[$i]) ? trim($authorsEn[$i]) : '';
                $thName = isset($authorsTh[$i]) ? trim($authorsTh[$i]) : '';

                $pubData['authors'][] = [
                    'name' => $thName ?: $enName,  // Prefer Thai name
                    'name_en' => $enName,
                    'name_th' => $thName,
                    'email' => '',  // Will be filled by matchAuthorsToUsers() when matching with database
                    'affiliation' => '',
                    'orcid' => ''
                ];
            }

            // Log detailed DOI fetch results for debugging
            log_message('info', '=== DOI Fetch Success ===');
            log_message('info', 'DOI: ' . $doi);
            log_message('info', 'Title: ' . ($pubData['title'] ?? 'N/A'));
            log_message('info', 'Type: ' . ($pubData['type'] ?? 'N/A'));
            log_message('info', 'Source: ' . ($pubData['source'] ?? 'N/A'));
            log_message('info', 'Year: ' . ($pubData['year'] ?? 'N/A'));
            log_message('info', 'Authors Count: ' . count($pubData['authors']));
            foreach ($pubData['authors'] as $idx => $author) {
                log_message('info', "Author " . ($idx + 1) . ": name_th='{$author['name_th']}', name_en='{$author['name_en']}', email='{$author['email']}'");
            }
            log_message('info', 'Raw API Output: ' . json_encode($output, JSON_UNESCAPED_UNICODE));

            return $pubData;
        } catch (\Exception $e) {
            log_message('error', 'DOI Fetch Exception for ' . $doi . ': ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Match authors from publication data to users in database
     * Returns array of matched author data with user_uid where matched
     * Supports both Thai (name_th) and English (name_en) names
     */
    private function matchAuthorsToUsers($authors)
    {
        if (empty($authors) || !is_array($authors)) {
            return [];
        }

        $matchedAuthors = [];
        $order = 1;

        foreach ($authors as $author) {
            $authorName = $author['name'] ?? $author['name_th'] ?? $author['name_en'] ?? '';
            $authorNameTh = $author['name_th'] ?? '';
            $authorNameEn = $author['name_en'] ?? '';
            $authorEmail = $author['email'] ?? '';
            $authorAffiliation = $author['affiliation'] ?? '';
            $authorOrcid = $author['orcid'] ?? '';

            $matchedUser = null;
            $authorId = null;

            // Try to match by email first (most reliable)
            if (!empty($authorEmail)) {
                // Check in authors table first
                $existingAuthor = $this->authorModel->where('email', $authorEmail)->first();
                if ($existingAuthor) {
                    $authorId = $existingAuthor['id'];
                    if (!empty($existingAuthor['user_email'])) {
                        $matchedUser = $this->userModel->find($existingAuthor['user_email']);
                    }
                }

                // If not matched via authors table, check in users table directly
                if (!$matchedUser) {
                    $matchedUser = $this->userModel->where('email', $authorEmail)->first();

                    // If found in users table, create/update author record
                    if ($matchedUser) {
                        if (!$authorId) {
                            // Create new author record with email
                            $authorData = [
                                'email' => $authorEmail,
                                'user_email' => $matchedUser['email'],
                                'created_by_email' => $matchedUser['email']
                            ];
                            $authorId = $this->authorModel->insert($authorData);
                            log_message('info', "Created author record from matched user email: {$authorEmail}, uid={$matchedUser['uid']}");
                        } else {
                            // Update existing author record with user_uid if not set
                            if (empty($existingAuthor['user_email'])) {
                                $this->authorModel->update($authorId, [
                                    'user_email' => $matchedUser['email']
                                ]);
                                log_message('info', "Updated author record with user_uid: email={$authorEmail}, uid={$matchedUser['uid']}");
                            }
                        }
                    }
                }
            }

            // Try to match by ORCID if available
            if (!$matchedUser && !empty($authorOrcid)) {
                $orcidProfile = $this->userProfileModel->where('orcid_id', $authorOrcid)->first();
                if ($orcidProfile) {
                    $matchedUser = $this->userModel->find($orcidProfile['user_email']);
                }
            }

            // Try to match by Thai name first
            if (!$matchedUser && !empty($authorNameTh)) {
                $nameParts = explode(' ', $authorNameTh, 2);
                if (count($nameParts) >= 2) {
                    $firstName = trim($nameParts[0]);
                    $lastName = trim($nameParts[1]);

                    // Search by Thai name
                    $matchedUser = $this->userModel
                        ->like('thai_name', $firstName)
                        ->like('thai_lastname', $lastName)
                        ->first();

                    // If matched by name, create/update author record with user's email
                    if ($matchedUser && !empty($matchedUser['email'])) {
                        $userEmail = $matchedUser['email'];
                        $existingAuthor = $this->authorModel->where('email', $userEmail)->first();

                        if ($existingAuthor) {
                            $authorId = $existingAuthor['id'];
                            // Update user_uid if not set
                            if (empty($existingAuthor['user_email'])) {
                                $this->authorModel->update($authorId, ['user_email' => $matchedUser['email']]);
                            }
                        } else {
                            // Create author record with user's email
                            $authorData = [
                                'email' => $userEmail,
                                'user_email' => $matchedUser['email'],
                                'created_by_email' => $matchedUser['email']
                            ];
                            $authorId = $this->authorModel->insert($authorData);
                            log_message('info', "Created author record from Thai name match: email={$userEmail}, uid={$matchedUser['uid']}");
                        }
                        // Use user's email for author_email
                        if (empty($authorEmail)) {
                            $authorEmail = $userEmail;
                        }
                    }
                }
            }

            // Try to match by English name if not found
            if (!$matchedUser && !empty($authorNameEn)) {
                $nameParts = explode(' ', $authorNameEn, 2);
                if (count($nameParts) >= 2) {
                    $firstName = trim($nameParts[0]);
                    $lastName = trim($nameParts[1]);

                    // Search by English name (using gf_name/gl_name columns)
                    $matchedUser = $this->userModel
                        ->like('gf_name', $firstName)
                        ->like('gl_name', $lastName)
                        ->first();

                    // If matched by name, create/update author record with user's email
                    if ($matchedUser && !empty($matchedUser['email'])) {
                        $userEmail = $matchedUser['email'];
                        $existingAuthor = $this->authorModel->where('email', $userEmail)->first();

                        if ($existingAuthor) {
                            $authorId = $existingAuthor['id'];
                            // Update user_uid if not set
                            if (empty($existingAuthor['user_email'])) {
                                $this->authorModel->update($authorId, ['user_email' => $matchedUser['email']]);
                            }
                        } else {
                            // Create author record with user's email
                            $authorData = [
                                'email' => $userEmail,
                                'user_email' => $matchedUser['email'],
                                'created_by_email' => $matchedUser['email']
                            ];
                            $authorId = $this->authorModel->insert($authorData);
                            log_message('info', "Created author record from English name match: email={$userEmail}, uid={$matchedUser['uid']}");
                        }
                        // Use user's email for author_email
                        if (empty($authorEmail)) {
                            $authorEmail = $userEmail;
                        }
                    }
                }
            }

            // Fallback: Try generic name field
            if (!$matchedUser && !empty($authorName) && empty($authorNameTh) && empty($authorNameEn)) {
                $nameParts = explode(' ', $authorName, 2);
                if (count($nameParts) >= 2) {
                    $firstName = trim($nameParts[0]);
                    $lastName = trim($nameParts[1]);

                    // Search by Thai name first
                    $matchedUser = $this->userModel
                        ->like('thai_name', $firstName)
                        ->like('thai_lastname', $lastName)
                        ->first();

                    // Search by English name if not found (using gf_name/gl_name columns)
                    if (!$matchedUser) {
                        $matchedUser = $this->userModel
                            ->like('gf_name', $firstName)
                            ->like('gl_name', $lastName)
                            ->first();
                    }

                    // If matched by name, create/update author record with user's email
                    if ($matchedUser && !empty($matchedUser['email'])) {
                        $userEmail = $matchedUser['email'];
                        $existingAuthor = $this->authorModel->where('email', $userEmail)->first();

                        if ($existingAuthor) {
                            $authorId = $existingAuthor['id'];
                            // Update user_uid if not set
                            if (empty($existingAuthor['user_email'])) {
                                $this->authorModel->update($authorId, ['user_email' => $matchedUser['email']]);
                            }
                        } else {
                            // Create author record with user's email
                            $authorData = [
                                'email' => $userEmail,
                                'user_email' => $matchedUser['email'],
                                'created_by_email' => $matchedUser['email']
                            ];
                            $authorId = $this->authorModel->insert($authorData);
                            log_message('info', "Created author record from generic name match: email={$userEmail}, uid={$matchedUser['uid']}");
                        }
                        // Use user's email for author_email
                        if (empty($authorEmail)) {
                            $authorEmail = $userEmail;
                        }
                    }
                }
            }

            // Create or get author record
            // IMPORTANT: Always create/update author record with email if email is available
            if (!empty($authorId) && !empty($authorEmail)) {
                // Check if author already exists by email
                $existingAuthor = $this->authorModel->where('email', $authorEmail)->first();

                if ($existingAuthor) {
                    $authorId = $existingAuthor['id'];
                    // Update user_uid if we found a match and it's not set
                    if ($matchedUser && empty($existingAuthor['user_email'])) {
                        $this->authorModel->update($authorId, [
                            'user_email' => $matchedUser['email']
                        ]);
                    }
                } else {
                    // Create new author record with email
                    $authorData = [
                        'email' => $authorEmail,
                        'user_email' => $matchedUser['email'] ?? null,
                        'created_by_email' => $matchedUser['email'] ?? $this->sessionOwnerEmail()
                    ];
                    $authorId = $this->authorModel->insert($authorData);
                    log_message('info', "Created new author record: email={$authorEmail}, user_uid=" . ($matchedUser['uid'] ?? 'NULL'));
                }
            } elseif (!empty($matchedUser) && !empty($matchedUser['email'])) {
                // If we matched a user but no email in author data, use user's email
                $userEmail = $matchedUser['email'];
                $existingAuthor = $this->authorModel->where('email', $userEmail)->first();

                if ($existingAuthor) {
                    $authorId = $existingAuthor['id'];
                } else {
                    // Create author record with user's email
                    $authorData = [
                        'email' => $userEmail,
                        'user_email' => $matchedUser['email'],
                        'created_by_email' => $matchedUser['email']
                    ];
                    $authorId = $this->authorModel->insert($authorData);
                    log_message('info', "Created author record from matched user: email={$userEmail}, uid={$matchedUser['uid']}");
                }
            }

            // Ensure we have email for author_email field
            $finalEmail = $authorEmail;
            if (empty($finalEmail) && !empty($matchedUser['email'])) {
                $finalEmail = $matchedUser['email'];
            }

            $matchedAuthors[] = [
                'author_id' => $authorId,
                'author_name' => $authorName ?: $authorNameTh ?: $authorNameEn,
                'author_email' => $finalEmail,  // Always include email if available
                'author_affiliation' => $authorAffiliation,
                'author_order' => $order,
                'uid' => $matchedUser['uid'] ?? null,
                'is_matched' => !empty($matchedUser)
            ];

            // Log author matching result
            $matchStatus = !empty($matchedUser) ? 'MATCHED (uid: ' . $matchedUser['uid'] . ')' : 'NOT MATCHED';
            log_message('info', "Author {$order}: '{$authorName}' (th: '{$authorNameTh}', en: '{$authorNameEn}') => {$matchStatus}");

            $order++;
        }

        log_message('info', '=== Author Matching Complete: ' . count(array_filter($matchedAuthors, fn($a) => $a['is_matched'])) . '/' . count($matchedAuthors) . ' matched ===');

        return $matchedAuthors;
    }

    /**
     * Enhanced ORCID sync with DOI fetching and author matching
     * Fetches full publication details for each DOI found in ORCID
     */
    public function syncOrcidWithDoi()
    {
        if (!$this->session->get('logged_in')) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Unauthorized'
            ])->setStatusCode(401);
        }

        try {
            $input = $this->request->getJSON(true);
            $orcidId = $input['orcid_id'] ?? null;

            // Validate ORCID format
            if (!$orcidId || !preg_match('/^[0-9]{4}-[0-9]{4}-[0-9]{4}-[0-9]{3}[0-9X]$/', $orcidId)) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'รูปแบบ ORCID iD ไม่ถูกต้อง'
                ]);
            }

            $userData = $this->session->get('user_data') ?? [];
            $ownerEmail = $this->sessionOwnerEmail($userData);

            // Call ngrok ORCID API to get works list
            $apiUrl = config(\Config\N8n::class)->syncOrcidUrl($orcidId);

            try {
                $result = $this->makeHttpGetRequest($apiUrl, [
                    'Accept: application/json',
                    'ngrok-skip-browser-warning: true'
                ], 120); // 120 second timeout for ORCID sync
                $response = $result['response'];
                $httpCode = $result['httpCode'];
            } catch (\Exception $e) {
                log_message('error', 'ORCID API Request Error: ' . $e->getMessage());
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'ไม่สามารถเชื่อมต่อกับ ORCID API ได้: ' . $e->getMessage()
                ]);
            }

            if ($httpCode !== 200) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'ORCID API Error (HTTP ' . $httpCode . ')'
                ]);
            }

            $rawData = json_decode($response, true);
            $orcidData = is_array($rawData) && isset($rawData[0]) ? $rawData[0] : $rawData;

            // Check for new structure (raw ORCID) vs old structure (personal_info, orcid_items)
            $useNewStructure = isset($orcidData['person']) || isset($orcidData['activities-summary']);

            if ($useNewStructure) {
                // New structure: raw ORCID data
                $personData = $orcidData['person'] ?? [];
                $activitiesSummary = $orcidData['activities-summary'] ?? [];

                // Extract personal info
                $firstName = $personData['name']['given-names']['value'] ?? '';
                $lastName = $personData['name']['family-name']['value'] ?? '';
                $biography = $personData['biography']['content'] ?? '';

                // Extract keywords
                $keywordsArray = [];
                $keywordsList = $personData['keywords']['keyword'] ?? [];
                foreach ($keywordsList as $kw) {
                    if (!empty($kw['content'])) {
                        $keywordsArray[] = $kw['content'];
                    }
                }
                $keywords = implode(', ', $keywordsArray);

                // Extract works
                $worksGroups = $activitiesSummary['works']['group'] ?? [];
                $worksWithDoi = [];
                foreach ($worksGroups as $group) {
                    $summaries = $group['work-summary'] ?? [];
                    if (!empty($summaries)) {
                        $summary = $summaries[0];
                        $externalIds = $summary['external-ids']['external-id'] ?? [];
                        $url = '';
                        foreach ($externalIds as $extId) {
                            if (($extId['external-id-type'] ?? '') === 'doi') {
                                $url = $extId['external-id-url']['value'] ?? '';
                                break;
                            }
                        }
                        if (!empty($url)) {
                            $worksWithDoi[] = [
                                'title' => $summary['title']['title']['value'] ?? '',
                                'url' => $url,
                                'put_code' => $summary['put-code'] ?? null,
                                'type' => $summary['type'] ?? 'journal-article',
                                'pub_year' => $summary['publication-date']['year']['value'] ?? null,
                                'journal' => $summary['journal-title']['value'] ?? ''
                            ];
                        }
                    }
                }

                // Extract education
                $educationItems = [];
                $educationGroups = $activitiesSummary['educations']['affiliation-group'] ?? [];
                foreach ($educationGroups as $group) {
                    $summaries = $group['summaries'] ?? [];
                    foreach ($summaries as $eduItem) {
                        $edu = $eduItem['education-summary'] ?? $eduItem;
                        $educationItems[] = [
                            'put_code' => $edu['put-code'] ?? null,
                            'role_title' => $edu['role-title'] ?? '',
                            'organization' => $edu['organization']['name'] ?? '',
                            'org_city' => $edu['organization']['address']['city'] ?? '',
                            'start_year' => $edu['start-date']['year']['value'] ?? '',
                            'end_year' => $edu['end-date']['year']['value'] ?? ''
                        ];
                    }
                }

                // Extract employment
                $employmentItems = [];
                $employmentGroups = $activitiesSummary['employments']['affiliation-group'] ?? [];
                foreach ($employmentGroups as $group) {
                    $summaries = $group['summaries'] ?? [];
                    foreach ($summaries as $empItem) {
                        $emp = $empItem['employment-summary'] ?? $empItem;
                        $employmentItems[] = [
                            'put_code' => $emp['put-code'] ?? null,
                            'role_title' => $emp['role-title'] ?? '',
                            'organization' => $emp['organization']['name'] ?? '',
                            'org_city' => $emp['organization']['address']['city'] ?? '',
                            'start_year' => $emp['start-date']['year']['value'] ?? '',
                            'end_year' => $emp['end-date']['year']['value'] ?? ''
                        ];
                    }
                }
            } else {
                // Old structure: pre-processed personal_info, orcid_items
                $firstName = $orcidData['personal_info']['first_name'] ?? '';
                $lastName = $orcidData['personal_info']['last_name'] ?? '';
                $biography = $orcidData['personal_info']['biography'] ?? '';
                $keywords = '';
                $orcidItems = $orcidData['orcid_items'] ?? [];

                $worksWithDoi = array_filter($orcidItems, function ($item) {
                    return ($item['category'] ?? '') === 'work' && !empty($item['url']);
                });

                $educationItems = array_values(array_filter($orcidItems, function ($item) {
                    return ($item['category'] ?? '') === 'education';
                }));

                $employmentItems = array_values(array_filter($orcidItems, function ($item) {
                    return ($item['category'] ?? '') === 'employment';
                }));
            }

            // Save biography and keywords to user_profile
            if (!empty($biography) || !empty($keywords)) {
                $profileData = [];
                if (!empty($biography)) {
                    $profileData['bio'] = $biography;
                }
                if (!empty($keywords)) {
                    $profileData['expertise'] = $keywords;
                }
                if (!empty($profileData)) {
                    $this->userProfileModel->updateByUserEmail($ownerEmail, $profileData);
                    log_message('info', 'ORCID Sync: Updated user profile with bio and keywords');
                }
            }

            $importedCount = 0;
            $skippedCount = 0;
            $errorCount = 0;
            $processedWorks = [];

            foreach ($worksWithDoi as $work) {
                $workTitle = $work['title'] ?? '';
                $workUrl = $work['url'] ?? '';
                $putCode = $work['put_code'] ?? null;

                // Extract DOI from URL
                $doi = '';
                if (preg_match('/10\.\d{4,}\/[^\s]+/', $workUrl, $matches)) {
                    $doi = $matches[0];
                }

                if (empty($doi)) {
                    $errorCount++;
                    continue;
                }

                // Check if already exists
                $existingPub = $this->publicationModel->where('doi', $doi)->first();
                if (!$existingPub && $putCode) {
                    $existingPub = $this->publicationModel->where('orcid_put_code', $putCode)->first();
                }

                if ($existingPub) {
                    $skippedCount++;
                    continue;
                }

                // Fetch full publication details using DOI
                $pubDetails = $this->fetchPublicationByDoi($doi);

                if ($pubDetails) {
                    // Use AI-fetched details
                    $pubData = [
                        'title' => $pubDetails['title'] ?? $workTitle,
                        'publication_type' => $pubDetails['type'] ?? 'journal-article',
                        'source' => $pubDetails['source'] ?? $pubDetails['journal'] ?? '',
                        'publication_year' => $pubDetails['year'] ?? ($work['pub_year'] ?? null),
                        'abstract' => $pubDetails['abstract'] ?? '',
                        'doi' => $doi,
                        'ref_url' => $workUrl,
                        'volume' => $pubDetails['volume'] ?? '',
                        'pages' => $pubDetails['pages'] ?? '',
                        'keywords' => is_array($pubDetails['keywords'] ?? null) ? implode(', ', $pubDetails['keywords']) : ($pubDetails['keywords'] ?? ''),
                        'created_by_email' => $ownerEmail,
                        'approve' => 0,
                        'notes' => 'Imported from ORCID iD: ' . $orcidId . ' (with DOI details)',
                        'orcid_put_code' => $putCode
                    ];

                    // Match authors
                    $matchedAuthors = $this->matchAuthorsToUsers($pubDetails['authors'] ?? []);
                } else {
                    // Use basic ORCID data if DOI fetch failed
                    $pubData = [
                        'title' => $workTitle,
                        'publication_type' => $work['type'] ?? 'journal-article',
                        'source' => $work['journal'] ?? '',
                        'publication_year' => $work['pub_year'] ?? null,
                        'doi' => $doi,
                        'ref_url' => $workUrl,
                        'created_by_email' => $ownerEmail,
                        'approve' => 0,
                        'notes' => 'Imported from ORCID iD: ' . $orcidId,
                        'orcid_put_code' => $putCode
                    ];
                    $matchedAuthors = [];
                }

                // Insert publication
                $publicationId = $this->publicationModel->insert($pubData);

                if ($publicationId) {
                    // Add authors using same logic as AI extract in publications/create
                    // Use PublicationAuthorModel::addAuthorsToPublication() to save all authors (matched or not)
                    $processedAuthors = [];

                    if (!empty($matchedAuthors)) {
                        foreach ($matchedAuthors as $author) {
                            // Save all authors (matched or not) - same as AI extract logic
                            $processedAuthors[] = [
                                'name' => $author['author_name'],
                                'email' => $author['author_email'],
                                'affiliation' => $author['author_affiliation'] ?? null,
                                'author_id' => $author['author_id'] ?? null,
                                'uid' => $author['uid'] ?? null,
                                'corresponding' => 0
                            ];

                            if (!empty($author['is_matched']) && $author['is_matched']) {
                                log_message('info', "Author matched: publication_id={$publicationId}, author_id={$author['author_id']}, uid={$author['uid']}, email={$author['author_email']}");
                            } else {
                                log_message('info', "Author not matched (will save as unlinked): publication_id={$publicationId}, author_name={$author['author_name']}");
                            }
                        }
                    } else {
                        // Add current user as author fallback
                        $user = $this->userModel->find($ownerEmail);
                        if ($user) {
                            $author = $this->authorModel->where('email', $user['email'])->first();
                            $authorId = $author ? $author['id'] : null;

                            if (!$authorId) {
                                $authorId = $this->authorModel->insert([
                                    'email' => $user['email'],
                                    'user_email' => $user['email'],
                                    'created_by_email' => $ownerEmail
                                ]);
                            }

                            $authorName = trim(($user['gf_name'] ?? '') . ' ' . ($user['gl_name'] ?? ''));
                            $processedAuthors[] = [
                                'name' => $authorName ?: $user['email'],
                                'email' => $user['email'],
                                'affiliation' => null,
                                'author_id' => $authorId,
                                'corresponding' => 0
                            ];
                        }
                    }

                    // Use PublicationAuthorModel::addAuthorsToPublication() (same as AI extract in publications/create)
                    if (!empty($processedAuthors)) {
                        $result = $this->publicationAuthorModel->addAuthorsToPublication($publicationId, $processedAuthors);
                        if ($result === false) {
                            $dbError = $this->db->error();
                            log_message('error', 'Failed to insert authors: ' . json_encode($dbError));
                        } else {
                            log_message('info', "Inserted " . count($processedAuthors) . " authors using PublicationAuthorModel::addAuthorsToPublication()");
                        }
                    }

                    $importedCount++;
                    $processedWorks[] = [
                        'title' => $pubData['title'],
                        'type' => $pubData['publication_type'] ?? 'N/A',
                        'doi' => $doi,
                        'authors_matched' => count(array_filter($matchedAuthors, fn($a) => $a['is_matched']))
                    ];
                } else {
                    $errorCount++;
                }
            }

            $message = 'ดึงข้อมูล ORCID สำเร็จ (พบ ' . count($worksWithDoi) . ' ผลงานที่มี DOI';
            if ($importedCount > 0) {
                $message .= ', นำเข้า ' . $importedCount . ' ผลงาน';
            }
            if ($skippedCount > 0) {
                $message .= ', ข้าม ' . $skippedCount . ' ผลงานที่มีอยู่แล้ว';
            }
            $message .= ')';

            return $this->response->setJSON([
                'success' => true,
                'message' => $message,
                'data' => [
                    'name' => trim($firstName . ' ' . $lastName),
                    'affiliation' => $employmentItems[0]['organization'] ?? '',
                    'works_count' => count($worksWithDoi),
                    'publications_imported' => $importedCount,
                    'publications_skipped' => $skippedCount,
                    'publications_errors' => $errorCount,
                    'works' => $processedWorks,
                    'education' => $educationItems,
                    'employments' => $employmentItems,
                    'education_count' => count($educationItems),
                    'employments_count' => count($employmentItems)
                ]
            ]);
        } catch (\Exception $e) {
            log_message('error', 'ORCID Sync with DOI Exception: ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Save a single ORCID publication from JavaScript AJAX
     * Called for each DOI when doing detailed sync
     */
    public function saveOrcidPublication()
    {
        if (!$this->session->get('logged_in')) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Unauthorized'
            ])->setStatusCode(401);
        }

        try {
            $input = $this->request->getJSON(true);

            $userData = $this->session->get('user_data') ?? [];
            $ownerEmail = $this->sessionOwnerEmail($userData);

            $title = $input['title'] ?? '';
            $doi = $input['doi'] ?? '';
            $refUrl = $input['ref_url'] ?? '';
            $putCode = $input['put_code'] ?? null;
            $orcidId = $input['orcid_id'] ?? '';

            log_message('info', '=== SaveOrcidPublication ===');
            log_message('info', 'Title: ' . $title);
            log_message('info', 'DOI: ' . $doi);
            log_message('info', 'User: ' . $ownerEmail);

            // Check if publication already exists by DOI, put_code, or ref_url
            $existingPub = null;
            if ($doi) {
                $existingPub = $this->publicationModel->where('doi', $doi)->first();
            }
            if (!$existingPub && $putCode) {
                $existingPub = $this->publicationModel->where('orcid_put_code', $putCode)->first();
            }
            if (!$existingPub && $refUrl) {
                $existingPub = $this->publicationModel->where('ref_url', $refUrl)->first();
            }

            // Prepare publication data
            $pubData = [
                'title' => $title,
                'publication_type' => $input['type'] ?? 'journal-article',
                'source' => $input['source'] ?? '',
                'publication_year' => $input['year'] ?? null,
                'abstract' => $input['abstract'] ?? '',
                'volume' => $input['volume'] ?? '',
                'pages' => $input['pages'] ?? '',
                'doi' => $doi,
                'ref_url' => $refUrl,
                'keywords' => is_array($input['keywords'] ?? null) ? implode(', ', $input['keywords']) : ($input['keywords'] ?? ''),
                'notes' => 'Imported from ORCID iD: ' . $orcidId . ' (via AI)',
                'orcid_put_code' => $putCode
            ];

            $isUpdate = false;
            $publicationId = null;

            if ($existingPub) {
                // UPDATE existing publication
                $publicationId = $existingPub['id'];
                $isUpdate = true;

                log_message('info', 'Publication exists (ID: ' . $publicationId . '), updating...');

                // Don't overwrite certain fields
                unset($pubData['created_by'], $pubData['created_by_email']);
                $pubData['updated_at'] = date('Y-m-d H:i:s');

                $this->publicationModel->update($publicationId, $pubData);

                // Delete old authors to re-add with new matching
                $this->db->table('publication_authors')->where('publication_id', $publicationId)->delete();

                log_message('info', 'Publication updated, old authors removed for re-matching');
            } else {
                // INSERT new publication
                $pubData['created_by_email'] = $ownerEmail;
                $pubData['approve'] = 0;

                $publicationId = $this->publicationModel->insert($pubData);

                if (!$publicationId) {
                    throw new \Exception('Failed to insert publication');
                }

                log_message('info', 'New publication inserted with ID: ' . $publicationId);
            }

            // Process authors with enhanced matching (using same logic as AI extract in publications/create)
            $authors = $input['authors'] ?? [];
            $matchedCount = 0;
            $matchedAuthorsInfo = []; // Track matching results for response
            $processedAuthors = []; // For PublicationAuthorModel::addAuthorsToPublication()

            if (!empty($authors)) {
                $order = 1;
                foreach ($authors as $author) {
                    $authorName = $author['name'] ?? $author['name_th'] ?? $author['name_en'] ?? '';
                    $authorNameTh = $author['name_th'] ?? '';
                    $authorNameEn = $author['name_en'] ?? '';
                    $authorEmail = $author['email'] ?? '';
                    $authorAffiliation = $author['affiliation'] ?? null;

                    log_message('info', "--- Author #{$order} ---");
                    log_message('info', "  name: {$authorName}");
                    log_message('info', "  name_th: {$authorNameTh}");
                    log_message('info', "  name_en: {$authorNameEn}");
                    log_message('info', "  email: {$authorEmail}");

                    // Check if UID was pre-matched by JavaScript (AJAX search)
                    $preMatchedUid = $author['uid'] ?? null;
                    $matchedUser = null;
                    $authorId = null;
                    $matchMethod = null;

                    if ($preMatchedUid) {
                        // Email already matched by JavaScript via AJAX search
                        $matchedUser = $this->userModel->find(UserIdentity::normalizeEmail((string) $preMatchedUid));
                        if ($matchedUser) {
                            $matchMethod = 'pre_matched_by_ajax';
                            log_message('info', "  ✓ Pre-matched by AJAX: uid={$preMatchedUid}");
                        }
                    }

                    // Fallback: Try to match by email if not pre-matched
                    if (!$matchedUser && !empty($authorEmail)) {
                        // Try author table first (same as PublicationController::addAuthors())
                        $existingAuthor = $this->authorModel->getAuthorlinkUser($authorEmail);
                        if ($existingAuthor) {
                            $authorId = $existingAuthor['id'];
                            if (!empty($existingAuthor['user_email'])) {
                                $matchedUser = $this->userModel->find($existingAuthor['user_email']);
                                if ($matchedUser) {
                                    $matchMethod = 'email_via_author';
                                    log_message('info', "  ✓ Matched via author email: {$authorEmail} → uid={$matchedUser['uid']}");
                                }
                            }
                        }

                        // Try users table directly
                        if (!$matchedUser) {
                            $matchedUser = $this->userModel->where('email', $authorEmail)->first();
                            if ($matchedUser) {
                                $matchMethod = 'email_direct';
                                log_message('info', "  ✓ Matched by email (direct): {$authorEmail} → uid={$matchedUser['uid']}");

                                // Create/update author record if matched by email
                                if (!$authorId) {
                                    $existingAuthorByEmail = $this->authorModel->where('email', $authorEmail)->first();
                                    if ($existingAuthorByEmail) {
                                        $authorId = $existingAuthorByEmail['id'];
                                        // Update user_uid if not set
                                        if (empty($existingAuthorByEmail['user_uid'])) {
                                            $this->authorModel->update($authorId, ['user_email' => $matchedUser['email']]);
                                        }
                                    } else {
                                        // Create new author record
                                        $authorId = $this->authorModel->insert([
                                            'email' => $authorEmail,
                                            'user_email' => $matchedUser['email'],
                                            'created_by_email' => $matchedUser['email']
                                        ]);
                                        log_message('info', "Created author record from email match: email={$authorEmail}, uid={$matchedUser['uid']}");
                                    }
                                }
                            }
                        }
                    }

                    // Fallback: Try name search if not matched yet
                    if (!$matchedUser && !empty($authorNameTh)) {
                        $matchedUser = $this->userModel->builder()
                            ->select('email, thai_name, thai_lastname, gf_name, gl_name')
                            ->where('active', 1)
                            ->like("CONCAT(thai_name, ' ', thai_lastname)", trim($authorNameTh))
                            ->limit(1)
                            ->get()
                            ->getRowArray();
                        if ($matchedUser) {
                            $matchMethod = 'thai_name_concat';
                            log_message('info', "  ✓ Matched by Thai name: {$authorNameTh} → uid={$matchedUser['uid']}");

                            // Create/update author record if matched by name
                            $userEmail = $matchedUser['email'] ?? null;
                            if ($userEmail) {
                                $existingAuthorByName = $this->authorModel->where('email', $userEmail)->first();
                                if ($existingAuthorByName) {
                                    $authorId = $existingAuthorByName['id'];
                                    if (empty($existingAuthorByName['user_uid'])) {
                                        $this->authorModel->update($authorId, ['user_email' => $matchedUser['email']]);
                                    }
                                } else {
                                    $authorId = $this->authorModel->insert([
                                        'email' => $userEmail,
                                        'user_email' => $matchedUser['email'],
                                        'created_by_email' => $matchedUser['email']
                                    ]);
                                    log_message('info', "Created author record from Thai name match: email={$userEmail}, uid={$matchedUser['uid']}");
                                }
                            }
                        }
                    }

                    if (!$matchedUser && !empty($authorNameEn)) {
                        $matchedUser = $this->userModel->builder()
                            ->select('email, thai_name, thai_lastname, gf_name, gl_name')
                            ->where('active', 1)
                            ->like("CONCAT(gf_name, ' ', gl_name)", trim($authorNameEn))
                            ->limit(1)
                            ->get()
                            ->getRowArray();
                        if ($matchedUser) {
                            $matchMethod = 'english_name_concat';
                            log_message('info', "  ✓ Matched by English name: {$authorNameEn} → uid={$matchedUser['uid']}");

                            // Create/update author record if matched by name
                            $userEmail = $matchedUser['email'] ?? null;
                            if ($userEmail) {
                                $existingAuthorByName = $this->authorModel->where('email', $userEmail)->first();
                                if ($existingAuthorByName) {
                                    $authorId = $existingAuthorByName['id'];
                                    if (empty($existingAuthorByName['user_uid'])) {
                                        $this->authorModel->update($authorId, ['user_email' => $matchedUser['email']]);
                                    }
                                } else {
                                    $authorId = $this->authorModel->insert([
                                        'email' => $userEmail,
                                        'user_email' => $matchedUser['email'],
                                        'created_by_email' => $matchedUser['email']
                                    ]);
                                    log_message('info', "Created author record from English name match: email={$userEmail}, uid={$matchedUser['uid']}");
                                }
                            }
                        }
                    }

                    // Determine author_name: prioritize matched user's name
                    $authorNameToSave = $authorName ?: $authorNameTh ?: $authorNameEn;
                    if ($matchedUser) {
                        $matchedThaiName = trim(($matchedUser['thai_name'] ?? '') . ' ' . ($matchedUser['thai_lastname'] ?? ''));
                        $matchedEnglishName = trim(($matchedUser['gf_name'] ?? '') . ' ' . ($matchedUser['gl_name'] ?? ''));

                        if (!empty($matchedThaiName)) {
                            $authorNameToSave = $matchedThaiName;
                        } elseif (!empty($matchedEnglishName)) {
                            $authorNameToSave = $matchedEnglishName;
                        }
                    }

                    // Determine email to save
                    $emailToSave = $authorEmail;
                    if ($matchedUser && !empty($matchedUser['email'])) {
                        $emailToSave = $matchedUser['email'];
                    }

                    // Build processed author data (same format as PublicationController::addAuthors())
                    $processedAuthor = [
                        'name' => $authorNameToSave,
                        'email' => $emailToSave,
                        'affiliation' => $authorAffiliation,
                        'author_id' => $authorId,
                        'uid' => $matchedUser['uid'] ?? null,
                        'corresponding' => 0
                    ];

                    $processedAuthors[] = $processedAuthor;

                    // Build matching result for response
                    $matchResult = [
                        'order' => $order,
                        'input_name' => $authorName,
                        'input_name_th' => $authorNameTh,
                        'input_name_en' => $authorNameEn,
                        'matched' => false,
                        'match_method' => null,
                        'matched_user' => null
                    ];

                    if ($matchedUser) {
                        $matchedCount++;
                        $matchResult['matched'] = true;
                        $matchResult['match_method'] = $matchMethod;
                        $matchResult['matched_user'] = [
                            'uid' => $matchedUser['uid'],
                            'thai_name' => ($matchedUser['thai_name'] ?? '') . ' ' . ($matchedUser['thai_lastname'] ?? ''),
                            'english_name' => ($matchedUser['gf_name'] ?? '') . ' ' . ($matchedUser['gl_name'] ?? ''),
                            'email' => $matchedUser['email'] ?? ''
                        ];
                        log_message('info', "  => MATCHED to uid={$matchedUser['uid']} ({$matchedUser['email']})");
                    } else {
                        log_message('info', "  => NOT MATCHED - will save as unlinked author");
                    }

                    $matchedAuthorsInfo[] = $matchResult;
                    $order++;
                }

                // Use PublicationAuthorModel::addAuthorsToPublication() (same as AI extract in publications/create)
                if (!empty($processedAuthors)) {
                    $result = $this->publicationAuthorModel->addAuthorsToPublication($publicationId, $processedAuthors);
                    if ($result === false) {
                        $dbError = $this->db->error();
                        log_message('error', 'Failed to insert authors: ' . json_encode($dbError));
                        throw new \RuntimeException('Failed to save author information: ' . ($dbError['message'] ?? 'Unknown error'));
                    }
                    log_message('info', "Inserted " . count($processedAuthors) . " authors using PublicationAuthorModel::addAuthorsToPublication()");
                }
            } else {
                // No authors from AI, add current user as author
                $user = $this->userModel->find($ownerEmail);
                if ($user) {
                    $author = $this->authorModel->where('email', $user['email'])->first();
                    $authorId = $author ? $author['id'] : null;

                    if (!$authorId) {
                        $authorId = $this->authorModel->insert([
                            'email' => $user['email'],
                            'user_email' => $user['email'],
                            'created_by_email' => $ownerEmail
                        ]);
                    }

                    $authorName = trim(($user['gf_name'] ?? '') . ' ' . ($user['gl_name'] ?? ''));
                    $this->db->table('publication_authors')->insert([
                        'publication_id' => $publicationId,
                        'author_id' => $authorId,
                        'author_name' => $authorName ?: $user['email'],
                        'author_email' => $user['email'],
                        'author_order' => 1,
                    ]);
                    $matchedCount = 1;
                    $matchedAuthorsInfo[] = [
                        'order' => 1,
                        'input_name' => $authorName,
                        'matched' => true,
                        'match_method' => 'current_user',
                        'matched_user' => [
                            'uid' => $userId,
                            'email' => $user['email']
                        ]
                    ];
                }
            }

            log_message('info', ($isUpdate ? 'Updated' : 'Inserted') . " publication. Authors matched: {$matchedCount}/" . count($authors));

            return $this->response->setJSON([
                'success' => true,
                'skipped' => false,
                'is_update' => $isUpdate,
                'publication_id' => $publicationId,
                'authors_matched' => $matchedCount,
                'authors_total' => count($authors),
                'matched_authors' => $matchedAuthorsInfo,
                'message' => $isUpdate ? 'Publication updated successfully' : 'Publication created successfully'
            ]);
        } catch (\Exception $e) {
            log_message('error', 'SaveOrcidPublication Error: ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Save ORCID education and employment data to cv_entries
     * Called from JavaScript after syncing publications
     */
    public function saveOrcidCv()
    {
        if (!$this->session->get('logged_in')) {
            return $this->response->setJSON(['success' => false, 'message' => 'Not authenticated']);
        }

        try {
            $userData = $this->session->get('user_data') ?? [];
            $ownerEmail = $this->sessionOwnerEmail($userData);

            $input = $this->request->getJSON(true);
            $education = $input['education'] ?? [];
            $employment = $input['employment'] ?? [];

            log_message('info', 'saveOrcidCv - Education items: ' . count($education));
            log_message('info', 'saveOrcidCv - Employment items: ' . count($employment));

            // Ensure education section exists (the only mandatory default)
            $this->ensureDefaultCvSections('', $ownerEmail);

            // Get education section
            $educationSection = $this->applyCvOwnerFilter($this->cvSectionModel, '', $ownerEmail)
                ->where('type', 'education')
                ->first();

            // Get or create 'work' section if employment data exists
            $employmentSection = $this->applyCvOwnerFilter($this->cvSectionModel, '', $ownerEmail)
                ->where('type', 'work')
                ->first();

            // Auto-create work section if we have employment data from ORCID
            if (!$employmentSection && !empty($employment)) {
                $maxOrder = $this->applyCvOwnerFilter($this->cvSectionModel, '', $ownerEmail)
                    ->selectMax('sort_order')
                    ->first();
                $newOrder = ($maxOrder['sort_order'] ?? 0) + 1;

                $this->cvSectionModel->insert($this->withCvOwnerEmail([
                    'type' => 'work',
                    'title' => 'Work Experience',
                    'sort_order' => $newOrder,
                    'is_default' => 0 // Not a default section, created from ORCID data
                ], $ownerEmail));

                $employmentSection = $this->applyCvOwnerFilter($this->cvSectionModel, '', $ownerEmail)
                    ->where('type', 'work')
                    ->first();

                log_message('info', 'saveOrcidCv - Auto-created Work Experience section for ORCID employment data');
            }

            $educationCount = 0;
            $employmentCount = 0;

            // Process education entries
            if ($educationSection && !empty($education)) {
                foreach ($education as $edu) {
                    $entryData = [
                        'section_id' => $educationSection['id'],
                        'title' => $edu['title'] ?? $edu['role_title'] ?? 'ระดับการศึกษา',
                        'organization' => $edu['organization'] ?? $edu['org_name'] ?? '',
                        'location' => $edu['location'] ?? $edu['org_city'] ?? '',
                        'start_date' => $this->formatOrcidDate($edu['start_date'] ?? $edu['start_year'] ?? null),
                        'end_date' => $this->formatOrcidDate($edu['end_date'] ?? $edu['end_year'] ?? null),
                        'is_current' => empty($edu['end_date']) && empty($edu['end_year']) ? 1 : 0,
                        'description' => $edu['department'] ?? $edu['description'] ?? '',
                        'metadata' => json_encode([
                            'orcid_put_code' => $edu['put_code'] ?? null,
                            'source' => 'orcid',
                            'synced_at' => date('Y-m-d H:i:s')
                        ])
                    ];

                    // Check for existing entry by put_code in metadata
                    $existingEntry = null;
                    if (!empty($edu['put_code'])) {
                        $existingEntries = $this->cvEntryModel
                            ->where('section_id', $educationSection['id'])
                            ->like('metadata', (string)$edu['put_code'])
                            ->findAll();
                        if (!empty($existingEntries)) {
                            $existingEntry = $existingEntries[0];
                        }
                    }

                    if ($existingEntry) {
                        $this->cvEntryModel->update($existingEntry['id'], $entryData);
                    } else {
                        $this->cvEntryModel->insert($entryData);
                    }
                    $educationCount++;
                }
            }

            // Process employment entries
            if ($employmentSection && !empty($employment)) {
                foreach ($employment as $emp) {
                    $entryData = [
                        'section_id' => $employmentSection['id'],
                        'title' => $emp['title'] ?? $emp['role_title'] ?? 'ตำแหน่ง',
                        'organization' => $emp['organization'] ?? $emp['org_name'] ?? '',
                        'location' => $emp['location'] ?? $emp['org_city'] ?? '',
                        'start_date' => $this->formatOrcidDate($emp['start_date'] ?? $emp['start_year'] ?? null),
                        'end_date' => $this->formatOrcidDate($emp['end_date'] ?? $emp['end_year'] ?? null),
                        'is_current' => empty($emp['end_date']) && empty($emp['end_year']) ? 1 : 0,
                        'description' => $emp['department'] ?? $emp['description'] ?? '',
                        'metadata' => json_encode([
                            'orcid_put_code' => $emp['put_code'] ?? null,
                            'source' => 'orcid',
                            'synced_at' => date('Y-m-d H:i:s')
                        ])
                    ];

                    // Check for existing entry by put_code in metadata
                    $existingEntry = null;
                    if (!empty($emp['put_code'])) {
                        $existingEntries = $this->cvEntryModel
                            ->where('section_id', $employmentSection['id'])
                            ->like('metadata', (string)$emp['put_code'])
                            ->findAll();
                        if (!empty($existingEntries)) {
                            $existingEntry = $existingEntries[0];
                        }
                    }

                    if ($existingEntry) {
                        $this->cvEntryModel->update($existingEntry['id'], $entryData);
                    } else {
                        $this->cvEntryModel->insert($entryData);
                    }
                    $employmentCount++;
                }
            }

            return $this->response->setJSON([
                'success' => true,
                'message' => "บันทึกข้อมูลสำเร็จ",
                'education_count' => $educationCount,
                'employment_count' => $employmentCount
            ]);
        } catch (\Exception $e) {
            log_message('error', 'saveOrcidCv error: ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Format ORCID date to MySQL format
     */
    private function formatOrcidDate($dateInput)
    {
        if (empty($dateInput)) return null;

        // If it's just a year (numeric)
        if (is_numeric($dateInput)) {
            return $dateInput . '-01-01';
        }

        // If it's already in date format
        if (is_string($dateInput) && preg_match('/^\d{4}-\d{2}-\d{2}/', $dateInput)) {
            return substr($dateInput, 0, 10);
        }

        // If it's an array with year/month/day
        if (is_array($dateInput)) {
            $year = $dateInput['year'] ?? null;
            $month = $dateInput['month'] ?? '01';
            $day = $dateInput['day'] ?? '01';
            if ($year) {
                return sprintf('%04d-%02d-%02d', $year, $month, $day);
            }
        }

        return null;
    }

    public function updateProfilePicture()
    {
        if (!$this->session->get('logged_in')) {
            return redirect()->to('/login');
        }

        $ownerEmail = $this->sessionOwnerEmail($this->session->get('user_data') ?? []);
        if ($ownerEmail === '') {
            return redirect()->to('/login');
        }

        $rules = [
            'profile_picture' => [
                'rules' => 'uploaded[profile_picture]|is_image[profile_picture]|max_size[profile_picture,5120]|ext_in[profile_picture,jpg,jpeg,png,gif,webp]',
                'errors' => [
                    'uploaded' => 'กรุณาเลือกไฟล์รูปภาพ',
                    'is_image' => 'ไฟล์ต้องเป็นรูปภาพเท่านั้น',
                    'max_size' => 'ขนาดไฟล์ไม่ควรเกิน 5MB',
                    'ext_in' => 'รองรับเฉพาะไฟล์ JPG, PNG, GIF หรือ WEBP'
                ]
            ]
        ];

        $isAjax = $this->request->isAJAX();

        if (!$this->validate($rules)) {
            $error = $this->validator->getError('profile_picture');
            if ($isAjax) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => $error
                ]);
            }
            return redirect()->back()->withInput()->with('error', $error);
        }

        $file = $this->request->getFile('profile_picture');
        if (!$file || !$file->isValid()) {
            $error = 'ไม่สามารถอัปโหลดไฟล์ได้';
            if ($isAjax) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => $error
                ]);
            }
            return redirect()->back()->with('error', $error);
        }

        $uploadPath = WRITEPATH . 'uploads/profile';
        if (!is_dir($uploadPath)) {
            mkdir($uploadPath, 0775, true);
        }

        $newName = $file->getRandomName();

        try {
            $file->move($uploadPath, $newName);
        } catch (\RuntimeException $e) {
            $error = 'อัปโหลดไฟล์ไม่สำเร็จ: ' . $e->getMessage();
            if ($isAjax) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => $error
                ]);
            }
            return redirect()->back()->with('error', $error);
        }

        $publicUrl = base_url('index.php/dashboard/profile-image/' . $newName);

        // Delete previous local file if exists
        $userData = $this->session->get('user_data');
        $oldImage = $userData['profile_picture'] ?? '';

        if (!empty($oldImage)) {
            $oldFilename = null;

            if (strpos($oldImage, 'dashboard/profile-image/') !== false) {
                $parsed = parse_url($oldImage, PHP_URL_PATH);
                $segments = explode('/', trim($parsed, '/'));
                $oldFilename = end($segments);
            } elseif (strpos($oldImage, 'uploads/profile_pictures/') !== false) {
                $parsed = parse_url($oldImage, PHP_URL_PATH);
                $segments = explode('/', trim($parsed, '/'));
                $oldFilename = end($segments);
                $legacyPath = FCPATH . 'uploads/profile_pictures/' . $oldFilename;
                if (is_file($legacyPath)) {
                    @unlink($legacyPath);
                }
                $oldFilename = null;
            }

            if ($oldFilename) {
                $oldPath = rtrim($uploadPath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $oldFilename;
                if (is_file($oldPath)) {
                    @unlink($oldPath);
                }
            }
        }

        $this->userModel->update($ownerEmail, [
            'profile_picture' => $publicUrl
        ]);

        $userData['profile_picture'] = $publicUrl;
        $this->session->set('user_data', $userData);

        if ($isAjax) {
            return $this->response->setJSON([
                'success' => true,
                'message' => 'อัปเดตรูปโปรไฟล์เรียบร้อยแล้ว',
                'profile_picture' => $publicUrl
            ]);
        }

        return redirect()->back()->with('success', 'อัปเดตรูปโปรไฟล์เรียบร้อยแล้ว');
    }

    /**
     * Serve profile images stored in writable/uploads/profile
     */
    public function profileImage($filename = null)
    {
        if (empty($filename) || basename($filename) !== $filename) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        $filePath = WRITEPATH . 'uploads/profile/' . $filename;

        if (!is_file($filePath)) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        $mimeType = mime_content_type($filePath) ?: 'application/octet-stream';

        return $this->response
            ->setHeader('Content-Type', $mimeType)
            ->setHeader('Content-Disposition', 'inline; filename="' . $filename . '"')
            ->setBody(file_get_contents($filePath));
    }

    /**
     * Add Publication Form
     */
    public function addPublication()
    {
        if (!$this->session->get('logged_in')) {
            return redirect()->to('/login');
        }

        $data = [
            'title' => 'Add New Publication',
            'user' => $this->session->get('user_data')
        ];

        return view('dashboard/add_publication', $data);
    }

    /**
     * Save New Publication
     */
    public function savePublication()
    {
        if (!$this->session->get('logged_in')) {
            return redirect()->to('/login');
        }

        $ownerEmail = $this->sessionOwnerEmail($this->session->get('user_data') ?? []);

        // Validation rules
        $validationRules = [
            'title' => 'required|min_length[3]|max_length[1000]',
            'publication_type' => 'required|in_list[journal,book,proceedings,thesis,report,other]',
            'source' => 'required|min_length[2]|max_length[500]',
            'authors' => 'required'
        ];

        if (!$this->validate($validationRules)) {
            return redirect()->back()
                ->withInput()
                ->with('validation', $this->validator);
        }

        try {
            $db = \Config\Database::connect();
            $db->transStart();

            // Save publication
            $publicationData = [
                'title' => $this->request->getPost('title'),
                'abstract' => $this->request->getPost('abstract'),
                'publication_type' => $this->request->getPost('publication_type'),
                'source' => $this->request->getPost('source'),
                'publication_year' => $this->request->getPost('publication_year') ?: null,
                'volume' => $this->request->getPost('volume'),
                'pages' => $this->request->getPost('pages'),
                'doi' => $this->request->getPost('doi'),
                'isbn' => $this->request->getPost('isbn'),
                'keywords' => $this->request->getPost('keywords'),
                'notes' => $this->request->getPost('notes'),
                'created_by_email' => $ownerEmail
            ];

            $publicationId = $this->publicationModel->insert($publicationData);

            if (!$publicationId) {
                throw new \Exception('Failed to save publication');
            }

            // Process authors
            $authorsJson = $this->request->getPost('authors');
            $authors = json_decode($authorsJson, true);

            if ($authors && is_array($authors)) {
                $this->savePublicationAuthors($publicationId, $authors, $ownerEmail);
            }

            $db->transComplete();

            if ($db->transStatus() === false) {
                throw new \Exception('Transaction failed');
            }

            return redirect()->to('/dashboard')
                ->with('success', 'Publication saved successfully!');
        } catch (\Exception $e) {
            log_message('error', 'Error saving publication: ' . $e->getMessage());
            return redirect()->back()
                ->withInput()
                ->with('error', 'Failed to save publication: ' . $e->getMessage());
        }
    }

    /**
     * Edit Publication
     */
    public function editPublication($id)
    {
        if (!$this->session->get('logged_in')) {
            return redirect()->to('/login');
        }

        $ownerEmail = $this->sessionOwnerEmail($this->session->get('user_data') ?? []);
        $publication = $this->publicationModel->getPublicationWithAuthors($id, $ownerEmail);

        if (!$publication) {
            return redirect()->to('/dashboard')
                ->with('error', 'Publication not found or access denied');
        }

        $data = [
            'title' => 'Edit Publication',
            'user' => $this->session->get('user_data'),
            'publication' => $publication
        ];

        return view('dashboard/edit_publication', $data);
    }

    /**
     * Update Publication
     */
    public function updatePublication($id)
    {
        if (!$this->session->get('logged_in')) {
            return redirect()->to('/login');
        }

        $ownerEmail = $this->sessionOwnerEmail();

        // Check ownership
        $publication = $this->publicationModel->where('id', $id)
            ->where('created_by_email', $ownerEmail)
            ->first();

        if (!$publication) {
            return redirect()->to('/dashboard')
                ->with('error', 'Publication not found or access denied');
        }

        // Validation
        $validationRules = [
            'title' => 'required|min_length[3]|max_length[1000]',
            'publication_type' => 'required|in_list[journal,book,proceedings,thesis,report,other]',
            'source' => 'required|min_length[2]|max_length[500]',
            'authors' => 'required'
        ];

        if (!$this->validate($validationRules)) {
            return redirect()->back()
                ->withInput()
                ->with('validation', $this->validator);
        }

        try {
            $db = \Config\Database::connect();
            $db->transStart();

            // Update publication
            $publicationData = [
                'title' => $this->request->getPost('title'),
                'abstract' => $this->request->getPost('abstract'),
                'publication_type' => $this->request->getPost('publication_type'),
                'source' => $this->request->getPost('source'),
                'publication_year' => $this->request->getPost('publication_year') ?: null,
                'volume' => $this->request->getPost('volume'),
                'pages' => $this->request->getPost('pages'),
                'doi' => $this->request->getPost('doi'),
                'isbn' => $this->request->getPost('isbn'),
                'keywords' => $this->request->getPost('keywords'),
                'notes' => $this->request->getPost('notes')
            ];

            $this->publicationModel->update($id, $publicationData);

            // Delete existing authors and re-add
            $this->publicationModel->deletePublicationAuthors($id);

            // Process authors
            $authorsJson = $this->request->getPost('authors');
            $authors = json_decode($authorsJson, true);

            if ($authors && is_array($authors)) {
                $this->savePublicationAuthors($id, $authors, $ownerEmail);
            }

            $db->transComplete();

            if ($db->transStatus() === false) {
                throw new \Exception('Transaction failed');
            }

            return redirect()->to('/dashboard')
                ->with('success', 'Publication updated successfully!');
        } catch (\Exception $e) {
            log_message('error', 'Error updating publication: ' . $e->getMessage());
            return redirect()->back()
                ->withInput()
                ->with('error', 'Failed to update publication: ' . $e->getMessage());
        }
    }

    /**
     * Delete Publication
     */
    public function deletePublication($id)
    {
        if (!$this->session->get('logged_in')) {
            return redirect()->to('/login');
        }

        $ownerEmail = $this->sessionOwnerEmail();

        // Check ownership
        $publication = $this->publicationModel->where('id', $id)
            ->where('created_by_email', $ownerEmail)
            ->first();

        if (!$publication) {
            return redirect()->to('/dashboard')
                ->with('error', 'Publication not found or access denied');
        }

        if ($this->publicationModel->delete($id)) {
            return redirect()->to('/dashboard')
                ->with('success', 'Publication deleted successfully!');
        } else {
            return redirect()->to('/dashboard')
                ->with('error', 'Failed to delete publication');
        }
    }

    /**
     * Search/Filter Publications (AJAX)
     */
    public function searchPublications()
    {
        if (!$this->session->get('logged_in')) {
            return $this->response->setJSON(['error' => 'Unauthorized']);
        }

        $ownerEmail = $this->sessionOwnerEmail();
        $searchTerm = $this->request->getGet('search');
        $typeFilter = $this->request->getGet('type');
        $yearFilter = $this->request->getGet('year');

        $publications = $this->publicationModel->searchUserPublications(
            $ownerEmail,
            $searchTerm,
            $typeFilter,
            $yearFilter
        );

        return $this->response->setJSON([
            'success' => true,
            'publications' => $publications,
            'count' => count($publications)
        ]);
    }

    /**
     * Authors Management
     */
    public function authors()
    {
        if (!$this->session->get('logged_in')) {
            return redirect()->to('/login');
        }

        $ownerEmail = $this->sessionOwnerEmail();
        $authors = $this->authorModel->getUserAuthorsWithStats($ownerEmail);

        $data = [
            'title' => 'Author Management',
            'user' => $this->session->get('user_data'),
            'authors' => $authors
        ];

        return view('dashboard/authors', $data);
    }

    /**
     * Export Publications
     */
    public function export($format = 'json')
    {
        if (!$this->session->get('logged_in')) {
            return redirect()->to('/login');
        }

        $ownerEmail = $this->sessionOwnerEmail();
        $publications = $this->publicationModel->getPublicationsByAuthor($ownerEmail);

        switch ($format) {
            case 'csv':
                return $this->exportCSV($publications);
            case 'json':
                return $this->exportJSON($publications);
            default:
                return redirect()->to('/dashboard')->with('error', 'Invalid export format');
        }
    }

    /**
     * Save Publication Authors with Email Matching
     */
    private function savePublicationAuthors($publicationId, $authors, $ownerEmail)
    {
        $ownerEmail = UserIdentity::normalizeEmail((string) $ownerEmail);

        foreach ($authors as $index => $authorData) {
            $authorName = trim($authorData['name'] ?? '');
            $authorEmail = trim($authorData['email'] ?? '');
            $authorAffiliation = trim($authorData['affiliation'] ?? '');

            if (empty($authorName)) continue;

            $linkedAuthorId = null;
            $finalAuthorName = $authorName;

            // Try email matching if email provided
            if (!empty($authorEmail)) {
                // Check if author exists in authors table
                $existingAuthor = $this->authorModel->where('email', $authorEmail)->first();

                if ($existingAuthor) {
                    $linkedAuthorId = $existingAuthor['id'];

                    // Use registered name if linked to user
                    if (! empty($existingAuthor['user_email'])) {
                        $user = $this->userModel->find($existingAuthor['user_email']);
                        if ($user) {
                            $finalAuthorName = trim($user['gf_name'] . ' ' . $user['gl_name']);
                        }
                    } else {
                        $finalAuthorName = $existingAuthor['name'];
                    }
                } else {
                    // Check if email matches a user directly
                    $user = $this->userModel->where('email', $authorEmail)->first();
                    if ($user) {
                        $finalAuthorName = trim($user['gf_name'] . ' ' . $user['gl_name']);

                        // Create new author linked to user
                        $newAuthorData = [
                            'email' => $authorEmail,
                            'user_email' => $user['email'],
                            'created_by_email' => $ownerEmail,
                        ];
                        $linkedAuthorId = $this->authorModel->insert($newAuthorData);
                    } else {
                        // Create new unlinked author
                        $newAuthorData = [
                            'email' => $authorEmail,
                            'created_by_email' => $ownerEmail,
                        ];
                        $linkedAuthorId = $this->authorModel->insert($newAuthorData);
                    }
                }
            }

            // Save to publication_authors table
            $publicationAuthorData = [
                'publication_id' => $publicationId,
                'author_name' => $finalAuthorName,
                'author_email' => $authorEmail ?: null,
                'author_affiliation' => $authorAffiliation ?: null,
                'author_id' => $linkedAuthorId,
                'author_order' => $index + 1
            ];

            $this->publicationModel->insertPublicationAuthor($publicationAuthorData);
        }
    }

    /**
     * Export as CSV
     */
    private function exportCSV($publications)
    {
        $filename = 'publications_' . date('Y-m-d') . '.csv';

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $output = fopen('php://output', 'w');

        // CSV headers
        fputcsv($output, [
            'ID',
            'Title',
            'Type',
            'Source',
            'Authors',
            'Year',
            'Volume',
            'Pages',
            'DOI',
            'ISBN',
            'Keywords',
            'Created'
        ]);

        foreach ($publications as $pub) {
            fputcsv($output, [
                $pub['id'],
                $pub['title'],
                $pub['publication_type'],
                $pub['source'],
                $pub['authors'] ?? '',
                $pub['publication_year'],
                $pub['volume'],
                $pub['pages'],
                $pub['doi'],
                $pub['isbn'],
                $pub['keywords'],
                $pub['created_at']
            ]);
        }

        fclose($output);
        exit;
    }

    /**
     * Export as JSON
     */
    private function exportJSON($publications)
    {
        $filename = 'publications_' . date('Y-m-d') . '.json';

        return $this->response
            ->setHeader('Content-Type', 'application/json')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->setBody(json_encode($publications, JSON_PRETTY_PRINT));
    }

    /**
     * Ensure default CV sections exist for user
     * Only Education is required, other sections can be deleted
     */
    private function ensureDefaultCvSections($userUid, string $ownerEmail = '')
    {
        // Only Education is mandatory - others can be deleted by user
        $defaults = [
            ['type' => 'education', 'title' => 'Education'],
        ];

        foreach ($defaults as $index => $default) {
            $existing = $this->applyCvOwnerFilter($this->cvSectionModel, $userUid, $ownerEmail)
                ->where('type', $default['type'])
                ->first();

            if (!$existing) {
                $this->cvSectionModel->insert($this->withCvOwnerEmail([
                    'type' => $default['type'],
                    'title' => $default['title'],
                    'sort_order' => $index + 1,
                    'is_default' => 1
                ], $ownerEmail));
            }
        }
    }
}
