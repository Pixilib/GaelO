<?php

namespace Tests\Feature\TestExportDb;

use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\AuthorizationTools;

class ExportDbTest extends TestCase
{
    use DatabaseMigrations {
        runDatabaseMigrations as baseRunDatabaseMigrations;
    }

    public function runDatabaseMigrations()
    {
        $this->baseRunDatabaseMigrations();
        $this->artisan('db:seed');

    }

    protected function setUp() : void{
        parent::setUp();
        Artisan::call('passport:install');

    }

    public function testExportDb()
    {
        AuthorizationTools::actAsAdmin(true);
        $response = $this->post('/api/export-db', []);
        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/zip');
    }

    public function testExportDbShouldBeForbiddenNotAdmin()
    {
        AuthorizationTools::actAsAdmin(false);
        $response = $this->post('/api/export-db', []);
        $response->assertStatus(403);
    }
}
