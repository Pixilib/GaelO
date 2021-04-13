@extends('mails.mail_template')

@section('content')
    The password change request cannot be done because the account is Deactivated<br>
    Username : {{$username}}<br>
    Please contact the {{$corporation}} to activate your account:<br>
    {{$adminEmail}}<br>
@endsection

