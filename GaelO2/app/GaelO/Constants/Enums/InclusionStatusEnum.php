<?php

namespace App\GaelO\Constants\Enums;

enum InclusionStatusEnum: string
{
    case INCLUDED = 'Included';
    case PRE_INCLUDED = 'Pre Included';
    case EXCLUDED = 'Excluded';
    case WITHDRAWN = 'Withdrawn';
}
