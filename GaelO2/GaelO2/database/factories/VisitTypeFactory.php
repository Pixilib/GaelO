<?php

namespace Database\Factories;

use App\Models\VisitGroup;
use Illuminate\Database\Eloquent\Factories\Factory;

class VisitTypeFactory extends Factory
{

    public function definition()
    {
        return [
            'visit_group_id'=>  VisitGroup::factory()->create()->id,
            'name'=>  $this->faker->unique()->word,
            'order'=>  $this->faker->unique()->randomNumber,
            'local_form_needed'=>false,
            'qc_needed'=>false,
            'review_needed'=>false,
            'optional'=>false,
            'limit_low_days'=>  $this->faker->randomNumber,
            'limit_up_days'=> $this->faker->randomNumber,
            'anon_profile'=> 'Default',
            'dicom_constraints' => []
        ];
    }

    public function name(string $name){

        return $this->state(function (array $attributes) use ($name) {
            return [
                'name' => $name
            ];
        });
    }

    public function visitGroupId(int $visitGroupId){

        return $this->state(function (array $attributes) use ($visitGroupId) {
            return [
                'visit_group_id' => $visitGroupId
            ];
        });
    }

    public function localFormNeeded(){

        return $this->state(function (array $attributes) {
            return [
                'local_form_needed' => true
            ];
        });
    }

    public function qcNeeded(){

        return $this->state(function (array $attributes) {
            return [
                'qc_needed' => true
            ];
        });
    }

    public function reviewNeeded(){

        return $this->state(function (array $attributes) {
            return [
                'review_needed' => true
            ];
        });
    }

    public function optional(){

        return $this->state(function (array $attributes) {
            return [
                'optional' => true
            ];
        });
    }

}
