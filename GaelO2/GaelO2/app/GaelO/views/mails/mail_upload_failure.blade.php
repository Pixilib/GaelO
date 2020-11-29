@extends('mails.mail_template')

@section('content')
    An Import Error occured during validation of upload <br>
    Visit ID : {{$idVisit}}<br>
    Patient Code : {{$patientCode}}<br>
    Visit Type : {{$visitType}}<br>
    Study : {{$study}}<br>
    zipPath : {{$zipPath}}<br>
    userId: {{$userId}}<br>
    error  : {{$errorMessage}}<br>
@endsection
