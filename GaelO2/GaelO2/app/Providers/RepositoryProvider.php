<?php

namespace App\Providers;

use App\GaelO\Interfaces\CenterRepositoryInterface;
use App\GaelO\Interfaces\CountryRepositoryInterface;
use App\GaelO\Interfaces\DocumentationRepositoryInterface;
use App\GaelO\Interfaces\DicomSeriesRepositoryInterface;
use App\GaelO\Interfaces\DicomStudyRepositoryInterface;
use App\GaelO\Interfaces\PatientRepositoryInterface;
use App\GaelO\Interfaces\ReviewRepositoryInterface;
use App\GaelO\Interfaces\ReviewStatusRepositoryInterface;
use App\GaelO\Interfaces\StudyRepositoryInterface;
use App\GaelO\Interfaces\TrackerRepositoryInterface;
use App\GaelO\Interfaces\UserRepositoryInterface;
use App\GaelO\Interfaces\VisitGroupRepositoryInterface;
use App\GaelO\Interfaces\VisitRepositoryInterface;
use App\GaelO\Interfaces\VisitTypeRepositoryInterface;
use App\GaelO\Repositories\CenterRepository;
use App\GaelO\Repositories\CountryRepository;
use App\GaelO\Repositories\DocumentationRepository;
use App\GaelO\Repositories\DicomSeriesRepository;
use App\GaelO\Repositories\DicomStudyRepository;
use App\GaelO\Repositories\PatientRepository;
use App\GaelO\Repositories\ReviewRepository;
use App\GaelO\Repositories\ReviewStatusRepository;
use App\GaelO\Repositories\StudyRepository;
use App\GaelO\Repositories\TrackerRepository;
use App\GaelO\Repositories\UserRepository;
use App\GaelO\Repositories\VisitGroupRepository;
use App\GaelO\Repositories\VisitRepository;
use App\GaelO\Repositories\VisitTypeRepository;
use Illuminate\Support\ServiceProvider;

class RepositoryProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind(UserRepositoryInterface::class, UserRepository::class);
        $this->app->bind(CenterRepositoryInterface::class, CenterRepository::class);
        $this->app->bind(CountryRepositoryInterface::class, CountryRepository::class);
        $this->app->bind(TrackerRepositoryInterface::class, TrackerRepository::class);
        $this->app->bind(DocumentationRepositoryInterface::class, DocumentationRepository::class);
        $this->app->bind(TrackerRepositoryInterface::class, TrackerRepository::class);
        $this->app->bind(UserRepositoryInterface::class, UserRepository::class);
        $this->app->bind(StudyRepositoryInterface::class, StudyRepository::class);
        $this->app->bind(VisitGroupRepositoryInterface::class, VisitGroupRepository::class);
        $this->app->bind(VisitTypeRepositoryInterface::class, VisitTypeRepository::class);
        $this->app->bind(VisitRepositoryInterface::class, VisitRepository::class);
        $this->app->bind(PatientRepositoryInterface::class, PatientRepository::class);
        $this->app->bind(DicomStudyRepositoryInterface::class, DicomStudyRepository::class);
        $this->app->bind(ReviewStatusRepositoryInterface::class, ReviewStatusRepository::class);
        $this->app->bind(DicomSeriesRepositoryInterface::class, DicomSeriesRepository::class);
        $this->app->bind(ReviewRepositoryInterface::class, ReviewRepository::class);
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}
