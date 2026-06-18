<?php

namespace App\Console\Commands;

use App\GaelO\Adapters\FrameworkAdapter;
use App\GaelO\Interfaces\Adapters\FTPClientInterface;
use App\GaelO\Interfaces\Adapters\ObjectStorageInterface;
use App\GaelO\Interfaces\Adapters\WebdavClientInterface;
use App\GaelO\Services\ExportStudyService;
use App\GaelO\Services\MailServices;
use Illuminate\Console\Command;
use ZipArchive;

enum ExportDataDestinations: string
{
    case FTP          = "ftp";
    case SFTP         = "sftp";
    case S3           = "s3";
    case AZURESTORAGE = "azure-storage";
    case WEBDAV       = "webdav";
}

class ExportStudyData extends Command
{
    private ExportStudyService $exportStudyService;
    private FTPClientInterface $ftpClientInterface;
    private WebdavClientInterface $webdavClientInterface;
    private ObjectStorageInterface $objectStorage;

    private string $studyName;
    private array $index = [];

    protected $signature = 'gaelo:export-study-data';
    protected $description = 'Export study data tables and/or associated files to a destination endpoint';

    public function handle(
        ExportStudyService $exportStudyService,
        FTPClientInterface $ftpClientInterface,
        WebdavClientInterface $webdavClientInterface,
        ObjectStorageInterface $objectStorage,
        MailServices $mailServices
    ): int {
        $this->exportStudyService    = $exportStudyService;
        $this->ftpClientInterface    = $ftpClientInterface;
        $this->webdavClientInterface = $webdavClientInterface;
        $this->objectStorage         = $objectStorage;

        $this->studyName = $this->ask('Study to export:');

        $exportData  = $this->confirm('Export data tables (forms, visits, reviews)?', true);
        $exportFiles = $this->confirm('Export associated files?', true);

        if (!$exportData && !$exportFiles) {
            $this->error('Nothing selected, aborting.');
            return 1;
        }

        $destinationType = $this->choice(
            'Destination Type',
            array_column(ExportDataDestinations::cases(), 'value')
        );

        switch ($destinationType) {
            case ExportDataDestinations::FTP->value:
                $host     = $this->ask('FTP Host: (ex: ftp.example.com)');
                $port     = (int) $this->ask('FTP Port: (ex: 21)');
                $username = $this->ask('FTP Username:');
                $password = $this->secret('FTP Password:');
                $this->ftpClientInterface->setFTPServer($host, $port, $username, $password, false, false);
                break;
            case ExportDataDestinations::SFTP->value:
                $host     = $this->ask('SFTP Host: (ex: sftp.example.com)');
                $port     = (int) $this->ask('SFTP Port: (ex: 22)');
                $username = $this->ask('SFTP Username:');
                $password = $this->secret('SFTP Password:');
                $this->ftpClientInterface->setFTPServer($host, $port, $username, $password, true, false);
                break;
            case ExportDataDestinations::S3->value:
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
            case ExportDataDestinations::AZURESTORAGE->value:
                $container        = $this->ask('Azure Container name:');
                $connectionString = $this->secret('Azure Connection String:');
                $this->objectStorage->setObjectStorageServer(
                    ObjectStorageInterface::PROVIDER_AZURE,
                    $container,
                    connectionString: $connectionString
                );
                break;
            case ExportDataDestinations::WEBDAV->value:
                $url      = $this->ask('WebDAV URL: (ex: https://www.example.com/webdav)');
                $username = $this->ask('WebDAV Username:');
                $password = $this->secret('WebDAV Password:');
                $this->webdavClientInterface->setWebdavServer($url, $username, $password);
                break;
        }

        if ($exportData) {
            $this->transferDataTables($destinationType);
        }

        if ($exportFiles) {
            $this->transferAssociatedFiles($destinationType);
        }

        $mailServices->sendExportCommandReport(
            $this->studyName,
            'Export Terminated',
            "Export of {$this->studyName} to {$destinationType} completed",
            []
        );

        return 0;
    }

    private function transferDataTables(string $destinationType): void
    {
        $fileName = "export_{$this->studyName}.zip";

        try {
            $this->exportStudyService->setStudyName($this->studyName);
            $this->exportStudyService->exportAllTables();

            // getResultsAsZip() returns a file path, not the file content
            $zipPath = $this->exportStudyService->getExportStudyResult()->getResultsAsZip();

            $this->validateZip($zipPath);

            $checksum = hash_file('sha256', $zipPath);
            $stream   = fopen($zipPath, 'rb');
            
            try {
                $success = $this->writeToDestination($destinationType, $stream, $fileName);
            } finally {
                if (is_resource($stream)) {
                    fclose($stream);
                }
            }

            $this->updateIndex('data_tables', $fileName, $checksum, $success ? 'success' : 'failure');
        } catch (\Exception $e) {
            $this->error("Data tables export error: {$e->getMessage()}");
            $this->updateIndex('data_tables', $fileName, '', 'failure');
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
                if (is_resource($stream)) {
                    fclose($stream);
                }
                // Always clean up the temporary file
                unlink($tempFile);
            }

            $this->updateIndex('associated_files', $fileName, $checksum, $success ? 'success' : 'failure');
        } catch (\Exception $e) {
            $this->error("Associated files export error: {$e->getMessage()}");
            $this->updateIndex('associated_files', $fileName, '', 'failure');
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
                $this->line("  Adding: {$file}");
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

    private function writeToDestination(string $destinationType, $stream, string $fileName): bool
    {
        return match ($destinationType) {
            ExportDataDestinations::FTP->value,
            ExportDataDestinations::SFTP->value         => $this->ftpClientInterface->writeStreamContent($stream, $fileName),
            ExportDataDestinations::S3->value,
            ExportDataDestinations::AZURESTORAGE->value => $this->objectStorage->writeStreamContent($stream, $fileName),
            ExportDataDestinations::WEBDAV->value       => $this->webdavClientInterface->writeStreamContent($stream, $fileName),
            default                                     => false,
        };
    }

    private function updateIndex(string $export, string $fileName, string $checksum, string $status): void
    {
        $this->index[$export] = [
            'export'          => $export,
            'fileName'        => $fileName,
            'checksum_sha256' => $checksum,
            'status'          => $status,
        ];
        $this->table(
            ['export', 'fileName', 'checksum_sha256', 'status'],
            array_values($this->index)
        );
    }
}
