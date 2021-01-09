<?php

namespace Tests\Unit;

use App\GaelO\Constants\Constants;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

use App\Models\Study;
use App\Models\User;
use App\Models\Center;
use App\Models\Role;
use App\Models\CenterUser;
use App\GaelO\Repositories\UserRepository;

class UserRepositoryTest extends TestCase
{
    private UserRepository $userRepository;
    use DatabaseMigrations {
        runDatabaseMigrations as baseRunDatabaseMigrations;
    }

    use RefreshDatabase;

    public function runDatabaseMigrations()
    {
        $this->baseRunDatabaseMigrations();
        $this->artisan('db:seed');
    }


    protected function setUp(): void
    {
        parent::setUp();

        $this->userRepository = new UserRepository(new User(), new Role(), new CenterUser());

        //Create 2 random studies
        $this->studies = Study::factory()->count(2)->create();
        //Create one center '3' and one center '5'
        $this->center3 = Center::factory()->code(3)->create();
        $this->center5 = Center::factory()->code(5)->create();
    }

    public function testCreateUser()
    {

        $createdEntity = $this->userRepository->createUser(
            'salimKanoun',
            'Kanoun',
            'Salim',
            Constants::USER_STATUS_UNCONFIRMED,
            'salim.kanoun@gmail.com',
            '0600000000',
            false,
            0,
            Constants::USER_JOB_SUPERVISION,
            null,
            null,
            null,
            'testPassword',
            null,
            now()
        );

        $this->assertIsArray($createdEntity);
        $this->assertNull($createdEntity['last_password_update']);

        return $createdEntity;
    }

    /**
     * @depends testCreateUser
     */
    public function testUpdateUser(array $existingEntity)
    {

        $userToModify = User::factory()->status(Constants::USER_STATUS_ACTIVATED)->job(Constants::USER_JOB_SUPERVISION)->create();

        $this->userRepository->updateUser(
            $userToModify->id,
            'newUsername',
            'newLastName',
            'newFirstName',
            Constants::USER_STATUS_UNCONFIRMED,
            'new@email.com',
            null,
            !$userToModify->administrator,
            $this->center3->code,
            Constants::USER_JOB_CRA,
            'a',
            'b',
            'c',
            null
        );

        $updatedEntity = $this->userRepository->find($existingEntity['id']);

        $this->assertNotEquals($updatedEntity['username'], $userToModify['username']);
        $this->assertNotEquals($updatedEntity['firstname'], $userToModify['firstname']);
        $this->assertNotEquals($updatedEntity['lastname'], $userToModify['lastname']);
        $this->assertNotEquals($updatedEntity['email'], $userToModify['email']);
        $this->assertNotEquals($updatedEntity['phone'], $userToModify['phone']);
        $this->assertNotEquals($updatedEntity['status'], $userToModify['status']);
        $this->assertNotEquals($updatedEntity['administrator'], $userToModify['administrator']);
        $this->assertNotEquals($updatedEntity['center_code'], $userToModify['center_code']);
        $this->assertNotEquals($updatedEntity['job'], $userToModify['job']);
        $this->assertNotEquals($updatedEntity['updated_at'], $userToModify['updated_at']);
        $this->assertNotEquals($updatedEntity['password_temporary'], $userToModify['password_temporary']);

        //SK ICI PROBLEME CAR LE USECASE ACCEDE DIRECTREMENT AU MODIFY
        //AJOUTER LES FONCTION D UPDATE POUR TOUS LES CHAMP POUR LE REPOSITORY
        $this->assertEquals($updatedEntity['password'], $userToModify['password']);
    }

    public function testGetUserByUsername()
    {
        //Test if user is not deleted
        $user = User::factory()->status(Constants::USER_STATUS_ACTIVATED)->job(Constants::USER_JOB_SUPERVISION)->create();
        $userEntity = $this->userRepository->getUserByUsername($user->username, false);
        $this->assertIsArray($userEntity);
        $this->assertNull($userEntity['deleted_at']);

        //Test if user is softdeleted
        $user->delete();
        $userEntity = $this->userRepository->getUserByUsername($user->username, true);
        $this->assertIsArray($userEntity);
        $this->assertNotNull($userEntity['deleted_at']);
    }

    public function testIsExistingUsername()
    {

        $user = User::factory()->create();
        //Username test even if soft deleted user
        $user->delete();

        $testExisting = $this->userRepository->isExistingUsername($user->username);
        $testNotExisting = $this->userRepository->isExistingUsername('randomUsername');

        $this->assertTrue($testExisting);
        $this->assertFalse($testNotExisting);
    }

    public function testIsExistingEmail()
    {

        $user = User::factory()->create();
        //Username test even if soft deleted user
        $user->delete();

        $testExisting = $this->userRepository->isExistingEmail($user->email);
        $testNotExisting = $this->userRepository->isExistingEmail('notEmail');

        $this->assertTrue($testExisting);
        $this->assertFalse($testNotExisting);
    }

    public function testGetAdministratorsEmails()
    {

        $userAdmin = User::factory()->status(Constants::USER_STATUS_ACTIVATED)->administrator()->count(4)->create();
        User::factory()->count(8)->create();

        //Deleted user should not outputed
        $userAdmin->first()->delete();

        $adminEmails = $this->userRepository->getAdministratorsEmails();
        //Assert 4 because of the default admin account

        $this->assertEquals(4, sizeof($adminEmails));
    }

    /**
     * Test email selection of investigator's study having a same main/affiliated center
     */
    public function testGetInvestigatorStudyEmails()
    {
        $usersCRA = User::factory()->job(Constants::USER_JOB_CRA)->status(Constants::USER_STATUS_ACTIVATED)->count(10)->create();

        $userSupervision = User::factory()->job(Constants::USER_JOB_SUPERVISION)->status(Constants::USER_STATUS_ACTIVATED)->count(15)->create();

        $study1 = $this->studies->first();
        $center3 = $this->center3;

        //For CRA users assing center 3 with role investigator in first study
        $usersCRA->each(function ($user) use ($center3, $study1) {
            CenterUser::factory()->centerCode($center3->code)->userId($user->id)->create();
            Role::factory()->userId($user->id)->studyName($study1->name)->roleName(Constants::ROLE_INVESTIGATOR)->create();
        });

        $study2 = $this->studies->last();
        $center5 = $this->center5;
        //For Supervision user assing center 5 with role investigator in last study
        $userSupervision->each(function ($user) use ($center5, $study2) {
            CenterUser::factory()->centerCode($center5->code)->userId($user->id)->create();
            Role::factory()->userId($user->id)->studyName($study2->name)->roleName(Constants::ROLE_INVESTIGATOR)->create();
        });

        //Querying investigator from first study and center 3 with CRA role should return 10 results
        $investigatorsEmails = $this->userRepository->getInvestigatorsStudyFromCenterEmails($study1->name, 3, Constants::USER_JOB_CRA);
        $this->assertEquals(10, sizeof($investigatorsEmails));

        //Querying investigator from last study and center 3 with CRA role should return 0 results
        $investigatorsEmails2 = $this->userRepository->getInvestigatorsStudyFromCenterEmails($study2->name, 3, Constants::USER_JOB_CRA);
        $this->assertEquals(0, sizeof($investigatorsEmails2));

        //Querying investigator from last study and center 5 with Supervision role should return 15 results
        $investigatorsEmails3 = $this->userRepository->getInvestigatorsStudyFromCenterEmails($study2->name, 5, Constants::USER_JOB_SUPERVISION);
        $this->assertEquals(15, sizeof($investigatorsEmails3));

        //Querying investigator from first study and center 3 with Radiologist role should return 0 results
        $investigatorsEmails4 = $this->userRepository->getInvestigatorsStudyFromCenterEmails($study2->name, 3, Constants::USER_JOB_RADIOLOGIST);
        $this->assertEquals(0, sizeof($investigatorsEmails4));

        //Results of user query should be different
        $commonEmails = array_intersect($investigatorsEmails, $investigatorsEmails2);
        $this->assertEquals(0, sizeof($commonEmails));
    }

    /**
     * Test mail selection acccording to Role in study
     */
    public function testGetMailsByRoleStudy()
    {
        $users = User::factory()->count(10)->status(Constants::USER_STATUS_ACTIVATED)->create();
        $users2 = User::factory()->count(20)->status(Constants::USER_STATUS_ACTIVATED)->create();

        $study1 = $this->studies->first();
        $study2 = $this->studies->last();
        $users->each(function ($user) use ($study1) {
            Role::factory()->userId($user->id)->studyName($study1->name)->roleName(Constants::ROLE_INVESTIGATOR)->create();
        });

        $users2->each(function ($user) use ($study1) {
            Role::factory()->userId($user->id)->studyName($study1->name)->roleName(Constants::ROLE_SUPERVISOR)->create();
        });

        //We should have 10 investigators, 20 supervisors, 0 monitor in this study
        $investigatorsEmails = $this->userRepository->getUsersEmailsByRolesInStudy($study1->name, Constants::ROLE_INVESTIGATOR);
        $this->assertEquals(10, sizeof($investigatorsEmails));
        $investigatorsEmails2 = $this->userRepository->getUsersEmailsByRolesInStudy($study1->name, Constants::ROLE_SUPERVISOR);
        $this->assertEquals(20, sizeof($investigatorsEmails2));
        $investigatorsEmails3 = $this->userRepository->getUsersEmailsByRolesInStudy($study1->name, Constants::ROLE_MONITOR);
        $this->assertEquals(0, sizeof($investigatorsEmails3));

        //We should have 0 investigators, in the other study
        $investigatorsEmails4 = $this->userRepository->getUsersEmailsByRolesInStudy($study2->name, Constants::ROLE_INVESTIGATOR);
        $this->assertEquals(0, sizeof($investigatorsEmails4));

        //Results of user query role should be different
        $commonEmails = array_intersect($investigatorsEmails, $investigatorsEmails2);
        $this->assertEquals(0, sizeof($commonEmails));
    }

    public function testGetUsersAffiliatedToCenter(){

    }
}
