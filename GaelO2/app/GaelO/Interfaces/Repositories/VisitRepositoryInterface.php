<?php

namespace App\GaelO\Interfaces\Repositories;

interface VisitRepositoryInterface
{

    public function delete($id): void;

    public function createVisit(
        string $studyName,
        int $creatorUserId,
        string $patientId,
        ?string $visitDate,
        int $visitTypeId,
        string $statusDone,
        ?string $reasonForNotDone,
        string $stateInvestigatorForm,
        string $stateQualityControl,
        string $stateReview
    ): int;

    public function isExistingVisit(string $patientId, int $visitTypeId): bool;

    public function updateUploadStatus(int $visitId, string $newUploadStatus): array;

    public function updateVisitDate(int $visitId, string $visitDate): void;

    public function getVisitContext(int $visitId, bool $withTrashed = false): array;

    public function getVisitWithContextAndReviewStatus(int $visitId, string $studyName): array;

    public function getPatientsVisits(string $patientId): array;

    public function getAllPatientsVisitsWithReviewStatus(string $patientId, string $studyName, bool $withTrashed): array;

    public function getVisitsFromPatientIdsWithContext(array $patientIdArray): array;

    public function getVisitFromPatientIdsWithContextAndReviewStatus(array $patientIdArray, string $studyName): array;

    public function getReviewVisitHistoryFromPatientIdsWithContextAndReviewStatus(array $patientIdArray, string $studyName): array;

    public function getVisitsInStudy(string $studyName, bool $withReviewStatus, bool $withPatientCenter, bool $withTrashed): array;

    public function hasVisitsInStudy(string $studyName): bool;

    public function getVisitsInStudyAwaitingControllerAction(string $studyName): array;

    public function getVisitsInStudyNeedingQualityControl(string $studyName): array;

    public function getPatientsHavingAtLeastOneAwaitingReviewForUser(string $studyName, int $userId): array;

    public function isParentPatientHavingOneVisitAwaitingReview(int $visitId, string $studyName, int $userId) : bool;

    public function editQc(int $visitId, string $stateQc, int $controllerId, bool $imageQc, bool $formQc, ?string $imageQcComment, ?string $formQcComment): void;

    public function resetQc(int $visitId): void;

    public function setCorrectiveAction(int $visitId, int $investigatorId, bool $newUpload, bool $newInvestigatorForm, bool $correctiveActionApplied, ?string $comment): void;

    public function updateInvestigatorFormStatus(int $visitId, string $stateInvestigatorForm): array;

    public function getImagingVisitsAwaitingUpload(string $studyName, array $centerCode): array;

    public function reactivateVisit(int $visitId): void;
    
    public function getVisitOfPatientByVisitTypeName(string $patientId, string $visitGroupName, string $visitTypeName, bool $withReviewStatus, string $studyName) : array;

    public function getVisitsInVisitType(int $visitTypeId, bool $withReviewStatus = false, string $studyName = null, bool $withTrashed = false, bool $withCenter = false): array;

    public function getVisitContextByVisitIdArray(array $visitIdArray): array;

}
