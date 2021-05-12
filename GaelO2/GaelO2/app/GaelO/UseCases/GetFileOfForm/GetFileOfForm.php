<?php

namespace App\GaelO\UseCases\GetFileOfForm;

use App\GaelO\Adapters\LaravelFunctionAdapter;
use App\GaelO\Constants\Constants;
use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Interfaces\ReviewRepositoryInterface;
use App\GaelO\Services\AuthorizationVisitService;
use Exception;

class GetFileOfForm {

    private AuthorizationVisitService $authorizationVisitService;
    private ReviewRepositoryInterface $reviewRepositoryInterface;

    public function __construct(AuthorizationVisitService $authorizationVisitService, ReviewRepositoryInterface $reviewRepositoryInterface)
    {
        $this->authorizationVisitService = $authorizationVisitService;
        $this->reviewRepositoryInterface = $reviewRepositoryInterface;
    }

    public function execute(GetFileOfFormRequest $getFileOfFormRequest, GetFileOfFormResponse $getFileOfFormResponse){

        try{

            $reviewEntity = $this->reviewRepositoryInterface->find($getFileOfFormRequest->id);

            $userId = $reviewEntity['user_id'];
            $local = $reviewEntity['local'];
            $visitId = $reviewEntity['visit_id'];
            $this->checkAuthorization($local, $userId, $visitId, $getFileOfFormRequest->currentUserId);

            $getFileOfFormResponse->status = 200;
            $getFileOfFormResponse->statusText = 'OK';
            $getFileOfFormResponse->filePath = LaravelFunctionAdapter::getStoragePath().'/'.$reviewEntity['sent_files'][$getFileOfFormRequest->key];
            $getFileOfFormResponse->filename = basename($reviewEntity['sent_files'][$getFileOfFormRequest->key]);

        } catch (GaelOException $e){

            $getFileOfFormResponse->status = $e->statusCode;
            $getFileOfFormResponse->statusText = $e->statusText;
            $getFileOfFormResponse->body = $e->getErrorBody();

        } catch (Exception $e){

            throw $e;

        }

    }

    private function checkAuthorization(bool $local, int $reviewOwner, int $visitId, int $currentUserId): void
    {
        $this->authorizationVisitService->setVisitId($visitId);
        if ($local) {
            $this->authorizationVisitService->setCurrentUserAndRole($currentUserId, Constants::ROLE_INVESTIGATOR);
            if (!$this->authorizationVisitService->isVisitAllowed()) throw new GaelOForbiddenException();
        } else {
            $this->authorizationVisitService->setCurrentUserAndRole($currentUserId, Constants::ROLE_SUPERVISOR);
            if (!$this->authorizationVisitService->isVisitAllowed()) throw new GaelOForbiddenException();
            if ($reviewOwner !== $currentUserId) throw new GaelOForbiddenException("Only form owner can add files");
        }
    }
}
