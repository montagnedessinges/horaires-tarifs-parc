from pathlib import Path

p = Path('includes/class-parcs-ht-schedule.php')
text = p.read_text()
old = "            'last_entry_minutes', 'accent_color', 'event_legend_label',"
new = "            'last_entry_minutes', 'accent_color', 'calendar_hours_title', 'event_legend_label',"
if text.count(old) != 1:
    raise SystemExit('public general whitelist marker not found exactly once')
p.write_text(text.replace(old, new, 1))

p = Path('tests/public-data-regressions.php')
text = p.read_text()
anchor = 'echo "Public data regressions: OK\\n";'
if text.count(anchor) != 1:
    raise SystemExit('public data regression end marker not found')
block = r'''

// 1.15.6 — Le titre des horaires reste configurable et les heures d’accès limité
// continuent de provenir des champs de la règle, sans valeur horaire figée dans le rendu.
$schedule_source = file_get_contents($root . '/includes/class-parcs-ht-schedule.php');
$frontend_source = file_get_contents($root . '/assets/frontend.js');
if (strpos($schedule_source, "'calendar_hours_title'") === false) {
    fwrite(STDERR, "[FAIL] calendar_hours_title absent du payload public.\n");
    exit(1);
}
foreach (array('access_message', 'details_message', 'rule.pause_start', 'rule.resume', 'rule.last_entry') as $needle) {
    if (strpos($frontend_source, $needle) === false) {
        fwrite(STDERR, "[FAIL] Rendu accès limité incomplet : {$needle}.\n");
        exit(1);
    }
}
if (strpos($frontend_source, 'parcs-ht-park-hours-title') === false || strpos($frontend_source, 'parcs-ht-domain-access') === false || strpos($frontend_source, 'parcs-ht-domain-times') === false) {
    fwrite(STDERR, "[FAIL] Hiérarchie publique horaires / accès limité absente.\n");
    exit(1);
}
'''
p.write_text(text.replace(anchor, block + '\n' + anchor, 1))
