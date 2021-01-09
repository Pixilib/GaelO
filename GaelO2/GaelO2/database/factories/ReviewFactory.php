<?php

namespace Database\Factories;

use App\Model\Review;
use Illuminate\Database\Eloquent\Factories\Factory;

class ReviewFactory extends Factory
{

    protected $model = Review::class;

    public function definition()
    {
        return [
            'id'=>$this->faker->unique()->randomNumber,
            'study_name'=>$this->faker->word,
            'visit_id'=>$this->faker->randomNumber,
            'user_id'=>$this->faker->randomNumber,
            'review_date'=> now(),
            'validated'=>$this->faker->randomElement([true, false]),
            'adjudication'=>$this->faker->randomElement([true, false]),
            'sent_files'=>json_encode([]),
            'review_data'=>json_encode(['item1'=>'a', 'item2'=>5])
        ];
    }
}
