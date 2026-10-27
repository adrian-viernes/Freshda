      /* ================= NOTIFICATIONS ================= */
      /* The topbar bell jumps straight to the full notifications page; there
         is no slide-over panel any more. */
      var notifBtn = document.getElementById("notifBtn");
      var notifCountBadge = document.getElementById("notifCountBadge");
      var sidebarNotifBadge = document.getElementById("sidebarNotifBadge");
      var mobileNotifBadge = document.getElementById("mobileNotifBadge");

      var iconMap = {
        fresh: "ri-checkbox-circle-line",
        warning: "ri-alert-line",
        error: "ri-close-circle-line",
        info: "ri-information-line",
        success: "ri-check-double-line",
      };
      function getNotifIcon(type) {
        return iconMap[type] || "ri-notification-line";
      }

      function renderNotifPage() {
        var list = document.getElementById("notifPageList");
        var filtered = notifications.filter(function (n) {
          if (state.notifPageFilter === "all") return true;
          if (state.notifPageFilter === "unread") return n.unread;
          return n.category === state.notifPageFilter;
        });
        if (filtered.length === 0) {
          list.innerHTML =
            '<div class="notif-empty" style="padding:3rem 1.5rem;"><i class="ri-notification-off-line"></i>No notifications in this filter</div>';
          return;
        }
        list.innerHTML = "";
        filtered.forEach(function (n) {
          var item = document.createElement("div");
          item.className = "notif-page-item" + (n.unread ? " unread" : "");
          item.innerHTML =
            '<div class="notif-page-icon notif-icon ' +
            n.type +
            '"><i class="' +
            getNotifIcon(n.type) +
            '"></i></div>' +
            '<div class="notif-page-content">' +
            '<div class="notif-page-title">' +
            escapeHtml(n.title) +
            "</div>" +
            '<div class="notif-page-desc">' +
            escapeHtml(n.desc) +
            "</div>" +
            '<div class="notif-page-meta"><span><i class="ri-time-line"></i> ' +
            n.time +
            '</span><span class="notif-page-category">' +
            n.category +
            "</span></div>" +
            "</div>" +
            '<div class="notif-page-actions">' +
            '<button class="notif-page-action mark-read" title="Mark As Read"><i class="ri-check-line"></i></button>' +
            '<button class="notif-page-action delete" title="Delete"><i class="ri-delete-bin-line"></i></button>' +
            "</div>";
          item.addEventListener("click", function (e) {
            if (e.target.closest(".notif-page-actions")) return;
            markNotificationRead(
              notifications.find(function (x) {
                return x.id === n.id;
              }),
            );
            updateNotifCount();
            if (n.page) showPage(n.page);
            renderNotifPage();
          });
          item
            .querySelector(".notif-page-action.mark-read")
            .addEventListener("click", function (e) {
              e.stopPropagation();
              markNotificationRead(
                notifications.find(function (x) {
                  return x.id === n.id;
                }),
              );
              updateNotifCount();
              renderNotifPage();
              showToast("Marked As Read", "success");
            });
          item
            .querySelector(".notif-page-action.delete")
            .addEventListener("click", function (e) {
              e.stopPropagation();
              deleteNotification(n);
              updateNotifCount();
              renderNotifPage();
              showToast("Notification Deleted", "info");
            });
          list.appendChild(item);
        });
      }

      function markNotificationRead(notif) {
        if (!notif || !notif.unread) return;
        notif.unread = false;
        /* Local notifications were never saved server-side. */
        if (!notif.local) {
          apiRequest("POST", "/notifications/" + notif.id + "/read", null);
        }
      }

      function deleteNotification(notif) {
        notifications = notifications.filter(function (x) {
          return x.id !== notif.id;
        });
        if (!notif.local) {
          apiRequest("DELETE", "/notifications/" + notif.id, null, function (data) {
            if (typeof data.unread === "number") setBadges(data.unread);
          });
        }
      }

      function setBadges(unreadCount) {
        if (unreadCount > 0) {
          notifCountBadge.classList.remove("hidden");
          notifCountBadge.style.display = "flex";
          notifCountBadge.textContent = unreadCount;
        } else {
          notifCountBadge.classList.add("hidden");
          notifCountBadge.style.display = "none";
        }
        if (sidebarNotifBadge) {
          if (unreadCount > 0) {
            sidebarNotifBadge.classList.remove("hidden");
            sidebarNotifBadge.textContent = unreadCount;
            sidebarNotifBadge.style.display = "inline-block";
          } else {
            sidebarNotifBadge.classList.add("hidden");
            sidebarNotifBadge.style.display = "none";
          }
        }
        if (mobileNotifBadge) {
          if (unreadCount > 0) {
            mobileNotifBadge.textContent = unreadCount;
            mobileNotifBadge.style.display = "block";
          } else {
            mobileNotifBadge.style.display = "none";
          }
        }
      }
      function updateNotifCount() {
        var unreadCount = notifications.filter(function (n) {
          return n.unread;
        }).length;
        setBadges(unreadCount);
      }

      function refreshNotifCount() {
        fetch("/notifications/count", {
          headers: { "Accept": "application/json" },
        })
          .then(function (r) {
            return r.json().catch(function () {
              return {};
            });
          })
          .then(function (data) {
            if (typeof data.unread === "number") setBadges(data.unread);
          })
          .catch(function () {});
      }

      notifBtn.addEventListener("click", function () {
        showPage("notifications");
      });

      var notifPageFilters = document.querySelectorAll(
        "#notifPageFilters .filter-chip-outline",
      );
      notifPageFilters.forEach(function (chip) {
        chip.addEventListener("click", function () {
          notifPageFilters.forEach(function (c) {
            c.classList.remove("active");
          });
          this.classList.add("active");
          state.notifPageFilter = this.dataset.filter;
          renderNotifPage();
        });
      });

      document
        .getElementById("notifPageMarkReadBtn")
        .addEventListener("click", function () {
          apiRequest("POST", "/notifications/read-all", null);
          notifications.forEach(function (n) {
            n.unread = false;
          });
          updateNotifCount();
          renderNotifPage();
          showToast("All Notifications Marked As Read", "success");
        });

      document
        .getElementById("notifPageClearAllBtn")
        .addEventListener("click", function () {
          if (confirm("Delete All Notifications?")) {
            apiRequest("POST", "/notifications/clear-all", null, function (data) {
              if (typeof data.unread === "number") setBadges(data.unread);
            });
            notifications = [];
            updateNotifCount();
            renderNotifPage();
            showToast("All Notifications Cleared", "info");
          }
        });

      function pushNotification(type, title, desc, category, page) {
        notifications.unshift({
          /* Local rows have no server record, so read/delete stay in memory. */
          local: true,
          id: Date.now(),
          type: type,
          title: title,
          desc: desc,
          time: "Just Now",
          unread: true,
          category: category || "system",
          page: page || null,
        });
        updateNotifCount();
        if (state.currentPage === "notifications") renderNotifPage();
      }

      updateNotifCount();
      renderNotifPage();

      var settingsBtn = document.getElementById("settingsBtn");
      if (settingsBtn) {
        settingsBtn.addEventListener("click", function (e) {
          e.stopPropagation();
          closeTopbarUserDropdown();
          showPage("settings");
        });
      }

      /* The topbar gear and the sidebar entry both open the full settings page;
         the old slide-over panel was removed because nothing ever opened it. */

