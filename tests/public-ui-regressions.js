const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');
const assert = require('node:assert/strict');
const { JSDOM } = require('jsdom');
const jquery = require('jquery');
const source = name => fs.readFileSync(path.join(process.env.PLUGIN_ROOT || path.join(__dirname, '..'), 'assets', name), 'utf8');
const pause = () => new Promise(resolve => setTimeout(resolve, 15));

async function calendarLoop() {
  const dom = new JSDOM('<section data-htp-component="calendar" data-htp-lang="fr"><button data-htp-month="2026-08" aria-selected="true"></button><div data-htp-month-summary></div><button class="parcs-ht-day is-selected" data-htp-date="2026-08-31"></button><div data-htp-day-detail><p class="parcs-ht-day-hours"></p></div></section>', { runScripts: 'outside-only' });
  const w = dom.window;
  let resolves = 0;
  const frames = [];
  const status = { open: true, slots: [{ open: '10:00', close: '12:00' }, { open: '14:00', close: '18:00' }], period: { last_entry_minutes_slot1: '30', last_entry_minutes_slot2: '45' } };
  w.ParcsHTPData = { settings: { timezone: 'Europe/Paris', general: {} }, dictionary: { fr: { monthHours: 'Horaires : {hours}' } } };
  w.ParcsHTP = { resolveDay: () => { resolves++; return status; }, resolveAnyDay: () => status };
  w.requestAnimationFrame = callback => { frames.push(callback); return frames.length; };
  w.eval(source('slot-last-entry-frontend.js'));
  await pause();
  // A single normal UI mutation starts the observer. No further user actions follow.
  w.document.querySelector('[data-htp-month-summary]').textContent = 'one UI change';
  await pause();
  const before = resolves;
  let count = 0;
  while (frames.length && count < 20) {
    frames.shift()(); count++;
    await Promise.resolve(); await Promise.resolve();
  }
  assert.equal(frames.length, 0, 'calendar observer must become idle');
  assert.ok(count <= 2, 'a mutation must not trigger a refresh loop');
  assert.ok(resolves - before <= 2 * 32);
  assert.match(w.document.querySelector('.parcs-ht-day-last').textContent, /11 h 30.*17 h 15/);
  assert.match(w.document.querySelector('[data-htp-month-summary]').textContent, /tout le mois/);
  // A real month change must still update the summary and settle.
  w.document.querySelector('[data-htp-month]').setAttribute('data-htp-month', '2026-09');
  w.document.querySelector('[data-htp-month]').setAttribute('aria-selected', 'true');
  await pause();
  count = 0;
  while (frames.length && count++ < 20) { frames.shift()(); await Promise.resolve(); }
  assert.equal(frames.length, 0, 'month changes must settle as well');
  assert.ok(resolves > before);
  console.log('PASS: calendar mutations settle and month changes still render');
  w.close();
}

function closedDay() {
  let lookups = 0;
  const sandbox = { window: { ParcsHTPData: { settings: {} }, ParcsHTP: {
    resolveAnyDay: () => ({ open: false, exceptional: false, slots: [] }),
    nextOpeningAcrossSeasons: () => { lookups++; return { date: '2026-09-03', status: { openTime: '10:00' } }; }
  } }, Intl, Date };
  vm.createContext(sandbox);
  vm.runInContext(source('display-state.js'), sandbox);
  const result = {};
  for (const lang of ['fr', 'en', 'de']) {
    const state = sandbox.window.ParcsHTPDisplayState.state('2026-09-02', '12:00', lang, true);
    assert.equal(state.statusText, {fr:'À demain !',en:'See you tomorrow!',de:'Bis morgen!'}[lang]);
    assert.ok(state.hoursText);
    result[lang] = { status: state.statusText, hours: state.hoursText };
  }
  assert.equal(lookups, 3);
  console.log(JSON.stringify({ test: 'closed-day-next-opening-and-language', result, nextOpeningCalls: lookups, verdict: 'PASS' }));
}

async function staleDate() {
  const dom = new JSDOM('<section class="parcs-ht-quote" data-htp-lang="fr"><div class="parcs-ht-quote-form-wrap"><div class="parcs-ht-quote-form"><div class="wpcf7"><form><input name="visite" type="date"></form></div></div></div></section>', { runScripts: 'outside-only' });
  const w = dom.window;
  const $ = jquery(w);
  w.jQuery = $;
  w.ParcsHTQuoteGate = { ajaxUrl: '/not-called', nonce: 'synthetic', visitField: 'visite', unavailableEnabled: true, unavailableMessage: 'Tarifs indisponibles' };
  const requests = [];
  $.post = (url, data) => { const deferred = $.Deferred(); requests.push({ data, deferred }); return deferred.promise(); };
  w.eval(source('quote-gate.js'));
  await pause();
  const input = $('.parcs-ht-quote-access-date');
  input.val('2026-09-01').trigger('change');
  input.val('2027-09-01').trigger('change');
  assert.equal(requests.length, 2);
  requests[1].deferred.resolve({ success: true, data: { tariffs: false, closed: false, year: '2027' } });
  requests[0].deferred.resolve({ success: true, data: { tariffs: true, closed: false, year: '2026' } });
  const result = { selectedDate: input.val(), submittedDate: $('[name="visite"]').val(), formOpen: $('.parcs-ht-quote').hasClass('parcs-ht-gate-open') };
  assert.equal(result.selectedDate, '2027-09-01');
  assert.equal(result.submittedDate, '2027-09-01');
  assert.equal(result.formOpen, false);
  input.val('2026-09-02').trigger('change');
  requests[2].deferred.resolve({ success: true, data: { tariffs: true } });
  assert.equal($('[name="visite"]').val(), '2026-09-02');
  assert.equal($('.parcs-ht-quote').hasClass('parcs-ht-gate-open'), true);
  input.val('2026-09-03').trigger('change');
  input.val('').trigger('change');
  assert.equal($('[name="visite"]').val(), '', 'clearing the selector also clears the submitted date');
  requests[3].deferred.resolve({ success: true, data: { tariffs: true } });
  assert.equal($('.parcs-ht-quote').hasClass('parcs-ht-gate-open'), false, 'clearing a date invalidates in-flight responses');
  input.val('2026-09-04').trigger('change');
  input.val('2026-09-05').trigger('change');
  requests[5].deferred.resolve({ success: true, data: { tariffs: true } });
  requests[4].deferred.reject();
  assert.equal($('.parcs-ht-quote').hasClass('parcs-ht-gate-open'), true, 'stale network errors must not close the latest date');
  assert.equal($('[name="visite"]').val(), '2026-09-05');
  console.log(JSON.stringify({ test: 'out-of-order-quote-responses', ...result, result: 'PASS' }));
  w.close();
}

async function exceptionErased() {
  const dom = new JSDOM('<section data-htp-component="today" data-htp-lang="fr"><h3 data-htp-today-status>OUVERT</h3><p data-htp-today-detail>Horaires exceptionnels · Forte chaleur</p></section>', { runScripts: 'outside-only' });
  const w = dom.window;
  const status = { open: true, exceptional: true, type: 'hours', slots: [{ open: '00:00', close: '23:59' }], exception: { show_public_marker: '1', context: { fr: 'Forte chaleur' } } };
  w.ParcsHTPData = { settings: { timezone: 'Europe/Paris', general: { last_entry_minutes: '30' } } };
  w.ParcsHTP = { resolveAnyDay: () => status };
  w.eval(source('display-state.js'));
  w.eval(source('status-sync.js'));
  w.ParcsHTPStatusSync.refresh();
  const detail = w.document.querySelector('[data-htp-today-detail]').textContent;
  assert.ok(detail.includes('Forte chaleur'));
  assert.ok(detail.includes('Horaires exceptionnels'));
  status.exception.show_public_marker = '0';
  w.ParcsHTPStatusSync.refresh();
  assert.ok(!w.document.querySelector('[data-htp-today-detail]').textContent.includes('Forte chaleur'));
  status.exception.show_public_marker = '1';
  status.open = false;
  w.ParcsHTPStatusSync.refresh();
  assert.match(w.document.querySelector('[data-htp-today-detail]').textContent, /Fermeture exceptionnelle.*Forte chaleur/);
  console.log(JSON.stringify({ test: 'exception-context-erased', detail, result: 'PASS' }));
  w.close();
}

(async () => { closedDay(); await calendarLoop(); await staleDate(); await exceptionErased(); })().catch(error => { console.error(error); process.exit(1); });
