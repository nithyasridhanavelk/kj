/* Payment-method UI behaviour from the Node.js reference checkout (UI only).
   Backend order saving stays in checkout.js / cart_api.php (only the chosen method is saved). */
(function () {
  'use strict';
  function init() {
    var items = Array.prototype.slice.call(document.querySelectorAll('.payment-option-item'));
    if (!items.length) return;

    var meta = {
      UPI: { label: 'UPI (Instant Verification)', icon: 'fa-mobile-screen-button', color: '#27824b' },
      Card: { label: 'Credit / Debit Card (Visa/RuPay)', icon: 'fa-credit-card', color: '#1a42b8' },
      NetBanking: { label: 'Net Banking (Online Banking)', icon: 'fa-building-columns', color: '#b84f1a' },
      Razorpay: { label: 'Razorpay (UPI, Card, Net Banking)', icon: 'fa-shield-halved', color: '#1a42b8' }
    };

    // Start with nothing selected (browser may restore form state)
    items.forEach(function (item) {
      var r = item.querySelector('input[name="pay_method"]'); if (r) r.checked = false;
      item.classList.remove('active', 'selected');
    });

    function select(item) {
      var radio = item.querySelector('input[name="pay_method"]');
      if (!radio) return;
      var wasChecked = radio.checked;
      radio.checked = true;
      items.forEach(function (i) { i.classList.toggle('active', i === item); });
      var m = meta[item.getAttribute('data-method')];
      var labelEl = document.getElementById('summary-pay-method-label');
      var iconEl = document.getElementById('summary-pay-icon');
      if (m && iconEl) { iconEl.innerHTML = '<i class="fa-solid ' + m.icon + '"></i>'; iconEl.style.color = m.color; }
      if (!wasChecked) radio.dispatchEvent(new Event('change', { bubbles: true }));
      else {
        document.querySelectorAll('.payment-option-details').forEach(function (d) { d.style.display = 'none'; });
        var d = item.querySelector('.payment-option-details'); if (d) d.style.display = 'block';
      }
      if (labelEl && m) labelEl.textContent = m.label;
    }

    items.forEach(function (item) {
      item.addEventListener('click', function (e) {
        if (e.target.closest('.payment-option-details')) return; // typing inside the form must not re-toggle
        select(item);
      });
    });

    if (items.length === 1) select(items[0]);

    // UPI ID format check (not a bank verification)
    var upiInput = document.getElementById('upi-id-input');
    var verify = document.getElementById('btn-verify-upi');
    var status = document.getElementById('upi-id-status');
    if (upiInput && verify && status) {
      verify.addEventListener('click', function () {
        var ok = /^[A-Za-z0-9._-]{2,}@[A-Za-z0-9.-]{2,}$/.test(upiInput.value.trim());
        status.textContent = ok ? 'UPI ID format looks valid. Actual payment verification requires a payment provider.' : 'Enter a valid UPI ID, for example name@bank.';
        status.classList.toggle('is-valid', ok); status.classList.toggle('is-invalid', !ok);
        upiInput.setAttribute('aria-invalid', ok ? 'false' : 'true');
      });
      upiInput.addEventListener('input', function () {
        status.textContent = ''; status.classList.remove('is-valid', 'is-invalid'); upiInput.removeAttribute('aria-invalid');
      });
    }

    // UPI app highlight
    document.querySelectorAll('input[name="upi_app"]').forEach(function (radio) {
      radio.checked = false;
      radio.addEventListener('change', function () {
        document.querySelectorAll('.upi-app-option').forEach(function (o) { o.classList.remove('selected'); });
        var o = radio.closest('.upi-app-option'); if (o) o.classList.add('selected');
      });
    });

    // Live luxury card preview
    var num = document.getElementById('card-num-input'), nm = document.getElementById('card-name-input'), ex = document.getElementById('card-exp-input');
    var vNum = document.getElementById('visual-card-number'), vNm = document.getElementById('visual-card-name'), vEx = document.getElementById('visual-card-expiry');
    if (num && vNum) num.addEventListener('input', function (e) {
      var v = e.target.value.replace(/\D/g, '').substring(0, 16);
      var f = (v.match(/.{1,4}/g) || []).join(' ');
      e.target.value = f; vNum.textContent = f || '4532 •••• •••• 9812';
    });
    if (nm && vNm) nm.addEventListener('input', function (e) { vNm.textContent = e.target.value.trim().toUpperCase(); });
    if (ex && vEx) ex.addEventListener('input', function (e) {
      var v = e.target.value.replace(/\D/g, '').substring(0, 4);
      if (v.length >= 2) v = v.substring(0, 2) + '/' + v.substring(2);
      e.target.value = v; vEx.textContent = v || '10/29';
    });
  }
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init, { once: true });
  else init();
})();
