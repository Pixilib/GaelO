@extends('mails.mail_template')

@section('content')
    This is a magic link to directly reach the study {{$study}}, patient {{$patientCode}}
    @if($visitType !== null)
        Visit :  {{$visitType}}.
    @endif
    as {{$role}}
    <br>
    <a href={{$url}}>
        Simply follow this link to access this
        @if($visitType !== null) Visit
        @else Patient
        @endif
    </a>
     (72h validity)

@endsection
