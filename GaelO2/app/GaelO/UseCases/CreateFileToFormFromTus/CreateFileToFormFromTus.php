<?php

namespace App\GaelO\UseCases\CreateFileToFormFromTus;

use App\GaelO\Constants\Constants;
use App\GaelO\Exceptions\AbstractGaelOException;
use App\GaelO\Exceptions\GaelOBadRequestException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Exceptions\GaelOValidateDicomException;
use App\GaelO\Interfaces\Adapters\MimeInterface;
use App\GaelO\Interfaces\Repositories\ReviewRepositoryInterface;
use App\GaelO\Interfaces\Repositories\TrackerRepositoryInterface;
use App\GaelO\Interfaces\Repositories\VisitRepositoryInterface;
use App\GaelO\Services\AuthorizationService\AuthorizationReviewService;
use App\GaelO\Services\AuthorizationService\AuthorizationVisitService;
use App\GaelO\Services\FormService\FormService;
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
    private FormService $formService;
    private MimeInterface $mimeInterface;
    private TusService $tusService;

    public function __construct(
        AuthorizationVisitService $authorizationVisitService,
        AuthorizationReviewService $authorizationReviewService,
        VisitRepositoryInterface $visitRepositoryInterface,
        ReviewRepositoryInterface $reviewRepositoryInterface,
        FormService $formService,
        TrackerRepositoryInterface $trackerRepositoryInterface,
        MimeInterface $mimeInterface,
        TusService $tusService,
    ) {
        $this->authorizationVisitService = $authorizationVisitService;
        $this->authorizationReviewService = $authorizationReviewService;
        $this->reviewRepositoryInterface = $reviewRepositoryInterface;
        $this->visitRepositoryInterface = $visitRepositoryInterface;
        $this->formService = $formService;
        $this->trackerRepositoryInterface = $trackerRepositoryInterface;
        $this->mimeInterface = $mimeInterface;
        $this->tusService = $tusService;
    }

    //SK TODO FINIR CE USECASE
    //EXPOSER UNE ROUTE
    //TESTER
    //Tester Validated Dicom
    
    public function execute(CreateFileToFormFromTusRequest $createFileToFormFromTusRequest, CreateFileToFormFromTusResponse $createFileToFormFromTusResponse)
    {

        try {
            $reviewEntity = $this->reviewRepositoryInterface->find($createFileToFormFromTusRequest->id);

            $studyName = $reviewEntity['study_name'];
            $local = $reviewEntity['local'];
            $visitId = $reviewEntity['visit_id'];
            $reviewId = $reviewEntity['id'];

            $key = $createFileToFormFromTusRequest->key;
            $tusIds = $createFileToFormFromTusRequest->tusIds;

            if (empty($tusIds)) {
                throw new GaelOBadRequestException('Tus Ids Should not be empty');
            }

            $this->checkAuthorization($local, $reviewEntity['validated'], $createFileToFormFromTusRequest->id, $visitId, $createFileToFormFromTusRequest->currentUserId, $studyName);

            $file = null;

            //Only one file
            if (sizeof($tusIds) === 1) {
                $file = $this->tusService->getFile($tusIds[0]);
                $this->tusService->deleteFile($tusIds[0]);
            }

            //Several file, expected ziped dicom upload, unzip and merge in a single zipe
            if (sizeof($tusIds) > 1) {
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

                $tempFileLocation = tempnam(ini_get('upload_tmp_dir'), 'TMPZIP_');
                //SK REPRENDRE ICI
                $expectedNumberOfInstances = $createFileToFormFromTusRequest->numberOfInstances;

                $orthancStudyImport = $this->orthancService->importDicomFolder($unzipedPath);
                if ($expectedNumberOfInstances !== $orthancStudyImport->getNumberOfInstances()) {
                    throw new GaelOValidateDicomException("Imported DICOM not matching announced number of Instances");
                }

                $importedOrthancStudyID = $orthancStudyImport->getStudyOrthancId();

                $file = $this->orthancService->getZipStreamToFile([$studyID], $tempFileLocation);
            }

            $mime = mime_content_type($file);
            $extension = $this->mimeInterface::getExtensionFromMime($mime);
            $fileName = 'review_' . $reviewId . '_' . $key . '.' . $extension;

            $visitContext = $this->visitRepositoryInterface->getVisitContext($visitId);
            $this->formService->setVisitContextAndStudy($visitContext, $studyName);

            $this->formService->attachFile($reviewEntity, $key, $fileName, $mime, $file);

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
