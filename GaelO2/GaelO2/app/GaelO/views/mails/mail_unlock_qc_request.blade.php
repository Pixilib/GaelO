@extends('mails.mail_template')

@section('content')
    A Quality Control Unlock Request was emitted by {{$name}} <br>
    Visit Type : {{$visitType}}<br>
    Patient : {{$patientId}}<br>
    Study : {{$study}}<br>
    Message for request: {{$messages}}<br>
@endsection
