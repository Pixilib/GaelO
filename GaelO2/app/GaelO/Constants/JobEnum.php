<?php

namespace App\GaelO\Constants;

enum JobEnum :string
{
    case CRA = 'CRA';
    case MONITOR = 'Monitor';
    case NUCLEARIST = 'Nuclearist';
    case PI = 'PI';
    case RADIOLOGIST = 'Radiologist';
    case STUDY_NURSE = 'Study nurse';
    case SUPERVISION = 'Supervision';
}