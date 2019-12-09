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
 * Check User's permissions status : Handles Connexion credential and ressource permissions check for scripts / form / apis...
 */

class User {

    private $linkpdo;
    
    //user details
    public $username;
    public $lastName;
    public $firstName;
    public $userEmail;
    public $userPhone;
    public $creationDateUser;
    public $lastConnexionDate;
    
    //Public variables to retrieve status account for the index page
    public $isAdministrator;
    public $userStatus;
    public $passwordDateValide;
    public $loginAttempt;
    public $isExistingUser;
    public $passwordCorrect;
    
    public $creationDatePassword;
    public $tempPassword;
    public $password;
    
    public $previousPassword1;
    public $previousPassword2;
    public $mainCenter;
    public $userJob;
    
    public $orthancAddress;
    public $orthancLogin;
    public $orthancPassword;
    
    //Constants roles available
    const ADMINISTRATOR="Administrator";
    const SUPERVISOR="Supervisor";
    const CONTROLLER="Controller";
    const MONITOR="Monitor";
    const INVESTIGATOR="Investigator";
    const REVIEWER="Reviewer";
    
    //Constants user status available
    const ACTIVATED="Activated";
    const DEACTIVATED="Deactivated";
    const BLOCKED="Blocked";
    const UNCONFIRMED="Unconfirmed";
    
    

    public function __construct(string $username, PDO $linkpdo){
        $this->linkpdo=$linkpdo;
        //Get the username from DB to get the case sensitive username
        $connecter = $this->linkpdo->prepare('SELECT * FROM users WHERE username = :username');
        $connecter->execute(array("username" => $username));
        $queryResults = $connecter->fetch(PDO::FETCH_ASSOC);
        //If no match in database => User doesn't exist
        if (empty($queryResults)){
            $this->isExistingUser=false;
        }
        else{
            $this->isExistingUser=true;
        }
        
        $this->username=$queryResults['username'];
        $this->lastName=$queryResults['last_name'];
        $this->firstName=$queryResults['first_name'];
        $this->userStatus=$queryResults['status'];
        $this->isAdministrator=$queryResults['is_administrator'];
        $this->userEmail=$queryResults['email'];
        $this->userPhone=$queryResults['phone'];
        $this->creationDateUser=$queryResults['creation_date'];
        $this->lastConnexionDate=$queryResults['connexion_date'];
        $this->creationDatePassword=$queryResults['creation_date_password'];
        $this->tempPassword=$queryResults['temp_password'];
        $this->password=$queryResults['password'];
        $this->previousPassword1=$queryResults['previous_password_1'];
        $this->previousPassword2=$queryResults['previous_password_2'];
        $this->loginAttempt=$queryResults['number_attempts'];
        $this->mainCenter=$queryResults['center'];
        $this->userJob=$queryResults['job'];
        
        $this->orthancAddress=$queryResults['orthanc_address'];
        $this->orthancLogin=$queryResults['orthanc_login'];
        $this->orthancPassword=$queryResults['orthanc_password'];
        
       
    }
    
    public static function getUserByEmail(String $email, PDO $linkpdo){
        
        $connecter = $linkpdo->prepare('SELECT username FROM users WHERE email = :email');
        $connecter->execute(array("email" => $email));
        $username = $connecter->fetch(PDO::FETCH_COLUMN);
        
        return new User($username, $linkpdo);
        
    }

    /**
     * Check connexion credential, number of tentatives and update status account if needed
     * @param String $password
     * @return boolean
     */
    public function isPasswordCorrectAndActivitedAccount(string $password){
        
        $date = new DateTime($this->creationDatePassword);
        $now = new DateTime();
        $delayDay=$date->diff($now)->format("%a");
        
        //If password delay over 90 => out dated password, need to be changed
        if(intVal($delayDay)<=90){
            $this->passwordDateValide=true;
        }
        else{
            $this->passwordDateValide=false;
        }

        //Check password correct
        if($this->userStatus==self::UNCONFIRMED){
            //Use the temp password for check
            $this->passwordCorrect=password_verify($password, $this->tempPassword);
        }else{
            //use the current password for check
            $this->passwordCorrect=password_verify($password, $this->password);
        }
        
        // If password OK, password date OK, and Account status active return OK for connexion
        if ( $this->passwordCorrect && $this->passwordDateValide && $this->userStatus == self::ACTIVATED) {
            //Update the last connexion date in DB and number attempt account to zero
            $now=date("Y-m-d H:i:s");
            $reset_tentatives = $this->linkpdo->prepare('UPDATE users SET number_attempts = 0, connexion_date=:datenow WHERE username = :username');
            //Exécution
            $reset_tentatives->execute(array('username' => $this->username, 'datenow' =>$now ));
            $this->loginAttempt=0;
            
            Session::logInfo('Connected Login : '.$this->username.' Admin '.( ($this->isAdministrator) ? 'true' : 'false') );
            
            return true;
        }
        //Else, return false, add +1 to attempt account and block account if over 3
        else if ( !$this->passwordCorrect) {
            
        	Session::logInfo('Wrong Password : '.$this->username.' Admin '.(($this->isAdministrator) ? 'true' : 'false') );
            
            //Add +1 to attempt account
            $res = $this->linkpdo->prepare('UPDATE users SET number_attempts = number_attempts+1 WHERE username = :username');
            $res->execute(array('username' => $this->username));
            //Look at the new value
            $tentatives = $this->linkpdo->prepare('SELECT number_attempts FROM users WHERE username = :username');
            $tentatives->execute(array("username" => $this->username));
            $nb_tentatives = $tentatives->fetch(PDO::FETCH_ASSOC);
            $this->loginAttempt=$nb_tentatives['number_attempts'];
            //If over three block account
            if($this->loginAttempt > 2){
                $bloquer = $this->linkpdo->prepare('UPDATE users SET status = "Blocked" WHERE username = :username');
                $bloquer->execute(array('username' => $this->username));
                $this->userStatus="Blocked";
                //Log login event
                $log['message']="Blocked";
                Tracker::logActivity($this->username, "User",null,null, "Account Blocked", $log);
                //Send email notification
                $this->sendBlockedEmail();
                
		        return false;

		    }
		    
        } else {
	        //If blocked status, re-send email notification
	        if ($this->userStatus=="Blocked"){
	            $this->sendBlockedEmail();
	        }
	        
	        return false;
		        
	    }

    }
    
    /**
     * Return all studies available for the users (no matter it's role)
     * @return array
     */
    public function getAllStudiesWithRole(){
    	
    	$connecter = $this->linkpdo->prepare('SELECT DISTINCT roles.study FROM roles, studies WHERE roles.username =:username
                                    AND studies.name=roles.study AND studies.active=1 ORDER BY roles.study');
    	$connecter->execute(array(
    			"username" => $this->username,
    	));
    	
    	$AvailableStudies = $connecter->fetchall(PDO::FETCH_COLUMN);
    	
    	return $AvailableStudies;
    }
    
    /**
     * Return if the requested Role in the requested study is allowed for the current user
     * @param string $study
     * @param string $role
     * @param $job
     * @return boolean
     */
    public function isRoleAllowed(string $study, string $role){
        
    	$query='SELECT * FROM roles, studies, users WHERE roles.username = :username 
                                                            AND roles.name=:role 
                                                            AND studies.name=roles.study 
                                                            AND studies.active=1 
                                                            AND studies.name=:study 
                                                            AND users.username=roles.username 
                                                            AND users.status="Activated" ';
    	$executeArray=array(
    			"username" => $this->username,
    			"role"=>$role,
    			"study"=>$study
    	);
    	
    	
        $connecter = $this->linkpdo->prepare($query);
        $connecter->execute($executeArray);
        
        $rownb=$connecter->rowCount();
       
        if($rownb==0){
            return false;
        }
        else{
            return true;
        }

    }

    /**
     * Return main and affiliated centers for the current user
     * @return Array  : main centers in array
     */
    public function getInvestigatorsCenters(){
    	$centers=$this->getAffiliatedCenters();
    	$centers[]=$this->mainCenter;
        return $centers;
    }
    
    /**
     * Return main center object of this user
     * @return Center
     */
    public function getMainCenter(){
        return new Center($this->linkpdo, $this->mainCenter);
    }
    
    /**
     * Return affiliated center of the user
     */
    public function getAffiliatedCenters(){
    	$result_center = $this->linkpdo->prepare('SELECT center FROM affiliated_centers WHERE affiliated_centers.username = :username ORDER BY center');
    	$result_center->execute(array('username' => $this->username));
    	
    	$affiliatedCenterBdd= $result_center->fetchAll(PDO::FETCH_COLUMN);
    	
    	return $affiliatedCenterBdd;
    }
    
    /**
     * Return affiliated center of the user as Object
     */
    public function getAffiliatedCentersAsObjects(){
        $centersCode=$this->getAffiliatedCenters();
        $centersObjects=[];
        foreach ($centersCode as $code){
            $centersObjects[]=new Center($this->linkpdo, $code);
        }
        return $centersObjects;
    }
    
    /**
     * Return all available role in the called study for an user
     * @param $study
     * @return array of available roles
     */
    public function getRolesInStudy(string $study){
        $role = $this->linkpdo->prepare('SELECT name FROM roles WHERE roles.username = :username AND roles.study = :study ORDER BY roles.name');
        $role->execute(array('username' => $this->username, 'study' => $study));
        $data_role = $role->fetchall(PDO::FETCH_COLUMN);
        return $data_role;
    }
    
    /**
     * Retrun role map of users  : each study as key with an array of available roles
     * @return array[]
     */
    public function getRolesMap(){
        $studies=$this->getAllStudiesWithRole();
        $map=[];
        foreach ($studies as $study){
            $map[$study]=$this->getRolesInStudy($study);
        }
        return $map;
    }
    
    /**
     * Check permission for a patient according to role
     * @param number $patientNumber
     * @param string $role
     * @return boolean
     */
    public function isPatientAllowed($patientNumber, string $role){
        
        if(empty($role)) return false;
        
        $connecter = $this->linkpdo->prepare('SELECT center, study FROM patients WHERE code = :patientNumber');
        $connecter->execute(array(
            "patientNumber" => $patientNumber,
        ));
        $patient = $connecter->fetch(PDO::FETCH_ASSOC);
        
        //If Investigator check the current patient is from one of the centers of the user
        if ($role==$this::INVESTIGATOR){
            $userCenters=$this->getInvestigatorsCenters();
            if (in_array($patient['center'], $userCenters) && $this->isRoleAllowed($patient['study'], $role)){
                return true;
            }
        
        //For other patient's permission is defined by patient's study availabilty
        }else {
            if ($this->isRoleAllowed($patient['study'], $role)){
                return true;
            }
        }
        
        return false;
        
    }
    
    /**
     * Check if the Visit and Role is available for the current user
     * Checks for 
     * - All : Check Role availability and Only access to non deleted visits
     * - Investigator : Check Patient is allowed for this user
     * - Reviewer : Check Review is availabl
     * @param $id_visit
     * @param string $role
     * @return boolean
     */
    public function isVisitAllowed($id_visit, string $role){
        
        if(empty($role)) return false;
        
        $visitData=new Visit($id_visit, $this->linkpdo);
        
        //Check that called Role exists for users and visit is not deleted
        if( $this->isRoleAllowed($visitData->study, $role) && !$visitData->deleted ){
            if($role==$this::INVESTIGATOR){ 
                if ( $this->isPatientAllowed($visitData->patientCode, $role) ) return true;
            }else if($role==$this::REVIEWER){
                if($visitData->reviewAvailable) return true;
            }else{
                //Controller, Supervisor, Admin, Monitor simply accept when role is available in patient's study (no specific rules)
                return true;
            }
            
        }
        
        return false;
        
    }
    
    /**
     * Send warning emails to notify that the account is blocked (to administrators + user)
     */
    private function sendBlockedEmail(){
        //Get all studies assosciated with account
        $etude = $this->linkpdo->prepare('SELECT DISTINCT study FROM roles, studies 
                                                    WHERE username = :username AND roles.study=studies.name AND studies.active=1');
        $etude->execute(array('username' => $this->username));
        $results=$etude->fetchAll(PDO::FETCH_COLUMN);
        //Send Email notification
        $etudesString= implode('<br>', $results);
        
        $message = 'The following user account is blocked after too many bad password
                attempts.<br>
                Username : '.$this->username.'<br>
                The account is linked to the following studies:<br>
                '. $etudesString.' </br>';
                
        $sendEmail=new Send_Email($this->linkpdo);
        //Destination list = administrators and user
        $destinators=$sendEmail->getAdminsEmails();
        $destinators[]=$sendEmail->getUserEmails($this->username);
        $sendEmail->setMessage($message);
        $sendEmail->sendEmail($destinators, 'Account Blocked');
    }
    
    /**
     * Add a role to the user
     * @param string $role
     * @param string $study
     */
    public function addRole(string $role, string $study){
        $addRole = $this->linkpdo->prepare('INSERT INTO roles(name, username, study)
                                        VALUES(:role, :username, :study)');
        //Exécution
        $addRole->execute(array('role' => $role,
                                'username' => $this->username,
                                'study' => $study));
        
    }
    
    /**
     * Add Affiliated center to the user
     * @param $centerCode
     */
    public function addAffiliatedCenter($centerCode){
        $addCenter = $this->linkpdo->prepare('INSERT INTO affiliated_centers(username, center)
                                                  VALUES(:username, :centerCode)');
        $addCenter->execute(array('username' => $this->username, 'centerCode' => $centerCode));
        
    }
    
    /**
     * Remove an affiliated center to the user
     * @param $centerCode
     */
    public function removeAffiliatedCenter($centerCode){
    	$res = $this->linkpdo->prepare('DELETE FROM affiliated_centers WHERE username = :username
                  AND center = :centerCode');
    	$res->execute(array('username' => $this->username,
    			'centerCode' => $centerCode));
    }
    
    /**
     * Delete a role in a study to the user
     * @param string $study
     * @param string $role
     */
    public function deleteRole(string $study, string $role){
    	$res = $this->linkpdo->prepare('DELETE FROM roles WHERE username = :username
                  AND study = :study
                  AND name = :role');
    	$res->execute(array('username' => $this->username,
    			'study' => $study,
    			'role' => $role));
    }
    
    
    /**
     * Update password and account status of an user
     * @param $username
     * @param $password
     * @param $linkpdo
     * @param $status
     */
    public static function updateUserPassword(string $username, string $password, PDO $linkpdo, string $status){
        //Update the database with new password and switch old passwords
        $req = $linkpdo->prepare('UPDATE users
                                    SET previous_password_2=users.previous_password_1,
                                        previous_password_1=users.password,
                                        password = :mdp,
                                        number_attempts = 0,
                                        creation_date_password = :datePassword,
                                        status = :status
                                    WHERE username = :username');
        
        $req->execute(array('username' => $username,
            'mdp' => password_hash($password, PASSWORD_DEFAULT),
            'status'=>$status,
            'datePassword' => date('Y-m-d')));
        
    }
    
    /**
     * Generate a temp password and set account to unconfirmed
     * @param $username
     * @param $password
     * @param $linkpdo
     */
    public static function setUnconfirmedAccount(string $username, string $password, PDO $linkpdo){
        
        $req = $linkpdo->prepare('UPDATE users
                                    SET temp_password = :mdp,
                                        number_attempts = 0,
                                        creation_date_password = :datePassword,
                                        status = :status
                                    WHERE username = :username');
        
        $req->execute(array('username' => $username,
            'mdp' => password_hash($password, PASSWORD_DEFAULT),
            'status'=>self::UNCONFIRMED,
            'datePassword' => date('Y-m-d')));
        
    }
    
    /**
     * Update users data
     * @param $last_name
     * @param $first_name
     * @param $email
     * @param $phone
     * @param $job
     * @param $status
     * @param bool $administrator
     * @param $mainCenterCode
     * @param $orthancAddress
     * @param $orthancLogin
     * @param s$orthancPassword
     */
    public function updateUser($last_name, $first_name, $email, $phone, $job, $status, bool $administrator, $mainCenterCode, $orthancAddress, $orthancLogin, $orthancPassword){
        
        $req = $this->linkpdo->prepare('UPDATE users SET
    								last_name = :nom,
    								first_name = :prenom,
    								email = :email,
    								creation_date_password = :date_Utilisateur,
    								phone = :telephone,
    								job = :job,
    								is_administrator= :admin,
    								status = :statut,
									center= :numero_centre,
                                    orthanc_address= :orthancAddress, 
                                    orthanc_login= :orthancLogin, 
                                    orthanc_password= :orthancPassword
						        WHERE users.username = :username');
        
        $req->execute(array(
            'username'=> $this->username,
            'nom' => $last_name,
            'prenom' => $first_name,
            'email' => $email,
            'date_Utilisateur' => date('Y-m-d'),
            'telephone' => empty($phone) ? null : $phone,
            'job' => $job,
            'statut' => $status,
            'admin'=>intval($administrator),
            'numero_centre' => $mainCenterCode,
            'orthancAddress'=>empty($orthancAddress) ? null : $orthancAddress,
            'orthancLogin'=>empty($orthancLogin) ? null : $orthancLogin ,
            'orthancPassword'=>empty($orthancPassword) ? null : $orthancPassword
        )) ;
    }
    
    /**
     * Create a new user
     * @param $username
     * @param $last_name
     * @param $first_name
     * @param $email
     * @param $phone
     * @param $mdp
     * @param $job
     * @param $mainCenter
     * @param $administrator
     * @param $orthancAddress
     * @param $orthancLogin
     * @param $orthancPassword
     * @param PDO $linkpdo
     * @throws Exception
     */
    public static function createUser($username, $last_name, $first_name, $email, $phone,
                $mdp, $job, $mainCenter, $administrator, $orthancAddress, $orthancLogin, $orthancPassword, PDO $linkpdo){
            
            //Check that new users is not already existing
            $accountQuery = $linkpdo->prepare('SELECT * FROM users WHERE (users.username=:username OR users.email=:email)');
            $accountQuery->execute(array(
                'username' => $username,
                'email' => $email
            ));
            $existingAccount = $accountQuery->fetchAll();
            
            if (!empty($existingAccount)) {
               throw new Exception("Account already existing");
            }
            
            
            // If new user, write it in database        
            $req = $linkpdo->prepare('INSERT INTO users(username, last_name, first_name, email, creation_date_password, phone, password, temp_password, job, center, creation_date, is_administrator, orthanc_address, orthanc_login, orthanc_password)
                  VALUES(:username, :nom, :prenom, :email, :creation_date_password, :telephone, :password, :tempPassword, :job, :numero_centre, :date_creation_account, :admin, :orthancAddress, :orthancLogin, :orthancPassword)');
            
            $req->execute(array(
                'username' => $username,
                'nom' => $last_name,
                'prenom' => $first_name,
                'email' => $email,
                'creation_date_password' => date('Y-m-d'),
                'telephone' => empty($phone) ? $phone : null,
                'password'=> password_hash($mdp, PASSWORD_DEFAULT),
                'tempPassword' => password_hash($mdp, PASSWORD_DEFAULT),
                'job' => $job,
                'numero_centre' => $mainCenter,
                'date_creation_account' => date("Y-m-d H:i:s"),
                'admin' => intval($administrator),
                'orthancAddress'=>empty($orthancAddress) ? null : $orthancAddress,
                'orthancLogin'=>empty($orthancLogin) ? null : $orthancLogin,
                'orthancPassword'=>empty($orthancPassword) ? null : $orthancPassword
            ));
    }
    
}
