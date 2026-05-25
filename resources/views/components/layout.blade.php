<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>{{ isset($title) ? $title . ' | ' . config('app.name', 'ScrumLab') : config('app.name', 'ScrumLab') }}</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('scrumlab-icon.svg') }}">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=plus-jakarta-sans:400,500,600,700,800&display=swap" rel="stylesheet" />
    <link href="https://fonts.bunny.net/css?family=playfair-display:600,700,800&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])

</head>

<body>
    {{ $slot }}
</body>

</html>
