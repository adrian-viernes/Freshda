@extends('layouts.app')

@section('title', 'Fresh-DA | AI Fish Freshness Recognition')

{{-- The landing page has no dark/light switch, so it always renders light. --}}
@section('lockLightTheme', '1')

@push('head')
    <!-- Icons -->
    <link rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">
@endpush

@push('styles')
    <!-- CSS -->
    <link rel="stylesheet" href="{{ asset('css/landing.css') }}?v={{ filemtime(public_path('css/landing.css')) }}">
@endpush

@section('content')

    @include('landing.header')


    <!-- ================= HERO ================= -->
    <section class="hero" id="home">

        <div class="hero-background"></div>

        <div class="hero-content">

            <!-- LEFT -->
            <div class="hero-text">

                <div class="badge">
                    <span class="badge-dot"></span>
                    AI-powered fish quality analysis
                </div>

                <h4>
                    SMARTER. FASTER. MORE ACCURATE.
                </h4>

                <h1>
                    Know your fish.<br>
                    <span>Know its freshness.</span>
                </h1>

                <p>
                    Fresh-DA uses machine learning to analyze uploaded or
                    captured fish images, recognizing the species and
                    assessing visible freshness indicators.
                </p>


                <div class="hero-buttons">

                    <a href="{{ route('scan.public') }}" class="primary-btn">
                        Scan Fish
                        <i class="fa-solid fa-camera"></i>
                    </a>

                    <a href="{{ route('login') }}" class="secondary-btn">
                        Login
                    </a>

                </div>


                <!-- Statistics -->
                <div class="hero-stats">

                    <div class="stat">
                        <i class="fa-solid fa-fish"></i>
                        <div>
                            <strong>AI</strong>
                            <span>Species Detection</span>
                        </div>
                    </div>

                    <div class="stat">
                        <i class="fa-solid fa-bolt"></i>
                        <div>
                            <strong>Fast</strong>
                            <span>AI Analysis</span>
                        </div>
                    </div>

                    <div class="stat">
                        <i class="fa-solid fa-chart-line"></i>
                        <div>
                            <strong>Smart</strong>
                            <span>Quality Insights</span>
                        </div>
                    </div>

                </div>

            </div>



            <!-- RIGHT CAMERA SCANNER -->
            <div class="hero-scanner">

                <div class="scanner-card">

                    <div class="scanner-top">

                        <div class="scanner-title">
                            <div class="scanner-title-icon">
                                <i class="fa-solid fa-camera"></i>
                            </div>

                            <div>
                                <h3>AI Fish Scanner</h3>
                                <p>Ready for analysis</p>
                            </div>
                        </div>

                        <span class="scanner-live">
                            <span></span>
                            LIVE
                        </span>

                    </div>


                    <div class="camera-area" id="landingScanArea">

                        <div class="camera-grid"></div>

                        <!-- Scan Corners -->
                        <span class="scan-corner top-left"></span>
                        <span class="scan-corner top-right"></span>
                        <span class="scan-corner bottom-left"></span>
                        <span class="scan-corner bottom-right"></span>

                        <div class="fish-illustration">
                            <i class="fa-solid fa-fish"></i>
                        </div>

                        <div class="scan-line"></div>

                    </div>


                    <div class="scanner-bottom">

                        <div class="scan-status">
                            <span class="status-circle"></span>
                            AI scanner ready
                        </div>

                    </div>


                </div>

            </div>

        </div>

    </section>



    <!-- ================= QUICK FEATURES ================= -->
    <section class="features-section">

        <div class="feature-item">

            <div class="feature-icon">
                <i class="fa-solid fa-camera"></i>
            </div>

            <div class="middle">
                <h3>AI-Powered Scanning</h3>
                <p>
                    Capture a fish image and let AI analyze
                    its visible quality indicators.
                </p>
            </div>

        </div>


        <div class="feature-item">

            <div class="feature-icon">
                <i class="fa-solid fa-fish"></i>
            </div>

            <div class="middle">
                <h3>Species Recognition</h3>
                <p>
                    Identify supported fish species using
                    AI object detection.
                </p>
            </div>

        </div>


        <div class="feature-item">

            <div class="feature-icon">
                <i class="fa-solid fa-chart-column"></i>
            </div>

            <div class="middle">
                <h3>Quality Insights</h3>
                <p>
                    View freshness scores, confidence levels,
                    and scan results.
                </p>
            </div>

        </div>

    </section>



    <!-- ================= FEATURES ================= -->
    <section class="products" id="features">

        <div class="section-header">

            <div class="section-badge">
                <i class="fa-solid fa-microchip"></i>
                SMART FEATURES
            </div>

            <h2>
                Smarter technology for
                <span>better fish quality.</span>
            </h2>

            <p>
                Everything you need to recognize, analyze, and monitor
                fish quality in one system. The fish scanner is public
                and free. Inventory, batches, and scan history need a
                vendor account.
            </p>

        </div>


        <div class="product-grid">


            <!-- FISH SCANNER -->
            <div class="product-card" id="fish-scanner">

                <div class="card-icon">
                    <i class="fa-solid fa-fish"></i>
                </div>

                <h3>Fish Scanner</h3>

                <p>
                    Upload or capture a fish photo and the AI returns
                    the species, its scientific name, a freshness
                    score, and a confidence level.
                </p>

                <ul class="card-points">
                    <li>
                        <i class="fa-solid fa-circle-check"></i>
                        Public and free to use
                    </li>
                    <li>
                        <i class="fa-solid fa-circle-check"></i>
                        Camera capture or photo upload
                    </li>
                    <li>
                        <i class="fa-solid fa-circle-check"></i>
                        Species, freshness, and confidence
                    </li>
                    <li>
                        <i class="fa-solid fa-circle-check"></i>
                        JPG, PNG, or WebP up to 10 MB
                    </li>
                </ul>

                <a href="{{ route('scan.public') }}" class="card-link">
                    Try it now
                    <i class="fa-solid fa-arrow-right"></i>
                </a>

            </div>


            <!-- INVENTORY -->
            <div class="product-card" id="inventory">

                <div class="card-icon">
                    <i class="fa-solid fa-boxes-stacked"></i>
                </div>

                <h3>Inventory Management</h3>

                <p>
                    Vendors keep their fish stock in one place, with
                    quantities and freshness updates coming straight
                    from their own scans.
                </p>

                <ul class="card-points">
                    <li>
                        <i class="fa-solid fa-circle-check"></i>
                        Record stock and quantities in kg
                    </li>
                    <li>
                        <i class="fa-solid fa-circle-check"></i>
                        Freshness updates from every scan
                    </li>
                    <li>
                        <i class="fa-solid fa-circle-check"></i>
                        Add, edit, or remove fish items
                    </li>
                    <li>
                        <i class="fa-solid fa-circle-check"></i>
                        Vendor account required
                    </li>
                </ul>

                <a href="{{ route('login') }}" class="card-link">
                    Login
                    <i class="fa-solid fa-arrow-right"></i>
                </a>

            </div>


            <!-- BATCH -->
            <div class="product-card" id="batch">

                <div class="card-icon">
                    <i class="fa-solid fa-layer-group"></i>
                </div>

                <h3>Batch Scanner</h3>

                <p>
                    Photograph several fish from one delivery and
                    get a single freshness verdict for the whole
                    batch.
                </p>

                <ul class="card-points">
                    <li>
                        <i class="fa-solid fa-circle-check"></i>
                        Capture 3-5 photos per batch
                    </li>
                    <li>
                        <i class="fa-solid fa-circle-check"></i>
                        One verdict for the whole batch
                    </li>
                    <li>
                        <i class="fa-solid fa-circle-check"></i>
                        Mixed freshness warnings
                    </li>
                    <li>
                        <i class="fa-solid fa-circle-check"></i>
                        Vendor account required
                    </li>
                </ul>

                <a href="#how-it-works" class="card-link">
                    Learn More
                    <i class="fa-solid fa-arrow-right"></i>
                </a>

            </div>


            <!-- HISTORY -->
            <div class="product-card" id="history">

                <div class="card-icon">
                    <i class="fa-solid fa-clock-rotate-left"></i>
                </div>

                <h3>Scan History</h3>

                <p>
                    Every scan a vendor runs is saved, so past
                    results can be reopened, compared, or shared
                    anytime.
                </p>

                <ul class="card-points">
                    <li>
                        <i class="fa-solid fa-circle-check"></i>
                        Review every saved scan
                    </li>
                    <li>
                        <i class="fa-solid fa-circle-check"></i>
                        Species, freshness, and confidence
                    </li>
                    <li>
                        <i class="fa-solid fa-circle-check"></i>
                        Filter by freshness level
                    </li>
                    <li>
                        <i class="fa-solid fa-circle-check"></i>
                        Export the list as CSV
                    </li>
                </ul>

                <a href="#how-it-works" class="card-link">
                    Learn More
                    <i class="fa-solid fa-arrow-right"></i>
                </a>

            </div>

        </div>


        <div class="btn">
            <a href="{{ route('scan.public') }}" class="view-btn">
                <i class="fa-solid fa-camera"></i>
                Open the Fish Scanner
                <i class="fa-solid fa-arrow-right"></i>
            </a>
        </div>

    </section>



    <!-- ================= HOW IT WORKS ================= -->
    <section class="how-it-works" id="how-it-works">

        <div class="section-header">

            <div class="section-badge">
                <i class="fa-solid fa-gears"></i>
                SIMPLE PROCESS
            </div>

            <h2>
                How <span>Fresh-DA</span> works
            </h2>

            <p>
                Get AI-powered fish analysis in four simple steps.
            </p>

        </div>


        <div class="steps-container">


            <div class="step-card">

                <div class="step-number">01</div>

                <div class="step-icon">
                    <i class="fa-solid fa-camera"></i>
                </div>

                <h3>Capture</h3>

                <p>
                    Take a clear image of the fish
                    using your device camera.
                </p>

            </div>


            <div class="step-arrow">
                <i class="fa-solid fa-arrow-right"></i>
            </div>


            <div class="step-card">

                <div class="step-number">02</div>

                <div class="step-icon">
                    <i class="fa-solid fa-cloud-arrow-up"></i>
                </div>

                <h3>Analyze</h3>

                <p>
                    The captured image is sent securely
                    to the AI analysis server.
                </p>

            </div>


            <div class="step-arrow">
                <i class="fa-solid fa-arrow-right"></i>
            </div>


            <div class="step-card">

                <div class="step-number">03</div>

                <div class="step-icon">
                    <i class="fa-solid fa-brain"></i>
                </div>

                <h3>Recognize</h3>

                <p>
                    AI identifies the fish species
                    and visible freshness indicators.
                </p>

            </div>


            <div class="step-arrow">
                <i class="fa-solid fa-arrow-right"></i>
            </div>


            <div class="step-card">

                <div class="step-number">04</div>

                <div class="step-icon">
                    <i class="fa-solid fa-chart-column"></i>
                </div>

                <h3>Get Results</h3>

                <p>
                    View predicted species, freshness score,
                    and AI confidence level.
                </p>            </div>
        </div>
    </section>


    <!-- ================= ABOUT ================= -->
    <section class="about-section" id="about">

        <div class="section-header">

            <div class="section-badge">
                <i class="fa-solid fa-circle-info"></i>
                ABOUT THE SYSTEM
            </div>

            <h2>
                What <span>Fresh-DA</span> is all about.
            </h2>

            <p>
                Background, objectives, and intended use of Fresh-DA.
            </p>

        </div>

        <div class="about-grid">

            <div class="about-card">
                <div class="about-icon">
                    <i class="fa-solid fa-bullseye"></i>
                </div>
                <h3>System Overview</h3>
                <p>
                    Fresh-DA is a machine learning-based progressive web app
                    that recognizes fish species and assesses freshness from
                    uploaded or captured images, with vendor tools for
                    inventory monitoring.
                </p>
            </div>

            <div class="about-card">
                <div class="about-icon">
                    <i class="fa-solid fa-brain"></i>
                </div>
                <h3>Machine Learning</h3>
                <p>
                    A trained model processes each fish image to identify the
                    supported species and assess visible freshness indicators.
                    You get understandable results, no model internals needed.
                </p>
            </div>

            <div class="about-card">
                <div class="about-icon">
                    <i class="fa-solid fa-users"></i>
                </div>
                <h3>Who it is for</h3>
                <p>
                    Customers use the public scanning features without an
                    account, while fish vendors get authenticated management
                    functions such as batch scanning, scan history, and inventory.
                </p>
            </div>

            <div class="about-card">
                <div class="about-icon">
                    <i class="fa-solid fa-hand-holding-heart"></i>
                </div>
                <h3>Benefits</h3>
                <p>
                    Fresh-DA helps fish vendors with faster fish assessment,
                    inventory monitoring, and kept scan assessment records
                    they can review and export anytime.
                </p>
            </div>

        </div>

        <div class="about-disclaimer">
            <i class="fa-solid fa-triangle-exclamation"></i>
            <div>
                <strong>Assessment Limitations.</strong>
                Image-based assessment is an estimate, and it does not guarantee
                food safety. Results depend on image quality, model training,
                and the supported fish classes.
            </div>
        </div>

    </section>


    <!-- ================= CTA ================= -->
    <section class="cta-section">

        <div class="cta-icon">
            <i class="fa-solid fa-fish"></i>
        </div>

        <h2>
            Ready to make fish quality
            <span>checking smarter?</span>
        </h2>

        <p>
            Start using Fresh-DA and get AI-powered insights
            about your fish quality.
        </p>

        <div class="cta-buttons">

            <a href="{{ route('scan.public') }}" class="cta-primary">
                <i class="fa-solid fa-camera"></i>
                Scan Fish Now
            </a>

            <a href="{{ route('login') }}" class="cta-secondary">
                Login
            </a>

        </div>

    </section>


    @include('landing.footer')


@endsection

@push('scripts')
    <!-- JavaScript -->
    <script src="{{ asset('js/landing.js') }}?v={{ filemtime(public_path('js/landing.js')) }}"></script>
@endpush
