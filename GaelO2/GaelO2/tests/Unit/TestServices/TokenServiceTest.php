<?php

namespace Tests\Unit\TestServices;

use Tests\TestCase;
use App\GaelO\Services\TokenService;
use Illuminate\Support\Facades\App;

class TokenServiceTest extends TestCase
{
    private TokenService $tokenService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tokenService = App::make(TokenService::class);
        $this->markTestSkipped();
    }

    public function testGetToken()
    {
        $res = $this->tokenService->getToken();
        $this->assertNotNull($res);
    }

    public function testSameToken()
    {
        $res1 = $this->tokenService->getToken();
        $res2 = $this->tokenService->getToken();
        $this->assertEquals($res1, $res2);
    }
}
