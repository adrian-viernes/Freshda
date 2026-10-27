      /* ================= LOGOUT CONFIRMATION =================
         Reuses the shared Fresh-DA modal. Cancel keeps the session;
         Logout submits the real Laravel logout form. */
      var logoutForm = document.getElementById("logoutForm");
      function confirmLogout() {
        var modal = document.getElementById("modalOverlay");
        if (!modal || !logoutForm) {
          if (logoutForm) logoutForm.submit();
          return;
        }
        document.getElementById("modalTitle").textContent = "Logout";
        document.getElementById("modalBody").innerHTML =
          '<div class="logout-confirm">' +
          '<span class="logout-confirm-icon"><i class="ri-logout-box-r-line"></i></span>' +
          "<h4>Are you sure you want to logout?</h4>" +
          "<p>You will be signed out of this device and need to enter your email and password again.</p>" +
          '<ul class="logout-confirm-list">' +
          '<li><i class="ri-check-line"></i> Your session will be closed</li>' +
          '<li><i class="ri-check-line"></i> Anything unsaved on this screen is discarded</li>' +
          '<li><i class="ri-check-line"></i> Your scans and inventory stay safe on your account</li>' +
          "</ul>" +
          "</div>";

        var footer = document.getElementById("modalFooter");
        footer.innerHTML = "";

        var cancelBtn = document.createElement("button");
        cancelBtn.className = "btn btn-secondary";
        cancelBtn.textContent = "Cancel";
        cancelBtn.addEventListener("click", function () {
          modal.classList.remove("open");
        });

        var confirmBtn = document.createElement("button");
        confirmBtn.className = "btn btn-danger";
        confirmBtn.innerHTML = '<i class="ri-logout-box-r-line"></i> Yes, logout';
        confirmBtn.addEventListener("click", function () {
          confirmBtn.disabled = true;
          logoutForm.submit();
        });

        footer.appendChild(cancelBtn);
        footer.appendChild(confirmBtn);
        modal.classList.add("open");
      }

      /* ================= GENERIC CONFIRMATION =================
         Content actions ask for confirmation through the shared modal
         instead of the browser's native confirm(). */
      function confirmAction(opts) {
        opts = opts || {};
        var modal = document.getElementById("modalOverlay");
        var run = function () {
          if (typeof opts.onConfirm === "function") opts.onConfirm();
        };
        if (!modal || !document.getElementById("modalBody")) {
          run();
          return;
        }
        document.getElementById("modalTitle").textContent =
          opts.title || "Please Confirm";
        document.getElementById("modalBody").innerHTML =
          '<div class="confirm-action">' +
          (opts.icon
            ? '<span class="confirm-action-icon"><i class="' +
              opts.icon +
              '"></i></span>'
            : "") +
          "<p>" +
          escapeHtml(opts.message || "Are You Sure?") +
          "</p>" +
          (opts.note
            ? '<p class="confirm-action-note">' + escapeHtml(opts.note) + "</p>"
            : "") +
          "</div>";

        var footer = document.getElementById("modalFooter");
        footer.innerHTML = "";

        var cancelBtn = document.createElement("button");
        cancelBtn.className = "btn btn-secondary";
        cancelBtn.textContent = opts.cancelText || "Cancel";
        cancelBtn.addEventListener("click", function () {
          modal.classList.remove("open");
        });

        var okBtn = document.createElement("button");
        okBtn.className = opts.danger ? "btn btn-danger" : "btn btn-primary";
        okBtn.innerHTML =
          (opts.confirmIcon ? '<i class="' + opts.confirmIcon + '"></i> ' : "") +
          escapeHtml(opts.confirmText || "Confirm");
        okBtn.addEventListener("click", function () {
          okBtn.disabled = true;
          modal.classList.remove("open");
          run();
        });

        footer.appendChild(cancelBtn);
        footer.appendChild(okBtn);
        modal.classList.add("open");
      }

      /*
       * Content actions in the blades carry data-confirm="<question>".
       * Capture the click, ask once through the modal, then replay the click
       * so the element's own handler runs untouched. Top-bar and sidebar
       * controls are deliberately not tagged.
       */
      document.addEventListener(
        "click",
        function (e) {
          var el = e.target.closest ? e.target.closest("[data-confirm]") : null;
          if (!el) return;
          if (el.dataset.confirmed === "1") {
            el.dataset.confirmed = "";
            return;
          }
          e.preventDefault();
          e.stopPropagation();
          confirmAction({
            title: el.dataset.confirmTitle || "Please Confirm",
            message: el.dataset.confirm,
            note: el.dataset.confirmNote,
            confirmText: el.dataset.confirmOk,
            confirmIcon: el.dataset.confirmIcon,
            danger: el.dataset.confirmDanger === "1",
            onConfirm: function () {
              el.dataset.confirmed = "1";
              el.click();
            },
          });
        },
        true,
      );

