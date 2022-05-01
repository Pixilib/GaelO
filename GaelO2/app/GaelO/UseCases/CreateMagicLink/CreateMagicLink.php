<?php

namespace App\GaelO\UseCases\CreateMagicLink;

use App\GaelO\Constants\Constants;
use App\GaelO\Exceptions\GaelOBadRequestException;
use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Interfaces\Adapters\FrameworkInterface;
use App\GaelO\Interfaces\Repositories\PatientRepositoryInterface;
use App\GaelO\Interfaces\Repositories\VisitRepositoryInterface;
use App\GaelO\Services\AuthorizationService\AuthorizationStudyService;
use App\GaelO\Services\MagicLinkService;
use App\GaelO\Services\MailServices;
use Exception;

class CreateMagicLink{

    private AuthorizationStudyService $authorizationStudyService;
    private VisitRepositoryInterface $visitRepositoryInterface;
    private PatientRepositoryInterface $patientRepositoryInterface;
    private MailServices $mailServices;
    private FrameworkInterface $frameworkInterface;

    public function __construct(AuthorizationStudyService $authorizationStudyService,
                                VisitRepositoryInterface $visitRepositoryInterface,
                                PatientRepositoryInterface $patientRepositoryInterface,
                                FrameworkInterface $frameworkInterface,
                                MailServices $mailServices)
    {
        $this->authorizationStudyService = $authorizationStudyService;
        $this->patientRepositoryInterface = $patientRepositoryInterface;
        $this->visitRepositoryInterface = $visitRepositoryInterface;
        $this->frameworkInterface = $frameworkInterface;
        $this->mailServices = $mailServices;
    }

    public function execute(CreateMagicLinkRequest $createMagicLinkRequest, CreateMagicLinkResponse $createMagicLinkResponse){
        try{

            $currentUserId = $createMagicLinkRequest->currentUserId;
            $ressourceId = $createMagicLinkRequest->ressourceId;
            $level = $createMagicLinkRequest->ressourceLevel;
            $role = ucfirst($createMagicLinkRequest->role);

            $patientCode = null;
            $visitType = null;

            if( !in_array($level, ['visit', 'patient']) ) throw new GaelOBadRequestException('Wrong Level Type');
            if( !in_array($role, [Constants::ROLE_INVESTIGATOR, Constants::ROLE_CONTROLLER, Constants::ROLE_MONITOR, Constants::ROLE_SUPERVISOR]) ) throw new GaelOBadRequestException('Wrong Role Type');

            if($level === 'patient'){
                $patientEntity = $this->patientRepositoryInterface->find($ressourceId);
                $studyName = $patientEntity['study_name'];
                $patientCode = $patientEntity['code'];

            }else if($level === 'visit'){
                $visitContext = $this->visitRepositoryInterface->getVisitContext($ressourceId);
                $studyName = $visitContext['patient']['study_name'];
                $patientCode = $visitContext['patient']['code'];
                $visitType = $visitContext['visit_type']['name'];

            }

            $this->checkAuthorization($currentUserId, $studyName);

            //Generate Magic Link for targeted user
            $urlLink = $this->frameworkInterface->createMagicLink($createMagicLinkRequest->targetUser, '/study/'.$studyName.'/role/'.$role.'/'.$level.'/'.$ressourceId);

            //Send the magic link to destinators
            $this->mailServices->sendMagicLink($createMagicLinkRequest->targetUser, $studyName, $urlLink, $role, $patientCode, $visitType);

            $createMagicLinkResponse->status = 200;
            $createMagicLinkResponse->statusText =  'OK';

        } catch (GaelOException $e){

            $createMagicLinkResponse->body = $e->getErrorBody();
            $createMagicLinkResponse->status = $e->statusCode;
            $createMagicLinkResponse->statusText =  $e->statusText;

        }catch (Exception $e){
            throw $e;
        }
    }

    private function checkAuthorization(int $currentUserId, string $studyName){

        $this->authorizationStudyService->setUserId($currentUserId);
        $this->authorizationStudyService->setStudyName($studyName);
        if( ! $this->authorizationStudyService->isAllowedStudy( Constants::ROLE_SUPERVISOR )){
            throw new GaelOForbiddenException();
        }

    }
}
