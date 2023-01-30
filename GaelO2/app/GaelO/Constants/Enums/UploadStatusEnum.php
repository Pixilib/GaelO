<?php

namespace App\GaelO\Constants\Enums;

enum UploadStatusEnum: string
{
    case DONE = 'Done';
    case PROCESSING = 'Processing';
    case NOT_DONE = 'Not Done';
}