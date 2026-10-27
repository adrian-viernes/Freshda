var CACHE_NAME = "freshda-v5";
var CORE_ASSETS = ["/", "/scan", "/manifest.json"];
/* Only public pages are safe to store in the shared HTTP cache. */
var PUBLIC_NAVIGATIONS = ["/", "/scan", "/login"];

self.addEventListener("install", function (event) {
  event.waitUntil(
    caches
      .open(CACHE_NAME)
      .then(function (cache) {
        return cache.addAll(CORE_ASSETS);
      })
      .then(function () {
        return self.skipWaiting();
      }),
  );
});

self.addEventListener("activate", function (event) {
  event.waitUntil(
    caches
      .keys()
      .then(function (keys) {
        return Promise.all(
          keys
            .filter(function (key) {
              return key !== CACHE_NAME;
            })
            .map(function (key) {
              return caches.delete(key);
            }),
        );
      })
      .then(function () {
        return self.clients.claim();
      }),
  );
});

/*
 * Only plain, non-redirected 200s may be stored. /login sits behind the guest
 * middleware, so following its redirect would cache the authenticated
 * dashboard HTML under the /login key and leak it to logged-out users later.
 * Responses the server marked no-store are skipped for the same reason.
 */
function isStorable(response) {
  if (!response || response.status !== 200 || response.type !== "basic") {
    return false;
  }
  if (response.redirected) {
    return false;
  }
  var cacheControl = response.headers.get("Cache-Control") || "";
  return cacheControl.indexOf("no-store") === -1;
}

function cacheResponse(request, response) {
  if (!isStorable(response)) {
    return response;
  }
  var clone = response.clone();
  caches.open(CACHE_NAME).then(function (cache) {
    cache.put(request, clone);
  });
  return response;
}

/* A tiny, always-fresh offline notice: never a stale copy of a real page. */
function offlineResponse() {
  return new Response(
    "<!DOCTYPE html><html lang=\"en\"><head><meta charset=\"UTF-8\">" +
      '<meta name="viewport" content="width=device-width, initial-scale=1">' +
      "<title>Offline — Fresh-DA</title></head>" +
      '<body style="font-family:system-ui,sans-serif;display:flex;min-height:100vh;' +
      'align-items:center;justify-content:center;margin:0;background:#0f172a;color:#e2e8f0;">' +
      '<div style="text-align:center;padding:2rem">' +
      '<h1 style="font-size:1.25rem;margin:0 0 .5rem">You are offline</h1>' +
      '<p style="margin:0;opacity:.75">Please reconnect to use Fresh-DA.</p>' +
      "</div></body></html>",
    {
      status: 503,
      headers: {
        "Content-Type": "text/html; charset=UTF-8",
        "Cache-Control": "no-store",
      },
    },
  );
}

self.addEventListener("fetch", function (event) {
  var request = event.request;
  if (request.method !== "GET") return;
  if (request.url.indexOf(self.location.origin) !== 0) return;

  /* Pages are network-first so users always receive the current markup. */
  if (request.mode === "navigate") {
    var path = new URL(request.url).pathname;
    var cacheable = PUBLIC_NAVIGATIONS.indexOf(path) !== -1;

    event.respondWith(
      fetch(request)
        .then(function (response) {
          return cacheable ? cacheResponse(request, response) : response;
        })
        .catch(function () {
          return caches.match(request).then(function (cached) {
            return cached || offlineResponse();
          });
        }),
    );
    return;
  }

  /* Assets are cache-first with a background refresh. */
  event.respondWith(
    caches.match(request).then(function (cached) {
      var network = fetch(request)
        .then(function (response) {
          return cacheResponse(request, response);
        })
        .catch(function () {
          return cached || new Response("", { status: 503 });
        });

      if (cached) {
        event.waitUntil(
          network
            .then(function () {})
            .catch(function () {}),
        );
        return cached;
      }

      return network;
    }),
  );
});
