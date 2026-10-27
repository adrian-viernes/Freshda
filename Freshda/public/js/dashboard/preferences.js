      /* ================= PREFERENCES ================= */
      var SETTINGS_KEY = "freshda-settings";

      var DEFAULT_SETTINGS = {
        darkMode: false,
        language: "en",
      };

      /* Capture presets used by the camera snapshot. */
      var QUALITY_PRESETS = {
        high: { maxWidth: 1600, jpeg: 0.92 },
        balanced: { maxWidth: 1280, jpeg: 0.85 },
        small: { maxWidth: 960, jpeg: 0.7 },
      };

      function readJson(key) {
        try {
          return JSON.parse(localStorage.getItem(key) || "{}") || {};
        } catch (e) {
          return {};
        }
      }

      function defaultSettings() {
        var copy = {};
        Object.keys(DEFAULT_SETTINGS).forEach(function (key) {
          copy[key] = DEFAULT_SETTINGS[key];
        });
        return copy;
      }

      function loadSettings() {
        var saved = readJson(SETTINGS_KEY);
        var loaded = defaultSettings();
        Object.keys(DEFAULT_SETTINGS).forEach(function (key) {
          if (saved[key] !== undefined) loaded[key] = saved[key];
        });
        /* The landing and login pages write the shared flag directly, so it
           wins over whatever the settings blob last recorded — otherwise the
           three pages drift apart as soon as one of them is toggled. */
        try {
          var sharedFlag = localStorage.getItem("freshda-dark");
          if (sharedFlag !== null) loaded.darkMode = sharedFlag === "1";
        } catch (e) {}
        return loaded;
      }

      var settings = loadSettings();

      /* Write the flag back so the other pages open with this theme too. */
      try {
        localStorage.setItem("freshda-dark", settings.darkMode ? "1" : "0");
      } catch (e) {}

      function saveSettings() {
        try {
          localStorage.setItem(SETTINGS_KEY, JSON.stringify(settings));
          localStorage.setItem("freshda-dark", settings.darkMode ? "1" : "0");
        } catch (e) {}
      }

