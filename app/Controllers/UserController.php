<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use App\Models\UserModel;
use App\Models\AuthorModel;

class UserController extends Controller
{
    protected $userModel;
    protected $authorModel;
    protected $session;

    public function __construct()
    {
        $this->userModel = new UserModel();
        $this->authorModel = new AuthorModel();
        $this->session = session();
    }

    /**
     * Get all users (AJAX)
     * Can filter by curriculum_id
     */
    public function index()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setJSON(['success' => false, 'message' => 'Invalid request']);
        }

        try {
            $curriculumId = $this->request->getGet('curriculum_id');

            if ($curriculumId !== null) {
                // Get users by curriculum
                $users = $this->userModel->getUsersByCurriculum($curriculumId);
            } else {
                // Get all active users
                $users = $this->userModel->getActiveUsers();
            }

            return $this->response->setJSON([
                'success' => true,
                'data' => $users
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Get users error: ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Failed to load users'
            ]);
        }
    }

    /**
     * Get unassigned users (AJAX)
     * Users without curriculum_id
     */
    public function getUnassigned()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setJSON(['success' => false, 'message' => 'Invalid request']);
        }

        try {
            $users = $this->userModel->getUnassignedUsers();

            return $this->response->setJSON([
                'success' => true,
                'data' => $users
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Get unassigned users error: ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Failed to load unassigned users'
            ]);
        }
    }

    /**
     * Update user curriculum assignment (AJAX)
     * Uses teacher_curriculum table for many-to-many relationship
     * Schema: User has main faculty (user.faculty_id), Curriculum can be from any faculty
     */
    public function updateCurriculum()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setJSON(['success' => false, 'message' => 'Invalid request']);
        }

        try {
            $userId = $this->request->getPost('user_id');
            $curriculumId = $this->request->getPost('curriculum_id');

            if (!$userId) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'User ID is required'
                ]);
            }

            // If curriculum_id is empty/null, remove all curriculum assignments
            if (empty($curriculumId)) {
                // Remove all curriculum assignments from teacher_curriculum table
                $db = \Config\Database::connect();
                $db->table('teacher_curriculum')
                    ->where('teacher_email', $userId)
                    ->delete();
                
                // Also clear legacy user.curriculum_id for backward compatibility
                $this->userModel->update($userId, ['curriculum_id' => null]);
                
                log_message('info', 'Removed all curriculum assignments for user: ' . $userId);
                
                return $this->response->setJSON([
                    'success' => true,
                    'message' => 'User curriculum removed successfully'
                ]);
            }

            // Assign curriculum using teacher_curriculum table
            // Set as primary curriculum and mark others as non-primary
            $assigned = $this->userModel->assignTeacherToCurriculum(
                $userId,
                $curriculumId,
                'instructor', // default role
                true // set as primary
            );

            // Also update legacy user.curriculum_id for backward compatibility
            $this->userModel->update($userId, ['curriculum_id' => $curriculumId]);

            if ($assigned) {
                log_message('info', 'Assigned curriculum ' . $curriculumId . ' to user: ' . $userId);
                return $this->response->setJSON([
                    'success' => true,
                    'message' => 'User curriculum updated successfully'
                ]);
            }

            return $this->response->setJSON([
                'success' => false,
                'message' => 'Failed to update user curriculum'
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Update user curriculum error: ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Error updating user curriculum: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Remove user from curriculum (AJAX)
     * Removes a specific user from a specific curriculum
     */
    public function removeFromCurriculum()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setJSON(['success' => false, 'message' => 'Invalid request']);
        }

        try {
            $userId = $this->request->getPost('user_id');
            $curriculumId = $this->request->getPost('curriculum_id');

            if (!$userId || !$curriculumId) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'User ID and Curriculum ID are required'
                ]);
            }

            // Remove user from curriculum using teacher_curriculum table
            $removed = $this->userModel->removeTeacherFromCurriculum($userId, $curriculumId);

            if ($removed) {
                // Check if removed user is the chair of this curriculum
                $curriculumModel = new \App\Models\CurriculumModel();
                $curriculum = $curriculumModel->find($curriculumId);
                
                if ($curriculum && isset($curriculum['chair_id']) && $curriculum['chair_id'] == $userId) {
                    // Remove chair if the removed user is the chair
                    $curriculumModel->update($curriculumId, ['chair_id' => null]);
                    log_message('info', 'Removed chair (user ' . $userId . ') from curriculum ' . $curriculumId);
                }
                
                log_message('info', 'Removed user ' . $userId . ' from curriculum ' . $curriculumId);
                return $this->response->setJSON([
                    'success' => true,
                    'message' => 'User removed from curriculum successfully'
                ]);
            }

            return $this->response->setJSON([
                'success' => false,
                'message' => 'Failed to remove user from curriculum'
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Remove user from curriculum error: ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Error removing user from curriculum: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Get users with curriculum info (AJAX)
     * Supports search parameter
     */
    public function getWithCurriculum()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setJSON(['success' => false, 'message' => 'Invalid request']);
        }

        try {
            $session = session();
            $userData = $session->get('user_data') ?? [];
            $userRole = $userData['role'] ?? 'user';
            $isGodMode = $session->get('god_mode') || $session->get('backdoor_session');
            $isFacultyAdmin = ($userRole === 'faculty_admin') && !$isGodMode;

            $search = $this->request->getGet('search');
            $facultyId = $this->request->getGet('faculty_id');

            // Faculty admin can only see their own faculty
            if ($isFacultyAdmin) {
                // Try to get managed faculties from RoleHelper first
                $managedFaculties = \App\Helpers\RoleHelper::getManagedFaculties($userData);

                if (!empty($managedFaculties)) {
                    // Use first managed faculty (most faculty admins manage only one)
                    $facultyId = $managedFaculties[0];
                } elseif (!empty($userData['faculty_id'])) {
                    // Fallback to user's faculty_id if no managed_faculties defined
                    $facultyId = $userData['faculty_id'];
                } else {
                    // Faculty admin without any faculty - return empty
                    return $this->response->setJSON([
                        'success' => true,
                        'data' => []
                    ]);
                }
            }

            if ($search || $facultyId) {
                // Search users with optional faculty filter
                $users = $this->userModel->searchUsersWithCurriculum($search, $facultyId);
            } else {
                // Get all users with curriculum
                $users = $this->userModel->getUsersWithCurriculum($facultyId);
            }

            return $this->response->setJSON([
                'success' => true,
                'data' => $users
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Get users with curriculum error: ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Failed to load users'
            ]);
        }
    }

    /**
     * Search user/author by email
     * Used for AI author matching
     */
    public function searchByEmail()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Invalid request method'
            ]);
        }

        try {
            $requestData = $this->request->getJSON(true);
            $email = $requestData['email'] ?? '';

            if (empty($email)) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Email is required'
                ]);
            }

            // First, search by email in users table
            $user = $this->userModel->where('email', $email)->first();

            if ($user) {
                // Convert user data to author format
                $authorData = [
                    'id' => null,
                    'user_id' => $user['email'],
                    'user_uid' => $user['email'],
                    'uid' => $user['email'],
                    'email' => $user['email'],
                    'thai_name' => ($user['thai_name'] ?? '') . ' ' . ($user['thai_lastname'] ?? ''),
                    'english_name' => ($user['gf_name'] ?? '') . ' ' . ($user['gl_name'] ?? ''),
                    'name' => ($user['thai_name'] ?? '') . ' ' . ($user['thai_lastname'] ?? ''),
                    'first_name' => $user['thai_name'] ?? '',
                    'last_name' => $user['thai_lastname'] ?? '',
                    'thai_first_name' => $user['thai_name'] ?? '',
                    'thai_last_name' => $user['thai_lastname'] ?? '',
                    'gf_name' => $user['gf_name'] ?? '',
                    'gl_name' => $user['gl_name'] ?? '',
                    'affiliation' => $user['organization'] ?? 'มหาวิทยาลัยราชภัฏอุตรดิตถ์',
                    'organization' => $user['organization'] ?? 'มหาวิทยาลัยราชภัฏอุตรดิตถ์'
                ];

                return $this->response->setJSON([
                    'success' => true,
                    'author' => $authorData
                ]);
            }

            // If not found in users table, search in authors table
            $author = $this->authorModel->getAuthorlinkUser($email);

            if ($author) {
                // Author found (may or may not be linked to a user)
                $authorData = [
                    'id' => $author['id'] ?? $author['author_id'] ?? null,
                    'author_id' => $author['author_id'] ?? null,
                    'user_id' => $author['user_id'] ?? null,
                    'user_uid' => $author['user_email'] ?? null,
                    'uid' => $author['user_email'] ?? null,
                    'email' => $author['email'] ?? $email,
                    'thai_name' => $author['name'] ?? '',
                    'english_name' => $author['name'] ?? '',
                    'name' => $author['name'] ?? '',
                    'first_name' => '',
                    'last_name' => '',
                    'thai_first_name' => '',
                    'thai_last_name' => '',
                    'gf_name' => '',
                    'gl_name' => '',
                    'affiliation' => $author['affiliation'] ?? 'มหาวิทยาลัยราชภัฏอุตรดิตถ์',
                    'organization' => $author['affiliation'] ?? 'มหาวิทยาลัยราชภัฏอุตรดิตถ์',
                    'is_linked' => $author['is_linked'] ?? false
                ];

                return $this->response->setJSON([
                    'success' => true,
                    'author' => $authorData
                ]);
            }

            return $this->response->setJSON([
                'success' => false,
                'message' => 'User not found'
            ]);

        } catch (\Exception $e) {
            log_message('error', 'User email search error: ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Search user/author by name (Thai or English)
     * Used for AI author matching
     * Matches BOTH first name AND last name together
     */
    public function searchByName()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Invalid request method'
            ]);
        }

        try {
            $requestData = $this->request->getJSON(true);
            $firstName = $requestData['first_name'] ?? '';
            $lastName = $requestData['last_name'] ?? '';
            $language = $requestData['language'] ?? 'th'; // 'th' or 'en'

            if (empty($firstName)) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'First name is required'
                ]);
            }

            // Search in users table
            // Match BOTH first name AND last name together
            if ($language === 'th') {
                // Thai: Search in thai_name and thai_lastname
                $query = $this->userModel->like('thai_name', $firstName, 'both');

                // Only add last name filter if last name is provided
                if (!empty($lastName)) {
                    $query = $query->like('thai_lastname', $lastName, 'both');
                }

                $user = $query->first();
            } else {
                // English: Search in gf_name (global first name) and gl_name (global last name)
                $query = $this->userModel->like('gf_name', $firstName, 'both');

                // Only add last name filter if last name is provided
                if (!empty($lastName)) {
                    $query = $query->like('gl_name', $lastName, 'both');
                }

                $user = $query->first();
            }

            if ($user) {
                // Convert user data to author format
                $authorData = [
                    'id' => null,
                    'user_id' => $user['email'],
                    'user_uid' => $user['email'],
                    'uid' => $user['email'],
                    'email' => $user['email'],
                    'thai_name' => ($user['thai_name'] ?? '') . ' ' . ($user['thai_lastname'] ?? ''),
                    'english_name' => ($user['gf_name'] ?? '') . ' ' . ($user['gl_name'] ?? ''),
                    'name' => ($user['thai_name'] ?? '') . ' ' . ($user['thai_lastname'] ?? ''),
                    'first_name' => $language === 'th' ? ($user['thai_name'] ?? '') : ($user['gf_name'] ?? ''),
                    'last_name' => $language === 'th' ? ($user['thai_lastname'] ?? '') : ($user['gl_name'] ?? ''),
                    'thai_first_name' => $user['thai_name'] ?? '',
                    'thai_last_name' => $user['thai_lastname'] ?? '',
                    'gf_name' => $user['gf_name'] ?? '',
                    'gl_name' => $user['gl_name'] ?? '',
                    'affiliation' => $user['organization'] ?? 'มหาวิทยาลัยราชภัฏอุตรดิตถ์',
                    'organization' => $user['organization'] ?? 'มหาวิทยาลัยราชภัฏอุตรดิตถ์'
                ];

                return $this->response->setJSON([
                    'success' => true,
                    'author' => $authorData
                ]);
            }

            return $this->response->setJSON([
                'success' => false,
                'message' => 'User not found'
            ]);

        } catch (\Exception $e) {
            log_message('error', 'User name search error: ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ]);
        }
    }

}
