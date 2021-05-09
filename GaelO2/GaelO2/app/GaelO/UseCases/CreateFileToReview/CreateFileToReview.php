<?php

namespace App\GaelO\UseCases\CreateFileToReview;

use App\GaelO\Constants\Constants;
use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Interfaces\ReviewRepositoryInterface;
use App\GaelO\Interfaces\TrackerRepositoryInterface;
use Exception;

class CreateFileToReview {

    private ReviewRepositoryInterface $reviewRepositoryInterface;
    private TrackerRepositoryInterface $trackerRepositoryInterface;

    public function __construct(ReviewRepositoryInterface $reviewRepositoryInterface, TrackerRepositoryInterface $trackerRepositoryInterface)
    {
        $this->reviewRepositoryInterface = $reviewRepositoryInterface;
        $this->trackerRepositoryInterface = $trackerRepositoryInterface;
    }

    public function execute(CreateFileToReviewRequest $createFileToReviewRequest, CreateFileToReviewResponse $createFileToReviewResponse) {

        try{

            $reviewEntity = $this->reviewRepositoryInterface->find($createFileToReviewRequest->id);

            $studyName = $reviewEntity['study_name'];
            $userId = $reviewEntity['user_id'];
            $this->checkAuthorization($reviewEntity['validated'], $userId, $createFileToReviewRequest->currentUserId);

            //SK ICI PASSER PAR LE SERVICE
            $actionDetails = [
                'uploaded_file' => true
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

    private function checkAuthorization(bool $validated, int $reviewOwner, int $currentUserId) : void {
        if($validated) throw new GaelOForbiddenException("Form Already Valited");
        if($reviewOwner !== $currentUserId) throw new GaelOForbiddenException("Only form Owned can add files");
    }

}
