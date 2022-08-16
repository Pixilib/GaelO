<?php

namespace Tests\Unit;

use App\GaelO\Constants\Constants;
use App\GaelO\Services\OrthancService;
use App\GaelO\Util;
use Illuminate\Support\Facades\App;
use Tests\TestCase;

class GaelOUtilTest extends TestCase
{

    public function testIsSemanticVersioning(){
        $shouldTrue = Util::isSemanticVersioning('1.2.0');
        $this->assertTrue($shouldTrue);

    }

    public function testIsNotSemanticVersioning(){
        $shouldFalse = Util::isSemanticVersioning('1.2.3.4');
        $this->assertFalse($shouldFalse);
        $shouldFalse = Util::isSemanticVersioning('1.2');
        $this->assertFalse($shouldFalse);
    }
}
