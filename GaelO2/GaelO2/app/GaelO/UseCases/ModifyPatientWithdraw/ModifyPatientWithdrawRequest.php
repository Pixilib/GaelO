<?php

namespace App\GaelO\UseCases\ModifyPatientWithdraw;

class ModifyPatientWithdrawRequest {
    public int $currentUserId;
    public int $patientCode;
    public bool $withdraw;
    public ?string $withdrawReason=null;
    public ?string $withdrawDate=null;

}
