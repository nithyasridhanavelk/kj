(function () {
  'use strict';

  if (window.__kinzUnifiedCustomerHeaderV4) return;
  window.__kinzUnifiedCustomerHeaderV4 = true;

  var csrfToken = '';
  var savedProductIds = new Set();
  var headerRefreshRunning = false;
  var headerRefreshPromise = null;
  var headerRefreshTimer = null;

  function all(selector) {
    return Array.prototype.slice.call(document.querySelectorAll(selector));
  }

  function accountEndpoint(action) {
    var url = new URL('./account_api.php', window.location.href);
    url.searchParams.set('action', action);
    url.searchParams.set('_', String(Date.now()));
    return url.toString();
  }

  var BADGE_KEYS = {
    '.notification-badge-count': 'notification',
    '.wishlist-badge-count': 'wishlist',
    '.cart-badge-count': 'cart'
  };

  function readBadgeCache() {
    try { return JSON.parse(localStorage.getItem('kinz_badge_counts') || '{}') || {}; }
    catch (error) { return {}; }
  }

  function saveBadgeCache(selector, value) {
    var key = BADGE_KEYS[selector];
    if (!key) return;
    var cache = readBadgeCache();
    cache[key] = value;
    try { localStorage.setItem('kinz_badge_counts', JSON.stringify(cache)); } catch (error) {}
  }

  function paintCachedBadges() {
    var cache = readBadgeCache();
    Object.keys(BADGE_KEYS).forEach(function (selector) {
      var value = parseInt(cache[BADGE_KEYS[selector]], 10) || 0;
      if (value > 0) setBadge(selector, value, true);
    });
  }

  function setBadge(selector, count, skipSave) {
    var value = Math.max(0, parseInt(count, 10) || 0);
    if (!skipSave) saveBadgeCache(selector, value);
    all(selector).forEach(function (badge) {
      badge.textContent = value > 99 ? '99+' : String(value);
      badge.style.setProperty('display', value > 0 ? 'inline-flex' : 'none', 'important');
      badge.style.setProperty('visibility', 'visible', 'important');
      badge.style.setProperty('opacity', '1', 'important');
      badge.setAttribute('aria-hidden', value > 0 ? 'false' : 'true');
    });
  }

  function paintWishlistButton(button, saved) {
    if (!button) return;
    button.classList.toggle('is-saved', !!saved);
    button.setAttribute('aria-pressed', saved ? 'true' : 'false');
    button.setAttribute('aria-label', saved ? 'Remove from Wishlist' : 'Add to Wishlist');
    button.title = saved ? 'Remove from Wishlist' : 'Add to Wishlist';
    var icon = button.querySelector('i');
    if (icon) {
      icon.classList.toggle('fa-solid', !!saved);
      icon.classList.toggle('fa-regular', !saved);
      icon.classList.toggle('fa-heart', true);
    } else {
      button.innerHTML = '<i class="' + (saved ? 'fa-solid' : 'fa-regular') + ' fa-heart" aria-hidden="true"></i>';
    }
  }

  function paintAllProductWishlistButtons() {
    all('.home-product-wishlist[data-product-id]').forEach(function (button) {
      var id = parseInt(button.getAttribute('data-product-id'), 10) || 0;
      paintWishlistButton(button, id > 0 && savedProductIds.has(id));
    });
  }

  function renderHeaderState(data) {
    var unreadNotifications = Math.max(0, parseInt(data.notification_count, 10) || 0);
    setBadge('.notification-badge-count', unreadNotifications);
    all('.account-nav-notification-count').forEach(function (badge) {
      badge.textContent = unreadNotifications > 99 ? '99+' : String(unreadNotifications);
      badge.style.setProperty('display', unreadNotifications > 0 ? 'inline-flex' : 'none', 'important');
      badge.setAttribute('aria-hidden', unreadNotifications > 0 ? 'false' : 'true');
      badge.setAttribute('title', unreadNotifications + ' unread notification' + (unreadNotifications === 1 ? '' : 's'));
    });
    all('.notification-action-btn').forEach(function (link) {
      link.setAttribute('aria-label', unreadNotifications > 0 ? 'Notifications, ' + unreadNotifications + ' unread' : 'Notifications');
      link.title = unreadNotifications > 0 ? 'Notifications (' + unreadNotifications + ' unread)' : 'Notifications';
    });
    setBadge('.wishlist-badge-count', data.wishlist_count);
    csrfToken = String(data.csrf_token || csrfToken || '');
    savedProductIds = new Set((Array.isArray(data.wishlist_ids) ? data.wishlist_ids : [])
      .map(function (id) { return parseInt(id, 10); })
      .filter(function (id) { return id > 0; }));
    paintAllProductWishlistButtons();
  }

  function refreshHeaderState() {
    if (headerRefreshRunning) return headerRefreshPromise || Promise.resolve();
    headerRefreshRunning = true;
    headerRefreshPromise = fetch(accountEndpoint('header_state'), {
      method: 'GET',
      credentials: 'same-origin',
      cache: 'no-store',
      headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
    })
      .then(function (response) {
        if (!response.ok) throw new Error('Header state request failed: ' + response.status);
        return response.json();
      })
      .then(function (data) {
        if (!data || data.success !== true) throw new Error((data && data.message) || 'Header state unavailable');
        renderHeaderState(data);
        return data;
      })
      .catch(function (error) {
        console.warn('KINZ header update failed:', error.message || error);
        return null;
      })
      .finally(function () {
        headerRefreshRunning = false;
        headerRefreshPromise = null;
      });
    return headerRefreshPromise;
  }

  function ensureToastStyles() {
    if (document.getElementById('kinz-header-toast-css')) return;
    var css = document.createElement('style');
    css.id = 'kinz-header-toast-css';
    css.textContent =
      '#kinz-header-toast{position:fixed;right:20px;top:calc(96px + env(safe-area-inset-top,0px));z-index:2147483000;' +
      'transform:translateX(30px) scale(.96);opacity:0;pointer-events:none;visibility:hidden;' +
      'display:flex;align-items:center;gap:14px;min-width:290px;max-width:min(400px,calc(100vw - 24px));' +
      'padding:14px 14px 16px 14px;border-radius:16px;overflow:hidden;color:#fff;' +
      'background:rgba(10,61,46,.55);-webkit-backdrop-filter:blur(16px) saturate(160%);backdrop-filter:blur(16px) saturate(160%);' +
      'border:1px solid rgba(255,255,255,.28);' +
      'box-shadow:0 18px 44px rgba(0,0,0,.35),inset 0 1px 0 rgba(255,255,255,.25);' +
      'font:600 14.5px/1.4 Poppins,Arial,sans-serif;' +
      'transition:transform .38s cubic-bezier(.16,1,.3,1),opacity .3s ease,visibility 0s linear .38s}' +
      '#kinz-header-toast.is-visible{transform:translateX(0) scale(1);opacity:1;pointer-events:auto;visibility:visible;' +
      'transition:transform .38s cubic-bezier(.16,1,.3,1),opacity .3s ease,visibility 0s}' +
      '#kinz-header-toast .kt-icon{flex:0 0 auto;width:38px;height:38px;border-radius:50%;display:flex;align-items:center;justify-content:center;' +
      'background:#22c55e;color:#fff;font-size:16px;box-shadow:0 0 0 4px rgba(34,197,94,.25)}' +
      '#kinz-header-toast .kt-text{flex:1;min-width:0}' +
      '#kinz-header-toast .kt-title{display:block;font:700 10.5px/1 Poppins,Arial,sans-serif;letter-spacing:1.6px;text-transform:uppercase;color:#86efac;margin-bottom:4px}' +
      '#kinz-header-toast .kt-msg{display:block;color:#fff}' +
      '#kinz-header-toast .kt-close{flex:0 0 auto;border:0;background:transparent;color:rgba(255,255,255,.75);font-size:22px;line-height:1;cursor:pointer;padding:2px 6px;border-radius:6px}' +
      '#kinz-header-toast .kt-close:hover{background:rgba(255,255,255,.15);color:#fff}' +
      '#kinz-header-toast .kt-bar{position:absolute;left:0;bottom:0;height:3.5px;width:100%;background:#22c55e;transform-origin:left;transform:scaleX(1)}' +
      '#kinz-header-toast.is-visible .kt-bar{animation:kinzToastBar 2.8s linear forwards}' +
      '#kinz-header-toast.is-remove{background:rgba(127,29,29,.55)}' +
      '#kinz-header-toast.is-remove .kt-icon{background:#ef4444;box-shadow:0 0 0 4px rgba(239,68,68,.28)}' +
      '#kinz-header-toast.is-remove .kt-title{color:#fca5a5}' +
      '#kinz-header-toast.is-remove .kt-bar{background:#ef4444}' +
      '#kinz-header-toast.is-error{background:rgba(120,53,15,.58)}' +
      '#kinz-header-toast.is-error .kt-icon{background:#f59e0b;box-shadow:0 0 0 4px rgba(245,158,11,.28)}' +
      '#kinz-header-toast.is-error .kt-title{color:#fcd34d}' +
      '#kinz-header-toast.is-error .kt-bar{background:#f59e0b}' +
      '@keyframes kinzToastBar{from{transform:scaleX(1)}to{transform:scaleX(0)}}' +
      '@media (max-width:600px){#kinz-header-toast{right:12px;top:calc(76px + env(safe-area-inset-top,0px));min-width:0;width:calc(100vw - 24px)}}';
    document.head.appendChild(css);
  }

  function showWishlistMessage(message, error, kind) {
    ensureToastStyles();
    var node = document.getElementById('kinz-header-toast');
    if (!node) {
      node = document.createElement('div');
      node.id = 'kinz-header-toast';
      node.setAttribute('role', 'status');
      node.setAttribute('aria-live', 'polite');
      node.innerHTML =
        '<span class="kt-icon"></span>' +
        '<span class="kt-text"><span class="kt-title"></span><span class="kt-msg"></span></span>' +
        '<button type="button" class="kt-close" aria-label="Close">&times;</button><span class="kt-bar"></span>';
      node.querySelector('.kt-close').addEventListener('click', function () { hideHeaderToast(node); });
      document.body.appendChild(node);
    }
    var text = String(message || '');
    var isBag = /bag|cart/i.test(text);
    var isWish = /wishlist/i.test(text);
    var isRemoved = kind === 'remove' || (!kind && /removed|deleted/i.test(text));
    var ICONS = {
      heart: 'M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z',
      heartOutline: 'M16.5 3c-1.74 0-3.41.81-4.5 2.09C10.91 3.81 9.24 3 7.5 3 4.42 3 2 5.42 2 8.5c0 3.78 3.4 6.86 8.55 11.54L12 21.35l1.45-1.32C18.6 15.36 22 12.28 22 8.5 22 5.42 19.58 3 16.5 3zm-4.4 15.55l-.1.1-.1-.1C7.14 14.24 4 11.39 4 8.5 4 6.5 5.5 5 7.5 5c1.54 0 3.04.99 3.57 2.36h1.87C13.46 5.99 14.96 5 16.5 5c2 0 3.5 1.5 3.5 3.5 0 2.89-3.14 5.74-7.9 10.05z',
      bag: 'M18 6h-2c0-2.21-1.79-4-4-4S8 3.79 8 6H6c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V8c0-1.1-.9-2-2-2zm-6-2c1.1 0 2 .9 2 2h-4c0-1.1.9-2 2-2zm0 10c-2.76 0-5-2.24-5-5h2c0 1.66 1.34 3 3 3s3-1.34 3-3h2c0 2.76-2.24 5-5 5z',
      check: 'M9 16.17L4.83 12l-1.41 1.41L9 19 21 7l-1.41-1.41z',
      trash: 'M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z',
      warn: 'M1 21h22L12 2 1 21zm12-3h-2v-2h2v2zm0-4h-2v-4h2v4z'
    };
    var iconPath = error ? ICONS.warn : (isRemoved ? (isBag ? ICONS.trash : ICONS.heartOutline) : (isBag ? ICONS.bag : (isWish ? ICONS.heart : ICONS.check)));
    var title = error ? 'Something went wrong' : (isRemoved ? 'Removed' : (isBag ? 'Added to bag' : (isWish ? 'Saved to wishlist' : 'Success')));
    node.querySelector('.kt-icon').innerHTML = '<svg viewBox="0 0 24 24" width="19" height="19" fill="currentColor" aria-hidden="true"><path d="' + iconPath + '"/></svg>';
    node.querySelector('.kt-title').textContent = title;
    node.querySelector('.kt-msg').textContent = text;
    node.classList.toggle('is-error', !!error);
    node.classList.toggle('is-remove', !error && isRemoved);
    node.classList.remove('is-visible');
    void node.offsetWidth;
    node.classList.add('is-visible');
    clearTimeout(node._hideTimer);
    node._hideTimer = setTimeout(function () { hideHeaderToast(node); }, 2800);
  }

  function hideHeaderToast(node) {
    if (!node) return;
    clearTimeout(node._hideTimer);
    node.classList.remove('is-visible');
  }

  window.KINZToast = {
    show: function (message, kind) {
      showWishlistMessage(message, kind === 'error', kind === 'error' ? '' : kind);
    }
  };

  async function toggleCardWishlist(button) {
    var productId = parseInt(button.getAttribute('data-product-id'), 10) || 0;
    if (!productId || button.disabled) return;
    button.disabled = true;
    try {
      if (!csrfToken) await refreshHeaderState();
      if (!csrfToken) throw new Error('Please refresh the page and try again.');
      var body = new URLSearchParams();
      body.set('action', 'wishlist_toggle');
      body.set('product_id', String(productId));
      body.set('csrf_token', csrfToken);
      var response = await fetch(new URL('./account_api.php', window.location.href).toString(), {
        method: 'POST',
        credentials: 'same-origin',
        cache: 'no-store',
        headers: { 'Accept': 'application/json', 'X-CSRF-Token': csrfToken },
        body: body
      });
      var data = await response.json();
      if (response.status === 401) {
        window.location.href = 'login.php';
        return;
      }
      if (!response.ok || !data || data.success !== true) {
        throw new Error((data && data.message) || 'Unable to update your wishlist.');
      }
      if (data.saved) savedProductIds.add(productId);
      else savedProductIds.delete(productId);
      paintAllProductWishlistButtons();
      window.dispatchEvent(new CustomEvent('kinz:wishlist-updated'));
      refreshHeaderState();
      showWishlistMessage(data.message || (data.saved ? 'Added to Wishlist.' : 'Removed from Wishlist.'), false);
    } catch (error) {
      showWishlistMessage(error.message || 'Unable to update Wishlist.', true);
    } finally {
      button.disabled = false;
    }
  }

  document.addEventListener('click', function (event) {
    var button = event.target && event.target.closest ? event.target.closest('.home-product-wishlist[data-product-id]') : null;
    if (!button) return;
    event.preventDefault();
    event.stopPropagation();
    toggleCardWishlist(button);
  }, true);

  function updateCartBadge() {
    fetch(new URL('./cart_api.php?action=get_cart&_=' + Date.now(), window.location.href).toString(), {
      credentials: 'same-origin',
      cache: 'no-store',
      headers: { 'Accept': 'application/json' }
    })
      .then(function (response) { return response.ok ? response.json() : null; })
      .then(function (data) {
        if (data && data.success) setBadge('.cart-badge-count', data.count);
      })
      .catch(function () {});
  }

  document.addEventListener('click', function (event) {
    var cartButton = event.target && event.target.closest ? event.target.closest('.open-cart-drawer-btn') : null;
    if (cartButton) {
      event.preventDefault();
      if (window.KINZCartDrawer) window.KINZCartDrawer.open();
      else window.location.href = 'cart.php';
    }
  });

  // Product-card "Add to Cart" (matches the original UI: button on every card).
  async function addCardToCart(button) {
    var productId = parseInt(button.getAttribute('data-product-id'), 10) || 0;
    if (!productId || button.disabled) return;
    var original = button.innerHTML;
    button.disabled = true;
    try {
      if (!csrfToken) await refreshHeaderState();
      if (!csrfToken) throw new Error('Please refresh the page and try again.');
      var body = new URLSearchParams();
      body.set('action', 'add');
      body.set('product_id', String(productId));
      body.set('quantity', '1');
      body.set('csrf_token', csrfToken);
      var response = await fetch(new URL('./cart_api.php', window.location.href).toString(), {
        method: 'POST',
        credentials: 'same-origin',
        cache: 'no-store',
        headers: { 'Accept': 'application/json', 'X-CSRF-Token': csrfToken },
        body: body
      });
      var data = await response.json();
      if (response.status === 401) { window.location.href = 'login.php'; return; }
      if (!response.ok || !data || data.success !== true) {
        throw new Error((data && data.message) || 'Unable to add this item to your bag.');
      }
      setBadge('.cart-badge-count', data.count);
      button.innerHTML = '<i class="fa-solid fa-check"></i> Added';
      showWishlistMessage('Added to your shopping bag.', false);
      setTimeout(function () { button.innerHTML = original; }, 1600);
    } catch (error) {
      button.innerHTML = original;
      showWishlistMessage(error.message || 'Unable to add this item to your bag.', true);
    } finally {
      button.disabled = false;
    }
  }

  document.addEventListener('click', function (event) {
    var addButton = event.target && event.target.closest ? event.target.closest('[data-card-add-to-cart]') : null;
    if (!addButton) return;
    event.preventDefault();
    addCardToCart(addButton);
  });

  function start() {
    refreshHeaderState();
    updateCartBadge();
    if (headerRefreshTimer) clearInterval(headerRefreshTimer);
    headerRefreshTimer = setInterval(refreshHeaderState, 8000);
    window.addEventListener('pageshow', refreshHeaderState);
    window.addEventListener('focus', refreshHeaderState);
    window.addEventListener('kinz:notifications-updated', refreshHeaderState);
    window.addEventListener('kinz:wishlist-updated', refreshHeaderState);
    document.addEventListener('visibilitychange', function () {
      if (!document.hidden) {
        refreshHeaderState();
        updateCartBadge();
      }
    });
  }

  paintCachedBadges();

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function () {
      paintCachedBadges();
      start();
    }, { once: true });
  } else {
    start();
  }

  window.KINZSetBadge = setBadge;

  window.KINZCustomerNotificationBadge = {
    refresh: refreshHeaderState,
    update: function (count) { setBadge('.notification-badge-count', count); }
  };
})();