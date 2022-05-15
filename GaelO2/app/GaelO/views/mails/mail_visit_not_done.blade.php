@extends('mails.mail_template')

@section('content')
    A Not Done visit has been created.<br>
    Patient Code : {{$patientCode}}<br>
    Study : {{$study}}<br>
    Visit Type : {{$visitType}}<br>
    Reason for Not Done Visit : {{$notDoneReason}}<br>
    Creating Username : {{$creatorUser}}<br>
@endsection
