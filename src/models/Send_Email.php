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
    
    public function __construct($linkpdo){
        $this->linkpdo= $linkpdo;
       
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
    }

    /**
     * Set the message to send (will be included in the HTML template)
     */
    public function setMessage(string $message){
        $this->message=$message;
    }

    /**
     * Send email prepared in this class
     * Sender is the the admin email in the preferece database
     * @param array $emails
     * @param string $subject
     * @param string $replyTo , optional, to allow a direct response to the user sending email
     * @return string
     */
    public function sendEmail($emails, string $subject, $replyTo=null){
        
        $mail = new PHPMailer(true); // Passing `true` enables exceptions
        $mail->CharSet = 'UTF-8';
        
        //Recipients
        if($this->smtp_config['useSMTP']){
            $mail->IsSMTP();    // Set mailer to use SMTP
            $mail->Host = $this->smtp_config['host'];   // Specify main and backup server
            $mail->Port = $this->smtp_config['port'];   // Set the SMTP port
            $mail->SMTPAuth = true; // Enable SMTP authentication
            $mail->Username = $this->smtp_config['user'];   // SMTP username
            $mail->Password = $this->smtp_config['password'];   // SMTP password
            $mail->SMTPSecure = $this->smtp_config['SMTPSecure'];
        }else{
            //Add DKIM private key if exist
            if(file_exists($_SERVER['DOCUMENT_ROOT'].'/data/_config/dkim.private')){
                $mail->DKIM_domain = GAELO_WEB_ADDRESS;
                $mail->DKIM_private = $_SERVER['DOCUMENT_ROOT'].'/data/_config/dkim.private';
                $mail->DKIM_selector = 'mail';
                $mail->DKIM_passphrase = '';
                $mail->DKIM_identity = $mail->From;
            }
        }
        


        
        //Add Sender Name
        $mail->setFrom($this->adminEmail, $this->corporation, true);
        
        //Add Reply To
        try{
            if($replyTo!=null){
               $mail->addReplyTo($replyTo); 
            }else{
                $mail->addReplyTo(GAELO_REPLY_TO); 
            }
        }catch (Exception $e){
            error_log("Reply to email problem".$e->getMessage());
        }
        
        //If array of one value transform it in sigle value
        if(is_array($emails) && sizeof($emails)==1){
            $emails=$emails[0];
        }
        
        //Add destinators
        if (is_array($emails)){
            foreach ($emails as $value){
                try{
                    $mail->addBCC($value);
                }catch (Exception $e){
                    error_log('error adding email'.$e->getMessage());
                }
                //Add message to mail object
                $this->buildMessage($mail);
                
             }
        } else {
            //If only one add regular adress
            try{
                $mail->addAddress($emails);
                $userObject=user::getUserByEmail($emails, $this->linkpdo);
                $this->buildMessage($mail, $userObject->lastName, $userObject->firstName);
            }catch (Exception $e){
                error_log('error adding email'.$e->getMessage());
            }

           
        }
        
        //Content
        $mail->isHTML(true);                                  // Set email format to HTML
        $mail->Subject = $this->corporation.' Imaging Platform -' . $subject;
        try{
            $mail->send();
        }catch (Exception $e){
            error_log("Message could not be sent. Mailer Error: {$mail->ErrorInfo}");
        }
        
        
    }
    
    private function buildMessage(PHPMailer $mail, $lastName=null, $firstName=null){
        
        if($lastName==null && $firstName==null){
            $nameString="user";
        }else{
            $nameString=$firstName.' '.$lastName;
        }
                    
        $messageToSend =
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
                <div id="message">Dear '.$nameString.',<br>'.$this->message.'</div>
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
        $htmlMessageObject = new \Html2Text\Html2Text($messageToSend);
        $mail->Body    = $messageToSend;
        $mail->AltBody = $htmlMessageObject->getText();
        $mail->AddEmbeddedImage('assets/images/gaelo-logo-square.png', 'logo_gaelo');
        
        
    }
    
    /**
     * Retrieve all emails of Admins users
     */
    public function getAdminsEmails(){
        $connecter = $this->linkpdo->prepare('SELECT email FROM users WHERE is_administrator=1 AND status!="Deactivated"');
        $connecter->execute();
        $adminResult=$connecter->fetchAll(PDO::FETCH_COLUMN);
       
        return $adminResult;
    }
    
    /**
     * Get email of a given user
     * @param string $username
     * @return string email
     */
    public function getUserEmails(string $username){
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
    public function getRolesEmails(string $role, string $study){
    	$connecter = $this->linkpdo->prepare('SELECT users.email FROM roles, users 
                                                     WHERE roles.study=:study 
                                                        AND roles.username=users.username 
                                                        AND roles.name=:role
                                                        AND users.status!="Deactivated"');
    	$connecter->execute(array(
    			'study'=>$study,
    			'role'=>$role) );
    	
    	$results=$connecter->fetchAll(PDO::FETCH_COLUMN);
    	return $results;
    }
    
}
