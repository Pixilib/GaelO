@extends('mails.mail_template')

@section('content')
    Review of the following visit is concluded <br>
    Study : {{ $study }}<br>
    Patient Code : {{ $patientCode }}
    Visit : {{ $visitType }}<br>
    <b>Conclusion Value : {{ $conclusionValue }}</b>
@endsection
