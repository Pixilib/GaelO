<?php
/**
 Copyright (C) 2018 KANOUN Salim
 This program is free software; you can redistribute it and/or modify
 it under the terms of the Affero GNU General Public v.3 License as published by
 the Free Software Foundation;
 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 Affero GNU General Public Public for more details.
 You should have received a copy of the Affero GNU General Public Public along
 with this program; if not, write to the Free Software Foundation, Inc.,
 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA
 */

/**
 * Email sending services for user's email notifications
 */

use PHPMailer\PHPMailer\PHPMailer;

Class Send_Email {
    
	private $message;
	private $linkpdo;
	private $smtp_config;
	public $platformName;
	public $adminEmail;
	public $corporation;
	public $webAddress;
	public $emailsDestinators;
	public $subject;

	public $replyTo;
    
	public function __construct(PDO $linkpdo) {
		$this->linkpdo=$linkpdo;
       
		$this->smtp_config=array(
			'useSMTP'=> GAELO_USE_SMTP,
			'host'=> GAELO_SMTP_HOST,
			'port'=> GAELO_SMTP_PORT,
			'user'=> GAELO_SMTP_USER,
			'password' => GAELO_SMTP_PASSWORD,
			'SMTPSecure' => GAELO_SMTP_SECURE
		);
        
		$this->platformName=GAELO_PLATEFORM_NAME;
		$this->adminEmail=GAELO_ADMIN_EMAIL;
		$this->corporation=GAELO_CORPORATION;
		$this->webAddress=GAELO_WEB_ADDRESS;
		$this->replyTo=GAELO_REPLY_TO;
		$this->emailsDestinators=[];
		$this->subject='GaelO Notification';
	}

	/**
	 * Set the message to send (will be included in the HTML template)
	 */
	public function setMessage(string $message) {
		$this->message=$message;
	}

	public function setSubject(String $subject) {
		$this->subject=$subject;
	}

	/**
	 * Send email prepared in this class
	 * Sender is the the admin email in the preferece database
	 * @param array $emails
	 * @param string $subject
	 * @param string $replyTo , optional, to allow a direct response to the user sending email
	 * @return string
	 */
	public function sendEmail() {

		$mail=new PHPMailer(true); // Passing `true` enables exceptions
		$mail->CharSet='UTF-8';

		//Recipients
		if ($this->smtp_config['useSMTP']) {
			$mail->IsSMTP(); // Set mailer to use SMTP
			$mail->Host=$this->smtp_config['host']; // Specify main and backup server
			$mail->Port=$this->smtp_config['port']; // Set the SMTP port
			$mail->SMTPAuth=true; // Enable SMTP authentication
			$mail->Username=$this->smtp_config['user']; // SMTP username
			$mail->Password=$this->smtp_config['password']; // SMTP password
			$mail->SMTPSecure=$this->smtp_config['SMTPSecure'];
		}else {
			//Add DKIM private key if exist
			if (file_exists($_SERVER['DOCUMENT_ROOT'].'/data/_config/dkim.private')) {
				$mail->DKIM_domain=GAELO_WEB_ADDRESS;
				$mail->DKIM_private=$_SERVER['DOCUMENT_ROOT'].'/data/_config/dkim.private';
				$mail->DKIM_selector='mail';
				$mail->DKIM_passphrase='';
				$mail->DKIM_identity=$mail->From;
			}
		}

		//Add Sender Name
		$mail->setFrom($this->adminEmail, $this->corporation, true);
        
		//Add Reply To
		try {
			$mail->addReplyTo($this->replyTo); 
		}catch (Exception $e) {
			error_log("Reply to email problem".$e->getMessage());
			return false;
		}
        
		//Add destinators
		if (sizeof($this->emailsDestinators) > 1) {
			foreach ($this->emailsDestinators as $value) {
				try {
					$mail->addBCC($value);
				}catch (Exception $e) {
					error_log('error adding email'.$e->getMessage());
				}
			 }
			//Add message to mail object
			$this->buildMessage($mail); 
		}else if (sizeof($this->emailsDestinators) == 1) {
			//If only one add regular adress
			try {
				$mail->addAddress($this->emailsDestinators[0]);
				$userObject=User::getUserByEmail($this->emailsDestinators[0], $this->linkpdo);
				$this->buildMessage($mail, $userObject->lastName, $userObject->firstName);
			}catch (Exception $e) {
				error_log('error adding email'.$e->getMessage());
				return false;
			}

           
		}

		//Content
		$mail->isHTML(true); // Set email format to HTML
		$mail->Subject=$this->corporation.' Imaging Platform -'.$this->subject;
		try {
			$answer=$mail->send();
			return $answer;
		}catch (Exception $e) {
			error_log("Message could not be sent. Mailer Error: {$mail->ErrorInfo}");
			return false;
		}
        
        
	}
    
	private function buildMessage(PHPMailer $mail, $lastName=null, $firstName=null) {
        
		if ($lastName == null && $firstName == null) {
			$nameString="user";
		}else {
			$nameString=$firstName.' '.$lastName;
		}
                    
		$messageToSend=
		'<!doctype html> <html lang="en">
            <head>
                <meta charset="utf-8">
                <title> '.$this->platformName.'</title>
                <style>
                    h1   {color: brown ; text-align: center}
                    a:link { color: brown; }
                    header { text-align: center;}
                    #footer-link {
                        width: 100%;
                        background-color: beige;
                        color: black;
                        text-align: center;
                    }
                    
                    #automatic {
                        font-style: italic;
                    }

                    #logo-gaelo {
                        max-height: 180px;
                        width: auto;
                    }
                    
                    #footer-contact{ color: black ; background: white ; text-align: left}
                    
                    #message { text-align: left; }
                </style>       
            </head>
            <body>
                <header class="main-header" id ="header">
                    <img id="logo-gaelo" src=cid:logo_gaelo alt="Banner Image"/>
                </header>
                <h1><a href="http://'.$this->webAddress.'">'.$this->platformName.'</a></h1>
                <div id="message"><b>Dear '.$nameString.',</b><br>'.$this->message.'</div>
                <div class="footer">
                    <p id ="footer-contact">Please contact the Imaging Department of '.$this->corporation.' for any questions (<a HREF="mailto:'.$this->adminEmail.'">'.$this->adminEmail.'</a>) <br>
                                Kind regards, <br>
                                The Imaging Department of '.$this->corporation.'. <br>
                    </p>
                    <p id="automatic">This is an automatic e-mail. Please do not reply. <br></p>
                    <p id="footer-link"><a href="http://'.$this->webAddress.'">'.$this->webAddress.'</a></p>
                </div>
            </body>
        </html>';
        
		$messageToSend=trim(preg_replace('/\t+/', '', $messageToSend));
		$messageToSend=str_replace(array("\r", "\n"), '', $messageToSend);
        
		//Prepare Html2PlainText for email validity (both version to enhance spam validation)
		$htmlMessageObject=new \Html2Text\Html2Text($messageToSend);
		$mail->Body=$messageToSend;
		$mail->AltBody=$htmlMessageObject->getText();
		$mail->AddEmbeddedImage($_SERVER['DOCUMENT_ROOT'].'/assets/images/gaelo-logo-square.png', 'logo_gaelo');
        
        
	}
    
	/**
	 * Retrieve all emails of Admins users
	 */
	public function getAdminsEmails() {
		$connecter=$this->linkpdo->prepare('SELECT email FROM users WHERE is_administrator=1 AND status!="Deactivated"');
		$connecter->execute();
		$adminResult=$connecter->fetchAll(PDO::FETCH_COLUMN);
       
		return $adminResult;
	}
    
	/**
	 * Get email of a given user
	 * @param string $username
	 * @return string email
	 */
	public function getUserEmails(string $username) {
		$userObject=new User($username, $this->linkpdo);
		return $userObject->userEmail;
	}
    
	/**
	 * Method to get email by role in a study
	 * Do not return deactivated account
	 * @param string $role
	 * @param string $study
	 * @return array mails
	 */
	public function getRolesEmails(string $study, string $role) {
		$connecter=$this->linkpdo->prepare('SELECT users.email FROM roles, users 
                                                     WHERE roles.study=:study 
                                                        AND roles.username=users.username 
                                                        AND roles.name=:role
                                                        AND users.status!="Deactivated"');
		$connecter->execute(array(
				'study'=>$study,
				'role'=>$role));
    	
		$results=$connecter->fetchAll(PDO::FETCH_COLUMN);
		return $results;
	}

	/**
	 * Return all users having a specific center in main of affiliated centers
	 * @param PDO $linkpdo
	 * @param $center
	 * @return User[]
	 */
	private function getUsersAffiliatedToCenter(int $center) {
		
		//Select All users that has a matching center
		$queryUsersEmail=$this->linkpdo->prepare('
								    SELECT users.username
									FROM users
									WHERE (center=:center)
									UNION
									SELECT affiliated_centers.username
									FROM affiliated_centers,
									     users
									WHERE (affiliated_centers.center=:center
									       AND affiliated_centers.username=users.username)');
		
		$queryUsersEmail->execute(array('center'=>$center));
		$users=$queryUsersEmail->fetchAll(PDO::FETCH_COLUMN);
		
		$usersObjects=[];
		foreach ($users as $user) {
			$usersObjects[]=new User($user, $this->linkpdo);	
		}
		
		return $usersObjects;

	}
    
	/**
	 * Add investigators emails having a particular center center in main or affiliated center
	 */
	public function selectInvestigatorsEmailsWithSameCenter(String $study, int $center, ?array $job=null) : Send_Email {
		//Select All users that has a matching center
		$users=$this->getUsersAffiliatedToCenter($center);
		//For each user check that we match role requirement (array if investigator), string if monitor or supervisor
		foreach ($users as $user) {
            
			if (is_array($job) && !in_array($user->userJob, $job)) {
				continue;
			}
        
			if ($user->isRoleAllowed($study, User::INVESTIGATOR)) {
				$this->addEmail($user->userEmail);
			}
            
		}
        
		return $this;
	}

	public function addAminEmails() : Send_Email {

		$emails=$this->getAdminsEmails();
		$this->addEmails($emails);
		return $this;

	}

	public function addEmails(Array $emails) : Send_Email{

		foreach ($emails as $email) {
			if (!in_array($email, $this->emailsDestinators))
			{
				$this->emailsDestinators[]=$email; 
			}

		}
		return $this;

	}

	public function addGroupEmails(String $study, String $role) : Send_Email {

		$emails=$this->getRolesEmails($study, $role);
		$this->addEmails($emails);
		return $this;

	}

	public function addEmail(String $email) : Send_Email {

		if (!in_array($email, $this->emailsDestinators))
			{
				$this->emailsDestinators[]=$email; 
			}
        
		return $this;
	}

	public function sendModifyUserMessage($username, $newPassword) {

		$message="Your account password is reset. Please log in at: ".$this->webAddress."<br>
        Username : $username<br>
        Temporary password : $newPassword<br>
        You will be asked to change this password at your first log in attempt<br>
        on the platform.<br>";
        
		$this->setMessage($message);
		$this->subject='Reactivation';
		$this->sendEmail();

	}

	public function sendNewAccountMessage($username, $password) {

		$message="Your account is created for the upload platform used to exchange
                imaging data. Please log in at: " . $this->webAddress." <br>
                Username : $username<br>
                Temporary password : $password<br>
                You will be asked to change this password at your first log in attempt
                on the platform.<br><br>";

		$this->setMessage($message);
		$this->subject='New account';
		$this->sendEmail();
	}

	public function sendNewPasswordEmail($username, $password) {

		$message="This automatic e-mail contains your new temporary password for your
          user account.<br>
          Username : ".$username." <br>
          Temporary password : ".$password." <br>
          You will be asked to change this password at your first connection.<br>";
        
		$this->setMessage($message);
		$this->subject='New Password';
		$this->sendEmail();

	}

	public function sendQCDesicionEmail(String $controlDecision, String $study, String $patientCode, String $visitType, $formDecision, $formComment, $imageDecision, $imageComment) {
		$message="Quality Control of the following visit has been set to : ".$controlDecision."<br>
                Study : ".$study."<br>
                Patient Number : ".$patientCode."<br>
                Visit : ".$visitType."<br>
                Investigation Form : ".$formDecision." Comment :".$formComment."<br>
                Image Series : ".$imageDecision." Comment :".$imageComment." <br>";

		$this->setMessage($message);
		$this->subject='Quality Control';
		$this->sendEmail();
	}

	public function sendBlockedAccountNoPasswordChangeEmail($username, $linkedStudy) {

		$message="The password change request cannot be done because the account is Deactivated<br>
          Username : ".$username."<br>
          The account is linked to the following studies:".implode(',', $linkedStudy)."<br>
          Please contact the ".$this->corporation." to activate your account:<br>
          ".$this->adminEmail."<br>";

		$this->setMessage($message);
		$this->subject='Blocked account';
		$this->sendEmail();

	}

	public function sendAdminLoggedAlertEmail($username, $remoteAdress) {
		$message='the Admin user '.$username.' logged in from '.$remoteAdress.'
        <br> Please review this activity';
		$this->setMessage($message);
		$this->subject="Admin Logged In";
		$this->sendEmail();
        
	}

	public function sendUnlockRequestMessage(String $role, String $username, String $visitType, $patientNum, String $study, String $request) {
		$message="An Unlock ".$role." form Request was emitted by ".$username. 
		" for the ".$visitType. 
		" visit of patient ".$patientNum. 
		" in Study ".$study."<br>
        Reason for request: " . $request." <br>";
		$this->setMessage($message);
		$this->subject='Ask Unlock';
		$this->sendEmail();
        
	}

	public function sendReviewReadyMessage(String $study, int $patientCode, String $visitType) {
		$message="The following visit is ready for review in the platform: <br>
        Study : ".$study."<br>
        Patient Number : ".$patientCode."<br>
        Visit : ".$visitType."<br>";
		$this->setMessage($message);
		$this->subject="Awaiting Review";
		$this->sendEmail();
	}

	public function sendCorrectiveActionDoneMessage(bool $done, String $study, int $patientCode, String $visitType) {

		if (!$done) {
			$message="No corrective action could be applied on the following visit: <br>
                        Study : " . $study."<br>
			            Patient Number : " . $patientCode."<br>
			            Uploaded visit : " . $visitType."<br>";
		} 
		else {
			$message="A corrective action was applied on the following visit: <br>
                        Study : " . $study."<br>
		          		Patient Number : " . $patientCode."<br>
		          		Uploaded visit : " . $visitType."<br>";
			
		}
		$this->setMessage($message);
		$this->subject="Corrective Action";
		$this->sendEmail();

	}

	public function sendRequestMessage(String $name, String $email, String $center, String $request) {
		$message="The following request was sent and will be processed as soon as possible:<br>
		Name : ".$name."<br>
		E-mail : ".$email."<br>
		Investigational center : ".$center."<br>
        Request : ".$request."<br>";
        
		$this->setMessage($message);
		$this->subject="Request";
		$this->sendEmail();
	}

	public function sendAwaitingAdjudicationMessage(String $study, int $patientCode, String $visitType) {
		$message="Review of the following visit is awaiting adjudication <br>
        Study : ".$study."<br>
        Patient Number : ".$patientCode."<br>
        Visit : ".$visitType."<br>
        The visit is awaiting for your adjudication review";

		$this->setMessage($message);
		$this->subject="Awaiting Adjudication";
		$this->sendEmail();
	}

	public function sendVisitConcludedMessage(String $study, int $patientCode, String $visitType, $conclusionValue) {
		$message="Review of the following visit is concluded <br>
                Study : ".$study."<br>
                Patient Number : ".$patientCode."<br>
				Visit : ".$visitType."<br>
                Conclusion Value : ".$conclusionValue;

		$this->setMessage($message);
		$this->subject="Visit Concluded";
		$this->sendEmail();
	}

	public function sendBlockedAccountNotification($username, $studies) {
		$message='The following user account is blocked after too many bad password
                attempts.<br>
                Username : '.$username.'<br>
                The account is linked to the following studies:<br>
                '. implode('<br>', $studies).' </br>';

		$this->setMessage($message);
		$this->subject="Account Blocked";
		$this->sendEmail();
	}

	public function sendUploadedVisitMessage($study, $patientCode, $visitType) {
		$message="The following visit has been uploaded on the platform: <br>
        Study : ".$study."<br>
        Patient Number : ".$patientCode."<br>
        Uploaded visit : ".$visitType."<br>";

		$this->setMessage($message);
		$this->subject="New upload";
		$this->sendEmail();

	}

	public function sendDeletedFormMessage(String $study, String $patientCode, String $visitType) {
		$message="Your form sent for study : ".$study."<br>
        Patient : ".$patientCode."<br>
        Visit  : ".$visitType." <br>
        Have been deleted. <br>
        You can now resend a new version of this form <br>";

		$this->setMessage($message);
		$this->subject="Form Deleted";
		$this->sendEmail();

	}

	public function sendUnlockedFormMessage(String $study, String $patientCode, String $visitType) {
		$message="Your form sent for study : ".$study."<br>
        Patient : ".$patientCode."<br>
        Visit  : ".$visitType." <br>
        Have been Unlocked. <br>
        You can now resend a new version of this form <br>";

		$this->setMessage($message);
		$this->subject="Form Unlocked";
		$this->sendEmail();

	}

	public function sendUploadValidationFailure($idVisit, $patientCode, String $visitType, String $study, String $zipPath, String $username, String $errorMessage) {
		$message="An Import Error occured during validation of upload <br>
            Visit ID : ".$idVisit."<br>
            Patient Code : ".$patientCode."<br>
            Visit Type : ".$visitType."<br>
            Study : ".$study."<br>
            zipPath : ".$zipPath."<br>
            username: ".$username."<br>
            error  : ".$errorMessage."<br>";

		$this->setMessage($message);
		$this->subject="Error During Import";
		$this->sendEmail();
	}

	public function sendCreatedNotDoneVisitNotification($patientCode, $study, $visitType, $creatorUser) {
		$message="A Not Done visit has been created <br>
        Patient Number : ".$patientCode."<br>
        Study : ".$study."<br> 
        Visit Type : ".$visitType."<br>
        Creating Username : ".$creatorUser."<br>";

		$this->setMessage($message);
		$this->subject="Visit Not Done";
		$this->sendEmail();
	}

    
}
