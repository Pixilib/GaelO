<?php

namespace App\Jobs\RadiomicsReport;

class GaelOProcessingFile
{

    private string $type;
    private string $id;

    public function __construct(string $type, string $id)
    {
        $this->type = $type;
        $this->id = $id;
    }

    public function getType() :string{
        return $this->type;
    }

    public function getId() :string {
        return $this->id;
    }
}
