<?php

namespace App\GaelO\Adapters;

use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Interfaces\Adapters\ObjectStorageInterface;
use Aws\S3\S3Client;
use League\Flysystem\AwsS3V3\AwsS3V3Adapter;
use AzureOss\Storage\BlobFlysystem\AzureBlobStorageAdapter;
use League\Flysystem\Filesystem;
use League\Flysystem\FilesystemException;
use AzureOss\Storage\Blob\BlobServiceClient;


/**
 * Uploads files (DICOM zips, or any binary payload) directly to
 * AWS S3 or Azure Blob Storage via Flysystem.
 *
 * Required Composer packages:
 *   league/flysystem-aws-s3-v3     (for S3)
 *   league/flysystem-azure-blob-storage (for Azure)
 */
class ObjectStorageAdapter implements ObjectStorageInterface
{
    private string $provider;
    private Filesystem $filesystem;

    // -------------------------------------------------------------------------
    // Configuration
    // -------------------------------------------------------------------------

    /**
     * Builds and stores the Flysystem filesystem for the chosen provider.
     *
     * {@inheritdoc}
     */
    public function setObjectStorageServer(
        string $provider,
        string $bucketOrContainer,
        ?string $region = null,
        ?string $accessKey = null,
        ?string $secretKey = null,
        ?string $endpoint = null,
        ?string $connectionString = null
    ): void {
        $this->provider = $provider;

        $this->filesystem = match ($provider) {
            self::PROVIDER_AWS_S3 => $this->buildS3Filesystem(
                $bucketOrContainer,
                $region,
                $accessKey,
                $secretKey,
                $endpoint
            ),
            self::PROVIDER_AZURE => $this->buildAzureFilesystem(
                $bucketOrContainer,
                $connectionString
            ),
            default => throw new GaelOException('Unknown storage provider: ' . $provider),
        };
    }

    // -------------------------------------------------------------------------
    // Write operations
    // -------------------------------------------------------------------------

    /**
     * Writes a readable stream to the configured cloud storage using Flysystem.
     *
     * {@inheritdoc}
     */
    public function writeStreamContent($stream, string $destinationPath): bool
    {
        $this->assertConfigured();

        try {
            $this->filesystem->writeStream($destinationPath, $stream);
            return true;
        } catch (FilesystemException $e) {
            throw new GaelOException('Cloud stream upload failed: ' . $e->getMessage());
        }
    }

    /**
     * Writes raw string content to the configured cloud storage using Flysystem.
     *
     * {@inheritdoc}
     */
    public function writeFileContent(string $content, string $destinationPath): bool
    {
        $this->assertConfigured();

        try {
            $this->filesystem->write($destinationPath, $content);
            return true;
        } catch (FilesystemException $e) {
            throw new GaelOException('Cloud file upload failed: ' . $e->getMessage());
        }
    }

    // -------------------------------------------------------------------------
    // Accessors
    // -------------------------------------------------------------------------

    /**
     * {@inheritdoc}
     */
    public function getProvider(): string
    {
        $this->assertConfigured();
        return $this->provider;
    }

    // -------------------------------------------------------------------------
    // Filesystem factory helpers
    // -------------------------------------------------------------------------

    /**
     * Builds a Flysystem Filesystem backed by AWS S3 (league/flysystem-aws-s3-v3).
     *
     * @throws GaelOException If required S3 credentials are missing
     */
    private function buildS3Filesystem(
        string $bucket,
        ?string $region,
        ?string $accessKey,
        ?string $secretKey,
        ?string $endpoint
    ): Filesystem {
        if (empty($accessKey) || empty($secretKey)) {
            throw new GaelOException('S3 access key and secret key are required');
        }

        $clientConfig = [
            'version' => 'latest',
            // Use the provided region as initial hint (required by the SDK).
            'region' => $region ?? 'us-west-1',
            'credentials' => [
                'key' => $accessKey,
                'secret' => $secretKey,
            ],
            '@use_aws_shared_config_files' => false,
        ];

        // Support custom endpoints (MinIO, Scaleway, OVH, etc.)
        if (!empty($endpoint)) {
            $clientConfig['endpoint'] = $endpoint;
            $clientConfig['use_path_style_endpoint'] = true;
        }

        // When no explicit region was provided and no custom endpoint is set,
        // auto-discover the real bucket region with a lightweight
        // getBucketLocation call, then rebuild the client on the correct region.
        // This prevents AuthorizationHeaderMalformed errors at upload time.
        if (empty($region) && empty($endpoint)) {
            $probeClient = new S3Client($clientConfig);
            $detectedRegion = $this->detectBucketRegion($probeClient, $bucket);
            if ($detectedRegion !== null) {
                $clientConfig['region'] = $detectedRegion;
            }
        }

        $s3Client = new S3Client($clientConfig);
        $adapter = new AwsS3V3Adapter($s3Client, $bucket);

        return new Filesystem($adapter);
    }

    /**
     * Builds a Flysystem Filesystem backed by Azure Blob Storage
     * (league/flysystem-azure-blob-storage).
     *
     * @throws GaelOException If the connection string or container name is missing
     */
    private function buildAzureFilesystem(
        string $container,
        ?string $connectionString
    ): Filesystem {

        if (empty($connectionString)) {
            throw new GaelOException('Azure connection string is required');
        }

        if (empty($container)) {
            throw new GaelOException('Azure container name is required');
        }

        $blobServiceClient = BlobServiceClient::fromConnectionString($connectionString);

        $containerClient = $blobServiceClient->getContainerClient($container);

        $adapter = new AzureBlobStorageAdapter($containerClient);

        return new Filesystem($adapter);
    }
    private function extractAccountEndpoint(string $connectionString): string
    {
        preg_match('/AccountName=([^;]+)/', $connectionString, $matches);

        if (!isset($matches[1])) {
            throw new GaelOException('Invalid Azure connection string');
        }

        return "https://{$matches[1]}.blob.core.windows.net";
    }

    // -------------------------------------------------------------------------
    // S3 region discovery
    // -------------------------------------------------------------------------

    /**
     * Resolves the actual AWS region of an S3 bucket by calling GetBucketLocation.
     *
     * AWS returns 'us-east-1' buckets as an empty string in the XML response;
     * we normalise that back to the canonical identifier.
     * Returns null on failure so the caller can fall back to the hint region.
     */
    private function detectBucketRegion(S3Client $client, string $bucket): ?string
    {
        try {
            $result = $client->getBucketLocation(['Bucket' => $bucket]);
            $location = $result->get('LocationConstraint');
            // AWS returns an empty string for us-east-1 buckets
            return (is_string($location) && $location !== '') ? $location : 'us-east-1';
        } catch (\Exception) {
            // If detection fails (permissions, network), silently fall back
            // to whatever region hint was provided by the caller.
            return null;
        }
    }

    // -------------------------------------------------------------------------
    // Guard
    // -------------------------------------------------------------------------

    /**
     * Ensures setObjectStorageServer() has been called before any I/O operation.
     *
     * @throws GaelOException
     */
    private function assertConfigured(): void
    {
        if (!isset($this->filesystem)) {
            throw new GaelOException(
                'ObjectStorageAdapter is not configured. Call setObjectStorageServer() first.'
            );
        }
    }
}