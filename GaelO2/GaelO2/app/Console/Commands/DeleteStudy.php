<?php

namespace App\Console\Commands;

use App\GaelO\Interfaces\Repositories\StudyRepositoryInterface;
use App\Models\Study;
use App\Models\Tracker;
use Illuminate\Console\Command;

class DeleteStudy extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'study:delete {studyName : the study name to delete}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete a Study from GaelO (hard delete)';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(Study $study, Tracker $tracker)
    {

        $studyName = $this->argument('studyName');
        $studyNameConfirmation = $this->ask('Warning : Please confirm study Name');
        if($studyName !== $studyNameConfirmation) {
            $this->error('Wrong study name!');
            return 0;
        }

        $studyEntity = $study->findOrFail($studyName);

        /*
        if( ! $studyEntity->deleted ){
            $this->error('Study is not soft deleted, terminating');
            return 0;
        }
        */

        if ($this->confirm('Warning : This CANNOT be undone, do you wish to continue?')) {


            $dicomSeries = $studyEntity->visitGroups->visitTypes->visits->withTrashed()->dicomStudies->withTrashed()->dicomSeries->withTrashed()->get()->pluck('series_uid');

            $this->table(

                ['seriesOrthancID'],
                $dicomSeries
            );

            return
            $tracker->where('study_name', '=' ,  $studyName)->withTrashed()->forceDelete();
            $studyEntity->documentations()->withTrashed()->forceDelete();
            $studyEntity->roles()->withTrashed()->forceDelete();
            $studyEntity->visitGroups()->visitTypes()->withTrashed()->visits()->withTrashed()->dicomStudies()->withTrashed()->dicomSeries()->withTrashed()->forceDelete();
            $studyEntity->visitGroups()->visitTypes()->withTrashed()->visits()->withTrashed()->dicomStudies()->withTrashed()->forceDelete();
            $studyEntity->visitGroups()->visitTypes()->withTrashed()->visits()->withTrashed()->reviews()->forceDelete();
            $studyEntity->visitGroups()->visitTypes()->withTrashed()->visits()->withTrashed()->reviewStatus()->forceDelete();
            $studyEntity->visitGroups()->visitTypes()->withTrashed()->visits()->forceDelete();
            $studyEntity->visitGroups()->visitTypes()->forceDelete();
            $studyEntity->visitGroups()->forceDelete();
            $studyEntity->patients()->forceDelete();
            $studyEntity->forceDelete();
            $this->info('The command was successful!');
        }

        return 0;
    }
}
