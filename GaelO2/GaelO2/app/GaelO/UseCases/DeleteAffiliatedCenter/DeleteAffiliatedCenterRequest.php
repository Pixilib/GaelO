<?php

namespace App\GaelO\UseCases\DeleteAffiliatedCenter;

class DeleteAffiliatedCenterRequest {
    public int $userId;
    public int $currentUserId;
    public int $centerCode;
}
