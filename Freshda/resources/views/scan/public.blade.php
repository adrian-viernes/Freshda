@extends('layouts.app')

@section('title', 'Fish Scanner | Fresh-DA')

@push('head')
    <link rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">
    <link rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/remixicon@4.2.0/fonts/remixicon.css" />
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,400;14..32,500;14..32,600;14..32,700&display=swap" rel="stylesheet" />
@endpush

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/scan-public.css') }}?v={{ filemtime(public_path('css/scan-public.css')) }}">
@endpush

@section('content')

    <!-- ================= HEADER ================= -->
    <header class="scan-header">

        <a href="{{ route('landing') }}" class="scan-logo">
            <img src="{{ asset('images/Freshda Logo.png') }}" alt="Fresh-DA" class="scan-logo-img">
            <span class="scan-logo-text">Fresh-<span>DA</span></span>
        </a>

        <nav class="scan-header-nav">
            <a href="{{ route('landing') }}" class="scan-header-link">
                <i class="fa-solid fa-house"></i>
                Home
            </a>
            <a href="{{ route('login') }}" class="scan-header-login">
                Login
            </a>
        </nav>

    </header>

    <!-- ================= PUBLIC SCANNER ================= -->
    <main class="scan-main">

        <section class="scan-card">

            <div class="scan-card-head">

                <div class="scan-card-icon">
                    <i class="fa-solid fa-camera"></i>
                </div>

                <div>
                    <h1>Fish Scanner</h1>
                    <p>
                        Upload or capture a fish photo and get an AI assessment of
                        its species and freshness in seconds, free, and without an account.
                    </p>
                </div>

            </div>

            <div class="scan-upload" id="scanUpload">

                <div class="scan-preview" id="scanPreview">
                    <div id="scanPreviewEmpty">
                        <p>Click or drop a fish photo here</p>
                        <span>JPG, PNG or WebP · up to 10 MB</span>
                    </div>
                    <img id="scanPreviewImage" class="hidden" alt="Selected fish image">

                    <video id="scanCameraVideo" class="scan-camera-video hidden"
                        playsinline autoplay muted></video>

                    <span class="scan-live-badge hidden" id="scanLiveBadge">
                        <span></span>LIVE
                    </span>

                    <canvas id="scanCanvas" class="hidden"></canvas>
                </div>

                <input type="file" id="scanFileInput" accept="image/*" class="hidden">

                <div class="scan-error" id="scanError">
                    <i class="ri-error-warning-line"></i>
                    <span id="scanErrorText"></span>
                </div>

                <div class="scan-actions">

                    <button type="button" class="scan-btn scan-camera-btn" id="scanCameraBtn">
                        <i class="fa-solid fa-camera"></i>
                        Open Camera
                    </button>

                    <button type="button" class="scan-btn hidden" id="scanCaptureBtn">
                        <i class="fa-solid fa-circle"></i>
                        Capture Photo
                    </button>

                    <button type="button" class="scan-btn hidden" id="scanCloseBtn">
                        <i class="fa-solid fa-xmark"></i>
                        Close
                    </button>

                    <button type="button" class="scan-btn" id="scanAnalyzeBtn" disabled>
                        <i class="fa-solid fa-wand-magic-sparkles"></i>
                        Analyze Fish
                    </button>

                </div>

                <input type="file" accept="image/*" capture="environment"
                    id="scanCaptureInput" class="hidden">

                <p class="scan-note">
                    Private vendors keep every scan in a full scan history with
                    batch sampling, inventory and export tools.
                    <a href="{{ route('login') }}">Login</a>
                    for vendor features.
                </p>

            </div>

            <div class="scan-result hidden" id="scanResult">

                <div class="scan-result-head">
                    <i class="ri-checkbox-circle-line"></i>
                    <span id="scanResultMessage"></span>
                </div>

                <div class="scan-result-grid">

                    <div class="scan-result-item">
                        <span class="scan-result-label"><i class="ri-apps-2-line"></i> Species</span>
                        <strong id="scanResultName"></strong>
                        <em id="scanResultScientific"></em>
                    </div>

                    <div class="scan-result-item">
                        <span class="scan-result-label">Freshness</span>
                        <strong class="freshness-badge" id="scanResultFreshness"></strong>
                        <div class="freshness-bar">
                            <span id="scanResultFreshnessBar"></span>
                        </div>
                        <span class="scan-result-sub" id="scanResultFreshnessConf"></span>
                    </div>

                    <div class="scan-result-item">
                        <span class="scan-result-label">Species Confidence</span>
                        <strong id="scanResultConfidence"></strong>
                        <span class="scan-result-sub" id="scanResultBand"></span>
                    </div>

                </div>

                <button type="button" class="scan-btn scan-again" id="scanAgainBtn">
                    <i class="fa-solid fa-rotate-right"></i>
                    Scan Another Fish
                </button>

            </div>

            <p class="scan-disclaimer">
                <i class="ri-information-line"></i>
                Image-based assessment is an estimate. Results depend on image
                quality, model training and the supported fish classes. They do
                not guarantee food safety.
            </p>

        </section>


        <!-- ================= HOW IT WORKS ================= -->
        <section class="scan-info-section">

            <div class="scan-info-head">
                <h2>How it works</h2>
                <p>Four quick steps from photo to result.</p>
            </div>

            <div class="scan-steps-grid">

                <div class="scan-step-card">
                    <span class="scan-step-number">01</span>
                    <div class="scan-step-icon">
                        <i class="fa-solid fa-camera"></i>
                    </div>
                    <h3>Add a Photo</h3>
                    <p>
                        Upload an image or open your camera and
                        capture the fish directly on this page.
                    </p>
                </div>

                <div class="scan-step-card">
                    <span class="scan-step-number">02</span>
                    <div class="scan-step-icon">
                        <i class="fa-solid fa-cloud-arrow-up"></i>
                    </div>
                    <h3>AI Analyzes</h3>
                    <p>
                        The image is sent securely to the AI
                        analysis server. JPG, PNG or WebP up to 10 MB.
                    </p>
                </div>

                <div class="scan-step-card">
                    <span class="scan-step-number">03</span>
                    <div class="scan-step-icon">
                        <i class="fa-solid fa-brain"></i>
                    </div>
                    <h3>Recognize</h3>
                    <p>
                        The model identifies the fish species and
                        reads visible freshness indicators.
                    </p>
                </div>

                <div class="scan-step-card">
                    <span class="scan-step-number">04</span>
                    <div class="scan-step-icon">
                        <i class="fa-solid fa-chart-column"></i>
                    </div>
                    <h3>Get Results</h3>
                    <p>
                        See the predicted species, freshness level,
                        and how confident the AI is.
                    </p>
                </div>

            </div>

        </section>



        <!-- ================= PHOTO TIPS + RESULTS GUIDE ================= -->
        <section class="scan-info-columns">

            <div class="scan-info-panel">

                <div class="scan-info-panel-head">
                    <div class="scan-info-panel-icon">
                        <i class="fa-solid fa-lightbulb"></i>
                    </div>
                    <div>
                        <h3>Tips for a better scan</h3>
                        <p>A clearer photo gives a more reliable assessment.</p>
                    </div>
                </div>

                <ul class="scan-tips-list">

                    <li>
                        <i class="fa-solid fa-circle-check"></i>
                        <div>
                            <strong>Use good lighting</strong>
                            <span>Natural or bright light. Avoid dark, backlit, or heavily shadowed shots.</span>
                        </div>
                    </li>

                    <li>
                        <i class="fa-solid fa-circle-check"></i>
                        <div>
                            <strong>Fill the frame with one fish</strong>
                            <span>Photograph a single fish close up instead of a pile or a busy stall.</span>
                        </div>
                    </li>

                    <li>
                        <i class="fa-solid fa-circle-check"></i>
                        <div>
                            <strong>Show the key parts</strong>
                            <span>Include the eyes, gills, skin, and body. These show freshness best.</span>
                        </div>
                    </li>

                    <li>
                        <i class="fa-solid fa-circle-check"></i>
                        <div>
                            <strong>Keep it sharp and level</strong>
                            <span>Hold steady, shoot from slightly above, and avoid blur or motion.</span>
                        </div>
                    </li>

                    <li>
                        <i class="fa-solid fa-circle-check"></i>
                        <div>
                            <strong>Avoid filters and edits</strong>
                            <span>Upload the original photo. Filters change the colors the AI reads.</span>
                        </div>
                    </li>

                </ul>

            </div>

            <div class="scan-info-panel">

                <div class="scan-info-panel-head">
                    <div class="scan-info-panel-icon">
                        <i class="fa-solid fa-clipboard-list"></i>
                    </div>
                    <div>
                        <h3>Understanding your result</h3>
                        <p>What each part of the assessment means.</p>
                    </div>
                </div>

                <div class="scan-legend">

                    <div class="scan-legend-item">
                        <span class="scan-legend-dot ok"></span>
                        <div>
                            <strong>Fresh</strong>
                            <p>No visible freshness problems detected in the photo.</p>
                        </div>
                    </div>

                    <div class="scan-legend-item">
                        <span class="scan-legend-dot warn"></span>
                        <div>
                            <strong>Moderately Fresh</strong>
                            <p>Some visible signs suggest reduced freshness. Check before buying or selling.</p>
                        </div>
                    </div>

                    <div class="scan-legend-item">
                        <span class="scan-legend-dot bad"></span>
                        <div>
                            <strong>Not Fresh</strong>
                            <p>Clear visible indicators of spoilage were detected.</p>
                        </div>
                    </div>

                    <div class="scan-legend-item">
                        <span class="scan-legend-dot info"></span>
                        <div>
                            <strong>Confidence</strong>
                            <p>
                                The percentage shows how sure the AI is. Low confidence usually
                                means the photo was unclear. Try a brighter, closer shot.
                            </p>
                        </div>
                    </div>

                </div>

            </div>

        </section>



    </main>

@endsection

@push('scripts')
    <script src="{{ asset('js/scan-public.js') }}?v={{ filemtime(public_path('js/scan-public.js')) }}"></script>
@endpush
