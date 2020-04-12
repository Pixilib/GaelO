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
 * Open Sessions, load constants, instanciate dabase connexion, write text logs for all scripts
 */

use Monolog\Logger;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Processor\WebProcessor;

/**
 * Methods that are call by all scripts
 */
Class Session{
    
	public static function checkSession(bool $log=true, bool $writeSession=false){
        
		if(session_status() == PHP_SESSION_NONE) {
				session_start();
		}
        
		//Write logs
		if($log){
			isset($_POST['id_visit']) ? $logIdVisit=$_POST['id_visit'] : $logIdVisit='N/A';
			isset($_POST['patient_num']) ? $logPatientNum=$_POST['patient_num'] : $logPatientNum='N/A';
			@self::logInfo('Username : '.$_SESSION['username'].
				' Role: '.$_SESSION ['role'].' Study: '.$_SESSION['study'].' Visit ID: '.$logIdVisit.' Patient Num: '.$logPatientNum);
            
		}

		//Check session availability
		if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY'] > 1200)) {
			// last request was more than 30 minutes ago or unexisting
			session_unset();     // unset $_SESSION variable for the run-time
			session_destroy();   // destroy session data in storage
			self::redirectAndEndScript();
		}else if(empty($_SESSION)){
			//if session already empty
			self::redirectAndEndScript();
		}else{
			$_SESSION['LAST_ACTIVITY'] = time(); // update last activity time stamp
		}
        
		//If script dosen't need to write on session data, close write to free async ajax request
		if(!$writeSession){
			session_write_close();
		}
        
	}
    
	/**
	 * Redirect to index and end script execution
	 */
	private static function redirectAndEndScript(){
		echo '<meta http-equiv="Refresh" content="0;/index.php">';
		exit("Session Lost");
	}
    
	/**
	 * Instanciate a new PDO object for database connexion
	 * And Fill Php constant parameter
	 * @return PDO
	 */
	public static function getLinkpdo(){
        
		//Load the config file defining constants
		if(!defined('DATABASE_HOST')){
			require_once($_SERVER["DOCUMENT_ROOT"].'/data/_config/config.inc.php');
		}
        
		//Instanciate PDO connexion with SSL or not
		if(DATABASE_SSL){
			$linkpdo= new PDO('mysql:host='.DATABASE_HOST.';dbname='.DATABASE_NAME.';charset=UTF8', ''.DATABASE_USERNAME.'', ''.DATABASE_PASSWORD.'', self::getSSLPDOArrayOptions() );    
		}else{
			$linkpdo= new PDO('mysql:host='.DATABASE_HOST.';dbname='.DATABASE_NAME.';charset=UTF8', ''.DATABASE_USERNAME.'', ''.DATABASE_PASSWORD.'');   
		}

		$linkpdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
		//Load preferences from the database
		if(! defined('GAELO_PATIENT_CODE_LENGHT') ) Session::loadPreferencesInConstants($linkpdo);
        
		return $linkpdo;
	}
    
	/**
	 * Options to use SSL connexion
	 * @return array
	 */
	public static function getSSLPDOArrayOptions(){
		$sslOptions = array(
			PDO::MYSQL_ATTR_SSL_CA => '',
			PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false
		);
        
		return $sslOptions;
	}
    
	/**
	 * Write log in a daily log file in log folder
	 * @param string $stringInfo
	 */
	public static function logInfo(string $stringInfo){
        
		if(is_writable($_SERVER["DOCUMENT_ROOT"].'/data/logs/')){
			// create a log channel
			$log = new Logger('OpenTrialProcessor');
			$log->pushHandler(new RotatingFileHandler($_SERVER["DOCUMENT_ROOT"].'/data/logs/gaelO.log', Logger::INFO));
			$log->pushProcessor(new WebProcessor());
			$log->info($stringInfo);
		}else{
			error_log("Can't write logs folder");
		}
	}
    
	/**
	 * Store preference from the database in PHP constants
	 * @param PDO $linkpdo
	 */
	public static function loadPreferencesInConstants(PDO $linkpdo){
        
		$connecter = $linkpdo->prepare('SELECT * FROM preferences');
		$connecter->execute();
        
		$result = $connecter->fetch(PDO::FETCH_ASSOC);
        
		define('GAELO_PATIENT_CODE_LENGHT',$result['patient_code_length']);
		define('GAELO_PLATEFORM_NAME', $result['name']);
		define('GAELO_ADMIN_EMAIL', $result['admin_email']);
		define('GAELO_REPLY_TO', $result['email_reply_to']);
		define('GAELO_CORPORATION', $result['corporation']);
		define('GAELO_WEB_ADDRESS', $result['address']);
		define('GAELO_DATE_FORMAT', $result['parse_date_import']);
		define('GAELO_COUNTRY_LANGUAGE', $result['parse_country_name']);

		define('GAELO_ORTHANC_EXPOSED_INTERNAL_ADDRESS', $result['orthanc_exposed_internal_address']);
		define('GAELO_ORTHANC_EXPOSED_INTERNAL_PORT', $result['orthanc_exposed_internal_port']);
		define('GAELO_ORTHANC_EXPOSED_EXTERNAL_ADDRESS', $result['orthanc_exposed_external_address']);
		define('GAELO_ORTHANC_EXPOSED_EXTERNAL_PORT', $result['orthanc_exposed_external_port']);
		define('GAELO_ORTHANC_EXPOSED_INTERNAL_LOGIN', $result['orthanc_exposed_internal_login']);
		define('GAELO_ORTHANC_EXPOSED_INTERNAL_PASSWORD', $result['orthanc_exposed_internal_password']);
		define('GAELO_ORTHANC_EXPOSED_EXTERNAL_LOGIN', $result['orthanc_exposed_external_login']);
		define('GAELO_ORTHANC_EXPOSED_EXTERNAL_PASSWORD', $result['orthanc_exposed_external_password']);
        
		define('GAELO_ORTHANC_PACS_ADDRESS', $result['orthanc_pacs_address']);
		define('GAELO_ORTHANC_PACS_PORT', $result['orthanc_pacs_port']);
		define('GAELO_ORTHANC_PACS_LOGIN', $result['orthanc_pacs_login']);
		define('GAELO_ORTHANC_PACS_PASSWORD', $result['orthanc_pacs_password']);
        
		define('GAELO_USE_SMTP', $result['use_smtp']);
		define('GAELO_SMTP_HOST', $result['smtp_host']);
		define('GAELO_SMTP_PORT', $result['smtp_port']);
		define('GAELO_SMTP_USER', $result['smtp_user']);
		define('GAELO_SMTP_PASSWORD', $result['smtp_password']);
		define('GAELO_SMTP_SECURE', $result['smtp_secure']);
        
	}

}