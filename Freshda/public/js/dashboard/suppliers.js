      /* ================= SUPPLIERS ================= */
      /* The supplier book the deliveries page depends on: register a supplier,
         edit its details, deactivate it, and open its delivery history. The
         records mirror what the Blade tables rendered, so the modals and the
         delivery form can build their options without another request. */
      var DASH_DATA = window.DASHBOARD_DATA || {};
      var supplierRecords = {};
      var deliveryRecords = {};

      (DASH_DATA.suppliers || []).forEach(function (s) {
        supplierRecords[s.id] = s;
      });
      (DASH_DATA.deliveries || []).forEach(function (d) {
        deliveryRecords[d.id] = d;
      });

      function fmtKg(value) {
        var n = parseFloat(value);
        return (isNaN(n) ? 0 : n).toFixed(1);
      }

      function deliveryStatusLabel(status) {
        return status ? status.charAt(0).toUpperCase() + status.slice(1) : "Pending";
      }

      /* Actual minus expected, or null while nobody has checked the delivery
         in yet. */
      function deliveryVariance(delivery) {
        if (delivery.actual_quantity === null || delivery.actual_quantity === undefined) {
          return null;
        }
        return (
          Math.round(
            (parseFloat(delivery.actual_quantity) -
              parseFloat(delivery.expected_quantity)) *
              100,
          ) / 100
        );
      }

      function deliveryReceivedCell(delivery) {
        var variance = deliveryVariance(delivery);
        if (variance === null) {
          return '<span class="text-muted">Not checked yet</span>';
        }
        var cls = variance === 0 ? "even" : variance < 0 ? "short" : "over";
        var label =
          variance === 0
            ? "Matches expected"
            : (variance < 0 ? "Short by " : "Over by ") +
              Math.abs(variance) +
              " kg";
        return (
          '<span class="qty">' +
          fmtKg(delivery.actual_quantity) +
          '</span> kg <span class="badge ' +
          cls +
          '">' +
          label +
          "</span>"
        );
      }

      function supplierSearchText(supplier) {
        return (
          (supplier.name || "") +
          " " +
          (supplier.contact_person || "") +
          " " +
          (supplier.contact_summary || "") +
          " " +
          (supplier.address || "")
        ).toLowerCase();
      }

      function detailItem(label, value, raw, wide) {
        return (
          '<div class="detail-item' +
          (wide ? " wide" : "") +
          '"><span class="detail-label">' +
          label +
          '</span><span class="detail-value">' +
          (raw ? value : escapeHtml(value || "—")) +
          "</span></div>"
        );
      }

      function detailItemBadge(label, text, cls) {
        return (
          '<div class="detail-item"><span class="detail-label">' +
          label +
          '</span><span class="detail-value"><span class="badge ' +
          cls +
          '">' +
          escapeHtml(text) +
          "</span></span></div>"
        );
      }

      /* A titled block of detail items — the modals read as sections instead
         of one flat run of fields. */
      function detailSection(title, itemsHtml) {
        return (
          '<div class="detail-block-title">' +
          escapeHtml(title) +
          '</div><div class="detail-grid">' +
          itemsHtml +
          "</div>"
        );
      }

      /* Stock timestamps come back as ISO strings; show them like a person
         would read them, and leave anything unparseable untouched. */
      function formatTimestamp(value) {
        if (!value) return "";
        var date = new Date(value);
        if (isNaN(date.getTime())) return String(value);
        return (
          date.toLocaleDateString() +
          " " +
          date.toLocaleTimeString([], { hour: "2-digit", minute: "2-digit" })
        );
      }

      /* ================= THREE-DOT ROW MENU =================
         The action cell only holds the toggle. While the menu is open its
         list is lifted onto the body as a fixed-position popup, because both
         .table-card and the table cells clip their children. Menu actions are
         dispatched from here — not from the row listeners — since the open
         list no longer sits inside its row. */
      var openRowMenu = null;

      function closeRowMenu() {
        if (!openRowMenu) return;
        var current = openRowMenu;
        openRowMenu = null;
        current.menu.classList.remove("open");
        current.toggle.setAttribute("aria-expanded", "false");
        current.list.classList.remove("floating");
        current.list.style.top = "";
        current.list.style.left = "";
        if (current.list.parentNode !== current.menu) {
          current.menu.appendChild(current.list);
        }
      }

      /* Opens downward next to the three dots, flipping above them when the
         viewport has no room below. */
      function showRowMenu(menu) {
        var toggle = menu.querySelector(".row-actions-toggle");
        var list = menu.querySelector(".row-actions-list");
        if (!toggle || !list) return;

        menu.classList.add("open");
        toggle.setAttribute("aria-expanded", "true");
        list.classList.add("floating");
        document.body.appendChild(list);

        var rect = toggle.getBoundingClientRect();
        var width = list.offsetWidth;
        var height = list.offsetHeight;
        var left = Math.min(
          Math.max(8, rect.right - width),
          window.innerWidth - width - 8,
        );
        var top = rect.bottom + 6;
        if (top + height > window.innerHeight - 8) {
          top = rect.top - height - 6;
        }

        list.style.left = Math.max(8, left) + "px";
        list.style.top = Math.max(8, top) + "px";
        openRowMenu = { menu: menu, list: list, toggle: toggle };
      }

      function handleRowMenuAction(list, item) {
        var scope = list.getAttribute("data-scope");
        var id = list.getAttribute("data-id");
        var action = item.getAttribute("data-action");

        if (scope === "supplier") {
          var supplier = supplierRecords[id];
          if (!supplier) return;
          if (action === "details") openSupplierDetails(supplier);
          else if (action === "edit") openSupplierForm(supplier);
          else if (action === "toggle") toggleSupplierStatus(supplier);
          else if (action === "delete") deleteSupplier(supplier);
          return;
        }

        if (scope === "delivery") {
          var delivery = deliveryRecords[id];
          if (!delivery) return;
          if (action === "details") openDeliveryDetails(delivery);
          else if (action === "edit") openDeliveryForm(delivery);
          else if (action === "delete") deleteDelivery(delivery);
        }
      }

      document.addEventListener("click", function (e) {
        var target = e.target;
        if (!target || !target.closest) return;

        var toggle = target.closest(".row-actions-toggle");
        if (toggle) {
          var menu = toggle.closest(".row-actions-menu");
          if (!menu) return;
          var wasOpen = openRowMenu && openRowMenu.menu === menu;
          closeRowMenu();
          if (!wasOpen) showRowMenu(menu);
          return;
        }

        var item = target.closest(".row-actions-item");
        if (item) {
          var list = item.closest(".row-actions-list");
          closeRowMenu();
          if (list) handleRowMenuAction(list, item);
          return;
        }

        if (!target.closest(".row-actions-list")) closeRowMenu();
      });

      /* The popup is positioned once, so it follows neither the scroll nor
         the page change that could slide it away from its row. */
      window.addEventListener("scroll", closeRowMenu, true);
      window.addEventListener("resize", closeRowMenu);
      document.addEventListener("keydown", function (e) {
        if (e.key === "Escape") closeRowMenu();
      });

      /* ================= SHARED TABLE STATE =================
         Removing a row by hand (delete) must leave the table telling the
         truth: the "nothing recorded yet" row comes back on the last delete,
         while the "no search matches" row stays reserved for filtering. */
      function removeRow(tbodyId, id) {
        closeRowMenu();
        var tbody = document.getElementById(tbodyId);
        if (!tbody) return;
        var row = tbody.querySelector('tr[data-id="' + id + '"]');
        if (row) row.remove();
      }

      function syncTableEmptyState(opts) {
        var tbody = document.getElementById(opts.bodyId);
        if (!tbody) return;

        var rows = tbody.querySelectorAll("tr[data-id]");
        var emptyRow = tbody.querySelector("." + opts.emptyClass);

        if (rows.length > 0) {
          if (emptyRow) emptyRow.remove();
          opts.applyFilters();
          return;
        }

        var filterEmpty = document.getElementById(opts.filterEmptyId);
        if (filterEmpty) filterEmpty.classList.add("hidden");

        if (!emptyRow) {
          emptyRow = document.createElement("tr");
          emptyRow.className = opts.emptyClass;
          emptyRow.innerHTML =
            '<td colspan="' +
            opts.colspan +
            '"><p class="text-muted" style="text-align:center; padding:1.5rem 0">' +
            opts.message +
            "</p></td>";
          tbody.insertBefore(
            emptyRow,
            document.getElementById(opts.filterEmptyId),
          );
        }
      }

      var supplierState = { filter: "all" };

      function supplierRowHtml(supplier) {
        return (
          '<td><div class="td-with-icon"><i class="ri-store-2-line"></i><span>' +
          escapeHtml(supplier.name) +
          "</span></div></td>" +
          '<td><span class="mobile-label">Contact</span>' +
          escapeHtml(supplier.contact_summary || "—") +
          "</td>" +
          '<td><span class="mobile-label">Address</span>' +
          escapeHtml(supplier.address || "—") +
          "</td>" +
          '<td><span class="mobile-label">Deliveries</span><span class="qty">' +
          (parseInt(supplier.deliveries_count, 10) || 0) +
          "</span></td>" +
          '<td><span class="mobile-label">Status</span><span class="badge ' +
          supplier.status_class +
          '">' +
          supplier.status_label +
          "</span></td>" +
          '<td><div class="row-actions"><div class="row-actions-menu">' +
          '<button type="button" class="row-actions-toggle" aria-haspopup="true" aria-expanded="false" aria-label="Actions for ' +
          escapeHtml(supplier.name) +
          '" title="Actions"><i class="ri-more-2-fill"></i></button>' +
          '<div class="row-actions-list" data-scope="supplier" data-id="' +
          supplier.id +
          '">' +
          '<button type="button" class="row-actions-item" data-action="details"><i class="ri-eye-line"></i> Details</button>' +
          '<button type="button" class="row-actions-item" data-action="edit"><i class="ri-pencil-line"></i> Edit</button>' +
          '<button type="button" class="row-actions-item" data-action="toggle"><i class="ri-toggle-line"></i> ' +
          (supplier.status === "active" ? "Deactivate" : "Activate") +
          "</button>" +
          '<button type="button" class="row-actions-item danger" data-action="delete"><i class="ri-delete-bin-line"></i> Delete</button>' +
          "</div></div></div></td>"
        );
      }

      function upsertSupplierRow(supplier) {
        var tbody = document.getElementById("supplierBody");
        if (!tbody) return;
        closeRowMenu();
        var row = tbody.querySelector('tr[data-id="' + supplier.id + '"]');
        if (!row) {
          row = document.createElement("tr");
          row.setAttribute("data-id", supplier.id);
          var seeded = tbody.querySelector(".supplier-empty-row");
          if (seeded) seeded.remove();
          tbody.insertBefore(row, tbody.querySelector("#supplierFilterEmpty"));
        }
        row.setAttribute("data-status", supplier.status);
        row.setAttribute("data-search", supplierSearchText(supplier));
        row.innerHTML = supplierRowHtml(supplier);
        applySupplierFilters();
      }

      function recalculateSuppliers() {
        var rows = document.querySelectorAll("#supplierBody tr[data-id]");
        var active = 0;
        var inactive = 0;
        var deliveries = 0;
        rows.forEach(function (row) {
          if (row.getAttribute("data-status") === "active") active++;
          else inactive++;
          var record = supplierRecords[row.getAttribute("data-id")];
          if (record && record.deliveries_count) {
            deliveries += parseInt(record.deliveries_count, 10) || 0;
          }
        });
        var totalEl = document.getElementById("supTotalSuppliers");
        var activeEl = document.getElementById("supActiveCount");
        var inactiveEl = document.getElementById("supInactiveCount");
        var deliveriesEl = document.getElementById("supDeliveryCount");
        if (totalEl) totalEl.textContent = rows.length;
        if (activeEl) activeEl.textContent = active;
        if (inactiveEl) inactiveEl.textContent = inactive;
        if (deliveriesEl) deliveriesEl.textContent = deliveries;
      }

      function applySupplierFilters() {
        var rows = document.querySelectorAll("#supplierBody tr[data-id]");
        var input = document.getElementById("supplierSearch");
        var term = input ? input.value.trim().toLowerCase() : "";
        var visible = 0;
        rows.forEach(function (row) {
          var statusMatch =
            supplierState.filter === "all" ||
            row.getAttribute("data-status") === supplierState.filter;
          var searchMatch =
            !term ||
            (row.getAttribute("data-search") || "").indexOf(term) !== -1;
          var show = statusMatch && searchMatch;
          row.style.display = show ? "" : "none";
          if (show) visible++;
        });
        var filterEmpty = document.getElementById("supplierFilterEmpty");
        if (filterEmpty) filterEmpty.classList.toggle("hidden", visible !== 0);
        var noRows = document.querySelector("#supplierBody .supplier-empty-row");
        if (noRows) noRows.classList.toggle("hidden", rows.length !== 0);
        return visible;
      }

      var supplierFilterChips = document.querySelectorAll(
        "#supplierFilters .filter-chip-outline",
      );
      supplierFilterChips.forEach(function (chip) {
        chip.addEventListener("click", function () {
          supplierFilterChips.forEach(function (c) {
            c.classList.remove("active");
          });
          this.classList.add("active");
          supplierState.filter = this.dataset.filter;
          var count = applySupplierFilters();
          showToast(
            "Showing " + count + " supplier" + (count !== 1 ? "s" : ""),
            "info",
          );
        });
      });

      var supplierSearch = document.getElementById("supplierSearch");
      if (supplierSearch) {
        supplierSearch.addEventListener("input", function () {
          applySupplierFilters();
        });
      }

      /* Rebuild the supplier dropdown the delivery form uses, keeping the
         supplier already chosen even if it has since been deactivated. */
      function refreshSupplierOptions() {
        var select = document.getElementById("deliverySupplier");
        if (!select) return;
        var current = select.value;
        select.innerHTML = supplierSelectOptions(current);
      }

      function supplierSelectOptions(selectedId) {
        var html = '<option value="">Select a supplier...</option>';
        Object.keys(supplierRecords).forEach(function (id) {
          var supplier = supplierRecords[id];
          var isSelected = String(supplier.id) === String(selectedId || "");
          if (supplier.status !== "active" && !isSelected) return;
          html +=
            '<option value="' +
            supplier.id +
            '" ' +
            (isSelected ? "selected" : "") +
            ">" +
            escapeHtml(supplier.name) +
            (supplier.status !== "active" ? " (inactive)" : "") +
            "</option>";
        });
        return html;
      }

      function openSupplierForm(supplier) {
        var modal = document.getElementById("modalOverlay");
        var isEdit = !!supplier;
        document.getElementById("modalTitle").textContent = isEdit
          ? "Edit " + supplier.name
          : "Add New Supplier";
        document.getElementById("modalBody").innerHTML =
          '<div class="form-group"><label class="form-label">Supplier name *</label><input type="text" class="form-input" id="supplierName" placeholder="E.G. Dela Cruz Fish Supply" value="' +
          escapeHtml(isEdit ? supplier.name : "") +
          '"></div>' +
          '<div class="form-group"><label class="form-label">Contact person</label><input type="text" class="form-input" id="supplierContact" placeholder="E.G. Juan Dela Cruz" value="' +
          escapeHtml(isEdit ? supplier.contact_person || "" : "") +
          '"></div>' +
          '<div class="form-group"><label class="form-label">Phone number *</label><input type="text" class="form-input" id="supplierPhone" placeholder="E.G. 09171234567" value="' +
          escapeHtml(isEdit ? supplier.phone || "" : "") +
          '"></div>' +
          '<div class="form-group"><label class="form-label">Address</label><input type="text" class="form-input" id="supplierAddress" placeholder="E.G. Batangas City" value="' +
          escapeHtml(isEdit ? supplier.address || "" : "") +
          '"></div>' +
          '<div class="form-group"><label class="form-label">Notes</label><input type="text" class="form-input" id="supplierNotes" placeholder="E.G. Delivers every Monday morning" value="' +
          escapeHtml(isEdit ? supplier.notes || "" : "") +
          '"></div>';

        var footer = document.getElementById("modalFooter");
        footer.innerHTML = "";
        var cancel = document.createElement("button");
        cancel.className = "btn btn-secondary";
        cancel.textContent = "Cancel";
        cancel.addEventListener("click", function () {
          modal.classList.remove("open");
        });
        var save = document.createElement("button");
        save.className = "btn btn-primary";
        save.innerHTML =
          '<i class="' +
          (isEdit ? "ri-save-line" : "ri-add-line") +
          '"></i> ' +
          (isEdit ? "Save" : "Add");
        save.addEventListener("click", function () {
          var name = document.getElementById("supplierName").value.trim();
          var phone = document.getElementById("supplierPhone").value.trim();
          if (!name) {
            showToast("Please Enter a Supplier Name", "error");
            return;
          }
          if (!phone) {
            showToast("Please Enter a Phone Number", "error");
            return;
          }
          save.disabled = true;
          apiRequest(
            isEdit ? "PATCH" : "POST",
            isEdit ? "/suppliers/" + supplier.id : "/suppliers",
            {
              name: name,
              contact_person:
                document.getElementById("supplierContact").value.trim() || null,
              phone: phone,
              /* The form files contact details only — no email field any
                 more. The API treats an email it is not given as "clear it",
                 so an edit hands back what is already on file instead of
                 wiping it behind the vendor's back. */
              email: isEdit ? supplier.email || null : null,
              address:
                document.getElementById("supplierAddress").value.trim() || null,
              notes: document.getElementById("supplierNotes").value.trim() || null,
            },
            function (res) {
              save.disabled = false;
              if (!res.supplier) {
                showToast((res && res.message) || "Save Failed", "error");
                return;
              }
              var saved = res.supplier;
              supplierRecords[saved.id] = saved;
              upsertSupplierRow(saved);
              recalculateSuppliers();
              refreshSupplierOptions();
              modal.classList.remove("open");
              showToast(saved.name + (isEdit ? " updated" : " added"), "success");
              refreshNotifCount();
            },
          );
        });
        footer.appendChild(cancel);
        footer.appendChild(save);
        modal.classList.add("open");
      }

      /* 8.8 — flipping the status keeps every delivery the supplier already
         has; the row only changes its badge and its action button. */
      function toggleSupplierStatus(supplier) {
        var next = supplier.status === "active" ? "inactive" : "active";
        apiRequest(
          "PATCH",
          "/suppliers/" + supplier.id + "/status",
          { status: next },
          function (res) {
            if (!res.supplier) {
              showToast("Update Failed", "error");
              return;
            }
            var updated = res.supplier;
            supplierRecords[updated.id] = updated;
            upsertSupplierRow(updated);
            recalculateSuppliers();
            refreshSupplierOptions();
            showToast(
              updated.name + " is now " + updated.status_label.toLowerCase(),
              next === "inactive" ? "info" : "success",
            );
          },
        );
      }

      /* 8.4 / 8.9 — everything on file for one supplier, delivery history
         included, read from the same records the deliveries table uses. */
      function openSupplierDetails(supplier) {
        var history = Object.keys(deliveryRecords)
          .map(function (key) {
            return deliveryRecords[key];
          })
          .filter(function (delivery) {
            return String(delivery.supplier_id) === String(supplier.id);
          })
          .sort(function (a, b) {
            return (b.date_display || "").localeCompare(a.date_display || "");
          });

        var historyHtml = history.length
          ? history
              .map(function (delivery) {
                var received =
                  delivery.actual_quantity === null ||
                  delivery.actual_quantity === undefined
                    ? ""
                    : " · " + fmtKg(delivery.actual_quantity) + " kg received";
                return (
                  '<li class="history-item"><div><strong>' +
                  escapeHtml(delivery.fish_type) +
                  '</strong><span class="text-muted">' +
                  escapeHtml(delivery.date_display || "") +
                  " · " +
                  fmtKg(delivery.expected_quantity) +
                  " kg expected" +
                  received +
                  '</span></div><span class="badge ' +
                  delivery.status +
                  '">' +
                  deliveryStatusLabel(delivery.status) +
                  "</span></li>"
                );
              })
              .join("")
          : '<li class="history-item"><span class="text-muted">No deliveries recorded for this supplier yet.</span></li>';

        /* Overview first, then how to reach them, then the free-text note,
           and the delivery list last — grouped instead of one long grid. */
        document.getElementById("modalTitle").textContent = supplier.name;
        document.getElementById("modalBody").innerHTML =
          detailSection(
            "Supplier Overview",
            detailItemBadge(
              "Status",
              supplier.status_label,
              supplier.status_class,
            ) +
              detailItem("Total Deliveries", String(history.length)) +
              detailItem(
                "Last Delivery",
                history.length ? history[0].date_display || "" : "No deliveries yet",
              ),
          ) +
          detailSection(
            "Contact Details",
            detailItem("Contact Person", supplier.contact_person) +
              detailItem("Phone", supplier.phone) +
              detailItem("Email", supplier.email) +
              detailItem("Address", supplier.address),
          ) +
          detailSection("Notes", detailItem("Notes", supplier.notes, false, true)) +
          '<div class="detail-block-title">Delivery History (' +
          history.length +
          ")</div>" +
          '<ul class="history-list">' +
          historyHtml +
          "</ul>";

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

      /* 8.10-ish — a supplier only disappears while it owns no deliveries;
         otherwise its history would go with it, so the server says no and we
         say that up front instead of opening a confirm dialog first. */
      function deleteSupplier(supplier) {
        if (parseInt(supplier.deliveries_count, 10) > 0) {
          showToast(
            supplier.name +
              " Still Has Recorded Deliveries — Deactivate It Instead",
            "error",
          );
          return;
        }

        confirmAction({
          title: "Delete Supplier",
          message: "Delete " + supplier.name + " From Your Supplier List?",
          note: "Suppliers with recorded deliveries cannot be deleted, so this only removes suppliers you have never delivered with.",
          confirmText: "Delete",
          confirmIcon: "ri-delete-bin-line",
          danger: true,
          onConfirm: function () {
            apiRequest(
              "DELETE",
              "/suppliers/" + supplier.id,
              null,
              function (res) {
                if (!res.deleted) {
                  showToast((res && res.message) || "Delete Failed", "error");
                  return;
                }
                delete supplierRecords[supplier.id];
                removeRow("supplierBody", supplier.id);
                syncTableEmptyState({
                  bodyId: "supplierBody",
                  emptyClass: "supplier-empty-row",
                  filterEmptyId: "supplierFilterEmpty",
                  colspan: 6,
                  message:
                    'No suppliers yet. Click "Add Supplier" to register your first one!',
                  applyFilters: applySupplierFilters,
                });
                recalculateSuppliers();
                refreshSupplierOptions();
                showToast(supplier.name + " deleted", "info");
              },
            );
          },
        });
      }

      /* The three-dot menu owns every action on a supplier row now, so the
         row itself no longer listens for buttons. */

      var addSupplierBtn = document.getElementById("addSupplierBtn");
      if (addSupplierBtn) {
        addSupplierBtn.addEventListener("click", function () {
          openSupplierForm(null);
        });
      }

