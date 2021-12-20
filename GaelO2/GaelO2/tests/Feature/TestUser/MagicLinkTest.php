<?php

namespace Tests\Feature\TestUser;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;
use App\GaelO\Constants\Constants;
use App\Models\Patient;
use App\Models\Visit;
use Tests\AuthorizationTools;

class MagicLinkTest extends TestCase
{
    use DatabaseMigrations {
        runDatabaseMigrations as baseRunDatabaseMigrations;
    }

    public function runDatabaseMigrations()
    {
        $this->baseRunDatabaseMigrations();
        $this->artisan('db:seed');
    }

    /**
     * Test login with correct email password and valid account (password up to date)
     */
    public function testCreateMagicLinkForVisit()
    {
        $userId = AuthorizationTools::actAsAdmin(false);
        $visit = Visit::factory()->create();
        $studyName = $visit->patient->study_name;
        AuthorizationTools::addRoleToUser($userId, Constants::ROLE_SUPERVISOR, $studyName);
        $data = [
            'ressourceLevel' => 'visit',
            'ressourceId' => $visit->id
        ];
        $response = $this->json('POST', 'api/user/1/magic-link', $data)-> assertSuccessful();
        $response->assertSuccessful();
    }

    public function testCreateMagicLinkForPatient()
    {
        $userId = AuthorizationTools::actAsAdmin(false);
        $patient = Patient::factory()->create();
        $studyName = $patient->study_name;
        AuthorizationTools::addRoleToUser($userId, Constants::ROLE_SUPERVISOR, $studyName);
        $data = [
            'ressourceLevel' => 'patient',
            'ressourceId' => $patient->id
        ];
        $response = $this->json('POST', 'api/user/1/magic-link', $data)-> assertSuccessful();
        $response->assertSuccessful();
    }

    public function testCreateMagicLinkShouldFailWrongLevel()
    {
        $userId = AuthorizationTools::actAsAdmin(false);
        $patient = Patient::factory()->create();
        $studyName = $patient->study_name;
        AuthorizationTools::addRoleToUser($userId, Constants::ROLE_SUPERVISOR, $studyName);
        $data = [
            'ressourceLevel' => 'review',
            'ressourceId' => $patient->id
        ];
        $response = $this->json('POST', 'api/user/1/magic-link', $data);
        $response->assertStatus(400);
    }

    public function testCreateMagicLinkForVisitShouldFailNoRole()
    {
        AuthorizationTools::actAsAdmin(false);
        $visit = Visit::factory()->create();
        $data = [
            'ressourceLevel' => 'visit',
            'ressourceId' => $visit->id
        ];
        $response = $this->json('POST', 'api/user/1/magic-link', $data);
        $response->assertStatus(403);
    }



}
