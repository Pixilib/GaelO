<html>

<body style="margin: 0; background-color: #f7f7f7;">
    <table align="center" cellpadding="0" cellspacing="0" width="100%">
        <tr>
            <td align="center">
                <table class="content" align="center" cellpadding="0" width="100%" cellspacing="0" style="border-collapse: separate; margin-top: 80px; max-width: 600px;" bgcolor="#ffffff">
                    <tr>
                        <td class="banner" align="center">
                            <a href="{{ $webAddress }}"><img src="<?= $message->embed(public_path('static/media/gaelo-mail-header.jpg')) ?>" alt="Logo" width="600" /></a>
                        </td>
                    </tr>
                    <tr>
                        <td bgcolor="#ffffff">
                            <table class="content content_wrapper" align="center" cellpadding="0" cellspacing="0" width="100%" style="max-width: 600px; padding: 32px 30px;">
                                <tr>
                                    <td>
                                        <div style="font-family: helvetica; color: #314053; font-size: 16px; line-height: 1.6; margin-top: 0; margin-bottom: 20px;">
                                            <strong>Dear {{ $name ?? "User" }},</strong>
                                        </div>
                                        <div style="font-family: helvetica; color: #314053; font-size: 16px; line-height: 1.6; margin-top: 0; margin-bottom: 40px;">
                                            @yield('content')</div>

                                        <div> @yield('visitLink') </div>
                                        <div style="font-family: helvetica; color: #314053; font-size: 16px; line-height: 1.6; margin-top: 0; margin-bottom: 20px;">
                                            Please contact the Imaging Department of {{ $corporation }} for any
                                            questions <a style="font-family: helvetica; color: #314053; font-size: 16px; line-height: 1.6; text-decoration: underline;" href="mailto:{{ $adminEmail }}"><strong>{{ $adminEmail }}</strong></a>
                                        </div>
                                        <div style="font-family: helvetica; color: #314053; font-size: 16px; line-height: 1.6; margin-top: 0; margin-bottom: 20px;">
                                            Kind regards,</div>
                                        <div style="font-family: helvetica; color: #314053; font-size: 16px; line-height: 1.6; margin-top: 0; margin-bottom: 20px;">
                                            The Imaging Department of {{ $corporation }}.</div>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td bgcolor="#f7f7f7">
                            <table class="content" align="center" cellpadding="0" cellspacing="0" width="100%" style="max-width: 600px; margin:0 auto; line-height: 1.3; padding: 18px">
                                <tr>
                                    <td align="center">
                                        <div style="font-family: helvetica; color: #314053; font-size: 18px; margin-top: 10px; margin-bottom: 10px;">
                                            <a style="font-family: helvetica; color: #314053; font-size: 18px; text-decoration: none;" href="{{ $webAddress }}"><strong>{{ $webAddress }}</strong></a>
                                        </div>
                                        <div style="font-family: helvetica; color: #314053; font-size: 14px; margin-top: 0px; margin-bottom: 80px; font-style: italic;">
                                            This is an automatic e-mail. Please do not reply.</div>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>

</html>
