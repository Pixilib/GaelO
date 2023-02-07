<?php

namespace App\GaelO\Constants\Enums;

enum QualityControlStateEnum :string
{
    case NOT_DONE = 'Not Done';
    case NOT_NEEDED = 'Not Needed';
    case WAIT_DEFINITIVE_CONCLUSION = 'Wait Definitive Conclusion';
    case CORRECTIVE_ACTION_ASKED = 'Corrective Action Asked';
    case REFUSED = 'Refused';
    case ACCEPTED = 'Accepted';
}