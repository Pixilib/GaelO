@extends('mails.mail_template')

@section('content')
    Your {{$formType}} form sent for study : {{$study}}<br>
    Patient : {{$patientId}}<br>
    Visit  : {{$visitType}}<br>
    Have been deleted. <br>
    You can now resend a new version of this form<br>
@endsection
