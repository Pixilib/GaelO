@extends('mails.mail_template')

@section('visitLink')
    @if (!empty($visitId) && !empty($study))
        <div
            style="font-family: helvetica, sans-serif; color: #314053; font-size: 16px; line-height: 1.6; margin-top: 0; margin-bottom: 20px">
            <strong>Access Visit as :</strong>
        </div>
        <div style="font-family: helvetica, sans-serif; font-size: 14px; margin-top: 0; margin-bottom: 20px">
            <a href="{{ $webAddress }}/study/{{ $study }}/role/Investigator/visit/{{ $visitId }}"
                style="display: inline-block; color: #0495A0; font-family: helvetica, sans-serif; font-size: 14px; padding: 10px 8px; border: 1px solid #0495A0; border-radius: 50px; text-decoration: none; margin-right: 17px; margin-bottom: 10px;"><strong>Investigator</strong></a><a
                href="{{ $webAddress }}/study/{{ $study }}/role/Monitor/visit/{{ $visitId }}"
                style="display: inline-block; color: #FFBA4D; font-family: helvetica, sans-serif; font-size: 14px; padding: 10px 8px; border: 1px solid #FFBA4D; border-radius: 50px; text-decoration: none; margin-right: 17px; margin-bottom: 10px;"><strong>Monitor</strong></a><a
                href="{{ $webAddress }}/study/{{ $study }}/role/Controller/visit/{{ $visitId }}"
                style="display: inline-block; color: #353275; font-family: helvetica, sans-serif; font-size: 14px; padding: 10px 8px; border: 1px solid #353275; border-radius: 50px; text-decoration: none; margin-right: 17px; margin-bottom: 10px;"><strong>Controller</strong></a><a
                href="{{ $webAddress }}/study/{{ $study }}/role/Reviewer/visit/{{ $visitId }}"
                style="display: inline-block; color: #FD4646; font-family: helvetica, sans-serif; font-size: 14px; padding: 10px 8px; border: 1px solid #FD4646; border-radius: 50px; text-decoration: none; margin-right: 17px; margin-bottom: 10px;"><strong>Reviewer</strong></a><a
                href="{{ $webAddress }}/study/{{ $study }}/role/Supervisor/visit/{{ $visitId }}"
                style="display: inline-block; color: #314053; font-family: helvetica, sans-serif; font-size: 14px; padding: 10px 8px; border: 1px solid #314053; border-radius: 50px; text-decoration: none; margin-bottom: 10px;"><strong>Supervisor</strong></a>
        </div>
    @endif
@endsection
