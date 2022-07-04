<?php

namespace App\GaelO\UseCases\GetCountry;

use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Interfaces\Repositories\CountryRepositoryInterface;
use App\GaelO\Entities\CountryEntity;
use App\GaelO\Services\AuthorizationService\AuthorizationUserService;
use App\GaelO\UseCases\GetCountry\GetCountryRequest;
use App\GaelO\UseCases\GetCountry\GetCountryResponse;
use Exception;

class GetCountry
{

    private CountryRepositoryInterface $countryRepositoryInterface;
    private AuthorizationUserService $authorizationUserService;

    public function __construct(CountryRepositoryInterface $countryRepositoryInterface, AuthorizationUserService $authorizationUserService)
    {
        $this->countryRepositoryInterface = $countryRepositoryInterface;
        $this->authorizationUserService = $authorizationUserService;
    }

    public function execute(GetCountryRequest $getCountryRequest, GetCountryResponse $getCountryResponse): void
    {
        try {

            $this->checkAuthorization($getCountryRequest->currentUserId);
            $code = $getCountryRequest->code;
            if ($code === null) {
                $responseArray = [];
                $countries = $this->countryRepositoryInterface->getAllCountries();
                foreach ($countries as $country) {
                    $responseArray[] = CountryEntity::fillFromDBReponseArray($country);
                }
                $getCountryResponse->body = $responseArray;
            } else {
                $country = $this->countryRepositoryInterface->getCountryByCode($code);
                $getCountryResponse->body = CountryEntity::fillFromDBReponseArray($country);
            }
            $getCountryResponse->status = 200;
            $getCountryResponse->statusText = 'OK';
        } catch (GaelOException $e) {
            $getCountryResponse->status = $e->statusCode;
            $getCountryResponse->statusText = $e->statusText;
            $getCountryRequest->body = $e->getErrorBody();
        } catch (Exception $e) {
            throw $e;
        }
    }

    private function checkAuthorization(int $userId)
    {
        $this->authorizationUserService->setUserId($userId);
        if (!$this->authorizationUserService->isAdmin()) {
            throw new GaelOForbiddenException();
        };
    }
}
