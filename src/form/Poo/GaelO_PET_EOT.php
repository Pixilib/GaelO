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
 * Specific object for study_visit, which extend the abstract class Form_Processor
 *
 */
class GaelO_PET_EOT extends Form_Processor {
    
    public function __construct($idVisit, $local, $username, $linkpdo){
        parent::__construct($idVisit, $local, $username, $linkpdo);
    }
    
    /**
     * Fill the specific table
     * @param $data
     * @param $id_Review
     * @param $specificTable
     */
    protected function saveSpecificForm($inputData, $id_review, $specificTable, $update) {
        
        $expectedValues=array('reviewer','glycemia', 'biopsy', 'biopsyLocation', 'biopsyDate','surgery','surgeryLocation',
            'surgeryDate', 'infection', 'infectionLocation', 'infectionDate', 'suvMaxTumor',
            'suvMaxTumorLocation', 'suvMaxHepatic', 'suvMaxMediastinum', 'boneMarrowInvolvement', 'deauville',
            'nodalExtraNodal','newLesion','lugano', 'comment'
        );
        
        if($this->local){
            $expectedValues[]='reviewer';
        }
        
        foreach ($expectedValues as $expected){
            if(! array_key_exists($expected, $inputData) || empty($inputData[$expected])){
                $inputData[$expected]=null;
            }
        }
        
        // Draft exist, we update the draft
        if ($update){
            $req_update = $this->linkpdo->prepare ( 'UPDATE ' . $specificTable . '
                              SET reviewer=:reviewer,
                                  glycemia = :glycemia,
                                  recentBiopsy = :biopsy,
								  biopsyLocation=:biopsyLocation,
									biopsyDate=:biopsyDate,
									recentSurgery=:surgery,
									surgeryLocation=:surgeryLocation,
									surgeryDate=:surgeryDate,
									recentInfection=:infection,
									infectionLocation=:infectionLocation,
									infectionDate=:infectionDate,
									suvMaxTumoral=:suvMaxTumoral,
									suvMaxTumoralLocation=:suvMaxTumoralLocation,
									suvMaxHepatic=:suvMaxHepatic,
									suvMaxMediastinum=:suvMaxMediastinum,
                                    boneMarrowInvolvment=:boneMarrowInvolvment,
                                    deauville=:deauville,
                                    nodalExtraNodal=:nodalExtraNodal,
                                    newLesion=:newLesion,
                                    lugano=:lugano,
									comment=:comment
                                WHERE id_review = :id_review' );
            
            $req_update->execute(array(
                'reviewer'=>$inputData['reviewer'],
                'glycemia' => $inputData['glycemia'],
                'biopsy' => intval($inputData['biopsy']),
                'biopsyLocation' => isset($inputData['biopsyLocation']) ? $inputData['biopsyLocation'] : null,
                'biopsyDate' =>isset($inputData['biopsyDate']) ? $inputData['biopsyDate'] : null,
                'surgery' =>intval($inputData['surgery']),
                'surgeryLocation' =>isset($inputData['surgeryLocation']) ? $inputData['surgeryLocation'] : null,
                'surgeryDate' =>isset($inputData['surgeryDate']) ? $inputData['surgeryDate'] : null,
                'infection' =>intval($inputData['infection']),
                'infectionLocation' =>isset($inputData['infectionLocation']) ? $inputData['infectionLocation'] : null,
                'infectionDate' =>isset($inputData['infectionDate']) ? $inputData['infectionDate'] : null,
                'suvMaxTumoral' =>$inputData['suvMaxTumor'],
                'suvMaxTumoralLocation' =>$inputData['suvMaxTumorLocation'],
                'suvMaxHepatic' =>$inputData['suvMaxHepatic'],
                'suvMaxMediastinum'=>$inputData['suvMaxMediastinum'],
                'boneMarrowInvolvment'=>intval($inputData['boneMarrowInvolvement']),
                'comment' =>isset($inputData['comment']) ? $inputData['comment'] : null,
                'deauville' =>$inputData['deauville'],
                'nodalExtraNodal'=>$inputData['nodalExtraNodal'],
                'newLesion' =>intval($inputData['newLesion']),
                'lugano' =>$inputData['lugano'],
                'id_review' => $id_review ));
            
        }else{
            //Draft doesn't exist we create a new entry in the specific table
            $insertion = $this->linkpdo->prepare('INSERT INTO '.$specificTable.' (id_review, reviewer, glycemia, recentBiopsy,
										biopsyLocation, biopsyDate, recentSurgery, surgeryLocation, surgeryDate, recentInfection,
										infectionLocation, infectionDate, suvMaxTumoral, suvMaxTumoralLocation,
										suvMaxHepatic, suvMaxMediastinum, boneMarrowInvolvment, deauville,nodalExtraNodal,newLesion,lugano, comment)
                                        VALUES (:id_review, :reviewer, :glycemia, :biopsy, :biopsyLocation, :biopsyDate, :surgery, :surgeryLocation, :surgeryDate,
												:infection, :infectionLocation, :infectionDate, :suvMaxTumoral, :suvMaxTumoralLocation,
												:suvMaxHepatic, :suvMaxMediastinum, :boneMarrowInvolvment ,:deauville, :nodalExtraNodal, :newLesion, :lugano, :comment)');
            
            $insertion->execute(array(
                'id_review' => $id_review,
                'reviewer' => $inputData['reviewer'],
                'glycemia' => $inputData['glycemia'],
                'biopsy' =>intval($inputData['biopsy']),
                'biopsyLocation' =>isset($inputData['biopsyLocation']) ? $inputData['biopsyLocation']: null,
                'biopsyDate' =>isset($inputData['biopsyDate']) ? $inputData['biopsyDate']: null,
                'surgery' => intval($inputData['surgery']),
                'surgeryLocation' => isset($inputData['surgeryLocation']) ? $inputData['surgeryLocation']:null,
                'surgeryDate' => isset($inputData['surgeryDate']) ? $inputData['surgeryDate']:null,
                'infection' =>intval($inputData['infection']),
                'infectionLocation' =>isset($inputData['infectionLocation']) ? $inputData['infectionLocation']: null,
                'infectionDate' =>isset($inputData['infectionDate']) ? $inputData['infectionDate'] : null,
                'suvMaxTumoral' =>$inputData['suvMaxTumor'],
                'suvMaxTumoralLocation' =>$inputData['suvMaxTumorLocation'],
                'suvMaxHepatic' =>$inputData['suvMaxHepatic'],
                'suvMaxMediastinum'=>$inputData['suvMaxMediastinum'],
                'boneMarrowInvolvment'=>intval($inputData['boneMarrowInvolvement']),
                'deauville' =>$inputData['deauville'],
                'nodalExtraNodal'=>$inputData['nodalExtraNodal'],
                'newLesion' =>intval($inputData['newLesion']),
                'lugano' =>$inputData['lugano'],
                'comment' =>isset($inputData['comment']) ? $inputData['comment'] : null
            ));
        }
        
    }
    
    /**
     * Define Review validation rule
     * Should also define status "Not Done" as default answer as this method will be called at review delete as well
     * {@inheritDoc}
     * @see Form_Processor::setVisitValidation()
     */
    public function setVisitValidation(){
        
        //If adjudication, take adjudicator decision as final decision
        if($this->reviewStatus==Form_Processor::WAIT_ADJUDICATION){
            $adjudicatorDecision=$this->rawDataForm['lugano'];
            $this->changeVisitValidationStatus(Form_Processor::DONE, $adjudicatorDecision);
            return;
        }
        
        $datas=$this->getAllValidatedFormsOfVisit();
        
        if(sizeof($datas)>=3){
            
            $conclusions=[];
            foreach ($datas as $review){
                $conclusions[]=$review['lugano'];
            }
            
            $counts = array_count_values($conclusions);
            $concordance=false;
            $majorityVote=null;
            foreach ($counts as $key=>$conclusion){
                //if one result reached two identical reading set status to Done
                if ($conclusion >= 2){
                    $concordance=true;
                    $majorityVote=$key;
                }
            }
            
            if(!empty($majorityVote)){
                $this->changeVisitValidationStatus(Form_Processor::DONE, $majorityVote);
            }else{
                $this->changeVisitValidationStatus(Form_Processor::WAIT_ADJUDICATION);
            }
            
        }else if(sizeof($datas)>=2){
            $this->changeVisitValidationStatus(Form_Processor::ONGOING);
            
        }else{
            $this->changeVisitValidationStatus(Form_Processor::NOT_DONE);
            
        }
        
    }
    
}