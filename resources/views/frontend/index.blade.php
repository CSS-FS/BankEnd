@extends('frontend.layout.frontend')

@section('content')

    <!-- Hero Section -->
    <header class="hero-section homepage-top d-flex align-items-center">
        @php
            $videos = settings_group('video');
            $selectedVideo = null;
            foreach ($videos as $row) {
                if (is_array($row) && !empty($row['selected'])) {
                    $selectedVideo = $row;
                    break;
                }
            }

            if (!$selectedVideo && !empty($videos)) {
                $firstVideo = reset($videos);
                $selectedVideo = is_array($firstVideo) ? $firstVideo : null;
            }
        @endphp
        <div class="homepage-top__video" aria-hidden="true">
            <video id="homepage-video-bg" class="homepage-top__video-player" preload="auto" autoplay muted loop
                         playsinline webkit-playsinline="true" poster="{{ $selectedVideo ? asset($selectedVideo['cover']) : '' }}">
                     @if($selectedVideo)
                          <source src="{{ asset($selectedVideo['video']) }}" type="video/mp4">
                     @endif
                     Sorry, your browser doesn't support HTML5 video.
            </video>
            <div class="homepage-top__bg"></div>
        </div>
        <div class="container position-relative">
            <div id="heroCarousel" class="carousel slide" data-bs-ride="carousel" data-bs-interval="6000" data-bs-pause="false">
                <div class="carousel-inner">
                    <div class="carousel-item active">
                        <div class="row justify-content-center text-center">
                            <div class="col-lg-8">
                                <h1 class="display-2 fw-bold hero-heading mb-3">
                                    <span class="text-primary">SMART</span> Farm Management
                                </h1>
                                <p class="lead hero-subtitle mb-4">Remotely monitor and control complete poultry farm operations</p>
                                <div class="d-flex justify-content-center flex-wrap">
                                    <a href="{{ asset('assets/app/demo.apk') }}" class="btn btn-primary me-3 mb-2">
                                        <i class="bi bi-phone-vibrate me-2"></i>Mobile Demo
                                    </a>
                                    <a href="{{ settings('company.demo') }}" class="btn btn-outline-light mb-2" target="_blank">
                                        <i class="bi bi-window me-2"></i>Web Demo
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="carousel-item">
                        <div class="row justify-content-center text-center">
                            <div class="col-lg-8">
                                <h1 class="display-2 fw-bold hero-heading mb-3">
                                    <span class="text-primary">Real-time</span> Monitoring & Alerts
                                </h1>

                                <p class="lead hero-subtitle mb-4">Get instant insights on climate, feeding and welfare across every shed.</p>

                                <div class="d-flex justify-content-center flex-wrap">
                                    <a href="{{ route('features') }}" class="btn btn-primary me-3 mb-2">
                                        <i class="bi bi-activity me-2"></i>Explore Features
                                    </a>
                                    <a href="{{ route('pricing') }}" class="btn btn-outline-light mb-2">
                                        <i class="bi bi-currency-dollar me-2"></i>View Pricing
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="carousel-item">
                        <div class="row justify-content-center text-center">
                            <div class="col-lg-8">
                                <h1 class="display-2 fw-bold hero-heading mb-3">
                                    <span class="text-primary">Automate</span>, Benchmark, Improve
                                </h1>
                                <p class="lead hero-subtitle mb-4">Automate controllers, benchmark KPIs, and drive better flock performance.</p>
                                <div class="d-flex justify-content-center flex-wrap">
                                    <a href="{{ settings('company.demo') }}" class="btn btn-primary me-3 mb-2">
                                        <i class="bi bi-person-plus me-2"></i>Start Demo
                                    </a>
                                    <a href="{{ route('events') }}" class="btn btn-outline-light mb-2">
                                        <i class="bi bi-book me-2"></i>See Events
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <button class="carousel-control-prev hero-carousel-control hero-carousel-control--prev" type="button" data-bs-target="#heroCarousel" data-bs-slide="prev" aria-label="Previous slide">
                    <span class="hero-carousel-control__icon" aria-hidden="true"><i class="bi bi-chevron-left"></i></span>
                    <span class="visually-hidden">Previous</span>
                </button>
                <button class="carousel-control-next hero-carousel-control hero-carousel-control--next" type="button" data-bs-target="#heroCarousel" data-bs-slide="next" aria-label="Next slide">
                    <span class="hero-carousel-control__icon" aria-hidden="true"><i class="bi bi-chevron-right"></i></span>
                    <span class="visually-hidden">Next</span>
                </button>
            </div>
        </div>
    </header>

    <!-- Platform pillars (industry + framework) -->
    <section class="section-dark py-5">
        <div class="container text-center">
            <h2 class="display-5 fw-bold mb-3">A Full-Stack Poultry Platform</h2>
            <p class="mb-5">Guided by industry leaders and our FlockSense framework.</p>
            <div class="row g-4">
                <div class="col-6 col-lg-3">
                    <div class="feature-card h-100 p-4 text-start text-lg-center">
                        <div class="mb-2 text-primary fs-2"><i class="bi bi-thermometer"></i></div>
                        <h6 class="fw-bold mb-1">Sensing & Devices</h6>
                        <p class="small text-muted mb-0">Env, feed & water lines</p>
                    </div>
                </div>
                <div class="col-6 col-lg-3">
                    <div class="feature-card h-100 p-4 text-start text-lg-center">
                        <div class="mb-2 text-primary fs-2"><i class="bi bi-wifi"></i></div>
                        <h6 class="fw-bold mb-1">Connectivity</h6>
                        <p class="small text-muted mb-0">LoRaWAN, Wi‑Fi, NB‑IoT</p>
                    </div>
                </div>
                <div class="col-6 col-lg-3">
                    <div class="feature-card h-100 p-4 text-start text-lg-center">
                        <div class="mb-2 text-primary fs-2"><i class="bi bi-cloud"></i></div>
                        <h6 class="fw-bold mb-1">Data Platform</h6>
                        <p class="small text-muted mb-0">APIs, RBAC, audit logs</p>
                    </div>
                </div>
                <div class="col-6 col-lg-3">
                    <div class="feature-card h-100 p-4 text-start text-lg-center">
                        <div class="mb-2 text-primary fs-2"><i class="bi bi-cpu"></i></div>
                        <h6 class="fw-bold mb-1">Intelligence & Control</h6>
                        <p class="small text-muted mb-0">Predict, simulate, automate</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Unified platform pillars -->
    <section class="section-light py-5">
        <div class="container text-center">
            <h2 class="display-5 fw-bold mb-3">A Unified Platform Across the Poultry Lifecycle</h2>
            <p class="text-muted mb-5">Hardware, data and workflows combined to improve livability, feed conversion and
                compliance.</p>
            <div class="row g-4">
                <div class="col-6 col-lg-3">
                    <div class="feature-card h-100 p-4 text-start text-lg-center">
                        <div class="mb-3 text-primary fs-2"><i class="bi bi-activity"></i></div>
                        <h5 class="fw-semibold">Live Monitoring</h5>
                        <p class="small text-muted mb-3">Telemetry for climate, feed, power and welfare, consolidated in
                            real time.</p>
                        <span class="badge bg-success-subtle text-dark"><i class="fa-solid fa-signal me-1"></i>LoRaWAN |
                            Wi‑Fi</span>
                    </div>
                </div>
                <div class="col-6 col-lg-3">
                    <div class="feature-card h-100 p-4 text-start text-lg-center">
                        <div class="mb-3 text-primary fs-2"><i class="bi bi-cpu"></i></div>
                        <h5 class="fw-semibold">Edge Automation</h5>
                        <p class="small text-muted mb-3">Smart controllers orchestrate ventilation, heating and lighting
                            with safe fallbacks.</p>
                        <span class="badge bg-success-subtle text-dark"><i class="fa-solid fa-bolt me-1"></i>Rules
                            Engine</span>
                    </div>
                </div>
                <div class="col-6 col-lg-3">
                    <div class="feature-card h-100 p-4 text-start text-lg-center">
                        <div class="mb-3 text-primary fs-2"><i class="bi bi-graph-up-arrow"></i></div>
                        <h5 class="fw-semibold">Analytics & AI</h5>
                        <p class="small text-muted mb-3">Predict outcomes, benchmark farms and run what-if simulations
                            with digital twins.</p>
                        <span class="badge bg-success-subtle text-dark"><i class="fa-solid fa-brain me-1"></i>Machine
                            Learning</span>
                    </div>
                </div>
                <div class="col-6 col-lg-3">
                    <div class="feature-card h-100 p-4 text-start text-lg-center">
                        <div class="mb-3 text-primary fs-2"><i class="bi bi-patch-check"></i></div>
                        <h5 class="fw-semibold">Compliance Ready</h5>
                        <p class="small text-muted mb-3">Audit trails, SOPs and SDG-aligned sustainability reporting
                            built-in.</p>
                        <span class="badge bg-success-subtle text-dark"><i
                                class="fa-solid fa-shield-halved me-1"></i>Immutable Logs</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Command center -->
    <section class="section-dark py-5">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6 mb-4 mb-lg-0 order-2 order-lg-1">
                    <h3 class="display-5 fw-bold mb-3">Command Center for Every Shed</h3>
                    <p class="lead mb-3">Alerting, dashboards and mobile workflows keep supervisors and growers aligned
                        24/7.</p>
                    <ul class="list-unstyled">
                        <li class="mb-2"><i class="fa-solid fa-circle-check me-2 text-secondary"></i>Role-based access
                            across mobile, tablet and web</li>
                        <li class="mb-2"><i class="fa-solid fa-circle-check me-2 text-secondary"></i>Critical alerts for
                            climate, feed, power and mortality</li>
                        <li class="mb-2"><i class="fa-solid fa-circle-check me-2 text-secondary"></i>Digital SOPs and
                            task assignments</li>
                        <li class="mb-2"><i class="fa-solid fa-circle-check me-2 text-secondary"></i>Offline-first
                            mobile apps for field teams</li>
                    </ul>
                </div>
                <div class="col-lg-6 order-1 order-lg-2 text-center">
                    <img src="assets/img/remote_monitoring.png" alt="Remote monitoring dashboards" class="img-fluid">
                </div>
            </div>
        </div>
    </section>

    <!-- Upcoming Events -->
    <section id="events" class="section-light py-5">
        <div class="container">
            <div class="row mb-4">
                <div class="col-12 text-center">
                    <h2 class="fw-bold text-dark mb-2">Upcoming Events</h2>
                    <p class="text-muted mb-0">Webinars, demos and industry meetups relevant to modern poultry operations.</p>
                </div>
            </div>
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="feature-card h-100 p-4">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="badge bg-warning text-dark">Webinar</span>
                            <span class="small text-muted"><i class="bi bi-calendar-event me-1"></i>Apr 24</span>
                        </div>
                        <h6 class="fw-semibold mb-1">Optimizing Ventilation & Welfare with IoT</h6>
                        <p class="small text-muted">A practical walkthrough of sensors, alarms and playbooks.</p>
                        <a href="#" class="btn btn-primary btn-sm">Register</a>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="feature-card h-100 p-4">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="badge bg-warning text-dark">Live Demo</span>
                            <span class="small text-muted"><i class="bi bi-calendar-event me-1"></i>May 08</span>
                        </div>
                        <h6 class="fw-semibold mb-1">Farm Command Center: Interactive Tour</h6>
                        <p class="small text-muted">Dashboards, alerts and team workflows across sheds.</p>
                        <a href="#" class="btn btn-primary btn-sm">Reserve Seat</a>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="feature-card h-100 p-4">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="badge bg-warning text-dark">Conference</span>
                            <span class="small text-muted"><i class="bi bi-calendar-event me-1"></i>Jun 12</span>
                        </div>
                        <h6 class="fw-semibold mb-1">Data‑Driven Poultry Summit</h6>
                        <p class="small text-muted">Benchmarks, case studies and sustainability reporting.</p>
                        <a href="#" class="btn btn-primary btn-sm">Get Details</a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Blogs -->
    <section id="blogs" class="section-dark py-5">
        <div class="container">
            <div class="row mb-4">
                <div class="col-12 text-center">
                    <h2 class="fw-bold mb-2">Latest From Our Blog</h2>
                    <p class="text-white-50 mb-0">Best practices, implementation tips and industry insights.</p>
                </div>
            </div>
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="pricing-hero-card h-100">
                        <h5 class="fw-semibold mb-2">Designing Resilient IoT for Poultry Sheds</h5>
                        <p class="mb-3">Connectivity choices (LoRaWAN vs Wi‑Fi vs NB‑IoT), store‑and‑forward, and safety interlocks.</p>
                        <a href="#" class="btn btn-outline-light btn-sm">Read more</a>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="pricing-hero-card h-100">
                        <h5 class="fw-semibold mb-2">From Telemetry to Actionable Insights</h5>
                        <p class="mb-3">How anomaly detection and digital twins improve welfare and feed efficiency.</p>
                        <a href="#" class="btn btn-outline-light btn-sm">Read more</a>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="pricing-hero-card h-100">
                        <h5 class="fw-semibold mb-2">Audit‑Ready Compliance in Poultry</h5>
                        <p class="mb-3">Immutable logs, signatures and SOP acknowledgements for certifications.</p>
                        <a href="#" class="btn btn-outline-light btn-sm">Read more</a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Trusted Section -->
    <section id="trusted" class="trusted-section py-5">
        <div class="container">
            <div class="row g-5 align-items-center">
                <div class="col-lg-5">
                    <h3 class="display-5 fw-bold mb-3">Trusted by Leading Poultry Producers</h3>
                    <p class="lead mb-4 text-muted">Replace your existing controller completely free. No hidden charges.
                        Advanced smart controllers for additional features.</p>
                    <div class="row g-3 trusted-metrics">
                        <div class="col-6">
                            <div class="trusted-metric-card d-flex align-items-center justify-content-between">
                                <div>
                                    <h4 class="fw-bold text-dark mb-1">500+</h4>
                                    <p class="mb-0">Farms</p>
                                </div>
                                <span class="trusted-metric-card__icon text-primary"><i
                                        class="fa-solid fa-warehouse"></i></span>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="trusted-metric-card d-flex align-items-center justify-content-between">
                                <div>
                                    <h4 class="fw-bold text-dark mb-1">1M+</h4>
                                    <p class="mb-0">Birds Monitored</p>
                                </div>
                                <span class="trusted-metric-card__icon text-success"><i
                                        class="fa-solid fa-dove"></i></span>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="trusted-metric-card d-flex align-items-center justify-content-between">
                                <div>
                                    <h4 class="fw-bold text-dark mb-1">99%</h4>
                                    <p class="mb-0">Accuracy</p>
                                </div>
                                <span class="trusted-metric-card__icon text-info"><i
                                        class="fa-solid fa-bullseye"></i></span>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="trusted-metric-card d-flex align-items-center justify-content-between">
                                <div>
                                    <h4 class="fw-bold text-dark mb-1">24/7</h4>
                                    <p class="mb-0">Support</p>
                                </div>
                                <span class="trusted-metric-card__icon text-warning"><i
                                        class="fa-solid fa-headset"></i></span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-7">
                    <div id="trustedMap" class="trusted-map mb-3"></div>
                </div>
            </div>
        </div>
    </section>

@endsection
