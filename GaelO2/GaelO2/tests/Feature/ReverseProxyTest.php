<?php

namespace Tests\Feature;

use App\GaelO\Constants\Constants;
use App\Models\OrthancSeries;
use App\Models\OrthancStudy;
use App\Models\Patient;
use App\Models\ReviewStatus;
use App\Models\Study;
use Illuminate\Support\Facades\Artisan;
use Laravel\Passport\Passport;
use Tests\TestCase;
use App\Models\User;
use App\Models\Visit;
use App\Models\VisitGroup;
use App\Models\VisitType;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\AuthorizationTools;

class ReverseProxyTest extends TestCase
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

        $this->study = factory(Study::class)->create(['name' => 'test', 'patient_code_prefix' => 1234]);
        $this->visitGroup = factory(VisitGroup::class)->create(['study_name' => 'test']);
        $this->visitType = factory(VisitType::class)->create(['visit_group_id' => $this->visitGroup['id']]);
        $this->patient = factory(Patient::class)->create(['code' => 12341234123412, 'study_name' => 'test', 'center_code' => 0]);
        $this->visit = factory(Visit::class)->create(['creator_user_id' => 1,
        'patient_code' => $this->patient['code'],
        'visit_type_id' => $this->visitType['id'],
        'status_done' => 'Done',
        'upload_status'=> 'Done']);

        $this->reviewStatus = factory(ReviewStatus::class)->create([
            'visit_id' => $this->visit->id,
            'study_name'=> $this->study->name,
        ]);

        $this->orthancStudy = factory(OrthancStudy::class)->create([
            'visit_id' => $this->visit->id,
            'study_uid'=>'1.2.826.0.1.3680043.5.2014.6.4.10.27.08.20160309120853.168.11008',
            'uploader_id'=>1
        ]);

        $this->orthancSeries = factory(OrthancSeries::class)->create([
            'series_uid'=> '1.2.826.0.1.3680043.5.2014.6.4.10.27.08.20160309120853.168.11009',
            'orthanc_study_id' => $this->orthancStudy->orthanc_id
        ]);

    }


    protected function setUp() : void{
        parent::setUp();
        Artisan::call('passport:install');
        Passport::actingAs(
            User::where('id',1)->first()
        );

        if (true) {
            $this->markTestSkipped('all tests in this file are invactive, this is only to check orthanc communication');
        }
    }

    public function testDicomWebReverseProxyStudy()
    {
        AuthorizationTools::addRoleToUser(1, Constants::ROLE_SUPERVISOR, 'test');
        $response = $this->get('/api/orthanc/dicom-web/studies/1.2.826.0.1.3680043.5.2014.6.4.10.27.08.20160309120853.168.11008/series', ['Accept'=>'application/json', 'gaelorole'=>['Supervisor']]);
        $response->assertStatus(200);
    }

    public function testDicomWebReverseProxyStudyShouldBeForbiddenNoRole()
    {
        $response = $this->get('/api/orthanc/dicom-web/studies/1.2.826.0.1.3680043.5.2014.6.4.10.27.08.20160309120853.168.11008/series', ['Accept'=>'application/json', 'gaelorole'=>['Supervisor']]);
        $response->assertStatus(403);
    }

    public function testDicomWebReverseProxySeries(){
        AuthorizationTools::addRoleToUser(1, Constants::ROLE_SUPERVISOR, 'test');
        $response = $this->get('/api/orthanc/dicom-web/studies/1.2.826.0.1.3680043.5.2014.6.4.10.27.08.20160309120853.168.11008/series/1.2.826.0.1.3680043.5.2014.6.4.10.27.08.20160309120853.168.11009/metadata', ['Accept'=>'application/json', 'gaelorole'=>['Supervisor']]);
        $response->assertStatus(200);
    }

    public function testDicomWebReverseProxySeriesShouldBeForbiddenNoRole(){
        $response = $this->get('/api/orthanc/dicom-web/studies/1.2.826.0.1.3680043.5.2014.6.4.10.27.08.20160309120853.168.11008/series/1.2.826.0.1.3680043.5.2014.6.4.10.27.08.20160309120853.168.11009/metadata', ['Accept'=>'application/json', 'gaelorole'=>['Supervisor']]);
        $response->assertStatus(403);
    }

    public function testTusReverseProxy()
    {
        $response = $this->get('/api/tus');
        $response->assertStatus(200);
    }
}
