<?php

namespace App\GaelO\UseCases\GetStudy;

class GetStudyRequest {
    public int $currentUserId;
    public bool $withTrashed;
}
