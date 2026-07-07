<?php

namespace App\Console\Commands;

use App\GaelO\Adapters\FrameworkAdapter;
use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Interfaces\Adapters\FTPClientInterface;
use App\GaelO\Interfaces\Adapters\ObjectStorageInterface;
use App\GaelO\Interfaces\Adapters\SpreadsheetInterface;
use App\GaelO\Interfaces\Adapters\WebdavClientInterface;
use App\GaelO\Interfaces\Repositories\DicomStudyRepositoryInterface;
use App\GaelO\Interfaces\Repositories\VisitRepositoryInterface;
use App\GaelO\Services\DicomWebService;
use App\GaelO\Services\ExportStudyService;
use App\GaelO\Services\MailServices;
use App\GaelO\Services\OrthancService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Exception;
use Generator;
use ZipArchive;

enum CombinedDestinations: string
{
    case ORTHANCPEER  = "orthanc-peer";
    case FTP          = "ftp";
    case SFTP         = "sftp";
    case DICOMWEB     = "dicom-web";
    case S3           = "s3";
    case AZURESTORAGE = "azure-storage";
    case WEBDAV       = "webdav";
}

class ExportStudy extends Command
{
    protected $signature = 'gaelo:export-study';
    protected $description = 'Export study data tables, associated files, and/or DICOMs to a destination endpoint';

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

    private array $fileIndex = [];
    private array $dicomStudyIndex = [];
    private array $dicomIndex = [];

    public function handle(
        ExportStudyService $exportStudyService,
        FTPClientInterface $ftpClientInterface,
        WebdavClientInterface $webdavClientInterface,
        ObjectStorageInterface $objectStorage,
        MailServices $mailServices,
        VisitRepositoryInterface $visitRepositoryInterface,
        DicomStudyRepositoryInterface $dicomStudyRepositoryInterface,
        OrthancService $orthancService,
        DicomWebService $dicomWebService,
        SpreadsheetInterface $spreadsheetInterface
    ): int {
        $this->exportStudyService            = $exportStudyService;
        $this->ftpClientInterface            = $ftpClientInterface;
        $this->webdavClientInterface         = $webdavClientInterface;
        $this->objectStorage                 = $objectStorage;
        $this->visitRepositoryInterface      = $visitRepositoryInterface;
        $this->dicomStudyRepositoryInterface = $dicomStudyRepositoryInterface;
        $this->orthancService                = $orthancService;
        $this->dicomWebService               = $dicomWebService;

        $this->orthancService->setOrthancServer(true);

        $this->studyName = $this->ask('Study to export:');

        $exportData  = $this->confirm('Export data tables (forms, visits, reviews)?', true);
        $exportFiles = $this->confirm('Export associated files?', true);
        $exportDicom = $this->confirm('Export DICOMs?', true);

        if (!$exportData && !$exportFiles && !$exportDicom) {
            $this->error('Nothing selected, aborting.');
            return 1;
        }

        if ($exportDicom) {
            $this->withDeletedStudies = $this->confirm('Includes deleted studies?', false);
            $this->withDeletedSeries  = $this->confirm('Includes deleted series?', false);
        }

        $availableDestinations = array_column(CombinedDestinations::cases(), 'value');

        if ($exportData || $exportFiles) {
            $availableDestinations = array_diff($availableDestinations, [
                CombinedDestinations::ORTHANCPEER->value,
                CombinedDestinations::DICOMWEB->value
            ]);

            $spreadsheetInterface->addSheet('Files Details');
            $spreadsheetInterface->fillData('Files Details', array_values($this->fileIndex));
            
            $csvTemp = $spreadsheetInterface->writeToCsv('Files Details');
            $csvFinal = dirname($csvTemp) . '/details_fichiers_' . $this->studyName . '.csv';
            rename($csvTemp, $csvFinal);
            $attachments[] = $csvFinal;

            $excelTemp = $spreadsheetInterface->writeToExcel();
            $excelFinal = dirname($excelTemp) . '/details_fichiers_' . $this->studyName . '.xlsx';
            rename($excelTemp, $excelFinal);
            $attachments[] = $excelFinal;
        }

        $destinationType = $this->choice('Destination Type', array_values($availableDestinations));

        switch ($destinationType) {
            case CombinedDestinations::ORTHANCPEER->value:
                $this->destinatorName = $this->ask('Orthanc Destinator Name: (ex: sanofi)');
                $orthancAddress       = $this->ask('Orthanc URL: (ex: http://www.example.com:8042) ');
                $orthancUsername      = $this->ask('Orthanc Username: ');
                $orthancPassword      = $this->secret('Orthanc Password: ');
                $this->initOrthancPeerClient($this->destinatorName, $orthancAddress, $orthancUsername, $orthancPassword);
                break;
            case CombinedDestinations::WEBDAV->value:
                $url      = $this->ask('WebDAV URL: (ex: https://www.example.com/webdav)');
                $username = $this->ask('WebDAV Username:');
                $password = $this->secret('WebDAV Password:');
                $this->webdavClientInterface->setWebdavServer($url, $username, $password);
                break;
            case CombinedDestinations::FTP->value:
                $host     = $this->ask('FTP Host: (ex: ftp.example.com)');
                $port     = (int) $this->ask('FTP Port: (ex: 21)');
                $username = $this->ask('FTP Username:');
                $password = $this->secret('FTP Password:');
                $this->ftpClientInterface->setFTPServer($host, $port, $username, $password, false, false);
                break;
            case CombinedDestinations::SFTP->value:
                $host     = $this->ask('SFTP Host: (ex: sftp.example.com)');
                $port     = (int) $this->ask('SFTP Port: (ex: 22)');
                $username = $this->ask('SFTP Username:');
                $password = $this->secret('SFTP Password:');
                $this->ftpClientInterface->setFTPServer($host, $port, $username, $password, true, false);
                break;
            case CombinedDestinations::S3->value:
                $bucket    = $this->ask('S3 Bucket name:');
                $region    = $this->ask('S3 Region: (e.g. eu-west-1)');
                $accessKey = $this->ask('S3 Access Key ID:');
                $secretKey = $this->secret('S3 Secret Access Key:');
                $endpoint  = $this->ask('S3 Custom Endpoint (leave empty for AWS):') ?: null;
                $this->objectStorage->setObjectStorageServer(
                    ObjectStorageInterface::PROVIDER_AWS_S3,
                    $bucket, $region, $accessKey, $secretKey, $endpoint
                );
                break;
            case CombinedDestinations::AZURESTORAGE->value:
                $container        = $this->ask('Azure Container name:');
                $connectionString = $this->secret('Azure Connection String:');
                $this->objectStorage->setObjectStorageServer(
                    ObjectStorageInterface::PROVIDER_AZURE,
                    $container,
                    connectionString: $connectionString
                );
                break;
            case CombinedDestinations::DICOMWEB->value:
                $dicomAddress     = $this->ask('Dicom URL base: (ex: http://orthancdestination:8042/dicom-web) ');
                $dicomWebUsername = $this->ask('Dicom Username: ');
                $dicomWebPassword = $this->secret('Dicom Password: ');
                $dicomWebToken    = $this->secret('Dicom Token') ?: "";
                $this->dicomWebService->setUrl($dicomAddress);
                if ($dicomWebUsername || $dicomWebPassword) {
                    $this->dicomWebService->setBasicAuthentication($dicomWebUsername, $dicomWebPassword);
                }
                if ($dicomWebToken) {
                    $this->dicomWebService->setAuthorizationToken($dicomWebToken);
                }
                break;
        }

        $attachments = [];

        if ($exportData) {
            $this->info("Exporting Data Tables...");
            $this->transferDataTables($destinationType);
        }

        if ($exportFiles) {
            $this->info("Exporting Associated Files...");
            $this->transferAssociatedFiles($destinationType);
        }

        if ($exportData || $exportFiles) {
            $spreadsheetInterface->addSheet('Files Details');
            $spreadsheetInterface->fillData('Files Details', array_values($this->fileIndex));
            $attachments[] = $spreadsheetInterface->writeToCsv('Files Details');
        }

        if ($exportDicom) {
            $this->info("Exporting DICOMs...");
            $dicomAttachments = $this->transferDicoms($destinationType, $spreadsheetInterface);
            $attachments = array_merge($attachments, $dicomAttachments);
        }
        $completed = [];

        foreach ($this->fileIndex as $key => $fileData) {
            if ($fileData['status'] === 'success' && !empty($fileData['checksum_sha256'])) {
                $completed[$key] = [
                    'checksum' => $fileData['checksum_sha256'],
                    'instances_count' => '1',
                    'info' => [
                        'server_filename' => $fileData['fileName'],
                        'export_type' => $fileData['export'],
                    ]
                ];
            }
        }

        foreach ($this->dicomStudyIndex as $studyOrthancId => $studyData) {
            if ($studyData['status'] === 'success' && !empty($studyData['checksum_sha256'])) {
                $completed[$studyOrthancId] = [
                    'checksum' => $studyData['checksum_sha256'],
                    'instances_count' => 'unknown', 
                    'info' => [
                        'server_filename' => $studyOrthancId . '.zip',
                        'export_type' => 'dicom',
                    ]
                ];
            }
        }

        $jsonExportData = [
            'completed' => $completed
        ];

        $jsonFinalPath = (ini_get('upload_tmp_dir') ?: sys_get_temp_dir()) . '/transfer_progress_' . $this->studyName . '.json';
        file_put_contents(
            $jsonFinalPath, 
            json_encode($jsonExportData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        );

        $attachments[] = $jsonFinalPath;

        $mailServices->sendExportCommandReport(
            $this->studyName,
            'Export Terminated',
            "Export of {$this->studyName} to {$destinationType} completed successfully.",
            $attachments
        );

        return 0;
    }

    private function transferDataTables(string $destinationType): void
    {
        $fileName = "export_{$this->studyName}.zip";

        try {
            $this->exportStudyService->setStudyName($this->studyName);
            $this->exportStudyService->exportAllTables();

            $zipPath = $this->exportStudyService->getExportStudyResult()->getResultsAsZip();
            $this->validateZip($zipPath);

            $checksum = hash_file('sha256', $zipPath);
            $stream   = fopen($zipPath, 'rb');
            
            try {
                $success = $this->writeToDestination($destinationType, $stream, $fileName);
            } finally {
                if (is_resource($stream)) fclose($stream);
            }

            $this->updateFileIndex('data_tables', $fileName, $checksum, $success ? 'success' : 'failure');
        } catch (\Exception $e) {
            $this->error("Data tables export error: {$e->getMessage()}");
            $this->updateFileIndex('data_tables', $fileName, '', 'failure');
        }
    }

    private function transferAssociatedFiles(string $destinationType): void
    {
        $fileName = "export_files_{$this->studyName}.zip";

        try {
            $tempFile = $this->getAssociatedFilesZipPath();
            $this->validateZip($tempFile);

            $checksum = hash_file('sha256', $tempFile);
            $stream   = fopen($tempFile, 'rb');
            
            try {
                $success = $this->writeToDestination($destinationType, $stream, $fileName);
            } finally {
                if (is_resource($stream)) fclose($stream);
                unlink($tempFile);
            }

            $this->updateFileIndex('associated_files', $fileName, $checksum, $success ? 'success' : 'failure');
        } catch (\Exception $e) {
            $this->error("Associated files export error: {$e->getMessage()}");
            $this->updateFileIndex('associated_files', $fileName, '', 'failure');
        }
    }

    protected function getAssociatedFilesZipPath(): string
    {
        $zipFile        = tempnam(ini_get('upload_tmp_dir'), 'TMPFILES_');
        $tempEntryFiles = [];

        $zip = new ZipArchive();
        if ($zip->open($zipFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException("Cannot create temporary ZIP archive.");
        }

        try {
            $zip->addFromString('README', 'Associated files for study ' . $this->studyName);

            $files = FrameworkAdapter::getStoredFiles($this->studyName);
            $this->line("  Found " . count($files) . " associated file(s).");

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

        return $zipFile;
    }

    private function validateZip(string $filePath): void
    {
        $zip    = new ZipArchive();
        $result = $zip->open($filePath, ZipArchive::CHECKCONS);

        if ($result !== true) {
            throw new \RuntimeException("Invalid ZIP archive (ZipArchive error code: {$result}).");
        }
        $zip->close();
    }

    private function updateFileIndex(string $export, string $fileName, string $checksum, string $status): void
    {
        $this->fileIndex[$export] = [
            'export'          => $export,
            'fileName'        => $fileName,
            'checksum_sha256' => $checksum,
            'status'          => $status,
        ];
        $this->table(['export', 'fileName', 'checksum_sha256', 'status'], array_values($this->fileIndex));
    }

    private function transferDicoms(string $destinationType, SpreadsheetInterface $spreadsheetInterface): array
    {
        $studies = $this->getDicomStudiesToSend();
        $fullSeriesOrthancIds = [];

        $totalStudies = $this->getCountTotalStudies();
        $progressBar = $this->output->createProgressBar($totalStudies);
        $progressBar->start();

        foreach ($studies as $study) {
            $orthancSeriesIds = array_map(
                fn ($series) => $series['orthanc_id'],
                $study['studies']['dicom_series']
            );

            array_push($fullSeriesOrthancIds, ...$orthancSeriesIds);
            $studyOrthancId = $study['studies']['orthanc_id'];
            $patientCode    = $study['visit']['patient']['code'];
            $visitType      = $study['visit']['visit_type']['name'];
            $visitName      = $study['visit']['visit_type']['visit_group']['study_name'];

            $fileName = $studyOrthancId . '.zip';

            $this->dicomStudyIndex[$studyOrthancId] = [
                'orthancStudyId'  => $studyOrthancId,
                'patientCode'     => $patientCode,
                'visitType'       => $visitType,
                'visitName'       => $visitName,
                'checksum_sha256' => null,
                'status'          => 'sending'
            ];

            switch ($destinationType) {
                case CombinedDestinations::ORTHANCPEER->value:
                    $this->buildDicomProtocolIndex($studyOrthancId, $patientCode, $visitType, $visitName, $orthancSeriesIds);
                    $idWithLevels = array_map(fn($id) => ['Level' => 'Series', 'ID' => $id], $orthancSeriesIds);
                    $jobData = $this->orthancService->sendToPeerWithAcceleratorIfAvailable($this->destinatorName, $idWithLevels, true);
                    $this->waitForJobFinished($jobData['ID']);
                    $this->updateStudyStatus($studyOrthancId, 'success');
                    break;

                case CombinedDestinations::DICOMWEB->value:
                    $this->buildDicomProtocolIndex($studyOrthancId, $patientCode, $visitType, $visitName, $orthancSeriesIds);
                    $success = $this->sendSeriesToDicomWeb($orthancSeriesIds);
                    $this->updateStudyStatus($studyOrthancId, $success ? 'success' : 'failure');
                    break;

                case CombinedDestinations::WEBDAV->value:
                case CombinedDestinations::FTP->value:
                case CombinedDestinations::SFTP->value:
                case CombinedDestinations::S3->value:
                case CombinedDestinations::AZURESTORAGE->value:
                    $filePath = $this->getZipToSend($orthancSeriesIds);
                    $checksum = hash_file('sha256', $filePath);
                    $this->dicomStudyIndex[$studyOrthancId]['checksum_sha256'] = $checksum;
                    $stream = fopen($filePath, 'rb');
                    
                    try {
                        $success = $this->writeToDestination($destinationType, $stream, $fileName);
                    } finally {
                        if (is_resource($stream)) fclose($stream);
                        unlink($filePath);
                    }

                    if (!$success && in_array($destinationType, [CombinedDestinations::FTP->value, CombinedDestinations::SFTP->value])) {
                        throw new GaelOException(strtoupper($destinationType) . " upload failed for {$fileName}");
                    }

                    $this->updateStudyStatus($studyOrthancId, $success ? 'success' : "failure");
                    if ($success) Log::info(strtoupper($destinationType) . " upload succeeded for {$fileName}");
                    break;
            }

            $this->newLine();
            $progressBar->advance();
            $this->newLine();
        }

        if ($destinationType === CombinedDestinations::ORTHANCPEER->value) {
            $this->orthancService->deletePeer($this->destinatorName);
        }

        $stats = $this->getExportStats($fullSeriesOrthancIds);
        $this->table(["instanceCount", "seriesCount", "studyCount", "patientCount"], [$stats]);
        
        $spreadsheetInterface->addSheet('Export Details');
        $spreadsheetInterface->fillData(
            'Export Details',
            $destinationType === CombinedDestinations::DICOMWEB->value || $destinationType === CombinedDestinations::ORTHANCPEER->value
                ? $this->dicomIndex
                : array_values($this->dicomStudyIndex)
        );

        $spreadsheetInterface->addSheet('Export Stats');
        $spreadsheetInterface->fillData('Export Stats', [$stats]);

        $detailsCsvTemp = $spreadsheetInterface->writeToCsv('Export Details');
        $detailsCsvFinal = dirname($detailsCsvTemp) . '/details_export_dicom_' . $this->studyName . '.csv';
        rename($detailsCsvTemp, $detailsCsvFinal);

        $statsCsvTemp = $spreadsheetInterface->writeToCsv('Export Stats');
        $statsCsvFinal = dirname($statsCsvTemp) . '/stats_export_dicom_' . $this->studyName . '.csv';
        rename($statsCsvTemp, $statsCsvFinal);

        $excelTemp = $spreadsheetInterface->writeToExcel();
        $excelFinal = dirname($excelTemp) . '/rapport_export_global_' . $this->studyName . '.xlsx';
        rename($excelTemp, $excelFinal);

        return [
            $detailsCsvFinal,
            $statsCsvFinal,
            $excelFinal
        ];
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

    private function updateStudyStatus($studyOrthancId, $status)
    {
        $this->dicomStudyIndex[$studyOrthancId]['status'] = $status;
        $this->table([
            'orthancStudyId',
            'patientCode',
            'visitType',
            'visitName',
            'checksum_sha256',
            'status'
        ], array_values($this->dicomStudyIndex));
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

    private function sendSeriesToDicomWeb(array $seriesOrthancIds): bool
    {
        try {
            $this->dicomWebService->sendStudyInstancesConcurrentlyToDicomWeb($this->orthancService, $seriesOrthancIds, 5);
        } catch (Exception $e) {
            Log::error($e);
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
    }

    private function writeToDestination(string $destinationType, $stream, string $fileName): bool
    {
        return match ($destinationType) {
            CombinedDestinations::FTP->value,
            CombinedDestinations::SFTP->value         => $this->ftpClientInterface->writeStreamContent($stream, $fileName),
            CombinedDestinations::S3->value,
            CombinedDestinations::AZURESTORAGE->value => $this->objectStorage->writeStreamContent($stream, $fileName),
            CombinedDestinations::WEBDAV->value       => $this->webdavClientInterface->writeStreamContent($stream, $fileName),
            default                                   => false,
        };
    }
}