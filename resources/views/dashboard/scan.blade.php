          <!-- ============ SCAN FISH ============ -->
          <section id="scan-section" class="page-section hidden">
            <div class="page-header">
              <h1 data-i18n="page.scan.title">Scan Fish</h1>
              <p class="text-muted" data-i18n="page.scan.sub">
                Capture or upload a fish image for AI analysis
              </p>
            </div>

            <div class="grid-2">
              <div class="card">
                <div class="card-header">
                  <span class="card-title"
                    ><i class="ri-camera-lens-line"></i> Capture Image</span
                  >
                </div>
                <div class="camera-preview" id="cameraPreview">
                  <div class="camera-placeholder" id="cameraPlaceholder">
                    <i class="ri-camera-line"></i>
                    <p>Camera Preview</p>
                    <span>Ensure Good Lighting And the Fish Is Clearly Visible</span>
                  </div>
                  <video
                    id="cameraVideo"
                    class="camera-video"
                    playsinline
                    autoplay
                    muted
                  ></video>
                  <img
                    id="cameraSnapshot"
                    class="camera-snapshot"
                    alt="Captured fish"
                  />
                  <span class="camera-live-badge hidden" id="cameraLiveBadge">
                    <span class="camera-live-dot"></span>LIVE
                  </span>
                  <canvas id="cameraCanvas" class="hidden"></canvas>
                </div>
                <div class="camera-actions">
                  <button class="btn btn-primary btn-block" id="openCameraBtn">
                    <i class="ri-camera-line"></i> Open Camera
                  </button>
                  <button
                    class="btn btn-primary btn-block hidden"
                    id="captureBtn"
                  >
                    <i class="ri-camera-lens-line"></i> Capture Photo
                  </button>
                  <button
                    class="btn btn-secondary hidden"
                    id="switchCameraBtn"
                    title="Switch camera"
                  >
                    <i class="ri-camera-switch-line"></i>
                  </button>
                  <button
                    class="btn btn-secondary hidden"
                    id="closeCameraBtn"
                    title="Close camera"
                  >
                    <i class="ri-close-line"></i>
                  </button>
                  <button
                    class="btn btn-secondary btn-block"
                    id="uploadFromScanBtn"
                  >
                    <i class="ri-upload-2-line"></i> Upload Image
                  </button>
                </div>
                <input
                  type="file"
                  id="scanCameraInput"
                  accept="image/*"
                  capture="environment"
                  style="display: none"
                />
                <p
                  class="text-muted text-xs"
                  style="margin-top: 0.9rem; text-align: center"
                >
                  <i class="ri-information-line"></i> Max file size: 10 MB ·
                  JPG, PNG
                </p>
              </div>

              <div class="flex-col gap-2">
                <div class="card">
                  <div class="card-header">
                    <span class="card-title"
                      ><i class="ri-upload-cloud-line"></i> Upload Fish
                      Image</span
                    >
                  </div>
                  <div class="form-group">
                    <label class="form-label">Choose File</label>
                    <label class="upload-drop" id="uploadDrop" for="uploadFile">
                      <span class="upload-drop-icon">
                        <i class="ri-upload-cloud-2-line"></i>
                      </span>
                      <span class="upload-drop-title">Click To Choose an Image</span>
                      <span class="upload-drop-sub">PNG or JPG · up to 10 MB</span>
                      <input
                        type="file"
                        id="uploadFile"
                        class="hidden"
                        accept="image/*"
                      />
                    </label>
                    <div class="upload-file-chip hidden" id="uploadFileChip">
                      <i class="ri-file-image-line"></i>
                      <span class="upload-file-name" id="uploadFileName"></span>
                      <button
                        type="button"
                        class="upload-file-remove"
                        id="uploadFileClear"
                        aria-label="Remove selected image"
                      >
                        <i class="ri-close-line"></i>
                      </button>
                    </div>
                  </div>
                  <div class="form-group">
                    <label class="form-label">Image Preview</label>
                    <div class="image-preview-box" id="imagePreview">
                      <i
                        class="ri-image-add-line"
                        style="font-size: 1.9rem; color: var(--muted)"
                      ></i>
                      <p class="text-muted text-xs" style="margin-top: 0.35rem">
                        Preview will appear here
                      </p>
                    </div>
                  </div>
                  <button
                    class="btn btn-primary btn-block"
                    id="analyzeBtn"
                    data-confirm="Run the AI freshness and species analysis on this image?"
                    data-confirm-title="Analyze fish image"
                    data-confirm-note="The result is saved to your scan history."
                    data-confirm-ok="Start analysis"
                    data-confirm-icon="ri-cpu-line"
                  >
                    <i class="ri-cpu-line"></i> Analyze Fish
                  </button>
                </div>

                <div class="card">
                  <div class="card-header">
                    <span class="card-title"
                      ><i class="ri-information-line"></i> Processing
                      States</span
                    >
                  </div>
                  <div class="processing-list">
                    <div class="processing-item">
                      <i class="ri-loader-4-line ok"></i> Analyzing your fish
                      image...
                    </div>
                    <div class="processing-item">
                      <i class="ri-check-line ok"></i> Analysis Complete
                    </div>
                    <div class="processing-item">
                      <i class="ri-error-warning-line warn"></i> Unable to
                      analyze this image
                    </div>
                    <div class="processing-item">
                      <i class="ri-cloud-off-line warn"></i> AI service
                      unavailable
                    </div>
                    <div class="processing-item">
                      <i class="ri-time-line warn"></i> Analysis timed out
                    </div>
                  </div>
                  <p
                    class="text-muted text-xs"
                    style="
                      margin-top: 0.9rem;
                      border-top: 1px solid var(--border);
                      padding-top: 0.75rem;
                    "
                  >
                    Freshness assessment is visible/image-based only. Does not
                    determine microbial spoilage, odor, or elasticity.
                  </p>
                </div>
              </div>
            </div>
          </section>
