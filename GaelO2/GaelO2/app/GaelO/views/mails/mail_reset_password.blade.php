@extends('mails.mail_template')

@section('content')
    <div>
        This automatic e-mail contains your new temporary password for your
        user account.<br>
        Username : {{$username}}<br>
        Temporary password : {{$newPassword}}<br>
        You will be asked to change this password at your first connection.
        <b style="color:red"> Please do not use Internet Explorer to connect.</b>
    </div>
    <div>
        For further informations read our <a href="https://www.gaelo.fr/documentation">documentation</a><br>
    </div>
@endsection
