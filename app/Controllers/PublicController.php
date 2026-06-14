<?php

namespace App\Controllers;

use App\Models\CurriculumModel;
use App\Models\FacultyModel;
use App\Models\UserModel;
use CodeIgniter\Controller;

final class PublicController extends Controller
{
    public function index()
    {
        helper(['url', 'asset']);

        $facultyModel = new FacultyModel();
        $faculties    = $facultyModel->getWithCurriculums();

        return view('public/home', [
            'faculties' => $faculties,
        ]);
    }

    public function curriculum(int $id)
    {
        helper(['url', 'asset']);

        $curriculumModel = new CurriculumModel();
        $userModel       = new UserModel();
        $db              = \Config\Database::connect();

        $curriculum = $curriculumModel->getDetailWithFacultyAndChair($id);
        if ($curriculum === null) {
            return redirect()->to(site_url('/'))->with('error', 'ไม่พบหลักสูตร');
        }

        $facultyModel = new FacultyModel();
        $faculties    = $facultyModel->getWithCurriculums();

        $chairEmail = (string) ($curriculum['chair_email'] ?? '');
        $teachers   = $userModel->getCurriculumResponsibleTeachers($id, $chairEmail !== '' ? $chairEmail : null, 8);

        // Publications ที่มีอาจารย์ในหลักสูตรนี้เป็นผู้เขียน
        // NOTE: เลี่ยง publication_view (บาง server สิทธิ์ view อาจไม่พร้อม) ใช้ตารางจริงแทน
        $teacherEmails = $db->table('teacher_curriculum tc')
            ->select('tc.teacher_email')
            ->where('tc.curriculum_id', (int) $id)
            ->where('tc.status', 1)
            ->get()
            ->getResultArray();

        $emails = array_values(array_filter(array_map(
            static fn (array $r) => (string) ($r['teacher_email'] ?? ''),
            $teacherEmails
        )));

        $publications = [];
        $publicationCount = 0;
        $pubCountByEmail = [];
        if ($emails !== []) {
            $publications = $db->table('publications p')
                ->select('
                    p.id, p.title, p.publication_type, p.source, p.publication_year, p.publication_month, p.doi, p.approve, p.created_at,
                    p.created_by_email, p.volume, p.issue, p.pages, p.publisher,
                    CONCAT(COALESCE(creator.thai_name, creator.gf_name, \'\'), \' \', COALESCE(creator.thai_lastname, creator.gl_name, \'\')) as created_by_name,
                    GROUP_CONCAT(pa.author_name ORDER BY pa.author_order SEPARATOR \', \') as authors,
                    GROUP_CONCAT(pa.author_email ORDER BY pa.author_order SEPARATOR \', \') as author_emails
                ')
                ->join('publication_authors pa', 'pa.publication_id = p.id', 'inner')
                ->join('user creator', 'creator.email = p.created_by_email', 'left')
                ->where('p.approve', 1)
                ->whereIn('pa.author_email', $emails)
                ->groupBy('p.id')
                ->orderBy('p.publication_year', 'DESC')
                ->orderBy('p.publication_month', 'DESC')
                ->orderBy('p.created_at', 'DESC')
                ->limit(80)
                ->get()
                ->getResultArray();

            $countRow = $db->table('publications p')
                ->select('COUNT(DISTINCT p.id) as c')
                ->join('publication_authors pa', 'pa.publication_id = p.id', 'inner')
                ->where('p.approve', 1)
                ->whereIn('pa.author_email', $emails)
                ->get()
                ->getRowArray();
            $publicationCount = (int) ($countRow['c'] ?? 0);

            $pubCounts = $db->table('publication_authors pa')
                ->select('pa.author_email, COUNT(DISTINCT pa.publication_id) as c')
                ->join('publications p', 'p.id = pa.publication_id', 'inner')
                ->where('p.approve', 1)
                ->whereIn('pa.author_email', $emails)
                ->groupBy('pa.author_email')
                ->get()
                ->getResultArray();
            foreach ($pubCounts as $r) {
                $email = (string) ($r['author_email'] ?? '');
                if ($email === '') {
                    continue;
                }
                $pubCountByEmail[$email] = (int) ($r['c'] ?? 0);
            }
        }

        foreach ($teachers as &$t) {
            $email = (string) ($t['email'] ?? '');
            $t['publication_count'] = $email !== '' ? (int) ($pubCountByEmail[$email] ?? 0) : 0;
        }
        unset($t);

        return view('public/curriculum', [
            'faculties'        => $faculties,
            'curriculum'      => $curriculum,
            'teachers'        => $teachers,
            'publications'    => $publications,
            'publicationCount'=> $publicationCount,
        ]);
    }

    public function curriculumJson(int $id)
    {
        $db = \Config\Database::connect();
        $curriculumModel = new CurriculumModel();
        $userModel       = new UserModel();

        $curriculum = $curriculumModel->getDetailWithFacultyAndChair($id);
        if ($curriculum === null) {
            return $this->response->setStatusCode(404)->setJSON([
                'ok' => false,
                'message' => 'ไม่พบหลักสูตร',
            ]);
        }

        $chairEmail = (string) ($curriculum['chair_email'] ?? '');
        $teachers   = $userModel->getCurriculumResponsibleTeachers($id, $chairEmail !== '' ? $chairEmail : null, 8);

        $teacherEmails = $db->table('teacher_curriculum tc')
            ->select('tc.teacher_email')
            ->where('tc.curriculum_id', (int) $id)
            ->where('tc.status', 1)
            ->get()
            ->getResultArray();

        $emails = array_values(array_filter(array_map(
            static fn (array $r) => (string) ($r['teacher_email'] ?? ''),
            $teacherEmails
        )));

        $publications = [];
        $publicationCount = 0;
        $pubCountByEmail = [];
        if ($emails !== []) {
            $publications = $db->table('publications p')
                ->select('
                    p.id, p.title, p.publication_type, p.source, p.publication_year, p.publication_month, p.doi, p.created_at,
                    p.created_by_email, p.volume, p.issue, p.pages, p.publisher,
                    CONCAT(COALESCE(creator.thai_name, creator.gf_name, \'\'), \' \', COALESCE(creator.thai_lastname, creator.gl_name, \'\')) as created_by_name,
                    GROUP_CONCAT(pa.author_name ORDER BY pa.author_order SEPARATOR \', \') as authors,
                    GROUP_CONCAT(pa.author_email ORDER BY pa.author_order SEPARATOR \', \') as author_emails
                ')
                ->join('publication_authors pa', 'pa.publication_id = p.id', 'inner')
                ->join('user creator', 'creator.email = p.created_by_email', 'left')
                ->where('p.approve', 1)
                ->whereIn('pa.author_email', $emails)
                ->groupBy('p.id')
                ->orderBy('p.publication_year', 'DESC')
                ->orderBy('p.publication_month', 'DESC')
                ->orderBy('p.created_at', 'DESC')
                ->limit(80)
                ->get()
                ->getResultArray();

            $countRow = $db->table('publications p')
                ->select('COUNT(DISTINCT p.id) as c')
                ->join('publication_authors pa', 'pa.publication_id = p.id', 'inner')
                ->where('p.approve', 1)
                ->whereIn('pa.author_email', $emails)
                ->get()
                ->getRowArray();
            $publicationCount = (int) ($countRow['c'] ?? 0);

            $pubCounts = $db->table('publication_authors pa')
                ->select('pa.author_email, COUNT(DISTINCT pa.publication_id) as c')
                ->join('publications p', 'p.id = pa.publication_id', 'inner')
                ->where('p.approve', 1)
                ->whereIn('pa.author_email', $emails)
                ->groupBy('pa.author_email')
                ->get()
                ->getResultArray();
            foreach ($pubCounts as $r) {
                $email = (string) ($r['author_email'] ?? '');
                if ($email === '') {
                    continue;
                }
                $pubCountByEmail[$email] = (int) ($r['c'] ?? 0);
            }
        }

        foreach ($teachers as &$t) {
            $email = (string) ($t['email'] ?? '');
            $t['publication_count'] = $email !== '' ? (int) ($pubCountByEmail[$email] ?? 0) : 0;
        }
        unset($t);

        return $this->response->setJSON([
            'ok' => true,
            'curriculum' => [
                'id' => (int) ($curriculum['id'] ?? $id),
                'name' => (string) ($curriculum['name'] ?? ''),
                'code' => (string) ($curriculum['code'] ?? ''),
                'degree_level' => (string) ($curriculum['degree_level'] ?? ''),
                'faculty_id' => (int) ($curriculum['faculty_id'] ?? 0),
                'faculty_name' => (string) ($curriculum['faculty_name'] ?? ''),
            ],
            'teachers' => $teachers,
            'publicationCount' => $publicationCount,
            'publications' => $publications,
        ]);
    }
}

