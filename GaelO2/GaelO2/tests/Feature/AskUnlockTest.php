<?php

namespace Tests\Feature;

use App\GaelO\Constants\Constants;
use App\Patient;
use App\Study;
use App\User;
use App\VisitGroup;
use App\VisitType;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Artisan;
use Laravel\Passport\Passport;
use Tests\TestCase;
use App\Visit;
use Tests\AuthorizationTools;

class AskUnlockTest extends TestCase
{
    use DatabaseMigrations {
        runDatabaseMigrations as baseRunDatabaseMigrations;
    }

    public function runDatabaseMigrations()
    {
        $this->baseRunDatabaseMigrations();
        $this->artisan('db:seed');
    }

    protected function setUp(): void
    {
        parent::setUp();

        Artisan::call('passport:install');
        Passport::actingAs(
            User::where('id', 1)->first()
        );

        $this->study = factory(Study::class)->create(['patient_code_prefix' => 1234]);
        $this->visitGroup = factory(VisitGroup::class)->create(['study_name' => $this->study->name]);
        $this->visitType = factory(VisitType::class)->create(['visit_group_id' => $this->visitGroup->id]);
        $this->patient = factory(Patient::class)->create(['code' => 12341234123412, 'study_name' => $this->study->name, 'center_code' => 0]);
        $this->visit = factory(Visit::class)->create([
            'creator_user_id' => 1,
            'patient_code' => $this->patient->code,
            'visit_type_id' => $this->visitType->id,
            'status_done' => 'Done'
        ]);
    }

    public function testAskUnlockInvestigator()
    {
        AuthorizationTools::addRoleToUser(1, Constants::ROLE_INVESTIGATOR, $this->study->name);
        $payload = [
            'message' => 'My Message'
        ];
        $response = $this->post('api/studies/' . $this->study->name . '/visits/' . $this->visit->id . '/ask-unlock?role=Investigator', $payload);
        $response->assertStatus(200);
    }

    public function testAskUnlockSupervisor()
    {
        AuthorizationTools::addRoleToUser(1, Constants::ROLE_SUPERVISOR, $this->study->name);
        $payload = [
            'message' => 'My Message'
        ];
        $response = $this->post('api/studies/' . $this->study->name . '/visits/' . $this->visit->id . '/ask-unlock?role=Supervisor', $payload);
        $response->assertStatus(200);
    }

    public function testAskUnlockFailBecauseMessageEmpty()
    {
        AuthorizationTools::addRoleToUser(1, Constants::ROLE_INVESTIGATOR, $this->study->name);
        $response = $this->post('api/studies/' . $this->study->name . '/visits/' . $this->visit->id . '/ask-unlock?role=Investigator', []);
        $response->assertStatus(400);
    }

    public function testAskUnlockShouldFailNoRole()
    {
        $payload = [
            'message' => 'My Message'
        ];
        $response = $this->post('api/studies/' . $this->study->name . '/visits/' . $this->visit->id . '/ask-unlock?role=Investigator', $payload);
        $response->assertStatus(403);
    }
}
