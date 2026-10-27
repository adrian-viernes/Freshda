      /* ==================================================================
       BATCH SAMPLING — REQUIRES 3–5 CAPTURED FISH PHOTOS
       ================================================================== */
      var batchList = document.getElementById("batchList");
      var batchCountBadge = document.getElementById("batchCountBadge");
      var batchProgressFill = document.getElementById("batchProgressFill");
      var batchCurrentCount = document.getElementById("batchCurrentCount");
      var batchProgressPercent = document.getElementById(
        "batchProgressPercent",
      );
      var batchStatusBadge = document.getElementById("batchStatusBadge");
      var batchRequirementNote = document.getElementById(
        "batchRequirementNote",
      );
      var finishBatchBtn = document.getElementById("finishBatchBtn");
      var batchSummaryCard = document.getElementById("batchSummaryCard");
      var batchOverallVerdict = document.getElementById("batchOverallVerdict");

      /* Batch capture elements (live camera, same flow as Scan Fish) */
      var batchCaptureQuota = document.getElementById("batchCaptureQuota");
      var batchCamFrame = document.getElementById("batchCamFrame");
      var batchCamSnapshot = document.getElementById("batchCamSnapshot");
      var batchCamLiveBadge = document.getElementById("batchCamLiveBadge");
      var batchOpenCameraBtn = document.getElementById("batchOpenCameraBtn");
      var batchCaptureBtn = document.getElementById("batchCaptureBtn");
      var batchSwitchCameraBtn = document.getElementById("batchSwitchCameraBtn");
      var batchCloseCameraBtn = document.getElementById("batchCloseCameraBtn");
      var batchUploadBtn = document.getElementById("batchUploadBtn");
      var batchAnalyzeBtn = document.getElementById("batchAnalyzeBtn");
      var batchCameraInput = document.getElementById("batchCameraInput");
      var batchUploadInput = document.getElementById("batchUploadInput");

      /* The batch capture card has its own camera stream and preview. */
      var batchCamera = createFishCamera({
        videoId: "batchCamVideo",
        previewId: "batchCamFrame",
        canvasId: "batchCamCanvas",
        liveBadge: batchCamLiveBadge,
        openBtn: batchOpenCameraBtn,
        captureBtn: batchCaptureBtn,
        switchBtn: batchSwitchCameraBtn,
        closeBtn: batchCloseCameraBtn,
        fallbackInput: batchCameraInput,
        uploadHint: "Use Upload Photo Instead",
        readyMessage: "Camera Ready - Frame the Fish And Tap Capture Photo",
        onCapture: function (file) {
          handleBatchImageFile(file);
        },
      });

      var sampleNames = [
        "Milkfish",
        "Tilapia",
        "Salmon",
        "Mackerel",
        "Tuna",
        "Sardine",
        "Snapper",
        "Grouper",
        "Catfish",
        "Bangus",
      ];
      var sampleSpecies = [
        "Chanos Chanos",
        "Oreochromis Niloticus",
        "Salmo Salar",
        "Scomber Scombrus",
        "Thunnus",
        "Sardinella",
        "Lutjanus",
        "Epinephelus",
        "Clarias",
        "Chanos Chanos",
      ];

      /* ================= BATCH DRAFT PERSISTENCE =================
         The captured photos and their analysis are mirrored to localStorage
         so a page refresh does not lose the batch. History and inventory are
         already rendered from the database on every page load. */
      function clearBatchDraft() {
        try {
          localStorage.removeItem(BATCH_STORAGE_KEY);
        } catch (e) {
          /* Storage may be unavailable (private mode) - nothing to clear. */
        }
      }

      function saveBatchDraft() {
        if (!state.batchSamples.length) {
          clearBatchDraft();
          return;
        }
        var draft = {
          samples: state.batchSamples,
          complete: state.batchComplete,
        };
        try {
          localStorage.setItem(BATCH_STORAGE_KEY, JSON.stringify(draft));
        } catch (e) {
          /* Storage full - keep the results but drop the photos. */
          try {
            localStorage.setItem(
              BATCH_STORAGE_KEY,
              JSON.stringify({
                samples: state.batchSamples.map(function (s) {
                  return {
                    name: s.name,
                    species: s.species,
                    status: s.status,
                    cls: s.cls,
                    label: s.label,
                    conf: s.conf,
                  };
                }),
                complete: state.batchComplete,
              }),
            );
            showToast(
              "Device Storage Is Full - the Batch Was Kept Without Its Photos",
              "info",
            );
          } catch (e2) {
            clearBatchDraft();
          }
        }
      }

      function loadBatchDraft() {
        var draft;
        try {
          draft = JSON.parse(localStorage.getItem(BATCH_STORAGE_KEY) || "null");
        } catch (e) {
          draft = null;
        }
        if (!draft || !Array.isArray(draft.samples)) return;

        /* A photo caught mid-analysis would otherwise stay "Processing..." forever. */
        var fallbackResults = [
          { cls: "fresh", label: "Fresh" },
          { cls: "moderate", label: "Moderate" },
          { cls: "notfresh", label: "Not Fresh" },
        ];
        var samples = draft.samples
          .filter(function (s) {
            return s && typeof s === "object";
          })
          .slice(0, BATCH_MAX_SAMPLES)
          .map(function (s) {
            if (s.status !== "processing") return s;
            var f =
              fallbackResults[Math.floor(Math.random() * fallbackResults.length)];
            return {
              name: s.name || "Fish",
              species: s.species || "",
              status: "complete",
              cls: f.cls,
              label: f.label,
              conf: 70 + Math.floor(Math.random() * 25),
              img: s.img || "",
            };
          });

        if (!samples.length) {
          clearBatchDraft();
          return;
        }

        state.batchSamples = samples;
        state.batchComplete = !!draft.complete;
        showToast(
          "Restored Your Batch - " +
            samples.length +
            " captured photo" +
            (samples.length !== 1 ? "s" : "") +
            " kept",
          "info",
        );
      }

      /* Render the entire batch section based on state.batchSamples */
      function renderBatch() {
        var count = state.batchSamples.length;
        var percent = Math.min(
          100,
          Math.round((count / BATCH_MAX_SAMPLES) * 100),
        );
        var isComplete = count >= BATCH_MIN_SAMPLES;
        var isFull = count >= BATCH_MAX_SAMPLES;

        // Update progress
        batchCurrentCount.textContent = count;
        batchProgressPercent.textContent = percent + "%";
        batchProgressFill.style.width = percent + "%";
        batchProgressFill.classList.toggle("complete", isComplete);

        // Update status badge
        if (isFull) {
          batchStatusBadge.className = "batch-status-badge complete";
          batchStatusBadge.innerHTML =
            '<i class="ri-image-line"></i> Max photos reached';
        } else if (isComplete) {
          batchStatusBadge.className = "batch-status-badge complete";
          batchStatusBadge.innerHTML =
            '<i class="ri-check-double-line"></i> Ready to verify';
        } else {
          batchStatusBadge.className = "batch-status-badge pending";
          batchStatusBadge.innerHTML =
            '<i class="ri-time-line"></i> Need ' +
            (BATCH_MIN_SAMPLES - count) +
            " more";
        }

        // Update requirement note
        if (isFull) {
          batchRequirementNote.className = "batch-requirement-note success";
          batchRequirementNote.innerHTML =
            '<i class="ri-checkbox-circle-line"></i><span><strong>Photo limit reached!</strong> You have captured ' +
            count +
            ' photos of fish, all analyzed. Click "Finish & Verify Batch" to see the verdict.</span>';
        } else if (isComplete) {
          batchRequirementNote.className = "batch-requirement-note success";
          batchRequirementNote.innerHTML =
            '<i class="ri-checkbox-circle-line"></i><span><strong>Requirement met!</strong> You have captured ' +
            count +
            ' photos of fish, all analyzed. You may capture up to ' +
            BATCH_MAX_SAMPLES +
            ' photos or click "Finish & Verify Batch" to see the verdict.</span>';
        } else {
          batchRequirementNote.className = "batch-requirement-note";
          batchRequirementNote.innerHTML =
            '<i class="ri-information-line"></i><span><strong>Requirement:</strong> Capture at least <strong>' +
            BATCH_MIN_SAMPLES +
            " photos of fish</strong> (up to " +
            BATCH_MAX_SAMPLES +
            ") to analyze those fish and compute a reliable freshness verdict for the whole batch. " +
            (BATCH_MIN_SAMPLES - count) +
            " more needed.</span>";
        }

        // Update counter badge
        batchCountBadge.innerHTML =
          '<i class="ri-image-line"></i> ' +
          count +
          " photo" +
          (count !== 1 ? "s" : "");
        batchCountBadge.classList.toggle("complete", isComplete);

        // Update finish button (minimum photos captured, all analyzed)
        var stillProcessing = state.batchSamples.some(function (s) {
          return s.status === "processing";
        });
        finishBatchBtn.disabled = !isComplete || stillProcessing;

        // Update the capture card quota (3–5 photos per batch)
        if (batchCaptureQuota) {
          batchCaptureQuota.innerHTML =
            '<i class="ri-image-line"></i> ' +
            count +
            " of " +
            BATCH_MAX_SAMPLES +
            " captured";
          batchCaptureQuota.classList.toggle("complete", isComplete);
        }
        toggleHidden(batchOpenCameraBtn, isFull);
        toggleHidden(batchUploadBtn, isFull);
        if (isFull) {
          batchCamera.stop();
          clearBatchPendingPhoto();
        }

        // Render sample list
        if (count === 0) {
          batchList.innerHTML =
            '<div class="batch-empty">' +
            '<i class="ri-image-line"></i>' +
            "<strong>No photos captured yet</strong>" +
            '<p>Use "Capture Photo" above to photograph a fish from this batch. You need ' +
            BATCH_MIN_SAMPLES +
            " to " +
            BATCH_MAX_SAMPLES +
            " photos of fish.</p>" +
            "</div>";
        } else {
          batchList.innerHTML = "";
          state.batchSamples.forEach(function (sample, index) {
            var item = document.createElement("div");
            item.className = "scan-item";
            item.setAttribute("data-sample-index", index);
            var thumbCls = sample.status === "processing" ? "" : sample.cls;
            var thumbContent = sample.img
              ? '<img src="' + sample.img + '" alt="Captured Fish Photo">'
              : sample.status === "processing"
                ? '<i class="ri-loader-4-line" style="animation:spin 1s linear infinite;"></i>'
                : '<i class="ri-check-line"></i>';
            var badgeHtml;
            if (sample.status === "processing") {
              badgeHtml = '<span class="badge">Processing...</span>';
            } else {
              badgeHtml =
                '<span class="badge ' +
                sample.cls +
                '">' +
                sample.label +
                "</span>";
            }
            item.innerHTML =
              '<div class="scan-thumb ' +
              thumbCls +
              '">' +
              thumbContent +
              "</div>" +
              '<div class="scan-info">' +
              '<div class="scan-name">' +
              sample.name +
              ' <span style="color:var(--muted-soft); font-weight:500; font-size:0.75rem;">#' +
              (index + 1) +
              "</span></div>" +
              '<div class="scan-meta">' +
              (sample.status === "processing"
                ? "<span>Analyzing...</span>"
                : "<span>" +
                  sample.label +
                  " · " +
                  sample.conf +
                  '%</span><span class="sep">•</span><span>' +
                  sample.species +
                  "</span>") +
              "</div>" +
              "</div>" +
              badgeHtml;
            batchList.appendChild(item);
          });
        }

        // Show/hide summary card
        if (state.batchComplete && isComplete) {
          batchSummaryCard.classList.remove("hidden");
          renderBatchSummary();
        } else {
          batchSummaryCard.classList.add("hidden");
        }
      }

      /* Compute overall batch verdict from current samples */
      function computeBatchVerdict() {
        var total = state.batchSamples.length;
        var freshCount = 0,
          moderateCount = 0,
          notFreshCount = 0;
        state.batchSamples.forEach(function (s) {
          if (s.cls === "fresh") freshCount++;
          else if (s.cls === "moderate") moderateCount++;
          else if (s.cls === "notfresh") notFreshCount++;
        });
        var verdict, verdictDesc, verdictCls;

        if (freshCount / total >= 0.7) {
          verdict = "FRESH";
          verdictDesc =
            "The Majority Of the Captured Fish Photos (" +
            freshCount +
            "/" +
            total +
            ") show fresh characteristics. This batch is safe to sell.";
          verdictCls = "fresh";
        } else if (notFreshCount / total >= 0.5) {
          verdict = "NOT FRESH";
          verdictDesc =
            "The Majority Of the Captured Fish Photos (" +
            notFreshCount +
            "/" +
            total +
            ") show signs of spoilage. Recommend discarding this batch.";
          verdictCls = "notfresh";
        } else {
          verdict = "MODERATE";
          verdictDesc =
            "Captured Fish Photos Show Mixed Freshness (" +
            freshCount +
            " fresh, " +
            moderateCount +
            " moderate, " +
            notFreshCount +
            " not fresh). Recommend selling quickly or further inspection.";
          verdictCls = "moderate";
        }

        return {
          verdict: verdict,
          verdictDesc: verdictDesc,
          verdictCls: verdictCls,
          freshCount: freshCount,
          moderateCount: moderateCount,
          notFreshCount: notFreshCount,
          total: total,
        };
      }

      /* Render batch summary based on sample results */
      function renderBatchSummary() {
        var v = computeBatchVerdict();
        document.getElementById("batchFreshCount").textContent = v.freshCount;
        document.getElementById("batchModerateCount").textContent =
          v.moderateCount;
        document.getElementById("batchNotFreshCount").textContent =
          v.notFreshCount;

        document.getElementById("batchVerdictLabel").textContent = v.verdict;

        // Update verdict card
        batchOverallVerdict.className = "batch-overall-verdict " + v.verdictCls;
        var iconEl = batchOverallVerdict.querySelector("i");
        if (iconEl) {
          if (v.verdictCls === "fresh") iconEl.className = "ri-shield-check-line";
          else if (v.verdictCls === "notfresh")
            iconEl.className = "ri-shield-cross-line";
          else iconEl.className = "ri-shield-flash-line";
        }
        document.getElementById("batchVerdictDesc").textContent = v.verdictDesc;
      }

      /* Reset the capture card back to its empty state. */
      function clearBatchPendingPhoto() {
        state.batchPendingImage = null;
        if (batchCameraInput) batchCameraInput.value = "";
        if (batchUploadInput) batchUploadInput.value = "";
        if (batchCamSnapshot) batchCamSnapshot.removeAttribute("src");
        if (batchCamFrame) batchCamFrame.classList.remove("has-snapshot");
        toggleHidden(batchAnalyzeBtn, true);
      }

      /* Validate the selected fish photo and show it before analysis. */
      function handleBatchImageFile(file) {
        if (!file) return;
        if (state.batchSamples.length >= BATCH_MAX_SAMPLES) {
          showToast(
            "Maximum Of " + BATCH_MAX_SAMPLES + " photos per batch reached",
            "error",
          );
          clearBatchPendingPhoto();
          return;
        }
        if (!file.type || !file.type.startsWith("image/")) {
          showToast("Please Select an Image File", "error");
          return;
        }
        if (file.size > 10 * 1024 * 1024) {
          showToast("File Too Large (Max 10MB)", "error");
          return;
        }
        var reader = new FileReader();
        reader.onload = function (e) {
          state.batchPendingImage = e.target.result;
          if (batchCamSnapshot) batchCamSnapshot.src = e.target.result;
          if (batchCamFrame) batchCamFrame.classList.add("has-snapshot");
          toggleHidden(batchAnalyzeBtn, false);
          showToast("Photo Ready - Tap Analyze & Add To Batch", "success");
        };
        reader.readAsDataURL(file);
      }

      /* Open / capture / switch / close — live camera like Scan Fish. */
      if (batchOpenCameraBtn)
        batchOpenCameraBtn.addEventListener("click", function () {
          batchCamera.start();
        });
      if (batchCaptureBtn)
        batchCaptureBtn.addEventListener("click", function () {
          batchCamera.capture();
        });
      if (batchSwitchCameraBtn)
        batchSwitchCameraBtn.addEventListener("click", function () {
          batchCamera.switchFacing();
        });
      if (batchCloseCameraBtn)
        batchCloseCameraBtn.addEventListener("click", function () {
          batchCamera.stop();
        });
      if (batchUploadBtn)
        batchUploadBtn.addEventListener("click", function () {
          batchUploadInput.click();
        });
      if (batchCameraInput)
        batchCameraInput.addEventListener("change", function () {
          handleBatchImageFile(this.files[0]);
        });
      if (batchUploadInput)
        batchUploadInput.addEventListener("change", function () {
          handleBatchImageFile(this.files[0]);
        });

      /* Analyze the captured photo and add it to the batch */
      if (batchAnalyzeBtn)
        batchAnalyzeBtn.addEventListener("click", function () {
          if (!state.batchPendingImage) {
            showToast("Capture Or Upload a Fish Photo First", "error");
            return;
          }
          if (state.batchSamples.length >= BATCH_MAX_SAMPLES) {
            showToast(
              "Maximum Of " + BATCH_MAX_SAMPLES + " photos per batch reached",
              "error",
            );
            return;
          }
          var idx = state.batchSamples.length;
          var name = sampleNames[idx % sampleNames.length];
          var species = sampleSpecies[idx % sampleSpecies.length];

          // Add the captured photo with processing status
          var sample = {
            name: name,
            species: species,
            status: "processing",
            cls: "",
            label: "",
            conf: 0,
            img: state.batchPendingImage,
          };
          state.batchSamples.push(sample);
          state.batchComplete = false;
          clearBatchPendingPhoto();
          renderBatch();
          saveBatchDraft();
          showToast("Photo #" + (idx + 1) + " captured — analyzing...", "info");

          // Simulate AI analysis
          setTimeout(
            function () {
              var results = [
                {
                  cls: "fresh",
                  label: "Fresh",
                  conf: 88 + Math.floor(Math.random() * 10),
                },
                {
                  cls: "moderate",
                  label: "Moderate",
                  conf: 70 + Math.floor(Math.random() * 10),
                },
                {
                  cls: "notfresh",
                  label: "Not Fresh",
                  conf: 40 + Math.floor(Math.random() * 15),
                },
              ];
              var r = results[Math.floor(Math.random() * results.length)];
              state.batchSamples[idx].status = "complete";
              state.batchSamples[idx].cls = r.cls;
              state.batchSamples[idx].label = r.label;
              state.batchSamples[idx].conf = r.conf;
              renderBatch();
              saveBatchDraft();
              showToast(
                "Photo #" +
                  (idx + 1) +
                  " analyzed: " +
                  r.label +
                  " (" +
                  r.conf +
                  "%)",
                "success",
              );

              pushNotification(
                r.cls === "fresh"
                  ? "fresh"
                  : r.cls === "moderate"
                    ? "info"
                    : "warning",
                "Batch Photo #" + (idx + 1) + " Processed",
                name + " · " + r.label + " (" + r.conf + "%)",
                "scans",
                "history",
              );

              // Auto-notify when batch requirement is met
              if (state.batchSamples.length === BATCH_MIN_SAMPLES) {
                showToast(
                  "🎉 Minimum of " +
                    BATCH_MIN_SAMPLES +
                    " photos reached! Verify the batch or capture more (up to " +
                    BATCH_MAX_SAMPLES +
                    ").",
                  "success",
                );
                pushNotification(
                  "success",
                  "Batch Ready For Verification",
                  BATCH_MIN_SAMPLES +
                    ' fish photos captured and analyzed. Click "Finish & Verify Batch" to see the verdict.',
                  "scans",
                  "batch",
                );
              }

              if (state.batchSamples.length === BATCH_MAX_SAMPLES) {
                showToast(
                  "🎉 Maximum of " +
                    BATCH_MAX_SAMPLES +
                    " photos reached — you can now verify the batch.",
                  "success",
                );
              }
            },
            1500 + Math.random() * 1000,
          );
        });

      /* Cancel batch */
      document
        .getElementById("cancelBatchBtn")
        .addEventListener("click", function () {
          if (state.batchSamples.length === 0) {
            showToast("No Photos In Batch", "info");
            return;
          }
          if (
            confirm(
              "Cancel the Current Batch? All " +
                state.batchSamples.length +
                " captured photos will be discarded.",
            )
          ) {
            state.batchSamples = [];
            state.batchComplete = false;
            clearBatchPendingPhoto();
            clearBatchDraft();
            renderBatch();
            showToast("Batch Cancelled", "info");
          }
        });

      /* Finish & Verify batch */
      finishBatchBtn.addEventListener("click", function () {
        if (state.batchSamples.length < BATCH_MIN_SAMPLES) {
          showToast(
            "You Need At Least " +
              BATCH_MIN_SAMPLES +
              " photos of fish to verify the batch",
            "error",
          );
          return;
        }
        // Make sure all samples are done processing
        var stillProcessing = state.batchSamples.some(function (s) {
          return s.status === "processing";
        });
        if (stillProcessing) {
          showToast(
            "Please Wait For All Photos To Finish Analyzing",
            "error",
          );
          return;
        }

        var v = computeBatchVerdict();
        var btn = this;
        var originalHtml = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML =
          '<i class="ri-loader-4-line" style="animation:spin 1s linear infinite;"></i> Verifying...';

        var payload = {
          samples: state.batchSamples.map(function (s) {
            return {
              name: s.name,
              cls: s.cls,
              label: s.label,
              conf: s.conf,
            };
          }),
          verdict: v.verdict,
          confidence: Math.round(
            state.batchSamples.reduce(function (sum, s) {
              return sum + s.conf;
            }, 0) / state.batchSamples.length,
          ),
        };

        apiRequest("POST", "/batches", payload, function (result) {
          btn.disabled = false;
          btn.innerHTML = originalHtml;
          if (!result.id) {
            showToast(
              (result && result.message) || "Verification Failed",
              "error",
            );
            return;
          }

          state.batchComplete = true;
          renderBatch();
          /* The verdict is stored server-side; keep the finished batch on screen. */
          saveBatchDraft();

          // Scroll to summary
          setTimeout(function () {
            batchSummaryCard.scrollIntoView({
              behavior: "smooth",
              block: "start",
            });
          }, 200);

          showToast(
            "Batch verified! Overall verdict: " + result.freshness,
            "success",
          );
          pushNotification(
            result.cls === "fresh"
              ? "success"
              : result.cls === "notfresh"
                ? "error"
                : "warning",
            "Batch Verified — " + result.freshness,
            (result.sample_count != null
              ? result.sample_count
              : state.batchSamples.length) +
              " samples processed. " +
              (result.fresh_count || 0) +
              " fresh, " +
              (result.moderate_count || 0) +
              " moderately fresh, " +
              (result.not_fresh_count || 0) +
              " not fresh.",
            "scans",
            "history",
          );
          addBatchToHistory(result);
          refreshNotifCount();
        });
      });

      function addBatchToHistory(result) {
        var tbody = document.getElementById("historyBody");
        var tr = document.createElement("tr");
        var badgeCls = result.cls || "moderate";
        tr.setAttribute("data-freshness", badgeCls);
        tr.setAttribute(
          "data-search",
          (result.name + " mixed batch " + result.freshness).toLowerCase(),
        );
        tr.setAttribute("data-name", result.name);
        tr.setAttribute("data-species", "Mixed");
        tr.setAttribute("data-freshness-label", result.freshness);
        tr.setAttribute("data-percentage", result.percentage);
        tr.setAttribute("data-confidence", result.confidence);
        tr.setAttribute("data-type", "Batch");
        tr.innerHTML =
          '<td><div class="td-with-icon"><i class="ri-stack-line"></i><span>' +
          escapeHtml(result.name) +
          "</span></div></td>" +
          '<td><span class="mobile-label">Species</span>Mixed</td>' +
          '<td><span class="mobile-label">Freshness</span><span class="badge ' +
          badgeCls +
          '">' +
          escapeHtml(result.freshness) +
          " · " +
          result.percentage +
          "%</span></td>" +
          '<td><span class="mobile-label">Confidence</span>' +
          (Number(result.confidence) || 0).toFixed(2) +
          "</td>" +
          '<td><span class="mobile-label">Date</span>Just now</td>' +
          '<td><span class="mobile-label">Type</span>Batch</td>';
        tbody.insertBefore(tr, tbody.firstChild);
      }

      /* Initialize batch view (restores any batch kept from a previous visit) */
      loadBatchDraft();
      renderBatch();

