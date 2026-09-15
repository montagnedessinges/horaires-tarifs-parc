(function () {
  'use strict';
  function all(root, selector) { return Array.prototype.slice.call(root.querySelectorAll(selector)); }
  function activateTariff(section, key) {
    if (!section) return;
    var tabs = all(section, '[data-htp-tariff-tab]');
    if (!tabs.some(function (tab) { return tab.dataset.htpTariffTab === key; })) key = tabs[0] && tabs[0].dataset.htpTariffTab;
    tabs.forEach(function (tab) {
      var active = tab.dataset.htpTariffTab === key;
      tab.setAttribute('aria-selected', String(active));
      tab.tabIndex = active ? 0 : -1;
      var panel = all(section, '.parcs-ht-tariff-panel').find(function (p) { return p.id === tab.getAttribute('aria-controls'); });
      if (panel) panel.hidden = !active;
    });
  }
  function selectYear(tab, focus) {
    var root = tab.closest('[data-htp-tariff-years]');
    if (!root) return;
    var current = root.querySelector('[data-htp-year-panel]:not([hidden]) [data-htp-tariff-tab][aria-selected="true"]');
    var key = current ? current.dataset.htpTariffTab : 'individual';
    all(root, '[data-htp-retail-year]').forEach(function (button) {
      var active = button === tab;
      button.setAttribute('aria-selected', String(active));
      button.tabIndex = active ? 0 : -1;
      button.classList.toggle('is-active', active);
    });
    all(root, '[data-htp-year-panel]').forEach(function (panel) {
      panel.hidden = panel.dataset.htpYearPanel !== tab.dataset.htpRetailYear;
      if (!panel.hidden) activateTariff(panel.querySelector('.parcs-ht-tariffs'), key);
    });
    if (focus) tab.focus();
    document.dispatchEvent(new CustomEvent('parcsht:tariffs-updated', {detail: {year: tab.dataset.htpRetailYear, section: root}}));
  }
  document.addEventListener('click', function (event) {
    var year = event.target.closest('[data-htp-retail-year]');
    if (year) { event.preventDefault(); selectYear(year, false); return; }
    var tab = event.target.closest('[data-htp-tariff-tab]');
    if (tab) { event.preventDefault(); activateTariff(tab.closest('.parcs-ht-tariffs'), tab.dataset.htpTariffTab); }
  });
  document.addEventListener('keydown', function (event) {
    var tab = event.target.closest('[data-htp-retail-year], [data-htp-tariff-tab]');
    if (!tab || ['ArrowLeft', 'ArrowRight', 'Home', 'End'].indexOf(event.key) < 0) return;
    var years = tab.hasAttribute('data-htp-retail-year');
    var tabs = all(tab.closest('[role="tablist"]'), years ? '[data-htp-retail-year]' : '[data-htp-tariff-tab]');
    var index = tabs.indexOf(tab);
    var next = event.key === 'Home' ? 0 : event.key === 'End' ? tabs.length - 1 : (index + (event.key === 'ArrowRight' ? 1 : -1) + tabs.length) % tabs.length;
    event.preventDefault();
    if (years) selectYear(tabs[next], true);
    else { activateTariff(tab.closest('.parcs-ht-tariffs'), tabs[next].dataset.htpTariffTab); tabs[next].focus(); }
  });
}());
