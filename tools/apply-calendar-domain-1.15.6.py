from pathlib import Path


def replace_once(path, old, new, label):
    p = Path(path)
    text = p.read_text()
    count = text.count(old)
    if count != 1:
        raise SystemExit(f"{label}: expected 1 match, got {count}")
    p.write_text(text.replace(old, new, 1))


# Version.
p = Path("horaires-tarifs-parc.php")
text = p.read_text()
if text.count("1.15.5") != 2:
    raise SystemExit("unexpected plugin version occurrences")
p.write_text(text.replace("1.15.5", "1.15.6"))

# Non-destructive schema migration and editable labels/messages.
p = Path("includes/class-parcs-ht-defaults.php")
text = p.read_text()
if "const SCHEMA_VERSION = 26;" not in text:
    raise SystemExit("schema 26 marker missing")
text = text.replace("const SCHEMA_VERSION = 26;", "const SCHEMA_VERSION = 27;", 1)
call = "            $saved = self::upgrade_v172_structures($saved, $version);\n"
if text.count(call) != 1:
    raise SystemExit("upgrade_v172 saved call not found exactly once")
text = text.replace(call, call + "            $saved = self::upgrade_v156_structures($saved, $version);\n", 1)
marker = "    private static function upgrade_v172_structures($settings, $from_version) {"
if text.count(marker) != 1:
    raise SystemExit("upgrade_v172 function marker not found exactly once")
migration = r'''    private static function upgrade_v156_structures($settings, $from_version) {
        unset($from_version);
        if (!is_array($settings)) return $settings;
        if (!isset($settings['general']) || !is_array($settings['general'])) $settings['general'] = array();
        if (!isset($settings['general']['calendar_hours_title']) || !is_array($settings['general']['calendar_hours_title'])) {
            $settings['general']['calendar_hours_title'] = array(
                'fr' => 'Horaires du parc',
                'en' => 'Park opening hours',
                'de' => 'Öffnungszeiten des Parks',
            );
        }
        $site_type = sanitize_key((string)($settings['site_type'] ?? ''));
        $highlight = sanitize_hex_color((string)($settings['general']['highlight_color'] ?? '')) ?: '#e7c55b';
        if (isset($settings['seasons']) && is_array($settings['seasons'])) {
            foreach ($settings['seasons'] as &$season) {
                if (!is_array($season) || !isset($season['domain_rules']) || !is_array($season['domain_rules'])) continue;
                foreach ($season['domain_rules'] as &$rule) {
                    if (!is_array($rule)) continue;
                    if (empty($rule['color']) || !sanitize_hex_color((string)$rule['color'])) $rule['color'] = $highlight;
                    if (!isset($rule['access_message']) || !is_array($rule['access_message'])) {
                        $rule['access_message'] = $site_type === 'mds'
                            ? array(
                                'fr' => 'Le Domaine des Singes n’est pas accessible de {pause_start} à {resume}.',
                                'en' => 'The monkey area is not accessible from {pause_start} to {resume}.',
                                'de' => 'Der Affenbereich ist von {pause_start} bis {resume} nicht zugänglich.',
                            )
                            : array(
                                'fr' => 'Cette zone n’est pas accessible de {pause_start} à {resume}.',
                                'en' => 'This area is not accessible from {pause_start} to {resume}.',
                                'de' => 'Dieser Bereich ist von {pause_start} bis {resume} nicht zugänglich.',
                            );
                    }
                    if (!isset($rule['details_message']) || !is_array($rule['details_message'])) {
                        $rule['details_message'] = array(
                            'fr' => 'Dernière entrée : {last_entry} · Reprise des visites : {resume}',
                            'en' => 'Last admission: {last_entry} · Visits resume: {resume}',
                            'de' => 'Letzter Einlass: {last_entry} · Besuche wieder ab: {resume}',
                        );
                    }
                }
                unset($rule);
            }
            unset($season);
        }
        return $settings;
    }

'''
text = text.replace(marker, migration + marker, 1)
p.write_text(text)

# Native admin fields.
p = Path("includes/class-parcs-ht-admin.php")
text = p.read_text()
old = '''            <h2>Accès temporairement limité</h2>
            <p>Module générique pour une zone dont l’accès peut être temporairement interrompu. Rien n’indique que le parc entier est fermé.</p>'''
new = '''            <h2>Accès temporairement limité</h2>
            <p>Module générique pour une zone dont l’accès peut être temporairement interrompu. Rien n’indique que le parc entier est fermé.</p>
            <div class="htp-grid htp-grid-2">
                <?php self::translated_input('settings[general][calendar_hours_title]', $settings['general']['calendar_hours_title'] ?? array('fr'=>'Horaires du parc','en'=>'Park opening hours','de'=>'Öffnungszeiten des Parks'), 'Titre au-dessus des horaires du parc'); ?>
            </div>'''
if text.count(old) != 1:
    raise SystemExit("domain section header not found exactly once")
text = text.replace(old, new, 1)

old = "'exclude_public_holidays' => '0', 'auto_details' => '1', 'show_tooltip' => '1',"
new = "'exclude_public_holidays' => '0', 'auto_details' => '1', 'color' => '#e7c55b', 'access_message' => array('fr'=>'Cette zone n’est pas accessible de {pause_start} à {resume}.','en'=>'This area is not accessible from {pause_start} to {resume}.','de'=>'Dieser Bereich ist von {pause_start} bis {resume} nicht zugänglich.'), 'details_message' => array('fr'=>'Dernière entrée : {last_entry} · Reprise des visites : {resume}','en'=>'Last admission: {last_entry} · Visits resume: {resume}','de'=>'Letzter Einlass: {last_entry} · Besuche wieder ab: {resume}'), 'show_tooltip' => '1',"
if text.count(old) != 1:
    raise SystemExit("domain row defaults marker not found exactly once")
text = text.replace(old, new, 1)

old = "<?php self::input($base . '[pause_start]', $row['pause_start'], 'Interruption à', 'time'); self::input($base . '[resume]', $row['resume'], 'Reprise à', 'time'); self::input($base . '[last_entry]', $row['last_entry'], 'Dernière entrée avant interruption', 'time'); ?>"
new = old + "\n                <?php self::input($base . '[color]', $row['color'], 'Couleur du bloc d’accès', 'color'); ?>"
if text.count(old) != 1:
    raise SystemExit("domain time inputs marker not found exactly once")
text = text.replace(old, new, 1)

old = "            <div class=\"htp-check-list\"><?php self::checkbox($base . '[auto_details]', $row['auto_details'], 'Afficher automatiquement les horaires d’interruption / reprise'); ?></div>"
new = old + "\n            <div class=\"htp-grid htp-grid-2\">\n                <?php self::translated_input($base . '[access_message]', $row['access_message'], 'Phrase d’accès — variables : {pause_start}, {resume}, {last_entry}'); ?>\n                <?php self::translated_input($base . '[details_message]', $row['details_message'], 'Ligne dernière entrée / reprise — mêmes variables'); ?>\n            </div>"
if text.count(old) != 1:
    raise SystemExit("domain auto details marker not found exactly once")
text = text.replace(old, new, 1)

old = "'period_legend_label'=>self::sanitize_translations(isset($g['period_legend_label'])?$g['period_legend_label']:array()), 'event_legend_label'=>self::sanitize_translations(isset($g['event_legend_label'])?$g['event_legend_label']:array()),"
new = "'calendar_hours_title'=>self::sanitize_translations(isset($g['calendar_hours_title'])?$g['calendar_hours_title']:array()), 'period_legend_label'=>self::sanitize_translations(isset($g['period_legend_label'])?$g['period_legend_label']:array()), 'event_legend_label'=>self::sanitize_translations(isset($g['event_legend_label'])?$g['event_legend_label']:array()),"
if text.count(old) != 1:
    raise SystemExit("general sanitizer marker not found exactly once")
text = text.replace(old, new, 1)

old = "'exclude_public_holidays'=>self::bool($row,'exclude_public_holidays'),'auto_details'=>array_key_exists('auto_details',$row)?self::bool($row,'auto_details'):'1','show_tooltip'"
new = "'exclude_public_holidays'=>self::bool($row,'exclude_public_holidays'),'auto_details'=>array_key_exists('auto_details',$row)?self::bool($row,'auto_details'):'1','color'=>self::color($row,'color','#e7c55b'),'access_message'=>self::sanitize_translations(isset($row['access_message'])?$row['access_message']:array(), true),'details_message'=>self::sanitize_translations(isset($row['details_message'])?$row['details_message']:array(), true),'show_tooltip'"
if text.count(old) != 1:
    raise SystemExit("domain sanitizer marker not found exactly once")
text = text.replace(old, new, 1)
p.write_text(text)

# Public calendar hierarchy and dynamic domain copy.
p = Path("assets/frontend.js")
text = p.read_text()
old = "    box.appendChild(hours);\n    if(status.open){var last=document.createElement('p');last.className='parcs-ht-day-last';last.textContent=text(d.lastEntry,{time:timeLabel(lastEntryTime(status),language)});box.appendChild(last);}"
new = "    var parkHoursTitle=translated((settings.general||{}).calendar_hours_title,language)||(language==='en'?'Park opening hours':language==='de'?'Öffnungszeiten des Parks':'Horaires du parc');\n    var parkTitle=document.createElement('p');parkTitle.className='parcs-ht-park-hours-title';parkTitle.textContent=parkHoursTitle;box.insertBefore(parkTitle,hours);\n    box.appendChild(hours);\n    if(status.open){var last=document.createElement('p');last.className='parcs-ht-day-last';last.textContent=text(d.lastEntry,{time:timeLabel(lastEntryTime(status),language)});box.appendChild(last);}"
if text.count(old) != 1:
    raise SystemExit("park hours insertion point not found exactly once")
text = text.replace(old, new, 1)

old = "        domain.className='parcs-ht-domain-note';\n        var title=translated(rule.public_title,language)||'';"
new = "        domain.className='parcs-ht-domain-note';\n        var domainColor=String(rule.color||(settings.general||{}).highlight_color||'#e7c55b');\n        domain.style.setProperty('--htp-domain-color',domainColor);\n        var title=translated(rule.public_title,language)||'';"
if text.count(old) != 1:
    raise SystemExit("domain color insertion point not found exactly once")
text = text.replace(old, new, 1)

old = """        if(String(rule.auto_details)!=='0'){
          var lines=[];
          if(rule.last_entry)lines.push((language==='en'?'Last admission: ':language==='de'?'Letzter Einlass: ':'Dernière entrée : ')+timeLabel(rule.last_entry,language));
          if(rule.pause_start&&rule.resume)lines.push((language==='en'?'Pause: ':language==='de'?'Pause: ':'Interruption : ')+timeLabel(rule.pause_start,language)+'–'+timeLabel(rule.resume,language));
          if(rule.resume)lines.push((language==='en'?'Visits resume: ':language==='de'?'Besuche wieder ab: ':'Reprise des visites : ')+timeLabel(rule.resume,language));
          lines.forEach(function(line){var p=document.createElement('p');p.textContent=line;domain.appendChild(p);});
        }"""
new = """        if(String(rule.auto_details)!=='0'){
          var values={pause_start:timeLabel(rule.pause_start,language),resume:timeLabel(rule.resume,language),last_entry:timeLabel(rule.last_entry,language)};
          var accessTemplate=translated(rule.access_message,language)||(language==='en'?'This area is not accessible from {pause_start} to {resume}.':language==='de'?'Dieser Bereich ist von {pause_start} bis {resume} nicht zugänglich.':'Cette zone n’est pas accessible de {pause_start} à {resume}.');
          var detailsTemplate=translated(rule.details_message,language)||(language==='en'?'Last admission: {last_entry} · Visits resume: {resume}':language==='de'?'Letzter Einlass: {last_entry} · Besuche wieder ab: {resume}':'Dernière entrée : {last_entry} · Reprise des visites : {resume}');
          if(rule.pause_start&&rule.resume){var accessLine=document.createElement('p');accessLine.className='parcs-ht-domain-access';accessLine.textContent=text(accessTemplate,values);domain.appendChild(accessLine);}
          if(rule.last_entry||rule.resume){var detailsLine=document.createElement('p');detailsLine.className='parcs-ht-domain-times';detailsLine.textContent=text(detailsTemplate,values);domain.appendChild(detailsLine);}
        }"""
if text.count(old) != 1:
    raise SystemExit("domain auto-details rendering block not found exactly once")
text = text.replace(old, new, 1)
p.write_text(text)

# Keep existing layout and add an editable visual distinction.
p = Path("assets/frontend.css")
text = p.read_text()
addition = """

/* 1.15.6 — hiérarchie horaires du parc / accès limité */
.parcs-ht-park-hours-title{margin:10px 0 4px!important;color:inherit!important;font-size:.86em!important;font-weight:850!important;letter-spacing:.04em;text-transform:uppercase;opacity:.72}
.parcs-ht-domain-note{margin:12px 0 0!important;padding:11px 13px!important;border-top:0!important;border-left:4px solid var(--htp-domain-color,var(--htp-highlight,#e7c55b))!important;border-radius:9px;background:color-mix(in srgb,var(--htp-domain-color,var(--htp-highlight,#e7c55b)) 12%,transparent)!important;opacity:1!important}
.parcs-ht-domain-note .parcs-ht-domain-title-row{display:flex;align-items:center;gap:7px;margin:0 0 5px;color:inherit}
.parcs-ht-domain-note .parcs-ht-domain-title-row strong{display:inline;margin:0;font-size:1.03em!important}
.parcs-ht-domain-note .parcs-ht-domain-access{display:block;margin:0!important;font-weight:650}
.parcs-ht-domain-note .parcs-ht-domain-times{display:block;margin:3px 0 0!important;font-weight:800}
.parcs-ht-domain-note .parcs-ht-domain-extra{display:block;margin:6px 0 0!important;opacity:.88}
@media(max-width:720px){.parcs-ht-park-hours-title{margin-top:6px!important;font-size:10px!important}.parcs-ht-domain-note{margin-top:7px!important;padding:8px 9px!important}.parcs-ht-domain-note .parcs-ht-domain-times{margin-top:2px!important}}
"""
if "/* 1.15.6 — hiérarchie horaires du parc / accès limité */" in text:
    raise SystemExit("1.15.6 CSS already present")
p.write_text(text.rstrip() + addition)

# Changelog.
p = Path("CHANGELOG.md")
text = p.read_text()
if "## 1.15.6" in text:
    raise SystemExit("1.15.6 changelog already present")
header = """## 1.15.6

- Clarifie le détail d’une journée du calendrier : la date reste en premier, suivie d’un titre « Horaires du parc », des horaires et de la dernière entrée.
- Distingue visuellement l’accès temporairement limité dans un bloc teinté sans le présenter comme une fermeture du parc.
- Rend modifiables le titre des horaires du parc, la couleur du bloc d’accès et les deux phrases automatiques de la règle d’accès.
- Les heures restent entièrement issues des champs existants (interruption, reprise, dernière entrée) et ne sont jamais figées dans le rendu.
- Conserve sans modification les textes complémentaires et l’infobulle déjà configurés sur la règle d’accès.
- Migration non destructive : les données existantes sont conservées ; seuls les nouveaux champs absents reçoivent des valeurs initiales modifiables.

"""
p.write_text(header + text)

# Contracts.
frontend = Path("assets/frontend.js").read_text()
admin = Path("includes/class-parcs-ht-admin.php").read_text()
defaults = Path("includes/class-parcs-ht-defaults.php").read_text()
css = Path("assets/frontend.css").read_text()
main = Path("horaires-tarifs-parc.php").read_text()
checks = [
    ("Version: 1.15.6" in main and "PARCS_HT_VERSION', '1.15.6" in main, "version"),
    ("const SCHEMA_VERSION = 27;" in defaults and "upgrade_v156_structures" in defaults, "migration"),
    ("calendar_hours_title" in admin and "calendar_hours_title" in defaults and "calendar_hours_title" in frontend, "park title setting"),
    ("access_message" in admin and "details_message" in admin and "Couleur du bloc d’accès" in admin, "editable domain fields"),
    ("parcs-ht-park-hours-title" in frontend and "parcs-ht-domain-access" in frontend and "parcs-ht-domain-times" in frontend, "public hierarchy"),
    ("rule.pause_start" in frontend and "rule.resume" in frontend and "rule.last_entry" in frontend, "dynamic times"),
    ("var(--htp-domain-color" in css and "color-mix" in css, "domain visual distinction"),
    ("translated(rule.info,language)" in frontend and "createDomainTooltip(rule,language)" in frontend, "existing info and tooltip preserved"),
]
for ok, name in checks:
    if not ok:
        raise SystemExit("contract failed: " + name)
