      /* ================= SCAN / UPLOAD ================= */
      var uploadFile = document.getElementById("uploadFile");
      var imagePreview = document.getElementById("imagePreview");
      var scanCameraInput = document.getElementById("scanCameraInput");
      var cameraPreview = document.getElementById("cameraPreview");
      var cameraVideo = document.getElementById("cameraVideo");
      var cameraSnapshot = document.getElementById("cameraSnapshot");
      var cameraLiveBadge = document.getElementById("cameraLiveBadge");
      var openCameraBtn = document.getElementById("openCameraBtn");
      var captureBtn = document.getElementById("captureBtn");
      var switchCameraBtn = document.getElementById("switchCameraBtn");
      var closeCameraBtn = document.getElementById("closeCameraBtn");
      var uploadFromScanBtn = document.getElementById("uploadFromScanBtn");
      var uploadFileChip = document.getElementById("uploadFileChip");
      var uploadFileName = document.getElementById("uploadFileName");
      var uploadFileClear = document.getElementById("uploadFileClear");
      var imagePreviewPlaceholder =
        '<i class="ri-image-add-line" style="font-size: 1.9rem; color: var(--muted)"></i>' +
        '<p class="text-muted text-xs" style="margin-top: 0.35rem">Preview will appear here</p>';

      /* The chosen file's name chip under the upload drop zone. */
      function showUploadChip(file) {
        if (uploadFileName) uploadFileName.textContent = file ? file.name : "";
        if (uploadFileChip) uploadFileChip.classList.toggle("hidden", !file);
      }

      function handleImageFile(file, successMessage) {
        if (!file) return;
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
          imagePreview.innerHTML =
            '<img src="' + e.target.result + '" alt="Preview">';
          if (cameraSnapshot) cameraSnapshot.src = e.target.result;
          if (cameraPreview) cameraPreview.classList.add("has-snapshot");
          state.capturedFile = file;
          showUploadChip(file);
          showToast(successMessage || "Image Loaded Successfully", "success");
        };
        reader.readAsDataURL(file);
      }

      uploadFile.addEventListener("change", function () {
        handleImageFile(this.files[0]);
      });
      scanCameraInput.addEventListener("change", function () {
        handleImageFile(this.files[0]);
      });

      /* Remove button on the selected-file chip: clears the pending image. */
      if (uploadFileClear) {
        uploadFileClear.addEventListener("click", function () {
          state.capturedFile = null;
          if (uploadFile) uploadFile.value = "";
          if (scanCameraInput) scanCameraInput.value = "";
          if (imagePreview) imagePreview.innerHTML = imagePreviewPlaceholder;
          if (cameraPreview) cameraPreview.classList.remove("has-snapshot");
          showUploadChip(null);
          showToast("Image Removed", "info");
        });
      }
      /* ================= LIVE CAMERA (getUserMedia) =================
         Shared camera controller. The Scan page and the Batch capture card
         each get their own instance (own stream, own buttons, own preview). */
      function cameraSupported() {
        return !!(
          navigator.mediaDevices &&
          typeof navigator.mediaDevices.getUserMedia === "function"
        );
      }

      function toggleHidden(el, isHidden) {
        if (el) el.classList.toggle("hidden", isHidden);
      }

      function createFishCamera(config) {
        var ctrl = {
          stream: null,
          facingMode: "environment",
          live: false,
        };

        function updateControls() {
          toggleHidden(config.openBtn, ctrl.live);
          toggleHidden(config.captureBtn, !ctrl.live);
          toggleHidden(config.switchBtn, !ctrl.live);
          toggleHidden(config.closeBtn, !ctrl.live);
          toggleHidden(config.liveBadge, !ctrl.live);
        }

        function stop() {
          if (ctrl.stream) {
            ctrl.stream.getTracks().forEach(function (track) {
              track.stop();
            });
            ctrl.stream = null;
          }
          var video = document.getElementById(config.videoId);
          if (video) video.srcObject = null;
          var preview = document.getElementById(config.previewId);
          if (preview) preview.classList.remove("camera-live");
          ctrl.live = false;
          updateControls();
        }

        function start() {
          if (ctrl.live) return;
          ctrl.facingMode = settings.cameraFacing || "environment";
          if (!cameraSupported()) {
            showToast(
              "Live Camera Unavailable - Opening the Camera App",
              "info",
            );
            if (config.fallbackInput) config.fallbackInput.click();
            return;
          }
          if (!window.isSecureContext) {
            showToast(
              "Camera Needs HTTPS Or Localhost - " + config.uploadHint,
              "error",
            );
            return;
          }
          navigator.mediaDevices
            .getUserMedia({
              audio: false,
              video: {
                facingMode: ctrl.facingMode,
                width: { ideal: 1280 },
                height: { ideal: 720 },
              },
            })
            .then(function (stream) {
              ctrl.stream = stream;
              var video = document.getElementById(config.videoId);
              if (video) {
                video.srcObject = stream;
                var playing = video.play();
                if (playing && playing.catch) playing.catch(function () {});
              }
              ctrl.live = true;
              var preview = document.getElementById(config.previewId);
              if (preview) preview.classList.add("camera-live");
              updateControls();
              showToast(config.readyMessage, "info");
            })
            .catch(function (err) {
              ctrl.live = false;
              updateControls();
              var name = err && err.name;
              if (name === "NotAllowedError" || name === "SecurityError") {
                showToast(
                  "Camera Permission Denied - " + config.uploadHint,
                  "error",
                );
              } else if (
                name === "NotFoundError" ||
                name === "OverconstrainedError"
              ) {
                showToast("No Camera Found - " + config.uploadHint, "error");
              } else {
                showToast(
                  "Unable To Start Camera - " + config.uploadHint,
                  "error",
                );
              }
            });
        }

        function switchFacing() {
          var wasLive = ctrl.live;
          ctrl.facingMode =
            ctrl.facingMode === "environment" ? "user" : "environment";
          stop();
          if (wasLive) start();
        }

        function capture() {
          var video = document.getElementById(config.videoId);
          if (!video || !video.videoWidth || !video.videoHeight) {
            showToast(
              "Camera Is Still Starting - Try Again In a Moment",
              "error",
            );
            return;
          }
          var canvas = document.getElementById(config.canvasId);
          if (!canvas || !canvas.getContext) {
            showToast("Capture Is Not Supported On This Browser", "error");
            return;
          }
          var preset =
            QUALITY_PRESETS[settings.imageQuality] || QUALITY_PRESETS.balanced;
          var scale = Math.min(1, preset.maxWidth / video.videoWidth);
          canvas.width = Math.round(video.videoWidth * scale);
          canvas.height = Math.round(video.videoHeight * scale);
          var context = canvas.getContext("2d");
          context.drawImage(video, 0, 0, canvas.width, canvas.height);
          if (!canvas.toBlob) {
            showToast("Capture Is Not Supported On This Browser", "error");
            return;
          }
          canvas.toBlob(
            function (blob) {
              if (!blob) {
                showToast("Capture Failed - Please Try Again", "error");
                return;
              }
              stop();
              config.onCapture(
                new File([blob], "capture-" + Date.now() + ".jpg", {
                  type: "image/jpeg",
                }),
              );
            },
            "image/jpeg",
            preset.jpeg,
          );
        }

        return {
          start: start,
          stop: stop,
          switchFacing: switchFacing,
          capture: capture,
          updateControls: updateControls,
        };
      }

      /* Scan Fish camera */
      var scanCamera = createFishCamera({
        videoId: "cameraVideo",
        previewId: "cameraPreview",
        canvasId: "cameraCanvas",
        liveBadge: cameraLiveBadge,
        openBtn: openCameraBtn,
        captureBtn: captureBtn,
        switchBtn: switchCameraBtn,
        closeBtn: closeCameraBtn,
        fallbackInput: scanCameraInput,
        uploadHint: "Use Upload Image Instead",
        readyMessage: "Camera Ready - Frame the Fish And Tap Capture",
        onCapture: function (file) {
          handleImageFile(file, "Photo Captured - Tap Analyze Fish");
        },
      });

      function startCamera() {
        scanCamera.start();
      }
      function stopCameraStream() {
        scanCamera.stop();
      }
      function switchCamera() {
        scanCamera.switchFacing();
      }
      function capturePhoto() {
        scanCamera.capture();
      }

      if (openCameraBtn) {
        openCameraBtn.addEventListener("click", function () {
          showPage("scan");
          startCamera();
        });
      }
      if (captureBtn) captureBtn.addEventListener("click", capturePhoto);
      if (switchCameraBtn)
        switchCameraBtn.addEventListener("click", switchCamera);
      if (closeCameraBtn)
        closeCameraBtn.addEventListener("click", stopCameraStream);
      if (uploadFromScanBtn) {
        uploadFromScanBtn.addEventListener("click", function () {
          showPage("scan");
          uploadFile.click();
        });
      }
      /* The mobile camera button opens the scanner directly. */
      var cameraNavItem = document.querySelector(".mobile-nav-item.camera-nav");
      if (cameraNavItem) {
        cameraNavItem.addEventListener("click", function () {
          startCamera();
        });
      }

      cameraPreview.addEventListener("dragover", function (e) {
        e.preventDefault();
        this.style.borderColor = "var(--primary)";
      });
      cameraPreview.addEventListener("dragleave", function () {
        this.style.borderColor = "";
      });
      cameraPreview.addEventListener("drop", function (e) {
        e.preventDefault();
        this.style.borderColor = "";
        if (e.dataTransfer.files.length)
          handleImageFile(e.dataTransfer.files[0]);
      });

