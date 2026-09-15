(function () {
  'use strict';

  var currentLanguage = 'fr';

  function initScopedSave() {
    var form=document.querySelector('.htp-admin form[action*="admin-post.php"] input[name="action"][value="parcs_ht_save"]');
    form=form?form.closest('form'):null;
    if(!form)return;
    form.addEventListener('submit',function(event){
      var submitter=event.submitter||document.activeElement;
      if(!submitter||submitter.name!=='htp_save_active')return;
      var activeInput=form.querySelector('[data-htp-active-tab-input]');
      var activeId=activeInput?activeInput.value:'';
      form.querySelectorAll('section.htp-card').forEach(function(section){
        if(section.id===activeId||section.classList.contains('htp-year-controls'))return;
        section.querySelectorAll('input,select,textarea,button').forEach(function(control){control.disabled=true;});
      });
    });
  }

  function applyLanguage(language) {
    currentLanguage = language;
    document.querySelectorAll('[data-htp-language]').forEach(function (button) {
      var active = button.getAttribute('data-htp-language') === language;
      button.classList.toggle('button-primary', active);
      button.setAttribute('aria-selected', active ? 'true' : 'false');
    });
    document.querySelectorAll('.htp-translate').forEach(function (field) {
      field.hidden = field.getAttribute('data-lang') !== language;
    });
  }

  function initLocalTranslations(root) {
    (root || document).querySelectorAll('.htp-local-translation').forEach(function (group) {
      if (group.dataset.htpLocalReady === '1') return;
      group.dataset.htpLocalReady = '1';
      group.querySelectorAll('[data-htp-local-language]').forEach(function (button) {
        button.addEventListener('click', function () {
          var lang = button.getAttribute('data-htp-local-language') || 'fr';
          group.setAttribute('data-htp-local-lang', lang);
          group.querySelectorAll('[data-htp-local-language]').forEach(function (b) {
            b.classList.toggle('button-primary', b === button);
          });
          group.querySelectorAll('.htp-local-lang-field').forEach(function (field) {
            field.hidden = field.getAttribute('data-lang') !== lang;
          });
        });
      });
    });
  }

  function syncOptionalColor(container) {
    if (!container) return;
    var picker = container.querySelector('.htp-optional-color-picker');
    var hidden = container.querySelector('.htp-optional-color-value');
    var inherit = container.querySelector('.htp-optional-color-inherit');
    if (!picker || !hidden || !inherit) return;
    picker.disabled = inherit.checked;
    hidden.value = inherit.checked ? '' : picker.value;
  }

  function initOptionalColors(root) {
    (root || document).querySelectorAll('.htp-optional-color').forEach(syncOptionalColor);
  }


  function syncPopupBlock(block) {
    if (!block) return;
    var toggle = block.querySelector('input[type="checkbox"][name$="[show_popup]"]');
    var settings = block.querySelector('[data-htp-popup-settings]');
    if (!toggle || !settings) return;
    settings.hidden = !toggle.checked;
  }
  function initPopupBlocks(root) {
    (root || document).querySelectorAll('[data-htp-popup-block]').forEach(syncPopupBlock);
  }

  function syncButtonBlock(block) {
    if (!block) return;
    var toggle = block.querySelector('input[type="checkbox"][name$="[show_button]"], input[type="checkbox"][name$="[popup_show_button]"]');
    var settings = block.querySelector('[data-htp-button-settings]');
    if (!toggle || !settings) return;
    settings.hidden = !toggle.checked;
  }
  function initButtonBlocks(root) {
    (root || document).querySelectorAll('[data-htp-button-block]').forEach(syncButtonBlock);
  }

  function syncDomainTooltipBlock(block) {
    if (!block) return;
    var toggle = block.querySelector('input[type="checkbox"][name$="[show_tooltip]"]');
    var settings = block.querySelector('[data-htp-domain-tooltip-settings]');
    if (!toggle || !settings) return;
    settings.hidden = !toggle.checked;
  }

  function initDomainTooltipBlocks(root) {
    (root || document).querySelectorAll('[data-htp-domain-tooltip-block]').forEach(syncDomainTooltipBlock);
  }

  var adminTabIds = [
    'htp-general','htp-regular','htp-advent','htp-holidays','htp-domain','htp-exceptions',
    'htp-alerts','htp-tariffs','htp-quote','htp-preview','htp-updates','htp-shortcodes'
  ];

  function initAdminTabs() {
    var nav = document.querySelector('[data-htp-admin-tabs]');
    if (!nav) return;

    var tabs = Array.prototype.slice.call(nav.querySelectorAll('[data-htp-admin-tab]'));
    var panels = adminTabIds.map(function (id) { return document.getElementById(id); }).filter(Boolean);
    var activeInput = document.querySelector('[data-htp-active-tab-input]');
    var seasonInput = document.querySelector('input[name="season_year"]');
    var mainSettingsForm = document.querySelector('[data-htp-main-settings-form]');
    var seasonManager = document.querySelector('[data-htp-season-manager]');
    var storageKey = 'parcsHTAdminTab:' + (seasonInput ? seasonInput.value : 'default');

    panels.forEach(function (panel) {
      panel.setAttribute('role', 'tabpanel');
      panel.setAttribute('tabindex', '0');
    });

    tabs.forEach(function (tab) {
      var id = tab.getAttribute('data-htp-admin-tab');
      tab.setAttribute('aria-controls', id);
      tab.setAttribute('tabindex', '-1');
    });

    function activate(id, options) {
      options = options || {};
      if (adminTabIds.indexOf(id) === -1 || !document.getElementById(id)) id = 'htp-general';

      tabs.forEach(function (tab) {
        var active = tab.getAttribute('data-htp-admin-tab') === id;
        tab.classList.toggle('nav-tab-active', active);
        tab.setAttribute('aria-selected', active ? 'true' : 'false');
        tab.setAttribute('tabindex', active ? '0' : '-1');
      });

      panels.forEach(function (panel) {
        panel.hidden = panel.id !== id;
      });

      var adventActive = id === 'htp-advent';
      if (mainSettingsForm) mainSettingsForm.hidden = adventActive;
      if (seasonManager) seasonManager.hidden = adventActive;

      if (activeInput) activeInput.value = id;
      try { window.localStorage.setItem(storageKey, id); } catch (error) {}

      if (options.updateUrl !== false && window.history && window.history.replaceState) {
        var url = new URL(window.location.href);
        url.searchParams.set('tab', id);
        url.hash = '';
        window.history.replaceState(null, '', url.toString());
      }

      if (options.focusPanel) {
        var panel = document.getElementById(id);
        if (panel) panel.focus({ preventScroll: true });
      }
    }

    var urlTab = '';
    try { urlTab = new URL(window.location.href).searchParams.get('tab') || ''; } catch (error) {}
    var storedTab = '';
    try { storedTab = window.localStorage.getItem(storageKey) || ''; } catch (error) {}
    var initial = adminTabIds.indexOf(urlTab) !== -1 ? urlTab : (adminTabIds.indexOf(storedTab) !== -1 ? storedTab : 'htp-general');
    activate(initial, { updateUrl: false });

    tabs.forEach(function (tab, index) {
      tab.addEventListener('click', function () {
        activate(tab.getAttribute('data-htp-admin-tab'), { updateUrl: true });
      });
      tab.addEventListener('keydown', function (event) {
        if (event.key !== 'ArrowLeft' && event.key !== 'ArrowRight' && event.key !== 'Home' && event.key !== 'End') return;
        event.preventDefault();
        var targetIndex = index;
        if (event.key === 'ArrowLeft') targetIndex = (index - 1 + tabs.length) % tabs.length;
        if (event.key === 'ArrowRight') targetIndex = (index + 1) % tabs.length;
        if (event.key === 'Home') targetIndex = 0;
        if (event.key === 'End') targetIndex = tabs.length - 1;
        var target = tabs[targetIndex];
        target.focus();
        activate(target.getAttribute('data-htp-admin-tab'), { updateUrl: true });
      });
    });
  }

  function syncWeekdays(fieldset, markTouched) {
    if (!fieldset) return;
    var csv = fieldset.querySelector('.htp-weekdays-csv');
    if (!csv) return;
    var values = [];
    fieldset.querySelectorAll('input[type="checkbox"][name*="[weekdays]"]:checked').forEach(function (box) {
      values.push(String(box.value));
    });
    csv.value = values.join(',');
    if (markTouched) {
      var touched = fieldset.querySelector('.htp-weekdays-touched');
      if (touched) touched.value = '1';
    }
  }

  function hydrateWeekdays(fieldset) {
    if (!fieldset) return;
    var csv = fieldset.querySelector('.htp-weekdays-csv');
    if (!csv) return;
    var selected = String(csv.value || '').split(',').filter(Boolean);
    fieldset.querySelectorAll('input[type="checkbox"][name*="[weekdays]"]').forEach(function (box) {
      box.checked = selected.indexOf(String(box.value)) !== -1;
    });
    var touched = fieldset.querySelector('.htp-weekdays-touched');
    if (touched) touched.value = '0';
  }

  function hydrateAllWeekdays(root) {
    (root || document).querySelectorAll('[data-htp-weekdays]').forEach(hydrateWeekdays);
  }


  function fieldValue(selector, root) {
    var el = (root || document).querySelector(selector);
    return el ? el.value : '';
  }

  function globalValue(name, fallback) {
    var el = document.querySelector('[name="settings[general][' + name + ']"]');
    return el && el.value !== '' ? el.value : fallback;
  }

  function globalChecked(name, fallback) {
    var el = document.querySelector('input[type="checkbox"][name="settings[general][' + name + ']"]');
    return el ? el.checked : fallback;
  }

  function translatedValue(root, key, language) {
    return fieldValue('[name$="[' + key + '][' + language + ']"]', root);
  }

  function plainValue(root, key) {
    return fieldValue('[name$="[' + key + ']"]', root);
  }

  function checkedValue(root, key, fallback) {
    var el = root ? root.querySelector('input[type="checkbox"][name$="[' + key + ']"]') : null;
    return el ? el.checked : fallback;
  }

  function formatPreviewDate(value, language) {
    var match = String(value || '').match(/^(\d{4})-(\d{2})-(\d{2})$/);
    if (!match) return String(value || '');
    var date = new Date(Date.UTC(parseInt(match[1], 10), parseInt(match[2], 10) - 1, parseInt(match[3], 10), 12, 0, 0));
    var locale = language === 'en' ? 'en-GB' : (language === 'de' ? 'de-DE' : 'fr-FR');
    try {
      return new Intl.DateTimeFormat(locale, { day: 'numeric', month: 'long', year: 'numeric', timeZone: 'UTC' }).format(date);
    } catch (error) {
      return match[3] + '/' + match[2] + '/' + match[1];
    }
  }

  function formatPreviewTime(value, language) {
    var match = String(value || '').match(/^(\d{1,2}):(\d{2})$/);
    if (!match) return String(value || '');
    var hour = parseInt(match[1], 10);
    var minute = parseInt(match[2], 10);
    if (language === 'en') {
      var suffix = hour >= 12 ? 'PM' : 'AM';
      var displayHour = hour % 12 || 12;
      return displayHour + (minute ? ':' + String(minute).padStart(2, '0') : '') + ' ' + suffix;
    }
    if (language === 'de') return hour + (minute ? ':' + String(minute).padStart(2, '0') : '') + ' Uhr';
    return hour + ' h' + (minute ? ' ' + String(minute).padStart(2, '0') : '');
  }

  function exceptionDefaultTitle(row, language) {
    var closed = plainValue(row, 'type') === 'closed';
    if (language === 'en') return closed ? 'Exceptional closure' : 'Exceptional opening hours';
    if (language === 'de') return closed ? 'Außergewöhnliche Schließung' : 'Außergewöhnliche Öffnungszeiten';
    return closed ? 'Fermeture exceptionnelle' : 'Horaires exceptionnels';
  }

  function exceptionAutoMessage(row, language) {
    var start = formatPreviewDate(plainValue(row, 'start'), language);
    var end = formatPreviewDate(plainValue(row, 'end'), language);
    var open = formatPreviewTime(plainValue(row, 'open'), language);
    var close = formatPreviewTime(plainValue(row, 'close'), language);
    var closed = plainValue(row, 'type') === 'closed';
    if (language === 'en') {
      if (closed) return 'The park is closed from ' + start + ' to ' + end + '.';
      return 'From ' + start + ' to ' + end + ', the park is open from ' + open + ' to ' + close + '.';
    }
    if (language === 'de') {
      if (closed) return 'Der Park ist vom ' + start + ' bis ' + end + ' geschlossen.';
      return 'Vom ' + start + ' bis ' + end + ' ist der Park von ' + open + ' bis ' + close + ' geöffnet.';
    }
    if (closed) return 'Le parc est fermé du ' + start + ' au ' + end + '.';
    return 'Du ' + start + ' au ' + end + ', le parc est ouvert de ' + open + ' à ' + close + '.';
  }

  function popupPreviewData(trigger, language) {
    var row = trigger ? trigger.closest('.htp-repeat-row') : null;
    if (!row) return null;

    if (row.matches('[data-htp-alert-row]')) {
      return {
        title: translatedValue(row, 'title', language),
        message: translatedValue(row, 'message', language),
        buttonLabel: translatedValue(row, 'button_label', language),
        buttonUrl: translatedValue(row, 'button_url', language),
        showButton: checkedValue(row, 'show_button', false),
        imageUrl: ''
      };
    }

    var isException = !!row.querySelector('[name^="settings[exceptions]"]');
    if (isException) {
      var mode = plainValue(row, 'popup_mode') || 'auto';
      var customTitle = translatedValue(row, 'popup_title', language);
      var customMessage = translatedValue(row, 'popup_message', language);
      var context = translatedValue(row, 'context', language);
      var title = '';
      var message = '';

      if (mode === 'custom') {
        title = customTitle || context;
        message = customMessage;
      } else {
        title = customTitle || context || exceptionDefaultTitle(row, language);
        message = exceptionAutoMessage(row, language);
        if (customMessage) message = (message ? message + '\n' : '') + customMessage;
      }

      return {
        title: title,
        message: message,
        buttonLabel: translatedValue(row, 'popup_button_label', language),
        buttonUrl: translatedValue(row, 'popup_button_url', language),
        showButton: checkedValue(row, 'popup_show_button', false),
        imageUrl: ''
      };
    }

    return {
      title: translatedValue(row, 'popup_title', language) || translatedValue(row, 'title', language),
      message: translatedValue(row, 'popup_message', language) || translatedValue(row, 'message', language),
      buttonLabel: translatedValue(row, 'popup_button_label', language),
      buttonUrl: translatedValue(row, 'popup_button_url', language),
      showButton: checkedValue(row, 'popup_show_button', false),
      imageUrl: plainValue(row, 'popup_image_url')
    };
  }

  function previewPopup(trigger, language) {
    var data = popupPreviewData(trigger, language);
    if (!data) return;
    if (!data.title && !data.message && !data.imageUrl) {
      window.alert('Ajoutez au moins un titre, un message ou une image dans cette langue avant la prévisualisation.');
      return;
    }
    var overlay = document.createElement('div');
    overlay.className = 'htp-admin-popup-preview';
    var opacity = Math.max(0, Math.min(100, parseInt(globalValue('alert_overlay_opacity', '68'), 10) || 0)) / 100;
    var overlayColor = globalValue('alert_overlay_color', '#000000');
    overlay.style.background = hexToRgba(overlayColor, opacity);
    var dialog = document.createElement('div');
    dialog.className = 'htp-admin-popup-dialog';
    dialog.style.background = globalValue('alert_bg_color', '#006757');
    dialog.style.color = globalValue('alert_text_color', '#ffffff');
    dialog.style.borderColor = globalValue('alert_border_color', '#ef7b5b');
    dialog.style.borderWidth = Math.max(0, Math.min(12, parseInt(globalValue('alert_border_width', '3'), 10) || 0)) + 'px';
    dialog.style.borderRadius = Math.max(0, Math.min(40, parseInt(globalValue('alert_radius', '16'), 10) || 0)) + 'px';
    dialog.style.boxShadow = globalChecked('alert_shadow', true) ? '0 18px 55px rgba(0,0,0,.3)' : 'none';
    var close = document.createElement('button');
    close.type = 'button';
    close.className = 'htp-admin-popup-close';
    close.setAttribute('aria-label', language === 'en' ? 'Close' : (language === 'de' ? 'Schließen' : 'Fermer'));
    close.textContent = '×';
    close.style.background = globalValue('alert_close_bg_color', '#ffffff');
    close.style.color = globalValue('alert_close_text_color', '#222222');
    dialog.appendChild(close);
    if (data.imageUrl) {
      var img = document.createElement('img');
      img.className = 'htp-admin-popup-image';
      img.src = data.imageUrl;
      img.alt = '';
      img.addEventListener('error', function () { img.remove(); });
      dialog.appendChild(img);
    }
    if (data.title) {
      var h = document.createElement('h2'); h.textContent = data.title; h.style.color = globalValue('alert_title_color', '#ffffff'); dialog.appendChild(h);
    }
    if (data.message) { var p = document.createElement('p'); p.textContent = data.message; dialog.appendChild(p); }
    if (data.showButton && data.buttonLabel) {
      var a = document.createElement('span'); a.className = 'htp-admin-popup-button'; a.textContent = data.buttonLabel;
      a.style.background = globalValue('alert_button_bg_color', '#ef7b5b');
      a.style.color = globalValue('alert_button_text_color', '#ffffff');
      a.style.borderColor = globalValue('alert_button_border_color', '#ef7b5b');
      if (data.buttonUrl) a.title = data.buttonUrl;
      dialog.appendChild(a);
    }
    overlay.appendChild(dialog); document.body.appendChild(overlay);
    function dismiss(){ overlay.remove(); document.removeEventListener('keydown', esc); }
    function esc(e){ if(e.key === 'Escape') dismiss(); }
    close.addEventListener('click', dismiss); overlay.addEventListener('click', function(e){if(e.target===overlay)dismiss();}); document.addEventListener('keydown',esc); close.focus();
  }

  function hexToRgba(hex, alpha) {
    var value = String(hex || '#000000').replace('#','');
    if (value.length !== 6) return 'rgba(0,0,0,' + alpha + ')';
    var r=parseInt(value.slice(0,2),16), g=parseInt(value.slice(2,4),16), b=parseInt(value.slice(4,6),16);
    return 'rgba(' + r + ',' + g + ',' + b + ',' + alpha + ')';
  }

  function nextIndex(repeater) {
    var indexes = [];
    var templateName = repeater ? String(repeater.getAttribute('data-template') || '') : '';
    var specialShared = templateName === 'htp-template-special-period' || templateName === 'htp-template-event';
    var scope = specialShared ? document : repeater;
    var selector = specialShared ? '[name^="settings[special_periods]["]' : ':scope > .htp-repeater-rows > .htp-repeat-row [name]';
    if (specialShared) {
      scope.querySelectorAll('[name^="settings[special_periods]["]').forEach(function (input) {
        var match = input.name.match(/settings\[special_periods\]\[(\d+)\]/);
        if (match) indexes.push(parseInt(match[1], 10));
      });
    } else {
      scope.querySelectorAll(selector).forEach(function (input) {
        var match = input.name.match(/\[(\d+)\]/);
        if (match) indexes.push(parseInt(match[1], 10));
      });
    }
    return indexes.length ? Math.max.apply(Math, indexes) + 1 : 0;
  }


  function cssEscape(value) {
    if (window.CSS && typeof window.CSS.escape === 'function') return window.CSS.escape(value);
    return String(value).replace(/(["'\\\[\]#.])/g, '\\$1');
  }

  function tariffGroupKey(group) {
    return group ? String(group.getAttribute('data-htp-tariff-group') || '') : '';
  }

  function reindexTariffRows(group) {
    var key = tariffGroupKey(group);
    if (!key) return;
    var rowsWrap = group.querySelector('.htp-tariff-repeater > .htp-repeater-rows');
    if (!rowsWrap) return;
    Array.prototype.forEach.call(rowsWrap.children, function (row, index) {
      if (!row.matches('[data-htp-tariff-row]')) return;
      row.querySelectorAll('[name]').forEach(function (field) {
        var pattern = new RegExp('settings\\[tariffs\\]\\[' + key + '\\]\\[\\d+\\]');
        field.name = field.name.replace(pattern, 'settings[tariffs][' + key + '][' + index + ']');
      });
    });
  }

  function reindexTariffColumns(group) {
    var key = tariffGroupKey(group);
    if (!key) return;
    var list = group.querySelector('[data-htp-tariff-columns]');
    if (!list) return;
    Array.prototype.forEach.call(list.children, function (column, index) {
      if (!column.matches('[data-htp-tariff-column]')) return;
      column.querySelectorAll('[name]').forEach(function (field) {
        var pattern = new RegExp('settings\\[tariffs\\]\\[columns\\]\\[' + key + '\\]\\[(?:\\d+|__COLINDEX__)\\]');
        field.name = field.name.replace(pattern, 'settings[tariffs][columns][' + key + '][' + index + ']');
      });
    });
  }

  function syncSpecialOfferRow(row) {
    if (!row) return;
    var type = row.querySelector('select[name$="[row_type]"]');
    var special = type && type.value === 'special';
    var settings = row.querySelector('[data-htp-special-offer-settings]');
    if (settings) settings.hidden = !special;
    row.querySelectorAll('[data-htp-old-price-field]').forEach(function (field) { field.hidden = !special; });
    row.classList.toggle('is-special-offer-admin', !!special);
  }

  function initSpecialOfferRows(root) {
    (root || document).querySelectorAll('[data-htp-tariff-row]').forEach(syncSpecialOfferRow);
  }

  function tariffColumns(group) {
    var list = group ? group.querySelector('[data-htp-tariff-columns]') : null;
    if (!list) return [];
    return Array.prototype.map.call(list.querySelectorAll(':scope > [data-htp-tariff-column]'), function (column) {
      var idInput = column.querySelector('[data-htp-column-id-input]');
      var frLabel = column.querySelector('input[name$="[label][fr]"]');
      return {
        id: idInput ? String(idInput.value || '') : String(column.getAttribute('data-col-id') || ''),
        label: frLabel && frLabel.value ? frLabel.value : 'Prix'
      };
    }).filter(function (column) { return column.id !== ''; });
  }

  function tariffRowIndex(row, groupKey) {
    var named = row ? row.querySelector('[name^="settings[tariffs][' + groupKey + ']["]') : null;
    if (!named) return 0;
    var match = named.name.match(new RegExp('settings\\[tariffs\\]\\[' + groupKey + '\\]\\[(\\d+)\\]'));
    return match ? parseInt(match[1], 10) : 0;
  }

  function makeTariffCell(row, group, column) {
    var key = tariffGroupKey(group);
    var index = tariffRowIndex(row, key);
    var special = !!(row.querySelector('select[name$="[row_type]"]') && row.querySelector('select[name$="[row_type]"]').value === 'special');
    var cell = document.createElement('div');
    cell.className = 'htp-tariff-cell-fields';
    cell.setAttribute('data-htp-tariff-cell', '');
    cell.setAttribute('data-col-id', column.id);
    var base = 'settings[tariffs][' + key + '][' + index + '][cells][' + column.id + ']';
    cell.innerHTML = '<strong class="htp-tariff-cell-title">' + escapeHtml(column.label) + '</strong>' +
      '<label class="htp-field"><span>Prix / valeur</span><input type="text" name="' + escapeHtml(base + '[value]') + '" value=""></label>' +
      '<div data-htp-old-price-field' + (special ? '' : ' hidden') + '><label class="htp-field"><span>Ancien prix à barrer (facultatif)</span><input type="text" name="' + escapeHtml(base + '[old_value]') + '" value=""></label></div>';
    return cell;
  }

  function escapeHtml(value) {
    return String(value == null ? '' : value).replace(/[&<>"']/g, function (char) {
      return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[char];
    });
  }

  function syncTariffCells(group) {
    if (!group) return;
    reindexTariffRows(group);
    reindexTariffColumns(group);
    var columns = tariffColumns(group);
    var validIds = columns.map(function (column) { return column.id; });
    var rowsWrap = group.querySelector('.htp-tariff-repeater > .htp-repeater-rows');
    if (!rowsWrap) return;
    rowsWrap.querySelectorAll(':scope > [data-htp-tariff-row]').forEach(function (row) {
      var grid = row.querySelector('[data-htp-tariff-cells]');
      if (!grid) return;
      grid.querySelectorAll(':scope > [data-htp-tariff-cell]').forEach(function (cell) {
        if (validIds.indexOf(String(cell.getAttribute('data-col-id') || '')) === -1) cell.remove();
      });
      columns.forEach(function (column) {
        var cell = Array.prototype.find.call(grid.querySelectorAll(':scope > [data-htp-tariff-cell]'), function (candidate) {
          return candidate.getAttribute('data-col-id') === column.id;
        });
        if (!cell) cell = makeTariffCell(row, group, column);
        var title = cell.querySelector('.htp-tariff-cell-title');
        if (title) title.textContent = column.label;
        grid.appendChild(cell);
      });
      syncSpecialOfferRow(row);
    });
  }

  function syncAllTariffGroups() {
    document.querySelectorAll('[data-htp-tariff-group]').forEach(syncTariffCells);
  }

  function moveWithinContainer(element, direction) {
    if (!element || !element.parentElement) return false;
    var sibling = direction === 'up' ? element.previousElementSibling : element.nextElementSibling;
    if (!sibling) return false;
    if (direction === 'up') element.parentElement.insertBefore(element, sibling);
    else element.parentElement.insertBefore(sibling, element);
    return true;
  }

  function refreshAfterTariffMove(element) {
    var group = element ? element.closest('[data-htp-tariff-group]') : null;
    if (element && element.matches('[data-htp-tariff-group]')) {
      syncAllTariffGroups();
      return;
    }
    if (group) syncTariffCells(group);
  }

  function generateColumnId() {
    return 'col_' + Date.now().toString(36) + '_' + Math.random().toString(36).slice(2, 7);
  }

  function addTariffColumn(group) {
    if (!group) return;
    var list = group.querySelector('[data-htp-tariff-columns]');
    var template = group.querySelector('.htp-template-tariff-column');
    if (!list || !template) return;
    var id = generateColumnId();
    var index = list.querySelectorAll(':scope > [data-htp-tariff-column]').length;
    var html = template.innerHTML.replace(/__COLINDEX__/g, String(index)).replace(/__COLID__/g, id);
    list.insertAdjacentHTML('beforeend', html);
    var added = list.lastElementChild;
    if (added) {
      added.setAttribute('data-col-id', id);
      var input = added.querySelector('[data-htp-column-id-input]');
      if (input) input.value = id;
      initLocalTranslations(added);
    }
    syncTariffCells(group);
  }

  function removeTariffColumn(button) {
    var column = button ? button.closest('[data-htp-tariff-column]') : null;
    var group = column ? column.closest('[data-htp-tariff-group]') : null;
    if (!column || !group) return;
    var list = group.querySelector('[data-htp-tariff-columns]');
    if (list && list.querySelectorAll(':scope > [data-htp-tariff-column]').length <= 1) {
      window.alert('Gardez au moins une colonne de prix. La colonne du nom du tarif reste toujours présente séparément.');
      return;
    }
    if (!window.confirm('Supprimer cette colonne et toutes ses valeurs ?')) return;
    column.remove();
    syncTariffCells(group);
  }

  function duplicateTariffRow(button) {
    var row = button ? button.closest('[data-htp-tariff-row]') : null;
    var group = row ? row.closest('[data-htp-tariff-group]') : null;
    if (!row || !group) return;
    var clone = row.cloneNode(true);
    clone.querySelectorAll('[data-htp-local-ready]').forEach(function (el) { el.removeAttribute('data-htp-local-ready'); });
    row.parentElement.insertBefore(clone, row.nextSibling);
    reindexTariffRows(group);
    initLocalTranslations(clone);
    initOptionalColors(clone);
    syncTariffCells(group);
    clone.scrollIntoView({ behavior: 'smooth', block: 'center' });
  }

  function initTariffSortables() {
    if (!window.jQuery || !window.jQuery.fn || typeof window.jQuery.fn.sortable !== 'function') return;
    var $ = window.jQuery;
    var groups = document.querySelector('[data-htp-tariff-groups]');
    if (groups && !groups.dataset.htpSortableReady) {
      groups.dataset.htpSortableReady = '1';
      $(groups).sortable({ items: '> [data-htp-tariff-group]', handle: '.htp-sort-handle-group', tolerance: 'pointer', update: syncAllTariffGroups });
    }
    document.querySelectorAll('[data-htp-tariff-group]').forEach(function (group) {
      var rows = group.querySelector('.htp-tariff-repeater > .htp-repeater-rows');
      if (rows && !rows.dataset.htpSortableReady) {
        rows.dataset.htpSortableReady = '1';
        $(rows).sortable({ items: '> [data-htp-tariff-row]', handle: '.htp-sort-handle-row', tolerance: 'pointer', update: function(){ syncTariffCells(group); } });
      }
      var columns = group.querySelector('[data-htp-tariff-columns]');
      if (columns && !columns.dataset.htpSortableReady) {
        columns.dataset.htpSortableReady = '1';
        $(columns).sortable({ items: '> [data-htp-tariff-column]', handle: '.htp-sort-handle-column', tolerance: 'pointer', update: function(){ syncTariffCells(group); } });
      }
    });
  }


  function initQuoteSortables() {
    if (!window.jQuery || !window.jQuery.fn || typeof window.jQuery.fn.sortable !== 'function') return;
    window.jQuery('[data-htp-quote-sortable]').each(function(){
      var el=this;
      if (window.jQuery(el).hasClass('ui-sortable')) return;
      window.jQuery(el).sortable({items:'> .htp-quote-row',handle:'.htp-sort-handle-quote',tolerance:'pointer'});
    });
  }

  document.addEventListener('change', function (event) {
    if (event.target.matches('input[type="checkbox"][name$="[show_popup]"]')) { syncPopupBlock(event.target.closest('[data-htp-popup-block]')); }
    if (event.target.matches('input[type="checkbox"][name$="[show_button]"], input[type="checkbox"][name$="[popup_show_button]"]')) { syncButtonBlock(event.target.closest('[data-htp-button-block]')); }
    if (event.target.matches('input[type="checkbox"][name$="[show_tooltip]"]')) { syncDomainTooltipBlock(event.target.closest('[data-htp-domain-tooltip-block]')); }
    if (event.target.matches('select[name$="[row_type]"]')) { syncSpecialOfferRow(event.target.closest('[data-htp-tariff-row]')); }
    if (event.target.matches('[data-htp-weekdays] input[type="checkbox"]')) {
      syncWeekdays(event.target.closest('[data-htp-weekdays]'), true);
    }
    var inherit = event.target.closest('.htp-optional-color-inherit');
    if (inherit) {
      syncOptionalColor(inherit.closest('.htp-optional-color'));
      return;
    }
    var picker = event.target.closest('.htp-optional-color-picker');
    if (picker) syncOptionalColor(picker.closest('.htp-optional-color'));
  });

  document.addEventListener('click', function (event) {
    var addColumn = event.target.closest('[data-htp-add-column]');
    if (addColumn) { addTariffColumn(addColumn.closest('[data-htp-tariff-group]')); return; }

    var removeColumn = event.target.closest('[data-htp-remove-column]');
    if (removeColumn) { removeTariffColumn(removeColumn); return; }

    var duplicateTariff = event.target.closest('[data-htp-duplicate-tariff-row]');
    if (duplicateTariff) { duplicateTariffRow(duplicateTariff); return; }

    var moveButton = event.target.closest('[data-htp-move]');
    if (moveButton) {
      var movable = moveButton.closest('[data-htp-tariff-column], [data-htp-tariff-row], [data-htp-tariff-group]');
      if (moveWithinContainer(movable, moveButton.getAttribute('data-htp-move'))) refreshAfterTariffMove(movable);
      return;
    }

    var popupTest = event.target.closest('[data-htp-popup-test]');
    if (popupTest) {
      var actions = popupTest.closest('.htp-popup-preview-actions');
      var languageSelect = actions ? actions.querySelector('[data-htp-popup-preview-language]') : null;
      previewPopup(popupTest, languageSelect ? languageSelect.value : 'fr');
      return;
    }

    var popupPreview = event.target.closest('[data-htp-popup-preview]');
    if (popupPreview) {
      previewPopup(popupPreview, popupPreview.getAttribute('data-htp-popup-preview') || 'fr');
      return;
    }

    var addButton = event.target.closest('.htp-add-row');
    if (addButton) {
      var repeater = addButton.closest('.htp-repeater');
      var template = document.getElementById(repeater.getAttribute('data-template'));
      if (!template) return;
      var html = template.innerHTML.replace(/__INDEX__/g, String(nextIndex(repeater)));
      repeater.querySelector(':scope > .htp-repeater-rows').insertAdjacentHTML('beforeend', html);
      initLocalTranslations(repeater);
      initOptionalColors(repeater);
      initPopupBlocks(repeater);
      initButtonBlocks(repeater);
      initDomainTooltipBlocks(repeater);
      initQuoteSortables();
      var rows = repeater.querySelectorAll(':scope > .htp-repeater-rows > .htp-repeat-row');
      if (rows.length) {
        var newRow = rows[rows.length - 1];
        hydrateAllWeekdays(newRow);
        var tariffGroup = repeater.closest('[data-htp-tariff-group]');
        if (tariffGroup) { syncTariffCells(tariffGroup); syncSpecialOfferRow(newRow); }
        newRow.scrollIntoView({ behavior: 'smooth', block: 'center' });
      }
      return;
    }

    var removeButton = event.target.closest('.htp-remove-row');
    if (removeButton) {
      var row = removeButton.closest('.htp-repeat-row');
      if (row && window.confirm('Supprimer cette ligne ? La suppression sera définitive après enregistrement.')) row.remove();
      return;
    }

    var previewButton = event.target.closest('[data-htp-preview-button]');
    if (previewButton) {
      var input = document.querySelector('[data-htp-preview-date]');
      var result = document.querySelector('[data-htp-preview-result]');
      if (!input.value || !window.ParcsHTP) { result.textContent = 'Choisissez une date valide.'; return; }
      var status = window.ParcsHTP.resolveDay(input.value);
      var ranges = status.open && window.ParcsHTP.dayRanges ? window.ParcsHTP.dayRanges(status, 'fr') : '';
      var lines = status.open ? ['Ouvert : ' + (ranges || (status.openTime + '–' + status.closeTime)), 'Dernière entrée : ' + window.ParcsHTP.lastEntryTime(status)] : ['Parc fermé'];
      if (status.exceptional) lines.push(status.type === 'closed' ? 'Fermeture exceptionnelle prioritaire.' : 'Horaires exceptionnels prioritaires.');
      var domain = status.open ? window.ParcsHTP.domainRule(input.value) : null;
      if (domain) lines.push('Une information discrète concernant le domaine sera affichée après le clic sur cette date.');
      result.innerHTML = lines.map(function (line) { return '<div>' + line.replace(/[&<>"']/g, function (char) { return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[char]; }) + '</div>'; }).join('');
    }
  });


  document.addEventListener('input', function (event) {
    if (event.target.matches('[data-htp-tariff-column] input[name$="[label][fr]"]')) {
      var group = event.target.closest('[data-htp-tariff-group]');
      if (group) syncTariffCells(group);
    }
  });


  document.addEventListener('DOMContentLoaded', function () {
    initOptionalColors(document);
    initLocalTranslations(document);
    hydrateAllWeekdays(document);
    initPopupBlocks(document);
    initButtonBlocks(document);
    initDomainTooltipBlocks(document);
    initSpecialOfferRows(document);
    syncAllTariffGroups();
    initTariffSortables();
    initQuoteSortables();
    initAdminTabs();
    initScopedSave();
    document.querySelectorAll('.htp-delete-season-form').forEach(function(form){form.addEventListener('submit',function(e){if(!window.confirm('Supprimer cette saison et toutes ses données ? Cette action est irréversible.'))e.preventDefault();});});
  });
}());
