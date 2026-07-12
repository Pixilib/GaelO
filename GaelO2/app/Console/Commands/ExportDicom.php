<?php

namespace App\Console\Commands;

use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Interfaces\Adapters\FTPClientInterface;
use App\GaelO\Interfaces\Adapters\SpreadsheetInterface;
use App\GaelO\Interfaces\Adapters\WebdavClientInterface;
use App\GaelO\Interfaces\Repositories\DicomStudyRepositoryInterface;
use App\GaelO\Interfaces\Repositories\VisitRepositoryInterface;
use App\GaelO\Services\DicomWebService;
use App\GaelO\Services\MailServices;
use App\GaelO\Services\OrthancService;
use App\GaelO\Interfaces\Adapters\ObjectStorageInterface;
use Exception;
use Generator;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

enum Destinations: string
{
    case ORTHANCPEER = "orthanc-peer";
    case FTP = "ftp";
    case SFTP = "sftp";
    case DICOMWEB = "dicom-web";
    case S3 = "s3";
    case AZURESTORAGE = "azure-storage";
    case WEBDAV = "webdav";
}

class ExportDicom extends Command
{

    private VisitRepositoryInterface $visitRepositoryInterface;
    private DicomStudyRepositoryInterface $dicomStudyRepositoryInterface;
    private OrthancService $orthancService;
    private FTPClientInterface $ftpClientInterface;
    private WebdavClientInterface $webdavClientInterface;
    private ObjectStorageInterface $objectStorage;

    private DicomWebService $dicomWebService;
    private bool $withDeletedStudies = false;
    private bool $withDeletedSeries = false;

    private string $studyName;

    private array $index = [];
    private array $dicomIndex = [];
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'gaelo:export-dicom';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Export DICOMs of a clinical trial to a destination endpoint';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(
        VisitRepositoryInterface $visitRepositoryInterface,
        DicomStudyRepositoryInterface $dicomStudyRepositoryInterface,
        OrthancService $orthancService,
        FTPClientInterface $ftpclientInterface,
        WebdavClientInterface $webdavClientInterface,
        ObjectStorageInterface $objectStorage,
        DicomWebService $dicomWebService,
        MailServices $mailServices,
        SpreadsheetInterface $spreadsheetInterface
    ) {
        $this->orthancService = $orthancService;
        $this->visitRepositoryInterface = $visitRepositoryInterface;
        $this->dicomStudyRepositoryInterface = $dicomStudyRepositoryInterface;
        $this->ftpClientInterface = $ftpclientInterface;
        $this->webdavClientInterface = $webdavClientInterface;
        $this->objectStorage = $objectStorage;
        $this->orthancService->setOrthancServer(true);
        $this->dicomWebService = $dicomWebService;

        $this->studyName = $this->ask('Study to export :');

        if ($this->confirm('Includes deleted studies ?')) {
            $this->withDeletedStudies = true;
        }
        if ($this->confirm('Includes deleted series ?')) {
            $this->withDeletedSeries = true;
        }

        $destinationType = $this->choice('Destination Type', array_column(Destinations::cases(), 'value'));

        switch ($destinationType) {
            case Destinations::ORTHANCPEER->value:
                $destinatorName = $this->ask('Orthanc Destinator Name : (ex: sanofi)');
                $orthancAddress = $this->ask('Orthanc URL : (ex: http://www.pixilib.fr:8042) ');
                $orthancUsername = $this->ask('Orthanc Username: ');
                $orthancPassword = $this->secret('Orthanc Password: ');
                $this->initOrthancPeerClient($destinatorName, $orthancAddress, $orthancUsername, $orthancPassword);
                break;
            case Destinations::WEBDAV->value:
                $webDavServer = $this->ask('WebDAV URL : (ex: https://www.pixilib.fr/webdav) ');
                $webDavUsername = $this->ask('Webdav Username: ');
                $webDavPassword = $this->secret('Webdav Password: ');
                $this->webdavClientInterface->setWebdavServer($webDavServer, $webDavUsername, $webDavPassword);
                break;
            case Destinations::FTP->value:
                $ftpHost = $this->ask('FTP Host : (ex: ftp.pixilib.fr) ');
                $ftpPort = (int) $this->ask('FTP Port : (ex: 21) ');
                $ftpUsername = $this->ask('FTP Username: ');
                $ftpPassword = $this->secret('FTP Password: ');
                $this->ftpClientInterface->setFTPServer($ftpHost, $ftpPort, $ftpUsername, $ftpPassword, false, false);
                break;
            case Destinations::SFTP->value:
                $ftpHost = $this->ask('SFTP Host : (ex: sftp.pixilib.fr) ');
                $ftpPort = (int) $this->ask('SFTP Port : (ex: 22) ');
                $ftpUsername = $this->ask('SFTP Username: ');
                $ftpPassword = $this->secret('SFTP Password: ');
                $this->ftpClientInterface->setFTPServer($ftpHost, $ftpPort, $ftpUsername, $ftpPassword, true, false);
                break;
            case Destinations::S3->value:
                $bucket = $this->ask('S3 Bucket name:');
                $region = $this->ask('S3 Region: (e.g. eu-west-1)');
                $accessKey = $this->ask('S3 Access Key ID:');
                $secretKey = $this->secret('S3 Secret Access Key:');
                $endpoint = $this->ask('S3 Custom Endpoint (leave empty for AWS):') ?: null;
                $this->objectStorage->setObjectStorageServer(
                    ObjectStorageInterface::PROVIDER_AWS_S3,
                    $bucket,
                    $region,
                    $accessKey,
                    $secretKey,
                    $endpoint
                );
                break;
            case Destinations::AZURESTORAGE->value:
                $container = $this->ask('Azure Container name:');
                $connectionString = $this->secret('Azure Connection String:');
                $this->objectStorage->setObjectStorageServer(
                    ObjectStorageInterface::PROVIDER_AZURE,
                    $container,
                    connectionString: $connectionString
                );
                break;
            case Destinations::DICOMWEB->value:
                $dicomAddress = $this->ask('Dicom URL base : (ex: http://orthancdestination:8042/dicom-web) ');
                $dicomWebUsername = $this->ask('Dicom Username: ');
                $dicomWebPassword = $this->secret('Dicom Password: ');
                $dicomWebToken = $this->secret('Dicom Token') ?: "";
                $this->dicomWebService->setUrl($dicomAddress);
                if ($dicomWebUsername || $dicomWebPassword)
                    $this->dicomWebService->setBasicAuthentication($dicomWebUsername, $dicomWebPassword);
                if ($dicomWebToken)
                    $this->dicomWebService->setAuthorizationToken($dicomWebToken);
                break;

        }


        $studies = $this->getDicomStudiesToSend();
        $fullSeriesOrthancIds = [];

        $totalStudies = $this->getCountTotalStudies();
        $progressBar = $this->output->createProgressBar($totalStudies);
        $progressBar->start();

        foreach ($studies as $study) {
            $orthancSeriesIds = array_map(
                function ($series) {
                    return $series['orthanc_id'];
                },
                $study['studies']['dicom_series']
            );

            array_push($fullSeriesOrthancIds, ...$orthancSeriesIds);
            $studyOrthancId = $study['studies']['orthanc_id'];
            $patientCode = $study['visit']['patient']['code'];
            $visitType = $study['visit']['visit_type']['name'];
            $visitName = $study['visit']['visit_type']['visit_group']['study_name'];

            $fileName = $studyOrthancId . '.zip';

            $this->index[$studyOrthancId] = [
                'orthancStudyId' => $studyOrthancId,
                'patientCode' => $patientCode,
                'visitType' => $visitType,
                'visitName' => $visitName,
                'checksum_sha256' => null,
                'status' => 'sending'
            ];

            switch ($destinationType) {
                case Destinations::ORTHANCPEER->value:

                    $this->buildDicomProtocolIndex(
                        $studyOrthancId,
                        $patientCode,
                        $visitType,
                        $visitName,
                        $orthancSeriesIds
                    );

                    $idWithLevels = array_map(function ($seriesOrthancId) {
                        return [
                            'Level' => 'Series',
                            'ID' => $seriesOrthancId
                        ];
                    }, $orthancSeriesIds);
                    $jobData = $this->orthancService->sendToPeerWithAcceleratorIfAvailable(
                        $destinatorName,
                        $idWithLevels,
                        true
                    );
                    $this->waitForJobFinished($jobData['ID']);
                    $this->updateStudyStatus($studyOrthancId, 'success');
                    break;
                case Destinations::WEBDAV->value:
                    $filePath = $this->getZipToSend($orthancSeriesIds);
                    $checksum = hash_file('sha256', $filePath);
                    $this->index[$studyOrthancId]['checksum_sha256'] = $checksum;
                    $stream = fopen($filePath, 'rb');
                    $success = $this->webdavClientInterface->writeStreamContent($stream, $fileName);
                    unlink($filePath);
                    $this->updateStudyStatus($studyOrthancId, $success ? 'success' : "failure");
                    break;
                case Destinations::FTP->value:
                case Destinations::SFTP->value:
                    $filePath = $this->getZipToSend($orthancSeriesIds);
                    $checksum = hash_file('sha256', $filePath);
                    $this->index[$studyOrthancId]['checksum_sha256'] = $checksum;
                    $stream = fopen($filePath, 'rb');
                    $success = $this->ftpClientInterface->writeStreamContent($stream, $fileName);
                    unlink($filePath);
                    if (!$success) {
                        throw new GaelOException("FTP upload failed for {$fileName}");
                    }
                    $this->updateStudyStatus($studyOrthancId, 'success');
                    break;
                case Destinations::S3->value:
                case Destinations::AZURESTORAGE->value:
                    $filePath = $this->getZipToSend($orthancSeriesIds);
                    $checksum = hash_file('sha256', $filePath);
                    $this->index[$studyOrthancId]['checksum_sha256'] = $checksum;
                    $stream = fopen($filePath, 'rb');
                    $success = $this->objectStorage->writeStreamContent($stream, $fileName);
                    unlink($filePath);
                    $this->updateStudyStatus($studyOrthancId, $success ? 'success' : 'failure');
                    break;
                case Destinations::DICOMWEB->value:

                    $this->buildDicomProtocolIndex(
                        $studyOrthancId,
                        $patientCode,
                        $visitType,
                        $visitName,
                        $orthancSeriesIds
                    );

                    $success = $this->sendSeriesToDicomWeb($orthancSeriesIds);
                    $this->updateStudyStatus($studyOrthancId, $success ? 'success' : 'failure');
                    break;
            }
            $this->newLine();
            $progressBar->advance();
            $this->newLine();
        }

        if ($destinationType === Destinations::ORTHANCPEER->value) {
                $this->orthancService->deletePeer($destinatorName);
            }

        $stats = $this->getExportStats($fullSeriesOrthancIds);
        $this->table(["instanceCount", "seriesCount", "studyCount", "patientCount"], [$stats]);
        
        $spreadsheetInterface->addSheet('Export Details');
        $spreadsheetInterface->fillData(
            'Export Details',
            $destinationType === Destinations::DICOMWEB->value || $destinationType === Destinations::ORTHANCPEER->value
                ? $this->dicomIndex
                : array_values($this->index)
        );

        $spreadsheetInterface->addSheet('Export Stats');
        $spreadsheetInterface->fillData('Export Stats', [$stats]);

        $detailsCsvReport = $spreadsheetInterface->writeToCsv('Export Details');
        $statsCsvReport = $spreadsheetInterface->writeToCsv('Export Stats');

        $mailServices->sendExportCommandReport(
            $this->studyName,
            "Export Terminated",
            "The export of $this->studyName to $destinationType destination has been successful",
            [
                $detailsCsvReport,
                $statsCsvReport
            ]
        );

        return 0;
    }

    private function getCountTotalStudies(): int
    {
        $visits = $this->visitRepositoryInterface->getVisitsInStudy($this->studyName, false, false, true, null);
        $count = 0;
        foreach ($visits as $visit) {
            $dicomStudies = $this->dicomStudyRepositoryInterface->getDicomsDataFromVisit($visit['id'], $this->withDeletedStudies, $this->withDeletedSeries);
            $count += count($dicomStudies);
        }
        return $count;
    }

    private function getExportStats(array $seriesOrthancIds)
    {
        $instances = $this->orthancService->describeResources('Instances', $seriesOrthancIds);
        $series = $this->orthancService->describeResources('Series', $seriesOrthancIds);
        $studies = $this->orthancService->describeResources('Studies', $seriesOrthancIds);
        $patients = $this->orthancService->describeResources('Patients', $seriesOrthancIds);
        return ['instanceCount' => sizeof($instances), 'seriesCount' => sizeof($series), 'studyCount' => sizeof($studies), 'patientCount' => sizeof($patients)];
    }

    private function updateStudyStatus($studyOrthancId, $status)
    {
        $this->index[$studyOrthancId]['status'] = $status;
        $this->table([
            'orthancStudyId',
            'patientCode',
            'visitType',
            'visitName',
            'checksum_sha256',
            'status'
        ], $this->index);
    }

    private function getZipToSend(array $seriesOrthancIds)
    {
        $tempFileLocation = tempnam(ini_get('upload_tmp_dir'), 'TMPZIP_');
        $this->orthancService->getZipStreamToFile($seriesOrthancIds, $tempFileLocation);
        return $tempFileLocation;
    }

    private function waitForJobFinished(string $jobId)
    {
        do {
            $job = $this->orthancService->getJobDetails($jobId);
        } while ($job['State'] !== "Success" && $job['State'] !== "Failure");

        if ($job['State'] === "Failure") {
            throw new Exception('Job Orthanc Failure');
        }

        return;
    }

    private function getDicomStudiesToSend(): Generator
    {
        $visits = $this->visitRepositoryInterface->getVisitsInStudy($this->studyName, false, false, true, null);
        $totalVisits = sizeof($visits);
        for ($i = 0; $i < $totalVisits; $i++) {
            $visit = $visits[$i];
            $dicomStudies = $this->dicomStudyRepositoryInterface->getDicomsDataFromVisit($visit['id'], $this->withDeletedStudies, $this->withDeletedSeries);
            foreach ($dicomStudies as $dicomStudy) {
                yield ['visit' => $visit, 'studies' => $dicomStudy, 'totalVisits' => $totalVisits, 'currentVisitNumber' => $i];
            }
        }
    }

    private function initOrthancPeerClient(string $name, string $url, string $username, string $password)
    {
        $this->orthancService->addPeer($name, $url, $username, $password);
        if (!$this->orthancService->echoPeer($name, $url, $username, $password)) {
            throw new GaelOException("Can't reach peer");
        }
    }

    private function sendSeriesToDicomWeb(array $seriesOrthancID): bool
    {
        try {
            $this->dicomWebService->sendStudyInstancesConcurrentlyToDicomWeb($this->orthancService, $seriesOrthancID, 5);
        } catch (Exception $e) {
            return false;
        }

        return true;

    }

    private function buildDicomProtocolIndex(
        string $studyOrthancId,
        string $patientCode,
        string $visitType,
        string $visitName,
        array $seriesOrthancIds
    ) {
        foreach ($seriesOrthancIds as $seriesOrthancId) {
            foreach ($this->orthancService->getInstancesOfSeries($seriesOrthancId) as $instanceFile) {
                $instanceId = pathinfo($instanceFile, PATHINFO_FILENAME);

                $this->dicomIndex[] = [
                    'orthancStudyId' => $studyOrthancId,
                    'patientCode' => $patientCode,
                    'visitType' => $visitType,
                    'visitName' => $visitName,
                    'dicomInstanceId' => $instanceId,
                    'checksum_sha256' => hash_file('sha256', $instanceFile),
                    'status' => 'sending'
                ];

                unlink($instanceFile);
            }
        }
    }

}