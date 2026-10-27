      /* ================= GLOBAL SEARCH ================= */
      var globalSearch = document.getElementById("globalSearch");
      var searchResults = document.getElementById("searchResults");
      var searchBtn = document.getElementById("searchBtn");

      var searchableItems = [
        {
          name: "Milkfish",
          meta: "Chanos Chanos · Fresh 94%",
          page: "history",
          icon: "ri-image-line",
        },
        {
          name: "Tilapia",
          meta: "Oreochromis Niloticus · Moderate 78%",
          page: "history",
          icon: "ri-image-line",
        },
        {
          name: "Salmon",
          meta: "Salmo Salar · Fresh 97%",
          page: "history",
          icon: "ri-image-line",
        },
        {
          name: "Mackerel",
          meta: "Scomber Scombrus · Not Fresh 45%",
          page: "history",
          icon: "ri-image-line",
        },
        {
          name: "Inventory",
          meta: "Manage Fish Stock",
          page: "inventory",
          icon: "ri-store-2-line",
        },
        {
          name: "Suppliers",
          meta: "Manage Your Fish Suppliers",
          page: "suppliers",
          icon: "ri-team-line",
        },
        {
          name: "Supplier Deliveries",
          meta: "Record Incoming Fish Deliveries",
          page: "deliveries",
          icon: "ri-truck-line",
        },
        {
          name: "Scan Fish",
          meta: "Capture Or Upload Image",
          page: "scan",
          icon: "ri-camera-lens-line",
        },
        {
          name: "Batch Sampling",
          meta: "Capture 3–5 Fish Photos To Analyze",
          page: "batch",
          icon: "ri-stack-line",
        },
        {
          name: "Notifications",
          meta: "View All Alerts",
          page: "notifications",
          icon: "ri-notification-3-line",
        },
        {
          name: "Settings",
          meta: "Customize App",
          page: "settings",
          icon: "ri-settings-3-line",
        },
        {
          name: "Reports",
          meta: "Export Reports",
          page: "reports",
          icon: "ri-file-chart-line",
        },
        {
          name: "Profile",
          meta: "Manage Account",
          page: "profile",
          icon: "ri-user-settings-line",
        },
      ];

      function performSearch(query) {
        query = query.trim().toLowerCase();
        searchResults.innerHTML = "";
        if (!query) {
          searchResults.classList.remove("open");
          return;
        }
        var matches = searchableItems.filter(function (item) {
          return (
            item.name.toLowerCase().includes(query) ||
            item.meta.toLowerCase().includes(query)
          );
        });
        if (matches.length === 0) {
          searchResults.innerHTML =
            '<div class="search-empty">No results for "' +
            escapeHtml(query) +
            '"</div>';
          searchResults.classList.add("open");
          return;
        }
        matches.forEach(function (item) {
          var div = document.createElement("div");
          div.className = "search-result-item";
          div.innerHTML =
            '<i class="' +
            item.icon +
            '"></i><span>' +
            item.name +
            '</span><span class="result-meta">' +
            item.meta +
            "</span>";
          div.addEventListener("click", function () {
            showPage(item.page);
            globalSearch.value = "";
            searchResults.classList.remove("open");
          });
          searchResults.appendChild(div);
        });
        searchResults.classList.add("open");
      }

      globalSearch.addEventListener("input", function () {
        performSearch(this.value);
      });
      globalSearch.addEventListener("focus", function () {
        if (this.value.trim()) performSearch(this.value);
      });
      searchBtn.addEventListener("click", function () {
        if (globalSearch.value.trim()) performSearch(globalSearch.value);
        else showToast("Please Enter a Search Term", "error");
      });
      globalSearch.addEventListener("keydown", function (e) {
        if (e.key === "Enter") {
          e.preventDefault();
          searchBtn.click();
        }
      });
      document.addEventListener("click", function (e) {
        if (
          !searchResults.contains(e.target) &&
          !globalSearch.contains(e.target) &&
          !searchBtn.contains(e.target)
        ) {
          searchResults.classList.remove("open");
        }
      });

