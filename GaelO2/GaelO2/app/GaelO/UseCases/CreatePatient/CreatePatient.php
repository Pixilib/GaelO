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
use App\ImportPatient;
use Illuminate\Support\Facades\Log;

class CreatePatient {

    public function __construct(PersistenceInterface $persistenceInterface, TrackerService $trackerService, MailServices $mailService){
        $this->persistenceInterface = $persistenceInterface;
        $this->trackerService = $trackerService;
        $this->mailService = $mailService;
     }

     public function execute(CreatePatientRequest $createPatientRequest, CreatePatientResponse $createPatientResponse) : void
    {
        $data = get_object_vars($createPatientRequest);

        $importPatient=new ImportPatient($_POST['json'], $_SESSION['study']);
		$importPatient -> readJson();

		//Build the Import report to send it by email
		$htmlReport=$importPatient->getHTMLImportAnswer();
        $textReport=$importPatient->getTextImportAnswer();

        //Check form completion
        try {

            $this->checkFormComplete($importPatient);
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
        if(!isset($data['code'])
        || !isset($data['registrationDate'])
        || !isset($data['withdraw'])) {
            throw new GaelOException('Form incomplete');
        }
    }
}
