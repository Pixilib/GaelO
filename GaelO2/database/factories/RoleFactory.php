<?php

namespace Database\Factories;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class RoleFactory extends Factory
{

    protected $model = Role::class;

    public function definition()
    {
        return [
            'name' => null,
            'user_id' => function () {
                return User::factory()->create()->id;
            },
            'study_name' => null
        ];
    }

    public function userId(int $userId)
    {
        return $this->state(function (array $attributes) use ($userId) {
            return [
                'user_id' => $userId,
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

    public function roleName(string $roleName)
    {
        return $this->state(function (array $attributes) use ($roleName) {
            return [
                'name' => $roleName,
            ];
        });
    }
}
