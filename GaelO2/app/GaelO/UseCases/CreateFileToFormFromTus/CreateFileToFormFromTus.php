<?php

namespace App\GaelO\UseCases\CreateFileToFormFromTus;

use App\GaelO\Constants\Constants;
use App\GaelO\Exceptions\AbstractGaelOException;
use App\GaelO\Exceptions\GaelOBadRequestException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Exceptions\GaelOValidateDicomException;
use App\GaelO\Interfaces\Adapters\FrameworkInterface;
use App\GaelO\Interfaces\Adapters\MimeInterface;
use App\GaelO\Interfaces\Repositories\ReviewRepositoryInterface;
use App\GaelO\Interfaces\Repositories\TrackerRepositoryInterface;
use App\GaelO\Interfaces\Repositories\VisitRepositoryInterface;
use App\GaelO\Services\AuthorizationService\AuthorizationReviewService;
use App\GaelO\Services\AuthorizationService\AuthorizationVisitService;
use App\GaelO\Services\FormService\InvestigatorFormService;
use App\GaelO\Services\FormService\ReviewFormService;
use App\GaelO\Services\OrthancService;
use App\GaelO\Services\TusService;
use App\GaelO\Util;
use Exception;
use ZipArchive;

class CreateFileToFormFromTus
{

    private AuthorizationVisitService $authorizationVisitService;
    private AuthorizationReviewService $authorizationReviewService;
    private ReviewRepositoryInterface $reviewRepositoryInterface;
    private TrackerRepositoryInterface $trackerRepositoryInterface;
    private VisitRepositoryInterface $visitRepositoryInterface;
    private FrameworkInterface $frameworkInterface;
    private MimeInterface $mimeInterface;
    private OrthancService $orthancService;
    private TusService $tusService;

    public function __construct(
        AuthorizationVisitService $authorizationVisitService,
        AuthorizationReviewService $authorizationReviewService,
        VisitRepositoryInterface $visitRepositoryInterface,
        ReviewRepositoryInterface $reviewRepositoryInterface,
        FrameworkInterface $frameworkInterface,
        OrthancService $orthancService,
        TrackerRepositoryInterface $trackerRepositoryInterface,
        MimeInterface $mimeInterface,
        TusService $tusService,
    ) {
        $this->authorizationVisitService = $authorizationVisitService;
        $this->authorizationReviewService = $authorizationReviewService;
        $this->reviewRepositoryInterface = $reviewRepositoryInterface;
        $this->visitRepositoryInterface = $visitRepositoryInterface;
        $this->frameworkInterface = $frameworkInterface;
        $this->trackerRepositoryInterface = $trackerRepositoryInterface;
        $this->mimeInterface = $mimeInterface;
        $this->orthancService = $orthancService;
        $this->tusService = $tusService;
    }

    public function execute(CreateFileToFormFromTusRequest $createFileToFormFromTusRequest, CreateFileToFormFromTusResponse $createFileToFormFromTusResponse)
    {

        try {
            $reviewEntity = $this->reviewRepositoryInterface->find($createFileToFormFromTusRequest->id);

            $currentUserId = $createFileToFormFromTusRequest->currentUserId;
            $studyName = $reviewEntity['study_name'];
            $local = $reviewEntity['local'];
            $visitId = $reviewEntity['visit_id'];
            $reviewId = $reviewEntity['id'];
            $validated = $reviewEntity['validated'];

            $key = $createFileToFormFromTusRequest->key;
            $tusIds = $createFileToFormFromTusRequest->tusIds;

            if (empty($tusIds)) {
                throw new GaelOBadRequestException('Tus Ids Should not be empty');
            }

            $this->checkAuthorization($local, $validated, $reviewId, $visitId, $currentUserId, $studyName);

            //Set Time Limit at 30min as operation could be really long
            set_time_limit(1800);

            $file = null;

            //Several file, expected ziped dicom upload, unzip and merge in a single zipe
            if ($createFileToFormFromTusRequest->isDicom) {
                //Create Temporary folder to work
                $unzipedPath = Util::getUploadTemporaryFolder();

                //Get uploaded Zips from TUS and upzip it in a temporary folder
                foreach ($tusIds as $tusId) {
                    $tusTempZip = $this->tusService->getFile($tusId);

                    $zipSize = filesize($tusTempZip);
                    $uncompressedzipSize = Util::getZipUncompressedSize($tusTempZip);
                    if ($uncompressedzipSize / $zipSize > 50) {
                        throw new GaelOValidateDicomException("Bomb Zip");
                    }

                    $zip = new ZipArchive();
                    $zip->open($tusTempZip);
                    $zip->extractTo($unzipedPath);
                    $zip->close();

                    //Remove file from TUS and downloaded temporary zip
                    $this->tusService->deleteFile($tusId);
                    unlink($tusTempZip);
                }

                $expectedNumberOfInstances = $createFileToFormFromTusRequest->numberOfInstances;

                $this->orthancService->setOrthancServer(false);
                $orthancStudyImport = $this->orthancService->importDicomFolder($unzipedPath);
                $importedNumberOfInstances = $orthancStudyImport->getNumberOfInstances();
                $importedOrthancStudyID = $orthancStudyImport->getStudyOrthancId();

                if ($expectedNumberOfInstances !== $importedNumberOfInstances) {
                    $this->orthancService->deleteFromOrthanc("studies", $importedOrthancStudyID);
                    throw new GaelOValidateDicomException("Imported DICOM not matching announced number of Instances");
                }

                $tempFileLocation = tempnam(ini_get('upload_tmp_dir'), 'TMPZIP_');
                $this->orthancService->getZipStreamToFile([$importedOrthancStudyID], $tempFileLocation);
                $file = $tempFileLocation;
            } else {
                //Only one file
                if (sizeof($tusIds) === 1) {
                    $file = $this->tusService->getFile($tusIds[0]);
                    $this->tusService->deleteFile($tusIds[0]);
                } else throw new GaelOBadRequestException("A single TUS Id is expected for non-DICOM upload");
            }

            //Send file to associated file
            $mime = mime_content_type($file);

            if (!is_null($createFileToFormFromTusRequest->extension)) {
                $extension = $createFileToFormFromTusRequest->extension;
            } else {
                $extension = $this->mimeInterface::getExtensionsFromMime($mime)[0];
            }

            $fileName = 'review_' . $reviewId . '_' . $key . '.' . $extension;

            $visitContext = $this->visitRepositoryInterface->getVisitWithContextAndReviewStatus($visitId, $studyName);

            $formService = null;

            if ($local) {
                $formService = $this->frameworkInterface->make(InvestigatorFormService::class);
            } else {
                $formService = $this->frameworkInterface->make(ReviewFormService::class);
            }

            $formService->setVisitContextAndStudy($visitContext, $studyName);
            $formService->attachFile($reviewEntity, $key, $fileName, $mime, fopen($file, 'r'));
            //Remove temporary file
            unlink($file);
            $actionDetails = [
                'uploaded_file' => $key,
                'filename' => $fileName,
                'review_id' => $reviewId
            ];

            $this->trackerRepositoryInterface->writeAction(
                $createFileToFormFromTusRequest->currentUserId,
                $local ? Constants::ROLE_INVESTIGATOR : Constants::ROLE_SUPERVISOR,
                $studyName,
                $visitId,
                $local ? Constants::TRACKER_SAVE_INVESTIGATOR_FORM : Constants::TRACKER_SAVE_REVIEWER_FORM,
                $actionDetails
            );

            $createFileToFormFromTusResponse->status = 201;
            $createFileToFormFromTusResponse->statusText =  'Created';
        } catch (AbstractGaelOException $e) {
            $createFileToFormFromTusResponse->body = $e->getErrorBody();
            $createFileToFormFromTusResponse->status = $e->statusCode;
            $createFileToFormFromTusResponse->statusText =  $e->statusText;
        } catch (Exception $e) {
            throw $e;
        }
    }

    private function checkAuthorization(bool $local, bool $validated, int $reviewId, int $visitId, int $currentUserId, string $studyName): void
    {
        if ($validated) throw new GaelOForbiddenException("Form Already Validated");

        if ($local) {
            $this->authorizationVisitService->setVisitId($visitId);
            $this->authorizationVisitService->setUserId($currentUserId);
            $this->authorizationVisitService->setStudyName($studyName);
            if (!$this->authorizationVisitService->isVisitAllowed(Constants::ROLE_INVESTIGATOR)) throw new GaelOForbiddenException();
        } else {
            $this->authorizationReviewService->setUserId($currentUserId);
            $this->authorizationReviewService->setReviewId($reviewId);
            if (!$this->authorizationReviewService->isReviewAllowed(Constants::ROLE_REVIEWER)) throw new GaelOForbiddenException();
        }
    }
}
