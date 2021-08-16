<?php

namespace Database\Factories;

use App\GaelO\Util;
use App\Models\Patient;
use App\Models\ReviewStatus;
use App\Models\User;
use App\Models\Visit;
use App\Models\VisitType;
use Illuminate\Database\Eloquent\Factories\Factory;

class VisitFactory extends Factory
{

    public function definition()
    {

        return [
            'creator_user_id' => User::factory()->create()->id,
            'creation_date'=> Util::now(),
            'patient_code'=> Patient::factory()->create()->code,
            'visit_date'=> Util::now(),
            'visit_type_id'=> VisitType::factory()->create()->id,
            'status_done'=> 'Done',
            'reason_for_not_done'=> $this->faker->word,
            'upload_status'=> 'Not Done',
            'state_investigator_form'=> 'Not Done',
            'state_quality_control'=> 'Not Done',
            'controller_user_id'=> null,
            'control_date'=>Util::now(),
            'image_quality_control'=> $this->faker->randomElement([true, false]),
            'form_quality_control'=> $this->faker->randomElement([true, false]),
            'image_quality_comment'=> $this->faker->word,
            'form_quality_comment'=> $this->faker->word,
            'corrective_action_user_id'=> null,
            'corrective_action_date'=>Util::now(),
            'corrective_action_new_upload'=> $this->faker->randomElement([true, false]),
            'corrective_action_investigator_form'=> $this->faker->randomElement([true, false]),
            'corrective_action_comment'=> $this->faker->word,
            'corrective_action_applied'=> $this->faker->randomElement([true, false]),
            'last_reminder_upload'=>Util::now()
        ];
    }

    public function creatorUserId(int $userId){

        return $this->state(function (array $attributes) use ($userId) {
            return [
                'creator_user_id' => $userId
            ];
        });
    }

    public function patientCode(int $patientCode){

        return $this->state(function (array $attributes) use ($patientCode) {
            return [
                'patient_code' => $patientCode
            ];
        });
    }

    public function visitTypeId(int $visitTypeId){

        return $this->state(function (array $attributes) use ($visitTypeId) {
            return [
                'visit_type_id' => $visitTypeId
            ];
        });
    }

    public function stateQualityControl(string $stateQualityControl){

        return $this->state(function (array $attributes) use ($stateQualityControl) {
            return [
                'state_quality_control' => $stateQualityControl
            ];
        });
    }

    public function stateInvestigatorForm(string $stateInvestigatorForm){

        return $this->state(function (array $attributes) use ($stateInvestigatorForm) {
            return [
                'state_investigator_form' => $stateInvestigatorForm
            ];
        });
    }

    public function notDone(){

        return $this->state(function (array $attributes) {
            return [
                'status_done' => 'Not Done'
            ];
        });
    }

    public function done(){

        return $this->state(function (array $attributes) {
            return [
                'status_done' => 'Done'
            ];
        });
    }

    public function uploadDone(){

        return $this->state(function (array $attributes) {
            return [
                'upload_status' => 'Done'
            ];
        });
    }

    public function configure()
    {
        return $this->afterMaking(function (Visit $visit) {
            /*
            $visitType = VisitType::factory()->create();
            $studyName = $visitType->visitGroup->study->name;
            $patient = Patient::factory()->studyName($studyName)->create();
            //Assign visit to a common study for VisitType and PatientCode
            $visit->patient_code = ($patient->code);
            $visit->visit_type_id = ($visitType->id);
            */
        })->afterCreating(function (Visit $visit) {
            //SK FAUDRAIT CREER LE REVIEW STATUS MAIS PB CONFLIT FAKER
            //$studyName = $visit->visitTypeOnly->visitGroup->study->name;
            //ReviewStatus::factory()->studyName($studyName)->visitId($visit->id)->create();
        });
    }
}
