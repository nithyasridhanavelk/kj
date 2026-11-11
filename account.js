(function () {
  const API = 'account_api.php';
  let accountData = null;
  let csrfToken = '';
  const $ = (selector, root = document) => root.querySelector(selector);
  const $$ = (selector, root = document) => Array.from(root.querySelectorAll(selector));

  function toast(message, type = 'success') {
    if (window.KINZToast) { window.KINZToast.show(message, type === 'error' ? 'error' : ''); return; }
    let node = $('#kinz-account-toast');
    if (!node) {
      node = document.createElement('div');
      node.id = 'kinz-account-toast';
      node.style.cssText = 'position:fixed;right:22px;bottom:22px;z-index:99999;max-width:360px;padding:14px 18px;border-radius:10px;background:#123d32;color:#fff;box-shadow:0 8px 28px rgba(0,0,0,.2);font-size:.9rem;opacity:0;transform:translateY(10px);transition:.2s ease';
      document.body.appendChild(node);
    }
    node.textContent = message;
    node.style.background = type === 'error' ? '#8e2f2a' : '#123d32';
    node.style.opacity = '1';
    node.style.transform = 'translateY(0)';
    clearTimeout(node._timer);
    node._timer = setTimeout(() => { node.style.opacity = '0'; node.style.transform = 'translateY(10px)'; }, 3200);
  }

  async function api(action, values = null, method = 'GET') {
    let url = API + '?action=' + encodeURIComponent(action);
    const options = { method, credentials: 'same-origin', headers: { 'Accept': 'application/json' } };
    if (method === 'POST') {
      const body = new URLSearchParams(values || {});
      body.set('action', action);
      body.set('csrf_token', csrfToken);
      options.body = body;
      options.headers['X-CSRF-Token'] = csrfToken;
      options.headers['Content-Type'] = 'application/x-www-form-urlencoded;charset=UTF-8';
    } else if (values) {
      const query = new URLSearchParams(values);
      url += '&' + query.toString();
    }
    const response = await fetch(url, options);
    let data;
    try { data = await response.json(); } catch (_) { throw new Error('The server returned an invalid response.'); }
    if (response.status === 401) {
      window.location.href = 'login.php';
      throw new Error('Please sign in to continue.');
    }
    if (!response.ok || !data.success) throw new Error(data.message || 'Something went wrong.');
    return data;
  }

  function escapeText(value) {
    return String(value == null ? '' : value);
  }

  async function markNotificationsSeen() {
    if (!accountData || !accountData.notifications || !accountData.notifications.some(note => Number(note.is_read) === 0)) return;
    try {
      await api('mark_all_notifications_read', {}, 'POST');
      accountData.notifications.forEach(note => { note.is_read = 1; });
      renderNotifications();
      if (window.KINZCustomerNotificationBadge) window.KINZCustomerNotificationBadge.refresh();
      window.dispatchEvent(new CustomEvent('kinz:notifications-updated'));
    } catch (error) {
      toast(error.message || 'Unable to mark notifications as read.', 'error');
    }
  }

  function switchAccountTab(tab) {
    const valid = ['profile', 'addresses', 'track', 'password', 'notifications', 'wishlist'];
    if (!valid.includes(tab)) tab = 'profile';
    $$('.account-nav-item[data-tab]').forEach(item => item.classList.toggle('active', item.dataset.tab === tab));
    $$('.account-tab-panel').forEach(panel => { panel.style.display = panel.id === 'tab-' + tab ? '' : 'none'; });
    if (history.replaceState) history.replaceState(null, '', '#' + tab);
    if (tab === 'track') renderOrders();
    if (tab === 'notifications') { renderNotifications(); markNotificationsSeen(); }
    if (tab === 'wishlist') renderWishlist();
  }
  window.switchAccountTab = switchAccountTab;

  function fillProfile(profile) {
    $('#prof-fullname').value = profile.name || '';
    $('#prof-email').value = profile.email || '';
    $('#prof-phone').value = profile.phone || '';
    $('#prof-dob').value = profile.date_of_birth || '';
    $('#sidebar-user-name').textContent = profile.name || 'My Account';
    $('#sidebar-user-email').textContent = profile.email || '';
    const initials = String(profile.name || 'K').trim().split(/\s+/).slice(0, 2).map(part => part.charAt(0)).join('').toUpperCase();
    $('#sidebar-user-avatar').textContent = initials || 'K';
  }

  function renderAddresses() {
    const root = $('#account-address-list');
    if (!root) return;
    root.replaceChildren();
    if (!accountData.addresses.length) {
      const empty = document.createElement('div');
      empty.className = 'account-empty-state';
      empty.textContent = 'You have not saved any delivery addresses yet. Select Add Address to save one.';
      root.appendChild(empty);
      return;
    }
    accountData.addresses.forEach(address => {
      const card = document.createElement('div');
      card.className = 'account-address-card';
      const top = document.createElement('div');
      top.className = 'account-address-top';
      const label = document.createElement('div');
      label.className = 'account-address-label';
      label.textContent = address.address_tag || 'Address';
      top.appendChild(label);
      if (Number(address.is_default) === 1) {
        const tag = document.createElement('span');
        tag.className = 'account-default-tag';
        tag.textContent = 'Default';
        top.appendChild(tag);
      }
      const details = document.createElement('div');
      details.className = 'account-address-details';
      details.textContent = [address.recipient_name, address.address_line, address.city, address.pincode, address.phone].filter(Boolean).join(' · ');
      const actions = document.createElement('div');
      actions.className = 'account-address-actions';
      const edit = makeButton('Edit', 'account-small-btn', () => openAddressDialog(address));
      const remove = makeButton('Delete', 'account-small-btn account-delete-btn', async () => {
        if (!confirm('Delete this saved address?')) return;
        try { await api('delete_address', { id: address.id }, 'POST'); await refreshData(); toast('Address deleted.'); }
        catch (error) { toast(error.message, 'error'); }
      });
      actions.append(edit, remove);
      if (Number(address.is_default) !== 1) {
        const setDefault = makeButton('Set as Default', 'account-small-btn', async () => {
          try { await api('set_default_address', { id: address.id }, 'POST'); await refreshData(); toast('Default address updated.'); }
          catch (error) { toast(error.message, 'error'); }
        });
        actions.appendChild(setDefault);
      }
      card.append(top, details, actions);
      root.appendChild(card);
    });
  }

  function makeButton(label, className, callback) {
    const button = document.createElement('button');
    button.type = 'button';
    button.className = className;
    button.textContent = label;
    button.addEventListener('click', callback);
    return button;
  }

  function openAddressDialog(address = null) {
    let dialog = $('#account-address-dialog');
    if (!dialog) {
      dialog = document.createElement('dialog');
      dialog.id = 'account-address-dialog';
      dialog.className = 'account-dialog';
      dialog.innerHTML = `
        <form id="account-address-form" method="dialog" class="account-dialog-card">
          <div class="account-dialog-heading"><div><h3 id="account-address-dialog-title">Add Address</h3><p>Save your delivery details</p></div><button type="button" class="account-dialog-close" aria-label="Close">×</button></div>
          <input type="hidden" name="id" value="">
          <div class="account-dialog-grid">
            <label>Address Type<select name="address_tag"><option>Home</option><option>Work</option><option>Other</option></select></label>
            <label>Recipient Name<input name="recipient_name" required maxlength="100"></label>
            <label class="full">Address<input name="address_line" required></label>
            <label>City<input name="city" required maxlength="100"></label>
            <label>PIN Code<input name="pincode" required maxlength="12"></label>
            <label>Phone Number<input name="phone" required maxlength="30"></label>
            <label class="account-checkbox-label"><input type="checkbox" name="is_default" value="1"> Set as default address</label>
          </div>
          <div class="account-dialog-actions"><button type="button" class="btn-cancel-profile account-dialog-cancel">Cancel</button><button type="submit" class="btn-save-profile">Save Address</button></div>
        </form>`;
      document.body.appendChild(dialog);
      $('.account-dialog-close', dialog).addEventListener('click', () => dialog.close());
      $('.account-dialog-cancel', dialog).addEventListener('click', () => dialog.close());
      $('#account-address-form', dialog).addEventListener('submit', async event => {
        event.preventDefault();
        const form = event.currentTarget;
        const values = Object.fromEntries(new FormData(form).entries());
        values.is_default = form.elements.is_default.checked ? '1' : '0';
        try {
          await api('save_address', values, 'POST');
          dialog.close();
          await refreshData();
          toast('Address saved successfully.');
        } catch (error) { toast(error.message, 'error'); }
      });
    }
    const form = $('#account-address-form', dialog);
    form.reset();
    form.elements.id.value = address ? address.id : '';
    form.elements.address_tag.value = address ? address.address_tag : 'Home';
    form.elements.recipient_name.value = address ? address.recipient_name : '';
    form.elements.address_line.value = address ? address.address_line : '';
    form.elements.city.value = address ? address.city : '';
    form.elements.pincode.value = address ? address.pincode : '';
    form.elements.phone.value = address ? address.phone : (accountData.profile.phone || '');
    form.elements.is_default.checked = address ? Number(address.is_default) === 1 : accountData.addresses.length === 0;
    $('#account-address-dialog-title', dialog).textContent = address ? 'Edit Address' : 'Add Address';
    if (typeof dialog.showModal === 'function') dialog.showModal(); else dialog.setAttribute('open', 'open');
  }

  function trackingStepIndex(status) {
    const value = String(status || 'Pending').toLowerCase().replace(/[^a-z]/g, '');
    if (value === 'confirmed') return 0;
    if (value === 'processing') return 1;
    if (value === 'shipped' || value === 'shipping') return 2;
    if (value === 'outfordelivery') return 3;
    if (value === 'delivered') return 4;
    return -1;
  }

  function appendTrackingTimeline(parent, order) {
    const status = String(order.status || 'Pending');
    const normalized = status.toLowerCase().replace(/[^a-z]/g, '');
    if (normalized === 'cancelled') {
      const cancelled = document.createElement('div');
      cancelled.className = 'kinz-tracking-cancelled';
      cancelled.innerHTML = '<i class="fa-solid fa-circle-xmark" aria-hidden="true"></i> This order has been cancelled.';
      parent.appendChild(cancelled);
      return;
    }

    const steps = [
      { label: 'Order Confirmed', icon: 'fa-clipboard-check' },
      { label: 'Processing', icon: 'fa-box-open' },
      { label: 'Shipping', icon: 'fa-truck-fast' },
      { label: 'Out for Delivery', icon: 'fa-motorcycle' },
      { label: 'Delivered', icon: 'fa-house-circle-check' }
    ];
    const activeIndex = trackingStepIndex(status);
    const wrap = document.createElement('div');
    wrap.className = 'kinz-tracking-wrap';
    const timeline = document.createElement('div');
    timeline.className = 'kinz-tracking-timeline';
    timeline.setAttribute('aria-label', 'Order delivery progress');

    const track = document.createElement('div');
    track.className = 'kinz-tracking-track';
    const fill = document.createElement('div');
    fill.className = 'kinz-tracking-fill';
    fill.style.width = activeIndex < 0 ? '0%' : ((activeIndex / (steps.length - 1)) * 80) + '%';
    track.appendChild(fill);
    timeline.appendChild(track);

    steps.forEach((step, index) => {
      const item = document.createElement('div');
      item.className = 'kinz-tracking-step';
      if (activeIndex >= 0 && index < activeIndex) item.classList.add('is-complete');
      if (activeIndex === index) item.classList.add('is-current');
      const dot = document.createElement('div');
      dot.className = 'kinz-tracking-dot';
      const icon = document.createElement('i');
      icon.className = 'fa-solid ' + step.icon;
      icon.setAttribute('aria-hidden', 'true');
      dot.appendChild(icon);
      const label = document.createElement('span');
      label.className = 'kinz-tracking-label';
      label.textContent = step.label;
      item.append(dot, label);
      if (activeIndex === index) item.setAttribute('aria-current', 'step');
      timeline.appendChild(item);
    });
    wrap.appendChild(timeline);

    const note = document.createElement('div');
    note.className = 'kinz-tracking-state-note';
    if (activeIndex < 0) {
      note.textContent = 'Your order has been received and is waiting for confirmation from the KINZ team.';
    } else {
      note.textContent = activeIndex === 4
        ? 'Your order has been delivered. Thank you for choosing KINZ.'
        : 'Current stage: ' + steps[activeIndex].label + '. We will update this timeline as your order moves forward.';
    }
    wrap.appendChild(note);
    parent.appendChild(wrap);
  }

  function renderOrders() {
    const root = $('#account-track-list');
    if (!root) return;
    root.replaceChildren();
    if (!accountData.orders.length) {
      const empty = document.createElement('div');
      empty.className = 'account-empty-state';
      empty.textContent = 'You do not have any orders to track yet.';
      root.appendChild(empty);
      return;
    }
    accountData.orders.forEach(order => {
      const card = document.createElement('div');
      card.className = 'account-track-card';
      const header = document.createElement('div');
      header.className = 'account-track-header';
      const info = document.createElement('div');
      const code = document.createElement('strong');
      code.textContent = 'Order #' + order.order_code;
      const date = document.createElement('span');
      date.textContent = order.order_date ? new Date(String(order.order_date).replace(' ', 'T')).toLocaleString() : 'Date unavailable';
      info.append(code, date);
      const status = document.createElement('span');
      status.className = 'account-status-pill status-' + String(order.status || 'pending').toLowerCase().replace(/[^a-z]/g, '');
      status.textContent = order.status || 'Pending';
      header.append(info, status);
      const meta = document.createElement('div');
      meta.className = 'account-track-meta';
      meta.textContent = 'Order total: ₹' + Number(order.total_amount || 0).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' · ' + Number(order.items_count || 0) + ' item(s)';
      const timeline = document.createElement('div');
      appendTrackingTimeline(timeline, order);
      const tracking = document.createElement('div');
      tracking.className = 'account-track-number';
      const normalized = String(order.status || '').toLowerCase().replace(/[^a-z]/g, '');
      tracking.textContent = order.tracking_number
        ? 'Courier tracking number: ' + order.tracking_number
        : (['shipped', 'shipping', 'outfordelivery'].includes(normalized) ? 'Courier tracking number will be added shortly.' : 'Courier tracking information will appear once your order is dispatched.');
      const action = document.createElement('a');
      action.href = 'my-orders.php';
      action.className = 'account-track-link';
      action.textContent = 'View full order details';
      card.append(header, meta, timeline, tracking, action);
      root.appendChild(card);
    });
  }

  function renderNotifications() {
    const root = $('#account-notification-list');
    if (!root) return;

    root.replaceChildren();
    const notifications = accountData && Array.isArray(accountData.notifications)
      ? accountData.notifications
      : [];

    if (!notifications.length) {
      const empty = document.createElement('div');
      empty.className = 'account-empty-state';
      empty.textContent = 'You are all caught up. New order and account updates will appear here.';
      root.appendChild(empty);
      return;
    }

    notifications.forEach(note => {
      const card = document.createElement('div');
      card.className = 'account-notification-card' + (Number(note.is_read) === 0 ? ' unread' : '');
      card.style.cssText = 'display:flex;align-items:flex-start;gap:13px;flex-wrap:wrap;';

      const icon = document.createElement('i');
      icon.className = 'fa-solid ' + (
        note.notification_type === 'order_status' || note.notification_type === 'current_order_status'
          ? 'fa-truck'
          : 'fa-bell'
      );
      icon.setAttribute('aria-hidden', 'true');

      const body = document.createElement('div');
      body.className = 'account-notification-body';
      body.style.cssText = 'flex:1 1 220px;min-width:180px;';

      const title = document.createElement('strong');
      title.textContent = note.title || 'Account notification';

      const message = document.createElement('p');
      message.textContent = note.message || '';

      const date = document.createElement('span');
      date.textContent = note.created_at
        ? new Date(String(note.created_at).replace(' ', 'T')).toLocaleString()
        : '';

      body.append(title, message, date);

      const actions = document.createElement('div');
      actions.className = 'account-notification-actions';
      actions.style.cssText = 'display:flex!important;visibility:visible!important;opacity:1!important;align-items:center;justify-content:flex-end;gap:8px;flex:0 0 auto;margin-left:auto;position:relative;z-index:2;';

      if (Number(note.is_read) === 0) {
        const read = makeButton('Mark as read', 'account-small-btn', async () => {
          read.disabled = true;
          try {
            await api('mark_notification_read', { id: note.id }, 'POST');
            await refreshData();
            if (window.KINZCustomerNotificationBadge) window.KINZCustomerNotificationBadge.refresh();
            window.dispatchEvent(new CustomEvent('kinz:notifications-updated'));
            toast('Notification marked as read.');
          } catch (error) {
            read.disabled = false;
            toast(error.message || 'Unable to mark notification as read.', 'error');
          }
        });
        read.style.cssText = 'display:inline-flex!important;visibility:visible!important;align-items:center;justify-content:center;white-space:nowrap;';
        actions.appendChild(read);
      }

      const remove = makeButton('Delete', 'account-small-btn account-delete-btn', async () => {
        if (!window.confirm('Delete this notification?')) return;
        remove.disabled = true;
        try {
          await api('delete_notification', { id: note.id }, 'POST');
          await refreshData();
          if (window.KINZCustomerNotificationBadge) window.KINZCustomerNotificationBadge.refresh();
          window.dispatchEvent(new CustomEvent('kinz:notifications-updated'));
          toast('Notification deleted.');
        } catch (error) {
          remove.disabled = false;
          toast(error.message || 'Unable to delete notification.', 'error');
        }
      });
      remove.style.cssText = 'display:inline-flex!important;visibility:visible!important;opacity:1!important;align-items:center;justify-content:center;white-space:nowrap;background:#fff!important;color:#a33430!important;border:1px solid #e5c5c1!important;border-radius:7px;padding:8px 13px;font-size:.8rem;font-weight:700;cursor:pointer;position:relative;z-index:3;';
      remove.setAttribute('aria-label', 'Delete notification: ' + (note.title || 'Account notification'));
      remove.title = 'Delete notification';
      actions.appendChild(remove);

      card.append(icon, body, actions);
      root.appendChild(card);
    });
  }

  function renderWishlist() {
    const root = $('#account-wishlist-grid');
    if (!root) return;
    root.replaceChildren();
    const count = accountData.wishlist.length;
    if (window.KINZSetBadge) window.KINZSetBadge('.wishlist-badge-count', count);
    $$('.wishlist-badge-count').forEach(badge => { badge.textContent = String(count); badge.style.display = count ? 'inline-flex' : 'none'; });
    if (!count) {
      const empty = document.createElement('div');
      empty.className = 'account-empty-state';
      empty.textContent = 'Your wishlist is empty. Save jewellery pieces from a product page and they will appear here.';
      root.appendChild(empty);
      return;
    }
    const inr = n => '₹' + Math.round(Number(n || 0)).toLocaleString('en-IN');
    const esc = v => String(v == null ? '' : v).replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    root.innerHTML = accountData.wishlist.map(product => {
      const id = Number(product.product_id) || 0;
      const discount = Math.max(0, Math.round(Number(product.discount_pct) || 0));
      const reviews = Number(product.review_count) || 0;
      const rating = Number(product.average_rating) || 0;
      const inStock = Number(product.stock) > 0;
      const detail = 'product-details.php?product_id=' + encodeURIComponent(id);
      return '<article class="product-card" data-product-id="' + id + '">' +
        (discount > 0 ? '<div class="hanging-offer-tag"><div class="hanging-offer-tag-body"><span class="tag-percent">' + discount + '%</span><span class="tag-off">OFF</span></div></div>' : '') +
        '<button type="button" class="wishlist-toggle-btn active" data-wishlist-remove="' + id + '" title="Remove from Wishlist" aria-label="Remove ' + esc(product.title) + ' from wishlist"><i class="fa-solid fa-heart"></i></button>' +
        '<div class="product-media-wrapper"><a href="' + detail + '"><img src="' + esc(product.image_url) + '" alt="' + esc(product.title) + '" class="product-image" loading="lazy"></a></div>' +
        '<div class="product-info-block"><a href="' + detail + '"><h3 class="product-title">' + esc(product.title) + '</h3></a>' +
        '<div class="product-pricing-row"><span class="product-sale-price">' + inr(product.price) + '</span>' +
        (discount > 0 ? '<span class="product-original-price">' + inr(product.original_price) + '</span><span class="product-discount-percent" style="color: #27824b; font-weight: 700; font-size: 0.82rem; margin-left: 6px;">' + discount + '% off</span>' : '') + '</div>' +
        '<div class="product-rating-row">' + (reviews > 0
          ? '<span class="rating-star" style="background: linear-gradient(135deg, #147A5C 0%, #0A3D2E 100%); color: #fff; border: 1px solid rgba(223, 193, 104, 0.45); padding: 1px 7px; border-radius: 4px; font-size: 0.75rem; font-weight: 700; display: inline-flex; align-items: center; gap: 3px; box-shadow: 0 2px 4px rgba(10, 61, 46, 0.2);">' + rating.toFixed(1) + ' <i class="fa-solid fa-star" style="color: #dfc168; font-size: 0.65rem;"></i></span><span class="rating-count" style="margin-left: 6px;">(' + (reviews > 999 ? Math.round(reviews / 1000) + 'K' : reviews) + ')</span>'
          : '<span class="rating-no-reviews">No ratings yet</span>') + '</div>' +
        '<div class="product-hover-actions">' + (inStock
          ? '<button type="button" class="btn-card-add" data-card-add-to-cart data-product-id="' + id + '"><i class="fa-solid fa-bag-shopping"></i> Add to Cart</button>'
          : '<a class="btn-card-add" href="' + detail + '">Out of Stock</a>') + '</div>' +
        '</div></article>';
    }).join('');
    root.querySelectorAll('[data-wishlist-remove]').forEach(button => {
      button.addEventListener('click', async () => {
        if (button.disabled) return;
        button.disabled = true;
        try {
          await api('remove_wishlist', { product_id: button.getAttribute('data-wishlist-remove') }, 'POST');
          await refreshData();
          window.dispatchEvent(new Event('kinz:wishlist-updated'));
          toast('Removed from wishlist.');
        } catch (error) { button.disabled = false; toast(error.message, 'error'); }
      });
    });
  }

  async function refreshData() {
    const data = await api('bootstrap');
    accountData = data;
    csrfToken = data.csrf_token;
    fillProfile(data.profile);

    // Render notifications first so a separate order/address panel issue cannot
    // prevent the customer from seeing or deleting their notifications.
    try { renderNotifications(); } catch (error) { console.error('KINZ notifications render failed:', error); }
    try { renderAddresses(); } catch (error) { console.error('KINZ addresses render failed:', error); }
    try { renderOrders(); } catch (error) { console.error('KINZ orders render failed:', error); }
    try { renderWishlist(); } catch (error) { console.error('KINZ wishlist render failed:', error); }

    if (window.KINZCustomerNotificationBadge) window.KINZCustomerNotificationBadge.refresh();
    return data;
  }

  async function initialise() {
    try {
      await refreshData();
      const profileForm = $('#profile-edit-form');
      if (profileForm) {
        profileForm.addEventListener('submit', async event => {
          event.preventDefault();
          try {
            await api('update_profile', {
              name: $('#prof-fullname').value.trim(),
              email: $('#prof-email').value.trim(),
              phone: $('#prof-phone').value.trim(),
              date_of_birth: $('#prof-dob').value
            }, 'POST');
            await refreshData();
            toast('Profile updated successfully.');
          } catch (error) { toast(error.message, 'error'); }
        });
        profileForm.addEventListener('reset', () => setTimeout(() => { if (accountData) fillProfile(accountData.profile); }, 0));
      }
      $('#btn-add-account-addr')?.addEventListener('click', () => openAddressDialog());
      $('#change-password-form')?.addEventListener('submit', async event => {
        event.preventDefault();
        const form = event.currentTarget;
        try {
          await api('change_password', {
            current_password: form.elements.current_password.value,
            new_password: form.elements.new_password.value,
            confirm_password: form.elements.confirm_password.value
          }, 'POST');
          form.reset();
          toast('Password changed successfully.');
        } catch (error) { toast(error.message, 'error'); }
      });
      $$('.account-nav-item[data-tab]').forEach(item => {
        if (item.dataset.tab === 'orders') return;
        item.addEventListener('click', () => switchAccountTab(item.dataset.tab));
      });
      const hashTab = location.hash.replace('#', '').toLowerCase();
      if (['profile', 'addresses', 'track', 'password', 'notifications', 'wishlist'].includes(hashTab)) switchAccountTab(hashTab);
      else switchAccountTab('profile');
    } catch (error) {
      if (error.message !== 'Please sign in to continue.') toast(error.message, 'error');
    }
  }

  window.addEventListener('hashchange', function () {
    const tab = location.hash.replace('#', '').toLowerCase();
    if (['profile', 'addresses', 'track', 'password', 'notifications', 'wishlist'].includes(tab)) switchAccountTab(tab);
  });

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initialise, { once: true });
  } else {
    initialise();
  }
})();
