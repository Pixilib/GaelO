<?php

namespace App\GaelO\Services\CommandExportStudyService;

use App\GaelO\Adapters\FrameworkAdapter;
use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Interfaces\Adapters\FTPClientInterface;
use App\GaelO\Interfaces\Adapters\ObjectStorageInterface;
use App\GaelO\Interfaces\Adapters\WebdavClientInterface;
use App\GaelO\Interfaces\Repositories\DicomStudyRepositoryInterface;
use App\GaelO\Interfaces\Repositories\VisitRepositoryInterface;
use App\GaelO\Services\DicomWebService;
use App\GaelO\Services\ExportStudyService;
use App\GaelO\Services\OrthancService;
use Illuminate\Support\Facades\Log;
use Exception;
use ExportDestinationsEnum;
use Generator;
use ZipArchive;

class CommandExportStudyService
{
    private ExportStudyService $exportStudyService;
    private FTPClientInterface $ftpClientInterface;
    private WebdavClientInterface $webdavClientInterface;
    private ObjectStorageInterface $objectStorage;
    private VisitRepositoryInterface $visitRepositoryInterface;
    private DicomStudyRepositoryInterface $dicomStudyRepositoryInterface;
    private OrthancService $orthancService;
    private DicomWebService $dicomWebService;

    private string $studyName;
    private string $destinatorName = '';
    private bool $withDeletedStudies = false;
    private bool $withDeletedSeries = false;

    public function __construct(
        ExportStudyService $exportStudyService,
        FTPClientInterface $ftpClientInterface,
        WebdavClientInterface $webdavClientInterface,
        ObjectStorageInterface $objectStorage,
        VisitRepositoryInterface $visitRepositoryInterface,
        DicomStudyRepositoryInterface $dicomStudyRepositoryInterface,
        OrthancService $orthancService,
        DicomWebService $dicomWebService
    ) {
        $this->exportStudyService            = $exportStudyService;
        $this->ftpClientInterface            = $ftpClientInterface;
        $this->webdavClientInterface         = $webdavClientInterface;
        $this->objectStorage                 = $objectStorage;
        $this->visitRepositoryInterface      = $visitRepositoryInterface;
        $this->dicomStudyRepositoryInterface = $dicomStudyRepositoryInterface;
        $this->orthancService                = $orthancService;
        $this->dicomWebService               = $dicomWebService;

        $this->orthancService->setOrthancServer(true);
    }

    public function setStudyName(string $studyName): void
    {
        $this->studyName = $studyName;
    }

    public function setWithDeletedStudies(bool $withDeletedStudies): void
    {
        $this->withDeletedStudies = $withDeletedStudies;
    }

    public function setWithDeletedSeries(bool $withDeletedSeries): void
    {
        $this->withDeletedSeries = $withDeletedSeries;
    }

    public function configureOrthancPeer(string $name, string $url, string $username, string $password): void
    {
        $this->destinatorName = $name;
        $this->orthancService->addPeer($name, $url, $username, $password);
        if (!$this->orthancService->echoPeer($name, $url, $username, $password)) {
            throw new GaelOException("Can't reach peer");
        }
    }

    public function configureWebdav(string $url, string $username, string $password): void
    {
        $this->webdavClientInterface->setWebdavServer($url, $username, $password);
    }

    public function configureFtp(string $host, int $port, string $username, string $password, bool $isSftp): void
    {
        $this->ftpClientInterface->setFTPServer($host, $port, $username, $password, $isSftp, false);
    }

    public function configureS3(string $bucket, string $region, string $accessKey, string $secretKey, ?string $endpoint): void
    {
        $this->objectStorage->setObjectStorageServer(
            ObjectStorageInterface::PROVIDER_AWS_S3,
            $bucket, $region, $accessKey, $secretKey, $endpoint
        );
    }

    public function configureAzure(string $container, string $connectionString): void
    {
        $this->objectStorage->setObjectStorageServer(
            ObjectStorageInterface::PROVIDER_AZURE,
            $container,
            connectionString: $connectionString
        );
    }

    public function configureDicomWeb(string $address, string $username, string $password, string $token): void
    {
        $this->dicomWebService->setUrl($address);
        if ($username || $password) {
            $this->dicomWebService->setBasicAuthentication($username, $password);
        }
        if ($token) {
            $this->dicomWebService->setAuthorizationToken($token);
        }
    }

    public function deleteOrthancPeer(): void
    {
        $this->orthancService->deletePeer($this->destinatorName);
    }

    public function buildDataTablesZip(): string
    {
        $this->exportStudyService->setStudyName($this->studyName);
        $this->exportStudyService->exportAllTables();
        return $this->exportStudyService->getExportStudyResult()->getResultsAsZip();
    }

    public function buildAssociatedFilesZip(): array
    {
        $zipFile        = tempnam(ini_get('upload_tmp_dir'), 'TMPFILES_');
        $tempEntryFiles = [];

        $zip = new ZipArchive();
        if ($zip->open($zipFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException("Cannot create temporary ZIP archive.");
        }

        try {
            $zip->addFromString('README', 'Associated files for study ' . $this->studyName);

            $files     = FrameworkAdapter::getStoredFiles($this->studyName);
            $fileCount = count($files);

            foreach ($files as $file) {
                $tempEntry        = tempnam(ini_get('upload_tmp_dir'), 'TMPENTRY_');
                $tempEntryFiles[] = $tempEntry;
                $stream           = FrameworkAdapter::getFile($file, true);
                stream_copy_to_stream($stream, fopen($tempEntry, 'w'));
                $zip->addFile($tempEntry, $file);
            }

            $zip->close();
        } catch (\Throwable $t) {
            if (file_exists($zipFile)) unlink($zipFile);
            throw new \RuntimeException("Error building associated files ZIP: " . $t->getMessage(), 0, $t);
        } finally {
            foreach ($tempEntryFiles as $temp) {
                if (file_exists($temp)) unlink($temp);
            }
        }

        return ['path' => $zipFile, 'count' => $fileCount];
    }

    public function validateZip(string $filePath): void
    {
        $zip    = new ZipArchive();
        $result = $zip->open($filePath, ZipArchive::CHECKCONS);

        if ($result !== true) {
            throw new \RuntimeException("Invalid ZIP archive (ZipArchive error code: {$result}).");
        }
        $zip->close();
    }

    public function writeToDestination(string $destinationType, $stream, string $fileName): bool
    {
        return match ($destinationType) {
            ExportDestinationsEnum::FTP->value,
            ExportDestinationsEnum::SFTP->value         => $this->ftpClientInterface->writeStreamContent($stream, $fileName),
            ExportDestinationsEnum::S3->value,
            ExportDestinationsEnum::AZURESTORAGE->value => $this->objectStorage->writeStreamContent($stream, $fileName),
            ExportDestinationsEnum::WEBDAV->value       => $this->webdavClientInterface->writeStreamContent($stream, $fileName),
            default                                   => false,
        };
    }

    public function getCountTotalStudies(): int
    {
        $visits = $this->visitRepositoryInterface->getVisitsInStudy($this->studyName, false, false, true, null);
        $count = 0;
        foreach ($visits as $visit) {
            $dicomStudies = $this->dicomStudyRepositoryInterface->getDicomsDataFromVisit($visit['id'], $this->withDeletedStudies, $this->withDeletedSeries);
            $count += count($dicomStudies);
        }
        return $count;
    }

    public function getDicomStudiesToSend(): Generator
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

    public function getExportStats(array $seriesOrthancIds): array
    {
        if (empty($seriesOrthancIds)) {
            return ['instanceCount' => 0, 'seriesCount' => 0, 'studyCount' => 0, 'patientCount' => 0];
        }

        $instances = $this->orthancService->describeResources('Instances', $seriesOrthancIds);
        $series    = $this->orthancService->describeResources('Series', $seriesOrthancIds);
        $studies   = $this->orthancService->describeResources('Studies', $seriesOrthancIds);
        $patients  = $this->orthancService->describeResources('Patients', $seriesOrthancIds);

        return [
            'instanceCount' => sizeof($instances),
            'seriesCount'   => sizeof($series),
            'studyCount'    => sizeof($studies),
            'patientCount'  => sizeof($patients)
        ];
    }

    public function getZipToSend(array $seriesOrthancIds): string
    {
        $tempFileLocation = tempnam(ini_get('upload_tmp_dir'), 'TMPZIP_');
        $this->orthancService->getZipStreamToFile($seriesOrthancIds, $tempFileLocation);
        return $tempFileLocation;
    }

    public function waitForJobFinished(string $jobId): void
    {
        do {
            //sleep for 3 seconds to avoid too many requests to orthanc
            sleep(3);
            $job = $this->orthancService->getJobDetails($jobId);
        } while ($job['State'] !== "Success" && $job['State'] !== "Failure");

        if ($job['State'] === "Failure") {
            throw new Exception('Job Orthanc Failure');
        }
    }

    public function sendToPeer(array $idWithLevels): array
    {
        return $this->orthancService->sendToPeerWithAcceleratorIfAvailable($this->destinatorName, $idWithLevels, true);
    }

    public function sendSeriesToDicomWeb(array $seriesOrthancIds): bool
    {
        try {
            $this->dicomWebService->sendStudyInstancesConcurrentlyToDicomWeb($this->orthancService, $seriesOrthancIds, 5);
        } catch (Exception $e) {
            Log::error($e);
            return false;
        }
        return true;
    }

    public function buildDicomProtocolIndex(
        string $studyOrthancId,
        string $patientCode,
        string $visitType,
        string $visitName,
        array $seriesOrthancIds
    ): array {
        $entries = [];

        foreach ($seriesOrthancIds as $seriesOrthancId) {
            foreach ($this->orthancService->getInstancesOfSeries($seriesOrthancId) as $instanceFile) {
                $instanceId = pathinfo($instanceFile, PATHINFO_FILENAME);

                $entries[] = [
                    'orthancStudyId'  => $studyOrthancId,
                    'patientCode'     => $patientCode,
                    'visitType'       => $visitType,
                    'visitName'       => $visitName,
                    'dicomInstanceId' => $instanceId,
                    'checksum_sha256' => hash_file('sha256', $instanceFile),
                    'status'          => 'sending'
                ];

                unlink($instanceFile);
            }
        }

        return $entries;
    }
}