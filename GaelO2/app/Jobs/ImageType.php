<?php
namespace App\Jobs;

enum ImageType : string
{
    case MIP = 'MIP';
    case MOSAIC = 'MOSAIC';
    case DEFAULT = 'DEFAULT';
}