@extends('mails.mail_template')

@section('content')
The following user account is blocked after too many bad password attempts.<br>
Email : {{$email}}<br>
The account is linked to the following studies:
<ul>
    @foreach ($studies as $study)
    <li>{{$study}}</li>
    @endforeach
</ul>
@endsection
