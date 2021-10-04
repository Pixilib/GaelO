<?php

namespace Database\Factories;

use App\GaelO\Constants\Constants;
use App\GaelO\Util;
use App\Models\Study;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class TrackerFactory extends Factory
{

    public function definition()
    {
        return [
            'study_name' => Study::factory()->create()->name,
            'user_id' => User::factory()->create()->id,
            'date' => Util::now(),
            'role' => null,
            'visit_id'=>null,
            'action_type'=>$this->faker->randomElement([Constants::TRACKER_UNLOCK_INVESTIGATOR_FORM, Constants::TRACKER_CREATE_USER]),
            'action_details'=>json_encode([])
        ];
    }

    public function studyName(string $studyName){

        return $this->state(function (array $attributes) use ($studyName) {
            return [
                'study_name' => $studyName,
            ];
        });
    }

    public function userId(int $userId){

        return $this->state(function (array $attributes) use ($userId) {
            return [
                'user_id' => $userId,
            ];
        });

    }

    public function role(string $role){

        return $this->state(function (array $attributes) use ($role) {
            return [
                'role' => $role,
            ];
        });

    }

    public function visitId(int $visitId){

        return $this->state(function (array $attributes) use ($visitId) {
            return [
                'visit_id' => $visitId,
            ];
        });
    }

    public function actionType(string $actionType){

        return $this->state(function (array $attributes) use ($actionType) {
            return [
                'action_type' => $actionType,
            ];
        });
    }
}
