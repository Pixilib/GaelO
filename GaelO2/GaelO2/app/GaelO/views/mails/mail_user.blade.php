@extends('mails.mail_minimal_template')

@section('content')
    {!!$content!!}
    @if(!empty($patientId) && empty($visitId) && !empty($study))
        <i>This e-mail is related to 
            <a href="{{$webAddress}}/study/{{$study}}/role/Supervisor/patient/{{$patientId}}">
                patient {{$patientId}}
            </a>
        </i>
    @endif
    @if(!empty($patientId) && !empty($visitId) && !empty($study))
        <i>This e-mail is related to 
            <a href="{{$webAddress}}/study/{{$study}}/role/Supervisor/visit/{{$visitId}}">
                visit {{$visitId}} 
                of patient {{$patientId}}
            </a>
        </i>
    @endif
@endsection
