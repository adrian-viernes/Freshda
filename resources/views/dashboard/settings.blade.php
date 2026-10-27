          <!-- ============ SETTINGS ============ -->
          <section id="settings-section" class="page-section hidden">
            <div class="page-header">
              <h1 data-i18n="page.settings.title">Settings</h1>
              <p class="text-muted" data-i18n="page.settings.sub">
                Customize your app experience
              </p>
            </div>

            <div class="settings-page">
              <!-- ===== Appearance ===== -->
              <div class="card settings-card">
                <div class="card-header">
                  <span class="card-title">
                    <i class="ri-palette-line"></i>
                    <span data-i18n="set.appearance">Appearance</span>
                  </span>
                </div>
                <div class="settings-group">
                  <div class="settings-row">
                    <div class="settings-row-label">
                      <i class="ri-moon-line"></i>
                      <div>
                        <span data-i18n="set.darkMode">Dark Mode</span>
                        <span class="settings-row-sub" data-i18n="set.darkModeSub">Switch To a Darker Colour Scheme</span>
                      </div>
                    </div>
                    <label class="switch">
                      <input
                        type="checkbox"
                        id="pageDarkToggle"
                        data-setting-checkbox="darkMode"
                      />
                      <span class="slider"></span>
                    </label>
                  </div>
                </div>
              </div>

              <!-- ===== Language ===== -->
              <div class="card settings-card">
                <div class="card-header">
                  <span class="card-title">
                    <i class="ri-global-line"></i>
                    <span data-i18n="set.language">Language</span>
                  </span>
                </div>
                <div class="settings-group">
                  <div class="settings-row">
                    <div class="settings-row-label">
                      <i class="ri-translate-2"></i>
                      <div>
                        <span data-i18n="set.appLanguage">App Language</span>
                        <span class="settings-row-sub" data-i18n="set.appLanguageSub">App Display Language</span>
                      </div>
                    </div>
                    <select
                      class="settings-select"
                      id="pageLangSelect"
                      data-setting-select="language"
                    >
                      <option value="en">🇺🇸 English</option>
                      <option value="tl">🇵🇭 Tagalog</option>
                    </select>
                  </div>
                </div>
                <p class="settings-note" data-i18n="set.languageNote">
                  Navigation, page headings and the whole settings screen are
                  translated. Live scan results and toasts stay in English.
                </p>
              </div>

              <!-- ===== AI & Scan ===== -->
              <div class="card settings-card">
                <div class="card-header">
                  <span class="card-title">
                    <i class="ri-cpu-line"></i>
                    <span data-i18n="set.aiScan">AI &amp; scanning</span>
                  </span>
                </div>
                <div class="settings-group">
                  <div class="settings-row">
                    <div class="settings-row-label">
                      <i class="ri-server-line"></i>
                      <div>
                        <span data-i18n="set.aiService">AI Service</span>
                        <span class="settings-row-sub">Offline Analysis</span>
                      </div>
                    </div>
                    <span class="badge notfresh">Not Configured</span>
                  </div>
                </div>
              </div>

              <!-- ===== Profile & Account ===== -->
              <div class="card settings-card">
                <div class="card-header">
                  <span class="card-title">
                    <i class="ri-user-settings-line"></i>
                    <span data-i18n="set.account">Profile &amp; account</span>
                  </span>
                </div>
                <div class="settings-group">
                  <div class="settings-row">
                    <div class="settings-row-label">
                      <i class="ri-user-line"></i>
                      <div>
                        <span data-i18n="set.name">Name</span>
                        <span class="settings-row-sub">{{ $user->name }}</span>
                      </div>
                    </div>
                  </div>
                  <div class="settings-row">
                    <div class="settings-row-label">
                      <i class="ri-mail-line"></i>
                      <div>
                        <span data-i18n="set.email">Email</span>
                        <span class="settings-row-sub">{{ $user->email }}</span>
                      </div>
                    </div>
                  </div>
                  <div class="settings-row">
                    <div class="settings-row-label">
                      <i class="ri-shield-user-line"></i>
                      <div>
                        <span data-i18n="set.role">Role</span>
                        <span class="settings-row-sub">{{ $user->roleLabel() }}</span>
                      </div>
                    </div>
                  </div>
                  <div class="settings-row">
                    <div class="settings-row-label">
                      <i class="ri-edit-line"></i>
                      <div>
                        <span data-i18n="set.manageProfile">Manage Profile</span>
                        <span class="settings-row-sub" data-i18n="set.manageProfileSub">Update Your Name, Email And Password</span>
                      </div>
                    </div>
                    <button class="btn btn-secondary" data-page="profile">
                      <i class="ri-arrow-right-line"></i>
                      <span data-i18n="set.open">Open</span>
                    </button>
                  </div>
                  <div class="settings-row">
                    <div class="settings-row-label">
                      <i class="ri-lock-password-line"></i>
                      <div>
                        <span data-i18n="set.changePassword">Change Password</span>
                        <span class="settings-row-sub" data-i18n="set.changePasswordSub">Update Your Account Password</span>
                      </div>
                    </div>
                    <button class="btn btn-secondary" id="settingsChangePasswordBtn">
                      <i class="ri-arrow-right-line"></i>
                      <span data-i18n="set.open">Open</span>
                    </button>
                  </div>
                  <div class="settings-row">
                    <div class="settings-row-label">
                      <i class="ri-logout-box-r-line"></i>
                      <div>
                        <span data-i18n="set.logout">Log Out</span>
                        <span class="settings-row-sub" data-i18n="set.logoutSub">End This Session</span>
                      </div>
                    </div>
                    <button class="btn btn-danger" data-page="logout">
                      <i class="ri-logout-box-r-line"></i>
                      <span data-i18n="nav.logout">Logout</span>
                    </button>
                  </div>
                </div>
              </div>

              <!-- ===== System / About ===== -->
              <div class="card settings-card">
                <div class="card-header">
                  <span class="card-title">
                    <i class="ri-information-line"></i>
                    <span data-i18n="set.system">System &amp; about</span>
                  </span>
                </div>
                <div class="settings-group">
                  <div class="settings-row">
                    <div class="settings-row-label">
                      <i class="ri-price-tag-3-line"></i>
                      <div>
                        <span data-i18n="set.version">Version</span>
                        <span class="settings-row-sub">Fresh‑DA PWA v1.0.0</span>
                      </div>
                    </div>
                  </div>
                  <div class="settings-row">
                    <div class="settings-row-label">
                      <i class="ri-database-2-line"></i>
                      <div>
                        <span data-i18n="set.cache">Offline Cache</span>
                        <span class="settings-row-sub" id="settingsCacheName">Freshda-v5</span>
                      </div>
                    </div>
                  </div>
                  <div class="settings-row">
                    <div class="settings-row-label">
                      <i class="ri-camera-lens-line"></i>
                      <div>
                        <span data-i18n="set.cameraSupport">Camera Support</span>
                        <span class="settings-row-sub" id="settingsCameraSupport">Checking…</span>
                      </div>
                    </div>
                  </div>
                  <div class="settings-row">
                    <div class="settings-row-label">
                      <i class="ri-restart-line"></i>
                      <div>
                        <span data-i18n="set.reset">Reset Preferences</span>
                        <span class="settings-row-sub" data-i18n="set.resetSub">Restore the Default Settings On This Device</span>
                      </div>
                    </div>
                    <button
                      class="btn btn-danger"
                      id="resetPreferencesBtn"
                      data-confirm="Reset all preferences on this device?"
                      data-confirm-title="Reset preferences"
                      data-confirm-note="Dark mode, language and scan settings return to their defaults."
                      data-confirm-ok="Reset"
                      data-confirm-icon="ri-restart-line"
                      data-confirm-danger="1"
                    >
                      <i class="ri-restart-line"></i>
                      <span data-i18n="set.resetBtn">Reset</span>
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </section>
