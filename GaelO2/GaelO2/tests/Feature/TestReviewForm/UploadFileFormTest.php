<?php

use App\Models\Review;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\AuthorizationTools;
use Tests\TestCase;

class UploadFileFormTest extends TestCase
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

    }

    public function testUploadFile(){

        $currentUserId = AuthorizationTools::actAsAdmin(false);
        $reviewId = Review::factory()->userId($currentUserId)->create();
        $response = $this->post('api/reviews/'.$reviewId->id.'/file/41', [base64_encode ("testFileContent")], ['CONTENT_TYPE'=>'application/csv']);
        dd($response);

    }
}
