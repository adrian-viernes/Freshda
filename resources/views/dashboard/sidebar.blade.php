      <aside class="sidebar">
        <div class="sidebar-brand">
          <img src="{{ asset('images/Freshda Logo.png') }}" alt="Fresh-DA" class="sidebar-brand-img" />
          Fresh-DA
        </div>

        <nav class="sidebar-nav" id="sidebarNav">
          <div class="sidebar-nav-label" data-i18n="navGroup.overview">Overview</div>
          <a data-page="dashboard" class="active"
            ><i class="ri-dashboard-line"></i> <span data-i18n="nav.dashboard">Dashboard</span></a
          >

          <div class="sidebar-nav-label" data-i18n="navGroup.scan">Scan</div>
          <a data-page="scan"
            ><i class="ri-camera-lens-line"></i> <span data-i18n="nav.scan">Scan Fish</span></a
          >
          <a data-page="batch"
            ><i class="ri-stack-line"></i> <span data-i18n="nav.batch">Batch Sampling</span></a
          >
          <a data-page="history"
            ><i class="ri-history-line"></i> <span data-i18n="nav.history">Scan History</span></a
          >

          @if ($user->isVendor())
            <div class="sidebar-nav-label" data-i18n="navGroup.vendor">Vendor</div>
            <a data-page="inventory"
              ><i class="ri-store-2-line"></i> <span data-i18n="nav.inventory">Inventory</span></a
            >
            <a data-page="suppliers"
              ><i class="ri-team-line"></i> <span data-i18n="nav.suppliers">Suppliers</span></a
            >
            <a data-page="deliveries"
              ><i class="ri-truck-line"></i> <span data-i18n="nav.deliveries">Deliveries</span></a
            >
            <a data-page="reports"
              ><i class="ri-line-chart-line"></i> <span data-i18n="nav.reports">Reports</span></a
            >
          @endif

          <div class="sidebar-nav-label" data-i18n="navGroup.support">Support</div>
          <a data-page="notifications">
            <i class="ri-notification-3-line"></i> <span data-i18n="nav.notifications">Notifications</span>
            <span class="nav-badge @if ($unreadCount === 0) hidden @endif" id="sidebarNotifBadge">{{ $unreadCount }}</span>
          </a>

          <div class="sidebar-nav-label" data-i18n="navGroup.account">Account</div>
          <a data-page="profile"
            ><i class="ri-user-settings-line"></i> <span data-i18n="nav.profile">Profile</span></a
          >
          <a data-page="settings" id="sidebarSettingsBtn">
            <i class="ri-settings-3-line"></i> <span data-i18n="nav.settings">Settings</span>
          </a>
          <a data-page="logout" class="danger"
            ><i class="ri-logout-box-r-line"></i> <span data-i18n="nav.logout">Logout</span></a
          >
        </nav>

        <div class="sidebar-footer">
          <div class="sidebar-user" data-page="profile">
            <div class="avatar">
              {{ $user->initials() }}
              @if ($user->profilePictureUrl())
                <img src="{{ $user->profilePictureUrl() }}" alt="{{ $user->name }}" />
              @endif
            </div>
            <div class="sidebar-user-info">
              <div class="name">{{ $user->name }}</div>
              <div class="role">{{ $user->roleLabel() }}</div>
            </div>
            <i
              class="ri-arrow-right-s-line"
              style="color: var(--muted); font-size: 1.15rem"
            ></i>
          </div>
        </div>
      </aside>
