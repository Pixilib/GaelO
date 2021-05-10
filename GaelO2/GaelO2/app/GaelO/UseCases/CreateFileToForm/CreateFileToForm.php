<?php

namespace App\GaelO\UseCases\CreateFileToForm;

use App\GaelO\Adapters\MimeAdapter;
use App\GaelO\Constants\Constants;
use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Interfaces\ReviewRepositoryInterface;
use App\GaelO\Interfaces\TrackerRepositoryInterface;
use App\GaelO\Services\FormService;
use Exception;

class CreateFileToForm {

    private ReviewRepositoryInterface $reviewRepositoryInterface;
    private TrackerRepositoryInterface $trackerRepositoryInterface;
    private FormService $formService;

    public function __construct(ReviewRepositoryInterface $reviewRepositoryInterface, FormService $formService, TrackerRepositoryInterface $trackerRepositoryInterface)
    {
        $this->reviewRepositoryInterface = $reviewRepositoryInterface;
        $this->formService = $formService;
        $this->trackerRepositoryInterface = $trackerRepositoryInterface;
    }

    public function execute(CreateFileToFormRequest $createFileToReviewRequest, CreateFileToFormResponse $createFileToReviewResponse) {

        try{

            $reviewEntity = $this->reviewRepositoryInterface->find($createFileToReviewRequest->id);

            $studyName = $reviewEntity['study_name'];
            $userId = $reviewEntity['user_id'];
            $key = $createFileToReviewRequest->key;
            $this->checkAuthorization($reviewEntity['validated'], $userId, $createFileToReviewRequest->currentUserId);

            $extension = MimeAdapter::getExtensionFromMime($createFileToReviewRequest->contentType);

            $fileName = 'review_'.$reviewEntity['id'].'_'.$key.'.'.$extension ;
            $this->formService->attachFile($reviewEntity, $key, $fileName, $createFileToReviewRequest->contentType, $createFileToReviewRequest->binaryData);

            $actionDetails = [
                'uploaded_file' => $key,
                'filename' => $fileName,
                'review_id' => $reviewEntity['id']
            ];

            $this->trackerRepositoryInterface->writeAction(
                $createFileToReviewRequest->currentUserId,
                Constants::ROLE_INVESTIGATOR,
                $studyName,
                $reviewEntity['visit_id'],
                Constants::TRACKER_SAVE_REVIEWER_FORM,
                $actionDetails);

            //Return created documentation ID to help front end to send file data
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

    //SK A REVOIR si investigator form checker authorisation role
    //si reviewer check authorizaton  + son propre formulaire
    //et non validé dans tous les cas
    private function checkAuthorization(bool $validated, int $reviewOwner, int $currentUserId) : void {
        if($validated) throw new GaelOForbiddenException("Form Already Validated");
        if($reviewOwner !== $currentUserId) throw new GaelOForbiddenException("Only form owner can add files");
    }

}
