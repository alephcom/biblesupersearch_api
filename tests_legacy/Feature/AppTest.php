<?php

use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Engine;

class AppTest extends TestCase {

    public function testPremiumDisableConfig() {
        $this->assertTrue(TRUE);
        $env = config('app.env');

        if($env == 'production') {
            $this->assertFalse( config('app.premium_disabled'), 'Config app.premium_disabled must be FALSE in production');
        }
    }

}
