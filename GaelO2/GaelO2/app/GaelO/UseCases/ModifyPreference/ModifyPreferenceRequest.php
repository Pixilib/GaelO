<?php

namespace App\GaelO\UseCases\ModifyPreference;

class ModifyPreferenceRequest {
    public int $currentUserId;
    public int $patientCodeLength;
    public String $parseDateImport;
    public String $parseCountryName;
}
