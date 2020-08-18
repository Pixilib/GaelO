@extends('mails.mail_template')

@section('content')
    The following request was sent and will be processed as soon as possible:<br>
    Name : {{$name}}<br>
    E-mail : {{$email}}<br>
    Investigational center : {{$center}}<br>
    Request : {{$request}}<br>
@endsection

