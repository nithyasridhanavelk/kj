(function () {
  const token = document.querySelector('meta[name="kinz-csrf"]')?.content || '';
  let checkoutData = null;
  let selectedAddressId = null;
  let activeShipping = 'standard';

  const el = function (id) { return document.getElementById(id); };
  const modal = el('payment-modal');
  const addressModal = el('add-address-modal');

  function money(value) {
    return '₹ ' + Number(value || 0).toLocaleString('en-IN', { minimumFractionDigits: 0, maximumFractionDigits: 2 });
  }

  function escapeHtml(value) {
    return String(value ?? '').replace(/[&<>"']/g, function (char) {
      return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' })[char];
    });
  }

  async function request(action, values) {
    const body = new URLSearchParams(Object.assign({ action: action }, values || {}));
    const response = await fetch('cart_api.php', {
      method: 'POST', credentials: 'same-origin', body: body,
      headers: { 'X-CSRF-Token': token }
    });
    const data = await response.json();
    if (!response.ok || !data.success) throw new Error(data.message || 'Unable to continue.');
    return data;
  }

  function showModal(target) {
    target.classList.add('show');
    target.setAttribute('aria-hidden', 'false');
  }

  function hideModal(target) {
    target.classList.remove('show');
    target.setAttribute('aria-hidden', 'true');
  }

  function getShippingAmount() {
    return activeShipping === 'express' ? 150 : 0;
  }

  function renderCouponStates(appliedCode) {
    const currentCode = String(appliedCode || '').trim().toUpperCase();
    document.querySelectorAll('[data-coupon-card]').forEach(function (card) {
      const code = String(card.dataset.couponCode || '').trim().toUpperCase();
      const eligible = card.dataset.couponEligible === '1';
      const isApplied = currentCode !== '' && code === currentCode;
      const status = card.querySelector('.available-coupon-status');
      const button = card.querySelector('.available-coupon-use');

      if (status) {
        status.textContent = isApplied ? 'Applied to this order.' : (eligible ? 'Available for this order.' : 'Not eligible');
        status.classList.toggle('available-coupon-applied', isApplied);
      }
      if (button) {
        button.textContent = isApplied ? 'Applied' : (eligible ? 'Use coupon' : 'Not eligible');
        button.disabled = isApplied || !eligible;
      }
    });

    const removeButton = el('btn-remove-coupon');
    if (removeButton) removeButton.hidden = currentCode === '';
  }

  function renderTotals(summary, count) {
    const shipping = getShippingAmount();
    const total = Number(summary.total || 0) + shipping;
    el('summary-items-count').textContent = String(count);
    el('summary-subtotal-count').textContent = String(count);
    el('summary-subtotal-val').textContent = money(summary.subtotal);
    el('summary-discount-row').style.display = Number(summary.discount) > 0 ? 'flex' : 'none';
    el('summary-discount-val').textContent = '- ' + money(summary.discount);
    el('summary-shipping-val').textContent = shipping > 0 ? money(shipping) : 'Free';
    el('summary-tax-val').textContent = money(summary.tax);
    el('summary-total-val').textContent = money(total);
    el('summary-pay-btn-val').textContent = money(total);
    el('modal-pay-total').textContent = money(total);
    el('btn-pay-amount').textContent = money(total);
    el('checkout-coupon-code').value = summary.coupon_code || '';
    renderCouponStates(summary.coupon_code || '');
    if (window.KINZSetBadge) window.KINZSetBadge('.cart-badge-count', count);
    document.querySelectorAll('.cart-badge-count').forEach(function (badge) {
      badge.textContent = String(count);
      badge.style.display = count > 0 ? 'inline-flex' : 'none';
    });
  }

  function renderItems(items) {
    el('checkout-summary-items').innerHTML = items.map(function (item) {
      return '<div class="summary-item-row">' +
        '<img src="' + escapeHtml(item.image_url) + '" alt="' + escapeHtml(item.title) + '" class="summary-item-img">' +
        '<div class="summary-item-info"><div class="summary-item-title">' + escapeHtml(item.title) + '</div>' +
        '<div class="summary-item-meta">SKU: ' + escapeHtml(item.sku) + ' · Qty: ' + item.quantity + '</div></div>' +
        '<div class="summary-item-price">' + money(item.line_total) + '</div></div>';
    }).join('');
  }

  function renderAddresses(addresses) {
    const container = el('checkout-address-list');
    if (!addresses.length) {
      container.innerHTML = '<div style="padding:16px;border:1px dashed var(--border-light);border-radius:var(--radius-sm);color:var(--text-muted);">No delivery address saved yet. Select <strong>Add New Address</strong> to continue.</div>';
      selectedAddressId = null;
      return;
    }
    if (!selectedAddressId || !addresses.some(function (address) { return Number(address.id) === Number(selectedAddressId); })) {
      const defaultAddress = addresses.find(function (address) { return Number(address.is_default) === 1; });
      selectedAddressId = Number((defaultAddress || addresses[0]).id);
    }
    container.innerHTML = addresses.map(function (address) {
      const selected = Number(address.id) === Number(selectedAddressId);
      return '<label class="address-radio-card' + (selected ? ' selected' : '') + '">' +
        '<input type="radio" name="checkout_address" value="' + address.id + '" ' + (selected ? 'checked' : '') + '>' +
        '<div class="address-details-body"><div class="address-tag-header"><span class="address-tag-name">' + escapeHtml(address.address_tag) + '</span>' +
        (Number(address.is_default) === 1 ? '<span class="badge-default-addr">DEFAULT</span>' : '') + '</div>' +
        '<div class="address-user-name">' + escapeHtml(address.recipient_name) + ' · ' + escapeHtml(address.phone) + '</div>' +
        '<div class="address-text">' + escapeHtml(address.address_line) + ', ' + escapeHtml(address.city) + ' - ' + escapeHtml(address.pincode) + '</div></div></label>';
    }).join('');
  }

  function render(data) {
    checkoutData = data;
    renderItems(data.items || []);
    renderAddresses(data.addresses || []);
    renderTotals(data.summary || {}, Number(data.count || 0));
    if (!data.items || !data.items.length) {
      alert('Your shopping bag is empty. Add a product before checkout.');
      window.location.href = 'cart.php';
      return;
    }
    if ((!data.addresses || !data.addresses.length) && addressModal) showModal(addressModal);
  }

  async function loadCheckout() {
    const response = await fetch('cart_api.php?action=checkout_data', { credentials: 'same-origin' });
    const data = await response.json();
    if (response.status === 401) {
      window.location.href = 'login.php';
      return;
    }
    if (!response.ok || !data.success) throw new Error(data.message || 'Unable to load checkout.');
    render(data);
  }

  el('checkout-address-list').addEventListener('change', function (event) {
    if (event.target.name === 'checkout_address') {
      selectedAddressId = Number(event.target.value);
      renderAddresses(checkoutData.addresses || []);
    }
  });

  el('btn-open-add-addr').addEventListener('click', function () { showModal(addressModal); });
  el('btn-close-add-addr').addEventListener('click', function () { hideModal(addressModal); });
  el('btn-close-payment').addEventListener('click', function () { hideModal(modal); });
  el('btn-summary-change-pay').addEventListener('click', function () { showModal(modal); });

  addressModal.addEventListener('click', function (event) {
    if (event.target === addressModal) hideModal(addressModal);
  });
  modal.addEventListener('click', function (event) {
    if (event.target === modal) hideModal(modal);
  });

  el('new-address-form').addEventListener('submit', async function (event) {
    event.preventDefault();
    const values = {
      tag: el('addr-tag').value.trim(),
      name: el('addr-name').value.trim(),
      line: el('addr-line').value.trim(),
      city: el('addr-city').value.trim(),
      pincode: el('addr-pincode').value.trim(),
      phone: el('addr-phone').value.trim()
    };
    try {
      const result = await request('save_address', values);
      selectedAddressId = Number(result.address_id);
      hideModal(addressModal);
      el('new-address-form').reset();
      await loadCheckout();
      selectedAddressId = Number(result.address_id);
      renderAddresses(checkoutData.addresses || []);
      alert(result.message);
    } catch (error) {
      alert(error.message);
    }
  });

  document.querySelectorAll('input[name="shipping_method"]').forEach(function (radio) {
    radio.addEventListener('change', function () {
      activeShipping = radio.value;
      el('ship-opt-std').classList.toggle('selected', activeShipping === 'standard');
      el('ship-opt-exp').classList.toggle('selected', activeShipping === 'express');
      if (checkoutData) renderTotals(checkoutData.summary || {}, Number(checkoutData.count || 0));
    });
  });
  const standard = document.querySelector('input[name="shipping_method"][value="standard"]');
  if (standard) standard.checked = true;
  el('ship-opt-std').classList.add('selected');

  el('btn-apply-coupon').addEventListener('click', async function () {
    const code = el('checkout-coupon-code').value.trim();
    try {
      const data = await request('apply_coupon', { code: code });
      checkoutData = Object.assign(checkoutData || {}, data);
      renderTotals(data.summary || {}, Number(data.count || 0));
      alert(data.message);
    } catch (error) {
      alert(error.message);
    }
  });

  const removeCouponButton = el('btn-remove-coupon');
  if (removeCouponButton) {
    removeCouponButton.addEventListener('click', async function () {
      removeCouponButton.disabled = true;
      try {
        const data = await request('clear_coupon');
        checkoutData = Object.assign(checkoutData || {}, data);
        renderTotals(data.summary || {}, Number(data.count || 0));
        alert(data.message || 'Coupon removed.');
      } catch (error) {
        alert(error.message);
      } finally {
        removeCouponButton.disabled = false;
      }
    });
  }

  const availableCoupons = el('available-coupons');
  if (availableCoupons) {
    availableCoupons.addEventListener('click', function (event) {
      const useButton = event.target.closest('[data-coupon-code]');
      if (!useButton) return;
      el('checkout-coupon-code').value = useButton.dataset.couponCode || '';
      el('btn-apply-coupon').click();
    });
  }

  document.querySelectorAll('input[name="pay_method"]').forEach(function (radio) {
    radio.addEventListener('change', function () {
      el('summary-pay-method-label').textContent = radio.value;
      document.querySelectorAll('.payment-option-item').forEach(function (item) {
        item.classList.toggle('selected', item.contains(radio));
      });
      document.querySelectorAll('.payment-option-details').forEach(function (details) { details.style.display = 'none'; });
      const details = radio.closest('.payment-option-item')?.querySelector('.payment-option-details');
      if (details) details.style.display = 'block';
    });
  });

  el('btn-open-payment').addEventListener('click', function () {
    if (!checkoutData || !checkoutData.items || !checkoutData.items.length) {
      alert('Your shopping bag is empty.');
      return;
    }
    if (!selectedAddressId) {
      alert('Please add or select a delivery address.');
      showModal(addressModal);
      return;
    }
    showModal(modal);
  });

  let pendingOrderCode = '';

  el('btn-submit-payment').addEventListener('click', async function () {
    const selected = document.querySelector('input[name="pay_method"]:checked');
    if (!selected) {
      alert('Please select a payment method.');
      return;
    }
    const button = el('btn-submit-payment');
    const originalHtml = button.innerHTML;
    button.disabled = true;
    button.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Starting Payment...';
    try {
      if (!pendingOrderCode) {
        const result = await request('place_order', {
          address_id: String(selectedAddressId || ''),
          shipping_method: activeShipping,
          payment_method: selected.value
        });
        pendingOrderCode = result.order_code;
      }
      const payment = await window.KINZPayOrder(pendingOrderCode);
      window.location.href = payment.paid ? payment.redirect : 'order-confirmation.php?order_code=' + encodeURIComponent(pendingOrderCode);
    } catch (error) {
      alert(error.message);
      button.disabled = false;
      button.innerHTML = originalHtml;
    }
  });

  loadCheckout().catch(function (error) { alert(error.message); });
})();
