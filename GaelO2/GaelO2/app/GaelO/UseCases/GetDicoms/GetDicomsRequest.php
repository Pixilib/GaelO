<?php

namespace App\GaelO\UseCases\GetDicoms;

class GetDicomsRequest{
    public int $currentUserId;
    public string $role;
    public int $visitId;
}
