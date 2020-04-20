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
 * Abstract class to handle review in the system
 * Each Study-Visit should extend this abstract class and redifine the two abstract methods
 * - saveSpecificForm : Which recieve the raw form and should write data to the specific table of database
 * - setVisitValidation : Which should define the rules to change status of review's visit (ex : Wait Adjudication or Done)
 * 
 * These two methodes will be launched for each new review recieved by the system
 * 
 * The makeReviewUnavailable() can be overrided to let the visit available for review even if Review status "Done" is reached 
 * (by default it will no longer accept new review after reaching this status)
 * @author salim
 *
 */

abstract class Form_Processor_File extends Form_Processor {

	function __construct(Visit $visitObject, bool $local, string $username, PDO $linkpdo) {
		parent::__construct($visitObject, $local, $username, $linkpdo);
	}

	//Should return an array of string of allowed key files
	abstract function getAllowedFileKeys() : array;

	/**
	 * Can be overwitten if need to allow other MIME Type
	 */
	protected function getAllowedType() : array {
		return array('text/csv');
	}
    
	protected function getMaxSizeMb() : int{
		//NB : if more than 5Mb need to update root htaccess
		return 5;
	}

	/**
	 * Store or overwirte a file, each file is defined by a Key (visit specific)
	 */
	public function storeAssociatedFile(string $fileKey, string $mime, int $fileSize, string $uploadedTempFile) {

		//If first form upload create a draft form to insert file uploaded data
		if (empty($this->reviewObject)) {
			$this->createReview();
		}else {
			//If review exist but validated throw exception
			if ($this->reviewObject->validated) {
				throw new Exception('Validated Review, can\'t add File');
			}
		}
        
		//Get extension of file and check extension is allowed
		//Get filesize and check it matches limits
		$sizeMb=$fileSize/1048576;
		if ($sizeMb > $this->getMaxSizeMb()) throw new Exception('File over limits');
		if (!$this->isInDeclaredKey($fileKey)) throw new Exception('Unhauthrized file key'); 
		if (!$this->isInAllowedType($mime)) throw new Exception('Extension not allowed');

		$mimes=new \Mimey\MimeTypes;
		$extension=$mimes->getExtension($mime);
		$fileName=$this->visitObject->patientCode.'_'.$this->visitObject->visitType.'_'.$fileKey.'.'.$extension;

		$associatedFinalFile=$this->reviewObject->storeAssociatedFile($uploadedTempFile, $fileName);
        
		//Add or overide file key and write to database
		$fileArray=$this->reviewObject->associatedFiles;
		$fileArray[$fileKey]=$associatedFinalFile;
		$this->reviewObject->updateAssociatedFiles($fileArray);

	}
    
	private function isInDeclaredKey(string $fileKey) {
		return in_array($fileKey, $this->getAllowedFileKeys());
	}

	private function isInAllowedType(string $extension) {
		return in_array($extension, $this->getAllowedType());
	}


}