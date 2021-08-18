@extends('mails.mail_template')

@section('content')
    Your account password is reset. Please log in at: {{$webAddress}}<br>
    Username : {{$username}}<br>
    Temporary password : {{$newPassword}}<br>
    You will be asked to change this password at your first log in attempt<br>
    on the platform.<br>
@endsection


