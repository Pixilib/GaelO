<?php

namespace App\Console\Commands;

use App\GaelO\Constants\Enums\JobEnum;
use App\GaelO\Repositories\DicomStudyRepository;
use App\GaelO\Repositories\VisitRepository;
use App\GaelO\Services\OrthancService;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

enum Desinations: string
{
    case ORTHANCPEER = "orthanc-peer";
    case ORTHANCACCELERATOR = "orthanc-accelerator";
    case FTP = "ftp";
    case SFTP = "sftp";
    case DICOMWEB = "dicom-web";
    case DICOMNODE = "dicom-node";
    case S3 = "s3";
    case AZURESTORAGE = "azure-storage";
}

class ExportDicom extends Command
{

    private VisitRepository $visitRepository;
    private DicomStudyRepository $dicomStudyRepository;
    private OrthancService $orthancService;
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

        $destinatorName = $this->ask('Destinator Name : (ex: sanofi)');
        $destinationType = $this->choice('Destination Type', array_column(Desinations::cases(), 'value'));
        $orthancAddress = $this->ask('Orthanc URL : (ex: http://www.pixilib.fr:8042) ');
        $orthancUsername = $this->ask('Orthanc Username: ');
        $orthancPassword = $this->secret('Orthanc Password: ');
        $withDeletedStudies = false;
        $withDeletedSeries = false;
        $studyName = $this->ask('Study to export :');

        if ($this->confirm('Includes deleted studies ?')) {
            $withDeletedStudies = true;
        }
        if ($this->confirm('Includes deleted series ?')) {
            $withDeletedSeries = true;
        }

        $this->registerPeer($destinatorName, $orthancAddress, $orthancUsername, $orthancPassword);

        $visits = $visitRepository->getVisitsInStudy($studyName, false, false, true, null);
        foreach ($visits as $visit) {
            $dicoms = $dicomStudyRepository->getDicomsDataFromVisit($visit['id'], $withDeletedStudies, $withDeletedSeries);
            foreach ($dicoms as $dicomStudy) {
                $series = $dicomStudy['dicom_series'];
                foreach ($series as $serie) {
                    $orthancId = $series['orthanc_id'];
                    $this->sendRessourceToOrthancPeer($orthancId, $destinatorName);
                }
            }
        }

        $orthancService->deletePeer($destinatorName);
        return 0;
    }

    private function registerPeer(string $name, string $url, string $username, string $password) {
        $this->orthancService->setOrthancServer(true);
        $this->orthancService->addPeer($name, $url, $username, $password);
    }

    private function sendRessourceToOrthancPeer(string $orthancId, string $peerName){
        $this->orthancService->sendToPeer($peerName, [$orthancId], true);
    }

}
