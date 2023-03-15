<?php

namespace App\Jobs\QcReport;

enum ImageType : string
{
    case MIP = 'MIP';
    case MOSAIC = 'MOSAIC';
    case DEFAULT = 'DEFAULT';
    case MULTIFRAME = 'MULTIFRAME';
}