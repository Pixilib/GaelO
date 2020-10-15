<?php

namespace App\GaelO\UseCases\GetPatient;

class PatientEntity {
    public int $code;
    public string $first_name;
    public string $last_name;
    public ?string $gender; //, ['M', 'F'])->nullable(true)->default(null);
    public int $birth_day;
    public int $birth_month (null);
    public int $birth_year (null);
    public date $registration_date')->nullable(false);
    public string $investigator_name (null);
    $table->unsignedInteger('center_code (null);
    public string $study_name (null);
    $table->boolean('withdraw (false)->nullable(false);
    public string $withdraw_reason (null);
    $table->date('withdraw_date (null);

    public static function fillFromDBReponseArray(array $array){
        $patientEntity  = new PatientEntity();
        $patientEntity->id = $array['id'];
        $patientEntity->lastname = $array['lastname'];
        $patientEntity->firstname = $array['firstname'];
        $patientEntity->patientname = $array['patientname'];
        $patientEntity->email = $array['email'];
        $patientEntity->phone = $array['phone'];
        $patientEntity->lastPasswordUpdate = $array['last_password_update'];
        $patientEntity->status = $array['status'];
        $patientEntity->attempts = $array['attempts'];
        $patientEntity->administrator = $array['administrator'];
        $patientEntity->centerCode = $array['center_code'];
        $patientEntity->job = $array['job'];
        $patientEntity->orthancAddress = $array['orthanc_address'];
        $patientEntity->orthancLogin = $array['orthanc_login'];
        $patientEntity->orthancPassword = $array['orthanc_password'];
        $patientEntity->deletedAt = $array['deleted_at'];
        $patientEntity->lastConnection = $array['last_connection'];
        return $patientEntity;
    }

}
