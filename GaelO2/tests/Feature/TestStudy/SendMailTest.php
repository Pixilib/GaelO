<?php

namespace Tests\Feature\TestStudy;

use App\GaelO\Constants\Constants;
use Tests\TestCase;
use App\Models\Study;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\AuthorizationTools;

class SendMailTest extends TestCase
{

    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed');

        $this->study = Study::factory()->code('123')->create();
        for ($i = 0; $i < 10; $i++) {
            $user = User::factory()->centerCode(0)->create();
            AuthorizationTools::addRoleToUser($user->id, Constants::ROLE_SUPERVISOR, $this->study->name);
            AuthorizationTools::addRoleToUser($user->id, Constants::ROLE_INVESTIGATOR, $this->study->name);
            AuthorizationTools::addRoleToUser($user->id, Constants::ROLE_REVIEWER, $this->study->name);
            AuthorizationTools::addRoleToUser($user->id, Constants::ROLE_CONTROLLER, $this->study->name);
        }
    }

    public function testSendReminderInvestigator()
    {
        $currentUserId = AuthorizationTools::actAsAdmin(false);
        AuthorizationTools::addRoleToUser($currentUserId, Constants::ROLE_SUPERVISOR, $this->study->name);

        $subject = 'Upload';
        $payload = [
            'userIds' => [$currentUserId],
            'subject' => $subject,
            'content' => '<p><b>Recipients: Default CRAs</b></p>
            <p><b>Object: ' . $this->study->name . ' - ' . $subject . '</b></p>

            <p><b>Message:</b></p>
            <p>Dear,</p>
            <p>I contact you regarding the ' . $this->study->name . ' trial as some actions should still be performed.<br />
                Could you please connect to the platform of the trial to perform the actions: test.com
            </p>
            Some additionnal information...
            <p>Thank you in advance for your help. <br />
                Have a nice day.
            </p>
            <p>Kind regards,</p>',
        ];

        $this->json('POST', '/api/studies/' . $this->study->name . '/send-reminder?role=' . Constants::ROLE_INVESTIGATOR, $payload)
            ->assertNoContent(200);
    }

    public function testSendReminderController()
    {
        $currentUserId = AuthorizationTools::actAsAdmin(false);
        AuthorizationTools::addRoleToUser($currentUserId, Constants::ROLE_SUPERVISOR, $this->study->name);

        $subject = 'Quality Control';
        $payload = [
            'userIds' => [$currentUserId],
            'subject' => $subject,
            'content' => '<p><b>Recipients: Controllers</b></p>
            <p><b>Object: ' . $this->study->name . ' - ' . $subject . '</b></p>
            <p><b>Message:</b></p>
            <p>Dear,</p>
            <p>I contact you regarding the ' . $this->study->name . ' trial as some actions should still be performed.<br />
                Could you please connect to the platform of the trial to perform the actions: test.com
            </p>
            Some additionnal information...
            <p>Thank you in advance for your help. <br />
                Have a nice day.
            </p>
            <p>Kind regards,</p>',
        ];

        $this->json('POST', '/api/studies/' . $this->study->name . '/send-reminder?role=' . Constants::ROLE_CONTROLLER, $payload)
            ->assertNoContent(200);
    }

    public function testSendReminderReviewer()
    {
        $currentUserId = AuthorizationTools::actAsAdmin(false);
        AuthorizationTools::addRoleToUser($currentUserId, Constants::ROLE_SUPERVISOR, $this->study->name);

        $subject = 'Review';
        $payload = [
            'userIds' => [$currentUserId],
            'subject' => $subject,
            'content' => '<p><b>Recipients: Reviewers</b></p>
            <p><b>Object: ' . $this->study->name . ' - ' . $subject . '</b></p>
            <p><b>Message:</b></p>
            <p>Dear,</p>
            <p>I contact you regarding the ' . $this->study->name . ' trial as some actions should still be performed.<br />
                Could you please connect to the platform of the trial to perform the actions: test.com
            </p>
            Some additionnal information...
            <p>Thank you in advance for your help. <br />
                Have a nice day.
            </p>
            <p>Kind regards,</p>',
        ];

        $this->json('POST', '/api/studies/' . $this->study->name . '/send-reminder?role=' . Constants::ROLE_REVIEWER, $payload)
            ->assertNoContent(200);
    }

    public function testSendMailToSupervisors()
    {
        $currentUserId = AuthorizationTools::actAsAdmin(false);
        AuthorizationTools::addRoleToUser($currentUserId, Constants::ROLE_INVESTIGATOR, $this->study->name);

        $payload = [
            'subject' => 'Question',
            'content' => '<p>Something</p>',
        ];

        $this->json('POST', '/api/send-mail?role=' . Constants::ROLE_INVESTIGATOR . '&studyName=' . $this->study->name, $payload)
            ->assertNoContent(200);
    }

    public function testSendMailToUser()
    {
        $currentUserId = AuthorizationTools::actAsAdmin(false);
        AuthorizationTools::addRoleToUser($currentUserId, Constants::ROLE_SUPERVISOR, $this->study->name);

        $payload = [
            'userIds' => [$currentUserId],
            'subject' => 'Question',
            'content' => '<p>Something</p>',
        ];

        $this->json('POST', '/api/send-mail?role=' . Constants::ROLE_SUPERVISOR . '&studyName=' . $this->study->name, $payload)
            ->assertNoContent(200);
    }

    public function testSendMailToUserNotAllowed()
    {
        $currentUserId = AuthorizationTools::actAsAdmin(false);
        AuthorizationTools::addRoleToUser($currentUserId, Constants::ROLE_INVESTIGATOR, $this->study->name);

        $payload = [
            'userIds' => [$currentUserId],
            'subject' => 'Question',
            'content' => '<p>Something</p>',
        ];

        $this->json('POST', '/api/send-mail?role=' . Constants::ROLE_INVESTIGATOR . '&studyName=' . $this->study->name, $payload)
            ->assertStatus(403);
    }

    public function testSendMailToUserFromAdmin()
    {
        $currentUserId = AuthorizationTools::actAsAdmin(true);

        $payload = [
            'userIds' => [$currentUserId],
            'subject' => 'Question',
            'content' => '<p>Something</p>',
        ];

        $this->json('POST', '/api/send-mail?role=' . Constants::ROLE_ADMINISTRATOR, $payload)
            ->assertNoContent(200);
    }

    public function testSendMailToAdmin()
    {
        $currentUserId = AuthorizationTools::actAsAdmin(true);
        AuthorizationTools::addRoleToUser($currentUserId, Constants::ROLE_SUPERVISOR, $this->study->name);

        $payload = [
            'subject' => 'Question',
            'content' => '<p>Something</p>',
            'toAdministrators' => true
        ];

        $this->json('POST', '/api/send-mail?role=' . Constants::ROLE_SUPERVISOR . '&studyName=' . $this->study->name, $payload)
            ->assertNoContent(200);
    }

    public function testSendPatientsCreationRequest()
    {
        $currentUserId = AuthorizationTools::actAsAdmin(true);
        AuthorizationTools::addRoleToUser($currentUserId, Constants::ROLE_INVESTIGATOR, $this->study->name);

        $payload = [
            'subject' => 'Request Patients Creation',
            'content' => '<p>Some very insightful message</p>',
            'patients' => [
                'id' => 1,
                'code' => 123,
                'firstname' => 'A',
                'lastname' => 'D',
                'gender' => 'M',
                'birthDay' => null,
                'birthMonth' => null,
                'birthYear' => null,
                'registrationDate' => now(),
                'investigatorName' => 'someone',
                'centerCode' => 0,
                'inclusionStatus' => 'Included'
            ]
        ];

        $this->json('POST', '/api/studies/'.$this->study->name.'/ask-patient-creation?role=' . Constants::ROLE_INVESTIGATOR , $payload)
            ->assertNoContent(200);
    }

    public function testSendPatientsCreationRequestBadRequestNoPatients()
    {
        $currentUserId = AuthorizationTools::actAsAdmin(true);
        AuthorizationTools::addRoleToUser($currentUserId, Constants::ROLE_INVESTIGATOR, $this->study->name);

        $payload = [
            'subject' => 'Request Patients Creation',
            'content' => '<p>Some very insightful message</p>',
            'patients' => []
        ];

        $this->json('POST', '/api/studies/'.$this->study->name.'/ask-patient-creation?role=' . Constants::ROLE_INVESTIGATOR, $payload)
            ->assertStatus(400);
    }
}
