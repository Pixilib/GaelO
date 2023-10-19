<?php

namespace Tests\Feature\TestStudy;

use App\GaelO\Adapters\FrameworkAdapter;
use App\GaelO\Constants\Constants;
use App\GaelO\Services\GaelOStudiesService\CreatablePatient\CreatablePatient;
use App\GaelO\Services\SpecificStudiesRules\TEST\TEST;
use App\Models\Study;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;
use Tests\AuthorizationTools;

class GetCreatablePatientsTest extends TestCase
{

    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed');
        Study::factory()->name('TEST')->create();

        $mockTestStudy = $this->partialMock(TEST::class, function (MockInterface $mock) {
            $mock->shouldReceive('getCreatablePatientsCode')
                ->andReturn([new CreatablePatient('1234', 0, 'Included')]);
        });

        $mockFramework = $this->partialMock(FrameworkAdapter::class, function (MockInterface $mock) use ($mockTestStudy) {
            $mock->shouldReceive('make')
                ->andReturn($mockTestStudy);
        });


        $this->instance(FrameworkAdapter::class, $mockFramework);
    }

    public function testGetCreatablePatient()
    {

        $userId = AuthorizationTools::actAsAdmin(true);
        AuthorizationTools::addRoleToUser($userId, Constants::ROLE_INVESTIGATOR, "TEST");
        $answer = $this->json('GET', '/api/studies/TEST/creatable-patients?role=Investigator');
        $answer->assertSuccessful();
    }
}
