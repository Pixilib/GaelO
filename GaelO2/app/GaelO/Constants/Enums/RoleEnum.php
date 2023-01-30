<?php

namespace App\GaelO\Constants\Enums;

enum RoleEnum :string
{
    case INVESTIGATOR = 'Investigator';
    case MONITOR = 'Monitor';
    case CONTROLLER = 'Controller';
    case SUPERVISOR = 'Supervisor';
    case REVIEWER = 'Reviewer';
}