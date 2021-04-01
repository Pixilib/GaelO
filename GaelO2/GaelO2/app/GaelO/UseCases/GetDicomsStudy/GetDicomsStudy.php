<?php

namespace App\GaelO\UseCases\GetDicomsStudy;

use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Interfaces\DicomStudyRepositoryInterface;
use App\GaelO\Services\AuthorizationService;
use Exception;

class GetDicomsStudy
{

    private AuthorizationService $authorizationService;
    private DicomStudyRepositoryInterface $dicomStudyRepositoryInterface;

    public function __construct(
        AuthorizationService $authorizationService,
        DicomStudyRepositoryInterface $dicomStudyRepositoryInterface
    ) {
        $this->authorizationService = $authorizationService;
        $this->dicomStudyRepositoryInterface = $dicomStudyRepositoryInterface;
    }

    public function execute(GetDicomsStudyRequest $getDicomsStudyRequest, GetDicomsStudyResponse $getDicomsStudyResponse)
    {

        try {

            $data = $this->dicomStudyRepositoryInterface->getDicomStudyFromStudy($getDicomsStudyRequest->studyName, true);
            dd($data);
            $getDicomsStudyResponse->status =200;
            $getDicomsStudyResponse->statusText = 'OK';
            $getDicomsStudyResponse->body = $data;

        } catch (GaelOException $e) {

            $getDicomsStudyResponse->status = $e->statusCode;
            $getDicomsStudyResponse->statusText = $e->statusText;
            $getDicomsStudyResponse->body = $e->getErrorBody();
        } catch (Exception $e) {
            throw $e;
        }
    }

    private function checkAuthorization()
    {
    }
}
