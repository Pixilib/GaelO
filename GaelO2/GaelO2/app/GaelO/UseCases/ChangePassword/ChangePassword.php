<?php

namespace App\GaelO\UseCases\ChangePassword;

use App\GaelO\Interfaces\PersistenceInterface;

use App\GaelO\UseCases\ChangePassword\ChangePasswordRequest;
use App\GaelO\UseCases\ChangePassword\ChangePasswordResponse;
use App\GaelO\Adapters\LaravelFunctionAdapter;
use App\GaelO\Util;
use App\GaelO\Constants\Constants;

use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Services\TrackerService;

class ChangePassword {

    public function __construct(PersistenceInterface $persistenceInterface, TrackerService $trackerService){
        $this->persistenceInterface = $persistenceInterface;
        $this->trackerService = $trackerService;
     }

    public function execute(ChangePasswordRequest $userRequest, ChangePasswordResponse $userResponse) : void {
        $id = $userRequest->id;
        $previousPassword = $userRequest->previous_password;
        $password1 = $userRequest->password1;
        $password2 = $userRequest->password2;

        $user = $this->persistenceInterface->find($id);

        try{

            if($user['status'] === Constants::USER_STATUS_UNCONFIRMED) {
                $this->checkMatchHashPasswords($previousPassword, $user['password_temporary']);
            } else {
                $this->checkMatchHashPasswords($previousPassword, $user['password']);
            }

            $this->checkPasswordFormatCorrect($password1);
            $this->checkMatchPasswords($password1, $password2);
            $this->checkNewPassword(
                $password1,
                $user['password_temporary'] ,
                $user['password'],
                $user['password_previous1'],
                $user['password_previous2']);

            $data['password_previous1'] = $user['password'];
            $data['password_previous2'] = $user['password_previous1'];
            $data['password'] = LaravelFunctionAdapter::hash($password1);
            $data['last_password_update'] = Util::now();
            $data['status'] = Constants::USER_STATUS_ACTIVATED;

            $this->persistenceInterface->update($user['id'], $data);
            $this->trackerService->writeAction($user['id'], Constants::TRACKER_ROLE_USER, null, null, Constants::TRACKER_CHANGE_PASSWORD, null);

            $userResponse->status = 200;
            $userResponse->statusText = 'OK';

        } catch(GaelOException $e) {
            $userResponse->status = 400;
            $userResponse->statusText = $e->getMessage();
            return;
        } catch (\Exception $e) {
            throw $e;
        }



    }

     /**
      * Check that candidate password is not in the 3 last used passwords
      */
    private function checkNewPassword($passwordCandidate, $temporaryPassword, $currentPassword, $previousPassword1, $previousPassword2) : void {
        $checkTemporary = LaravelFunctionAdapter::checkHash($passwordCandidate, $temporaryPassword);
        if ($currentPassword !== null) $checkCurrent = LaravelFunctionAdapter::checkHash($passwordCandidate, $currentPassword);
        else $checkCurrent = false;
        if ($previousPassword1 !== null) $checkPrevious1 = LaravelFunctionAdapter::checkHash($passwordCandidate, $previousPassword1);
        else $checkPrevious1 = false;
        if ($previousPassword2) $checkPrevious2 = LaravelFunctionAdapter::checkHash($passwordCandidate, $previousPassword2);
        else $checkPrevious2 = false;

        if( $checkTemporary ||
            $checkCurrent ||
            $checkPrevious1 ||
            $checkPrevious2 ) {
            throw new GaelOException('Already Previously Used Password');
        }
    }

    /**
     * Check Password constraints :
     * Should have length at least 8 carachters
     * Should not have carachters different from alfa numerical
     * Should have at least a differente case (so strlower should not be equal to original string)
     */
    private function checkPasswordFormatCorrect(string $password) {
        $checkLetterAndNumber =  preg_match('/(?i)([a-z])/', $password) && preg_match('/([0-9])/', $password);
        $checkOnlyAlphaNumerical = !preg_match('/(?i)([^a-z0-9])/', $password);
        $checkNotAllSameCase = (strtolower($password) !== $password);
        $checkLength= strlen($password) >= 8;

        if ( $checkLength === false ||
                $checkLetterAndNumber === false  ||
                $checkOnlyAlphaNumerical === false  ||
                $checkNotAllSameCase === false
            ){
            throw new GaelOException('Password Contraints Failure');
        }
    }

    /**
     * Check password equality, used to check current password and new candidate password
     */
    private function checkMatchPasswords(string $pass1, string $pass2) : void {
        if( $pass1 != $pass2 ) {
            throw new GaelOException('New Passwords Do Not Match');
        }
    }

    private function checkMatchHashPasswords(string $plainTextPassword, string $hashComparator) : void {
        if( !LaravelFunctionAdapter::checkHash($plainTextPassword, $hashComparator) ) {
            throw new GaelOException('Wrong User Password');
        }
    }

}

?>
