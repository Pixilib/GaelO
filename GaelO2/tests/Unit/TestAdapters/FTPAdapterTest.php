<?php

namespace Tests\Unit\TestAdapters;

use App\GaelO\Adapters\FtpClientAdapter;
use Tests\TestCase;


class FTPAdapterTest extends TestCase
{
    private FtpClientAdapter $ftpClientAdapter;


    protected function setUp(): void
    {
        $this->markTestSkipped();
        parent::setUp();
        $this->ftpClientAdapter = new FtpClientAdapter();
        $this->ftpClientAdapter->setFTPServer('ftp.dlptest.com', 21, 'dlpuser', 'rNrKYTX9g7z3RgJRmxWuGHbeu', false, false);
    }

    /**
     * @depends testWriteFile
     */
    public function testReadFile()
    {
        //$this->markTestSkipped('need external FTP Server');
        $content = $this->ftpClientAdapter->getFileContent('myFile.txt', 100);
        $this->assertEquals('coucou', $content);

    }

    public function testWriteFile()
    {
        $success = $this->ftpClientAdapter->writeFileContent('coucou', 'myFile.txt');
        $this->assertTrue($success);


    }
}
