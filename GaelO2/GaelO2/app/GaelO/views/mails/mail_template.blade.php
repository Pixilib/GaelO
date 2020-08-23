<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>{{$platformName}}</title>
    <style>
        h1 {
            color: brown;
            text-align: center
        }

        a:link {
            color: brown;
        }

        header {
            text-align: center;
        }

        #footer-link {
            width: 100%;
            background-color: beige;
            color: black;
            text-align: center;
        }

        #automatic {
            font-style: italic;
        }

        #logo-gaelo {
            max-height: 180px;
            width: auto;
        }

        #footer-contact {
            color: black;
            background: white;
            text-align: left
        }

        #message {
            text-align: left;
        }
    </style>
</head>

<body>
    <header class="main-header" id="header">
        <img id="logo-gaelo" src="<?= $message->embed(public_path('static/media/gaelo-logo-square.png')); ?>" alt="Banner Image" >
    </header>
    <h1><a href="http://{{$webAddress}}">{{$platformName}}</a></h1>
    <div id="message">
        <b>Dear {{$name}},</b><br>
        @yield('content')
    </div>
    <div class="footer">
        <p id="footer-contact">Please contact the Imaging Department of {{$corporation}} for any questions (<a HREF="mailto:{{$adminEmail}}">{{$adminEmail}}</a>) <br>
            Kind regards, <br>
            The Imaging Department of {{$corporation}}. <br>
        </p>
        <p id="automatic">
            This is an automatic e-mail. Please do not reply. <br>
        </p>
        <p id="footer-link">
            <a href="http://{{$webAddress}}">{{$webAddress}}</a>
        </p>
    </div>
</body>

</html>
