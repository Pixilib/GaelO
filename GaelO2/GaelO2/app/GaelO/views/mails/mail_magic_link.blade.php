@extends('mails.mail_template')

@section('content')
    Magic Link To Connect <a href={{$url}}>Following This Link </a>
@endsection
