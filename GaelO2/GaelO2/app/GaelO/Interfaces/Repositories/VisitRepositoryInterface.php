<?php

namespace App\GaelO\Interfaces\Repositories;

interface VisitRepositoryInterface
{

    public function find($id) : array ;

    public function delete($id) : void ;

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
    ) : int;

    public function isExistingVisit(int $patientCode, int $visitTypeId): bool ;

    public function updateUploadStatus(int $visitId, string $newUploadStatus): array ;

    public function getVisitContext(int $visitId, bool $withTrashed = false): array ;

    public function getPatientsVisits(int $patientCode) :array ;

    public function getAllPatientsVisitsWithReviewStatus(int $patientCode, string $studyName, bool $withTrashed) : array ;

    public function getPatientListVisitsWithContext(array $patientCodeArray) : array ;

    public function getPatientListVisitWithContextAndReviewStatus(array $patientCodeArray, string $studyName) : array;

    public function getVisitsInStudy(string $studyName, bool $withReviewStatus, bool $withTrashed) : array ;

    public function hasVisitsInStudy(string $studyName) : bool ;

    public function getVisitsInVisitGroup(int $visitGroupId) : array ;

    public function hasVisitsInVisitGroup(int $visitGroupId) : bool ;

    public function getVisitsInStudyAwaitingControllerAction(string $studyName) : array ;

    public function getVisitsAwaitingReviews(string $studyName) : array;

    public function getVisitsAwaitingReviewForUser(string $studyName, int $userId) : array ;

    public function getPatientsHavingAtLeastOneAwaitingReviewForUser(string $studyName, int $userId) : array ;

    public function isVisitAvailableForReview(int $visitId, string $studyName, int $userId) : bool ;

    public function editQc(int $visitId, string $stateQc, int $controllerId, bool $imageQc, bool $formQc, ?string $imageQcComment, ?string $formQcComment): void ;

    public function resetQc(int $visitId): void ;

    public function setCorrectiveAction(int $visitId, int $investigatorId, bool $newUpload, bool $newInvestigatorForm, bool $correctiveActionApplied, ?string $comment) : void ;

    public function updateInvestigatorFormStatus(int $visitId, string $stateInvestigatorForm): array ;

    public function getImagingVisitsAwaitingUpload(string $studyName, array $centerCode): array ;

    public function reactivateVisit(int $visitId) : void ;

    public function getVisitsInVisitType(int $visitTypeId, bool $withReviewStatus = false, string $studyName = null, bool $withTrashed = false, bool $withCenter = false ) : array;

    public function getVisitContextByVisitIdArray(array $visitIdArray)  : array ;
}
