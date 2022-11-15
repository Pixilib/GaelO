<?php

namespace App\GaelO\Services\FormService;

use App\GaelO\Exceptions\GaelOBadRequestException;
use App\GaelO\Interfaces\Adapters\FrameworkInterface;
use App\GaelO\Interfaces\Repositories\ReviewRepositoryInterface;
use App\GaelO\Services\GaelOStudiesService\AbstractGaelOStudy;
use App\GaelO\Services\GaelOStudiesService\AbstractVisitRules;
use App\GaelO\Services\MailServices;
use App\GaelO\Services\VisitService;

class FormService
{

    protected ReviewRepositoryInterface $reviewRepositoryInterface;
    protected VisitService $visitService;
    protected AbstractVisitRules $abstractVisitRules;
    protected MailServices $mailServices;
    protected FrameworkInterface $frameworkInterface;

    protected int $currentUserId;
    protected int $visitId;
    protected array $visitContext;
    protected string $studyName;
    protected string $visitType;
    protected string $patientId;
    protected string $patientCode;
    protected int $uploaderId;
    protected string $local;

    public function __construct(
        ReviewRepositoryInterface $reviewRepositoryInterface,
        VisitService $visitService,
        MailServices $mailServices,
        FrameworkInterface $frameworkInterface
    ) {
        $this->reviewRepositoryInterface = $reviewRepositoryInterface;
        $this->visitService = $visitService;
        $this->mailServices = $mailServices;
        $this->frameworkInterface = $frameworkInterface;
    }


    public function setCurrentUserId(int $currentUserId): void
    {
        $this->currentUserId = $currentUserId;
    }

    public function setVisitContextAndStudy(array $visitContext, string $studyName): void
    {

        $this->visitId = $visitContext['id'];
        $this->visitService->setVisitId($visitContext['id']);
        $this->visitContext = $visitContext;
        $this->visitType = $this->visitContext['visit_type']['name'];
        $this->patientId = $this->visitContext['patient_id'];
        $this->patientCode = $this->visitContext['patient']['code'];
        $this->uploaderId = $this->visitContext['creator_user_id'];
        $this->studyName = $studyName;
        $visitGroup = $this->visitContext['visit_type']['visit_group']['name'];
        $this->abstractVisitRules = AbstractGaelOStudy::getSpecificStudiesRules($this->studyName, $visitGroup, $this->visitType);
        $this->abstractVisitRules->setVisitContext($this->visitContext);
    }

    public function attachFile(array $reviewEntity, string $key, string $filename, string $mimeType, $binaryData): void
    {

        $keyMimeArray = [];

        if ($reviewEntity['local']) {
            $keyMimeArray = $this->abstractVisitRules->getAllowedKeyAndMimeTypeInvestigator();
        } else {
            $keyMimeArray = $this->abstractVisitRules->getAllowedKeyAndMimeTypeReviewer();
        }

        $expectedMime = $keyMimeArray[$key];

        if (!empty($reviewEntity['sent_files'][$key])) {
            throw new GaelOBadRequestException("Already Existing File for this review");
        }

        if ($mimeType !== $expectedMime) {
            throw new GaelOBadRequestException("File Key or Mime Not Allowed");
        }
        
        $destinationPath = $this->studyName . '/' . 'attached_review_file';

        $destinationFileName = $destinationPath . '/' . $filename;
        $this->frameworkInterface->storeFile($destinationFileName, $binaryData);

        $reviewEntity['sent_files'][$key] = $destinationFileName;

        $this->reviewRepositoryInterface->updateReviewFile($reviewEntity['id'], $reviewEntity['sent_files']);
    }

    public function removeFile(array $reviewEntity, string $key): void
    {
        if (empty($reviewEntity['sent_files'][$key])) {
            throw new GaelOBadRequestException('Non exisiting key file in review');
        }

        $targetedFile = $reviewEntity['sent_files'][$key];
        $this->frameworkInterface->deleteFile($targetedFile);

        unset($reviewEntity['sent_files'][$key]);
        $this->reviewRepositoryInterface->updateReviewFile($reviewEntity['id'], $reviewEntity['sent_files']);
    }

}
