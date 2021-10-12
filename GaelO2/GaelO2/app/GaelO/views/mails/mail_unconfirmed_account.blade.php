@extends('mails.mail_template')

@section('content')
    <div>
        Your account password is reset. Please log in at: {{$webAddress}}<br>
        Username : {{$username}}<br>
        Temporary password : {{$newPassword}}<br>
        You will be asked to change this password at your first log in attempt<br>
        on the platform.
    </div>
    <div style="text-align: center">
        <a href="https://www.gaelo.fr/documentation">Read GaelO Documentation</a><br>
        <b style="color:red"> Please do not use Internet Explorer to connect.</b>
    </div>
@endsection


