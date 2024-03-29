<?php

namespace App\GaelO\Services\AuthorizationService;

use App\GaelO\Constants\Constants;
use App\GaelO\Constants\Enums\InvestigatorFormStateEnum;
use App\GaelO\Constants\Enums\QualityControlStateEnum;
use App\GaelO\Constants\Enums\UploadStatusEnum;
use App\GaelO\Interfaces\Repositories\VisitRepositoryInterface;
use App\GaelO\Services\AuthorizationService\AuthorizationPatientService;

class AuthorizationVisitService
{

    private VisitRepositoryInterface $visitRepositoryInterface;
    private AuthorizationPatientService $authorizationPatientService;
    private int $visitId;
    private int $userId;
    private string $studyName;
    private array $visitContext;
    private string $stateQualityControl;
    private int $patientCenter;
    private string $patientId;
    private string $visitUploadStatus;
    private string $stateInvestigatorForm;

    public function __construct(VisitRepositoryInterface $visitRepositoryInterface, AuthorizationPatientService $authorizationPatientService)
    {
        $this->visitRepositoryInterface = $visitRepositoryInterface;
        $this->authorizationPatientService = $authorizationPatientService;
    }

    public function setUserId(int $userId)
    {
        $this->authorizationPatientService->setUserId($userId);
        $this->userId = $userId;
    }

    public function setVisitId(int $visitId)
    {
        $this->visitId = $visitId;
    }

    public function setStudyName(string $studyName)
    {
        $this->studyName = $studyName;
        $this->authorizationPatientService->setStudyName($studyName);
    }

    public function setVisitContext(array $visitContext)
    {
        $this->visitContext = $visitContext;
    }

    private function fillVisitData()
    {
        if (!isset($this->visitContext)) $this->visitContext = $this->visitRepositoryInterface->getVisitContext($this->visitId);

        $this->stateQualityControl = $this->visitContext['state_quality_control'];
        $this->patientCenter = $this->visitContext['patient']['center_code'];
        $this->patientId = $this->visitContext['patient']['id'];
        $this->visitUploadStatus = $this->visitContext['upload_status'];
        $this->stateInvestigatorForm = $this->visitContext['state_investigator_form'];
    }

    private function isAllowedStatusQC()
    {
        return in_array($this->stateQualityControl, [QualityControlStateEnum::NOT_DONE->value, QualityControlStateEnum::WAIT_DEFINITIVE_CONCLUSION->value]);
    }

    private function isAllowedInvestigatorFormStatus()
    {
        return in_array($this->stateInvestigatorForm, [InvestigatorFormStateEnum::DONE->value, InvestigatorFormStateEnum::NOT_NEEDED->value]);
    }

    private function isControllerAllowed(): bool
    {
        //For controller visit QC status be not done or awaiting definitive conclusion, Investigator Form should be Done or Not Needed and Upload status should be done
        return $this->isAllowedStatusQC() && $this->isAllowedInvestigatorFormStatus() && $this->visitUploadStatus === UploadStatusEnum::DONE->value;
    }


    public function isVisitAllowed(string $requestedRole): bool
    {

        $this->fillVisitData();

        $this->authorizationPatientService->setPatientEntity($this->visitContext['patient']);

        if ($requestedRole === Constants::ROLE_REVIEWER) {
            //Check parent patient allowed and has one awaiting review visit
            return $this->authorizationPatientService->isPatientAllowed($requestedRole) && $this->visitRepositoryInterface->isParentPatientHavingOneVisitAwaitingReview($this->visitId, $this->studyName, $this->userId);
        } else if ($requestedRole === Constants::ROLE_CONTROLLER) {
            $showAll = $this->authorizationPatientService->getAuthorizationStudyService()->getStudyEntity()->controllerShowAll;
            //if not show all check QC status to allow access
            if (!$showAll  && !$this->isControllerAllowed()) return false;
        }

        //For all other role access depend on patient access
        return $this->authorizationPatientService->isPatientAllowed($requestedRole);
    }
}
