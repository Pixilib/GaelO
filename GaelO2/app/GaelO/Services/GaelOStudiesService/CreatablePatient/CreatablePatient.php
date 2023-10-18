<?php

namespace App\GaelO\Services\GaelOStudiesService\CreatablePatient;

class CreatablePatient
{
    public string $code;
    public int $centerCode;
    public string $inclusionStatus;

    public function __construct(string $code, int $centerCode, string $inclusionStatus)
    {
        $this->code = $code;
        $this->centerCode = $centerCode;
        $this->inclusionStatus = $inclusionStatus;
    }
}
