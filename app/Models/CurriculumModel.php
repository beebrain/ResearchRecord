<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * CurriculumModel.php
 */
class CurriculumModel extends Model
{
    protected $table = 'curriculum';
    protected $primaryKey = 'id';
    protected $returnType = 'array';

    protected $allowedFields = [
        'faculty_id',
        'name',
        'code',
        'degree_level',
        'status',
        'chair_id'
    ];

    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = '';

    protected $validationRules = [
        'faculty_id' => 'required|integer',
        'name' => 'required|min_length[3]|max_length[255]',
        'code' => 'required|min_length[2]|max_length[20]',
        'degree_level' => 'required|in_list[bachelor,master,doctoral]',
        'status' => 'in_list[0,1]'
    ];

    /**
     * Get curriculum with faculty info
     */
    public function getWithFaculty()
    {
        return $this->select('curriculum.*, faculties.name as faculty_name, faculties.code as faculty_code')
            ->join('faculties', 'faculties.id = curriculum.faculty_id')
            ->where('curriculum.status', 1)
            ->where('faculties.status', 1)
            ->findAll();
    }

    /**
     * Get curriculum by faculty
     */
    public function getByFaculty($facultyId)
    {
        return $this->select('curriculum.*, faculties.name as faculty_name, faculties.code as faculty_code')
            ->join('faculties', 'faculties.id = curriculum.faculty_id')
            ->where('curriculum.faculty_id', $facultyId)
            ->where('curriculum.status', 1)
            ->findAll();
    }

    /**
     * Get curriculum by degree level
     */
    public function getByDegreeLevel($degreeLevel)
    {
        return $this->select('curriculum.*, faculties.name as faculty_name')
            ->join('faculties', 'faculties.id = curriculum.faculty_id')
            ->where('curriculum.degree_level', $degreeLevel)
            ->where('curriculum.status', 1)
            ->findAll();
    }

    /**
     * Get curriculum with user count
     */
    public function getWithUserCount()
    {
        return $this->select('curriculum.*, faculties.name as faculty_name, COUNT(user.uid) as user_count')
            ->join('faculties', 'faculties.id = curriculum.faculty_id')
            ->join('user', 'user.curriculum_id = curriculum.id', 'left')
            ->where('curriculum.status', 1)
            ->groupBy('curriculum.id')
            ->findAll();
    }

    /**
     * Get curriculum for dropdown
     */
    public function getForSelect($facultyId = null)
    {
        $builder = $this->select('curriculum.id, curriculum.name, curriculum.code, faculties.name as faculty_name')
            ->join('faculties', 'faculties.id = curriculum.faculty_id')
            ->where('curriculum.status', 1);

        if ($facultyId) {
            $builder->where('curriculum.faculty_id', $facultyId);
        }

        $results = $builder->findAll();

        $options = [];
        foreach ($results as $row) {
            $options[$row['id']] = $row['faculty_name'] . ' - ' . $row['name'] . ' (' . $row['code'] . ')';
        }
        return $options;
    }

    /**
     * Check if code is unique within faculty
     */
    public function isCodeUniqueInFaculty($code, $facultyId, $excludeId = null)
    {
        $builder = $this->where('code', $code)->where('faculty_id', $facultyId);

        if ($excludeId) {
            $builder->where('id !=', $excludeId);
        }

        return $builder->countAllResults() === 0;
    }

    /**
     * Get curriculum statistics
     */
    public function getStats()
    {
        $total = $this->countAll();
        $active = $this->where('status', 1)->countAllResults();

        $byDegree = $this->select('degree_level, COUNT(*) as count')
            ->where('status', 1)
            ->groupBy('degree_level')
            ->findAll();

        $byFaculty = $this->select('faculties.name as faculty_name, COUNT(curriculum.id) as count')
            ->join('faculties', 'faculties.id = curriculum.faculty_id')
            ->where('curriculum.status', 1)
            ->groupBy('curriculum.faculty_id')
            ->findAll();

        return [
            'total' => $total,
            'active' => $active,
            'by_degree' => $byDegree,
            'by_faculty' => $byFaculty
        ];
    }

    /**
     * Get curriculum with publication statistics
     * Counts publications where any author is affiliated with the curriculum
     */
    public function getWithPublicationStats($facultyId = null)
    {
        $builder = $this->db->table('curriculum');

        $builder->select('curriculum.*, faculties.name as faculty_name')
            ->join('faculties', 'faculties.id = curriculum.faculty_id', 'left')
            ->where('curriculum.status', 1);

        // Filter by faculty if provided
        if ($facultyId) {
            $builder->where('curriculum.faculty_id', $facultyId);
        }

        $builder->orderBy('faculties.name ASC, curriculum.name ASC');

        $curricula = $builder->get()->getResultArray();

        $db = \Config\Database::connect();
        $result = [];

        foreach ($curricula as $curriculum) {
            // Count publications where this curriculum appears in author_curriculum
            $pubQuery = $db->query("
                SELECT COUNT(*) as count
                FROM publication_view
                WHERE author_curriculum LIKE ?
            ", ['%' . $curriculum['name'] . '%']);

            $publicationCount = $pubQuery->getRowArray()['count'] ?? 0;

            // Count unique authors for this curriculum
            $authorQuery = $db->query("
                SELECT COUNT(DISTINCT pa.author_id) as count
                FROM publication_view pv
                JOIN publication_authors pa ON pv.id = pa.publication_id
                WHERE pv.author_curriculum LIKE ?
                AND pa.author_id IS NOT NULL
            ", ['%' . $curriculum['name'] . '%']);

            $authorCount = $authorQuery->getRowArray()['count'] ?? 0;

            $result[] = [
                'id' => $curriculum['id'],
                'curriculum_name' => $curriculum['name'],
                'code' => $curriculum['code'],
                'degree_level' => $curriculum['degree_level'],
                'faculty_id' => $curriculum['faculty_id'],
                'faculty_name' => $curriculum['faculty_name'],
                'publication_count' => $publicationCount,
                'author_count' => $authorCount,
                'total_citations' => 0
            ];
        }

        // Debug log
        log_message('debug', 'getWithPublicationStats returned ' . count($result) . ' records');

        return $result;
    }

    /**
     * Find active curricula by name: exact match first, then partial (LIKE).
     *
     * @return list<array<string,mixed>>
     */
    public function searchActiveByNamePartial(string $name, ?int $facultyId = null): array
    {
        $name = trim($name);
        if ($name === '') {
            return [];
        }

        $normalized = mb_strtolower($name);
        $db         = $this->db;
        $select     = 'curriculum.*, faculties.name as faculty_name, faculties.code as faculty_code';

        $applyFacultyFilter = static function ($builder) use ($facultyId) {
            if ($facultyId !== null && $facultyId > 0) {
                $builder->where('curriculum.faculty_id', $facultyId);
            }

            return $builder;
        };

        $exactBuilder = $applyFacultyFilter(
            $db->table('curriculum')
                ->select($select)
                ->join('faculties', 'faculties.id = curriculum.faculty_id')
                ->where('curriculum.status', 1)
                ->where('LOWER(curriculum.name)', $normalized)
        );
        $exact = $exactBuilder->orderBy('curriculum.name', 'ASC')->get()->getResultArray();

        if ($exact !== []) {
            return $exact;
        }

        $partialBuilder = $applyFacultyFilter(
            $db->table('curriculum')
                ->select($select)
                ->join('faculties', 'faculties.id = curriculum.faculty_id')
                ->where('curriculum.status', 1)
                ->like('curriculum.name', $name, 'both')
        );

        return $partialBuilder->orderBy('curriculum.name', 'ASC')->get()->getResultArray();
    }

    /**
     * @return array<string,mixed>|null
     */
    public function getDetailWithFacultyAndChair(int $id): ?array
    {
        $row = $this->select('curriculum.*, faculties.name as faculty_name, faculties.code as faculty_code')
            ->join('faculties', 'faculties.id = curriculum.faculty_id')
            ->where('curriculum.id', $id)
            ->where('curriculum.status', 1)
            ->first();

        if ($row === null) {
            return null;
        }

        $row['chair'] = null;
        if (! empty($row['chair_id'])) {
            $userModel = new UserModel();
            $chair = $userModel->find($row['chair_id']);
            if (is_array($chair)) {
                $row['chair'] = $chair;
            }
        }

        return $row;
    }
}
