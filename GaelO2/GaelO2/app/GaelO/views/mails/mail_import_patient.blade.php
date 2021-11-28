@extends('mails.mail_template')

@section('content')
    Results of the import patient in the study : {{$study}}<br>

    @foreach ($successList as $success)
    <li>{{$success}}</li>
    @endforeach

    @foreach ($failList as $reason => $patientIdArray)
    <li>{{$reason}} :</li>
        @foreach ($patientIdArray as $patientId)
        <li>{{$patientId}}</li>
        @endforeach
    @endforeach

@endsection

