<?php

namespace App\GaelO\Services\GaelOStudiesService\Events;

enum GaelOStudyEventEnum: string
{
    case UPLOAD_VISIT_EVENT = 'UPLOAD_VISIT_EVENT';
    case QC_MODIFIED_EVENT = 'QC_MODIFIFED_EVENT';
}