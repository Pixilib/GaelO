<?php

namespace App\GaelO\UseCases\GetReadiness;

use App\GaelO\Exceptions\AbstractGaelOException;
use App\GaelO\Interfaces\Repositories\CountryRepositoryInterface;
use App\GaelO\Services\OrthancService;
use Exception;

class GetReadiness
{

    private OrthancService $orthancService;
    private CountryRepositoryInterface $countryRepositoryInterface;

    public function __construct(OrthancService $orthancService, CountryRepositoryInterface $countryRepositoryInterface)
    {
        $this->orthancService = $orthancService;
        $this->countryRepositoryInterface = $countryRepositoryInterface;
    }

    public function execute(GetReadinessRequest $getReadinessRequest, GetReadinessResponse $getReadinessResponse)
    {
        try {

            $countries = $this->countryRepositoryInterface->getAllCountries();

            if(sizeof($countries) === 0){
                throw new Exception("Missing countries record");
            }

            $this->orthancService->setOrthancServer(true);
            $this->orthancService->getSystem();

            $this->orthancService->setOrthancServer(false);
            $this->orthancService->getSystem();

            $getReadinessResponse->status = '200';
            $getReadinessResponse->statusText = 'OK';

        } catch (AbstractGaelOException $e) {
            $getReadinessResponse->body = $e->getErrorBody();
            $getReadinessResponse->status = $e->statusCode;
            $getReadinessResponse->statusText = $e->statusText;
        } catch (Exception $e) {
            throw $e;
        }
    }
}
