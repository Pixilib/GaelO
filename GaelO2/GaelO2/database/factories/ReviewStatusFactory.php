<?php

namespace Database\Factories;

use App\Model\ReviewStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

class ReviewStatusFactory extends Factory
{

    protected $model = ReviewStatus::class;

    public function definition()
    {
        return [
            'visit_id'=>$this->faker->randomNumber,
            'study_name'=>$this->faker->word,
            'review_available'=>$this->faker->randomElement([true, false]),
            'review_status'=> $this->faker->randomElement(['Not Done', 'Not Needed', 'Ongoing','Wait Adjudication','Done']),
            'review_conclusion_value'=>$this->faker->word,
            'review_conclusion_date'=>now()
        ];
    }
}
