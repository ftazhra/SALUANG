
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>@yield('title', 'SALUANG')</title>

    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link
    rel="stylesheet"
    href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <link rel="stylesheet" href="{{ asset('css/saluang.css') }}?v=2">

    <script src="{{ asset('js/saluang.js') }}" defer></script>
    <script src="{{ asset('js/saluang-panduan.js') }}?v=1" defer></script>
</head>

<body>
    @yield('content')
</body>

</html>