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
use Mail_Constants;

class SendEmailAdapter implements MailInterface {



    public function setReplyTo(string $replyTo){
        $this->replyTo = $replyTo;
    }

    public function setTo(array $to){
        $this->to = $to;

    }

    public function setVariable(array $variables){
        $this->variables = $variables;
        $this->variables['platformName']= "GaelO";
        $this->variables['webAddress']= "http://gaelo.fr";
        $this->variables['corporation']= "GaelO";
        $this->variables['adminEmail']= "salim.kanoun@gmail.com";

    }

    public function sendModel(int $model){

        switch ($model) {
            case Mail_Constants::EMAIL_REQUEST:
                $this->model = new Request($this->parameters);
                break;
            case Mail_Constants::EMAIL_VISIT_NOT_DONE:
                $this->model = new VisitNotDone($this->parameters);
                break;
            case Mail_Constants::EMAIL_USER_CREATED:
                $this->model = new UserCreated($this->parameters);
                break;
            case Mail_Constants::EMAIL_UPLOAD_FAILURE:
                $this->model = new UploadFailure($this->parameters);
                break;
            case Mail_Constants::EMAIL_UPLOADED_VISIT:
                $this->model = new UploadedVisit($this->parameters);
                break;
            case Mail_Constants::EMAIL_UNLOCK_REQUEST:
                $this->model = new UnlockRequest($this->parameters);
                break;
            case Mail_Constants::EMAIL_UNLOCK_FORM:
                $this->model = new UnlockedForm($this->parameters);
                break;
            case Mail_Constants::EMAIL_UNCONFIRMED_ACCOUNT:
                $this->model = new UnconfirmedAccount($this->parameters);
                break;
            case Mail_Constants::EMAIL_REVIEW_READY:
                $this->model = new ReviewReady($this->parameters);
                break;
            case Mail_Constants::EMAIL_RESET_PASSWORD:
                $this->model = new ResetPassword($this->parameters);
                break;
            case Mail_Constants::EMAIL_QC_DECISION:
                $this->model = new QCDecision($this->parameters);
                break;
            case Mail_Constants::EMAIL_DELETED_FORM:
                $this->model = new DeletedForm($this->parameters);
                break;
            case Mail_Constants::EMAIL_CORRECTIVE_ACTION:
                $this->model = new CorrectiveAction($this->parameters);
                break;
            case Mail_Constants::EMAIL_CONCLUSION:
                $this->model = new Conclusion($this->parameters);
                break;
            case Mail_Constants::EMAIL_CHANGE_PASSWORD_DEACTIVATED:
                $this->model = new ChangePasswordDeactivated($this->parameters);
                break;
            case Mail_Constants::EMAIL_BLOCKED_ACCOUNT:
                $this->model = new BlockedAccount($this->parameters);
                break;
            case Mail_Constants::EMAIL_ADMIN_LOGGED:
                $this->model = new AdminLoged($this->parameters);
                break;
            case Mail_Constants::EMAIL_ADJUDICATION:
                $this->model = new Adjudication($this->parameters);
                break;
        }

        $this->sendEmail();


    }


    public function setBody(string $body){
        $this->body = $body;

    }

    public function sendEmail(){
        foreach($this->to as $destinator ){
            Mail::to($destinator)->queue($this->model);
        }
    }

}
