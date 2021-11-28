@extends('mails.mail_template')

@section('content')
    The following visit is ready for review in the platform:<br>
    Study : {{$study}}<br>
    Patient Number : {{$patientId}}<br>
    Visit : {{$visitType}}<br>
@endsection
