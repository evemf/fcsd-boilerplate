
/*! FCSD Nav JS (v23) */
(function(){
  function onReady(fn){ if(document.readyState!=='loading'){fn();} else {document.addEventListener('DOMContentLoaded',fn);} }

  // 1) Sticky header detection (wrap containing top/middle/nav)
  function findHeaderWrap(){
    var nav = document.querySelector('.navbar.mainnav');
    if(!nav) return null;
    var node = nav;
    while(node && node !== document.body){
      if(node.querySelector('.middlebar') || node.querySelector('.topbar') || node.querySelector('.upperbar')) return node;
      node = node.parentElement;
    }
    var header = document.querySelector('header');
    return header || nav;
  }

  // 2) Toggle search on mobile (lupa)
  
function initSearchToggle(){
  var searchForm = document.querySelector('.middlebar form[role="search"]') || document.querySelector('form[role="search"]');
  if(!searchForm) return;
  var wrapper = searchForm.closest('.search-wrapper');
  if(!wrapper){
    wrapper = document.createElement('div');
    wrapper.className = 'search-wrapper';
    searchForm.parentNode.insertBefore(wrapper, searchForm);
    wrapper.appendChild(searchForm);
  }

  // Try to locate an existing search icon/button inside the middlebar (left of the input)
  var existingIcon = document.querySelector('.middlebar .search-icon, .middlebar .bi-search, .middlebar .fa-search, .middlebar [aria-label="Cerca"], .middlebar .input-group .input-group-prepend .input-group-text, .middlebar .input-group-text');
  // Fallback: any element with role=button and search label near the form
  if(!existingIcon){
    var candidates = Array.from(document.querySelectorAll('.middlebar .input-group-text, .middlebar button, .middlebar a, .middlebar i, .middlebar span'));
    existingIcon = candidates.find(function(el){
      var t = (el.getAttribute('aria-label') || el.title || el.textContent || '').toLowerCase();
      return t.includes('cerca') || t.includes('buscar') || t.includes('search');
    });
  }

  function toggleForm(){
    var open = wrapper.classList.toggle('is-open');
    if(open){
      var input = searchForm.querySelector('input[type="search"], input[type="text"], input[name="q"]');
      if(input){ setTimeout(function(){ input.focus(); input.select && input.select(); }, 10); }
    }
  }

  if(existingIcon){
    existingIcon.style.cursor = 'pointer';
    existingIcon.setAttribute('role','button');
    existingIcon.setAttribute('tabindex','0');
    existingIcon.addEventListener('click', toggleForm);
    existingIcon.addEventListener('keydown', function(e){ if(e.key === 'Enter' || e.key === ' ') { e.preventDefault(); toggleForm(); } });
  }
  // If no icon found, keep the form visible on desktop and collapsed on mobile without a toggle.
  searchForm.addEventListener('keydown', function(e){ if(e.key==='Escape'){ wrapper.classList.remove('is-open'); } });
}

  // 3) Contrast toggle (bind to button having text 'Contrast', .contrast-toggle or [data-contrast-toggle])
  function initContrastToggle(){
    var btn = document.querySelector('[data-contrast-toggle]') ||
              document.querySelector('.contrast-toggle') ||
              Array.from(document.querySelectorAll('button, a')).find(function(el){ return (el.textContent||'').trim().toLowerCase()==='contrast'; });
    if(btn){ btn.addEventListener('click', function(){ document.body.classList.toggle('contrast-mode'); }); }
  }

  // 4) Mobile collapse helpers: lock html/body scroll and focus management
  function initMobileCollapse(){
    var collapse = document.querySelector('.navbar.mainnav .navbar-collapse');
    var toggler = document.querySelector('.navbar.mainnav .navbar-toggler');
    if(!collapse) return;
    var lastFocus = null;
    function shown(){
      document.body.classList.add('menu-open');
      document.documentElement.classList.add('menu-open');
      lastFocus = document.activeElement;
      var first = collapse.querySelector('a, button, input, [tabindex]:not([tabindex="-1"])');
      if(first){ setTimeout(function(){ first.focus(); }, 0); }
    }
    function hidden(){
      document.body.classList.remove('menu-open');
      document.documentElement.classList.remove('menu-open');
      if(lastFocus && lastFocus.focus){ setTimeout(function(){ lastFocus.focus(); }, 0); }
    }
    function isShown(){ return collapse.classList.contains('show'); }

    document.addEventListener('keydown', function(e){
      if(e.key==='Escape' && isShown()){
        if(typeof jQuery!=='undefined' && jQuery(collapse).collapse) jQuery(collapse).collapse('hide');
        else collapse.classList.remove('show');
      }
    });
    document.addEventListener('click', function(e){
      if(!isShown()) return;
      var inside = e.target.closest('.navbar.mainnav .navbar-collapse') || e.target.closest('.navbar.mainnav .navbar-toggler');
      if(!inside){
        if(typeof jQuery!=='undefined' && jQuery(collapse).collapse) jQuery(collapse).collapse('hide');
        else collapse.classList.remove('show');
      }
      if(e.target.closest('.navbar.mainnav .nav-link, .navbar.mainnav .dropdown-item')){
        if(typeof jQuery!=='undefined' && jQuery(collapse).collapse) jQuery(collapse).collapse('hide');
        else collapse.classList.remove('show');
      }
    });

    if(typeof jQuery!=='undefined' && jQuery(collapse).on){
      jQuery(collapse).on('shown.bs.collapse', shown);
      jQuery(collapse).on('hidden.bs.collapse', hidden);
    } else if(toggler){
      toggler.addEventListener('click', function(){ setTimeout(function(){ isShown()? shown(): hidden(); }, 0); });
    }
  }

  onReady(function(){
    var wrap = findHeaderWrap(); if(wrap){ wrap.classList.add('sticky-header'); }
    initSearchToggle();
    initContrastToggle();
    initMobileCollapse();
  });
})();
