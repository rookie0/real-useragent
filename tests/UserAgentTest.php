<?php

namespace Rookie0\RealUserAgent\Tests;

use PHPUnit\Framework\TestCase;
use Rookie0\RealUserAgent\UserAgent;

class UserAgentTest extends TestCase
{

    protected static $ua;

    public function setUp()
    {
        self::$ua = new UserAgent();
    }

    public function testRandom()
    {
        $this->assertNotFalse(self::$ua->random());
    }

    public function testProperties()
    {
        $this->assertStringContainsString('(KHTML, like Gecko) Chrome/', self::$ua->chrome);
        $this->assertStringContainsString(' Firefox/', self::$ua->firefox);
        $this->assertStringContainsString(' MicroMessenger/', self::$ua->wechat);
    }

    public function testMethods()
    {
        $this->assertStringContainsString('(KHTML, like Gecko) Chrome/', self::$ua->chrome());
        $this->assertStringContainsString('(KHTML, like Gecko) Chrome/60.', self::$ua->chrome(['software_version' => '60']));

        $this->assertStringContainsString(' Firefox/', self::$ua->firefox());
        $this->assertStringContainsString('(Macintosh; Intel Mac OS X', self::$ua->firefox(['operating_system' => 'Mac OS X']));

        $this->assertStringContainsString(' MicroMessenger/', self::$ua->wechat());
        $this->assertStringContainsString('Windows NT', self::$ua->wechat(['hardware_type' => 'Computer']));
    }

}