<?php

namespace App\GaelO\UseCases\CreateFileToFormFromTus;

class CreateFileToFormFromTusRequest
{
    public int $currentUserId;
    public int $id;
    public string $key;
    public ?string $extension = null;
    public array $tusIds;
    public ?bool $isDicom = false;
    public ?int $numberOfInstances;
}
