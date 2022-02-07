<?php

namespace App\GaelO\Services;

use App\GaelO\Interfaces\Adapters\HttpClientInterface;
use App\GaelO\Interfaces\Adapters\FrameworkInterface;
use App\GaelO\Constants\SettingsConstants;

class AzureService
{

    private HttpClientInterface $httpClientInterface;
    private FrameworkInterface $frameworkInterface;
    private $resource = "https://management.azure.com/";

    const ACI_STATUS_RUNNING = "Running";
    const ACI_STATUS_PENDING = "Pending";
    const ACI_STATUS_STOPPED = "Stopped";


    public function __construct(HttpClientInterface $httpClientInterface, FrameworkInterface $frameworkInterface)
    {
        $this->httpClientInterface = $httpClientInterface;
        $this->frameworkInterface = $frameworkInterface;
        $this->setServerAddress();
    }

  

    private function setAccessToken(): void
    {
        $authorizationToken = $this->tokenService->getToken();
        $this->httpClientInterface->setAuthorizationToken($authorizationToken);
    }

    private function setServerAddress(): void
    {
        $subID = $this->frameworkInterface::getConfig(SettingsConstants::AZURE_SUBSCRIPTION_ID);
        $ressourceGroupe = $this->frameworkInterface::getConfig(SettingsConstants::AZURE_RESOURCE_GROUP);
        $containerGroupe = $this->frameworkInterface::getConfig(SettingsConstants::AZURE_CONTAINER_GROUP);
        $url = "https://management.azure.com/subscriptions/" . $subID . "/resourceGroups/" . $ressourceGroupe . "/providers/Microsoft.ContainerInstance/containerGroups/" . $containerGroupe . "";
        $this->httpClientInterface->setUrl($url);
    }

    public function startAci(): bool
    {
        $this->setAccessToken();
        $uri = "/start?api-version=2021-09-01";
        $response = $this->httpClientInterface->rowRequest('POST', $uri, null, ['Accept' => 'application/json'])->getStatusCode();

        return $response === 202;
    }

    public function stopACI(): bool
    {
        $this->setAccessToken();
        $uri = "/stop?api-version=2021-09-01";
        $response = $this->httpClientInterface->rowRequest('POST', $uri, null, ['Accept' => 'application/json'])->getStatusCode();

        return $response === 204;
    }

    public function getStatusAci(): array
    {
        $this->setAccessToken();
        $uri = "?api-version=2021-09-01";
        $response = $this->httpClientInterface->rowRequest('GET', $uri, null, ['Accept' => 'application/json'])->getJsonBody();

        /*3 states disponible
        * Pending -> Creation en cours
        * Running -> en cours d'allumage de l'aci
        * Stopped -> Fermé
        *
        *L'ip est disponible uniquement en pending et running
        */
        $attributes = [
            'state' => $response["properties"]['instanceView']["state"],
            'ip' =>  empty($response["properties"]["ipAddress"]["ip"]) ? '' : $response["properties"]["ipAddress"]["ip"],
        ];

        return  $attributes;
    }

  
    private function waitEndOfPending(): string
    {
        $aciStatus = $this->getStatusAci();

        while ($aciStatus['state'] === AzureService::ACI_STATUS_PENDING) {
            $aciStatus = $this->getStatusAci();
            sleep(15);
        }

        return $aciStatus['state'];
    }

    public function getIP() : string
    {
        $status = $this->getStatusAci();
        $ip = $status['ip'];
        return $ip;
    }

    public function startAndWait(): void
    {
        $this->startAci();
        $this->waitEndOfPending();
    }


    public function isRunning(): Bool
    {
        $status = $this->getStatusAci();
        return $status['state'] === self::ACI_STATUS_RUNNING;
    }

    public function stopAciAndWait(): void
    {
        $this->stopACI();
        $this->waitEndOfPending();
    }

    public function isStopped(): bool
    {
        $status = $this->getStatusAci();
        return $status['state'] === self::ACI_STATUS_STOPPED;
    }
}
