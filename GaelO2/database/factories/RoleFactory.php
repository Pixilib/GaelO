<?php

namespace Database\Factories;

use App\GaelO\Constants\Constants;
use App\Models\Role;
use App\Models\Study;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class RoleFactory extends Factory
{

    protected $model = Role::class;

    public function definition()
    {
        return [
            'name' => $this->faker->randomElement([Constants::ROLE_INVESTIGATOR, Constants::ROLE_CONTROLLER, Constants::ROLE_REVIEWER, Constants::ROLE_MONITOR, Constants::ROLE_SUPERVISOR]),
            'user_id' => function () {
                return User::factory()->create()->id;
            },
            'study_name' => function () {
                return Study::factory()->create();
            },
            'validated_documentation_version' => null
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

    public function validatedDocumentationVersion(string $version)
    {
        return $this->state(function (array $attributes) use ($version) {
            return [
                'validated_documentation_version' => $version,
            ];
        });
    }
}
