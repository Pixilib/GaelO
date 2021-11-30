<?php

namespace App\GaelO\Services;

use App\GaelO\Constants\SettingsConstants;
use App\GaelO\Exceptions\GaelOBadRequestException;
use App\GaelO\Interfaces\Adapters\FrameworkInterface;
use App\GaelO\Interfaces\Repositories\CenterRepositoryInterface;
use App\GaelO\Interfaces\Repositories\PatientRepositoryInterface;
use App\GaelO\Interfaces\Repositories\StudyRepositoryInterface;
use App\GaelO\Util;
use Exception;
use Hamcrest\Type\IsNumeric;
use Illuminate\Support\Facades\Log;

class ImportPatientService
{

    private int $patientCodeLength;
    private array $existingPatientNumber;
    private PatientRepositoryInterface $patientRepository;
    private CenterRepositoryInterface $centerRepository;
    private StudyRepositoryInterface $studyRepository;
    /**
     * Import patient in study
     */

	public array $successList = [];
	public array $failList = [];

	public function __construct(StudyRepositoryInterface $studyRepository, PatientRepositoryInterface $patientRepository, CenterRepositoryInterface $centerRepository) {
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
        $this->patientCodeLength = $studyEntity['patient_code_length'];
        $this->existingPatientNumber = $this->patientRepository->getAllPatientsNumberInStudy($studyEntity['name']);

        $allCenters = $this->centerRepository->getAll();
        //Store array of all existing centers code
        $this->existingCenter = array_map( function($center) {
            return $center['code'];
        }, $allCenters);

        //For each patient from the array list
		foreach ($this->patientEntities as $patientEntity) {
            try {
                if($patientEntity['registrationDate']) $patientEntity['registrationDate'] = Util::formatUSDateStringToSQLDateFormat($patientEntity['registrationDate']);
                //Check condition before import
                self::checkPatientGender($patientEntity['gender']);
                self::checkCorrectBirthDate($patientEntity['birthDay'], $patientEntity['birthMonth'], $patientEntity['birthYear']);
                $this->checkNewPatient($patientEntity['code']);
                $this->isCorrectPatientNumber($patientEntity['code']);
                $this->isExistingCenter($patientEntity['centerCode']);
                $this->checkCurrentStudy($patientEntity['studyName'], $this->studyName);

                //Store the patient result import process in this object
                $this->patientRepository->addPatientInStudy($studyEntity['code'].$patientEntity['code'], $patientEntity['code'],
                    $patientEntity['lastname'], $patientEntity['firstname'], $patientEntity['gender'],
                    $patientEntity['birthDay'], $patientEntity['birthMonth'], $patientEntity['birthYear'],$patientEntity['registrationDate'],$patientEntity['investigatorName'], $patientEntity['centerCode'], $this->studyName
                );

				$this->successList[]=$patientEntity['code'];

			//If conditions not met, add to the fail list with the respective error reason
            } catch(Exception $error) {
                $this->failList[$error->getMessage()][]=$patientEntity['code'];
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



    public static function checkPatientGender(?string $sex){
        if($sex !== "M" && $sex!=="F" && $sex != null) throw new GaelOBadRequestException("Incorrect Gender : M or F");
    }

    public function checkCurrentStudy(string $patientStudy, string $currentStudy){
        if($patientStudy !== $currentStudy) throw new GaelOBadRequestException("Patient Wrong Study");
    }

	/**
	 * Check that the importing patient is not already known in the system
	 * NB : Each patient code should be unique (across study), patient code should include a study identifier
	 * @param $patientId
	 */
	private function checkNewPatient(string $patientNumber) : void {
        if (in_array($patientNumber, $this->existingPatientNumber)) {
            throw new GaelOBadRequestException('Existing Patient Code');
        }
	}

	/**
	 * Check that patient code has the correct lenght
	 * @param $patientId
	 */
	private function isCorrectPatientNumber(string $patientNumber) : void {

        if ( !is_numeric($patientNumber) ) {
			throw new GaelOBadRequestException('Patient Code accept only numbers');
		}

		$lenghtImport=strlen((string) $patientNumber);

		if ($lenghtImport != $this->patientCodeLength) {
			throw new GaelOBadRequestException('Incorrect Patient Code Length');
		}
	}

	/**
	 * Check that patient's center is one of known center in the plateform
	 * @param $patientNumCenter
	 */
	private function isExistingCenter(?int $patientNumCenter) : void {
        if ($patientNumCenter === null ||!in_array($patientNumCenter, $this->existingCenter)) {
            throw new GaelOBadRequestException('Unknown Center');
        }
	}
}

