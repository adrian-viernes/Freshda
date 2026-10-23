          <!-- ============ NOTIFICATIONS PAGE ============ -->
          <section id="notifications-section" class="page-section hidden">
            <div class="page-header">
              <h1 data-i18n="page.notifications.title">Notifications</h1>
              <p class="text-muted" data-i18n="page.notifications.sub">
                All your alerts, scan updates, and system messages
              </p>
              <div class="page-header-actions">
                <button
                  class="btn btn-secondary"
                  id="notifPageMarkReadBtn"
                  data-confirm="Mark every notification as read?"
                  data-confirm-title="Mark all as read"
                  data-confirm-note="All unread alerts will be cleared from your badge count."
                  data-confirm-ok="Mark all read"
                  data-confirm-icon="ri-check-double-line"
                >
                  <i class="ri-check-double-line"></i> Mark all as read
                </button>
                <button
                  class="btn btn-secondary"
                  id="notifPageClearAllBtn"
                  data-confirm="Clear all notifications?"
                  data-confirm-title="Clear notifications"
                  data-confirm-note="Every notification in the list will be deleted."
                  data-confirm-ok="Clear all"
                  data-confirm-icon="ri-delete-bin-line"
                  data-confirm-danger="1"
                >
                  <i class="ri-delete-bin-line"></i> Clear all
                </button>
              </div>
            </div>

            <div class="notif-page-filters" id="notifPageFilters">
              <span class="filter-chip-outline active" data-filter="all">
                <i class="ri-filter-3-line"></i> All
              </span>
              <span class="filter-chip-outline" data-filter="unread"
                >Unread</span
              >
              <span class="filter-chip-outline" data-filter="scans">Scans</span>
              <span class="filter-chip-outline" data-filter="system"
                >System</span
              >
            </div>

            <div class="notif-page-list" id="notifPageList"></div>
          </section>
