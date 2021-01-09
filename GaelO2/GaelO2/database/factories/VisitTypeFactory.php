<?php

namespace Database\Factories;

use App\Model\VisitType;
use Illuminate\Database\Eloquent\Factories\Factory;

class VisitTypeFactory extends Factory
{

    protected $model = VisitType::class;

    public function definition()
    {
        return [
            'visit_group_id'=>  $this->faker->unique()->randomNumber,
            'name'=>  $this->faker->unique()->word,
            'order'=>  $this->faker->unique()->randomNumber,
            'local_form_needed'=>$this->faker->randomElement([true, false]),
            'qc_needed'=>$this->faker->randomElement([true, false]),
            'review_needed'=>$this->faker->randomElement([true, false]),
            'optional'=>$this->faker->randomElement([true, false]),
            'limit_low_days'=>  $this->faker->randomNumber,
            'limit_up_days'=> $this->faker->randomNumber,
            'anon_profile'=> $this->faker->randomElement( ['Default', 'Full'])
        ];
    }
}
