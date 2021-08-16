@extends('mails.mail_template')

@section('content')
    An Unlock {{$role}} form Request was emitted by {{$username}}<br>
    Visit Type : {{$visitType}}<br>
    Patient : {{$patientCode}}<br>
    Study : {{$study}}<br>
    Message for request: {{$messages}}<br>
@endsection
