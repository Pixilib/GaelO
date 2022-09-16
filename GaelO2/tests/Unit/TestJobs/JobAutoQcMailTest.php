<?php

namespace Tests\Unit\TestJobs;

use App\Mail\AutoQC;
use Illuminate\Support\Facades\Mail;

class JobAutoQcMailTest
{

    public function sendTestAutoQcMail() {

        $studyInfo = [];
        $studyInfo['studyDescription'] = 'studyDescription';
        $studyInfo['studyManufacturer'] = 'studyManufacturer';
        $studyInfo['studyDate'] = 'studyDate';
        $studyInfo['studyTime'] = 'studyTime';
        $studyInfo['numberOfSeries'] = 5;
        $studyInfo['numberOfInstances'] = 0;
        $studyInfo['visitDate'] = '05/05/2021';
        $studyInfo['registrationDate'] = '05/05/2021';
        $studyInfo['investigatorForm'] = '"0008,0012" : {
            "Name" : "InstanceCreationDate",
            "Type" : "String",
            "Value" : "20151217"
},';
        $seriesInfo = [];
        for ($i = 0; $i < $studyInfo['numberOfSeries']; $i++) {
            $nbInstances = rand(0, 500);
            $studyInfo['numberOfInstances'] += $nbInstances;
            $seriesData = [];
            $seriesData['infos'] = [];
            $seriesData['image_path'] = (getcwd()."/tests/Unit/TestJobs/testGif.gif");
            $seriesData['series_description'] = 'HEAD/NECK  2.0  B30s';
            $seriesData['infos']['Modality'] = 'CT';
            $seriesData['infos']['Series date'] =  '20091022';
            $seriesData['infos']['Series time'] = '173151.203000';
            $seriesData['infos']['Slice thickness'] = '2';
            $seriesData['infos']['Pixel spacing'] = '9.765625e-1\\9.765625e-1';
            $seriesData['infos']['Number of instances'] = $nbInstances;
            $seriesInfo[] = $seriesData;
        }
        $visitType = 'visitType';
        $patientCode = 'patientCode';

        $parameters = [
            'study' =>  'studyName',
            'visitType' => $visitType,
            'patientCode' => $patientCode,
            'studyInfo' => $studyInfo,
            'seriesInfo' => $seriesInfo,
            'magicLink' => 'https://google.com',
            'webAddress' => 'https://google.com',
            'corporation' => 'Pixilib',
            'adminEmail' => 'test@gaelo.fr',
        ];

        Mail::to('test@gaelo.fr')->send(new AutoQC($parameters));
        dd($studyInfo['investigatorForm']);
        
    }
}



