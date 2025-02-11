<?php

namespace Tests\Feature\TestExportDb;

use App\GaelO\Adapters\DatabaseDumperAdapter;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Mockery\MockInterface;
use Tests\AuthorizationTools;

class ExportDbTest extends TestCase
{
    use RefreshDatabase;
    private MockInterface $dbDumperSpy;

    protected function setUp() : void {
        parent::setUp();
        $this->artisan('db:seed');
        $this->dbDumperSpy = $this->spy(DatabaseDumperAdapter::class);
        app()->instance(DatabaseDumperAdapter::class, $this->dbDumperSpy);
    }

    public function testExportDb()
    {
        Storage::fake();
        Storage::spy();
        Storage::shouldReceive('allFiles')
        ->once()
        ->andReturn([]);
        AuthorizationTools::actAsAdmin(true);
        $response = $this->get('/api/export-db');
        $response->assertStatus(200);
        ob_start();
        $response->sendContent();
        ob_get_clean();
        $this->dbDumperSpy->shouldHaveReceived('createDatabaseDumpFile')->once();
    }

    public function testExportDbShouldBeForbiddenNotAdmin()
    {
        AuthorizationTools::actAsAdmin(false);
        $response = $this->get('/api/export-db');
        $response->assertStatus(403);
    }
}
