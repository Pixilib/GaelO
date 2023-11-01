<?php

namespace App\GaelO\Services;

use App\GaelO\Constants\Enums\InvestigatorFormStateEnum;
use App\GaelO\Constants\Enums\QualityControlStateEnum;
use App\GaelO\Constants\Enums\ReviewStatusEnum;
use App\GaelO\Constants\Enums\UploadStatusEnum;
use App\GaelO\Exceptions\GaelOBadRequestException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Exceptions\GaelONotFoundException;
use App\GaelO\Interfaces\Adapters\FrameworkInterface;
use App\GaelO\Repositories\ReviewRepository;
use App\GaelO\Repositories\ReviewStatusRepository;
use App\GaelO\Repositories\VisitRepository;
use App\GaelO\Services\GaelOStudiesService\AbstractGaelOStudy;
use App\GaelO\Services\GaelOStudiesService\Events\QCModifiedEvent;
use App\GaelO\Services\GaelOStudiesService\Events\VisitUploadedEvent;

class VisitService
{
    private VisitRepository $visitRepository;
    private ReviewRepository $reviewRepository;
    private ReviewStatusRepository $reviewStatusRepository;
    private FrameworkInterface $frameworkInterface;

    private int $visitId;
    private int $currentUserId;

    public function __construct(
        FrameworkInterface $frameworkInterface,
        VisitRepository $visitRepository,
        ReviewRepository $reviewRepository,
        ReviewStatusRepository $reviewStatusRepository
    ) {
        $this->frameworkInterface = $frameworkInterface;
        $this->visitRepository = $visitRepository;
        $this->reviewStatusRepository = $reviewStatusRepository;
        $this->reviewRepository = $reviewRepository;
    }

    public function setVisitId(int $visitId)
    {
        $this->visitId = $visitId;
    }

    public function setCurrentUserId(int $currentUserId)
    {
        $this->currentUserId = $currentUserId;
    }

    public function getVisitContext(): array
    {
        return $this->visitRepository->getVisitContext($this->visitId);
    }

    public function updateUploadStatus(string $uploadStatus)
    {

        if ($uploadStatus === UploadStatusEnum::NOT_DONE->value) {
            $visitContext = $this->getVisitContext($this->visitId);
            if ($visitContext['state_investigator_form'] === InvestigatorFormStateEnum::DONE->value) {
                $this->reviewRepository->unlockInvestigatorForm($this->visitId);
                $this->updateInvestigatorFormStatus(InvestigatorFormStateEnum::DRAFT->value);
            }
        }

        $updatedEntity = $this->visitRepository->updateUploadStatus($this->visitId, $uploadStatus);

        if (
            $updatedEntity['upload_status'] === UploadStatusEnum::DONE->value
            && ($updatedEntity['state_investigator_form'] === InvestigatorFormStateEnum::NOT_NEEDED->value || $updatedEntity['state_investigator_form'] === InvestigatorFormStateEnum::DONE->value)
        ) {
            $this->visitUploaded($this->visitId);
        }
    }

    public function updateInvestigatorFormStatus(string $stateInvestigatorForm)
    {
        $updatedEntity = $this->visitRepository->updateInvestigatorFormStatus($this->visitId, $stateInvestigatorForm);
        if (
            $updatedEntity['upload_status'] === UploadStatusEnum::DONE->value
            && ($updatedEntity['state_investigator_form'] === InvestigatorFormStateEnum::DONE->value)
        ) {
            $this->visitUploaded($this->visitId);
        }
    }

    /**
     * Skip QC if needed and send email notification of uploaded visit
     */
    private function visitUploaded()
    {
        $visitEntity = $this->getVisitContext($this->visitId);
        $studyName = $visitEntity['patient']['study_name'];
        $reviewStatus = $this->getReviewStatus($studyName);

        $qcNeeded = $visitEntity['state_quality_control'] !== QualityControlStateEnum::NOT_NEEDED->value;
        $reviewNeeded = $reviewStatus['review_status'] !== ReviewStatusEnum::NOT_NEEDED->value;

        //If Qc NotNeeded mark visit as available for review
        if (!$qcNeeded && $reviewNeeded) {
            $this->reviewStatusRepository->updateReviewAvailability($this->visitId, $studyName, true);
        }

        //Notify of the upload done
        $visitUploadedEvent = new VisitUploadedEvent($visitEntity);
        $visitUploadedEvent->setReviewNeeded($reviewNeeded);
        $visitUploadedEvent->setUploaderUserId($this->currentUserId);
        $studyObject = AbstractGaelOStudy::getSpecificStudyObject($studyName);
        $studyObject->onEventStudy($visitUploadedEvent);
    }


    public function editQc(string $stateQc, int $controllerId, ?bool $imageQc, ?bool $formQc, ?string $imageQcComment, ?string $formQcComment)
    {
        $visitEntity = $this->getVisitContext($this->visitId);
        $studyName = $visitEntity['patient']['study_name'];

        $reviewStatus = $this->getReviewStatus($studyName);

        $reviewNeeded = $reviewStatus['review_status'] !== ReviewStatusEnum::NOT_NEEDED->value;
        $localFormNeeded = $visitEntity['state_investigator_form'] !== InvestigatorFormStateEnum::NOT_NEEDED->value;

        $this->visitRepository->editQc($this->visitId, $stateQc, $controllerId, $imageQc, $formQc, $imageQcComment, $formQcComment);

        if ($stateQc === QualityControlStateEnum::CORRECTIVE_ACTION_ASKED->value && $localFormNeeded) {
            //Invalidate invistagator form and set it status as draft in the visit
            $this->reviewRepository->unlockInvestigatorForm($this->visitId);
            $this->visitRepository->updateInvestigatorFormStatus($this->visitId, InvestigatorFormStateEnum::DRAFT->value);
        }

        if ($stateQc === QualityControlStateEnum::ACCEPTED->value && $reviewNeeded) {
            //Invalidate invistagator form and set it status as draft in the visit
            $this->reviewStatusRepository->updateReviewAvailability($this->visitId, $studyName, true);
        }

        $qcModifiedEvent = new QCModifiedEvent($visitEntity);
        $qcModifiedEvent->setCurrentUserId($controllerId);
        $qcModifiedEvent->setQcStatus($stateQc);
        $qcModifiedEvent->setFormQcStatus($formQc ? 'Accepted ' : 'Refused');
        $qcModifiedEvent->setImageQcStatus($imageQc ? 'Accepted ' : 'Refused');
        $qcModifiedEvent->setFormQcComment($formQcComment ?? 'None');
        $qcModifiedEvent->setImageQcComment($imageQcComment ?? 'None');

        $studyObject = AbstractGaelOStudy::getSpecificStudyObject($studyName);
        $studyObject->onEventStudy($qcModifiedEvent);
    }

    public function resetQc(): void
    {
        $visitEntity = $this->getVisitContext($this->visitId);
        $studyName = $visitEntity['patient']['study_name'];
        $this->visitRepository->resetQc($this->visitId);
        $this->reviewStatusRepository->updateReviewAvailability($this->visitId, $studyName, false);
    }

    public function getReviewStatus(string $studyName)
    {
        return $this->reviewStatusRepository->getReviewStatus($this->visitId, $studyName);
    }

    public function attachFile(string $key, string $mimeType, string $extension, $binaryData): string
    {
        $visitEntity = $this->getVisitContext($this->visitId);
        $studyName = $visitEntity['patient']['study_name'];
        $visitGroupName = $visitEntity['visit_type']['visit_group']['name'];
        $visitTypeName = $visitEntity['visit_type']['name'];

        $studyRule = AbstractGaelOStudy::getSpecificStudyObject($studyName);
        $abstractVisitRules = $studyRule->getSpecificVisitRules($visitGroupName, $visitTypeName);
        $associatedFilesVisit = $abstractVisitRules->getAssociatedFilesVisit();

        if (!empty($visitEntity['sent_files'][$key])) {
            throw new GaelOBadRequestException("Already Existing File for this visit");
        }

        if (!array_key_exists($key, $associatedFilesVisit)) {
            throw new GaelOForbiddenException("Unexpected file key");
        }

        $associatiedFile = $associatedFilesVisit[$key];

        if (!in_array($mimeType, $associatiedFile->mimes)) {
            throw new GaelOBadRequestException("Mime Not Allowed");
        }

        $destinationPath = $studyName . '/' . 'attached_visit_file';

        $filename = 'visit_' . $this->visitId . '_' . $key . '.' . $extension;
        $destinationFileName = $destinationPath . '/' . $filename;

        $this->frameworkInterface->storeFile($destinationFileName, $binaryData);

        $visitEntity['sent_files'][$key] = $destinationFileName;
        $this->visitRepository->updateVisitFile($visitEntity['id'], $visitEntity['sent_files']);
        return $filename;
    }

    public function removeFile(string $key): void
    {
        $visitEntity = $this->getVisitContext($this->visitId);

        if (empty($visitEntity['sent_files'][$key])) {
            throw new GaelONotFoundException('Non exisiting key file in review');
        }

        $targetedFile = $visitEntity['sent_files'][$key];
        $this->frameworkInterface->deleteFile($targetedFile);

        unset($visitEntity['sent_files'][$key]);
        $this->visitRepository->updateVisitFile($visitEntity['id'], $visitEntity['sent_files']);
    }
}
