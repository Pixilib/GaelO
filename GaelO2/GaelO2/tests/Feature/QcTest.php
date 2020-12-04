<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Artisan;
use Laravel\Passport\Passport;
use Tests\TestCase;
use App\User;

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
        $this->visitType = factory(VisitType::class)->create(['visit_group_id' => $this->visitGroup['id']]);
        $this->patient = factory(Patient::class)->create(['code' => 12341234123412, 'study_name' => 'test', 'center_code' => 0]);

        $this->visit = factory(Visit::class)->create([
            'creator_user_id' => 1,
            'patient_code' => $this->patient->code,
            'visit_type_id' => $this->visitType->id
        ]);
    }

    public function testQc()
    {

        $payload = [
            'stateQc'=>'Accepted',
            'imageQc'=>true,
            'formQc'=>false,
            'imageQcComment'=>'OK',
            'formQcComment'=>'non'
        ];

        $response = $this->patch('/api/visits/'.$this->visit->id.'/quality-control', $payload);
        dd($response);
        $response->assertStatus(200);
    }
}
