<?php

namespace Database\Factories;

use App\Models\Review;
use App\Models\Study;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Database\Eloquent\Factories\Factory;

class ReviewFactory extends Factory
{
    public function definition()
    {
        return [
            'id' => $this->faker->unique()->randomNumber,
            'study_name' => Study::factory()->create()->name,
            'visit_id' => Visit::factory()->create()->id,
            'user_id' => User::factory()->create()->id,
            'review_date' => now(),
            'validated' => $this->faker->randomElement([true, false]),
            'adjudication' => $this->faker->randomElement([true, false]),
            'sent_files' => json_encode([]),
            'review_data' => json_encode(['item1'=>'a', 'item2'=>5])
        ];
    }
}
