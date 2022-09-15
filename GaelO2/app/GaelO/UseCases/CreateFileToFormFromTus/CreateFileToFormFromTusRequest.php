<?php

namespace App\GaelO\UseCases\CreateFileToFormFromTus;

class CreateFileToFormFromTusRequest
{
    public int $currentUserId;
    public int $id;
    public string $key;
    public array $tusIds;
    public ?int $numberOfInstances;
}
