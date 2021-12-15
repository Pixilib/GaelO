@extends('mails.mail_template')

@section('content')
    <div>
        Your account is created for the GaelO platform used to exchange
        imaging data.<br>
        Define your password on the platform following <a href={{$url}}> this link</a> (72h validity) <br>
        <b style="color:red"> Please do not use Internet Explorer to connect.</b>
    </div>
    <div>
        For further informations read our <a href="https://www.gaelo.fr/documentation">documentation</a><br>
    </div>

@endsection
