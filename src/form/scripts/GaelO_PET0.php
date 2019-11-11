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
		if($("select[name='suvMaxTumorLocation']").val() ==0 ){
			alertifyError("Missing SUVmax Location")
    		return false
		}
		if($("#recentBiopsyYes").is(':checked') 
        		&& ($("select[name='biopsyLocation']").val()==0 || $("input[name='biopsyDate']").val()=='') ){
			alertifyError("Missing biopsy details")
    		return false
		}
		if($("#recentSurgeryYes").is(':checked') 
        		&& ($("select[name='surgeryLocation']").val()==0 || $("input[name='surgeryDate']").val()=='') ){
			alertifyError("Missing surgery details")
    		return false
		}
		if($("#recentInfectionYes").is(':checked') 
        		&& ($("select[name='infectionLocation']").val()==0 || $("input[name='infectionDate']").val()=='') ){
			alertifyError("Missing infection details")
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
		    		
		//Add optios in select
		$.each(items, function (nb) {
		    $('.location').append($('<option>', { 
		        value: nb,
		        text : items[nb] 
		    }));
		});

	});
	
	function triggerChangeListeners(){
		$("#recentBiopsyYes").change();
		$("#recentSurgeryYes").change();
		$("#recentInfectionYes").change();
	}
	//If available review in DB fill the form with available value (the draft/Validate status is automatically made by the platform)
	<?php if(!empty($results)){ 
	?>
    	$(document).ready(function(){
        	<?php if($local){?>
        	    $("input[name='reviewer']").val('<?= $results['reviewer'] ?>');
        	<?php }?>
    		$("input[name='glycemia']").val(<?= $results['glycemia'] ?>);
    		$("#recentBiopsyYes").prop( "checked", <?= boolval($results['recentBiopsy'])?> );
    		$("select[name='biopsyLocation'] option[value=<?php if(!empty($results['biopsyLocation'])) echo $results['biopsyLocation'] ; else echo(0)?>]").prop('selected', 'selected');
    		$("[name='biopsyDate']").val('<?= date('Y-m-d',strtotime($results['biopsyDate'])) ?>');
    		$("#recentSurgeryYes").prop( "checked", <?= boolval($results['recentSurgery'])?> );
    		$("select[name='surgeryLocation'] option[value=<?php if(!empty($results['surgeryLocation'])) echo $results['surgeryLocation'] ; else echo(0)?>]").prop('selected', 'selected');
    		$("[name='surgeryDate']").val('<?=  date('Y-m-d',strtotime($results['surgeryDate'])) ?>');
    		$("#recentInfectionYes").prop( "checked", <?= boolval($results['recentInfection'])?> );
    		$("select[name='infectionLocation'] option[value=<?php if(!empty($results['infectionLocation'])) echo $results['infectionLocation'] ; else echo(0)?>]").prop('selected', 'selected');
    		$("[name='infectionDate']").val('<?=date('Y-m-d',strtotime($results['infectionDate']))?>');
    		$("input[name='suvMaxTumor']").val(<?= $results['suvMaxTumoral'] ?>);
    		$('select[name="suvMaxTumorLocation"] option[value=<?php if(!empty($results['suvMaxTumoralLocation'])) echo $results['suvMaxTumoralLocation'] ; else echo(0)?>]').prop('selected', true)
    		$("input[name='suvMaxHepatic']").val(<?= $results['suvMaxHepatic'] ?>);
    		$("input[name='suvMaxMediastinum']").val(<?= $results['suvMaxMediastinum'] ?>);

    		$("#boneMarrowInvolvementYes").prop( "checked", <?= boolval($results['boneMarrowInvolvment'])?> );
    		$("input[name='comment']").val('<?= $results['comment'] ?>');

    		triggerChangeListeners();
    		
    	});
	<?php 
    } 
    //If current user didn't have any form submitted and non local form
    //Fill form from local data to give reviewer's data
    if (!$local){
        $localResults=$visitObject->getReviewsObject(true)->getSpecificData();
        //SI ICI SI PAS DE REVIEW DECLANCHE UNE ERREUR A VOIR
        ?>
        $(document).ready(function(){
			$("input[name='glycemia']").val(<?= $localResults['glycemia'] ?>);

    		$( "#recentBiopsyYes" ).prop( "checked", <?= boolval($localResults['recentBiopsy'])?> );
    		$("select[name='biopsyLocation'] option[value=<?php if(!empty($localResults['biopsyLocation'])) echo $localResults['biopsyLocation'] ; else echo(0)?>]").prop('selected', 'selected');
    		$("[name='biopsyDate']").val('<?= date('Y-m-d',strtotime($localResults['biopsyDate'])) ?>');
    		$( "#recentSurgeryYes" ).prop( "checked", <?= boolval($localResults['recentSurgery'])?> );
    		$("select[name='surgeryLocation'] option[value=<?php if(!empty($localResults['surgeryLocation'])) echo $localResults['surgeryLocation'] ; else echo(0)?>]").prop('selected', 'selected');
    		$("[name='surgeryDate']").val('<?=  date('Y-m-d',strtotime($localResults['surgeryDate'])) ?>');
    		$( "#recentInfectionYes" ).prop( "checked", <?= boolval($localResults['recentInfection'])?> );
    		$("select[name='infectionLocation'] option[value=<?php if(!empty($localResults['infectionLocation'])) echo $localResults['infectionLocation'] ; else echo(0)?>]").prop('selected', 'selected');
    		$("[name='infectionDate']").val('<?=  date('Y-m-d',strtotime($localResults['infectionDate'])) ?>');
    		$("input[name='LocalReviewComment']").val('<?= $localResults['comment'] ?>');

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
    }?>
    		
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
    		<label for="recentBiopsyYes">
    		<input id="recentBiopsyYes" name="biopsy" type="radio" value=1>
    		Yes</label>
    		<label for="recentBiopsyNo">
    		<input id="recentBiopsyNo" name="biopsy" type="radio" value=0 checked>
			No</label>
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
    		<label for="recentSurgeryYes">
    		<input id="recentSurgeryYes" name="surgery" type="radio" value=1>
    		Yes</label>
    		<label for="recentSurgeryNo">
    		<input id="recentSurgeryNo" name="surgery" type="radio" value=0 checked>
			No</label>
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
    		<label for="recentInfectionYes">
    		<input id="recentInfectionYes" name="infection" type="radio" value=1>
    		Yes</label>
    		<label for="recentInfectionNo">
    		<input id="recentInfectionNo" name="infection" type="radio" value=0 checked>
    		No</label>
		</div>
		<div class="form-group" id="infectionDetails" style="display:none">
			<label class="font-weight-bold">Infection Location: </label>
			<SELECT class="custom-select location" name="infectionLocation" id="infectionLocation"></SELECT>
			<label class="font-weight-bold">Infection Date: </label>
			<input class="form-control" name="infectionDate" type="date">
		</div>
	</div>
	<div class="form-group" id="localComment" style="display: none;">
		<label class="font-weight-bold">Local Review Comment: </label> <br> 
		<input class="form-control" name="LocalReviewComment" type="text">
	</div>
	<div class="form-group" >
		<label class="font-weight-bold">SUVMax Tumoral: </label> <br> 
		<input class="form-control" name="suvMaxTumor" type="number" step="0.1" value=0>
	</div>
	<div class="form-group" >
		<label class="font-weight-bold">SUVMax Location: </label> <br> 
		<SELECT class="custom-select location" name="suvMaxTumorLocation"></SELECT>
	</div>
	<div class="form-group" >
		<label class="font-weight-bold">SUVMax Hepatic: </label> <br> 
		<input class="form-control" name="suvMaxHepatic" type="number" step="0.1" value=0>
	</div>
	<div class="form-group" >
		<label class="font-weight-bold">SUVMax Mediastinum: </label> <br> 
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
			<input id="boneMarrowInvolvementNe" name="boneMarrowInvolvement" type="radio" value=0 checked>
			No
			</label>
		</div>
	</div>
	<div class="form-group">
		<label class="font-weight-bold">Comment: </label>
		<input class="form-control" name="comment" type="text">
		
	</div>
	
	<!----Warning : Need to be add ---->
	<input type="hidden" value="<?=$patient_num?>" name="patient_num" id="patient_num"> 
	<input type="hidden" value="<?=$type_visit?>" name="type_visit" id="type_visit"> 
	<input type="hidden" value="<?=$id_visit?>" name="id_visit" id="id_visit">
</form>
