<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Testing\TestResponse;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    public function assertForbiddenAncillariesStudy(TestResponse $answer){
        $content = $answer->json();
        $answer->assertStatus(403);
        $this->assertTrue(str_contains($content['errorMessage'], 'Ancillaries'));
    }
}
