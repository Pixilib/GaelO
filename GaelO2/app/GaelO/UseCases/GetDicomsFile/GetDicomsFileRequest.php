<?php

namespace App\GaelO\UseCases\GetDicomsFile;


class GetDicomsFileRequest
{
    public int $currentUserId;
    public string $studyName;
    public int $visitId;
    public string $role;
}
