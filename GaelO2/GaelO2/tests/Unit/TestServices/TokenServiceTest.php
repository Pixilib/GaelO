<?php

namespace Tests\Unit\TestServices;

use App\GaelO\Services\GaelOProcessingService\AzureTokenService;
use Tests\TestCase;
use Illuminate\Support\Facades\App;

class TokenServiceTest extends TestCase
{
    private AzureTokenService $azureTokenService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->azureTokenService = App::make(AzureTokenService::class);
        $this->markTestSkipped();
    }

    public function testGetToken()
    {
        $res = $this->azureTokenService->getToken();
        $this->assertNotNull($res);
    }

    public function testSameToken()
    {
        $res1 = $this->azureTokenService->getToken();
        $res2 = $this->azureTokenService->getToken();
        $this->assertEquals($res1, $res2);
    }
}
