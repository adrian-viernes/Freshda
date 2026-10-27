      /* ================= TOPBAR PROFILE DROPDOWN ================= */
      var topbarUserBtn = document.getElementById("topbarUserBtn");
      var topbarUserDropdown = document.getElementById("topbarUserDropdown");

      function closeTopbarUserDropdown() {
        if (!topbarUserDropdown) return;
        topbarUserDropdown.classList.add("hidden");
        if (topbarUserBtn) {
          topbarUserBtn.classList.remove("active");
          topbarUserBtn.setAttribute("aria-expanded", "false");
        }
      }

      if (topbarUserBtn && topbarUserDropdown) {
        topbarUserBtn.addEventListener("click", function (e) {
          e.stopPropagation();
          var willOpen = topbarUserDropdown.classList.contains("hidden");
          topbarUserDropdown.classList.toggle("hidden", !willOpen);
          topbarUserBtn.classList.toggle("active", willOpen);
          topbarUserBtn.setAttribute("aria-expanded", willOpen ? "true" : "false");
          if (willOpen) {
            document.getElementById("searchResults").classList.remove("open");
          }
        });

        /* Clicking a menu entry goes straight to that destination. */
        topbarUserDropdown.querySelectorAll("[data-page]").forEach(function (item) {
          item.addEventListener("click", function () {
            closeTopbarUserDropdown();
          });
        });

        document.addEventListener("click", function (e) {
          if (
            !topbarUserDropdown.contains(e.target) &&
            !topbarUserBtn.contains(e.target)
          ) {
            closeTopbarUserDropdown();
          }
        });
      }

