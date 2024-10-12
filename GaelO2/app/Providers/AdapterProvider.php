<?php

namespace App\Providers;

use App\Console\Commands\GaelODeleteRessourcesRepository;
use App\GaelO\Adapters\AzureCacheAdapter;
use App\GaelO\Adapters\DatabaseDumperAdapter;
use App\GaelO\Adapters\FrameworkAdapter;
use App\GaelO\Adapters\FtpClientAdapter;
use App\GaelO\Adapters\HttpClientAdapter;
use App\GaelO\Adapters\JobAdapter;
use App\GaelO\Adapters\MimeAdapter;
use App\GaelO\Adapters\PdfAdapter;
use App\GaelO\Adapters\PhoneNumberAdapter;
use App\GaelO\Interfaces\Adapters\DatabaseDumperInterface;
use App\GaelO\Interfaces\Adapters\FrameworkInterface;
use App\GaelO\Interfaces\Adapters\FTPClientInterface;
use App\GaelO\Interfaces\Adapters\HttpClientInterface;
use App\GaelO\Interfaces\Adapters\JobInterface;
use App\GaelO\Interfaces\Adapters\MimeInterface;
use App\GaelO\Interfaces\Adapters\PdfInterface;
use App\GaelO\Interfaces\Adapters\PhoneNumberInterface;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\ServiceProvider;
use League\Flysystem\AzureBlobStorage\AzureBlobStorageAdapter;
use League\Flysystem\Filesystem;
use MicrosoftAzure\Storage\Blob\BlobRestProxy;

class AdapterProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind(DatabaseDumperInterface::class, DatabaseDumperAdapter::class);
        $this->app->bind(HttpClientInterface::class, HttpClientAdapter::class);
        $this->app->bind(FrameworkInterface::class, FrameworkAdapter::class);
        $this->app->bind(MimeInterface::class, MimeAdapter::class);
        $this->app->bind(PhoneNumberInterface::class, PhoneNumberAdapter::class);
        $this->app->bind(JobInterface::class, JobAdapter::class);
        $this->app->bind(PdfInterface::class, PdfAdapter::class);
        $this->app->bind(GaelODeleteRessourcesRepository::class, GaelODeleteRessourcesRepository::class);
        $this->app->bind(FTPClientInterface::class, FtpClientAdapter::class);
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        Storage::extend('azure', function (Application $app, array $config) {
            $client = BlobRestProxy::createBlobService($config['dsn']);
            $adapter = new AzureBlobStorageAdapter(
                $client,
                $config['container'],
                $config['prefix'],
            );

            return new FilesystemAdapter(new Filesystem($adapter, $config),$adapter, $config);
        });

        Cache::extend('azure', function($app, $config){
            $client = BlobRestProxy::createBlobService($config['dsn']);
            $adapter = new AzureBlobStorageAdapter(
                $client,
                $config['container'],
                $config['prefix'],
            );
    
            $fileSystem = new Filesystem($adapter, $config);

			return Cache::repository(new AzureCacheAdapter($fileSystem));
		});
    }
}
