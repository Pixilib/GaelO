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
	<title>Send Request</title>

	<?php include_once $_SERVER['DOCUMENT_ROOT'].'/includes/jsLibrairies.php'; ?>

	<script type="text/javascript">
		$(document).ready(function() {
			$("#send").on('click', function() {
				if (checkForm($("#requestForm")[0])) {
					$.ajax({
						type: "POST",
						url: '/request',
						data: $("#requestForm").serialize(), // serializes the form's elements.
						success: function(data) {
							$("#requestConfirm").html("Request successfully sent, redirecting to main page");
							$("#requestConfirm").show();
							setTimeout(function() {
								window.location.href = "index.php";
							}, 2000);

						}
					});
				}

			});
		});
	</script>
</head>

<body>

	<?php require $_SERVER['DOCUMENT_ROOT'].'/includes/header.php'; ?>

	<div id="requestConfirm" class="text-center alert alert-success" style="display: none;"></div>
	<div class="block block-400">
		<div class="block-title">Send a request</div>
		<div class="block-content">
			<form id="requestForm" class="form-horizontal ">

				<div class="form-group">
					<label class="control-label">Name:</label>
					<div>
						<input type="text" class="form-control" id="name" name="name" required>
					</div>
				</div>

				<div class="form-group">
					<label class="control-label">Email:</label>
					<div>
						<input type="text" class="form-control" id="email" name="email" required>
					</div>
				</div>

				<div class="form-group">
					<label class="control-label">Investigator center:</label>
					<div>
						<input type="text" class="form-control" id="ic" name="ic" required>
					</div>
				</div>

				<div class="form-group">
					<label class="control-label">Request:</label>
					<div>
						<textarea class="form-control" id="request" name="request" rows="3" required></textarea>
					</div>
				</div>

				<div class="text-right">
					<div class="fset-2">
						<input type="hidden" name="send" value="1" />
						<a href="index.php" class="btn btn-secondary">Cancel</a>
						<button id="send" type="button" class="btn btn-primary">Send</button>
					</div>
				</div>
			</form>
		</div>
	</div>
</body>

</html>