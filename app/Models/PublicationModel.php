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
        'created_by_email',
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
    public function getUserPublications($userEmail)
    {
        $userEmail = UserIdentity::normalizeEmail((string) $userEmail);

        return $this->where('created_by_email', $userEmail)
            ->orderBy('publication_year', 'DESC')
            ->orderBy('publication_month', 'DESC')
            ->orderBy('created_at', 'DESC')
            ->findAll();
    }

    /**
     * Get user publications with authors
     */
    public function getUserPublicationsWithAuthors($userEmail)
    {
        $userEmail = UserIdentity::normalizeEmail((string) $userEmail);
        $builder = $this->db->table('publications p');
        $builder->select('p.*, GROUP_CONCAT(pa.author_name ORDER BY pa.author_order SEPARATOR ", ") as authors')
            ->join('publication_authors pa', 'p.id = pa.publication_id', 'left')
            ->where('p.created_by_email', $userEmail)
            ->groupBy('p.id')
            ->orderBy('p.publication_year', 'DESC')
            ->orderBy('p.publication_month', 'DESC')
            ->orderBy('p.created_at', 'DESC');

        return $builder->get()->getResultArray();
    }

    /**
     * Get publication with authors for editing
     */
    public function getPublicationWithAuthors($publicationId, $userEmail)
    {
        $userEmail = UserIdentity::normalizeEmail((string) $userEmail);
        $publication = $this->where('id', $publicationId)
            ->where('created_by_email', $userEmail)
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
    public function searchUserPublications($userEmail, $searchTerm = null, $typeFilter = null, $yearFilter = null)
    {
        $userEmail = UserIdentity::normalizeEmail((string) $userEmail);
        $builder = $this->db->table('publications p');
        $builder->select('p.*, GROUP_CONCAT(pa.author_name ORDER BY pa.author_order SEPARATOR ", ") as authors')
            ->join('publication_authors pa', 'p.id = pa.publication_id', 'left')
            ->where('p.created_by_email', $userEmail);

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
    public function getThisYearCount($userEmail)
    {
        $userEmail = UserIdentity::normalizeEmail((string) $userEmail);

        return $this->where('created_by_email', $userEmail)
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
    public function getPublicationsByUser($userEmail, $limit = 1000)
    {
        $userEmail = UserIdentity::normalizeEmail((string) $userEmail);
        $builder = $this->db->table('publication_view');
        $builder->select('*')
            ->where('created_by_email', $userEmail)
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
    public function getPublicationsByAuthor($userKey, $limit = 1000)
    {
        if (is_string($userKey) && str_contains($userKey, '@')) {
            return $this->getPublicationsByCanonicalEmail($userKey, $limit);
        }

        $user = UserIdentity::resolveUserByEmail((string) $userKey);
        if ($user !== null && ! empty($user['email'])) {
            return $this->getPublicationsByCanonicalEmail((string) $user['email'], $limit);
        }

        return [];
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
        $emails = $this->emailsForIdentity($email, $user);

        return $this->getPublicationsForIdentity($emails, $limit);
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
        $emails = $this->emailsForIdentity($email, $user);
        $ids    = $this->publicationIdsForIdentity($emails);

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

        $userEmails = [];
        $userModel  = new UserModel();

        foreach ($userModel->whereIn('faculty_id', $facultyIds)->findAll() as $teacher) {
            $email = UserIdentity::normalizeEmail((string) ($teacher['email'] ?? ''));
            if ($email !== '') {
                $userEmails[] = $email;
            }
        }

        $curriculumModel = new CurriculumModel();
        $curricula       = $curriculumModel->whereIn('faculty_id', $facultyIds)->findAll();

        if (! empty($curricula)) {
            $curriculumIds = array_column($curricula, 'id');
            $teachersInCurricula = $this->db->table('teacher_curriculum tc')
                ->select('u.email')
                ->join('user u', 'tc.teacher_email = u.email', 'inner')
                ->whereIn('tc.curriculum_id', $curriculumIds)
                ->where('tc.status', 1)
                ->whereNotIn('u.faculty_id', $facultyIds)
                ->get()
                ->getResultArray();

            foreach ($teachersInCurricula as $teacher) {
                $email = UserIdentity::normalizeEmail((string) ($teacher['email'] ?? ''));
                if ($email !== '') {
                    $userEmails[] = $email;
                }
            }
        }

        $userEmails = $this->normalizeEmailList($userEmails);
        if ($userEmails === []) {
            return [];
        }

        foreach ($userEmails as $email) {
            $authorEmails = $this->db->table('authors')
                ->select('email')
                ->where('user_email', $email)
                ->where('email IS NOT NULL')
                ->where('email !=', '')
                ->get()
                ->getResultArray();

            foreach ($authorEmails as $row) {
                $userEmails[] = (string) ($row['email'] ?? '');
            }
        }

        $userEmails = $this->normalizeEmailList($userEmails);
        if ($userEmails === []) {
            return [];
        }

        $ids = $this->publicationIdsForIdentity($userEmails);

        return $ids === [] ? [] : $this->publicationRowsByIds($ids, (int) $limit, false);
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
    private function getPublicationsForIdentity(array $emails, int $limit): array
    {
        $emails = $this->normalizeEmailList($emails);
        $ids    = $this->publicationIdsForIdentity($emails);
        if ($ids === []) {
            return [];
        }

        return $this->publicationRowsByIds($ids, $limit, false);
    }

    /**
     * @param array<string,mixed>|null $user
     *
     * @return list<string>
     */
    private function emailsForIdentity(string $canonicalEmail, ?array $user): array
    {
        $emails = [$canonicalEmail];

        if ($user !== null && ! empty($user['email'])) {
            $authorEmails = $this->db->table('authors')
                ->select('email')
                ->where('user_email', $user['email'])
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
     * Public identity-email expansion for permission checks (same set used when
     * matching a person's publications by canonical email).
     *
     * @return list<string>
     */
    public function identityEmailsFor(string $email): array
    {
        $email = UserIdentity::normalizeEmail($email);
        if ($email === '') {
            return [];
        }

        $user = UserIdentity::resolveUserByEmail($email);

        return $this->emailsForIdentity($email, $user);
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
    private function publicationIdsForIdentity(array $emails): array
    {
        $emails = $this->normalizeEmailList($emails);
        if ($emails === []) {
            return [];
        }

        $builder = $this->db->table('publication_authors pa');
        $builder->select('pa.publication_id')
            ->distinct()
            ->join('authors a', 'pa.author_id = a.id', 'left')
            ->join('user u', 'a.user_email = u.email', 'left')
            ->groupStart()
            ->whereIn('pa.author_email', $emails)
            ->orWhereIn('a.email', $emails)
            ->orWhereIn('u.email', $emails)
            ->groupEnd();

        $publicationIds = $builder->get()->getResultArray();
        $ids            = array_column($publicationIds, 'publication_id');

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
                        WHEN pa2.author_id IS NOT NULL AND a2.user_email IS NOT NULL
                        THEN CONCAT(uu2.gf_name, " ", uu2.gl_name)
                        ELSE pa2.author_name
                    END
                    ORDER BY pa2.author_order
                    SEPARATOR ", "
                ) as authors_names_en,
                GROUP_CONCAT(
                    CASE
                        WHEN pa2.author_id IS NOT NULL AND a2.user_email IS NOT NULL
                        THEN CONCAT(uu2.thai_name, " ", uu2.thai_lastname)
                        ELSE pa2.author_name
                    END
                    ORDER BY pa2.author_order
                    SEPARATOR ", "
                ) as authors_names_thai,
                GROUP_CONCAT(
                    CASE
                        WHEN pa2.author_id IS NOT NULL AND a2.user_email IS NOT NULL
                        THEN CONCAT(uu2.thai_name, " ", uu2.thai_lastname)
                        ELSE pa2.author_name
                    END
                    ORDER BY pa2.author_order
                    SEPARATOR ", "
                ) as authors
            ')
            ->join('user u', 'p.created_by_email = u.email', 'left')
            ->join('publication_authors pa2', 'p.id = pa2.publication_id', 'left')
            ->join('authors a2', 'pa2.author_id = a2.id', 'left')
            ->join('user uu2', 'a2.user_email = uu2.email', 'left')
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
