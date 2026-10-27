      /* ================= TOAST ================= */
      function showToast(message, type) {
        type = type || "info";
        var container = document.getElementById("toastContainer");
        var toast = document.createElement("div");
        toast.className = "toast " + type;
        var icon =
          type === "success"
            ? "ri-checkbox-circle-line"
            : type === "error"
              ? "ri-error-warning-line"
              : "ri-information-line";
        toast.innerHTML = '<i class="' + icon + '"></i> ' + message;
        container.appendChild(toast);
        setTimeout(function () {
          toast.style.opacity = "0";
          toast.style.transition = "opacity 0.3s ease";
          setTimeout(function () {
            toast.remove();
          }, 300);
        }, 2600);
      }

      function escapeHtml(str) {
        var div = document.createElement("div");
        div.appendChild(document.createTextNode(str));
        return div.innerHTML;
      }

