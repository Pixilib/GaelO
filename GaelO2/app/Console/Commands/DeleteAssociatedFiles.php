<?php

namespace App\Console\Commands;

use App\Models\Study;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class DeleteAssociatedFiles extends Command
{

    private Study $study;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'gaelo:delete-associated-files {studyName : the study name to delete}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete a study storage folder from GaelO (hard delete)';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(
        Study $study
    ) {
        parent::__construct();
        $this->study = $study;
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
            $this->error('Wrong study name, terminating');
            return 0;
        }

        $studyEntity = $this->study->withTrashed()->find($studyName);

        //Check study have been soft delete before real deletion
        if ($studyEntity) {
            $this->error('Study is not hard deleted, terminating');
            return 0;
        }
        
        Storage::deleteDirectory($studyName);

        return 0;
    }
}
