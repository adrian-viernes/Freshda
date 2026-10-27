          <!-- ============ BATCH SAMPLING (UPDATED) ============ -->
          <section id="batch-section" class="page-section hidden">
            <div class="page-header">
              <h1 data-i18n="page.batch.title">Batch Sampling Mode</h1>
              <p class="text-muted" data-i18n="page.batch.sub">
                Capture 3–5 photos of fish to analyze those fish and verify the
                freshness of the whole batch
              </p>
            </div>

            <!-- Capture Card (live camera, same capture flow as Scan Fish) -->
            <div class="card">
              <div class="card-header">
                <span class="card-title"
                  ><i class="ri-camera-lens-line"></i> Capture Fish Photo</span
                >
                <span class="batch-sample-counter" id="batchCaptureQuota">
                  <i class="ri-image-line"></i> 0 of 5 captured
                </span>
              </div>
              <div class="camera-preview" id="batchCamFrame">
                <div class="camera-placeholder">
                  <i class="ri-camera-line"></i>
                  <p>Camera Preview</p>
                  <span>Capture 3 To 5 Fish Photos, One At a Time</span>
                </div>
                <video
                  id="batchCamVideo"
                  class="camera-video"
                  playsinline
                  autoplay
                  muted
                ></video>
                <img
                  id="batchCamSnapshot"
                  class="camera-snapshot"
                  alt="Captured fish"
                />
                <span class="camera-live-badge hidden" id="batchCamLiveBadge">
                  <span class="camera-live-dot"></span>LIVE
                </span>
                <canvas id="batchCamCanvas" class="hidden"></canvas>
              </div>
              <div class="camera-actions">
                <button
                  class="btn btn-primary btn-block"
                  id="batchOpenCameraBtn"
                >
                  <i class="ri-camera-line"></i> Open Camera
                </button>
                <button
                  class="btn btn-primary btn-block hidden"
                  id="batchCaptureBtn"
                >
                  <i class="ri-camera-lens-line"></i> Capture Photo
                </button>
                <button
                  class="btn btn-secondary hidden"
                  id="batchSwitchCameraBtn"
                  title="Switch camera"
                >
                  <i class="ri-camera-switch-line"></i>
                </button>
                <button
                  class="btn btn-secondary hidden"
                  id="batchCloseCameraBtn"
                  title="Close camera"
                >
                  <i class="ri-close-line"></i>
                </button>
                <button class="btn btn-secondary btn-block" id="batchUploadBtn">
                  <i class="ri-upload-2-line"></i> Upload Photo
                </button>
              </div>
              <input
                type="file"
                id="batchCameraInput"
                accept="image/*"
                capture="environment"
                style="display: none"
              />
              <input
                type="file"
                id="batchUploadInput"
                accept="image/*"
                style="display: none"
              />
              <button
                class="btn btn-primary btn-block hidden"
                id="batchAnalyzeBtn"
                data-confirm="Analyze this photo and add it to the batch?"
                data-confirm-title="Analyze fish photo"
                data-confirm-note="The captured photo is analyzed and added to the current batch."
                data-confirm-ok="Analyze &amp; add"
                data-confirm-icon="ri-cpu-line"
              >
                <i class="ri-cpu-line"></i> Analyze &amp; Add to Batch
              </button>
              <p
                class="text-muted text-xs"
                style="margin-top: 0.9rem; text-align: center"
              >
                <i class="ri-information-line"></i> Max file size: 10 MB · JPG,
                PNG · 3–5 photos per batch
              </p>
            </div>

            <!-- Progress Card -->
            <div class="batch-progress-card">
              <div class="batch-progress-icon">
                <i class="ri-stack-line"></i>
              </div>
              <div class="batch-progress-info">
                <div class="batch-progress-title">
                  Batch Verification Progress
                  <span
                    class="batch-status-badge pending"
                    id="batchStatusBadge"
                  >
                    <i class="ri-time-line"></i> Need more photos
                  </span>
                </div>
                <div class="batch-progress-desc">
                  Capture <strong>3 To 5 Photos Of Fish</strong> so each fish
                  can be analyzed and the freshness of the entire batch
                  verified.
                </div>
                <div class="batch-progress-bar">
                  <div class="batch-progress-fill" id="batchProgressFill"></div>
                </div>
                <div class="batch-progress-count">
                  <span
                    ><strong id="batchCurrentCount">0</strong> photos
                    captured · <strong>3–5</strong> Required</span
                  >
                  <span id="batchProgressPercent">0%</span>
                </div>
              </div>
            </div>

            <!-- Requirement Note -->
            <div class="batch-requirement-note" id="batchRequirementNote">
              <i class="ri-information-line"></i>
              <span>
                <strong>Requirement:</strong> Capture at least
                <strong>3</strong> And Up To <strong>5 Photos Of Fish</strong>
                in this batch so each fish is analyzed and a reliable freshness
                verdict can be computed for the whole batch.
              </span>
            </div>

            <div class="card">
              <div class="card-header">
                <span class="card-title"
                  ><i class="ri-stack-line"></i> Current Batch Photos</span
                >
                <span class="batch-sample-counter" id="batchCountBadge">
                  <i class="ri-image-line"></i> 0 photos
                </span>
              </div>
              <div class="scan-list" id="batchList">
                <!-- Empty state handled by JS -->
              </div>

              <div class="batch-actions-bar">
                <button
                  class="btn btn-secondary"
                  id="cancelBatchBtn"
                  data-confirm="Discard all captured photos in this batch?"
                  data-confirm-title="Cancel batch"
                  data-confirm-note="The photos you captured so far will be removed."
                  data-confirm-ok="Cancel batch"
                  data-confirm-danger="1"
                >
                  <i class="ri-close-line"></i> Cancel Batch
                </button>
                <button
                  class="btn btn-primary"
                  id="finishBatchBtn"
                  disabled
                  data-confirm="Verify this batch and save its verdict?"
                  data-confirm-title="Verify batch"
                  data-confirm-note="The combined verdict is saved to your scan history."
                  data-confirm-ok="Verify &amp; save"
                  data-confirm-icon="ri-check-double-line"
                >
                  <i class="ri-check-double-line"></i> Finish &amp; Verify Batch
                </button>
              </div>
            </div>

            <!-- Batch Summary (only shows when batch is complete) -->
            <div class="batch-summary-card hidden" id="batchSummaryCard">
              <div class="batch-summary-header">
                <h3><i class="ri-bar-chart-box-line"></i> Batch Summary</h3>
                <span class="batch-status-badge complete">
                  <i class="ri-check-double-line"></i> Verification Complete
                </span>
              </div>

              <div class="batch-summary-grid">
                <div class="batch-summary-item">
                  <div class="batch-summary-icon fresh">
                    <i class="ri-checkbox-circle-line"></i>
                  </div>
                  <div>
                    <div class="batch-summary-value" id="batchFreshCount">
                      0
                    </div>
                    <div class="batch-summary-label">Fresh</div>
                  </div>
                </div>
                <div class="batch-summary-item">
                  <div class="batch-summary-icon moderate">
                    <i class="ri-error-warning-line"></i>
                  </div>
                  <div>
                    <div class="batch-summary-value" id="batchModerateCount">
                      0
                    </div>
                    <div class="batch-summary-label">Moderate</div>
                  </div>
                </div>
                <div class="batch-summary-item">
                  <div class="batch-summary-icon notfresh">
                    <i class="ri-close-circle-line"></i>
                  </div>
                  <div>
                    <div class="batch-summary-value" id="batchNotFreshCount">
                      0
                    </div>
                    <div class="batch-summary-label">Not Fresh</div>
                  </div>
                </div>
              </div>

              <div class="batch-overall-verdict" id="batchOverallVerdict">
                <i class="ri-shield-check-line"></i>
                <div>
                  <strong
                    >Batch Verdict:
                    <span id="batchVerdictLabel">—</span></strong
                  >
                  <span id="batchVerdictDesc">Awaiting Analysis Completion</span>
                </div>
              </div>
            </div>
          </section>
