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
            margin: 5% 0;
        }

        #visit-link {
            text-align: left;
            margin: 5% 0;
        }

        table,
        th,
        td {
            border: 1px solid black;
        }

        th {
            font-weight: bold;
            text-align: center;
        }

        table {
            border-collapse: collapse;
            width: 80%;
        }
    </style>
</head>

<body>
    <header class="main-header" id="header">
        <img id="logo-gaelo" src="<?= $message->embed(public_path('static/media/gaelo-logo.png')); ?>" alt="Banner Image">
    </header>
    <div id="message">
        @yield('content')
    </div>
    <div class="footer">
        <p id="automatic">
            This is an automatic e-mail. Please do not reply.<br>
        </p>
        <p id="footer-link">
            <a href="{{$webAddress}}">{{$webAddress}}</a>
        </p>
    </div>
</body>

</html>