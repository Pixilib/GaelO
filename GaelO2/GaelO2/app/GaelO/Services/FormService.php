<?php

namespace App\GaelO\Services;

use App\GaelO\Adapters\LaravelFunctionAdapter;
use App\GaelO\Exceptions\GaelOBadRequestException;
use App\GaelO\Interfaces\ReviewRepositoryInterface;
use App\GaelO\Interfaces\ReviewStatusRepositoryInterface;
use App\GaelO\Services\SpecificStudiesRules\AbstractStudyRules;
use App\GaelO\Util;

class FormService
{

    protected VisitService $visitService;
    protected ReviewRepositoryInterface $reviewRepositoryInterface;
    protected ReviewStatusRepositoryInterface $reviewStatusRepositoryInterface;
    protected AbstractStudyRules $abstractStudyRules;
    protected MailServices $mailServices;

    protected int $currentUserId;
    protected int $visitId;
    protected array $visitContext;
    protected string $studyName;
    protected string $visitType;
    protected int $patientCode;
    protected int $uploaderId;

    public function __construct(
        VisitService $visitService,
        ReviewRepositoryInterface $reviewRepositoryInterface,
        ReviewStatusRepositoryInterface $reviewStatusRepositoryInterface,
        MailServices $mailServices
    ) {
        $this->visitService = $visitService;
        $this->reviewRepositoryInterface = $reviewRepositoryInterface;
        $this->reviewStatusRepositoryInterface = $reviewStatusRepositoryInterface;
        $this->mailServices = $mailServices;
    }


    public function setCurrentUserId(int $currentUserId)
    {
        $this->currentUserId = $currentUserId;
    }

    public function setVisitContextAndStudy(array $visitContext, string $studyName)
    {

        $this->visitId = $visitContext['id'];
        $this->visitService->setVisitId($visitContext['id']);
        $this->visitContext = $visitContext;
        $this->visitType = $this->visitContext['visit_type']['name'];
        $this->patientCode = $this->visitContext['patient_code'];
        $this->uploaderId = $this->visitContext['creator_user_id'];
        $this->studyName = $studyName;
        $modality = $this->visitContext['visit_type']['visit_group']['modality'];
        $this->abstractStudyRules = LaravelFunctionAdapter::make('\App\GaelO\Services\SpecificStudiesRules\\' . $this->studyName . '_' . $modality . '_' . $this->visitType);
    }

    public function attachFile(array $reviewEntity, string $key, string $filename, string $mimeType, $binaryData)
    {

        $keyMimeArray = [];

        if ($reviewEntity['local']) {
            $keyMimeArray = $this->abstractStudyRules->getAllowedKeyAndMimeTypeInvestigator();
        } else {
            $keyMimeArray = $this->abstractStudyRules->getAllowedKeyAndMimeTypeReviewer();
        }

        $expectedMime = $keyMimeArray[$key];

        if ($mimeType !== $expectedMime) {
            throw new GaelOBadRequestException("File Key or Mime Not Allowed");
        }

        if (!Util::is_base64_encoded($binaryData)) {
            throw new GaelOBadRequestException("Payload should be base64 encoded");
        }

        $storagePath = LaravelFunctionAdapter::getStoragePath();

        $destinationPath = '/attached_review_file/' . $this->studyName;
        if (!is_dir($storagePath . '/' . $destinationPath)) {
            mkdir($storagePath . '/' . $destinationPath, 0755, true);
        }

        $destinationFileName = $storagePath . '/' . $destinationPath . '/' . $filename;
        file_put_contents($destinationFileName, base64_decode($binaryData));

        $reviewEntity['sent_files'][$key] = $destinationFileName;

        $this->reviewRepositoryInterface->updateReviewFile($reviewEntity['id'], $reviewEntity);
    }

    public function removeFile(array $reviewEntity, string $key)
    {
        $fileName = $reviewEntity['sent_files'][$key];
        unlink($fileName);
        unset($reviewEntity['sent_files'][$key]);
        $this->reviewRepositoryInterface->updateReviewFile($reviewEntity['id'], $reviewEntity);
    }
}
