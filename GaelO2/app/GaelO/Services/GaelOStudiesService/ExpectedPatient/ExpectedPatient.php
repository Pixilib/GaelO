<?php

namespace App\GaelO\Services\GaelOStudiesService\ExpectedPatient;

class ExpectedPatient
{
    public string $code;
    public ?int $centerCode;
    public ?string $inclusionStatus;

    public function __construct(string $code, ?int $centerCode = null, ?string $inclusionStatus = null)
    {
        $this->code = $code;
        $this->centerCode = $centerCode;
        $this->inclusionStatus = $inclusionStatus;
    }
}
