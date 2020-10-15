<?php

namespace App\GaelO\UseCases\CreatePatient;

use App\GaelO\Adapters\LaravelFunctionAdapter;
use App\GaelO\Constants\Constants;
use App\GaelO\Interfaces\PersistenceInterface;

use App\GaelO\UseCases\CreatePatient\CreatePatientRequest;
use App\GaelO\UseCases\CreatePatient\CreatePatientResponse;
use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Services\MailServices;
use App\GaelO\Services\TrackerService;
use App\GaelO\Util;
use Illuminate\Support\Facades\Log;

class CreatePatient {

    /**
     * Dependency injection that will be provided by the Dependency Injection Container
     * Persistence Interfate => Will be a instance of Patient Repository (defined by PatientRepositoryProvider)
     * Tracker Service to be able to write in the Tracker
     * Mail Service to be able to send email
     */
    public function __construct(PersistenceInterface $persistenceInterface, TrackerService $trackerService, MailServices $mailService){
        $this->persistenceInterface = $persistenceInterface;
        $this->trackerService = $trackerService;
        $this->mailService = $mailService;
     }

     public function execute(CreatePatientRequest $createPatientRequest, CreatePatientResponse $createPatientResponse) : void
    {
        $data = get_object_vars($createPatientRequest);

        /*$importPatient=new Import_Patient($_POST['json'], $_SESSION['study'], $linkpdo);
		$importPatient -> readJson();

		//Build the Import report to send it by email
		$htmlReport=$importPatient->getHTMLImportAnswer();
        $textReport=$importPatient->getTextImportAnswer();*/

        //Check form completion
        try {

            //In no Exception thrown by checks methods, data are ok to be written in db
            $createdPatientEntity = $this->persistenceInterface->createPatient($createPatientRequest->code,
            $createPatientRequest->firstname,
            $createPatientRequest->lastname,
            $createPatientRequest->gender,
            $createPatientRequest->birthDay,
            $createPatientRequest->birthMonth,
            $createPatientRequest->birthYear,
            $createPatientRequest->registrationDate,
            $createPatientRequest->investigatorName,
            $createPatientRequest->centerCode,
            $createPatientRequest->studyName,
            $createPatientRequest->withdraw,
            $createPatientRequest->withdrawReason,
            $createPatientRequest->withdrawDate);

           //save patient creation in tracker
            $detailsTracker = [
                'success'=> $createdPatientEntity['code']
            ];
            //Save action in Tracker
            $actionDetails = [ /*
                'createdCenterCode'=>$code,
                'createdCenterName'=>$name,
                'createdCenterCountryCode'=>$countryCode */
            ];

            $this->trackerService->writeAction($createPatientRequest->currentUserCode, Constants::TRACKER_ROLE_USER, null, null, Constants::TRACKER_IMPORT_PATIENT, $actionDetails);

        } catch (GaelOException $e) {
            //If Exception thrown by our buisness logic, handle it
            $createPatientResponse->status = 400;
            $createPatientResponse->statusText = $e->getMessage();
        }catch (\Exception $e) {
            //If execption thrown by framework, let the framework handle it
            throw $e;
        }
    }

    private function checkFormComplete(array $data) : void {
        if(!isset($data['patientname'])
        || !isset($data['job'])
        || !isset($data['email'])
        || !is_numeric($data['centerCode'])
        || !isset($data['administrator']) ) {
            throw new GaelOException('Form incomplete');
        }
    }

    private function checkEmailValid(array $data) : void {
        if (!preg_match('/^[a-z0-9\-_.]+@[a-z0-9\-_.]+\.[a-z]{2,4}$/i', $data['email'])) {
            throw new GaelOException('Not a valid email format');
        }

    }

    private function checkPhoneCorrect(?string $phone) : void {
        //If contains non number caracters throw error
        if ($phone != null && preg_match('/[^0-9]/', $phone)) {
            throw new GaelOException('Not a valid email phone number');
        }
    }

    private function checkPatientUnique(array $data) : void {
        if($this->persistenceInterface->isExistingEmail($data['email'])) throw new GaelOException('Already Existing Patientname');
        if($this->persistenceInterface->isExistingPatientname($data['patientname'])) throw new GaelOException('Already used Email');
    }
}
