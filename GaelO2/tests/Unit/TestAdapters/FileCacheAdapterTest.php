<?php

namespace Tests\Unit\TestAdapters;

use App\GaelO\Adapters\FileCacheAdapter;
use Tests\TestCase;


class FileCacheAdapterTest extends TestCase
{
    private FileCacheAdapter $fileCacheAdapter;


    protected function setUp(): void
    {
        parent::setUp();
        $this->fileCacheAdapter = new FileCacheAdapter();
    }

    public function testReadFile()
    {
        $this->fileCacheAdapter->store('keyFile2', 'payload', null);
        $content = $this->fileCacheAdapter->get('keyFile2');
        $this->assertEquals('payload', $content);
    }

    public function testStoreFile()
    {
        $success = $this->fileCacheAdapter->store('keyFile', 'payload', null);
        $this->assertTrue($success);
    }

    public function testDeleteFile()
    {
        $this->fileCacheAdapter->store('keyFile3', 'payload', null);
        $success = $this->fileCacheAdapter->delete('keyFile3');
        $this->assertTrue($success);
    }
}
