<?php

namespace App\GaelO\Services;

use App\GaelO\Adapters\Psr7ResponseAdapter;
use App\GaelO\Constants\Enums\AnonProfileEnum;
use App\GaelO\Constants\SettingsConstants;
use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Interfaces\Adapters\FrameworkInterface;
use App\GaelO\Interfaces\Adapters\HttpClientInterface;
use App\GaelO\Services\GaelOProcessingService\GaelOProcessingService;
use App\GaelO\Services\StoreObjects\OrthancMetaData;
use App\GaelO\Services\StoreObjects\TagAnon;
use App\GaelO\Services\StoreObjects\OrthancStudy;
use App\GaelO\Services\StoreObjects\OrthancStudyImport;
use App\GaelO\Util;
use Exception;

class DicomWebService
{
    private HttpClientInterface $httpClientInterface;

    public function __construct(HttpClientInterface $httpClientInterface)
    {
        $this->httpClientInterface = $httpClientInterface;
    }

    public function setDicomWebServer(string $url, string $login, string $password, string $token): void
    {
        if ($url)
            $this->httpClientInterface->setUrl($url);
        if ($login && $password)
            $this->httpClientInterface->setBasicAuthentication($login, $password);
        if($token)
            $this->httpClientInterface->setAuthorizationToken($token);
    }

    public function storeDicom($dicomPath)
    {
        $this->httpClientInterface->uploadFile('POST', '/studies', $dicomPath);
    }
}
