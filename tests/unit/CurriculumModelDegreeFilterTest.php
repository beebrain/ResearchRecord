<?php

namespace Tests\Unit;

use App\Models\CurriculumModel;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * @internal
 */
final class CurriculumModelDegreeFilterTest extends CIUnitTestCase
{
    public function testNormalizeDegreeLevelFilter(): void
    {
        $this->assertSame('master', CurriculumModel::normalizeDegreeLevelFilter('master'));
        $this->assertSame('master', CurriculumModel::normalizeDegreeLevelFilter('ป.โท'));
        $this->assertSame('doctoral', CurriculumModel::normalizeDegreeLevelFilter('doctoral'));
        $this->assertSame('doctoral', CurriculumModel::normalizeDegreeLevelFilter('ป.เอก'));
        $this->assertNull(CurriculumModel::normalizeDegreeLevelFilter('invalid'));
        $this->assertNull(CurriculumModel::normalizeDegreeLevelFilter(null));
    }

    public function testDegreeLevelLabelTh(): void
    {
        $this->assertSame('ป.โท', CurriculumModel::degreeLevelLabelTh('master'));
        $this->assertSame('ป.เอก', CurriculumModel::degreeLevelLabelTh('doctoral'));
    }
}
