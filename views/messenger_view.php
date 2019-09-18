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
?>

<script type="text/javascript">
$(document).ready(function() {

    tinymce.init({
    	selector: '#messageText',
   	});

    $("#destinatorsList").chosen();
    $("#destinatorsRoles").chosen();

    $("#sendEmails").on('click', function(){
    	tinyMCE.triggerSave();

    	$.ajax({
	           type: "POST",
	           url: '/messenger',
	           data: $("#messengerForm").serialize(), // serializes the form's elements.
	           success: function(data) {
	        	   alertifySuccess('message sent');
	           },
	           error: function( jqXHR, textStatus, errorThrown ){
					console.log("Error:");
					console.log(jqXHR);
					console.log(textStatus);
					console.log(errorThrown);
			   }
     	});		
        
    });
});
</script>

<div id="messenger" class="container">

    <form class="align-self-center" id="messengerForm">
    
    	<div class="text-center">
    		<h1>Study : <?=htmlspecialchars($_SESSION['study'])?></h1>
    	</div>
    	
    	<div class="row">
    		<h4>Contact Group</h4>
    	</div>
    	<div class="row">
    		<select name="destinatorsRoles[]" id="destinatorsRoles" class="custom-select" style="width:auto;" multiple>
            	  <?php
            	  $roles = array("None", User::INVESTIGATOR, User::CONTROLLER, User::MONITOR, User::SUPERVISOR, User::REVIEWER, User::ADMINISTRATOR);
                  //add results in selector
                  foreach ($roles as $role){
                      echo'<option value='.$role.'>'.$role.' </option>';
                  }
                  ?>
        	</select>
        	
    	</div>
    
    	<div class="row">
    		<h4>Contact Users</h4>
    	</div>
    	<div class="row">
        	<select name="destinatorsList[]" id="destinatorsList" class="custom-select" style="width:auto;" multiple >
            	  <?php
                  //add results in selector
                  foreach ($usersObjects as $user) {
                      echo'<option value='.htmlspecialchars($user->username).'>'.$user->firstName.' '.$user->lastName.' </option>';
                  }
                  ?>
        	</select>
    	</div>
    	
    
    	<div class="row">
    		<h4>Message</h4>
    		<textarea class="container" id="messageText" name="messageText"></textarea>
    	</div>
    	
    	<div class="text-center">
    		 <button class="text-center btn btn-dark" type="button" id="sendEmails">Send</button>
    	</div>
    </form>

</div>