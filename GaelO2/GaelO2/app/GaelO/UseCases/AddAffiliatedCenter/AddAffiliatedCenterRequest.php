<?php

namespace App\GaelO\UseCases\AddAffiliatedCenter;

class AddAffiliatedCenterRequest {
    public int $currentUserId;
    //Center code can be int or array of int
    public $centerCode;
    public int $userId;
}
