(function () {
  "use strict";

  function closest(el, selector) {
    while (el && el.nodeType === 1) {
      if (el.matches(selector)) return el;
      el = el.parentElement;
    }
    return null;
  }

  function setFlipped(tile, flipped) {
    if (!tile) return;
    tile.classList.toggle("is-flipped", flipped);
    var btn = tile.querySelector(".fcsd-coin");
    if (btn) btn.setAttribute("aria-pressed", flipped ? "true" : "false");
  }

  // Toggle on click/tap (mobile-friendly).
  document.addEventListener("click", function (e) {
    var link = closest(e.target, ".fcsd-coin__link");
    if (link) {
      // Let the link work.
      return;
    }

    var coin = closest(e.target, ".fcsd-coin");
    if (!coin) return;

    var tile = closest(coin, ".fcsd-ambit");
    if (!tile) return;

    var isFlipped = tile.classList.contains("is-flipped");

    // Close others, keep the interaction tidy.
    document
      .querySelectorAll(".fcsd-ambit.is-flipped")
      .forEach(function (t) {
        if (t !== tile) setFlipped(t, false);
      });

    setFlipped(tile, !isFlipped);
  });

  // Close if you click outside.
  document.addEventListener("click", function (e) {
    if (closest(e.target, ".fcsd-ambit")) return;
    document
      .querySelectorAll(".fcsd-ambit.is-flipped")
      .forEach(function (t) {
        setFlipped(t, false);
      });
  });

  // Keyboard: Escape closes.
  document.addEventListener("keydown", function (e) {
    if (e.key !== "Escape") return;
    document
      .querySelectorAll(".fcsd-ambit.is-flipped")
      .forEach(function (t) {
        setFlipped(t, false);
      });
  });

  // Keyboard: Enter/Space toggles when focus is on a coin.
  document.addEventListener("keydown", function (e) {
    if (e.key !== "Enter" && e.key !== " ") return;
    var coin = closest(document.activeElement, ".fcsd-coin");
    if (!coin) return;
    e.preventDefault();
    var tile = closest(coin, ".fcsd-ambit");
    if (!tile) return;
    setFlipped(tile, !tile.classList.contains("is-flipped"));
  });


  // Welcome panel: show white background while pressed/held (touch + mouse).
  var welcome = document.querySelector(".fcsd-home-welcome__panel");
  if (welcome) {
    var pressOn = function () { welcome.classList.add("is-pressed"); };
    var pressOff = function () { welcome.classList.remove("is-pressed"); };

    // Pointer events cover mouse + touch in modern browsers.
    welcome.addEventListener("pointerdown", pressOn);
    welcome.addEventListener("pointerup", pressOff);
    welcome.addEventListener("pointercancel", pressOff);
    welcome.addEventListener("pointerleave", pressOff);

    // Fallbacks (older iOS Safari)
    welcome.addEventListener("touchstart", pressOn, { passive: true });
    welcome.addEventListener("touchend", pressOff);
    welcome.addEventListener("touchcancel", pressOff);
    welcome.addEventListener("mousedown", pressOn);
    document.addEventListener("mouseup", pressOff);
  }

})();
