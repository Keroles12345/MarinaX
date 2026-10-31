<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>{{ __(trim($__env->yieldContent('title', 'بوابة الموظفين'))) }} | {{ __('بوابة العمليات') }}</title>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
@vite(['resources/css/app.css', 'resources/js/app.js'])
@if (request()->routeIs('dashboard'))
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
@endif
