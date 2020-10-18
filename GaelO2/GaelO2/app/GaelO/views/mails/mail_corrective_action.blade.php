@extends('mails.mail_template')

@section('content')
    @if (count($done) == true)
    No corrective action could be applied on the following visit: <br>
    @else
    No corrective action could be applied on the following visit: <br>
    @endif
    Study : {{$study}}<br>
    Patient Number : {{$patientCode}}<br>
    Uploaded visit : {{$visitType}}<br>
@endsection
