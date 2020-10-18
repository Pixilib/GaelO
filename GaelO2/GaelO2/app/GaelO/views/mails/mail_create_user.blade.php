@extends('mails.mail_template')

@section('content')
    Your account is created for the upload platform used to exchange
    imaging data. Please log in at: {{$webAddress}} <br>
    Username : {{$username}}<br>
    Temporary password : {{$password}}<br>
    You will be asked to change this password at your first log in attempt
    on the platform.<br>
@endsection
