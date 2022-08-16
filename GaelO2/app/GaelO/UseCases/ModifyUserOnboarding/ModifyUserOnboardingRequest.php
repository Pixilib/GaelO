<?php

namespace App\GaelO\UseCases\ModifyUserOnboarding;

class ModifyUserOnboardingRequest {
    public int $currentUserId;
    public int $userId;
    public string $onboardingVersion;
}
