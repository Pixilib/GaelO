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

<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<title>Ask New password</title>
    <?php include_once $_SERVER['DOCUMENT_ROOT'].'/includes/jsLibrairies.php'; ?>
    
    <script type="text/javascript">
     $(document).ready(function () {
    	$("#send").on('click', function() {
    		if( checkForm( $("#forgotForm")[0] ) ){
    			$.ajax({
    	           type: "POST",
    	           dataType: 'json',
    	           url: '/forgot_password',
    	           data: $("#forgotForm").serialize()+"&send=1", // serializes the form's elements.
    	           success: function(data) {
        	          if(data=="Success"){
        	        	  $("#forgotConfirm").html("New password successfully sent by email, redirecting to main page");
        	        	  $("#forgotConfirm").removeClass( "alert-danger" ).addClass( "alert-success" );
        	        	  $("#forgotConfirm").show();
        	        	  setTimeout(function () {
      	        			window.location.href = "index.php";
      	        		  }, 2000);
        	        	  
        	          }else if(data=="Blocked"){
        	        	  $("#forgotConfirm").html("Your account is blocked or deactivated. Can't change Password");
        	        	  $("#forgotConfirm").removeClass( "alert-success" ).addClass( "alert-danger" );
        	        	  $("#forgotConfirm").show();
            	          
        	          }else if(data=="Unknown"){
        	        	  $("#forgotConfirm").html("The username and e-mail are not found. Please contact the Imaging Department of <?= GAELO_CORPORATION ?> by clicking on \"A request? Any questions?\" at the main page");
        	        	  $("#forgotConfirm").removeClass( "alert-success" ).addClass( "alert-danger" );
        	        	  $("#forgotConfirm").show();
        	          }

    	           }
             	});		
    		}
    			 
    	});
     });
    
    </script>
		
</head>
<body>

	<?php require $_SERVER['DOCUMENT_ROOT'].'/includes/header.php'; ?>
	
	<div class="block block-400">
		<div class="block-title">Password assistance</div>
    <div class="block-content">
      <div id="forgotConfirm" class="text-center alert" style="display: none;"></div>
      <form class="form-horizontal" id="forgotForm">
        <div class="form-group">
          <label class="control-label">Username*</label>
					<input type="text" class="form-control" id="username" name="username" required >
        </div>
        <div class="form-group">
          <label class="control-label">Email*</label>
					<input type="text" class="form-control" id="email" name="email" required>
        </div>
        <div class="form-group">
          <div class="text-right">
            <a href="index.php" class="btn btn-secondary">Cancel</a>
            <button type="button" name="send" id="send" class="btn btn-primary">Send</button>
          </div>
        </div>
      </form>
    </div>
	</div>

</body>
</html>