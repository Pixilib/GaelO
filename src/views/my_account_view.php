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
<div id="editUser">

    <div> 
        <label for="changeUsername" class="control-label">Username : </label>
            <input name="changeUsername" id="changeUsername" type="text" class="form-control" value="" disabled>
        <label for="changeEmail" class="control-label">Email : </label>
            <input name="changeEmail" id="changeEmail" type="text" class="form-control" value="" disabled>
        <label for="changeLastName" class="control-label">Last Name : </label>
            <input name="changeLastName" id="changeLastName" type="text" class="form-control" value="">
        <label for="changeFirstName" class="control-label">First Name : </label>
            <input name="changeFirstName" id="changeFirstName" type="text" class="form-control" value="">
        <label for="changePhoneNumber" class="control-label">Phone Number : </label>
            <input name="changePhoneNumber" id="changePhoneNumber" type="text" class="form-control" value="">
    </div>


    <div class="text-center mt-3 mb-3">
        <input type="button" class="btn btn-primary" id="updateButton" value="Update">
    </div>


    <div>
        To Change email : Use "Request" page to contact administrators <br>
        To Reset Password : Use "Forgot Password" at main page
    </div>


</div>
<script type="text/javascript">
     $(document).ready(function () {
         $.ajax({
    	           type: "GET",
    	           dataType: 'json',
    	           url: '/scripts/get_account_details.php',
    	           success: function(data) {
                        $("#changeUsername").val(data['Username'])
                        $("#changeLastName").val(data['LastName'])
                        $("#changeFirstName").val(data['FirstName'])
                        $("#changeEmail").val(data['Email'])
                        $("#changePhoneNumber").val(data['Phone'])
    	           }
             	})
                 
        $('#updateButton').on('click', function(){
            let lastName = $("#changeLastName").val()
            let firstName = $("#changeFirstName").val()
            let email = $("#changeEmail").val()
            let phoneNumber= $("#changePhoneNumber").val()
            $.ajax({
                type : "POST",
                dataType : 'json',
                url : '/scripts/get_account_details.php',
                data : { LastName : lastName,
                        FirstName :  firstName,
                        Phone : phoneNumber},
                success : function(data){
                    alertifySuccess('Update Done');
                }
            })
        })
     })
</script>