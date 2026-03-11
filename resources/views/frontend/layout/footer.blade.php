<!-- Back to Top Button -->
<button type="button" class="back-to-top" id="backToTop" aria-label="Back to top">
    <span class="visually-hidden">Back to top</span>
    <span class="back-to-top__icon"><i class="bi bi-arrow-up"></i></span>
</button>

<!-- Footer -->
<footer class="footer">
    <div class="container">
        <div class="row g-5">
            <div class="col-lg-3">
                <img src="{{ asset(settings('company.logo', 'assets/img/logo.png')) }}" alt="FlockSense Logo" class="footer-logo mb-4">
                <p class="small">{{ settings('company.slogan') }}</p>
                <div class="d-flex gap-3 mt-3">
                    @foreach(settings_group('social') as $row)
                        <a href="{{ $row['id'] }}" class="text-secondary fs-5">
                            <i class="text-white fa-brands {{ $row['icon'] }}"></i>
                        </a>
                    @endforeach
                </div>
            </div>
            <div class="col-lg-2">
                <h6 class="fw-bold mb-3">Quick Links</h6>
                <ul class="list-unstyled">
                    <li><a href="{{ route('features') }}" class="footer-link">Features</a></li>
                    <li><a href="{{ route('partners') }}" class="footer-link">Partners</a></li>
                    <li><a href="{{ route('blogs') }}" class="footer-link">Blogs</a></li>
                    <li><a href="{{ route('events') }}" class="footer-link">Events</a></li>
                    <li><a href="{{ route('pricing') }}" class="footer-link">Pricing</a></li>
                    <li><a href="{{ route('about') }}" class="footer-link">About Us</a></li>
                </ul>
            </div>
            <div class="col-lg-4">
                <h6 class="fw-bold mb-3">Contact</h6>
                <ul class="list-unstyled small mb-0">
                    <li class="mb-2"><i class="text-white fa-solid fa-envelope me-2"></i>{{ settings('contact.email') }}</li>
                    <li class="mb-2"><i class="text-white fa-solid fa-phone me-2"></i>{{ settings('contact.phone') }}</li>
                    <li class="mb-2">
                        <i class="text-white fa-solid fa-location-dot me-2"></i>{{ settings('contact.address1') }}
                    </li>
                    <li>
                        <i class="text-white fa-solid fa-location-dot me-2"></i>{{ settings('contact.address2') }}
                    </li>
                </ul>
            </div>
            <div class="col-lg-3">
                <h6 class="fw-bold mb-3">Newsletter</h6>
                <p class="small">Stay updated with our latest features and releases.</p>
                <form class="d-flex flex-column" id="newsletterForm" action="{{ route('newsletter.subscribe') }}" method="POST" onsubmit="return false;">
                    @csrf
                    <div class="mb-2">
                        <input type="email" id="newsletterEmail" name="email" class="form-control" placeholder="Enter your email" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Subscribe</button>
                </form>
            </div>
        </div>
        <hr class="border-secondary mt-5">
        <p class="small text-center mb-0">&copy; 2025 {{ settings('company.name') }}. All rights reserved.</p>
    </div>
</footer>

<!-- Newsletter Response Modal -->
<div class="modal fade" id="newsletterModal" tabindex="-1" aria-labelledby="newsletterModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="newsletterModalLabel">Newsletter Subscription</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="newsletterModalBody">
                <!-- Response message will be inserted here -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="ti ti-x me-1"></i>Close
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Bootstrap Bundle JS -->
<script src="{{ asset('assets/js/bootstrap.bundle.min.js') }}"></script>
<!-- Leaflet JS -->
<script src="{{ asset('assets/js/leaflet.js') }}"></script>
<!-- Custom JS -->
<script src="{{ asset('assets/js/landing.js') }}"></script>

<script>
    document.getElementById('newsletterForm').addEventListener('submit', function(e) {
        e.preventDefault();

        const form = this;
        const formData = new FormData(form);
        const emailInput = document.getElementById('newsletterEmail');

        fetch(form.action, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            const modalBody = document.getElementById('newsletterModalBody');
            const modalLabel = document.getElementById('newsletterModalLabel');

            modalBody.innerHTML = data.message;

            if (data.type === 'success') {
                modalLabel.textContent = 'Success';
            } else if (data.type === 'warning') {
                modalLabel.textContent = 'Warning';
            } else {
                modalLabel.textContent = 'Newsletter Subscription';
            }

            const modal = new bootstrap.Modal(document.getElementById('newsletterModal'));
            modal.show();

            emailInput.value = '';
        })
        .catch(error => {
            const modalBody = document.getElementById('newsletterModalBody');
            const modalLabel = document.getElementById('newsletterModalLabel');

            modalLabel.textContent = 'Error';
            modalBody.innerHTML = 'An error occurred. Please try again later.';

            const modal = new bootstrap.Modal(document.getElementById('newsletterModal'));
            modal.show();
        });
    });
</script>

@stack('js')

</body>
</html>
