@extends('mails.mail_template')

@section('content')
    The password change request cannot be done because the account is Deactivated<br>
    User : {{$firstname}} {{$lastname}}<br>
    Email : {{$email}}<br>
    Please contact the {{$corporation}} to activate your account:<br>
    {{$adminEmail}}<br>
@endsection

