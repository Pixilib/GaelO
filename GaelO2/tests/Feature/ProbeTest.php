<?php
namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProbeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp() : void {
        parent::setUp();
        $this->artisan('db:seed');
    }

    public function testLiveness()
    {
        $response = $this->get('api/liveness');
        $response->assertStatus(200);
    }

    public function testReadiness()
    {
        $response = $this->get('api/readiness');
        $response->assertStatus(200);
    }

}