<?php

namespace App\GaelO\UseCases\CreateMagicLink;

class CreateMagicLinkRequest{
    public int $currentUserId;
    public int $targetUser;
    public string $ressourceLevel;
    public string $role;
    public int $ressourceId;
}
