<?php

namespace App\Models;

use CodeIgniter\Model;

class PublicationModel extends Model
{
    protected $table = 'publications';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    protected $allowedFields = [
        'title',
        'abstract',
        'publication_type',
        'source',
        'publication_year',
        'publication_month',
        'volume',
        'pages',
        'doi',
        'isbn',
        'keywords',
        'notes',
        'ref_url',
        'created_by',
        'approve',
        'orcid_put_code'
    ];

    /**
     * Get all publications for a user
     */
    public function getUserPublications($userId)
    {
        return $this->where('created_by', $userId)
            ->orderBy('publication_year', 'DESC')
            ->orderBy('publication_month', 'DESC')
            ->orderBy('created_at', 'DESC')
            ->findAll();
    }

    /**
     * Get user publications with authors
     */
    public function getUserPublicationsWithAuthors($userId)
    {
        $builder = $this->db->table('publications p');
        $builder->select('p.*, GROUP_CONCAT(pa.author_name ORDER BY pa.author_order SEPARATOR ", ") as authors')
            ->join('publication_authors pa', 'p.id = pa.publication_id', 'left')
            ->where('p.created_by', $userId)
            ->groupBy('p.id')
            ->orderBy('p.publication_year', 'DESC')
            ->orderBy('p.publication_month', 'DESC')
            ->orderBy('p.created_at', 'DESC');

        return $builder->get()->getResultArray();
    }

    /**
     * Get publication with authors for editing
     */
    public function getPublicationWithAuthors($publicationId, $userId)
    {
        $publication = $this->where('id', $publicationId)
            ->where('created_by', $userId)
            ->first();

        if (!$publication) {
            return null;
        }

        // Get authors
        $authorsBuilder = $this->db->table('publication_authors');
        $authors = $authorsBuilder->where('publication_id', $publicationId)
            ->orderBy('author_order', 'ASC')
            ->get()
            ->getResultArray();

        $publication['authors'] = $authors;
        return $publication;
    }

    /**
     * Search publications
     */
    public function searchUserPublications($userId, $searchTerm = null, $typeFilter = null, $yearFilter = null)
    {
        $builder = $this->db->table('publications p');
        $builder->select('p.*, GROUP_CONCAT(pa.author_name ORDER BY pa.author_order SEPARATOR ", ") as authors')
            ->join('publication_authors pa', 'p.id = pa.publication_id', 'left')
            ->where('p.created_by', $userId);

        if ($searchTerm) {
            $builder->like('p.title', $searchTerm);
        }

        if ($typeFilter) {
            $builder->where('p.publication_type', $typeFilter);
        }

        if ($yearFilter) {
            $builder->where('p.publication_year', $yearFilter);
        }

        $builder->groupBy('p.id')
            ->orderBy('p.publication_year', 'DESC')
            ->orderBy('p.publication_month', 'DESC')
            ->orderBy('p.created_at', 'DESC');

        return $builder->get()->getResultArray();
    }

    /**
     * Get this year count
     */
    public function getThisYearCount($userId)
    {
        return $this->where('created_by', $userId)
            ->where('publication_year', date('Y'))
            ->countAllResults();
    }

    /**
     * Insert publication author
     */
    public function insertPublicationAuthor($data)
    {
        $builder = $this->db->table('publication_authors');
        return $builder->insert($data);
    }

    /**
     * Delete publication authors
     */
    public function deletePublicationAuthors($publicationId)
    {
        $builder = $this->db->table('publication_authors');
        return $builder->where('publication_id', $publicationId)->delete();
    }

    /**
     * Get all publications with authors for dashboard (admin only)
     */
    public function getAllPublicationsWithAuthors($limit = 20)
    {
        $builder = $this->db->table('publication_view');
        $builder->select('*')
            ->orderBy('publication_year', 'DESC')
            ->orderBy('publication_month', 'DESC')
            ->orderBy('created_at', 'DESC')
            ->limit($limit);

        // Debug: Log the SQL query
        $sql = $builder->getCompiledSelect(false);
        log_message('debug', 'getAllPublicationsWithAuthors SQL: ' . $sql);

        $result = $builder->get()->getResultArray();

        // Debug: Log the result count
        log_message('debug', 'getAllPublicationsWithAuthors returned ' . count($result) . ' records');

        return $result;
    }

    /**
     * Get publications by user ID with authors
     * Used for regular users to see only their own publications
     */
    public function getPublicationsByUser($userId, $limit = 1000)
    {
        $builder = $this->db->table('publication_view');
        $builder->select('*')
            ->where('created_by', $userId)
            ->orderBy('publication_year', 'DESC')
            ->orderBy('publication_month', 'DESC')
            ->orderBy('created_at', 'DESC')
            ->limit($limit);

        return $builder->get()->getResultArray();
    }

    /**
     * Get publications where user is an author
     * Filters by publication_authors table using author_email as PRIMARY condition
     * Used for "My Publications" page to show publications where user is listed as author
     * Query directly from publication_authors table, not from publication_view
     * 
     * Priority: author_email from publication_authors is PRIMARY
     * Match with all user emails (user.email + authors.email where user_uid = userId)
     * Since authors can have multiple emails, we match author_email with all user emails
     * 
     * ALSO includes publications where user is the creator (created_by = userId)
     */
    public function getPublicationsByAuthor($userId, $limit = 1000)
    {
        // Step 1: Get ALL emails associated with this user
        // - Primary email from user table
        // - All emails from authors table where user_uid = userId (user can have multiple author emails)
        $user = $this->db->table('user')
            ->select('email, uid')
            ->where('uid', $userId)
            ->get()
            ->getRowArray();

        $userEmails = [];
        if (!empty($user['email'])) {
            $userEmails[] = $user['email'];
        }

        // Get all emails from authors table for this user
        $authorEmails = $this->db->table('authors')
            ->select('email')
            ->where('user_uid', $userId)
            ->where('email IS NOT NULL')
            ->where('email !=', '')
            ->get()
            ->getResultArray();

        foreach ($authorEmails as $authorEmail) {
            if (!empty($authorEmail['email']) && !in_array($authorEmail['email'], $userEmails)) {
                $userEmails[] = $authorEmail['email'];
            }
        }

        // Step 2: Get distinct publication IDs where user is an author
        // PRIMARY: Match author_email from publication_authors with ALL user emails
        // SECONDARY: Match by UID (pa.uid or authors.user_uid) as fallback
        $builder = $this->db->table('publication_authors pa');
        $builder->select('pa.publication_id')
            ->distinct()
            ->join('authors a', 'pa.author_id = a.id', 'left')
            ->join('user u', 'pa.uid = u.uid', 'left');
        
        // PRIMARY condition: Match author_email with ALL user emails
        if (!empty($userEmails)) {
            $builder->groupStart()
                ->whereIn('pa.author_email', $userEmails)  // PRIMARY: author_email from publication_authors
                ->orWhereIn('a.email', $userEmails)        // Also check authors.email
                ->orWhereIn('u.email', $userEmails)         // Also check user.email from joined user
                // SECONDARY: Also include UID match as fallback
                ->orWhere('pa.uid', $userId)
                ->orWhere('a.user_uid', $userId)
            ->groupEnd();
        } else {
            // Fallback: If no emails found, search by UID only
            $builder->groupStart()
                ->where('pa.uid', $userId)
                ->orWhere('a.user_uid', $userId)
            ->groupEnd();
        }
        
        $publicationIds = $builder->get()->getResultArray();

        $ids = array_column($publicationIds, 'publication_id');

        // Step 2b: ALSO get publications where user is the creator (created_by = userId)
        // This ensures we include publications that don't have publication_authors records yet
        $createdByIds = $this->db->table('publications')
            ->select('id')
            ->where('created_by', $userId)
            ->get()
            ->getResultArray();

        $createdIds = array_column($createdByIds, 'id');

        // Merge both arrays and get unique IDs
        $allIds = array_unique(array_merge($ids, $createdIds));

        if (empty($allIds)) {
            return [];
        }

        // Step 3: Get publications from publications table directly (not publication_view)
        // and join with publication_authors to get author information
        $builder = $this->db->table('publications p');
        $builder->select('
                p.*,
                -- Creator information
                CONCAT(u.gf_name, " ", u.gl_name) as created_by_name,
                -- Author names (English)
                GROUP_CONCAT(
                    CASE
                        WHEN pa2.author_id IS NOT NULL AND a2.user_uid IS NOT NULL
                        THEN CONCAT(uu2.gf_name, " ", uu2.gl_name)
                        ELSE pa2.author_name
                    END
                    ORDER BY pa2.author_order
                    SEPARATOR ", "
                ) as authors_names_en,
                -- Author names (Thai)
                GROUP_CONCAT(
                    CASE
                        WHEN pa2.author_id IS NOT NULL AND a2.user_uid IS NOT NULL
                        THEN CONCAT(uu2.thai_name, " ", uu2.thai_lastname)
                        ELSE pa2.author_name
                    END
                    ORDER BY pa2.author_order
                    SEPARATOR ", "
                ) as authors_names_thai,
                -- Combined authors field (for backward compatibility)
                GROUP_CONCAT(
                    CASE
                        WHEN pa2.author_id IS NOT NULL AND a2.user_uid IS NOT NULL
                        THEN CONCAT(uu2.thai_name, " ", uu2.thai_lastname)
                        ELSE pa2.author_name
                    END
                    ORDER BY pa2.author_order
                    SEPARATOR ", "
                ) as authors
            ')
            ->join('user u', 'p.created_by = u.uid', 'left')
            ->join('publication_authors pa2', 'p.id = pa2.publication_id', 'left')
            ->join('authors a2', 'pa2.author_id = a2.id', 'left')
            ->join('user uu2', 'a2.user_uid = uu2.uid', 'left')
            ->whereIn('p.id', $allIds)
            ->groupBy('p.id')
            ->orderBy('p.publication_year', 'DESC')
            ->orderBy('p.publication_month', 'DESC')
            ->orderBy('p.created_at', 'DESC')
            ->limit($limit);

        return $builder->get()->getResultArray();
    }

    /**
     * Get publications by faculties
     * Used for faculty admins to see publications from their managed faculties
     * 
     * Returns publications from:
     * 1. Teachers IN the faculty (user.faculty_id IN facultyIds)
     * 2. Teachers NOT in the faculty but assigned to curriculums that belong to the faculty
     *    (via teacher_curriculum where curriculum.faculty_id IN facultyIds)
     * 
     * Uses author_email as PRIMARY condition (same as getPublicationsByAuthor)
     * Removes duplicates by publication ID
     */
    public function getPublicationsByFaculties($facultyIds, $limit = 1000)
    {
        if (empty($facultyIds)) {
            return [];
        }

        $userIds = [];
        $userEmails = [];

        // PART 1: Get teachers IN the faculty (user.faculty_id IN facultyIds)
        $userModel = new \App\Models\UserModel();
        $teachersInFaculty = $userModel->whereIn('faculty_id', $facultyIds)->findAll();
        
        foreach ($teachersInFaculty as $teacher) {
            $userIds[] = $teacher['uid'];
            if (!empty($teacher['email'])) {
                $userEmails[] = $teacher['email'];
            }
        }

        // PART 2: Get teachers NOT in the faculty but assigned to curriculums of the faculty
        // Get curricula from these faculties
        $curriculumModel = new \App\Models\CurriculumModel();
        $curricula = $curriculumModel->whereIn('faculty_id', $facultyIds)->findAll();
        
        if (!empty($curricula)) {
            $curriculumIds = array_column($curricula, 'id');
            
            // Get teachers assigned to these curricula via teacher_curriculum table
            // Exclude teachers who are already in the faculty (to avoid duplicates)
            $teachersInCurricula = $this->db->table('teacher_curriculum tc')
                ->select('tc.teacher_uid, u.email')
                ->join('user u', 'tc.teacher_uid = u.uid', 'left')
                ->whereIn('tc.curriculum_id', $curriculumIds)
                ->where('tc.status', 1)
                ->whereNotIn('u.faculty_id', $facultyIds) // Exclude teachers already in faculty
                ->get()
                ->getResultArray();
            
            foreach ($teachersInCurricula as $teacher) {
                $uid = $teacher['teacher_uid'];
                if (!in_array($uid, $userIds)) {
                    $userIds[] = $uid;
                    if (!empty($teacher['email']) && !in_array($teacher['email'], $userEmails)) {
                        $userEmails[] = $teacher['email'];
                    }
                }
            }
        }

        if (empty($userIds)) {
            return [];
        }

        // Step 1: Get ALL emails from ALL users (from both parts)
        // Get all emails from authors table for these users
        $authorEmails = $this->db->table('authors')
            ->select('email')
            ->whereIn('user_uid', $userIds)
            ->where('email IS NOT NULL')
            ->where('email !=', '')
            ->get()
            ->getResultArray();
        
        foreach ($authorEmails as $authorEmail) {
            if (!empty($authorEmail['email']) && !in_array($authorEmail['email'], $userEmails)) {
                $userEmails[] = $authorEmail['email'];
            }
        }
        
        if (empty($userEmails) && empty($userIds)) {
            return [];
        }

        // Step 2: Get distinct publication IDs where users are authors (using author_email as PRIMARY)
        $builder = $this->db->table('publication_authors pa');
        $builder->select('pa.publication_id')
            ->distinct()
            ->join('authors a', 'pa.author_id = a.id', 'left')
            ->join('user u', 'pa.uid = u.uid', 'left');
        
        if (!empty($userEmails)) {
            $builder->groupStart()
                ->whereIn('pa.author_email', $userEmails)  // PRIMARY: author_email from publication_authors
                ->orWhereIn('a.email', $userEmails)         // Also check authors.email
                ->orWhereIn('u.email', $userEmails)          // Also check user.email from joined user
                // SECONDARY: Also include UID match as fallback
                ->orWhereIn('pa.uid', $userIds)
                ->orWhereIn('a.user_uid', $userIds)
            ->groupEnd();
        } else {
            // Fallback: If no emails found, search by UID only
            $builder->groupStart()
                ->whereIn('pa.uid', $userIds)
                ->orWhereIn('a.user_uid', $userIds)
            ->groupEnd();
        }
        
        $publicationIds = $builder->get()->getResultArray();

        if (empty($publicationIds)) {
            return [];
        }

        $ids = array_column($publicationIds, 'publication_id');
        
        // Remove duplicates by publication ID (in case same publication appears multiple times)
        $ids = array_unique($ids);

        // Step 3: Get publications from publications table directly (same structure as getPublicationsByAuthor)
        $builder = $this->db->table('publications p');
        $builder->select('
                p.*,
                CONCAT(u.gf_name, " ", u.gl_name) as created_by_name,
                GROUP_CONCAT(
                    CASE
                        WHEN pa2.author_id IS NOT NULL AND a2.user_uid IS NOT NULL
                        THEN CONCAT(uu2.gf_name, " ", uu2.gl_name)
                        ELSE pa2.author_name
                    END
                    ORDER BY pa2.author_order
                    SEPARATOR ", "
                ) as authors_names_en,
                GROUP_CONCAT(
                    CASE
                        WHEN pa2.author_id IS NOT NULL AND a2.user_uid IS NOT NULL
                        THEN CONCAT(uu2.thai_name, " ", uu2.thai_lastname)
                        ELSE pa2.author_name
                    END
                    ORDER BY pa2.author_order
                    SEPARATOR ", "
                ) as authors_names_thai,
                GROUP_CONCAT(
                    CASE
                        WHEN pa2.author_id IS NOT NULL AND a2.user_uid IS NOT NULL
                        THEN CONCAT(uu2.thai_name, " ", uu2.thai_lastname)
                        ELSE pa2.author_name
                    END
                    ORDER BY pa2.author_order
                    SEPARATOR ", "
                ) as authors
            ')
            ->join('user u', 'p.created_by = u.uid', 'left')
            ->join('publication_authors pa2', 'p.id = pa2.publication_id', 'left')
            ->join('authors a2', 'pa2.author_id = a2.id', 'left')
            ->join('user uu2', 'a2.user_uid = uu2.uid', 'left')
            ->whereIn('p.id', $ids)
            ->groupBy('p.id')
            ->orderBy('p.publication_year', 'DESC')
            ->orderBy('p.publication_month', 'DESC')
            ->orderBy('p.created_at', 'DESC')
            ->limit($limit);

        return $builder->get()->getResultArray();
    }
    /**
     * Get publications strictly by author email
     * Matches author_email in publication_authors table
     */
    public function getPublicationsByEmail($email, $limit = 1000)
    {
        if (empty($email)) {
            return [];
        }

        // Step 1: Get distinct publication IDs where author_email matches
        $builder = $this->db->table('publication_authors');
        $builder->select('publication_id')
            ->distinct()
            ->where('author_email', $email);
        
        $publicationIds = $builder->get()->getResultArray();
        $ids = array_column($publicationIds, 'publication_id');

        if (empty($ids)) {
            return [];
        }

        // Step 2: Get full publication details
        $builder = $this->db->table('publications p');
        $builder->select('
                p.*,
                CONCAT(u.gf_name, " ", u.gl_name) as created_by_name,
                GROUP_CONCAT(
                    CASE
                        WHEN pa2.author_id IS NOT NULL AND a2.user_uid IS NOT NULL
                        THEN CONCAT(uu2.gf_name, " ", uu2.gl_name)
                        ELSE pa2.author_name
                    END
                    ORDER BY pa2.author_order
                    SEPARATOR ", "
                ) as authors_names_en,
                GROUP_CONCAT(
                    CASE
                        WHEN pa2.author_id IS NOT NULL AND a2.user_uid IS NOT NULL
                        THEN CONCAT(uu2.thai_name, " ", uu2.thai_lastname)
                        ELSE pa2.author_name
                    END
                    ORDER BY pa2.author_order
                    SEPARATOR ", "
                ) as authors_names_thai,
                GROUP_CONCAT(
                    CASE
                        WHEN pa2.author_id IS NOT NULL AND a2.user_uid IS NOT NULL
                        THEN CONCAT(uu2.thai_name, " ", uu2.thai_lastname)
                        ELSE pa2.author_name
                    END
                    ORDER BY pa2.author_order
                    SEPARATOR ", "
                ) as authors
            ')
            ->join('user u', 'p.created_by = u.uid', 'left')
            ->join('publication_authors pa2', 'p.id = pa2.publication_id', 'left')
            ->join('authors a2', 'pa2.author_id = a2.id', 'left')
            ->join('user uu2', 'a2.user_uid = uu2.uid', 'left')
            ->whereIn('p.id', $ids)
            ->groupBy('p.id')
            ->orderBy('p.publication_year', 'DESC')
            ->orderBy('p.publication_month', 'DESC')
            ->orderBy('p.created_at', 'DESC')
            ->limit($limit);

        return $builder->get()->getResultArray();
    }
}
