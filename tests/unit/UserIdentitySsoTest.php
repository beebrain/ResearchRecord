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

    public function testUserHasCompleteThaiName(): void
    {
        $this->assertTrue(UserIdentity::userHasCompleteThaiName([
            'thai_name' => 'วาสนา',
            'thai_lastname' => 'บุณยมณี',
        ]));
        $this->assertFalse(UserIdentity::userHasCompleteThaiName([
            'thai_name' => 'tassanee',
            'thai_lastname' => 'raya',
            'profile_customer' => 'newscience_sso',
        ]));
        $this->assertTrue(UserIdentity::userHasCompleteThaiName([
            'thai_name' => 'Min',
            'thai_lastname' => 'Xiao',
            'profile_customer' => UserIdentity::PROFILE_SSO_NAME_OK,
        ]));
        $this->assertFalse(UserIdentity::userHasCompleteThaiName([
            'thai_name' => 'Min',
            'thai_lastname' => 'Xiao',
            'profile_customer' => 'newscience_sso',
        ]));
        $this->assertFalse(UserIdentity::userHasCompleteThaiName([
            'thai_name' => 'ทัศนีย์',
            'thai_lastname' => '',
        ]));
    }

    public function testIsAcceptableLegalNamePart(): void
    {
        $this->assertTrue(UserIdentity::isAcceptableLegalNamePart('ทัศนีย์'));
        $this->assertTrue(UserIdentity::isAcceptableLegalNamePart('Min'));
        $this->assertFalse(UserIdentity::isAcceptableLegalNamePart('X'));
        $this->assertFalse(UserIdentity::isAcceptableLegalNamePart('123'));
    }
}
