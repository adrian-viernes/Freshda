      /* ================= SECTION REFS ================= */
      var sections = {};
      [
        "dashboard",
        "notifications",
        "scan",
        "batch",
        "history",
        "inventory",
        "suppliers",
        "deliveries",
        "reports",
        "profile",
        "settings",
        "logout",
      ].forEach(function (id) {
        sections[id] = document.getElementById(id + "-section");
      });

      var sidebarLinks = document.querySelectorAll("#sidebarNav a[data-page]");
      var mobileItems = document.querySelectorAll(".mobile-nav-item");

      function showPage(pageId) {
        Object.keys(sections).forEach(function (key) {
          if (sections[key]) sections[key].classList.add("hidden");
        });

        if (sections[pageId]) {
          sections[pageId].classList.remove("hidden");
        } else {
          sections.dashboard.classList.remove("hidden");
          pageId = "dashboard";
        }

        state.currentPage = pageId;

        sidebarLinks.forEach(function (link) {
          link.classList.toggle("active", link.dataset.page === pageId);
        });
        mobileItems.forEach(function (item) {
          var target = item.dataset.page;
          if (target === "more") {
            item.classList.toggle(
              "active",
              ["dashboard", "scan", "batch", "history", "inventory"].indexOf(
                pageId,
              ) === -1,
            );
          } else {
            item.classList.toggle("active", target === pageId);
          }
        });
        if (typeof closeTopbarUserDropdown === "function")
          closeTopbarUserDropdown();
        window.scrollTo({ top: 0, behavior: "smooth" });
        document.getElementById("searchResults").classList.remove("open");

        if (pageId === "notifications") renderNotifPage();
        if (pageId === "batch") renderBatch();
        if (pageId !== "scan") stopCameraStream();
        if (pageId !== "batch" && batchCamera) batchCamera.stop();
      }

      sidebarLinks.forEach(function (link) {
        link.addEventListener("click", function (e) {
          e.preventDefault();
          var page = link.dataset.page;
          if (page === "logout") {
            confirmLogout();
            return;
          }
          if (page) showPage(page);
        });
      });

      mobileItems.forEach(function (item) {
        item.addEventListener("click", function (e) {
          e.preventDefault();
          var page = item.dataset.page;
          if (page) showPage(page);
        });
      });

      document.querySelectorAll("[data-page]").forEach(function (el) {
        if (el.closest("#sidebarNav") || el.closest(".mobile-bottom-nav"))
          return;
        el.addEventListener("click", function (e) {
          e.preventDefault();
          var page = el.dataset.page;
          if (page === "logout") {
            confirmLogout();
            return;
          }
          if (page) showPage(page);
        });
      });

      /* ================= PHONE SIDEBAR DRAWER =================
         Below 900px the sidebar slides off-canvas and only the topbar
         hamburger can bring it back. */
      var sidebarDrawer = document.querySelector(".sidebar");
      var sidebarToggleBtn = document.getElementById("sidebarToggle");
      var sidebarBackdrop = document.getElementById("sidebarBackdrop");

      function closeSidebarDrawer() {
        if (sidebarDrawer) sidebarDrawer.classList.remove("open");
        if (sidebarBackdrop) sidebarBackdrop.classList.remove("open");
        if (sidebarToggleBtn) {
          var drawerIcon = sidebarToggleBtn.querySelector("i");
          if (drawerIcon) drawerIcon.className = "ri-menu-line";
        }
      }

      if (sidebarDrawer && sidebarToggleBtn) {
        sidebarToggleBtn.addEventListener("click", function () {
          var open = sidebarDrawer.classList.toggle("open");
          if (sidebarBackdrop) sidebarBackdrop.classList.toggle("open", open);
          var toggleIcon = sidebarToggleBtn.querySelector("i");
          if (toggleIcon) toggleIcon.className = open ? "ri-close-line" : "ri-menu-line";
        });

        if (sidebarBackdrop) {
          sidebarBackdrop.addEventListener("click", closeSidebarDrawer);
        }

        /* Picking a page from the drawer should reveal the page it opened. */
        sidebarLinks.forEach(function (link) {
          link.addEventListener("click", closeSidebarDrawer);
        });

        document.addEventListener("keydown", function (e) {
          if (e.key === "Escape") closeSidebarDrawer();
        });
      }

