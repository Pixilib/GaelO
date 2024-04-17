@extends('mails.mail_template')

@section('content')
    Your {{$formType}} form sent for study : {{$study}}<br>
    Patient Code : {{$patientCode}}<br>
    Visit  : {{$visitType}}<br>
    Has been unlocked by {{$supervisorName}}.<br>
    You can now resend a new version of this form.<br>
@endsection
