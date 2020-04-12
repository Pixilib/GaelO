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
 * Specific form for each visit
 * Name should be strictly studyName_VisitType.php
 * Need to implement autocompletion and deactivation script if review available in db
 * Completion verification script in a script called "validateForm" which return boolean for completion check
 */

?>
 <script>
	//Each specific form should implement this validateForm() function to make specific validation rules
	function validateForm() {
		//Fill here validation rule, need to return a boolean
	}
	
	//If available review in DB fill the form with available value (the draft/Validate status is automatically made by the platform)
	<?php if (!empty($results)) { 
	?>
    	$(document).ready(function(){
    			//Fill here completion function
				// Warning : If output of text field stored in database (text, tinytext, varchar), content should be XSS protected 
				//with htmlspecialchars(string);
    	});
	<?php 
	} 
	?>
    		
</script>

<form class="bloc_bordures" id=<?=$visitObject->study.'_'.$visitObject->visitType?> >

	<!-- Add specific form here -->
	
	<!----Warning : Need to be add ---->
	<input type="hidden" value="<?=$patient_num?>" name="patient_num" id="patient_num"> 
	<input type="hidden" value="<?=$type_visit?>" name="type_visit" id="type_visit"> 
	<input type="hidden" value="<?=$id_visit?>" name="id_visit" id="id_visit">
</form>