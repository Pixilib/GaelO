<?php

namespace Tests\Feature;

use App\GaelO\Constants\Constants;
use App\Model\Patient;
use App\Model\ReviewStatus;
use App\Model\Study;
use App\Model\User;
use App\Model\Visit;
use App\Model\VisitGroup;
use App\Model\VisitType;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Artisan;
use Laravel\Passport\Passport;
use Tests\AuthorizationTools;
use Tests\TestCase;

class GetPossibleUploadTest extends TestCase
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


    protected function setUp() : void{
        parent::setUp();
        Artisan::call('passport:install');
        Passport::actingAs(
            User::where('id',1)->first()
        );

        $this->study = factory(Study::class)->create();
        $visitGroup = factory(VisitGroup::class)->create(['study_name' => $this->study->name, 'modality' => 'CT']);
        $visitType = factory(VisitType::class)->create(['visit_group_id' => $visitGroup['id']]);
        $patient = factory(Patient::class)->create(['code' => 12341234123412, 'study_name' => $this->study->name, 'center_code' => 0]);
        factory(Visit::class)->create(['creator_user_id' => 1,
        'patient_code' => $patient['code'],
        'visit_type_id' => $visitType['id'],
        'upload_status'=>'Not Done',
        'status_done' => 'Done']);
    }

    public function testGetPossibleUpload()
    {
        AuthorizationTools::addRoleToUser(1, Constants::ROLE_INVESTIGATOR, $this->study->name);

        $response = $this->get('api/studies/'.$this->study->name.'/possible-uploads');
        $response->assertStatus(200);
    }

    public function testGetPossibleUploadFailNoRole()
    {
        $response = $this->get('api/studies/'.$this->study->name.'/possible-uploads');
        $response->assertStatus(403);
    }
}
