(function () {
  'use strict';
  document.querySelectorAll('.parcs-ht-voucher').forEach(function (root) {
    var check = root.querySelector('.parcs-ht-voucher-check');
    var input = check.querySelector('input[name="postcode"]');
    var button = check.querySelector('button[type="submit"]');
    var feedback = root.querySelector('[data-voucher-feedback]');
    var panel = root.querySelector('[data-voucher-form]');
    var cf7postal = panel.querySelector('input[name="bon_code_postal"]');
    var lastGood = '';
    function hideForm() {
      panel.hidden = true;
      if (cf7postal) cf7postal.value = '';
      lastGood = '';
    }
    input.addEventListener('input', function () {
      if (input.value !== lastGood) hideForm();
      feedback.textContent = '';
    });
    check.addEventListener('submit', function (event) {
      event.preventDefault();
      hideForm();
      var code = input.value.trim();
      if (!/^[0-9]{5}$/.test(code)) {
        feedback.textContent = root.dataset.invalid;
        input.focus();
        return;
      }
      if (!cf7postal) {
        feedback.textContent = root.dataset.error;
        return;
      }
      var body = new URLSearchParams();
      body.append('action', 'parcs_ht_discovery_postcode');
      body.append('nonce', root.dataset.nonce);
      body.append('postcode', code);
      button.disabled = true;
      fetch(root.dataset.url, {
        method: 'POST',
        credentials: 'same-origin',
        headers: {'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'},
        body: body.toString()
      }).then(function (response) {
        if (!response.ok) throw new Error('Request failed');
        return response.json();
      }).then(function (payload) {
        if (!payload || !payload.success) throw new Error('Invalid reply');
        if (input.value.trim() !== code) return;
        if (payload.data && payload.data.eligible) {
          cf7postal.value = code;
          cf7postal.dispatchEvent(new Event('input', {bubbles: true}));
          lastGood = code;
          panel.hidden = false;
          feedback.textContent = '';
        } else {
          feedback.textContent = root.querySelector('[data-voucher-not-eligible]').dataset.voucherNotEligible;
        }
      }).catch(function () {
        feedback.textContent = root.dataset.error;
      }).finally(function () { button.disabled = false; });
    });
  });
}());
