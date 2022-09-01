<?php

namespace Tests\Feature\TestDeleteCommand;

use App\GaelO\Constants\Constants;
use App\Models\DicomSeries;
use App\Models\DicomStudy;
use App\Models\Review;
use App\Models\ReviewStatus;
use App\Models\Visit;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\AuthorizationTools;
use Tests\TestCase;

class DeleteCommandTest extends TestCase
{

    use DatabaseMigrations {
        runDatabaseMigrations as baseRunDatabaseMigrations;
    }

    /**
     * Define hooks to migrate the database before and after each test.
     *
     * @return void
     */
    public function runDatabaseMigrations()
    {
        $this->baseRunDatabaseMigrations();
        $this->artisan('db:seed');
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->visit = Visit::factory()->create();
    }

    public function testDeleteCommandShouldFailStudyNotDeleted()
    {
        $studyName = $this->visit->patient->study_name;

        $this->artisan('study:delete '.$studyName)->expectsQuestion('Warning : Please confirm study Name', $studyName)
        ->expectsOutput('Study is not soft deleted, terminating');
        
    }

    public function testDeleteCommand()
    {
        $this->visit->patient->study->delete();
        $studyName = $this->visit->patient->study_name;

        /*
        $this->artisan('study:delete '.$studyName)->expectsQuestion('Warning : Please confirm study Name', $studyName)
        ->expectsQuestion('Warning : This CANNOT be undone, do you wish to continue?', "\r\n")
        ->expectsOutput('The command was successful!');
        */
        
    }

}