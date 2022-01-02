@extends('mails.mail_template')

@section('content')
    <div>
        Please follow <a href={{$url}}>this link to verify your email<br>
    </div>
    <div>
        For further informations read our <a href="https://www.gaelo.fr/documentation">documentation</a><br>
    </div>

@endsection
