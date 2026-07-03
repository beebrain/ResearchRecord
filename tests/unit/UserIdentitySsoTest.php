<?php

namespace Tests\Unit;

use App\Libraries\UserIdentity;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * @internal
 */
final class UserIdentitySsoTest extends CIUnitTestCase
{
    public function testIsLiveUruEmail(): void
    {
        $this->assertTrue(UserIdentity::isLiveUruEmail('apichat.suk@live.uru.ac.th'));
        $this->assertFalse(UserIdentity::isLiveUruEmail('apichat.tg61@gmail.com'));
        $this->assertFalse(UserIdentity::isLiveUruEmail('staff@uru.ac.th'));
    }
}
