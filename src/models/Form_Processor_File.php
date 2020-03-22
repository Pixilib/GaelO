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

    function __construct(Visit $visitObject, bool $local, string $username, PDO $linkpdo){
        parent::__construct($visitObject, $local, $username, $linkpdo);
    }


	private function getAssociatedFileArrayInDb(){
		$querySentFile = $this->linkpdo->prepare('SELECT sent_files FROM '.$this->specificTable.' WHERE id_review = :idReview ');
        $querySentFile->execute(array('id_review' => $this->reviewObject->id_review));
		$sentFileString=$querySentFile->fetch(PDO::FETCH_COLUMN);
		
		return json_decode($sentFileString);
	}

	/**
	 * Update the file array column
	 * File array should be an associative array following 
	 * key => filename
	 */
	private function updateAssociatedFileArrayInDB($fileArray){
		try{
			$updateRequest = $this->linkpdo->prepare('UPDATE '.$this->specificTable.'
                              SET sent_files = :sent_files
								WHERE id_review = :id_review');
		
			$answer = $updateRequest->execute(array( 'id_review' => $this->reviewObject->id_review , 
													'sent_files' => json_encode($fileArray) ));
		}catch( Exception $e){
			return false;
		}
		return $answer;

    }

    private function getPath(){
        $path = $_SERVER['DOCUMENT_ROOT'] . '/data/upload/attached_review_file/'.$this->study;
        return $path;
    }

    /**
     * Can be overwitten if need to allow other extensions
     */
    protected function getAllowedExtension() : array {
        return array('.csv');
    }
    
    protected function getMaxSizeMb() : int{
        //NB : if more than 5Mb need to update root htaccess
        return 5;
    }

	/**
	 * Store or overwirte a file, each file is defined by a Key (visit specific)
	 */
	protected function storeAssociatedFile($key, $uploadedFile){

		//If first form upload create a draft form to insert file uploaded data
		if(empty($this->reviewObject)){
			$this->createReview();
		}

        $fileArray = $this->getAssociatedFileArrayInDb();
        
		//Get extension of file and check extension is allowed
		//Get filesize and check it matches limits
        $extension= strrchr($uploadedFile, '.');
        $bytes = filesize($uploadedFile);
        $sizeMb = $bytes / 1048576 ;
        if($sizeMb > $this->getMaxSizeMb) throw new Exception('File over limits');
        if( !in_array($extension, $this->getAllowedExtension()) ) throw new Exception('Extension not allowed') ;

        $fileName= $this->visitObject->patientCode.'_'.$this->visitObject->visitType.'_'.$key.$extension;

        $path = $this->getPath();
        if ( !is_dir($path) ) {
            mkdir($path, 0755, true);
        }
        //Copy file to finale destination with final name
        copy($uploadedFile, $path.'/'.$fileName);
        
		//Add or overide file key and write to database
		$fileArray[$key] = $uploadedFile;
		$this->updateAssociatedFileArrayInDB($fileArray);

	}

    /**
     * Delete an associative file
     */
	protected function deleteAssociatedFile($fileKey){

        $fileArray = $this->getAssociatedFileArrayInDb();
        unlink($this->getPath().'/'.$fileArray[$fileKey]);
        unset($fileArray[$fileKey]);
        $this->updateAssociatedFileArrayInDB($fileArray);

	}

    /**
     * Return file destination of an associated file
     */
	protected function getAssociatedFile($fileKey) : String {

        $fileArray = $this->getAssociatedFileArrayInDb();
        return $this->getPath().'/'.$fileArray[$fileKey];
	}
	


}