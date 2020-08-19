@extends('mails.mail_template')

@section('content')
    Your form sent for study : {{$study}}<br>
    Patient : {{$patientCode}}<br>
    Visit  : {{$visitType}} <br>
    Have been deleted. <br>
    You can now resend a new version of this form <br>
@endsection
