<?php

namespace App\GaelO\Adapters;

use Illuminate\Support\Facades\Mail;
use App\Mail\Adjudication;
use App\Mail\AdminLoged;
use App\Mail\BlockedAccount;
use App\Mail\ChangePasswordDeactivated;
use App\Mail\Conclusion;
use App\Mail\CorrectiveAction;
use App\Mail\DeletedForm;
use App\Mail\QCDecision;
use App\Mail\Request;
use App\Mail\ReviewReady;
use App\Mail\UnlockedForm;
use App\Mail\UnlockRequest;
use App\Mail\UploadedVisit;
use App\Mail\UploadFailure;
use App\Mail\VisitNotDone;
use App\Mail\Reminder;
use App\Mail\MailUser;

use App\GaelO\Constants\MailConstants;
use App\GaelO\Constants\SettingsConstants;
use App\GaelO\Interfaces\Adapters\FrameworkInterface;
use App\GaelO\Interfaces\Adapters\MailerInterface;
use App\Mail\ImportPatient;
use App\Mail\MagicLink;
use App\Mail\UserCreated;
use Illuminate\Contracts\Mail\Mailable;

class MailerAdapter implements MailerInterface {

    private FrameworkInterface $frameworkInterface;

    public function __construct(FrameworkInterface $frameworkInterface)
    {
        $this->frameworkInterface = $frameworkInterface;
    }

    private array $to;

    public function setReplyTo( ?String $replyTo = null ){
        if($replyTo == null) $this->replyTo= $this->frameworkInterface::getConfig('mail_reply_to_default');
        else $this->replyTo = $replyTo;
    }

    public function setTo(array $to){
        //get only unique emails
        $this->to = array_values(array_unique($to));

    }

    public function setParameters(array $parameters){
        $this->parameters = $parameters;
        $this->parameters['platformName'] = $this->frameworkInterface::getConfig(SettingsConstants::PLATFORM_NAME);
        $this->parameters['webAddress'] = $this->frameworkInterface::getConfig(SettingsConstants::APP_URL);
        $this->parameters['corporation'] = $this->frameworkInterface::getConfig(SettingsConstants::CORPORATION);
        $this->parameters['adminEmail']= $this->frameworkInterface::getConfig(SettingsConstants::MAIL_FROM_ADDRESS);

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
            case MailConstants::EMAIL_REVIEW_READY:
                $model = new ReviewReady($this->parameters);
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
            case MailConstants::EMAIL_REMINDER:
                $model = new Reminder($this->parameters);
                break;
            case MailConstants::EMAIL_USER:
                $model = new MailUser($this->parameters);
                break;
            case MailConstants::EMAIL_USER_CREATED:
                $model = new UserCreated($this->parameters);
                break;
            case MailConstants::EMAIL_MAGIC_LINK:
                $model = new MagicLink($this->parameters);
                break;
        }

        return $model;

    }

}
