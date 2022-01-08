@extends('mails.mail_template')

@section('content')
    <div>
        Please follow <a href={{$url}}>this link to verify your email<br>
    </div>

@endsection
