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
 * Center management in the plateform
 */

Class Center{
	public $name;
	public $code;
	public $countryCode;
	public $countryName;
	private $linkpdo;

	/**
	 * Construct the center from center code
	 * @param PDO $linkpdo
	 * @param $code
	 * @throws Exception
	 */
	public function __construct(PDO $linkpdo, $code){
	    
	    $centerQuery = $linkpdo->prepare('SELECT * FROM centers WHERE code=:code');
	    $centerQuery->execute(array('code'=>$code));
	    $center=$centerQuery->fetch(PDO::FETCH_ASSOC);
	    
	    if(empty($center)){
	        throw new Exception("Non Existing Center");
	    }
	    $this->linkpdo=$linkpdo;
	    $this->name=$center['name'];
	    $this->code=$center['code'];
	    $this->countryCode=$center['country_code'];
	    $this->countryName=Global_Data::getCountryName($linkpdo, $this->countryCode);
	}

	/**
	 * Update Center with new name and new country code
	 * @param string $name
	 * @param string $countryCode
	 */
	public function updateCenter(string $name, string $countryCode){
	    $updatePatient = $this->linkpdo->prepare("UPDATE centers
                                            SET name = :centerName,
                                            country_code = :countryCode
                                            WHERE code = :code");
	    
	    $updatePatient->execute(array('centerName'=>$name,
	        'countryCode'=>$countryCode,
	        'code'=>$this->code));
	    
	}

	/**
	 * Create a new center in the database
	 * @param PDO $linkpdo
	 * @param int $code
	 * @param $name
	 * @param string $countryCode
	 */
	public static function addCenter(PDO $linkpdo, $code, String $name, String $countryCode){
		$insertion = $linkpdo->prepare('INSERT INTO centers (code, name, country_code) VALUES (:code, :name, :countryCode)' );
		$insertion->execute(array(
				'name' => $name,
				'code' => $code,
		        'countryCode' =>$countryCode
		));
	}
}