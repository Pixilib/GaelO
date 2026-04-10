<?php

namespace App\Console\Commands;

use App\GaelO\Constants\Constants;
use App\GaelO\Constants\Enums\JobEnum;
use App\GaelO\Repositories\DicomStudyRepository;
use App\GaelO\Repositories\VisitRepository;
use App\GaelO\Services\OrthancService;
use App\GaelO\Adapters\FtpClientAdapter;
use App\Models\User;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

enum Desinations: string
{
    case ORTHANCPEER = "orthanc-peer";
    case FTP = "ftp";
    case SFTP = "sftp";
    case DICOMWEB = "dicom-web";
    case DICOMNODE = "dicom-node";
    case S3 = "s3";
    case AZURESTORAGE = "azure-storage";
    case WEBDAV = "webdav";
}

class ExportDicom extends Command
{

    private VisitRepository $visitRepository;
    private DicomStudyRepository $dicomStudyRepository;
    private OrthancService $orthancService;
    private FtpClientAdapter $ftpClientAdapter;
    private bool $withDeletedStudies = false;
    private bool $withDeletedSeries = false;

    private string $studyName;

    private array $index;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'gaelo:export-dicom';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Export DICOMs of a clinical trial to a destination endpoint';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(
        VisitRepository $visitRepository,
        DicomStudyRepository $dicomStudyRepository,
        OrthancService $orthancService,
    ) {
        $this->orthancService = $orthancService;
        $this->visitRepository = $visitRepository;
        $this->dicomStudyRepository = $dicomStudyRepository;

        $destinatorName = $this->ask('Destinator Name : (ex: sanofi)');
        $destinationType = $this->choice('Destination Type', array_column(Desinations::cases(), 'value'));
        $orthancAddress = $this->ask('Orthanc URL : (ex: http://www.pixilib.fr:8042) ');
        $orthancUsername = $this->ask('Orthanc Username: ');
        $orthancPassword = $this->secret('Orthanc Password: ');
        \Log::warning('test 1');
        $this->studyName = $this->ask('Study to export :');

        if ($this->confirm('Includes deleted studies ?')) {
            $this->withDeletedStudies = true;
        }
        if ($this->confirm('Includes deleted series ?')) {
            $this->withDeletedSeries = true;
        }

        $studies = $this->getDicomStudiesToSend();
        Log::info('Number of Series to send : ' . sizeof($studies));



        foreach ($studies as $study) {
            $studyOrthancId = $study['orthanc_id'];
            $patientCode = $study['visit']['patient_code'];
            $visitType = $study['visit']['visit_type']['name'];
            $visitName = $study['visit']['name'];

            $fileName = $studyOrthancId . '.zip';

            $this->index[$studyOrthancId] = [
                'filename' => $fileName,
                'patientCode' => $patientCode,
                'visitType' => $visitType,
                'visitName' => $visitName,
                'status' => 'sending'
            ];

            switch ($destinationType) {
                case "orthanc-peer":
                    $this->registerPeer($destinatorName, $orthancAddress, $orthancUsername, $orthancPassword);
                    $jobData = $this->orthancService->sendToPeerAsyncWithAccelerator($destinatorName, [$studyOrthancId], true);
                    $this->waitForJobFinished($jobData['ID']);
                    $this->updateStudyStatus($studyOrthancId, 'success');
                    break;
                case "webdav":
                    $filePath = $this->getZipToSend([$studyOrthancId]);
                    
                case "ftp":
                    $filePath = $this->getZipToSend([$studyOrthancId]);
                    //Envoyer vers client SFTP
                    //Quant transférer supprimer le fichier temporaire avant de passer a la series suivante
                    $isSftp = false;
                    if ($this->confirm('Use SFTP ?')) {
                        $isSftp= true;
                    }
                    $this->ftpClientAdapter->setFTPServer($orthancAddress, 0000, $orthancUsername, $orthancPassword, $isSftp, false);
                    $this->ftpClientAdapter->writeStreamContent();
                    break;
                case "sftp":
                    $filePath = $this->getZipToSend([$studyOrthancId]);
                    break;
                case "s3":
                    $filePath = $this->getZipToSend([$studyOrthancId]);
                    break;
                case "asure-storage":
                    $filePath = $this->getZipToSend([$studyOrthancId]);
                    break;
                case "dicom-web":
                    break;
                case "dicom-node":
                    break;


            }


        }


        //$orthancService->deletePeer($destinatorName);
        return 0;
    }

    private function updateStudyStatus($studyOrthancId, $status)
    {
        $this->index[$studyOrthancId]['status'] = $status;
    }

    private function getZipToSend(array $studiesIds)
    {
        $tempFileLocation = tempnam(ini_get('upload_tmp_dir'), 'TMPZIP_');
        $this->orthancService->getZipStreamToFile($studiesIds, $tempFileLocation);
        return $tempFileLocation;
    }


    private function waitForJobFinished(string $jobId)
    {
        do {
            sleep(3);
            $job = $this->getJobDetails($jobId);
        } while ($job['State'] !== "Success" && $job['State'] !== "Failure");

        if ($job['State'] === "Failure") {
            throw new Exception('Job Orthanc Failure');
        }

        return;
    }

    private function getDicomStudiesToSend(): array
    {
        $visits = $this->visitRepository->getVisitsInStudy($this->studyName, false, false, true, null);
        foreach ($visits as $visit) {
            $dicomStudies = $this->dicomStudyRepository->getDicomsDataFromVisit($visit['id'], $this->withDeletedStudies, $this->withDeletedSeries);
            foreach ($dicomStudies as $dicomStudy) {
                yield ['visit' => $visit, 'studies' => $dicomStudy];
            }
        }
    }

    private function getSeriesOrthancIdToSend(): array
    {
        $orthancIds = [];
        $visits = $this->visitRepository->getVisitsInStudy($this->studyName, false, false, true, null);
        foreach ($visits as $visit) {
            $dicoms = $this->dicomStudyRepository->getDicomsDataFromVisit($visit['id'], $this->withDeletedStudies, $this->withDeletedSeries);
            foreach ($dicoms as $dicomStudy) {
                $series = $dicomStudy['dicom_series'];
                foreach ($series as $serie) {
                    $orthancIds[] = $serie['orthanc_id'];

                }
            }
        }
        return $orthancIds;
    }


    private function registerPeer(string $name, string $url, string $username, string $password)
    {
        $this->orthancService->setOrthancServer(true);
        $this->orthancService->addPeer($name, $url, $username, $password);
    }

}
