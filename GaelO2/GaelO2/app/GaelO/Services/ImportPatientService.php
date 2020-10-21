<?php

namespace App\GaelO\Services;

use App\GaelO\Adapters\LaravelFunctionAdapter;
use App\GaelO\Repositories\CenterRepository;
use App\GaelO\Repositories\PatientRepository;
use App\GaelO\Repositories\StudyRepository;

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
        $this->studyEntity = $this->studyRepository->getStudy($this->studyName);
        $this->existingPatientCode = $this->patientRepository->getPatientsInStudy($this->studyName);
        $this->existingCenter = $this->centerRepository->getExistingCenter();

        //For each patient from the array list
		foreach ($this->patientEntities as $patientEntity) {
            try {
                $patientEntity->registrationDate = $this->formatRegistrationDate($patientEntity->registrationDate);
                //Check condition before import
                $this->checkCorrectDate($patientEntity->birthDay, $patientEntity->birthMonth, $patientEntity->birthYear);
                $this->checkNewPatient($patientEntity->code);
                $this->isCorrectPatientCodeLenght($patientEntity->code);
                $this->isExistingCenter($patientEntity->centerCode);
                $this->isCorrectPrefix($patientEntity->code);

                //Store the patient result import process in this object
                $this->patientRepository->addPatientInStudy($patientEntity, $this->studyName);

				$this->successList[]=$patientEntity->code;

			//If conditions not met, add to the fail list with the respective error reason
            } catch(\Exception $error) {
                $this->failList[$error->getMessage()][]=$patientEntity->code;
            }

		}

    }

    private function checkCorrectDate(?int $days, ?int $months, ?int $year) : void {
        if ($days !== null && ($days < 1 || $days > 31)) {
            throw new \Exception('Incorrect Birthdate day format');
        }
        if ($months !== null && ($months < 1 || $months > 12)) {
            throw new \Exception('Incorrect Birthdate month format');
        }
        if ($year !== null && ($year < 1900 || $year > 3000)) {
            throw new \Exception('Incorrect Birthdate year format');
        }
    }

	/**
	 * Format registration date according to plateform preference (french or US format)
	 * @param string registrationDate
	 * @return String
	 */
	private function formatRegistrationDate(string $registrationDate) : String {
		$dateNbArray=explode('/', $registrationDate);
        $registrationDay=intval($dateNbArray[1]);
        $registrationMonth=intval($dateNbArray[0]);
        $registrationYear=intval($dateNbArray[2]);

		if ($registrationDay == 0 || $registrationMonth == 0 || $registrationYear == 0) {
			throw new \Exception('Wrong Registration Date');
		}

		try {
			$dateResult=new \DateTime($registrationYear.'-'.$registrationMonth.'-'.$registrationDay);
            return $dateResult->format('Y-m-d');
        }catch (\Exception $e) {
			throw new \Exception('Wrong Registration Date');
		}

	}

	/**
	 * Check that the importing patient is not already known in the system
	 * NB : Each patient code should be unique (across study), patient number should include a study identifier
	 * @param $patientCode
	 */
	private function checkNewPatient(int $patientCode) : void {
        if (in_array($patientCode, $this->existingPatientCode)) {
            throw new \Exception('Existing Patient Code');
        }
	}

	/**
	 * Check that patient number has the correct lenght
	 * @param $patientCode
	 */
	private function isCorrectPatientCodeLenght(int $patientCode) : void {
		$lenghtImport=strlen((string) $patientCode);

		if ($lenghtImport != $this->patientCodeLength) {
			throw new \Exception('Incorrect Patient Code Length');
		}
	}

	private function isCorrectPrefix(int $patientCode) : void {
		if (!empty($this->studyEntity->patientCodePrefix) && !$this->startsWith((string) $patientCode, $this->studyEntity->patientCodePrefix)) {
    		throw new \Exception('Wrong Patient Prefix');
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
            throw new \Exception('Unknown Center');
        }
	}
}

