@extends('mails.mail_template')

@section('content')
    Results of the import patient in the study : {{ $study }}<br>

    @if (count($successList) > 0)
        <p>Success :</p>
        <ul>
            @foreach ($successList as $success)
                <li>{{ $success }}</li>
            @endforeach
        </ul>
    @endif

    @if (count($failList) > 0)
        <p>Error :</p>
        <ul>
            @foreach ($failList as $reason => $patientCodeArray)
                <li>{{ $reason }} :</li>
                <ul>
                    @foreach ($patientCodeArray as $patientCode)
                        <li>{{ $patientCode }}</li>
                    @endforeach
                </ul>
            @endforeach
        </ul>
    @endif
@endsection
