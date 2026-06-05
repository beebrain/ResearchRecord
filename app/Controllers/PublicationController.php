<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use App\Models\UserModel;
use App\Models\AuthorModel;
use App\Models\PublicationModel;
use App\Models\PublicationAuthorModel;
use App\Libraries\PublicationReturnNavigation;
use App\Libraries\UserIdentity;

class PublicationController extends Controller
{
    protected $userModel;
    protected $authorModel;
    protected $publicationModel;
    protected $publicationAuthorModel;
    protected $session;
    protected $db;

    public function __construct()
    {
        $this->userModel = new UserModel();
        $this->authorModel = new AuthorModel();
        $this->publicationModel = new PublicationModel();
        $this->publicationAuthorModel = new PublicationAuthorModel();
        $this->session = session();
        $this->db = \Config\Database::connect();

        // Load year helper for normalize_year_to_ce() function
        helper('year');
    }

    private function sessionUserEmail(?array $userData = null): string
    {
        $userData = $userData ?? $this->session->get('user_data') ?? [];
        $email    = UserIdentity::sessionEmail();
        if ($email !== '') {
            return $email;
        }

        return UserIdentity::normalizeEmail((string) ($userData['email'] ?? ''));
    }

    private function userEmailsForAccess(?array $userData = null): array
    {
        $email = $this->sessionUserEmail($userData);
        if ($email === '') {
            return [];
        }

        $emails = [$email];
        $extra  = $this->authorModel->where('user_email', $email)
            ->where('email IS NOT NULL')
            ->where('email !=', '')
            ->findColumn('email') ?? [];

        foreach ($extra as $rowEmail) {
            $normalized = UserIdentity::normalizeEmail((string) $rowEmail);
            if ($normalized !== '' && ! in_array($normalized, $emails, true)) {
                $emails[] = $normalized;
            }
        }

        return $emails;
    }

    /**
     * Publications list page
     * Route: GET /publications
     */
    public function index()
    {
        $userData = $this->session->get('user_data');
        if (!$userData) {
            return redirect()->to('/auth/login');
        }

        $data = [
            'title' => 'Publications',
            'user' => array_merge($userData, ['email' => UserIdentity::normalizeEmail((string) ($userData['email'] ?? UserIdentity::sessionEmail()))]),
            'is_admin' => $this->isAdmin()
        ];

        return view('publications/index', $data);
    }

    /**
     * Manage publications page (for regular users)
     * Route: GET /publications/manage
     * Similar to admin view but shows only user's publications
     */
    public function manage()
    {
        $userData = $this->session->get('user_data');
        if (!$userData) {
            return redirect()->to('/auth/login');
        }

        $data = [
            'title' => 'จัดการผลงานวิจัยของฉัน',
            'user' => $userData,
            'is_admin' => $this->isAdmin()
        ];

        return view('publications/manage', $data);
    }

    /**
     * Create publication page
     * Route: GET /publications/create
     */
    public function create()
    {
        $userData = $this->session->get('user_data');
        if (!$userData) {
            return redirect()->to('/auth/login');
        }

        PublicationReturnNavigation::captureInternalReturnFromRequest();
        PublicationReturnNavigation::captureNsReturnFromRequest();

        $data = [
            'title'       => 'Add Publication',
            'user'        => $userData,
            'is_admin'    => $this->isAdmin(),
            'cancel_url'  => PublicationReturnNavigation::cancelUrl(),
        ];

        return view('publications/create', $data);
    }


    public function savePublication()
    {
        try {
            // Get current user
            $userData = $this->session->get('user_data');
            $userEmail = $this->sessionUserEmail($userData);

            // Prepare publication data
            // Normalize year to CE (ค.ศ.) for database storage
            $publicationYear = $this->request->getPost('publication_year');
            $publicationYear = normalize_year_to_ce($publicationYear);

            $publicationData = [
                'title' => $this->request->getPost('title'),
                'abstract' => $this->request->getPost('abstract'),
                'publication_type' => $this->request->getPost('publication_type'),
                'source' => $this->request->getPost('source'),
                'publication_year' => $publicationYear,
                'publication_month' => $this->request->getPost('publication_month') ?: null,
                'volume' => $this->request->getPost('volume'),
                'pages' => $this->request->getPost('pages'),
                'doi' => $this->request->getPost('doi'),
                'isbn' => $this->request->getPost('isbn'),
                'keywords' => $this->request->getPost('keywords'),
                'notes' => $this->request->getPost('notes'),
                'ref_url' => $this->request->getPost('ref_url'), // Store file reference or external URL
                'created_by_email' => $userEmail
            ];

            // Save publication
            $publicationId = $this->publicationModel->insert($publicationData);

            if ($publicationId) {
                // Process authors
                $authors = $this->request->getPost('authors');

                if (!empty($authors)) {
                    // Handle JSON string format
                    if (is_string($authors)) {
                        $authors = json_decode($authors, true);
                    }

                    if (is_array($authors)) {
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

                                // Try to find existing author by email (only for linking, not creating)
                                if (!empty($authorInput['email'])) {
                                    // $ = $this->authorModel->getAuthorByEmail($authorInput['email']);
                                    $existingAuthor = $this->authorModel->getAuthorlinkUser($authorInput['email']);
                                    if ($existingAuthor) {
                                        // Use existing author ID for linking
                                        $authorData['author_id'] = $existingAuthor['id'];
                                        $authorData['autor_name'] = $existingAuthor['name'];
                                        $authorData['author_affiliation'] = $existingAuthor['affiliation'];
                                        $authorData['author_email'] = $existingAuthor['email'];
                                    }
                                    // If no existing author found, just use input data without author_id
                                }

                                $processedAuthors[] = $authorData;
                            }
                        }

                        // Insert all authors to publication using PublicationAuthorModel
                        if (!empty($processedAuthors)) {
                            $this->publicationAuthorModel->addAuthorsToPublication($publicationId, $processedAuthors);
                        }
                    }
                }

                return $this->response->setJSON([
                    'success' => true,
                    'message' => 'Publication added successfully!',
                    'publication_id' => $publicationId
                ]);
            }

            return $this->response->setJSON([
                'success' => false,
                'message' => 'Failed to save publication'
            ]);
        } catch (\Exception $e) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Store new publication
     * Route: POST /publications/store
     */
    public function store()
    {
        $userData = $this->session->get('user_data');
        if (!$userData) {
            if ($this->request->isAJAX()) {
                return $this->response->setJSON(['success' => false, 'message' => 'Unauthorized']);
            }
            return redirect()->to('/auth/login');
        }

        $validation = \Config\Services::validation();
        $validation->setRules([
            'title' => 'required|min_length[3]',
            'publication_type' => 'required',
            'source' => 'required'
        ]);

        if (!$validation->withRequest($this->request)->run()) {
            if ($this->request->isAJAX()) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validation->getErrors()
                ]);
            }
            return redirect()->back()->withInput()->with('errors', $validation->getErrors());
        }

        $this->db->transBegin();

        try {
            // Normalize year to CE (ค.ศ.) for database storage
            $publicationYear = $this->request->getPost('publication_year');
            $publicationYear = normalize_year_to_ce($publicationYear);

            $userEmail = $this->sessionUserEmail($userData);

            // Create publication
            $publicationData = [
                'title' => $this->request->getPost('title'),
                'publication_type' => $this->request->getPost('publication_type'),
                'source' => $this->request->getPost('source'),
                'publication_year' => $publicationYear,
                'publication_month' => $this->request->getPost('publication_month') ?: null,
                'volume' => $this->request->getPost('volume') ?: null,
                'pages' => $this->request->getPost('pages') ?: null,
                'doi' => $this->request->getPost('doi') ?: null,
                'abstract' => $this->request->getPost('abstract') ?: null,
                'keywords' => $this->request->getPost('keywords') ?: null,
                'created_by_email' => $this->sessionUserEmail($userData)
            ];

            $publicationId = $this->publicationModel->insert($publicationData);

            if (!$publicationId) {
                throw new \Exception('Failed to create publication');
            }

            // Process authors
            $authorsJson = $this->request->getPost('authors');
            if (!empty($authorsJson)) {
                $authors = json_decode($authorsJson, true);
                if (is_array($authors) && !empty($authors)) {
                    $this->addAuthors($publicationId, $authors, $userEmail);
                }
            }

            if ($this->db->transStatus() === false) {
                throw new \RuntimeException('Database transaction failed');
            }

            $this->db->transCommit();

            if ($this->request->isAJAX()) {
                return $this->response->setJSON([
                    'success'  => true,
                    'message'  => 'Publication created successfully',
                    'redirect' => PublicationReturnNavigation::ajaxRedirectUrl(),
                ]);
            }

            return PublicationReturnNavigation::redirectAfterSave('Publication created successfully');
        } catch (\Exception $e) {
            $this->db->transRollback();
            log_message('error', 'Create publication error: ' . $e->getMessage());

            if ($this->request->isAJAX()) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => $e->getMessage()
                ]);
            }

            return redirect()->back()->withInput()->with('error', $e->getMessage());
        }
    }

    /**
     * View single publication
     * Route: GET /publications/view/{id}
     */
    public function view($id)
    {
        $userData = $this->session->get('user_data');
        if (!$userData) {
            return redirect()->to('/auth/login');
        }

        $publication = $this->publicationModel->find($id);
        if (!$publication) {
            return redirect()->to('publications')->with('error', 'Publication not found');
        }

        $userEmail = $this->sessionUserEmail($userData);
        $creator   = UserIdentity::normalizeEmail((string) ($publication['created_by_email'] ?? ''));
        if (! $this->isAdmin() && $creator !== '' && $creator !== $userEmail) {
            $emails = $this->userEmailsForAccess($userData);
            $isAuthor = ! empty($emails) && $this->publicationAuthorModel
                ->where('publication_id', $id)
                ->whereIn('author_email', $emails)
                ->countAllResults() > 0;
            if (! $isAuthor) {
                return redirect()->to('publications')->with('error', 'Access denied');
            }
        } elseif (! $this->isAdmin() && $creator === '') {
            return redirect()->to('publications')->with('error', 'Access denied');
        }

        $publication['authors'] = $this->getPublicationAuthors($id);

        $data = [
            'title' => 'View Publication',
            'user' => $userData,
            'is_admin' => $this->isAdmin(),
            'publication' => $publication
        ];

        return view('publications/view', $data);
    }

    /**
     * Edit publication page
     * Route: GET /publications/edit/{id}
     */
    public function edit($id)
    {
        $userData = $this->session->get('user_data');
        if (!$userData) {
            return redirect()->to('/auth/login');
        }

        $publication = $this->publicationModel->find($id);
        if (!$publication) {
            return redirect()->to('publications')->with('error', 'Publication not found');
        }

        // Check permissions
        if (!$this->canEdit($publication, $userData)) {
            return redirect()->to('publications')->with('error', 'Access denied');
        }

        $publication['authors'] = $this->getPublicationAuthors($id);

        PublicationReturnNavigation::captureInternalReturnFromRequest();
        PublicationReturnNavigation::captureNsReturnFromRequest();

        $data = [
            'title'        => 'Edit Publication',
            'user'         => $userData,
            'is_admin'     => $this->isAdmin(),
            'publication'  => $publication,
            'is_edit'      => true,
            'form_action'  => site_url('publications/update/' . (int) $id),
            'cancel_url'   => PublicationReturnNavigation::cancelUrl(),
        ];

        return view('publications/create', $data);
    }

    /**
     * Update publication
     * Route: POST /publications/update/{id}
     */
    public function update($id)
    {
        $userData = $this->session->get('user_data');
        if (!$userData) {
            if ($this->request->isAJAX()) {
                return $this->response->setJSON(['success' => false, 'message' => 'Unauthorized']);
            }
            return redirect()->to('/auth/login');
        }

        $publication = $this->publicationModel->find($id);
        if (!$publication) {
            if ($this->request->isAJAX()) {
                return $this->response->setJSON(['success' => false, 'message' => 'Publication not found']);
            }
            return redirect()->to('publications')->with('error', 'Publication not found');
        }

        if (!$this->canEdit($publication, $userData)) {
            if ($this->request->isAJAX()) {
                return $this->response->setJSON(['success' => false, 'message' => 'Access denied']);
            }
            return redirect()->to('publications')->with('error', 'Access denied');
        }

        $this->db->transStart();

        try {
            // Clear any debug output before JSON response
            if (ob_get_level() > 0) {
                ob_clean();
            }
            // Normalize year to CE (ค.ศ.) for database storage
            $publicationYear = $this->request->getPost('publication_year');
            $publicationYear = normalize_year_to_ce($publicationYear);

            $updateData = [
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

            // Only update title if provided (not editable in user edit form)
            $title = $this->request->getPost('title');
            if ($title !== null && $title !== '') {
                $updateData['title'] = $title;
            }

            $this->publicationModel->update($id, $updateData);

            // Update authors if provided
            $authorsJson = $this->request->getPost('authors');
            if (!empty($authorsJson)) {
                $authors = json_decode($authorsJson, true);
                if (is_array($authors) && !empty($authors)) {
                    // Remove old authors
                    $this->publicationAuthorModel->where('publication_id', $id)->delete();

                    // Process authors similar to AdminController
                    $processedAuthors = [];
                    foreach ($authors as $authorInput) {
                        if (empty($authorInput['name'])) continue;

                        $authorData = [
                            'name' => $authorInput['name'],
                            'email' => $authorInput['email'] ?? null,
                            'affiliation' => $authorInput['affiliation'] ?? null,
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

                    // Insert updated authors using PublicationAuthorModel
                    if (!empty($processedAuthors)) {
                        $result = $this->publicationAuthorModel->addAuthorsToPublication($id, $processedAuthors);
                        if ($result === false) {
                            $dbError = $this->db->error();
                            log_message('error', 'Failed to insert authors: ' . json_encode($dbError));
                            throw new \Exception('Failed to insert authors: ' . ($dbError['message'] ?? 'Unknown error'));
                        }
                    }
                }
            }

            $this->db->transComplete();

            if ($this->request->isAJAX()) {
                if (ob_get_level() > 0) {
                    ob_clean();
                }

                return $this->response->setJSON([
                    'success'  => true,
                    'message'  => 'Publication updated successfully',
                    'redirect' => PublicationReturnNavigation::ajaxRedirectUrl(),
                ]);
            }

            return PublicationReturnNavigation::redirectAfterSave('Publication updated successfully');
        } catch (\Exception $e) {
            $this->db->transRollback();
            log_message('error', 'Update publication error: ' . $e->getMessage());

            if ($this->request->isAJAX()) {
                if (ob_get_level() > 0) ob_clean();
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Failed to update publication: ' . $e->getMessage()
                ]);
            }

            return redirect()->back()->withInput()->with('error', 'Failed to update publication');
        }
    }

    /**
     * Approve publication
     * Route: POST /publications/approve/{id}
     */
    public function approve($id = null)
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

            // Update approval status
            $this->publicationModel->update($id, [
                'approve' => 1
            ]);

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
     * Delete publication
     * Route: DELETE /publications/delete/{id}
     */
    public function delete($id)
    {
        log_message('info', '========== DELETE PUBLICATION START ==========');
        log_message('info', 'Delete Publication - ID: ' . $id);
        log_message('info', 'Delete Publication - Request Method: ' . $this->request->getMethod());
        log_message('info', 'Delete Publication - Request URI: ' . $this->request->getUri()->getPath());
        log_message('info', 'Delete Publication - Full URL: ' . $this->request->getUri());
        log_message('info', 'Delete Publication - Base URL: ' . base_url());
        log_message('info', 'Delete Publication - Site URL: ' . site_url('publications/delete/' . $id));

        // Check authentication
        $userData = $this->session->get('user_data');
        log_message('info', 'Delete Publication - User Data: ' . json_encode($userData ? ['uid' => $userData['uid'] ?? 'N/A', 'email' => $userData['email'] ?? 'N/A'] : 'NULL'));

        if (!$userData) {
            log_message('warning', 'Delete Publication - Unauthorized: No user data in session');
            return $this->response->setJSON(['success' => false, 'message' => 'Unauthorized']);
        }

        $userEmail = $this->sessionUserEmail($userData);
        log_message('info', 'Delete Publication - User email: ' . ($userEmail ?: 'NULL'));

        // Get publication
        $publication = $this->publicationModel->find($id);
        log_message('info', 'Delete Publication - Publication found: ' . ($publication ? 'YES' : 'NO'));

        if (!$publication) {
            log_message('warning', 'Delete Publication - Publication not found: ID=' . $id);
            return $this->response->setJSON(['success' => false, 'message' => 'Publication not found']);
        }

        log_message('info', 'Delete Publication - Publication Data: ' . json_encode([
            'id' => $publication['id'] ?? 'N/A',
            'title' => substr($publication['title'] ?? 'N/A', 0, 50),
            'created_by_email' => $publication['created_by_email'] ?? 'N/A'
        ]));

        // Check permissions
        $canDelete = $this->canDelete($publication, $userData);
        log_message('info', 'Delete Publication - Can Delete: ' . ($canDelete ? 'YES' : 'NO'));
        log_message('info', 'Delete Publication - Is Admin: ' . ($this->isAdmin() ? 'YES' : 'NO'));
        log_message('info', 'Delete Publication - Publication created_by_email: ' . ($publication['created_by_email'] ?? 'NULL'));
        log_message('info', 'Delete Publication - User email: ' . ($userEmail ?: 'NULL'));

        if (!$canDelete) {
            log_message('warning', 'Delete Publication - Access denied for user: ' . ($userEmail ?: 'unknown'));
            return $this->response->setJSON(['success' => false, 'message' => 'Access denied']);
        }

        try {
            log_message('info', 'Delete Publication - Starting transaction');
            $this->db->transStart();

            // Delete authors
            log_message('info', 'Delete Publication - Deleting publication_authors for publication_id: ' . $id);
            $authorsDeleted = $this->publicationAuthorModel->where('publication_id', $id)->delete();
            log_message('info', 'Delete Publication - Authors deleted count: ' . ($authorsDeleted ?: '0'));

            // Check if there are any remaining authors
            $remainingAuthors = $this->publicationAuthorModel->where('publication_id', $id)->countAllResults();
            log_message('info', 'Delete Publication - Remaining authors count: ' . $remainingAuthors);

            // Delete publication
            log_message('info', 'Delete Publication - Deleting publication with ID: ' . $id);
            $publicationDeleted = $this->publicationModel->delete($id);
            log_message('info', 'Delete Publication - Publication delete result: ' . ($publicationDeleted ? 'SUCCESS' : 'FAILED'));

            // Verify deletion
            $publicationStillExists = $this->publicationModel->find($id);
            log_message('info', 'Delete Publication - Publication still exists after delete: ' . ($publicationStillExists ? 'YES (ERROR!)' : 'NO (OK)'));

            $transStatus = $this->db->transStatus();
            log_message('info', 'Delete Publication - Transaction status: ' . ($transStatus ? 'SUCCESS' : 'FAILED'));

            if ($transStatus === false) {
                log_message('error', 'Delete Publication - Transaction failed, rolling back');
                $this->db->transRollback();
                $dbError = $this->db->error();
                log_message('error', 'Delete Publication - Database error: ' . json_encode($dbError));
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Failed to delete publication: Database transaction failed'
                ]);
            }

            $this->db->transComplete();
            log_message('info', 'Delete Publication - Transaction completed successfully');

            log_message('info', '========== DELETE PUBLICATION SUCCESS ==========');
            return $this->response->setJSON([
                'success' => true,
                'message' => 'Publication deleted successfully'
            ]);
        } catch (\Exception $e) {
            log_message('error', '========== DELETE PUBLICATION ERROR ==========');
            log_message('error', 'Delete Publication - Exception: ' . $e->getMessage());
            log_message('error', 'Delete Publication - Exception Code: ' . $e->getCode());
            log_message('error', 'Delete Publication - Exception File: ' . $e->getFile());
            log_message('error', 'Delete Publication - Exception Line: ' . $e->getLine());
            log_message('error', 'Delete Publication - Stack Trace: ' . $e->getTraceAsString());

            $this->db->transRollback();
            log_message('error', 'Delete Publication - Transaction rolled back');

            return $this->response->setJSON([
                'success' => false,
                'message' => 'Failed to delete publication: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Search users for author linking (AJAX endpoint)
     */
    public function searchUsers()
    {
        $userData = $this->session->get('user_data');
        if (!$userData || !$this->request->isAJAX()) {
            return $this->response->setJSON(['success' => false, 'message' => 'Unauthorized']);
        }

        try {
            $search = $this->request->getGet('search');
            $email = $this->request->getGet('email');

            if (empty($search) && empty($email)) {
                return $this->response->setJSON(['success' => true, 'data' => []]);
            }

            $builder = $this->userModel->where('active', 1);

            if (!empty($email)) {
                $builder->like('email', $email);
            } else {
                $builder->groupStart()
                    ->like('gf_name', $search)
                    ->orLike('gl_name', $search)
                    ->orLike('email', $search)
                    ->groupEnd();
            }

            $users = $builder->limit(10)->findAll();

            $result = [];
            foreach ($users as $user) {
                $result[] = [
                    'uid' => $user['email'],
                    'name' => trim($user['gf_name'] . ' ' . $user['gl_name']),
                    'email' => $user['email'],
                    'major' => $user['major'] ?? ''
                ];
            }

            return $this->response->setJSON([
                'success' => true,
                'data' => $result
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Search users error: ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Search failed'
            ]);
        }
    }

    /**
     * Link author to user (admin only - AJAX endpoint)
     */
    public function linkAuthor()
    {
        $userData = $this->session->get('user_data');
        if (!$userData || !$this->isAdmin() || !$this->request->isAJAX()) {
            return $this->response->setJSON(['success' => false, 'message' => 'Admin access required']);
        }

        try {
            $authorId = $this->request->getPost('author_id');
            $userEmail = UserIdentity::normalizeEmail((string) $this->request->getPost('user_id'));
            $author = $this->authorModel->find($authorId);
            $user = $this->userModel->find($userEmail);

            if (!$author || !$user) {
                return $this->response->setJSON(['success' => false, 'message' => 'Author or user not found']);
            }

            $this->authorModel->update($authorId, ['user_email' => $userEmail]);

            return $this->response->setJSON([
                'success' => true,
                'message' => 'Author linked successfully'
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Link author error: ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Failed to link author'
            ]);
        }
    }

    // ============================================================================
    // HELPER METHODS
    // ============================================================================


    public function searchUserNames()
    {
        // Check authentication
        $userData = $this->session->get('user_data');
        // if (!$userData || !$this->request->isAJAX()) {
        //     return $this->response->setJSON(['success' => false, 'message' => 'Unauthorized']);
        // }

        try {
            $name = $this->request->getGet('name');
            $email = $this->request->getGet('email');
            $limit = $this->request->getGet('limit');

            // Validate input - accept either name or email
            $searchTerm = !empty($name) ? trim($name) : (!empty($email) ? trim($email) : '');

            if (empty($searchTerm) || strlen($searchTerm) < 2) {
                return $this->response->setJSON([
                    'success' => true,
                    'users' => [],
                    'message' => 'Search term too short'
                ]);
            }

            $limit = min(max((int)$limit, 1), 20); // Between 1 and 20

            // Search users by name or email
            $builder = $this->userModel->builder();

            $users = $builder
                ->select('
                email,
                gf_name,
                gl_name,
                thai_name,
                thai_lastname,
                major,
                title,
                titleThai
            ')
                ->where('active', 1)
                ->groupStart()
                ->like('gf_name', $searchTerm)
                ->orLike('gl_name', $searchTerm)
                ->orLike('thai_name', $searchTerm)
                ->orLike('thai_lastname', $searchTerm)
                ->orLike('email', $searchTerm)
                ->orLike("CONCAT(gf_name, ' ', gl_name)", $searchTerm)
                ->orLike("CONCAT(thai_name, ' ', thai_lastname)", $searchTerm)
                ->groupEnd()
                ->orderBy('gf_name', 'ASC')
                ->limit($limit)
                ->get()
                ->getResultArray();

            // Format results for frontend
            $formattedUsers = array_map(function ($user) {
                // Determine best display name
                $englishName = trim($user['gf_name'] . ' ' . $user['gl_name']);
                $thaiName = trim($user['thai_name'] . ' ' . $user['thai_lastname']);

                $displayName = !empty($thaiName) ? $thaiName : $englishName;

                return [
                    'id' => $user['email'],
                    'uid' => $user['email'],
                    'name' => $displayName,
                    'display_name' => $displayName,
                    'english_name' => $englishName,
                    'thai_name' => $thaiName,
                    'email' => $user['email'],
                    'affiliation' => 'มหาวิทยาลัยราชภัฏอุตรดิตถ์',
                    'title' => $user['title'] ?? ''
                    // titleThai removed - not needed in frontend
                ];
            }, $users);

            return $this->response->setJSON([
                'success' => true,
                'users' => $formattedUsers,
                'count' => count($formattedUsers),
                'search_term' => $searchTerm
            ]);
        } catch (\Exception $e) {
            log_message('error', 'User name search error: ' . $e->getMessage());

            return $this->response->setJSON([
                'success' => false,
                'message' => 'Search failed',
                'users' => []
            ]);
        }
    }


    /**
     * Enhanced Email Auto-Complete - Search author by email
     * Handles multiple emails per user and proper linking
     * Route: GET /publications/search-author-email
     */
    public function searchAuthorEmail()
    {
        // Check authentication and AJAX request
        $userData = $this->session->get('user_data');
        // if (!$userData || !$this->request->isAJAX()) {
        //     return $this->response->setJSON(['success' => false, 'message' => 'Unauthorized']);
        // }

        try {
            $email = $this->request->getGet('email');

            // Validate minimum length (3 characters)
            if (empty($email) || strlen(trim($email)) < 3) {
                return $this->response->setJSON([
                    'success' => true,
                    'found' => false,
                    'message' => 'Search term too short'
                ]);
            }

            $email = trim($email);

            // Try exact match first if it's a valid email
            $author = null;
            if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $author = $this->authorModel->getAuthorlinkUser($email);
            }

            // If no exact match and not a complete email, search by partial match
            if (!$author) {
                // Search in user table by email (partial match)
                $user = $this->userModel->builder()
                    ->select('email, thai_name, thai_lastname, gf_name, gl_name, titleThai, major')
                    ->where('active', 1)
                    ->like('email', $email)
                    ->limit(1)
                    ->get()
                    ->getRowArray();

                if ($user) {
                    // Format user data as author
                    $thaiName = trim($user['thai_name'] . ' ' . $user['thai_lastname']);
                    $author = [
                        'id' => null,
                        'name' => !empty($thaiName) ? $thaiName : trim($user['gf_name'] . ' ' . $user['gl_name']),
                        'email' => $user['email'],
                        'user_email' => $user['email'],
                        'is_linked' => true,
                        'affiliation' => $user['major'] ?? '',
                    ];
                }
            }

            if ($author) {
                $affiliation = !empty($author['affiliation'])
                    ? $author['affiliation']
                    : 'มหาวิทยาลัยราชภัฏอุตรดิตถ์';

                // Build response using data already retrieved
                $response = [
                    'success' => true,
                    'found' => true,
                    'author' => [
                        'id' => $author['id'],
                        'name' => $author['name'],
                        'email' => $author['email'],
                        'affiliation' => $affiliation,
                        'user_uid' => $author['user_email'] ?? ''
                    ]
                ];

                // Add user info if linked (data already available from JOIN)
                if ($author['is_linked']) {
                    $response['user_info'] = [
                        'full_name' => $author['name'],
                        'major' => $author['affiliation'] ?? '',
                        'is_linked' => true
                    ];

                    // Optional: Get other emails for this user (if needed)
                    if (method_exists($this, 'getAuthorEmailsByUser')) {
                        $userEmails = $this->getAuthorEmailsByUser($author['user_email'] ?? '');
                        if (count($userEmails) > 1) {
                            $response['other_emails'] = array_filter($userEmails, function ($e) use ($email) {
                                return $e !== $email;
                            });
                        }
                    }
                }

                return $this->response->setJSON($response);
            }

            // No match found
            return $this->response->setJSON([
                'success' => true,
                'found' => false,
                'message' => 'New author - will be added to publication_authors table'
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Author email search error: ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Search failed'
            ]);
        }
    }

    /**
     * Helper method to get all author emails for a specific user
     */
    private function getAuthorEmailsByUser($userEmail)
    {
        $userEmail = UserIdentity::normalizeEmail((string) $userEmail);

        return $this->authorModel->getAuthorEmailsByUser($userEmail);
    }


    /**
     * @deprecated This method is no longer used. Use addAuthorsToPublication() from PublicationAuthorModel instead.
     * The authors table doesn't have 'name' and 'affiliation' columns - those are stored in publication_authors table.
     */
    private function addAuthors($publicationId, $authors, $createdBy)
    {
        // This method is deprecated - use PublicationAuthorModel::addAuthorsToPublication() instead
        // which properly handles the author data structure
        $processedAuthors = [];
        foreach ($authors as $index => $authorData) {
            if (empty($authorData['name'])) continue;

            $processedAuthor = [
                'name' => $authorData['name'],
                'email' => $authorData['email'] ?? null,
                'affiliation' => $authorData['affiliation'] ?? null,
                'author_id' => null,
                'uid' => null,
                'corresponding' => isset($authorData['corresponding']) && $authorData['corresponding'] == '1' ? 1 : 0
            ];

            // Try to find existing author by email
            if (!empty($authorData['email'])) {
                $existingAuthor = $this->authorModel->getAuthorlinkUser($authorData['email']);
                if ($existingAuthor) {
                    $processedAuthor['author_id'] = $existingAuthor['id'];
                    $processedAuthor['uid'] = $existingAuthor['user_id'];
                }
            }

            $processedAuthors[] = $processedAuthor;
        }

        if (!empty($processedAuthors)) {
            $result = $this->publicationAuthorModel->addAuthorsToPublication($publicationId, $processedAuthors);
            if ($result === false) {
                $dbError = $this->db->error();
                log_message('error', 'Failed to insert authors: ' . json_encode($dbError));
                throw new \RuntimeException('Failed to save author information: ' . ($dbError['message'] ?? 'Unknown error'));
            }
        }
    }

    /**
     * Get single publication with authors for editing (AJAX endpoint for user)
     * Route: GET /publications/get/{id}
     */
    public function getPublication($id = null)
    {
        log_message('info', '========== getPublication (User) START ==========');
        log_message('info', 'getPublication - ID: ' . $id);

        $userData = $this->session->get('user_data');
        if (!$userData) {
            log_message('warning', 'getPublication - Unauthorized');
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Unauthorized'
            ]);
        }

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

            // Check permissions - user can only view their own publications
            if (!$this->canEdit($publication, $userData)) {
                log_message('warning', 'getPublication - Access denied for user: ' . ($userData['uid'] ?? 'unknown'));
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Access denied'
                ]);
            }

            // Get authors for this publication with user info (same as admin)
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

            // Process authors to get proper names (without titleThai for edit form)
            foreach ($authors as &$author) {
                // Flag whether this author is linked to a system user — used by
                // the edit form to render a "matched" chip instead of plain inputs.
                $author['is_user_matched'] = !empty($author['user_thai_name'])
                    || !empty($author['author_user_thai_name']);

                // Priority: uid from user table > author_id from authors+user > name from publication_authors
                if (!empty($author['author_email']) && !empty($author['user_thai_name'])) {
                    // Use user table Thai name (direct link via author_email) - WITHOUT titleThai
                    $fullName = trim($author['user_thai_name'] . ' ' . ($author['user_thai_lastname'] ?? ''));
                    // Do NOT include titleThai in author_name for edit form
                    $author['author_name'] = $fullName;
                } elseif (!empty($author['author_id']) && !empty($author['author_user_thai_name'])) {
                    // Use authors table linked to user (via authors.user_uid) - WITHOUT titleThai
                    $fullName = trim($author['author_user_thai_name'] . ' ' . ($author['author_user_thai_lastname'] ?? ''));
                    // Do NOT include titleThai in author_name for edit form
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

                // Ensure email and affiliation are set
                if (empty($author['author_email'])) {
                    $author['author_email'] = $author['author_email'] ?? '';
                }
                if (empty($author['author_affiliation'])) {
                    $author['author_affiliation'] = $author['author_affiliation'] ?? '';
                }
                if (!isset($author['corresponding'])) {
                    $author['corresponding'] = $author['corresponding'] ?? 0;
                }

                // Remove titleThai and related fields from response (not needed in frontend)
                unset($author['user_title_thai']);
                unset($author['author_user_title_thai']);
                unset($author['user_thai_name']);
                unset($author['user_thai_lastname']);
                unset($author['author_user_thai_name']);
                unset($author['author_user_thai_lastname']);
                unset($author['author_email_from_authors']);
            }

            $publication['authors'] = $authors;

            log_message('info', 'getPublication - Authors count: ' . count($authors));
            log_message('info', 'getPublication - SUCCESS');
            log_message('info', '========== getPublication (User) END ==========');

            return $this->response->setJSON([
                'success' => true,
                'data' => $publication
            ]);
        } catch (\Exception $e) {
            log_message('error', '========== getPublication (User) ERROR ==========');
            log_message('error', 'getPublication error: ' . $e->getMessage());
            log_message('error', 'Stack trace: ' . $e->getTraceAsString());
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Get publication authors with linking status
     */
    private function getPublicationAuthors($publicationId)
    {
        $rows = $this->db->table('publication_authors pa')
            ->select('pa.*, a.user_email as authors_user_email, a.id as author_id,
                      u1.email as matched_user_email_by_pa,
                      u2.email as matched_user_email_by_a')
            ->join('authors a', 'pa.author_id = a.id', 'left')
            ->join('user u1', 'pa.author_email = u1.email', 'left')
            ->join('user u2', 'a.user_email = u2.email', 'left')
            ->where('pa.publication_id', $publicationId)
            ->orderBy('pa.author_order')
            ->get()
            ->getResultArray();

        foreach ($rows as &$row) {
            $row['is_user_matched'] = !empty($row['matched_user_email_by_pa'])
                || !empty($row['matched_user_email_by_a']);
            if (empty($row['author_email']) && !empty($row['authors_user_email'])) {
                $row['author_email'] = $row['authors_user_email'];
            }
            unset($row['matched_user_email_by_pa'], $row['matched_user_email_by_a']);
        }

        return $rows;
    }

    /**
     * Check if user can edit publication
     */
    private function canEdit($publication, $userData)
    {
        if ($this->isAdmin()) {
            return true;
        }

        $email = $this->sessionUserEmail($userData);
        if ($email === '') {
            return false;
        }

        $creator = UserIdentity::normalizeEmail((string) ($publication['created_by_email'] ?? ''));
        if ($creator !== '' && $creator === $email) {
            return true;
        }

        $emails = $this->userEmailsForAccess($userData);

        return $emails !== [] && $this->publicationAuthorModel
            ->where('publication_id', $publication['id'])
            ->whereIn('author_email', $emails)
            ->countAllResults() > 0;
    }

    /**
     * Check if user can delete publication
     */
    private function canDelete($publication, $userData)
    {
        return $this->canEdit($publication, $userData);
    }

    /**
     * Check if current user is admin
     */
    private function isAdmin()
    {
        $userData = $this->session->get('user_data');
        return $userData && isset($userData['is_admin']) && $userData['is_admin'] == 1;
    }
}
