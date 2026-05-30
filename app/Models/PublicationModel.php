<?php

namespace App\Models;

use App\Libraries\UserIdentity;
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
        'orcid_put_code',
        'sync_external_key',
        'ns_publication_id',
        'sync_origin',
        'last_synced_from',
        'content_hash',
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
     * Get publications where a user is an author, using email as the canonical
     * match while preserving uid fallback for older rows.
     */
    public function getPublicationsByAuthor($userId, $limit = 1000)
    {
        $user = $this->db->table('user')
            ->select('email, uid')
            ->where('uid', $userId)
            ->get()
            ->getRowArray();

        if (!empty($user['email'])) {
            return $this->getPublicationsByCanonicalEmail((string) $user['email'], $limit);
        }

        return $this->getPublicationsForIdentity([], (int) $userId, $limit);
    }

    /**
     * Get publications for the canonical normalized email identity.
     *
     * @return list<array<string,mixed>>
     */
    public function getPublicationsByCanonicalEmail(string $email, int $limit = 1000): array
    {
        $email = UserIdentity::normalizeEmail($email);
        if ($email === '') {
            return [];
        }

        $user = UserIdentity::resolveUserByEmail($email);
        $userId = $user !== null ? (int) ($user['uid'] ?? 0) : 0;
        $emails = $this->emailsForIdentity($email, $userId);

        return $this->getPublicationsForIdentity($emails, $userId, $limit);
    }

    /**
     * Approved publications only (approve = 1) for a canonical email identity.
     *
     * @return list<array<string,mixed>>
     */
    public function getApprovedPublicationsByCanonicalEmail(string $email, int $limit = 1000): array
    {
        $email = UserIdentity::normalizeEmail($email);
        if ($email === '') {
            return [];
        }

        $user   = UserIdentity::resolveUserByEmail($email);
        $userId = $user !== null ? (int) ($user['uid'] ?? 0) : 0;
        $emails = $this->emailsForIdentity($email, $userId);
        $ids    = $this->publicationIdsForIdentity($emails, $userId);

        if ($ids === []) {
            return [];
        }

        return $this->publicationRowsByIds($ids, $limit, true);
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
        $email = UserIdentity::normalizeEmail((string) $email);
        if ($email === '') {
            return [];
        }

        return $this->getPublicationsByCanonicalEmail($email, (int) $limit);
    }

    /**
     * @param list<string> $emails
     *
     * @return list<array<string,mixed>>
     */
    private function getPublicationsForIdentity(array $emails, int $userId, int $limit): array
    {
        $emails = $this->normalizeEmailList($emails);
        $ids = $this->publicationIdsForIdentity($emails, $userId);
        if ($ids === []) {
            return [];
        }

        return $this->publicationRowsByIds($ids, $limit, false);
    }

    /**
     * @return list<string>
     */
    private function emailsForIdentity(string $canonicalEmail, int $userId): array
    {
        $emails = [$canonicalEmail];

        if ($userId > 0) {
            $authorEmails = $this->db->table('authors')
                ->select('email')
                ->where('user_uid', $userId)
                ->where('email IS NOT NULL')
                ->where('email !=', '')
                ->get()
                ->getResultArray();

            foreach ($authorEmails as $authorEmail) {
                $emails[] = (string) ($authorEmail['email'] ?? '');
            }
        }

        return $this->normalizeEmailList($emails);
    }

    /**
     * @param list<string> $emails
     *
     * @return list<string>
     */
    private function normalizeEmailList(array $emails): array
    {
        $out = [];
        foreach ($emails as $email) {
            $email = UserIdentity::normalizeEmail((string) $email);
            if ($email !== '' && ! in_array($email, $out, true)) {
                $out[] = $email;
            }
        }

        return $out;
    }

    /**
     * @param list<string> $emails
     *
     * @return list<int>
     */
    private function publicationIdsForIdentity(array $emails, int $userId): array
    {
        $builder = $this->db->table('publication_authors pa');
        $builder->select('pa.publication_id')
            ->distinct()
            ->join('authors a', 'pa.author_id = a.id', 'left')
            ->join('user u', 'pa.uid = u.uid', 'left');

        if ($emails !== []) {
            $builder->groupStart()
                ->whereIn('pa.author_email', $emails)
                ->orWhereIn('a.email', $emails)
                ->orWhereIn('u.email', $emails);

            if ($userId > 0) {
                $builder->orWhere('pa.uid', $userId)
                    ->orWhere('a.user_uid', $userId);
            }

            $builder->groupEnd();
        } elseif ($userId > 0) {
            $builder->groupStart()
                ->where('pa.uid', $userId)
                ->orWhere('a.user_uid', $userId)
                ->groupEnd();
        } else {
            return [];
        }

        $publicationIds = $builder->get()->getResultArray();
        $ids = array_column($publicationIds, 'publication_id');

        // Intentionally exclude publications.created_by: data entry must not list as someone's
        // research output unless they appear in publication_authors (or linked authors/user).

        return array_values(array_unique(array_map('intval', $ids)));
    }

    /**
     * @param list<int> $ids
     *
     * @return list<array<string,mixed>>
     */
    private function publicationRowsByIds(array $ids, int $limit, bool $approvedOnly = false): array
    {
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
            ->groupBy('p.id');

        if ($approvedOnly) {
            $builder->where('p.approve', 1);
        }

        $builder->orderBy('p.publication_year', 'DESC')
            ->orderBy('p.publication_month', 'DESC')
            ->orderBy('p.created_at', 'DESC')
            ->limit($limit);

        return $builder->get()->getResultArray();
    }
}
