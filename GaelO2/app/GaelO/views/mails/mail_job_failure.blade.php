@extends('mails.mail_template')

@section('content')
    A Job Failure occured in the job {{$jobType}}<br>
    Error Message : {{$errorMessage}}<br>
    @foreach($details as $key => $value)
    {{ $key }} : {{ $value }}<br>
    @endforeach
@endsection
