<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="FlockSense is an advanced smart farm management platform designed specifically for modern poultry producers, empowering farmers to remotely monitor, analyze, and optimize every aspect of their poultry operations. Leveraging IoT sensors, real-time data analytics, and AI-powered insights, FlockSense enables seamless control over environmental factors, feed efficiency, bio-security, and flock health, resulting in higher productivity, improved animal welfare, and greater sustainability. With intuitive dashboards, mobile access, and robust security, FlockSense delivers a scalable, data-driven solution trusted by leading poultry farms worldwide.">
    <meta name="keywords" content="Smart farm management, poultry farm automation, IoT poultry farming, poultry monitoring system, real-time farm analytics, AI poultry management, poultry health monitoring, feed efficiency solutions, biosecurity poultry, smart poultry sensors, modern poultry farming, digital poultry solutions, flock management platform, poultry farm control system, smart poultry environment, poultry data analytics, automated poultry operations, remote poultry monitoring, sustainable poultry farming, advanced poultry technology, smart chicken farm, poultry performance optimization, cloud-based poultry management, AI farm insights, smart livestock solutions">
    <meta name="author" content="Flock Sense">
    <meta name="robots" content="index, follow">
    <meta name="google-site-verification" content="78jyRInmxCC83985nwpZ5qsVphiGHZqCWLYyoH_cnzA" />

    <title>FlockSense - @yield('title')</title>
    <link href="{{ asset('assets/img/favicon.png') }}" type="image/x-icon" rel="shortcut icon">
    <link href="{{ asset('assets/css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/plugins/fontawesome/css/fontawesome.min.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/plugins/fontawesome/css/all.min.css') }}" rel="stylesheet">

    @stack('css')
</head>
<body>
<div class="navbar sticky-top-nav">
    <div class="container py-3">
        <header class="w-100 d-flex justify-content-between align-items-center">
            <div class="logo">
                <img src="{{ asset(settings('company.logo', 'assets/img/logo.png')) }}" alt="Logo">
            </div>
            <div class="nav">
                <a href="/" class="pt-1 me-3 fw-semibold text-decoration-none text-dark">Home</a>
                <a href="#models" class="pt-1 me-3 fw-semibold text-decoration-none text-dark">Models</a>
                <a href="#features" class="pt-1 me-3 fw-semibold text-decoration-none text-dark">Features</a>
                <a href="#services" class="pt-1 me-3 fw-semibold text-decoration-none text-dark">Services</a>
                <a href="#contact" class="pt-1 me-3 fw-semibold text-decoration-none text-dark">Contact Us</a>
                <a href="/login" class="btn btn-success">
                    <i class="bi bi-unlock me-2"></i>Login
                </a>
            </div>
        </header>
    </div>
</div>

