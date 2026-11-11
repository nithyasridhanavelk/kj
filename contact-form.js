(function () {
  'use strict';
  const form = document.getElementById('contact-form');
  const status = document.getElementById('contact-form-status');
  if (!form || !status) return;

  form.addEventListener('submit', async function (event) {
    event.preventDefault();
    if (!form.reportValidity()) return;

    const submitButton = form.querySelector('button[type="submit"]');
    const originalButtonHtml = submitButton ? submitButton.innerHTML : '';
    status.style.display = 'block';
    status.style.color = 'var(--text-muted)';
    status.textContent = 'Sending your inquiry...';
    if (submitButton) submitButton.disabled = true;

    try {
      const response = await fetch(form.action, {
        method: 'POST',
        body: new FormData(form),
        credentials: 'same-origin',
        headers: { 'Accept': 'application/json' }
      });
      const result = await response.json();
      status.textContent = result.message || (result.success ? 'Your inquiry has been submitted.' : 'Unable to submit your inquiry.');
      status.style.color = result.success ? 'var(--emerald-800)' : '#b42318';
      if (result.success) form.reset();
    } catch (error) {
      status.textContent = 'Unable to connect right now. Please try again.';
      status.style.color = '#b42318';
    } finally {
      if (submitButton) {
        submitButton.disabled = false;
        submitButton.innerHTML = originalButtonHtml;
      }
    }
  });
})();
