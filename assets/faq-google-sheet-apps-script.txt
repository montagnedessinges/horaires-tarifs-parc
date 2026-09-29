/**
 * Gestion du parc 1.19.0 — pont FAQ Google Sheet -> WordPress.
 * Coller ce code dans Extensions > Apps Script du Google Sheet FAQ.
 */

const FAQ_SCHEMA_VERSION = 1;
const FAQ_ALLOWED_TABS = ['Montagne des Singes', 'Forêt des Singes'];

function installerConfiguration() {
  const properties = PropertiesService.getScriptProperties();
  let secret = properties.getProperty('FAQ_SHARED_SECRET');
  if (!secret) {
    secret = Utilities.getUuid().replace(/-/g, '') + Utilities.getUuid().replace(/-/g, '');
    properties.setProperty('FAQ_SHARED_SECRET', secret);
  }

  return 'Configuration prête. Copiez FAQ_SHARED_SECRET dans Paramètres du projet > Propriétés du script.';
}

function regenererCleSecrete() {
  const properties = PropertiesService.getScriptProperties();
  const secret = Utilities.getUuid().replace(/-/g, '') + Utilities.getUuid().replace(/-/g, '');
  properties.setProperty('FAQ_SHARED_SECRET', secret);
  return 'Clé renouvelée. Copiez FAQ_SHARED_SECRET depuis les propriétés du script dans WordPress.';
}

function doGet() {
  return jsonResponse_({ ok: false, error: 'Utilisez une requête POST authentifiée depuis Gestion du parc.' });
}

function doPost(event) {
  try {
    const payload = parsePayload_(event);
    if (String(payload.action || '') !== 'faq_export') {
      return jsonResponse_({ ok: false, error: 'Action inconnue.' });
    }

    const properties = PropertiesService.getScriptProperties();
    const expectedSecret = String(properties.getProperty('FAQ_SHARED_SECRET') || '');
    const spreadsheetId = String(payload.spreadsheet_id || '');
    if (!expectedSecret) {
      return jsonResponse_({ ok: false, error: 'Le script n’est pas configuré. Lancez installerConfiguration().' });
    }
    if (!safeEquals_(String(payload.secret || ''), expectedSecret)) {
      return jsonResponse_({ ok: false, error: 'Clé secrète invalide.' });
    }

    if (payload.schema_version !== FAQ_SCHEMA_VERSION || !/^[a-zA-Z0-9_-]{20,100}$/.test(spreadsheetId)) {
      return jsonResponse_({ ok: false, error: 'Source ou version de protocole invalide.' });
    }

    const tab = String(payload.tab || '');
    if (FAQ_ALLOWED_TABS.indexOf(tab) === -1) {
      return jsonResponse_({ ok: false, error: 'Onglet non autorisé.' });
    }

    const expectedParkCode = tab === 'Forêt des Singes' ? 'FDS' : 'MDS';
    const requestedParkCode = String(payload.park_code || expectedParkCode).toUpperCase();
    if (requestedParkCode !== expectedParkCode) {
      return jsonResponse_({ ok: false, error: 'Le parc demandé ne correspond pas à l’onglet sélectionné.' });
    }

    const spreadsheet = SpreadsheetApp.openById(spreadsheetId);
    const sheet = spreadsheet.getSheetByName(tab);
    if (!sheet) {
      return jsonResponse_({ ok: false, error: 'Onglet introuvable : ' + tab });
    }

    const values = sheet.getDataRange().getDisplayValues();
    const headerIndex = findHeaderRow_(values);
    if (headerIndex < 0) {
      return jsonResponse_({ ok: false, error: 'La ligne d’en-tête contenant « ID stable » est introuvable.' });
    }

    const headers = values[headerIndex].map(function (value) { return String(value || '').trim(); });
    const required = ['ID stable', 'Question canonique FR', 'Réponse courte FR', 'Statut', 'Usage / visibilité'];
    if (required.some(function (header) { return headers.indexOf(header) === -1; }) || headers.some(function (header, index) { return header && headers.indexOf(header) !== index; })) {
      return jsonResponse_({ ok: false, error: 'Colonnes requises manquantes ou dupliquées.' });
    }
    const allowed = required.concat(['Priorité', 'Catégorie', 'Donnée dynamique ?', 'Source principale', 'Vérifié le', 'Mode FAQ', 'Lien public']);
    ['FR', 'EN', 'DE'].forEach(function (lang) {
      ['Question canonique ', 'Réponse courte ', 'Variantes / formulations IA ', 'Catégorie ', 'Libellé du lien '].forEach(function (prefix) { allowed.push(prefix + lang); });
    });
    const records = [];
    const seen = Object.create(null);
    for (let rowIndex = headerIndex + 1; rowIndex < values.length; rowIndex++) {
      const row = values[rowIndex];
      const record = {};
      let hasValue = false;
      headers.forEach(function (header, columnIndex) {
        if (!header || allowed.indexOf(header) === -1) return;
        const value = String(row[columnIndex] || '').trim();
        if (value) hasValue = true;
        record[header] = value;
      });
      if (!hasValue) continue;
      const stableId = String(record['ID stable'] || '').trim().toUpperCase();
      if (!stableId || stableId.indexOf(expectedParkCode + '-') !== 0) continue;
      if (seen[stableId]) return jsonResponse_({ ok: false, error: 'ID stable dupliqué.' });
      seen[stableId] = true;
      records.push(record);
    }

    return jsonResponse_({
      ok: true,
      schema_version: FAQ_SCHEMA_VERSION,
      spreadsheet_id: spreadsheetId,
      tab: tab,
      park_code: expectedParkCode,
      checked_at: new Date().toISOString(),
      headers: headers.filter(function (header) { return allowed.indexOf(header) !== -1; }),
      records: records
    });
  } catch (error) {
    return jsonResponse_({ ok: false, error: 'Lecture impossible. Vérifiez l’accès du compte Google au fichier demandé.' });
  }
}

function parsePayload_(event) {
  if (!event || !event.postData || !event.postData.contents) return {};
  try {
    return JSON.parse(event.postData.contents);
  } catch (error) {
    return {};
  }
}

function findHeaderRow_(values) {
  const limit = Math.min(values.length, 15);
  for (let index = 0; index < limit; index++) {
    const row = values[index] || [];
    for (let column = 0; column < row.length; column++) {
      if (String(row[column] || '').trim().toLowerCase() === 'id stable') return index;
    }
  }
  return -1;
}

function safeEquals_(a, b) {
  a = String(a || '');
  b = String(b || '');
  if (a.length !== b.length) return false;
  let result = 0;
  for (let index = 0; index < a.length; index++) {
    result |= a.charCodeAt(index) ^ b.charCodeAt(index);
  }
  return result === 0;
}

function jsonResponse_(payload) {
  return ContentService
    .createTextOutput(JSON.stringify(payload))
    .setMimeType(ContentService.MimeType.JSON);
}
