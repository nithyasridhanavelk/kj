(function () {
  const modal = document.getElementById('modal-forgot-password');
  if (!modal) return;

  const step1 = document.getElementById('forgot-step-1');
  const step2 = document.getElementById('forgot-step-2');
  const message = document.getElementById('forgot-message');
  const idInput = document.getElementById('forgot-input-id');
  const codeInput = document.getElementById('forgot-otp-code');
  const passInput = document.getElementById('forgot-new-pass');
  const sendButton = document.getElementById('btn-send-reset-code');
  const confirmButton = document.getElementById('btn-confirm-password-reset');

  function showMessage(text, isError) {
    message.textContent = text;
    message.style.display = 'block';
    message.style.background = isError ? '#fff0ef' : '#e8f5ec';
    message.style.color = isError ? '#a12622' : '#176b36';
  }

  function hideMessage() {
    message.style.display = 'none';
    message.textContent = '';
  }

  function openModal() {
    step1.style.display = 'block';
    step2.style.display = 'none';
    codeInput.value = '';
    passInput.value = '';
    hideMessage();
    const loginInput = document.getElementById('login-identifier');
    if (loginInput && loginInput.value.trim() !== '') idInput.value = loginInput.value.trim();
    modal.classList.add('active');
  }

  function closeModal() {
    modal.classList.remove('active');
  }

  async function post(values) {
    const response = await fetch('forgot-password.php', {
      method: 'POST', credentials: 'same-origin', body: new URLSearchParams(values)
    });
    const data = await response.json();
    if (!response.ok || !data.success) throw new Error(data.message || 'Something went wrong. Please try again.');
    return data;
  }

  document.getElementById('btn-open-forgot')?.addEventListener('click', openModal);
  document.getElementById('btn-close-forgot')?.addEventListener('click', closeModal);
  modal.addEventListener('click', function (event) {
    if (event.target === modal) closeModal();
  });

  sendButton.addEventListener('click', async function () {
    if (idInput.value.trim() === '') {
      showMessage('Enter your registered email or mobile number.', true);
      return;
    }
    sendButton.disabled = true;
    try {
      const data = await post({ action: 'send_code', identifier: idInput.value.trim() });
      step1.style.display = 'none';
      step2.style.display = 'block';
      showMessage(data.message, false);
    } catch (error) {
      showMessage(error.message, true);
    }
    sendButton.disabled = false;
  });

  confirmButton.addEventListener('click', async function () {
    confirmButton.disabled = true;
    try {
      const data = await post({
        action: 'reset',
        identifier: idInput.value.trim(),
        code: codeInput.value.trim(),
        new_password: passInput.value
      });
      showMessage(data.message, false);
      const loginInput = document.getElementById('login-identifier');
      if (loginInput) loginInput.value = idInput.value.trim();
      setTimeout(closeModal, 2200);
    } catch (error) {
      showMessage(error.message, true);
    }
    confirmButton.disabled = false;
  });
})();
