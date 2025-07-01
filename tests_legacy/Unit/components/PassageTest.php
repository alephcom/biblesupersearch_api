<?php

use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;

use App\Passage;

class PassageTest extends TestCase
{

    public function testMoved()
    {
        $this->assertTrue(TRUE, 'Moved to tests/Feature/PassageTest.php');
    }
}
