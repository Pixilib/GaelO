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