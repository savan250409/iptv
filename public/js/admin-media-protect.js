// Admin media protection (client side).
//
// The server (ProtectAdminMediaUrls middleware) hands every protected media file to
// the page as an encrypted, expiring token in a `data-ms` holder attribute — never a
// live `src`. This script turns that into displayed media WITHOUT leaving any readable
// reference in the DOM:
//
//   * IMAGES  -> painted onto a <canvas> (no src attribute at all). The bytes are
//                fetched once, drawn as raw pixels, and the blob is revoked instantly.
//                Nothing — no path, no token, no blob URL — is left in the DOM.
//
//   * VIDEOS  -> a <video> must have a src to stream/seek, so it gets an in-memory
//                blob: URL (loaded lazily, only near the viewport). A blob URL exposes
//                no path or token and cannot be opened or shared outside this tab.
//
// window.MediaGuard exposes the same canvas painter for add/edit previews, so those
// are URL-free too.
(function () {
  'use strict';

  var TOKEN_RE = /\/secure-media\?token=/;
  var UPLOAD_RE = /\/upload\//;

  function isProtected(u) {
    if (typeof u !== 'string' || u === '') return false;
    if (u.indexOf('blob:') === 0 || u.indexOf('data:') === 0) return false;
    return TOKEN_RE.test(u) || UPLOAD_RE.test(u);
  }

  function stripFragment(u) {
    var i = u.indexOf('#');
    return i === -1 ? u : u.slice(0, i);
  }

  // Shared Blob cache (images). Each use makes its own object URL from the Blob.
  var blobCache = {};
  function fetchBlob(url, cache) {
    if (cache && blobCache[url]) return blobCache[url];
    var p = fetch(url, {
      credentials: 'same-origin',
      headers: { 'X-Requested-With': 'XMLHttpRequest' }
    }).then(function (r) {
      if (!r.ok) throw new Error('media ' + r.status);
      return r.blob();
    });
    if (cache) {
      blobCache[url] = p;
      p['catch'](function () { delete blobCache[url]; });
    }
    return p;
  }

  // ---- image helpers ----------------------------------------------------------

  function imageFromBlob(blob) {
    return new Promise(function (resolve, reject) {
      var url = URL.createObjectURL(blob);
      var im = new Image();
      im.onload = function () { resolve({ im: im, url: url }); };
      im.onerror = function () { URL.revokeObjectURL(url); reject(new Error('decode')); };
      im.src = url;
    });
  }

  // draw an image into a canvas honouring object-fit cover/contain for fixed boxes
  function drawFit(ctx, im, cw, ch, fit) {
    var iw = im.naturalWidth, ih = im.naturalHeight;
    var s = (fit === 'cover') ? Math.max(cw / iw, ch / ih) : Math.min(cw / iw, ch / ih);
    var w = iw * s, h = ih * s;
    ctx.clearRect(0, 0, cw, ch);
    ctx.drawImage(im, (cw - w) / 2, (ch - h) / 2, w, h);
  }

  // Replace a protected <img> with a <canvas> carrying the painted pixels only.
  function toCanvas(imgEl, token) {
    imgEl.__msDone = token;
    fetchBlob(stripFragment(token), true).then(imageFromBlob).then(function (r) {
      var im = r.im;
      var dpr = window.devicePixelRatio || 1;
      var rect = imgEl.getBoundingClientRect();
      var fit = (getComputedStyle(imgEl).objectFit || 'fill').toLowerCase();

      var canvas = document.createElement('canvas');
      if (imgEl.id) canvas.id = imgEl.id;
      canvas.className = imgEl.className;
      var alt = imgEl.getAttribute('alt');
      if (alt) { canvas.setAttribute('role', 'img'); canvas.setAttribute('aria-label', alt); }

      var boxW = Math.round(rect.width) || imgEl.offsetWidth || im.naturalWidth;
      var boxH = Math.round(rect.height) || imgEl.offsetHeight || im.naturalHeight;

      if (fit === 'cover' || fit === 'contain') {
        canvas.width = Math.max(1, Math.round(boxW * dpr));
        canvas.height = Math.max(1, Math.round(boxH * dpr));
        drawFit(canvas.getContext('2d'), im, canvas.width, canvas.height, fit);
      } else {
        canvas.width = im.naturalWidth;
        canvas.height = im.naturalHeight;
        canvas.getContext('2d').drawImage(im, 0, 0);
      }

      canvas.__msSrc = token;        // JS property only — NOT a DOM attribute, invisible in the inspector
      imgEl.replaceWith(canvas);
      harden(canvas);
      URL.revokeObjectURL(r.url);    // throw the blob handle away immediately
    })['catch'](function () { imgEl.__msDone = null; });
  }

  // For app-managed preview <img> elements (flagged data-ms-live): keep an <img>,
  // load the bytes as a blob. (Used where page scripts later reset the src themselves.)
  function toBlobImg(imgEl, token) {
    imgEl.__msDone = token;
    fetchBlob(stripFragment(token), true).then(function (blob) {
      setBlob(imgEl, 'src', URL.createObjectURL(blob));
    })['catch'](function () { imgEl.__msDone = null; });
  }

  // ---- video helpers ----------------------------------------------------------

  function setBlob(el, prop, objUrl) {
    if (el.__msBlob && el.__msBlob !== objUrl) {
      try { URL.revokeObjectURL(el.__msBlob); } catch (e) {}
    }
    el.__msBlob = objUrl;
    el.__msSetting = true;
    el[prop] = objUrl;
    el.__msSetting = false;
  }

  function loadVideo(el, url) {
    el.__msDone = url;
    fetchBlob(stripFragment(url), false).then(function (blob) {
      setBlob(el, 'src', URL.createObjectURL(blob));
      var t = url.indexOf('#t=') !== -1 ? parseFloat(url.split('#t=')[1]) : null;
      if (t != null && !isNaN(t)) {
        el.addEventListener('loadedmetadata', function once() {
          el.removeEventListener('loadedmetadata', once);
          try { el.currentTime = t; } catch (e) {}
        });
      }
      try { el.load(); } catch (e) {}
    })['catch'](function () { el.__msDone = null; });
  }

  var vio = ('IntersectionObserver' in window) ? new IntersectionObserver(function (entries) {
    entries.forEach(function (en) {
      if (!en.isIntersecting) return;
      var el = en.target;
      vio.unobserve(el);
      if (isProtected(el.__msPending)) loadVideo(el, el.__msPending);
    });
  }, { rootMargin: '300px' }) : null;

  // ---- dispatch ---------------------------------------------------------------

  function process(el) {
    if (!el || el.nodeType !== 1) return;
    var tag = el.tagName;
    if (tag !== 'IMG' && tag !== 'VIDEO' && tag !== 'SOURCE') return;

    var poster = el.getAttribute && el.getAttribute('data-ms-poster');
    if (poster && isProtected(poster) && el.__msPoster !== poster) {
      el.__msPoster = poster;
      el.removeAttribute('data-ms-poster');
      fetchBlob(stripFragment(poster), true).then(function (blob) {
        setBlob(el, 'poster', URL.createObjectURL(blob));
      })['catch'](function () {});
    }

    var url = el.getAttribute('data-ms') || el.getAttribute('src');
    if (!isProtected(url) || el.__msSetting || el.__msDone === url) return;

    if (el.hasAttribute('data-ms')) el.removeAttribute('data-ms');

    if (tag === 'VIDEO' || tag === 'SOURCE') {
      el.__msDone = url;
      el.__msPending = url;
      if (vio && tag === 'VIDEO') vio.observe(el);
      else loadVideo(el, url);
    } else if (el.hasAttribute('data-ms-live')) {
      toBlobImg(el, url);
    } else {
      toCanvas(el, url);
    }
  }

  // ---- public painter for previews -------------------------------------------

  function paintNatural(canvas, im) {
    canvas.width = im.naturalWidth;
    canvas.height = im.naturalHeight;
    canvas.getContext('2d').drawImage(im, 0, 0);
  }

  window.MediaGuard = {
    // draw an encrypted-token image into `canvas` (full image, no DOM url)
    showToken: function (canvas, token) {
      if (!canvas || !isProtected(token)) return;
      fetchBlob(stripFragment(token), true).then(imageFromBlob).then(function (r) {
        paintNatural(canvas, r.im);
        canvas.__msSrc = token;
        harden(canvas);
        URL.revokeObjectURL(r.url);
      })['catch'](function () {});
    },
    // draw a picked File/Blob (the admin's own upload) into `canvas`
    showFile: function (canvas, file) {
      if (!canvas || !file) return;
      imageFromBlob(file).then(function (r) {
        paintNatural(canvas, r.im);
        harden(canvas);
        URL.revokeObjectURL(r.url);
      })['catch'](function () {});
    },
    // draw any ready src (e.g. a generated data: URL) into `canvas`
    showSrc: function (canvas, src) {
      if (!canvas || !src) return;
      var im = new Image();
      im.onload = function () { paintNatural(canvas, im); harden(canvas); };
      im.src = src;
    },
    clear: function (canvas) {
      if (canvas && canvas.getContext) {
        try { canvas.getContext('2d').clearRect(0, 0, canvas.width, canvas.height); } catch (e) {}
      }
    }
  };

  // ---- cosmetic hardening -----------------------------------------------------

  function harden(el) {
    if (!el || el.nodeType !== 1) return;
    var tag = el.tagName;
    if (tag === 'IMG' || tag === 'VIDEO' || tag === 'CANVAS') {
      if (el.getAttribute('draggable') !== 'false') el.setAttribute('draggable', 'false');
      if (!el.__msHard) {
        el.addEventListener('dragstart', function (e) { e.preventDefault(); });
        el.addEventListener('contextmenu', function (e) { e.preventDefault(); });
        el.__msHard = true;
      }
    }
    if (tag === 'VIDEO') {
      el.setAttribute('controlsList', 'nodownload noplaybackrate noremoteplayback');
      el.setAttribute('disablePictureInPicture', '');
      try { el.disableRemotePlayback = true; } catch (e) {}
    }
  }

  function sweep(root) {
    if (!root || !root.querySelectorAll) return;
    root.querySelectorAll('img, video, source').forEach(function (el) { harden(el); process(el); });
  }

  var mo = new MutationObserver(function (muts) {
    muts.forEach(function (m) {
      if (m.type === 'attributes') {
        harden(m.target);
        process(m.target);
      } else {
        m.addedNodes.forEach(function (n) {
          if (n.nodeType !== 1) return;
          harden(n); process(n);
          sweep(n);
        });
      }
    });
  });

  function start() {
    sweep(document);
    mo.observe(document.documentElement, {
      childList: true,
      subtree: true,
      attributes: true,
      attributeFilter: ['src', 'poster', 'data-ms', 'data-ms-poster']
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', start);
  } else {
    start();
  }

  window.addEventListener('beforeunload', function () {
    document.querySelectorAll('img, video, source').forEach(function (el) {
      if (el.__msBlob) { try { URL.revokeObjectURL(el.__msBlob); } catch (e) {} }
    });
  });
})();
