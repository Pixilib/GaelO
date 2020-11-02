<?php

namespace Tests\Unit;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Artisan;
use Laravel\Passport\Passport;
use Tests\TestCase;
use App\User;
use App\Study;
use App\VisitGroup;
use App\VisitType;
use App\Patient;
use App\Visit;

class VisitServiceTest extends TestCase
{

    use DatabaseMigrations {
        runDatabaseMigrations as baseRunDatabaseMigrations;
    }

    use RefreshDatabase;

    public function runDatabaseMigrations()
    {
        $this->baseRunDatabaseMigrations();
        $this->artisan('db:seed');
    }


    protected function setUp() : void {
        parent::setUp();

        Artisan::call('passport:install');
        Passport::actingAs(
            User::where('id',1)->first()
        );

        $this->study = factory(Study::class)->create(['name' => 'test', 'patient_code_prefix' => 1234]);
        $this->visitGroup = factory(VisitGroup::class)->create(['study_name' => 'test']);
        $this->visitType = factory(VisitType::class)->create(
            ['visit_group_id' => $this->visitGroup['id'],
            'local_form_needed'=>true,
            'qc_needed'=>true]
        );
        $this->patient = factory(Patient::class)->create(['code' => 12341234123412, 'study_name' => 'test', 'center_code' => 0]);
        $this->visit = factory(Visit::class)->create(
            [
                'creator_user_id' => 1,
                'patient_code' => $this->patient['code'],
                'visit_type_id' => $this->visitType['id'],
                'status_done' => 'Done',
            ]
        );
    }


    public function testUpdateUploadStatus()
    {
       $visitService  = App::Make(\App\GaelO\Services\VisitService::class);
       dd($visitService->updateUploadStatus( $this->visit['id'], 'Done', 'salim.kanoun@gmail.com'));
    }
}
