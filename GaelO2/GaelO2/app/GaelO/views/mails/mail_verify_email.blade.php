@extends('mails.mail_template')

@section('content')
    <div>
        Please follow this link to verify your email <a href={{$url}}><br>
    </div>
    <div>
        For further informations read our <a href="https://www.gaelo.fr/documentation">documentation</a><br>
    </div>

@endsection
