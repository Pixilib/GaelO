<?php
namespace Tests\Feature;

use Tests\TestCase;

class RootTest extends TestCase
{
    public function testRootUrl()
    {
        $response = $this->get('/');
        $response->assertStatus(200);
    }

}
