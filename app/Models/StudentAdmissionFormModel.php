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
        'majors_detail',
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
                               curriculum.chair_email,
                               faculties.name as faculty_name,
                               faculties.code as faculty_code,
                               faculties.dean_email,
                               chair.titleThai as chair_title,
                               chair.title as chair_title_en,
                               chair.thai_name as chair_name,
                               chair.thai_lastname as chair_lastname,
                               chair.gf_name as chair_gf_name,
                               chair.gl_name as chair_gl_name,
                               dean.titleThai as dean_title,
                               dean.title as dean_title_en,
                               dean.thai_name as dean_name,
                               dean.thai_lastname as dean_lastname,
                               dean.gf_name as dean_gf_name,
                               dean.gl_name as dean_gl_name')
            ->join('curriculum', 'curriculum.id = student_admission_forms.curriculum_id', 'left')
            ->join('faculties', 'faculties.id = student_admission_forms.faculty_id', 'left')
            ->join('user as chair', 'chair.email = curriculum.chair_email', 'left')
            ->join('user as dean', 'dean.email = faculties.dean_email', 'left')
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
            if (!empty($form['chair_email'])) {
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
            if (!empty($form['dean_email'])) {
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
     * Get responsible teachers for a curriculum
     * Tries to fetch from admission_form_teachers first, then falls back to teacher_curriculum table.
     */
    public function getTeachers(int $formId)
    {
        $db = \Config\Database::connect();

        // First check if there are saved teachers in admission_form_teachers
        $savedTeachers = $db->table('admission_form_teachers aft')
            ->select('aft.id, aft.user_email as user_id, aft.user_email, aft.position,
                      aft.full_name, aft.order_num,
                      aft.pub_year_1, aft.pub_year_2, aft.pub_year_3, aft.pub_year_4, aft.pub_year_5,
                      aft.admission_year,
                      u.thai_name, u.thai_lastname, u.titleThai,
                      CONCAT_WS(" ", u.gf_name, u.gl_name) as user_name_en,
                      CONCAT_WS(" ", u.thai_name, u.thai_lastname) as user_name_th')
            ->join('user u', 'u.email = aft.user_email', 'left')
            ->where('aft.admission_form_id', $formId)
            ->orderBy('aft.order_num', 'ASC')
            ->get()
            ->getResultArray();

        if (!empty($savedTeachers)) {
            $this->attachTeacherEducation($savedTeachers);
            return $savedTeachers;
        }

        // Fallback to teacher_curriculum table
        $form = $this->find($formId);
        if (!$form || empty($form['curriculum_id'])) {
            return [];
        }

        $curriculumId = $form['curriculum_id'];

        $defaultTeachers = $db->table('teacher_curriculum tc')
            ->select('tc.id, tc.teacher_email as user_id, tc.teacher_email as user_email, tc.role as position,
                      u.thai_name, u.thai_lastname, u.titleThai,
                      CONCAT_WS(" ", COALESCE(u.titleThai, ""), u.thai_name, u.thai_lastname) as full_name,
                      CONCAT_WS(" ", u.gf_name, u.gl_name) as user_name_en,
                      CONCAT_WS(" ", u.thai_name, u.thai_lastname) as user_name_th')
            ->join('user u', 'u.email = tc.teacher_email', 'inner')
            ->where('tc.curriculum_id', $curriculumId)
            ->orderBy('tc.role', 'ASC')
            ->orderBy('u.thai_name', 'ASC')
            ->get()
            ->getResultArray();

        // Assign order_num and defaults to fallback teachers
        foreach ($defaultTeachers as $index => &$teacher) {
            $teacher['order_num'] = $index + 1;
            $teacher['pub_year_1'] = 0;
            $teacher['pub_year_2'] = 0;
            $teacher['pub_year_3'] = 0;
            $teacher['pub_year_4'] = 0;
            $teacher['pub_year_5'] = 0;
            $teacher['admission_year'] = null;
        }

        $this->attachTeacherEducation($defaultTeachers);
        return $defaultTeachers;
    }

    /**
     * Attach education history (คุณวุฒิการศึกษาทุกระดับ) to each teacher row.
     *
     * Pulls from the CV module (cv_sections type=education + cv_entries) which the
     * EducationController/profile pages already populate. Each teacher gains an
     * `education` array ordered highest/most-recent degree first, with a
     * pre-computed Buddhist-era graduation year so the PDF generator can render
     * the qualification column without further lookups.
     */
    private function attachTeacherEducation(array &$teachers): void
    {
        if (empty($teachers)) {
            return;
        }

        $db = \Config\Database::connect();

        foreach ($teachers as &$teacher) {
            $email = strtolower(trim($teacher['user_email'] ?? $teacher['user_id'] ?? ''));
            $teacher['education'] = [];
            if ($email === '') {
                continue;
            }

            $section = $db->table('cv_sections')
                ->select('id')
                ->where('owner_email_norm', $email)
                ->where('type', 'education')
                ->get()
                ->getRowArray();

            if (empty($section)) {
                continue;
            }

            $entries = $db->table('cv_entries')
                ->select('title, organization, location, start_date, end_date, is_current')
                ->where('section_id', $section['id'])
                ->orderBy('is_current', 'DESC')
                ->orderBy('end_date', 'DESC')
                ->orderBy('start_date', 'DESC')
                ->get()
                ->getResultArray();

            foreach ($entries as $entry) {
                $gradYearBe = null;
                if (!empty($entry['is_current'])) {
                    $gradYearBe = 'ปัจจุบัน';
                } elseif (!empty($entry['end_date'])) {
                    $year = (int) substr($entry['end_date'], 0, 4);
                    if ($year > 0) {
                        $gradYearBe = (string) ($year + 543);
                    }
                }

                $teacher['education'][] = [
                    'title'        => $entry['title'] ?? '',
                    'organization' => $entry['organization'] ?? '',
                    'location'     => $entry['location'] ?? '',
                    'grad_year_be' => $gradYearBe,
                ];
            }

            // Graduation dates are frequently blank, so the SQL date sort cannot
            // guarantee highest-degree-first. Re-sort by degree level inferred
            // from the title (เอก > โท > ตรี), newest year as a tiebreaker.
            usort($teacher['education'], static function ($a, $b) {
                $rank = static function (string $title): int {
                    if (preg_match('/เอก|ปร\.ด|ค\.ด|ดุษฎี|Ph\.?\s*D|D\.Eng/iu', $title)) return 3;
                    if (preg_match('/โท|มหาบัณฑิต|M\.(Sc|A|Eng|Ed|S)|ค\.ม|วท\.ม|ศศ\.ม/iu', $title)) return 2;
                    if (preg_match('/ตรี|บัณฑิต|B\.(Sc|A|Eng|Ed)|วท\.บ|ค\.บ|วศ\.บ/iu', $title)) return 1;
                    return 0;
                };
                $ra = $rank($a['title']);
                $rb = $rank($b['title']);
                if ($ra !== $rb) {
                    return $rb <=> $ra; // higher degree first
                }
                return (int) ($b['grad_year_be'] ?? 0) <=> (int) ($a['grad_year_be'] ?? 0);
            });
        }
        unset($teacher);
    }

    /**
     * Save responsible teachers for an admission form (from full edit page)
     */
    public function saveTeachers(int $formId, array $teachers)
    {
        $db = \Config\Database::connect();

        // First delete existing teachers for this form
        $db->table('admission_form_teachers')
            ->where('admission_form_id', $formId)
            ->delete();

        $form = $this->find($formId);
        if (!$form || empty($form['curriculum_id'])) {
            return;
        }
        $curriculumId = $form['curriculum_id'];

        $dbTeachers = $db->table('teacher_curriculum tc')
            ->select('tc.teacher_email')
            ->join('user u', 'u.email = tc.teacher_email', 'inner')
            ->where('tc.curriculum_id', $curriculumId)
            ->orderBy('tc.role', 'ASC')
            ->orderBy('u.thai_name', 'ASC')
            ->get()
            ->getResultArray();

        $insertData = [];
        foreach ($teachers as $orderNum => $teacherData) {
            $index = $orderNum - 1;
            $userEmail = $dbTeachers[$index]['teacher_email'] ?? null;

            $insertData[] = [
                'admission_form_id' => $formId,
                'order_num'         => $orderNum,
                'position'          => $teacherData['position'] ?? null,
                'full_name'         => $teacherData['full_name'] ?? null,
                'user_email'        => $userEmail,
                'pub_year_1'        => isset($teacherData['pub_year_1']) ? (int)$teacherData['pub_year_1'] : 0,
                'pub_year_2'        => isset($teacherData['pub_year_2']) ? (int)$teacherData['pub_year_2'] : 0,
                'pub_year_3'        => isset($teacherData['pub_year_3']) ? (int)$teacherData['pub_year_3'] : 0,
                'pub_year_4'        => isset($teacherData['pub_year_4']) ? (int)$teacherData['pub_year_4'] : 0,
                'pub_year_5'        => isset($teacherData['pub_year_5']) ? (int)$teacherData['pub_year_5'] : 0,
                'admission_year'    => !empty($teacherData['admission_year']) ? (int)$teacherData['admission_year'] : null,
            ];
        }

        if (!empty($insertData)) {
            $db->table('admission_form_teachers')->insertBatch($insertData);
        }
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
     * SECONDARY: Match by email address
     * Does not include publications by created_by alone (data entry ≠ author).
     */
    public function getTeacherPublications(int $formId)
    {
        $db = \Config\Database::connect();
        $log = \Config\Services::logger();

        $log->info("getTeacherPublications: Starting for form ID {$formId}");

        // Get teachers for this form
        $teachers = $this->getTeachers($formId);
        $userIds = array_filter(array_column($teachers, 'user_id')); // user_id is teacher_email here

        $log->info("getTeacherPublications: form {$formId} teacher_count=" . count($teachers));

        if (empty($userIds)) {
            $log->warning("getTeacherPublications: No teachers found for form ID {$formId}");
            return [];
        }

        // Step 1: Get ALL emails for ALL teachers
        $allUserEmails = [];
        foreach ($userIds as $userId) {
            if (!empty($userId)) {
                $email = strtolower(trim($userId));
                if (!in_array($email, $allUserEmails)) {
                    $allUserEmails[] = $email;
                }
            }

            // Get all emails from authors table for this user
            $authorEmails = $db->table('authors')
                ->select('email')
                ->where('user_email', $userId)
                ->where('email IS NOT NULL')
                ->where('email !=', '')
                ->get()
                ->getResultArray();

            foreach ($authorEmails as $authorEmail) {
                if (!empty($authorEmail['email'])) {
                    $email = strtolower(trim($authorEmail['email']));
                    if (!in_array($email, $allUserEmails)) {
                        $allUserEmails[] = $email;
                    }
                }
            }
        }

        $log->info("getTeacherPublications: form {$formId} email_count=" . count($allUserEmails));

        // Step 2: Get distinct publication IDs where teachers are authors
        // PRIMARY: Match author_email with ALL user emails
        $builder = $db->table('publication_authors pa');
        $builder->select('pa.publication_id')
            ->distinct()
            ->join('authors a', 'pa.author_id = a.id', 'left')
            ->join('user u', 'pa.author_email = u.email', 'left');

        if (!empty($allUserEmails)) {
            $builder->groupStart()
                ->whereIn('pa.author_email', $allUserEmails)  // PRIMARY: author_email from publication_authors
                ->orWhereIn('a.email', $allUserEmails)         // Also check authors.email
                ->orWhereIn('u.email', $allUserEmails)          // Also check user.email from joined user
                ->orWhereIn('a.user_email', $userIds)
                ->groupEnd();
        } else {
            $builder->groupStart()
                ->whereIn('a.user_email', $userIds)
                ->groupEnd();
        }

        $publicationIds = $builder->get()->getResultArray();
        $ids = array_column($publicationIds, 'publication_id');

        $allIds = array_values(array_unique(array_map('intval', $ids)));

        $log->info("getTeacherPublications: form {$formId} matched_pub_ids=" . count($allIds));

        if (empty($allIds)) {
            $log->warning("getTeacherPublications: No publications found for form ID {$formId}");
            return [];
        }

        // Step 3: Get publications from publication_view
        $publications = $db->table('publication_view')
            ->select('*')
            ->whereIn('id', $allIds)
            ->orderBy('publication_year', 'DESC')
            ->orderBy('id', 'DESC')
            ->get()
            ->getResultArray();

        $log->info("getTeacherPublications: form {$formId} retrieved_publications=" . count($publications));

        // Step 4: Map publications to teachers using EMAIL ONLY
        $emailToTeacherMap = [];
        foreach ($userIds as $teacherId) {
            $teacherEmails = [];
            
            if (!empty($teacherId)) {
                $email = strtolower(trim($teacherId));
                $teacherEmails[] = $email;
                $emailToTeacherMap[$email] = $teacherId;
            }

            // Get emails from authors table
            $authorEmails = $db->table('authors')->select('email')->where('user_email', $teacherId)->get()->getResultArray();
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
                'mapped_publications' => count($publicationAuthorMap)
            ], JSON_UNESCAPED_UNICODE));
        }

        // Step 5: Expand publications to include author_uid for matching teachers
        $result = [];
        $teacherPublicationCount = [];
        $matchingDetails = [];
        foreach ($publications as $pub) {
            $pubId = $pub['id'];
            $pubAuthorUids = $publicationAuthorMap[$pubId] ?? [];

            $matchedTeacherIds = [];
            $matchReasons = [];
            foreach ($userIds as $teacherId) {
                $isAuthor = in_array($teacherId, $pubAuthorUids);

                if ($isAuthor) {
                    $matchedTeacherIds[] = $teacherId;
                    $matchReasons[$teacherId] = [
                        'is_author' => true,
                        'match_by_email' => true,
                        'pub_author_uids' => $pubAuthorUids,
                        'pub_created_by_email' => $pub['created_by_email'] ?? null
                    ];
                }
            }

            $matchingDetails[] = [
                'publication_id' => $pubId,
                'title' => substr($pub['title'] ?? '', 0, 50),
                'matched_teachers' => $matchedTeacherIds,
                'pub_author_uids' => $pubAuthorUids
            ];

            if (!empty($matchedTeacherIds)) {
                foreach ($matchedTeacherIds as $teacherId) {
                    $authorName = null;
                    $authorNameTh = null;

                    $teacherEmails = [];
                    $teacherEmails[] = strtolower(trim($teacherId));
                    $authorEmails = $db->table('authors')->select('email')->where('user_email', $teacherId)->get()->getResultArray();
                    foreach ($authorEmails as $ae) {
                        if (!empty($ae['email'])) {
                            $email = strtolower(trim($ae['email']));
                            if (!in_array($email, $teacherEmails)) {
                                $teacherEmails[] = $email;
                            }
                        }
                    }

                    if (!empty($teacherEmails)) {
                        $authorData = $db->table('publication_authors pa')
                            ->select('pa.author_name, pa.author_email, a.user_email, u.thai_name, u.thai_lastname, u.titleThai')
                            ->join('authors a', 'pa.author_id = a.id', 'left')
                            ->join('user u', 'a.user_email = u.email', 'left')
                            ->where('pa.publication_id', $pubId)
                            ->whereIn('LOWER(TRIM(pa.author_email))', array_map('strtolower', $teacherEmails))
                            ->orderBy('pa.author_order', 'ASC')
                            ->limit(1)
                            ->get()
                            ->getRowArray();

                        if ($authorData) {
                            if (!empty($authorData['thai_name']) && !empty($authorData['thai_lastname'])) {
                                $title = !empty($authorData['titleThai']) ? $authorData['titleThai'] . ' ' : '';
                                $authorNameTh = $title . $authorData['thai_name'] . ' ' . $authorData['thai_lastname'];
                            } else {
                                $authorNameTh = $authorData['author_name'] ?? null;
                            }
                            $authorName = $authorData['author_name'] ?? null;
                        }
                    }

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
                        'publication_month' => $pub['publication_month'] ?? null,
                        'volume' => $pub['volume'] ?? null,
                        'pages' => $pub['pages'] ?? null,
                        'doi' => $pub['doi'] ?? null,
                        'notes' => $pub['notes'] ?? null,
                        'abstract' => $pub['abstract'] ?? null,
                        'authors_names_th' => $pub['authors_names_thai'] ?? $pub['authors_names_th'] ?? '',
                        'authors_names_en' => $pub['authors_names_en'] ?? '',
                        'author_name_th' => $authorNameTh ?? '-',
                        'author_name' => $authorName ?? '-',
                        'approve' => $pub['approve'] ?? null,
                        'author_uid' => $teacherId,
                        'user_id' => $teacherId,
                    ];
                    if (!isset($teacherPublicationCount[$teacherId])) {
                        $teacherPublicationCount[$teacherId] = 0;
                    }
                    $teacherPublicationCount[$teacherId]++;
                }
            }
        }

        $log->info("getTeacherPublications: Matching details - " . json_encode([
            'form_id' => $formId,
            'matching_details' => $matchingDetails
        ], JSON_UNESCAPED_UNICODE));

        $log->info("getTeacherPublications: Final result summary - " . json_encode([
            'form_id' => $formId,
            'total_result_count' => count($result),
            'publications_per_teacher' => $teacherPublicationCount,
            'result_sample' => array_slice($result, 0, 5, true),
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
