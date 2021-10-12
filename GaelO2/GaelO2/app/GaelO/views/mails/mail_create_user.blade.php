@extends('mails.mail_template')

@section('content')
    <div>
        Your account is created for the GaelO platform used to exchange
        imaging data.<br>
        Please log in at: {{$webAddress}}<br>
        Username : {{$username}}<br>
        Temporary password : {{$password}}<br>
        You will be asked to change this password at your first log in attempt
        on the platform.
    </div>
    <div style="text-align: center">
        <a href="https://www.gaelo.fr/documentation">Read GaelO Documentation</a><br>
        <b style="color:red"> Please do not use Internet Explorer to connect.</b>
    </div>

@endsection
