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
 * Build JSON for JSTree with patient's / visit's data
 *
 */

class Tree {
    
  private $json;
  private $role;
  private $username;
  private $study;
  private $studyObject;
  private $linkpdo;
 
  public function __construct(string $role, string $username, string $study, PDO $linkpdo){
      $this->linkpdo=$linkpdo;
      $this->role=$role;
      $this->username=$username;
      $this->study=$study;
      $this->studyObject=new Study($study, $linkpdo);
      
      
  }

  /**
   * Return JSON for JSTree according to role  (patient + Visit)
   * @return array
   */
  public function make_Tree(){
    
    if($this->role == User::INVESTIGATOR){
      //retrieve from DB the patient's list of the requested study and included in user's center or affiliated centers
                
      $patients = $this->linkpdo->prepare(' SELECT patients.code
                                            FROM   patients
                                            WHERE  patients.center IN (SELECT affiliated_centers.center
                                                FROM   affiliated_centers
                                                WHERE  affiliated_centers.username = :username
                                                UNION
                                                SELECT users.center
                                                FROM   users
                                                WHERE  users.username = :username)
                                                AND patients.study = :study
                                                GROUP  BY patients.code');
      
      $patients->execute(array('study' => $this->study,
                               'username' => $this->username));
      
      $patientsCodes = $patients->fetchAll(PDO::FETCH_COLUMN);
      
      foreach ($patientsCodes as $patientCode) {

        $jsonObject['id'] = $patientCode;
        $jsonObject['parent'] = '#';
        $jsonObject['icon'] = '/assets/images/person-icon.png';
        $jsonObject['text'] = $patientCode;
    
        $this->json[] =$jsonObject;
        $this->make_Visit_Tree($patientCode);
     }
     
    } else if($this->role == User::CONTROLLER){
      //Select distinct patient having a study with QC status not done or wait conclusion
      $patients = $this->linkpdo->prepare('SELECT DISTINCT patient_code FROM visits
                                            WHERE (study = :study
                                            AND deleted=0
                                            AND status_done = :done
                                            AND upload_status= :done
                                            AND state_investigator_form= :done
                                            AND (state_quality_control = "Not Done"
                                            OR state_quality_control = "Wait Definitive Conclusion") ) ');
      
      $patients->execute(array('study' => $this->study,
                                'done'=> "Done"));
      
      $dataPatients = $patients->fetchAll(PDO::FETCH_COLUMN);
      
      foreach ($dataPatients as $patientCode) {
        //create a patient entry
        $jsonObject['id'] = $patientCode;
        $jsonObject['parent'] = '#';
        $jsonObject['icon'] = '/assets/images/person-icon.png';
        $jsonObject['text'] = $patientCode;
        $this->json[] =$jsonObject;
        //Add all visits of this patient
        $this->make_Visit_Tree_Controller($patientCode);
      }

    } else if($this->role == User::MONITOR){
        $patients = $this->linkpdo->prepare('SELECT patient_code FROM visits
                                      WHERE (study = :study AND deleted=0)
                                      GROUP BY patient_code');

        $patients->execute(array('study' => $this->study));
        $dataPatients = $patients->fetchAll(PDO::FETCH_COLUMN);
        
        foreach ($dataPatients as $patient) {
          $jsonObject['id'] = $patient;
          $jsonObject['parent'] = '#';
          $jsonObject['icon'] = '/assets/images/person-icon.png';
          $jsonObject['text'] = $patient;

          $this->json[] =$jsonObject;
          $this->make_Visit_Tree($patient);
        }

    } else if($this->role == User::REVIEWER){
        
        $visitObjectList = $this->studyObject->getAwaitingReviewVisit($this->username);
        
        $patientsArray=[];
        
        foreach ($visitObjectList as $visitObject) {
            
            //Check if visit comes from a new patient
            if(  ! in_array($visitObject->patientCode, $patientsArray) ){
                //create a patient entry
                $jsonObject['id'] = $visitObject->patientCode;
                $jsonObject['parent'] = '#';
                $jsonObject['icon'] = '/assets/images/person-icon.png';
                $jsonObject['text'] = $visitObject->patientCode;
                $this->json[] =$jsonObject;
                //Add the add patient in the array list
                $patientsArray[]=$visitObject->patientCode;
            }
            
            //Add the visit entry
            $jsonObjectVisit['id'] = $visitObject->id_visit;
            $jsonObjectVisit['parent'] = $visitObject->patientCode;
            $jsonObjectVisit['icon'] = '/assets/images/report-icon.png';
            $jsonObjectVisit['text'] = $visitObject->visitType;
            //Add review conclusion status in custom attribut (for reviewer filtering)
            $attr['review']=$visitObject->reviewStatus;
            $jsonObjectVisit['li_attr']=$attr;
            
            $this->json[] =$jsonObjectVisit;
            
            
        }
        
    }
    
    
    return  $this->json;
  }

  /**
   * Process the study level for investigator
   * @param $numero_patient
   */
  private function make_Visit_Tree($numero_patient){
  	
    $visites = $this->linkpdo->prepare('SELECT visits.* 
											FROM visits 
											INNER JOIN visit_type ON (visit_type.name=visits.visit_type AND visit_type.study=visits.study) 
										WHERE patient_code=:num_patient AND deleted=0 ORDER BY visit_type.visit_order ');
    $visites->execute(array('num_patient' => $numero_patient));

    //For each visit creates it's node data
     while ($data_visite = $visites->fetch(PDO::FETCH_ASSOC)) {
        //Add upload status / user form in class 
         if( $data_visite['status_done']==Visit::DONE && $data_visite['upload_status'] == Visit::NOT_DONE && $data_visite['state_investigator_form'] != Visit::DONE ){
         $class="NotBoth";
        }
        else if( $data_visite['status_done']==Visit::DONE && $data_visite['state_investigator_form'] !=  Visit::DONE ){
         $class="NotForm";
        }
        else if( $data_visite['status_done']==Visit::DONE && $data_visite['upload_status'] ==  Visit::NOT_DONE ){
         $class="NotUpload";
        }
        else{
         $class="OK";
        }
        
        $attr['class']=$class;
        
        $jsonObject['id'] = $data_visite['id_visit'];
        $jsonObject['parent'] = $numero_patient;
        $jsonObject['icon'] = '/assets/images/report-icon.png';
        $jsonObject['text'] = $data_visite['visit_type'];
        $jsonObject['li_attr'] =$attr;
        
        $this->json[] = $jsonObject;

     }
  }
  
 
  /**
   * For controller display all visits that have been QC or ready for QC
   * @param $numero_patient
   */
  private function make_Visit_Tree_Controller($numero_patient){
      
      $visites = $this->linkpdo->prepare('SELECT * FROM visits INNER JOIN visit_type ON (visit_type.name=visits.visit_type AND visit_type.study=visits.study) 
											WHERE patient_code = :num_patient
											AND deleted=0
											AND status_done ="Done"
											AND upload_status= "Done"
											AND state_investigator_form="Done" 
											ORDER BY visit_type.visit_order');
      $visites->execute(array('num_patient' => $numero_patient));
      
      //For each visit creates it's node data
      while ($data_visite = $visites->fetch(PDO::FETCH_ASSOC)) {
          

          if($data_visite['state_quality_control']==Visit::QC_ACCEPTED || $data_visite['state_quality_control']==Visit::QC_REFUSED){
              $class="OK";  
          }else if($data_visite['state_quality_control']==Visit::QC_NOT_DONE || $data_visite['state_quality_control']==Visit::QC_WAIT_DEFINITVE_CONCLUSION){
              $class="NotBoth";  
          //if corrective action pending, nothing to do for controller do not display this visit
          }else{
              continue;
          }

          $attr['class']=$class;
          $jsonObject['id'] = $data_visite['id_visit'];
          $jsonObject['parent'] = $numero_patient;
          $jsonObject['icon'] = '/assets/images/report-icon.png';
          $jsonObject['text'] = $data_visite['visit_type'];
          $jsonObject['li_attr'] =$attr;
          
          $this->json[] = $jsonObject;
          
      }
  }
  
}