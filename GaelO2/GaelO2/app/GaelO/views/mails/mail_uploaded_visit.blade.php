@extends('mails.mail_template')

@section('content')
    The following visit has been uploaded on the platform:<br>
    Study : {{$study}}<br>
    Patient Number : {{$patientCode}}<br>
    Uploaded visit : {{$visitType}}<br>
@endsection
