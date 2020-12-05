<?php

namespace Tests\Feature;

use App\GaelO\Constants\Constants;
use App\Patient;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Artisan;
use Laravel\Passport\Passport;
use Tests\TestCase;
use App\User;
use App\Study;
use App\Visit;
use App\VisitGroup;
use App\VisitType;
use App\Review;
use Tests\AuthorizationTools;

class QcTest extends TestCase
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

    protected function setUp() : void {
        parent::setUp();

        Artisan::call('passport:install');
        Passport::actingAs(
            User::where('id',1)->first()
        );

        $this->study = factory(Study::class)->create(['name' => 'test', 'patient_code_prefix' => 1234]);
        $this->visitGroup = factory(VisitGroup::class)->create(['study_name' => 'test']);
        $this->visitType = factory(VisitType::class)->create(['local_form_needed'=> true, 'visit_group_id' => $this->visitGroup['id']]);
        $this->patient = factory(Patient::class)->create(['code' => 12341234123412, 'study_name' => 'test', 'center_code' => 0]);

        $this->visit = factory(Visit::class)->create([
            'creator_user_id' => 1,
            'upload_status' => Constants::UPLOAD_STATUS_DONE,
            'patient_code' => $this->patient->code,
            'visit_type_id' => $this->visitType->id,
            'state_quality_control'=> Constants::QUALITY_CONTROL_NOT_DONE
        ]);
    }

    public function testQc()
    {
        AuthorizationTools::addRoleToUser(1, Constants::ROLE_CONTROLER, $this->study->name);

        $payload = [
            'stateQc'=>Constants::QUALITY_CONTROL_ACCEPTED,
            'imageQc'=>true,
            'formQc'=>true
        ];

        $response = $this->patch('/api/visits/'.$this->visit->id.'/quality-control', $payload);
        $response->assertStatus(200);

    }

    public function testQcForbiddenNotRole(){
        $payload = [
            'stateQc'=>Constants::QUALITY_CONTROL_ACCEPTED,
            'imageQc'=>true,
            'formQc'=>true,
            'imageQcComment'=>'OK',
            'formQcComment'=>'non'
        ];

        $response = $this->patch('/api/visits/'.$this->visit->id.'/quality-control', $payload);
        $response->assertStatus(403);

    }

    public function testQcForbiddenNotUploaded(){
        $this->visit->upload_status = Constants::UPLOAD_STATUS_NOT_DONE;
        $this->visit->save();
        AuthorizationTools::addRoleToUser(1, Constants::ROLE_CONTROLER, $this->study->name);
        $payload = [
            'stateQc'=>Constants::QUALITY_CONTROL_ACCEPTED,
            'imageQc'=>true,
            'formQc'=>true,
            'imageQcComment'=>'OK',
            'formQcComment'=>'non'
        ];

        $response = $this->patch('/api/visits/'.$this->visit->id.'/quality-control', $payload);
        $response->assertStatus(403);

    }

    public function testQcForbiddenQcAlreadyDone(){

        $this->visit->state_quality_control = Constants::QUALITY_CONSTROL_REFUSED;
        $this->visit->save();
        AuthorizationTools::addRoleToUser(1, Constants::ROLE_CONTROLER, $this->study->name);
        $payload = [
            'stateQc'=>Constants::QUALITY_CONTROL_ACCEPTED,
            'imageQc'=>true,
            'formQc'=>true,
            'imageQcComment'=>'OK',
            'formQcComment'=>'non'
        ];

        $response = $this->patch('/api/visits/'.$this->visit->id.'/quality-control', $payload);
        $response->assertStatus(403);

    }

    public function testQcCorrectiveActionUnlockLocalForm(){

        $review = factory(Review::class)->create([
            'visit_id' => $this->visit->id,
            'study_name' => $this->study->name,
            'local'=>true,
            'user_id'=>1,
            'validated'=>true
        ]);

        AuthorizationTools::addRoleToUser(1, Constants::ROLE_CONTROLER, $this->study->name);
        $payload = [
            'stateQc'=> Constants::QUALITY_CONTROL_CORRECTIVE_ACTION_ASKED ,
            'imageQc'=>true,
            'formQc'=>false,
            'imageQcComment'=>'OK',
            'formQcComment'=>'non'
        ];

        $response = $this->patch('/api/visits/'.$this->visit->id.'/quality-control', $payload);
        $response->assertStatus(200);
    }

    public function testQcAcceptedWithNoAcceptedItemShouldFail(){


        AuthorizationTools::addRoleToUser(1, Constants::ROLE_CONTROLER, $this->study->name);
        $payload = [
            'stateQc'=> Constants::QUALITY_CONTROL_ACCEPTED ,
            'imageQc'=>true,
            'formQc'=>false,
            'imageQcComment'=>'OK',
            'formQcComment'=>'non'
        ];

        $response = $this->patch('/api/visits/'.$this->visit->id.'/quality-control', $payload);
        $response->assertStatus(400);

    }

    public function testQCImageRefusedReasonShouldBeSpecified(){

        AuthorizationTools::addRoleToUser(1, Constants::ROLE_CONTROLER, $this->study->name);

        $payload = [
            'stateQc'=> Constants::QUALITY_CONTROL_ACCEPTED ,
            'imageQc'=>false,
            'formQc'=>true,
            'formQcComment'=>'non'
        ];

        $response = $this->patch('/api/visits/'.$this->visit->id.'/quality-control', $payload);
        $response->assertStatus(400);

    }

    public function testQCFormRefusedReasonShouldBeSpecified(){

        AuthorizationTools::addRoleToUser(1, Constants::ROLE_CONTROLER, $this->study->name);
        $payload = [
            'stateQc'=> Constants::QUALITY_CONTROL_ACCEPTED ,
            'imageQc'=>true,
            'formQc'=>false,
            'imageQcComment'=>'OK'
        ];

        $response = $this->patch('/api/visits/'.$this->visit->id.'/quality-control', $payload);
        $response->assertStatus(400);


    }

    public function testResetQc()
    {
        AuthorizationTools::addRoleToUser(1, Constants::ROLE_CONTROLER, $this->study->name);

        $payload = [];

        //$response = $this->patch('/api/visits/'.$this->visit->id.'/quality-control/reset', $payload);
        //$response->assertStatus(200);

    }

    public function testResetQcShouldFailNoRole()
    {
        $payload = [];

        //$this->patch('/api/visits/'.$this->visit->id.'/quality-control/reset', $payload)->assertStatus(403);

    }

    public function testResetQcShouldFailReviewStatusNotNotDone()
    {
        //SK FAIRE REVIEW STATUS TO "Ongoing" et le test devrait envoyer un forbidden
        $payload = [];

        //$this->patch('/api/visits/'.$this->visit->id.'/quality-control/reset', $payload)->assertStatus(403);

    }
}
