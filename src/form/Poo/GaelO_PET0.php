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
class GaelO_PET0 extends Form_Processor {
	
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
	        'suvMaxTumorLocation', 'suvMaxHepatic', 'suvMaxMediastinum', 'boneMarrowInvolvement',
	        'comment'
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
									comment=:comment
                                WHERE id_review = :id_review' );
			
			$req_update->execute(array(
                    'reviewer'=>$inputData['reviewer'],
					'glycemia' => $inputData['glycemia'],
					'biopsy' =>intval($inputData['biopsy']),
			        'biopsyLocation' =>isset($inputData['biopsyLocation']) ? $inputData['biopsyLocation'] : null,
			        'biopsyDate' =>isset($inputData['biopsyDate']) ? $inputData['biopsyDate'] : null,
                    'surgery' =>intval($inputData['surgery']),
                    'surgeryLocation' =>isset($inputData['surgeryLocation']) ? $inputData['surgeryLocation'] :null,
                    'surgeryDate' =>isset($inputData['surgeryDate']) ? $inputData['surgeryDate'] : null,
					'infection' =>intval($inputData['infection']),
                    'infectionLocation' =>isset($inputData['infectionLocation']) ? $inputData['infectionLocation'] : null,
			        'infectionDate' =>isset($inputData['infectionDate']) ? $inputData['infectionDate'] : null,
					'suvMaxTumoral' =>$inputData['suvMaxTumor'],
					'suvMaxTumoralLocation' =>$inputData['suvMaxTumorLocation'],
					'suvMaxHepatic' =>$inputData['suvMaxHepatic'],
					'suvMaxMediastinum'=>$inputData['suvMaxMediastinum'],
					'boneMarrowInvolvment' =>intval($inputData['boneMarrowInvolvement']),
                    'comment' =>isset($inputData['comment']) ? $inputData['comment'] : null,
					'id_review' => $id_review ));
			
		} else{
			//Draft doesn't exist we create a new entry in the specific table
			$insertion = $this->linkpdo->prepare('INSERT INTO '.$specificTable.' (id_review, reviewer, glycemia, recentBiopsy,
										biopsyLocation, biopsyDate, recentSurgery, surgeryLocation, surgeryDate, recentInfection,
										infectionLocation, infectionDate, suvMaxTumoral, suvMaxTumoralLocation,
										suvMaxHepatic, suvMaxMediastinum, boneMarrowInvolvment, comment)
                                        VALUES (:id_review, :reviewer, :glycemia, :biopsy, :biopsyLocation, :biopsyDate, :surgery, :surgeryLocation, :surgeryDate,
												:infection, :infectionLocation, :infectionDate, :suvMaxTumoral, :suvMaxTumoralLocation,
												:suvMaxHepatic, :suvMaxMediastinum, :boneMarrowInvolvment, :comment)');
			
			$insertion->execute(array(
            					'id_review' => $id_review,
                                'reviewer' => $inputData['reviewer'],
								'glycemia' => $inputData['glycemia'],
								'biopsy' =>intval($inputData['biopsy']),
                                'biopsyLocation' =>isset($inputData['biopsyLocation']) ? $inputData['biopsyLocation']: null,
                                'biopsyDate' =>isset($inputData['biopsyDate']) ? $inputData['biopsyDate'] : null,
								'surgery' =>intval($inputData['surgery']),
                                'surgeryLocation' =>isset($inputData['surgeryLocation']) ? $inputData['surgeryLocation'] : null,
                                'surgeryDate' =>isset($inputData['surgeryDate']) ? $inputData['surgeryDate'] :null,
								'infection' => intval($inputData['infection']),
                                'infectionLocation' =>isset($inputData['infectionLocation']) ? $inputData['infectionLocation'] :null,
                                'infectionDate' =>isset($inputData['infectionDate']) ? $inputData['infectionDate']:null,
								'suvMaxTumoral' =>$inputData['suvMaxTumor'],
								'suvMaxTumoralLocation' =>$inputData['suvMaxTumorLocation'],
								'suvMaxHepatic' =>$inputData['suvMaxHepatic'],
								'suvMaxMediastinum'=>$inputData['suvMaxMediastinum'],
								'boneMarrowInvolvment' =>intval($inputData['boneMarrowInvolvement']),
                                'comment' =>isset($inputData['comment']) ? $inputData['comment']: null
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
		
		$datas=$this->getAllValidatedFormsOfVisit();
		if(sizeof($datas)<2){
			$this->changeVisitValidationStatus(Form_Processor::NOT_DONE);
		}else{
			$this->changeVisitValidationStatus(Form_Processor::DONE);
		}
		
		
	}
	
	private function emptyValueToNull($array){
	    foreach ($array as $key => $value) {
	        if (empty($value)) {
	            $array[$key] = null;
	        }
	    }
	}
	
}