<?php

namespace Tests\Unit\TestUseCase;

use App\GaelO\Interfaces\Repositories\DicomSeriesRepositoryInterface;
use App\GaelO\Repositories\VisitRepository;
use App\GaelO\Services\AuthorizationService\AuthorizationStudyService;
use App\GaelO\Services\AuthorizationService\AuthorizationUserService;
use App\GaelO\Services\OrthancService;
use App\GaelO\UseCases\GetDicomsFileSupervisor\GetDicomsFileSupervisor;
use App\GaelO\UseCases\GetDicomsFileSupervisor\GetDicomsFileSupervisorRequest;
use App\GaelO\UseCases\GetDicomsFileSupervisor\GetDicomsFileSupervisorResponse;
use Illuminate\Support\Facades\App;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

class GetDicomFileSupervisorTest extends TestCase
{

    private GetDicomsFileSupervisor $getDicomsFileSupervisor;

    protected function setUp(): void
    {
        parent::setUp();

        $orthancServiceMock = Mockery::mock(OrthancService::class);
        $orthancServiceMock->shouldReceive('getOrthancZipStream')
            ->andReturn('FileTest');

        $authorizationServiceMock = $this->partialMock(AuthorizationStudyService::class, function (MockInterface $mock) {
            $mock->shouldReceive('setUserId')->andReturn(null);
            $mock->shouldReceive('setStudyName')->andReturn(null);
            $mock->shouldReceive('isAllowedStudy')->andReturn(true);
        });

        $dicomRepositoryMock = Mockery::mock(DicomSeriesRepositoryInterface::class);
        $dicomRepositoryMock->shouldReceive('getRelatedVisitIdFromSeriesInstanceUID')
            ->andReturn([1]);

        $dicomRepositoryMock->shouldReceive('getSeriesOrthancIDOfSeriesInstanceUID')
            ->andReturn(['1234-1234-1234-1234']);

        $visitRepositoryMock = Mockery::mock(VisitRepository::class);
        $context1 = [];
        $context1['patient']['study_name'] = 'test';
        $context2 = [];
        $context2['patient']['study_name'] = 'test';

        $visitRepositoryMock->shouldReceive('getVisitContextByVisitIdArray')

            ->andReturn([$context1, $context2]);



        $this->instance(AuthorizationStudyService::class, $authorizationServiceMock);
        $this->instance(DicomSeriesRepositoryInterface::class, $dicomRepositoryMock);
        $this->instance(OrthancService::class, $orthancServiceMock);
        $this->instance(VisitRepository::class, $visitRepositoryMock);


        $this->getDicomsFileSupervisor = new GetDicomsFileSupervisor(
            App::make(OrthancService::class),
            App::make(AuthorizationStudyService::class),
            App::make(DicomSeriesRepositoryInterface::class),
            App::make(VisitRepository::class),
        );
    }

    public function testUseCaseGetDicomFileSupervisorTest()
    {
        $getDicomsFilesSupervisorRequest = new GetDicomsFileSupervisorRequest();
        $getDicomsFilesSupervisorRequest->currentUserId = 1;
        $getDicomsFilesSupervisorRequest->seriesInstanceUID = ['1234'];
        $getDicomsFilesSupervisorRequest->studyName = 'test';

        $getDicomFilesSupervisorResponse = new GetDicomsFileSupervisorResponse();
        $this->getDicomsFileSupervisor->execute($getDicomsFilesSupervisorRequest, $getDicomFilesSupervisorResponse);

        $this->assertEquals(200, $getDicomFilesSupervisorResponse->status);
    }
}