<?php

namespace App\GaelO\UseCases\GetKnownOrthancID;

use App\GaelO\Constants\Constants;
use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Exceptions\GaelONotFoundException;
use App\GaelO\Interfaces\Repositories\DicomStudyRepositoryInterface;
use App\GaelO\Services\AuthorizationService\AuthorizationStudyService;
use Exception;

class GetKnownOrthancID
{
    private DicomStudyRepositoryInterface $dicomStudyRepositoryInterface;
    private AuthorizationStudyService $authorizationStudyService;

    public function __construct(DicomStudyRepositoryInterface $dicomStudyRepositoryInterface, AuthorizationStudyService $authorizationStudyService)
    {
        $this->dicomStudyRepositoryInterface = $dicomStudyRepositoryInterface;
        $this->authorizationStudyService = $authorizationStudyService;
    }

    public function execute(GetKnownOrthancIDRequest $getKnownOrthancIDRequest, GetKnownOrthancIDResponse $getKnownOrthancIDResponse)
    {
        try {

            $this->checkAuthorization($getKnownOrthancIDRequest->currentUserId, $getKnownOrthancIDRequest->studyName);

            $known = $this->dicomStudyRepositoryInterface->isExistingOriginalOrthancStudyID($getKnownOrthancIDRequest->orthancStudyID, $getKnownOrthancIDRequest->studyName);

            if ($known) {
                $getKnownOrthancIDResponse->body = $known;
                $getKnownOrthancIDResponse->status = '200';
                $getKnownOrthancIDResponse->statusText = 'OK';
            } else {
                throw new GaelONotFoundException('Unknown Orthanc Study ID');
            }
        } catch (GaelOException $e) {
            $getKnownOrthancIDResponse->body = $e->getErrorBody();
            $getKnownOrthancIDResponse->status = $e->statusCode;
            $getKnownOrthancIDResponse->statusText = $e->statusText;
        } catch (Exception $e) {
            throw $e;
        }
    }

    private function checkAuthorization(int $currentUserId, string $studyName)
    {
        $this->authorizationStudyService->setStudyName($studyName);
        $this->authorizationStudyService->setUserId($currentUserId);
        if (!$this->authorizationStudyService->isAllowedStudy(Constants::ROLE_INVESTIGATOR)) {
            throw new GaelOForbiddenException();
        };
    }
}
