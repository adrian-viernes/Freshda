      /* ================= INVENTORY ================= */
      function recalculateInventory() {
        var rows = document.querySelectorAll("#inventoryBody tr[data-id]");
        var totalKg = 0;
        var lowStock = 0;
        var species = {};
        rows.forEach(function (row) {
          var qtyEl = row.querySelector(".qty");
          var qty = qtyEl ? parseFloat(qtyEl.textContent) || 0 : 0;
          totalKg += qty;
          var threshold = parseFloat(row.getAttribute("data-min-stock"));
          if (isNaN(threshold)) threshold = 3;
          if (qty < threshold) lowStock++;
          var name = (row.getAttribute("data-species") || "").trim();
          if (name) species[name] = true;
        });
        var totalEl = document.getElementById("invTotalItems");
        var weightEl = document.getElementById("invTotalWeight");
        var speciesEl = document.getElementById("invSpeciesCount");
        var lowStockEl = document.getElementById("invLowStockCount");
        if (totalEl) totalEl.textContent = rows.length;
        if (weightEl) weightEl.textContent = totalKg.toFixed(1);
        if (speciesEl) speciesEl.textContent = Object.keys(species).length;
        if (lowStockEl) lowStockEl.textContent = lowStock;
      }

      function inventoryFreshnessOut(v) {
        return v === "not_fresh" ? "notfresh" : v;
      }
      function inventoryFreshnessIn(v) {
        return v === "notfresh" ? "not_fresh" : v;
      }
      var inventoryConfPer = { fresh: 94, moderate: 78, notfresh: 45 };
      var inventoryLabels = {
        fresh: "Fresh",
        moderate: "Moderate",
        notfresh: "Not Fresh",
      };

      var inventoryBody = document.getElementById("inventoryBody");
      if (inventoryBody) {
        inventoryBody.addEventListener("click", function (e) {
          var editBtn = e.target.closest(".edit-inv-btn");
          var deleteBtn = e.target.closest(".delete-inv-btn");
          var row = e.target.closest("tr");
          if (!row) return;
          var id = row.getAttribute("data-id");
          if (editBtn) {
            openEditInventory(row, id);
            return;
          }
          if (deleteBtn) {
            deleteInventory(row, id);
            return;
          }
          openInventoryDetails(row, id);
        });
      }

      /* Keep the newest ledger row at the top of the page's data so the
         movement history modal shows fresh entries without a reload. */
      function pushMovement(movement) {
        if (!movement) return;
        var data = window.DASHBOARD_DATA || {};
        data.movements = data.movements || [];
        data.movements.unshift(movement);
      }

      function openEditInventory(row, id) {
        var fishName = row.querySelector(".td-with-icon span").textContent;
        var qtyEl = row.querySelector(".qty");
        var currentQty = qtyEl ? qtyEl.textContent : "0";
        var currentFresh = inventoryFreshnessOut(
          row.getAttribute("data-freshness") || "fresh",
        );
        var currentSpecies = (row.getAttribute("data-species") || "").trim();
        var modal = document.getElementById("modalOverlay");
        document.getElementById("modalTitle").textContent = "Edit " + fishName;
        document.getElementById("modalBody").innerHTML =
          '<div class="form-group"><label class="form-label">Fish name</label><input type="text" class="form-input" id="editFishName" value="' +
          escapeHtml(fishName) +
          '"></div>' +
          '<div class="form-group"><label class="form-label">Species</label><input type="text" class="form-input" id="editFishSpecies" value="' +
          escapeHtml(currentSpecies) +
          '"></div>' +
          '<div class="form-group"><label class="form-label">Quantity (kg)</label><input type="number" class="form-input" id="editQty" value="' +
          currentQty +
          '" min="0" step="0.5"></div>' +
          '<div class="form-group"><label class="form-label">Low-Stock Threshold (kg)</label><input type="number" class="form-input" id="editMinStock" value="' +
          escapeHtml(row.getAttribute("data-min-stock") || "") +
          '" min="0" step="0.5" placeholder="Default: 3"></div>' +
          '<div class="form-group"><label class="form-label">Freshness</label><select class="form-input" id="editFreshness">' +
          '<option value="fresh" ' +
          (currentFresh === "fresh" ? "selected" : "") +
          '>Fresh</option><option value="moderate" ' +
          (currentFresh === "moderate" ? "selected" : "") +
          '>Moderate</option><option value="notfresh" ' +
          (currentFresh === "notfresh" ? "selected" : "") +
          '>Not Fresh</option></select></div>';
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
        save.innerHTML = '<i class="ri-save-line"></i> Save';
        save.addEventListener("click", function () {
          var name = document.getElementById("editFishName").value.trim();
          var species =
            document.getElementById("editFishSpecies").value.trim() ||
            "Unknown";
          var newQty = document.getElementById("editQty").value;
          var newMinStock = document.getElementById("editMinStock").value.trim();
          var newFresh = document.getElementById("editFreshness").value;
          if (!name) {
            showToast("Please Enter a Fish Name", "error");
            return;
          }
          save.disabled = true;
          apiRequest(
            "PATCH",
            "/inventory/" + id,
            {
              name: name,
              species: species,
              quantity: parseFloat(newQty) || 0,
              min_stock: newMinStock === "" ? null : parseFloat(newMinStock) || 0,
              freshness: inventoryFreshnessIn(newFresh),
              freshness_percentage: inventoryConfPer[newFresh],
            },
            function (res) {
              save.disabled = false;
              if (!res.item) {
                showToast((res && res.message) || "Update Failed", "error");
                return;
              }
              var it = res.item;
              var badgeCls = inventoryFreshnessOut(it.freshness);
              row.setAttribute("data-id", it.id);
              row.setAttribute("data-freshness", badgeCls);
              row.setAttribute("data-species", it.species || "");
              row.setAttribute(
                "data-min-stock",
                it.min_stock === null || it.min_stock === undefined
                  ? ""
                  : String(it.min_stock),
              );
              row.querySelector(".td-with-icon span").textContent = it.name;
              row.querySelector(".qty").textContent = it.quantity;
              var badgeEl = row.querySelector(".badge");
              badgeEl.className = "badge " + badgeCls;
              badgeEl.textContent =
                inventoryLabels[badgeCls] + " · " + it.freshness_percentage + "%";
              var dateEl = row.querySelector(".inv-date");
              if (dateEl) dateEl.textContent = "Just Now";
              pushMovement(res.movement);
              recalculateInventory();
              modal.classList.remove("open");
              showToast(it.name + " updated", "success");
              refreshNotifCount();
            },
          );
        });
        footer.appendChild(cancel);
        footer.appendChild(save);
        modal.classList.add("open");
      }

      function deleteInventory(row, id) {
        if (!confirm("Delete This Inventory Item?")) return;
        apiRequest("DELETE", "/inventory/" + id, null, function (res) {
          if (!res.deleted) {
            showToast("Delete Failed", "error");
            return;
          }
          row.remove();
          recalculateInventory();
          showToast(res.name + " deleted", "info");
          refreshNotifCount();
        });
      }

      function movementIcon(type) {
        return type === "delivery"
          ? "ri-truck-line"
          : type === "removed"
            ? "ri-delete-bin-line"
            : type === "adjust"
              ? "ri-arrow-left-right-line"
              : "ri-add-line";
      }

      function movementRowHtml(m) {
        var change = parseFloat(m.change) || 0;
        var cls = change >= 0 ? "fresh" : "error";
        var balance =
          m.balance_after === null || m.balance_after === undefined
            ? ""
            : " · Balance: " + fmtKg(m.balance_after) + " kg";
        return (
          '<div class="scan-item">' +
          '<div class="scan-thumb ' +
          cls +
          '"><i class="' +
          movementIcon(m.type) +
          '"></i></div>' +
          '<div class="scan-info"><div class="scan-name">' +
          escapeHtml(m.item_name) +
          " — " +
          (change >= 0 ? "+" : "-") +
          fmtKg(Math.abs(change)) +
          " kg</div>" +
          '<div class="scan-meta"><span>' +
          escapeHtml(m.type_label || m.type) +
          "</span>" +
          (m.reference
            ? '<span class="sep">•</span><span>' + escapeHtml(m.reference) + "</span>"
            : "") +
          '<span class="sep">•</span><span>' +
          escapeHtml(m.time_ago || "") +
          balance +
          "</span></div></div></div>"
        );
      }

      function closeModal() {
        document.getElementById("modalOverlay").classList.remove("open");
      }

      /* The full ledger, newest first, opened from the inventory toolbar. */
      var movementHistoryBtn = document.getElementById("movementHistoryBtn");
      if (movementHistoryBtn)
        movementHistoryBtn.addEventListener("click", function () {
          var movements = (window.DASHBOARD_DATA || {}).movements || [];
          document.getElementById("modalTitle").textContent =
            "Stock Movement History";
          var body = document.getElementById("modalBody");
          if (!movements.length) {
            body.innerHTML =
              '<p class="text-muted" style="text-align:center; padding:1rem 0">No stock movements recorded yet.</p>';
          } else {
            body.innerHTML =
              '<div class="scan-list">' +
              movements.map(movementRowHtml).join("") +
              "</div>";
          }
          var footer = document.getElementById("modalFooter");
          footer.innerHTML = "";
          var close = document.createElement("button");
          close.className = "btn btn-secondary";
          close.textContent = "Close";
          close.addEventListener("click", closeModal);
          footer.appendChild(close);
          document.getElementById("modalOverlay").classList.add("open");
        });

      /* Row click: the item's card, plus the movements behind its balance. */
      function openInventoryDetails(row, id) {
        var nameCell = row.querySelector(".td-with-icon span");
        var name = nameCell ? nameCell.textContent : "Item";
        var species = (row.getAttribute("data-species") || "").trim() || "Unknown";
        var qtyEl = row.querySelector(".qty");
        var badge = row.querySelector(".badge");
        var dateEl = row.querySelector(".inv-date");
        var minStock = row.getAttribute("data-min-stock");
        var movements = ((window.DASHBOARD_DATA || {}).movements || []).filter(
          function (m) {
            return String(m.inventory_item_id) === String(id);
          },
        );

        document.getElementById("modalTitle").textContent = name + " — Details";
        document.getElementById("modalBody").innerHTML =
          '<div class="detail-grid">' +
          detailItem("Species", species) +
          detailItem("Quantity", fmtKg(qtyEl ? qtyEl.textContent : "0") + " kg") +
          detailItem(
            "Low-Stock Threshold",
            minStock ? fmtKg(minStock) + " kg" : "Default (3 kg)",
          ) +
          detailItem("Freshness", badge ? badge.textContent : "—") +
          detailItem("Last Scan", dateEl ? dateEl.textContent : "—") +
          "</div>" +
          '<div style="margin-top:1rem; font-weight:600">Stock Movements</div>' +
          (movements.length
            ? '<div class="scan-list">' +
              movements.map(movementRowHtml).join("") +
              "</div>"
            : '<p class="text-muted" style="margin-top:0.5rem">No movements recorded for this item yet.</p>');

        var footer = document.getElementById("modalFooter");
        footer.innerHTML = "";
        var close = document.createElement("button");
        close.className = "btn btn-secondary";
        close.textContent = "Close";
        close.addEventListener("click", closeModal);
        footer.appendChild(close);
        document.getElementById("modalOverlay").classList.add("open");
      }

      var addFishBtn = document.getElementById("addFishBtn");
      if (addFishBtn) addFishBtn.addEventListener("click", function () {
          var modal = document.getElementById("modalOverlay");
          document.getElementById("modalTitle").textContent = "Add New Fish";
          document.getElementById("modalBody").innerHTML =
            '<div class="form-group"><label class="form-label">Fish name</label><input type="text" class="form-input" id="newFishName" placeholder="E.G. Tuna"></div>' +
            '<div class="form-group"><label class="form-label">Species</label><input type="text" class="form-input" id="newFishSpecies" placeholder="E.G. Thunnus"></div>' +
            '<div class="form-group"><label class="form-label">Quantity (kg)</label><input type="number" class="form-input" id="newFishQty" value="5" min="0" step="0.5"></div>' +
            '<div class="form-group"><label class="form-label">Low-Stock Threshold (kg)</label><input type="number" class="form-input" id="newFishMinStock" min="0" step="0.5" placeholder="Default: 3"></div>';
          var footer = document.getElementById("modalFooter");
          footer.innerHTML = "";
          var cancel = document.createElement("button");
          cancel.className = "btn btn-secondary";
          cancel.textContent = "Cancel";
          cancel.addEventListener("click", function () {
            modal.classList.remove("open");
          });
          var add = document.createElement("button");
          add.className = "btn btn-primary";
          add.innerHTML = '<i class="ri-add-line"></i> Add';
          add.addEventListener("click", function () {
            var name = document.getElementById("newFishName").value.trim();
            var species =
              document.getElementById("newFishSpecies").value.trim() ||
              "Unknown";
            var qty = document.getElementById("newFishQty").value || "0";
            var minStock = document.getElementById("newFishMinStock").value.trim();
            if (!name) {
              showToast("Please Enter a Fish Name", "error");
              return;
            }
            add.disabled = true;
            apiRequest(
              "POST",
              "/inventory",
              {
                name: name,
                species: species,
                quantity: parseFloat(qty) || 0,
                min_stock: minStock === "" ? null : parseFloat(minStock) || 0,
              },
              function (res) {
                add.disabled = false;
                if (!res.item) {
                  showToast((res && res.message) || "Add Failed", "error");
                  return;
                }
                var it = res.item;
                var badgeCls = inventoryFreshnessOut(it.freshness);
                var tr = document.createElement("tr");
                tr.setAttribute("data-id", it.id);
                tr.setAttribute("data-freshness", badgeCls);
                tr.setAttribute("data-species", it.species || "");
                tr.setAttribute(
                  "data-min-stock",
                  it.min_stock === null || it.min_stock === undefined
                    ? ""
                    : String(it.min_stock),
                );
                tr.innerHTML =
                  '<td><div class="td-with-icon"><i class="ri-image-line"></i><span>' +
                  escapeHtml(it.name) +
                  "</span></div></td>" +
                  '<td><span class="mobile-label">Species</span>' +
                  escapeHtml(it.species || "Unknown") +
                  "</td>" +
                  '<td><span class="mobile-label">Quantity</span><span class="qty">' +
                  it.quantity +
                  "</span> kg</td>" +
                  '<td><span class="mobile-label">Freshness</span><span class="badge ' +
                  badgeCls +
                  '">' +
                  inventoryLabels[badgeCls] +
                  " · " +
                  it.freshness_percentage +
                  "%</span></td>" +
                  '<td><span class="mobile-label">Last Scan</span><span class="inv-date">Just now</span></td>' +
                  '<td><div class="row-actions"><button class="btn btn-secondary btn-sm edit-inv-btn">Edit</button><button class="btn btn-danger btn-sm delete-inv-btn">Delete</button></div></td>';
                document.getElementById("inventoryBody").appendChild(tr);
                pushMovement(res.movement);
                recalculateInventory();
                modal.classList.remove("open");
                showToast(it.name + " added", "success");
                refreshNotifCount();
              },
            );
          });
          footer.appendChild(cancel);
          footer.appendChild(add);
          modal.classList.add("open");
        });

      recalculateInventory();

