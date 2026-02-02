@extends('frontend.layout.frontend')

@section('content')

    <!-- Verify Email Hero -->
    <header class="hero-section auth-hero register-hero d-flex align-items-center">
        <div class="container">
            <div class="row align-items-center g-5">
                <!-- Left Column: Same Value Prop to maintain context -->
                <div class="col-lg-6 order-2 order-lg-1">
                    <h1 class="display-4 fw-bold text-white mb-3">Almost there!</h1>
                    <p class="lead text-white-50 mb-4">
                        We've sent a verification link to your email. Confirming your email helps us keep your farm data secure from day one.
                    </p>
                    <ul class="auth-benefits list-unstyled text-white-50 mb-0">
                        <li class="mb-3">
                            <i class="bi bi-check-circle-fill text-primary me-2"></i>
                            Secure role-based access control
                        </li>
                        <li class="mb-3">
                            <i class="bi bi-check-circle-fill text-primary me-2"></i>
                            Verified communication channels
                        </li>
                        <li>
                            <i class="bi bi-check-circle-fill text-primary me-2"></i>
                            Instant access to your dashboard upon verification
                        </li>
                    </ul>
                </div>

                <!-- Right Column: Verification Card -->
                <div class="col-lg-5 offset-lg-1 order-1 order-lg-2">
                    <div class="auth-card shadow-lg text-center py-5">
                        <div class="mb-4">
                            <div class="rounded-circle bg-primary bg-opacity-25 d-inline-flex align-items-center justify-content-center" style="width: 80px; height: 80px;">
                                <i class="bi bi-envelope-check-fill text-primary display-4"></i>
                            </div>
                        </div>

                        <h4 class="fw-bold mb-3 text-white">Check your inbox</h4>
                        <p class="text-white-50 mb-4 px-3">
                            We have sent a verification link to
                            @if(session('user') || isset($user))
                                <br><strong class="text-white">{{ isset($user) ? $user->email : session('user')->email }}</strong>
                            @else
                                <strong class="text-white">your email address</strong>
                            @endif
                        </p>

                        <div class="d-grid gap-3 px-4">
                            <a href="https://mail.google.com" target="_blank" class="btn btn-primary">
                                <i class="bi bi-envelope-open me-2"></i>Open Gmail
                            </a>
                            <a href="/login" class="btn btn-outline-light">
                                Skip to Login
                            </a>
                        </div>

                        <div class="mt-4 pt-3 border-top border-secondary border-opacity-25 mx-4">
                            <p class="text-white-50 small mb-2">Didn't receive the email?</p>
                            <form action="{{ route('verification.send') }}" method="POST">
                                @csrf
                                <button type="submit" class="btn btn-link link-primary text-decoration-none p-0 fw-semibold">
                                    Click to resend
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <section class="section-light py-5">
        <div class="container">
            <div class="row g-4 align-items-center justify-content-center text-center">
                <div class="col-lg-8">
                    <h5 class="fw-bold text-dark mb-3">While you wait...</h5>
                    <p class="text-muted mb-0">
                        Our support team is available 24/7 to help you onboard your devices.
                        Check out our <a href="#" class="text-primary text-decoration-none">Quick Start Guide</a> or
                        <a href="#" class="text-primary text-decoration-none">Community Forum</a>.
                    </p>
                </div>
            </div>
        </div>
    </section>

@endsection
