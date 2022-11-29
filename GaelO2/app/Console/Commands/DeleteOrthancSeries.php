<?php

namespace App\Console\Commands;

use App\GaelO\Services\OrthancService;
use App\Models\DicomSeries;
use Exception;
use Illuminate\Console\Command;
use Throwable;

class DeleteOrthancSeries extends Command
{


    private DicomSeries $dicomSeries;
    private OrthancService $orthancService;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'gaelo:delete-dicom-series {seriesOrthancIds*}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete a dicom series from Orthanc (hard delete)';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(
        DicomSeries $dicomSeries,
        OrthancService $orthancService
    ) {
        parent::__construct();
        $this->dicomSeries = $dicomSeries;
        $this->orthancService = $orthancService;
        $this->orthancService->setOrthancServer(true);
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {

        if (!$this->confirm('Warning : This CANNOT be undone, do you wish to continue?')) {
            $this->info('Aborted');
            return 0;
        }

        $seriesOrthancIds = $this->argument('seriesOrthancIds');

        $results = [];
        foreach ($seriesOrthancIds as $seriesOrthancId) {

            $result = [];
            $result['id'] = $seriesOrthancId;
            try {
                $records = $this->dicomSeries->where('orthanc_id', $seriesOrthancId)->count();
                if ($records !== 0) throw new Exception('Yet referenced series');
                $this->orthancService->deleteFromOrthanc('series', $seriesOrthancId);
                $result['status'] = 'Success';
            } catch (Throwable $e) {
                $result['status'] = 'Failure';
                $result['reason'] = $e->getMessage();
            }
            $results[] = $result;
        }

        $this->table(
            ['id', 'status', 'reason'],
            $results
        );

        return 0;
    }
}
