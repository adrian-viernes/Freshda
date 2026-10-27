      /* ================= INIT ================= */
      Object.keys(DEFAULT_SETTINGS).forEach(applySetting);
      syncSettingControls();

      /* The header chip says "Today", so it carries the date of the device
         reading it: the server clock is UTC, which is still yesterday for a
         vendor whose local day has already rolled over. The blade renders a
         UTC fallback for the first paint, this makes it local. */
      var todayDateEl = document.getElementById("todayDate");
      if (todayDateEl) {
        todayDateEl.textContent = new Date().toLocaleDateString(undefined, {
          weekday: "short",
          month: "short",
          day: "numeric",
          year: "numeric",
        });
      }

      showPage("dashboard");

      document.addEventListener("keydown", function (e) {
        if (e.key === "Escape") {
          document.getElementById("modalOverlay").classList.remove("open");
          searchResults.classList.remove("open");
          closeTopbarUserDropdown();
          sidebarLinks.forEach(function (link) {
            link.classList.toggle(
              "active",
              link.dataset.page === state.currentPage,
            );
          });
        }
        if (
          e.key === "/" &&
          e.target.tagName !== "INPUT" &&
          e.target.tagName !== "TEXTAREA"
        ) {
          e.preventDefault();
          globalSearch.focus();
        }
      });

      console.log(
        "%cFresh-DA Loaded ✓",
        "color:#0284C7; font-weight:bold; font-size:14px;",
      );
