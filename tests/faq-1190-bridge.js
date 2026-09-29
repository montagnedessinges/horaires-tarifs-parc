'use strict';
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');
const assert = require('node:assert/strict');
const root = process.env.PLUGIN_ROOT || path.join(__dirname, '..');
const source = fs.readFileSync(path.join(root, 'assets/faq-google-sheet-apps-script.txt'), 'utf8');
if (!process.env.PLUGIN_ROOT) assert.equal(source, fs.readFileSync(path.join(root,'docs/FAQ-GOOGLE-SHEET-APPS-SCRIPT.gs'), 'utf8'));
assert.deepEqual(JSON.parse(fs.readFileSync(path.join(root,'assets/faq-google-sheet-manifest.txt'), 'utf8')).oauthScopes, ['https://www.googleapis.com/auth/spreadsheets.readonly']);
const props = new Map();
let reads = [], denied = false, missing = false;
const headers = ['ID stable','Question canonique FR','Réponse courte FR','Statut','Usage / visibilité','Notes / garde-fou','Private column'];
let values = [headers, ['MDS-TEST-001','Question','Réponse','Validé','FAQ publique','internal','private'], ['FDS-TEST-001','Frage','Antwort','Validé','FAQ publique','secret','private']];
const context = vm.createContext({
    PropertiesService: {getScriptProperties: () => ({getProperty: k => props.get(k), setProperty: (k,v) => props.set(k,v)})},
    Utilities: {getUuid: () => '12345678-abcd-abcd-abcd-123456789012'},
    SpreadsheetApp: {openById: id => { reads.push(id); if (denied) throw Error('PRIVATE ERROR'); return {getSheetByName: () => missing ? null : {getDataRange: () => ({getDisplayValues: () => values})}}; }},
    ContentService: {MimeType: {JSON:'json'}, createTextOutput: value => ({setMimeType: () => JSON.parse(value)})}
});
vm.runInContext(source, context);
context.installerConfiguration();
const secret = props.get('FAQ_SHARED_SECRET');
const payload = {action:'faq_export',schema_version:1,secret,spreadsheet_id:'a'.repeat(30),tab:'Montagne des Singes',park_code:'MDS'};
const post = changes => context.doPost({postData:{contents:JSON.stringify({...payload,...changes})}});
assert.equal(context.doGet().ok,false);
assert.equal(post({secret:'bad'}).ok,false);
assert.equal(reads.length,0,'Unauthenticated read');
assert.equal(post({tab:'Internal'}).ok,false);
assert.equal(post({park_code:'FDS'}).ok,false);
assert.equal(post({schema_version:99}).ok,false);
assert.equal(post({spreadsheet_id:'https://example.org'}).ok,false);
let response = post({});
assert.equal(response.ok,true);
assert.equal(response.records.length,1);
assert.equal(response.records[0]['Private column'],undefined);
assert.equal(response.records[0]['Notes / garde-fou'],undefined);
assert.equal(post({spreadsheet_id:'b'.repeat(30)}).spreadsheet_id,'b'.repeat(30));
assert.equal(props.size,1,'Export must not change persistent source properties');
assert.equal(post({park_code:'FDS',tab:'Forêt des Singes'}).records[0]['ID stable'],'FDS-TEST-001');
denied=true; assert.equal(post({}).ok,false); assert.ok(!JSON.stringify(post({})).includes('PRIVATE')); denied=false;
missing=true; assert.equal(post({}).ok,false); missing=false;
values.push(values[1]); assert.equal(post({}).ok,false); values.pop();
values=[['ID stable'],['MDS-TEST-001']]; assert.equal(post({}).ok,false);
assert.equal(context.doPost({postData:{contents:'invalid'}}).ok,false);
console.log('FAQ Apps Script OK: auth, source switching, read-only scope, park/column isolation and schema validation.');
