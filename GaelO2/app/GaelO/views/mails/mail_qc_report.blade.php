@extends('mails.mail_template')

@section('content')
    <body style="word-spacing:normal;">
        The QC report for the following visit has been prepared on the platform:<br>
        Study : {{ $study }}<br>
        Patient Code : {{ $patientCode }}<br>
        Uploaded visit : {{ $visitType }}<br>
        @include('mails.mail_qc_report_buttons')
    </body>
@endsection
