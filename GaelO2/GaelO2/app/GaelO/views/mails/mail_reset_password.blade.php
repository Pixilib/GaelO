@extends('mails.mail_template')

@section('content')
    A password reset request has been recieved for this account<br>
    Use <a href={{$url}}>this link to reset your password</a><br>
    If you didn't asked for a password reset, please contact the administrator<br>
    {{$adminEmail}}<br>
@endsection
