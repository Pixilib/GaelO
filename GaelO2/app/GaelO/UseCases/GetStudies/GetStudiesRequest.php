<?php

namespace App\GaelO\UseCases\GetStudies;

class GetStudiesRequest
{
    public int $currentUserId;
    public bool $withTrashed;
}
