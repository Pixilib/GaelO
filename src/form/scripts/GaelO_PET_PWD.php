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
		console.log("routine Validate");
		if($("input[name='glycemia']").val() == 0 ){
			alertifyError("Missing Glycemia")
    		return false
		}
		if($("input[name='suvMaxTumor']").val() == 0 ){
			alertifyError("Missing SUVmax Tumor")
    		return false
		}
		if($("input[name='suvMaxHepatic']").val() == 0 ){
			alertifyError("Missing SUVmax Hepatic")
    		return false
		}
		if($("input[name='suvMaxMediastinum']").val() == 0 ){
			alertifyError("Missing SUVmax Mediastinum")
    		return false
		}
		if($("select[name='deauville']").val() ==0 ){
			alertifyError("Missing Deauville Score")
    		return false
		}
		if($("select[name='nodalExtraNodal']").val() ==0 ){
			alertifyError("Nodal/ExtraNodal status")
    		return false
		}
		if($("select[name='luganoView']").val() ==0 ){
			alertifyError("Missing Lugano")
    		return false
		}
		if($("select[name='suvMaxTumorLocation']").val() ==0 ){
			alertifyError("Missing SUVmax Location")
    		return false
		}
		if($("#recentBiopsy").is(':checked') 
        		&& ($("select[name='biopsyLocation']").val()==0 || $("input[name='biopsyDate']").val()=='') ){
			alertifyError("Missing biopsy details")
    		return false
		}
		if($("#recentSurgery").is(':checked') 
        		&& ($("select[name='surgeryLocation']").val()==0 || $("input[name='surgeryDate']").val()=='') ){
			alertifyError("Missing surgery details")
    		return false
		}
		if($("#recentInfection").is(':checked') 
        		&& ($("select[name='infectionLocation']").val()==0 || $("input[name='infectionDate']").val()=='') ){
			alertifyError("Missing infection details")
    		return false
		}
		if($("select[name='deauville']").val() <=3 && $("select[name='nodalExtraNodal']").val() >1){
			alertifyError("Deauville and Nodal status contradiction")
    		return false
		}
		if($("select[name='deauville']").val() >=4 && $("select[name='nodalExtraNodal']").val() <=1){
			alertifyError("Deauville and Nodal status contradiction")
    		return false
		}

		if($("select[name='deauville']").val() ==0 || $("select[name='nodalExtraNodal']").val()==0 || $("select[name='luganoView']").val()==0){
			alertifyError("Form Imcomplete")
    		return false
		}
    		

		return true
	}

	$(document).ready(function(){

		$("[name='biopsy']").change(
			    function(){
			        if ($("#recentBiopsyYes").is(':checked')) {
			        	$('#biopsyDetails').show();
			        }else{
			        	$('#biopsyDetails').hide();
			        }
			    });

		$("[name='surgery']").change(
			    function(){
			        if ($('#recentSurgeryYes').is(':checked')) {
			        	$('#surgeryDetails').show();
			        }else{
			        	$('#surgeryDetails').hide();
			        }
			    });

		$("[name='infection']").change(
			    function(){
			        if ($('#recentInfectionYes').is(':checked')) {
			        	$('#infectionDetails').show();
			        }else{
			        	$('#infectionDetails').hide();
			        }
				});

		var items = new Array(
		'',
		'Cervical right',
		'Cervical left',
		'Supraclavicular right',
		'Supraclavicular left',
		'Axillary right',
		'Axillary left',
		'Inguinal right',
		'Inguinal left',
		'Mediastinal',
		'Pulmonary hilar',
		'Retroperitoneal / Para-aortic',
		'Mesenteric',
		'Iliac right',
		'Splenic hilar',
		'Epitrochlear right',
		'Epitrochlear left',
		'Popliteal right',
		'Popliteal left',
		'Other nodal involvement',
		'Liver',
		'Ascites',
		'Pleura',
		'Lung',
		'Spleen',
		'Pericardium',
		'Breast',
		'Gonadal',
		'Kidney',
		'Adrenal',
		'Thyroid',
		'Skin',
		'Bone',
		'Blood',
		'Tonsil',
		'Cavum',
		'Parotid',
		'Orbit',
		'Sinus',
		'Other ORL area',
		'Oesophagus',
		'Stomach',
		'Duodenum',
		'Colon',
		'Caecum',
		'Ileon',
		'Rectum',
		'Other digestive area',
		'Bone marrow',
		'Urinary tract',
		'Soft tissues',
		'Heart',
		'Other extra-nodal involvement',
		'Splenic node',
		'Hepatic node',
		'Pancreas',
		'Meningeal',
		'Other Central Nervous System',
		'Small intestine',
		'Ileo-caecal junction');
		    		
		//Add options in select
		$.each(items, function (nb) {
		    $('.location').append($('<option>', { 
		        value: nb,
		        text : items[nb] 
		    }));
		});
		
		$("select[name='newLesions']").on('change', function() {
    		if(this.value ==1){
    			$("select[name='deauville'] option:eq(5)").prop('selected', true);
    			//If nodal value deauville 1-3 change it to N/A
    			if($("select[name='nodalExtraNodal']").val()==1){
    				$("select[name='nodalExtraNodal']").val(0);
    			}
    		}
    		determineLugano();
		});

		$("select[name='deauville']").on('change', function() {
			if(this.value <=3 && this.value >0){
				if($("select[name='nodalExtraNodal']").val()>1){
					$("select[name='nodalExtraNodal']").val(0);
				}
				$("select[name='newLesions']").val(0);
			}else if(this.value >3){
				if($("select[name='nodalExtraNodal']").val()==1){
					$("select[name='nodalExtraNodal']").val(0);
				}
			}


			determineLugano();
		});

		$("select[name='nodalExtraNodal']").on('change', function() {
    		console.log(this.value);
    		if(this.value ==1){
    			if($("select[name='deauville']").val()>3){
    				$("select[name='deauville']").val(0);
				}
				
    		}else if (this.value !=0){
    			if($("select[name='deauville']").val()<=3 ){
    				$("select[name='deauville']").val(0);
				}
    		}
    		
    		determineLugano();
		});
		

	});
	function triggerChangeListeners(){
		$("[name='biopsy']").change();
		$("[name='surgery']").change();
		$("[name='infection']").change();
	}

	function determineLugano(){
		if($("select[name='deauville']").val()>0 && $("select[name='deauville']").val()<=3 && $("select[name='nodalExtraNodal']").val()==1 && $("select[name='newLesions']").val()==0){
			$("select[name='luganoView'] option:eq(1)").prop('selected', true);
		}else if($("select[name='deauville']").val()>3 && $("select[name='nodalExtraNodal']").val()==2 && $("select[name='newLesions']").val()==0){
			$("select[name='luganoView'] option:eq(2)").prop('selected', true);
		}else if($("select[name='deauville']").val()>3 && $("select[name='nodalExtraNodal']").val()==3 && $("select[name='newLesions']").val()==0){
			$("select[name='luganoView'] option:eq(3)").prop('selected', true);
		}else if( ($("select[name='deauville']").val()>3 && $("select[name='nodalExtraNodal']").val()==4) || $("select[name='newLesions']").val()==1){
			$("select[name='luganoView'] option:eq(4)").prop('selected', true);
		}else {
			$("select[name='luganoView'] option:eq(0)").prop('selected', true);
		}
		//Fill empty input with curent value
		$("input[name='lugano']").val( $("select[name='luganoView']").val() );
    	
	}
	
	//If available review in DB fill the form with available value (the draft/Validate status is automatically made by the platform)
	<?php if(!empty($results)){ 
    	?>
        	$(document).ready(function(){
            	<?php if($local){?>
        	    $("input[name='reviewer']").val('<?= htmlspecialchars($results['reviewer']) ?>');
        	   <?php }?>
        		$("input[name='glycemia']").val(<?= $results['glycemia'] ?>);
        		$("#recentBiopsyYes").prop( "checked", <?= boolval($results['recentBiopsy'])?> );
        		$("select[name='biopsyLocation'] option[value=<?php if(!empty($results['biopsyLocation'])) echo $results['biopsyLocation'] ; else echo(0)?>]").prop('selected', 'selected');
        		$("[name='biopsyDate']").val('<?= date('Y-m-d',strtotime($results['biopsyDate'])) ?>');
        		$( "#recentSurgeryYes" ).prop( "checked", <?= boolval($results['recentSurgery'])?> );
        		$("select[name='surgeryLocation'] option[value=<?php if(!empty($results['surgeryLocation'])) echo $results['surgeryLocation'] ; else echo(0)?>]").prop('selected', 'selected');
        		$("[name='surgeryDate']").val('<?=  date('Y-m-d',strtotime($results['surgeryDate'])) ?>');
        		$( "#recentInfectionYes" ).prop( "checked", <?= boolval($results['recentInfection'])?> );
        		$("select[name='infectionLocation'] option[value=<?php if(!empty($results['infectionLocation'])) echo $results['infectionLocation'] ; else echo(0)?>]").prop('selected', 'selected');
        		$("[name='infectionDate']").val('<?=date('Y-m-d',strtotime($results['infectionDate']))?>');
        		$("input[name='suvMaxTumor']").val(<?= $results['suvMaxTumoral'] ?>);
        		$('select[name="suvMaxTumorLocation"] option[value=<?php if(!empty($results['suvMaxTumoralLocation'])) echo $results['suvMaxTumoralLocation'] ; else echo(0)?>]').prop('selected', true)
        		$("input[name='suvMaxHepatic']").val(<?= $results['suvMaxHepatic'] ?>);
        		$("input[name='suvMaxMediastinum']").val(<?= $results['suvMaxMediastinum'] ?>);
        		$("#boneMarrowInvolvementYes").prop( "checked", <?= boolval($results['boneMarrowInvolvment'])?> );
        		

        		$("select[name='deauville']").val(<?= $results['deauville'] ?>);
        		$("select[name='nodalExtraNodal']").val(<?= $results['nodalExtraNodal'] ?>);
        		$("select[name='newLesions']").val(<?= $results['newLesion'] ?>);
        		$("select[name='luganoView']").val('<?= $results['lugano'] ?>').prop('selected', true);
        		$("input[name='lugano']").val('<?= $results['lugano'] ?>');
        		

        		$("input[name='comment']").val(<?= htmlspecialchars($results['comment']) ?>);
        		triggerChangeListeners();
        		
        	});
    	<?php 
        } 
        //If current user didn't have any form submitted and non local form
        //Fill form from local data to give reviewer's data
        if (!$local){
            $localResults=$visitObject->getReviewsObject(true)->getSpecificData();
            ?>
            $(document).ready(function(){
				$("input[name='glycemia']").val(<?= $localResults['glycemia'] ?>);
    
        		$( "#recentBiopsyYes" ).prop( "checked", <?= boolval($localResults['recentBiopsy'])?> );
        		$("select[name='biopsyLocation']").val(<?= $localResults['biopsyLocation'] ?>);
        		$("[name='biopsyDate']").val('<?= date('Y-m-d',strtotime($localResults['biopsyDate']))?>');
        		$( "#recentSurgeryYes" ).prop( "checked", <?= boolval($localResults['recentSurgery'])?> );
        		$("select[name='surgeryLocation']").val(<?= $localResults['surgeryLocation'] ?>);
        		$("[name='surgeryDate']").val('<?= date('Y-m-d',strtotime($localResults['surgeryDate']))?>');
        		$( "#recentInfectionYes" ).prop( "checked", <?= boolval($localResults['recentInfection'])?> );
        		$("select[name='infectionLocation']").val(<?= $localResults['infectionLocation'] ?>);
        		$("[name='infectionDate']").val('<?= date('Y-m-d',strtotime($localResults['infectionDate']))?>');
        		$("input[name='LocalReviewComment']").val(<?= htmlspecialchars($localResults['comment']) ?>);

        		$("input[name='glycemia']").attr('disabled','disabled');
        		$("[name='biopsy']").attr('disabled','disabled');
        		$("select[name='biopsyLocation']").attr('disabled','disabled');
        		$("[name='biopsyDate']").attr('disabled','disabled');
        		$("[name='surgery']").attr('disabled','disabled');
        		$("select[name='surgeryLocation']").attr('disabled','disabled');
        		$("[name='surgeryDate']").attr('disabled','disabled');
        		$("[name='infection']").attr('disabled','disabled');
        		$("select[name='infectionLocation']").attr('disabled','disabled');
        		$("[name='infectionDate']").attr('disabled','disabled');
        		$("input[name='LocalReviewComment']").attr('disabled','disabled');
        		$("#localComment").show();

        		triggerChangeListeners();
            });
    	<?php
        }
        
        if($visitObject->reviewStatus == Form_Processor::WAIT_ADJUDICATION){
        	?>
        	$(document).ready(function(){
        		$("input[name='lugano']").removeAttr('disabled');
            })
            	
			$("select[name='luganoView']").on('change', function() {
				$("input[name='lugano']").val( $("select[name='luganoView']").val() );
			});
		<?php
		}
		?>
</script>

<form class="bloc_bordures container-fluid" id=<?=$visitObject->study.'_'.$visitObject->visitType?> >
	
	<?php if($local){ ?>
	<div class="form-group">
		<label class="font-weight-bold">Reviewer: </label> <br> 
		<input class="form-control" name="reviewer" type="text">
	</div>
	<?php } ?>
	
	<div class="form-group">
		<label class="font-weight-bold">Glycemia: </label> <br> 
		<input class="form-control" name="glycemia" type="number" placeholder="mmol/l" step="0.1" value=0>
	</div>
	<div class="form-group">
		<label class="font-weight-bold">Recent Biopsy: </label>
		<div>
			<label for="recentBiopsyYes" class="radio-inline">
    		<input name="biopsy" type="radio" id="recentBiopsyYes" value=1>
    		Yes
    		</label>
    		<label for="recentBiopsyNo" class="radio-inline">
    		<input name="biopsy" type="radio" id="recentBiopsyNo" value=0 checked>
    		No
    		</label>
		</div> 
		<div class="form-group" id="biopsyDetails" style="display:none">
			<label class="font-weight-bold">Biopsy Location: </label>
			<SELECT class="custom-select location" name="biopsyLocation" id="biopsyLocation"></SELECT>
			<label class="font-weight-bold">Biopsy Date: </label>
			<input class="form-control" name="biopsyDate" type="date">
		</div>
	</div>
	<div class="form-group">
		<label class="font-weight-bold">Recent Surgery: </label>
		<div>
			<label for="recentSurgeryYes" class="radio-inline">
    		<input name="surgery" id="recentSurgeryYes" type="radio" value=1>
    		Yes
    		</label>
    		<label for="recentSurgeryNo" class="radio-inline">
    		<input name="surgery" id="recentSurgeryNo" type="radio" value=0 checked>
    		No
    		</label>
		</div>
		<div class="form-group" id="surgeryDetails" style="display:none">
			<label class="font-weight-bold">Surgery Location: </label>
			<SELECT class="custom-select location" name="surgeryLocation" id="surgeryLocation"></SELECT>
			<label class="font-weight-bold">Surgery Date: </label>
			<input class="form-control" name="surgeryDate" type="date">
		</div>
	</div>
	<div class="form-group">
		<label class="font-weight-bold">Recent Infection: </label>
		<div>
			<label for="recentInfectionYes" class="radio-inline">
    		<input name="infection" id="recentInfectionYes" type="radio" value=1>
    		Yes
    		</label>
    		<label for="recentInfectionNo" class="radio-inline">
    		<input name="infection" id="recentInfectionNo" type="radio" value=0 checked>
    		No
    		</label>
	</div>
		<div id="infectionDetails" class="form-group" style="display:none">
			<label class="font-weight-bold">Infection Location: </label>
			<SELECT class="custom-select location" name="infectionLocation" id="infectionLocation"></SELECT>
			<label class="font-weight-bold">Infection Date: </label>
			<input class="form-control" name="infectionDate" type="date">
		</div>
	</div>
	<div class="form-group" id="localComment" style="display: none;">
		<label class="font-weight-bold">Local Review Comment: </label> 
		<input class="form-control" name="LocalReviewComment" type="text">
	</div>
	
	<?php if( !empty($results) || $visitObject->reviewStatus!=Form_Processor::WAIT_ADJUDICATION){
	?>
		<div class="form-group">
    		<label class="font-weight-bold">SUVMax Tumoral: </label>
    		<input class="form-control" name="suvMaxTumor" type="number" step="0.1" value=0>
    	</div>
		<div class="form-group">
    		<label class="font-weight-bold">SUVMax Location: </label> 
    		<SELECT class="custom-select location" name="suvMaxTumorLocation"></SELECT>
    	</div>
    	<div class="form-group">
    		<label class="font-weight-bold">SUVMax Hepatic: </label> 
    		<input class="form-control" name="suvMaxHepatic" type="number" step="0.1" value=0>
    	</div>
		<div class="form-group">
    		<label class="font-weight-bold">SUVMax Mediastinum: </label> 
    		<input class="form-control" name="suvMaxMediastinum" type="number" step="0.1" value=0>
    	</div>
		<div class="form-group">
			<label class="font-weight-bold">Bone Marrow Involvment: </label> 
			<div>
				<label for="boneMarrowInvolvementYes" class="radio-inline">
				<input id="boneMarrowInvolvementYes" name="boneMarrowInvolvement" type="radio" value=1>
				Yes
				</label>
				<label for="boneMarrowInvolvementNo" class="radio-inline">
				<input id="boneMarrowInvolvementNo" name="boneMarrowInvolvement" type="radio" value=0 checked>
				No
				</label>
			</div>
		</div>
		<div class="form-group">
    		<label class="font-weight-bold">Deauville Criteria: </label>
    		<SELECT class="custom-select" name="deauville">
				  <option value=0>N/A</option>
                  <option value=1>1</option>
                  <option value=2>2</option>
                  <option value=3>3</option>
                  <option value=4>4</option>
                  <option value=5>5</option>
    		</SELECT>
    	</div>
		<div class="form-group">
    		<label class="font-weight-bold">Nodal Masses, extranodal lesions: </label> 
    		<SELECT class="custom-select" name="nodalExtraNodal">
				  <option value=0>N/A</option>
				  <option value=1>Deauville 1-2-3 with or without residual mass</option>
                  <option value=2>Deauville 4-5 with reduced uptake compared with baseline and residual mass(es) of any size</option>
                  <option value=3>Deauville 4-5 with no significant change in FDG uptake from baseline</option>
                  <option value=4>Deauville 4-5 with an increase in intensity of uptake from baseline</option>
    		</SELECT>
    	</div>
		<div class="form-group">
    		<label class="font-weight-bold">New Lesions: </label> 
    		<SELECT class="custom-select" name="newLesions">
				<option value=0>None</option>
				<option value=1>New FDG Avid loci consistent with lymphoma</option>
             </SELECT>
    	</div>
		<div class="form-group">
    		<label class="font-weight-bold">Lugano Classification: </label>
    		<SELECT class="custom-select" name="luganoView" disabled>
        		<option value='N/A'>N/A</option>
    			<option value='CMR'>Complete Metabolic Response (CMR)</option>
    			<option value='PMR'>Partial Metabolic Response (PMR)</option>
    			<option value='NMR'>No Metabolic Response (NMR)</option>
    			<option value='PD'>Progressive Metabolic Disease (PMD)</option>
    		</SELECT>
    		
    		<input type="hidden" name="lugano">
    	</div>
		<div class="form-group">
    		<label class="font-weight-bold">Comment: </label> 
    		<input class="form-control" name="comment" type="text">
    	</div>
	<?php 
	//Adjudication form if reviewer not perfomed review and wait adjudication status
	}else if( empty($results) && $visitObject->reviewStatus==Form_Processor::WAIT_ADJUDICATION){
	    $localData=$visitObject->getReviewsObject(true)->getSpecificData();
	    $centralReviews=$visitObject->getReviewsObject(false);
	    ?>
	    <div class="block mt-5">
		    <span class="font-weight-bold text-center"> You are adjudicator of this exam, so your conclusion will be the reference conclusion
		    concerning the Lugano Classification. You can take note of the previous reviewer's conclusion
		    concerning the Lugano Classification. </span>
		    <table class="text-center">
		    <tr>
		    	<td></td>
    		    <td> Local Reader </td>
    		    <?php for($i=0; $i<sizeof($centralReviews) ; $i++){
    		        echo ('<td> Reader'.($i+1).'</td>');
    		    }?>
		    </tr>
			<tr>
				<td>Nodal Masses/Extranodal</td>
    		    <td> <?php 
        		    if($localData['nodalExtraNodal']==1){
        		        $value="Deauville 1-2-3 with or without residual mass";
        		    }else if($localData['nodalExtraNodal']==2){
        		        $value="Deauville 4-5 with reduced uptake compared with baseline and residual mass(es) of any size";
        		    }else if($localData['nodalExtraNodal']==3){
        		        $value="Deauville 4-5 with no significant change in FDG uptake from baseline";
        		    }else if($localData['nodalExtraNodal']==4){
        		        $value="Deauville 4-5 with an increase in intensity of uptake from baseline";
        		    } 
        		    
        		    echo($value)?> 
    		    </td>

    		    <?php foreach ($centralReviews as $centralReview){
    		        $centralReviewsDatas=$centralReview->getSpecificData();
    		        if($centralReviewsDatas['nodalExtraNodal']==1){
    		            $value="Deauville 1-2-3 with or without residual mass";
    		        }else if($centralReviewsDatas['nodalExtraNodal']==2){
    		            $value="Deauville 4-5 with reduced uptake compared with baseline and residual mass(es) of any size";
    		        }else if($centralReviewsDatas['nodalExtraNodal']==3){
    		            $value="Deauville 4-5 with no significant change in FDG uptake from baseline";
    		        }else if($centralReviewsDatas['nodalExtraNodal']==4){
    		            $value="Deauville 4-5 with an increase in intensity of uptake from baseline";
    		        } 
    		        
    		        echo ('<td>'.$value.'</td>');
    		    }?>
		    </tr>
			<tr>
				<td>New Lesions</td>
    		    <td> <?=$localData['newLesion']?> </td>
    		    <?php foreach ($centralReviews as $centralReview){
    		        $centralReviewsDatas=$centralReview->getSpecificData();
    		        echo ('<td>'.$centralReviewsDatas['newLesion'].'</td>');
    		    }?>
		    </tr>
			<tr>
			<td>Comment</td>
    		    <td> <?=$localData['comment']?> </td>
    		    <?php foreach ($centralReviews as $centralReview){
    		        $centralReviewsDatas=$centralReview->getSpecificData();
    		        echo ('<td>'.htmlspecialchars($centralReviewsDatas['comment']).'</td>');
    		    }?>
		    </tr>
			<tr>
			<td>Lugano</td>
    		    <td> <?=$localData['lugano']?> </td>
    		    <?php foreach ($centralReviews as $centralReview){
    		        $centralReviewsDatas=$centralReview->getSpecificData();
    		        echo ('<td>'.$centralReviewsDatas['lugano'].'</td>');
    		    }?>
		    </tr>
			<tr>
			<td>Adjudication</td>
			<td>
				<input type="radio" name="lugano" value="<?=$localData['lugano']?>">
			</td>
    		    <?php foreach ($centralReviews as $centralReview){
    		        $centralReviewsDatas=$centralReview->getSpecificData();
    		        echo('<td><input type="radio" name="lugano" value="'.$centralReviewsDatas['lugano'].'"</td>');
    		    }?>
		    </tr>
		    </table>
			
		    <div>
    			<label class="font-weight-bold">Comment: </label>
    			<input class="form-control" name="comment" type="text">
			</div>
	    </div>
	    
    <?php 
	}?>
	<!----Warning : Need to be add ---->
	<input type="hidden" value="<?=$patient_num?>" name="patient_num" id="patient_num"> 
	<input type="hidden" value="<?=$type_visit?>" name="type_visit" id="type_visit"> 
	<input type="hidden" value="<?=$id_visit?>" name="id_visit" id="id_visit">
</form>
