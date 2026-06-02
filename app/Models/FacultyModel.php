<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * FacultyModel.php
 */
class FacultyModel extends Model
{
    protected $table = 'faculties';
    protected $primaryKey = 'id';
    protected $returnType = 'array';

    protected $allowedFields = [
        'name',
        'code',
        'status',
        'dean_email'
    ];

    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = '';

    protected $validationRules = [
        'name' => 'required|min_length[3]|max_length[255]',
        'code' => 'required|min_length[2]|max_length[10]',
        'status' => 'in_list[0,1]'
    ];

    /**
     * Get all active faculties
     */
    public function getActive()
    {
        return $this->where('status', 1)->findAll();
    }

    /**
     * Get faculty with curriculum count
     */
    public function getWithCurriculumCount()
    {
        return $this->select('faculties.*, COUNT(curriculum.id) as curriculum_count,
                dean.email as dean_uid,
                dean.titleThai as dean_title,
                dean.title as dean_title_en,
                dean.thai_name as dean_name,
                dean.thai_lastname as dean_lastname,
                dean.gf_name as dean_gf_name,
                dean.gl_name as dean_gl_name')
            ->join('curriculum', 'curriculum.faculty_id = faculties.id', 'left')
            ->join('user as dean', 'dean.email = faculties.dean_email', 'left')
            ->groupBy('faculties.id')
            ->findAll();
    }

    /**
     * Get faculty by code
     */
    public function getByCode($code)
    {
        return $this->where('code', $code)->first();
    }

    /**
     * Get faculties for dropdown
     */
    public function getForSelect()
    {
        $faculties = $this->getActive();
        $options = [];
        foreach ($faculties as $faculty) {
            $options[$faculty['id']] = $faculty['name'] . ' (' . $faculty['code'] . ')';
        }
        return $options;
    }

    /**
     * Get faculty with publication count for dashboard
     * Uses publication_view with faculty_id for accurate counts
     */
    public function getWithPublicationCount()
    {
        $db = \Config\Database::connect();

        // Get all active faculties
        $faculties = $this->where('status', 1)->findAll();
        $result = [];

        foreach ($faculties as $faculty) {
            // Count publications using faculty_id from publication_view (more accurate)
            $count = $db->table('publication_view')
                ->where('faculty_id', $faculty['id'])
                ->countAllResults();

            $result[] = [
                'id' => $faculty['id'],
                'faculty' => $faculty['name'],
                'code' => $faculty['code'],
                'count' => $count
            ];
        }

        // Sort by count descending
        usort($result, function($a, $b) {
            return $b['count'] - $a['count'];
        });

        // Debug log
        log_message('debug', 'getWithPublicationCount returned ' . count($result) . ' records');
        log_message('debug', 'Faculty counts: ' . json_encode($result));

        return $result;
    }

    /**
     * Get all faculties with their curriculums
     */
    public function getWithCurriculums()
    {
        $db = \Config\Database::connect();

        // Get all active faculties
        $faculties = $this->where('status', 1)
            ->orderBy('name', 'ASC')
            ->findAll();

        // Get all active curriculums grouped by faculty
        $curriculumQuery = $db->table('curriculum')
            ->select('id, faculty_id, name, code, degree_level')
            ->where('status', 1)
            ->orderBy('name', 'ASC')
            ->get();

        $curriculums = $curriculumQuery->getResultArray();

        // Group curriculums by faculty
        $curriculumsByFaculty = [];
        foreach ($curriculums as $curriculum) {
            $facultyId = $curriculum['faculty_id'];
            if (!isset($curriculumsByFaculty[$facultyId])) {
                $curriculumsByFaculty[$facultyId] = [];
            }
            $curriculumsByFaculty[$facultyId][] = $curriculum;
        }

        // Attach curriculums to faculties
        foreach ($faculties as &$faculty) {
            $faculty['curriculums'] = $curriculumsByFaculty[$faculty['id']] ?? [];
        }

        return $faculties;
    }
}
