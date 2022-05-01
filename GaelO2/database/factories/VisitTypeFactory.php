<?php

namespace Database\Factories;

use App\Models\VisitGroup;
use Illuminate\Database\Eloquent\Factories\Factory;

class VisitTypeFactory extends Factory
{

    public function definition()
    {
        return [
            'visit_group_id' => function () {
                return VisitGroup::factory()->create()->id;
            },
            'name' =>  $this->faker->unique()->word,
            'order' =>  $this->faker->unique()->randomNumber,
            'local_form_needed' => false,
            'qc_probability' =>  $this->faker->numberBetween(0, 100),
            'review_probability' => $this->faker->numberBetween(0, 100),
            'optional' => false,
            'limit_low_days' =>  $this->faker->randomNumber,
            'limit_up_days' => $this->faker->randomNumber,
            'anon_profile' => 'Default',
            'dicom_constraints' => []
        ];
    }

    public function name(string $name)
    {
        return $this->state(function (array $attributes) use ($name) {
            return [
                'name' => $name
            ];
        });
    }

    public function order(int $order)
    {
        return $this->state(function (array $attributes) use ($order) {
            return [
                'order' => $order
            ];
        });
    }

    public function visitGroupId(int $visitGroupId)
    {
        return $this->state(function (array $attributes) use ($visitGroupId) {
            return [
                'visit_group_id' => $visitGroupId
            ];
        });
    }

    public function localFormNeeded()
    {
        return $this->state(function (array $attributes) {
            return [
                'local_form_needed' => true
            ];
        });
    }

    public function qcProbability(int $probability = 100)
    {
        return $this->state(function (array $attributes) use ($probability) {
            return [
                'qc_probability' => $probability
            ];
        });
    }

    public function reviewProbability(int $probability = 100)
    {
        return $this->state(function (array $attributes) use ($probability) {
            return [
                'review_probability' => $probability
            ];
        });
    }

    public function optional()
    {
        return $this->state(function (array $attributes) {
            return [
                'optional' => true
            ];
        });
    }

    public function limitLowDays(int $limitLowDays)
    {
        return $this->state(function (array $attributes) use ($limitLowDays) {
            return [
                'limit_low_days' => $limitLowDays
            ];
        });
    }

    public function limitUpDays(int $limitUpDays)
    {
        return $this->state(function (array $attributes) use ($limitUpDays) {
            return [
                'limit_up_days' => $limitUpDays
            ];
        });
    }
}
