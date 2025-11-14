<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8" />
    <meta
        content="width=device-width, initial-scale=1"
        name="viewport"
    />
    <title>Mini Wallet</title>
    @vite(['resources/css/app.css', 'resources/ts/main.ts'])
</head>

<body class="bg-slate-950 text-slate-50 antialiased">
    <div
        class="min-h-screen"
        id="app"
    ></div>
    <noscript>Mini Wallet requires JavaScript to run.</noscript>
</body>

</html>
