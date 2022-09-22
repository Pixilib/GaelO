@extends('mails.mail_template')

@section('content')
    {!!$content!!}
    @if(!empty($patientId) && empty($visitId) && !empty($study))
        <em>This e-mail is related to
            <a href="{{$webAddress}}/study/{{$study}}/role/Supervisor/patient/{{$patientId}}">
                patient {{$patientId}}
            </a>
        </em>
    @endif
    @if(!empty($patientId) && !empty($visitId) && !empty($study))
        <em>This e-mail is related to
            <a href="{{$webAddress}}/study/{{$study}}/role/Supervisor/visit/{{$visitId}}">
                visit {{$visitId}}
                of patient {{$patientId}}
            </a>
        </em>
    @endif
@endsection
