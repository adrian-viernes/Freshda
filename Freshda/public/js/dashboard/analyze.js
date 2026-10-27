      /* ================= ANALYZE ================= */
      document
        .getElementById("analyzeBtn")
        .addEventListener("click", function () {
          if (state.analysisInProgress) return;
          var file =
            state.capturedFile || uploadFile.files[0] || scanCameraInput.files[0];
          if (!file) {
            showToast("Please Upload Or Capture a Fish Image First", "error");
            return;
          }
          stopCameraStream();
          state.analysisInProgress = true;
          var btn = this;
          var originalHtml = btn.innerHTML;
          btn.disabled = true;
          btn.innerHTML =
            '<i class="ri-loader-4-line" style="animation:spin 1s linear infinite;"></i> Analyzing...';
          if (!document.getElementById("spinStyle")) {
            var s = document.createElement("style");
            s.id = "spinStyle";
            s.textContent =
              "@keyframes spin { to { transform: rotate(360deg); } }";
            document.head.appendChild(s);
          }
          var form = new FormData();
          if (file) form.append("image", file);
          fetch("/scans", {
            method: "POST",
            headers: {
              "X-CSRF-TOKEN": csrfToken(),
              "Accept": "application/json",
            },
            body: form,
          })
            .then(function (r) {
              return r.json().catch(function () {
                return {};
              });
            })
            .then(function (result) {
              state.analysisInProgress = false;
              btn.disabled = false;
              btn.innerHTML = originalHtml;
              if (!result.id) {
                showToast(
                  (result && result.errors && Object.keys(result.errors).length
                    ? "Please Choose a Valid Fish Image"
                    : result.message) || "Scan Failed — Please Try Again",
                  "error",
                );
                return;
              }
              addRecentScan(result);
              addHistoryRow(result);
              pushNotification(
                result.cls === "fresh"
                  ? "success"
                  : result.cls === "moderate"
                    ? "warning"
                    : "error",
                "Scan Complete — " + result.name,
                result.name +
                  " assessed as " +
                  result.freshness +
                  " (" +
                  result.freshness_percentage +
                  "%, " +
                  (result.source === "ai" ? "AI" : "simulated") +
                  ").",
                "scans",
                "history",
              );
              refreshNotifCount();
              showAnalysisResult(result);
            })
            .catch(function () {
              state.analysisInProgress = false;
              btn.disabled = false;
              btn.innerHTML = originalHtml;
              showToast("Network Error — Please Try Again", "error");
            });
        });

      function addRecentScan(result) {
        var list = document.getElementById("recentScansList");
        var item = document.createElement("div");
        item.className = "scan-item";
        item.innerHTML =
          '<div class="scan-thumb ' +
          result.cls +
          '"><i class="ri-image-line"></i></div>' +
          '<div class="scan-info"><div class="scan-name">' +
          escapeHtml(result.name) +
          "</div>" +
          '<div class="scan-meta"><span>' +
          escapeHtml(result.species_label) +
          '</span><span class="sep">•</span><span>Just now</span></div></div>' +
          '<span class="badge ' +
          result.cls +
          '">' +
          result.freshness_percentage +
          "%</span>";
        list.insertBefore(item, list.firstChild);
        var total = document.getElementById("statTotal");
        total.textContent = parseInt(total.textContent, 10) + 1;
        if (result.cls === "fresh") {
          var f = document.getElementById("statFresh");
          f.textContent = parseInt(f.textContent, 10) + 1;
        } else if (result.cls === "moderate") {
          var m = document.getElementById("statModerate");
          m.textContent = parseInt(m.textContent, 10) + 1;
        } else {
          var n = document.getElementById("statNotFresh");
          n.textContent = parseInt(n.textContent, 10) + 1;
        }
      }

      /* Confidence bands: high ≥ 0.80, moderate ≥ 0.60, low below that —
         the same thresholds the server applies for scan.confidence_band. */
      function confidenceBand(value) {
        var n = Number(value);
        if (!isFinite(n)) return "low";
        if (n > 1) n = n / 100;
        return n >= 0.8 ? "high" : n >= 0.6 ? "moderate" : "low";
      }

      function bandText(band, long) {
        var text =
          band === "high" ? "High" : band === "moderate" ? "Moderate" : "Low";
        return long ? text + " confidence" : text;
      }

      function addHistoryRow(result) {
        var tbody = document.getElementById("historyBody");
        if (!tbody) return;
        var tr = document.createElement("tr");
        tr.setAttribute("data-freshness", result.cls);
        tr.setAttribute(
          "data-search",
          (result.name + " " + result.species_label + " " + result.freshness).toLowerCase(),
        );
        tr.setAttribute("data-name", result.name);
        tr.setAttribute("data-species", result.species_label);
        tr.setAttribute("data-latin", result.species_label);
        tr.setAttribute("data-freshness-label", result.freshness);
        tr.setAttribute("data-percentage", result.freshness_percentage);
        tr.setAttribute("data-confidence", result.species_confidence);
        tr.setAttribute(
          "data-confidence-band",
          bandText(
            result.confidence_band ||
              confidenceBand(result.species_confidence),
            false,
          ),
        );
        tr.setAttribute("data-type", "Single");
        tr.setAttribute("data-source", "Single Scan");
        tr.setAttribute("data-image", result.image || "");
        tr.setAttribute("data-time", "Just now");
        tr.setAttribute("data-ts", String(Date.now() / 1000));
        tr.innerHTML =
          '<td><div class="td-with-icon"><i class="ri-image-line"></i><span>' +
          escapeHtml(result.name) +
          "</span></div></td>" +
          "<td><span class=\"mobile-label\">Species</span>" +
          escapeHtml(result.species_label) +
          "</td>" +
          '<td><span class="mobile-label">Freshness</span><span class="badge ' +
          result.cls +
          '">' +
          escapeHtml(result.freshness) +
          " · " +
          result.freshness_percentage +
          "%</span></td>" +
          '<td><span class="mobile-label">Confidence</span>' +
          (Number(result.species_confidence) || 0).toFixed(2) +
          " · " +
          bandText(
            result.confidence_band ||
              confidenceBand(result.species_confidence),
            false,
          ) +
          "</td>" +
          '<td><span class="mobile-label">Date</span>Just now</td>' +
          '<td><span class="mobile-label">Type</span>Single</td>';
        tbody.insertBefore(tr, tbody.firstChild);
      }

      function showAnalysisResult(result) {
        var modal = document.getElementById("modalOverlay");
        document.getElementById("modalTitle").textContent = "Analysis Complete";
        document.getElementById("modalBody").innerHTML =
          '<div style="text-align:center; margin-bottom:1rem;">' +
          '<div class="stat-icon ' +
          result.cls +
          '" style="width:3.5rem; height:3.5rem; font-size:1.6rem; margin:0 auto 0.7rem;"><i class="ri-check-line"></i></div>' +
          '<h2 style="font-size:1.15rem;">' +
          escapeHtml(result.name) +
          "</h2>" +
          '<p class="text-muted" style="font-style:italic;">' +
          escapeHtml(result.species_label) +
          "</p>" +
          "</div>" +
          '<div style="display:flex; justify-content:space-around; padding:0.9rem 0; border-top:1px solid var(--border); border-bottom:1px solid var(--border); margin:0.7rem 0;">' +
          '<div style="text-align:center;"><div style="font-size:0.7rem; color:var(--muted); text-transform:uppercase; font-weight:600;">Freshness</div><div style="font-weight:700; color:var(--primary-deep); margin-top:0.25rem;">' +
          escapeHtml(result.freshness) +
          "</div></div>" +
          '<div style="text-align:center;"><div style="font-size:0.7rem; color:var(--muted); text-transform:uppercase; font-weight:600;">Confidence</div><div style="font-weight:700; color:var(--primary-deep); margin-top:0.25rem;">' +
          result.freshness_percentage +
          '%</div><div style="font-size:0.68rem; color:var(--muted); margin-top:0.2rem;">' +
          bandText(
            confidenceBand(
              result.freshness_confidence != null
                ? result.freshness_confidence
                : result.freshness_percentage / 100,
            ),
            true,
          ) +
          "</div></div>" +
          '<div style="text-align:center;"><div style="font-size:0.7rem; color:var(--muted); text-transform:uppercase; font-weight:600;">Source</div><div style="font-weight:700; margin-top:0.25rem;">' +
          (result.source === "ai" ? "AI" : "Simulated") +
          "</div></div>" +
          "</div>";
        var footer = document.getElementById("modalFooter");
        footer.innerHTML = "";
        var closeBtn = document.createElement("button");
        closeBtn.className = "btn btn-secondary";
        closeBtn.textContent = "Close";
        closeBtn.addEventListener("click", function () {
          modal.classList.remove("open");
        });
        var historyBtn = document.createElement("button");
        historyBtn.className = "btn btn-primary";
        historyBtn.innerHTML = '<i class="ri-history-line"></i> View History';
        historyBtn.addEventListener("click", function () {
          modal.classList.remove("open");
          showPage("history");
        });
        footer.appendChild(closeBtn);
        footer.appendChild(historyBtn);
        modal.classList.add("open");
        showToast("Analysis complete: " + result.name, "success");
      }

      document
        .getElementById("modalClose")
        .addEventListener("click", function () {
          document.getElementById("modalOverlay").classList.remove("open");
        });
      document
        .getElementById("modalOverlay")
        .addEventListener("click", function (e) {
          if (e.target === this) this.classList.remove("open");
        });

