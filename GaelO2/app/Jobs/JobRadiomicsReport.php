<?php

namespace App\Jobs;

use App\GaelO\Constants\Enums\ProcessingMaskEnum;
use App\GaelO\Interfaces\Repositories\VisitRepositoryInterface;
use App\GaelO\Repositories\StudyRepository;
use App\GaelO\Services\GaelOClientService;
use App\GaelO\Services\MailServices;
use App\GaelO\Services\PdfServices;
use App\GaelO\Services\TmtvProcessingService;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\App;
use DateTime;
use Throwable;

class JobRadiomicsReport implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $failOnTimeout = true;
    public $timeout = 1200;
    public $tries = 1;
    private int $visitId;
    private ?int $behalfUserId;
    private ?array $destinatorEmails;
    private array $createdFiles = [];

    public function __construct(int $visitId, ?int $behalfUserId, ?array $destinatorEmails)
    {
        $this->onQueue('processing');
        $this->visitId = $visitId;
        $this->behalfUserId = $behalfUserId;
        $this->destinatorEmails = $destinatorEmails;
    }

    public function handle(
        TmtvProcessingService $tmtvProcessingService,
        VisitRepositoryInterface $visitRepositoryInterface,
        StudyRepository $studyRepository,
        MailServices $mailServices,
        PdfServices $pdfServices,
        GaelOClientService $gaeloClientService
    ): void {
        
        $visitEntity = $visitRepositoryInterface->getVisitContext($this->visitId);
        $studyName = $visitEntity['patient']['study_name'];
        $visitType = $visitEntity['visit_type']['name'];
        $patientCode = $visitEntity['patient']['code'];
        $creatorUserId = $visitEntity['creator_user_id'];
        $existingFiles = $visitEntity['sent_files'];
        $visitDate = new DateTime($visitEntity['visit_date']);
        $formattedVisitDate = $visitDate->format('m/d/Y');
        $tmtvProcessingService->loadPetAndCtSeriesOrthancIdsFromVisit($this->visitId);
        $rawMaskInference = $tmtvProcessingService->runInference();
        $fragmentedMask = $rawMaskInference->fragmentMask();

        $tmtvProcessingService->addCreatedRessource('masks', $fragmentedMask->getMaskId());
        $fragmentedMask41 =$fragmentedMask->thresholdMaskTo41();
        $tmtvProcessingService->addCreatedRessource('masks', $fragmentedMask41->getMaskId());
        $mipMask = $fragmentedMask41->createTepMaskMip();

        #Download .nii.gz Mask Dicom (not thrsholded)
        $maskdicom = $fragmentedMask41->getMaskAs(ProcessingMaskEnum::NIFTI, 'LPI');

        #get Stats
        $stats = $fragmentedMask41->getStatsOfMask();
        $statValue = [
            'TMTV 41%' => $stats['volume'],
            'Dmax (voxel)' => $stats['dmax'],
            'SUVmax' => $stats['suvmax'],
            'SUVmean'=> $stats['suvmean'],
            'SUVpeak' => $stats['suvpeak'],
            'TLG' => $stats['tlg'],
            'Dmax Bulk' => $stats['dmaxbulk'],
        ];

        if($this->destinatorEmails){
            $mailServices->sendRadiomicsReport(
                $studyName,
                $patientCode,
                $visitType,
                $formattedVisitDate,
                $mipMask,
                $statValue,
                $this->destinatorEmails
            );
        }

        $pdfReport  = $pdfServices->saveRadiomicsPdf(
            $studyName,
            $patientCode,
            $visitType,
            $formattedVisitDate,
            $statValue
        );

        //Send file to store using API as job worker may not access to the storage backend
        if ($this->behalfUserId) {
            $user = User::find($this->behalfUserId);
        } else {
            $studyEntity = $studyRepository->find($studyName);
            $user = User::where('email', $studyEntity->contactEmail)->sole();
        }

        $tokenResult = $user->createToken('GaelO')->plainTextToken;
        $gaeloClientService->loadUrl();
        $gaeloClientService->setAuthorizationToken($tokenResult);
        //In case of changed upload remove the last mask
        if (array_key_exists('tmtv41', $existingFiles)) {
            $gaeloClientService->deleteFileToVisit($studyName, $this->visitId, 'tmtv41');
        }
        if (array_key_exists('tmtvReport', $existingFiles)) {
            $gaeloClientService->deleteFileToVisit($studyName, $this->visitId, 'tmtvReport');
        }
        if (array_key_exists('mipSegmentation', $existingFiles)) {
            $gaeloClientService->deleteFileToVisit($studyName, $this->visitId, 'mipSegmentation');
        }
        //Store the file for review availability
        $gaeloClientService->createFileToVisit($studyName, $this->visitId, 'tmtv41', 'nii.gz', $maskdicom);
        $gaeloClientService->createFileToVisit($studyName, $this->visitId, 'tmtvReport', 'pdf', $pdfReport);
        $gaeloClientService->createFileToVisit($studyName, $this->visitId, 'mipSegmentation', 'gif', $mipMask);

        $tmtvProcessingService->deleteCreatedRessources();
    }

    public function failed(Throwable $exception)
    {
        $mailServices = App::make(MailServices::class);
        $mailServices->sendJobFailure('RadiomicsReport', ['visitId' => $this->visitId, 'behalfUserId' => $this->behalfUserId], $exception->getMessage());
        if (app()->bound('sentry')) {
            app('sentry')->captureException($exception);
        }
    }
}