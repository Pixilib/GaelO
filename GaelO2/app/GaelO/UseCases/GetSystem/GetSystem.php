<?php

namespace App\GaelO\UseCases\GetSystem;

use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Interfaces\Adapters\FrameworkInterface;
use Exception;

class GetSystem
{

    private FrameworkInterface $frameworkInterface;

    public function __construct(FrameworkInterface $frameworkInterface)
    {
        $this->frameworkInterface = $frameworkInterface;
    }

    public function execute(GetSystemRequest $getSystemRequest, GetSystemResponse $getSystemResponse)
    {

        try {

            $version = $this->frameworkInterface->getConfig('version');

            $response = [
                'version' => $version
            ];

            $getSystemResponse->body = $response;
            $getSystemResponse->status = 200;
            $getSystemResponse->statusText = 'OK';
        } catch (GaelOException $e) {
            $getSystemResponse->body = $e->getErrorBody();
            $getSystemResponse->status = $e->statusCode;
            $getSystemResponse->statusText = $e->statusText;
        } catch (Exception $e) {
            throw $e;
        }
    }
}
