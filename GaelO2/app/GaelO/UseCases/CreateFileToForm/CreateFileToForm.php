<?php

namespace App\GaelO\UseCases\CreateFileToForm;

use App\GaelO\Constants\Constants;
use App\GaelO\Exceptions\AbstractGaelOException;
use App\GaelO\Exceptions\GaelOBadRequestException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Interfaces\Adapters\FrameworkInterface;
use App\GaelO\Interfaces\Adapters\MimeInterface;
use App\GaelO\Interfaces\Repositories\ReviewRepositoryInterface;
use App\GaelO\Interfaces\Repositories\TrackerRepositoryInterface;
use App\GaelO\Interfaces\Repositories\VisitRepositoryInterface;
use App\GaelO\Services\AuthorizationService\AuthorizationReviewService;
use App\GaelO\Services\AuthorizationService\AuthorizationVisitService;
use App\GaelO\Services\FormService\InvestigatorFormService;
use App\GaelO\Services\FormService\ReviewFormService;
use App\GaelO\Util;
use Exception;

class CreateFileToForm
{

    private AuthorizationVisitService $authorizationVisitService;
    private AuthorizationReviewService $authorizationReviewService;
    private ReviewRepositoryInterface $reviewRepositoryInterface;
    private TrackerRepositoryInterface $trackerRepositoryInterface;
    private VisitRepositoryInterface $visitRepositoryInterface;
    private FrameworkInterface $frameworkInterface;
    private MimeInterface $mimeInterface;

    public function __construct(
        AuthorizationVisitService $authorizationVisitService,
        AuthorizationReviewService $authorizationReviewService,
        VisitRepositoryInterface $visitRepositoryInterface,
        ReviewRepositoryInterface $reviewRepositoryInterface,
        FrameworkInterface $frameworkInterface,
        TrackerRepositoryInterface $trackerRepositoryInterface,
        MimeInterface $mimeInterface
    ) {
        $this->authorizationVisitService = $authorizationVisitService;
        $this->authorizationReviewService = $authorizationReviewService;
        $this->reviewRepositoryInterface = $reviewRepositoryInterface;
        $this->visitRepositoryInterface = $visitRepositoryInterface;
        $this->frameworkInterface = $frameworkInterface;
        $this->trackerRepositoryInterface = $trackerRepositoryInterface;
        $this->mimeInterface = $mimeInterface;
    }

    public function execute(CreateFileToFormRequest $createFileToReviewRequest, CreateFileToFormResponse $createFileToReviewResponse)
    {

        try {
            $reviewEntity = $this->reviewRepositoryInterface->find($createFileToReviewRequest->id);

            $studyName = $reviewEntity['study_name'];
            $local = $reviewEntity['local'];
            $visitId = $reviewEntity['visit_id'];
            $reviewId = $reviewEntity['id'];

            $key = $createFileToReviewRequest->key;
            $binaryData = $createFileToReviewRequest->binaryData;


            if (!Util::isBase64Encoded($binaryData)) {
                throw new GaelOBadRequestException("Payload should be base64 encoded");
            }

            $this->checkAuthorization($local, $reviewEntity['validated'], $createFileToReviewRequest->id, $visitId, $createFileToReviewRequest->currentUserId, $studyName);

            $extension = $this->mimeInterface::getExtensionFromMime($createFileToReviewRequest->contentType);

            $fileName = 'review_' . $reviewId . '_' . $key . '.' . $extension;

            $visitContext = $this->visitRepositoryInterface->getVisitWithContextAndReviewStatus($visitId, $studyName);
            
            $formService = null;

            if ($local) {
                $formService = $this->frameworkInterface->make(InvestigatorFormService::class);
            } else {
                $formService = $this->frameworkInterface->make(ReviewFormService::class);
            }

            $formService->setVisitContextAndStudy($visitContext, $studyName);
            $formService->attachFile($reviewEntity, $key, $fileName, $createFileToReviewRequest->contentType, base64_decode($binaryData));

            $actionDetails = [
                'uploaded_file' => $key,
                'filename' => $fileName,
                'review_id' => $reviewId
            ];

            $this->trackerRepositoryInterface->writeAction(
                $createFileToReviewRequest->currentUserId,
                $local ? Constants::ROLE_INVESTIGATOR : Constants::ROLE_SUPERVISOR,
                $studyName,
                $visitId,
                $local ? Constants::TRACKER_SAVE_INVESTIGATOR_FORM : Constants::TRACKER_SAVE_REVIEWER_FORM,
                $actionDetails
            );

            $createFileToReviewResponse->status = 201;
            $createFileToReviewResponse->statusText =  'Created';
        } catch (AbstractGaelOException $e) {
            $createFileToReviewResponse->body = $e->getErrorBody();
            $createFileToReviewResponse->status = $e->statusCode;
            $createFileToReviewResponse->statusText =  $e->statusText;
        } catch (Exception $e) {
            throw $e;
        }
    }

    private function checkAuthorization(bool $local, bool $validated, int $reviewId, int $visitId, int $currentUserId, string $studyName): void
    {
        if ($validated) throw new GaelOForbiddenException("Form Already Validated");

        if ($local) {
            $this->authorizationVisitService->setVisitId($visitId);
            $this->authorizationVisitService->setUserId($currentUserId);
            $this->authorizationVisitService->setStudyName($studyName);
            if (!$this->authorizationVisitService->isVisitAllowed(Constants::ROLE_INVESTIGATOR)) throw new GaelOForbiddenException();
        } else {
            $this->authorizationReviewService->setUserId($currentUserId);
            $this->authorizationReviewService->setReviewId($reviewId);
            if (!$this->authorizationReviewService->isReviewAllowed(Constants::ROLE_REVIEWER)) throw new GaelOForbiddenException();
        }
    }
}
