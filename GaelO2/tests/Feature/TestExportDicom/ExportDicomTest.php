<?php

namespace Tests\Feature\TestExportDicom;

use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Interfaces\Adapters\FTPClientInterface;
use App\GaelO\Interfaces\Adapters\ObjectStorageInterface;
use App\GaelO\Interfaces\Adapters\WebdavClientInterface;
use App\GaelO\Interfaces\Repositories\DicomStudyRepositoryInterface;
use App\GaelO\Interfaces\Repositories\VisitRepositoryInterface;
use App\GaelO\Repositories\TrackerRepository;
use App\GaelO\Services\DicomWebService;
use App\GaelO\Services\OrthancService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;
use Tests\TestCase;

class ExportDicomTest extends TestCase
{
    use RefreshDatabase;

    private MockInterface $trackerSpy;
    private MockInterface $visitRepositoryMock;
    private MockInterface $dicomStudyRepositoryMock;
    private MockInterface $orthancServiceMock;
    private MockInterface $ftpClientMock;
    private MockInterface $webdavClientMock;
    private MockInterface $objectStorageMock;
    private MockInterface $dicomWebServiceMock;

    private array $fakeVisits;
    private array $fakeDicomStudies;

    protected function setUp(): void
    {
        parent::setUp();

        $this->fakeVisits = [
            [
                'id' => 1,
                'patient' => ['code' => 'PAT001'],
                'visit_type' => [
                    'name' => 'Baseline',
                    'visit_group' => ['study_name' => 'STUDY_A'],
                ],
            ],
        ];

        $this->fakeDicomStudies = [
            [
                'orthanc_id' => 'study-orthanc-uid-001',
                'dicom_series' => [
                    ['orthanc_id' => 'series-orthanc-uid-001'],
                    ['orthanc_id' => 'series-orthanc-uid-002'],
                ],
            ],
        ];

        $this->visitRepositoryMock = $this->mock(VisitRepositoryInterface::class);
        $this->visitRepositoryMock
            ->shouldReceive('getVisitsInStudy')
            ->andReturn($this->fakeVisits);

        $this->dicomStudyRepositoryMock = $this->mock(DicomStudyRepositoryInterface::class);
        $this->dicomStudyRepositoryMock
            ->shouldReceive('getDicomsDataFromVisit')
            ->andReturn($this->fakeDicomStudies);

        $this->orthancServiceMock = $this->mock(OrthancService::class);
        $this->orthancServiceMock
            ->shouldReceive('setOrthancServer')
            ->zeroOrMoreTimes()
            ->with(true);
        $this->orthancServiceMock
            ->shouldReceive('describeResources')
            ->zeroOrMoreTimes()
            ->andReturn([])
            ->byDefault();

        $this->ftpClientMock       = $this->mock(FTPClientInterface::class);
        $this->webdavClientMock    = $this->mock(WebdavClientInterface::class);
        $this->objectStorageMock   = $this->mock(ObjectStorageInterface::class);
        $this->dicomWebServiceMock = $this->mock(DicomWebService::class);

        $this->trackerSpy = $this->spy(TrackerRepository::class);
        app()->instance(TrackerRepository::class, $this->trackerSpy);
    }

    private function mockOrthancZip(): void
    {
        $this->orthancServiceMock
            ->shouldReceive('getZipStreamToFile')
            ->once()
            ->andReturnUsing(function (array $series, string $dest) {
                file_put_contents($dest, 'fake-zip-content');
            });
    }

    private function expectCommonQuestions(\Illuminate\Testing\PendingCommand $cmd, string $destination): \Illuminate\Testing\PendingCommand
    {
        return $cmd
            ->expectsQuestion('Study to export :', 'STUDY_A')
            ->expectsConfirmation('Includes deleted studies ?', 'no')
            ->expectsConfirmation('Includes deleted series ?', 'no')
            ->expectsChoice(
                'Destination Type',
                $destination,
                ['orthanc-peer', 'ftp', 'sftp', 'dicom-web', 's3', 'azure-storage', 'webdav']
            );
    }

    // ORTHANC PEER
    public function testExportWithORTHANCPEER(): void
    {
        // switch 1
        $this->orthancServiceMock->shouldReceive('addPeer')
            ->once()->with('test-peer', 'http://orthanc:8042', 'admin', 'password');
        $this->orthancServiceMock->shouldReceive('echoPeer')
            ->once()->with('test-peer', 'http://orthanc:8042', 'admin', 'password')
            ->andReturn(true);

        // switch 2
        $this->orthancServiceMock->shouldReceive('sendToPeerWithAcceleratorIfAvailable')
            ->once()
            ->with(
                'test-peer',
                [
                    ['Level' => 'Series', 'ID' => 'series-orthanc-uid-001'],
                    ['Level' => 'Series', 'ID' => 'series-orthanc-uid-002'],
                ],
                true
            )
            ->andReturn(['ID' => 'job-001']);
        $this->orthancServiceMock->shouldReceive('getJobDetails')
            ->with('job-001')->andReturn(['State' => 'Success']);

        $cmd = $this->artisan('gaelo:export-dicom');
        $this->expectCommonQuestions($cmd, 'orthanc-peer')
            ->expectsQuestion('Orthanc Destinator Name : (ex: sanofi)', 'test-peer')
            ->expectsQuestion('Orthanc URL : (ex: http://www.pixilib.fr:8042) ', 'http://orthanc:8042')
            ->expectsQuestion('Orthanc Username: ', 'admin')
            ->expectsQuestion('Orthanc Password: ', 'password')
            ->expectsOutputToContain('study-orthanc-uid-001')
            ->assertExitCode(0);
    }

    public function testExportWithORTHANCPEERJobFailureThrows(): void
    {
        $this->orthancServiceMock->shouldReceive('addPeer')->once();
        $this->orthancServiceMock->shouldReceive('echoPeer')->once()->andReturn(true);
        $this->orthancServiceMock->shouldReceive('sendToPeerWithAcceleratorIfAvailable')
            ->once()->andReturn(['ID' => 'job-fail']);
        $this->orthancServiceMock->shouldReceive('getJobDetails')
            ->andReturn(['State' => 'Failure']);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Job Orthanc Failure');

        $cmd = $this->artisan('gaelo:export-dicom');
        $this->expectCommonQuestions($cmd, 'orthanc-peer')
            ->expectsQuestion('Orthanc Destinator Name : (ex: sanofi)', 'test-peer')
            ->expectsQuestion('Orthanc URL : (ex: http://www.pixilib.fr:8042) ', 'http://orthanc:8042')
            ->expectsQuestion('Orthanc Username: ', 'admin')
            ->expectsQuestion('Orthanc Password: ', 'password');
    }

    public function testExportORTHANCPEERUnreachableThrows(): void
    {
        $this->orthancServiceMock->shouldReceive('addPeer')->once();
        $this->orthancServiceMock->shouldReceive('echoPeer')->once()->andReturn(false);
        $this->orthancServiceMock->shouldNotReceive('sendToPeerWithAcceleratorIfAvailable');

        $this->expectException(GaelOException::class);

        $cmd = $this->artisan('gaelo:export-dicom');
        $this->expectCommonQuestions($cmd, 'orthanc-peer')
            ->expectsQuestion('Orthanc Destinator Name : (ex: sanofi)', 'test-peer')
            ->expectsQuestion('Orthanc URL : (ex: http://www.pixilib.fr:8042) ', 'http://orthanc:8042')
            ->expectsQuestion('Orthanc Username: ', 'admin')
            ->expectsQuestion('Orthanc Password: ', 'password');
    }

    // FTP
    public function testExportWithFTP(): void
    {
        $this->mockOrthancZip();

        // switch 1
        $this->ftpClientMock->shouldReceive('setFTPServer')
            ->once()->with('ftp.example.com', 21, 'ftpuser', 'ftppass', false, false);

        // switch 2
        $this->ftpClientMock->shouldReceive('writeStreamContent')
            ->once()->with(\Mockery::type('resource'), 'study-orthanc-uid-001.zip')
            ->andReturn(true);

        $cmd = $this->artisan('gaelo:export-dicom');
        $this->expectCommonQuestions($cmd, 'ftp')
            ->expectsQuestion('FTP Host : (ex: ftp.pixilib.fr) ', 'ftp.example.com')
            ->expectsQuestion('FTP Port : (ex: 21) ', '21')
            ->expectsQuestion('FTP Username: ', 'ftpuser')
            ->expectsQuestion('FTP Password: ', 'ftppass')
            ->expectsOutputToContain('study-orthanc-uid-001')
            ->assertExitCode(0);
    }

    public function testExportFTPUploadFailureThrows(): void
    {
        $this->mockOrthancZip();
        $this->ftpClientMock->shouldReceive('setFTPServer')->once();
        $this->ftpClientMock->shouldReceive('writeStreamContent')->once()->andReturn(false);

        $this->expectException(GaelOException::class);
        $this->expectExceptionMessage('FTP upload failed for study-orthanc-uid-001.zip');

        $cmd = $this->artisan('gaelo:export-dicom');
        $this->expectCommonQuestions($cmd, 'ftp')
            ->expectsQuestion('FTP Host : (ex: ftp.pixilib.fr) ', 'ftp.example.com')
            ->expectsQuestion('FTP Port : (ex: 21) ', '21')
            ->expectsQuestion('FTP Username: ', 'ftpuser')
            ->expectsQuestion('FTP Password: ', 'ftppass');
    }

    // SFTP — same switch 2 branch as FTP, only sftp=true differs in switch 1
    public function testExportWithSFTP(): void
    {
        $this->mockOrthancZip();

        // switch 1
        $this->ftpClientMock->shouldReceive('setFTPServer')
            ->once()->with('sftp.example.com', 22, 'sftpuser', 'sftppass', true, false);

        // switch 2
        $this->ftpClientMock->shouldReceive('writeStreamContent')
            ->once()->with(\Mockery::type('resource'), 'study-orthanc-uid-001.zip')
            ->andReturn(true);

        $cmd = $this->artisan('gaelo:export-dicom');
        $this->expectCommonQuestions($cmd, 'sftp')
            ->expectsQuestion('SFTP Host : (ex: sftp.pixilib.fr) ', 'sftp.example.com')
            ->expectsQuestion('SFTP Port : (ex: 22) ', '22')
            ->expectsQuestion('SFTP Username: ', 'sftpuser')
            ->expectsQuestion('SFTP Password: ', 'sftppass')
            ->expectsOutputToContain('study-orthanc-uid-001')
            ->assertExitCode(0);
    }

    public function testExportSFTPUploadFailureThrows(): void
    {
        $this->mockOrthancZip();
        $this->ftpClientMock->shouldReceive('setFTPServer')->once();
        $this->ftpClientMock->shouldReceive('writeStreamContent')->once()->andReturn(false);

        $this->expectException(GaelOException::class);
        $this->expectExceptionMessage('FTP upload failed for study-orthanc-uid-001.zip');

        $cmd = $this->artisan('gaelo:export-dicom');
        $this->expectCommonQuestions($cmd, 'sftp')
            ->expectsQuestion('SFTP Host : (ex: sftp.pixilib.fr) ', 'sftp.example.com')
            ->expectsQuestion('SFTP Port : (ex: 22) ', '22')
            ->expectsQuestion('SFTP Username: ', 'sftpuser')
            ->expectsQuestion('SFTP Password: ', 'sftppass');
    }

    // DICOM-WEB
    public function testExportWithDICOMWEB(): void
    {
        // switch 1 — basic auth, no token
        $this->dicomWebServiceMock->shouldReceive('setUrl')
            ->once()->with('http://dicomweb:8042/dicom-web');
        $this->dicomWebServiceMock->shouldReceive('setBasicAuthentication')
            ->once()->with('dcmuser', 'dcmpass');
        $this->dicomWebServiceMock->shouldNotReceive('setAuthorizationToken');

        // switch 2
        $this->dicomWebServiceMock->shouldReceive('sendStudyInstancesConcurrentlyToDicomWeb')
            ->once()
            ->with(
                $this->orthancServiceMock,
                ['series-orthanc-uid-001', 'series-orthanc-uid-002'],
                5
            );

        $cmd = $this->artisan('gaelo:export-dicom');
        $this->expectCommonQuestions($cmd, 'dicom-web')
            ->expectsQuestion('Dicom URL base : (ex: http://orthancdestination:8042/dicom-web) ', 'http://dicomweb:8042/dicom-web')
            ->expectsQuestion('Dicom Username: ', 'dcmuser')
            ->expectsQuestion('Dicom Password: ', 'dcmpass')
            ->expectsQuestion('Dicom Token', '')
            ->expectsOutputToContain('study-orthanc-uid-001')
            ->assertExitCode(0);
    }

    public function testExportWithDICOMWEBTokenOnly(): void
    {
        // switch 1 — token only, no basic auth
        $this->dicomWebServiceMock->shouldReceive('setUrl')->once();
        $this->dicomWebServiceMock->shouldNotReceive('setBasicAuthentication');
        $this->dicomWebServiceMock->shouldReceive('setAuthorizationToken')
            ->once()->with('my-secret-token');

        // switch 2
        $this->dicomWebServiceMock->shouldReceive('sendStudyInstancesConcurrentlyToDicomWeb')
            ->once();

        $cmd = $this->artisan('gaelo:export-dicom');
        $this->expectCommonQuestions($cmd, 'dicom-web')
            ->expectsQuestion('Dicom URL base : (ex: http://orthancdestination:8042/dicom-web) ', 'http://dicomweb:8042/dicom-web')
            ->expectsQuestion('Dicom Username: ', '')
            ->expectsQuestion('Dicom Password: ', '')
            ->expectsQuestion('Dicom Token', 'my-secret-token')
            ->expectsOutputToContain('study-orthanc-uid-001')
            ->assertExitCode(0);
    }

    public function testExportWithDICOMWEBSendFailureSetsStatusFailure(): void
    {
        $this->dicomWebServiceMock->shouldReceive('setUrl')->once();
        $this->dicomWebServiceMock->shouldReceive('setBasicAuthentication')->once();
        // exception caught internally by sendSeriesToDicomWeb → status failure, exit 0
        $this->dicomWebServiceMock->shouldReceive('sendStudyInstancesConcurrentlyToDicomWeb')
            ->once()->andThrow(new \Exception('DicomWeb unreachable'));

        $cmd = $this->artisan('gaelo:export-dicom');
        $this->expectCommonQuestions($cmd, 'dicom-web')
            ->expectsQuestion('Dicom URL base : (ex: http://orthancdestination:8042/dicom-web) ', 'http://dicomweb:8042/dicom-web')
            ->expectsQuestion('Dicom Username: ', 'dcmuser')
            ->expectsQuestion('Dicom Password: ', 'dcmpass')
            ->expectsQuestion('Dicom Token', '')
            ->expectsOutputToContain('study-orthanc-uid-001')
            ->assertExitCode(0);
    }

    // S3
    public function testExportWithS3(): void
    {
        $this->mockOrthancZip();

        // switch 1
        $this->objectStorageMock->shouldReceive('setObjectStorageServer')
            ->once()->withAnyArgs();

        // switch 2
        $this->objectStorageMock->shouldReceive('writeStreamContent')
            ->once()->with(\Mockery::type('resource'), 'study-orthanc-uid-001.zip')
            ->andReturn(true);

        $cmd = $this->artisan('gaelo:export-dicom');
        $this->expectCommonQuestions($cmd, 's3')
            ->expectsQuestion('S3 Bucket name:', 'my-bucket')
            ->expectsQuestion('S3 Region: (e.g. eu-west-1)', 'eu-west-1')
            ->expectsQuestion('S3 Access Key ID:', 'AKID')
            ->expectsQuestion('S3 Secret Access Key:', 'SECRET')
            ->expectsQuestion('S3 Custom Endpoint (leave empty for AWS):', '')
            ->expectsOutputToContain('study-orthanc-uid-001')
            ->assertExitCode(0);
    }

    public function testExportWithS3UploadFailureSetsStatusFailure(): void
    {
        $this->mockOrthancZip();
        $this->objectStorageMock->shouldReceive('setObjectStorageServer')->once()->withAnyArgs();
        $this->objectStorageMock->shouldReceive('writeStreamContent')->once()->andReturn(false);

        $cmd = $this->artisan('gaelo:export-dicom');
        $this->expectCommonQuestions($cmd, 's3')
            ->expectsQuestion('S3 Bucket name:', 'my-bucket')
            ->expectsQuestion('S3 Region: (e.g. eu-west-1)', 'eu-west-1')
            ->expectsQuestion('S3 Access Key ID:', 'AKID')
            ->expectsQuestion('S3 Secret Access Key:', 'SECRET')
            ->expectsQuestion('S3 Custom Endpoint (leave empty for AWS):', '')
            ->expectsOutputToContain('study-orthanc-uid-001')
            ->assertExitCode(0);
    }

    // AZURE STORAGE — same switch 2 branch as S3
    public function testExportWithAZURESTORAGE(): void
    {
        $this->mockOrthancZip();

        // switch 1
        $this->objectStorageMock->shouldReceive('setObjectStorageServer')
            ->once()->withAnyArgs();

        // switch 2
        $this->objectStorageMock->shouldReceive('writeStreamContent')
            ->once()->with(\Mockery::type('resource'), 'study-orthanc-uid-001.zip')
            ->andReturn(true);

        $cmd = $this->artisan('gaelo:export-dicom');
        $this->expectCommonQuestions($cmd, 'azure-storage')
            ->expectsQuestion('Azure Container name:', 'my-container')
            ->expectsQuestion('Azure Connection String:', 'DefaultEndpointsProtocol=https;AccountName=test;AccountKey=abc')
            ->expectsOutputToContain('study-orthanc-uid-001')
            ->assertExitCode(0);
    }

    public function testExportWithAZURESTORAGEUploadFailureSetsStatusFailure(): void
    {
        $this->mockOrthancZip();
        $this->objectStorageMock->shouldReceive('setObjectStorageServer')->once()->withAnyArgs();
        $this->objectStorageMock->shouldReceive('writeStreamContent')->once()->andReturn(false);

        $cmd = $this->artisan('gaelo:export-dicom');
        $this->expectCommonQuestions($cmd, 'azure-storage')
            ->expectsQuestion('Azure Container name:', 'my-container')
            ->expectsQuestion('Azure Connection String:', 'DefaultEndpointsProtocol=https;AccountName=test;AccountKey=abc')
            ->expectsOutputToContain('study-orthanc-uid-001')
            ->assertExitCode(0);
    }

    // WEBDAV
    public function testExportWithWEBDAV(): void
    {
        $this->mockOrthancZip();

        // switch 1
        $this->webdavClientMock->shouldReceive('setWebdavServer')
            ->once()->with('https://webdav.example.com', 'wduser', 'wdpass');

        // switch 2
        $this->webdavClientMock->shouldReceive('writeStreamContent')
            ->once()->with(\Mockery::type('resource'), 'study-orthanc-uid-001.zip')
            ->andReturn(true);

        $cmd = $this->artisan('gaelo:export-dicom');
        $this->expectCommonQuestions($cmd, 'webdav')
            ->expectsQuestion('WebDAV URL : (ex: https://www.pixilib.fr/webdav) ', 'https://webdav.example.com')
            ->expectsQuestion('Webdav Username: ', 'wduser')
            ->expectsQuestion('Webdav Password: ', 'wdpass')
            ->expectsOutputToContain('study-orthanc-uid-001')
            ->assertExitCode(0);
    }

    public function testExportWithWEBDAVUploadFailureSetsStatusFailure(): void
    {
        $this->mockOrthancZip();
        $this->webdavClientMock->shouldReceive('setWebdavServer')->once();
        // no throw unlike FTP, just failure status
        $this->webdavClientMock->shouldReceive('writeStreamContent')->once()->andReturn(false);

        $cmd = $this->artisan('gaelo:export-dicom');
        $this->expectCommonQuestions($cmd, 'webdav')
            ->expectsQuestion('WebDAV URL : (ex: https://www.pixilib.fr/webdav) ', 'https://webdav.example.com')
            ->expectsQuestion('Webdav Username: ', 'wduser')
            ->expectsQuestion('Webdav Password: ', 'wdpass')
            ->expectsOutputToContain('study-orthanc-uid-001')
            ->assertExitCode(0);
    }

    public function testConsoleOutputDisplaysTablesAndProgressBar(): void
    {
        $this->mockOrthancZip();
        
        // mock WebDAV success
        $this->webdavClientMock->shouldReceive('setWebdavServer')->once();
        $this->webdavClientMock->shouldReceive('writeStreamContent')->once()->andReturn(true);

        // mock final stats for table
        $this->orthancServiceMock->shouldReceive('describeResources')
            ->with('Instances', \Mockery::any())->andReturn([1, 2, 3]);
        $this->orthancServiceMock->shouldReceive('describeResources')
            ->with('Series', \Mockery::any())->andReturn([1, 2]);
        $this->orthancServiceMock->shouldReceive('describeResources')
            ->with('Studies', \Mockery::any())->andReturn([1]);
        $this->orthancServiceMock->shouldReceive('describeResources')
            ->with('Patients', \Mockery::any())->andReturn([1]);

        $cmd = $this->artisan('gaelo:export-dicom');
        
        $this->expectCommonQuestions($cmd, 'webdav')
            ->expectsQuestion('WebDAV URL : (ex: https://www.pixilib.fr/webdav) ', 'https://webdav.example.com')
            ->expectsQuestion('Webdav Username: ', 'wduser')
            ->expectsQuestion('Webdav Password: ', 'wdpass');

        // assert study status table
        $cmd->expectsTable(
            ['orthancStudyId', 'patientCode', 'visitType', 'visitName', 'status'],
            [
                [
                    'orthancStudyId' => 'study-orthanc-uid-001',
                    'patientCode'    => 'PAT001',
                    'visitType'      => 'Baseline',
                    'visitName'      => 'STUDY_A',
                    'status'         => 'success',
                ]
            ]
        );

        // assert final stats table
        $cmd->expectsTable(
            ["instanceCount", "seriesCount", "studyCount", "patientCount"],
            [
                [
                    'instanceCount' => 3, 
                    'seriesCount'   => 2, 
                    'studyCount'    => 1, 
                    'patientCount'  => 1
                ]
            ]
        );

        // assert progress bar advance
        $cmd->expectsOutputToContain('1/1');

        $cmd->assertExitCode(0);
    }

    // Deleted flags
    public function testDeletedFlagsPassedToRepository(): void
    {
        $this->mockOrthancZip();
        $this->webdavClientMock->shouldReceive('setWebdavServer')->once();
        $this->webdavClientMock->shouldReceive('writeStreamContent')->once()->andReturn(true);

        $this->dicomStudyRepositoryMock
            ->shouldReceive('getDicomsDataFromVisit')
            ->with(1, true, true)
            ->andReturn($this->fakeDicomStudies);

        $cmd = $this->artisan('gaelo:export-dicom');
        $cmd->expectsQuestion('Study to export :', 'STUDY_A')
            ->expectsConfirmation('Includes deleted studies ?', 'yes')
            ->expectsConfirmation('Includes deleted series ?', 'yes')
            ->expectsChoice(
                'Destination Type',
                'webdav',
                ['orthanc-peer', 'ftp', 'sftp', 'dicom-web', 's3', 'azure-storage', 'webdav']
            )
            ->expectsQuestion('WebDAV URL : (ex: https://www.pixilib.fr/webdav) ', 'https://webdav.example.com')
            ->expectsQuestion('Webdav Username: ', 'wduser')
            ->expectsQuestion('Webdav Password: ', 'wdpass')
            ->assertExitCode(0);
    }
}