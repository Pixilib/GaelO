<?php

namespace Tests\Unit\TestRepositories;

use App\GaelO\Constants\Constants;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

use App\Models\Study;
use App\Models\User;
use App\Models\Center;
use App\Models\Role;
use App\Models\CenterUser;
use App\GaelO\Repositories\UserRepository;
use Illuminate\Support\Facades\App;

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

        $this->userRepository = App::make(UserRepository::class);

        //Create 2 random studies
        $this->studies = Study::factory()->count(2)->create();
        //Create one center '3' and one center '5'
        $this->center3 = Center::factory()->code(3)->create();
        $this->center5 = Center::factory()->code(5)->create();
    }

    public function testCreateUser()
    {

        $createdEntity = $this->userRepository->createUser(
            'Kanoun',
            'Salim',
            'salim.kanoun@gmail.com',
            '0600000000',
            false,
            0,
            Constants::USER_JOB_SUPERVISION,
            null,
            null,
            null
        );

        $this->assertIsArray($createdEntity);

        return $createdEntity;
    }

    /**
     * @depends testCreateUser
     */
    public function testUpdateUser(array $existingEntity)
    {

        $userToModify = User::factory()->job(Constants::USER_JOB_SUPERVISION)->create();

        $this->userRepository->updateUser(
            $userToModify->id,
            'newLastName',
            'newFirstName',
            'new@email.com',
            null,
            !$userToModify->administrator,
            $this->center3->code,
            Constants::USER_JOB_CRA,
            'a',
            'b',
            'c',
            '1.0.1',
            false
        );

        $updatedEntity = $this->userRepository->find($existingEntity['id']);

        $this->assertNotEquals($updatedEntity['firstname'], $userToModify['firstname']);
        $this->assertNotEquals($updatedEntity['lastname'], $userToModify['lastname']);
        $this->assertNotEquals($updatedEntity['email'], $userToModify['email']);
        $this->assertNotEquals($updatedEntity['phone'], $userToModify['phone']);
        $this->assertNotEquals($updatedEntity['administrator'], $userToModify['administrator']);
        $this->assertNotEquals($updatedEntity['center_code'], $userToModify['center_code']);
        $this->assertNotEquals($updatedEntity['job'], $userToModify['job']);
        $this->assertNotEquals($updatedEntity['updated_at'], $userToModify['updated_at']);
        $this->assertNotEquals($updatedEntity['onboarding_version'], $userToModify['onboarding_version']);
        $this->assertEquals($updatedEntity['password'], $userToModify['password']);
    }

    public function testUpdateUserPassword(){

        $user = User::factory()->create();
        $this->userRepository->updateUserPassword($user['id'], 'newPassword');

        $updatedUser = User::find($user->id);
        $this->assertNotEquals($user->password, $updatedUser->password);

    }


    public function testUpdateUserAttempts(){

        $user = User::factory()->create();
        $this->userRepository->updateUserAttempts($user['id'], 99);

        $updatedUser = User::find($user->id);
        $this->assertEquals(99, $updatedUser->attempts);

    }

    public function testResetAttemptsAndUpdateLastConnexion(){

        $user = User::factory()->attempts(5)->create();
        $this->userRepository->resetAttemptsAndUpdateLastConnexion($user['id']);

        $updatedUser = User::find($user->id);
        $this->assertEquals(0, $updatedUser->attempts);
        $this->assertNotEquals($user->last_connection, $updatedUser->last_connection);


    }

    public function testGetUserByUsername()
    {
        //Test if user is not deleted
        $user = User::factory()->job(Constants::USER_JOB_SUPERVISION)->create();
        $userEntity = $this->userRepository->getUserByEmail($user->email, false);
        $this->assertIsArray($userEntity);
        $this->assertNull($userEntity['deleted_at']);

        //Test if user is softdeleted
        $user->delete();
        $userEntity = $this->userRepository->getUserByEmail($user->email, true);
        $this->assertIsArray($userEntity);
        $this->assertNotNull($userEntity['deleted_at']);
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

        $userAdmin = User::factory()->administrator()->count(4)->create();
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
        $usersCRA = User::factory()->job(Constants::USER_JOB_CRA)->count(10)->create();

        $userSupervision = User::factory()->job(Constants::USER_JOB_SUPERVISION)->count(15)->create();

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
        $investigatorsEmails = $this->userRepository->getInvestigatorsEmailsFromStudyFromCenter($study1->name, 3, Constants::USER_JOB_CRA);
        $this->assertEquals(10, sizeof($investigatorsEmails));

        //Querying investigator from last study and center 3 with CRA role should return 0 results
        $investigatorsEmails2 = $this->userRepository->getInvestigatorsEmailsFromStudyFromCenter($study2->name, 3, Constants::USER_JOB_CRA);
        $this->assertEquals(0, sizeof($investigatorsEmails2));

        //Querying investigator from last study and center 5 with Supervision role should return 15 results
        $investigatorsEmails3 = $this->userRepository->getInvestigatorsEmailsFromStudyFromCenter($study2->name, 5, Constants::USER_JOB_SUPERVISION);
        $this->assertEquals(15, sizeof($investigatorsEmails3));

        //Querying investigator from first study and center 3 with Radiologist role should return 0 results
        $investigatorsEmails4 = $this->userRepository->getInvestigatorsEmailsFromStudyFromCenter($study2->name, 3, Constants::USER_JOB_RADIOLOGIST);
        $this->assertEquals(0, sizeof($investigatorsEmails4));

        //Results of user query should be different
        $commonEmails = array_intersect($investigatorsEmails, $investigatorsEmails2);
        $this->assertEquals(0, sizeof($commonEmails));
    }

    public function testGetUserByRoleStudy(){

        $users = User::factory()->count(10)->create();

        $study1 = $this->studies->first();
        $users->each(function ($user) use ($study1) {
            Role::factory()->userId($user->id)->studyName($study1->name)->roleName(Constants::ROLE_INVESTIGATOR)->create();
        });

        $users = $this->userRepository->getUsersByRolesInStudy($study1->name, Constants::ROLE_INVESTIGATOR);
        $this->assertEquals(10, sizeof($users));
    }

    /**
     * Test mail selection acccording to Role in study
     */
    public function testGetMailsByRoleStudy()
    {
        $users = User::factory()->count(10)->create();
        $users2 = User::factory()->count(20)->create();

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

    public function testGetStudiesWithRoleForUser(){

        $user = User::factory()->create();

        $study1Name = $this->studies->first()->name;
        $study2Name = $this->studies->last()->name;

        Role::factory()->userId($user->id)->roleName(Constants::ROLE_INVESTIGATOR)->studyName($study1Name)->create();
        Role::factory()->userId($user->id)->roleName(Constants::ROLE_SUPERVISOR)->studyName($study1Name)->create();
        Role::factory()->userId($user->id)->roleName(Constants::ROLE_INVESTIGATOR)->studyName($study2Name)->create();

        $studies = $this->userRepository->getStudiesOfUser($user->id);
        $this->assertEquals(2, sizeof($studies));

        $this->studies->first()->delete();
        //Deleted study should not appear anymore
        $studies = $this->userRepository->getStudiesOfUser($user->id);
        $this->assertEquals(1, sizeof($studies));
    }

    public function testGetUserRoles(){

        $user = User::factory()->create();

        $study1Name = $this->studies->first()->name;
        $study2Name = $this->studies->last()->name;

        Role::factory()->userId($user->id)->roleName(Constants::ROLE_INVESTIGATOR)->studyName($study1Name)->create();
        Role::factory()->userId($user->id)->roleName(Constants::ROLE_SUPERVISOR)->studyName($study1Name)->create();
        Role::factory()->userId($user->id)->roleName(Constants::ROLE_INVESTIGATOR)->studyName($study2Name)->create();

        $rolesAnswer = $this->userRepository->getUsersRoles($user->id);
        $this->assertEquals(1, sizeof($rolesAnswer[$study2Name]));
        $this->assertEquals(2, sizeof($rolesAnswer[$study1Name]));

    }

    public function testGetUserRolesInStudy(){

        $user = User::factory()->create();

        $study1Name = $this->studies->first()->name;
        $study2Name = $this->studies->last()->name;

        Role::factory()->userId($user->id)->roleName(Constants::ROLE_INVESTIGATOR)->studyName($study1Name)->create();
        Role::factory()->userId($user->id)->roleName(Constants::ROLE_SUPERVISOR)->studyName($study1Name)->create();
        Role::factory()->userId($user->id)->roleName(Constants::ROLE_INVESTIGATOR)->studyName($study2Name)->create();

        $roles = $this->userRepository->getUsersRolesInStudy($user->id, $study1Name);

        $this->assertTrue(in_array(Constants::ROLE_INVESTIGATOR, $roles));
        $this->assertTrue(in_array(Constants::ROLE_SUPERVISOR, $roles));

    }

    public function testGetUseRoleInStudy(){
        $role = Role::factory()->roleName(Constants::ROLE_INVESTIGATOR)->create();
        $entity = $this->userRepository->getUserRoleInStudy($role->user_id, $role->study_name, $role->name );
        $this->assertArrayHasKey('validated_documentation_version', $entity);
    }

    public function testUpdateValidatedDocumentationVersion(){
        $role = Role::factory()->roleName(Constants::ROLE_INVESTIGATOR)->create();
        $this->userRepository->updateValidatedDocumentationVersion($role->user_id, $role->study_name, $role->name, '3.0.0' );
        $updatedRole = Role::where('user_id',  $role->user_id)->where('study_name', $role->study_name)->where('name', $role->name)->sole();
        $this->assertEquals('3.0.0', $updatedRole->validated_documentation_version);
    }

    public function testAddUserRoleInStudy(){

        $user = User::factory()->create();
        $this->userRepository->addUserRoleInStudy($user->id, $this->studies->first()->name, Constants::ROLE_INVESTIGATOR);
        $newRolesRecords = User::find($user->id)->roles()->get()->toArray();
        $this->assertEquals(1, sizeof($newRolesRecords));

        return $user;
    }

    public function testDeleteRoleForUser(){

        $user = User::factory()->create();
        Role::factory()->userId($user->id)->roleName(Constants::ROLE_INVESTIGATOR)->studyName($this->studies->first()->name)->create();
        $this->userRepository->deleteRoleForUser($user->id, $this->studies->first()->name, Constants::ROLE_INVESTIGATOR);

        $newRolesRecords = User::find($user->id)->roles()->get()->toArray();
        $this->assertEquals(0, sizeof($newRolesRecords));
    }

    public function testAddAffiliatedCenter(){

        $user = User::factory()->create();
        $this->userRepository->addAffiliatedCenter($user->id, $this->center3->code);

        $affiliatedCenters = User::find($user->id)->affiliatedCenters()->get()->toArray();
        $this->assertEquals(3, $affiliatedCenters[0]['code']);
    }

    public function testDeleteAffiliatedCenter(){
        $user = User::factory()->create();
        CenterUser::factory()->centerCode($this->center3->code)->userId($user->id)->create();

        $this->userRepository->deleteAffiliatedCenter($user->id, $this->center3->code);

        $affiliatedCenters = User::find($user->id)->affiliatedCenters()->get()->toArray();
        $this->assertEquals(0, sizeof($affiliatedCenters));

    }

    public function testGetAffiliatedCenters(){

        $user = User::factory()->create();
        CenterUser::factory()->centerCode($this->center3->code)->userId($user->id)->create();
        CenterUser::factory()->centerCode($this->center5->code)->userId($user->id)->create();

        $affiliatedCenters = $this->userRepository->getAffiliatedCenter($user->id);

        $this->assertEquals(2, sizeof($affiliatedCenters));
        $this->assertNotNull(2, $affiliatedCenters[0]['country_code']);

    }

    public function testGetAllUsersCenters(){

        $user = User::factory()->create();
        CenterUser::factory()->centerCode($this->center3->code)->userId($user->id)->create();

        $centers = $this->userRepository->getAllUsersCenters($user->id);

        $this->assertTrue(in_array(0, $centers));
        $this->assertTrue(in_array(3, $centers));

    }

    public function testGetUsersFromStudy(){

        $userStudy1 = User::factory()->count(5)->create();
        $userStudy2 = User::factory()->count(5)->create();
        $userNoRole = User::factory()->count(5)->create();

        $study1Name = $this->studies->first()->name;
        $study2Name = $this->studies->last()->name;
        $userStudy1->each(function ($user) use($study1Name) {
            Role::factory()->userId($user->id)->roleName(Constants::ROLE_INVESTIGATOR)->studyName($study1Name)->create();
            Role::factory()->userId($user->id)->roleName(Constants::ROLE_SUPERVISOR)->studyName($study1Name)->create();
        });

        $userStudy2->each(function ($user) use($study2Name) {
            Role::factory()->userId($user->id)->roleName(Constants::ROLE_INVESTIGATOR)->studyName($study2Name)->create();
        });

        $users = $this->userRepository->getUsersFromStudy($study1Name);

        $this->assertEquals(5, sizeof($users));
    }

    public function testGetAllUser(){
        $users = User::factory()->count(5)->create();
        $users->first()->delete();
        $withDeletedUsers = $this->userRepository->getAll(true);
        $nonDeletedUsers = $this->userRepository->getAll(false);

        //5 users created + default user
        $this->assertEquals(6, sizeof($withDeletedUsers));
        $this->assertEquals(5, sizeof($nonDeletedUsers));
    }

}
