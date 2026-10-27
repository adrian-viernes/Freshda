@extends('layouts.app')

@section('title', 'Fresh-DA - Fish Freshness & Species Recognition')

@push('head')
    <link href="https://cdn.jsdelivr.net/npm/remixicon@4.2.0/fonts/remixicon.css" rel="stylesheet" />
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,400;14..32,500;14..32,600;14..32,700&display=swap" rel="stylesheet" />
@endpush

@push('styles')
    {{-- Load order comes from config/assets.php: later modules may rely on
         earlier ones, exactly as if they were a single stylesheet. --}}
    @foreach (config('assets.dashboard.css') as $module)
        <link rel="stylesheet" href="{{ asset('css/dashboard/'.$module) }}?v={{ filemtime(public_path('css/dashboard/'.$module)) }}">
    @endforeach
@endpush

@section('content')
    <div class="app-shell">
        @include('dashboard.sidebar')

        <!-- Dimming layer for the phone sidebar drawer, opened by the topbar
             hamburger button when the sidebar is off-canvas. -->
        <div class="sidebar-backdrop" id="sidebarBackdrop"></div>

      <div class="main-area">
        @include('dashboard.topbar')

        <main class="content">
          @include('dashboard.index')
          @include('dashboard.notifications')
          @include('dashboard.scan')
          @include('dashboard.batch')
          @include('dashboard.history')
          @if ($user->isVendor())
            @include('dashboard.inventory')
            @include('dashboard.suppliers')
            @include('dashboard.deliveries')
            @include('dashboard.reports')
          @endif
          @include('dashboard.profile')
          @include('dashboard.settings')
        </main>
      </div>
    </div>

    <!-- The slide-over quick-settings panel was removed: the topbar gear and
         the sidebar entry open the full Settings page directly. -->

    <form id="logoutForm" action="{{ route('logout') }}" method="POST" class="hidden">
      @csrf
    </form>

    <div class="modal-overlay" id="modalOverlay">
      <div class="modal" id="modal">
        <div class="modal-header">
          <h3 id="modalTitle">Title</h3>
          <button class="modal-close" id="modalClose">
            <i class="ri-close-line"></i>
          </button>
        </div>
        <div class="modal-body" id="modalBody"></div>
        <div class="modal-footer" id="modalFooter"></div>
      </div>
    </div>

    <div class="toast-container" id="toastContainer"></div>

    <!-- ===== MOBILE BOTTOM NAV (Home / Batch / Scan / History / More) ===== -->
    <nav class="mobile-bottom-nav" id="mobileNav">
      <a class="mobile-nav-item active" data-page="dashboard">
        <i class="ri-home-5-line"></i><span data-i18n="nav.dashboard">Home</span>
      </a>
      <a class="mobile-nav-item" data-page="batch">
        <i class="ri-stack-line"></i><span data-i18n="nav.batchShort">Batch</span>
      </a>
      <a class="mobile-nav-item camera-nav" data-page="scan">
        <i class="ri-camera-lens-line"></i><span data-i18n="nav.scan">Scan</span>
      </a>
      <a class="mobile-nav-item" data-page="history">
        <i class="ri-history-line"></i><span data-i18n="nav.historyShort">History</span>
      </a>
      @if ($user->isVendor())
        <a class="mobile-nav-item" data-page="inventory">
          <i class="ri-store-2-line"></i><span data-i18n="nav.inventory">Inventory</span>
        </a>
      @else
        <a class="mobile-nav-item" data-page="notifications">
          <i class="ri-notification-3-line"></i><span data-i18n="nav.notifications">Notifications</span>
        </a>
      @endif
    </nav>

@endsection

@push('scripts')
    <script>
      window.DASHBOARD_DATA = @json($dashboardData);
    </script>
    {{-- Classic scripts in order: they share the page's global scope, so the
         concatenation of these files behaves like the old single bundle. --}}
    @foreach (config('assets.dashboard.js') as $module)
        <script src="{{ asset('js/dashboard/'.$module) }}?v={{ filemtime(public_path('js/dashboard/'.$module)) }}"></script>
    @endforeach
@endpush
