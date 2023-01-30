@extends('mails.mail_template')

@section('content')
    <div>
        Your account is created for the GaelO platform used to exchange
        imaging data.<br>
        Your login is your email address : {{$email}}<br>
        You should have received a separate email containing a link to set your password<br>
        <strong style="color:red">Please use an up to date browser (Chrome, Firefox, Edge v90+, Opera v80+, Safari v15+) to connect.</strong>
    </div>

@endsection
