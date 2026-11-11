/* KINZ slide-over shopping bag - same UI as the Node.js reference (cart drawer),
   wired to the PHP/MySQL cart (cart_api.php) and wishlist (account_api.php). */
(function () {
  'use strict';
  if (window.KINZCartDrawer) return;

  var csrf = (document.querySelector('meta[name="kinz-csrf"]') || {}).content || '';
  var cart = { items: [], count: 0, summary: { subtotal: 0 } };
  var suggestions = [];
  var pendingRemove = null;
  var built = false;

  function esc(v) { return String(v == null ? '' : v).replace(/[&<>"']/g, function (c) { return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[c]; }); }
  function inr(n) { return '₹ ' + Math.round(Number(n || 0)).toLocaleString('en-IN'); }

  function injectStyle() {
    var st = document.createElement('style');
    st.textContent =
      '.bag-remove-choice{margin-top:8px;padding:8px 10px;border:1px dashed var(--border-gold,#d9c7a4);border-radius:8px;background:#fffdf8;font-size:.76rem;color:#444}' +
      '.bag-remove-choice .rc-actions{display:flex;flex-wrap:wrap;gap:6px;margin-top:6px}' +
      '.bag-remove-choice button{font:600 .72rem Poppins,Arial,sans-serif;padding:5px 9px;border-radius:6px;border:1px solid #cfd8d3;background:#fff;color:#174b3d;cursor:pointer}' +
      '.bag-remove-choice button.rc-danger{background:#9c332e;border-color:#9c332e;color:#fff}' +
      '.bag-remove-choice button:disabled{opacity:.6;cursor:wait}' +
      '.cart-drawer .drawer-toast{position:absolute;left:16px;right:16px;bottom:150px;background:#174b3d;color:#fff;padding:9px 12px;border-radius:8px;font-size:.8rem;display:none;z-index:5}' +
      '.cart-drawer .drawer-toast.is-error{background:#9c332e}';
    document.head.appendChild(st);
  }

  function build() {
    if (built) return;
    built = true;
    injectStyle();
    var drawer = document.createElement('div');
    drawer.className = 'cart-drawer';
    drawer.id = 'cart-drawer';
    drawer.setAttribute('role', 'dialog');
    drawer.setAttribute('aria-label', 'Shopping bag');
    drawer.innerHTML =
      '<div class="cart-drawer-header"><div class="cart-drawer-title"><i class="fa-solid fa-chevron-left"></i> Cart (<span class="drawer-header-count">0</span>)</div>' +
      '<button class="cart-drawer-close" type="button" aria-label="Close cart">&times;</button></div>' +
      '<div class="cart-drawer-body"><div class="drawer-section-heading">IN YOUR BAG</div>' +
      '<div class="bag-item-list" id="drawer-bag-items"></div>' +
      '<div class="last-minute-section"><div class="drawer-section-heading">LAST MINUTE ADDITIONS</div><div class="last-minute-grid" id="drawer-cross-sells"></div></div></div>' +
      '<div class="drawer-toast" id="drawer-toast" role="status"></div>' +
      '<div class="cart-drawer-footer"><div class="drawer-subtotal-row"><span class="drawer-subtotal-label">Subtotal (<span class="drawer-footer-count">0</span> items)</span>' +
      '<span class="drawer-subtotal-val" id="drawer-subtotal-price">₹ 0</span></div>' +
      '<div class="drawer-free-shipping-note"><i class="fa-solid fa-truck-fast"></i> Free Insured Delivery on every order</div>' +
      '<button class="btn-drawer-checkout" type="button" id="drawer-checkout-btn">Checkout <span id="drawer-checkout-total">₹ 0</span></button>' +
      '<div class="drawer-secure-note"><i class="fa-solid fa-lock"></i> Secure Payments <span style="font-weight:700;color:#111;margin-left:4px;">[UPI] [Card] [NetBanking]</span></div></div>';
    var backdrop = document.createElement('div');
    backdrop.className = 'drawer-backdrop';
    document.body.appendChild(backdrop);
    document.body.appendChild(drawer);

    drawer.querySelector('.cart-drawer-close').addEventListener('click', close);
    drawer.querySelector('.cart-drawer-title').addEventListener('click', close);
    backdrop.addEventListener('click', close);
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && drawer.classList.contains('open')) close(); });
    drawer.querySelector('#drawer-checkout-btn').addEventListener('click', function () {
      if (!cart.count) { toast('Your bag is empty. Add a piece to continue.', true); return; }
      window.location.href = 'checkout.php';
    });
    drawer.addEventListener('click', onDrawerClick);
  }

  function toast(msg, isError) {
    if (window.KINZToast) { window.KINZToast.show(msg, isError ? 'error' : ''); return; }
    var t = document.getElementById('drawer-toast');
    if (!t) return;
    t.textContent = msg; t.className = 'drawer-toast' + (isError ? ' is-error' : ''); t.style.display = 'block';
    clearTimeout(t._h); t._h = setTimeout(function () { t.style.display = 'none'; }, 2600);
  }

  async function cartRequest(action, values, method) {
    var isPost = method === 'POST';
    var params = new URLSearchParams(Object.assign({ action: action }, values || {}));
    if (isPost) params.set('csrf_token', csrf);
    var url = 'cart_api.php' + (isPost ? '' : '?' + params.toString() + '&_=' + Date.now());
    var res = await fetch(url, { method: isPost ? 'POST' : 'GET', credentials: 'same-origin', cache: 'no-store', headers: { 'Accept': 'application/json', 'X-CSRF-Token': csrf }, body: isPost ? params : undefined });
    var data = await res.json().catch(function () { return null; });
    if (res.status === 401) { window.location.href = 'login.php'; throw new Error('Please sign in.'); }
    if (!res.ok || !data || data.success === false) throw new Error((data && data.message) || 'Something went wrong. Please try again.');
    return data;
  }

  async function accountRequest(action, values, method) {
    var isPost = method === 'POST';
    var params = new URLSearchParams(Object.assign({ action: action }, values || {}));
    var res = await fetch('account_api.php' + (isPost ? '' : '?' + params.toString()), { method: isPost ? 'POST' : 'GET', credentials: 'same-origin', headers: { 'X-CSRF-Token': csrf }, body: isPost ? params : undefined });
    var data = await res.json().catch(function () { return null; });
    if (!res.ok || !data || data.success === false) throw new Error((data && data.message) || 'Unable to use your wishlist right now.');
    return data;
  }

  function applyCart(data) {
    cart = { items: data.items || [], count: Number(data.count || 0), summary: data.summary || { subtotal: 0 } };
    if (data.csrf_token) csrf = data.csrf_token;
    if (window.KINZSetBadge) {
      window.KINZSetBadge('.cart-badge-count', cart.count);
    } else {
      document.querySelectorAll('.cart-badge-count').forEach(function (b) {
        b.textContent = cart.count > 99 ? '99+' : String(cart.count);
        b.style.setProperty('display', cart.count > 0 ? 'inline-flex' : 'none', 'important');
      });
    }
    render();
  }

  function render() {
    var list = document.getElementById('drawer-bag-items');
    if (!list) return;
    document.querySelectorAll('.drawer-header-count, .drawer-footer-count').forEach(function (el) { el.textContent = cart.count; });
    var sub = inr(cart.summary.subtotal);
    var s1 = document.getElementById('drawer-subtotal-price'), s2 = document.getElementById('drawer-checkout-total');
    if (s1) s1.textContent = sub; if (s2) s2.textContent = sub;

    if (!cart.items.length) {
      list.innerHTML = '<div style="text-align:center;padding:36px 12px;color:#888;"><i class="fa-solid fa-bag-shopping" style="font-size:2.5rem;color:#ccc;margin-bottom:12px;"></i>' +
        '<p style="font-weight:600;font-size:1rem;color:#222;">Your bag is empty</p><p style="font-size:.85rem;margin-top:4px;">Discover our handcrafted royal collection.</p>' +
        '<button class="btn-royal-gold" type="button" data-drawer-go="shop.php" style="margin-top:16px;font-size:.82rem;padding:8px 18px;">Start Shopping</button></div>';
    } else {
      list.innerHTML = cart.items.map(function (item) {
        var id = esc(item.id), disc = Number(item.discount_pct) || 0;
        var choice = pendingRemove === String(item.id)
          ? '<div class="bag-remove-choice">Save this piece for later or remove it?<div class="rc-actions">' +
            '<button type="button" data-rc="move" data-id="' + id + '"><i class="fa-regular fa-heart"></i> Move to Wishlist</button>' +
            '<button type="button" class="rc-danger" data-rc="delete" data-id="' + id + '"><i class="fa-regular fa-trash-can"></i> Remove</button>' +
            '<button type="button" data-rc="cancel" data-id="' + id + '">Keep</button></div></div>' : '';
        return '<div class="bag-item-card"><div class="bag-item-thumb"><img src="' + esc(item.image_url) + '" alt="' + esc(item.title) + '">' +
          (disc > 0 ? '<span class="bag-item-discount-tag">' + disc + '% OFF</span>' : '') + '</div>' +
          '<div class="bag-item-info"><div class="bag-item-title">' + esc(item.title) + '</div><div class="bag-item-sku">SKU: ' + esc(item.sku) + '</div>' +
          '<div class="bag-item-pricing"><span class="bag-item-price">' + inr(item.unit_price) + '</span>' + (disc > 0 ? '<span class="bag-item-orig-price">' + inr(item.original_price) + '</span>' : '') + '</div>' +
          '<div class="bag-item-meta">' + esc(item.category) + '</div>' +
          '<div class="bag-item-controls"><div class="qty-spinner"><button class="qty-btn" type="button" data-q="dec" data-id="' + id + '" aria-label="Decrease quantity">-</button>' +
          '<span class="qty-value">' + Number(item.quantity) + '</span><button class="qty-btn" type="button" data-q="inc" data-id="' + id + '" aria-label="Increase quantity">+</button></div>' +
          '<button class="bag-item-delete" type="button" data-q="ask" data-id="' + id + '" title="Remove" aria-label="Remove item"><i class="fa-regular fa-trash-can"></i></button></div>' + choice + '</div></div>';
      }).join('');
    }

    var cross = document.getElementById('drawer-cross-sells');
    if (cross) {
      cross.innerHTML = suggestions.map(function (cs) {
        return '<div class="last-minute-card"><img src="' + esc(cs.image_url) + '" alt="' + esc(cs.title) + '" class="last-minute-thumb"><div class="last-minute-title">' + esc(cs.title) + '</div>' +
          '<div class="last-minute-price">' + inr(cs.price) + '</div><button class="btn-add-cross-sell" type="button" data-cs="' + esc(cs.id) + '">+ Add</button></div>';
      }).join('');
      var section = cross.closest('.last-minute-section');
      if (section) section.style.display = suggestions.length ? '' : 'none';
    }
  }

  async function onDrawerClick(e) {
    var t = e.target.closest ? e.target.closest('[data-q],[data-rc],[data-cs],[data-drawer-go]') : null;
    if (!t) return;
    var id = t.getAttribute('data-id');
    try {
      if (t.hasAttribute('data-drawer-go')) { window.location.href = t.getAttribute('data-drawer-go'); return; }
      if (t.hasAttribute('data-cs')) {
        t.disabled = true;
        applyCart(await cartRequest('add', { product_id: t.getAttribute('data-cs'), quantity: 1 }, 'POST'));
        await loadSuggestions(); render(); toast('Added to your bag.'); return;
      }
      var q = t.getAttribute('data-q');
      if (q === 'ask') { pendingRemove = String(id); render(); return; }
      if (q === 'inc' || q === 'dec') {
        var item = cart.items.filter(function (i) { return String(i.id) === String(id); })[0];
        if (!item) return;
        var next = Number(item.quantity) + (q === 'inc' ? 1 : -1);
        if (next < 1) { pendingRemove = String(id); render(); return; }
        applyCart(await cartRequest('update', { product_id: id, quantity: next }, 'POST'));
        return;
      }
      var rc = t.getAttribute('data-rc');
      if (rc === 'cancel') { pendingRemove = null; render(); return; }
      if (rc === 'delete') {
        t.disabled = true; pendingRemove = null;
        applyCart(await cartRequest('remove', { product_id: id }, 'POST'));
        await loadSuggestions(); render(); toast('Item removed from your bag.'); return;
      }
      if (rc === 'move') {
        t.disabled = true;
        var status = await accountRequest('wishlist_status', { product_id: id }, 'GET');
        if (!status.saved) await accountRequest('wishlist_toggle', { product_id: id }, 'POST');
        pendingRemove = null;
        applyCart(await cartRequest('remove', { product_id: id }, 'POST'));
        window.dispatchEvent(new Event('kinz:wishlist-updated'));
        await loadSuggestions(); render(); toast('Moved to your wishlist.'); return;
      }
    } catch (err) {
      if (t) t.disabled = false;
      toast(err.message || 'Please try again.', true);
    }
  }

  async function loadSuggestions() {
    try { suggestions = (await cartRequest('suggestions', {}, 'GET')).suggestions || []; } catch (_) { suggestions = []; }
  }

  async function refresh() {
    try { applyCart(await cartRequest('get_cart', {}, 'GET')); } catch (_) { render(); }
    await loadSuggestions();
    render();
  }

  function open() {
    build();
    render();
    document.querySelector('.cart-drawer').classList.add('open');
    document.querySelector('.drawer-backdrop').classList.add('show');
    document.body.style.overflow = 'hidden';
    refresh();
  }

  function close() {
    var d = document.querySelector('.cart-drawer'), b = document.querySelector('.drawer-backdrop');
    if (d) d.classList.remove('open');
    if (b) b.classList.remove('show');
    document.body.style.overflow = '';
    pendingRemove = null;
    if (window.KINZ_CART_PAGE) {
      var ref = document.referrer || '';
      if (ref && ref.indexOf(location.host) !== -1 && ref.indexOf('cart.php') === -1) history.back();
      else window.location.href = 'shop.php';
    }
  }

  window.KINZCartDrawer = { open: open, close: close, refresh: refresh };
  document.addEventListener('DOMContentLoaded', function () {
    build();
    if (window.KINZ_CART_PAGE) open();
  });
})();
