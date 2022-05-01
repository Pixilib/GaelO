@extends('mails.mail_template')

@section('content')
    The Admin user {{$email}} logged in from {{$remoteAddress}}<br>
    Please review this activity
@endsection

