<?php

namespace App\GaelO\Constants\Enums;

enum TransferSyntaxEnum :string
{
    case IMPLICT_VR_ENDIAN = '1.2.840.10008.1.2';
    case EXPLICIT_VR_LITTLE_ENDIAN = '1.2.840.10008.1.2.1';
    case JPEG_LS_LOSSLESS = '1.2.840.10008.1.2.4.80';
    case JPEG_BASLINE = '1.2.840.10008.1.2.4.50';
}