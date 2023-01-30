<?php

namespace App\GaelO\Constants\Enums;

enum InvestigatorFormStateEnum :string
{
    case NOT_DONE = 'Not Done';
    case NOT_NEEDED = 'Not Needed';
    case DRAFT = 'Draft';
    case DONE = 'Done';
}