      /* ================= HISTORY FILTERS & SEARCH ================= */
      var historyFilters = document.querySelectorAll(
        "#historyFilters .filter-chip-outline",
      );
      var historySearch = document.getElementById("historySearch");

      function applyHistoryFilters() {
        var rows = document.querySelectorAll("#historyBody tr");
        var searchTerm = historySearch
          ? historySearch.value.trim().toLowerCase()
          : "";
        var filter = state.historyFilter || "all";
        var visibleCount = 0;
        rows.forEach(function (row) {
          var freshnessMatch =
            filter === "all" || row.dataset.freshness === filter;
          var searchMatch =
            !searchTerm ||
            (row.getAttribute("data-search") || "").toLowerCase().indexOf(searchTerm) !== -1;
          var show = freshnessMatch && searchMatch;
          row.style.display = show ? "" : "none";
          if (show) visibleCount++;
        });
        return visibleCount;
      }

      historyFilters.forEach(function (chip) {
        chip.addEventListener("click", function () {
          historyFilters.forEach(function (c) {
            c.classList.remove("active");
          });
          this.classList.add("active");
          state.historyFilter = this.dataset.filter;
          var count = applyHistoryFilters();
          showToast(
            "Showing " +
              count +
              " result" +
              (count !== 1 ? "s" : ""),
            "info",
          );
        });
      });

      if (historySearch) {
        historySearch.addEventListener("input", function () {
          applyHistoryFilters();
        });
      }

      /* ================= HISTORY SORT & SCAN DETAIL ================= */
      var historySort = document.getElementById("historySort");

      if (historySort) {
        historySort.addEventListener("change", function () {
          var tbody = document.getElementById("historyBody");
          if (!tbody) return;

          var rows = Array.prototype.slice.call(
            tbody.querySelectorAll("tr[data-name]"),
          );
          var mode = historySort.value;

          rows.sort(function (a, b) {
            var tsA = parseFloat(a.getAttribute("data-ts")) || 0;
            var tsB = parseFloat(b.getAttribute("data-ts")) || 0;
            var pctA = parseFloat(a.getAttribute("data-percentage")) || 0;
            var pctB = parseFloat(b.getAttribute("data-percentage")) || 0;

            if (mode === "oldest") return tsA - tsB;
            if (mode === "fresh-desc") return pctB - pctA;
            if (mode === "fresh-asc") return pctA - pctB;
            return tsB - tsA;
          });

          rows.forEach(function (row) {
            tbody.appendChild(row);
          });
        });
      }

      /* Clicking a history row opens its full result card. */
      var historyBodyEl = document.getElementById("historyBody");
      if (historyBodyEl) {
        historyBodyEl.addEventListener("click", function (e) {
          var row = e.target.closest("tr[data-name]");
          if (!row) return;
          openScanDetail(row);
        });
      }

      function openScanDetail(row) {
        var image = row.getAttribute("data-image");

        document.getElementById("modalTitle").textContent =
          row.getAttribute("data-name") + " — Scan Detail";
        document.getElementById("modalBody").innerHTML =
          (image
            ? '<img src="' +
              image +
              '" alt="Scan photo" style="width:100%; max-height:220px; object-fit:cover; border-radius:12px; margin-bottom:1rem">'
            : "") +
          '<div class="detail-grid">' +
          detailItem(
            "Species",
            row.getAttribute("data-latin") ||
              row.getAttribute("data-species") ||
              "—",
          ) +
          detailItem(
            "Freshness",
            row.getAttribute("data-freshness-label") +
              " · " +
              row.getAttribute("data-percentage") +
              "%",
          ) +
          detailItem(
            "Confidence",
            row.getAttribute("data-confidence") +
              " · " +
              (row.getAttribute("data-confidence-band") || ""),
          ) +
          detailItem(
            "Source",
            row.getAttribute("data-source") ||
              row.getAttribute("data-type") ||
              "—",
          ) +
          detailItem("Date", row.getAttribute("data-time") || "—") +
          "</div>";

        var footer = document.getElementById("modalFooter");
        footer.innerHTML = "";
        var close = document.createElement("button");
        close.className = "btn btn-secondary";
        close.textContent = "Close";
        close.addEventListener("click", function () {
          document.getElementById("modalOverlay").classList.remove("open");
        });
        footer.appendChild(close);
        document.getElementById("modalOverlay").classList.add("open");
      }

      /* ================= HISTORY EXPORT (CSV / PDF) ================= */
      var exportCsvBtn = document.getElementById("exportCsvBtn");
      var exportPdfBtn = document.getElementById("exportPdfBtn");

      function activeHistoryFilter() {
        return state.historyFilter || "all";
      }

      function exportEndpoint(button, fallback) {
        return button.getAttribute("data-url") || fallback;
      }

      function fitText(value, max) {
        var str = String(value == null ? "" : value);
        return str.length > max ? str.substring(0, max - 1) + "…" : str;
      }

      /* How many scan records the table actually holds right now. */
      function historyRecordCount() {
        var body = document.getElementById("historyBody");
        return body ? body.querySelectorAll("tr[data-name]").length : 0;
      }

      /*
       * Validate first (nothing to export?), then ask for confirmation, so the
       * user always knows what the export button is about to do.
       */
      function runHistoryExport(format, onProceed) {
        if (historyRecordCount() === 0) {
          showToast("Nothing To Export Yet — Run a Scan First.", "error");
          return;
        }
        var filter = activeHistoryFilter();
        var scope =
          filter === "all"
            ? "All Of Your Scan Records"
            : 'The Scan Records Filtered As "' + filter + '"';
        confirmAction({
          title: "Export " + format,
          icon: format === "PDF" ? "ri-file-pdf-2-line" : "ri-file-excel-2-line",
          message: "Generate And Download the " + format + " of " + scope + "?",
          note: "The Report Is Built From Your Saved Scan History And Downloaded To This Device.",
          confirmText: "Export " + format,
          confirmIcon: "ri-download-2-line",
          onConfirm: onProceed,
        });
      }

      if (exportCsvBtn) {
        exportCsvBtn.addEventListener("click", function () {
          runHistoryExport("CSV", function () {
            var url = exportEndpoint(exportCsvBtn, "/history/export/csv");
            window.location.href =
              url + "?filter=" + encodeURIComponent(activeHistoryFilter());
          });
        });
      }

      if (exportPdfBtn) {
        exportPdfBtn.addEventListener("click", function () {
          runHistoryExport("PDF", function () {
          var btn = exportPdfBtn;
          var url = exportEndpoint(exportPdfBtn, "/history/export/data");
          btn.disabled = true;

          fetch(url + "?filter=" + encodeURIComponent(activeHistoryFilter()))
            .then(function (response) {
              return response.json();
            })
            .then(function (payload) {
              var scans = Array.isArray(payload.scans) ? payload.scans : [];
              if (scans.length === 0) {
                showToast("No Scan Data To Export.", "error");
                return;
              }
              buildScanPdf(payload);
            })
            .catch(function (err) {
              console.error("PDF export failed:", err);
              showToast("Failed To Generate PDF. Please Try Again.", "error");
            })
            .finally(function () {
              btn.disabled = false;
            });
          });
        });
      }

      function buildScanPdf(payload) {
        try {
          var jsPDF = window.jspdf && window.jspdf.jsPDF;
          if (!jsPDF || typeof jsPDF !== "function") {
            showToast("PDF Library Not Loaded.", "error");
            return;
          }

          var user = payload.user || {};
          var summary = payload.summary || {};
          var scans = payload.scans || [];

          var doc = new jsPDF({
            orientation: "portrait",
            unit: "pt",
            format: "a4",
          });

          var pageWidth = doc.internal.pageSize.getWidth();
          var margin = 40;

          /* ---- PDF: PAGE HEADER ---- */
          doc.setFillColor(2, 132, 199);
          doc.rect(0, 0, pageWidth, 70, "F");

          doc.setTextColor(255, 255, 255);
          doc.setFont("helvetica", "bold");
          doc.setFontSize(18);
          doc.text("Fresh-DA", margin, 34);

          doc.setFont("helvetica", "normal");
          doc.setFontSize(10);
          doc.text("Fish Freshness & Species Recognition", margin, 52);

          doc.setFontSize(9);
          doc.text("Generated: " + new Date().toLocaleString(), pageWidth - margin, 34, {
            align: "right",
          });
          doc.text("Report: Scan History", pageWidth - margin, 52, { align: "right" });

          /* ---- PDF: ACCOUNT INFORMATION ---- */
          doc.setTextColor(15, 23, 42);
          doc.setFont("helvetica", "bold");
          doc.setFontSize(11);
          doc.text("Account Information", margin, 100);

          doc.setDrawColor(226, 232, 240);
          doc.setLineWidth(0.5);
          doc.line(margin, 108, pageWidth - margin, 108);

          doc.setFont("helvetica", "normal");
          doc.setFontSize(10);
          doc.setTextColor(100, 116, 139);
          doc.text("Name:", margin, 126);
          doc.text("Email:", margin, 142);
          doc.text("Role:", margin + 250, 126);
          doc.text("Member Since:", margin + 250, 142);

          doc.setTextColor(15, 23, 42);
          doc.setFont("helvetica", "bold");
          doc.text(fitText(user.name, 30), margin + 60, 126);
          doc.text(fitText(user.email, 30), margin + 60, 142);
          doc.text(fitText(user.role, 18), margin + 340, 126);
          doc.text(fitText(user.member_since, 18), margin + 340, 142);

          /* ---- PDF: SUMMARY CHIPS ---- */
          var totalScans = summary.total || scans.length;
          var freshCount = summary.fresh || 0;
          var moderateCount = summary.moderate || 0;
          var notFreshCount = summary.not_fresh || 0;

          var chipY = 165;

          doc.setFillColor(224, 242, 254);
          doc.roundedRect(margin, chipY, 125, 40, 6, 6, "F");
          doc.setTextColor(7, 89, 133);
          doc.setFont("helvetica", "bold");
          doc.setFontSize(14);
          doc.text(String(totalScans), margin + 15, chipY + 22);
          doc.setFont("helvetica", "normal");
          doc.setFontSize(8);
          doc.text("TOTAL SCANS", margin + 15, chipY + 34);

          doc.setFillColor(220, 252, 231);
          doc.roundedRect(margin + 140, chipY, 125, 40, 6, 6, "F");
          doc.setTextColor(22, 163, 74);
          doc.setFont("helvetica", "bold");
          doc.setFontSize(14);
          doc.text(String(freshCount), margin + 155, chipY + 22);
          doc.setFont("helvetica", "normal");
          doc.setFontSize(8);
          doc.text("FRESH", margin + 155, chipY + 34);

          doc.setFillColor(254, 243, 199);
          doc.roundedRect(margin + 280, chipY, 125, 40, 6, 6, "F");
          doc.setTextColor(217, 119, 6);
          doc.setFont("helvetica", "bold");
          doc.setFontSize(14);
          doc.text(String(moderateCount), margin + 295, chipY + 22);
          doc.setFont("helvetica", "normal");
          doc.setFontSize(8);
          doc.text("MODERATE", margin + 295, chipY + 34);

          doc.setFillColor(254, 226, 226);
          doc.roundedRect(margin + 420, chipY, 125, 40, 6, 6, "F");
          doc.setTextColor(220, 38, 38);
          doc.setFont("helvetica", "bold");
          doc.setFontSize(14);
          doc.text(String(notFreshCount), margin + 435, chipY + 22);
          doc.setFont("helvetica", "normal");
          doc.setFontSize(8);
          doc.text("NOT FRESH", margin + 435, chipY + 34);

          /* ---- PDF: RESULTS TABLE ---- */
          var tableBody = scans.map(function (row) {
            return [
              row.fish,
              row.species,
              row.freshness + " · " + row.percentage + "%",
              row.confidence,
              row.date,
            ];
          });

          doc.autoTable({
            startY: chipY + 60,
            head: [["Fish", "Species", "Freshness", "Confidence", "Date"]],
            body: tableBody,
            theme: "grid",
            margin: { left: margin, right: margin },
            styles: {
              font: "helvetica",
              fontSize: 9,
              cellPadding: 7,
              textColor: [15, 23, 42],
              lineColor: [226, 232, 240],
              lineWidth: 0.5,
              overflow: "linebreak",
            },
            headStyles: {
              fillColor: [2, 132, 199],
              textColor: [255, 255, 255],
              fontStyle: "bold",
              fontSize: 9,
              cellPadding: 8,
            },
            alternateRowStyles: {
              fillColor: [248, 250, 252],
            },
            columnStyles: {
              0: { cellWidth: 85, fontStyle: "bold" },
              1: { cellWidth: 140, fontStyle: "italic", textColor: [100, 116, 139] },
              2: { cellWidth: 100, fontStyle: "bold" },
              3: { cellWidth: 75, halign: "center" },
              4: { cellWidth: 110 },
            },
            didParseCell: function (hookData) {
              if (hookData.section === "body" && hookData.column.index === 2) {
                var val = String(hookData.cell.raw).toLowerCase();
                if (val.indexOf("Not Fresh") !== -1) {
                  hookData.cell.styles.textColor = [220, 38, 38];
                } else if (val.indexOf("moderate") !== -1) {
                  hookData.cell.styles.textColor = [217, 119, 6];
                } else if (val.indexOf("fresh") !== -1) {
                  hookData.cell.styles.textColor = [22, 163, 74];
                }
              }
            },
            didDrawPage: function () {
              var pageCount = doc.internal.getNumberOfPages();
              var currentPage = doc.internal.getCurrentPageInfo().pageNumber;
              var pageHeight = doc.internal.pageSize.getHeight();

              doc.setDrawColor(226, 232, 240);
              doc.setLineWidth(0.5);
              doc.line(margin, pageHeight - 45, pageWidth - margin, pageHeight - 45);

              doc.setTextColor(148, 163, 184);
              doc.setFont("helvetica", "normal");
              doc.setFontSize(8);
              doc.text(
                "Fresh-DA · AI-Powered Fish Freshness & Species Recognition",
                margin,
                pageHeight - 28,
              );
              doc.text(
                "Page " + currentPage + " of " + pageCount,
                pageWidth - margin,
                pageHeight - 28,
                { align: "right" },
              );
            },
          });

          var now = new Date();
          var pad = function (n) {
            return String(n).padStart(2, "0");
          };
          var stamp =
            now.getFullYear() +
            pad(now.getMonth() + 1) +
            pad(now.getDate()) +
            "-" +
            pad(now.getHours()) +
            pad(now.getMinutes()) +
            pad(now.getSeconds());

          doc.save("freshda-scan-report-" + stamp + ".pdf");
          showToast("PDF Report Downloaded.", "success");
        } catch (err) {
          console.error("PDF export failed:", err);
          showToast("Failed To Generate PDF. Please Try Again.", "error");
        }
      }

      /* History row detail modal */
      document
        .getElementById("historyTable")
        .addEventListener("click", function (e) {
          var tr = e.target.closest("tbody tr");
          if (!tr) return;
          var name =
            tr.getAttribute("data-name") ||
            tr.querySelector(".td-with-icon span").textContent;
          var species = tr.getAttribute("data-species") || "—";
          var freshnessLabel =
            tr.getAttribute("data-freshness-label") ||
            tr.querySelector(".badge").textContent.replace(/[ ·].*/, "");
          var percentage = tr.getAttribute("data-percentage") || "";
          var confidence = tr.getAttribute("data-confidence") || "—";
          var type = tr.getAttribute("data-type") || "Single";
          var date = tr
            .querySelector("td:nth-child(5)")
            .textContent.replace("Date", "")
            .trim();
          var cls = tr.getAttribute("data-freshness") || "";

          var modal = document.getElementById("modalOverlay");
          document.getElementById("modalTitle").textContent = name;
          document.getElementById("modalBody").innerHTML =
            '<div class="history-detail">' +
            '<div class="history-detail-row"><span>Species</span><strong>' +
            escapeHtml(species) +
            "</strong></div>" +
            '<div class="history-detail-row"><span>Freshness</span><strong><span class="badge ' +
            cls +
            '">' +
            escapeHtml(freshnessLabel) +
            (percentage ? " · " + percentage + "%" : "") +
            "</span></strong></div>" +
            '<div class="history-detail-row"><span>Confidence</span><strong>' +
            escapeHtml(confidence) +
            "</strong></div>" +
            '<div class="history-detail-row"><span>Type</span><strong>' +
            escapeHtml(type) +
            "</strong></div>" +
            '<div class="history-detail-row"><span>Date</span><strong>' +
            escapeHtml(date) +
            "</strong></div>" +
            "</div>";
          var footer = document.getElementById("modalFooter");
          footer.innerHTML = "";
          var closeBtn = document.createElement("button");
          closeBtn.className = "btn btn-secondary";
          closeBtn.textContent = "Close";
          closeBtn.addEventListener("click", function () {
            modal.classList.remove("open");
          });
          footer.appendChild(closeBtn);
          modal.classList.add("open");
        });

