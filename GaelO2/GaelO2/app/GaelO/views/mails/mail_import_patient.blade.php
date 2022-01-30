@extends('mails.mail_template')

@section('content')
    Results of the import patient in the study : {{$study}}<br>

    <p>Success :</p>
    <ul>
        @foreach ($successList as $success)
        <li>{{$success}}</li>
        @endforeach
    </ul>

    <p>Error :</p>
    <ul>
        @foreach ($failList as $reason => $patientIdArray)
        <li>{{$reason}} :</li>
            @foreach ($patientIdArray as $patientId)
            <li>{{$patientId}}</li>
            @endforeach
        @endforeach
    </ul>

@endsection

