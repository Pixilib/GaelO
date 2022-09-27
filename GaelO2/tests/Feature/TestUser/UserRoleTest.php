<?php

namespace Tests\Feature\TestUser;

use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\AuthorizationTools;
use Tests\TestCase;

class UserRoleTest extends TestCase
{

    use RefreshDatabase;

    public function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed');
    }

    public function testGetUserRole()
    {
        $userId = AuthorizationTools::actAsAdmin(false);
        $this->role = Role::factory()->userId($userId)->validatedDocumentationVersion('2.0.0')->create();
        $answer = $this->json('GET', 'api/users/' . $this->role->user_id . '/studies/' . $this->role->study_name . '/roles/' . $this->role->name);
        $response = json_decode($answer->content(), true);
        $this->assertArrayHasKey('validatedDocumentationVersion', $response);
        $this->assertArrayHasKey('study', $response);
        $this->assertEquals('2.0.0', $response['validatedDocumentationVersion']);
    }

    public function testModifyUserRoleValidatedDocumentation()
    {
        $userId = AuthorizationTools::actAsAdmin(false);
        $this->role = Role::factory()->userId($userId)->validatedDocumentationVersion('2.0.0')->create();
        $answer = $this->json('PUT', 'api/users/' . $this->role->user_id . '/studies/' . $this->role->study_name . '/roles/' . $this->role->name . '/validated-documentation', ['version' => '5.0.0']);
        $answer->assertSuccessful();
    }
}
