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
            'study_name' => Study::factory()->create()->name,
            'visit_id' => Visit::factory()->create()->id,
            'user_id' => User::factory()->create()->id,
            'review_date' => now(),
            'validated' => false,
            'local' => true,
            'adjudication' => false,
            'sent_files' => json_encode([]),
            'review_data' => json_encode(['item1'=>'a', 'item2'=>5])
        ];
    }

    public function review(){

        return $this->state(function (array $attributes) {
            return [
                'local' => false,
            ];
        });

    }

    public function validated(){

        return $this->state(function (array $attributes) {
            return [
                'validated' => true,
            ];
        });

    }

    public function adjudication(){

        return $this->state(function (array $attributes) {
            return [
                'adjudication' => true,
            ];
        });

    }

    public function centerCode(int $centerCode){

        return $this->state(function (array $attributes) use($centerCode) {
            return [
                'center_code' => $centerCode,
            ];
        });

    }


}
