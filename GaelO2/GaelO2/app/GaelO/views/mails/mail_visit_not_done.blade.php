@extends('mails.mail_template')

@section('content')
    A Not Done visit has been created <br>
    Patient Number : {{$patientCode}}<br>
    Study : {{$study}}<br>
    Visit Type : {{$visitType}}<br>
    Creating Username : {{$creatorUser}}<br>
@endsection
