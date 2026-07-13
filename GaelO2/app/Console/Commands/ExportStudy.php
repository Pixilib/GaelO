<?php

namespace App\Console\Commands;

use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Interfaces\Adapters\SpreadsheetInterface;
use App\GaelO\Services\CombinedDestinations;
use App\GaelO\Services\CommandExportStudyService;
use App\GaelO\Services\MailServices;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ExportStudy extends Command
{
    protected $signature = 'gaelo:export-study';
    protected $description = 'Export study data tables, associated files, and/or DICOMs to a destination endpoint';

    private string $studyName;

    private array $fileIndex = [];
    private array $dicomStudyIndex = [];
    private array $dicomIndex = [];

    public function handle(
        CommandExportStudyService $commandExportStudyService,
        MailServices $mailServices,
        SpreadsheetInterface $spreadsheetInterface
    ): int {
        $this->studyName = $this->ask('Study to export:');
        $commandExportStudyService->setStudyName($this->studyName);

        $exportData  = $this->confirm('Export data tables (forms, visits, reviews)?', true);
        $exportFiles = $this->confirm('Export associated files?', true);
        $exportDicom = $this->confirm('Export DICOMs?', true);

        if (!$exportData && !$exportFiles && !$exportDicom) {
            $this->error('Nothing selected, aborting.');
            return 1;
        }

        if ($exportDicom) {
            $commandExportStudyService->setWithDeletedStudies($this->confirm('Includes deleted studies?', false));
            $commandExportStudyService->setWithDeletedSeries($this->confirm('Includes deleted series?', false));
        }

        $availableDestinations = array_column(CombinedDestinations::cases(), 'value');

        $destinationType = $this->choice('Destination Type', array_values($availableDestinations));

        switch ($destinationType) {
            case CombinedDestinations::ORTHANCPEER->value:
                $destinatorName  = $this->ask('Orthanc Destinator Name: (ex: sanofi)');
                $orthancAddress  = $this->ask('Orthanc URL: (ex: http://www.example.com:8042) ');
                $orthancUsername = $this->ask('Orthanc Username: ');
                $orthancPassword = $this->secret('Orthanc Password: ');
                $commandExportStudyService->configureOrthancPeer($destinatorName, $orthancAddress, $orthancUsername, $orthancPassword);
                break;
            case CombinedDestinations::WEBDAV->value:
                $url      = $this->ask('WebDAV URL: (ex: https://www.example.com/webdav)');
                $username = $this->ask('WebDAV Username:');
                $password = $this->secret('WebDAV Password:');
                $commandExportStudyService->configureWebdav($url, $username, $password);
                break;
            case CombinedDestinations::FTP->value:
                $host     = $this->ask('FTP Host: (ex: ftp.example.com)');
                $port     = (int) $this->ask('FTP Port: (ex: 21)');
                $username = $this->ask('FTP Username:');
                $password = $this->secret('FTP Password:');
                $commandExportStudyService->configureFtp($host, $port, $username, $password, false);
                break;
            case CombinedDestinations::SFTP->value:
                $host     = $this->ask('SFTP Host: (ex: sftp.example.com)');
                $port     = (int) $this->ask('SFTP Port: (ex: 22)');
                $username = $this->ask('SFTP Username:');
                $password = $this->secret('SFTP Password:');
                $commandExportStudyService->configureFtp($host, $port, $username, $password, true);
                break;
            case CombinedDestinations::S3->value:
                $bucket    = $this->ask('S3 Bucket name:');
                $region    = $this->ask('S3 Region: (e.g. eu-west-1)');
                $accessKey = $this->ask('S3 Access Key ID:');
                $secretKey = $this->secret('S3 Secret Access Key:');
                $endpoint  = $this->ask('S3 Custom Endpoint (leave empty for AWS):') ?: null;
                $commandExportStudyService->configureS3($bucket, $region, $accessKey, $secretKey, $endpoint);
                break;
            case CombinedDestinations::AZURESTORAGE->value:
                $container        = $this->ask('Azure Container name:');
                $connectionString = $this->secret('Azure Connection String:');
                $commandExportStudyService->configureAzure($container, $connectionString);
                break;
            case CombinedDestinations::DICOMWEB->value:
                $dicomAddress     = $this->ask('Dicom URL base: (ex: http://orthancdestination:8042/dicom-web) ');
                $dicomWebUsername = $this->ask('Dicom Username: ');
                $dicomWebPassword = $this->secret('Dicom Password: ');
                $dicomWebToken    = $this->secret('Dicom Token') ?: "";
                $commandExportStudyService->configureDicomWeb($dicomAddress, $dicomWebUsername, $dicomWebPassword, $dicomWebToken);
                break;
        }


        $attachments = [];

        if ($exportData) {
            $this->info("Exporting Data Tables...");
            $this->transferDataTables($destinationType, $commandExportStudyService);
        }

        if ($exportFiles) {
            $this->info("Exporting Associated Files...");
            $this->transferAssociatedFiles($destinationType, $commandExportStudyService);
        }

        if ($exportData || $exportFiles) {
            $spreadsheetInterface->addSheet('Files Details');
            $spreadsheetInterface->fillData('Files Details', array_values($this->fileIndex));
            $attachments[] = $spreadsheetInterface->writeToCsv('Files Details');
        }

        if ($exportDicom) {
            $this->info("Exporting DICOMs...");
            $dicomAttachments = $this->transferDicoms($destinationType, $spreadsheetInterface, $commandExportStudyService);
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

    private function transferDataTables(string $destinationType, CommandExportStudyService $commandExportStudyService): void
    {
        $fileName = "export_{$this->studyName}.zip";

        try {
            $zipPath = $commandExportStudyService->buildDataTablesZip();
            $commandExportStudyService->validateZip($zipPath);

            $checksum = hash_file('sha256', $zipPath);
            $stream   = fopen($zipPath, 'rb');

            try {
                $success = $commandExportStudyService->writeToDestination($destinationType, $stream, $fileName);
            } finally {
                if (is_resource($stream)) fclose($stream);
            }

            $this->updateFileIndex('data_tables', $fileName, $checksum, $success ? 'success' : 'failure');
        } catch (\Exception $e) {
            $this->error("Data tables export error: {$e->getMessage()}");
            $this->updateFileIndex('data_tables', $fileName, '', 'failure');
        }
    }

    private function transferAssociatedFiles(string $destinationType, CommandExportStudyService $commandExportStudyService): void
    {
        $fileName = "export_files_{$this->studyName}.zip";

        try {
            $zipInfo  = $commandExportStudyService->buildAssociatedFilesZip();
            $tempFile = $zipInfo['path'];
            $this->line("  Found " . $zipInfo['count'] . " associated file(s).");

            $commandExportStudyService->validateZip($tempFile);

            $checksum = hash_file('sha256', $tempFile);
            $stream   = fopen($tempFile, 'rb');

            try {
                $success = $commandExportStudyService->writeToDestination($destinationType, $stream, $fileName);
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

    private function transferDicoms(string $destinationType, SpreadsheetInterface $spreadsheetInterface, CommandExportStudyService $commandExportStudyService): array
{
    $studies = $commandExportStudyService->getDicomStudiesToSend();
    $fullSeriesOrthancIds = [];

    $totalStudies = $commandExportStudyService->getCountTotalStudies();
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
                $this->dicomIndex = array_merge(
                    $this->dicomIndex,
                    $commandExportStudyService->buildDicomProtocolIndex($studyOrthancId, $patientCode, $visitType, $visitName, $orthancSeriesIds)
                );
                $idWithLevels = array_map(fn($id) => ['Level' => 'Series', 'ID' => $id], $orthancSeriesIds);
                $jobData = $commandExportStudyService->sendToPeer($idWithLevels);
                $commandExportStudyService->waitForJobFinished($jobData['ID']);
                $this->updateStudyStatus($studyOrthancId, 'success');
                break;

            case CombinedDestinations::DICOMWEB->value:
                $this->dicomIndex = array_merge(
                    $this->dicomIndex,
                    $commandExportStudyService->buildDicomProtocolIndex($studyOrthancId, $patientCode, $visitType, $visitName, $orthancSeriesIds)
                );
                $success = $commandExportStudyService->sendSeriesToDicomWeb($orthancSeriesIds);
                $this->updateStudyStatus($studyOrthancId, $success ? 'success' : 'failure');
                break;

            case CombinedDestinations::WEBDAV->value:
            case CombinedDestinations::FTP->value:
            case CombinedDestinations::SFTP->value:
            case CombinedDestinations::S3->value:
            case CombinedDestinations::AZURESTORAGE->value:
                $filePath = $commandExportStudyService->getZipToSend($orthancSeriesIds);
                $checksum = hash_file('sha256', $filePath);
                $this->dicomStudyIndex[$studyOrthancId]['checksum_sha256'] = $checksum;
                
                $stream = fopen($filePath, 'rb');
                try {
                    $success = $commandExportStudyService->writeToDestination($destinationType, $stream, $fileName);
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
        $commandExportStudyService->deleteOrthancPeer();
    }

    $stats = $commandExportStudyService->getExportStats($fullSeriesOrthancIds);
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

    private function updateStudyStatus(string $studyOrthancId, string $status): void
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
}