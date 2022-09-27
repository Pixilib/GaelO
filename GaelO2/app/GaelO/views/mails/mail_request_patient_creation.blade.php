@extends('mails.mail_template')

@section('content')
{!!$content!!}
<em>
    <a href="{{$webAddress}}/study/{{$study}}/role/Supervisor/patients">
        Connect to the platform
    </a>
    if you want to add these patients to {{$study}} study
</em>
@endsection