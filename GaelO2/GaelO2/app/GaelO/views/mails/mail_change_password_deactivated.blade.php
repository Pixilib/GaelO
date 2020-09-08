@extends('mails.mail_template')

@section('content')
    The password change request cannot be done because the account is Deactivated<br>
    Username : {{$username}}<br>
    The account is linked to the following studies:<br>
    @foreach ($studies as $study)
        <li>{{$study}}</li>
    @endforeach
    Please contact the {{$this->corporation}} to activate your account:<br>
    {{$this->adminEmail}}<br>
@endsection

