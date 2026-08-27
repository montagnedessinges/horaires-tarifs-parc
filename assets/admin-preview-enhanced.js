(function () {
  'use strict';

  function pad(value) { return String(value).padStart(2, '0'); }
  function toMinutes(value) {
    var parts = String(value || '').split(':');
    return parts.length === 2 ? (Number(parts[0]) * 60 + Number(parts[1])) : 0;
  }
  function timeLabel(value) {
    if (!value) return '';
    var parts = String(value).split(':').map(Number);
    return parts[0] + 'h' + (parts[1] ? pad(parts[1]) : '');
  }
  function fromMinutes(value) {
    value = Math.max(0, Number(value) || 0);
    return pad(Math.floor(value / 60)) + ':' + pad(value % 60);
  }
  function slots(status) {
    if (!status) return [];
    if (status.slots && status.slots.length) return status.slots;
    if (status.openTime && status.closeTime) return [{ open: status.openTime, close: status.closeTime }];
    return [];
  }
  function phase(status, current) {
    var list = slots(status);
    if (!status || !status.open || !list.length) return { type: 'closed', index: -1 };
    for (var i = 0; i < list.length; i++) {
      if (current >= toMinutes(list[i].open) && current < toMinutes(list[i].close)) return { type: 'open', index: i };
      if (current < toMinutes(list[i].open)) return { type: i === 0 ? 'before' : 'gap', index: i };
    }
    return { type: 'after', index: -1 };
  }
  function lastEntry(status, index) {
    var list = slots(status);
    if (!list[index]) return '';
    var source = status.exception || status.period || {};
    var general = ((window.ParcsHTPData || {}).settings || {}).general || {};
    var raw;
    if (index === 0) raw = source.last_entry_minutes;
    else raw = source.last_entry_minutes2;
    if (raw === '' || raw === undefined || raw === null) raw = source.last_entry_minutes;
    if (raw === '' || raw === undefined || raw === null) raw = general.last_entry_minutes;
    var offset = Number(raw || 0);
    return fromMinutes(toMinutes(list[index].close) - offset);
  }
  function ranges(status) {
    return slots(status).map(function (slot) { return timeLabel(slot.open) + '–' + timeLabel(slot.close); }).join(' / ');
  }
  function remainingRanges(status, current) {
    return slots(status).filter(function (slot) { return current < toMinutes(slot.close); }).map(function (slot) {
      return timeLabel(slot.open) + '–' + timeLabel(slot.close);
    }).join(' / ');
  }
  function nextOpening(date) {
    return window.ParcsHTP && typeof window.ParcsHTP.nextOpeningAcrossSeasons === 'function'
      ? window.ParcsHTP.nextOpeningAcrossSeasons(date) : null;
  }
  function stateFor(date, time) {
    if (!window.ParcsHTP || typeof window.ParcsHTP.resolveDay !== 'function') return null;
    var status = window.ParcsHTP.resolveDay(date);
    var current = toMinutes(time);
    var p = phase(status, current);
    var list = slots(status);
    var state = { status: status, phase: p, current: current, statusText: '', hoursText: '', lastEntryText: '', ruleText: '' };

    if (!status.open) {
      state.statusText = 'FERMÉ';
      state.hoursText = 'Parc fermé pour cette date';
    } else if (p.type === 'before') {
      state.statusText = 'Ouverture à ' + timeLabel(list[0].open);
      state.hoursText = ranges(status);
    } else if (p.type === 'open') {
      state.statusText = 'OUVERT';
      state.hoursText = remainingRanges(status, current) || ranges(status);
      var entry = lastEntry(status, p.index);
      state.lastEntryText = entry ? 'Dernière entrée : ' + timeLabel(entry) : '';
    } else if (p.type === 'gap') {
      state.statusText = 'Réouverture à ' + timeLabel(list[p.index].open);
      state.hoursText = remainingRanges(status, current);
    } else {
      var next = nextOpening(date);
      state.statusText = next ? 'Prochaine ouverture' : 'Fermé pour aujourd’hui';
      state.hoursText = next ? ((next.date || '') + ' à partir de ' + timeLabel((next.status || {}).openTime || (slots(next.status)[0] || {}).open)) : '';
    }

    if (status.exceptional) state.ruleText = status.type === 'closed' ? 'Fermeture exceptionnelle prioritaire' : 'Horaire exceptionnel prioritaire';
    else if (status.open) state.ruleText = 'Horaire classique';
    else state.ruleText = 'Aucun horaire applicable';
    return state;
  }
  function escapeHtml(value) {
    return String(value == null ? '' : value).replace(/[&<>"']/g, function (char) {
      return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[char];
    });
  }
  function field(row, suffix) {
    var el = row.querySelector('[name$="[' + suffix + ']"]');
    return el ? String(el.value || '') : '';
  }
  function translatedField(row, suffix) {
    var el = row.querySelector('[name$="[' + suffix + '][fr]"]');
    return el ? String(el.value || '') : field(row, suffix);
  }
  function checked(row, suffix) {
    var el = row.querySelector('input[type="checkbox"][name$="[' + suffix + ']"]');
    return !!(el && el.checked);
  }
  function popupFor(date) {
    var found = null;
    document.querySelectorAll('.htp-repeat-row').forEach(function (row) {
      if (found) return;
      var popupToggle = row.querySelector('input[type="checkbox"][name$="[show_popup]"]');
      if (!popupToggle || !popupToggle.checked) return;
      var enabled = row.querySelector('input[type="checkbox"][name$="[enabled]"]');
      if (enabled && !enabled.checked) return;
      var start = field(row, 'start');
      var end = field(row, 'end');
      if (start && date < start) return;
      if (end && date > end) return;
      var title = translatedField(row, 'popup_title') || translatedField(row, 'title') || translatedField(row, 'context') || 'Pop-up actif';
      var message = translatedField(row, 'popup_message') || translatedField(row, 'message');
      var buttonLabel = translatedField(row, 'popup_button_label') || translatedField(row, 'button_label');
      found = { title: title, message: message, buttonLabel: buttonLabel };
    });
    return found;
  }
  function card(title, state, todayMode) {
    var hours = todayMode ? (ranges(state.status) || state.hoursText) : state.hoursText;
    return '<div class="htp-sim-card"><span class="htp-sim-kicker">' + escapeHtml(title) + '</span>' +
      '<strong class="htp-sim-status">' + escapeHtml(state.statusText) + '</strong>' +
      (hours ? '<div class="htp-sim-hours">' + escapeHtml(hours) + '</div>' : '') +
      (state.lastEntryText ? '<div class="htp-sim-entry">' + escapeHtml(state.lastEntryText) + '</div>' : '') + '</div>';
  }
  function render() {
    var dateInput = document.querySelector('[data-htp-preview-date]');
    var timeInput = document.querySelector('[data-htp-preview-time]');
    var result = document.querySelector('[data-htp-preview-result]');
    if (!dateInput || !timeInput || !result) return;
    if (!dateInput.value || !timeInput.value) {
      result.textContent = 'Choisissez une date et une heure.';
      return;
    }
    var state = stateFor(dateInput.value, timeInput.value);
    if (!state) {
      result.textContent = 'Le moteur d’aperçu n’est pas disponible.';
      return;
    }
    var popup = popupFor(dateInput.value);
    var popupHtml = popup
      ? '<div class="htp-sim-popup"><span class="htp-sim-kicker">Pop-up actif</span><strong>' + escapeHtml(popup.title) + '</strong>' +
        (popup.message ? '<p>' + escapeHtml(popup.message).replace(/\n/g, '<br>') + '</p>' : '') +
        (popup.buttonLabel ? '<span class="button button-secondary">' + escapeHtml(popup.buttonLabel) + '</span>' : '') + '</div>'
      : '<div class="htp-sim-popup is-empty"><span class="htp-sim-kicker">Pop-up</span><span>Aucun pop-up actif pour cette date.</span></div>';
    result.innerHTML = '<div class="htp-sim-meta"><strong>Simulation :</strong> ' + escapeHtml(dateInput.value) + ' à ' + escapeHtml(timeInput.value) +
      '<br><strong>Règle appliquée :</strong> ' + escapeHtml(state.ruleText) + '</div>' +
      '<div class="htp-sim-grid">' + card('Page d’accueil', state, false) + card('Page Horaires & Tarifs — Aujourd’hui', state, true) + '</div>' + popupHtml;
  }
  function enhance() {
    var section = document.getElementById('htp-preview');
    if (!section || section.dataset.htpEnhancedPreview === '1') return;
    section.dataset.htpEnhancedPreview = '1';

    var controls = section.querySelector('.htp-preview-controls');
    var dateInput = section.querySelector('[data-htp-preview-date]');
    var button = section.querySelector('[data-htp-preview-button]');
    if (controls && dateInput && button) {
      var label = document.createElement('label');
      label.className = 'htp-field';
      label.innerHTML = '<span>Heure à tester</span><input type="time" data-htp-preview-time value="12:00">';
      controls.insertBefore(label, button);
      button.textContent = 'Actualiser l’aperçu';
      button.addEventListener('click', function (event) {
        event.preventDefault();
        event.stopImmediatePropagation();
        render();
      }, true);
      dateInput.addEventListener('change', render);
      label.querySelector('input').addEventListener('change', render);
    }

    var saveAll = document.querySelector('[name="htp_save_all"]');
    if (saveAll) saveAll.remove();

    var style = document.createElement('style');
    style.textContent = '.htp-preview-controls{display:flex;gap:12px;align-items:end;flex-wrap:wrap}.htp-sim-meta{margin:14px 0;padding:12px;border:1px solid #dcdcde;border-radius:8px}.htp-sim-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:12px}.htp-sim-card,.htp-sim-popup{padding:16px;border:1px solid #dcdcde;border-radius:10px;background:#fff}.htp-sim-kicker{display:block;font-size:12px;text-transform:uppercase;letter-spacing:.04em;margin-bottom:6px;opacity:.75}.htp-sim-status{display:block;font-size:22px;margin-bottom:6px}.htp-sim-hours{font-size:18px;font-weight:600}.htp-sim-entry{margin-top:6px}.htp-sim-popup{margin-top:12px}.htp-sim-popup.is-empty{opacity:.7}';
    document.head.appendChild(style);
  }

  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', enhance);
  else enhance();
}());
