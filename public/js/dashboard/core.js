      /* ==========================================================================
       FRESH-DA — DASHBOARD SCRIPT
       Single-file behaviour for the whole dashboard (one page, many sections).

       Naming rules used here, so markup built from JS always matches
       dashboard.css and the blade views:
         - element ids   -> camelCase    ("historyBody", "addFishBtn")
         - class names   -> kebab-case   ("btn", "batch-status-badge")
         - data hooks    -> kebab-case   ("data-page", "data-setting-toggle")

       Section map — search a banner name to jump straight to it:
         NOTIFICATIONS DATA      boot payload (window.DASHBOARD_DATA)
         PREFERENCES             saved theme / language / sound settings
         I18N                    translation dictionary
         SETTINGS ENGINE         read, write and apply one setting
         SECTION REFS            page sections and showPage()
         TOAST                   showToast()
         NOTIFICATIONS           notification list, read/delete actions
         SETTINGS PANEL          topbar gear → settings page
         TOPBAR PROFILE DROPDOWN avatar menu
         GLOBAL SEARCH           topbar search box
         HISTORY FILTERS & SEARCH / EXPORT  filter chips, CSV, PDF
         SCAN / UPLOAD           single-scan flow and camera capture
         ANALYZE                 scan result, recent scans, overview stats
         BATCH SAMPLING          3–5 photo batch verification
         INVENTORY               vendor stock table
         PROFILE / CHANGE PASSWORD
         SETTINGS PAGE EXTRAS    settings page widgets
         INIT                    boot
       ========================================================================== */

      var BATCH_MIN_SAMPLES = 3; // Minimum fish photos required to verify a batch
      var BATCH_MAX_SAMPLES = 5; // Maximum fish photos accepted per batch
      var BATCH_STORAGE_KEY = "freshda-batch-draft"; // In-progress batch, kept across refreshes

      var state = {
        currentPage: "dashboard",
        darkMode: false,
        batchSamples: [], // Array of sample objects (one per captured photo)
        analysisInProgress: false,
        notifPageFilter: "all",
        historyFilter: "all",
        batchComplete: false,
        capturedFile: null,
        batchPendingImage: null, // Data URL of the photo waiting to be analyzed
      };

      /* ================= NOTIFICATIONS DATA ================= */
      var bootData = window.DASHBOARD_DATA || {};
      var notifications = (Array.isArray(bootData.notifications) ? bootData.notifications : []).map(function (n) {
        return {
          id: n.id,
          type: n.type,
          title: n.title,
          desc: n.desc,
          time: n.time,
          unread: n.unread,
          category: n.category,
          page: n.page,
        };
      });

      function csrfToken() {
        var el = document.querySelector('meta[name="csrf-token"]');
        return el ? el.getAttribute("content") : "";
      }

      function apiRequest(method, url, body, onOk, onError) {
        var opts = {
          method: method,
          headers: {
            "X-CSRF-TOKEN": csrfToken(),
            "Accept": "application/json",
          },
        };
        if (body !== undefined && body !== null) {
          opts.headers["Content-Type"] = "application/json";
          opts.body = JSON.stringify(body);
        }
        fetch(url, opts)
          .then(function (r) {
            return r
              .json()
              .catch(function () {
                return {};
              })
              .then(function (data) {
                if (!r.ok) throw new Error(serverErrorMessage(r, data));
                return data;
              });
          })
          .then(function (data) {
            if (onOk) onOk(data);
          })
          .catch(function (err) {
            showToast(
              err && err.message
                ? err.message
                : "Network Error — Please Try Again",
              "error",
            );
            if (onError) onError();
          });
      }

      /* Surface the first real message the API sent back. */
      function serverErrorMessage(r, data) {
        if (data && data.errors) {
          for (var key in data.errors) {
            if (
              Object.prototype.hasOwnProperty.call(data.errors, key) &&
              data.errors[key] &&
              data.errors[key][0]
            ) {
              return data.errors[key][0];
            }
          }
        }
        if (data && data.message) return data.message;
        if (r.status === 404)
          return "Not Found — It May Have Been Deleted Already";
        if (r.status === 403) return "You Do Not Have Access To That";
        if (r.status === 419) return "Session Expired — Please Refresh the Page";
        return "Request Failed (" + r.status + ") — please try again";
      }

