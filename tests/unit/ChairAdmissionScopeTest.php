<?php

namespace Tests\Unit;

use App\Models\StudentAdmissionFormModel;
use App\Helpers\RoleHelper;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * @internal
 */
final class ChairAdmissionScopeTest extends CIUnitTestCase
{
    public function testGetFormsByCurriculaReturnsEmptyForEmptyInput(): void
    {
        $model = new StudentAdmissionFormModel();
        $this->assertSame([], $model->getFormsByCurricula([]));
    }

    public function testGetStatisticsByCurriculaReturnsZerosForEmptyInput(): void
    {
        $model = new StudentAdmissionFormModel();
        $stats = $model->getStatistics(null, null, []);

        $this->assertSame(0, (int) ($stats['total'] ?? -1));
        $this->assertSame(0, (int) ($stats['draft_count'] ?? -1));
    }

    public function testGetChairCurriculaUsesChairEmail(): void
    {
        if (getenv('INTEGRATION_DB') !== '1') {
            $this->markTestSkipped('Local MySQL not available in PHPUnit (use INTEGRATION_DB=1)');
        }

        try {
            $db = \Config\Database::connect('default');
            $row = $db->table('curriculum')
                ->select('chair_email')
                ->where('chair_email IS NOT NULL')
                ->where('chair_email !=', '')
                ->limit(1)
                ->get()
                ->getRowArray();
        } catch (\Throwable) {
            $this->markTestSkipped('Local MySQL not available in PHPUnit (use INTEGRATION_DB=1)');
        }

        if ($row === null) {
            $this->markTestSkipped('No curriculum with chair_email in local database');
        }

        $email = (string) $row['chair_email'];
        $ids   = RoleHelper::getChairCurricula(['email' => $email]);

        $this->assertNotEmpty($ids);
    }
}
