<?php

namespace Tests\Feature\TestExportDb;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\AuthorizationTools;

class ExportDbTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp() : void {
        parent::setUp();
        $this->artisan('db:seed');
    }

    public function testExportDb()
    {
        AuthorizationTools::actAsAdmin(true);
        $response = $this->get('/api/export-db', []);
        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/zip');
    }

    public function testExportDbShouldBeForbiddenNotAdmin()
    {
        AuthorizationTools::actAsAdmin(false);
        $response = $this->get('/api/export-db', []);
        $response->assertStatus(403);
    }
}
