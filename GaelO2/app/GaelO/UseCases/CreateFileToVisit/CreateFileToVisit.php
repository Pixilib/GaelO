<?php

namespace App\GaelO\UseCases\CreateFileToVisit;

use App\GaelO\Exceptions\AbstractGaelOException;
use App\GaelO\Exceptions\GaelOBadRequestException;
use App\GaelO\Interfaces\Adapters\FrameworkInterface;
use App\GaelO\Interfaces\Adapters\MimeInterface;
use App\GaelO\Interfaces\Repositories\TrackerRepositoryInterface;
use App\GaelO\Interfaces\Repositories\VisitRepositoryInterface;
use App\GaelO\Services\AuthorizationService\AuthorizationVisitService;
use App\GaelO\Util;
use Exception;

class CreateFileToVisit
{

    private AuthorizationVisitService $authorizationVisitService;
    private TrackerRepositoryInterface $trackerRepositoryInterface;
    private VisitRepositoryInterface $visitRepositoryInterface;
    private FrameworkInterface $frameworkInterface;
    private MimeInterface $mimeInterface;

    public function __construct(
        AuthorizationVisitService $authorizationVisitService,
        VisitRepositoryInterface $visitRepositoryInterface,
        FrameworkInterface $frameworkInterface,
        TrackerRepositoryInterface $trackerRepositoryInterface,
        MimeInterface $mimeInterface
    ) {
        $this->authorizationVisitService = $authorizationVisitService;
        $this->visitRepositoryInterface = $visitRepositoryInterface;
        $this->frameworkInterface = $frameworkInterface;
        $this->trackerRepositoryInterface = $trackerRepositoryInterface;
        $this->mimeInterface = $mimeInterface;
    }

    public function execute(CreateFileToVisitRequest $createFileToVisitRequest, CreateFileToVisitResponse $createFileToVisitResponse)
    {

        try {

            $visitId = $createFileToVisitRequest->visitId;

            $reviewEntity = $this->reviewRepositoryInterface->find($visitId);

            $studyName = $reviewEntity['study_name'];
            $local = $reviewEntity['local'];
            $reviewId = $reviewEntity['id'];

            $key = $createFileToVisitRequest->key;
            $binaryData = $createFileToVisitRequest->binaryData;
            $currentUserId = $createFileToVisitRequest->currentUserId;

            if (!Util::isBase64Encoded($binaryData)) {
                throw new GaelOBadRequestException("Payload should be base64 encoded");
            }

            $this->checkAuthorization($visitId, $currentUserId, $studyName);

            $extension = $this->mimeInterface::getExtensionsFromMime($createFileToVisitRequest->contentType)[0];

            $visitContext = $this->visitRepositoryInterface->getVisitWithContextAndReviewStatus($visitId, $studyName);

            $formService =  $this->frameworkInterface->make(ReviewFormService::class);

            $formService->setVisitContextAndStudy($visitContext, $studyName);

            $filename = $formService->attachFile($reviewEntity, $key, $createFileToReviewRequest->contentType, $extension, base64_decode($binaryData));

            $actionDetails = [
                'uploaded_file' => $key,
                'filename' => $filename,
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

            $createFileToVisitResponse->status = 201;
            $createFileToVisitResponse->statusText =  'Created';
        } catch (AbstractGaelOException $e) {
            $createFileToVisitResponse->body = $e->getErrorBody();
            $createFileToVisitResponse->status = $e->statusCode;
            $createFileToVisitResponse->statusText =  $e->statusText;
        } catch (Exception $e) {
            throw $e;
        }
    }

    private function checkAuthorization(int $visitId, int $currentUserId, string $studyName): void
    {

        $this->authorizationVisitService->setVisitId($visitId);
        $this->authorizationVisitService->setUserId($currentUserId);
        $this->authorizationVisitService->setStudyName($studyName);
        if (!$this->authorizationVisitService->isVisitAllowed(Constants::ROLE_INVESTIGATOR)) throw new GaelOForbiddenException();
    }
}
