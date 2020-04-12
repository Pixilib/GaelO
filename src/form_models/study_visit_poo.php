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
class study_visit extends Form_Processor {
	
	public function __construct($idVisit, $local, $username, $linkpdo) {
		parent::__construct($idVisit, $local, $username, $linkpdo);
	}
	
	/**
	 * Fill the specific table
	 * @param $data
	 * @param $id_Review
	 * @param $specificTable
	 */
	protected function saveSpecificForm($inputData, $id_review, $update) {
		// Draft exist, we update the draft
		if ($update) {
			$req_update=$this->linkpdo->prepare('UPDATE '.$this->specificTable.'
                              SET item = :value,
                                  item2 = :value2
                                WHERE id_review = :id_review');
			
			$req_update->execute(array(
					'value' => '',
					'value2' =>'',
					'id_review' => $id_review ));
			
		} else {
			//Draft doesn't exist we create a new entry in the specific table
			$insertion=$this->linkpdo->prepare('INSERT INTO '.$this->specificTable.' (id_review, item, item2)
                                        VALUES (:id_review, :value, :value2)');
			
			$insertion->execute(array(
								'id_review' => $id_review,
								'value' => '',
								'value2' =>''
								));
		}
		
	}
	
	/**
	 * Define Review validation rule
	 * Should also define status "Not Done" as default answer as this method will be called at review delete as well
	 * {@inheritDoc}
	 * @see Form_Processor::setVisitValidation()
	 */
	public function setVisitValidation() {
		/*
	     * Possible to get all saved form with $datas=$this->getAllValidatedFormsOfVisit();
	     * then need to set each rule for Not Done, Ongoing, Wait Adjudication, Done with $this->changeVisitValidationStatus(Visit::REVIEW_DONE);
	    */
		
		
	}
	
	
	
	
}