@extends('mails.mail_template')

@section('content')
    The following visit is ready for review on the platform:<br>
    Study : {{$study}}<br>
    Patient Code : {{$patientCode}}<br>
    Visit : {{$visitType}}<br>
@endsection
