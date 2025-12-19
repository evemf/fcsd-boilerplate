/* global YT */

/**
 * Service video (YouTube) — play on first user interaction (scroll/click/tap/key).
 *
 * Notes:
 * - Browsers block autoplay with sound unless initiated by a user gesture.
 * - We listen for user interaction anywhere on the page and start the video once.
 * - We also support the overlay button for accessibility.
 */
(function () {
  const IFRAME_ID = "service-youtube-iframe";

  function onDomReady(cb) {
    if (document.readyState === "loading") {
      document.addEventListener("DOMContentLoaded", cb, { once: true });
    } else {
      cb();
    }
  }

  function loadYouTubeAPI() {
    return new Promise((resolve) => {
      if (window.YT && typeof window.YT.Player === "function") {
        resolve();
        return;
      }

      // If already requested, wait.
      if (window.__fcsdYTApiLoading) {
        window.__fcsdYTApiLoading.then(resolve);
        return;
      }

      window.__fcsdYTApiLoading = new Promise((innerResolve) => {
        const tag = document.createElement("script");
        tag.src = "https://www.youtube.com/iframe_api";
        tag.async = true;
        document.head.appendChild(tag);

        const prev = window.onYouTubeIframeAPIReady;
        window.onYouTubeIframeAPIReady = function () {
          if (typeof prev === "function") prev();
          innerResolve();
        };
      });

      window.__fcsdYTApiLoading.then(resolve);
    });
  }

  onDomReady(async function () {
    const iframe = document.getElementById(IFRAME_ID);
    if (!iframe) return;

    const screen = iframe.closest(".service-video__screen");
    const section = iframe.closest(".service-video");
    const overlayBtn = screen ? screen.querySelector(".service-video__overlay") : null;
    if (!screen || !section) return;

    // Ensure the iframe has the attributes that help playback.
    iframe.setAttribute("allow", "autoplay; fullscreen; picture-in-picture");
    iframe.setAttribute("title", iframe.getAttribute("title") || "YouTube video");

    await loadYouTubeAPI();

    let player;
    let ready = false;
    let started = false;
    let armed = true;

    function markPlaying() {
      started = true;
      section.classList.add("is-playing");
      if (overlayBtn) {
        overlayBtn.setAttribute("aria-hidden", "true");
        overlayBtn.tabIndex = -1;
      }
    }

    function tryStartPlayback() {
      if (!armed || started || !ready || !player) return;

      try {
        // Unmute + set volume before starting.
        if (typeof player.unMute === "function") player.unMute();
        if (typeof player.setVolume === "function") player.setVolume(80);
        if (typeof player.playVideo === "function") player.playVideo();
        markPlaying();
        removeActivationListeners();
      } catch (e) {
        // If browser blocks the attempt (rare with user gesture), keep overlay.
      }
    }

    function addActivationListeners() {
      const opts = { passive: true };
      window.addEventListener("pointerdown", tryStartPlayback, opts);
      window.addEventListener("touchstart", tryStartPlayback, opts);
      window.addEventListener("click", tryStartPlayback, opts);
      window.addEventListener("keydown", tryStartPlayback, opts);
      window.addEventListener("wheel", tryStartPlayback, opts);
      window.addEventListener("scroll", tryStartPlayback, opts);

      if (overlayBtn) {
        overlayBtn.addEventListener("click", function (e) {
          e.preventDefault();
          tryStartPlayback();
        });
      }
    }

    function removeActivationListeners() {
      window.removeEventListener("pointerdown", tryStartPlayback);
      window.removeEventListener("touchstart", tryStartPlayback);
      window.removeEventListener("click", tryStartPlayback);
      window.removeEventListener("keydown", tryStartPlayback);
      window.removeEventListener("wheel", tryStartPlayback);
      window.removeEventListener("scroll", tryStartPlayback);
    }

    // Only arm the autoplay once the video is near viewport to avoid surprising audio.
    // Still: any click on overlay will work immediately.
    if ("IntersectionObserver" in window) {
      const io = new IntersectionObserver(
        (entries) => {
          const entry = entries[0];
          if (entry && entry.isIntersecting) {
            armed = true;
          }
        },
        { threshold: 0.35 }
      );
      io.observe(section);
      armed = false;
    }

    player = new YT.Player(IFRAME_ID, {
      events: {
        onReady: function () {
          ready = true;
          addActivationListeners();
        },
        onStateChange: function (ev) {
          // If user plays manually via YouTube controls, hide overlay.
          if (ev && ev.data === YT.PlayerState.PLAYING) {
            markPlaying();
          }
        },
      },
    });
  });
})();
