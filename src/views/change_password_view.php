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
	<title>Change your password</title>
	<?php require $_SERVER['DOCUMENT_ROOT'].'/includes/jsLibrairies.php'; ?>
	
	<script type="text/javascript">
	//Send form in Ajax and process JSON answers
	$(document).ready(function () {
		$('#sendChangePassword').on('click', function(e) {
			e.preventDefault();
			var valide=checkForm($("#changePassForm")[0]);
			if (valide){
				$.ajax({
					type: "POST",
					url: "/change_password",
					dataType: 'json',
					data: $("#changePassForm").serialize()+"&confirmer=1", // serializes the form's elements.
					success: function(data) {
						
						if (data['result'] == "DifferentPassword") {
							$("#error").html("Both entries for the new password you entered are not a match. Please try again.");
							$("#error").show();
							
						} else if (data['result'] == "WrongOldPassword") {
							$("#error").html("The old password you entered is not correct. Please try again.");
							$("#error").show();
							
						} else if (data['result'] == "IncorrectFormat") {
							$("#error").html("The new password does not match one or several of the following requirements:<br> ► Should be composed out of 8 alphanumerical characters<br> ► Should be composed out of letters and numbers<br> ► Should contain at least one capital letter<br> ► Should not be the last password you used<br> Please try again.");
							$("#error").show();
							
						} else if (data['result'] == "SamePrevious") {
							$("#error").html("The new password is identical to one of the three previous old password, Please try again.");
							$("#error").show();
							
						} else if (data['result'] == "OK") {
							$("#error").hide();
							$("#success").html("New Password set with sucess, Redirecting for reconnexion");
							$("#success").show();
							//Redirect back to the index for new login, timout to be able to read message
							setTimeout(function(){location.href = "index.php";}, 2000);  

						}

					},
					error: function( jqXHR, textStatus, errorThrown ){
						console.log("Error: Change Password");
					}	
				});
			}
		});

		//Form submission when press enter
		$(document).bind('keypress', function(e) {
            if(e.keyCode==13){
                 $('#sendChangePassword').trigger('click');
             }
        });
        
	});
	</script>
</head>

<body>
<?php require $_SERVER['DOCUMENT_ROOT'].'/includes/header.php'; ?>

    <div class="alert alert-danger text-center" id="error" style="display: none;" ></div>
    <div class="alert alert-success text-center" id="success" style="display: none;" ></div>
    
    <div class="alert alert-info" id="info" >
	Password Should :<br>
	► Contains at least 8 alphanumerical characters with only letters and numbers<br>
	► Not contains any special characters (such as , . ! # ... )<br>
	► Contains at least one capital letter<br>
	► Be different from the last three password you used<br>
    </div>
    
	<div class="container jumbotron">
		<form method="post" action="" class="form-horizontal" id="changePassForm">
			<div class="form-group">
				<label for="inputPseudo" class="col-form-label">Old Password:</label>
				<div class="col-sm-10">
					<input type="password" class="form-control" id="old_password" name="old_password" required>
				</div>
			</div>
			<div class="form-group">
				<label for="inputPasswd" class="col-form-label">New Password:</label>
				<div class="col-sm-10">
					<input type="password" class="form-control" id="mdp1" name="mdp1" required>
				</div>
				<label for="inputPasswd" class="col-form-label">Repeat new password:</label>
				<div class="col-sm-10">
					<input type="password" class="form-control" id="mdp2" name="mdp2" required>
				</div>
			</div>
			<div class="form-group">
				<div class="col-sm-offset-2 col-sm-10">
					<button type="button" name="confirmer" id="sendChangePassword" class="btn btn-primary">Change password</button>
				</div>
			</div>
		</form>
	</div>
</body>
</html>