<?php
/**
 Copyright (C) 2018 KANOUN Salim
 This program is free software; you can redistribute it and/or modify
 it under the terms of the Affero GNU General Public v.3 License as published by
 the Free Software Foundation;
 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 Affero GNU General Public Public for more details.
 You should have received a copy of the Affero GNU General Public Public along
 with this program; if not, write to the Free Software Foundation, Inc.,
 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA
 */

/**
 * Access Patient data
 */

class Patient{
    
    public $patient;
    private $linkpdo;
    public $patientCode;
    public $patientFirstName;
    public $patientLastName;
    public $patientGender;
    public $patientBirthDateUS;
    public $patientBirthDate;
    public $patientRegistrationDate;
    public $patientInvestigatorName;
    public $patientCenter;
    public $patientStudy;
    public $patientWithdraw;
    public $patientWithdrawReason;
    public $patientWithdrawDate;
    public $patientWithdrawDateString;

    const PATIENT_WITHDRAW="withdraw";
    
    function __construct($patientCode, PDO $linkpdo) {
        $this->patient=$patientCode;
        $this->linkpdo= $linkpdo;
        
        $queryPatientsData = $this->linkpdo->prepare('SELECT * FROM patients WHERE code = :patient');
        $queryPatientsData->execute(array('patient' => $this->patient));
        $dataPatient = $queryPatientsData->fetch(PDO::FETCH_ASSOC);
        
        if(empty($dataPatient)){
            throw new Exception("Non Existing Patient");
        }
        
        $this->patientCode=$dataPatient['code'];
        $this->patientFirstName=$dataPatient['first_name'];
        $this->patientLastName=$dataPatient['last_name'];
        $this->patientGender=$dataPatient['gender'];
        $this->patientBirthDateUS=sprintf("%02d", $dataPatient['birth_month']).'-'.sprintf("%02d", $dataPatient['birth_day']).'-'.$dataPatient['birth_year'];
        $this->patientBirthDate=sprintf("%02d", $dataPatient['birth_year']).'-'.sprintf("%02d", $dataPatient['birth_month']).'-'.$dataPatient['birth_day'];
        $this->patientRegistrationDate=$dataPatient['registration_date'];
        $this->patientInvestigatorName=$dataPatient['investigator_name'];
        $this->patientCenter=$dataPatient['center'];
        $this->patientStudy=$dataPatient['study'];
        $this->patientWithdraw=$dataPatient['withdraw'];
        $this->patientWithdrawReason=$dataPatient['withdraw_reason'];
        $this->patientWithdrawDate=new DateTimeImmutable($dataPatient['withdraw_date']);
        $this->patientWithdrawDateString=$dataPatient['withdraw_date'];
        
    }

    public function getImmutableRegistrationDate(){
        return new DateTimeImmutable($this->patientRegistrationDate);
    }
    
    /**
     * get Patient's Center Object
     * @return Center
     */
    public function getPatientCenter(){
        $centerObject=new Center($this->linkpdo, $this->patientCenter);
        return $centerObject;
    }
    
    /**
     * Update withdraw status
     * @param bool $withdraw
     * @param string $withdrawDate
     * @param string $withdrawReason
     */
    public function changeWithdrawStatus(bool $withdraw, string $withdrawDate, string $withdrawReason){
        
        $insertion = $this->linkpdo->prepare('UPDATE patients
                                            SET withdraw = :withdraw,
                                            withdraw_date = :withdraw_date,
                                            withdraw_reason = :withdraw_reason
                                            WHERE patients.code = :patient');
        
        $insertion->execute(array('withdraw'=>intval($withdraw),
            'withdraw_date' => $withdrawDate,
            'withdraw_reason' => $withdrawReason,
            'patient' => $this->patient));
        
    }
    
    /**
     * Update patient data
     * @param $initials
     * @param $gender
     * @param $birthDate
     * @param $registrationDate
     * @param $investigator
     * @param $centerCode
     */
    public function editPatientDetails($initials, $gender, $birthDate, $registrationDate, $investigator, $centerCode){
            $insertion = $this->linkpdo->prepare("UPDATE patients
                                            SET first_name = :firstName,
                                            last_name = :lastName,
                                            gender = :gender,
                                            birth_year=:year,
                                            birth_month=:month,
                                            birth_day=:day,
                                            registration_date=:registrationDate,
                                            investigator_name=:investigator,
                                            center=:centerCode
                                            WHERE patients.code = :patient");
            
            $insertion->execute(array('firstName'=>$initials[1],
                'lastName'=>$initials[0],
                'gender'=>strtoupper($gender[0]),
                'year'=>substr($birthDate, 0, 4),
                'month'=>substr($birthDate, 5, 2),
                'day'=>substr($birthDate, 8, 2),
                'registrationDate' => $registrationDate,
                'investigator' => $investigator,
                'centerCode'=>$centerCode,
                'patient' => $this->patient));
       
        
    }

    /**
     * Return visit Manage to manage patient's visit status
     */
    public function getVisitManager(Visit_Group $visitGroupObject) {
        //Look if specific patient visit manager exists for this study
        $specificObjectFile=$_SERVER["DOCUMENT_ROOT"]."/data/form/Poo/".$this->patientStudy."_Patient_Visit_Manager.php";
            
        if(is_file($specificObjectFile)){
            require($specificObjectFile);
            $objectName=$this->patientStudy."_Patient_Visit_Manager.php";
            return new $objectName($this);
        }else{
            return new Patient_Visit_Manager($this, $visitGroupObject, $this->linkpdo);
        }

    }

    public function getPatientStudy(){
        return new Study($this->patientStudy, $this->linkpdo);
    }
      
}