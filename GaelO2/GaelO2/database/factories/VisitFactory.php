<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Visit;
use Faker\Generator as Faker;

$factory->define(Visit::class, function (Faker $faker) {
    return [
        'creator_user_id' =>$faker->unique()->randomNumber,
        'creation_date'=>now(),
        'patient_code'=>$faker->unique()->randomNumber,
        'acquisition_date'=>now(),
        'visit_type_id'=>$faker->unique()->randomNumber,
        'status_done'=>$faker->randomElement(['Not Done','Done']),
        'reason_for_not_done'=>$faker->word,
        'upload_status'=>$faker->randomElement(['Not Done','Processing','Done']),
        'state_investigator_form'=>$faker->randomElement(['Not Done', 'Not Needed', 'Draft', 'Done']),
        'state_quality_control'=>$faker->randomElement(['Not Done', 'Not Needed', 'Draft', 'Done']),
        'state_quality_control'=>$faker->randomElement(['Not Done', 'Not Needed', 'Wait Definitive Conclusion','Corrective Action Asked','Refused','Accepted']),
        'controller_user_id'=>$faker->randomNumber,
        'control_date'=>now(),
        'image_quality_control'=>$faker->randomElement([true, false]),
        'form_quality_control'=>$faker->randomElement([true, false]),
        'image_quality_comment'=>$faker->word,
        'form_quality_comment'=>$faker->word,
        'corrective_action_user_id'=>$faker->randomNumber,
        'corrective_action_date'=>now(),
        'corrective_action_new_upload'=>$faker->randomElement([true, false]),
        'corrective_action_investigator_form'=>$faker->randomElement([true, false]),
        'corrective_action_other'=>$faker->word,
        'corrective_action_decision'=>$faker->randomElement([true, false]),
        'review_available'=>$faker->randomElement([true, false]),
        'review_status'=>$faker->randomElement(['Not Done', 'Not Needed', 'Ongoing','Wait Adjudication','Done']),
        'review_conclusion_value'=>$faker->word,
        'review_conclusion_date'=>now(),
        'last_reminder_upload'=>now()
    ];
});
