(function () {
  const token = document.querySelector('meta[name="kinz-csrf"]')?.content || '';
  const list = document.getElementById('cart-page-items-list');
  if (!list) return;

  const emptyView = document.getElementById('cart-page-empty');
  const proceed = document.getElementById('btn-proceed-checkout');
  const modal = document.getElementById('cart-remove-modal');
  const modalImage = document.getElementById('cart-remove-product-image');
  const modalName = document.getElementById('cart-remove-product-name');
  const modalPrice = document.getElementById('cart-remove-product-price');
  const feedback = document.getElementById('cart-remove-feedback');
  const signInLink = document.getElementById('cart-remove-signin');
  const moveButton = document.getElementById('cart-remove-move');
  const deleteButton = document.getElementById('cart-remove-delete');
  const toast = document.getElementById('cart-page-toast');
  const couponInput = document.getElementById('cart-coupon-code');
  const couponButton = document.getElementById('btn-cart-coupon');
  const couponMessage = document.getElementById('cart-coupon-message');

  let cartItems = new Map();
  let selectedProductId = null;
  let lastFocusedElement = null;
  let toastTimer = null;

  function money(value) {
    return '₹ ' + Number(value || 0).toLocaleString('en-IN', { minimumFractionDigits: 0, maximumFractionDigits: 2 });
  }

  function escapeHtml(value) {
    return String(value ?? '').replace(/[&<>"']/g, function (char) {
      return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' })[char];
    });
  }

  function showToast(message, kind) {
    if (window.KINZToast) { window.KINZToast.show(message, kind); return; }
    toast.textContent = message;
    toast.classList.add('is-visible');
    if (toastTimer) window.clearTimeout(toastTimer);
    toastTimer = window.setTimeout(() => toast.classList.remove('is-visible'), 3200);
  }

  async function request(action, values) {
    const body = new URLSearchParams(Object.assign({ action: action }, values || {}));
    const response = await fetch('cart_api.php', {
      method: 'POST', credentials: 'same-origin', body: body,
      headers: { 'X-CSRF-Token': token }
    });
    const data = await response.json();
    if (!response.ok || !data.success) throw new Error(data.message || 'Unable to update the cart.');
    return data;
  }

  async function accountRequest(action, values, method) {
    const isPost = method === 'POST';
    const url = isPost ? 'account_api.php' : 'account_api.php?' + new URLSearchParams(Object.assign({ action: action }, values || {})).toString();
    const options = { method: method || 'GET', credentials: 'same-origin', headers: {} };
    if (isPost) {
      options.body = new URLSearchParams(Object.assign({ action: action }, values || {}));
      options.headers['X-CSRF-Token'] = token;
    }
    const response = await fetch(url, options);
    let data;
    try { data = await response.json(); }
    catch (_) { throw new Error('The wishlist service returned an unexpected response.'); }
    if (response.status === 401) {
      const error = new Error(data.message || 'Please sign in to use your wishlist.');
      error.signInRequired = true;
      throw error;
    }
    if (!response.ok || !data.success) throw new Error(data.message || 'Unable to update your wishlist.');
    return data;
  }

  async function refreshWishlistBadge() {
    try {
      const data = await accountRequest('bootstrap', {}, 'GET');
      const count = Array.isArray(data.wishlist) ? data.wishlist.length : 0;
      document.querySelectorAll('.wishlist-badge-count').forEach(function (badge) {
        badge.textContent = String(count);
        badge.style.display = count > 0 ? 'inline-flex' : 'none';
      });
    } catch (_) {
      document.querySelectorAll('.wishlist-badge-count').forEach(function (badge) {
        badge.style.display = 'none';
      });
    }
  }

  function setBusy(isBusy) {
    [moveButton, deleteButton].forEach(function (button) {
      button.disabled = isBusy;
    });
    moveButton.innerHTML = isBusy
      ? '<i class="fa-solid fa-spinner fa-spin"></i> Please wait...'
      : '<i class="fa-regular fa-heart"></i> Move to Wishlist';
  }

  function resetFeedback() {
    feedback.textContent = '';
    feedback.classList.remove('is-visible');
    signInLink.classList.remove('is-visible');
  }

  function showFeedback(message, requireSignIn) {
    feedback.textContent = message;
    feedback.classList.add('is-visible');
    signInLink.classList.toggle('is-visible', Boolean(requireSignIn));
  }

  function openRemoveDialog(productId, trigger) {
    const item = cartItems.get(String(productId));
    if (!item) return;
    selectedProductId = String(productId);
    lastFocusedElement = trigger || document.activeElement;
    resetFeedback();
    setBusy(false);
    modalImage.src = item.image_url || 'assets/images/kinz_logo.png';
    modalImage.alt = item.title || 'Selected product';
    modalName.textContent = item.title || 'Selected jewellery';
    modalPrice.textContent = money(item.line_total || item.unit_price);
    modal.classList.add('is-open');
    modal.setAttribute('aria-hidden', 'false');
    document.body.style.overflow = 'hidden';
    moveButton.focus();
  }

  function closeRemoveDialog() {
    modal.classList.remove('is-open');
    modal.setAttribute('aria-hidden', 'true');
    document.body.style.overflow = '';
    selectedProductId = null;
    if (lastFocusedElement && typeof lastFocusedElement.focus === 'function') lastFocusedElement.focus();
  }

  function render(data) {
    const items = data.items || [];
    const summary = data.summary || {};
    const count = Number(data.count || 0);
    cartItems = new Map(items.map(item => [String(item.id), item]));

    document.getElementById('cart-page-count').textContent = String(count);
    document.querySelectorAll('.cart-badge-count').forEach(function (badge) {
      badge.textContent = String(count);
      badge.style.display = count > 0 ? 'inline-flex' : 'none';
    });

    list.innerHTML = items.map(function (item) {
      return '<article class="bag-item-card">' +
        '<div class="bag-item-thumb"><img src="' + escapeHtml(item.image_url) + '" alt="' + escapeHtml(item.title) + '">' +
        (item.discount_pct > 0 ? '<span class="bag-item-discount-tag">' + Number(item.discount_pct) + '% OFF</span>' : '') + '</div>' +
        '<div class="bag-item-info"><div class="bag-item-title">' + escapeHtml(item.title) + '</div>' +
        '<div class="bag-item-sku">SKU: ' + escapeHtml(item.sku) + '</div>' +
        '<div class="bag-item-pricing"><span class="bag-item-price">' + money(item.unit_price) + '</span>' +
        (item.discount_pct > 0 ? '<span class="bag-item-orig-price">' + money(item.original_price) + '</span>' : '') + '</div>' +
        '<div class="bag-item-meta">' + escapeHtml(item.category) + ' · ' + Number(item.stock) + ' available</div>' +
        '<div class="bag-item-controls"><div class="qty-spinner">' +
        '<button type="button" class="qty-btn" data-cart-action="decrease" data-product-id="' + item.id + '" aria-label="Decrease quantity">−</button>' +
        '<span class="qty-value">' + item.quantity + '</span>' +
        '<button type="button" class="qty-btn" data-cart-action="increase" data-product-id="' + item.id + '" data-stock="' + item.stock + '" aria-label="Increase quantity">+</button>' +
        '</div><strong>' + money(item.line_total) + '</strong>' +
        '<button type="button" class="bag-item-delete" data-cart-action="remove" data-product-id="' + item.id + '" aria-label="Remove item"><i class="fa-regular fa-trash-can"></i></button></div></div></article>';
    }).join('');

    const isEmpty = items.length === 0;
    emptyView.style.display = isEmpty ? 'block' : 'none';
    list.style.display = isEmpty ? 'none' : 'flex';
    const crossSell = document.querySelector('.last-minute-section');
    if (crossSell) crossSell.style.display = isEmpty ? 'none' : 'block';
    proceed.style.pointerEvents = isEmpty ? 'none' : 'auto';
    proceed.style.opacity = isEmpty ? '0.5' : '1';
    proceed.href = isEmpty ? 'shop.php' : 'checkout.php';

    const cartSubtotal = Number(summary.subtotal || 0);
    const cartDiscount = Number(summary.discount || 0);
    const cartShipping = Number(summary.shipping || 0);
    const cartTax = Number(summary.tax ?? Math.round(Math.max(0, cartSubtotal - cartDiscount) * 0.05 * 100) / 100);
    const cartTotal = Number(summary.total ?? Math.round((cartSubtotal - cartDiscount + cartTax + cartShipping) * 100) / 100);
    document.getElementById('cart-subtotal-val').textContent = money(cartSubtotal);
    document.getElementById('cart-shipping-val').textContent = cartShipping > 0 ? money(cartShipping) : 'Free';
    document.getElementById('cart-tax-val').textContent = money(cartTax);
    document.getElementById('cart-total-val').textContent = money(cartTotal);
    const discountRow = document.getElementById('cart-discount-row');
    discountRow.style.display = cartDiscount > 0 ? 'flex' : 'none';
    document.getElementById('cart-discount-label').textContent = summary.coupon_code ? 'Discount (' + summary.coupon_code + ')' : 'Discount';
    document.getElementById('cart-discount-val').textContent = '- ' + money(cartDiscount);
    if (couponInput && document.activeElement !== couponInput) couponInput.value = summary.coupon_code || '';
    if (couponButton) couponButton.textContent = summary.coupon_code ? 'Applied' : 'Apply';
  }

  async function load() {
    const response = await fetch('cart_api.php?action=get_cart', { credentials: 'same-origin' });
    const data = await response.json();
    if (!data.success) throw new Error(data.message || 'Unable to load your cart.');
    render(data);
  }

  async function removeSelectedFromCart(successMessage) {
    if (!selectedProductId) return;
    const productId = selectedProductId;
    setBusy(true);
    try {
      const data = await request('remove', { product_id: productId });
      render(data);
      closeRemoveDialog();
      showToast(successMessage || 'This item has been removed from your shopping bag.');
    } catch (error) {
      setBusy(false);
      showFeedback(error.message, false);
    }
  }

  async function moveSelectedToWishlist() {
    if (!selectedProductId) return;
    const productId = selectedProductId;
    setBusy(true);
    resetFeedback();
    try {
      const status = await accountRequest('wishlist_status', { product_id: productId }, 'GET');
      let wasAlreadySaved = Boolean(status.saved);
      if (!wasAlreadySaved) {
        await accountRequest('wishlist_toggle', { product_id: productId }, 'POST');
      }
      const cartData = await request('remove', { product_id: productId });
      render(cartData);
      await refreshWishlistBadge();
      window.dispatchEvent(new Event('kinz:wishlist-updated'));
      closeRemoveDialog();
      showToast(wasAlreadySaved
        ? 'This item was already in your wishlist and has been removed from your cart.'
        : 'Item moved to your wishlist successfully.');
    } catch (error) {
      setBusy(false);
      showFeedback(error.message, Boolean(error.signInRequired));
    }
  }


  if (couponButton && couponInput && couponMessage) {
    couponButton.addEventListener('click', async function () {
      const code = couponInput.value.trim();
      if (!code) {
        couponMessage.textContent = 'Enter a discount code.';
        couponMessage.className = 'cart-coupon-message is-error';
        return;
      }
      couponButton.disabled = true;
      couponButton.textContent = 'Applying...';
      couponMessage.textContent = '';
      couponMessage.className = 'cart-coupon-message';
      try {
        const data = await request('apply_coupon', { code: code });
        render(data);
        couponMessage.textContent = data.message || 'Coupon applied successfully.';
        couponMessage.className = 'cart-coupon-message is-success';
      } catch (error) {
        couponMessage.textContent = error.message;
        couponMessage.className = 'cart-coupon-message is-error';
      } finally {
        couponButton.disabled = false;
        couponButton.textContent = couponInput.value.trim() ? 'Apply' : 'Apply';
      }
    });
    couponInput.addEventListener('keydown', function (event) {
      if (event.key === 'Enter') { event.preventDefault(); couponButton.click(); }
    });
  }

  list.addEventListener('click', async function (event) {
    const button = event.target.closest('[data-cart-action]');
    if (!button) return;
    const action = button.dataset.cartAction;
    const productId = button.dataset.productId;
    try {
      if (action === 'remove') {
        openRemoveDialog(productId, button);
        return;
      }
      const card = button.closest('.bag-item-card');
      const current = Number(card.querySelector('.qty-value').textContent);
      const next = action === 'increase' ? current + 1 : current - 1;
      if (next < 1) return;
      render(await request('update', { product_id: productId, quantity: next }));
    } catch (error) {
      showToast(error.message, 'error');
    }
  });

  document.getElementById('cart-remove-close').addEventListener('click', closeRemoveDialog);
  document.getElementById('cart-remove-cancel').addEventListener('click', closeRemoveDialog);
  deleteButton.addEventListener('click', function () { removeSelectedFromCart('Item removed from your shopping bag.'); });
  moveButton.addEventListener('click', moveSelectedToWishlist);
  modal.addEventListener('click', function (event) {
    if (event.target === modal) closeRemoveDialog();
  });
  document.addEventListener('keydown', function (event) {
    if (event.key === 'Escape' && modal.classList.contains('is-open')) closeRemoveDialog();
  });

  load().catch(function (error) { showToast(error.message, 'error'); });
})();
