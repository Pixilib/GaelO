<?php

namespace App\GaelO\Services\FormService;

use App\GaelO\Exceptions\GaelOBadRequestException;
use App\GaelO\Interfaces\Adapters\FrameworkInterface;
use App\GaelO\Interfaces\Repositories\ReviewRepositoryInterface;
use App\GaelO\Services\GaelOStudiesService\AbstractGaelOStudy;
use App\GaelO\Services\GaelOStudiesService\AbstractVisitDecisions;
use App\GaelO\Services\GaelOStudiesService\AbstractVisitRules;
use App\GaelO\Services\MailServices;
use App\GaelO\Services\VisitService;

abstract class FormService
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
    protected bool $local;

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
        //SK a revoir si c'est une bonne idée, le visit context sert a avoir la decision etc (objet a part?)
        $this->abstractVisitRules->setVisitContext($this->visitContext);
        $this->abstractVisitRules->setLocalForm($this->local);
    }

    public abstract function saveForm(array $data, bool $validated, ?bool $adjudication = null): int;
    public abstract function updateForm(int $reviewId, array $data, bool $validated);
    public abstract function unlockForm(int $reviewId);
    public abstract function deleteForm(int $reviewId);

    public function attachFile(array $reviewEntity, string $key, string $filename, string $mimeType, $binaryData): void
    {

        $keyMimeArray = [];

        //SK Checker que local de review est bien le meme que local de la classe ? 
        if ($reviewEntity['local']) {
            $keyMimeArray = $this->abstractVisitRules->getAllowedKeyAndMimeTypeInvestigator();
        } else {
            $isAdjudication = $reviewEntity['adjudication'];
            if ($isAdjudication) $keyMimeArray = $this->abstractVisitRules->getAllowedKeyAndMimeTypeAdjudication();
            else $keyMimeArray = $this->abstractVisitRules->getAllowedKeyAndMimeTypeReviewer();
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

    public function getVisitRules(): AbstractVisitRules
    {
        return $this->abstractVisitRules;
    }

    public function getVisitDecisionObject() : AbstractVisitDecisions
    {
        return $this->abstractVisitRules->getVisitDecisionObject();
    }
}
