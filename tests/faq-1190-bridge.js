'use strict';
const fs = require('node:fs');
const path = require('node:path');
const assert = require('node:assert/strict');
const root = process.env.PLUGIN_ROOT || path.join(__dirname, '..');
const csv = fs.readFileSync(path.join(root, 'includes/class-parcs-ht-faq-csv-1191.php'), 'utf8');

for (const removed of [
  'assets/faq-google-sheet-apps-script.txt',
  'assets/faq-google-sheet-manifest.txt',
  'docs/FAQ-GOOGLE-SHEET-APPS-SCRIPT.gs',
  'docs/FAQ-GOOGLE-SHEET.md'
]) {
  assert.equal(fs.existsSync(path.join(root, removed)), false, `${removed} must be removed`);
}

for (const required of [
  'admin_post_parcs_ht_faq_csv_preview',
  'admin_post_parcs_ht_faq_csv_apply',
  'fgetcsv(',
  'is_uploaded_file',
  'ID stable',
  'Question canonique FR',
  'Réponse courte FR',
  'Usage / visibilité',
  'Frage DE',
  'Kurzantwort DE',
  'Question EN',
  'Short answer EN',
  'Avant import CSV FAQ',
  'Les fiches absentes du CSV n’ont pas été supprimées'
]) assert.ok(csv.includes(required), `Missing CSV contract: ${required}`);

for (const forbidden of ['script.google.com', 'FAQ_SHARED_SECRET', 'wp_safe_remote_post']) {
  assert.equal(csv.includes(forbidden), false, `Google bridge leaked into CSV workflow: ${forbidden}`);
}

console.log('FAQ CSV bridge OK: deprecated Apps Script assets removed and local CSV workflow enforced.');
