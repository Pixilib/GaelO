<?php

namespace App\Providers;

use App\GaelO\Interfaces\Repositories\CenterRepositoryInterface;
use App\GaelO\Interfaces\Repositories\CountryRepositoryInterface;
use App\GaelO\Interfaces\Repositories\DocumentationRepositoryInterface;
use App\GaelO\Interfaces\Repositories\DicomSeriesRepositoryInterface;
use App\GaelO\Interfaces\Repositories\DicomStudyRepositoryInterface;
use App\GaelO\Interfaces\Repositories\PatientRepositoryInterface;
use App\GaelO\Interfaces\Repositories\ReviewRepositoryInterface;
use App\GaelO\Interfaces\Repositories\ReviewStatusRepositoryInterface;
use App\GaelO\Interfaces\Repositories\StudyRepositoryInterface;
use App\GaelO\Interfaces\Repositories\TrackerRepositoryInterface;
use App\GaelO\Interfaces\Repositories\UserRepositoryInterface;
use App\GaelO\Interfaces\Repositories\VisitGroupRepositoryInterface;
use App\GaelO\Interfaces\Repositories\VisitRepositoryInterface;
use App\GaelO\Interfaces\Repositories\VisitTypeRepositoryInterface;
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
