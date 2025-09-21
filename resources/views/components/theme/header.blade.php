<!doctype html>
<html lang="en" @foreach (session('theme_settings', [
    'data-layout' => 'vertical',
    'data-topbar' => 'light',
    'data-sidebar' => 'dark',
    'data-sidebar-size' => 'sm',
    'data-sidebar-image' => 'none',
    'data-preloader' => 'enable',
    'data-theme' => 'default',
    'data-theme-colors' => 'default',
    'data-bs-theme' => 'light',
    'data-layout-width' => 'fluid',
    'data-layout-position' => 'fixed',
    'data-layout-style' => 'default',
    'data-body-image' => 'none',
    'data-sidebar-visibility' => 'show',
]) as $key => $value) {{ $key }}="{{ $value }}" @endforeach>

<head>
    <meta charset="utf-8" />
    <title>@yield('title', 'Xeasy Web')</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" href="{{ URL::to('/') }}/custom/favicon.png">
    <script src="{{ URL::to('/') }}/assets/js/layout.js"></script>
    <link href="{{ URL::to('/') }}/assets/css/bootstrap.min.css" rel="stylesheet" type="text/css" />
    <link href="{{ URL::to('/') }}/assets/css/icons.min.css" rel="stylesheet" type="text/css" />
    <link href="{{ URL::to('/') }}/assets/css/app.min.css" rel="stylesheet" type="text/css" />
    <link href="{{ URL::to('/') }}/assets/css/custom.min.css" rel="stylesheet" type="text/css" />
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css" />
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.2.9/css/responsive.bootstrap.min.css" />
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.2.2/css/buttons.dataTables.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jquery-toast-plugin/1.3.2/jquery.toast.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/viewerjs/1.11.7/viewer.min.css" />
    @stack('styles')
</head>

<body>