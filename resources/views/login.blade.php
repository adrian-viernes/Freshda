@extends('layouts.app')

@section('title', 'Login | Fresh-DA')

@push('head')
    <!-- Icons -->
    <link href="https://cdn.jsdelivr.net/npm/remixicon@4.2.0/fonts/remixicon.css" rel="stylesheet" />

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet" />
@endpush

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/auth.css') }}?v={{ filemtime(public_path('css/auth.css')) }}">
@endpush

@section('content')

    <!-- ==================== FLOATING DARK MODE TOGGLE ==================== -->
    <div class="page-top-actions">
        <button class="page-dark-toggle" id="pageDarkToggle" title="Toggle dark mode">
            <i class="ri-moon-line"></i>
        </button>
    </div>

    <!-- ============================================================
         AUTH SHELL — FLOATING CARD
         ============================================================ -->
    <div class="auth-shell active" id="authShell" data-view="{{ session()->hasOldInput('name') || session()->hasOldInput('password_confirmation') ? 'register' : 'login' }}">
        <div class="auth-card">

            <!-- ==================== LEFT — SOLID DARK PANEL ==================== -->
            <div class="auth-illustration">
                <div class="auth-illustration-icon">
                    <img src="{{ asset('images/Freshda Logo.png') }}" alt="Fresh-DA">
                </div>

                <h2 class="auth-headline">Freshness You Can Trust,<br />Every Single Scan.</h2>

                <p class="auth-subtext">
                    AI-powered fish freshness and species recognition.
                </p>
            </div>

            <!-- ==================== RIGHT — FORM PANEL ==================== -->
            <div class="auth-form-panel">
                <div class="auth-form-wrap">

                    <!-- Top Toggle (Log In / Register) -->
                    <div class="auth-toggle">
                        <button class="auth-toggle-btn active" id="toggleLogin">Log In</button>
                        <button class="auth-toggle-btn" id="toggleRegister">Register</button>
                    </div>

                    <!-- ============ LOGIN VIEW ============ -->
                    <div id="loginView">
                        <h1 class="auth-form-title">Welcome Back</h1>
                        <p class="auth-form-desc">Log In To Continue Scanning Fish.</p>

                        <div class="auth-success {{ session('status') ? 'show' : '' }}" id="loginStatus">
                            <i class="ri-checkbox-circle-line"></i>
                            <span>{{ session('status') }}</span>
                        </div>

                        <div class="auth-error {{ $errors->has('credentials') || $errors->has('email') || $errors->has('password') ? 'show' : '' }}" id="loginError">
                            <i class="ri-error-warning-line"></i>
                            <span id="loginErrorText">{{ $errors->first('credentials') ?: $errors->first('email') ?: $errors->first('password') ?: 'Invalid email or password' }}</span>
                        </div>

                        <form id="loginForm" action="{{ route('login.submit') }}" method="POST" novalidate>
                            @csrf
                            <div class="form-group">
                                <label class="form-label" for="loginEmail">Email Address</label>
                                <div class="auth-field">
                                    <input type="email" class="form-input" id="loginEmail" name="email" value="{{ old('email') }}" placeholder="You@Example.Com" autocomplete="email" />
                                    <i class="ri-check-line auth-field-check"></i>
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="loginPassword">Password</label>
                                <div class="auth-field">
                                    <input type="password" class="form-input" id="loginPassword" name="password" placeholder="••••••••" autocomplete="current-password" />
                                    <button type="button" class="toggle-password" data-target="loginPassword" aria-label="Toggle Password Visibility">
                                        <i class="ri-eye-line"></i>
                                    </button>
                                </div>
                            </div>

                            <div class="auth-remember">
                                <input type="checkbox" id="loginRemember" name="remember" value="1" />
                                <label for="loginRemember">Remember Me</label>
                            </div>

                            <button type="submit" class="auth-btn-solid" id="loginBtn">Log In</button>
                        </form>

                        <div class="auth-form-footer">
                            No account yet? <a class="auth-link" id="goToRegisterLink">Create One</a>
                        </div>
                    </div>

                    <!-- ============ REGISTER VIEW ============ -->
                    <div id="registerView" class="hidden">
                        <h1 class="auth-form-title">Create Account</h1>
                        <p class="auth-form-desc">Register Your Vendor Shop And Start Scanning Today.</p>

                        <div class="auth-error {{ $errors->has('name') || $errors->has('email') || $errors->has('password') ? 'show' : '' }}" id="registerError">
                            <i class="ri-error-warning-line"></i>
                            <span id="registerErrorText">{{ $errors->first('name') ?: $errors->first('email') ?: $errors->first('password') ?: 'Please fill in all required fields' }}</span>
                        </div>

                        <form id="registerForm" action="{{ route('register') }}" method="POST" novalidate>
                            @csrf
                            <div class="form-group">
                                <label class="form-label" for="registerName">Full Name</label>
                                <div class="auth-field">
                                    <input type="text" class="form-input" id="registerName" name="name" value="{{ old('name') }}" placeholder="Your full name" autocomplete="name" />
                                    <i class="ri-check-line auth-field-check"></i>
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="registerEmail">Email Address</label>
                                <div class="auth-field">
                                    <input type="email" class="form-input" id="registerEmail" name="email" value="{{ old('email') }}" placeholder="You@Example.Com" autocomplete="email" />
                                    <i class="ri-check-line auth-field-check"></i>
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="registerPassword">Password</label>
                                <div class="auth-field">
                                    <input type="password" class="form-input" id="registerPassword" name="password" placeholder="••••••••" autocomplete="new-password" />
                                    <button type="button" class="toggle-password" data-target="registerPassword" aria-label="Toggle Password Visibility">
                                        <i class="ri-eye-line"></i>
                                    </button>
                                </div>
                                <div class="password-strength" id="passwordStrength">
                                    <span class="bar"></span>
                                    <span class="bar"></span>
                                    <span class="bar"></span>
                                    <span class="bar"></span>
                                </div>
                                <div class="password-strength-text" id="passwordStrengthText">Use At Least 8 Characters</div>
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="registerConfirm">Confirm Password</label>
                                <div class="auth-field">
                                    <input type="password" class="form-input" id="registerConfirm" name="password_confirmation" placeholder="••••••••" autocomplete="new-password" />
                                    <button type="button" class="toggle-password" data-target="registerConfirm" aria-label="Toggle Password Visibility">
                                        <i class="ri-eye-line"></i>
                                    </button>
                                </div>
                            </div>

                            <button type="submit" class="auth-btn-solid" id="registerBtn">Register</button>                        </form>

                        <div class="auth-form-footer">
                            Already have an account? <a class="auth-link" id="goToLoginLink">Log In</a>
                        </div>
                    </div>

                </div>
            </div>

        </div>
    </div>

@endsection

@push('scripts')
    <script src="{{ asset('js/auth.js') }}?v={{ filemtime(public_path('js/auth.js')) }}"></script>
@endpush

