<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use App\Models\UserModel;
use App\Models\AuthorModel;
use App\Models\PublicationModel;
use App\Models\FacultyModel;
use App\Models\CurriculumModel;
use App\Helpers\RoleHelper;
use App\Libraries\UserIdentity;

class AdminController extends Controller
{
    protected $userModel;
    protected $authorModel;
    protected $publicationModel;
    protected $facultyModel;
    protected $curriculumModel;
    protected $session;
    protected $db;

    public function __construct()
    {
        $this->userModel = new UserModel();
        $this->authorModel = new AuthorModel();
        $this->publicationModel = new PublicationModel();
        $this->facultyModel = new FacultyModel();
        $this->curriculumModel = new CurriculumModel();
        $this->session = session();
        $this->db = \Config\Database::connect();

        helper(['form', 'url', 'year']);
    }

    /**
     * Faculty admin: allow load/mutate only if creator or any author is in a managed faculty.
     * Super admin / god / backdoor session: no restriction here.
     *
     * @return \CodeIgniter\HTTP\ResponseInterface|null JSON 403 or null to continue
     */
    protected function facultyAdminPublicationForbiddenResponse(int $publicationId): ?\CodeIgniter\HTTP\ResponseInterface
    {
        if ($this->session->get('god_mode') === true
            || $this->session->get('backdoor_session') === true
            || $this->session->get('backdoor_admin_auth') === true) {
            return null;
        }
        $userData = $this->session->get('user_data') ?? [];
        if (($userData['role'] ?? '') !== 'faculty_admin') {
            return null;
        }
        $email = UserIdentity::sessionEmail();
        if ($email === '') {
            $userData = $this->session->get('user_data') ?? [];
            $email    = UserIdentity::normalizeEmail((string) ($userData['email'] ?? ''));
        }
        if ($email === '') {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'ไม่ได้รับอนุญาต',
            ])->setStatusCode(403);
        }
        $user = $this->userModel->find($email);
        if (!is_array($user)) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'ไม่ได้รับอนุญาต',
            ])->setStatusCode(403);
        }
        if (!RoleHelper::canAccessPublication($user, $publicationId, $this->publicationModel)) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'ไม่มีสิทธิ์: ผลงานนี้ไม่มีผู้แต่งหรือผู้แต่งร่วมในคณะที่คุณดูแล',
            ])->setStatusCode(403);
        }

        return null;
    }

    /**
     * Admin Dashboard
     */
    public function index()
    {
        $data = [
            'title' => 'Admin Dashboard - University Publication Statistics3'
        ];

        return view('admin/Dashboard/dashboard', $data);
    }


    /**
     * Dashboard API - Get Dashboard Summary
     * Returns consolidated data for dashboard including faculty summary table
     */
    public function getDashboardSummary()
    {
        try {
            $userId = UserIdentity::sessionEmail();
            if ($userId === '') {
                $userData = $this->session->get('user_data') ?? [];
                $userId   = UserIdentity::normalizeEmail((string) ($userData['email'] ?? ''));
            }

            if (!$userId) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'User not authenticated'
                ]);
            }

            $user = $this->userModel->find($userId);
            if (!$user) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'User not found'
                ]);
            }

            $isSuperAdmin = $this->session->get('god_mode') === true || RoleHelper::isSuperAdmin($user);
            $facultyIds = [];

            if (RoleHelper::isFacultyAdmin($user)) {
                $facultyIds = RoleHelper::getManagedFaculties($user);
            } elseif (RoleHelper::isDean($user)) {
                $facultyIds = RoleHelper::getDeanFaculties($user);
            } elseif (RoleHelper::isChair($user)) {
                $facultyIds = RoleHelper::getChairFaculties($user);
            }

            // Get faculty summary table data
            $facultySummary = $this->getFacultySummaryData($facultyIds);

            // Get publication type distribution
            $publicationTypes = $this->getPublicationTypeStats($facultyIds);

            // Get recent publications
            $recentPublications = $this->getRecentPublications($facultyIds, 10);

            return $this->response->setJSON([
                'success' => true,
                'data' => [
                    'isSuperAdmin' => $isSuperAdmin,
                    'facultySummary' => $facultySummary,
                    'publicationTypes' => $publicationTypes,
                    'recentPublications' => $recentPublications
                ]
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Get dashboard summary error: ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Error loading dashboard summary: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Get Faculty Summary Data for Dashboard Table
     */
    private function getFacultySummaryData(array $facultyIds = [])
    {
        $db = \Config\Database::connect();

        $builder = $db->table('faculties f');
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
     * Get Publication Type Statistics
     */
    private function getPublicationTypeStats(array $facultyIds = [])
    {
        $db = \Config\Database::connect();

        $builder = $db->table('publication_view pv');
        $builder->select('pv.publication_type, COUNT(*) as count');

        if (!empty($facultyIds)) {
            $builder->whereIn('pv.faculty_id', $facultyIds);
        }

        $builder->groupBy('pv.publication_type');
        $builder->orderBy('count', 'DESC');

        return $builder->get()->getResultArray();
    }

    /**
     * Get Recent Publications
     */
    private function getRecentPublications(array $facultyIds = [], int $limit = 10)
    {
        $db = \Config\Database::connect();

        $builder = $db->table('publication_view pv');
        $builder->select("
            pv.id,
            pv.title,
            pv.publication_type,
            pv.publication_year,
            pv.created_at,
            pv.created_by_name,
            pv.created_by_faculty_name as faculty_name
        ");

        if (!empty($facultyIds)) {
            $builder->whereIn('pv.faculty_id', $facultyIds);
        }

        $builder->orderBy('pv.created_at', 'DESC');
        $builder->limit($limit);

        return $builder->get()->getResultArray();
    }


    /**
     * Show add publication form
     */
    public function addPublication()
    {
        $data = [
            'title' => 'Add New Publication'
        ];

        return view('admin/publications/add', $data);
    }

    /**
     * Save new publication
     */


    /**
     * Show all publications
     */
    public function publications()
    {
        // Get all publications from all users
        $publications = $this->getAllPublications();

        $data = [
            'title' => 'All Publications',
            'publications' => $publications
        ];

        return view('admin/publications/index', $data);
    }

    /**
     * Manage publications page (with edit modal)
     * Route: GET /admin/publications/manage
     */
    public function managePublications()
    {
        $data = [
            'title' => 'Manage Publications'
        ];

        return view('admin/publications/managePublication', $data);
    }

    /**
     * Delete publication
     */
    public function deletePublication($id = null)
    {
        if (!$id) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Publication ID required'
            ]);
        }

        $denied = $this->facultyAdminPublicationForbiddenResponse((int) $id);
        if ($denied !== null) {
            return $denied;
        }

        try {
            // Delete authors first
            $this->publicationModel->deletePublicationAuthors($id);

            // Delete publication
            $deleted = $this->publicationModel->delete($id);

            if ($deleted) {
                return $this->response->setJSON([
                    'success' => true,
                    'message' => 'Publication deleted successfully'
                ]);
            }

            return $this->response->setJSON([
                'success' => false,
                'message' => 'Failed to delete publication'
            ]);
        } catch (\Exception $e) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Get single publication with authors for editing
     * Route: GET /admin/publications/get/{id}
     */
    public function getPublication($id = null)
    {
        log_message('info', '========== getPublication START ==========');
        log_message('info', 'getPublication - ID: ' . $id);

        if (!$id) {
            log_message('warning', 'getPublication - Missing ID');
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Publication ID required'
            ]);
        }

        try {
            // Get publication
            $publication = $this->publicationModel->find($id);
            log_message('info', 'getPublication - Publication found: ' . ($publication ? 'YES' : 'NO'));

            if (!$publication) {
                log_message('warning', 'getPublication - Publication not found: ID=' . $id);
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Publication not found'
                ]);
            }

            $denied = $this->facultyAdminPublicationForbiddenResponse((int) $id);
            if ($denied !== null) {
                return $denied;
            }

            // Get authors for this publication with user info
            $authors = $this->db->table('publication_authors pa')
                ->select('pa.*,
                          u1.thai_name as user_thai_name,
                          u1.thai_lastname as user_thai_lastname,
                          u1.titleThai as user_title_thai,
                          a.email as author_email_from_authors,
                          u2.thai_name as author_user_thai_name,
                          u2.thai_lastname as author_user_thai_lastname,
                          u2.titleThai as author_user_title_thai')
                ->join('user u1', 'pa.author_email = u1.email', 'left')
                ->join('authors a', 'pa.author_id = a.id', 'left')
                ->join('user u2', 'a.user_email = u2.email', 'left')
                ->where('pa.publication_id', $id)
                ->orderBy('pa.author_order', 'ASC')
                ->get()
                ->getResultArray();

            // Process authors to get proper names
            foreach ($authors as &$author) {
                // Priority: uid from user table > author_id from authors+user > name from publication_authors
                if (!empty($author['author_email']) && !empty($author['user_thai_name'])) {
                    // Use user table Thai name (direct link via author_email)
                    $fullName = trim($author['user_thai_name'] . ' ' . ($author['user_thai_lastname'] ?? ''));
                    if (!empty($author['user_title_thai'])) {
                        $fullName = $author['user_title_thai'] . $fullName;
                    }
                    $author['author_name'] = $fullName;
                } elseif (!empty($author['author_id']) && !empty($author['author_user_thai_name'])) {
                    // Use authors table linked to user (via authors.user_uid)
                    $fullName = trim($author['author_user_thai_name'] . ' ' . ($author['author_user_thai_lastname'] ?? ''));
                    if (!empty($author['author_user_title_thai'])) {
                        $fullName = $author['author_user_title_thai'] . $fullName;
                    }
                    $author['author_name'] = $fullName;
                    if (!empty($author['author_email_from_authors'])) {
                        $author['author_email'] = $author['author_email_from_authors'];
                    }
                } else {
                    // Use name from publication_authors directly
                    if (empty($author['author_name'])) {
                        $author['author_name'] = $author['author_name'] ?? '';
                    }
                }
            }

            $publication['authors'] = $authors;

            log_message('info', 'getPublication - Authors count: ' . count($authors));
            log_message('info', 'getPublication - SUCCESS');
            log_message('info', '========== getPublication END ==========');

            return $this->response->setJSON([
                'success' => true,
                'data' => $publication
            ]);
        } catch (\Exception $e) {
            log_message('error', '========== getPublication ERROR ==========');
            log_message('error', 'getPublication error: ' . $e->getMessage());
            log_message('error', 'Stack trace: ' . $e->getTraceAsString());
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Set publication approval status (3-level system)
     * Route: POST /admin/publications/set_approval_status/{id}
     * Supports: null (pending), 0 (rejected), 1 (approved)
     */
    public function set_approval_status($id = null)
    {
        if (!$id) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Publication ID is required'
            ]);
        }

        try {
            // Check user authorization
            $userData = $this->session->get('user_data');
            if (!$userData) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Unauthorized'
                ]);
            }

            // Get approval status from request body
            $input = $this->request->getJSON(true);
            $approvalStatus = $input['status'] ?? null;

            // Validate approval status: must be null, 0, or 1
            if ($approvalStatus !== null && !in_array($approvalStatus, [0, 1, '0', '1'], true)) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Invalid approval status. Must be null, 0, or 1'
                ]);
            }

            // Convert string to int if needed (but keep null as null)
            if ($approvalStatus !== null) {
                $approvalStatus = (int)$approvalStatus;
            }

            // Get publication
            $publication = $this->publicationModel->find($id);
            if (!$publication) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Publication not found'
                ]);
            }

            $denied = $this->facultyAdminPublicationForbiddenResponse((int) $id);
            if ($denied !== null) {
                return $denied;
            }

            // Get status text for logging
            $statusText = match ($approvalStatus) {
                1 => 'Approved (Meets กพอ Criteria)',
                0 => 'Rejected (Does Not Meet กพอ Criteria)',
                null => 'Pending Review',
                default => 'Unknown'
            };

            // Update approval status
            $this->publicationModel->update($id, [
                'approve' => $approvalStatus
            ]);

            // Log the action with user details
            log_message('info', sprintf(
                'Publication ID %d ("%s") approval status changed to: %s by user %d (%s)',
                $id,
                substr($publication['title'] ?? 'Unknown', 0, 50),
                $statusText,
                $userData['uid'] ?? 'unknown',
                $userData['email'] ?? 'unknown'
            ));

            return $this->response->setJSON([
                'success' => true,
                'message' => 'Publication approval status updated successfully',
                'status' => $approvalStatus,
                'status_text' => $statusText
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Set publication approval status error: ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Failed to update approval status: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Approve publication (Backward compatibility wrapper)
     * Route: POST /admin/publications/approve/{id}
     * This now calls setPublicationApprovalStatus with status=1
     */
    public function approvePublication($id = null)
    {
        if (!$id) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Publication ID is required'
            ]);
        }

        try {
            $userData = $this->session->get('user_data');
            if (!$userData) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Unauthorized'
                ]);
            }

            // Get publication
            $publication = $this->publicationModel->find($id);
            if (!$publication) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Publication not found'
                ]);
            }

            $denied = $this->facultyAdminPublicationForbiddenResponse((int) $id);
            if ($denied !== null) {
                return $denied;
            }

            // Update approval status to 1 (approved)
            $this->publicationModel->update($id, [
                'approve' => 1
            ]);

            // Log for backward compatibility
            log_message('info', sprintf(
                'Publication ID %d approved (legacy method) by user %d (%s)',
                $id,
                $userData['uid'] ?? 'unknown',
                $userData['email'] ?? 'unknown'
            ));

            return $this->response->setJSON([
                'success' => true,
                'message' => 'Publication approved successfully'
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Approve publication error: ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Failed to approve publication: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Update publication
     * Route: POST /admin/publications/update/{id}
     */
    public function updatePublication($id = null)
    {
        if (!$id) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Publication ID required'
            ]);
        }

        try {
            // Check if publication exists
            $existing = $this->publicationModel->find($id);
            if (!$existing) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Publication not found'
                ]);
            }

            $denied = $this->facultyAdminPublicationForbiddenResponse((int) $id);
            if ($denied !== null) {
                return $denied;
            }

            $this->db->transStart();

            // Normalize year to CE (ค.ศ.) for database storage
            $publicationYear = $this->request->getPost('publication_year');
            $publicationYear = normalize_year_to_ce($publicationYear);

            // Update publication data
            $updateData = [
                'title' => $this->request->getPost('title'),
                'abstract' => $this->request->getPost('abstract'),
                'publication_type' => $this->request->getPost('publication_type'),
                'source' => $this->request->getPost('source'),
                'publication_year' => $publicationYear,
                'publication_month' => $this->request->getPost('publication_month') ?: null,
                'volume' => $this->request->getPost('volume') ?: null,
                'issue' => $this->request->getPost('issue') ?: null,
                'pages' => $this->request->getPost('pages') ?: null,
                'doi' => $this->request->getPost('doi') ?: null,
                'isbn' => $this->request->getPost('isbn') ?: null,
                'publisher' => $this->request->getPost('publisher') ?: null,
                'conference_name' => $this->request->getPost('conference_name') ?: null,
                'conference_location' => $this->request->getPost('conference_location') ?: null,
                'conference_date' => $this->request->getPost('conference_date') ?: null,
                'book_title' => $this->request->getPost('book_title') ?: null,
                'chapter' => $this->request->getPost('chapter') ?: null,
                'editor' => $this->request->getPost('editor') ?: null,
                'keywords' => $this->request->getPost('keywords') ?: null,
                'notes' => $this->request->getPost('notes') ?: null,
                'ref_url' => $this->request->getPost('ref_url') ?: null,
                'url' => $this->request->getPost('url') ?: null
            ];

            $this->publicationModel->update($id, $updateData);

            // Update authors
            $authors = $this->request->getPost('authors');
            log_message('debug', 'Authors received: ' . json_encode($authors));

            if (!empty($authors)) {
                // Handle JSON string format
                if (is_string($authors)) {
                    $authors = json_decode($authors, true);
                    log_message('debug', 'Authors after JSON decode: ' . json_encode($authors));
                }

                if (is_array($authors)) {
                    // Delete existing authors
                    $this->publicationModel->deletePublicationAuthors($id);

                    // Re-add authors using the same logic as add
                    $processedAuthors = [];
                    foreach ($authors as $index => $authorInput) {
                        if (!empty($authorInput['name'])) {
                            $authorData = [
                                'name' => $authorInput['name'],
                                'email' => $authorInput['email'] ?? null,
                                'affiliation' => $authorInput['affiliation'] ?? 'มหาวิทยาลัยราชภัฏอุตรดิตถ์',
                                'author_id' => null,
                                'corresponding' => isset($authorInput['corresponding']) && $authorInput['corresponding'] == '1' ? 1 : 0
                            ];

                            $linkedEmail = UserIdentity::normalizeEmail((string) ($authorInput['user_uid'] ?? $authorInput['uid'] ?? ''));
                            if ($linkedEmail !== '') {
                                if (!empty($authorInput['author_id'])) {
                                    $authorExists = $this->db->table('authors')
                                        ->where('id', $authorInput['author_id'])
                                        ->countAllResults();
                                    if ($authorExists > 0) {
                                        $authorData['author_id'] = $authorInput['author_id'];
                                    }
                                }
                                if (empty($authorData['email'])) {
                                    $authorData['email'] = $linkedEmail;
                                }
                            } elseif (!empty($authorInput['email'])) {
                                $existingAuthor = $this->authorModel->getAuthorlinkUser($authorInput['email']);
                                if ($existingAuthor) {
                                    $authorData['author_id'] = $existingAuthor['id'];
                                    $authorData['email'] = $existingAuthor['email'] ?? $authorInput['email'];
                                }
                            }

                            $processedAuthors[] = $authorData;
                        }
                    }

                    log_message('debug', 'Processed authors: ' . json_encode($processedAuthors));

                    // Insert updated authors
                    if (!empty($processedAuthors)) {
                        $publicationAuthorModel = new \App\Models\PublicationAuthorModel();
                        $result = $publicationAuthorModel->addAuthorsToPublication($id, $processedAuthors);
                        log_message('debug', 'Insert result: ' . json_encode($result));

                        if ($result === false) {
                            $dbError = $this->db->error();
                            log_message('error', 'Failed to insert authors: ' . json_encode($dbError));
                            throw new \Exception('Failed to insert authors: ' . ($dbError['message'] ?? 'Unknown error'));
                        }
                    }
                }
            }

            $this->db->transComplete();

            if ($this->db->transStatus() === false) {
                // Get the actual database error
                $error = $this->db->error();
                $errorMsg = 'Transaction failed';
                if (!empty($error['message'])) {
                    $errorMsg .= ': ' . $error['message'];
                }
                log_message('error', 'Transaction error: ' . json_encode($error));
                throw new \Exception($errorMsg);
            }

            return $this->response->setJSON([
                'success' => true,
                'message' => 'Publication updated successfully'
            ]);
        } catch (\Exception $e) {
            $this->db->transRollback();
            log_message('error', 'Update publication error: ' . $e->getMessage());
            log_message('error', 'Stack trace: ' . $e->getTraceAsString());

            return $this->response->setJSON([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * User management
     */
    public function users()
    {
        $data = [
            'title' => 'User Management',
            'users' => $this->userModel->findAll()
        ];

        return view('admin/users/index', $data);
    }

    // Private helper methods

    /**
     * Get basic stats
     */
    private function getStats()
    {
        $db = \Config\Database::connect();

        // Count total publications from publication_view
        $totalPublications = $db->table('publication_view')->countAll();

        // Count total unique authors who are:
        // 1. Teachers in curriculum (teacher_curriculum) AND have publications
        // 2. OR authors with publications in faculties
        $authorQuery = $db->query("
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
        $totalAuthors = $authorQuery->getRowArray()['count'] ?? 0;

        // Debug logging
        log_message('debug', "Publications count from publication_view: {$totalPublications}");
        log_message('debug', "Authors count (teachers in curriculum with publications): {$totalAuthors}");

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
     * Get curricula statistics grouped by degree level
     */
    private function getCurriculaStats(array $facultyIds = [])
    {
        $db = \Config\Database::connect();
        $builder = $db->table('curriculum');
        $builder->where('status', 1);

        if (!empty($facultyIds)) {
            $builder->whereIn('faculty_id', $facultyIds);
        }

        $total = $builder->countAllResults(false);

        // Count by degree level
        $builder->select('degree_level, COUNT(*) as count');
        $builder->where('status', 1);
        if (!empty($facultyIds)) {
            $builder->whereIn('faculty_id', $facultyIds);
        }
        $builder->groupBy('degree_level');
        $degreeCounts = $builder->get()->getResultArray();

        $stats = [
            'total' => $total,
            'bachelor' => 0,
            'master' => 0,
            'doctoral' => 0
        ];

        foreach ($degreeCounts as $row) {
            $level = $row['degree_level'] ?? '';
            if (isset($stats[$level])) {
                $stats[$level] = (int)$row['count'];
            }
        }

        return $stats;
    }

    /**
     * Get admission form statistics
     */
    private function getAdmissionStats(array $facultyIds = [])
    {
        $db = \Config\Database::connect();
        $builder = $db->table('student_admission_forms');

        if (!empty($facultyIds)) {
            $builder->whereIn('faculty_id', $facultyIds);
        }

        $total = $builder->countAllResults(false);

        // Count by status
        $builder->select('status, COUNT(*) as count');
        if (!empty($facultyIds)) {
            $builder->whereIn('faculty_id', $facultyIds);
        }
        $builder->groupBy('status');
        $statusCounts = $builder->get()->getResultArray();

        $stats = [
            'total' => $total,
            'draft' => 0,
            'submitted' => 0,
            'approved' => 0,
            'rejected' => 0
        ];

        foreach ($statusCounts as $row) {
            $status = $row['status'] ?? 'draft';
            if (isset($stats[$status])) {
                $stats[$status] = (int)$row['count'];
            }
        }

        return $stats;
    }

    /**
     * Get education statistics (percentage of users with education history)
     */
    private function getEducationStats(array $facultyIds = [])
    {
        $db = \Config\Database::connect();

        // Count total active users (teachers)
        $userBuilder = $db->table('user u');
        $userBuilder->select('u.email');
        $userBuilder->join('teacher_curriculum tc', 'tc.teacher_email = u.email AND tc.status = 1', 'inner');
        $userBuilder->where('u.active', 1);
        $userBuilder->distinct();

        if (!empty($facultyIds)) {
            $userBuilder->join('curriculum c', 'c.id = tc.curriculum_id', 'inner');
            $userBuilder->whereIn('c.faculty_id', $facultyIds);
        }

        $totalUsers = $userBuilder->countAllResults(false);

        // Count users with education entries
        $eduBuilder = $db->table('user u');
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
     */
    private function getPublicationsThisYear(array $facultyIds = [])
    {
        $db = \Config\Database::connect();
        $currentYear = date('Y');

        $builder = $db->table('publications p');
        $builder->where('p.publication_year', $currentYear);

        if (!empty($facultyIds)) {
            // Join to get faculty info
            $builder->join('user u', 'u.email = p.created_by_email', 'left');
            $builder->join('curriculum c', 'c.id = u.curriculum_id', 'left');
            $builder->whereIn('c.faculty_id', $facultyIds);
        }

        return $builder->countAllResults();
    }

    /**
     * Get statistics by faculties
     */
    private function getStatsByFaculties(array $facultyIds)
    {
        if (empty($facultyIds)) {
            return [
                'total_publications' => 0,
                'total_authors' => 0
            ];
        }

        $db = \Config\Database::connect();

        // Count publications from managed faculties
        $totalPublications = $db->table('publication_view')
            ->whereIn('faculty_id', $facultyIds)
            ->countAllResults();

        // Count unique authors who are teachers in curricula of managed faculties AND have publications
        $facultyIdsStr = implode(',', array_map('intval', $facultyIds));
        $authorQuery = $db->query("
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
        $totalAuthors = $authorQuery->getRowArray()['count'] ?? 0;

        return [
            'total_publications' => $totalPublications,
            'total_authors' => $totalAuthors
        ];
    }

    /**
     * Get statistics by user
     */
    private function getStatsByUser(string $userEmail)
    {
        $db = \Config\Database::connect();
        $userEmail = UserIdentity::normalizeEmail($userEmail);

        // Count user's publications using author_email (same condition as getPublicationsByAuthor)
        $userEmails = [];
        $user = $this->userModel->find($userEmail);
        if ($user && !empty($user['email'])) {
            $userEmails[] = UserIdentity::normalizeEmail((string) $user['email']);
        }

        $authorEmails = $db->table('authors')
            ->select('email')
            ->where('user_email', $userEmail)
            ->where('email IS NOT NULL')
            ->where('email !=', '')
            ->get()
            ->getResultArray();

        foreach ($authorEmails as $authorEmail) {
            if (!empty($authorEmail['email']) && !in_array($authorEmail['email'], $userEmails, true)) {
                $userEmails[] = $authorEmail['email'];
            }
        }

        $publicationIds = $db->table('publication_authors pa')
            ->select('pa.publication_id')
            ->distinct()
            ->join('authors a', 'pa.author_id = a.id', 'left');

        if (!empty($userEmails)) {
            $publicationIds->groupStart()
                ->whereIn('pa.author_email', $userEmails)
                ->orWhereIn('a.email', $userEmails)
                ->groupEnd();
        } else {
            $publicationIds->where('pa.author_email', $userEmail);
        }

        $ids = array_column($publicationIds->get()->getResultArray(), 'publication_id');
        $totalPublications = !empty($ids) ? count($ids) : 0;

        // Count unique authors from user's publications
        $authorQuery = $db->query("
            SELECT COUNT(DISTINCT pa.author_id) as count
            FROM publication_authors pa
            INNER JOIN publications p ON p.id = pa.publication_id
            WHERE pa.author_id IS NOT NULL
            AND p.id IN (" . (!empty($ids) ? implode(',', array_map('intval', $ids)) : '0') . ")
        ");
        $totalAuthors = $authorQuery->getRowArray()['count'] ?? 0;

        return [
            'total_publications' => $totalPublications,
            'total_authors' => $totalAuthors
        ];
    }

    /**
     * Get all publications with basic info
     */
    private function getAllPublications()
    {
        $db = \Config\Database::connect();

        $query = $db->query("
            SELECT 
                p.*,
                u.gf_name,
                u.gl_name,
                GROUP_CONCAT(pa.author_name ORDER BY pa.author_order SEPARATOR ', ') as authors
            FROM publications p
            LEFT JOIN user u ON p.created_by_email = u.email
            LEFT JOIN publication_authors pa ON p.id = pa.publication_id
            GROUP BY p.id
            ORDER BY p.created_at DESC
        ");

        return $query->getResultArray();
    }

    /**
     * Manage User Curriculum Assignment
     * Displays the drag-and-drop interface for assigning users to curriculums
     */
    public function manageuserCuriculum()
    {
        $data = [
            'title' => 'Manage User Curriculum Assignment'
        ];

        return view('admin/manageuserCuriculum/curiculumuser', $data);
    }

    /**
     * Manage User Emails
     * Displays the email management interface for managing secondary user emails
     */
    public function manageEmails()
    {
        $data = [
            'title' => 'จัดการอีเมลผู้ใช้'
        ];

        return view('admin/manageUser/manageEmail', $data);
    }

    /**
     * Get all users with their secondary emails (AJAX)
     * Returns users with primary email from user table and secondary emails from authors table
     */
    public function getUsersWithEmails()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setJSON(['success' => false, 'message' => 'Invalid request']);
        }

        try {
            $search = $this->request->getGet('search');

            if ($search) {
                $users = $this->userModel->searchUsersWithCurriculum($search);
            } else {
                $users = $this->userModel->getActiveUsers();
            }

            // Enrich users with secondary emails from authors table
            foreach ($users as &$user) {
                $secondaryEmails = $this->authorModel->getAuthorEmailsByUser($user['email']);
                $user['uid'] = $user['email'];
                $user['secondary_emails'] = implode(',', $secondaryEmails);
                $user['username'] = $user['gf_name'] . ' ' . $user['gl_name'];
                $user['primary_email'] = $user['email'];
            }

            return $this->response->setJSON([
                'success' => true,
                'data' => $users
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Get users with emails error: ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Failed to load users with emails'
            ]);
        }
    }

    /**
     * Add secondary email to user (AJAX)
     * Adds email to authors table, NOT user table
     */
    public function addSecondaryEmail()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setJSON(['success' => false, 'message' => 'Invalid request']);
        }

        try {
            $userEmail = UserIdentity::normalizeEmail((string) ($this->request->getPost('user_uid') ?? ''));
            $email = $this->request->getPost('email');

            if (!$userEmail || !$email) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'User ID and email are required'
                ]);
            }

            // Validate email format
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Invalid email format'
                ]);
            }

            // Check if user exists
            $user = $this->userModel->find($userEmail);
            if (!$user) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'User not found'
                ]);
            }

            // Check if email is the same as primary email
            if ($user['email'] === $email) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'This email is already the primary email'
                ]);
            }

            // Check if email already exists in authors table for this user
            $existingAuthor = $this->authorModel->where('user_email', $userEmail)
                ->where('email', $email)
                ->first();

            if ($existingAuthor) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'This email already exists for this user'
                ]);
            }

            // Check if email exists for another user
            $otherUser = $this->userModel->getUserByEmail($email);
            if ($otherUser) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'This email is already used by another user'
                ]);
            }

            // Add email to authors table
            $authorData = [
                'email' => $email,
                'user_email' => $userEmail,
                'created_by_email' => $userEmail
            ];

            $inserted = $this->authorModel->insert($authorData);

            if ($inserted) {
                return $this->response->setJSON([
                    'success' => true,
                    'message' => 'Secondary email added successfully'
                ]);
            }

            return $this->response->setJSON([
                'success' => false,
                'message' => 'Failed to add secondary email'
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Add secondary email error: ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Error adding secondary email: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Update secondary email (AJAX)
     * Updates email in authors table only
     */
    public function updateSecondaryEmail()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setJSON(['success' => false, 'message' => 'Invalid request']);
        }

        try {
            $authorId = $this->request->getPost('author_id');
            $newEmail = $this->request->getPost('email');
            $userEmail = UserIdentity::normalizeEmail((string) ($this->request->getPost('user_uid') ?? ''));

            if (!$authorId || !$newEmail || $userEmail === '') {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Author ID, user email, and email are required'
                ]);
            }

            // Validate email format
            if (!filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Invalid email format'
                ]);
            }

            // Check if author exists and belongs to this user
            $author = $this->authorModel->find($authorId);
            if (!$author || UserIdentity::normalizeEmail((string) ($author['user_email'] ?? '')) !== $userEmail) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Author not found or unauthorized'
                ]);
            }

            // Get user data
            $user = $this->userModel->find($userEmail);
            if (!$user) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'User not found'
                ]);
            }

            // Check if new email is the same as primary email
            if ($user['email'] === $newEmail) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'This email is already the primary email'
                ]);
            }

            // Check if new email already exists for this user (in authors table)
            $existingAuthor = $this->authorModel->where('user_email', $userEmail)
                ->where('email', $newEmail)
                ->where('id !=', $authorId)
                ->first();

            if ($existingAuthor) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'This email already exists for this user'
                ]);
            }

            // Check if email exists for another user
            $otherUser = $this->userModel->getUserByEmail($newEmail);
            if ($otherUser) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'This email is already used by another user'
                ]);
            }

            // Update email
            $updated = $this->authorModel->update($authorId, ['email' => $newEmail]);

            if ($updated) {
                return $this->response->setJSON([
                    'success' => true,
                    'message' => 'Secondary email updated successfully'
                ]);
            }

            return $this->response->setJSON([
                'success' => false,
                'message' => 'Failed to update secondary email'
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Update secondary email error: ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Error updating secondary email: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Delete secondary email (AJAX)
     * Deletes email from authors table only
     */
    public function deleteSecondaryEmail()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setJSON(['success' => false, 'message' => 'Invalid request']);
        }

        try {
            $authorId = $this->request->getPost('author_id');
            $userEmail = UserIdentity::normalizeEmail((string) ($this->request->getPost('user_uid') ?? ''));

            if (!$authorId || $userEmail === '') {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Author ID and user email are required'
                ]);
            }

            // Check if author exists and belongs to this user
            $author = $this->authorModel->find($authorId);
            if (!$author || UserIdentity::normalizeEmail((string) ($author['user_email'] ?? '')) !== $userEmail) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Author not found or unauthorized'
                ]);
            }

            // Delete author record
            $deleted = $this->authorModel->delete($authorId);

            if ($deleted) {
                return $this->response->setJSON([
                    'success' => true,
                    'message' => 'Secondary email deleted successfully'
                ]);
            }

            return $this->response->setJSON([
                'success' => false,
                'message' => 'Failed to delete secondary email'
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Delete secondary email error: ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Error deleting secondary email: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Get secondary emails for a specific user (AJAX)
     */
    public function getSecondaryEmails()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setJSON(['success' => false, 'message' => 'Invalid request']);
        }

        try {
            $userEmail = UserIdentity::normalizeEmail((string) ($this->request->getGet('user_uid') ?? ''));

            if ($userEmail === '') {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'User email is required'
                ]);
            }

            $authors = $this->authorModel->getUserAuthorProfiles($userEmail);

            return $this->response->setJSON([
                'success' => true,
                'data' => $authors
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Get secondary emails error: ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Failed to load secondary emails'
            ]);
        }
    }

    // ==================== Faculty & Curriculum Management ====================

    /**
     * Display Faculty & Curriculum Management View
     */
    public function manageFacultyCurriculum()
    {
        return view('admin/faculty_curriculum');
    }

    /**
     * Get all faculties with curriculum count
     * Filters based on user role
     */
    public function getFaculties()
    {
        try {
            // Check god mode first - bypass user lookup if god mode is active
            if ($this->session->get('god_mode') === true) {
                $faculties = $this->facultyModel->getWithCurriculumCount();
                // Format dean information for each faculty
                foreach ($faculties as &$faculty) {
                    $faculty['dean_info'] = $this->formatDeanInfo($faculty);
                }
                return $this->response->setJSON([
                    'success' => true,
                    'data' => $faculties
                ]);
            }

            // Get current user only if not in god mode
            $userId = $this->session->get('user_id');
            $user = $this->userModel->find($userId);

            if (!$user) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'User not found'
                ]);
            }

            // Get user data from session for role checking
            $userData = $this->session->get('user_data') ?? [];
            $userRole = $userData['role'] ?? $user['role'] ?? null;

            // Check if user is super admin
            if ($userRole === 'super_admin' || (isset($user['admin']) && $user['admin'] == 1)) {
                // Super admin sees all faculties
                $faculties = $this->facultyModel->getWithCurriculumCount();
            } elseif ($userRole === 'faculty_admin') {
                // Faculty admin sees only assigned faculties
                $managedFaculties = \App\Helpers\RoleHelper::getManagedFaculties($userData);

                if (!empty($managedFaculties)) {
                    // Get only managed faculties with dean info
                    $faculties = $this->db->table('faculties')
                        ->select('faculties.*, 
                            COUNT(curriculum.id) as curriculum_count,
                            dean.email as dean_uid,
                            dean.titleThai as dean_title,
                            dean.title as dean_title_en,
                            dean.thai_name as dean_name,
                            dean.thai_lastname as dean_lastname,
                            dean.gf_name as dean_gf_name,
                            dean.gl_name as dean_gl_name')
                        ->join('curriculum', 'curriculum.faculty_id = faculties.id', 'left')
                        ->join('user as dean', 'dean.email = faculties.dean_email', 'left')
                        ->whereIn('faculties.id', $managedFaculties)
                        ->groupBy('faculties.id')
                        ->get()
                        ->getResultArray();
                } else {
                    // Faculty admin without assigned faculties - return empty
                    $faculties = [];
                }
            } else {
                // Regular users see no faculties
                $faculties = [];
            }

            // Format dean information for each faculty
            foreach ($faculties as &$faculty) {
                $faculty['dean_info'] = $this->formatDeanInfo($faculty);
            }

            return $this->response->setJSON([
                'success' => true,
                'data' => $faculties
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Get faculties error: ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Failed to load faculties: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Get all curricula with faculty info
     * Filters by faculty admin permissions if applicable
     */
    public function getCurricula()
    {
        try {
            $facultyId = $this->request->getGet('faculty_id');

            // Get user data from session
            $userData = $this->session->get('user_data') ?? [];
            $userId = UserIdentity::sessionEmail();
            if ($userId === '') {
                $userId = UserIdentity::normalizeEmail((string) ($userData['email'] ?? ''));
            }

            // Get full user data from database and merge with session data (same as getPublicationSummaryData)
            $user = $userData;
            if ($userId !== '') {
                $dbUser = $this->userModel->find($userId);
                if ($dbUser) {
                    $user = array_merge($dbUser, $userData);
                }
            }

            // Check user role and permissions
            $userRole = $user['role'] ?? null;
            $isGodMode = $this->session->get('god_mode') === true;
            $isAdmin = $user['is_admin'] ?? false;
            $adminFlag = $user['admin'] ?? 0;

            // Super admin includes god_mode, super_admin role, or admin flags
            $isSuperAdmin = ($userRole === 'super_admin') || $isGodMode || $isAdmin || ($adminFlag == 1);
            $isFacultyAdmin = ($userRole === 'faculty_admin') && !$isGodMode;

            // Debug log
            log_message('debug', 'getCurricula - userRole: ' . $userRole . ', isGodMode: ' . ($isGodMode ? 'true' : 'false') . ', isSuperAdmin: ' . ($isSuperAdmin ? 'true' : 'false') . ', isFacultyAdmin: ' . ($isFacultyAdmin ? 'true' : 'false'));

            // Get managed faculties for faculty admin
            $managedFaculties = [];
            if ($isFacultyAdmin) {
                $managedFaculties = \App\Helpers\RoleHelper::getManagedFaculties($user);
                log_message('debug', 'getCurricula - Faculty admin managed faculties: ' . json_encode($managedFaculties));

                // If faculty admin has no managed faculties, return empty
                if (empty($managedFaculties)) {
                    return $this->response->setJSON([
                        'success' => true,
                        'data' => []
                    ]);
                }
            }

            // Build query
            if ($isSuperAdmin) {
                // Super admin sees all curricula
                if ($facultyId) {
                    $curricula = $this->curriculumModel->getByFaculty($facultyId);
                } else {
                    $curricula = $this->curriculumModel->getWithFaculty();
                }
            } elseif ($isFacultyAdmin && !empty($managedFaculties)) {
                // Faculty admin sees only curricula from managed faculties
                $curricula = $this->curriculumModel->select('curriculum.*, faculties.name as faculty_name, faculties.code as faculty_code')
                    ->join('faculties', 'faculties.id = curriculum.faculty_id')
                    ->where('curriculum.status', 1)
                    ->whereIn('curriculum.faculty_id', $managedFaculties);

                // Additional filter by specific faculty if provided
                if ($facultyId && in_array($facultyId, $managedFaculties)) {
                    $curricula->where('curriculum.faculty_id', $facultyId);
                }

                $curricula = $curricula->orderBy('faculties.name', 'ASC')
                    ->orderBy('curriculum.name', 'ASC')
                    ->findAll();
            } else {
                // Regular users see no curricula
                log_message('debug', 'getCurricula - User has no admin permissions, returning empty array');
                $curricula = [];
            }

            log_message('debug', 'getCurricula - Returning ' . count($curricula) . ' curricula');

            return $this->response->setJSON([
                'success' => true,
                'data' => $curricula
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Get curricula error: ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Failed to load curricula'
            ]);
        }
    }

    /**
     * Create new faculty
     */
    public function createFaculty()
    {
        try {
            $data = $this->request->getJSON(true);

            // Check if code already exists
            $existingFaculty = $this->facultyModel->where('code', $data['code'])->first();
            if ($existingFaculty) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'รหัสคณะนี้มีอยู่ในระบบแล้ว กรุณาใช้รหัสอื่น'
                ]);
            }

            $facultyData = [
                'code' => $data['code'],
                'name' => $data['name'],
                'status' => isset($data['status']) && $data['status'] ? 1 : 0,
                'dean_id' => !empty($data['dean_id']) ? $data['dean_id'] : null
            ];

            if ($this->facultyModel->insert($facultyData)) {
                return $this->response->setJSON([
                    'success' => true,
                    'message' => 'Faculty created successfully'
                ]);
            }

            // Get validation errors
            $errors = $this->facultyModel->errors();

            return $this->response->setJSON([
                'success' => false,
                'message' => 'Failed to create faculty',
                'errors' => $errors
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Create faculty error: ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Update faculty
     */
    public function updateFaculty()
    {
        try {
            $data = $this->request->getJSON(true);
            $id = $data['id'] ?? null;

            log_message('debug', 'Update faculty request: ' . json_encode($data));

            if (!$id) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Faculty ID is required'
                ]);
            }

            // Get current faculty data
            $currentFaculty = $this->facultyModel->find($id);

            // Check if code has changed
            if ($currentFaculty['code'] !== $data['code']) {
                // Code has changed, check if new code is unique
                $existingFaculty = $this->facultyModel->where('code', $data['code'])->first();
                if ($existingFaculty) {
                    return $this->response->setJSON([
                        'success' => false,
                        'message' => 'รหัสคณะนี้มีอยู่ในระบบแล้ว กรุณาใช้รหัสอื่น'
                    ]);
                }
            }

            $facultyData = [
                'code' => $data['code'],
                'name' => $data['name'],
                'status' => isset($data['status']) && $data['status'] ? 1 : 0,
                'dean_id' => !empty($data['dean_id']) ? $data['dean_id'] : null
            ];

            log_message('debug', 'Faculty data to update: ' . json_encode($facultyData));

            // Skip validation since we already validated manually above
            $this->facultyModel->skipValidation(true);
            $result = $this->facultyModel->update($id, $facultyData);
            $this->facultyModel->skipValidation(false);

            if ($result) {
                log_message('debug', 'Faculty updated successfully, ID: ' . $id);
                return $this->response->setJSON([
                    'success' => true,
                    'message' => 'Faculty updated successfully'
                ]);
            }

            // Get validation errors if update failed
            $errors = $this->facultyModel->errors();
            log_message('error', 'Update faculty failed - Validation errors: ' . json_encode($errors));

            return $this->response->setJSON([
                'success' => false,
                'message' => 'Failed to update faculty',
                'errors' => $errors
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Update faculty error: ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Delete faculty
     */
    public function deleteFaculty()
    {
        try {
            $data = $this->request->getJSON(true);
            $id = $data['id'] ?? null;

            if (!$id) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Faculty ID is required'
                ]);
            }

            // Check if faculty has curricula
            $curricula = $this->curriculumModel->getByFaculty($id);
            if (count($curricula) > 0) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Cannot delete faculty with existing curricula'
                ]);
            }

            if ($this->facultyModel->delete($id)) {
                return $this->response->setJSON([
                    'success' => true,
                    'message' => 'Faculty deleted successfully'
                ]);
            }

            return $this->response->setJSON([
                'success' => false,
                'message' => 'Failed to delete faculty'
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Delete faculty error: ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Toggle faculty status
     */
    public function toggleFacultyStatus()
    {
        try {
            $data = $this->request->getJSON(true);
            $id = $data['id'] ?? null;

            if (!$id) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Faculty ID is required'
                ]);
            }

            $faculty = $this->facultyModel->find($id);
            if (!$faculty) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Faculty not found'
                ]);
            }

            $newStatus = $faculty['status'] == 1 ? 0 : 1;

            if ($this->facultyModel->update($id, ['status' => $newStatus])) {
                return $this->response->setJSON([
                    'success' => true,
                    'message' => 'Faculty status updated successfully'
                ]);
            }

            return $this->response->setJSON([
                'success' => false,
                'message' => 'Failed to update faculty status'
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Toggle faculty status error: ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Create new curriculum
     */
    public function createCurriculum()
    {
        try {
            $data = $this->request->getJSON(true);

            $curriculumData = [
                'faculty_id' => $data['faculty_id'],
                'code' => $data['code'],
                'name' => $data['name'],
                'degree_level' => $data['degree_level'],
                'status' => isset($data['status']) ? 1 : 0
            ];

            if ($this->curriculumModel->insert($curriculumData)) {
                return $this->response->setJSON([
                    'success' => true,
                    'message' => 'Curriculum created successfully'
                ]);
            }

            return $this->response->setJSON([
                'success' => false,
                'message' => 'Failed to create curriculum'
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Create curriculum error: ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Update curriculum
     */
    public function updateCurriculum()
    {
        try {
            $data = $this->request->getJSON(true);
            $id = $data['id'] ?? null;

            if (!$id) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Curriculum ID is required'
                ]);
            }

            $curriculumData = [
                'faculty_id' => $data['faculty_id'],
                'code' => $data['code'],
                'name' => $data['name'],
                'degree_level' => $data['degree_level'],
                'status' => isset($data['status']) ? 1 : 0
            ];

            if ($this->curriculumModel->update($id, $curriculumData)) {
                return $this->response->setJSON([
                    'success' => true,
                    'message' => 'Curriculum updated successfully'
                ]);
            }

            return $this->response->setJSON([
                'success' => false,
                'message' => 'Failed to update curriculum'
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Update curriculum error: ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Delete curriculum
     */
    public function deleteCurriculum()
    {
        try {
            $data = $this->request->getJSON(true);
            $id = $data['id'] ?? null;

            if (!$id) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Curriculum ID is required'
                ]);
            }

            // Check if curriculum has users
            $users = $this->userModel->where('curriculum_id', $id)->findAll();
            if (count($users) > 0) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Cannot delete curriculum with assigned users'
                ]);
            }

            if ($this->curriculumModel->delete($id)) {
                return $this->response->setJSON([
                    'success' => true,
                    'message' => 'Curriculum deleted successfully'
                ]);
            }

            return $this->response->setJSON([
                'success' => false,
                'message' => 'Failed to delete curriculum'
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Delete curriculum error: ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Toggle curriculum status
     */
    public function toggleCurriculumStatus()
    {
        try {
            $data = $this->request->getJSON(true);
            $id = $data['id'] ?? null;

            if (!$id) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Curriculum ID is required'
                ]);
            }

            $curriculum = $this->curriculumModel->find($id);
            if (!$curriculum) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Curriculum not found'
                ]);
            }

            $newStatus = $curriculum['status'] == 1 ? 0 : 1;

            if ($this->curriculumModel->update($id, ['status' => $newStatus])) {
                return $this->response->setJSON([
                    'success' => true,
                    'message' => 'Curriculum status updated successfully'
                ]);
            }

            return $this->response->setJSON([
                'success' => false,
                'message' => 'Failed to update curriculum status'
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Toggle curriculum status error: ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ]);
        }
    }

    // ========== USER ROLE MANAGEMENT ==========

    /**
     * Display user roles management page
     * Only super admins can access this
     */
    public function manageUserRoles()
    {
        // Only super admins or god mode can access user roles management
        $userRole = $this->session->get('user_data')['role'] ?? 'user';
        $isGodMode = $this->session->get('god_mode') || $this->session->get('backdoor_session');

        if ($userRole !== 'super_admin' && !$isGodMode) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        return view('admin/user_roles');
    }

    /**
     * Get all users with role information
     */
    public function getUsersWithRole()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setJSON(['success' => false, 'message' => 'Invalid request']);
        }

        try {
            $users = $this->userModel->getUsersWithRole();

            return $this->response->setJSON([
                'success' => true,
                'data' => $users
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Get users with role error: ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Update user role and managed faculties
     */
    public function updateUserRole()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setJSON(['success' => false, 'message' => 'Invalid request']);
        }

        // Only super admins or god mode can update user roles
        $userRole = $this->session->get('user_data')['role'] ?? 'user';
        $isGodMode = $this->session->get('god_mode') || $this->session->get('backdoor_session');

        if ($userRole !== 'super_admin' && !$isGodMode) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Unauthorized: Only super admins can update user roles'
            ]);
        }

        try {
            log_message('info', '========== updateUserRole START ==========');

            $data = $this->request->getJSON(true);
            log_message('info', 'updateUserRole - Raw request data: ' . json_encode($data));

            // Support both user_id and email for finding user
            $userId = $data['user_id'] ?? null;
            $userEmail = $data['user_email'] ?? null;
            $userType = $data['user_type'] ?? null;
            $facultyId = $data['faculty_id'] ?? null;
            $role = $data['role'] ?? 'user';
            $managedFaculties = $data['managed_faculties'] ?? null;

            log_message('info', 'updateUserRole - Parsed data: userId=' . $userId . ', userEmail=' . $userEmail . ', userType=' . $userType . ', facultyId=' . $facultyId . ', role=' . $role);

            // If email is provided, find user by email
            if ($userEmail && !$userId) {
                $userByEmail = $this->userModel->getUserByEmail($userEmail);
                if ($userByEmail) {
                    $userId = $userByEmail['email'];
                    log_message('info', 'updateUserRole - Found user by email: ' . $userEmail . ' -> userId=' . $userId);
                } else {
                    log_message('warning', 'updateUserRole - User not found with email: ' . $userEmail);
                    return $this->response->setJSON([
                        'success' => false,
                        'message' => 'User not found with email: ' . $userEmail
                    ]);
                }
            }

            if (!$userId) {
                log_message('warning', 'updateUserRole - Missing user ID or email');
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'User ID or email is required'
                ]);
            }

            // Validate user type
            $validUserTypes = ['TEACHER', 'STAFF', 'STUDENT'];
            if ($userType && !in_array($userType, $validUserTypes)) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Invalid user type'
                ]);
            }

            // Validate faculty requirement for teachers
            if ($userType === 'TEACHER' && empty($facultyId)) {
                log_message('warning', 'updateUserRole - Teacher missing faculty: userId=' . $userId);
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Teachers must have a faculty affiliation'
                ]);
            }

            // Staff should not have faculty
            if ($userType === 'STAFF' && !empty($facultyId)) {
                log_message('info', 'updateUserRole - Forcing faculty to null for staff: userId=' . $userId);
                $facultyId = null; // Force null for staff
            }

            // Validate system role
            $validRoles = ['user', 'faculty_admin', 'super_admin'];
            if (!in_array($role, $validRoles)) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Invalid role'
                ]);
            }

            // Validate managed faculties for faculty admin
            if ($role === 'faculty_admin') {
                if (empty($managedFaculties) || !is_array($managedFaculties)) {
                    return $this->response->setJSON([
                        'success' => false,
                        'message' => 'Faculty admin must have at least one managed faculty'
                    ]);
                }
            }

            // Update user type and/or faculty
            // Always update faculty_id directly to user table when provided
            $userUpdateData = [];

            if ($userType !== null) {
                $userUpdateData['user_type'] = $userType;
            }

            // Always update faculty_id when provided (or explicitly set to null for staff)
            if ($facultyId !== null || $userType === 'STAFF') {
                $userUpdateData['faculty_id'] = $facultyId;
            }

            if (!empty($userUpdateData)) {
                log_message('info', 'updateUserRole - Updating user data: userId=' . $userId . ', data=' . json_encode($userUpdateData));

                $userUpdateResult = $this->userModel->update($userId, $userUpdateData);

                log_message('info', 'updateUserRole - User update result: ' . ($userUpdateResult ? 'SUCCESS' : 'FAILED'));

                // Verify the update
                $verifyUser = $this->userModel->find($userId);
                log_message('info', 'updateUserRole - Verified user data: user_type=' . ($verifyUser['user_type'] ?? 'NULL') . ', faculty_id=' . ($verifyUser['faculty_id'] ?? 'NULL'));
            }

            // Update role
            log_message('info', 'updateUserRole - Updating role: userId=' . $userId . ', role=' . $role . ', managedFaculties=' . json_encode($managedFaculties));

            $updated = $this->userModel->updateRole($userId, $role, $managedFaculties);

            log_message('info', 'updateUserRole - Role update result: ' . ($updated ? 'SUCCESS' : 'FAILED'));
            log_message('info', '========== updateUserRole END ==========');

            if ($updated) {
                return $this->response->setJSON([
                    'success' => true,
                    'message' => 'User role updated successfully'
                ]);
            }

            return $this->response->setJSON([
                'success' => false,
                'message' => 'Failed to update user role'
            ]);
        } catch (\Exception $e) {
            log_message('error', '========== updateUserRole EXCEPTION ==========');
            log_message('error', 'Update user role error: ' . $e->getMessage());
            log_message('error', 'Stack trace: ' . $e->getTraceAsString());
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Publication Summary Page
     * Route: GET /admin/publications/summary
     */
    public function publicationSummary()
    {
        return view('admin/publications/summary');
    }

    /**
     * PDF Summary View Page
     * Route: GET /admin/publications/pdf-summary
     * Displays dedicated page for PDF generation
     */
    public function pdfSummaryView()
    {
        try {
            $curriculumId = $this->request->getGet('curriculum_id');

            if (!$curriculumId) {
                return redirect()->to('admin/publications/manage')->with('error', 'กรุณาระบุหลักสูตร');
            }

            // Get user data from session
            $userData = $this->session->get('user_data') ?? [];
            $sessionEmail = UserIdentity::sessionEmail();
            if ($sessionEmail === '') {
                $sessionEmail = UserIdentity::normalizeEmail((string) ($userData['email'] ?? ''));
            }

            $user = $userData;
            if ($sessionEmail !== '') {
                $dbUser = $this->userModel->find($sessionEmail);
                if ($dbUser) {
                    $user = array_merge($dbUser, $userData);
                }
            }

            // Check user role and permissions
            $userRole = $user['role'] ?? null;
            $isGodMode = $this->session->get('god_mode') === true;
            $isAdmin = $user['is_admin'] ?? false;
            $adminFlag = $user['admin'] ?? 0;
            $isSuperAdmin = ($userRole === 'super_admin') || $isGodMode || $isAdmin || ($adminFlag == 1);
            $isFacultyAdmin = ($userRole === 'faculty_admin') && !$isGodMode;
            $isDean = RoleHelper::isDean($user) && !$isGodMode;

            // Get curriculum information to verify access
            $curriculum = $this->curriculumModel->find($curriculumId);
            if (!$curriculum) {
                return redirect()->to('admin/publications/manage')->with('error', 'ไม่พบหลักสูตรที่ระบุ');
            }

            // Check permissions for faculty admin
            if ($isFacultyAdmin) {
                $managedFaculties = \App\Helpers\RoleHelper::getManagedFaculties($user);

                // Check if curriculum belongs to managed faculty
                if (empty($managedFaculties) || !in_array($curriculum['faculty_id'], $managedFaculties)) {
                    return redirect()->to('admin/publications/manage')->with('error', 'คุณไม่มีสิทธิ์เข้าถึงหลักสูตรนี้');
                }
            } elseif ($isDean) {
                // Dean has same permission as Faculty Admin - check if curriculum belongs to their faculty
                $deanFaculties = RoleHelper::getDeanFaculties($user);

                // Check if curriculum belongs to dean's faculty
                if (empty($deanFaculties) || !in_array($curriculum['faculty_id'], $deanFaculties)) {
                    return redirect()->to('admin/publications/manage')->with('error', 'คุณไม่มีสิทธิ์เข้าถึงหลักสูตรนี้');
                }
            } elseif (!$isSuperAdmin) {
                // Regular users cannot access
                return redirect()->to('admin/publications/manage')->with('error', 'คุณไม่มีสิทธิ์เข้าถึงหน้านี้');
            }

            $data = [
                'curriculum_id' => $curriculumId,
                'curriculum_name' => $curriculum['name']
            ];

            return view('admin/publications/pdf_summary', $data);
        } catch (\Exception $e) {
            log_message('error', 'PDF Summary View Error: ' . $e->getMessage());
            return redirect()->to('admin/publications/manage')->with('error', 'เกิดข้อผิดพลาด: ' . $e->getMessage());
        }
    }


    /**
     * Get curriculum report data for PDF generation
     * Route: GET /admin/publications/curriculum-report
     * Filters by faculty admin permissions if applicable
     */
    public function getCurriculumReportData()
    {
        try {
            $curriculumId = $this->request->getGet('curriculum_id');

            if (!$curriculumId) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Curriculum ID is required'
                ]);
            }

            // Get user data from session
            $userData = $this->session->get('user_data') ?? [];
            $sessionEmail = UserIdentity::sessionEmail();
            if ($sessionEmail === '') {
                $sessionEmail = UserIdentity::normalizeEmail((string) ($userData['email'] ?? ''));
            }

            $user = $userData;
            if ($sessionEmail !== '') {
                $dbUser = $this->userModel->find($sessionEmail);
                if ($dbUser) {
                    $user = array_merge($dbUser, $userData);
                }
            }

            // Check user role and permissions
            $userRole = $user['role'] ?? null;
            $isGodMode = $this->session->get('god_mode') === true;
            $isAdmin = $user['is_admin'] ?? false;
            $adminFlag = $user['admin'] ?? 0;
            $isSuperAdmin = ($userRole === 'super_admin') || $isGodMode || $isAdmin || ($adminFlag == 1);
            $isFacultyAdmin = ($userRole === 'faculty_admin') && !$isGodMode;
            $isDean = RoleHelper::isDean($user) && !$isGodMode;

            // Get curriculum information
            $curriculum = $this->curriculumModel->find($curriculumId);
            if (!$curriculum) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Curriculum not found'
                ]);
            }

            // Check permissions for faculty admin
            if ($isFacultyAdmin) {
                $managedFaculties = \App\Helpers\RoleHelper::getManagedFaculties($user);

                // Check if curriculum belongs to managed faculty
                if (empty($managedFaculties) || !in_array($curriculum['faculty_id'], $managedFaculties)) {
                    return $this->response->setJSON([
                        'success' => false,
                        'message' => 'Access denied: You do not have permission to access this curriculum'
                    ]);
                }
            } elseif ($isDean) {
                // Dean has same permission as Faculty Admin - check if curriculum belongs to their faculty
                $deanFaculties = RoleHelper::getDeanFaculties($user);

                // Check if curriculum belongs to dean's faculty
                if (empty($deanFaculties) || !in_array($curriculum['faculty_id'], $deanFaculties)) {
                    return $this->response->setJSON([
                        'success' => false,
                        'message' => 'Access denied: You do not have permission to access this curriculum'
                    ]);
                }
            } elseif (!$isSuperAdmin) {
                // Regular users cannot access
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Access denied'
                ]);
            }

            // Get faculty information
            $faculty = $this->facultyModel->find($curriculum['faculty_id']);

            // Get users (อาจารย์ผู้รับผิดชอบหลักสูตร) for this curriculum
            $users = $this->db->table('teacher_curriculum tc')
                ->select('u.*, tc.is_primary')
                ->join('user u', 'tc.teacher_email = u.email', 'inner')
                ->where('tc.curriculum_id', $curriculumId)
                ->where('tc.status', 1)
                ->where('u.active', 1)
                ->orderBy('tc.is_primary', 'DESC')
                ->orderBy('u.thai_name', 'ASC')
                ->get()
                ->getResultArray();

            $usersData = [];

            // Calculate 5-year range dynamically (B.E. years)
            $currentYear = (int)date('Y') + 543; // Current year in B.E.
            $years = [];
            for ($i = 4; $i >= 0; $i--) {
                $years[] = $currentYear - $i;
            }
            // years = [currentYear-4, currentYear-3, currentYear-2, currentYear-1, currentYear]

            foreach ($users as $user) {
                // Get user's emails
                $userEmails = [];
                if (!empty($user['email'])) {
                    $userEmails[] = $user['email'];
                }

                // Get all emails from authors table for this user
                $authorEmails = $this->db->table('authors')
                    ->select('email')
                    ->where('user_email', $user['email'])
                    ->where('email IS NOT NULL')
                    ->where('email !=', '')
                    ->get()
                    ->getResultArray();

                foreach ($authorEmails as $authorEmail) {
                    if (!empty($authorEmail['email']) && !in_array($authorEmail['email'], $userEmails, true)) {
                        $userEmails[] = $authorEmail['email'];
                    }
                }

                // Get publications where user is an author (approved only)
                $publications = [];
                if (!empty($userEmails)) {
                    $pubQuery = $this->db->table('publications p')
                        ->select('p.*')
                        ->join('publication_authors pa', 'pa.publication_id = p.id', 'left')
                        ->join('authors a', 'a.id = pa.author_id', 'left')
                        ->where('p.approve', 1) // Only approved publications
                        ->groupStart()
                        ->whereIn('pa.author_email', $userEmails)
                        ->orWhereIn('a.email', $userEmails)
                        ->orWhere('p.created_by_email', $user['email'])
                        ->groupEnd()
                        ->groupBy('p.id')
                        ->orderBy('p.publication_year', 'DESC')
                        ->orderBy('p.publication_month', 'DESC')
                        ->get()
                        ->getResultArray();

                    $minYear = min($years);
                    $maxYear = max($years);

                    // Map publication type to Thai
                    $typeMap = [
                        'journal' => 'งานวิจัย',
                        'proceedings' => 'บทความวิชาการ',
                        'book' => 'หนังสือ',
                        'thesis' => 'เอกสาร',
                        'report' => 'เอกสาร',
                        'other' => 'เอกสาร'
                    ];

                    foreach ($pubQuery as $pub) {
                        $pubYear = $pub['publication_year'] ? (int)$pub['publication_year'] + 543 : null; // Convert to B.E.
                        // Include all publications that have a valid year in range
                        if ($pubYear && $pubYear >= $minYear && $pubYear <= $maxYear) {
                            $publications[] = [
                                'id' => $pub['id'],
                                'title' => $pub['title'],
                                'publication_type' => $typeMap[$pub['publication_type']] ?? 'เอกสาร',
                                'publication_year' => $pubYear,
                                'source' => $pub['source'] ?? ''
                            ];
                        }
                    }
                }

                // Also try to get publications directly by created_by if user has no email-matched publications
                if (empty($publications)) {
                    $directPubQuery = $this->db->table('publications p')
                        ->select('p.*')
                        ->where('p.approve', 1)
                        ->where('p.created_by_email', $user['email'])
                        ->orderBy('p.publication_year', 'DESC')
                        ->get()
                        ->getResultArray();

                    $typeMap = [
                        'journal' => 'งานวิจัย',
                        'proceedings' => 'บทความวิชาการ',
                        'book' => 'หนังสือ',
                        'thesis' => 'เอกสาร',
                        'report' => 'เอกสาร',
                        'other' => 'เอกสาร'
                    ];

                    $minYear = min($years);
                    $maxYear = max($years);

                    foreach ($directPubQuery as $pub) {
                        $pubYear = $pub['publication_year'] ? (int)$pub['publication_year'] + 543 : null;
                        if ($pubYear && $pubYear >= $minYear && $pubYear <= $maxYear) {
                            $publications[] = [
                                'id' => $pub['id'],
                                'title' => $pub['title'],
                                'publication_type' => $typeMap[$pub['publication_type']] ?? 'เอกสาร',
                                'publication_year' => $pubYear,
                                'source' => $pub['source'] ?? ''
                            ];
                        }
                    }
                }

                // Organize publications by year
                $publicationsByYear = [];
                foreach ($years as $year) {
                    $publicationsByYear[$year] = array_values(array_filter($publications, function ($pub) use ($year) {
                        return $pub['publication_year'] == $year;
                    }));
                }

                $usersData[] = [
                    'uid' => $user['email'],
                    'name' => trim(($user['titleThai'] ?? '') . ' ' . ($user['thai_name'] ?? '') . ' ' . ($user['thai_lastname'] ?? '')),
                    'position' => $user['position'] ?? '',
                    'is_primary' => $user['is_primary'] ?? 0,
                    'publications' => $publications,
                    'publications_by_year' => (object)$publicationsByYear, // Cast to object to ensure JSON serialization
                    'publication_count' => count($publications)
                ];
            }

            return $this->response->setJSON([
                'success' => true,
                'data' => [
                    'curriculum' => [
                        'id' => $curriculum['id'],
                        'name' => $curriculum['name'],
                        'degree_level' => $curriculum['degree_level'],
                        'version_year' => $curriculum['version_year'] ?? null
                    ],
                    'faculty' => [
                        'id' => $faculty['id'],
                        'name' => $faculty['name']
                    ],
                    'users' => $usersData,
                    'years' => $years
                ]
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Get curriculum report data error: ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Failed to load curriculum data: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Get Publication Summary Data (OPTIMIZED VERSION)
     * Route: GET /admin/publications/summary-data
     * Improvements: Batch queries, reduced N+1 problems, email caching
     */
    public function getPublicationSummaryData()
    {
        try {
            $currentYear = date('Y');
            $fiveYearsAgo = $currentYear - 5;
            $nextYear = $currentYear + 1;

            // Get current user from session
            $userData = $this->session->get('user_data') ?? [];
            $userId = UserIdentity::sessionEmail();
            if ($userId === '') {
                $userId = UserIdentity::normalizeEmail((string) ($userData['email'] ?? ''));
            }

            if ($userId === '') {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'User not found in session'
                ]);
            }

            // Get full user data from database if needed
            $user = $this->userModel->find($userId);

            if (!$user) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'User not found'
                ]);
            }

            // Use session user_data for role checks (more accurate)
            // Merge with database user data
            $user = array_merge($user, $userData);

            // Check user role and filter faculties accordingly
            $userRole = $user['role'] ?? null;
            $isGodMode = $this->session->get('god_mode') === true;
            $isSuperAdmin = ($userRole === 'super_admin') || $isGodMode;
            $isFacultyAdmin = ($userRole === 'faculty_admin') && !$isGodMode;
            $isDean = RoleHelper::isDean($user) && !$isGodMode;

            // Get faculties based on role
            if ($isGodMode || $isSuperAdmin) {
                // Super admin sees all faculties
                $faculties = $this->db->table('faculties')
                    ->where('status', 1)
                    ->orderBy('name', 'ASC')
                    ->get()
                    ->getResultArray();
            } elseif ($isFacultyAdmin) {
                // Faculty admin sees only their managed faculties
                $managedFaculties = RoleHelper::getManagedFaculties($user);
                if (!empty($managedFaculties)) {
                    $faculties = $this->db->table('faculties')
                        ->where('status', 1)
                        ->whereIn('id', $managedFaculties)
                        ->orderBy('name', 'ASC')
                        ->get()
                        ->getResultArray();
                } else {
                    // No managed faculties, return empty data
                    return $this->response->setJSON([
                        'success' => true,
                        'data' => []
                    ]);
                }
            } elseif ($isDean) {
                // Dean sees only their faculty (same permission as Faculty Admin)
                $deanFaculties = RoleHelper::getDeanFaculties($user);
                if (!empty($deanFaculties)) {
                    $faculties = $this->db->table('faculties')
                        ->where('status', 1)
                        ->whereIn('id', $deanFaculties)
                        ->orderBy('name', 'ASC')
                        ->get()
                        ->getResultArray();
                } else {
                    // No dean faculties, return empty data
                    return $this->response->setJSON([
                        'success' => true,
                        'data' => []
                    ]);
                }
            } else {
                // Regular users see no faculties
                return $this->response->setJSON([
                    'success' => true,
                    'data' => []
                ]);
            }

            // OPTIMIZATION 1: Get all curricula for these faculties in one query
            $facultyIds = array_column($faculties, 'id');
            $allCurricula = $this->db->table('curriculum')
                ->whereIn('faculty_id', $facultyIds)
                ->where('status', 1)
                ->orderBy('name', 'ASC')
                ->get()
                ->getResultArray();

            // OPTIMIZATION 2: Get all teachers for these curricula in one query
            $curriculumIds = array_column($allCurricula, 'id');
            if (empty($curriculumIds)) {
                return $this->response->setJSON([
                    'success' => true,
                    'data' => []
                ]);
            }

            $allTeachers = $this->db->table('teacher_curriculum tc')
                ->select('tc.curriculum_id as tc_curriculum_id, u.*')
                ->join('user u', 'tc.teacher_email = u.email', 'inner')
                ->whereIn('tc.curriculum_id', $curriculumIds)
                ->where('tc.status', 1)
                ->where('u.active', 1)
                ->get()
                ->getResultArray();

            // DEBUG: Log all teachers and their curriculum_ids
            $debugTeachers = [];
            foreach ($allTeachers as $t) {
                $debugTeachers[] = ['cid' => $t['tc_curriculum_id'], 'email' => $t['email'] ?? '', 'name' => $t['thai_name'] ?? 'N/A'];
            }
            log_message('debug', 'Summary API - Curriculum IDs requested: ' . json_encode($curriculumIds));
            log_message('debug', 'Summary API - Teachers loaded: ' . json_encode($debugTeachers));

            // OPTIMIZATION 3: Get all author emails in one batch query
            $teacherEmails = array_unique(array_filter(array_column($allTeachers, 'email')));
            $allAuthorEmails = [];
            if (!empty($teacherEmails)) {
                $emailResults = $this->db->table('authors')
                    ->select('user_email, email')
                    ->whereIn('user_email', $teacherEmails)
                    ->where('email IS NOT NULL')
                    ->where('email !=', '')
                    ->get()
                    ->getResultArray();

                foreach ($emailResults as $row) {
                    $key = UserIdentity::normalizeEmail((string) ($row['user_email'] ?? ''));
                    if ($key === '') {
                        continue;
                    }
                    if (!isset($allAuthorEmails[$key])) {
                        $allAuthorEmails[$key] = [];
                    }
                    $allAuthorEmails[$key][] = $row['email'];
                }
            }

            // OPTIMIZATION 4: Get all publications for all teachers in one query
            $allUserEmails = [];
            foreach ($allTeachers as $teacher) {
                $key = UserIdentity::normalizeEmail((string) ($teacher['email'] ?? ''));
                if ($key === '') {
                    continue;
                }
                $emails = [$key];
                if (isset($allAuthorEmails[$key])) {
                    $emails = array_merge($emails, $allAuthorEmails[$key]);
                }
                $allUserEmails[$key] = array_unique($emails);
            }

            $flatEmails = [];
            foreach ($allUserEmails as $emails) {
                $flatEmails = array_merge($flatEmails, $emails);
            }
            $flatEmails = array_unique($flatEmails);

            $allPublicationsQuery = $this->db->table('publication_authors pa')
                ->select('p.id, p.title, p.publication_year, p.publication_type, p.source, p.approve, pa.author_email, a.email as author_table_email')
                ->join('publications p', 'pa.publication_id = p.id', 'inner')
                ->join('authors a', 'pa.author_id = a.id', 'left')
                ->where('p.publication_year >=', $fiveYearsAgo)
                ->where('p.publication_year <=', $currentYear);

            if (!empty($flatEmails)) {
                $allPublicationsQuery->groupStart()
                    ->whereIn('pa.author_email', $flatEmails)
                    ->orWhereIn('a.email', $flatEmails)
                    ->groupEnd();
            } else {
                $allPublicationsQuery->where('1=0', null, false);
            }

            $allPublicationsRaw = $allPublicationsQuery->get()->getResultArray();

            $publicationsByUser = [];
            foreach ($allPublicationsRaw as $pub) {
                foreach ($allUserEmails as $userKey => $emails) {
                    $matched = in_array($pub['author_email'], $emails, true)
                        || (!empty($pub['author_table_email']) && in_array($pub['author_table_email'], $emails, true));

                    if ($matched) {
                        if (!isset($publicationsByUser[$userKey])) {
                            $publicationsByUser[$userKey] = [];
                        }
                        $exists = false;
                        foreach ($publicationsByUser[$userKey] as $existing) {
                            if ($existing['id'] == $pub['id']) {
                                $exists = true;
                                break;
                            }
                        }
                        if (!$exists) {
                            $publicationsByUser[$userKey][] = $pub;
                        }
                    }
                }
            }

            // Group teachers by curriculum (cast to int to avoid type mismatch)
            $teachersByCurriculum = [];
            foreach ($allTeachers as $teacher) {
                $cid = (int) $teacher['tc_curriculum_id'];
                if (!isset($teachersByCurriculum[$cid])) {
                    $teachersByCurriculum[$cid] = [];
                }
                $teachersByCurriculum[$cid][] = $teacher;
            }

            // Group curricula by faculty (cast to int to avoid type mismatch)
            $curriculaByFaculty = [];
            foreach ($allCurricula as $curriculum) {
                $fid = (int) $curriculum['faculty_id'];
                if (!isset($curriculaByFaculty[$fid])) {
                    $curriculaByFaculty[$fid] = [];
                }
                $curriculaByFaculty[$fid][] = $curriculum;
            }

            // Build summary data
            $summaryData = [];

            foreach ($faculties as $faculty) {
                // Get curriculums for this faculty from pre-loaded data
                $curriculums = $curriculaByFaculty[(int) $faculty['id']] ?? [];

                $facultyData = [
                    'id' => $faculty['id'],
                    'name' => $faculty['name'],
                    'curriculums' => []
                ];

                foreach ($curriculums as $curriculum) {
                    // Determine required publications based on degree level
                    $degreeLevel = $curriculum['degree_level'] ?? 'bachelor';
                    $requiredPublications = ($degreeLevel === 'bachelor') ? 1 : 3;

                    // Get users for this curriculum from pre-loaded data (cast to int)
                    $users = $teachersByCurriculum[(int) $curriculum['id']] ?? [];

                    $curriculumUsers = [];
                    $allUsersSafe = true;
                    $anyUserAtRisk = false;
                    $oldestPublicationYear = null;
                    $totalPublicationCount = 0;

                    foreach ($users as $user) {
                        $teacherKey = UserIdentity::normalizeEmail((string) ($user['email'] ?? ''));

                        // Get publications from pre-loaded data
                        $allPublications = $publicationsByUser[$teacherKey] ?? [];

                        // Filter approved publications
                        $approvedPublications = array_filter($allPublications, function ($pub) {
                            return $pub['approve'] == 1;
                        });

                        // Sort by year desc and get latest 3
                        usort($allPublications, function ($a, $b) {
                            return $b['publication_year'] - $a['publication_year'];
                        });
                        $latestPublications = array_slice($allPublications, 0, 3);

                        // Count publications
                        $userPublicationCount = count($allPublications);
                        $totalPublicationCount += $userPublicationCount;
                        $approvedPublicationCount = count($approvedPublications);

                        // Check if user meets requirements
                        $userMeetsRequirement = ($approvedPublicationCount >= $requiredPublications);
                        if (!$userMeetsRequirement) {
                            $allUsersSafe = false;
                        }

                        // Check  if user is at risk
                        $userWillExpireSoon = false;
                        if ($approvedPublicationCount > 0 && $approvedPublicationCount < $requiredPublications + 2) {
                            foreach ($approvedPublications as $pub) {
                                $expiryYear = (int)$pub['publication_year'] + 5;
                                if ($expiryYear == $nextYear) {
                                    $userWillExpireSoon = true;
                                    $anyUserAtRisk = true;
                                    break;
                                }
                            }
                        }

                        // Track oldest publication
                        foreach ($allPublications as $pub) {
                            if ($oldestPublicationYear === null || $pub['publication_year'] < $oldestPublicationYear) {
                                $oldestPublicationYear = $pub['publication_year'];
                            }
                        }

                        // Add user to list
                        $curriculumUsers[] = [
                            'user_id' => $user['email'],
                            'user_name' => trim(($user['thai_name'] ?? '') . ' ' . ($user['thai_lastname'] ?? '')),
                            'titleThai' => $user['titleThai'] ?? null,
                            'user_email' => $user['email'],
                            'publications' => $latestPublications,
                            'publication_count' => $userPublicationCount,
                            'approved_count' => $approvedPublicationCount,
                            'meets_requirement' => $userMeetsRequirement,
                            'will_expire_soon' => $userWillExpireSoon
                        ];
                    }

                    // Determine curriculum status
                    $status = 'danger';
                    if (count($users) === 0) {
                        $status = 'danger';
                    } elseif ($allUsersSafe) {
                        if ($anyUserAtRisk) {
                            $status = 'warning';
                        } else {
                            $status = 'safe';
                        }
                    }

                    $facultyData['curriculums'][] = [
                        'id' => $curriculum['id'],
                        'name' => $curriculum['name'],
                        'degree_level' => $degreeLevel,
                        'required_publications' => $requiredPublications,
                        'status' => $status,
                        'user_count' => count($users),
                        'publication_count' => $totalPublicationCount,
                        'users_with_publications' => $curriculumUsers,
                        'oldest_publication_year' => $oldestPublicationYear
                    ];
                }

                if (!empty($facultyData['curriculums'])) {
                    $summaryData[] = $facultyData;
                }
            }

            return $this->response->setJSON([
                'success' => true,
                'data' => $summaryData,
                'current_year' => $currentYear,
                'five_years_ago' => $fiveYearsAgo
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Get publication summary error: ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Get all users for curriculum management
     * Returns users with faculty, email, and curriculum info from teacher_curriculum_view
     */
    public function getAllUsersForCurriculumManagement()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setJSON(['success' => false, 'message' => 'Invalid request']);
        }

        try {
            $search = $this->request->getGet('search');
            $facultyId = $this->request->getGet('faculty_id');

            // Get all active teachers with their curriculum info from teacher_curriculum_view
            $builder = $this->db->table('user');

            $builder->select([
                'user.email',
                'user.email as uid',
                'user.gf_name',
                'user.gl_name',
                'user.thai_name',
                'user.thai_lastname',
                'user.faculty_id as user_faculty_id',
                'uf.name as user_faculty_name',
                'uf.code as user_faculty_code',
                // Get primary curriculum from teacher_curriculum_view
                'MAX(CASE WHEN tcv.is_primary = 1 THEN tcv.curriculum_id END) as primary_curriculum_id',
                'MAX(CASE WHEN tcv.is_primary = 1 THEN tcv.curriculum_name END) as primary_curriculum_name',
                'MAX(CASE WHEN tcv.is_primary = 1 THEN tcv.curriculum_code END) as primary_curriculum_code',
                'MAX(CASE WHEN tcv.is_primary = 1 THEN tcv.curriculum_faculty_name END) as primary_curriculum_faculty_name',
                // Get all curriculums
                'GROUP_CONCAT(DISTINCT CONCAT(tcv.curriculum_name, " (", tcv.curriculum_code, ")") SEPARATOR ", ") as all_curriculums'
            ])
                ->join('faculties uf', 'uf.id = user.faculty_id', 'left')
                ->join('teacher_curriculum_view tcv', 'tcv.teacher_email = user.email AND tcv.assignment_status = 1', 'left')
                ->where('user.active', 1)
                ->where('user.user_type', 'TEACHER');

            // Apply faculty filter
            if (!empty($facultyId) && $facultyId !== 'all') {
                if ($facultyId === 'null') {
                    $builder->where('user.faculty_id IS NULL');
                } else {
                    $builder->where('user.faculty_id', $facultyId);
                }
            }

            // Apply search filter
            if (!empty($search)) {
                $search = trim($search);
                $builder->groupStart()
                    ->like('user.gf_name', $search)
                    ->orLike('user.gl_name', $search)
                    ->orLike('user.thai_name', $search)
                    ->orLike('user.thai_lastname', $search)
                    ->orLike('user.email', $search)
                    ->orLike('uf.name', $search)
                    ->groupEnd();
            }

            $users = $builder->groupBy('user.email')
                ->orderBy('user.faculty_id', 'ASC')
                ->orderBy('user.gf_name', 'ASC')
                ->get()
                ->getResultArray();

            // Debug: Get last SQL query (log only, not in response)
            $lastQuery = $this->db->getLastQuery();
            log_message('debug', 'getAllUsersForCurriculumManagement SQL: ' . $lastQuery);
            log_message('debug', 'getAllUsersForCurriculumManagement - Found ' . count($users) . ' users');

            return $this->response->setJSON([
                'success' => true,
                'data' => $users
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Get all users for curriculum management error: ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Failed to load users'
            ]);
        }
    }

    /**
     * Get curriculums by faculty with members
     * Returns all curriculums in a faculty with their assigned teachers
     */
    public function getCurriculumsByFacultyWithMembers()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setJSON(['success' => false, 'message' => 'Invalid request']);
        }

        try {
            $facultyId = $this->request->getGet('faculty_id');

            // Check user role and permissions
            $userData = $this->session->get('user_data') ?? [];
            $userRole = $userData['role'] ?? null;
            $isGodMode = $this->session->get('god_mode') === true;
            $isFacultyAdmin = ($userRole === 'faculty_admin') && !$isGodMode;

            // Get managed faculties for faculty admin
            $managedFaculties = [];
            if ($isFacultyAdmin) {
                $managedFaculties = \App\Helpers\RoleHelper::getManagedFaculties($userData);

                // If faculty admin has no managed faculties, return empty
                if (empty($managedFaculties)) {
                    return $this->response->setJSON([
                        'success' => true,
                        'data' => []
                    ]);
                }
            }

            // Get curriculums by faculty
            $curriculumBuilder = $this->db->table('curriculum c');
            $curriculumBuilder->select([
                'c.id',
                'c.name',
                'c.code',
                'c.degree_level',
                'c.faculty_id',
                'c.chair_email',
                'f.name as faculty_name',
                'f.code as faculty_code',
                'chair.email as chair_uid',
                'chair.titleThai as chair_title',
                'chair.title as chair_title_en',
                'chair.thai_name as chair_name',
                'chair.thai_lastname as chair_lastname',
                'chair.gf_name as chair_gf_name',
                'chair.gl_name as chair_gl_name'
            ])
                ->join('faculties f', 'f.id = c.faculty_id', 'left')
                ->join('user as chair', 'chair.email = c.chair_email', 'left')
                ->where('c.status', 1);

            // For faculty admin, always filter by managed faculties (even if 'all' is selected)
            if ($isFacultyAdmin && !empty($managedFaculties)) {
                $curriculumBuilder->whereIn('c.faculty_id', $managedFaculties);
            } elseif (!empty($facultyId) && $facultyId !== 'all') {
                // For super admin or god mode, respect the filter
                if ($facultyId === 'null') {
                    $curriculumBuilder->where('c.faculty_id IS NULL');
                } else {
                    $curriculumBuilder->where('c.faculty_id', $facultyId);
                }
            }

            $curriculums = $curriculumBuilder->orderBy('f.name', 'ASC')
                ->orderBy('c.name', 'ASC')
                ->get()
                ->getResultArray();

            // Debug: Get last SQL query for curriculums
            $curriculumQuery = $this->db->getLastQuery();
            log_message('debug', 'getCurriculumsByFacultyWithMembers - Curriculums SQL: ' . $curriculumQuery);
            log_message('debug', 'getCurriculumsByFacultyWithMembers - Found ' . count($curriculums) . ' curriculums');

            // Get members for each curriculum from teacher_curriculum_view
            $result = [];
            foreach ($curriculums as $curriculum) {
                $membersBuilder = $this->db->table('teacher_curriculum_view tcv');
                $membersBuilder->select([
                    'tcv.teacher_email as teacher_uid',
                    'tcv.thai_name',
                    'tcv.thai_lastname',
                    'tcv.gf_name',
                    'tcv.email',
                    'tcv.is_primary',
                    'tcv.role',
                    'tcv.teacher_faculty_name'
                ])
                    ->where('tcv.curriculum_id', $curriculum['id'])
                    ->where('tcv.assignment_status', 1)
                    ->orderBy('tcv.is_primary', 'DESC')
                    ->orderBy('tcv.thai_name', 'ASC');

                $members = $membersBuilder->get()->getResultArray();

                // Debug: Log last query for members (only for first curriculum to avoid spam)
                if (count($result) === 0) {
                    $membersQuery = $this->db->getLastQuery();
                    log_message('debug', 'getCurriculumsByFacultyWithMembers - Members SQL (sample): ' . $membersQuery);
                }

                // Format chair information
                $chairInfo = null;
                if (!empty($curriculum['chair_uid'])) {
                    $title = '';
                    if (!empty($curriculum['chair_title'])) {
                        $title = $curriculum['chair_title'];
                    } elseif (!empty($curriculum['chair_title_en'])) {
                        $title = $curriculum['chair_title_en'];
                    }

                    $chairName = '';
                    if (!empty($curriculum['chair_name']) && !empty($curriculum['chair_lastname'])) {
                        $chairName = (!empty($title) ? $title . ' ' : '') . $curriculum['chair_name'] . ' ' . $curriculum['chair_lastname'];
                    } elseif (!empty($curriculum['chair_gf_name']) && !empty($curriculum['chair_gl_name'])) {
                        $chairName = (!empty($title) ? $title . ' ' : '') . $curriculum['chair_gf_name'] . ' ' . $curriculum['chair_gl_name'];
                    }

                    if ($chairName) {
                        $chairInfo = [
                            'uid' => $curriculum['chair_uid'],
                            'name' => $chairName,
                            'title' => $title ?: null
                        ];
                    }
                }

                $result[] = [
                    'id' => $curriculum['id'],
                    'name' => $curriculum['name'],
                    'code' => $curriculum['code'],
                    'degree_level' => $curriculum['degree_level'],
                    'faculty_id' => $curriculum['faculty_id'],
                    'faculty_name' => $curriculum['faculty_name'],
                    'faculty_code' => $curriculum['faculty_code'],
                    'chair_info' => $chairInfo,
                    'members' => $members,
                    'member_count' => count($members)
                ];
            }

            // Debug: Log SQL queries (log only, not in response)
            return $this->response->setJSON([
                'success' => true,
                'data' => $result
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Get curriculums by faculty with members error: ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Failed to load curriculums'
            ]);
        }
    }

    // ===================================================================
    // STUDENT ADMISSION FORM MANAGEMENT
    // ===================================================================

    /**
     * Show list of student admission forms
     */
    public function admissionIndex()
    {
        $admissionModel = new \App\Models\StudentAdmissionFormModel();
        $facultyModel = new \App\Models\FacultyModel();

        // Get available years first
        $years = $admissionModel->getAvailableYears();

        // Get selected year from query param or default to latest available year
        $selectedYear = $this->request->getGet('year');
        if (empty($selectedYear)) {
            // Use the latest year (first in array since it's ordered DESC)
            $selectedYear = !empty($years) ? $years[0] : (date('Y') + 543);
        }

        $selectedFaculty = $this->request->getGet('faculty') ?? '';

        // If no years available, use selected year
        if (empty($years)) {
            $years = [$selectedYear];
        }

        // Get all faculties for filter dropdown
        $faculties = $facultyModel->orderBy('name', 'ASC')->findAll();

        // Get forms based on user role
        $userData = $this->session->get('user_data');
        $user = $this->userModel->find(UserIdentity::sessionEmail() ?: ($userData['email'] ?? ''));

        if ($this->session->get('god_mode') === true || RoleHelper::isSuperAdmin($user)) {
            // Super admin: see all forms
            if ($selectedFaculty) {
                $forms = $admissionModel->getFormsByFaculties([$selectedFaculty], $selectedYear);
            } else {
                $forms = $admissionModel->getFormsByYear($selectedYear);
            }
        } elseif (RoleHelper::isFacultyAdmin($user)) {
            // Faculty admin: see forms from managed faculties
            $managedFaculties = RoleHelper::getManagedFaculties($user);
            if ($selectedFaculty && in_array($selectedFaculty, $managedFaculties)) {
                $forms = $admissionModel->getFormsByFaculties([$selectedFaculty], $selectedYear);
            } else {
                $forms = $admissionModel->getFormsByFaculties($managedFaculties, $selectedYear);
            }
            // Filter faculties dropdown to only show managed faculties
            $faculties = array_filter($faculties, fn($f) => in_array($f['id'], $managedFaculties));
        } elseif (RoleHelper::isDean($user)) {
            // Dean: see forms from their faculty
            $deanFaculties = RoleHelper::getDeanFaculties($user);
            if (!empty($deanFaculties)) {
                if ($selectedFaculty && in_array($selectedFaculty, $deanFaculties)) {
                    $forms = $admissionModel->getFormsByFaculties([$selectedFaculty], $selectedYear);
                } else {
                    $forms = $admissionModel->getFormsByFaculties($deanFaculties, $selectedYear);
                }
                // Filter faculties dropdown to only show dean's faculties
                $faculties = array_filter($faculties, fn($f) => in_array($f['id'], $deanFaculties));
            } else {
                $forms = [];
            }
        } elseif (RoleHelper::isChair($user)) {
            // Chair: see forms from their curriculum's faculty
            $chairFaculties = RoleHelper::getChairFaculties($user);
            if (!empty($chairFaculties)) {
                if ($selectedFaculty && in_array($selectedFaculty, $chairFaculties)) {
                    $forms = $admissionModel->getFormsByFaculties([$selectedFaculty], $selectedYear);
                } else {
                    $forms = $admissionModel->getFormsByFaculties($chairFaculties, $selectedYear);
                }
                // Filter faculties dropdown to only show chair's faculties
                $faculties = array_filter($faculties, fn($f) => in_array($f['id'], $chairFaculties));
            } else {
                $forms = [];
            }
        } else {
            $forms = [];
        }

        // Get statistics
        $stats = $admissionModel->getStatistics($selectedFaculty ? [$selectedFaculty] : null, $selectedYear);

        return view('admin/admission/index', [
            'forms' => $forms,
            'years' => $years,
            'selectedYear' => $selectedYear,
            'faculties' => $faculties,
            'selectedFaculty' => $selectedFaculty,
            'stats' => $stats
        ]);
    }

    /**
     * Get admission forms list via AJAX
     */
    public function admissionList()
    {
        $admissionModel = new \App\Models\StudentAdmissionFormModel();

        // Get available years first
        $years = $admissionModel->getAvailableYears();

        // Get year from query param or default to latest available year
        $year = $this->request->getGet('year');
        if (empty($year)) {
            // Use the latest year (first in array since it's ordered DESC)
            $year = !empty($years) ? $years[0] : (date('Y') + 543);
        }

        $faculty = $this->request->getGet('faculty') ?? '';

        // Get forms based on user role
        $userData = $this->session->get('user_data');
        $user = $this->userModel->find(UserIdentity::sessionEmail() ?: ($userData['email'] ?? ''));

        if ($this->session->get('god_mode') === true || RoleHelper::isSuperAdmin($user)) {
            // Super admin: see all forms
            if ($faculty) {
                $forms = $admissionModel->getFormsByFaculties([$faculty], $year);
            } else {
                $forms = $admissionModel->getFormsByYear($year);
            }
        } elseif (RoleHelper::isFacultyAdmin($user)) {
            // Faculty admin: see forms from managed faculties
            $managedFaculties = RoleHelper::getManagedFaculties($user);
            if ($faculty && in_array($faculty, $managedFaculties)) {
                $forms = $admissionModel->getFormsByFaculties([$faculty], $year);
            } else {
                $forms = $admissionModel->getFormsByFaculties($managedFaculties, $year);
            }
        } elseif (RoleHelper::isDean($user)) {
            // Dean: see forms from their faculty
            $deanFaculties = RoleHelper::getDeanFaculties($user);
            if (!empty($deanFaculties)) {
                if ($faculty && in_array($faculty, $deanFaculties)) {
                    $forms = $admissionModel->getFormsByFaculties([$faculty], $year);
                } else {
                    $forms = $admissionModel->getFormsByFaculties($deanFaculties, $year);
                }
            } else {
                $forms = [];
            }
        } elseif (RoleHelper::isChair($user)) {
            // Chair: see forms from their curriculum's faculty
            $chairFaculties = RoleHelper::getChairFaculties($user);
            if (!empty($chairFaculties)) {
                if ($faculty && in_array($faculty, $chairFaculties)) {
                    $forms = $admissionModel->getFormsByFaculties([$faculty], $year);
                } else {
                    $forms = $admissionModel->getFormsByFaculties($chairFaculties, $year);
                }
            } else {
                $forms = [];
            }
        } else {
            $forms = [];
        }

        // Get statistics
        $stats = $admissionModel->getStatistics($faculty ? [$faculty] : null, $year);

        return $this->response->setJSON([
            'success' => true,
            'forms' => $forms,
            'stats' => $stats
        ]);
    }

    /**
     * Generate admission forms for a new academic year
     */
    public function admissionGenerate()
    {
        try {
            $input = $this->request->getJSON(true);
            $year = $input['year'] ?? null;

            if (!$year || $year < 2500 || $year > 2600) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Invalid year'
                ]);
            }

            $admissionModel = new \App\Models\StudentAdmissionFormModel();

            // Get user's managed faculties if faculty admin
            $userData = $this->session->get('user_data');
            $user = $this->userModel->find(UserIdentity::sessionEmail() ?: ($userData['email'] ?? ''));

            $facultyIds = null;
            if (RoleHelper::isFacultyAdmin($user) && !$this->session->get('god_mode')) {
                $facultyIds = RoleHelper::getManagedFaculties($user);
            }

            $count = $admissionModel->createFormsForNewYear($year, $facultyIds);

            return $this->response->setJSON([
                'success' => true,
                'count' => $count,
                'message' => "สร้างแบบฟอร์ม {$count} รายการสำเร็จ"
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Admission generate error: ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Edit admission form
     */
    public function admissionEdit($id = null)
    {
        if (!$id) {
            return redirect()->to(base_url('index.php/admin/admission'));
        }

        $admissionModel = new \App\Models\StudentAdmissionFormModel();
        $form = $admissionModel->getFormWithDetails($id);

        if (!$form) {
            return redirect()->to(base_url('index.php/admin/admission'))->with('error', 'ไม่พบข้อมูล');
        }

        // Get faculties and curricula for dropdowns
        $faculties = $this->facultyModel->where('status', 1)->findAll();
        $curricula = $this->curriculumModel->where('status', 1)->findAll();

        // Get users for teacher selection
        $users = $this->userModel->where('user_type', 'TEACHER')->findAll();

        return view('admin/admission/edit', [
            'form' => $form,
            'faculties' => $faculties,
            'curricula' => $curricula,
            'users' => $users
        ]);
    }

    /**
     * Save admission form
     */
    public function admissionSave($id = null)
    {
        if (!$id) {
            return $this->response->setJSON(['success' => false, 'message' => 'ID required']);
        }

        try {
            $admissionModel = new \App\Models\StudentAdmissionFormModel();
            $input = $this->request->getJSON(true) ?? $this->request->getPost();

            // Update form data
            $userData = $this->session->get('user_data');
            $input['updated_by_email'] = UserIdentity::sessionEmail() ?: ($userData['email'] ?? null);

            // Remove teachers data to save separately
            $teachers = $input['teachers'] ?? [];
            unset($input['teachers']);

            $admissionModel->update($id, $input);

            // Save teachers
            if (!empty($teachers)) {
                $admissionModel->saveTeachers($id, $teachers);
            }

            return $this->response->setJSON([
                'success' => true,
                'message' => 'บันทึกข้อมูลสำเร็จ'
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Admission save error: ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Get admission form data (AJAX)
     */
    public function admissionGet($id = null)
    {
        if (!$id) {
            return $this->response->setJSON(['success' => false, 'message' => 'ID required']);
        }

        $admissionModel = new \App\Models\StudentAdmissionFormModel();
        $form = $admissionModel->getFormWithDetails($id);

        if (!$form) {
            return $this->response->setJSON(['success' => false, 'message' => 'Not found']);
        }

        return $this->response->setJSON(['success' => true, 'data' => $form]);
    }

    /**
     * View admission form (read-only)
     */
    public function admissionView($id = null)
    {
        if (!$id) {
            return redirect()->to(base_url('index.php/admin/admission'));
        }

        $admissionModel = new \App\Models\StudentAdmissionFormModel();
        $form = $admissionModel->getFormWithDetails($id);

        if (!$form) {
            return redirect()->to(base_url('index.php/admin/admission'))->with('error', 'ไม่พบข้อมูล');
        }

        return view('admin/admission/view', ['form' => $form]);
    }

    /**
     * Print admission form (PDF-ready view)
     */
    public function admissionPrint($id = null)
    {
        if (!$id) {
            return redirect()->to(base_url('index.php/admin/admission'));
        }

        $admissionModel = new \App\Models\StudentAdmissionFormModel();
        $form = $admissionModel->getFormWithDetails($id);

        if (!$form) {
            return redirect()->to(base_url('index.php/admin/admission'))->with('error', 'ไม่พบข้อมูล');
        }

        return view('admin/admission/print', ['form' => $form]);
    }

    /**
     * PDF Summary View Page for Admission Form
     * Route: GET /admin/admission/pdf-summary
     * Displays dedicated page for PDF generation using pdfMake
     */
    public function admissionPdfSummary()
    {
        $formId = $this->request->getGet('form_id');

        if (!$formId) {
            return redirect()->to(base_url('index.php/admin/admission'))->with('error', 'ไม่พบรหัสแบบฟอร์ม');
        }

        // Verify form exists
        $admissionModel = new \App\Models\StudentAdmissionFormModel();
        $form = $admissionModel->find($formId);

        if (!$form) {
            return redirect()->to(base_url('index.php/admin/admission'))->with('error', 'ไม่พบข้อมูลแบบฟอร์ม');
        }

        return view('admin/admission/pdf_summary');
    }

    /**
     * Update teacher academic position
     */
    public function admissionUpdatePosition()
    {
        try {
            $input = $this->request->getJSON(true);
            $userId = $input['user_id'] ?? null;
            $position = $input['position'] ?? null;

            if (!$userId || !$position) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'ข้อมูลไม่ครบถ้วน'
                ]);
            }

            // Update titleThai in user table
            $this->userModel->update($userId, ['titleThai' => $position]);

            return $this->response->setJSON([
                'success' => true,
                'message' => 'อัปเดตตำแหน่งวิชาการเรียบร้อย'
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Update position error: ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Format dean information from faculty data
     */
    private function formatDeanInfo($faculty)
    {
        if (empty($faculty['dean_uid'])) {
            return null;
        }

        // Get title (prefer Thai title, fallback to English title)
        $title = '';
        if (!empty($faculty['dean_title'])) {
            $title = $faculty['dean_title'];
        } elseif (!empty($faculty['dean_title_en'])) {
            $title = $faculty['dean_title_en'];
        }

        // Prefer Thai name, fallback to English name
        $name = '';
        if (!empty($faculty['dean_name']) && !empty($faculty['dean_lastname'])) {
            // Thai name with title
            $name = (!empty($title) ? $title . ' ' : '') . $faculty['dean_name'] . ' ' . $faculty['dean_lastname'];
        } elseif (!empty($faculty['dean_gf_name']) && !empty($faculty['dean_gl_name'])) {
            // English name with title
            $name = (!empty($title) ? $title . ' ' : '') . $faculty['dean_gf_name'] . ' ' . $faculty['dean_gl_name'];
        }

        return [
            'uid' => $faculty['dean_uid'],
            'name' => $name ?: '-',
            'title' => $title ?: null,
            'thai_name' => $faculty['dean_name'] ?? null,
            'thai_lastname' => $faculty['dean_lastname'] ?? null,
            'gf_name' => $faculty['dean_gf_name'] ?? null,
            'gl_name' => $faculty['dean_gl_name'] ?? null
        ];
    }

    /**
     * Get users for dean selection dropdown
     * Returns all active users (teachers) for selecting as faculty dean
     */
    public function getUsersForDeanSelection()
    {
        try {
            $facultyId = $this->request->getGet('faculty_id');
            $curriculumId = $this->request->getGet('curriculum_id');

            // If curriculum_id is provided, get only members of that curriculum
            if ($curriculumId) {
                $builder = $this->db->table('teacher_curriculum tc')
                    ->select('u.email, u.email as uid, u.titleThai, u.title, u.thai_name, u.thai_lastname, u.gf_name, u.gl_name, u.faculty_id')
                    ->join('user u', 'u.email = tc.teacher_email', 'inner')
                    ->where('tc.curriculum_id', $curriculumId)
                    ->where('tc.status', 1)
                    ->where('u.active', 1)
                    ->orderBy('u.thai_name', 'ASC')
                    ->orderBy('u.gf_name', 'ASC');
            } else {
                // Get all active users (preferably teachers)
                $builder = $this->db->table('user')
                    ->select('email as uid, titleThai, title, thai_name, thai_lastname, gf_name, gl_name, email, faculty_id')
                    ->where('active', 1)
                    ->orderBy('thai_name', 'ASC')
                    ->orderBy('gf_name', 'ASC');

                // If faculty_id is provided, prioritize users from that faculty
                if ($facultyId) {
                    // Use raw query for CASE statement ordering
                    $builder->orderBy('CASE WHEN faculty_id = ' . (int)$facultyId . ' THEN 0 ELSE 1 END', 'ASC', false);
                }
            }

            $users = $builder->get()->getResultArray();

            // Format user names
            $formattedUsers = [];
            foreach ($users as $user) {
                $name = '';
                $title = '';

                // Get title (prefer Thai title, fallback to English title)
                if (!empty($user['titleThai'])) {
                    $title = $user['titleThai'];
                } elseif (!empty($user['title'])) {
                    $title = $user['title'];
                }

                if (!empty($user['thai_name']) && !empty($user['thai_lastname'])) {
                    // Thai name with Thai title
                    $name = (!empty($title) ? $title . ' ' : '') . $user['thai_name'] . ' ' . $user['thai_lastname'];
                } elseif (!empty($user['gf_name']) && !empty($user['gl_name'])) {
                    // English name with title
                    $name = (!empty($title) ? $title . ' ' : '') . $user['gf_name'] . ' ' . $user['gl_name'];
                } else {
                    $name = $user['email'];
                }

                $formattedUsers[] = [
                    'uid' => $user['email'],
                    'name' => $name,
                    'title' => $title,
                    'email' => $user['email'],
                    'faculty_id' => $user['faculty_id']
                ];
            }

            return $this->response->setJSON([
                'success' => true,
                'data' => $formattedUsers
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Get users for dean selection error: ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Failed to load users'
            ]);
        }
    }

    /**
     * Set curriculum chair (ประธานหลักสูตร)
     */
    public function setCurriculumChair()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setJSON(['success' => false, 'message' => 'Invalid request']);
        }

        try {
            $data = $this->request->getJSON(true);
            $curriculumId = $data['curriculum_id'] ?? null;
            $chairId = $data['chair_id'] ?? null; // null to remove chair

            if (!$curriculumId) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Curriculum ID is required'
                ]);
            }

            // Check permissions
            $userData = $this->session->get('user_data') ?? [];
            $userRole = $userData['role'] ?? null;
            $isGodMode = $this->session->get('god_mode') === true;
            $isSuperAdmin = ($userRole === 'super_admin') || $isGodMode;
            $isFacultyAdmin = ($userRole === 'faculty_admin') && !$isGodMode;

            // Get curriculum
            $curriculum = $this->curriculumModel->find($curriculumId);
            if (!$curriculum) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Curriculum not found'
                ]);
            }

            // Check permissions for faculty admin
            if ($isFacultyAdmin) {
                $managedFaculties = \App\Helpers\RoleHelper::getManagedFaculties($userData);
                if (empty($managedFaculties) || !in_array($curriculum['faculty_id'], $managedFaculties)) {
                    return $this->response->setJSON([
                        'success' => false,
                        'message' => 'Access denied: You do not have permission to modify this curriculum'
                    ]);
                }
            } elseif (!$isSuperAdmin) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Access denied'
                ]);
            }

            // Validate chair_id if provided
            if ($chairId) {
                $chair = $this->userModel->find($chairId);
                if (!$chair || $chair['active'] != 1) {
                    return $this->response->setJSON([
                        'success' => false,
                        'message' => 'Invalid chair user'
                    ]);
                }
            }

            // Update curriculum
            $updateData = ['chair_id' => $chairId ?: null];
            if ($this->curriculumModel->update($curriculumId, $updateData)) {
                return $this->response->setJSON([
                    'success' => true,
                    'message' => $chairId ? 'ตั้งประธานหลักสูตรสำเร็จ' : 'ลบประธานหลักสูตรสำเร็จ'
                ]);
            } else {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Failed to update curriculum chair'
                ]);
            }
        } catch (\Exception $e) {
            log_message('error', 'Set curriculum chair error: ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ]);
        }
    }
}
