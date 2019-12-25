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
 * Access visit data
 */

class Visit{
    
    private $linkpdo;
    public $id_visit;
    public $creatorName;
    public $creationDate;
    public $qcStatus;
    public $reviewStatus;
    public $statusDone;
    public $reasonForNotDone;
    public $uploadStatus;
    public $uploadDate;
    public $uploaderUsername;
    public $acquisitionDate;
    public $stateInvestigatorForm;
    public $stateQualityControl;
    public $formQualityControl;
    public $imageQualityControl;
    public $newSeriesUpload;
    public $investigatorFormIsCorrected;
    public $otherCorrectiveAction;
    public $correctiveActionDecision;
    public $formQualityComment;
    public $imageQualityComment;
    public $controllerUsername;
    public $controlDate;
    public $correctiveActionUsername;
    public $correctiveActionDate;
    public $study;
    public $patientCode;
    public $visitType;
    public $reviewAvailable;
    public $reviewConclusionDate;
    public $reviewConclusion;
    public $deleted;
    
    public $studyDicomObject;
    
    const QC_NOT_DONE="Not Done";
    const QC_ACCEPTED="Accepted";
    const QC_REFUSED="Refused";
    const QC_WAIT_DEFINITVE_CONCLUSION="Wait Definitive Conclusion";
    const QC_CORRECTIVE_ACTION_ASKED="Corrective Action Asked";
    
    const LOCAL_FORM_NOT_DONE="Not Done";
    const LOCAL_FORM_DRAFT="Draft";
    const LOCAL_FORM_DONE="Done";
    
    const DONE="Done";
    const NOT_DONE="Not Done";
    
    const UPLOAD_PROCESSING="Processing";

    public static function getVisitbyPatientAndVisitName(int $patientCode, String $visitType, PDO $linkpdo){

        $visitQuery = $linkpdo->prepare ( 'SELECT id_visit FROM visits WHERE patient_code=:patientCode AND visit_type=:visitType' );
        
        $visitQuery->execute ( array('patientCode' => $patientCode, 'visitType'=>$visitType) );
        $visitId = $visitQuery->fetch(PDO::FETCH_COLUMN);

        if(empty($visitId)){
            throw new Exception("Visit Non Existing");
        }else{
            return new Visit($visitId, $linkpdo);
        }
        
    }
    
    public function __construct($id_visit, PDO $linkpdo){
        $this->linkpdo=$linkpdo;
        $this->id_visit=$id_visit;
        
        $this->refreshVisitData();
        
    }
    
    /**
     * Refresh this current object proprieties from database
     */
    private function refreshVisitData(){
        
        $visitQuery = $this->linkpdo->prepare ( 'SELECT * FROM visits WHERE id_visit=:idVisit' );
        
        $visitQuery->execute ( array('idVisit' => $this->id_visit) );
        $visitDbData = $visitQuery->fetch(PDO::FETCH_ASSOC);
        
        $this->creatorName=$visitDbData['creator_name'];
        $this->creationDate=$visitDbData['creation_date'];
        
        $this->qcStatus=$visitDbData['state_quality_control'];
        $this->controllerUsername=$visitDbData['controller_username'];
        $this->controlDate=$visitDbData['control_date'];
        
        $this->formQualityControl=$visitDbData['form_quality_control'];
        $this->imageQualityControl=$visitDbData['image_quality_control'];
        $this->formQualityComment=$visitDbData['form_quality_comment'];
        $this->imageQualityComment=$visitDbData['image_quality_comment'];
        
        $this->statusDone=$visitDbData['status_done'];
        $this->reasonForNotDone=$visitDbData['reason_for_not_done'];
        $this->study=$visitDbData['study'];
        $this->patientCode=$visitDbData['patient_code'];
        $this->visitType=$visitDbData['visit_type'];
        
        $this->uploadStatus=$visitDbData['upload_status'];
        $this->acquisitionDate=$visitDbData['acquisition_date'];
        $this->stateInvestigatorForm=$visitDbData['state_investigator_form'];
        $this->stateQualityControl=$visitDbData['state_quality_control'];
        
        $this->newSeriesUpload=$visitDbData['corrective_action_new_upload'];
        $this->investigatorFormIsCorrected=$visitDbData['corrective_action_investigator_form'];
        $this->otherCorrectiveAction=$visitDbData['corrective_action_other'];
        $this->correctiveActionDecision=$visitDbData['corrective_action_decision'];
        
        $this->reviewAvailable=$visitDbData['review_available'];
        
        $this->reviewStatus=$visitDbData['review_status'];
        $this->reviewConclusionDate=$visitDbData['review_conclusion_date'];
        $this->reviewConclusion=$visitDbData['review_conclusion_value'];
        
        $this->correctiveActionUsername=$visitDbData['corrective_action_username'];
        $this->correctiveActionDate=$visitDbData['corrective_action_date'];
        $this->deleted=$visitDbData['deleted'];
        
        if( $this->uploadStatus == Visit::DONE){
            $studyDicomObject=$this->getStudyDicomDetails();
            $this->uploaderUsername=$studyDicomObject->uploaderUsername;
            $this->uploadDate=$studyDicomObject->uploadDate;
        }
        
    }

    public function getImmutableAcquisitionDate(){
        return new DateTimeImmutable($this->acquisitionDate);
    }

    /**
     * return Patient Object of this visit
     * @return Patient
     */
    public function getPatient(){
        return new Patient($this->patientCode, $this->linkpdo);
    }
    
    /**
     * Update upload status of this visit
     * if upload set to done, eventually skip local form/ QC and trgger upload notification method
     * @param string $uploadStatus
     * @param string $username
     */
    public function changeUploadStatus(string $uploadStatus, ?string $username=null){

        $changeStatusUpload=$this->linkpdo->prepare('UPDATE visits SET upload_status= :statusDone WHERE id_visit = :idvisit');
        $changeStatusUpload->execute(array(
            'statusDone'=>$uploadStatus,
            'idvisit'=> $this->id_visit)
            );

        //Update status in this object
        $this->refreshVisitData();
        
        if($uploadStatus==Visit::DONE){
            $this->skipQcIfNeeded();
            $this->sendUploadedVisitEmailToController($username);
        }
    }
    
    /**
     * If Form and / or QC not needed skip these step by writing done in database for each status
     */
    private function skipQcIfNeeded(){
        
        $visitType=$this->getVisitCharacteristics();
        
        if(! $visitType->localFormNeeded || $visitType->qcNeeded) {

            //If QC Not needed validate it
            if( !$visitType->qcNeeded ){
                $this->editQc(true, true, null, null, Visit::QC_ACCEPTED, null);
                
            }
            //If form Not Needed put investigator form to Done
            if(!$visitType->localFormNeeded){
                $this->changeVisitStateInvestigatorForm(Visit::DONE);
            }
            
        }
        
    }
    
    /**
     * Reset QC and corrective action of the visit
     */  
    public function resetQC(){
        $update = $this->linkpdo->prepare('UPDATE visits
								SET state_quality_control = "Not Done",
								controller_username=NULL,
								control_date=NULL,
								image_quality_control=0,
								form_quality_control=0,
								image_quality_comment=NULL,
								form_quality_comment=NULL,
								corrective_action_investigator_form=NULL,
								corrective_action_date=NULL,
								corrective_action_username=NULL,
								corrective_action_other=NULL,
								corrective_action_decision=NULL,
								corrective_action_new_upload=0
								WHERE (id_visit = :id_visit AND deleted=0)');
        
        $update->execute(array('id_visit' => $this->id_visit));
        
        $this->refreshVisitData();
        
    }
    
    /**
     * Return visit type details of this visit
     * @return Visit_Type
     */
    public function getVisitCharacteristics(){
        $visitTypeObject=new Visit_Type($this->linkpdo, $this->study, $this->visitType);
        return $visitTypeObject;  
    }
    
    /**
     * Retrun series Orthanc ID of this visit
     * @param bool $deletedSeries
     * @return String[]
     */
    public function getSeriesOrthancID(bool $deletedSeries=false){
        
        $seriesObjects=$this->getSeriesDetails($deletedSeries);
        
        $orthancSeriesIDs=[];
        
        foreach ($seriesObjects as $seriesObject){
                $orthancSeriesIDs[]=$seriesObject->seriesOrthancID;
        }
        
        return $orthancSeriesIDs;
        
    }
    
    /**
     * Return study details of this visit (only one if calling non deleted)
     * @param bool $deletedStudies
     * @return Study_Details|Study_Details[]
     */
    public function getStudyDicomDetails(bool $deletedStudies=false){
    	
    	$idFetcher = $this->linkpdo->prepare("SELECT Study_Orthanc_ID FROM orthanc_studies
										WHERE deleted=:deleted
										AND id_visit=:idVisit");
    	$idFetcher->execute(array(
    			"idVisit" => $this->id_visit,
    	         "deleted"=>intval($deletedStudies)
    	));

    	$orthancStudyIDs=$idFetcher->fetchAll(PDO::FETCH_COLUMN);

    	$studyDetails=[];
    	foreach ($orthancStudyIDs as $studyOrthancId){
    	    $studyDetails[]=new Study_Details($studyOrthancId, $this->linkpdo);
    	}
    	
    	if(!$deletedStudies && !empty($studyDetails)){
    	    return $studyDetails[0];
    	}
    	
    	return $studyDetails;
    	
    }
    
    /**
     * Return series details of this visit
     * @param bool $deletedSeries
     * @return Series_Details[]
     */
    public function getSeriesDetails(bool $deletedSeries=false){
        
        $orthancSeriesObjects=[];
        
        $studyDicomObject=$this->getStudyDicomDetails();
        
        if(!empty($studyDicomObject)){
            $childSeriesObjects=$studyDicomObject->getChildSeries();
            foreach ($childSeriesObjects as $seriesObject){
                if($seriesObject->deleted == $deletedSeries ){
                    $orthancSeriesObjects[]=$seriesObject;
                }
            }
        }
        
        return $orthancSeriesObjects;
        
    }
    
    
    /**
     * Return reviews (local or reviewer) of this visit
     * @param bool $local
     * @return Review|Review[]
     */
    public function getReviewsObject(bool $local){
        
        $reviewQuery = $this->linkpdo->prepare ( 'SELECT id_review FROM reviews WHERE id_visit=:idVisit AND deleted=0 AND is_local=:isLocal' );
        
        $reviewQuery->execute ( array('idVisit' => $this->id_visit, 'isLocal'=>intval($local)) );
        $reviewResults = $reviewQuery->fetchAll(PDO::FETCH_COLUMN);
        
        if($local && sizeof($reviewResults)==1){
            return new Review($reviewResults[0], $this->linkpdo);
        }
        //Else put review object in an Array
        $reviewObjects=[];
        foreach ($reviewResults as $reviewID){
            $reviewObjects[]=new Review($reviewID, $this->linkpdo);
        }
        
        return $reviewObjects;
        
    }
    
    
    /**
     * return the existing reviewer review for this visit
     * @return null if no review for this reviewer in this visit
     */
    public function queryExistingReviewForReviewer($username){
        $reviewsObjects=$this->getReviewsObject(false);
        
        foreach ($reviewsObjects as $review){
            if($review->username==$username){
                return $review;
            }
        }
        //If not found return null
        return null;
        
    }

    /**
     * Check that no activated visit exsits for this visit
     * @return boolean
     */
    private function isNoOtherActivatedVisit(){
    	$visitQuery = $this->linkpdo->prepare('SELECT id_visit FROM visits
                                        WHERE visits.study=:study AND visits.visit_type=:visitType AND visits.patient_code=:patientCode AND visits.deleted=0;
                                    ');
    	$visitQuery->execute(array('study' => $this->study,
    			'visitType'=> $this->visitType,
    			'patientCode'=>$this->patientCode ));
    	
    	$dataVisits = $visitQuery->fetchAll(PDO::FETCH_COLUMN);
    	
    	if(empty($dataVisits)){
    		return true;
    	}else{
    		return false;
    	}
    	
    }
    
    /**
     * Update quality control data
     * @param bool $formAccepted
     * @param bool $imageAccepted
     * @param $formComment
     * @param $imageComment
     * @param $controlDecision
     * @param $usernameController
     */
    public function editQc( bool $formAccepted, bool $imageAccepted, $formComment, $imageComment, $controlDecision, $usernameController  ){
        
        $req_update = $this->linkpdo->prepare('UPDATE visits
                                        SET form_quality_control = :form,
                                        image_quality_control = :image,
                                        form_quality_comment = :form_comment,
                                        image_quality_comment = :image_comment,
                                        state_quality_control= :decision,
                                        controller_username=:username,
                                        control_date=:date
                                        WHERE id_visit = :id_visit');
        
        $req_update->execute(array(
            'id_visit' => $this->id_visit,
            'form' => intval($formAccepted),
            'image' => intval($imageAccepted),
            'form_comment' => $formComment,
            'image_comment' => $imageComment,
            'decision' =>$controlDecision,
            'username'=>$usernameController,
            'date'=>date("Y-m-d H:i:s")
        ));
        
        $this->refreshVisitData();
        
        if($controlDecision==Visit::QC_ACCEPTED){
            if( $this->getVisitCharacteristics()->reviewNeeded){
                //If review needed make it available for reviewers
                $this->changeReviewAvailability(true);
            }else{
                //The visit is QC accepted and will not go further as Review is not needed
                //Inform supervisors that the visit is well recieved
                $this->sendUploadNotificationToSupervisor();
            }
        }
        
    }
    
    /**
     * update corrective action data
     * @param bool $newSeries
     * @param bool $formCorrected
     * @param bool $correctiveActionDecision
     * @param string $otherComment
     * @param string $username
     */
    public function setCorrectiveAction(bool $newSeries, bool $formCorrected, bool $correctiveActionDecision, string $otherComment, string $username){
        //Write in the database
        $req_update = $this->linkpdo->prepare ( 'UPDATE visits
                                          SET corrective_action_username=:username,
											  corrective_action_date=:date,
											  corrective_action_new_upload = :new_series,
                                              corrective_action_other = :other_comment,
                                              corrective_action_investigator_form = :invest_form,
											  corrective_action_decision=:corrective_action,
											  state_quality_control="Wait Definitive Conclusion"
                                          WHERE visits.id_visit = :id_visit' );
        
        $req_update->execute ( array (
            'username'=>$username,
            'date'=>date("Y-m-d H:i:s"),
            'id_visit' => $this->id_visit,
            'new_series' => intval($newSeries),
            'other_comment' => $otherComment,
            'invest_form' => intval($formCorrected),
            'corrective_action'=>intval($correctiveActionDecision)
        ));
        
        $this->refreshVisitData();
    }
    
    /**
     * Delete / reactivate visit
     * @param bool $delete
     * @return boolean
     */
    public function changeDeletionStatus(bool $delete){
    	
    	//If reactivation of Visit check that not other same activated visit type exist in the DB for this patient
    	if($delete==false && !$this->isNoOtherActivatedVisit()){
    		return false;
    	}
    	
        $connecter = $this->linkpdo->prepare('UPDATE visits SET deleted=:deletion WHERE id_visit = :idvisit');
        $connecter->execute(array(
            "idvisit" => $this->id_visit,
            "deletion"=>intval($delete)
        ));
        
        $this->refreshVisitData();
        
        return true;
    }
    
    /**
     * Update the Local Investigator Form status
     * If Done inform controller that visit await QC (sent if Form and upload done and QC not done)
     * @param string $status see visit constant (Not Done, Draft or Done)
     * @param string $username
     */
    public function changeVisitStateInvestigatorForm(string $status, ?string $username=null){
        //Update Visit table
        $update = $this->linkpdo->prepare('UPDATE visits SET
                                        state_investigator_form = :formStatus WHERE id_visit = :id_visit');
        
        $update->execute( array( 'formStatus'=>$status,'id_visit' => $this->id_visit) );
        
        //Update data in this object
        $this->refreshVisitData();
        
        if($status==Visit::LOCAL_FORM_DONE ){
            $this->sendUploadedVisitEmailToController($username);
        }
    }
    
    /**
     * Update the review availability status according to boolean argument
     * @param boolean $available
     */
    public function changeReviewAvailability(bool $available){
        $dbStatus = $this->linkpdo->prepare('UPDATE visits SET review_available =:available WHERE visits.id_visit = :idVisit');
        $dbStatus->execute(array ('idVisit'=>$this->id_visit, 'available'=>intval($available)));
        
        $this->refreshVisitData();
        
        if($available){
            //If available notify reviewers of this study by email
            $this->sendAvailableReviewMail();
        }
    }
    
    /**
     * Update review conclusion
     * @param string $reviewStatus
     * @param $conclusionValue
     */
    public function changeVisitValidationStatus(string $reviewStatus, $conclusionValue=null){
        
        if($reviewStatus==Visit::DONE){
            $date=date("Y-m-d H:i:s");
        }
        
        $dbStatus = $this->linkpdo->prepare('UPDATE visits SET 
                                            review_status = :conclusionStatus, 
                                            review_conclusion_value=:conclusionValue, 
                                            review_conclusion_date=:conclusionDate 
                                            WHERE visits.id_visit = :id_visit');
        $dbStatus->execute(array(
            'id_visit' => $this->id_visit,
            'conclusionStatus'=> $reviewStatus,
            'conclusionDate'=>isset($date) ? $date : null,
            'conclusionValue'=>isset($conclusionValue) ? $conclusionValue : null
        ));
        
        $this->refreshVisitData();
        
    }
    
    /**
     * Return the object sepecific instance for this visit to manage users form data
     * @param boolean $local
     * @param string $username
     * @return Form_Processor
     */
    public function getFromProcessor(bool $local, string $username){
        //Destination of the specific post processing POO
        $specificObjectFile=$_SERVER["DOCUMENT_ROOT"]."/data/form/Poo/$this->study"."_"."$this->visitType.php";
        
        $formProcessor=null;
        
        if(is_file($specificObjectFile)){
            require($specificObjectFile);
            $objectName=$this->study."_".$this->visitType;
            $formProcessor = new $objectName($this, $local, $username, $this->linkpdo);
        }
    	
    	return $formProcessor;
    	
    }
    
    /**
     * Send uploaded confirmation to supervisors and uploader
     * will send email only if uploaded dicom and local form done and QC Not Done
     * @param string $username
     * @return boolean
     */
    private function sendUploadedVisitEmailToController(?string $username){
        
        $emailObject=new Send_Email($this->linkpdo);

        $message = "The following visit has been uploaded on the platform: <br>
                  Patient Number : ".$this->patientCode."<br>
                  Uploaded visit : ".$this->visitType."<br>";
        
        $emailObject->setMessage($message);
        
        if($this->uploadStatus==Visit::DONE 
            && $this->stateInvestigatorForm==Visit::DONE 
            && $this->stateQualityControl==Visit::NOT_DONE){
			//Inform Controllers that Visit is uploaded and awaiting QC
            $emailsController=$emailObject->getRolesEmails(User::CONTROLLER, $this->study);
            $emailsMonitor=$emailObject->getRolesEmails(User::MONITOR, $this->study);
            $emailsSupervisor=$emailObject->getRolesEmails(User::SUPERVISOR, $this->study);
            $email=array_merge($emailsController, $emailsMonitor, $emailsSupervisor);
            if($username!=null) {
                $email[]=$emailObject->getUserEmails($username);
            }
            error_log("UploadEmails".implode(';', $email));
            $emailObject->sendEmail($email, $this->study.' - New upload');
            return true;
            
        } else {
        	return false;
        }
    }
    
    /**
     * Send emails to reviewers saying the visit is available for review
     */
    private function sendAvailableReviewMail(){
        
        $emailObject=new Send_Email($this->linkpdo);
        
        $message = "The following visit is ready for review in the platform: <br>
                  Patient Number : ".$this->patientCode."<br>
                  Uploaded visit : ".$this->visitType."<br>";
        
        $emailObject->setMessage($message);
        
        $email=$emailObject->getRolesEmails(User::REVIEWER, $this->study);
        
        error_log("ReviewEmailNotification".implode(';', $email));
        
        $emailObject->sendEmail($email, $this->study.' - Visit Awaiting Review');

        
    }
    
    /**
     * Send emails to supervisors when visit recieved and QC done and does not need review process
     */
    private function sendUploadNotificationToSupervisor(){
        
        $emailObject=new Send_Email($this->linkpdo);
        
        $message = "The following visit has been uploaded to the platform: <br>
                  Patient Number : ".$this->patientCode."<br>
                  Uploaded visit : ".$this->visitType."<br>";
        
        $emailObject->setMessage($message);
        
        $email=$emailObject->getRolesEmails(User::SUPERVISOR, $this->study);
        
        error_log("SupervisorEmailNotification".implode(';', $email));
        
        $emailObject->sendEmail($email, $this->study.' - Visit Recieved');
        
    }
    
    /**
     * Return study object associated with this visit
     * @return Study
     */
    public function getParentStudyObject(){
        return new Study($this->study, $this->linkpdo);
    }
    
    /**
     * Return if the review is awaiting a review form for a specific reviewer
     * @param string $username
     * @return boolean
     */
    public function isAwaitingReviewForReviewerUser(string $username){
        
        $reviewForReviwer=$this->queryExistingReviewForReviewer($username);
        if(empty($reviewForReviwer)) return true; else return false;
        
    }
    
    /**
     * Create a new visit
     * @param $visitType
     * @param $study
     * @param $patientCode
     * @param $statusDone
     * @param $reasonNotDone
     * @param $acquisitionDate
     * @param $username
     * @param $linkpdo
     * @return string
     */
    public static function createVisit($visitType, $study, $patientCode, $statusDone, $reasonNotDone, $acquisitionDate, $username, PDO $linkpdo){
        
        //Add visit verifying that this visit doesn't already have an active visite registered
        $insertion = $linkpdo->prepare ( 'INSERT INTO visits(study, visit_type, status_done, patient_code, reason_for_not_done, acquisition_date, creator_name, creation_date)
      										SELECT :study, :type_visite, :status_done, :patient_code, :reason, :acquisition_date, :creator_name, :creation_date FROM DUAL
											WHERE NOT EXISTS (SELECT id_visit FROM visits WHERE patient_code=:patient_code AND study=:study AND visit_type=:type_visite AND deleted=0) ' );
        
        $insertion->execute ( array (
            'study'=>$study,
            'type_visite' => $visitType,
            'status_done' => $statusDone,
            'patient_code' => $patientCode,
            'reason' => $reasonNotDone,
            'acquisition_date' => $acquisitionDate,
            'creator_name' => $username,
            'creation_date' => date("Y-m-d H:i:s")
        ) );
        
        $createdId=$linkpdo->lastInsertId();
        
        return $createdId;
    }
    
}