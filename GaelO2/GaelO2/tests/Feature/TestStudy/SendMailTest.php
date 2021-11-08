<?php

namespace Tests\Feature\TestStudy;

use App\GaelO\Constants\Constants;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;
use App\Models\Study;
use App\Models\User;
use Tests\AuthorizationTools;

class SendMailTest extends TestCase
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

    protected function setUp() : void{
        parent::setUp();

        $this->study = Study::factory()->patientCodePrefix('123')->create();
        for($i = 0; $i < 10; $i++) {
            $user = User::factory()->centerCode(0)->create();
            AuthorizationTools::addRoleToUser($user->id, Constants::ROLE_SUPERVISOR, $this->study->name);
            AuthorizationTools::addRoleToUser($user->id, Constants::ROLE_INVESTIGATOR, $this->study->name);
            AuthorizationTools::addRoleToUser($user->id, Constants::ROLE_REVIEWER, $this->study->name);
            AuthorizationTools::addRoleToUser($user->id, Constants::ROLE_CONTROLLER, $this->study->name);
        }
    }


    public function testSendReminderInvestigator() {
        $currentUserId = AuthorizationTools::actAsAdmin(false);
        AuthorizationTools::addRoleToUser($currentUserId, Constants::ROLE_SUPERVISOR, $this->study->name);
        
        $subject = 'Upload';
        $payload = [
            'centerCode' => 0,
            'subject' => $subject,
            'content' => '<p><b>Recipients: Default CRAs</b></p>
            <p><b>Object: '.$this->study->name.' - '.$subject.'</b></p>

            <p><b>Message:</b></p>
            <p>Dear,</p>
            <p>I contact you regarding the '.$this->study->name.' trial as some actions should still be performed.<br />
                Could you please connect to the platform of the trial to perform the actions: test.com
            </p>
            Some additionnal information...
            <p>Thank you in advance for your help. <br />
                Have a nice day.
            </p>
            <p>Kind regards,</p>
            <p>The imaging team</p>',
        ];

        $response = $this->json('POST', '/api/studies/'.$this->study->name.'/send-reminder?role='.Constants::ROLE_INVESTIGATOR, $payload)->assertNoContent(200);
    }

    public function testSendReminderController() {
        $currentUserId = AuthorizationTools::actAsAdmin(false);
        AuthorizationTools::addRoleToUser($currentUserId, Constants::ROLE_SUPERVISOR, $this->study->name);

        $subject = 'Quality Control';
        $payload = [
            'subject' => $subject,
            'content' => '<p><b>Recipients: Controllers</b></p>
            <p><b>Object: '.$this->study->name.' - '.$subject.'</b></p>
            <p><b>Message:</b></p>
            <p>Dear,</p>
            <p>I contact you regarding the '.$this->study->name.' trial as some actions should still be performed.<br />
                Could you please connect to the platform of the trial to perform the actions: test.com
            </p>
            Some additionnal information...
            <p>Thank you in advance for your help. <br />
                Have a nice day.
            </p>
            <p>Kind regards,</p>
            <p>The imaging team</p>',
        ];

        $response = $this->json('POST', '/api/studies/'.$this->study->name.'/send-reminder?role='.Constants::ROLE_CONTROLLER, $payload)->assertNoContent(200);
    }

    public function testSendReminderReviewer() {
        $currentUserId = AuthorizationTools::actAsAdmin(false);
        AuthorizationTools::addRoleToUser($currentUserId, Constants::ROLE_SUPERVISOR, $this->study->name);

        $subject = 'Review';
        $payload = [
            'subject' => $subject,
            'content' => '<p><b>Recipients: Reviewers</b></p>
            <p><b>Object: '.$this->study->name.' - '.$subject.'</b></p>
            <p><b>Message:</b></p>
            <p>Dear,</p>
            <p>I contact you regarding the '.$this->study->name.' trial as some actions should still be performed.<br />
                Could you please connect to the platform of the trial to perform the actions: test.com
            </p>
            Some additionnal information...
            <p>Thank you in advance for your help. <br />
                Have a nice day.
            </p>
            <p>Kind regards,</p>
            <p>The imaging team</p>',
        ];

        $response = $this->json('POST', '/api/studies/'.$this->study->name.'/send-reminder?role='.Constants::ROLE_REVIEWER, $payload)->assertNoContent(200);
    }

    public function testSendMailToSupervisors() {
        $currentUserId = AuthorizationTools::actAsAdmin(false);
        AuthorizationTools::addRoleToUser($currentUserId, Constants::ROLE_INVESTIGATOR, $this->study->name);
        AuthorizationTools::addRoleToUser($currentUserId, Constants::ROLE_SUPERVISOR, $this->study->name);

        $payload = [
            'subject' => 'Question',
            'content' => '<p>Something</p>',
        ];

        $response = $this->json('POST', '/api/studies/'.$this->study->name.'/send-mail?role='.Constants::ROLE_INVESTIGATOR, $payload)->assertNoContent(200);
    }

    public function testSendMailToUser() {
        $currentUserId = AuthorizationTools::actAsAdmin(false);
        AuthorizationTools::addRoleToUser($currentUserId, Constants::ROLE_SUPERVISOR, $this->study->name);
        
        $payload = [
            'userId' => $currentUserId,
            'subject' => 'Question',
            'content' => '<p>Something</p>',
        ];

        $response = $this->json('POST', '/api/studies/'.$this->study->name.'/send-mail?role='.Constants::ROLE_SUPERVISOR, $payload)->assertNoContent(200);
    }

}