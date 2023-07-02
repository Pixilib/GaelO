<?php
namespace Tests\Feature;

use App\GaelO\Services\OrthancService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;
use Tests\TestCase;

class ProbeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp() : void {
        parent::setUp();
        $this->artisan('db:seed');

        $orthancServiceMock = $this->partialMock(OrthancService::class, function (MockInterface $mock) {
            $mock->shouldReceive('setOrthancServer')->andReturn(null);
            $mock->shouldReceive('getSystem')->andReturn(['Salim']);
        });
        app()->instance(OrthancService::class, $orthancServiceMock);
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