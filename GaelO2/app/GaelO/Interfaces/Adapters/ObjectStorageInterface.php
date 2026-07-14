<?php

namespace App\GaelO\Interfaces\Adapters;

/**
 * Contract for uploading files directly to cloud storage via Flysystem.
 * Supports AWS S3 (using League\Flysystem\AwsS3V3) and
 * Azure Blob Storage (using League\Flysystem\AzureBlobStorage).
 *
 * Unlike the previous Orthanc-relay approach, writes go straight to the
 * cloud filesystem, making the adapter suitable for any binary payload
 * (DICOM zip archives, etc.), not just Orthanc-managed instances.
 */
interface ObjectStorageInterface
{
    public const PROVIDER_AWS_S3 = 'aws-s3';
    public const PROVIDER_AZURE  = 'azure';

    /**
     * Configures the Flysystem filesystem for the chosen cloud provider.
     *
     * S3 parameters  (required when provider = PROVIDER_AWS_S3):
     *   $bucketOrContainer, $region, $accessKey, $secretKey
     *   $endpoint is optional (MinIO, Scaleway, etc.)
     *
     * Azure parameters (required when provider = PROVIDER_AZURE):
     *   $bucketOrContainer (container name), $connectionString
     *
     * @param string      $provider           PROVIDER_AWS_S3 or PROVIDER_AZURE
     * @param string      $bucketOrContainer  S3 bucket name or Azure container name
     * @param string|null $region             S3 region (e.g. 'eu-west-1')
     * @param string|null $accessKey          S3 access key ID
     * @param string|null $secretKey          S3 secret access key
     * @param string|null $endpoint           S3 custom endpoint URL (optional)
     * @param string|null $connectionString   Azure storage connection string
     *
     * @throws \App\GaelO\Exceptions\GaelOException If the provider is unknown or config is incomplete
     */
    public function setObjectStorageServer(
        string  $provider,
        string  $bucketOrContainer,
        ?string $region           = null,
        ?string $accessKey        = null,
        ?string $secretKey        = null,
        ?string $endpoint         = null,
        ?string $connectionString = null
    ): void;

    /**
     * Writes a readable stream to the configured cloud storage.
     *
     * @param resource $stream          Readable stream (e.g. opened with fopen)
     * @param string   $destinationPath Target key/blob path inside the bucket/container
     *
     * @throws \App\GaelO\Exceptions\GaelOException
     */
    public function writeStreamContent($stream, string $destinationPath): bool;

    /**
     * Writes raw string content to the configured cloud storage.
     *
     * @param string $content          Raw bytes to upload
     * @param string $destinationPath  Target key/blob path inside the bucket/container
     *
     * @throws \App\GaelO\Exceptions\GaelOException
     */
    public function writeFileContent(string $content, string $destinationPath): bool;

    /**
     * Returns the currently configured cloud provider identifier.
     *
     * @return string PROVIDER_AWS_S3 or PROVIDER_AZURE
     */
    public function getProvider(): string;
}