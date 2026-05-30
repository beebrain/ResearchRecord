<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Student Admission Form Model
 * จัดการข้อมูลแบบฟอร์มขอเปิดรับนักศึกษาใหม่ประจำปีการศึกษา
 */
class StudentAdmissionFormModel extends Model
{
    protected $table = 'student_admission_forms';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $allowedFields = [
        'academic_year',
        'curriculum_id',
        'faculty_id',
        'curriculum_name',
        'curriculum_version_year',
        'ministry_approval_date',
        'university_approval_date',
        'quality_assessment_year1',
        'quality_assessment_result1',
        'quality_assessment_year2',
        'quality_assessment_result2',
        'teachers_status',
        'teachers_incomplete_order',
        'teachers_incomplete_reason',
        'teachers_incomplete_teachers',
        'retiring_teachers',
        'studying_teachers',
        // Keep old fields for backward compatibility
        'retiring_year1',
        'retiring_count1',
        'retiring_year2',
        'retiring_count2',
        'retiring_year3',
        'retiring_count3',
        'studying_year1',
        'studying_count1',
        'studying_year2',
        'studying_count2',
        'studying_year3',
        'studying_count3',
        'has_major_minor',
        'major_count',
        'admission_plan_count',
        'target_highschool',
        'target_highschool_count',
        'target_diploma',
        'target_diploma_count',
        'qualification_highschool',
        'qualification_diploma',
        'current_highschool_year1',
        'current_highschool_year2',
        'current_highschool_year3',
        'current_highschool_year4',
        'current_highschool_graduated',
        'current_highschool_remain',
        'current_diploma_year1',
        'current_diploma_year2',
        'current_diploma_year3',
        'current_diploma_year4',
        'current_diploma_graduated',
        'current_diploma_remain',
        'remaining_student_plan',
        'remaining_student_kpi',
        'curriculum_head_approval_date',
        'curriculum_head_name',
        'dean_approval_date',
        'dean_name',
        'status',
        'created_by',
        'updated_by'
    ];

    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    /**
     * Get all forms for a specific academic year
     */
    public function getFormsByYear(int $year)
    {
        return $this->select('student_admission_forms.*, 
                              curriculum.name as curriculum_name_display,
                              faculties.name as faculty_name')
            ->join('curriculum', 'curriculum.id = student_admission_forms.curriculum_id', 'left')
            ->join('faculties', 'faculties.id = student_admission_forms.faculty_id', 'left')
            ->where('academic_year', $year)
            ->orderBy('faculties.name', 'ASC')
            ->orderBy('curriculum.name', 'ASC')
            ->findAll();
    }

    /**
     * Get all distinct academic years
     */
    public function getAvailableYears()
    {
        return $this->distinct()
            ->select('academic_year')
            ->orderBy('academic_year', 'DESC')
            ->findColumn('academic_year') ?? [];
    }

    /**
     * Get form by ID with related data
     */
    public function getFormWithDetails(int $id)
    {
        $form = $this->select('student_admission_forms.*, 
                               curriculum.name as curriculum_name_display,
                               curriculum.code as curriculum_code,
                               curriculum.degree_level,
                               curriculum.chair_id,
                               faculties.name as faculty_name,
                               faculties.code as faculty_code,
                               faculties.dean_id,
                               chair.uid as chair_uid,
                               chair.titleThai as chair_title,
                               chair.title as chair_title_en,
                               chair.thai_name as chair_name,
                               chair.thai_lastname as chair_lastname,
                               chair.gf_name as chair_gf_name,
                               chair.gl_name as chair_gl_name,
                               dean.uid as dean_uid,
                               dean.titleThai as dean_title,
                               dean.title as dean_title_en,
                               dean.thai_name as dean_name,
                               dean.thai_lastname as dean_lastname,
                               dean.gf_name as dean_gf_name,
                               dean.gl_name as dean_gl_name')
            ->join('curriculum', 'curriculum.id = student_admission_forms.curriculum_id', 'left')
            ->join('faculties', 'faculties.id = student_admission_forms.faculty_id', 'left')
            ->join('user as chair', 'chair.uid = curriculum.chair_id', 'left')
            ->join('user as dean', 'dean.uid = faculties.dean_id', 'left')
            ->where('student_admission_forms.id', $id)
            ->first();

        if ($form) {
            // Get responsible teachers
            $form['teachers'] = $this->getTeachers($id);
            // Get teacher publications
            $form['publications'] = $this->getTeacherPublications($id);

            // Parse JSON fields for retiring and studying teachers
            if (!empty($form['retiring_teachers'])) {
                $form['retiring_teachers_parsed'] = json_decode($form['retiring_teachers'], true) ?: [];
            } else {
                $form['retiring_teachers_parsed'] = [];
            }

            if (!empty($form['studying_teachers'])) {
                $form['studying_teachers_parsed'] = json_decode($form['studying_teachers'], true) ?: [];
            } else {
                $form['studying_teachers_parsed'] = [];
            }

            // Format chair name (ประธานหลักสูตร)
            if (!empty($form['chair_id'])) {
                $chairName = '';
                if (!empty($form['chair_name']) && !empty($form['chair_lastname'])) {
                    $title = !empty($form['chair_title']) ? $form['chair_title'] . ' ' : '';
                    $chairName = $title . $form['chair_name'] . ' ' . $form['chair_lastname'];
                } elseif (!empty($form['chair_gf_name']) && !empty($form['chair_gl_name'])) {
                    $title = !empty($form['chair_title_en']) ? $form['chair_title_en'] . ' ' : '';
                    $chairName = $title . $form['chair_gf_name'] . ' ' . $form['chair_gl_name'];
                }
                // Auto-fill if not already set
                if (empty($form['curriculum_head_name']) && !empty($chairName)) {
                    $form['curriculum_head_name'] = $chairName;
                }
            }

            // Format dean name (คณบดี) - store original values to avoid conflict
            if (!empty($form['dean_id'])) {
                // IMPORTANT: When using SELECT with student_admission_forms.*, 
                // the JOIN fields (dean.thai_name as dean_name) may be overridden by 
                // student_admission_forms.dean_name (saved value). We need to preserve 
                // the JOIN values before they get overwritten.

                // Store original dean name from user table (from JOIN: dean.thai_name as dean_name)
                $deanNameFromUser = $form['dean_name'] ?? ''; // This should be from dean.thai_name (JOIN)
                $deanLastnameFromUser = $form['dean_lastname'] ?? ''; // This should be from dean.thai_lastname (JOIN)

                // Get the saved value from the form table separately
                $savedForm = $this->where('id', $id)->select('dean_name')->first();
                $savedDeanName = $savedForm['dean_name'] ?? '';

                // Store the user table values (full name from system) for display
                $form['dean_name_from_db'] = $deanNameFromUser;
                $form['dean_lastname_from_db'] = $deanLastnameFromUser;

                // Build full name from user table
                $deanName = '';
                if (!empty($deanNameFromUser) && !empty($deanLastnameFromUser)) {
                    $title = !empty($form['dean_title']) ? $form['dean_title'] . ' ' : '';
                    $deanName = $title . $deanNameFromUser . ' ' . $deanLastnameFromUser;
                } elseif (!empty($form['dean_gf_name']) && !empty($form['dean_gl_name'])) {
                    $title = !empty($form['dean_title_en']) ? $form['dean_title_en'] . ' ' : '';
                    $deanName = $title . $form['dean_gf_name'] . ' ' . $form['dean_gl_name'];
                }

                // Always use the full name from user table if available (ensures complete name)
                // This fixes the issue where saved value might be incomplete
                if (!empty($deanName)) {
                    $form['dean_name'] = $deanName;
                } elseif (!empty($savedDeanName)) {
                    // Fallback to saved value if no user table data
                    $form['dean_name'] = $savedDeanName;
                }
            }
        }

        return $form;
    }

    /**
     * Get form by curriculum and year
     */
    public function getFormByCurriculumAndYear(int $curriculumId, int $year)
    {
        return $this->where('curriculum_id', $curriculumId)
            ->where('academic_year', $year)
            ->first();
    }

    /**
     * Get forms by faculty IDs (for faculty admin filtering)
     */
    public function getFormsByFaculties(array $facultyIds, ?int $year = null)
    {
        $builder = $this->select('student_admission_forms.*, 
                                  curriculum.name as curriculum_name_display,
                                  faculties.name as faculty_name')
            ->join('curriculum', 'curriculum.id = student_admission_forms.curriculum_id', 'left')
            ->join('faculties', 'faculties.id = student_admission_forms.faculty_id', 'left')
            ->whereIn('student_admission_forms.faculty_id', $facultyIds);

        if ($year) {
            $builder->where('academic_year', $year);
        }

        return $builder->orderBy('academic_year', 'DESC')
            ->orderBy('faculties.name', 'ASC')
            ->orderBy('curriculum.name', 'ASC')
            ->findAll();
    }

    /**
     * Create forms for all curricula for a new academic year
     * Called when a new year starts to auto-generate forms
     */
    public function createFormsForNewYear(int $year, ?array $facultyIds = null)
    {
        $db = \Config\Database::connect();
        $curriculumModel = new CurriculumModel();

        // Get all active curricula (optionally filtered by faculty)
        $builder = $db->table('curriculum')
            ->select('curriculum.id as curriculum_id, curriculum.name, curriculum.faculty_id')
            ->where('curriculum.status', 1);

        if ($facultyIds && !empty($facultyIds)) {
            $builder->whereIn('curriculum.faculty_id', $facultyIds);
        }

        $curricula = $builder->get()->getResultArray();

        $createdCount = 0;
        foreach ($curricula as $curr) {
            // Check if form already exists
            $existing = $this->getFormByCurriculumAndYear($curr['curriculum_id'], $year);
            if (!$existing) {
                $this->insert([
                    'academic_year' => $year,
                    'curriculum_id' => $curr['curriculum_id'],
                    'faculty_id' => $curr['faculty_id'],
                    'curriculum_name' => $curr['name'],
                    'status' => 'draft'
                ]);
                $createdCount++;
            }
        }

        return $createdCount;
    }

    /**
     * Get responsible teachers for a curriculum (from teacher_curriculum table)
     */
    public function getTeachers(int $formId)
    {
        $db = \Config\Database::connect();

        // First get the curriculum_id from the form
        $form = $this->find($formId);
        if (!$form || empty($form['curriculum_id'])) {
            return [];
        }

        $curriculumId = $form['curriculum_id'];

        // Get teachers from teacher_curriculum table
        // Use CONCAT_WS and COALESCE to handle NULL values (titleThai may be NULL)
        return $db->table('teacher_curriculum tc')
            ->select('tc.id, tc.teacher_uid as user_id, tc.role as position,
                      u.thai_name, u.thai_lastname, u.titleThai,
                      CONCAT_WS(" ", COALESCE(u.titleThai, ""), u.thai_name, u.thai_lastname) as full_name,
                      CONCAT_WS(" ", u.gf_name, u.gl_name) as user_name_en,
                      CONCAT_WS(" ", u.thai_name, u.thai_lastname) as user_name_th')
            ->join('user u', 'u.uid = tc.teacher_uid')
            ->where('tc.curriculum_id', $curriculumId)
            ->orderBy('tc.role', 'ASC')
            ->orderBy('u.thai_name', 'ASC')
            ->get()
            ->getResultArray();
    }

    /**
     * Get teachers from curriculum for publications lookup
     */
    public function getTeacherUserIds(int $formId)
    {
        $teachers = $this->getTeachers($formId);
        return array_filter(array_column($teachers, 'user_id'));
    }

    /**
     * Get teacher publications for a form (from publications table via teacher user_id)
     * Uses the same matching logic as PublicationModel::getPublicationsByAuthor()
     * PRIMARY: Match by author_email from publication_authors
     * SECONDARY: Match by UID (pa.uid or authors.user_uid)
     * Does not include publications by created_by alone (data entry ≠ author).
     */
    public function getTeacherPublications(int $formId)
    {
        $db = \Config\Database::connect();
        $log = \Config\Services::logger();

        $log->info("getTeacherPublications: Starting for form ID {$formId}");

        // Get teachers for this form
        $teachers = $this->getTeachers($formId);
        $userIds = array_filter(array_column($teachers, 'user_id'));

        $log->info("getTeacherPublications: Found " . count($teachers) . " teachers - " . json_encode([
            'form_id' => $formId,
            'teacher_count' => count($teachers),
            'user_ids' => $userIds,
            'teachers' => array_map(function ($t) {
                return [
                    'user_id' => $t['user_id'] ?? null,
                    'name' => ($t['thai_name'] ?? '') . ' ' . ($t['thai_lastname'] ?? '')
                ];
            }, $teachers)
        ], JSON_UNESCAPED_UNICODE));

        if (empty($userIds)) {
            $log->warning("getTeacherPublications: No teachers found for form ID {$formId}");
            return [];
        }

        // Step 1: Get ALL emails for ALL teachers
        $allUserEmails = [];
        foreach ($userIds as $userId) {
            // Get primary email from user table
            $user = $db->table('user')
                ->select('email')
                ->where('uid', $userId)
                ->get()
                ->getRowArray();

            if (!empty($user['email'])) {
                $allUserEmails[] = $user['email'];
            }

            // Get all emails from authors table for this user
            $authorEmails = $db->table('authors')
                ->select('email')
                ->where('user_uid', $userId)
                ->where('email IS NOT NULL')
                ->where('email !=', '')
                ->get()
                ->getResultArray();

            foreach ($authorEmails as $authorEmail) {
                if (!empty($authorEmail['email']) && !in_array($authorEmail['email'], $allUserEmails)) {
                    $allUserEmails[] = $authorEmail['email'];
                }
            }
        }

        $log->info("getTeacherPublications: Collected emails for teachers - " . json_encode([
            'form_id' => $formId,
            'email_count' => count($allUserEmails),
            'emails' => $allUserEmails
        ], JSON_UNESCAPED_UNICODE));

        // Step 2: Get distinct publication IDs where teachers are authors
        // PRIMARY: Match author_email with ALL user emails
        // SECONDARY: Match by UID (pa.uid or authors.user_uid)
        $builder = $db->table('publication_authors pa');
        $builder->select('pa.publication_id')
            ->distinct()
            ->join('authors a', 'pa.author_id = a.id', 'left')
            ->join('user u', 'pa.uid = u.uid', 'left');

        if (!empty($allUserEmails)) {
            $builder->groupStart()
                ->whereIn('pa.author_email', $allUserEmails)  // PRIMARY: author_email from publication_authors
                ->orWhereIn('a.email', $allUserEmails)         // Also check authors.email
                ->orWhereIn('u.email', $allUserEmails)          // Also check user.email from joined user
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
        $ids = array_column($publicationIds, 'publication_id');

        $log->info("getTeacherPublications: Found publications by author match - " . json_encode([
            'form_id' => $formId,
            'publication_count' => count($ids),
            'publication_ids' => $ids
        ], JSON_UNESCAPED_UNICODE));

        $allIds = array_values(array_unique(array_map('intval', $ids)));

        $log->info("getTeacherPublications: Total unique publication IDs - " . json_encode([
            'form_id' => $formId,
            'total_count' => count($allIds),
            'all_ids' => $allIds
        ], JSON_UNESCAPED_UNICODE));

        if (empty($allIds)) {
            $log->warning("getTeacherPublications: No publications found for form ID {$formId}");
            return [];
        }

        // Step 3: Get publications from publication_view
        // publication_view already has aggregated author data, so we query it directly
        // Use SELECT * to avoid field name issues, then filter what we need
        $publications = $db->table('publication_view')
            ->select('*')
            ->whereIn('id', $allIds)
            ->orderBy('publication_year', 'DESC')
            ->orderBy('id', 'DESC')
            ->get()
            ->getResultArray();

        $log->info("getTeacherPublications: Retrieved publications from publication_view - " . json_encode([
            'form_id' => $formId,
            'publication_count' => count($publications),
            'publication_titles' => array_map(function ($p) {
                return [
                    'id' => $p['id'] ?? null,
                    'title' => substr($p['title'] ?? '', 0, 50) . '...',
                    'year' => $p['publication_year'] ?? null,
                    'created_by' => $p['created_by'] ?? null
                ];
            }, $publications)
        ], JSON_UNESCAPED_UNICODE));

        // Step 4: Map publications to teachers using EMAIL ONLY
        // Create email-to-teacher mapping (one-time lookup)
        $emailToTeacherMap = [];
        foreach ($userIds as $teacherId) {
            // Get all emails for this teacher
            $teacherEmails = [];

            // Get email from user table
            $user = $db->table('user')->select('email')->where('uid', $teacherId)->get()->getRowArray();
            if (!empty($user['email'])) {
                $email = strtolower(trim($user['email']));
                $teacherEmails[] = $email;
                $emailToTeacherMap[$email] = $teacherId;
            }

            // Get emails from authors table
            $authorEmails = $db->table('authors')->select('email')->where('user_uid', $teacherId)->get()->getResultArray();
            foreach ($authorEmails as $ae) {
                if (!empty($ae['email'])) {
                    $email = strtolower(trim($ae['email']));
                    if (!in_array($email, $teacherEmails)) {
                        $teacherEmails[] = $email;
                        $emailToTeacherMap[$email] = $teacherId;
                    }
                }
            }
        }

        $log->info("getTeacherPublications: Email to teacher mapping - " . json_encode([
            'form_id' => $formId,
            'email_map_count' => count($emailToTeacherMap),
            'email_map' => $emailToTeacherMap
        ], JSON_UNESCAPED_UNICODE));

        // Get ALL author emails from publication_authors for these publications
        $publicationAuthorMap = [];
        if (!empty($allIds)) {
            $authorData = $db->table('publication_authors pa')
                ->select('pa.publication_id, pa.author_email')
                ->whereIn('pa.publication_id', $allIds)
                ->where('pa.author_email IS NOT NULL')
                ->where('pa.author_email !=', '')
                ->get()
                ->getResultArray();

            $log->info("getTeacherPublications: All author emails from publication_authors - " . json_encode([
                'form_id' => $formId,
                'author_data_count' => count($authorData),
                'author_data_sample' => array_slice($authorData, 0, 10)
            ], JSON_UNESCAPED_UNICODE));

            // Build publication author map using EMAIL ONLY
            foreach ($authorData as $author) {
                $pubId = $author['publication_id'];
                $authorEmail = strtolower(trim($author['author_email'] ?? ''));

                if (empty($authorEmail)) {
                    continue;
                }

                if (!isset($publicationAuthorMap[$pubId])) {
                    $publicationAuthorMap[$pubId] = [];
                }

                // Match by email ONLY
                if (isset($emailToTeacherMap[$authorEmail])) {
                    $teacherId = $emailToTeacherMap[$authorEmail];
                    if (!in_array($teacherId, $publicationAuthorMap[$pubId])) {
                        $publicationAuthorMap[$pubId][] = $teacherId;
                    }
                }
            }

            $log->info("getTeacherPublications: Created author mapping - " . json_encode([
                'form_id' => $formId,
                'mapped_publications' => count($publicationAuthorMap),
                'mapping_details' => array_map(function ($pubId, $uids) {
                    return ['publication_id' => $pubId, 'author_uids' => $uids];
                }, array_keys($publicationAuthorMap), array_values($publicationAuthorMap))
            ], JSON_UNESCAPED_UNICODE));
        }

        // Step 5: Expand publications to include author_uid for matching teachers
        $result = [];
        $teacherPublicationCount = [];
        $matchingDetails = [];
        foreach ($publications as $pub) {
            $pubId = $pub['id'];
            $pubAuthorUids = $publicationAuthorMap[$pubId] ?? [];

            // Check if any teacher is an author (via email match ONLY)
            $matchedTeacherIds = [];
            $matchReasons = [];
            foreach ($userIds as $teacherId) {
                $isAuthor = in_array($teacherId, $pubAuthorUids);
                // Note: We only match by email, not by created_by

                if ($isAuthor) {
                    $matchedTeacherIds[] = $teacherId;
                    $matchReasons[$teacherId] = [
                        'is_author' => true,
                        'match_by_email' => true,
                        'pub_author_uids' => $pubAuthorUids,
                        'pub_created_by' => $pub['created_by']
                    ];
                }
            }

            $matchingDetails[] = [
                'publication_id' => $pubId,
                'title' => substr($pub['title'] ?? '', 0, 50),
                'matched_teachers' => $matchedTeacherIds,
                'match_reasons' => $matchReasons,
                'pub_author_uids' => $pubAuthorUids,
                'pub_created_by' => $pub['created_by']
            ];

            // Create a result entry for each matched teacher
            if (!empty($matchedTeacherIds)) {
                foreach ($matchedTeacherIds as $teacherId) {
                    // Get author name from publication_authors for this specific teacher
                    $authorName = null;
                    $authorNameTh = null;

                    // Get teacher's emails for matching
                    $teacherEmails = [];
                    $user = $db->table('user')->select('email')->where('uid', $teacherId)->get()->getRowArray();
                    if (!empty($user['email'])) {
                        $teacherEmails[] = strtolower(trim($user['email']));
                    }
                    $authorEmails = $db->table('authors')->select('email')->where('user_uid', $teacherId)->get()->getResultArray();
                    foreach ($authorEmails as $ae) {
                        if (!empty($ae['email'])) {
                            $email = strtolower(trim($ae['email']));
                            if (!in_array($email, $teacherEmails)) {
                                $teacherEmails[] = $email;
                            }
                        }
                    }

                    // Find author name from publication_authors by email match
                    if (!empty($teacherEmails)) {
                        $authorData = $db->table('publication_authors pa')
                            ->select('pa.author_name, pa.author_email, a.user_uid, u.thai_name, u.thai_lastname, u.titleThai')
                            ->join('authors a', 'pa.author_id = a.id', 'left')
                            ->join('user u', 'a.user_uid = u.uid', 'left')
                            ->where('pa.publication_id', $pubId)
                            ->whereIn('LOWER(TRIM(pa.author_email))', array_map('strtolower', $teacherEmails))
                            ->orderBy('pa.author_order', 'ASC')
                            ->limit(1)
                            ->get()
                            ->getRowArray();

                        if ($authorData) {
                            // Use user's Thai name if available, otherwise use author_name
                            if (!empty($authorData['thai_name']) && !empty($authorData['thai_lastname'])) {
                                $title = !empty($authorData['titleThai']) ? $authorData['titleThai'] . ' ' : '';
                                $authorNameTh = $title . $authorData['thai_name'] . ' ' . $authorData['thai_lastname'];
                            } else {
                                $authorNameTh = $authorData['author_name'] ?? null;
                            }
                            $authorName = $authorData['author_name'] ?? null;
                        }
                    }

                    // Fallback to first author from authors_names_thai if not found
                    if (empty($authorNameTh)) {
                        $allAuthors = $pub['authors_names_thai'] ?? $pub['authors_names_th'] ?? '';
                        if (!empty($allAuthors)) {
                            $authorsList = explode(',', $allAuthors);
                            $authorNameTh = trim($authorsList[0] ?? '');
                        }
                    }

                    $result[] = [
                        'id' => $pub['id'],
                        'title' => $pub['title'],
                        'publication_type' => $pub['publication_type'],
                        'source' => $pub['source'],
                        'publication_year' => $pub['publication_year'],
                        'volume' => $pub['volume'] ?? null,
                        'notes' => $pub['notes'] ?? null,
                        'abstract' => $pub['abstract'] ?? null,
                        'authors_names_th' => $pub['authors_names_thai'] ?? $pub['authors_names_th'] ?? '',
                        'authors_names_en' => $pub['authors_names_en'] ?? '',
                        'author_name_th' => $authorNameTh ?? '-',  // Individual author name for this teacher
                        'author_name' => $authorName ?? '-',      // Individual author name (English/fallback)
                        'approve' => $pub['approve'] ?? null,
                        'author_uid' => $teacherId,  // Set to matched teacher ID
                        'user_id' => $teacherId,     // Also include for backward compatibility
                    ];
                    // Count publications per teacher
                    if (!isset($teacherPublicationCount[$teacherId])) {
                        $teacherPublicationCount[$teacherId] = 0;
                    }
                    $teacherPublicationCount[$teacherId]++;
                }
            }
            // Note: We only match by email, not by created_by
        }

        $log->info("getTeacherPublications: Matching details - " . json_encode([
            'form_id' => $formId,
            'matching_details' => $matchingDetails
        ], JSON_UNESCAPED_UNICODE));

        $log->info("getTeacherPublications: Final result summary - " . json_encode([
            'form_id' => $formId,
            'total_result_count' => count($result),
            'publications_per_teacher' => $teacherPublicationCount,
            'result_sample' => array_slice($result, 0, 5, true), // First 5 results as sample
            'all_result_ids' => array_map(function ($r) {
                return [
                    'id' => $r['id'] ?? null,
                    'author_uid' => $r['author_uid'] ?? null,
                    'user_id' => $r['user_id'] ?? null,
                    'title' => substr($r['title'] ?? '', 0, 30)
                ];
            }, $result)
        ], JSON_UNESCAPED_UNICODE));

        return $result;
    }


    /**
     * Get statistics for dashboard
     */
    public function getStatistics(?array $facultyIds = null, ?int $year = null)
    {
        $builder = $this->select('COUNT(*) as total,
                                  SUM(CASE WHEN status = "draft" THEN 1 ELSE 0 END) as draft_count,
                                  SUM(CASE WHEN status = "submitted" THEN 1 ELSE 0 END) as submitted_count,
                                  SUM(CASE WHEN status = "approved" THEN 1 ELSE 0 END) as approved_count,
                                  SUM(CASE WHEN status = "rejected" THEN 1 ELSE 0 END) as rejected_count');

        if ($facultyIds && !empty($facultyIds)) {
            $builder->whereIn('faculty_id', $facultyIds);
        }

        if ($year) {
            $builder->where('academic_year', $year);
        }

        return $builder->first();
    }
}
