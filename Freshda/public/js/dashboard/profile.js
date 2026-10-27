      /* ================= PROFILE ================= */
      /* Paint an avatar with the current initials plus the uploaded photo when
         one exists. Resetting textContent first drops a stale <img>, so
         removing the photo falls back to the initials. */
      function applyAvatarState(el, initials, url) {
        if (!el) return;
        el.textContent = initials;
        if (!url) return;
        var img = document.createElement("img");
        img.src = url;
        img.alt = "";
        img.onerror = function () {
          img.remove();
        };
        el.prepend(img);
      }

      function updateProfileUi(res) {
        var nameEl = document.querySelector(".sidebar-user-info .name");
        if (nameEl) nameEl.textContent = res.name;
        var roleEl = document.querySelector(".sidebar-user-info .role");
        if (roleEl && bootData.user && bootData.user.role_label) {
          roleEl.textContent = bootData.user.role_label;
        }
        var avatars = document.querySelectorAll(".avatar, .topbar-avatar");
        avatars.forEach(function (el) {
          applyAvatarState(el, res.initials, res.profile_picture_url);
        });
        var profileAvatarEl = document.getElementById("profileAvatar");
        if (profileAvatarEl) {
          applyAvatarState(profileAvatarEl, res.initials, res.profile_picture_url);
          profileAvatarEl.setAttribute(
            "data-profile-picture-path",
            res.profile_picture_path || "",
          );
        }
        var photoBtn = document.getElementById("removeProfilePictureBtn");
        if (photoBtn) {
          photoBtn.style.display = res.profile_picture_url ? "" : "none";
        }

        var docName = document.querySelector(".profile-display-name");
        if (docName) docName.textContent = res.name;
        var docEmail = document.querySelector(".profile-display-email");
        if (docEmail) docEmail.textContent = res.email;

        var viewName = document.getElementById("viewName");
        if (viewName) viewName.textContent = res.name;
        var viewEmail = document.getElementById("viewEmail");
        if (viewEmail) viewEmail.textContent = res.email;
        var viewContactField = document.getElementById("viewContactField");
        var viewContact = document.getElementById("viewContact");
        if (viewContactField && viewContact) {
          if (res.contact_number) {
            viewContact.textContent = res.contact_number;
            viewContactField.style.display = "";
          } else {
            viewContactField.style.display = "none";
          }
        }
        var viewAddressField = document.getElementById("viewAddressField");
        var viewAddress = document.getElementById("viewAddress");
        if (viewAddressField && viewAddress) {
          if (res.address) {
            viewAddress.textContent = res.address;
            viewAddressField.style.display = "";
          } else {
            viewAddressField.style.display = "none";
          }
        }
      }

      function fillProfileForm(res) {
        var nameEl = document.getElementById("profileName");
        if (nameEl) nameEl.value = res.name;
        var emailEl = document.getElementById("profileEmail");
        if (emailEl) emailEl.value = res.email;
        var contactEl = document.getElementById("profileContact");
        if (contactEl) contactEl.value = res.contact_number || "";
        var addressEl = document.getElementById("profileAddress");
        if (addressEl) addressEl.value = res.address || "";
      }

      function applyProfileFormError(errors) {
        if (!errors) return;
        var first = null;
        Object.keys(errors).forEach(function (key) {
          var el = document.getElementById("profile" + key.charAt(0).toUpperCase() + key.slice(1));
          if (el) {
            el.classList.add("input-error");
            el.setAttribute("aria-invalid", "true");
            if (!first) first = el;
          }
        });
        if (first) first.focus();
      }

      function clearProfileFormErrors() {
        var els = document.querySelectorAll(".input-error");
        els.forEach(function (el) {
          el.classList.remove("input-error");
          el.removeAttribute("aria-invalid");
        });
      }

      function saveProfileFromForm() {
        clearProfileFormErrors();
        var name = (document.getElementById("profileName").value || "").trim();
        var email = (document.getElementById("profileEmail").value || "").trim();
        var contact = (document.getElementById("profileContact").value || "").trim();
        var address = (document.getElementById("profileAddress").value || "").trim();

        if (!name || !email) {
          showToast("Name And Email Are Required", "error");
          return;
        }
        if (email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
          var emailEl = document.getElementById("profileEmail");
          if (emailEl) {
            emailEl.classList.add("input-error");
            emailEl.setAttribute("aria-invalid", "true");
            emailEl.focus();
          }
          showToast("Enter a Valid Email Address", "error");
          return;
        }

        var btn = document.getElementById("saveProfileBtn");
        if (btn) btn.disabled = true;

        function onSaved(res) {
          if (btn) btn.disabled = false;

          if (!res.name) {
            applyProfileFormError(res.errors);
            showToast((res.message || "Failed To Save Profile"), "error");
            return;
          }

          updateProfileUi(res);
          fillProfileForm(res);
          pendingProfilePicture = null;
          var pictureInput = document.getElementById("profilePictureInput");
          if (pictureInput) pictureInput.value = "";
          showToast("Profile Updated Successfully", "success");
        }

        function onSaveFailed() {
          if (btn) btn.disabled = false;
        }

        /* A File cannot be JSON-stringified, so uploads go through FormData.
           POST + _method=PATCH is used because some servers (including the
           PHP built-in server) do not parse multipart bodies on PATCH. */
        if (pendingProfilePicture) {
          var formData = new FormData();
          formData.append("_method", "PATCH");
          formData.append("name", name);
          formData.append("email", email);
          formData.append("contact_number", contact);
          formData.append("address", address);
          formData.append("profile_picture", pendingProfilePicture);

          fetch("/profile", {
            method: "POST",
            headers: {
              "X-CSRF-TOKEN": csrfToken(),
              "Accept": "application/json",
            },
            body: formData,
          })
            .then(function (response) {
              return response.json().catch(function () {
                return {};
              });
            })
            .then(onSaved)
            .catch(function () {
              showToast("Network Error — Please Try Again", "error");
              onSaveFailed();
            });
          return;
        }

        apiRequest(
          "PATCH",
          "/profile",
          { name: name, email: email, contact_number: contact, address: address },
          onSaved,
          onSaveFailed,
        );
      }

      var pendingProfilePicture = null;

      function initProfile() {
        /* Show the stored profile photo on page load. */
        var avatarWrap = document.getElementById("profileAvatar");
        if (avatarWrap && !avatarWrap.querySelector("img")) {
          var storedPath = avatarWrap.getAttribute("data-profile-picture-path");
          if (storedPath) {
            var storedImg = document.createElement("img");
            storedImg.src =
              "/storage/" + String(storedPath).replace(/^\/+/, "");
            storedImg.alt = "Profile Picture";
            storedImg.onerror = function () {
              storedImg.remove();
            };
            avatarWrap.prepend(storedImg);
          }
        }

        var editBtn = document.getElementById("editProfileBtn");
        var editBtnView = document.getElementById("editProfileBtnView");
        var formEl = document.getElementById("profileForm");
        var viewEl = document.getElementById("profileView");
        var saveBtn = document.getElementById("saveProfileBtn");
        var cancelBtn = document.getElementById("cancelProfileBtn");
        var pictureInput = document.getElementById("profilePictureInput");
        var changeBtn = document.getElementById("changeProfilePictureBtn");
        var removeBtn = document.getElementById("removeProfilePictureBtn");

        /* A stored photo can always be removed, so keep the button visible on
           load instead of only after a new file is picked. */
        if (removeBtn && avatarWrap && avatarWrap.getAttribute("data-profile-picture-path")) {
          removeBtn.style.display = "";
        }

        function setEditing(editing) {
          if (!formEl || !viewEl) return;
          formEl.style.display = editing ? "" : "none";
          viewEl.style.display = editing ? "none" : "";
          if (editing) {
            var currentName = "";
            var currentEmail = "";
            var currentContact = "";
            var currentAddress = "";
            var viewNameEl = document.getElementById("viewName");
            var viewEmailEl = document.getElementById("viewEmail");
            var contactEl = document.getElementById("profileContact");
            var addressEl = document.getElementById("profileAddress");
            if (viewNameEl) currentName = (viewNameEl.textContent || "").trim();
            if (viewEmailEl) currentEmail = (viewEmailEl.textContent || "").trim();
            if (contactEl) currentContact = contactEl.value || "";
            if (addressEl) currentAddress = addressEl.value || "";
            fillProfileForm({
              name: currentName,
              email: currentEmail,
              contact_number: currentContact,
              address: currentAddress,
            });
            clearProfileFormErrors();
          }
        }

        setEditing(false);

        function startEdit() {
          setEditing(true);
        }
        if (editBtn) editBtn.addEventListener("click", startEdit);
        if (editBtnView) editBtnView.addEventListener("click", startEdit);

        var editPersonalBtn = document.getElementById("editPersonalBtn");
        if (editPersonalBtn)
          editPersonalBtn.addEventListener("click", startEdit);

        /* ================= CHANGE PASSWORD MODAL ================= */
        function openChangePasswordModal() {
          var modal = document.getElementById("modalOverlay");
          if (!modal) return;

          document.getElementById("modalTitle").textContent = "Change Password";
          document.getElementById("modalBody").innerHTML =
            '<div class="confirm-action">' +
            '<span class="confirm-action-icon"><i class="ri-lock-password-line"></i></span>' +
            "<p>Enter your current password and choose a new one. Your session stays active.</p>" +
            '<div class="form-group"><label class="form-label" for="cpCurrent">Current password</label>' +
            '<input type="password" class="form-input" id="cpCurrent" autocomplete="current-password"></div>' +
            '<div class="form-group"><label class="form-label" for="cpNew">New password</label>' +
            '<input type="password" class="form-input" id="cpNew" autocomplete="new-password"></div>' +
            '<div class="form-group"><label class="form-label" for="cpConfirm">Confirm new password</label>' +
            '<input type="password" class="form-input" id="cpConfirm" autocomplete="new-password"></div>' +
            '<p class="confirm-action-note" id="cpError" style="display:none; color:#dc2626;"></p>' +
            "</div>";

          var footer = document.getElementById("modalFooter");
          footer.innerHTML = "";

          var cancelBtn = document.createElement("button");
          cancelBtn.className = "btn btn-secondary";
          cancelBtn.textContent = "Cancel";
          cancelBtn.addEventListener("click", function () {
            modal.classList.remove("open");
          });

          var saveBtn = document.createElement("button");
          saveBtn.className = "btn btn-primary";
          saveBtn.innerHTML = '<i class="ri-lock-password-line"></i> Update password';
          saveBtn.addEventListener("click", function () {
            var current = document.getElementById("cpCurrent").value;
            var next = document.getElementById("cpNew").value;
            var confirm = document.getElementById("cpConfirm").value;
            var errEl = document.getElementById("cpError");

            function fail(msg) {
              errEl.textContent = msg;
              errEl.style.display = "block";
              saveBtn.disabled = false;
            }

            if (!current || !next || !confirm) {
              fail("Please Fill In All Three Fields.");
              return;
            }
            if (next.length < 8) {
              fail("New Password Must Be At Least 8 Characters.");
              return;
            }
            if (next !== confirm) {
              fail("New Password And Confirmation Do Not Match.");
              return;
            }

            saveBtn.disabled = true;
            errEl.style.display = "none";
            apiRequest(
              "PATCH",
              "/password",
              {
                current_password: current,
                password: next,
                password_confirmation: confirm,
              },
              function (res) {
                saveBtn.disabled = false;
                if (res && res.message === "Password Updated Successfully.") {
                  modal.classList.remove("open");
                  showToast("Password Updated Successfully.", "success");
                  return;
                }
                var errors = (res && res.errors) || {};
                if (errors.current_password) {
                  fail(errors.current_password[0]);
                } else if (errors.password) {
                  fail(errors.password[0]);
                } else {
                  fail((res && res.message) || "Could Not Update the Password.");
                }
              },
              function () {
                saveBtn.disabled = false;
              },
            );
          });

          footer.appendChild(cancelBtn);
          footer.appendChild(saveBtn);
          modal.classList.add("open");
          var first = document.getElementById("cpCurrent");
          if (first) first.focus();
        }

        var changePasswordBtn = document.getElementById("changePasswordBtn");
        if (changePasswordBtn)
          changePasswordBtn.addEventListener("click", openChangePasswordModal);

        var profileChangePasswordBtn = document.getElementById(
          "profileChangePasswordBtn",
        );
        if (profileChangePasswordBtn)
          profileChangePasswordBtn.addEventListener(
            "click",
            openChangePasswordModal,
          );

        var settingsChangePasswordBtn = document.getElementById(
          "settingsChangePasswordBtn",
        );
        if (settingsChangePasswordBtn)
          settingsChangePasswordBtn.addEventListener(
            "click",
            openChangePasswordModal,
          );

        if (saveBtn) {
          saveBtn.addEventListener("click", function () {
            saveProfileFromForm();
          });
        }

        if (cancelBtn) {
          cancelBtn.addEventListener("click", function () {
            setEditing(false);
            clearProfileFormErrors();
          });
        }

        if (pictureInput) {
          pictureInput.addEventListener("change", function () {
            var file = pictureInput.files && pictureInput.files[0];
            if (!file) return;
            if (!file.type.startsWith("image/")) {
              showToast("Please Choose an Image File", "error");
              pictureInput.value = "";
              return;
            }
            if (file.size > 5 * 1024 * 1024) {
              showToast("Profile Photo Must Be Smaller Than 5MB", "error");
              pictureInput.value = "";
              return;
            }
            pendingProfilePicture = file;
            if (removeBtn) removeBtn.style.display = "";
            uploadProfilePicture(file);
          });
        }

        /* Upload the picked photo immediately so the avatar changes without
           requiring the Edit -> Save flow. Edit -> Save still retries it as
           a fallback when this upload fails. */
        function uploadProfilePicture(file) {
          function viewValue(id) {
            var el = document.getElementById(id);
            return el ? (el.textContent || "").trim() : "";
          }

          var fd = new FormData();
          fd.append("_method", "PATCH");
          fd.append("name", viewValue("viewName"));
          fd.append("email", viewValue("viewEmail"));
          fd.append("contact_number", viewValue("viewContact"));
          fd.append("address", viewValue("viewAddress"));
          fd.append("profile_picture", file);

          fetch("/profile", {
            method: "POST",
            headers: {
              "X-CSRF-TOKEN": csrfToken(),
              "Accept": "application/json",
            },
            body: fd,
          })
            .then(function (r) {
              return r.json().catch(function () {
                return {};
              });
            })
            .then(function (res) {
              if (!res.profile_picture_url) {
                showToast(
                  (res && res.message) ||
                    "Photo Upload Failed — Try Edit Profile Then Save Changes",
                  "error",
                );
                return;
              }
              if (pendingProfilePicture !== file) return;
              pendingProfilePicture = null;
              if (pictureInput) pictureInput.value = "";
              updateProfileUi(res);
              showToast("Profile Photo Updated", "success");
            })
            .catch(function () {
              showToast("Network Error — Photo Not Uploaded", "error");
            });
        }

        if (changeBtn) {
          changeBtn.addEventListener("click", function (e) {
            /* Our own programmatic click on the input bubbles back here;
               returning early keeps its default action (file picker) alive. */
            if (e.target === pictureInput) return;
            e.preventDefault();
            if (pictureInput) pictureInput.click();
          });
        }

        if (removeBtn) {
          removeBtn.addEventListener("click", function () {
            var storedPath =
              (avatarWrap && avatarWrap.getAttribute("data-profile-picture-path")) || "";

            /* Nothing uploaded yet - just abandon the pending pick. */
            pendingProfilePicture = null;
            if (pictureInput) pictureInput.value = "";

            if (!storedPath) {
              removeBtn.style.display = "none";
              showToast("Profile Photo Removed", "info");
              return;
            }

            removeStoredProfilePicture();
          });
        }

        /* Delete the stored photo on the server so it stays gone after a
           refresh and on the next login. */
        function removeStoredProfilePicture() {
          function viewValue(id) {
            var el = document.getElementById(id);
            return el ? (el.textContent || "").trim() : "";
          }

          removeBtn.disabled = true;

          apiRequest(
            "PATCH",
            "/profile",
            {
              name: viewValue("viewName"),
              email: viewValue("viewEmail"),
              contact_number: viewValue("viewContact"),
              address: viewValue("viewAddress"),
              remove_profile_picture: 1,
            },
            function (res) {
              removeBtn.disabled = false;

              if (res && res.profile_picture_url) {
                showToast("Could Not Remove the Profile Photo", "error");
                return;
              }

              if (res && res.errors) {
                showToast("Could Not Remove the Profile Photo", "error");
                return;
              }

              updateProfileUi(res);
              showToast("Profile Photo Removed", "info");
            },
            function () {
              removeBtn.disabled = false;
            },
          );
        }
      }

      if (document.getElementById("saveProfileBtn")) initProfile();

      function getDefaultProfilePlaceholder() {
        return "Contact Number";
      }

