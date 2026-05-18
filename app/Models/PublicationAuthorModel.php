<?php

namespace App\Models;

use CodeIgniter\Model;

class PublicationAuthorModel extends Model
{
    protected $table = 'publication_authors';
    protected $primaryKey = 'id';
    protected $returnType = 'array';

    protected $allowedFields = [
        'publication_id',
        'author_name',
        'author_email',
        'author_affiliation',
        'author_id',
        'uid',
        'author_order',
        'corresponding'
    ];

    protected $useTimestamps = false;

    /**
     * Get all authors for a specific publication
     */
    public function getPublicationAuthors($publicationId)
    {
        return $this->where('publication_id', $publicationId)
            ->orderBy('author_order', 'ASC')
            ->findAll();
    }

    /**
     * Add multiple authors to a publication
     */
    public function addAuthorsToPublication($publicationId, $authors)
    {
        $data = [];
        foreach ($authors as $index => $author) {
            $data[] = [
                'publication_id' => $publicationId,
                'author_name' => $author['name'],
                'author_email' => $author['email'] ?? null,
                'author_affiliation' => $author['affiliation'] ?? null,
                'author_id' => $author['author_id'] ?? null,
                'uid' => $author['uid'] ?? null,
                'author_order' => $index + 1,
                'corresponding' => $author['corresponding'] ?? 0
            ];
        }

        return $this->insertBatch($data);
    }

    /**
     * Remove all authors from a publication
     */
    public function removePublicationAuthors($publicationId)
    {
        return $this->where('publication_id', $publicationId)->delete();
    }
}
