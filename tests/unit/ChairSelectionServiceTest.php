<?php

namespace Tests\Unit;

use App\Services\ChairSelectionService;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;

/**
 * @internal
 */
final class ChairSelectionServiceTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    private ChairSelectionService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ChairSelectionService();
    }

    public function testFormatTeacherDisplayNameUsesThaiNameAndTitle(): void
    {
        $name = $this->service->formatTeacherDisplayName([
            'titleThai'    => 'ผศ.ดร.',
            'thai_name'    => 'สมชาย',
            'thai_lastname'=> 'ใจดี',
            'email'        => 'somchai@test.ac.th',
        ]);

        $this->assertSame('ผศ.ดร. สมชาย ใจดี', $name);
    }

    public function testFormatTeacherDisplayNameUsesEnglishNameWhenThaiMissing(): void
    {
        $name = $this->service->formatTeacherDisplayName([
            'title'   => 'Dr.',
            'gf_name' => 'Somchai',
            'gl_name' => 'Jaidee',
            'email'   => 'somchai@test.ac.th',
        ]);

        $this->assertSame('Dr. Somchai Jaidee', $name);
    }

    public function testFormatTeacherDisplayNameFallsBackToEmail(): void
    {
        $name = $this->service->formatTeacherDisplayName([
            'email' => 'only-email@test.ac.th',
        ]);

        $this->assertSame('only-email@test.ac.th', $name);
    }

    public function testGetChairSelectionConflictsReturnsFalseForEmptyEmail(): void
    {
        $result = $this->service->getChairSelectionConflicts('', 1);

        $this->assertFalse($result['has_conflict']);
        $this->assertSame([], $result['warnings']);
    }

    public function testSearchTeachersReturnsEmptyWhenQueryTooShort(): void
    {
        $this->assertSame([], $this->service->searchTeachers('ก', 1));
        $this->assertSame([], $this->service->searchTeachers(' ', 1));
    }

    public function testSearchTeachersReturnsEmptyForInvalidCurriculumId(): void
    {
        $this->assertSame([], $this->service->searchTeachers('test', 0));
    }

    public function testSearchTeachersReturnsTeachersFromDatabase(): void
    {
        $curriculumId = $this->firstActiveCurriculumId();
        if ($curriculumId === null) {
            $this->markTestSkipped('Local MySQL not available in PHPUnit (use INTEGRATION_DB=1)');
        }

        $results = $this->service->searchTeachers('ส', $curriculumId);
        $this->assertIsArray($results);

        if ($results !== []) {
            $first = $results[0];
            $this->assertArrayHasKey('email', $first);
            $this->assertArrayHasKey('name', $first);
            $this->assertArrayHasKey('has_conflict', $first);
            $this->assertArrayHasKey('role_badges', $first);
        }
    }

    public function testSearchApiRequiresCurriculumId(): void
    {
        $result = $this->withSession([
            'logged_in' => true,
            'god_mode'  => true,
            'user_data' => ['role' => 'super_admin'],
        ])->post('admin/searchTeachersForChairSelection', [
            'q' => 'test',
        ]);

        $result->assertStatus(200);
        $json = json_decode($result->getJSON(), true);
        $this->assertFalse($json['success']);
    }

    public function testSearchApiReturnsEmptyForShortQuery(): void
    {
        $curriculumId = $this->firstActiveCurriculumId();
        if ($curriculumId === null) {
            $this->markTestSkipped('Local MySQL not available in PHPUnit (use INTEGRATION_DB=1)');
        }

        $result = $this->withSession([
            'logged_in' => true,
            'god_mode'  => true,
            'user_data' => ['role' => 'super_admin'],
        ])->post('admin/searchTeachersForChairSelection', [
            'q'             => 'ก',
            'curriculum_id' => $curriculumId,
        ]);

        $result->assertStatus(200);
        $json = json_decode($result->getJSON(), true);
        $this->assertTrue($json['success']);
        $this->assertSame([], $json['data']);
    }

    public function testSearchApiFindsTeachersWithTwoCharQuery(): void
    {
        $curriculumId = $this->firstActiveCurriculumId();
        if ($curriculumId === null) {
            $this->markTestSkipped('Local MySQL not available in PHPUnit (use INTEGRATION_DB=1)');
        }

        $result = $this->withSession([
            'logged_in' => true,
            'god_mode'  => true,
            'user_data' => ['role' => 'super_admin'],
        ])->post('admin/searchTeachersForChairSelection', [
            'q'             => 'สม',
            'curriculum_id' => $curriculumId,
        ]);

        $result->assertStatus(200);
        $json = json_decode($result->getJSON(), true);
        $this->assertTrue($json['success']);
        $this->assertIsArray($json['data']);
    }

    public function testSetCurriculumChairRequiresConfirmationWhenConflictExists(): void
    {
        $curriculumId = $this->firstActiveCurriculumId();
        if ($curriculumId === null) {
            $this->markTestSkipped('Local MySQL not available in PHPUnit (use INTEGRATION_DB=1)');
        }

        $search = $this->service->searchTeachers('สม', $curriculumId);
        $conflictTeacher = null;
        foreach ($search as $row) {
            if (! empty($row['has_conflict'])) {
                $conflictTeacher = $row;
                break;
            }
        }

        if ($conflictTeacher === null) {
            $this->markTestSkipped('No teacher with cross-curriculum conflict found (INTEGRATION_DB=1)');
        }

        $result = $this->withSession([
            'logged_in' => true,
            'god_mode'  => true,
            'user_data' => ['role' => 'super_admin'],
        ])->withHeaders([
            'X-Requested-With' => 'XMLHttpRequest',
        ])->withBodyFormat('json')->post('admin/setCurriculumChair', [
            'curriculum_id' => $curriculumId,
            'chair_email'   => $conflictTeacher['email'],
        ]);

        $result->assertStatus(200);
        $json = json_decode($result->getJSON(), true);
        $this->assertFalse($json['success']);
        $this->assertTrue($json['requires_confirmation'] ?? false);
        $this->assertNotEmpty($json['warnings']);
    }

    private function firstActiveCurriculumId(): ?int
    {
        if (getenv('INTEGRATION_DB') !== '1') {
            return null;
        }

        try {
            $db = \Config\Database::connect('default');
            if (! $db->tableExists('curriculum')) {
                return null;
            }

            $row = $db->table('curriculum')->select('id')->where('status', 1)->limit(1)->get()->getRowArray();

            return $row ? (int) $row['id'] : null;
        } catch (\Throwable) {
            return null;
        }
    }
}
