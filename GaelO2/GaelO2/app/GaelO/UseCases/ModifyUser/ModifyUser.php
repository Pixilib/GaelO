<?php

namespace App\GaelO\UseCases\ModifyUser;

use App\GaelO\Adapters\LaravelFunctionAdapter;
use App\GaelO\Constants\Constants;
use App\GaelO\Interfaces\PersistenceInterface;

use App\GaelO\UseCases\ModifyUser\ModifyUserRequest;
use App\GaelO\UseCases\ModifyUser\ModifyUserResponse;
use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Services\MailServices;
use App\GaelO\Services\TrackerService;

class ModifyUser {

    public function __construct(PersistenceInterface $persistenceInterface, TrackerService $trackerService, MailServices $mailService){
        $this->persistenceInterface = $persistenceInterface;
        $this->trackerService = $trackerService;
        $this->mailService = $mailService;
    }

    public function execute(ModifyUserRequest $modifyUserRequest, ModifyUserResponse $modifyUserResponse) : void {

        try {

            $id = $modifyUserRequest->id;
            $data = get_object_vars($modifyUserRequest);
            $user = $this->persistenceInterface->find($id);

            $this->checkFormComplete($data);
            $this->checkEmailValid($data);
            if($modifyUserRequest->email !== $user['email']) $this->checkNewEmailUnique($data['email']);
            if($modifyUserRequest->username !== $user['username']) $this->checkNewUsernameUnique($data['username']);

            if($modifyUserRequest->status === Constants::USER_STATUS_UNCONFIRMED) {
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

            $this->persistenceInterface->updateUser($user['id'], $data['username'],
                                                                $data['lastname'],
                                                                $data['firstname'],
                                                                $data['status'],
                                                                $data['email'],
                                                                $data['phone'],
                                                                $data['administrator'],
                                                                $data['centerCode'],
                                                                $data['job'],
                                                                $data['orthancAddress'],
                                                                $data['orthancLogin'],
                                                                $data['orthancPassword'],
                                                                $data['password_temporary'],
                                                                $data['password'],
                                                                $data['creation_date'],
                                                                $data['last_password_update']);

            $details = [
                'modified_user_id'=>$user['id'],
                'status'=>$user['status']
            ];

            $this->trackerService->writeAction($modifyUserRequest->currentUserId, Constants::TRACKER_ROLE_USER, null, null, Constants::TRACKER_EDIT_USER, $details);

            $modifyUserResponse->status = 200;
            $modifyUserResponse->statusText = 'OK';

        } catch (GaelOException $e) {
            $modifyUserResponse->status = 400;
            $modifyUserResponse->statusText = $e->getMessage();
        } catch (\Exception $e) {
            throw $e;
        }
    }

    private function checkFormComplete(array $data) {
        if(!isset($data['username'])
        || !isset($data['job'])
        || !isset($data['email'])
        || !is_numeric($data['centerCode'])
        || !isset($data['administrator']) )
        throw new GaelOException('Form incomplete');
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
