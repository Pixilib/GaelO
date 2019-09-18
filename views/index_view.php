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


<!DOCTYPE html>
<html lang="en">

<head>
	<title>Log In</title>
	<?php include_once $_SERVER['DOCUMENT_ROOT'] . '/includes/jsLibrairies.php'; ?>
	<script type="text/javascript">
		//Send form in Ajax and process JSON answers
		$(document).ready(function() {
			$('#connexionBtn').on('click', function(e) {
				e.preventDefault();
				var valide = checkForm($("#login-form")[0]);
				if (valide) {
					$.ajax({
						type: "POST",
						dataType: 'json',
						url: "index.php",
						data: $("#login-form").serialize() + "&formSent=1", // serializes the form's elements.
						success: function(data) {
							if (data['result'] == "user") {
								location.href = "/main";

							} else {
								if (data['result'] == "temporary") {
									if (!data['isPasswordDateValid']) {
										$("#error").html("Password obsolete");
									} else {
										$("#error").html("Account awaiting Password reset");
									}

									setTimeout(function() {
										location.href = "/change_password";
									}, 2000);

								} else if (data['result'] == "Blocked") {
									$("#error").html("Your account has been blocked. Please contact <?= GAELO_CORPORATION ?> by clicking on “A request? Any questions? “, below this page");


								} else if (data['result'] == "Deactivated") {
									$("#error").html("Your account has been deactivated. Please contact <?= GAELO_CORPORATION ?> by clicking on “A request? Any questions? “, below this page");


								} else if (data['result'] == "unknown") {
									$("#error").html("The username you entered is not recognized by the platform. Please enter your correct username or contact <?= GAELO_CORPORATION ?> by clicking on \"A request? Any questions?\", below this page.");


								} else if (data['result'] == "NowBlocked") {
									$("#error").html("The password you entered does not match the username. Your account has been blocked.");


								} else if (data['result'] == "WrongPassword") {
									if (data['attempt'] == 1) {
										$("#error").html("The Password you entered does not match the username. 2 Attempts remaining. Please notice that after 3 attempts with a wrong password, your account will be blocked.");

									} else if (data['attempt'] == 2) {
										$("#error").html("The Password you entered does not match the username. Last attempt remaining. Please notice that after 3 attempts with a wrong password, your account will be blocked.");

									}

								}
								$("#error").show();
							}

						},
						error: function(jqXHR, textStatus, errorThrown) {
							console.log("Error:");
							console.log(jqXHR);
							console.log(textStatus);
							console.log(errorThrown);
						}
					});
				}
			});

			//Form submission if press enter
			$(document).bind('keypress', function(e) {
				if (e.keyCode == 13) {
					$('#connexionBtn').trigger('click');
				}
			});

		});
	</script>
</head>

<body>
	<noscript>
		<div class="alert alert-danger" id="errorJS"> Warning : Your Navigator do not support Javascript, mendatory for this website</div>
	</noscript>

	<section id="logos">
		<img id="logo-gaelo" src="assets/images/gaelo-logo-square.png" alt="GaelO">
		<div id="name-div">
			<?php if(defined('GAELO_PLATEFORM_NAME')) echo(GAELO_PLATEFORM_NAME); else echo('GaelO')?>
		</div>
	</section>

	<div class="alert alert-danger" id="error" style="display: none;"></div>
	<div id="login" class="block block-400">
		<div class="block-title">Authentication</div>
		<div class="block-content">
			<form id="login-form">

				<fieldset>
					<label>Username*</label>
					<input class="form-control" type="text" id="username" placeholder="username" name="username" required>
				</fieldset>

				<fieldset>
					<label>Password*</label>
					<input class="form-control" type="password" id="mdp" placeholder="password" name="mdp" required>
				</fieldset>

				<fieldset class="text-right">
					<button name="connexion" id="connexionBtn" type="button" class="btn btn-dark"> Connect </button> <br>
				</fieldset>

				<div class="text-center">
					<a href="/forgot_password">Forgot your password?</a><br>
					<a href="/request">A request ? Any questions ?</a><br>
				</div>

			</form>
		</div>
	</div>
</body>

</html>