@extends('frontend.layout.frontend')

@section('content')

    <!-- Force Reset Password Hero -->
    <header class="hero-section auth-hero d-flex align-items-center">
        <div class="container">
            <div class="row align-items-center g-5">
                <div class="col-lg-6 order-2 order-lg-1">
                    <h1 class="display-4 fw-bold text-white mb-3">Update your password</h1>
                    <p class="lead text-white-50 mb-4">Your password has been reset by an administrator. Please create a new secure password to continue accessing your account.</p>
                    <div class="row g-3 auth-highlight">
                        <div class="col-sm-6">
                            <div class="auth-highlight-card">
                                <span class="auth-highlight-card__icon"><i class="bi bi-shield-exclamation"></i></span>
                                <h6 class="text-white mb-1">Admin Reset</h6>
                                <p class="text-white-50 mb-0">Your password was reset for security reasons.</p>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="auth-highlight-card">
                                <span class="auth-highlight-card__icon"><i class="bi bi-key-fill"></i></span>
                                <h6 class="text-white mb-1">Temporary Access</h6>
                                <p class="text-white-50 mb-0">Use your temporary password to authenticate.</p>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="auth-highlight-card">
                                <span class="auth-highlight-card__icon"><i class="bi bi-lock-fill"></i></span>
                                <h6 class="text-white mb-1">Strong Password</h6>
                                <p class="text-white-50 mb-0">Create a unique password with 8+ characters.</p>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="auth-highlight-card">
                                <span class="auth-highlight-card__icon"><i class="bi bi-check-circle-fill"></i></span>
                                <h6 class="text-white mb-1">Instant Access</h6>
                                <p class="text-white-50 mb-0">You'll be signed in after successful reset.</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="auth-card shadow-lg">
                        <h4 class="fw-bold mb-2 text-white">Force Password Reset</h4>
                        <p class="text-white-50 mb-4">Enter your current password and choose a new one.</p>

                        @if (session('status'))
                            <div class="alert alert-success mb-3">{{ session('status') }}</div>
                        @endif

                        @if ($errors->any())
                            <div class="alert alert-danger alert-dismissible fade show mb-3" role="alert">
                                <strong><i class="bi bi-exclamation-triangle me-2"></i>There were some errors:</strong>
                                <ul class="mb-0 mt-2">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        @endif

                        <form id="forceResetForm" class="row g-3" action="{{ route('force.reset', $user) }}" method="POST">
                            @csrf
                            @method('PUT')

                            <div class="col-12">
                                <label class="form-label text-white-50" for="currentPassword">Current password</label>
                                <div class="input-group">
                                    <input type="password" class="form-control" id="currentPassword" name="current_password"
                                           placeholder="Enter current password" autocomplete="current-password" required>
                                    <button class="btn btn-outline-light" type="button" id="toggleCurrent" aria-label="Show password">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                </div>
                            </div>

                            <div class="col-12">
                                <label class="form-label text-white-50" for="newPassword">New password</label>
                                <div class="input-group">
                                    <input type="password" class="form-control" id="newPassword" name="new_password"
                                           placeholder="Enter new password" autocomplete="new-password" required>
                                    <button class="btn btn-outline-light" type="button" id="toggleNew" aria-label="Show password">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                </div>
                            </div>

                            <div class="col-12">
                                <label class="form-label text-white-50" for="confirmPassword">Confirm new password</label>
                                <div class="input-group">
                                    <input type="password" class="form-control" id="confirmPassword" name="new_password_confirmation"
                                           placeholder="Confirm new password" autocomplete="new-password" required>
                                    <button class="btn btn-outline-light" type="button" id="toggleConfirm" aria-label="Show password">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                </div>
                            </div>

                            <div class="col-12">
                                <div class="progress" role="progressbar" aria-label="Password strength" aria-valuemin="0" aria-valuemax="100">
                                    <div id="strengthBar" class="progress-bar bg-danger" style="width: 0%"></div>
                                </div>
                                <p id="strengthText" class="small text-white-50 mt-1 mb-0">Strength: too weak</p>
                            </div>

                            <div class="col-12">
                                <ul class="small text-white-50 mb-0 list-unstyled" id="rules">
                                    <li id="rule-length"><i class="bi bi-dot me-1"></i>8+ characters</li>
                                    <li id="rule-upper"><i class="bi bi-dot me-1"></i>At least one uppercase letter</li>
                                    <li id="rule-lower"><i class="bi bi-dot me-1"></i>At least one lowercase letter</li>
                                    <li id="rule-number"><i class="bi bi-dot me-1"></i>At least one number</li>
                                    <li id="rule-symbol"><i class="bi bi-dot me-1"></i>At least one symbol</li>
                                    <li id="rule-match"><i class="bi bi-dot me-1"></i>Passwords match</li>
                                </ul>
                            </div>

                            <div class="col-12 d-grid">
                                <button id="submitBtn" type="submit" class="btn btn-primary" disabled>
                                    <i class="bi bi-arrow-repeat me-2"></i>Update Password
                                </button>
                            </div>
                            <div class="col-12">

                            </div>
                        </form>
                        <div class="d-flex justify-content-between mt-3">
                            <a href="/login" class="small fw-semibold text-primary text-decoration-none">
                                <i class="bi bi-arrow-left me-1"></i>Back to login
                            </a>
                            <p class="small text-white-50 mb-0">
                                Having trouble? <a href="{{ route('about') }}" class="text-primary">Contact Support</a>.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <section class="section-light py-5">
        <div class="container">
            <div class="row align-items-center g-4">
                <div class="col-lg-7">
                    <h2 class="fw-bold text-dark mb-2">Enterprise-grade security</h2>
                    <p class="text-muted mb-0">Reset flows are encrypted, audited and protected with anomaly detection
                        to keep your operations safe.</p>
                </div>
                <div class="col-lg-5">
                    <div class="row g-3">
                        <div class="col-sm-6">
                            <div class="auth-feature-tile">
                                <span class="auth-feature-tile__icon"><i class="bi bi-fingerprint"></i></span>
                                <h6 class="fw-semibold mb-1">Device attestation</h6>
                                <p class="small text-muted mb-0">Checks devices during sensitive changes.</p>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="auth-feature-tile">
                                <span class="auth-feature-tile__icon"><i class="bi bi-shield-lock"></i></span>
                                <h6 class="fw-semibold mb-1">Encrypted storage</h6>
                                <p class="small text-muted mb-0">Passwords hashed with industry best practices.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

@endsection

@push('js')
    <script>
        (function () {
            const currentPw = document.getElementById('currentPassword');
            const newPw = document.getElementById('newPassword');
            const confirmPw = document.getElementById('confirmPassword');
            const strengthBar = document.getElementById('strengthBar');
            const strengthText = document.getElementById('strengthText');
            const submitBtn = document.getElementById('submitBtn');

            const rules = {
                length: document.getElementById('rule-length'),
                upper: document.getElementById('rule-upper'),
                lower: document.getElementById('rule-lower'),
                number: document.getElementById('rule-number'),
                symbol: document.getElementById('rule-symbol'),
                match: document.getElementById('rule-match'),
            };

            function assess(pw) {
                const checks = {
                    length: pw.length >= 8,
                    upper: /[A-Z]/.test(pw),
                    lower: /[a-z]/.test(pw),
                    number: /\d/.test(pw),
                    symbol: /[^A-Za-z0-9]/.test(pw),
                };
                let score = Object.values(checks).filter(Boolean).length;
                return { checks, score };
            }

            function paintRule(el, ok) {
                el.classList.toggle('text-success', ok);
                el.classList.toggle('text-white-50', !ok);
            }

            function update() {
                const pw = newPw.value;
                const { checks, score } = assess(pw);
                const matches = pw.length > 0 && pw === confirmPw.value;
                const hasCurrentPw = currentPw.value.length > 0;

                paintRule(rules.length, checks.length);
                paintRule(rules.upper, checks.upper);
                paintRule(rules.lower, checks.lower);
                paintRule(rules.number, checks.number);
                paintRule(rules.symbol, checks.symbol);
                paintRule(rules.match, matches);

                let pct = (score / 5) * 100;
                strengthBar.style.width = pct + '%';
                strengthBar.classList.remove('bg-danger', 'bg-warning', 'bg-success');
                if (pct < 40) {
                    strengthBar.classList.add('bg-danger');
                    strengthText.textContent = 'Strength: too weak';
                } else if (pct < 80) {
                    strengthBar.classList.add('bg-warning');
                    strengthText.textContent = 'Strength: fair';
                } else {
                    strengthBar.classList.add('bg-success');
                    strengthText.textContent = 'Strength: strong';
                }

                submitBtn.disabled = !(score === 5 && matches && hasCurrentPw);
            }

            currentPw.addEventListener('input', update);
            newPw.addEventListener('input', update);
            confirmPw.addEventListener('input', update);
            update();

            function toggle(id, btnId) {
                const input = document.getElementById(id);
                const btn = document.getElementById(btnId);
                btn.addEventListener('click', () => {
                    const isPw = input.type === 'password';
                    input.type = isPw ? 'text' : 'password';
                    const icon = btn.querySelector('i');
                    icon.classList.toggle('bi-eye', !isPw);
                    icon.classList.toggle('bi-eye-slash', isPw);
                });
            }
            toggle('currentPassword', 'toggleCurrent');
            toggle('newPassword', 'toggleNew');
            toggle('confirmPassword', 'toggleConfirm');
        })();
    </script>
@endpush
