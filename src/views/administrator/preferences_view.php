<?php
/**
 Copyright (C) 2018-2020 KANOUN Salim
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
 ?>
 
 <script type="text/javascript">
      $(document).ready(function() {
          
        $("#preferenceSubmit").on('click', function(){
			//Send form in Ajax
			$.ajax({
	           type: "POST",
	           dataType: 'json',
	           url: '/preferences',
	           data: $("#prefrenceForm").serialize()+"&preferenceSubmit=1", // serializes the form's elements.
	           success: function(data) {
                    if (data != "Success") {
                      alertifyError(data);
                      return;
                    }else{
                        //Close the create user dialog
                        $("#adminDialog").dialog('close');
                        alertifySuccess('Preferences Saved');
                    }
                    
				}
			});
		});

      });

       
</script>
<div class="jumbotron">
	<form id="prefrenceForm" class="align-self-center" autocomplete="off" >
		<div>
			<label for="plateformName">Plateform Name</label><input name="plateformName" type="text" maxlength="32" value="<?=GAELO_PLATEFORM_NAME?>"><br>
		    <label for="adminEmail">Administrator Email</label><input name="adminEmail" type="text" maxlength="255" value="<?=GAELO_ADMIN_EMAIL?>"><br>
		    <label for="replyTo">Reply To</label><input name="replyTo" type="text" maxlength="255" value="<?=GAELO_REPLY_TO?>"><br>
		    
		    <label for="corporation">Corporation</label><input name="coporation" type="text" maxlength="32" value="<?=GAELO_CORPORATION?>"><br>
		    <label for="webAddress">Web Address (without http://)</label><input name="webAddress" type="text" maxlength="255" placeholder="ex: gaelo.fr" value="<?=GAELO_WEB_ADDRESS?>"><br>
		    <label for="patientCodeLenght">Patient Code Lenght (max=18)</label><input name="patientCodeLenght" type="number" max="18" value="<?=GAELO_PATIENT_CODE_LENGHT?>"><br>
		    <label for="parseDateImport">Parse Date Import : </label>
		    	<label>MM/DD/YYY</label>
		    	<input name="parseDateImport" type="radio" maxlength="10" value="m.d.Y" <?php if (GAELO_DATE_FORMAT == "m.d.Y") echo("checked")?>>
		    	<label>DD/MM/YYY</label>
		    	<input name="parseDateImport" type="radio" maxlength="10" value="d.m.Y" <?php if (GAELO_DATE_FORMAT == "d.m.Y") echo("checked")?>>
		    	<br>
		    	
			<label for="parseCountryName">Parse Country Name : </label>
	    	<label>US</label>
	    	<input name="parseCountryName" type="radio" value="US" <?php if (GAELO_COUNTRY_LANGUAGE == "US") echo("checked")?>>
	    	<label>FR</label>
	    	<input name="parseCountryName" type="radio" value="FR" <?php if (GAELO_COUNTRY_LANGUAGE == "FR") echo("checked")?>>
	    	<br>
		   
		   	<label for="orthancExposedInternalAddress">Orthanc Exposed Internal Address</label><input name="orthancExposedInternalAddress" type="text" maxlength="255" value="<?=GAELO_ORTHANC_EXPOSED_INTERNAL_ADDRESS?>"><br>
		    <label for="orthancExposedInternalPort">Orthanc Exposed Internal Port</label><input name="orthancExposedInternalPort" type="number" value="<?=GAELO_ORTHANC_EXPOSED_INTERNAL_PORT?>"><br>
		    
		    <label for="orthancExposedExternalAddress">Orthanc Exposed External Address</label><input name="orthancExposedExternalAddress" type="text" maxlength="255" value="<?=GAELO_ORTHANC_EXPOSED_EXTERNAL_ADDRESS?>"><br>
		    <label for="orthancExposedExternalPort">Orthanc Exposed External Port</label><input name="orthancExposedExternalPort" type="number" value="<?=GAELO_ORTHANC_EXPOSED_EXTERNAL_PORT?>"><br>
		    
		    <label for="orthancExposedInternalLogin">Orthanc Exposed Internal Login</label><input name="orthancExposedInternalLogin" type="text" maxlength="255" value="<?=GAELO_ORTHANC_EXPOSED_INTERNAL_LOGIN?>"><br>
		    <label for="orthancExposedInternalPassword">Orthanc Exposed Internal Password</label><input name="orthancExposedInternalPassword" type="password" maxlength="255" value="<?=GAELO_ORTHANC_EXPOSED_INTERNAL_PASSWORD?>"><br>
		    
		    <label for="orthancExposedExternalLogin">Orthanc Exposed External Login</label><input name="orthancExposedExternalLogin" type="text" maxlength="255" value="<?=GAELO_ORTHANC_EXPOSED_EXTERNAL_LOGIN?>"><br>
		    <label for="orthancExposedExternalPassword">Orthanc Exposed External Password</label><input name="orthancExposedExternalPassword" type="password" maxlength="255" value="<?=GAELO_ORTHANC_EXPOSED_EXTERNAL_PASSWORD?>"><br>
		    
		    <label for="orthancPacsAddress">Orthanc Pacs Address</label><input name="orthancPacsAddress" type="text" maxlength="255" value="<?=GAELO_ORTHANC_PACS_ADDRESS?>"><br>
		    <label for="orthancPacsPort">Orthanc Pacs Port</label><input name="orthancPacsPort" type="number" value="<?=GAELO_ORTHANC_PACS_PORT?>"><br>
		    <label for="orthancPacsLogin">Orthanc Pacs Login</label><input name="orthancPacsLogin" type="text" maxlength="255" value="<?=GAELO_ORTHANC_PACS_LOGIN?>"><br>	    
		    <label for="orthancPacsPassword">Orthanc Pacs Password</label><input name="orthancPacsPassword" type="password" maxlength="255" value="<?=GAELO_ORTHANC_PACS_PASSWORD?>"><br>
		    
			<label for="orthancPacsPassword">Use SMTP</label><input name="useSmtp" type="checkbox" value="1" <?php if (GAELO_USE_SMTP) echo('checked'); ?>><br>
		    <label for="orthancPacsPassword">SMTP Host</label><input name="smtpHost" type="text" maxlength="255" value="<?=GAELO_SMTP_HOST?>"><br>
		    <label for="orthancPacsPassword">SMTP Port</label><input name="smtpPort" type="text" value="<?=GAELO_SMTP_PORT?>"><br>
		    <label for="orthancPacsPassword">SMTP User</label><input name="smtpUser" type="text" maxlength="255" value="<?=GAELO_SMTP_USER?>"><br>
		    <label for="orthancPacsPassword">SMTP Password</label><input name="smtpPassword" type="password" maxlength="255" value="<?=GAELO_SMTP_PASSWORD?>"><br>
		    <label for="orthancPacsPassword">SMTP Secure type (ex: ssl)</label><input name="smtpSecure" type="text" maxlength="32" value="<?=GAELO_SMTP_SECURE?>"><br>

		</div>
		<div>
			<div class="text-center">
	    		<button type="button" id="preferenceSubmit" name="preferenceSubmit" class="btn btn-primary">Apply</button>
	    	</div>
	    </div>
    </form>
</div>