<?php

namespace Database\Factories;

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
        $visitType = VisitType::factory()->create();

        return [
            'creator_user_id' => User::factory()->create()->id,
            'creation_date'=> now(),
            'patient_code'=> function (array $attributes) use ($visitType) {
                $studyName = $visitType->visitGroup->study->name;
                return Patient::factory()->studyName($studyName)->create()->code;
            },

            'visit_date'=> now(),
            'visit_type_id'=> $visitType->id,
            'status_done'=> $this->faker->randomElement(['Not Done','Done']),
            'reason_for_not_done'=> $this->faker->word,
            'upload_status'=> $this->faker->randomElement(['Not Done','Processing','Done']),
            'state_investigator_form'=> $this->faker->randomElement(['Not Done', 'Not Needed', 'Draft', 'Done']),
            'state_quality_control'=> $this->faker->randomElement(['Not Done', 'Not Needed', 'Wait Definitive Conclusion','Corrective Action Asked','Refused','Accepted']),
            'controller_user_id'=> null,
            'control_date'=>now(),
            'image_quality_control'=> $this->faker->randomElement([true, false]),
            'form_quality_control'=> $this->faker->randomElement([true, false]),
            'image_quality_comment'=> $this->faker->word,
            'form_quality_comment'=> $this->faker->word,
            'corrective_action_user_id'=> null, //$faker->randomNumber,
            'corrective_action_date'=>now(),
            'corrective_action_new_upload'=> $this->faker->randomElement([true, false]),
            'corrective_action_investigator_form'=> $this->faker->randomElement([true, false]),
            'corrective_action_comment'=> $this->faker->word,
            'corrective_action_applyed'=> $this->faker->randomElement([true, false]),
            'last_reminder_upload'=>now()
        ];
    }

    public function configure()
    {
        return $this->afterCreating(function (Visit $visit) {
            $studyName = $visit->visitTypeOnly->visitGroup->study->name;
            ReviewStatus::factory()->studyName($studyName)->visitId($visit->id)->create();
        });
    }
}
