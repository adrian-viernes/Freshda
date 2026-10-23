      /* ================= SUPPLIER DELIVERIES ================= */
      /* Record an incoming delivery against a supplier, check in what really
         arrived, then accept (which stocks the inventory once) or reject. */
      var deliveryState = { filter: "all" };

      function deliveryRowHtml(delivery) {
        var pending = delivery.status === "pending";
        return (
          '<td><div class="td-with-icon"><i class="ri-price-tag-3-line"></i><span>' +
          escapeHtml(delivery.fish_type) +
          "</span></div></td>" +
          '<td><span class="mobile-label">Supplier</span>' +
          escapeHtml(delivery.supplier_name || "—") +
          "</td>" +
          '<td><span class="mobile-label">Delivery Date</span><span class="inv-date">' +
          escapeHtml(delivery.date_display || "") +
          "</span></td>" +
          '<td><span class="mobile-label">Expected</span><span class="qty">' +
          fmtKg(delivery.expected_quantity) +
          "</span> kg</td>" +
          '<td><span class="mobile-label">Received</span>' +
          deliveryReceivedCell(delivery) +
          "</td>" +
          '<td><span class="mobile-label">Status</span><span class="badge ' +
          delivery.status +
          '">' +
          deliveryStatusLabel(delivery.status) +
          "</span></td>" +
          '<td><div class="row-actions">' +
          (pending
            ? '<button class="btn btn-primary btn-sm accept-delivery-btn">Check In</button>' +
              '<button class="btn btn-danger btn-sm reject-delivery-btn">Reject</button>'
            : "") +
          '<div class="row-actions-menu">' +
          '<button type="button" class="row-actions-toggle" aria-haspopup="true" aria-expanded="false" aria-label="Actions for the ' +
          escapeHtml(delivery.fish_type) +
          ' delivery" title="Actions"><i class="ri-more-2-fill"></i></button>' +
          '<div class="row-actions-list" data-scope="delivery" data-id="' +
          delivery.id +
          '">' +
          '<button type="button" class="row-actions-item" data-action="details"><i class="ri-eye-line"></i> Details</button>' +
          (pending
            ? '<button type="button" class="row-actions-item" data-action="edit"><i class="ri-pencil-line"></i> Edit</button>'
            : "") +
          '<button type="button" class="row-actions-item danger" data-action="delete"><i class="ri-delete-bin-line"></i> Delete</button>' +
          "</div></div></div></td>"
        );
      }

      function upsertDeliveryRow(delivery) {
        var tbody = document.getElementById("deliveryBody");
        if (!tbody) return;
        closeRowMenu();
        var row = tbody.querySelector('tr[data-id="' + delivery.id + '"]');
        if (!row) {
          row = document.createElement("tr");
          row.setAttribute("data-id", delivery.id);
          var seeded = tbody.querySelector(".delivery-empty-row");
          if (seeded) seeded.remove();
          tbody.insertBefore(row, tbody.querySelector("#deliveryFilterEmpty"));
        }
        row.setAttribute("data-status", delivery.status);
        row.setAttribute("data-supplier-id", delivery.supplier_id);
        row.setAttribute(
          "data-search",
          (
            (delivery.fish_type || "") +
            " " +
            (delivery.supplier_name || "") +
            " " +
            (delivery.date_display || "")
          ).toLowerCase(),
        );
        row.innerHTML = deliveryRowHtml(delivery);
        applyDeliveryFilters();
      }

      function recalculateDeliveries() {
        var rows = document.querySelectorAll("#deliveryBody tr[data-id]");
        var pending = 0;
        var accepted = 0;
        var rejected = 0;
        rows.forEach(function (row) {
          var status = row.getAttribute("data-status");
          if (status === "pending") pending++;
          else if (status === "accepted") accepted++;
          else if (status === "rejected") rejected++;
        });
        var totalEl = document.getElementById("delTotal");
        var pendingEl = document.getElementById("delPending");
        var acceptedEl = document.getElementById("delAccepted");
        var rejectedEl = document.getElementById("delRejected");
        if (totalEl) totalEl.textContent = rows.length;
        if (pendingEl) pendingEl.textContent = pending;
        if (acceptedEl) acceptedEl.textContent = accepted;
        if (rejectedEl) rejectedEl.textContent = rejected;
      }

      function applyDeliveryFilters() {
        var rows = document.querySelectorAll("#deliveryBody tr[data-id]");
        var input = document.getElementById("deliverySearch");
        var term = input ? input.value.trim().toLowerCase() : "";
        var visible = 0;
        rows.forEach(function (row) {
          var statusMatch =
            deliveryState.filter === "all" ||
            row.getAttribute("data-status") === deliveryState.filter;
          var searchMatch =
            !term ||
            (row.getAttribute("data-search") || "").indexOf(term) !== -1;
          var show = statusMatch && searchMatch;
          row.style.display = show ? "" : "none";
          if (show) visible++;
        });
        var filterEmpty = document.getElementById("deliveryFilterEmpty");
        if (filterEmpty) filterEmpty.classList.toggle("hidden", visible !== 0);
        var noRows = document.querySelector("#deliveryBody .delivery-empty-row");
        if (noRows) noRows.classList.toggle("hidden", rows.length !== 0);
        return visible;
      }

      var deliveryFilterChips = document.querySelectorAll(
        "#deliveryFilters .filter-chip-outline",
      );
      deliveryFilterChips.forEach(function (chip) {
        chip.addEventListener("click", function () {
          deliveryFilterChips.forEach(function (c) {
            c.classList.remove("active");
          });
          this.classList.add("active");
          deliveryState.filter = this.dataset.filter;
          var count = applyDeliveryFilters();
          showToast(
            "Showing " + count + " deliver" + (count !== 1 ? "ies" : "y"),
            "info",
          );
        });
      });

      var deliverySearch = document.getElementById("deliverySearch");
      if (deliverySearch) {
        deliverySearch.addEventListener("input", function () {
          applyDeliveryFilters();
        });
      }

      function openDeliveryForm(delivery) {
        var activeSuppliers = Object.keys(supplierRecords).filter(function (id) {
          return supplierRecords[id].status === "active";
        });
        if (!activeSuppliers.length) {
          showToast("Add An Active Supplier First", "error");
          return;
        }

        var modal = document.getElementById("modalOverlay");
        var isEdit = !!delivery;
        var today = new Date().toISOString().slice(0, 10);
        document.getElementById("modalTitle").textContent = isEdit
          ? "Edit " + delivery.fish_type + " Delivery"
          : "Record Delivery";
        document.getElementById("modalBody").innerHTML =
          '<div class="form-group"><label class="form-label">Supplier *</label><select class="form-input" id="deliverySupplier">' +
          supplierSelectOptions(isEdit ? delivery.supplier_id : "") +
          "</select></div>" +
          '<div class="form-group"><label class="form-label">Fish type *</label><input type="text" class="form-input" id="deliveryFish" placeholder="E.G. Milkfish" value="' +
          escapeHtml(isEdit ? delivery.fish_type : "") +
          '"></div>' +
          '<div class="form-group"><label class="form-label">Delivery date *</label><input type="date" class="form-input" id="deliveryDate" value="' +
          escapeHtml(isEdit ? delivery.date_display || today : today) +
          '"></div>' +
          '<div class="form-group"><label class="form-label">Expected quantity (kg) *</label><input type="number" class="form-input" id="deliveryExpected" min="0.1" step="0.5" value="' +
          (isEdit ? fmtKg(delivery.expected_quantity) : "10") +
          '"></div>' +
          '<div class="form-group"><label class="form-label">Notes</label><input type="text" class="form-input" id="deliveryNotes" placeholder="E.G. Morning delivery from the port" value="' +
          escapeHtml(isEdit ? delivery.inspection_notes || "" : "") +
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
          (isEdit ? "Save" : "Record");
        save.addEventListener("click", function () {
          var supplierId = document.getElementById("deliverySupplier").value;
          var fish = document.getElementById("deliveryFish").value.trim();
          var date = document.getElementById("deliveryDate").value;
          var expected = document.getElementById("deliveryExpected").value;
          if (!supplierId) {
            showToast("Please Select a Supplier", "error");
            return;
          }
          if (!fish) {
            showToast("Please Enter a Fish Type", "error");
            return;
          }
          if (!date) {
            showToast("Please Enter a Delivery Date", "error");
            return;
          }
          if (!(parseFloat(expected) > 0)) {
            showToast("Expected Quantity Must Be Above Zero", "error");
            return;
          }
          save.disabled = true;
          apiRequest(
            isEdit ? "PATCH" : "POST",
            isEdit ? "/deliveries/" + delivery.id : "/deliveries",
            {
              supplier_id: parseInt(supplierId, 10),
              fish_type: fish,
              delivery_date: date,
              expected_quantity: parseFloat(expected),
              inspection_notes:
                document.getElementById("deliveryNotes").value.trim() || null,
            },
            function (res) {
              save.disabled = false;
              if (!res.delivery) {
                showToast((res && res.message) || "Save Failed", "error");
                return;
              }
              var saved = res.delivery;
              deliveryRecords[saved.id] = saved;
              upsertDeliveryRow(saved);
              recalculateDeliveries();
              modal.classList.remove("open");
              showToast(
                saved.fish_type + " delivery " + (isEdit ? "updated" : "recorded"),
                "success",
              );
              refreshNotifCount();
            },
          );
        });
        footer.appendChild(cancel);
        footer.appendChild(save);
        modal.classList.add("open");
      }

      /* 9.4 / 9.7 — the received quantity defaults to what was expected, so
         only a shortfall or surplus needs typing. */
      function openAcceptDelivery(delivery) {
        var modal = document.getElementById("modalOverlay");
        var startQty =
          delivery.actual_quantity === null ||
          delivery.actual_quantity === undefined
            ? delivery.expected_quantity
            : delivery.actual_quantity;
        document.getElementById("modalTitle").textContent =
          "Check In " + delivery.fish_type;
        document.getElementById("modalBody").innerHTML =
          '<p class="text-muted">Expected from ' +
          escapeHtml(delivery.supplier_name || "Supplier") +
          ": <strong>" +
          fmtKg(delivery.expected_quantity) +
          " kg</strong> on " +
          escapeHtml(delivery.date_display || "") +
          ".</p>" +
          '<div class="form-group"><label class="form-label">Actual received (kg)</label><input type="number" class="form-input" id="acceptQty" min="0" step="0.5" value="' +
          fmtKg(startQty) +
          '"></div>' +
          '<div class="form-group"><label class="form-label">Inspection notes</label><input type="text" class="form-input" id="acceptNotes" placeholder="E.G. Still cold, packaging intact" value="' +
          escapeHtml(delivery.inspection_notes || "") +
          '"></div>' +
          '<p class="confirm-action-note">The received quantity is added to your inventory item for ' +
          escapeHtml(delivery.fish_type) +
          " once you accept.</p>";

        var footer = document.getElementById("modalFooter");
        footer.innerHTML = "";
        var cancel = document.createElement("button");
        cancel.className = "btn btn-secondary";
        cancel.textContent = "Cancel";
        cancel.addEventListener("click", function () {
          modal.classList.remove("open");
        });
        var accept = document.createElement("button");
        accept.className = "btn btn-primary";
        accept.innerHTML =
          '<i class="ri-checkbox-circle-line"></i> Accept & Update Inventory';
        accept.addEventListener("click", function () {
          var qty = document.getElementById("acceptQty").value;
          accept.disabled = true;
          apiRequest(
            "PATCH",
            "/deliveries/" + delivery.id + "/accept",
            {
              actual_quantity: qty === "" ? null : parseFloat(qty),
              inspection_notes:
                document.getElementById("acceptNotes").value.trim() || null,
            },
            function (res) {
              accept.disabled = false;
              if (!res.delivery) {
                showToast((res && res.message) || "Accept Failed", "error");
                return;
              }
              var saved = res.delivery;
              deliveryRecords[saved.id] = saved;
              upsertDeliveryRow(saved);
              recalculateDeliveries();
              pushMovement(res.movement);
              modal.classList.remove("open");
              showToast(saved.variance_label || "Delivery accepted", "success");
              refreshNotifCount();
            },
          );
        });
        footer.appendChild(cancel);
        footer.appendChild(accept);
        modal.classList.add("open");
      }

      function openRejectDelivery(delivery) {
        var modal = document.getElementById("modalOverlay");
        document.getElementById("modalTitle").textContent =
          "Reject " + delivery.fish_type;
        document.getElementById("modalBody").innerHTML =
          '<div class="form-group"><label class="form-label">Reason for rejection *</label><input type="text" class="form-input" id="rejectReason" placeholder="E.G. Arrived warm and below the agreed weight"></div>' +
          '<p class="confirm-action-note">A rejected delivery stays on record for this supplier and adds nothing to your inventory.</p>';

        var footer = document.getElementById("modalFooter");
        footer.innerHTML = "";
        var cancel = document.createElement("button");
        cancel.className = "btn btn-secondary";
        cancel.textContent = "Cancel";
        cancel.addEventListener("click", function () {
          modal.classList.remove("open");
        });
        var reject = document.createElement("button");
        reject.className = "btn btn-danger";
        reject.innerHTML = '<i class="ri-close-circle-line"></i> Reject Delivery';
        reject.addEventListener("click", function () {
          var reason = document.getElementById("rejectReason").value.trim();
          if (!reason) {
            showToast("Please Enter a Reason", "error");
            return;
          }
          reject.disabled = true;
          apiRequest(
            "PATCH",
            "/deliveries/" + delivery.id + "/reject",
            { rejection_reason: reason },
            function (res) {
              reject.disabled = false;
              if (!res.delivery) {
                showToast((res && res.message) || "Reject Failed", "error");
                return;
              }
              var saved = res.delivery;
              deliveryRecords[saved.id] = saved;
              upsertDeliveryRow(saved);
              recalculateDeliveries();
              modal.classList.remove("open");
              showToast(saved.fish_type + " delivery rejected", "info");
              refreshNotifCount();
            },
          );
        });
        footer.appendChild(cancel);
        footer.appendChild(reject);
        modal.classList.add("open");
      }

      function openDeliveryDetails(delivery) {
        /* Three groups: what happened, what was written down, and what it
           did to the inventory. */
        var overview =
          detailItemBadge("Status", delivery.status_label, delivery.status) +
          detailItem("Supplier", delivery.supplier_name) +
          detailItem("Delivery Date", delivery.date_display) +
          detailItem("Expected", fmtKg(delivery.expected_quantity) + " kg") +
          detailItem(
            "Received",
            delivery.actual_quantity === null ||
              delivery.actual_quantity === undefined
              ? "Not checked yet"
              : fmtKg(delivery.actual_quantity) + " kg",
          ) +
          detailItem("Comparison", delivery.variance_label);

        var inspection =
          detailItem("Inspection Notes", delivery.inspection_notes, false, true) +
          (delivery.rejection_reason
            ? detailItem("Rejection Reason", delivery.rejection_reason, false, true)
            : "");

        var inventory =
          detailItem(
            "Linked Inventory Item",
            delivery.inventory_item_id ? delivery.fish_type : null,
            false,
            true,
          ) +
          detailItem(
            "Stock Movement",
            delivery.stock_updated_at
              ? "Added to inventory on " + formatTimestamp(delivery.stock_updated_at)
              : "Not added to inventory yet",
            false,
            true,
          );

        document.getElementById("modalTitle").textContent =
          delivery.fish_type + " — " + (delivery.supplier_name || "Supplier");
        document.getElementById("modalBody").innerHTML =
          detailSection("Delivery Overview", overview) +
          detailSection("Inspection", inspection) +
          detailSection("Inventory Impact", inventory);

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

      /* Only deliveries that never stocked anything may go: an accepted one
         already pushed its quantity into the inventory, and the server holds
         that line too. */
      function deleteDelivery(delivery) {
        if (delivery.stock_updated_at) {
          showToast(
            delivery.fish_type +
              " Delivery Was Accepted And Stocked — It Cannot Be Deleted",
            "error",
          );
          return;
        }

        confirmAction({
          title: "Delete Delivery",
          message:
            "Delete The " + delivery.fish_type + " Delivery Of " + (delivery.date_display || "") + "?",
          note: "Pending and rejected deliveries can be removed. Anything already added to your inventory stays on record.",
          confirmText: "Delete",
          confirmIcon: "ri-delete-bin-line",
          danger: true,
          onConfirm: function () {
            apiRequest(
              "DELETE",
              "/deliveries/" + delivery.id,
              null,
              function (res) {
                if (!res.deleted) {
                  showToast((res && res.message) || "Delete Failed", "error");
                  return;
                }
                delete deliveryRecords[delivery.id];
                removeRow("deliveryBody", delivery.id);
                syncTableEmptyState({
                  bodyId: "deliveryBody",
                  emptyClass: "delivery-empty-row",
                  filterEmptyId: "deliveryFilterEmpty",
                  colspan: 7,
                  message:
                    'No deliveries recorded yet. Click "Record Delivery" to log an incoming batch!',
                  applyFilters: applyDeliveryFilters,
                });
                recalculateDeliveries();

                /* The supplier's delivery count on its own row drops by one
                   with it, and the supplier table follows. */
                var supplier = supplierRecords[delivery.supplier_id];
                if (supplier) {
                  supplier.deliveries_count = Math.max(
                    0,
                    (parseInt(supplier.deliveries_count, 10) || 0) - 1,
                  );
                  upsertSupplierRow(supplier);
                  recalculateSuppliers();
                }

                showToast(delivery.fish_type + " delivery deleted", "info");
              },
            );
          },
        });
      }

      /* Check In and Reject stay as plain buttons in the cell; everything
         else on a delivery row runs through the three-dot menu. */
      var deliveryBody = document.getElementById("deliveryBody");
      if (deliveryBody) {
        deliveryBody.addEventListener("click", function (e) {
          var row = e.target.closest("tr[data-id]");
          if (!row) return;
          var delivery = deliveryRecords[row.getAttribute("data-id")];
          if (!delivery) return;
          if (e.target.closest(".accept-delivery-btn")) {
            openAcceptDelivery(delivery);
            return;
          }
          if (e.target.closest(".reject-delivery-btn")) {
            openRejectDelivery(delivery);
          }
        });
      }

      var addDeliveryBtn = document.getElementById("addDeliveryBtn");
      if (addDeliveryBtn) {
        addDeliveryBtn.addEventListener("click", function () {
          openDeliveryForm(null);
        });
      }

      recalculateSuppliers();
      recalculateDeliveries();

