<?php

namespace Tests\Unit\TestAdapters;

use App\GaelO\Adapters\SpreadsheetAdapter;
use Tests\TestCase;


class SpreadsheetAdapterTest extends TestCase
{
    private SpreadsheetAdapter $spreadsheetAdapter;


    protected function setUp(): void
    {
        parent::setUp();
        $this->spreadsheetAdapter = new SpreadsheetAdapter();
    }

    public function testSpreadSheetCreation()
    {

        $this->spreadsheetAdapter->setDefaultWorksheetTitle('Sassa');
        $this->spreadsheetAdapter->addSheet('TestSalim');

        $data = [
            [
                'title1' => 'value1',
                'title2' => 'value2'
            ]
        ];

        $this->spreadsheetAdapter->fillData('TestSalim', $data);
        //Create Temp file to be automatically collected
        $tempFile = tmpfile();
        $tempFileMetadata = stream_get_meta_data($tempFile);
        $filename = $tempFileMetadata["uri"];
        $this->spreadsheetAdapter->writeToExcel($filename);
    }
}
