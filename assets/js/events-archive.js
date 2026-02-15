(function () {
  "use strict";

  // Progressive enhancement: allow CSS to hide classic pagination when JS is on.
  document.documentElement.classList.add("js");

  var root = document.querySelector(".archive-events");
  if (!root || typeof window.fcsdEventsArchive === "undefined") return;

  var cfg = window.fcsdEventsArchive;
  var grid = root.querySelector(".events-grid");
  var loading = root.querySelector(".events-loading");
  var empty = root.querySelector(".events-empty");
  var searchInput = root.querySelector("[data-events-search]");
  var chips = Array.prototype.slice.call(root.querySelectorAll("[data-events-term]"));
  var loadMoreBtn = root.querySelector("[data-events-load-more]");
  var sentinel = root.querySelector(".events-sentinel");

  if (!grid) return;

  var state = {
    page: 1,
    maxPages: parseInt(root.getAttribute("data-max-pages"), 10) || 1,
    termId: 0,
    search: "",
    isLoading: false,
  };

  function setLoading(isLoading) {
    state.isLoading = isLoading;
    if (loading) loading.classList.toggle("is-visible", !!isLoading);
    if (loadMoreBtn) loadMoreBtn.disabled = !!isLoading;
  }

  function setEmpty(isEmpty) {
    if (empty) empty.style.display = isEmpty ? "block" : "none";
  }

  function setActiveChip(termId) {
    chips.forEach(function (btn) {
      var isActive = parseInt(btn.getAttribute("data-events-term"), 10) === termId;
      btn.classList.toggle("is-active", isActive);
      btn.setAttribute("aria-pressed", isActive ? "true" : "false");
    });
  }

  function buildFormData(page) {
    var fd = new FormData();
    fd.append("action", "fcsd_events_query");
    fd.append("nonce", cfg.nonce);
    fd.append("page", String(page));
    fd.append("per_page", String(cfg.perPage || 12));
    fd.append("term_id", String(state.termId || 0));
    fd.append("search", state.search || "");
    return fd;
  }

  function updateLoadMoreVisibility() {
    if (!loadMoreBtn) return;
    var canLoadMore = state.page < state.maxPages;
    loadMoreBtn.parentElement.style.display = canLoadMore ? "flex" : "none";
  }

  function fetchPage(page, append) {
    if (state.isLoading) return;
    setLoading(true);

    return fetch(cfg.ajaxUrl, {
      method: "POST",
      credentials: "same-origin",
      body: buildFormData(page),
    })
      .then(function (r) {
        return r.json();
      })
      .then(function (payload) {
        if (!payload || !payload.success) {
          throw new Error("bad_response");
        }

        var html = (payload.data && payload.data.html) || "";
        state.maxPages = (payload.data && payload.data.max_pages) || 1;

        if (!append) {
          grid.innerHTML = "";
        }

        if (html.trim()) {
          grid.insertAdjacentHTML("beforeend", html);
        }

        var hasAnyCards = grid.querySelectorAll("article").length > 0;
        setEmpty(!hasAnyCards);

        state.page = page;
        updateLoadMoreVisibility();
      })
      .catch(function () {
        // If something fails, keep the current UI but remove the loader.
      })
      .finally(function () {
        setLoading(false);
      });
  }

  function resetAndLoad() {
    state.page = 1;
    fetchPage(1, false);
  }

  // Chip clicks
  chips.forEach(function (btn) {
    btn.addEventListener("click", function () {
      var termId = parseInt(btn.getAttribute("data-events-term"), 10) || 0;
      state.termId = termId;
      setActiveChip(termId);
      resetAndLoad();
    });
  });

  // Search (debounced)
  var t = null;
  if (searchInput) {
    searchInput.addEventListener("input", function () {
      window.clearTimeout(t);
      t = window.setTimeout(function () {
        state.search = (searchInput.value || "").trim();
        resetAndLoad();
      }, 250);
    });
  }

  // Load more button
  if (loadMoreBtn) {
    loadMoreBtn.addEventListener("click", function () {
      if (state.page >= state.maxPages) return;
      fetchPage(state.page + 1, true);
    });
  }

  // Infinite scroll (progressive enhancement)
  if (sentinel && "IntersectionObserver" in window) {
    var io = new IntersectionObserver(
      function (entries) {
        entries.forEach(function (e) {
          if (!e.isIntersecting) return;
          if (state.isLoading) return;
          if (state.page >= state.maxPages) return;
          fetchPage(state.page + 1, true);
        });
      },
      { rootMargin: "600px 0px" }
    );
    io.observe(sentinel);
  }

  // Initial state
  setActiveChip(0);
  setEmpty(grid.querySelectorAll("article").length === 0);
  updateLoadMoreVisibility();
})();
