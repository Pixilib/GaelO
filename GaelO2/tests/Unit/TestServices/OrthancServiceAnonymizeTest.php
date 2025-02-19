<?php

namespace Tests\Unit\TestServices;

use App\GaelO\Constants\Enums\AnonProfileEnum;
use App\GaelO\Services\OrthancService;
use App\GaelO\Services\StoreObjects\OrthancSeries;
use Illuminate\Support\Facades\App;
use ReflectionClass;
use Tests\TestCase;

class OrthancServiceAnonymizeTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    public function testAnonymizeOrthancDefaultProfile()
    {
        $orthancService = App::make(OrthancService::class);

        $reflection = new ReflectionClass($orthancService);
        $method = $reflection->getMethod('buildAnonQuery');
        $method->setAccessible(true);

        $result = $method->invokeArgs($orthancService, [AnonProfileEnum::DEFAULT->value, 'patientname', 'patientid', 'description', 'trial']);
        $this->assertEquals($result['KeepPrivateTags'], false);
        $this->assertEquals($result['Force'], true);
        $this->assertEquals($result['DicomVersion'], '2023b');
        $this->assertEquals($result['Replace']['0008,0050'], 'trial');
        $this->assertEquals($result['Replace']['0010,0020'], 'patientid');
        $this->assertEquals($result['Replace']['0010,0010'], 'patientname');
        $this->assertEquals($result['Replace']['0008,1030'], 'description');
        $this->assertEquals($result['Replace']['0010,0030'], '19000101');

        $keepvalues = [
            "0008,0022",
            "0008,002A",
            "0008,0032",
            "0038,0020",
            "0038,0021",
            "0008,0035",
            "0008,0025",
            "0008,0023",
            "0008,0033",
            "0008,0024",
            "0008,0034",
            "0040,0244",
            "0040,0245",
            "0008,0021",
            "0008,0031",
            "0008,0020",
            "0008,0030",
            "0008,0201",
            "0040,0002",
            "0040,0003",
            "0040,0004",
            "0040,0005",
            "0010,0040",
            "0010,1020",
            "0010,1030",
            "0008,103E",
            "3006,0026",
            "3006,0008",
            "3006,0009",
            "300A,0006",
            "300A,0007",
            "7053,1000",
            "7053,1009",
            "0009,103B",
            "0009,100D",

            "300C,0127",
            "0018,1042",
            "0018,1043",
            "0018,1202",
            "0018,9701",
            "0018,9804",
            "0018,9074",
            "0018,9151",
            "0018,9623",
            "0018,0012",
            "0018,0013",
            "0018,0035",
            "0018,0027",
            "0040,A032",
            "0040,A033",
            "0070,0082",
            "0070,0083",
            "0044,000B",
            "0018,1078",
            "0018,1072",
            "0018,1079",
            "0018,1073",
            "0040,A13A",
            "3008,0162",
            "3008,0164",
            "3008,0166",
            "3008,0168",
            "0032,1000",
            "0032,1001",
            "0032,1010",
            "0032,1011",
            "0100,0420",
            "300A,022C",
            "300A,022E",
            "0044,0010",
            "0018,1201",
            "0018,700E",
            "0040,A030"
        ];

        $this->assertEqualsCanonicalizing($result['Keep'], $keepvalues);
    }

    public function testAnonymizeOrthancFullProfile()
    {
        $orthancService = App::make(OrthancService::class);

        $reflection = new ReflectionClass($orthancService);
        $method = $reflection->getMethod('buildAnonQuery');
        $method->setAccessible(true);

        $result = $method->invokeArgs($orthancService, [AnonProfileEnum::DEFAULT->value, 'patientname', 'patientid', 'description', 'trial']);

        $this->assertEquals($result['KeepPrivateTags'], false);
        $this->assertEquals($result['Force'], true);
        $this->assertEquals($result['DicomVersion'], '2023b');
        $this->assertEquals($result['Replace']['0008,0050'], 'trial');
        $this->assertEquals($result['Replace']['0010,0020'], 'patientid');
        $this->assertEquals($result['Replace']['0010,0010'], 'patientname');
        $this->assertEquals($result['Replace']['0008,1030'], 'description');
        $this->assertEquals($result['Replace']['0010,0030'], '19000101');

        $keepvalues = [
            "7053,1000",
            "7053,1009",
            "0009,103B",
            "0009,100D",
        ];
        $this->assertEqualsCanonicalizing($result['Keep'], $keepvalues);
    }

    public function testIsSecondaryCapture()
    {
        $orthancSeries = App::make(OrthancSeries::class);
        $scUids = [
            "1.2.840.10008.5.1.4.1.1.7",
            "1.2.840.10008.5.1.4.1.1.7.1",
            "1.2.840.10008.5.1.4.1.1.7.2",
            "1.2.840.10008.5.1.4.1.1.7.3",
            "1.2.840.10008.5.1.4.1.1.7.4",
            "1.2.840.10008.5.1.4.1.1.88.11",
            "1.2.840.10008.5.1.4.1.1.88.22",
            "1.2.840.10008.5.1.4.1.1.88.33",
            "1.2.840.10008.5.1.4.1.1.88.40",
            "1.2.840.10008.5.1.4.1.1.88.50",
            "1.2.840.10008.5.1.4.1.1.88.59",
            "1.2.840.10008.5.1.4.1.1.88.65",
            "1.2.840.10008.5.1.4.1.1.88.67"
        ];
        foreach ($scUids as $uid) {
            $orthancSeries->sopClassUid = $uid;
            $answer = $orthancSeries->isSecondaryCapture();
            $this->assertTrue($answer);
        }
        $orthancSeries->sopClassUid = "notsc";
        $answer = $orthancSeries->isSecondaryCapture();
        $this->assertFalse($answer);
    }
}
