<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>@yield('title', 'SALUANG')</title>

    <link rel="stylesheet" href="/css/auth.css?v=10">

    <script src="{{ asset('js/auth.js') }}?v=20261016-3" defer></script>
</head>
<body>
    @yield('content')
</body>
</html>