<?php

namespace App\GaelO\Constants\Enums;

enum ReviewStatusEnum :string
{
    case NOT_DONE = 'Not Done';
    case NOT_NEEDED = 'Not Needed';
    case ONGOING = 'Ongoing';
    case WAIT_ADJUDICATION = 'Wait Adjudication';
    case DONE = 'Done';
}