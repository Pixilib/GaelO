@extends('mails.mail_template')

@section('content')
You have received the following message :
<div style="padding: 20px; margin: 20px; box-shadow: rgba(0, 0, 0, 0.1) 0px 4px 12px;">
    {!!$content!!}
</div>
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