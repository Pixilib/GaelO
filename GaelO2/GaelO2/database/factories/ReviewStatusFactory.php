<?php

namespace Database\Factories;

use App\Models\Study;
use App\Models\Visit;
use Illuminate\Database\Eloquent\Factories\Factory;

class ReviewStatusFactory extends Factory
{

    public function definition()
    {

        return [
            'visit_id' => Visit::factory()->create(),
            'study_name' => Study::factory()->create(),
            'review_available'=> false,
            'review_status'=> 'Not Done',
            'review_conclusion_value'=> $this->faker->word,
            'review_conclusion_date'=> now()
        ];
    }

    public function visitId( int $visitId){

        return $this->state(function (array $attributes) use($visitId) {
            return [
                'visit_id' => $visitId,
            ];
        });
    }

    public function studyName(string $studyName){

        return $this->state(function (array $attributes) use($studyName) {
            return [
                'study_name' => $studyName,
            ];
        });

    }

    public function review_available(){

        return $this->state(function (array $attributes) {
            return [
                'review_available' => true,
            ];
        });

    }

    public function review_status(string $reviewStatus)
    {
        return $this->state(function (array $attributes) use ($reviewStatus) {
            return [
                'review_status' => $reviewStatus,
            ];
        });
    }
}
