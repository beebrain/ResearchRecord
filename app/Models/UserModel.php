<?php

namespace App\Models;

use CodeIgniter\Model;

class UserModel extends Model
{
    protected $table = 'user';
    protected $primaryKey = 'uid';
    protected $returnType = 'array';

    protected $allowedFields = [
        'login_uid',
        'email',
        'password',
        'title',
        'gf_name',
        'gl_name',
        'major',
        'thai_name',
        'thai_lastname',
        'profile_picture',
        'profile_customer',
        'created_at',
        'titleThai',
        'active',
        'admin',
        'edoc',
        'curriculum_id',
        'role',
        'managed_faculties',
        'faculty_id',
        'department_id',
        'user_type',
        'degree',
        'gender',
        'nickname',
        'birth_date',
        'nationality',
        'citizen_id',
        'passport_id'
    ];

    /**
     * Find user by login_uid
     */
    public function findByLoginUid($loginUid)
    {
        return $this->where('login_uid', $loginUid)->first();
    }

    /**
     * Get user by email
     */
    public function getUserByEmail($email)
    {
        return $this->where('email', $email)
            ->where('active', 1)
            ->first();
    }

    /**
     * Insert user data
     */
    public function insertUserData($userData)
    {
        if ($this->insert($userData)) {
            return $this->getInsertID();
        }
        return false;
    }

    /**
     * Update user data with email condition
     */
    public function updateUserDataWithEmail($userData, $email, $field = 'email')
    {
        return $this->where($field, $email)->set($userData)->update();
    }

    /**
     * Get user statistics
     */
    public function getUserStats($userId)
    {
        $db = \Config\Database::connect();

        $publicationCount = $db->table('publications')
            ->where('created_by', $userId)
            ->countAllResults();

        $authorCount = $db->table('authors')
            ->where('created_by', $userId)
            ->countAllResults();

        return [
            'publications' => $publicationCount,
            'authors' => $authorCount
        ];
    }


    public function getActiveUsers()
    {
        return $this->where('active', 1)
            ->orderBy('uid', 'ASC')
            ->findAll();
    }

    /**
     * Get users by curriculum
     */
    public function getUsersByCurriculum($curriculumId)
    {
        if ($curriculumId === '0' || $curriculumId === 0 || $curriculumId === null) {
            return $this->getUnassignedUsers();
        }

        return $this->where('curriculum_id', $curriculumId)
            ->where('active', 1)
            ->orderBy('gf_name', 'ASC')
            ->findAll();
    }

    /**
     * Get unassigned users (users without curriculum)
     */
    public function getUnassignedUsers()
    {
        return $this->where('active', 1)
            ->groupStart()
            ->where('curriculum_id', null)
            ->orWhere('curriculum_id', 0)
            ->groupEnd()
            ->orderBy('gf_name', 'ASC')
            ->findAll();
    }

    /**
     * Get users with curriculum information
     * Schema: User has main faculty (user.faculty_id), User can have curriculum (from teacher_curriculum)
     * Curriculum can be from different faculty than user's main faculty
     */
    public function getUsersWithCurriculum($facultyId = null)
    {
        // Show ONLY TEACHERS - Staff are excluded from curriculum management
        // Use ONLY teacher_curriculum table (no fallback to user.curriculum_id)
        $builder = $this->db->table('user');

        $builder->select([
            'user.*',
            'user.faculty_id as user_faculty_id',
            'uf.name as user_faculty_name',
            'uf.code as user_faculty_code',
            // Use ONLY teacher_curriculum table - get primary curriculum
            'MAX(tc.curriculum_id) as curriculum_id',
            'MAX(tc.is_primary) as is_primary',
            'MAX(tc.role) as curriculum_role',
            // Get curriculum details from teacher_curriculum
            'MAX(c.name) as curriculum_name',
            'MAX(c.code) as curriculum_code',
            'MAX(c.faculty_id) as curriculum_faculty_id',
            'MAX(cf.name) as curriculum_faculty_name',
            'COUNT(DISTINCT tc2.curriculum_id) as total_curriculums',
            'GROUP_CONCAT(DISTINCT CASE WHEN tc2.is_primary = 0 THEN c2.name END SEPARATOR ", ") as other_curriculums'
        ])
            ->join('faculties uf', 'uf.id = user.faculty_id', 'left')
            ->join('teacher_curriculum tc', 'tc.teacher_uid = user.uid AND tc.is_primary = 1 AND tc.status = 1', 'left')
            ->join('teacher_curriculum tc2', 'tc2.teacher_uid = user.uid AND tc2.status = 1', 'left')
            ->join('curriculum c', 'c.id = tc.curriculum_id', 'left')
            ->join('curriculum c2', 'c2.id = tc2.curriculum_id', 'left')
            ->join('faculties cf', 'cf.id = c.faculty_id', 'left')
            ->where('user.active', 1)
            // ONLY show TEACHER (exclude STAFF and others)
            ->where('user.user_type', 'TEACHER');

        // Handle faculty filter - filter by user's main faculty (user.faculty_id)
        if ($facultyId === 'null') {
            // Show only users without main faculty
            $builder->where('user.faculty_id IS NULL');
        } elseif (!empty($facultyId)) {
            // Show users with specific main faculty (user.faculty_id)
            $builder->where('user.faculty_id', $facultyId);
        }

        return $builder->groupBy('user.uid')
            // Show teachers without main faculty first, then those without curriculum
            ->orderBy('CASE WHEN user.faculty_id IS NULL THEN 0 ELSE 1 END', 'ASC', false)
            ->orderBy('CASE WHEN MAX(tc.curriculum_id) IS NULL THEN 0 ELSE 1 END', 'ASC', false)
            ->orderBy('user.faculty_id', 'ASC')
            ->orderBy('user.gf_name', 'ASC')
            ->get()
            ->getResultArray();
    }

    /**
     * Update user curriculum
     */
    public function updateCurriculum($userId, $curriculumId)
    {
        return $this->update($userId, [
            'curriculum_id' => $curriculumId
        ]);
    }

    /**
     * Search users with curriculum information
     * Searches by name (Thai/English) and email
     * Updated to show ONLY TEACHERS (exclude STAFF)
     * Schema: User has main faculty (user.faculty_id), Curriculum can be from different faculty
     */
    public function searchUsersWithCurriculum($search, $facultyId = null)
    {
        $search = trim($search);
        $builder = $this->db->table('user');

        $builder->select([
            'user.*',
            'user.faculty_id as user_faculty_id',
            'uf.name as user_faculty_name',
            'uf.code as user_faculty_code',
            // Use ONLY teacher_curriculum table - get primary curriculum
            'MAX(tc.curriculum_id) as curriculum_id',
            'MAX(tc.is_primary) as is_primary',
            'MAX(tc.role) as curriculum_role',
            // Get curriculum details from teacher_curriculum
            'MAX(c.name) as curriculum_name',
            'MAX(c.code) as curriculum_code',
            'MAX(c.faculty_id) as curriculum_faculty_id',
            'MAX(cf.name) as curriculum_faculty_name',
            'COUNT(DISTINCT tc2.curriculum_id) as total_curriculums',
            'GROUP_CONCAT(DISTINCT CASE WHEN tc2.is_primary = 0 THEN c2.name END SEPARATOR ", ") as other_curriculums'
        ])
            ->join('faculties uf', 'uf.id = user.faculty_id', 'left')
            ->join('teacher_curriculum tc', 'tc.teacher_uid = user.uid AND tc.is_primary = 1 AND tc.status = 1', 'left')
            ->join('teacher_curriculum tc2', 'tc2.teacher_uid = user.uid AND tc2.status = 1', 'left')
            ->join('curriculum c', 'c.id = tc.curriculum_id', 'left')
            ->join('curriculum c2', 'c2.id = tc2.curriculum_id', 'left')
            ->join('faculties cf', 'cf.id = c.faculty_id', 'left')
            ->where('user.active', 1);

        // ONLY show TEACHER (exclude STAFF and others)
        $builder->where('user.user_type', 'TEACHER');

        if (!empty($search)) {
            $builder->groupStart()
                ->like('user.thai_name', $search)
                ->orLike('user.thai_lastname', $search)
                ->orLike('user.gf_name', $search)
                ->orLike('user.gl_name', $search)
                ->orLike('user.email', $search)
                ->groupEnd();
        }

        // Handle faculty filter - filter by user's main faculty (user.faculty_id)
        if ($facultyId === 'null') {
            // Show only users without main faculty
            $builder->where('user.faculty_id IS NULL');
        } elseif (!empty($facultyId)) {
            // Show users with specific main faculty (user.faculty_id)
            $builder->where('user.faculty_id', $facultyId);
        }

        $builder->groupBy('user.uid')
            ->orderBy('CASE WHEN user.faculty_id IS NULL THEN 0 ELSE 1 END', 'ASC', false)
            ->orderBy('CASE WHEN MAX(tc.curriculum_id) IS NULL THEN 0 ELSE 1 END', 'ASC', false)
            ->orderBy('user.faculty_id', 'ASC')
            ->orderBy('user.gf_name', 'ASC');

        return $builder->get()->getResultArray();
    }

    // ========== ROLE-BASED METHODS ==========

    /**
     * Get users by role
     */
    public function getUsersByRole($role)
    {
        return $this->where('role', $role)
            ->where('active', 1)
            ->orderBy('gf_name', 'ASC')
            ->findAll();
    }

    /**
     * Get all faculty admins
     */
    public function getFacultyAdmins()
    {
        return $this->getUsersByRole('faculty_admin');
    }

    /**
     * Get all super admins
     */
    public function getSuperAdmins()
    {
        return $this->getUsersByRole('super_admin');
    }

    /**
     * Update user role
     */
    public function updateRole($userId, $role, $managedFaculties = null)
    {
        $data = ['role' => $role];

        if ($role === 'faculty_admin' && is_array($managedFaculties)) {
            $data['managed_faculties'] = json_encode($managedFaculties);
        } else {
            $data['managed_faculties'] = null;
        }

        return $this->update($userId, $data);
    }

    /**
     * Get users filtered by faculty admin permissions
     * Used to show only users within managed faculties
     */
    public function getUsersByFacultyAdmin($managedFaculties)
    {
        if (empty($managedFaculties)) {
            return [];
        }

        $curriculumModel = new \App\Models\CurriculumModel();
        $curricula = $curriculumModel->whereIn('faculty_id', $managedFaculties)->findAll();

        if (empty($curricula)) {
            return [];
        }

        $curriculumIds = array_column($curricula, 'id');

        return $this->select('user.*, curriculum.name as curriculum_name, curriculum.code as curriculum_code, faculties.name as faculty_name')
            ->join('curriculum', 'curriculum.id = user.curriculum_id', 'left')
            ->join('faculties', 'faculties.id = curriculum.faculty_id', 'left')
            ->where('user.active', 1)
            ->whereIn('user.curriculum_id', $curriculumIds)
            ->orderBy('user.gf_name', 'ASC')
            ->findAll();
    }

    /**
     * Get users with role information
     * Includes role badge and managed faculties for faculty admins
     * Faculty is loaded from user.faculty_id (user table) - main faculty affiliation
     * Curriculum is loaded from teacher_curriculum table (primary curriculum)
     * Uses user.uid (user_id) as the main join key
     * 
     * Schema: User has faculty (required), User can have curriculum (optional, can be from different faculty)
     */
    public function getUsersWithRole()
    {
        // Use user.uid as the main identifier and join key
        // Use ONLY teacher_curriculum table (no fallback to user.curriculum_id)
        $builder = $this->db->table('user')
            ->select('
                user.uid as user_id, 
                user.*, 
                uf.name as user_faculty_name, 
                uf.code as user_faculty_code,
                user.faculty_id as user_faculty_id,
                tc.curriculum_id as curriculum_id,
                c.name as curriculum_name,
                c.code as curriculum_code,
                c.faculty_id as curriculum_faculty_id,
                cf.name as curriculum_faculty_name
            ', false)
            ->join('faculties uf', 'uf.id = user.faculty_id', 'left')
            ->join('teacher_curriculum tc', 'tc.teacher_uid = user.uid AND tc.is_primary = 1 AND tc.status = 1', 'left')
            ->join('curriculum c', 'c.id = tc.curriculum_id', 'left')
            ->join('faculties cf', 'cf.id = c.faculty_id', 'left')
            ->where('user.active', 1)
            ->orderBy('user.role', 'DESC')
            ->orderBy('user.gf_name', 'ASC');

        // Log the query for debugging
        $query = $builder->get();
        $sql = $this->db->getLastQuery();
        log_message('debug', 'getUsersWithRole SQL Query: ' . $sql);
        log_message('debug', 'getUsersWithRole - Using user.uid (user_id) as main join key');
        log_message('debug', 'getUsersWithRole - Faculty loaded from: user.faculty_id (user table - main faculty)');
        log_message('debug', 'getUsersWithRole - Curriculum loaded from: teacher_curriculum table ONLY (primary curriculum)');

        return $query->getResultArray();
    }


    // ===============================================================
    // Teacher-Curriculum Many-to-Many Relationship Methods
    // ===============================================================

    /**
     * Get all curriculums assigned to a teacher
     *
     * @param int $teacherUid Teacher's UID
     * @return array Array of curriculum assignments with details
     */
    public function getTeacherCurriculums($teacherUid)
    {
        $builder = $this->db->table('teacher_curriculum_view');
        return $builder->where('teacher_uid', $teacherUid)
            ->where('assignment_status', 1)
            ->orderBy('is_primary', 'DESC')
            ->orderBy('curriculum_name', 'ASC')
            ->get()
            ->getResultArray();
    }

    /**
     * Get primary curriculum for a teacher
     *
     * @param int $teacherUid Teacher's UID
     * @return array|null Primary curriculum assignment or null
     */
    public function getTeacherPrimaryCurriculum($teacherUid)
    {
        $builder = $this->db->table('teacher_curriculum_view');
        return $builder->where('teacher_uid', $teacherUid)
            ->where('is_primary', 1)
            ->where('assignment_status', 1)
            ->get()
            ->getRowArray();
    }

    /**
     * Assign a teacher to a curriculum
     *
     * @param int $teacherUid Teacher's UID
     * @param int $curriculumId Curriculum ID
     * @param string $role Role of teacher (instructor, coordinator, assistant)
     * @param bool $isPrimary Whether this is the primary curriculum
     * @return bool Success status
     */
    public function assignTeacherToCurriculum($teacherUid, $curriculumId, $role = 'instructor', $isPrimary = false)
    {
        $builder = $this->db->table('teacher_curriculum');

        // Check if assignment already exists
        $existing = $builder->where('teacher_uid', $teacherUid)
            ->where('curriculum_id', $curriculumId)
            ->get()
            ->getRowArray();

        if ($existing) {
            // Update existing assignment
            return $builder->where('teacher_uid', $teacherUid)
                ->where('curriculum_id', $curriculumId)
                ->update([
                    'role' => $role,
                    'is_primary' => $isPrimary ? 1 : 0,
                    'status' => 1
                ]);
        }

        // If setting as primary, unset other primary assignments
        if ($isPrimary) {
            $builder->where('teacher_uid', $teacherUid)
                ->where('is_primary', 1)
                ->update(['is_primary' => 0]);
        }

        // Insert new assignment
        return $builder->insert([
            'teacher_uid' => $teacherUid,
            'curriculum_id' => $curriculumId,
            'role' => $role,
            'is_primary' => $isPrimary ? 1 : 0,
            'status' => 1
        ]);
    }

    /**
     * Remove a teacher from a curriculum
     *
     * @param int $teacherUid Teacher's UID
     * @param int $curriculumId Curriculum ID
     * @return bool Success status
     */
    public function removeTeacherFromCurriculum($teacherUid, $curriculumId)
    {
        $builder = $this->db->table('teacher_curriculum');
        return $builder->where('teacher_uid', $teacherUid)
            ->where('curriculum_id', $curriculumId)
            ->delete();
    }

    /**
     * Set a curriculum as primary for a teacher
     *
     * @param int $teacherUid Teacher's UID
     * @param int $curriculumId Curriculum ID to set as primary
     * @return bool Success status
     */
    public function setTeacherPrimaryCurriculum($teacherUid, $curriculumId)
    {
        $builder = $this->db->table('teacher_curriculum');

        // Unset all primary flags for this teacher
        $builder->where('teacher_uid', $teacherUid)
            ->update(['is_primary' => 0]);

        // Set new primary
        return $builder->where('teacher_uid', $teacherUid)
            ->where('curriculum_id', $curriculumId)
            ->update(['is_primary' => 1]);
    }

    /**
     * Get all teachers assigned to a curriculum
     *
     * @param int $curriculumId Curriculum ID
     * @return array Array of teacher assignments
     */
    public function getCurriculumTeachers($curriculumId)
    {
        $builder = $this->db->table('teacher_curriculum_view');
        return $builder->where('curriculum_id', $curriculumId)
            ->where('assignment_status', 1)
            ->orderBy('is_primary', 'DESC')
            ->orderBy('thai_name', 'ASC')
            ->get()
            ->getResultArray();
    }

    /**
     * อาจารย์ผู้รับผิดชอบหลักสูตร (สูงสุด 5 คน) จาก teacher_curriculum + ประธานหลักสูตร
     *
     * @return list<array<string,mixed>>
     */
    public function getCurriculumResponsibleTeachers(int $curriculumId, ?int $chairId = null, int $limit = 5): array
    {
        if ($limit < 1) {
            return [];
        }

        $rows = $this->db->table('teacher_curriculum tc')
            ->select('tc.role, tc.is_primary, u.uid, u.email, u.title, u.titleThai,
                      u.gf_name, u.gl_name, u.thai_name, u.thai_lastname, u.faculty_id')
            ->join('user u', 'u.uid = tc.teacher_uid', 'inner')
            ->where('tc.curriculum_id', $curriculumId)
            ->where('tc.status', 1)
            ->where('u.active', 1)
            ->where('u.user_type', 'TEACHER')
            ->get()
            ->getResultArray();

        $chairInList = false;
        if ($chairId !== null && $chairId > 0) {
            foreach ($rows as $row) {
                if ((int) ($row['uid'] ?? 0) === (int) $chairId) {
                    $chairInList = true;
                    break;
                }
            }

            if (! $chairInList) {
                $chair = $this->find($chairId);
                if (is_array($chair) && (int) ($chair['active'] ?? 0) === 1) {
                    array_unshift($rows, [
                        'uid'          => $chair['uid'],
                        'email'        => $chair['email'] ?? '',
                        'title'        => $chair['title'] ?? '',
                        'titleThai'    => $chair['titleThai'] ?? '',
                        'gf_name'      => $chair['gf_name'] ?? '',
                        'gl_name'      => $chair['gl_name'] ?? '',
                        'thai_name'    => $chair['thai_name'] ?? '',
                        'thai_lastname'=> $chair['thai_lastname'] ?? '',
                        'faculty_id'   => $chair['faculty_id'] ?? null,
                        'role'         => 'chair',
                        'is_primary'   => 1,
                    ]);
                }
            }
        }

        $roleOrder = ['chair' => 0, 'coordinator' => 1, 'instructor' => 2, 'assistant' => 3];

        usort($rows, static function (array $a, array $b) use ($chairId, $roleOrder): int {
            $aIsChair = $chairId !== null && (int) ($a['uid'] ?? 0) === (int) $chairId;
            $bIsChair = $chairId !== null && (int) ($b['uid'] ?? 0) === (int) $chairId;
            if ($aIsChair !== $bIsChair) {
                return $aIsChair ? -1 : 1;
            }

            $aRole = $roleOrder[$a['role'] ?? 'instructor'] ?? 9;
            $bRole = $roleOrder[$b['role'] ?? 'instructor'] ?? 9;
            if ($aRole !== $bRole) {
                return $aRole <=> $bRole;
            }

            $aName = trim(($a['thai_name'] ?? '') . ' ' . ($a['thai_lastname'] ?? ''));
            $bName = trim(($b['thai_name'] ?? '') . ' ' . ($b['thai_lastname'] ?? ''));

            return strcmp($aName, $bName);
        });

        return array_slice($rows, 0, $limit);
    }

    /**
     * Get cross-faculty teaching assignments
     * Teachers teaching in curriculums outside their home faculty
     *
     * @return array Array of cross-faculty assignments
     */
    public function getCrossFacultyTeachingAssignments()
    {
        $builder = $this->db->table('teacher_curriculum_view');
        return $builder->where('is_cross_faculty', 1)
            ->where('assignment_status', 1)
            ->orderBy('teacher_faculty_name', 'ASC')
            ->orderBy('thai_name', 'ASC')
            ->get()
            ->getResultArray();
    }

    /**
     * Update getUsersWithCurriculum to show all teacher curriculums
     * This version shows teachers with their primary curriculum
     * and indicates if they teach in multiple curriculums
     */
    public function getUsersWithCurriculumEnhanced()
    {
        $builder = $this->db->table('user');

        $result = $builder->select([
            'user.*',
            'user.faculty_id as user_faculty_id',
            'tc.curriculum_id',
            'tc.is_primary',
            'tc.role as curriculum_role',
            'curriculum.name as curriculum_name',
            'curriculum.code as curriculum_code',
            'curriculum.faculty_id as curriculum_faculty_id',
            'cf.name as curriculum_faculty_name',
            'uf.name as user_faculty_name',
            'COUNT(DISTINCT tc2.curriculum_id) as total_curriculums',
            'GROUP_CONCAT(DISTINCT CASE WHEN tc2.is_primary = 0 THEN c2.name END SEPARATOR ", ") as other_curriculums'
        ])
            ->join('faculties uf', 'uf.id = user.faculty_id', 'left')
            ->join('teacher_curriculum tc', 'tc.teacher_uid = user.uid AND tc.is_primary = 1', 'left')
            ->join('teacher_curriculum tc2', 'tc2.teacher_uid = user.uid AND tc2.status = 1', 'left')
            ->join('curriculum', 'curriculum.id = tc.curriculum_id', 'left')
            ->join('curriculum c2', 'c2.id = tc2.curriculum_id', 'left')
            ->join('faculties cf', 'cf.id = curriculum.faculty_id', 'left')
            ->join('faculties uf', 'uf.id = user.faculty_id', 'left')
            ->where('user.active', 1)
            ->groupStart()
            ->where('user.user_type', 'TEACHER')
            ->orWhere('user.user_type IS NULL')
            ->groupEnd()
            ->groupBy('user.uid')
            ->orderBy('CASE WHEN user.faculty_id IS NULL THEN 0 ELSE 1 END', 'ASC', false)
            ->orderBy('user.faculty_id', 'ASC')
            ->orderBy('user.gf_name', 'ASC')
            ->get()
            ->getResultArray();

        return $result;
    }
}
