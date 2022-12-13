<?php

namespace App\GaelO\UseCases\CreateFileToFormFromTus;

class CreateFileToFormFromTusRequest
{
    public int $currentUserId;
    public int $id;
    public string $key;
    public ?string $extension;
    public array $tusIds;
    public ?int $numberOfInstances;
    public ?bool $isDicom = false;
}
