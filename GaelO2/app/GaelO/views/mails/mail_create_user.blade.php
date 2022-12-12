@extends('mails.mail_template')

@section('content')
    <div>
        Your account is created for the GaelO platform used to exchange
        imaging data.<br>
        Your login is your email address : {{$email}}<br>
        You should have received a separate email containing a link to set your password<br>
        <strong style="color:red"> Please do not use Internet Explorer to connect.</strong>
    </div>

@endsection
