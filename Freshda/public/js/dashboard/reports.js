      /* ================= REPORTS ================= */
      /* One export card: the picker chooses the report, the date inputs bound
         it and the two buttons stream the CSV or typeset the printable PDF.
         The PDF is written in the browser with jsPDF — header, period, summary,
         one paragraph per record, the freshness explainer, remarks and the
         system notice — so no server round trip is needed to print it. */

      /* The narrative every report type wears: its document heading, how each
         record is titled and the paragraphs that explain the verdicts. */
      var REPORT_PDF_COPY = {
        assessment: {
          heading: "Fish Inventory and Freshness Assessment Report",
          recordHeading: "Fish Assessment Record",
          recordLabel: "Assessment Record",
          recordNoun: "assessment record",
          explainerHeading: "Freshness Assessment",
          intro:
            "This report presents the fish inventory and freshness assessment records generated and stored through the Fresh-DA system. It provides a consolidated record of the fish species identified, corresponding freshness classifications, confidence scores, and quantities recorded during the specified reporting period.",
          explainer: [
            "Fresh-DA uses machine learning-based image analysis to assist in identifying fish species and determining the apparent freshness condition of fish based on visible characteristics present in the submitted image.",
            "A Fresh classification indicates that the fish exhibits visible characteristics associated with a fresh condition based on the system's assessment. A Moderately Fresh classification indicates that the fish exhibits visible characteristics associated with an intermediate freshness condition. A Not Fresh classification indicates that the fish exhibits visible characteristics associated with reduced freshness.",
            "The confidence score represents the model's confidence in the generated prediction and should be considered as part of the overall assessment rather than as a direct measurement of food safety.",
          ],
        },
        inventory: {
          heading: "Fish Inventory Report",
          recordHeading: "Inventory Assessment Record",
          recordLabel: "Inventory Record",
          recordNoun: "inventory item",
          explainerHeading: "Freshness Assessment",
          intro:
            "This report presents the fish inventory records generated and stored through the Fresh-DA system. It provides a consolidated record of the stocked fish species identified, their corresponding freshness classifications, confidence scores and recorded quantities as of the date of report generation.",
          explainer: [
            "Fresh-DA uses machine learning-based image analysis to assist in identifying fish species and determining the apparent freshness condition of fish based on visible characteristics present in the submitted image. The freshness of a stocked item follows the most recent assessment recorded for that item.",
            "A Fresh classification indicates that the fish exhibits visible characteristics associated with a fresh condition based on the system's assessment. A Moderately Fresh classification indicates that the fish exhibits visible characteristics associated with an intermediate freshness condition. A Not Fresh classification indicates that the fish exhibits visible characteristics associated with reduced freshness.",
            "The confidence score represents the model's confidence in the generated prediction and should be considered as part of the overall assessment rather than as a direct measurement of food safety.",
          ],
        },
        lowstock: {
          heading: "Fish Inventory Low-Stock Report",
          recordHeading: "Low-Stock Assessment Record",
          recordLabel: "Low-Stock Record",
          recordNoun: "low-stock item",
          explainerHeading: "Freshness Assessment",
          intro:
            "This report presents the fish inventory records that sit below their minimum stock level through the Fresh-DA system. It provides a consolidated record of the stocked fish species identified, their corresponding freshness classifications, confidence scores, recorded quantities and the amount each item is short by.",
          explainer: [
            "An item appears in this report when the quantity on hand falls below the minimum stock level set for it, either on the item itself or on the shared default used when no minimum was recorded.",
            "Freshness is carried from the most recent assessment recorded for the item, so a short item can still be fresh. A Fresh classification indicates visible characteristics associated with a fresh condition, a Moderately Fresh classification indicates an intermediate condition, and a Not Fresh classification indicates reduced freshness.",
            "The confidence score represents the model's confidence in the generated prediction and should be considered as part of the overall assessment rather than as a direct measurement of food safety.",
          ],
        },
        deliveries: {
          heading: "Supplier Delivery Report",
          recordHeading: "Supplier Delivery Record",
          recordLabel: "Delivery Record",
          recordNoun: "delivery record",
          explainerHeading: "Delivery Assessment",
          intro:
            "This report presents the supplier delivery records generated and stored through the Fresh-DA system. It provides a consolidated record of the fish delivered, the corresponding delivery verdicts and the quantities recorded during the specified reporting period.",
          explainer: [
            "Fresh-DA records supplier deliveries alongside the fish inventory so incoming stock can be traced back to the supplier that provided it. Each delivery is classified as Accepted, Pending or Rejected according to the verdict recorded when it was checked in.",
            "The quantity quoted for a delivery is the quantity that physically arrived once it has been recorded, or the quantity promised by the supplier while the delivery is still pending.",
            "Fresh-DA does not inspect, test or sample delivered fish. Any freshness assessment shown in this report comes from a separate image-based assessment recorded through the system.",
          ],
        },
      };

      /* Printed under every remarks section and at the end of every report. */
      var REPORT_PDF_REMARKS_NOTE =
        "The information contained in this report reflects the records available in Fresh-DA at the time of generation. Any changes made to the inventory or assessment records after this report was generated will not be reflected unless a new report is created.";

      var REPORT_PDF_DEFAULT_REMARKS = "No additional remarks were provided for this report.";

      var REPORT_PDF_NOTICE = [
        "Fresh-DA is designed to provide machine learning-assisted fish species recognition and visual freshness assessment for monitoring, documentation, and decision-support purposes. The generated results are based on image analysis and should not be considered a substitute for laboratory testing, professional inspection, or established food-safety procedures.",
        "Fresh-DA does not directly determine microbial contamination, chemical spoilage, odor, or other laboratory-based indicators of fish quality.",
      ];

      /* Only these reports are bounded by dates; the stock ones are a snapshot
         of right now, so the picker hides the range and says why. */
      var REPORT_RANGE_TYPES = { assessment: true, deliveries: true };

      /* A vendor with thousands of scans would otherwise print a book. */
      var REPORT_PDF_MAX_RECORDS = 200;

      /* Point the card at the selected report: the range only applies when the
         report is bounded by dates. */
      (function wireReportRange() {
        var picker = document.getElementById("reportType");
        var range = document.getElementById("reportRange");
        var note = document.getElementById("reportRangeNote");

        if (!picker || !range || !note) return;

        var applyRange = function () {
          var applies = REPORT_RANGE_TYPES[picker.value] === true;
          range.hidden = !applies;
          note.hidden = applies;
        };

        picker.addEventListener("change", applyRange);
        applyRange();
      })();

      Array.prototype.forEach.call(
        document.querySelectorAll(".report-card .export-btn"),
        function (btn) {
          btn.addEventListener("click", function () {
            var params = reportParams();
            var remarksEl = document.getElementById("reportRemarks");
            var remarks = remarksEl ? remarksEl.value.trim() : "";

            if (btn.classList.contains("csv")) {
              window.location.href =
                btn.getAttribute("data-csv-url") + "?" + params;
              return;
            }

            generateReportPdf(btn, params, remarks);
          });
        },
      );

      /* The picked report plus the range, when the picked report uses one. */
      function reportParams() {
        var picker = document.getElementById("reportType");
        var type = picker && picker.value ? picker.value : "assessment";
        var params = "type=" + encodeURIComponent(type);

        if (REPORT_RANGE_TYPES[type] !== true) return params;

        var fromEl = document.getElementById("reportFrom");
        var toEl = document.getElementById("reportTo");
        var from = fromEl ? fromEl.value : "";
        var to = toEl ? toEl.value : "";

        if (from) params += "&from=" + encodeURIComponent(from);
        if (to) params += "&to=" + encodeURIComponent(to);

        return params;
      }

      function reportType() {
        var picker = document.getElementById("reportType");
        return picker && picker.value ? picker.value : "assessment";
      }

      function generateReportPdf(btn, params, remarks) {
        if (typeof window.jspdf === "undefined") {
          showToast("Pdf Library Not Loaded", "error");
          return;
        }

        var original = btn.innerHTML;
        var restore = function () {
          btn.disabled = false;
          btn.innerHTML = original;
        };

        btn.disabled = true;
        btn.innerHTML = '<span class="spinner"></span>';

        apiRequest(
          "GET",
          btn.getAttribute("data-json-url") + "?" + params,
          undefined,
          function (report) {
            restore();

            try {
              renderReportPdf(report, reportType(), remarks);
              showToast(report.title + " PDF Downloaded", "success");
            } catch (err) {
              console.error("PDF export failed:", err);
              showToast("Failed To Generate PDF. Please Try Again", "error");
            }
          },
          restore,
        );
      }

      /* ---------- the printable document ---------- */

      function renderReportPdf(report, type, remarks) {
        var copy = REPORT_PDF_COPY[type] || REPORT_PDF_COPY.assessment;
        var jsPDF = window.jspdf.jsPDF;
        var doc = new jsPDF({
          orientation: "portrait",
          unit: "pt",
          format: "a4",
        });

        var pageWidth = doc.internal.pageSize.getWidth();
        var pageHeight = doc.internal.pageSize.getHeight();
        var margin = 40;
        var contentWidth = pageWidth - margin * 2;
        var rightEdge = pageWidth - margin;
        var footerRule = pageHeight - 45;
        var bottomLimit = pageHeight - 62;
        var pageStart = 56;

        var ink = [15, 23, 42];
        var muted = [100, 116, 139];
        var blue = [2, 132, 199];

        var meta = report.meta || {};
        var totals = report.totals || {};
        var records = report.records || [];
        var reference = meta.reference || "—";
        var generated = String(report.generated_at || "—");
        var generatedParts = splitStamp(generated);
        var quantity =
          typeof totals.total_quantity === "number" ? totals.total_quantity : 0;
        var y = 0;

        /* -- layout primitives -- */

        function setFont(style, size) {
          doc.setFont("helvetica", style);
          doc.setFontSize(size);
        }

        function ensureSpace(height) {
          if (y + height <= bottomLimit) return;
          doc.addPage();
          runningHeader();
          y = pageStart;
        }

        function rule(color) {
          doc.setDrawColor(color[0], color[1], color[2]);
          doc.setLineWidth(0.6);
          doc.line(margin, y, rightEdge, y);
        }

        function sectionHeading(text) {
          ensureSpace(36);
          setFont("bold", 11.5);
          doc.setTextColor(ink[0], ink[1], ink[2]);
          doc.text(text.toUpperCase(), margin, y);
          y += 7;
          rule(blue);
          y += 17;
        }

        /* Lay a run of segments out word by word so a word can stay bold
           inside a wrapping paragraph, then print it line by line. */
        function flow(segments, opts) {
          opts = opts || {};
          var size = opts.size || 10;
          var width = opts.width || contentWidth;
          var x = opts.x === undefined ? margin : opts.x;
          var color = opts.color || ink;
          var lineHeight = opts.lineHeight || size + 4.5;
          var lines = wrap(styleSegments(segments, opts.style), width, size);

          for (var i = 0; i < lines.length; i++) {
            /* Re-set the colour every line: a page break resets the palette. */
            ensureSpace(lineHeight);
            doc.setTextColor(color[0], color[1], color[2]);
            var lineX = x;

            for (var j = 0; j < lines[i].length; j++) {
              var item = lines[i][j];
              setFont(item.style, size);
              doc.text(item.text, lineX, y);
              lineX += doc.getTextWidth(item.text);
            }

            y += lineHeight;
          }
        }

        /* Strings and {text, bold} segments become one shape, with the
           paragraph's own style (italic closing lines) as the fallback. */
        function styleSegments(segments, defaultStyle) {
          var fallback = defaultStyle || "normal";

          return segments.map(function (segment) {
            if (typeof segment === "string") {
              return { text: segment, style: fallback };
            }

            return {
              text: String(segment.text || ""),
              style: segment.style || (segment.bold ? "bold" : fallback),
            };
          });
        }

        function wrap(segments, width, size) {
          var lines = [];
          var line = [];
          var lineWidth = 0;

          var push = function (style, word) {
            setFont(style, size);
            var text = word + " ";
            var wordWidth = doc.getTextWidth(text);

            if (lineWidth + wordWidth > width && line.length) {
              lines.push(line);
              line = [];
              lineWidth = 0;
            }

            line.push({ text: text, style: style });
            lineWidth += wordWidth;
          };

          segments.forEach(function (segment) {
            segment.text.split(/\s+/).forEach(function (word) {
              if (word !== "") push(segment.style, word);
            });
          });

          if (line.length) lines.push(line);

          return lines;
        }

        function fitFont(text, maxWidth, startSize, minSize, style) {
          var size = startSize;

          while (size > minSize) {
            setFont(style, size);
            if (doc.getTextWidth(text) <= maxWidth) break;
            size -= 0.5;
          }

          setFont(style, size);

          return size;
        }

        /* -- the document -- */

        function masthead() {
          doc.setFillColor(blue[0], blue[1], blue[2]);
          doc.rect(0, 0, pageWidth, 70, "F");

          doc.setTextColor(255, 255, 255);
          setFont("bold", 20);
          doc.text("FRESH-DA", margin, 34);
          setFont("normal", 9.5);
          doc.text(
            "Machine Learning-Based Progressive Web App for Fish Freshness and Species Recognition",
            margin,
            52,
          );
          setFont("normal", 9);
          doc.text("System-Generated Report", rightEdge, 34, { align: "right" });
          doc.text("Reference No.: " + reference, rightEdge, 52, {
            align: "right",
          });
        }

        function runningHeader() {
          setFont("normal", 8.5);
          doc.setTextColor(148, 163, 184);
          doc.text(copy.heading + " · " + reference, margin, 30);
          rule([226, 232, 240]);
        }

        function titleBlock() {
          y = 96;
          setFont("bold", 14);
          doc.setTextColor(ink[0], ink[1], ink[2]);
          doc.text(copy.heading.toUpperCase(), margin, y);
          y += 10;
          rule([226, 232, 240]);
          y += 26;
        }

        function metaCell(pair, x, cellY) {
          setFont("normal", 8);
          doc.setTextColor(muted[0], muted[1], muted[2]);
          doc.text(String(pair[0]).toUpperCase(), x, cellY + 9);
          setFont("bold", 10);
          doc.setTextColor(ink[0], ink[1], ink[2]);
          doc.text(doc.splitTextToSize(String(pair[1]), contentWidth / 2 - 16)[0], x, cellY + 24);
        }

        function metaGrid(pairs) {
          var columnWidth = contentWidth / 2;
          var rowHeight = 34;

          for (var i = 0; i < pairs.length; i += 2) {
            ensureSpace(rowHeight);
            metaCell(pairs[i], margin, y);
            if (pairs[i + 1]) metaCell(pairs[i + 1], margin + columnWidth, y);
            y += rowHeight;
          }

          y += 4;
          rule([226, 232, 240]);
          y += 24;
        }

        function classificationCounts() {
          var order = ["Fresh", "Moderate", "Not Fresh", "Accepted", "Pending", "Rejected"];
          var counts = {};

          records.forEach(function (record) {
            var key = record.classification || "—";
            counts[key] = (counts[key] || 0) + 1;
          });

          var list = [];

          order.forEach(function (key) {
            if (!Object.prototype.hasOwnProperty.call(counts, key)) return;
            list.push({ key: key, label: classificationLabel(key), count: counts[key] });
            delete counts[key];
          });

          Object.keys(counts).forEach(function (key) {
            if (counts[key] > 0) {
              list.push({ key: key, label: classificationLabel(key), count: counts[key] });
            }
          });

          return list;
        }

        function chipColors(key) {
          var fresh = ["fresh", "Fresh", "Accepted"];
          var moderate = ["moderate", "Moderate", "Pending"];
          var stale = ["not_fresh", "Not Fresh", "Rejected"];

          if (fresh.indexOf(key) !== -1) return [[22, 163, 74], [220, 252, 231]];
          if (moderate.indexOf(key) !== -1) return [[217, 119, 6], [254, 243, 199]];
          if (stale.indexOf(key) !== -1) return [[220, 38, 38], [254, 226, 226]];

          return [[71, 85, 105], [241, 245, 249]];
        }

        function statChips() {
          var chips = [
            {
              label: "Total Quantity",
              value: formatQuantity(quantity),
              color: [7, 89, 133],
              bg: [224, 242, 254],
            },
            {
              label: "Total Records",
              value: String(records.length),
              color: [71, 85, 105],
              bg: [241, 245, 249],
            },
          ];

          classificationCounts().slice(0, 3).forEach(function (entry) {
            var colors = chipColors(entry.key);
            chips.push({
              label: entry.label,
              value: String(entry.count),
              color: colors[0],
              bg: colors[1],
            });
          });

          var gap = 8;
          var width = (contentWidth - gap * (chips.length - 1)) / chips.length;
          var height = 44;

          ensureSpace(height + 18);

          chips.forEach(function (chip, index) {
            var x = margin + index * (width + gap);
            var label = chip.label.toUpperCase();

            doc.setFillColor(chip.bg[0], chip.bg[1], chip.bg[2]);
            doc.roundedRect(x, y, width, height, 6, 6, "F");

            doc.setTextColor(chip.color[0], chip.color[1], chip.color[2]);
            setFont("bold", fitFont(chip.value, width - 16, 15, 8, "bold"));
            doc.text(chip.value, x + 10, y + 22);

            setFont("normal", fitFont(label, width - 16, 7.5, 5.5, "normal"));
            doc.text(label, x + 10, y + 35);
          });

          y += height + 18;
        }

        function summary() {
          sectionHeading("Report Summary");
          statChips();
          flow([{ text: copy.intro }], { size: 10 });

          y += 6;
          flow(summarySentences(), { size: 10 });
          y += 18;
        }

        function summarySentences() {
          var segments = [
            { text: "As of the date and time of report generation, the system recorded a total of " },
            { text: formatQuantity(quantity), bold: true },
            { text: " of fish across " },
            {
              text:
                String(records.length) +
                " " +
                copy.recordNoun +
                (records.length === 1 ? "" : "s"),
              bold: true,
            },
            { text: "." },
          ];

          if (!records.length) {
            segments.push({ text: " No records matched the reporting period." });

            return segments;
          }

          var counts = classificationCounts();

          segments.push({ text: " Of these records, " });

          counts.forEach(function (entry, index) {
            if (index > 0) {
              segments.push({
                text: index === counts.length - 1 ? ", and " : ", ",
              });
            }
            segments.push({ text: String(entry.count), bold: true });
            segments.push({ text: index === 0 ? " were classified as " : " as " });
            segments.push({ text: entry.label, bold: true });
          });

          segments.push({ text: "." });

          return segments;
        }

        function recordBlocks() {
          sectionHeading(copy.recordHeading);

          var limit = Math.min(records.length, REPORT_PDF_MAX_RECORDS);

          for (var i = 0; i < limit; i++) {
            ensureSpace(56);
            setFont("bold", 10.5);
            doc.setTextColor(blue[0], blue[1], blue[2]);
            doc.text(recordLabel(i), margin, y);
            y += 15;
            flow(recordSegments(records[i]), { size: 10, lineHeight: 14.5 });
            y += 10;
          }

          if (records.length > limit) {
            flow(
              [
                { text: "…and " },
                { text: String(records.length - limit), bold: true },
                {
                  text:
                    " further records. The complete list is available through the CSV export.",
                },
              ],
              { size: 10 },
            );
            y += 10;
          }
        }

        function explainer() {
          sectionHeading(copy.explainerHeading || "Freshness Assessment");

          copy.explainer.forEach(function (paragraph) {
            flow([{ text: paragraph }], { size: 9.5, color: [51, 65, 85] });
            y += 8;
          });

          y += 10;
        }

        function remarksSection(text) {
          sectionHeading("Report Remarks");
          flow([{ text: text }], { size: 10 });
          y += 8;
          flow([{ text: REPORT_PDF_REMARKS_NOTE }], {
            size: 9,
            color: muted,
            lineHeight: 13,
          });
          y += 16;
        }

        function noticeSection() {
          sectionHeading("System Notice");

          REPORT_PDF_NOTICE.forEach(function (paragraph) {
            flow([{ text: paragraph }], { size: 9.5, color: [51, 65, 85] });
            y += 8;
          });

          y += 14;
        }

        function closingBlock() {
          ensureSpace(104);
          setFont("bold", 12);
          doc.setTextColor(blue[0], blue[1], blue[2]);
          doc.text("FRESH-DA", margin, y);
          y += 15;
          flow(
            [
              {
                text:
                  "Machine Learning-Based Progressive Web App for Fish Freshness and Species Recognition",
              },
            ],
            { size: 9.5, style: "italic", color: muted, lineHeight: 13 },
          );
          y += 14;
          setFont("bold", 10);
          doc.setTextColor(ink[0], ink[1], ink[2]);
          doc.text("System-Generated Report", margin, y);
          y += 14;
          setFont("bold", 9.5);
          doc.text(
            "Reference No.: " + reference + " | Generated: " + generated,
            margin,
            y,
          );
          y += 10;
        }

        function stampFooters() {
          var pages = doc.internal.getNumberOfPages();

          for (var page = 1; page <= pages; page++) {
            doc.setPage(page);
            doc.setDrawColor(226, 232, 240);
            doc.setLineWidth(0.5);
            doc.line(margin, footerRule, rightEdge, footerRule);
            setFont("normal", 8);
            doc.setTextColor(148, 163, 184);
            doc.text(
              "Fresh-DA · System-Generated Report · " + reference,
              margin,
              pageHeight - 28,
            );
            doc.text("Page " + page + " of " + pages, rightEdge, pageHeight - 28, {
              align: "right",
            });
          }

          doc.setPage(1);
        }

        /* -- what each record says -- */

        function nameSegments(record) {
          var fish = record.fish || "Unknown";
          var segments = [{ text: fish, bold: true }];
          var species = String(record.species || "");

          if (species && species.toLowerCase() !== fish.toLowerCase()) {
            segments.push({ text: " (" + species + ")" });
          }

          return segments;
        }

        function confidenceSegments(record) {
          if (
            record.confidence === null ||
            record.confidence === undefined ||
            confidencePercent(record.confidence) === ""
          ) {
            return [];
          }

          return [
            { text: ", with a model confidence score of " },
            { text: confidencePercent(record.confidence) + "%", bold: true },
          ];
        }

        function quantitySegments(record, leadIn, tail) {
          if (record.quantity === null || record.quantity === undefined) {
            return [{ text: " No matching inventory quantity was recorded." }];
          }

          return [
            { text: " " + leadIn + " " },
            { text: formatQuantity(record.quantity), bold: true },
            { text: tail },
          ];
        }

        function whenSegments(record, leadIn) {
          if (!record.datetime) return [];

          var stamp = splitStamp(record.datetime);
          var segments = [{ text: " " + leadIn + " " }, { text: stamp.date, bold: true }];

          if (stamp.time) {
            segments.push({ text: " at " });
            segments.push({ text: stamp.time, bold: true });
          }

          segments.push({ text: "." });

          return segments;
        }

        function recordSegments(record) {
          if (type === "deliveries") return deliverySegments(record);
          if (type === "inventory" || type === "lowstock") return stockSegments(record);
          return assessmentSegments(record);
        }

        function assessmentSegments(record) {
          var segments = [{ text: "The fish was identified as " }]
            .concat(nameSegments(record))
            .concat([{ text: " and classified as " }, {
              text: classificationLabel(record.classification),
              bold: true,
            }])
            .concat(confidenceSegments(record))
            .concat([{ text: "." }])
            .concat(
              quantitySegments(record, "A quantity of", " was recorded."),
            )
            .concat(
              whenSegments(record, "The assessment was conducted on"),
            );

          return segments;
        }

        function stockSegments(record) {
          var segments = [{ text: "The stocked item " }]
            .concat(nameSegments(record))
            .concat([{ text: " is classified as " }, {
              text: classificationLabel(record.classification),
              bold: true,
            }])
            .concat(confidenceSegments(record))
            .concat([{ text: "." }]);

          if (record.quantity !== null && record.quantity !== undefined) {
            segments = segments
              .concat([{ text: " A stock quantity of " }])
              .concat([{ text: formatQuantity(record.quantity), bold: true }])
              .concat([{ text: " is on hand." }]);
          }

          if (record.detail) {
            segments = segments
              .concat([{ text: " It is " }])
              .concat([{ text: record.detail, bold: true }])
              .concat([{ text: " against its minimum stock level." }]);
          }

          if (record.datetime) {
            segments = segments.concat(
              whenSegments(record, "The item was last assessed on"),
            );
          } else {
            segments = segments.concat([
              { text: " This item has not been scanned yet." },
            ]);
          }

          return segments;
        }

        function deliverySegments(record) {
          var segments = [{ text: "The delivery of " }].concat(nameSegments(record));

          if (record.detail) {
            segments = segments
              .concat([{ text: " from " }])
              .concat([{ text: record.detail, bold: true }]);
          }

          segments = segments
            .concat([{ text: " was classified as " }, {
              text: classificationLabel(record.classification),
              bold: true,
            }])
            .concat([{ text: "." }])
            .concat(quantitySegments(record, "A received quantity of", " was recorded."))
            .concat(whenSegments(record, "The delivery was recorded on"));

          return segments;
        }

        function recordLabel(index) {
          var number = String(index + 1);

          while (number.length < 3) number = "0" + number;

          return copy.recordLabel + " " + number;
        }

        /* -- shared formatting -- */

        function splitStamp(value) {
          var text = String(value || "");
          var at = text.lastIndexOf(" ");

          if (at > -1 && /^\d{1,2}:\d{2}$/.test(text.slice(at + 1))) {
            return { date: text.slice(0, at), time: text.slice(at + 1) };
          }

          return { date: text, time: "" };
        }

        function confidencePercent(value) {
          var number = typeof value === "number" ? value : parseFloat(value);

          if (isNaN(number)) return "";
          if (number <= 1) number = number * 100;

          return number.toFixed(1);
        }

        function formatQuantity(value) {
          var number = typeof value === "number" ? value : parseFloat(value);

          if (isNaN(number)) number = 0;

          return number.toFixed(1) + " kg";
        }

        function classificationLabel(value) {
          if (value === "moderate" || value === "Moderate") return "Moderately Fresh";
          if (value === "not_fresh") return "Not Fresh";
          if (!value) return "—";

          return String(value);
        }

        function reportPeriod() {
          if (report.from && report.to) return report.from + " – " + report.to;
          if (report.from) return "From " + report.from;
          if (report.to) return "Until " + report.to;

          return "As of " + generatedParts.date;
        }

        function fileStamp() {
          var now = new Date();
          var pad = function (number) {
            return number < 10 ? "0" + number : String(number);
          };

          return (
            String(now.getFullYear()) +
            pad(now.getMonth() + 1) +
            pad(now.getDate()) +
            "-" +
            pad(now.getHours()) +
            pad(now.getMinutes()) +
            pad(now.getSeconds())
          );
        }

        /* -- print -- */

        masthead();
        titleBlock();

        metaGrid([
          ["Report Reference No.", reference],
          ["Date Generated", generatedParts.date],
          ["Time Generated", generatedParts.time || "—"],
          ["Generated By", meta.generated_by || "—"],
          ["User Role", meta.role || "—"],
          ["Reporting Period", reportPeriod()],
        ]);

        summary();
        recordBlocks();
        explainer();
        remarksSection(
          remarks && remarks !== "" ? remarks : REPORT_PDF_DEFAULT_REMARKS,
        );
        noticeSection();
        closingBlock();
        stampFooters();

        doc.save("freshda-" + type + "-report-" + fileStamp() + ".pdf");
      }
