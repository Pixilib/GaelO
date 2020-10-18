<?php
/**
 Copyright (C) 2018-2020 KANOUN Salim
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
 * Static methods for global data
 */

use Ifsnop\Mysqldump as IMysqldump;

Class Global_Data {
    
	/**
	 * Get CountryName by Code
	 * Source country list from : https://github.com/umpirsky/country-list
	 * @param PDO $linkpdo
	 * @param $countryCode
	 * @return string
	 */
	public static function getCountryName(PDO $linkpdo, string $countryCode) {
        
		$countryQuery=$linkpdo->prepare("SELECT country_us FROM country WHERE country_code = :countryCode");
		$countryQuery->execute(array('countryCode' => $countryCode));
		$countryName=$countryQuery->fetch(PDO::FETCH_COLUMN);
        
		return  $countryName;
        
	}
    
	/**
	 * Return a country code depending of countryName (take account language prefrences)
	 * @param PDO $linkpdo
	 * @param string $countryName
	 * @return string
	 */
	public static function getCountryCode(PDO $linkpdo, string $countryName) {
		if (GAELO_COUNTRY_LANGUAGE == "FR") {
			$request="SELECT country_code FROM country WHERE country_fr = :countryName";
		}else if (GAELO_COUNTRY_LANGUAGE == "US") {
			$request="SELECT country_code FROM country WHERE country_us = :countryName";
		}
        
		$countryQuery=$linkpdo->prepare($request);
		$countryQuery->execute(array('countryName' => $countryName));
		$countryName=$countryQuery->fetch(PDO::FETCH_COLUMN);
        
		return  $countryName;
        
	}
	
	/**
	 * Return all studies name in the platefome (activated true to get only active study)
	 * @param PDO $linkpdo
	 * @param bool $onlyActivated
	 * @return array
	 */
	public static function getAllStudies(PDO $linkpdo, bool $onlyActivated=false) {
        
		if ($onlyActivated) {
			$connecter=$linkpdo->prepare('SELECT name FROM studies WHERE active=1 ORDER BY name');
		}else {
			$connecter=$linkpdo->prepare('SELECT name FROM studies ORDER BY name');
		}
		$connecter->execute();
        
		$AvailableStudies=$connecter->fetchall(PDO::FETCH_COLUMN);
        
		return $AvailableStudies;
	}
    
	/**
	 * Get All centers objects declared in the plateform
	 * @param PDO $linkpdo
	 * @return Center[]
	 */
	public static function getAllCentersObjects(PDO $linkpdo) {
		$centerQuery=$linkpdo->prepare('SELECT code FROM centers ORDER BY code');
		$centerQuery->execute();
		$centers=$centerQuery->fetchAll(PDO::FETCH_COLUMN);
	    
		$centerObject=[];
		foreach ($centers as $center) {
			$centerObject[]=new Center($linkpdo, $center);
		}
		return $centerObject;
	}

	public static function getAllCentersAsJson(PDO $linkpdo) : String {
		$centersObjectArray=Global_Data::getAllCentersObjects($linkpdo);
		$centerResponseArray=[];
		
		foreach ($centersObjectArray as $centerObject) {
			$centerResponseArray[$centerObject->code]['centerName']=$centerObject->name;
			$centerResponseArray[$centerObject->code]['countryCode']=$centerObject->countryCode;
		}

		$temporaryFile=Global_Data::writeTextAsTempFile(json_encode($centerResponseArray, JSON_FORCE_OBJECT));

		return $temporaryFile;
	}
	
	/**
	 * Get All possible countries
	 * @param PDO $linkpdo
	 * @return array
	 */
	public static function getAllcountries(PDO $linkpdo) {
		$countryQuery=$linkpdo->prepare("SELECT * FROM country ORDER BY country_us");
		$countryQuery->execute();
		$countryCode=$countryQuery->fetchAll(PDO::FETCH_ASSOC);
	    
		return  $countryCode;
	}
	
	/**
	 * Get all possible jobs existing in the plateform
	 * @param PDO $linkpdo
	 * @return array
	 */
	public static function getAllJobs(PDO $linkpdo) {
		$jobQuery=$linkpdo->prepare('SELECT name FROM job ORDER BY name');
		$jobQuery->execute();
		$jobs=$jobQuery->fetchAll(PDO::FETCH_COLUMN);
		return $jobs;
		
	}
	
	/**
	 * Return userObject array for all users in the system
	 * @return User[]
	 */
	public static function getAllUsers(PDO $linkpdo) {
		$req=$linkpdo->prepare('SELECT username FROM users');
		$req->execute();
		$answers=$req->fetchAll(PDO::FETCH_COLUMN);
	    
		$usersObjects=[];
		foreach ($answers as $username) {
			$usersObjects[]=new User($username, $linkpdo);
		}
		return $usersObjects;
	    
	}
	
	/**
	 * Return full list of series IDs in the database, just to export reference for administrator
	 * @param PDO $linkpdo
	 * @return mixed
	 */
	public static function getAllSeriesOrthancID(PDO $linkpdo) {
		$seriesQuery=$linkpdo->prepare('SELECT Series_Orthanc_ID FROM orthanc_series');
		$seriesQuery->execute();
		$seriesOrthancIds=$seriesQuery->fetchAll(PDO::FETCH_COLUMN);
		return $seriesOrthancIds;
	}
	
	/**
	 * Update plateform preferences
	 * @param array $post
	 * @param PDO $linkpdo
	 */
	public static function updatePlateformPreferences(array $post, PDO $linkpdo) {
	    
		$prefUpdater=$linkpdo->prepare('UPDATE preferences SET patient_code_length=:codeLenght,
                                            name=:name,
                                            admin_email=:email,
                                            email_reply_to=:reply_to,
                                            corporation=:corporation,
                                            address=:address,
                                            parse_date_import=:parse_date_import,
                                            parse_country_name=:parseCountryName,
                                            orthanc_exposed_internal_address=:Orthanc_Exposed_Internal_Address,
                                            orthanc_exposed_internal_port=:Orthanc_Exposed_Internal_Port,
                                            orthanc_exposed_internal_login=:Orthanc_Exposed_Internal_Login,
                                            orthanc_exposed_internal_password=:Orthanc_Exposed_Internal_Password,
                                            orthanc_exposed_external_address=:Orthanc_Exposed_External_Address,
                                            orthanc_exposed_external_port=:Orthanc_Exposed_External_Port,
                                            orthanc_exposed_external_login=:Orthanc_Exposed_External_Login,
                                            orthanc_exposed_external_password=:Orthanc_Exposed_External_Password,
                                            orthanc_pacs_address=:Orthanc_Pacs_Address,
                                            orthanc_pacs_port=:Orthanc_Pacs_Port,
                                            orthanc_pacs_login=:Orthanc_Pacs_Login,
                                            orthanc_pacs_password=:Orthanc_Pacs_Password,
                                            use_smtp=:use_smtp,
                                            smtp_host=:smtp_host,
                                            smtp_port=:smtp_port,
                                            smtp_user=:smtp_user,
                                            smtp_password=:smtp_password,
                                            smtp_secure=:smtp_secure
                                            WHERE 1');
	    
		$prefUpdater->execute(array('codeLenght'=>$post['patientCodeLenght'],
			'corporation'=>$post['coporation'],
			'address'=>$post['webAddress'],
			'email'=>$post['adminEmail'],
			'reply_to'=>$post['replyTo'],
			'name'=>$post['platformName'],
			'parse_date_import'=>$post['parseDateImport'],
			'parseCountryName'=>$post['parseCountryName'],
			'Orthanc_Exposed_Internal_Address'=>$post['orthancExposedInternalAddress'],
			'Orthanc_Exposed_Internal_Port'=>$post['orthancExposedInternalPort'],
			'Orthanc_Exposed_Internal_Login'=>$post['orthancExposedInternalLogin'],
			'Orthanc_Exposed_Internal_Password'=>$post['orthancExposedInternalPassword'],
			'Orthanc_Pacs_Address'=>$post['orthancPacsAddress'],
			'Orthanc_Pacs_Port'=>$post['orthancPacsPort'],
			'Orthanc_Pacs_Login'=>$post['orthancPacsLogin'],
			'Orthanc_Pacs_Password'=>$post['orthancPacsPassword'],
			'use_smtp'=>isset($post['useSmtp']) ? 1 : 0,
			'smtp_host'=>$post['smtpHost'],
			'smtp_port'=>$post['smtpPort'],
			'smtp_user'=>$post['smtpUser'],
			'smtp_password'=>$post['smtpPassword'],
			'smtp_secure'=>$post['smtpSecure'],
			'Orthanc_Exposed_External_Address'=>$post['orthancExposedExternalAddress'],
			'Orthanc_Exposed_External_Port'=>$post['orthancExposedExternalPort'],
			'Orthanc_Exposed_External_Login'=>$post['orthancExposedExternalLogin'],
			'Orthanc_Exposed_External_Password'=>$post['orthancExposedExternalPassword']
		));
	    
	}

	/**
	 * Generate a raw dump of the database for backum purpose
	 */
	public static function dumpDatabase() : String {
        
		$fileSql=tempnam(ini_get('upload_tmp_dir'), 'TMPDB_');
   
		try {
			if (DATABASE_SSL) {
				$dump=new IMysqldump\Mysqldump('mysql:host='.DATABASE_HOST.';dbname='.DATABASE_NAME.'', ''.DATABASE_USERNAME.'', ''.DATABASE_PASSWORD.'', array(), Session::getSSLPDOArrayOptions());
			}else {
				$dump=new IMysqldump\Mysqldump('mysql:host='.DATABASE_HOST.';dbname='.DATABASE_NAME.'', ''.DATABASE_USERNAME.'', ''.DATABASE_PASSWORD.'');   
			}
            
			$dump->start($fileSql);
		}catch (Exception $e) {
			echo 'mysqldump-php error: '.$e->getMessage();
		}

		return $fileSql;

	}

	public static function writeTextAsTempFile($strData) : String {
		$seriesJsonFile=tempnam(ini_get('upload_tmp_dir'), 'TMPGaelOFile_');
		$seriesJsonHandler=fopen($seriesJsonFile, 'w');
		fwrite($seriesJsonHandler, $strData);
		fclose($seriesJsonHandler);

		return $seriesJsonFile;

	}
	
	/**
	 * Generate a JSON File with all Series stored in Orthanc (either marked deleted or not)
	 * For backup purposes
	 */
	public static function dumpOrthancSeriesJSON(PDO $linkpdo) : String {

		$seriesFullJson=json_encode(Global_Data::getAllSeriesOrthancID($linkpdo), JSON_PRETTY_PRINT);
		$seriesJsonFile=Global_Data::writeTextAsTempFile($seriesFullJson);

		return $seriesJsonFile;

	}

	/**
	 * Return file of a folder on the server
	 * Use of Generator
	 * For backup purpose
	 */
	public static function getFileInPath(String $path) {

		if (is_dir($path)) {
			// Create recursive directory iterator
			$files=new RecursiveIteratorIterator(
				new RecursiveDirectoryIterator($path),
				RecursiveIteratorIterator::LEAVES_ONLY
				);
			
			foreach ($files as $name => $file) {
				// Skip directories (they would be added automatically)
				if (!$file->isDir()) {
					// Get real and relative path for current file
					yield $file;
				}
			}
		}
	}
	
	
}