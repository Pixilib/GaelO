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

class OrthancMetaData
{
    private array $metaDataArray;

    public function __construct(array $metaData) 
    {
        $this->metaDataArray = $metaData;
    }

    public function getMetaDataFromCode(string $code): ?array
    {
        if (array_key_exists($code, $this->metaDataArray)) {
            return $this->metaDataArray[$code];
        }
        return null;
    }

    private function getMetaDataValueFromCode(string $code): ?string
    {
        if (array_key_exists($code, $this->metaDataArray)) {
            return $this->metaDataArray[$code]['Value'];
        }
        return null;
    }

    private function getRadioPharmaceuticalGroup() : array
    {
        if (array_key_exists('0054,0016', $this->metaDataArray)) {
            return $this->metaDataArray['0054,0016']['Value'][0];
        }
        return [];
    }

    public function getStudyManufacturer(): ?string
    {
        return $this->getMetaDataValueFromCode('0008,0070');
    }

    public function getStudyDate(): ?string
    {
        return $this->getMetaDataValueFromCode('0008,0020');
    }

    public function getSeriesDate() : ?string
    {
        return $this->getMetaDataValueFromCode('0008,0021');
    }

    public function getSeriesDescription() : ?string
    {
        return $this->getMetaDataValueFromCode('0008,103e');
    }

    public function getStudyDescription() : ?string
    {
        return $this->getMetaDataValueFromCode('0008,1030');
    }

    public function getSeriesModality() : ?string
    {
        return $this->getMetaDataValueFromCode('0008,0060');
    }

    public function getSliceThickness() : ?string
    {
        return $this->getMetaDataValueFromCode('0018,0050');
    }

    public function getPixelSpacing() : ?string
    {
        return $this->getMetaDataValueFromCode('0028,0030');
    }

    public function getMatrixSize() : ?string
    {
        if (array_key_exists('0028,0011', $this->metaDataArray) && array_key_exists('0028,0010', $this->metaDataArray)) {
            return $this->metaDataArray['0028,0010']['Value']. 'x'. $this->metaDataArray['0028,0011']['Value'];
        }
        return null;
    }

    public function getPatientPosition() : ?string
    {
        return $this->getMetaDataValueFromCode('0018,5100');
    }

    public function getImageOrientation() : ?string
    {
        return $this->getMetaDataValueFromCode('0020,0037');
    }

    public function getFieldOfView() : ?string
    {
        $pixelSpacing = $this->getMetaDataValueFromCode('0028,0030');
        if ($pixelSpacing) {
            $seperatedPixelSpacing = explode("\\", $pixelSpacing);
        }
        $rows = $this->getMetaDataValueFromCode('0028,0010');
        $columns = $this->getMetaDataValueFromCode('0028,0011');

        if ($rows == null || $columns == null || $pixelSpacing == null) {
            return null;
        }
        return $rows * $seperatedPixelSpacing[0] . 'x'. $columns * $seperatedPixelSpacing[1];
    }

    public function getScanningSequence() : ?string
    {
        return $this->getMetaDataValueFromCode('0018,0020');
    }

    public function getSequenceVariant() : ?string
    {
        return $this->getMetaDataValueFromCode('0018,0021');
    }

    public function getRepetitionTime() : ?string
    {
        return $this->getMetaDataValueFromCode('0018,0080');
    }

    public function getEchoTime() : ?string
    {
        return $this->getMetaDataValueFromCode('0018,0081');
    }

    public function getInversionTime() : ?string
    {
        return $this->getMetaDataValueFromCode('0018,0082');
    }

    public function getEchoTrainLength() : ?string
    {
        return $this->getMetaDataValueFromCode('0018,0091');
    }

    public function getSpacingBetweenSlices() : ?string
    {
        return $this->getMetaDataValueFromCode('0018,0088');
    }

    public function getProtocolName() : ?string
    {
        return $this->getMetaDataValueFromCode('0018,1030');
    }

    public function getPatientHeight() : ?string
    {
        return $this->getMetaDataValueFromCode('0010,1020');
    }

    public function getPatientWeight() : ?string
    {
        return $this->getMetaDataValueFromCode('0010,1030');
    }

    public function getStudyTime() : ?string
    {
        return $this->getMetaDataValueFromCode('0008,0030');
    }

    public function getSeriesTime() : ?string
    {
        return $this->getMetaDataValueFromCode('0008,0031');
    }

    public function getSOPClassUID() : ?string
    {
        return $this->getMetaDataValueFromCode('0008,0016');
    }

    public function getModelName() : ?string
    {
        return $this->getMetaDataValueFromCode('0008,1090');
    }

    public function getInjectedDose() : ?string
    {
        $radioPharmaceuticalGroup = $this->getRadioPharmaceuticalGroup();
        if (array_key_exists('0018,1074', $radioPharmaceuticalGroup)) {
            return $radioPharmaceuticalGroup['0018,1074']['Value'];
        }
        return null;
    }

    public function getInjectedTime() : ?string
    {
        $radioPharmaceuticalGroup = $this->getRadioPharmaceuticalGroup();
        if (array_key_exists('0018,1072', $radioPharmaceuticalGroup)) {
            return $radioPharmaceuticalGroup['0018,1072']['Value'];
        }
        return null;
    }
    
    public function getInjectedDateTime() : ?string
    {
        $radioPharmaceuticalGroup = $this->getRadioPharmaceuticalGroup();
        if (array_key_exists('0018,1078', $radioPharmaceuticalGroup)) {
            return $radioPharmaceuticalGroup['0018,1078']['Value'];
        }
        return null;
    }

    public function getSpecificActivity() : ?string
    {
        $radioPharmaceuticalGroup = $this->getRadioPharmaceuticalGroup();
        if (array_key_exists('0018,1077', $radioPharmaceuticalGroup)) {
            return $radioPharmaceuticalGroup['0018,1077']['Value'];
        }
        return null;
    }

    public function getRadiopharmaceutical() : ?string
    {
        $radioPharmaceuticalGroup = $this->getRadioPharmaceuticalGroup();
        if (array_key_exists('0018,0031', $radioPharmaceuticalGroup)) {
            return $radioPharmaceuticalGroup['0018,0031']['Value'];
        }
        return null;
    }

    public function getHalfLife() : ?string
    {
        $radioPharmaceuticalGroup = $this->getRadioPharmaceuticalGroup();
        if (array_key_exists('0018,1075', $radioPharmaceuticalGroup)) {
            return $radioPharmaceuticalGroup['0018,1075']['Value'];
        }
        return null;
    }

    public function getAcquisitonDateTime() : ?string
    {
        return $this->getMetaDataValueFromCode('0008,002a');
    }

    public function getAcquisitonDate() : ?string
    {
        return $this->getMetaDataValueFromCode('0008,0022');
    }

    public function getAcquisitonTime() : ?string
    {
        return $this->getMetaDataValueFromCode('0008,0032');
    }

    public function getNumberOfFrames() : int
    {
        return $this->getMetaDataValueFromCode('0028,0008') ?? 1;
    }

    public function getImageID() : ?string
    {
        return $this->getMetaDataValueFromCode('0054,0400')
    }
}
