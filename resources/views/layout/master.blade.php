<!DOCTYPE html>
<!--
Template Name: NobleUI - Laravel Admin Dashboard Template
Author: NobleUI
Website: https://nobleui.com
Contact: nobleui.team@gmail.com
Purchase: https://1.envato.market/nobleui_laravel
License: You must have a valid license to legally use this template for your project.
-->
<html>
<head>
  <meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<meta http-equiv="X-UA-Compatible" content="ie=edge">
  <meta name="description" content="Responsive Laravel Admin Dashboard Template based on Bootstrap 5">
	<meta name="author" content="NobleUI">
	<meta name="keywords" content="nobleui, bootstrap, bootstrap 5, bootstrap5, admin, dashboard, template, responsive, css, sass, html, laravel, theme, front-end, ui kit, web">

  <title>@yield('title', 'Dashboard') | DesignDemonz</title>

  <!-- color-modes:js -->
  @vite(['resources/js/pages/color-modes.js'])
  <script>
    (function() {
      const theme = localStorage.getItem('theme') || (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
      document.documentElement.setAttribute('data-bs-theme', theme);
    })();
  </script>

  <!-- Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700;900&display=swap" rel="stylesheet">
  <!-- End fonts -->
  
  <!-- CSRF Token -->
  <meta name="_token" content="{{ csrf_token() }}">
  
  <link rel="icon" type="image/svg+xml" href="{{ asset('build/images/favicon.svg') }}">
  <link rel="alternate icon" type="image/png" href="{{ asset('build/images/favicon.png') }}">

  <!-- Splash Screen -->
  <link href="{{ asset('splash-screen.css') }}" rel="stylesheet" />

  <!-- plugin css -->
  <link href="{{ asset('build/plugins/perfect-scrollbar/perfect-scrollbar.css') }}" rel="stylesheet" />

  @stack('plugin-styles')

  <!-- CSS for LTR layout-->
  @vite(['resources/sass/app.scss', 'resources/css/custom.css'])

  <!-- CSS for RTL layout-->
  <!-- @vite(['resources/rtl-css/app-rtl.css', 'resources/rtl-css/custom-rtl.css']) -->

  @stack('plugin-styles')
  @stack('style')

  {{-- Critical styles (inline so it works on live without client asset cache delay) --}}
  <style>
    [data-bs-theme='dark']  .logo-mini-light { display: none !important; }
    [data-bs-theme='light'] .logo-mini-dark  { display: none !important; }

    /* Active Sidebar Menu Item Highlight (Red) */
    .sidebar .sidebar-body .nav .nav-item.active > .nav-link,
    .sidebar .sidebar-body .nav .nav-item.active > .nav-link .link-title,
    .sidebar-dark .sidebar .sidebar-body .nav .nav-item.active > .nav-link,
    .sidebar-dark .sidebar .sidebar-body .nav .nav-item.active > .nav-link .link-title {
      color: #ef4444 !important;
      font-weight: 600 !important;
    }

    .sidebar .sidebar-body .nav .nav-item.active > .nav-link::before,
    .sidebar-dark .sidebar .sidebar-body .nav .nav-item.active > .nav-link::before {
      background: #ef4444 !important;
      background-color: #ef4444 !important;
    }

    .sidebar .sidebar-body .nav .nav-item.active > .nav-link .link-icon,
    .sidebar .sidebar-body .nav .nav-item.active > .nav-link svg,
    .sidebar-dark .sidebar .sidebar-body .nav .nav-item.active > .nav-link .link-icon,
    .sidebar-dark .sidebar .sidebar-body .nav .nav-item.active > .nav-link svg {
      color: #ef4444 !important;
      stroke: #ef4444 !important;
      fill: rgba(239, 68, 68, 0.15) !important;
    }

    .sidebar .sidebar-body .nav.sub-menu .nav-item .nav-link.active,
    .sidebar-dark .sidebar .sidebar-body .nav.sub-menu .nav-item .nav-link.active {
      color: #ef4444 !important;
    }

    .sidebar .sidebar-body .nav.sub-menu .nav-item .nav-link.active::before,
    .sidebar-dark .sidebar .sidebar-body .nav.sub-menu .nav-item .nav-link.active::before {
      background: #ef4444 !important;
      border-color: #ef4444 !important;
    }
  </style>
</head>
<body data-base-url="{{url('/')}}">

  <script>
    // Create splash screen container
    var splash = document.createElement("div");
    splash.innerHTML = `
      <div class="splash-screen">
        <div class="logo"></div>
        <div class="spinner"></div>
      </div>`;
    
    // Insert splash screen as the first child of the body
    document.body.insertBefore(splash, document.body.firstChild);

    // Add 'loaded' class to body once DOM is fully loaded
    document.addEventListener("DOMContentLoaded", function () {
      document.body.classList.add("loaded");
    });
  </script>

  <div class="main-wrapper" id="app">
    @include('layout.partials.sidebar')
    <div class="page-wrapper">
      @include('layout.partials.header')
      <div class="page-content container-xxl">
        @yield('content')
      </div>
      @include('layout.partials.footer')
    </div>
  </div>

    <!-- base js -->
    @vite(['resources/js/app.js'])
    <script src="{{ asset('build/plugins/bootstrap/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('build/plugins/lucide/lucide.min.js') }}"></script>
    <script src="{{ asset('build/plugins/perfect-scrollbar/perfect-scrollbar.min.js') }}"></script>
    <!-- end base js -->

    <!-- plugin js -->
    @stack('plugin-scripts')
    <!-- end plugin js -->

    <!-- common js -->
    @vite(['resources/js/pages/template.js'])
    <!-- end common js -->

    @stack('custom-scripts')
    @include('partials.message-notification')
</body>
</html>