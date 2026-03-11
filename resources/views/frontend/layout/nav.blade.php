<!-- Navigation Bar -->
<nav class="navbar navbar-expand-lg navbar-light fixed-top">
    <div class="container d-flex align-items-center justify-content-between">
        <div class="navbar-leading">
            <button class="navbar-toggler d-lg-none" type="button" data-bs-toggle="offcanvas"
                    data-bs-target="#navbarOffcanvas" aria-controls="navbarOffcanvas" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <a class="navbar-brand d-flex align-items-center" href="/">
                <img src="{{ asset(settings('company.logo', 'assets/img/logo.png')) }}" alt="FlockSense logo" class="desktop-logo d-none d-lg-block">
                <img src="{{ asset(settings('company.mobile-logo', 'assets/img/mobile_logo.png')) }}" alt="FlockSense mobile logo" class="mobile-logo d-lg-none">
                <span class="visually-hidden">{{ settings('company.name') }}</span>
            </a>
        </div>
        <div class="offcanvas offcanvas-start offcanvas-lg" tabindex="-1" id="navbarOffcanvas"
             aria-labelledby="navbarOffcanvasLabel">
            <div class="offcanvas-header d-lg-none">
                <h5 class="offcanvas-title" id="navbarOffcanvasLabel">Menu</h5>
                <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
            </div>
            <div class="offcanvas-body d-flex flex-column flex-lg-row align-items-lg-center">
                <ul class="navbar-nav mb-4 mb-lg-0 align-items-start align-items-lg-center">
                    <li class="nav-item">
                        <a @class(['nav-link', 'active' => Request::is('/')]) href="/">Home</a>
                    </li>
                    <li class="nav-item">
                        <a @class(['nav-link', 'active' => Request::routeIs('features')]) href="{{ route('features') }}">Features</a>
                    </li>
                    <li class="nav-item">
                        <a @class(['nav-link', 'active' => Request::routeIs('partners')]) href="{{ route('partners') }}">Partners</a>
                    </li>
                    <li class="nav-item dropdown">
                        <a @class(['nav-link dropdown-toggle', 'active' => Request::routeIs('blogs') || Request::routeIs('events')])
                          href="#" id="resourcesDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">Resources</a>
                        <ul class="dropdown-menu" aria-labelledby="resourcesDropdown">
                            <li><a class="dropdown-item" href="{{ route('blogs') }}">Blogs</a></li>
                            <li><a class="dropdown-item" href="{{ route('events') }}">Upcoming Events</a></li>
                        </ul>
                    </li>
                    <li class="nav-item">
                        <a @class(['nav-link', 'active' => Request::routeIs('pricing')]) href="{{ route('pricing') }}">Pricing</a>
                    </li>
                    <li class="nav-item">
                        <a @class(['nav-link', 'active' => Request::routeIs('about')]) href="{{ route('about') }}">About Us</a>
                    </li>
                </ul>
            </div>
        </div>
        <div class="navbar-actions d-flex align-items-center">
            <div class="auth-icons d-flex d-lg-none align-items-center">
                <a class="nav-icon" href="/login" aria-label="Login">
                    <i class="bi bi-person-circle"></i>
                </a>
                <a class="nav-icon" href="/register" aria-label="Sign Up">
                    <i class="bi bi-person-plus"></i>
                </a>
            </div>
            <div class="auth-buttons d-none d-lg-flex align-items-center ms-3">
                <a class="btn btn-outline-secondary me-2" href="/login">
                    <i class="bi bi-person-circle me-2"></i>Login
                </a>
                <a class="btn btn-primary" href="/register">
                    <i class="bi bi-person-plus me-2"></i>Sign Up
                </a>
            </div>
        </div>
    </div>
</nav>
