<?php

namespace App\GaelO\UseCases\GetDicomsFileSupervisor;

use App\GaelO\Constants\Constants;
use App\GaelO\Exceptions\AbstractGaelOException;
use App\GaelO\Exceptions\GaelOBadRequestException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Interfaces\Repositories\DicomSeriesRepositoryInterface;
use App\GaelO\Interfaces\Repositories\StudyRepositoryInterface;
use App\GaelO\Interfaces\Repositories\VisitRepositoryInterface;
use App\GaelO\Services\AuthorizationService\AuthorizationStudyService;
use App\GaelO\Services\OrthancService;
use Exception;

class GetDicomsFileSupervisor
{

    private AuthorizationStudyService $authorizationStudyService;
    private DicomSeriesRepositoryInterface $dicomSeriesRepositoryInterface;
    private OrthancService $orthancService;
    private VisitRepositoryInterface $visitRepositoryInterface;
    private StudyRepositoryInterface $studyRepositoryInterface;
    private array $orthancSeriesIDs;
    private ?string $transferSyntaxUID;

    public function __construct(
        OrthancService $orthancService,
        AuthorizationStudyService $authorizationStudyService,
        DicomSeriesRepositoryInterface $dicomSeriesRepositoryInterface,
        StudyRepositoryInterface $studyRepositoryInterface,
        VisitRepositoryInterface $visitRepositoryInterface
    ) {
        $this->authorizationStudyService = $authorizationStudyService;
        $this->dicomSeriesRepositoryInterface = $dicomSeriesRepositoryInterface;
        $this->studyRepositoryInterface = $studyRepositoryInterface;
        $this->orthancService = $orthancService;
        $this->orthancService->setOrthancServer(true);
        $this->visitRepositoryInterface = $visitRepositoryInterface;
    }

    public function execute(GetDicomsFileSupervisorRequest $getDicomsFileSupervisorRequest, GetDicomsFileSupervisorResponse $getDicomsFileSupervisorResponse)
    {

        try {

            if (empty($getDicomsFileSupervisorRequest->seriesInstanceUID)) {
                throw new GaelOBadRequestException('Missing Series Instance UID');
            }

            //store requested TransferSyntaxUID
            $this->transferSyntaxUID = $getDicomsFileSupervisorRequest->transferSyntaxUID;

            //Get Related visit ID of requested seriesInstanceUID
            $visitIds = $this->dicomSeriesRepositoryInterface->getRelatedVisitIdFromSeriesInstanceUID($getDicomsFileSupervisorRequest->seriesInstanceUID, true);

            //Get Contexts of these visits
            $contexts = $this->visitRepositoryInterface->getVisitContextByVisitIdArray($visitIds);

            //Extract parent StudyName
            $studyNames = [];
            foreach ($contexts as $context) {
                $studyNames[] = $context['patient']['study_name'];
            }

            $uniqueStudyName = array_values(array_unique($studyNames));

            //Check that all requested series comes from the same study
            if (sizeof($uniqueStudyName) != 1) {
                throw new GaelOBadRequestException('Requested Series should come from the same study');
            }

            //Retrieve study information, in case being an ancillary study we need to retrieve original study dicom
            $originalStudyName = $uniqueStudyName[0];

            //Get original studyname if called study is an ancillary one
            $requestedStudyName = $this->studyRepositoryInterface->find($getDicomsFileSupervisorRequest->studyName);
            $originalRequestedStudyName = $requestedStudyName->getOriginalStudyName();

            //called original study and dicom original study shall be identical
            if( $originalStudyName !== $originalRequestedStudyName) throw new GaelOForbiddenException("Requested Study in not original or ancillary study of these dicoms");

            //Check that currentUser is Supervisor in this study
            $this->checkAuthorization($getDicomsFileSupervisorRequest->currentUserId, $getDicomsFileSupervisorRequest->studyName);

            //getOrthancSeriesIdArray
            $this->orthancSeriesIDs = $this->dicomSeriesRepositoryInterface->getSeriesOrthancIDsOfSeriesInstanceUIDs($getDicomsFileSupervisorRequest->seriesInstanceUID, true);

            //First output the filename, then the controller will call outputStream to get content of orthanc response
            $getDicomsFileSupervisorResponse->filename = 'DICOM_Export_' . $getDicomsFileSupervisorRequest->studyName . '.zip';
            $getDicomsFileSupervisorResponse->status = 200;
            $getDicomsFileSupervisorResponse->statusText = 'OK';
        } catch (AbstractGaelOException $e) {
            $getDicomsFileSupervisorResponse->status = $e->statusCode;
            $getDicomsFileSupervisorResponse->statusText = $e->statusText;
            $getDicomsFileSupervisorResponse->body = $e->getErrorBody();
        } catch (Exception $e) {
            throw $e;
        }
    }

    private function checkAuthorization(int $currentUserId, string $studyName)
    {
        $this->authorizationStudyService->setUserId($currentUserId);
        $this->authorizationStudyService->setStudyName($studyName);
        if (!$this->authorizationStudyService->isAllowedStudy(Constants::ROLE_SUPERVISOR)) {
            throw new GaelOForbiddenException();
        }
    }

    public function outputStream()
    {
        $this->orthancService->getOrthancZipStream($this->orthancSeriesIDs, $this->transferSyntaxUID);
    }
}
