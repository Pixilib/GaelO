<?php

namespace App\GaelO\Constants\Enums;

enum ProcessingMaskEnum: string
{
    case RTSS = 'rtss';
    case SEG = 'seg';
    case NIFTI = 'nifti';
}