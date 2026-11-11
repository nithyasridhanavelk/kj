(function () {
  'use strict';

  function initHeroCarousel() {
    var section = document.getElementById('hero-carousel');
    if (!section) return;
    var slides = Array.prototype.slice.call(section.querySelectorAll('.hero-slide'));
    var dots = Array.prototype.slice.call(section.querySelectorAll('.hero-dot'));
    var previous = section.querySelector('#hero-prev-btn');
    var next = section.querySelector('#hero-next-btn');
    if (!slides.length) return;

    var active = slides.findIndex(function (slide) { return slide.classList.contains('active'); });
    if (active < 0) active = 0;
    var timer = null;
    var paused = false;
    var interval = 3000;

    function showSlide(index) {
      active = (index + slides.length) % slides.length;
      slides.forEach(function (slide, position) {
        var selected = position === active;
        slide.classList.toggle('active', selected);
        slide.setAttribute('aria-hidden', selected ? 'false' : 'true');
      });
      dots.forEach(function (dot, position) {
        var selected = position === active;
        dot.classList.toggle('active', selected);
        dot.setAttribute('aria-current', selected ? 'true' : 'false');
      });
    }

    function stopTimer() {
      if (timer) window.clearInterval(timer);
      timer = null;
    }
    function startTimer() {
      stopTimer();
      if (slides.length < 2 || paused || document.hidden) return;
      timer = window.setInterval(function () { showSlide(active + 1); }, interval);
    }

    if (previous) previous.addEventListener('click', function () { showSlide(active - 1); startTimer(); });
    if (next) next.addEventListener('click', function () { showSlide(active + 1); startTimer(); });
    dots.forEach(function (dot, index) {
      dot.addEventListener('click', function () { showSlide(index); startTimer(); });
    });
    section.addEventListener('mouseenter', function () { paused = true; stopTimer(); });
    section.addEventListener('mouseleave', function () { paused = false; startTimer(); });
    section.addEventListener('focusin', function () { paused = true; stopTimer(); });
    section.addEventListener('focusout', function (event) {
      if (!section.contains(event.relatedTarget)) { paused = false; startTimer(); }
    });
    document.addEventListener('visibilitychange', function () {
      if (document.hidden) stopTimer(); else startTimer();
    });

    var touchStartX = null;
    section.addEventListener('touchstart', function (event) {
      if (event.changedTouches && event.changedTouches.length) touchStartX = event.changedTouches[0].clientX;
    }, { passive: true });
    section.addEventListener('touchend', function (event) {
      if (touchStartX === null || !event.changedTouches || !event.changedTouches.length) return;
      var delta = event.changedTouches[0].clientX - touchStartX;
      if (Math.abs(delta) > 45) showSlide(active + (delta < 0 ? 1 : -1));
      touchStartX = null;
      startTimer();
    }, { passive: true });

    showSlide(active);
    startTimer();
  }

  function parseExpiry(value) {
    var m = String(value || '').match(/^(\d{4})-(\d{2})-(\d{2})(?:[ T](\d{2}):(\d{2})(?::(\d{2}))?)?/);
    if (!m) return null;
    return new Date(+m[1], +m[2] - 1, +m[3], +(m[4] || 23), +(m[5] || 59), +(m[6] || 59));
  }

  function formatDate(dateString) {
    var d = parseExpiry(dateString);
    if (!d) return '';
    return d.toLocaleDateString('en-IN', { day: '2-digit', month: 'short', year: 'numeric' }) + ', ' +
      d.toLocaleTimeString('en-IN', { hour: '2-digit', minute: '2-digit', hour12: true });
  }

  function initOfferTimer(expiryDate) {
    var wrapper = document.querySelector('.offer-timer-wrapper');
    if (!wrapper) return;
    if (!expiryDate) { wrapper.hidden = true; return; }
    var expiryObj = parseExpiry(expiryDate);
    if (!expiryObj) { wrapper.hidden = true; return; }
    var expiry = expiryObj.getTime();
    var ids = {
      days: document.getElementById('countdown-days'),
      hours: document.getElementById('countdown-hours'),
      minutes: document.getElementById('countdown-minutes'),
      seconds: document.getElementById('countdown-seconds')
    };
    function tick() {
      var remaining = Math.max(0, expiry - Date.now());
      var seconds = Math.floor(remaining / 1000);
      var values = {
        days: Math.floor(seconds / 86400),
        hours: Math.floor((seconds % 86400) / 3600),
        minutes: Math.floor((seconds % 3600) / 60),
        seconds: seconds % 60
      };
      Object.keys(ids).forEach(function (key) {
        if (ids[key]) ids[key].textContent = String(values[key]).padStart(2, '0');
      });
      if (remaining <= 0) wrapper.hidden = true;
    }
    wrapper.hidden = false;
    tick();
    window.setInterval(tick, 1000);
  }

  async function loadActiveCoupon() {
    var codeNode = document.querySelector('[data-featured-coupon-code]');
    if (!codeNode) return;
    var metaNode = document.querySelector('[data-featured-coupon-meta]');
    var termsNode = document.querySelector('[data-featured-offer-terms]');
    var discountNode = document.querySelector('[data-featured-offer-discount]');
    var copyButton = document.querySelector('[data-featured-coupon-copy]');
    try {
      var response = await fetch('account_api.php?action=homepage_coupon&_=' + Date.now(), {
        method: 'GET', credentials: 'same-origin', cache: 'no-store',
        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
      });
      if (!response.ok) throw new Error('Could not load the current coupon.');
      var payload = await response.json();
      var coupon = payload && payload.success ? payload.coupon : null;
      if (!coupon || !coupon.code) {
        codeNode.textContent = 'No active code right now';
        if (metaNode) metaNode.textContent = 'Please check back for the next KINZ offer.';
        if (termsNode) termsNode.textContent = 'There is no active coupon available at this moment.';
        if (discountNode) discountNode.textContent = 'new offers soon';
        if (copyButton) copyButton.disabled = true;
        var claim = document.querySelector('[data-featured-offer-cta]');
        if (claim) claim.hidden = true;
        var timer = document.querySelector('.offer-timer-wrapper');
        if (timer) timer.hidden = true;
        return;
      }

      codeNode.textContent = coupon.code;
      if (discountNode) discountNode.textContent = coupon.discount_label || 'special savings';
      var meta = [];
      if (Number(coupon.min_order) > 0) meta.push('Min. order ₹' + Number(coupon.min_order).toLocaleString('en-IN'));
      if (coupon.expires_at) meta.push('Ends on ' + formatDate(coupon.expires_at));
      if (metaNode) metaNode.textContent = meta.join(' · ');
      if (termsNode) {
        var scope = coupon.applicable_to ? 'Applies to ' + coupon.applicable_to : 'See coupon terms at checkout';
        termsNode.textContent = 'Use this active code at checkout. ' + scope + (Number(coupon.min_order) > 0 ? '. Minimum order ₹' + Number(coupon.min_order).toLocaleString('en-IN') + '.' : '.');
      }
      if (copyButton) {
        copyButton.disabled = false;
        copyButton.dataset.code = coupon.code;
        copyButton.title = 'Copy ' + coupon.code;
      }
      initOfferTimer(coupon.expires_at);
    } catch (error) {
      codeNode.textContent = 'Offer unavailable';
      if (metaNode) metaNode.textContent = 'Please view active coupons at checkout.';
      if (termsNode) termsNode.textContent = 'We could not load the active coupon right now.';
      if (copyButton) copyButton.disabled = true;
      var timer = document.querySelector('.offer-timer-wrapper');
      if (timer) timer.hidden = true;
      console.warn('KINZ homepage offer:', error.message || error);
    }
  }

  async function copyCoupon(button) {
    var code = String(button.dataset.code || '').trim();
    if (!code || button.disabled) return;
    try {
      if (navigator.clipboard && window.isSecureContext) {
        await navigator.clipboard.writeText(code);
      } else {
        var field = document.createElement('textarea');
        field.value = code;
        field.style.position = 'fixed';
        field.style.opacity = '0';
        document.body.appendChild(field);
        field.select();
        var copied = document.execCommand('copy');
        field.remove();
        if (!copied) throw new Error('Copy was not available in this browser.');
      }
      var original = button.innerHTML;
      button.innerHTML = '<i class="fa-solid fa-check"></i> Copied';
      window.setTimeout(function () { button.innerHTML = original; }, 1800);
    } catch (_) {
      window.prompt('Copy this active coupon code:', code);
    }
  }

  document.addEventListener('click', function (event) {
    var button = event.target && event.target.closest ? event.target.closest('[data-featured-coupon-copy]') : null;
    if (!button) return;
    event.preventDefault();
    copyCoupon(button);
  });

  function init() {
    // hero carousel is initialised by kinz-ui.js (Node reference logic)
    loadActiveCoupon();
  }
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init, { once: true });
  else init();
})();
