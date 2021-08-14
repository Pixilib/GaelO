@extends('mails.mail_template')

@section('content')
    @if ($correctionApplied == true)
    A Corrective action has be applied on the following visit:<br>
    @else
    No corrective action could be applied on the following visit:<br>
    @endif
    Study : {{$study}}<br>
    Patient Number : {{$patientCode}}<br>
    Visit Modality : {{$visitModality}}<br>
    Visit Type :  {{$visitType}}<br>
@endsection
