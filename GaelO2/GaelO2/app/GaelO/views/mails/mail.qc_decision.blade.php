@extends('mails.mail_template')

@section('content')

    Quality Control of the following visit has been set to : {{$controlDecision}}<br>
    Study : {{$study}}<br>
    Patient Number : {{$patientCode}}<br>
    Visit : {{$visitType}}<br>
    Investigation Form : {{$formDecision}} Comment : {{$formComment}} <br>
    Image Series : {{$imageDecision}} Comment : {{$imageComment}} <br>
@endsection

