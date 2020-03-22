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
 * Access Data of a review
 */
Class Review{
    
    private $linkpdo;
    public $id_visit;
    public $username;
    public $reviewDate;
    public $reviewDateObject;
    public $validated;
    public $isLocal;
    public $isAdjudication;
    public $deleted;
    public $id_review;
    
    
    public function __construct($id_review, PDO $linkpdo){
        $this->linkpdo=$linkpdo;
        $this->id_review=$id_review;
        $dataFetcher = $this->linkpdo->prepare('SELECT * FROM reviews WHERE id_review=:idReview');
        $dataFetcher->execute(array(
            "idReview" => $id_review,
        ));
        $reviewData = $dataFetcher->fetch(PDO::FETCH_ASSOC);
        
        $this->id_visit=$reviewData['id_visit'];
        $this->username=$reviewData['username'];
        $this->reviewDate=$reviewData['review_date'];
        $this->reviewDateObject=new DateTimeImmutable($reviewData['review_date']);
        $this->validated=intval($reviewData['validated']);
        $this->isLocal=intval($reviewData['is_local']);
        $this->deleted=$reviewData['deleted'];
        $this->isAdjudication=$reviewData['is_adjudication'];
        
       
        
    }
    
    /**
     * Return specific data as an associative array (database answer)
     * @return mixed
     */
    public function getSpecificData(){
        
        $visitObject=$this->getParentVisitObject();
        $visitCharacteristics=$visitObject->getVisitCharacteristics();
        $dataFetcher = $this->linkpdo->prepare('SELECT * FROM '.$visitCharacteristics->tableReviewSpecificName.' WHERE id_review=:idReview');
        $dataFetcher->execute(array(
            "idReview" => $this->id_review,
        ));
        $reviewData = $dataFetcher->fetch(PDO::FETCH_ASSOC);
        
        return $reviewData;
        
    }
    
    /**
     * Return user who has made this review
     * @return User
     */
    public function getUserObject(){
        return new User($this->username, $this->linkpdo);
    }
    
    /**
     * Return parent visit Object
     * @return Visit
     */
    public function getParentVisitObject(){
        $visitsObject=new Visit($this->id_visit, $this->linkpdo);
        return $visitsObject;
    }
    
    
    /**
     * Set current review to deleted
     */
    public function deleteReview(){
    	
    	$visitObject=$this->getParentVisitObject();
    	if($this->isLocal && ($visitObject->stateQualityControl == Visit::QC_ACCEPTED || $visitObject->stateQualityControl ==Visit::QC_REFUSED)) {
    		throw new Exception("Can't delete Local form with QC done");
    	}

        $update = $this->linkpdo->prepare('UPDATE reviews SET deleted=1 WHERE id_review = :id_review');
        $update->execute(array('id_review' => $this->id_review));

        //If local form have beed destroyed, reset QC and mark investigator form Not Done
        if($this->isLocal){
            $visitObject->resetQC();
            $visitObject->changeVisitStateInvestigatorForm(Visit::LOCAL_FORM_NOT_DONE);
        }else{
        	$formProcessor=$visitObject->getFromProcessor($this->isLocal, $this->username);
        	$formProcessor->setVisitValidation();
        }
        
        
    }

    public function updateReviewDate(){
        $update = $this->linkpdo->prepare('UPDATE reviews SET review_date = :reviewdate WHERE id_review = :idReview');
        $update->execute( array( 'reviewdate'=> date("Y-m-d H:i:s"), 'idReview' => $this->id_review ) );

    }

    public function changeReviewValidationStatus(bool $validate){

        $update = $this->linkpdo->prepare('UPDATE reviews SET validated = :validated WHERE id_review = :idReview');
        $update->execute( array( 'validated'=> intval($validate), 'idReview' => $this->id_review ) );

        if($validate){
            $this->updateReviewDate();
        }
    }

    /**
     * In case of failure of writing specific form.
     * Only used in form processor
     */
    public function hardDeleteReview() {

        $dbStatus = $this->linkpdo->prepare('DELETE FROM reviews WHERE id_review=:idReview');
	    $dbStatus->execute(array ('idReview'=> $this->id_review));

    }
    
    /**
     * Unlock the current form
     * @throws Exception
     */
    public function unlockForm(){
    	
    	$visitObject=$this->getParentVisitObject();
    	if($this->isLocal && ($visitObject->stateQualityControl == Visit::QC_ACCEPTED || $visitObject->stateQualityControl ==Visit::QC_REFUSED)) {
    		throw new Exception("Can't Unlock Local form with QC done");
    	}
    	
        //Update review table
        $this->changeReviewValidationStatus(false);
        
        if($this->isLocal) {
        	$visitObject->changeVisitStateInvestigatorForm(Visit::LOCAL_FORM_DRAFT);
        }else{
        	$formProcessor=$visitObject->getFromProcessor($this->isLocal, $this->username);
        	$formProcessor->setVisitValidation();
        }
        
    }


    	/*
	 * Create new entry in review table
	 */
	public static function createReview(int $id_visit, string $username, bool $local, bool $adjudication, PDO $linkpdo) : Review {
	    
	    $newReview = $linkpdo->prepare('INSERT INTO reviews(id_visit, username, review_date, validated , is_local, is_adjudication) VALUES (:idvisit, :username, :reviewdate, :validated, :local, :adjudication)');
	    $newReview->execute(array(
	        'idvisit' =>$id_visit,
	        'username'=>$username,
	        'reviewdate'=>date("Y-m-d H:i:s"),
	        'validated'=> 0,
	        'local'=>intval($local),
	        'adjudication'=>intval($adjudication)
	    ));
        $idReview=$linkpdo->lastInsertId();
        
	    $reviewObject = new Review($idReview, $linkpdo);
	    return $reviewObject;
	}
    
}