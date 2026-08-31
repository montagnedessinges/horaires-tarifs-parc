(function () {
  'use strict';

  function protectScopedSave(form, submitter) {
    if (!form || !submitter || submitter.name !== 'htp_save_active') return;
    var activeInput = form.querySelector('[data-htp-active-tab-input]');
    var activeId = activeInput ? String(activeInput.value || '') : '';
    if (!activeId) return;

    form.querySelectorAll('section.htp-card').forEach(function (section) {
      if (section.id === activeId) return;
      section.querySelectorAll('input,select,textarea,button').forEach(function (control) {
        control.disabled = true;
      });
    });
  }

  function bind() {
    document.querySelectorAll('.htp-admin form').forEach(function (form) {
      var action = form.querySelector('input[name="action"][value="parcs_ht_save"]');
      if (!action || form.getAttribute('data-htp-save-guard-ready') === '1') return;
      form.setAttribute('data-htp-save-guard-ready', '1');
      form.addEventListener('submit', function (event) {
        protectScopedSave(form, event.submitter || document.activeElement);
      }, true);
    });
  }

  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', bind);
  else bind();
}());
