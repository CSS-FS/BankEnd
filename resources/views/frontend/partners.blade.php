@extends('frontend.layout.frontend')

@section('content')

    <!-- Partners Hero -->
    <header class="hero-section auth-hero register-hero d-flex align-items-center">
        <div class="container">
            <div class="row align-items-center g-5">
                <div class="col-lg-7">
                    <h1 class="display-4 fw-bold text-white mb-3">Our Global Partners</h1>
                    <p class="lead text-white-50 mb-4">We collaborate with silicon vendors, channel distributors,
                        development partners and international programs to accelerate Smart Poultry Farming. Together we
                        deliver resilient IoT hardware, scalable data platforms and evidence-based outcomes.</p>
                    <div class="d-flex flex-wrap gap-2">
                        <span class="badge bg-warning text-dark">Edge IoT</span>
                        <span class="badge bg-warning text-dark">LoRaWAN & NB-IoT</span>
                        <span class="badge bg-warning text-dark">AI-driven Insights</span>
                        <span class="badge bg-warning text-dark">Sustainability</span>
                    </div>
                </div>
                <div class="col-lg-5">
                    <div class="auth-card text-white">
                        <h5 class="mb-3">Impact at a Glance</h5>
                        <div class="row g-3">
                            <div class="col-6">
                                <div class="auth-highlight-card h-100">
                                    <div class="auth-highlight-card__icon">
                                        <i class="fa-solid fa-microchip"></i>
                                    </div>
                                    <p class="mb-1 fw-semibold">Device MTBF</p>
                                    <h4 class="mb-0">78,000 hrs</h4>
                                    <span class="small text-white-50">Microchip-enabled nodes</span>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="auth-highlight-card h-100">
                                    <div class="auth-highlight-card__icon">
                                        <i class="fa-solid fa-users"></i>
                                    </div>
                                    <p class="mb-1 fw-semibold">Growers Training</p>
                                    <h4 class="mb-0">30+</h4>
                                    <span class="small text-white-50">Capacity Building</span>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="auth-highlight-card h-100">
                                    <div class="auth-highlight-card__icon">
                                        <i class="fa-solid fa-brain"></i>
                                    </div>
                                    <p class="mb-1 fw-semibold">Event Detection</p>
                                    <h4 class="mb-0">92%</h4>
                                    <span class="small text-white-50">Welfare analytics</span>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="auth-highlight-card h-100">
                                    <div class="auth-highlight-card__icon">
                                        <i class="fa-solid fa-user-check"></i>
                                    </div>
                                    <p class="mb-1 fw-semibold">Active usage</p>
                                    <h4 class="mb-0">78–92%</h4>
                                    <span class="small text-white-50">Adoption & Training</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Logo strip -->
        <div class="hero-partners">
            <div class="hero-partners__wrapper">
                <div class="hero-partners__logos container">
                    @foreach($partners as $partner)
                        <a class="hero-partners__link" href="{{ $partner->url }}" target="_blank" rel="nofollow noopener">
                            <img src="{{ $partner->media->first()->url ?? 'assets/img/partners/avnet-light.svg' }}" alt="{{ $partner->company_name }}" class="hero-partners__logo">
                        </a>
                    @endforeach
                </div>
            </div>
        </div>
    </header>

    <!-- What We Build Together -->
    <section class="section-light py-5">
        <div class="container">
            <div class="row mb-4">
                <div class="col-12 text-center text-dark">
                    <h2 class="fw-bold mb-2">What We Build Together</h2>
                    <p class="mb-0">A full‑stack approach to connected poultry: rugged IoT hardware, secure device identity, reliable backhaul and analytics mapped to industry KPIs (FCR, PEF, CV, livability).</p>
                </div>
            </div>
            <div class="row g-4">
                <div class="col-md-6 col-lg-3">
                    <div class="trusted-metric-card h-100">
                        <div class="trusted-metric-card__icon mb-3"><i class="fa-solid fa-temperature-three-quarters"></i></div>
                        <h6 class="fw-semibold">Environmental Telemetry</h6>
                        <p class="small mb-2">Temp, RH, CO₂, NH₃, static pressure, airspeed, light.</p>
                        <p class="small mb-0 text-muted">Sampling 1–30s; validated sensor drift compensation.</p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3">
                    <div class="trusted-metric-card h-100">
                        <div class="trusted-metric-card__icon mb-3"><i class="fa-solid fa-diagram-project"></i></div>
                        <h6 class="fw-semibold">Controllers & Actuation</h6>
                        <p class="small mb-2">Ventilation, heaters, cooling pads, feeders, water lines.</p>
                        <p class="small mb-0 text-muted">Edge safety interlocks; OTA rules and schedules.</p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3">
                    <div class="trusted-metric-card h-100">
                        <div class="trusted-metric-card__icon mb-3"><i class="fa-solid fa-cloud"></i></div>
                        <h6 class="fw-semibold">Data Platform</h6>
                        <p class="small mb-2">Immutable logs, KPI benchmarking, alerting and APIs.</p>
                        <p class="small mb-0 text-muted">Device PKI, multi‑tenant RBAC, audit trails.</p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3">
                    <div class="trusted-metric-card h-100">
                        <div class="trusted-metric-card__icon mb-3"><i class="fa-solid fa-seedling"></i></div>
                        <h6 class="fw-semibold">Sustainability</h6>
                        <p class="small mb-2">Energy & water intensity tracking, GHG baselines.</p>
                        <p class="small mb-0 text-muted">Supports SDG reporting and audit readiness.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

@endsection

@push('js')
    <script>
        (function () {
            if (typeof L === 'undefined') return;
            const mapEl = document.getElementById('officeMap');
            if (!mapEl) return;

            const map = L.map(mapEl, {
                zoomControl: false,
                scrollWheelZoom: false
            }).setView([40.0, 20.0], 2);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; OpenStreetMap contributors'
            }).addTo(map);

            const locations = [
                { name: 'Norwich, UK', coords: [52.6309, 1.2974], addr: '23 Roundtree Cl, NR7 8SX' },
                { name: 'Islamabad, PK', coords: [33.6844, 73.0479], addr: '300 Street-17, G-15/2' }
            ];

            const markerIcon = L.icon({
                iconUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon.png',
                iconRetinaUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon-2x.png',
                iconSize: [25, 41],
                iconAnchor: [12, 41],
                popupAnchor: [0, -32],
                shadowUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-shadow.png',
                shadowSize: [41, 41],
                shadowAnchor: [14, 41]
            });

            const bounds = [];
            locations.forEach(loc => {
                L.marker(loc.coords, { icon: markerIcon })
                    .addTo(map)
                    .bindPopup(`<strong>${loc.name}</strong><br>${loc.addr}`);
                bounds.push(loc.coords);
            });

            if (bounds.length > 0) {
                map.fitBounds(bounds, { padding: [20, 20] });
            }

            L.control.zoom({ position: 'bottomright' }).addTo(map);
        })();
    </script>
@endpush
