<?php

namespace Tests\Unit\TestServices;

use App\GaelO\Services\PdfServices;
use Tests\TestCase;
use Illuminate\Support\Facades\App;

class PdfServiceTest extends TestCase
{
    private PdfServices $pdfService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->pdfService = App::make(PdfServices::class);
    }

    public function testMakeRadiomicsPdf()
    {
        $filename = $this->pdfService->saveRadiomicsPdf('TEST', '12345', 'PET0', '07141995', ['tmtv41' => 55, 'magicLink'=> 'link']);
        $this->assertIsString($filename);
    }

}
