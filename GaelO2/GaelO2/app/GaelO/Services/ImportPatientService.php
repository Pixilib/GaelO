<?php

namespace App\GaelO\Services;

use App\GaelO\Adapters\LaravelFunctionAdapter;
use App\GaelO\Exceptions\GaelOBadRequestException;
use App\GaelO\Repositories\CenterRepository;
use App\GaelO\Repositories\PatientRepository;
use App\GaelO\Repositories\StudyRepository;
use App\GaelO\Util;
use Exception;
use Illuminate\Support\Facades\Log;

class ImportPatientService
{
    /**
     * Import patient in study
     */

	public array $successList = [];
	public array $failList = [];

	public function __construct(StudyRepository $studyRepository, PatientRepository $patientRepository, CenterRepository $centerRepository) {
        $this->patientCodeLength = LaravelFunctionAdapter::getConfig('patientCodeLength');
        $this->patientRepository = $patientRepository;
        $this->centerRepository = $centerRepository;
        $this->studyRepository = $studyRepository;
	}

    public function setPatientEntities(array $patientEntities) : void {
        $this->patientEntities = $patientEntities;
    }

    public function setStudyName(String $studyName) : void {
        $this->studyName = $studyName;
    }

	public function import() {
        $studyEntity = $this->studyRepository->find($this->studyName);
        $patientEntities = $this->patientRepository->getPatientsInStudy($this->studyName);
        $this->existingPatientCode = array_map(function ($patientEntity){ return $patientEntity['code']; }, $patientEntities);

        $allCenters = $this->centerRepository->getAll();
        //Store array of all existing centers code
        $this->existingCenter = array_map( function($center) {
            return $center['code'];
        }, $allCenters);

        //For each patient from the array list
		foreach ($this->patientEntities as $patientEntity) {
            try {
                $patientEntity->registrationDate = Util::formatUSDateStringToSQLDateFormat($patientEntity->registrationDate);
                //Check condition before import
                self::checkPatientGender($patientEntity->gender);
                self::checkCorrectBirthDate($patientEntity->birthDay, $patientEntity->birthMonth, $patientEntity->birthYear);
                $this->checkNewPatient($patientEntity->code);
                $this->isCorrectPatientCodeLenght($patientEntity->code);
                $this->isExistingCenter($patientEntity->centerCode);
                $this->checkCurrentStudy($patientEntity->studyName, $this->studyName);
                $this->isCorrectPrefix($studyEntity['patient_code_prefix'],$patientEntity->code);

                //Store the patient result import process in this object
                $this->patientRepository->addPatientInStudy($patientEntity, $this->studyName);

				$this->successList[]=$patientEntity->code;

			//If conditions not met, add to the fail list with the respective error reason
            } catch(Exception $error) {
                $this->failList[$error->getMessage()][]=$patientEntity->code;
            }

		}

    }

    public static function checkCorrectBirthDate(?int $days, ?int $months, ?int $year) : void {
        if ($days !== null && ($days < 1 || $days > 31)) {
            throw new GaelOBadRequestException('Incorrect Birthdate day format');
        }
        if ($months !== null && ($months < 1 || $months > 12)) {
            throw new GaelOBadRequestException('Incorrect Birthdate month format');
        }
        if ($year !== null && ($year < 1900 || $year > 3000)) {
            throw new GaelOBadRequestException('Incorrect Birthdate year format');
        }
    }



    public static function checkPatientGender(string $sex){
        if($sex !== "M" && $sex!=="F") throw new GaelOBadRequestException("Incorrect Gender : M or F");
    }

    public function checkCurrentStudy(string $patientStudy, string $currentStudy){
        if($patientStudy !== $currentStudy) throw new GaelOBadRequestException("Patient Wrong Study");
    }

	/**
	 * Check that the importing patient is not already known in the system
	 * NB : Each patient code should be unique (across study), patient number should include a study identifier
	 * @param $patientCode
	 */
	private function checkNewPatient(int $patientCode) : void {
        if (in_array($patientCode, $this->existingPatientCode)) {
            throw new GaelOBadRequestException('Existing Patient Code');
        }
	}

	/**
	 * Check that patient number has the correct lenght
	 * @param $patientCode
	 */
	private function isCorrectPatientCodeLenght(int $patientCode) : void {
		$lenghtImport=strlen((string) $patientCode);

		if ($lenghtImport != $this->patientCodeLength) {
			throw new GaelOBadRequestException('Incorrect Patient Code Length');
		}
	}

	private function isCorrectPrefix(?int $patientCodePrefix, int $patientCode) : void {
		if (!empty($patientCodePrefix) && !$this->startsWith((string) $patientCode, $patientCodePrefix)) {
    		throw new GaelOBadRequestException('Wrong Patient Prefix');
        }
	}

	private function startsWith(string $string, string $startString) : bool {
		$len=strlen($startString);
		return (substr($string, 0, $len) === $startString);
	}

	/**
	 * Check that patient's center is one of known center in the plateform
	 * @param $patientNumCenter
	 */
	private function isExistingCenter($patientNumCenter) : void {
        if (!in_array($patientNumCenter, $this->existingCenter)) {
            throw new GaelOBadRequestException('Unknown Center');
        }
	}
}

