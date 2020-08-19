@extends('mails.mail_template')

@section('content')
    This automatic e-mail contains your new temporary password for your
    user account.<br>
    Username : {{$username}} <br>
    Temporary password : {{$newPassword}} <br>
    You will be asked to change this password at your first connection.<br>
@endsection
