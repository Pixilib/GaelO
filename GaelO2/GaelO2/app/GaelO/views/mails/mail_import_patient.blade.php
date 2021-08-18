@extends('mails.mail_template')

@section('content')
    Results of the import patient in the study : {{$study}}<br>

    @foreach ($successList as $success)
    <li>{{$success}}</li>
    @endforeach

    @foreach ($failList as $reason => $patientCodeArray)
    <li>{{$reason}} :</li>
        @foreach ($patientCodeArray as $patientCode)
        <li>{{$patientCode}}</li>
        @endforeach
    @endforeach

@endsection

