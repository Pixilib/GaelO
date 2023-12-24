<?php

namespace Tests\Unit\TestAdapters;

use App\GaelO\Adapters\MimeAdapter;
use Tests\TestCase;


class MimeAdapterTest extends TestCase
{
    private MimeAdapter $mimeAdapter;


    protected function setUp(): void
    {
        parent::setUp();
        $this->mimeAdapter = new MimeAdapter();
    }

    
    public function testMimeDicom()
    {
        $extensions = $this->mimeAdapter->getExtensionsFromMime('application/dicom');
        $this->assertEquals('dcm', $extensions[0]);
    }

    public function testExtensionDicom()
    {
        $mime = $this->mimeAdapter->getMimeFromExtension('dcm');
        $this->assertEquals('application/dicom', $mime); 
    }
}
