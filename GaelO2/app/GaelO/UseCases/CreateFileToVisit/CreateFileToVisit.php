<?php

namespace App\GaelO\UseCases\CreateFileToVisit;

use App\GaelO\Constants\Constants;
use App\GaelO\Exceptions\AbstractGaelOException;
use App\GaelO\Exceptions\GaelOBadRequestException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Interfaces\Adapters\MimeInterface;
use App\GaelO\Interfaces\Repositories\TrackerRepositoryInterface;
use App\GaelO\Services\AuthorizationService\AuthorizationVisitService;
use App\GaelO\Services\VisitService;
use App\GaelO\Util;
use Exception;

class CreateFileToVisit
{

    private AuthorizationVisitService $authorizationVisitService;
    private TrackerRepositoryInterface $trackerRepositoryInterface;
    private VisitService $visitService;
    private MimeInterface $mimeInterface;

    public function __construct(
        AuthorizationVisitService $authorizationVisitService,
        VisitService $visitService,
        TrackerRepositoryInterface $trackerRepositoryInterface,
        MimeInterface $mimeInterface
    ) {
        $this->authorizationVisitService = $authorizationVisitService;
        $this->visitService = $visitService;
        $this->trackerRepositoryInterface = $trackerRepositoryInterface;
        $this->mimeInterface = $mimeInterface;
    }

    public function execute(CreateFileToVisitRequest $createFileToVisitRequest, CreateFileToVisitResponse $createFileToVisitResponse)
    {

        try {

            $visitId = $createFileToVisitRequest->visitId;
            $key = $createFileToVisitRequest->key;
            $content = $createFileToVisitRequest->content;
            $currentUserId = $createFileToVisitRequest->currentUserId;
            $contentType = $createFileToVisitRequest->contentType;

            $this->visitService->setVisitId($visitId);
            $visitContext = $this->visitService->getVisitContext();
            $studyName = $visitContext['patient']['study_name'];

            if ($createFileToVisitRequest->studyName !== $studyName) {
                throw new GaelOForbiddenException('Should be called from original study');
            }

            if (!Util::isBase64Encoded($content)) {
                throw new GaelOBadRequestException("Payload should be base64 encoded");
            }

            $this->checkAuthorization($visitId, $currentUserId, $studyName);

            if ($createFileToVisitRequest->extension == null) {
                $extension = $this->mimeInterface::getExtensionsFromMime($createFileToVisitRequest->contentType)[0];
            } else {
                $extension = $createFileToVisitRequest->extension;
                $contentType = $this->mimeInterface::getMimesFromExtension($extension)[0];
            }

            $filename = $this->visitService->attachFile($key, $contentType, $extension, base64_decode($content));

            $actionDetails = [
                'uploaded_file' => $key,
                'filename' => $filename
            ];

            $this->trackerRepositoryInterface->writeAction(
                $currentUserId,
                Constants::ROLE_SUPERVISOR,
                $studyName,
                $visitId,
                Constants::TRACKER_UPDATE_VISIT_FILE,
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
        if (!$this->authorizationVisitService->isVisitAllowed(Constants::ROLE_SUPERVISOR)) throw new GaelOForbiddenException();
    }
}
