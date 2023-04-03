<?php

namespace Tests\Unit\Mail;

use App\Mail\QcReport;
use Illuminate\Support\Facades\Mail;

/*
use Tests\Unit\Mail\SendMailQcReport;
$mail = new SendMailQcReport();
$mail->sendTestQcReportMail();
*/

class SendMailQcReport
{

    public function sendTestQcReportMail() {

        $studyInfo = [];
        $studyInfo['studyDescription'] = 'studyDescription';
        $studyInfo['studyManufacturer'] = 'studyManufacturer';
        $studyInfo['studyDate'] = 'studyDate';
        $studyInfo['studyTime'] = 'studyTime';
        $studyInfo['numberOfSeries'] = 5;
        $studyInfo['numberOfInstances'] = 0;
        $studyInfo['visitDate'] = '05/05/2021';
        $studyInfo['registrationDate'] = '05/05/2021';
        $studyInfo['investigatorForm'] = [
            "Name" => "InstanceCreationDate",
            "Type" => "String",
            "Value" => "20151217"
        ];
        $seriesInfo = [];
        for ($i = 0; $i < $studyInfo['numberOfSeries']; $i++) {
            $nbInstances = rand(0, 500);
            $studyInfo['numberOfInstances'] += $nbInstances;
            $seriesData = [];
            $seriesData['image_path'] = null;
            $seriesData['Series Description'] = 'HEAD/NECK  2.0  B30s';
            $seriesData['Modality'] = 'CT';
            $seriesData['Series date'] =  '20091022';
            $seriesData['Series time'] = '173151.203000';
            $seriesData['Slice thickness'] = '2';
            $seriesData['Pixel spacing'] = '9.765625e-1\\9.765625e-1';
            $seriesData['Number of instances'] = $nbInstances;
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
            'mailFromAddress' => 'test@gaelo.fr',
            'mailReplyTo' => 'test@gaelo.fr'
        ];

        Mail::to('test@gaelo.fr')->send(new QcReport($parameters));
    }
}



