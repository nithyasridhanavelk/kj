(function () {
  const token = document.querySelector('meta[name="kinz-csrf"]')?.content || '';

  async function post(action, values) {
    const body = new URLSearchParams(Object.assign({ action: action }, values || {}));
    const response = await fetch('cart_api.php', {
      method: 'POST', credentials: 'same-origin', body: body,
      headers: { 'X-CSRF-Token': token }
    });
    const data = await response.json();
    if (!response.ok || !data.success) throw new Error(data.message || 'Unable to continue.');
    return data;
  }

  window.KINZPayOrder = async function (orderCode) {
    if (typeof Razorpay === 'undefined') {
      throw new Error('Razorpay could not be loaded. Check your internet connection and try again.');
    }
    const session = await post('razorpay_order', { order_code: orderCode });
    return new Promise(function (resolve, reject) {
      const checkout = new Razorpay({
        key: session.key_id,
        amount: session.amount,
        currency: session.currency,
        name: 'KINZ - A Hidden Treasure',
        description: 'Order ' + session.order_code,
        order_id: session.razorpay_order_id,
        prefill: { name: session.name, email: session.email, contact: session.contact },
        theme: { color: '#0A3D2E' },
        handler: async function (payment) {
          try {
            const result = await post('razorpay_verify', {
              order_code: session.order_code,
              razorpay_order_id: payment.razorpay_order_id,
              razorpay_payment_id: payment.razorpay_payment_id,
              razorpay_signature: payment.razorpay_signature
            });
            resolve({ paid: true, redirect: result.redirect });
          } catch (error) {
            reject(error);
          }
        },
        modal: {
          ondismiss: function () { resolve({ paid: false }); }
        }
      });
      checkout.open();
    });
  };
})();
