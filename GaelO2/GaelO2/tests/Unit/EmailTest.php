<?php

namespace Tests\Feature;

use App\GaelO\Repositories\UserRepository;
use App\GaelO\Services\Mails\MailServices;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

use Illuminate\Support\Facades\App;
use App\Study;
use App\User;
use App\Center;
use App\Role;
use App\AffiliatedCenter;

class EmailTest extends TestCase
{
    use DatabaseMigrations {
        runDatabaseMigrations as baseRunDatabaseMigrations;
    }

    /**
     * Define hooks to migrate the database before and after each test.
     *
     * @return void
     */
    public function runDatabaseMigrations()
    {
        $this->baseRunDatabaseMigrations();
        $this->artisan('db:seed');
    }

    public function testGetInvestigatorEmails(){
        //SK A TESTER DANS USERREPOSITORY
        $userRepository = new UserRepository();
        $studies = factory(Study::class, 2)->create();
        $center = factory(Center::class)->create(['code'=>3]);
        $users = factory(User::class,10)->create(['job'=>'CRA']);

        $users->each(function ($user) use ($center, $studies)  {
            $studiesModel = $studies->first();
            $user->centers()->save(factory(AffiliatedCenter::class)->create(['user_id'=>$user->id, 'center_code'=>$center->code]));
            $user->roles()->save(factory(Role::class)->create(['user_id'=>$user->id, 'name'=>'Investigator', 'study_name'=>$studiesModel->name]));
        });

        $investigatorsEmails = $userRepository->getInvestigatorsStudyFromCenterEmails($studies->first()->name, 3, 'CRA');
        $this->assertEquals(10, sizeof($investigatorsEmails));

    }
}
