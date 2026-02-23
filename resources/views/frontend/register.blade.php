@extends('frontend.layout.frontend')

@section('content')

    <!-- Register hero -->
    <header class="hero-section auth-hero register-hero d-flex align-items-center">
        <div class="container">
            <div class="row align-items-center g-5">
                <div class="col-lg-6 order-2 order-lg-1">
                    <h1 class="display-4 fw-bold text-white mb-3">Create your FlockSense account</h1>
                    <p class="lead text-white-50 mb-4">
                        Bring every flock, facility and team member together. Start with
                        a basic plan, no credit card required.
                    </p>
                    <ul class="auth-benefits list-unstyled text-white-50 mb-0">
                        <li class="mb-3">
                            <i class="bi bi-check-circle-fill text-primary me-2"></i>
                            Unlimited collaborators with role-based access
                        </li>
                        <li class="mb-3">
                            <i class="bi bi-check-circle-fill text-primary me-2"></i>
                            Automated alerts for mortality, feed and climate
                        </li>
                        <li class="mb-3">
                            <i class="bi bi-check-circle-fill text-primary me-2"></i>
                            One-page ROI model tailored to your flock size and costs
                        </li>
                        <li>
                            <i class="bi bi-check-circle-fill text-primary me-2"></i>
                            Live sustainability dashboard for auditors and integrators
                        </li>
                    </ul>
                </div>

                <div class="col-lg-5 offset-lg-1 order-1 order-lg-2">
                    <div class="auth-card shadow-lg">
                        <h4 class="fw-bold mb-3 text-white">Create Your Account</h4>
                        <p class="text-white-50 mb-4">Tell us about your farm to personalise recommendations.</p>
                        <form class="row g-3" action="{{ route('client.register') }}" method="POST">
                            @csrf
                            <div class="col-md-12">
                                <label class="form-label text-white-50" for="registerName">Full Name</label>
                                <input type="text" id="registerName" name="name"
                                       class="form-control @error('name') is-invalid @enderror"
                                       placeholder="Chaudhari Shujaat" value="{{ old('name') }}" required>
                                @error('name')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label text-white-50" for="registerPhone">Phone</label>
                                <input type="tel" id="registerPhone" name="phone"
                                       class="form-control @error('phone') is-invalid @enderror"
                                       placeholder="03001234567" value="{{ old('phone') }}" required>
                                @error('phone')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label text-white-50" for="registerEmail">Email</label>
                                <input type="email" id="registerEmail" name="email"
                                       class="form-control @error('email') is-invalid @enderror"
                                       placeholder="you@farm.com" value="{{ old('email') }}" required>
                                @error('email')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label text-white-50" for="registerPassword">Password</label>
                                <div class="input-group">
                                    <input type="password" id="registerPassword" name="password"
                                           class="form-control @error('password') is-invalid @enderror"
                                           placeholder="********"
                                           required>
                                    <button type="button" class="btn btn-outline-secondary text-white-50 toggle-password" data-target="registerPassword" tabindex="-1">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label text-white-50" for="registerConfirm">Confirm Password</label>
                                <div class="input-group">
                                    <input type="password" id="registerConfirm" name="password_confirmation"
                                           class="form-control"
                                           placeholder="********" required>
                                    <button type="button" class="btn btn-outline-secondary text-white-50 toggle-password" data-target="registerConfirm" tabindex="-1">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="progress" role="progressbar" aria-label="Password strength" aria-valuemin="0" aria-valuemax="100">
                                    <div id="strengthBar" class="progress-bar bg-danger" style="width: 0%"></div>
                                </div>
                                <p id="strengthText" class="small text-white-50 mt-1 mb-0">Strength: too weak</p>
                                @error('password')
                                <div class="invalid-feedback d-block">
                                    {{ $message }}
                                </div>
                                @enderror
                            </div>
                            <div class="col-12">
                                <label class="form-label text-white-50" for="registerRole">Role</label>
                                <select id="registerRole" name="role" class="form-select @error('role') is-invalid @enderror" required>
                                    <option value="" selected disabled>Select your role</option>
                                    <option value="owner" {{ old('role') == 'owner' ? 'selected' : '' }}>Farm Owner</option>
                                    <option value="manager" {{ old('role') == 'manager' ? 'selected' : '' }}>Farm Manager</option>
                                </select>
                                @error('role')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                                @enderror
                            </div>
                            <div class="col-12">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" value="" id="terms" required>
                                    <label class="form-check-label text-white-50" for="terms">
                                        I agree to the
                                        <a href="{{ route('privacy') }}" class="link-warning">
                                            Terms & Privacy Policy
                                        </a>
                                    </label>
                                </div>
                            </div>
                            <div class="col-12 d-grid">
                                <button type="submit" class="btn btn-primary">Create Account</button>
                            </div>
                        </form>
                        <p class="text-white-50 small mt-3 mb-0">
                            Already using FlockSense?
                            <a href="/login" class="fw-semibold text-primary">Log in here</a>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <section class="section-light py-5">
        <div class="container">
            <div class="row g-4 align-items-center">
                <div class="col-lg-6">
                    <h2 class="fw-bold text-dark mb-3">Launch with confidence in 3 simple steps</h2>
                    <div class="timeline">
                        <div class="timeline-item">
                            <span class="timeline-dot bg-warning text-white">1</span>
                            <div>
                                <h6 class="fw-semibold mb-1">Share your farm profile</h6>
                                <p class="text-muted small mb-0">Tell us about sheds, hardware and current processes so
                                    we can tailor the rollout.</p>
                            </div>
                        </div>
                        <div class="timeline-item">
                            <span class="timeline-dot bg-warning text-white">2</span>
                            <div>
                                <h6 class="fw-semibold mb-1">Connect sensors and devices</h6>
                                <p class="text-muted small mb-0">Our specialists help connect controllers, climate
                                    stations and mobile users in under a week.</p>
                            </div>
                        </div>
                        <div class="timeline-item">
                            <span class="timeline-dot bg-warning text-white">3</span>
                            <div>
                                <h6 class="fw-semibold mb-1">Train your team</h6>
                                <p class="text-muted small mb-0">Role-based training ensures supervisors and growers
                                    adopt the platform from day one.</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="auth-feature-panel">
                        <h5 class="fw-bold text-dark mb-3">Included in every new account</h5>
                        <ul class="list-unstyled text-muted mb-0">
                            <li class="mb-2">
                                <i class="bi bi-check-circle-fill text-success me-2"></i>Basic plan include farm
                                monitoring
                            </li>
                            <li class="mb-2">
                                <i class="bi bi-check-circle-fill text-success me-2"></i>Preconfigured data retention,
                                role scopes, and immutable logs
                            </li>
                            <li class="mb-2">
                                <i class="bi bi-check-circle-fill text-success me-2"></i>IoT hardware
                                onboarding session
                            </li>
                            <li class="mb-2">
                                <i class="bi bi-check-circle-fill text-success me-2"></i>Executive dashboard preloaded
                                with industry KPIs (FCR, PEF, CV, livability)
                            </li>
                            <li class="mb-2">
                                <i class="bi bi-check-circle-fill text-success me-2"></i>Dedicated success
                                manager
                            </li>
                            <li>
                                <i class="bi bi-check-circle-fill text-success me-2"></i>Access to community playbooks
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </section>

@endsection

@push('js')
<script>
    (function () {
        const passwordInput = document.getElementById('registerPassword');
        const strengthBar = document.getElementById('strengthBar');
        const strengthText = document.getElementById('strengthText');

        function assess(pw) {
            const checks = {
                length: pw.length >= 8,
                upper: /[A-Z]/.test(pw),
                lower: /[a-z]/.test(pw),
                number: /\d/.test(pw),
                symbol: /[^A-Za-z0-9]/.test(pw),
            };
            let score = Object.values(checks).filter(Boolean).length;
            return score;
        }

        function update() {
            const pw = passwordInput.value;
            const score = assess(pw);

            let pct = (score / 5) * 100;
            strengthBar.style.width = pct + '%';
            strengthBar.classList.remove('bg-danger', 'bg-warning', 'bg-success');

            if (pct === 0) {
                 strengthBar.classList.add('bg-danger');
                 strengthText.textContent = 'Strength: too weak';
            } else if (pct < 40) {
                strengthBar.classList.add('bg-danger');
                strengthText.textContent = 'Strength: weak';
            } else if (pct < 80) {
                strengthBar.classList.add('bg-warning');
                strengthText.textContent = 'Strength: fair';
            } else {
                strengthBar.classList.add('bg-success');
                strengthText.textContent = 'Strength: strong';
            }
        }

        if(passwordInput) {
            passwordInput.addEventListener('input', update);
        }
    })();
</script>
<script>
    document.querySelectorAll('.toggle-password').forEach(function(button) {
        button.addEventListener('click', function() {
            const input = document.getElementById(this.getAttribute('data-target'));
            const icon = this.querySelector('i');
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.replace('bi-eye', 'bi-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.replace('bi-eye-slash', 'bi-eye');
            }
        });
    });
</script>
@endpush
