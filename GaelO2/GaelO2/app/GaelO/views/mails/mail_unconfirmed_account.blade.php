@extends('mails.mail_template')

@section('content')
    <div>
        Your account password is reset. Please log in at: {{$webAddress}}<br>
        Username : {{$username}}<br>
        Temporary password : {{$newPassword}}<br>
        You will be asked to change this password at your first log in attempt<br>
        on the platform.
        <b style="color:red"> Please do not use Internet Explorer to connect.</b>
    </div>
    <div>
        For further informations read our <a href="https://www.gaelo.fr/documentation">documentation</a><br>
    </div>
@endsection


