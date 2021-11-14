<?php

namespace App\GaelO\Services\AuthorizationService;

use App\GaelO\Adapters\FrameworkAdapter;
use App\GaelO\Constants\Constants;
use App\GaelO\Interfaces\Repositories\ReviewRepositoryInterface;
use App\GaelO\Services\AuthorizationService\AuthorizationVisitService;

class AuthorizationReviewService {

    private ReviewRepositoryInterface $reviewRepositoryInterface;
    private int $reviewId;
    private array $reviewData;

    public function __construct(ReviewRepositoryInterface $reviewRepositoryInterface)
    {
        $this->reviewRepositoryInterface = $reviewRepositoryInterface;
    }

    public function setReviewId(int $reviewId){
        $this->reviewId = $reviewId;
    }

    private function fillVisitData(){
        if($this->reviewData == null) $this->reviewData = $this->reviewRepositoryInterface->find($this->reviewId);
        $this->reviewStudyName = $this->reviewData['study_name'];
        $this->reviewOwnerId = intval($this->reviewData['user_id']);
    }

    public function isReviewAllowed(int $userId, string $requestedRole, string $studyName): bool {

        $this->fillVisitData();

        $authorizationVisitService = FrameworkAdapter::make(AuthorizationVisitService::class);
        $authorizationVisitService->setPatientEntity($this->reviewData['patient']);

        if ($requestedRole === Constants::ROLE_REVIEWER) {
            //Role should be allowed and current user be the review owner
            return $this->authorizationVisitService->isVisitAllowed($userId, $requestedRole, $studyName) && $this->reviewOwnerId === $userId;
        } else if ($requestedRole === Constants::ROLE_SUPERVISOR) {
            //Allow Review of the study with supervisor roles
           return $this->authorizationVisitService->isVisitAllowed($userId, $requestedRole, $studyName);
        } else {
            return false;
        }

    }




}
