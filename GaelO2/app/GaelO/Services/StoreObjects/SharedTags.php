<?php
/**
 Copyright (C) 2018-2020 KANOUN Salim
 This program is free software; you can redistribute it and/or modify
 it under the terms of the Affero GNU General Public v.3 License as published by
 the Free Software Foundation;
 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 Affero GNU General Public Public for more details.
 You should have received a copy of the Affero GNU General Public Public along
 with this program; if not, write to the Free Software Foundation, Inc.,
 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA
 */

namespace App\GaelO\Services\StoreObjects;

use App\GaelO\Services\OrthancService;

class SharedTags
{
    private array $sharedTagsArray;

    public function __construct(array $sharedTags) 
    {
        $this->sharedTagsArray=$sharedTags;
    }

    private function getSharedTagsFromCode(string $code): ?array
    {
        if (array_key_exists($code, $this->sharedTagsArray)) {
            return $this->sharedTagsArray[$code];
        }
        return null;
    }

    private function getSharedTagsValueFromCode(string $code): ?string
    {
        if (array_key_exists($code, $this->sharedTagsArray)) {
            return $this->sharedTagsArray[$code]['Value'];
        }
        return null;
    }

    public function getStudyManufacturer(): ?string
    {
        return $this->getSharedTagsValueFromCode('0008,0070');
    }

    public function getStudyDate(): ?string
    {
        return $this->getSharedTagsValueFromCode('0008,0020');
    }

    public function getSeriesDate() : ?string
    {
        return $this->getSharedTagsValueFromCode('0008,0021');
    }

    public function getSeriesDescription() : ?string
    {
        return $this->getSharedTagsValueFromCode('0008,103e');
    }

    public function getStudyDescription() : ?string
    {
        return $this->getSharedTagsValueFromCode('0008,1030');
    }

    public function getSeriesModality() : ?string
    {
        return $this->getSharedTagsValueFromCode('0008,0060');
    }

    public function getSliceThickness() : ?string
    {
        return $this->getSharedTagsValueFromCode('0018,0050');
    }

    public function getPixelSpacing() : ?string
    {
        return $this->getSharedTagsValueFromCode('0028,0030');
    }

    public function getMatrixSize() : ?string
    {
        if (array_key_exists('0028,0011', $this->sharedTagsArray) && array_key_exists('0028,0010', $this->sharedTagsArray)) {
            return $this->sharedTagsArray['0028,0010']['Value']. 'x'. $this->sharedTagsArray['0028,0011']['Value'];
        }
        return null;
    }

    public function getPatientPosition() : ?string
    {
        return $this->getSharedTagsValueFromCode('0018,5100');
    }

    public function getImageOrientation() : ?string
    {
        return $this->getSharedTagsValueFromCode('0020,0037');
    }

    public function getFieldOfView() : ?string
    {
        $pixelSpacing = $this->getSharedTagsValueFromCode('0028,0030');
        if ($pixelSpacing) {
            $seperatedPixelSpacing = explode("\\", $pixelSpacing);
        }
        $rows = $this->getSharedTagsValueFromCode('0028,0010');
        $columns = $this->getSharedTagsValueFromCode('0028,0011');

        if ($rows == null || $columns == null || $pixelSpacing == null) {
            return null;
        }
        return $rows * $seperatedPixelSpacing[0] . 'x'. $columns * $seperatedPixelSpacing[1];
    }

    public function getScanningSequence() : ?string
    {
        return $this->getSharedTagsValueFromCode('0018,0020');
    }

    public function getSequenceVariant() : ?string
    {
        return $this->getSharedTagsValueFromCode('0018,0021');
    }

    public function getRepetitionTime() : ?string
    {
        return $this->getSharedTagsValueFromCode('0018,0080');
    }

    public function getEchoTime() : ?string
    {
        return $this->getSharedTagsValueFromCode('0018,0081');
    }

    public function getInversionTime() : ?string
    {
        return $this->getSharedTagsValueFromCode('0018,0082');
    }

    public function getEchoTrainLength() : ?string
    {
        return $this->getSharedTagsValueFromCode('0018,0091');
    }

    public function getSpacingBetweenSlices() : ?string
    {
        return $this->getSharedTagsValueFromCode('0018,0088');
    }

    public function getProtocolName() : ?string
    {
        return $this->getSharedTagsValueFromCode('0018,1030');
    }

    public function getPatientHeight() : ?string
    {
        return $this->getSharedTagsValueFromCode('0010,0010');
    }

    public function getPatientWeight() : ?string
    {
        return $this->getSharedTagsValueFromCode('0010,1030');
    }

    public function getStudyTime() : ?string
    {
        return $this->getSharedTagsValueFromCode('0008,0030');
    }

    public function getSeriesTime() : ?string
    {
        return $this->getSharedTagsValueFromCode('0008,0031');
    }

    public function getRadioPharmaceuticalTags(OrthancService $orthancService, string $orthancID) : ?array
    {
        $radioPharmaceuticalArray = [];
        $firstInstanceTags = new SharedTags($orthancService->getInstanceTags($orthancID));
        $radioPharmaceuticalTags = $firstInstanceTags->getSharedTagsFromCode('0018,1072');

        if ($radioPharmaceuticalTags == null) {
            return null;
        }
        for ($j = 0; $j < count($radioPharmaceuticalTags); $j++) {
            $radioPharmaceuticalArray[$radioPharmaceuticalTags[$j]['Name']] = $radioPharmaceuticalTags[$j]['Value'];
        }
        return $radioPharmaceuticalArray;
    }

    public function getImageType() : int
    {
        $SOPClassUID = $this->getSharedTagsValueFromCode('0008,0016');

        if ($SOPClassUID == '1.2.840.10008.5.1.4.1.1.4' ||
            $SOPClassUID == '1.2.840.10008.5.1.4.1.1.4.1') {
            return 0;
        } elseif ($SOPClassUID == '1.2.840.10008.5.1.4.1.1.2' ||
            $SOPClassUID == '1.2.840.10008.5.1.4.1.1.2.1' ||
            $SOPClassUID == '1.2.840.10008.5.1.4.1.1.20' ||
            $SOPClassUID == '1.2.840.10008.5.1.4.1.1.128' ||
            $SOPClassUID == '1.2.840.10008.5.1.4.1.1.130' ||
            $SOPClassUID == '1.2.840.10008.5.1.4.1.1.128.1') {
            return 1;
        } else {
            return 2;
        }
    }
}