<?php

namespace Database\Factories;

use App\GaelO\Util;
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
            'study_name' => function () {
                return Study::factory()->create()->name;
            },
            'visit_id' => function () {
                return Visit::factory()->create()->id;
            },
            'user_id' => function () {
                return User::factory()->create()->id;
            },
            'review_date' => Util::now(),
            'validated' => false,
            'local' => true,
            'adjudication' => false,
            'sent_files' => [],
            'review_data' => ['comment' => 'a']
        ];
    }

    public function visitId(int $visitId)
    {
        return $this->state(function (array $attributes) use ($visitId) {
            return [
                'visit_id' => $visitId,
            ];
        });
    }

    public function userId(int $userId)
    {
        return $this->state(function (array $attributes) use ($userId) {
            return [
                'user_id' => $userId,
            ];
        });
    }

    public function reviewForm()
    {
        return $this->state(function (array $attributes) {
            return [
                'local' => false,
            ];
        });
    }

    public function studyName(string $studyName)
    {
        return $this->state(function (array $attributes) use ($studyName) {
            return [
                'study_name' => $studyName,
            ];
        });
    }

    public function validated()
    {
        return $this->state(function (array $attributes) {
            return [
                'validated' => true,
            ];
        });
    }

    public function adjudication()
    {
        return $this->state(function (array $attributes) {
            return [
                'adjudication' => true,
            ];
        });
    }

    public function centerCode(int $centerCode)
    {
        return $this->state(function (array $attributes) use ($centerCode) {
            return [
                'center_code' => $centerCode,
            ];
        });
    }

    public function reviewData(array $reviewData)
    {
        return $this->state(function (array $attributes) use ($reviewData) {
            return [
                'review_data' => $reviewData,
            ];
        });
    }
}
