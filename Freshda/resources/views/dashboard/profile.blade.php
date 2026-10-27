<!-- ============ PROFILE ============ -->
          <section id="profile-section" class="page-section hidden">
            <div class="page-header">
              <h1 data-i18n="page.profile.title">Profile</h1>
              <p class="text-muted" data-i18n="page.profile.sub">
                Manage your account information
              </p>
            </div>

            <div class="profile-grid">

              <!-- ===== SIDEBAR ===== -->
              <aside class="profile-sidebar">
                <div class="sidebar-top"></div>

                <div class="sidebar-avatar-wrap">
                  <div
                    class="profile-avatar sidebar-avatar"
                    id="profileAvatar"
                    data-profile-picture-path="{{ $user->profile_picture_path }}"
                    title="{{ $user->name }}"
                  >
                    {{ $user->initials() }}
                    @if ($user->profilePictureUrl())
                      <img src="{{ $user->profilePictureUrl() }}" alt="{{ $user->name }}" />
                    @endif
                  </div>
                </div>

                <div class="sidebar-photo-actions">
                  <label class="btn btn-secondary btn-sm" id="changeProfilePictureBtn">
                    <i class="ri-camera-line"></i>
                    <span data-i18n="profile.changePhoto">Change Photo</span>
                    <input
                      type="file"
                      id="profilePictureInput"
                      class="hidden"
                      accept="image/png, image/jpeg, image/webp"
                    />
                  </label>
                  <button
                    class="btn btn-secondary btn-sm"
                    id="removeProfilePictureBtn"
                    type="button"
                    style="display: none"
                    data-confirm="Remove your profile photo?"
                    data-confirm-title="Remove photo"
                    data-confirm-note="Your initials will be shown instead."
                    data-confirm-ok="Remove photo"
                    data-confirm-icon="ri-delete-bin-line"
                    data-confirm-danger="1"
                  >
                    <i class="ri-delete-bin-line"></i>
                    <span data-i18n="profile.removePhoto">Remove</span>
                  </button>
                </div>

                <div class="sidebar-info">
                  <div class="profile-display-name sidebar-name">{{ $user->name }}</div>
                  <div class="profile-display-email sidebar-email">{{ $user->email }}</div>
                  <span class="sidebar-role">
                    <i class="ri-verified-badge-line"></i>
                    {{ $user->roleLabel() }}
                  </span>
                </div>

                <div class="sidebar-divider"></div>

                <div class="sidebar-stats">
                  <div class="sidebar-stat">
                    <div class="sidebar-stat-value">{{ $stats['total'] }}</div>
                    <div class="sidebar-stat-label" data-i18n="profile.scans">Scans</div>
                  </div>
                  <div class="sidebar-stat">
                    <div class="sidebar-stat-value">{{ $stats['fresh'] }}</div>
                    <div class="sidebar-stat-label" data-i18n="profile.fresh">Fresh</div>
                  </div>
                </div>

                <div class="sidebar-actions">
                  <button class="btn btn-primary btn-block" id="editProfileBtn" type="button">
                    <i class="ri-edit-line"></i>
                    <span data-i18n="profile.editProfile">Edit Profile</span>
                  </button>
                  <button class="btn btn-secondary btn-block" id="changePasswordBtn" type="button">
                    <i class="ri-lock-line"></i>
                    <span data-i18n="profile.changePassword">Change Password</span>
                  </button>
                  <button class="btn btn-danger btn-block" data-page="logout" type="button">
                    <i class="ri-logout-box-r-line"></i>
                    <span data-i18n="nav.logout">Logout</span>
                  </button>
                </div>
              </aside>

              <!-- ===== MAIN ===== -->
              <main class="profile-main">

                <!-- PERSONAL INFORMATION -->
                <section class="panel">
                  <div class="panel-header">
                    <div class="panel-header-left">
                      <div class="panel-header-icon">
                        <i class="ri-user-line"></i>
                      </div>
                      <div>
                        <h2 data-i18n="profile.contactDetails">Contact Details</h2>
                        <p data-i18n="profile.personalSub">Your Basic Account Details</p>
                      </div>
                    </div>
                    <span class="panel-header-action" id="editPersonalBtn">
                      <i class="ri-pencil-line"></i>
                      <span data-i18n="profile.edit">Edit</span>
                    </span>
                  </div>

                  <div class="detail-list">
                    <div id="profileForm" class="profile-form" style="display: none">
                      <div class="form-group">
                        <label class="form-label" for="profileName">
                          <span data-i18n="profile.fullName">Full Name</span>
                        </label>
                        <input
                          type="text"
                          class="form-input"
                          id="profileName"
                          name="name"
                          value="{{ $user->name }}"
                          autocomplete="name"
                          maxlength="255"
                        />
                      </div>

                      <div class="form-group">
                        <label class="form-label" for="profileEmail">
                          <span data-i18n="profile.emailAddress">Email Address</span>
                        </label>
                        <input
                          type="email"
                          class="form-input"
                          id="profileEmail"
                          name="email"
                          value="{{ $user->email }}"
                          autocomplete="email"
                          maxlength="255"
                        />
                      </div>

                      <div class="form-group">
                        <label class="form-label" for="profileContact">
                          <span data-i18n="profile.contactNumber">Contact Number</span>
                          <span class="text-muted"> (Optional)</span>
                        </label>
                        <input
                          type="text"
                          class="form-input"
                          id="profileContact"
                          name="contact_number"
                          value="{{ old('contact_number', $user->contact_number) }}"
                          autocomplete="tel"
                          maxlength="64"
                        />
                      </div>

                      <div class="form-group">
                        <label class="form-label" for="profileAddress">
                          <span data-i18n="profile.address">Address</span>
                          <span class="text-muted"> (Optional)</span>
                        </label>
                        <textarea
                          class="form-input"
                          id="profileAddress"
                          name="address"
                          rows="3"
                          maxlength="500"
                        >{{ old('address', $user->address) }}</textarea>
                      </div>

                      <div class="profile-form-actions">
                        <button
                          class="btn btn-primary btn-block"
                          id="saveProfileBtn"
                          type="button"
                          data-confirm="Save these changes to your profile?"
                          data-confirm-title="Save profile changes"
                          data-confirm-note="Your name, email, contact number and address will be updated."
                          data-confirm-ok="Save changes"
                          data-confirm-icon="ri-save-line"
                        >
                          <i class="ri-save-line"></i>
                          <span data-i18n="profile.saveChanges">Save Changes</span>
                        </button>
                        <button
                          class="btn btn-secondary"
                          id="cancelProfileBtn"
                          type="button"
                        >
                          <i class="ri-close-line"></i>
                          <span data-i18n="profile.cancel">Cancel</span>
                        </button>
                      </div>
                    </div>

                    <div id="profileView" class="profile-view">
                      <div class="profile-view-field">
                        <span class="profile-view-label" data-i18n="profile.fullName">
                          Full name
                        </span>
                        <span class="profile-view-value" id="viewName">{{ $user->name }}</span>
                      </div>

                      <div class="profile-view-field">
                        <span class="profile-view-label" data-i18n="profile.emailAddress">
                          Email address
                        </span>
                        <span class="profile-view-value" id="viewEmail">{{ $user->email }}</span>
                      </div>

                      @if ($user->contact_number)
                        <div class="profile-view-field" id="viewContactField">
                          <span class="profile-view-label" data-i18n="profile.contactNumber">Contact Number</span>
                          <span class="profile-view-value" id="viewContact">{{ $user->contact_number }}</span>
                        </div>
                      @endif

                      @if ($user->address)
                        <div class="profile-view-field" id="viewAddressField">
                          <span class="profile-view-label" data-i18n="profile.address">Address</span>
                          <span class="profile-view-value" id="viewAddress">{{ $user->address }}</span>
                        </div>
                      @endif

                      <div class="profile-view-actions">
                        <button
                          class="btn btn-primary btn-block"
                          id="editProfileBtnView"
                          type="button"
                        >
                          <i class="ri-edit-line"></i>
                          <span data-i18n="profile.editProfile">Edit Profile</span>
                        </button>
                      </div>
                    </div>
                  </div>
                </section>

                <!-- ACCOUNT & SECURITY -->
                <section class="panel">
                  <div class="panel-header">
                    <div class="panel-header-left">
                      <div class="panel-header-icon">
                        <i class="ri-shield-keyhole-line"></i>
                      </div>
                      <div>
                        <h2 data-i18n="profile.accountSecurity">Account &amp; security</h2>
                        <p data-i18n="profile.accountSecuritySub">
                          Manage your account status
                        </p>
                      </div>
                    </div>
                  </div>

                  <div class="detail-list">
                    <div class="detail-row">
                      <div class="detail-label">
                        <i class="ri-briefcase-4-line"></i>
                        <span data-i18n="set.role">Role</span>
                      </div>
                      <div class="detail-value">{{ $user->roleLabel() }}</div>
                    </div>

                    <div class="detail-row">
                      <div class="detail-label">
                        <i class="ri-calendar-check-line"></i>
                        <span data-i18n="profile.memberSince">Member Since</span>
                      </div>
                      <div class="detail-value muted">
                        {{ $user->created_at ? $user->created_at->format('F j, Y') : '—' }}
                      </div>
                    </div>

                    <div class="detail-row">
                      <div class="detail-label">
                        <i class="ri-shield-check-line"></i>
                        <span data-i18n="profile.accountStatus">Account Status</span>
                      </div>
                      <div class="detail-value">
                        <span class="badge success">
                          <i class="ri-checkbox-circle-fill"></i>
                          <span data-i18n="profile.active">Active</span>
                        </span>
                      </div>
                    </div>

                    <div class="detail-row">
                      <div class="detail-label">
                        <i class="ri-lock-password-line"></i>
                        <span data-i18n="profile.password">Password</span>
                      </div>
                      <div class="detail-value">
                        <button
                          class="btn btn-secondary btn-sm"
                          id="profileChangePasswordBtn"
                          type="button"
                        >
                          <i class="ri-lock-password-line"></i>
                          <span data-i18n="profile.changePassword">Change Password</span>
                        </button>
                      </div>
                    </div>
                  </div>
                </section>

              </main>
            </div>
          </section>
