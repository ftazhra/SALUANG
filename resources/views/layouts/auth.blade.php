<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>@yield('title', 'SALUANG')</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/css/auth.css?v=10">
    <link rel="stylesheet" href="{{ asset('css/saluang-selects.css') }}?v=1">

    <script src="{{ asset('js/auth.js') }}?v=20261016-3" defer></script>
</head>
<body>
    @yield('content')
</body>
</html>