<?php

namespace App\GaelO\Services;

use App\GaelO\Constants\Enums\InclusionStatusEnum;
use App\GaelO\Entities\StudyEntity;
use App\GaelO\Exceptions\GaelOBadRequestException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Interfaces\Repositories\CenterRepositoryInterface;
use App\GaelO\Interfaces\Repositories\PatientRepositoryInterface;
use DateTime;
use Exception;
use Throwable;

class ImportPatientService
{
    private int $patientCodeLength;
    private array $existingPatientCodes;
    private array $existingCenter;
    private array $patientEntities;
    private StudyEntity $studyEntity;
    private PatientRepositoryInterface $patientRepository;
    private CenterRepositoryInterface $centerRepository;

    /**
     * Import patient in study
     */

    public array $successList = [];
    public array $failList = [];

    public function __construct(PatientRepositoryInterface $patientRepository, CenterRepositoryInterface $centerRepository)
    {
        $this->patientRepository = $patientRepository;
        $this->centerRepository = $centerRepository;
    }

    public function setPatientEntities(array $patientEntities): void
    {
        $this->patientEntities = $patientEntities;
    }

    public function setStudyEntity(StudyEntity $studyEntity): void
    {
        if ($studyEntity->isAncillaryStudy()) {
            throw new GaelOForbiddenException("Import Patient not allowed for ancillaries studies");
        }
        $this->studyEntity = $studyEntity;
    }

    public function import()
    {

        $this->patientCodeLength = $this->studyEntity->patientCodeLength;
        $this->existingPatientCodes = $this->patientRepository->getAllPatientsCodesInStudy($this->studyEntity->name);

        $allCenters = $this->centerRepository->getAll();
        //Store array of all existing centers code
        $this->existingCenter = array_map(function ($center) {
            return $center['code'];
        }, $allCenters);

        //For each patient from the array list
        foreach ($this->patientEntities as $patientEntity) {
            try {
                //Check condition before import
                self::checkPatientGender($patientEntity['gender']);
                self::checkCorrectBirthDate($patientEntity['birthDay'], $patientEntity['birthMonth'], $patientEntity['birthYear']);
                if ($patientEntity['inclusionStatus']  === InclusionStatusEnum::INCLUDED->value && $patientEntity['registrationDate'] == null) {
                    throw new GaelOBadRequestException('Registration Date Missing or Invalid');
                }
                if ($patientEntity['inclusionStatus']  !== null) {
                    $this->isRegistrationDateValid($patientEntity['registrationDate']);
                }
                if (!array_key_exists('metadata', $patientEntity)) {
                    $patientEntity['metadata'] = null;
                }else{
                    if(!array_key_exists('tags', $patientEntity) || !is_array($patientEntity['metadata']['tags'])){
                        throw new GaelOBadRequestException('Tags key mandatory for metadata with array structure');
                    };
                }
                $this->checkNewPatient($patientEntity['code']);
                $this->isCorrectPatientCode($patientEntity['code']);
                $this->isExistingCenter($patientEntity['centerCode']);
                $this->checkCurrentStudy($patientEntity['studyName'], $this->studyEntity->name);

                //Store the patient result import process in this object
                $this->patientRepository->addPatientInStudy(
                    $this->studyEntity->code . $patientEntity['code'],
                    $patientEntity['code'],
                    $patientEntity['lastname'],
                    $patientEntity['firstname'],
                    $patientEntity['gender'],
                    $patientEntity['birthDay'],
                    $patientEntity['birthMonth'],
                    $patientEntity['birthYear'],
                    $patientEntity['registrationDate'],
                    $patientEntity['investigatorName'],
                    $patientEntity['centerCode'],
                    $patientEntity['inclusionStatus'],
                    $this->studyEntity->name,
                    $patientEntity['metadata']
                );

                $this->successList[] = $patientEntity['code'];

                //If conditions not met, add to the fail list with the respective error reason
            } catch (Exception $error) {
                $this->failList[$error->getMessage()][] = $patientEntity['code'];
            }
        }
    }

    public static function checkCorrectBirthDate(?int $days, ?int $months, ?int $year): void
    {
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



    public static function checkPatientGender(?string $sex)
    {
        if ($sex !== "M" && $sex !== "F" && $sex != null) throw new GaelOBadRequestException("Incorrect Gender : M or F");
    }

    public function checkCurrentStudy(string $patientStudy, string $currentStudy)
    {
        if ($patientStudy !== $currentStudy) throw new GaelOBadRequestException("Patient Wrong Study");
    }

    /**
     * Check that the importing patient is not already known in the system
     * NB : Each patient code should be unique (across study), patient code should include a study identifier
     * @param $patientId
     */
    private function checkNewPatient(string $patientCode): void
    {
        if (in_array($patientCode, $this->existingPatientCodes)) {
            throw new GaelOBadRequestException('Existing Patient Code');
        }
    }

    /**
     * Check that patient code has the correct lenght
     * @param $patientId
     */
    private function isCorrectPatientCode(string $patientCode): void
    {

        if (!is_numeric($patientCode)) {
            throw new GaelOBadRequestException('Patient Code accept only numbers');
        }

        $lenghtImport = strlen((string) $patientCode);

        if ($lenghtImport != $this->patientCodeLength) {
            throw new GaelOBadRequestException('Incorrect Patient Code Length');
        }
    }

    /**
     * Check that patient's center is one of known center in the plateform
     * @param $patientNumCenter
     */
    private function isExistingCenter(?int $patientNumCenter): void
    {
        if ($patientNumCenter === null || !in_array($patientNumCenter, $this->existingCenter)) {
            throw new GaelOBadRequestException('Unknown Center');
        }
    }

    private function isRegistrationDateValid(?string $registrationDate): void
    {
        try {
            new DateTime($registrationDate);
        } catch (Throwable) {
            throw new GaelOBadRequestException('Registration Date Missing or Invalid');
        }
    }
}
