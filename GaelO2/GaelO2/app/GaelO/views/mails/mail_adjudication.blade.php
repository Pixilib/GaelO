@extends('mails.mail_template')

@section('content')
    Review of the following visit is awaiting adjudication <br>
    Study : {{$study}}<br>
    Patient Number : {{$patientCode}}<br>
    Visit : {{$visitType}}<br>
    The visit is awaiting for your adjudication review
@endsection

