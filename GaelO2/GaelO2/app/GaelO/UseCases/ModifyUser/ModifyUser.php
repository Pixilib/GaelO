<?php

namespace App\GaelO\UseCases\ModifyUser;

use App\GaelO\Adapters\LaravelFunctionAdapter;
use App\GaelO\Constants\Constants;
use App\GaelO\Interfaces\PersistenceInterface;

use App\GaelO\UseCases\ModifyUser\ModifyUserRequest;
use App\GaelO\UseCases\ModifyUser\ModifyUserResponse;
use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Services\Mails\MailServices;
use App\GaelO\Services\TrackerService;

class ModifyUser {

    public function __construct(PersistenceInterface $persistenceInterface, TrackerService $trackerService, MailServices $mailService){
        $this->persistenceInterface = $persistenceInterface;
        $this->trackerService = $trackerService;
        $this->mailService = $mailService;
     }

     public function execute(ModifyUserRequest $userRequest, ModifyUserResponse $userResponse) : void
    {

        try {

            $id = $userRequest->id;
            $data = get_object_vars($userRequest);
            $user = $this->persistenceInterface->find($id);

            $this->checkFormComplete($data);
            $this->checkEmailValid($data);
            if($userRequest->email !== $user['email']) $this->checkNewEmailUnique($data['email']);
            if($userRequest->username !== $user['username']) $this->checkNewUsernameUnique($data['username']);

            if($userRequest->status === Constants::USER_STATUS_UNCONFIRMED) {
                $newPassword = substr(uniqid(), 1, 10);
                $data['password_temporary'] = LaravelFunctionAdapter::hash( $newPassword );

                $this->mailService->sendResetPasswordMessage(
                    ($data['firstname'].' '.$data['lastname']),
                    $data['username'],
                    $newPassword,
                    $data['email']
                );
            }else{
                $data['password_temporary'] = null;
            }

            //These property can't be modified in user edition
            $data['password'] = $user['password'];
            $data['password_previous1'] = $user['password_previous1'];
            $data['password_previous2'] = $user['password_previous2'];
            $data['last_password_update'] = $user['last_password_update'];
            $data['creation_date'] = $user['creation_date'];

            $this->persistenceInterface->update($user['id'], $data);

            $details = [
                'modified_user_id'=>$user['id'],
                'status'=>$user['status']
            ];

            $this->trackerService->writeAction($user['id'], Constants::TRACKER_ROLE_USER, null, null, Constants::TRACKER_EDIT_USER, $details);

            $userResponse->status = 200;
            $userResponse->statusText = 'OK';

        } catch (GaelOException $e) {
            $userResponse->status = 400;
            $userResponse->statusText = $e->getMessage();
        } catch (\Exception $e) {
            throw $e;
        }
    }

    private function checkFormComplete(array $data) {
         if(!isset($data['username']) ||
        !isset($data['lastname']) ||
        !isset($data['firstname']) ||
        !isset($data['email']) ||
        !is_numeric($data['center_code']) ||
        !isset($data['job']) ||
        !isset($data['status']) ||
        !isset($data['administrator']) ||
        !isset($data['phone']) ||
        !isset($data['orthanc_address']) ||
        !isset($data['orthanc_login']) ||
        !isset($data['orthanc_password'])
        ) throw new GaelOException('Form incomplete');
    }

    private function checkEmailValid(array $data) {
        if (!preg_match('/^[a-z0-9\-_.]+@[a-z0-9\-_.]+\.[a-z]{2,4}$/i', $data['email'])) throw new GaelOException('Not a valid email format');
    }

    private function checkNewUsernameUnique($username){
        $knownUsername = $this->persistenceInterface->isExistingUsername($username);
        if($knownUsername) throw new GaelOException("Username Already Used");

    }

    private function checkNewEmailUnique($email){
        $knownEmail = $this->persistenceInterface->isExistingEmail($email);
        if($knownEmail) throw new GaelOException("Email Already Known");

    }

}

?>
