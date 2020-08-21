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
        $userRepository = new UserRepository();
        factory(Study::class)->create(['name'=>'test']);
        factory(Center::class)->create(['code'=>3]);
        factory(User::class)->create(['center_code'=>0])
        ->each(function ($user) {
            $user->centers()->save(factory(AffiliatedCenter::class)->create(['user_id'=>$user->id, 'center_code'=>3]));
        });;
        $investigatorsEmails = $userRepository->getInvestigatorsStudyFromCenterEmails('test', 3, null);
        dd($investigatorsEmails);
        $study = factory(Study::class,2)->create();
        $emailService  = App::Make('MailServices');

    }
}
