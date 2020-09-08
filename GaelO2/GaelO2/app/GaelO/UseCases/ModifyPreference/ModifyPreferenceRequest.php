<?php

namespace App\GaelO\UseCases\ModifyPreference;

class ModifyPreferenceRequest {
    public int $currentUserId;
    public int $patientCodeLength;
    public String $plateformeName;
    public String $adminEmail;
    public String $replyToEmail;
    public String $corporation;
    public String $url;
    public String $parseDateImport;
    public String $parseCountryName;
}
