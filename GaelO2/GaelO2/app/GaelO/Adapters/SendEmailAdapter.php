<?php

namespace App\GaelO\Adapters;

use Illuminate\Support\Facades\Mail;
use App\GaelO\Interfaces\MailInterface;
use App\Mail\Adjudication;
use App\Mail\AdminLoged;
use App\Mail\BlockedAccount;
use App\Mail\ChangePasswordDeactivated;
use App\Mail\Conclusion;
use App\Mail\CorrectiveAction;
use App\Mail\DeletedForm;
use App\Mail\QCDecision;
use App\Mail\Request;
use App\Mail\ResetPassword;
use App\Mail\ReviewReady;
use App\Mail\UnconfirmedAccount;
use App\Mail\UnlockedForm;
use App\Mail\UnlockRequest;
use App\Mail\UploadedVisit;
use App\Mail\UploadFailure;
use App\Mail\UserCreated;
use App\Mail\VisitNotDone;

use App\GaelO\Constants\MailConstants;
use App\Mail\ImportPatient;
use Illuminate\Contracts\Mail\Mailable;
use Illuminate\Support\Facades\Log;

class SendEmailAdapter implements MailInterface {

    private array $to;

    public function setReplyTo( ?String $replyTo = null ){
        if($replyTo == null) $this->replyTo= LaravelFunctionAdapter::getConfig('mailReplyToDefault');
        else $this->replyTo = $replyTo;
    }

    public function setTo(array $to){
        $this->to = $to;

    }

    public function setParameters(array $parameters){
        $this->parameters = $parameters;
        $this->parameters['platformName'] = LaravelFunctionAdapter::getConfig('name');
        $this->parameters['webAddress'] = LaravelFunctionAdapter::getConfig('url');
        $this->parameters['corporation'] = LaravelFunctionAdapter::getConfig('corporation');
        $this->parameters['adminEmail']= LaravelFunctionAdapter::getConfig('mailFromAddress');

    }

    public function setBody($modelType){
        $this->modelType = $modelType;
    }

    public function send(){
        foreach($this->to as $destinator ){
            $model = $this->getModel($this->modelType);
            Mail::to($destinator)->send($model);
        }


    }

    private function getModel(int $model) : Mailable{

        switch ($model) {
            case MailConstants::EMAIL_REQUEST:
                $model = new Request($this->parameters);
                break;
            case MailConstants::EMAIL_VISIT_NOT_DONE:
                $model = new VisitNotDone($this->parameters);
                break;
            case MailConstants::EMAIL_USER_CREATED:
                $model = new UserCreated($this->parameters);
                break;
            case MailConstants::EMAIL_UPLOAD_FAILURE:
                $model = new UploadFailure($this->parameters);
                break;
            case MailConstants::EMAIL_UPLOADED_VISIT:
                $model = new UploadedVisit($this->parameters);
                break;
            case MailConstants::EMAIL_UNLOCK_REQUEST:
                $model = new UnlockRequest($this->parameters);
                break;
            case MailConstants::EMAIL_UNLOCK_FORM:
                $model = new UnlockedForm($this->parameters);
                break;
            case MailConstants::EMAIL_UNCONFIRMED_ACCOUNT:
                $model = new UnconfirmedAccount($this->parameters);
                break;
            case MailConstants::EMAIL_REVIEW_READY:
                $model = new ReviewReady($this->parameters);
                break;
            case MailConstants::EMAIL_RESET_PASSWORD:
                $model = new ResetPassword($this->parameters);
                break;
            case MailConstants::EMAIL_QC_DECISION:
                $model = new QCDecision($this->parameters);
                break;
            case MailConstants::EMAIL_DELETED_FORM:
                $model = new DeletedForm($this->parameters);
                break;
            case MailConstants::EMAIL_CORRECTIVE_ACTION:
                $model = new CorrectiveAction($this->parameters);
                break;
            case MailConstants::EMAIL_CONCLUSION:
                $model = new Conclusion($this->parameters);
                break;
            case MailConstants::EMAIL_CHANGE_PASSWORD_DEACTIVATED:
                $model = new ChangePasswordDeactivated($this->parameters);
                break;
            case MailConstants::EMAIL_BLOCKED_ACCOUNT:
                $model = new BlockedAccount($this->parameters);
                break;
            case MailConstants::EMAIL_ADMIN_LOGGED:
                $model = new AdminLoged($this->parameters);
                break;
            case MailConstants::EMAIL_ADJUDICATION:
                $model = new Adjudication($this->parameters);
                break;
            case MailConstants::EMAIL_IMPORT_PATIENT:
                $model = new ImportPatient($this->parameters);
                break;
            break;
        }

        return $model;

    }

}
