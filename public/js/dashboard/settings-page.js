      /* ================= SETTINGS PAGE EXTRAS ================= */
      var resetPreferencesBtn = document.getElementById("resetPreferencesBtn");
      if (resetPreferencesBtn) {
        resetPreferencesBtn.addEventListener("click", function () {
          if (!confirm("Reset All Preferences On This Device?")) return;
          resetPreferences();
          showToast("Preferences Reset To Defaults", "success");
        });
      }

      var settingsCacheName = document.getElementById("settingsCacheName");
      if (settingsCacheName && window.caches) {
        caches
          .keys()
          .then(function (keys) {
            if (keys && keys.length)
              settingsCacheName.textContent = keys.join(", ");
          })
          .catch(function () {});
      }

      var settingsCameraSupport = document.getElementById(
        "settingsCameraSupport",
      );
      if (settingsCameraSupport) {
        if (!cameraSupported()) {
          settingsCameraSupport.textContent = "Not Supported On This Browser";
        } else if (!window.isSecureContext) {
          settingsCameraSupport.textContent = "Needs HTTPS Or Localhost";
        } else {
          settingsCameraSupport.textContent = "Live Camera Available";
        }
      }

