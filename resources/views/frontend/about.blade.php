@extends('frontend.layout.frontend')

@section('content')

    <!-- About Hero -->
    <header class="hero-section auth-hero d-flex align-items-center">
        <div class="container">
            <div class="row align-items-center g-5">
                <div class="col-lg-7">
                    <h1 class="display-4 fw-bold text-white mb-3">About FlockSense</h1>
                    <p class="lead text-white-50 mb-4">We are on a mission to modernize poultry farming through real-time data, actionable insights, and simple, reliable automation.</p>
                    <ul class="list-unstyled text-white-50 mb-0">
                        <li class="mb-2"><i class="bi bi-check-circle-fill text-primary me-2"></i>IoT-driven monitoring and control</li>
                        <li class="mb-2"><i class="bi bi-check-circle-fill text-primary me-2"></i>Benchmarking, alerts, and compliance-by-design</li>
                        <li class="mb-2"><i class="bi bi-check-circle-fill text-primary me-2"></i>Built with growers, for growers</li>
                    </ul>
                </div>
                <div class="col-lg-5">
                    <div class="auth-feature-panel bg-white p-4 rounded-4">
                        <h5 class="fw-bold text-dark mb-2">What we value</h5>
                        <p class="text-muted mb-2">Reliable hardware, intuitive software, and customer-first support. We help teams deliver better animal welfare and performance.</p>
                        <p class="text-muted mb-0">From small farms to integrated operations, FlockSense scales with you.</p>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- Offices & Map -->
    <section class="section-light py-5">
        <div class="container">
            <div class="row g-4 align-items-start">
                <div class="col-lg-5">
                    <h2 class="fw-bold text-dark mb-3">Our Offices</h2>
                    <p class="text-muted">Reach out or visit one of our locations. We’d love to connect.</p>
                    <div class="mb-4">
                        <h6 class="fw-semibold mb-1">United Kingdom</h6>
                        <p class="small mb-2">{{ settings('contact.address1') }}</p>
                        <p class="small mb-0"><i class="fa-solid fa-envelope me-2"></i>{{ settings('contact.email') }}</p>
                    </div>
                    <div class="mb-4">
                        <h6 class="fw-semibold mb-1">Pakistan</h6>
                        <p class="small mb-2">{{ settings('contact.address2') }}</p>
                        <p class="small mb-0"><i class="fa-solid fa-phone me-2"></i>{{ settings('contact.phone') }}</p>
                    </div>
                </div>
                <div class="col-lg-7">
                    <div id="officeMap" class="trusted-map"></div>
                    <p class="small text-muted text-center mt-2 mb-0">OpenStreetMap showing our office locations</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Contact Form -->
    <section class="section-dark py-5">
        <div class="container" id="contact">
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <h2 class="fw-bold mb-3">Contact Us</h2>
                    <p class="mb-4">Send us a message and our team will get back to you shortly.</p>
                    <form action="mailto:{{ settings('contact.email') }}" method="post" enctype="text/plain">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="name" class="form-label">Full Name</label>
                                <input type="text" class="form-control" id="name" name="name" placeholder="Your name" required>
                            </div>
                            <div class="col-md-6">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email" placeholder="you@example.com" required>
                            </div>
                            <div class="col-12">
                                <label for="subject" class="form-label">Subject</label>
                                <input type="text" class="form-control" id="subject" name="subject" placeholder="How can we help?" required>
                            </div>
                            <div class="col-12">
                                <label for="message" class="form-label">Message</label>
                                <textarea class="form-control" id="message" name="message" rows="5" placeholder="Write your message..." required></textarea>
                            </div>
                            <div class="col-12">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" value="accepted" id="consent" required>
                                    <label class="form-check-label" for="consent">
                                        I agree to the
                                        <a href="{{ route('privacy') }}" class="link-warning">
                                            Terms & Privacy Policy
                                        </a>
                                    </label>
                                </div>
                            </div>
                            <div class="col-12 d-grid">
                                <button type="submit" class="btn btn-primary">Send Message</button>
                            </div>
                        </div>
                    </form>
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
