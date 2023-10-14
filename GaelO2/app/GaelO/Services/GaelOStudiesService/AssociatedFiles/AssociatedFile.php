<?php

namespace App\GaelO\Services\GaelOStudiesService\AssociatedFiles;

class AssociatedFile
{
    public string $key;
    public array $mimes;
    public bool $mandatory;

    public function __construct(string $key, array $mimes, bool $mandatory)
    {
        $this->key = $key;
        $this->mimes = $mimes;
        $this->mandatory = $mandatory;
    }
}
