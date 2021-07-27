<?php

namespace App\Console\Commands;

use App\GaelO\Interfaces\Repositories\StudyRepositoryInterface;
use App\Models\Documentation;
use App\Models\Role;
use App\Models\Study;
use App\Models\Tracker;
use App\Models\Visit;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class DeleteStudy extends Command
{

    private Study $study;
    private Visit $visit;
    private Tracker $tracker;
    private Documentation $documentation;
    private Role $role;
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
    public function __construct(Study $study, Visit $visit, Role $role, Tracker $tracker, Documentation $documentation)
    {
        parent::__construct();
        $this->study = $study;
        $this->visit = $visit;
        $this->tracker = $tracker;
        $this->documentation = $documentation;
        $this->role = $role;

    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {

        $studyName = $this->argument('studyName');
        $studyNameConfirmation = $this->ask('Warning : Please confirm study Name');
        if ($studyName !== $studyNameConfirmation) {
            $this->error('Wrong study name!');
            return 0;
        }

        $studyEntity = $this->study->findOrFail($studyName);

        /*
        if( ! $studyEntity->deleted ){
            $this->error('Study is not soft deleted, terminating');
            return 0;
        }
        */

        if ($this->confirm('Warning : This CANNOT be undone, do you wish to continue?')) {



            $dicomSeries = [];


            $this->deleteDocumentation($studyEntity->name);
            $this->deleteRoles($studyEntity->name);
            $this->deleteTracker($studyEntity->name);
            $visits = $this->getVisitsOfStudy($studyEntity->name);

            $this->table(

                ['seriesOrthancID'],
                $dicomSeries
            );


            /*
            $studyEntity->visitGroups()->visitTypes()->withTrashed()->visits()->withTrashed()->dicomStudies()->withTrashed()->dicomSeries()->withTrashed()->forceDelete();
            $studyEntity->visitGroups()->visitTypes()->withTrashed()->visits()->withTrashed()->dicomStudies()->withTrashed()->forceDelete();
            $studyEntity->visitGroups()->visitTypes()->withTrashed()->visits()->withTrashed()->reviews()->forceDelete();
            $studyEntity->visitGroups()->visitTypes()->withTrashed()->visits()->withTrashed()->reviewStatus()->forceDelete();
            $studyEntity->visitGroups()->visitTypes()->withTrashed()->visits()->forceDelete();
            $studyEntity->visitGroups()->visitTypes()->forceDelete();
            $studyEntity->visitGroups()->forceDelete();
            $studyEntity->patients()->forceDelete();
            $studyEntity->forceDelete();
            */
            $this->info('The command was successful!');
        }

        return 0;
    }

    private function getVisitsOfStudy(string $studyName)
    {

        return $this->visit->withTrashed()->with(['visitType', 'patient'])
            ->whereHas('visitType', function ($query) use ($studyName) {
                $query->whereHas('visitGroup', function ($query) use ($studyName) {
                    $query->where('study_name', $studyName);
                });
            })->get();
    }

    private function deleteDocumentation(string $studyName){
        $this->documentation->where('study_name', $studyName)->withTrashed()->forceDelete();
    }

    private function deleteRoles(string $studyName){
        $this->role->where('study_name', $studyName )->delete();
    }

    private function deleteTracker(string $studyName){
        $this->tracker->where('study_name', $studyName )->delete();
    }
}
