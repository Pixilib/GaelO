<?php

namespace App\GaelO\Interfaces;

interface VisitRepositoryInterface
{


    public function createVisit(
        string $studyName,
        int $creatorUserId,
        int $patientCode,
        ?string $visitDate,
        int $visitTypeId,
        string $statusDone,
        ?string $reasonForNotDone,
        string $stateInvestigatorForm,
        string $stateQualityControl
    );

    public function isExistingVisit(int $patientCode, int $visitTypeId): bool ;

    public function updateUploadStatus(int $visitId, string $newUploadStatus): array ;

    public function getVisitContext(int $visitId): array ;

    public function updateReviewAvailability(int $visitId, string $studyName, bool $available): void ;

    public function getPatientsVisits(int $patientCode) :array ;

    public function getPatientsVisitsWithReviewStatus(int $patientCode, string $studyName) : array ;

    public function getPatientVisitsWithContext(int $patientCode) : array ;

    public function getPatientListVisitsWithContext(array $patientCodeArray) : array ;

    public function getVisitsInStudy(string $studyName) : array ;

    public function getVisitsAwaitingControllerAction(string $studyName) : array ;

    public function getVisitsAwaitingReviews(string $studyName) : array;

    public function getVisitsAwaitingReviewForUser(string $studyName, int $userId) : array ;

    public function getPatientsHavingAtLeastOneAwaitingReviewForUser(string $studyName, int $userId) : array ;

    public function isVisitAvailableForReview(int $visitId, string $studyName, int $userId) : bool ;

    public function editQc(int $visitId, string $stateQc, int $controllerId, bool $imageQc, bool $formQc, ?string $imageQcComment, ?string $formQcComment): void ;

    public function resetQc(int $visitId): void ;

    public function setCorrectiveAction(int $visitId, int $investigatorId, bool $newUpload, bool $newInvestigatorForm, bool $correctiveActionApplyed, ?string $comment) : void ;

    public function updateInvestigatorForm(int $visitId, string $stateInvestigatorForm): array ;

    public function getImagingVisitsAwaitingUpload(string $studyName, array $centerCode): array ;
}
