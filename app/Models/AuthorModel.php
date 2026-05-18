<?php

namespace App\Models;

use CodeIgniter\Model;

class AuthorModel extends Model
{
    protected $table = 'authors';
    protected $primaryKey = 'id';
    protected $returnType = 'array';

    protected $allowedFields = [
        'email',
        'user_uid',
        'created_by'
    ];

    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = '';

    /**
     * Get author by email
     */
    public function getAuthorByEmail($email)
    {
        return $this->where('email', $email)->first();
    }


    public function getAuthorlinkUser($email)
    {
        if (empty($email)) {
            return null;
        }

        $builder = $this->db->table('authors a');

        $result = $builder
            ->select('
            a.id as author_id,
            a.email as author_email,
            a.user_uid,
            u.uid as user_id,
            u.thai_name as thai_name,
            u.thai_lastname as thai_lastname,
            u.email as user_email,
            u.major
        ')
            ->join('user u', 'a.user_uid = u.uid', 'left')
            ->where('a.email', $email)
            ->orWhere('u.email', $email)
            ->get()
            ->getRowArray();

        // ADD THIS LINE TO SEE THE QUERY
        log_message('debug', 'SQL: ' . $this->db->getLastQuery());

        if ($result) {
            return [
                'id' => $result['author_id'],              // ← ADD THIS LINE
                'author_id' => $result['author_id'],       // Keep existing
                'user_id' => $result['user_id'],
                'name' => trim($result['thai_name'] . ' ' . $result['thai_lastname']),
                'email' => $email,
                'affiliation' => $result['major'] ?? '',
                'user_uid' => $result['user_id'],          // Frontend expects this
                'is_linked' => !empty($result['user_uid'])
            ];
        }

        return null;
    }
    /**
     * Link author to user account
     */
    public function linkAuthorToUser($authorId, $userId)
    {
        return $this->update($authorId, ['user_uid' => $userId]);
    }

    /**
     * Get authors created by user
     */
    public function getAuthorsByUser($userId)
    {
        return $this->where('created_by', $userId)
            ->orderBy('name', 'ASC')
            ->findAll();
    }




    /**
     * Get unique authors for a user (from publication_authors table)
     */
    public function getUserUniqueAuthors($userId)
    {
        $builder = $this->db->table('publication_authors pa');
        $builder->select('pa.author_name, pa.author_email, MAX(pa.author_affiliation) as author_affiliation')
            ->join('publications p', 'pa.publication_id = p.id')
            ->where('p.created_by', $userId)
            ->groupBy('pa.author_email, pa.author_name')
            ->orderBy('pa.author_name', 'ASC');

        return $builder->get()->getResultArray();
    }

    /**
     * Get author count for user
     */
    public function getUserAuthorCount($userId)
    {
        $authors = $this->getUserUniqueAuthors($userId);
        return count($authors);
    }

    /**
     * Get authors with publication statistics
     */
    public function getUserAuthorsWithStats($userId)
    {
        $builder = $this->db->table('publication_authors pa');
        $builder->select('pa.author_name, pa.author_email, MAX(pa.author_affiliation) as author_affiliation, COUNT(pa.id) as publication_count')
            ->join('publications p', 'pa.publication_id = p.id')
            ->where('p.created_by', $userId)
            ->groupBy('pa.author_email, pa.author_name')
            ->orderBy('publication_count', 'DESC');

        return $builder->get()->getResultArray();
    }


    /**
     * ADD this method to your AuthorModel.php
     * 
     * Get all author emails for a specific user
     * Used for showing multiple emails in autocomplete
     */
    public function getAuthorEmailsByUser($userUid)
    {
        return $this->where('user_uid', $userUid)
            ->where('email IS NOT NULL')
            ->where('email !=', '')
            ->findColumn('email');
    }

    /**
     * OPTIONAL: Get author with user information
     * Useful for getting complete author + user data
     */
    public function getAuthorWithUser($email)
    {
        $builder = $this->db->table('authors a');
        return $builder
            ->select('a.*, u.gf_name, u.gl_name, u.major, u.title')
            ->join('user u', 'a.user_uid = u.uid', 'left')
            ->where('a.email', $email)
            ->get()
            ->getRowArray();
    }

    /**
     * OPTIONAL: Get all authors for a user with their info
     * Shows all email addresses for a user
     */
    public function getUserAuthorProfiles($userUid)
    {
        return $this->where('user_uid', $userUid)
            ->where('email IS NOT NULL')
            ->orderBy('created_at', 'DESC')
            ->findAll();
    }
}
