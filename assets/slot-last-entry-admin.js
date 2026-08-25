(function () {
  'use strict';

  var data = window.ParcsHTSlotAdminData || {regular_periods:[], exceptions:[]};

  function field(label, name, value) {
    var wrap = document.createElement('label');
    wrap.className = 'htp-field htp-slot-last-entry-field';
    var title = document.createElement('span');
    title.textContent = label;
    var input = document.createElement('input');
    input.type = 'number';
    input.min = '0';
    input.max = '1440';
    input.step = '1';
    input.name = name;
    input.value = value == null ? '' : String(value);
    wrap.appendChild(title);
    wrap.appendChild(input);
    return {wrap:wrap, input:input};
  }

  function enhanceRow(legacyInput) {
    if (!legacyInput || legacyInput.dataset.htpSlotsReady === '1') return;
    var match = legacyInput.name.match(/^settings\[(regular_periods|exceptions)\]\[([^\]]+)\]\[last_entry_minutes\]$/);
    if (!match) return;

    legacyInput.dataset.htpSlotsReady = '1';
    var listKey = match[1];
    var rawIndex = match[2];
    var numericIndex = /^\d+$/.test(rawIndex) ? Number(rawIndex) : -1;
    var saved = numericIndex >= 0 && data[listKey] && data[listKey][numericIndex] ? data[listKey][numericIndex] : {};
    var legacyValue = String(legacyInput.value || '');
    var slot1Value = saved.slot1 !== undefined ? saved.slot1 : legacyValue;
    var slot2Value = saved.slot2 !== undefined ? saved.slot2 : legacyValue;
    var base = 'settings[' + listKey + '][' + rawIndex + ']';

    var slot1 = field('Dernière entrée créneau 1 — minutes avant fermeture', base + '[last_entry_minutes_slot1]', slot1Value);
    var slot2 = field('Dernière entrée créneau 2 — minutes avant fermeture', base + '[last_entry_minutes_slot2]', slot2Value);
    var legacyWrap = legacyInput.closest('.htp-field');
    if (!legacyWrap || !legacyWrap.parentNode) return;

    legacyWrap.parentNode.insertBefore(slot1.wrap, legacyWrap);
    legacyWrap.parentNode.insertBefore(slot2.wrap, legacyWrap);
    legacyWrap.style.display = 'none';
    legacyWrap.setAttribute('aria-hidden', 'true');

    var row = legacyInput.closest('.htp-repeat-row');
    var open2 = row ? row.querySelector('input[name="' + base.replace(/([\[\]])/g, '\\$1') + '[open2]"]') : null;
    var close2 = row ? row.querySelector('input[name="' + base.replace(/([\[\]])/g, '\\$1') + '[close2]"]') : null;
    // querySelector avec des crochets échappés varie selon les navigateurs : secours par recherche de nom exact.
    if (row) {
      Array.prototype.forEach.call(row.querySelectorAll('input'), function (input) {
        if (input.name === base + '[open2]') open2 = input;
        if (input.name === base + '[close2]') close2 = input;
      });
    }

    function syncLegacy() {
      legacyInput.value = slot2.input.value !== '' ? slot2.input.value : slot1.input.value;
    }
    function toggleSlot2() {
      var hasSecond = !!((open2 && open2.value) || (close2 && close2.value));
      slot2.wrap.style.opacity = hasSecond ? '1' : '.58';
      slot2.wrap.title = hasSecond ? '' : 'Utilisé uniquement si le créneau 2 est renseigné.';
    }
    slot1.input.addEventListener('input', syncLegacy);
    slot2.input.addEventListener('input', syncLegacy);
    if (open2) open2.addEventListener('input', toggleSlot2);
    if (close2) close2.addEventListener('input', toggleSlot2);
    syncLegacy();
    toggleSlot2();
  }

  function enhanceAll(root) {
    (root || document).querySelectorAll('input[name$="[last_entry_minutes]"]').forEach(enhanceRow);
  }

  function boot() {
    enhanceAll(document);
    var observer = new MutationObserver(function (mutations) {
      mutations.forEach(function (mutation) {
        mutation.addedNodes.forEach(function (node) {
          if (node.nodeType === 1) enhanceAll(node);
        });
      });
    });
    observer.observe(document.body, {childList:true, subtree:true});
  }

  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', boot);
  else boot();
}());
