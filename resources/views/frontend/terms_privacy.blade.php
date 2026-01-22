@extends('frontend.layout.frontend')

@section('content')

    <!-- Terms hero -->
    <header class="hero-section auth-hero register-hero d-flex align-items-center">
        <div class="container">
            <div class="row align-items-center g-5">
                <div class="col-lg-6 order-2 order-lg-1">
                    <h1 class="display-4 fw-bold text-white mb-3">FlockSense Terms & Privacy</h1>
                    <p class="lead text-white-50 mb-3">
                        Our commitment to you: transparent terms, secure data handling, and reliable service.
                    </p>
                    <p class="text-white-50 mb-4 small fw-semibold">
                        <em>Effective:</em> October 1, 2025 • <strong>Jurisdiction:</strong> Pakistan • <i class="bi bi-telephone ms-1"></i> Support: 9:00 AM–5:00 PM (PKT)
                    </p>
                    <ul class="auth-benefits list-unstyled text-white-50 mb-0">
                        <li class="mb-3">
                            <i class="bi bi-check-circle-fill text-primary me-2"></i>
                            Full terms of service
                        </li>
                        <li class="mb-3">
                            <i class="bi bi-check-circle-fill text-primary me-2"></i>
                            Comprehensive privacy policy
                        </li>
                        <li>
                            <i class="bi bi-check-circle-fill text-primary me-2"></i>
                            Contact: {{ settings('contact.email') }}
                        </li>
                    </ul>
                </div>

                <div class="col-lg-5 offset-lg-1 order-1 order-lg-2">
                    <div class="auth-card shadow-lg">
                        <h4 class="fw-bold mb-3 text-white">Review & Accept</h4>
                        <p class="text-white-50 mb-4">Read our policies below. Questions? Reach out anytime.</p>
                        <div class="row g-3">
                            <a href="/login" class="btn btn-primary">Log In</a>
                            <a href="/register" class="btn btn-outline-light">Register</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <section class="section-light py-5">
        <div class="container">
            <div class="row g-4 align-items-start">
                <div class="col-12">
                    <!-- TERMS OF SERVICE -->
                    <section class="mb-5">
                        <h2 class="fw-bold text-dark mb-4">Terms of Service</h2>
                        <div class="row">
                            <div class="col-12">
                                <ol class="ps-4 mb-0 fs-6 lh-lg">
                                    <li><strong>Who we are.</strong> “FlockSense”, “we”, “us” provides IoT-enabled poultry farm tools (devices, dashboards, audit prep, exports). Contact: <a href="mailto:{{ settings('contact.email') }}">{{ settings('contact.email') }}</a>.</li>
                                    <li><strong>Agreement.</strong> By using our web/mobile apps, APIs, or connecting devices, you accept these Terms on behalf of yourself or your organization.</li>
                                    <li><strong>Accounts.</strong> Keep registration info accurate and credentials secure. You’re responsible for activity under your account.</li>
                                    <li><strong>Subscriptions & fees.</strong> Paid features require an active plan. Fees are billed per plan or order form; taxes are your responsibility.</li>
                                    <li><strong>Availability.</strong> We target high uptime but don’t guarantee uninterrupted service. Maintenance and emergencies may occur. SLAs apply only if agreed in writing.</li>
                                    <li><strong>Devices & firmware.</strong> You’re responsible for safe installation and operation. Supported devices may receive OTA firmware updates.</li>
                                    <li><strong>Informational only.</strong> Outputs (alerts, analytics, scores) are informational and not veterinary, medical, safety, or legal advice.</li>
                                    <li><strong>Your data.</strong> You own your input/operational data; you grant us a license to host/process it to provide and improve the service.</li>
                                    <li><strong>Acceptable use.</strong> Don’t break the law, infringe rights, upload malware, abuse the API, or attempt unauthorized access. We may suspend/terminate for violations.</li>
                                    <li><strong>AI/ML features.</strong> We use ML to power features for your account. We will not use identifiable farm data to train generalized models without your explicit opt-in.</li>
                                    <li><strong>Third-party services.</strong> Integrations are governed by their own terms; we aren’t responsible for their acts/omissions.</li>
                                    <li><strong>IP & feedback.</strong> We retain rights to our software/brand. You grant us a license to use feedback.</li>
                                    <li><strong>Privacy.</strong> See “Privacy” below. A DPA is available upon request.</li>
                                    <li><strong>Termination.</strong> Either party may terminate as permitted. We provide a reasonable data-export window unless prohibited by law.</li>
                                    <li><strong>Disclaimers & limitation.</strong> Service is “as is.” To the extent allowed by law, we disclaim implied warranties. Our total liability is limited to amounts paid in the 12 months before the claim; no indirect or consequential damages.</li>
                                    <li><strong>Governing law.</strong> Pakistan law governs. Venue is the competent courts in Pakistan unless we agree otherwise in writing.</li>
                                </ol>
                            </div>
                        </div>
                    </section>

                    <hr class="my-5">

                    <!-- PRIVACY POLICY -->
                    <section>
                        <h2 class="fw-bold text-dark mb-4">Privacy Policy</h2>
                        <div class="row">
                            <div class="col-12 col-md-6">
                                <ul class="ps-4 mb-4 fs-6 lh-lg">
                                    <li><strong>Data we collect:</strong> account/contact data; farm/shed/flock and operational records; device/telemetry (e.g., temperature, humidity, NH₃, CO₂, firmware); usage/cookies; support communications; integration data you connect.</li>
                                    <li><strong>How we use it:</strong> provide/secure our services; configure devices and firmware; analytics/alerts; product improvement; service notices and security alerts (marketing only with consent where required); legal compliance.</li>
                                    <li><strong>Sharing:</strong> processors (hosting, storage, analytics, comms, billing) under contract; integrations you authorize; advisors; lawful authorities; corporate transactions with safeguards. We do <em>not</em> sell personal data.</li>
                                </ul>
                            </div>
                            <div class="col-12 col-md-6">
                                <ul class="ps-4 mb-4 fs-6 lh-lg">
                                    <li><strong>Legal bases (where applicable):</strong> contract performance, legitimate interests (security/improvement), consent (certain cookies/marketing), legal obligations.</li>
                                    <li><strong>Security & retention:</strong> encryption in transit, access controls, monitoring; retain as needed for service/legal duties; aggregated/anonymous data may be kept.</li>
                                    <li><strong>Your rights:</strong> subject to law, request access, correction, deletion, restriction, portability, or object; withdraw consent where used. Contact: <a href="mailto:{{ settings('contact.email') }}">{{ settings('contact.email') }}</a>.</li>
                                    <li><strong>AI/ML controls:</strong> identifiable farm data won’t be used to train generalized models without your explicit opt-in; manage via in-app settings or by email.</li>
                                </ul>
                            </div>
                        </div>
                        <div class="text-center">
                            <p class="small text-primary mb-0">
                                Questions? <a href="mailto:{{ settings('contact.email') }}">{{ settings('contact.email') }}</a> • Support: 9:00 AM–5:00 PM (PKT)
                            </p>
                        </div>
                    </section>
                </div>
            </div>
        </div>
    </section>

@endsection
