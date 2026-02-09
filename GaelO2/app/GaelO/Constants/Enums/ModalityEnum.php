<?php

namespace App\GaelO\Constants\Enums;

enum ModalityEnum :string
{
    case CR = "CR";
    case CT = 'CT';
    case DX = "DX";
    case ECG = "ECG";
    case ES = "ES";
    case MG = "MG";
    case MR = 'MR';
    case NM = 'NM';
    case OP = 'OP';
    case PT = 'PT';
    case RTSTRUCT = 'RTSTRUCT';
    case SEG = 'SEG';
    case SM = 'SM';
    case US = 'US';
    case DOC = 'DOC';
}