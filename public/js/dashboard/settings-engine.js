      /* ================= SETTINGS ENGINE ================= */
      function syncSettingControls() {
        document
          .querySelectorAll("[data-setting-checkbox]")
          .forEach(function (el) {
            var key = el.getAttribute("data-setting-checkbox");
            if (settings[key] !== undefined) el.checked = !!settings[key];
          });
        document
          .querySelectorAll("[data-setting-select]")
          .forEach(function (el) {
            var key = el.getAttribute("data-setting-select");
            if (settings[key] !== undefined) el.value = String(settings[key]);
          });
        document
          .querySelectorAll("[data-setting-toggle]")
          .forEach(function (el) {
            var key = el.getAttribute("data-setting-toggle");
            var icon = el.querySelector("i");
            if (icon && key === "darkMode")
              icon.className = settings.darkMode
                ? "ri-sun-line"
                : "ri-moon-line";
          });
      }

      function applySetting(key) {
        if (key === "darkMode") {
          /* One shared dark-mode preference: the layout already painted with
             it, this only keeps the body class in step when it changes. */
          state.darkMode = !!settings.darkMode;
          document.body.classList.toggle("dark", !!settings.darkMode);
        }
        if (key === "language") applyLanguage(settings.language);
      }

      function setSetting(key, value) {
        if (settings[key] === value) {
          syncSettingControls();
          return;
        }
        settings[key] = value;
        saveSettings();
        applySetting(key);
        syncSettingControls();

      }

      function resetPreferences() {
        try {
          localStorage.removeItem(SETTINGS_KEY);
          localStorage.removeItem("freshda-dark");
        } catch (e) {}
        settings = defaultSettings();
        Object.keys(DEFAULT_SETTINGS).forEach(applySetting);
        syncSettingControls();
      }

      document.addEventListener("change", function (e) {
        var el = e.target;
        if (!el || typeof el.getAttribute !== "function") return;
        var key =
          el.getAttribute("data-setting-checkbox") ||
          el.getAttribute("data-setting-select");
        if (!key) return;
        setSetting(key, el.type === "checkbox" ? el.checked : el.value);
      });

      document.querySelectorAll("[data-setting-toggle]").forEach(function (el) {
        el.addEventListener("click", function (e) {
          e.preventDefault();
          var key = el.getAttribute("data-setting-toggle");
          if (!key) return;
          setSetting(key, !settings[key]);
        });
      });

