<?php

namespace App\GaelO\Services\FormService;

use App\GaelO\Entities\StudyEntity;
use App\GaelO\Exceptions\GaelOBadRequestException;
use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Interfaces\Adapters\FrameworkInterface;
use App\GaelO\Interfaces\Repositories\ReviewRepositoryInterface;
use App\GaelO\Interfaces\Repositories\StudyRepositoryInterface;
use App\GaelO\Services\GaelOStudiesService\AbstractGaelOStudy;
use App\GaelO\Services\GaelOStudiesService\AbstractVisitDecisions;
use App\GaelO\Services\GaelOStudiesService\AbstractVisitRules;
use App\GaelO\Services\MailServices;
use App\GaelO\Services\VisitService;

abstract class FormService
{

    protected StudyRepositoryInterface $studyRepositoryInterface;
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
    protected StudyEntity $studyEntity;

    public function __construct(
        StudyRepositoryInterface $studyRepositoryInterface,
        ReviewRepositoryInterface $reviewRepositoryInterface,
        VisitService $visitService,
        MailServices $mailServices,
        FrameworkInterface $frameworkInterface
    ) {
        $this->studyRepositoryInterface = $studyRepositoryInterface;
        $this->reviewRepositoryInterface = $reviewRepositoryInterface;
        $this->visitService = $visitService;
        $this->mailServices = $mailServices;
        $this->frameworkInterface = $frameworkInterface;
    }


    public function setCurrentUserId(int $currentUserId): void
    {
        $this->currentUserId = $currentUserId;
        $this->visitService->setCurrentUserId($currentUserId);
    }

    public function setVisitContextAndStudy(array $visitContext, string $studyName): void
    {
        $this->studyEntity = $this->studyRepositoryInterface->find($studyName);
        $this->visitId = $visitContext['id'];
        $this->visitService->setVisitId($visitContext['id']);
        $this->visitContext = $visitContext;
        $this->visitType = $this->visitContext['visit_type']['name'];
        $this->patientId = $this->visitContext['patient_id'];
        $this->patientCode = $this->visitContext['patient']['code'];
        $this->uploaderId = $this->visitContext['creator_user_id'];
        $this->studyName = $studyName;
        $visitGroup = $this->visitContext['visit_type']['visit_group']['name'];
        $studyRule = AbstractGaelOStudy::getSpecificStudyObject($this->studyName);
        $this->abstractVisitRules = $studyRule->getSpecificVisitRules($visitGroup, $this->visitType);
        //SK a revoir si c'est une bonne idée, le visit context sert a avoir la decision etc (objet a part?)
        $this->abstractVisitRules->setVisitContext($this->visitContext);
        $this->abstractVisitRules->setLocalForm($this->local);
    }

    public abstract function createForm(array $data, bool $validated, ?bool $adjudication = null): int;
    public abstract function updateForm(int $reviewId, array $uploadedFileKeys, array $data, bool $validated);
    public abstract function unlockForm(int $reviewId);
    public abstract function deleteForm(int $reviewId);

    public function attachFile(array $reviewEntity, string $key, string $mimeType, string $extension, $binaryData): string
    {

        $associatedFiles = [];

        //Safty check
        if ($reviewEntity['local'] !== $this->local) throw new GaelOException("Form Service Unconsitancy");

        if ($reviewEntity['local']) {
            $associatedFiles = $this->abstractVisitRules->getAssociatedFilesInvestigator();
        } else {
            $isAdjudication = $reviewEntity['adjudication'];
            if ($isAdjudication) $associatedFiles = $this->abstractVisitRules->getAssociatedFilesAdjudication();
            else $associatedFiles = $this->abstractVisitRules->getAssociatedFilesReview();
        }

        if (!array_key_exists($key, $associatedFiles)) {
            throw new GaelOForbiddenException("Unexpected file key");
        }

        $associatiedFile = $associatedFiles[$key];

        if (!empty($reviewEntity['sent_files'][$key])) {
            throw new GaelOBadRequestException("Already Existing File for this review");
        }

        if (!in_array($mimeType, $associatiedFile->mimes)) {
            throw new GaelOBadRequestException("File Key or Mime Not Allowed");
        }

        $destinationPath = $this->studyName . '/' . 'attached_review_file';

        $filename = 'review_' . $reviewEntity['id'] . '_' . $key . '.' . $extension;
        $destinationFileName = $destinationPath . '/' . $filename;

        $this->frameworkInterface->storeFile($destinationFileName, $binaryData);

        $reviewEntity['sent_files'][$key] = $destinationFileName;

        $this->reviewRepositoryInterface->updateReviewFile($reviewEntity['id'], $reviewEntity['sent_files']);

        return $filename;
    }

    public function deleteFile(array $reviewEntity, string $key): void
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

    public function getVisitDecisionObject(): AbstractVisitDecisions
    {
        return $this->abstractVisitRules->getVisitDecisionObject();
    }
}
