@extends('mails.mail_template')

@section('content')
    A password reset request has been received for this account<br>
    Use <a href={{$url}}> this link to reset your password</a>  (24h validity)<br>
    If you didn't ask for a password reset, please ignore this email<br>
@endsection

