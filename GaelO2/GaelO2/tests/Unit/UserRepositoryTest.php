<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

use App\Study;
use App\User;
use App\Center;
use App\Role;
use App\CenterUser;
use App\GaelO\Repositories\UserRepository;

class UserRepositoryTest extends TestCase
{
    use DatabaseMigrations {
        runDatabaseMigrations as baseRunDatabaseMigrations;
    }

    use RefreshDatabase;

    public function runDatabaseMigrations()
    {
        $this->baseRunDatabaseMigrations();
        $this->artisan('db:seed');
    }

    /**
     * Test email selection of investigator's study having a same main/affiliated center
     */
    public function testGetInvestigatorStudyEmailsWithJobs(){
        $userRepository = new UserRepository();
        //Create 2 random studies
        $studies = factory(Study::class, 2)->create();
        //Create one center '3' and one center '5'
        $center = factory(Center::class)->create(['code'=>3]);
        factory(Center::class)->create(['code'=>5]);
        //Create 10 user job 'CRA' and 15 job 'Supervision'
        $usersCRA = factory(User::class,10)->create(['job'=>'CRA']);
        $userSupervision = factory(User::class,15)->create(['job'=>'Supervision']);

        //For CRA users assing center 3 with role investigator in first study
        $usersCRA->each(function ($user) use ($center, $studies)  {
            $studiesModel = $studies->first();
            factory(CenterUser::class)->create(['user_id'=>$user->id, 'center_code'=>$center->code]);
            factory(Role::class)->create(['user_id'=>$user->id, 'name'=>'Investigator', 'study_name'=>$studiesModel->name]);
        });

        //For Supervision user assing center 5 with role investigator in last study
        $userSupervision->each(function ($user) use ($studies)  {
            $studiesModel = $studies->last();
            factory(CenterUser::class)->create(['user_id'=>$user->id, 'center_code'=>5]);
            factory(Role::class)->create(['user_id'=>$user->id, 'name'=>'Investigator', 'study_name'=>$studiesModel->name]);
        });

        //Querying investigator from first study and center 3 with CRA role should return 10 results
        $investigatorsEmails = $userRepository->getInvestigatorsStudyFromCenterEmails($studies->first()->name, 3, 'CRA');
        $this->assertEquals(10, sizeof($investigatorsEmails));

        //Querying investigator from last study and center 3 with CRA role should return 0 results
        $investigatorsEmails2 = $userRepository->getInvestigatorsStudyFromCenterEmails($studies->last()->name, 3, 'CRA');
        $this->assertEquals(0, sizeof($investigatorsEmails2));

        //Querying investigator from last study and center 5 with Supervision role should return 15 results
        $investigatorsEmails3 = $userRepository->getInvestigatorsStudyFromCenterEmails($studies->last()->name, 5, 'Supervision');
        $this->assertEquals(15, sizeof($investigatorsEmails3));

        //Querying investigator from first study and center 3 with Radiologist role should return 0 results
        $investigatorsEmails4 = $userRepository->getInvestigatorsStudyFromCenterEmails($studies->first()->name, 3, 'Radiologist');
        $this->assertEquals(0, sizeof($investigatorsEmails4));

        //Results of user query should be different
        $commonEmails = array_intersect($investigatorsEmails, $investigatorsEmails2);
        $this->assertEquals(0, sizeof($commonEmails));


    }

    /**
     * Test mail selection acccording to Role in study
     */
    public function testGetMailsByRoleStudy(){
        $userRepository = new UserRepository();
        $studies = factory(Study::class, 2)->create();
        $users = factory(User::class,10)->create();
        $users2 = factory(User::class,20)->create();

        $users->each(function ($user) use ($studies)  {
            $studiesModel = $studies->first();
            $user->roles()->save(factory(Role::class)->create(['user_id'=>$user->id, 'name'=>'Investigator', 'study_name'=>$studiesModel->name]));
        });

        $users2->each(function ($user) use ($studies)  {
            $studiesModel = $studies->first();
            $user->roles()->save(factory(Role::class)->create(['user_id'=>$user->id, 'name'=>'Supervisor', 'study_name'=>$studiesModel->name]));
        });

        //We should have 10 investigators, 20 supervisors, 0 monitor in this study
        $investigatorsEmails = $userRepository->getUsersEmailsByRolesInStudy($studies->first()->name, 'Investigator');
        $this->assertEquals(10, sizeof($investigatorsEmails));
        $investigatorsEmails2 = $userRepository->getUsersEmailsByRolesInStudy($studies->first()->name, 'Supervisor');
        $this->assertEquals(20, sizeof($investigatorsEmails2));
        $investigatorsEmails3 = $userRepository->getUsersEmailsByRolesInStudy($studies->first()->name, 'Monitor');
        $this->assertEquals(0, sizeof($investigatorsEmails3));

        //We should have 0 investigators, in the other study
        $investigatorsEmails4 = $userRepository->getUsersEmailsByRolesInStudy($studies->last()->name, 'Investigator');
        $this->assertEquals(0, sizeof($investigatorsEmails4));


        //Results of user query role should be different
        $commonEmails = array_intersect($investigatorsEmails, $investigatorsEmails2);
        $this->assertEquals(0, sizeof($commonEmails));


    }
}
