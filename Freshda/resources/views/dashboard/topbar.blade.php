        <header class="topbar">
          <button
            class="icon-btn sidebar-toggle"
            id="sidebarToggle"
            type="button"
            title="Menu"
            aria-label="Open menu"
          >
            <i class="ri-menu-line"></i>
          </button>

          <div class="search-wrapper">
            <div class="search-input-group">
              <i class="ri-search-line"></i>
              <input
                type="search"
                class="search-input"
                id="globalSearch"
                placeholder="Search fish species, scans, inventory..."
                data-i18n-placeholder="topbar.searchPlaceholder"
              />
              <button class="search-end-btn" type="button" id="searchBtn">
                <i class="ri-search-line"></i> <span data-i18n="topbar.search">Search</span>
              </button>
            </div>
            <div class="search-results" id="searchResults"></div>
          </div>

          <div class="topbar-actions" style="position: relative">
            <select
              class="lang-select"
              id="langSelect"
              title="Language"
              data-setting-select="language"
            >
              <option value="en">🇺🇸 EN</option>
              <option value="tl">🇵🇭 TL</option>
            </select>

            <button
              class="dark-toggle"
              id="darkToggle"
              title="Toggle dark mode"
              data-setting-toggle="darkMode"
            >
              <i class="ri-moon-line"></i>
            </button>

            <button
              class="icon-btn"
              title="Notifications"
              id="notifBtn"
              data-i18n-title="nav.notifications"
            >
              <i class="ri-notification-3-line"></i>
              <span class="count-badge @if ($unreadCount === 0) hidden @endif" id="notifCountBadge">{{ $unreadCount }}</span>
            </button>

            <button
              class="icon-btn"
              id="settingsBtn"
              title="Settings"
              data-i18n-title="nav.settings"
            >
              <i class="ri-settings-3-line"></i>
            </button>
            <div class="topbar-divider"></div>
            <div class="topbar-user-menu">
              <button
                class="topbar-user"
                type="button"
                id="topbarUserBtn"
                title="{{ $user->name }}"
                aria-haspopup="menu"
                aria-expanded="false"
              >
                <span class="topbar-avatar">
                  {{ $user->initials() }}
                  @if ($user->profilePictureUrl())
                    <img src="{{ $user->profilePictureUrl() }}" alt="{{ $user->name }}" />
                  @endif
                </span>
                <span class="topbar-user-name">{{ $user->name }}</span>
                <i class="ri-arrow-down-s-line topbar-user-caret"></i>
              </button>
              <div
                class="topbar-user-dropdown hidden"
                id="topbarUserDropdown"
                role="menu"
              >
                <button
                  class="topbar-user-item"
                  type="button"
                  data-page="profile"
                  role="menuitem"
                >
                  <i class="ri-user-settings-line"></i>
                  <span data-i18n="nav.profile">Profile</span>
                </button>
                <button
                  class="topbar-user-item danger"
                  type="button"
                  data-page="logout"
                  role="menuitem"
                >
                  <i class="ri-logout-box-r-line"></i>
                  <span data-i18n="nav.logout">Logout</span>
                </button>
              </div>
            </div>
          </div>
        </header>
