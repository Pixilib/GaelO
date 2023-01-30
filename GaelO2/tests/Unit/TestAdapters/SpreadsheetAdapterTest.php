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
        $this->spreadsheetAdapter->addSheet('TestSalim');

        $data = [
            [
                'title1' => 'value1',
                'title2' => 'value2'
            ]
        ];

        $this->spreadsheetAdapter->fillData('TestSalim', $data);
        $filename = $this->spreadsheetAdapter->writeToExcel();
        unlink($filename);
    }
}
