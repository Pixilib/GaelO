<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class AutoQCTest extends Mailable //implements ShouldQueue
{
    //use Queueable, SerializesModels;

    protected array $parameters;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct()
    {
        $studyInfo = [];


        $studyInfo['study'] = 'StudyDesc';
        $studyInfo['studyDescription'] = 'StudyDesc';
        $studyInfo['studyManufacturer'] = 'StudyManuf';
        $studyInfo['studyDate'] = 'StudyDate';
        $studyInfo['studyTime'] = 'StudyTime';
        $studyInfo['numberOfSeries'] = 1;
        $studyInfo['numberOfInstances'] = 100;

        $seriesInfo = [[
            'seriesDescription' => 'SeriesDesc',
            'image_path' => 'ImagePath',
            'infos' => [
                'seriesDescription' => 'SeriesDesc',
                'seriesNumber' => 1,
                'seriesDate' => 'SeriesDate',
                'seriesTime' => 'SeriesTime',
                'numberOfInstances' => 100,
            ],
        ]
        ];


        $this->parameters = [
            'study' => 'Study',
            'visitType' => 'VisitType',
            'patientCode' => 'PatientCode',
            'studyInfo' => $studyInfo,
            'seriesInfo' => $seriesInfo,
            'magicLink' => 'MagicLink',
            'webAddress' => 'WebAddress',
            'adminEmail' => 'AdminEmail',
            'corporation' => 'Corporation',
        ];
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->view('mails.mail_auto_qc')
        ->subject($this->parameters['study']." - AutoQc Patient - ".$this->parameters['patientCode']." - Visit - ".$this->parameters['visitType'])
        ->with($this->parameters);
    }
}
