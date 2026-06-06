<?php

namespace App\Models;

use App\Libraries\UserIdentity;
use CodeIgniter\Model;

class AuthorModel extends Model
{
    protected $table      = 'authors';
    protected $primaryKey = 'id';
    protected $returnType = 'array';

    protected $allowedFields = [
        'email',
        'user_email',
        'created_by_email',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = '';

    public function getAuthorByEmail($email)
    {
        $email = UserIdentity::normalizeEmail($email);

        return $email === '' ? null : $this->where('email', $email)->first();
    }

    public function getAuthorlinkUser($email)
    {
        $email = UserIdentity::normalizeEmail($email);
        if ($email === '') {
            return null;
        }

        $result = $this->db->table('authors a')
            ->select('a.id AS author_id, a.email AS author_email,
                COALESCE(u.email, a.user_email) AS user_email,
                u.thai_name, u.thai_lastname, u.gf_name, u.gl_name, u.major')
            ->join('user u', 'a.user_email = u.email', 'left')
            ->groupStart()
            ->where('a.email', $email)
            ->orWhere('u.email', $email)
            ->groupEnd()
            ->get()
            ->getRowArray();

        if (! $result) {
            return null;
        }

        // Prefer Thai name, fall back to English so the name is never blank
        $thaiName    = trim(($result['thai_name'] ?? '') . ' ' . ($result['thai_lastname'] ?? ''));
        $englishName = trim(($result['gf_name'] ?? '') . ' ' . ($result['gl_name'] ?? ''));

        return [
            'id'         => $result['author_id'],
            'author_id'  => $result['author_id'],
            'email'      => $email,
            'name'       => $thaiName !== '' ? $thaiName : $englishName,
            'affiliation'=> $result['major'] ?? '',
            'user_email' => $result['user_email'] ?? '',
            'is_linked'  => ! empty($result['user_email']),
        ];
    }

    public function linkAuthorToUser($authorId, $userEmail)
    {
        return $this->update($authorId, [
            'user_email' => UserIdentity::normalizeEmail((string) $userEmail),
        ]);
    }

    public function getAuthorsByUser(string $userEmail)
    {
        $userEmail = UserIdentity::normalizeEmail($userEmail);

        return $this->where('created_by_email', $userEmail)
            ->orderBy('email', 'ASC')
            ->findAll();
    }

    public function getUserUniqueAuthors(string $userEmail)
    {
        $userEmail = UserIdentity::normalizeEmail($userEmail);

        return $this->db->table('publication_authors pa')
            ->select('pa.author_name, pa.author_email, MAX(pa.author_affiliation) as author_affiliation')
            ->join('publications p', 'pa.publication_id = p.id')
            ->where('p.created_by_email', $userEmail)
            ->groupBy('pa.author_email, pa.author_name')
            ->orderBy('pa.author_name', 'ASC')
            ->get()
            ->getResultArray();
    }

    public function getUserAuthorCount(string $userEmail): int
    {
        return count($this->getUserUniqueAuthors($userEmail));
    }

    public function getUserAuthorsWithStats(string $userEmail)
    {
        $userEmail = UserIdentity::normalizeEmail($userEmail);

        return $this->db->table('publication_authors pa')
            ->select('pa.author_name, pa.author_email, MAX(pa.author_affiliation) as author_affiliation, COUNT(pa.id) as publication_count')
            ->join('publications p', 'pa.publication_id = p.id')
            ->where('p.created_by_email', $userEmail)
            ->groupBy('pa.author_email, pa.author_name')
            ->orderBy('publication_count', 'DESC')
            ->get()
            ->getResultArray();
    }

    /**
     * @return list<string>
     */
    public function getAuthorEmailsByUser(string $userEmail): array
    {
        $userEmail = UserIdentity::normalizeEmail($userEmail);
        if ($userEmail === '') {
            return [];
        }

        $cols = $this->where('user_email', $userEmail)
            ->where('email IS NOT NULL')
            ->where('email !=', '')
            ->findColumn('email');

        return is_array($cols) ? array_values($cols) : [];
    }

    public function getAuthorWithUser($email)
    {
        $email = UserIdentity::normalizeEmail($email);
        if ($email === '') {
            return null;
        }

        return $this->db->table('authors a')
            ->select('a.*, u.gf_name, u.gl_name, u.major, u.title')
            ->join('user u', 'a.user_email = u.email', 'left')
            ->where('a.email', $email)
            ->get()
            ->getRowArray();
    }

    public function getUserAuthorProfiles(string $userEmail)
    {
        return $this->where('user_email', UserIdentity::normalizeEmail($userEmail))
            ->where('email IS NOT NULL')
            ->orderBy('created_at', 'DESC')
            ->findAll();
    }
}
