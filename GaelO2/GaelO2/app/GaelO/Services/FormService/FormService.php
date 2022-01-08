<?php

namespace App\GaelO\Services\FormService;

use App\GaelO\Exceptions\GaelOBadRequestException;
use App\GaelO\Interfaces\Adapters\FrameworkInterface;
use App\GaelO\Interfaces\Repositories\ReviewRepositoryInterface;
use App\GaelO\Interfaces\Repositories\ReviewStatusRepositoryInterface;
use App\GaelO\Services\MailServices;
use App\GaelO\Services\SpecificStudiesRules\AbstractVisitRules;
use App\GaelO\Services\VisitService;
use App\GaelO\Util;

class FormService
{

    protected VisitService $visitService;
    protected ReviewRepositoryInterface $reviewRepositoryInterface;
    protected ReviewStatusRepositoryInterface $reviewStatusRepositoryInterface;
    protected AbstractVisitRules $abstractVisitRules;
    protected MailServices $mailServices;
    protected FrameworkInterface $frameworkInterface;

    protected int $currentUserId;
    protected int $visitId;
    protected array $visitContext;
    protected string $studyName;
    protected string $visitType;
    protected string $patientId;
    protected int $uploaderId;

    public function __construct(
        VisitService $visitService,
        ReviewRepositoryInterface $reviewRepositoryInterface,
        ReviewStatusRepositoryInterface $reviewStatusRepositoryInterface,
        MailServices $mailServices,
        FrameworkInterface $frameworkInterface
    ) {
        $this->visitService = $visitService;
        $this->reviewRepositoryInterface = $reviewRepositoryInterface;
        $this->reviewStatusRepositoryInterface = $reviewStatusRepositoryInterface;
        $this->mailServices = $mailServices;
        $this->frameworkInterface = $frameworkInterface;
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
        $this->patientId = $this->visitContext['patient_id'];
        $this->uploaderId = $this->visitContext['creator_user_id'];
        $this->studyName = $studyName;
        $modality = $this->visitContext['visit_type']['visit_group']['modality'];
        $this->abstractVisitRules = $this->frameworkInterface::make('\App\GaelO\Services\SpecificStudiesRules\\' . $this->studyName . '_' . $modality . '_' . $this->visitType);
    }

    public function attachFile(array $reviewEntity, string $key, string $filename, string $mimeType, $binaryData)
    {

        $keyMimeArray = [];

        if ($reviewEntity['local']) {
            $keyMimeArray = $this->abstractVisitRules->getAllowedKeyAndMimeTypeInvestigator();
        } else {
            $keyMimeArray = $this->abstractVisitRules->getAllowedKeyAndMimeTypeReviewer();
        }

        $expectedMime = $keyMimeArray[$key];

        if(!empty($reviewEntity['sent_files'][$key])){
            throw new GaelOBadRequestException("Already Existing File for this review");
        }

        if ($mimeType !== $expectedMime) {
            throw new GaelOBadRequestException("File Key or Mime Not Allowed");
        }

        if (!Util::is_base64_encoded($binaryData)) {
            throw new GaelOBadRequestException("Payload should be base64 encoded");
        }

        $storagePath = $this->frameworkInterface::getStoragePath();

        $destinationPath = $this->studyName.'/'.'attached_review_file';
        if (!is_dir($storagePath . '/' . $destinationPath)) {
            mkdir($storagePath . '/' . $destinationPath, 0755, true);
        }

        $destinationFileName = $storagePath . '/' . $destinationPath . '/' . $filename;
        file_put_contents($destinationFileName, base64_decode($binaryData));

        $reviewEntity['sent_files'][$key] = $destinationPath . '/' . $filename;

        $this->reviewRepositoryInterface->updateReviewFile($reviewEntity['id'], $reviewEntity);
    }

    public function removeFile(array $reviewEntity, string $key)
    {
        if(empty($reviewEntity['sent_files'][$key])){
            throw new GaelOBadRequestException('Non exisiting key file in review');
        }
        $storagePath = $this->frameworkInterface::getStoragePath();
        $targetedFile = $storagePath.'/'.$reviewEntity['sent_files'][$key];
        unlink($targetedFile);
        unset($reviewEntity['sent_files'][$key]);
        $this->reviewRepositoryInterface->updateReviewFile($reviewEntity['id'], $reviewEntity);
    }
}
