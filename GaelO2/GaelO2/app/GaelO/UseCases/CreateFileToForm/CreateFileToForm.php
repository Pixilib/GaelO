<?php

namespace App\GaelO\UseCases\CreateFileToForm;

use App\GaelO\Constants\Constants;
use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Interfaces\Adapters\MimeInterface;
use App\GaelO\Interfaces\Repositories\ReviewRepositoryInterface;
use App\GaelO\Interfaces\Repositories\TrackerRepositoryInterface;
use App\GaelO\Interfaces\Repositories\VisitRepositoryInterface;
use App\GaelO\Services\AuthorizationVisitService;
use App\GaelO\Services\FormService;
use Exception;

class CreateFileToForm {

    private AuthorizationVisitService $authorizationVisitService;
    private ReviewRepositoryInterface $reviewRepositoryInterface;
    private TrackerRepositoryInterface $trackerRepositoryInterface;
    private VisitRepositoryInterface $visitRepositoryInterface;
    private FormService $formService;
    private MimeInterface $mimeInterface;

    public function __construct(AuthorizationVisitService $authorizationVisitService,
                                VisitRepositoryInterface $visitRepositoryInterface,
                                ReviewRepositoryInterface $reviewRepositoryInterface,
                                FormService $formService,
                                TrackerRepositoryInterface $trackerRepositoryInterface,
                                MimeInterface $mimeInterface)
    {
        $this->authorizationVisitService = $authorizationVisitService;
        $this->reviewRepositoryInterface = $reviewRepositoryInterface;
        $this->visitRepositoryInterface = $visitRepositoryInterface;
        $this->formService = $formService;
        $this->trackerRepositoryInterface = $trackerRepositoryInterface;
        $this->mimeInterface = $mimeInterface;
    }

    public function execute(CreateFileToFormRequest $createFileToReviewRequest, CreateFileToFormResponse $createFileToReviewResponse) {

        try{

            $reviewEntity = $this->reviewRepositoryInterface->find($createFileToReviewRequest->id);

            $studyName = $reviewEntity['study_name'];
            $userId = $reviewEntity['user_id'];
            $local = $reviewEntity['local'];
            $visitId = $reviewEntity['visit_id'];
            $key = $createFileToReviewRequest->key;
            $this->checkAuthorization($local, $reviewEntity['validated'], $userId, $visitId, $createFileToReviewRequest->currentUserId);

            $extension = $this->mimeInterface::getExtensionFromMime($createFileToReviewRequest->contentType);

            $fileName = 'review_'.$reviewEntity['id'].'_'.$key.'.'.$extension ;

            $visitContext = $this->visitRepositoryInterface->getVisitContext($reviewEntity['visit_id']);
            $this->formService->setVisitContextAndStudy($visitContext, $studyName);
            $this->formService->attachFile($reviewEntity, $key, $fileName, $createFileToReviewRequest->contentType, $createFileToReviewRequest->binaryData);

            $actionDetails = [
                'uploaded_file' => $key,
                'filename' => $fileName,
                'review_id' => $reviewEntity['id']
            ];

            $this->trackerRepositoryInterface->writeAction(
                $createFileToReviewRequest->currentUserId,
                $reviewEntity['local'] ? Constants::ROLE_INVESTIGATOR : Constants::ROLE_SUPERVISOR,
                $studyName,
                $reviewEntity['visit_id'],
                $reviewEntity['local'] ? Constants::TRACKER_SAVE_INVESTIGATOR_FORM : Constants::TRACKER_SAVE_REVIEWER_FORM,
                $actionDetails
            );

            $createFileToReviewResponse->status = 201;
            $createFileToReviewResponse->statusText =  'Created';

        } catch (GaelOException $e){

            $createFileToReviewResponse->body = $e->getErrorBody();
            $createFileToReviewResponse->status = $e->statusCode;
            $createFileToReviewResponse->statusText =  $e->statusText;

        }catch (Exception $e){
            throw $e;
        }

    }

    private function checkAuthorization(bool $local, bool $validated, int $reviewOwner, int $visitId, int $currentUserId) : void {
        if($validated) throw new GaelOForbiddenException("Form Already Validated");
        $this->authorizationVisitService->setVisitId($visitId);
        if($local){
            $this->authorizationVisitService->setCurrentUserAndRole($currentUserId, Constants::ROLE_INVESTIGATOR);
            if(!$this->authorizationVisitService->isVisitAllowed()) throw new GaelOForbiddenException();
        }else{
            $this->authorizationVisitService->setCurrentUserAndRole($currentUserId, Constants::ROLE_SUPERVISOR);
            if(!$this->authorizationVisitService->isVisitAllowed()) throw new GaelOForbiddenException();
            if($reviewOwner !== $currentUserId) throw new GaelOForbiddenException("Only form owner can add files");
        }

    }

}
