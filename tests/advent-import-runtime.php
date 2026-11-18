<?php
// Runs standalone with WordPress boundary stubs, or via wp eval-file on a disposable site.
if (!defined('ABSPATH')) {
    define('ABSPATH', __DIR__);
    class Parcs_HT_Defaults { const OPTION = 'parcs_ht_settings'; }
    class WP_Error { public function __construct($code, $message) {} }
    function is_wp_error($value) { return $value instanceof WP_Error; }
    function sanitize_key($v) { return preg_replace('/[^a-z0-9_-]/', '', strtolower((string)$v)); }
    function sanitize_text_field($v) { return trim(strip_tags((string)$v)); }
    function sanitize_textarea_field($v) { return trim(strip_tags((string)$v)); }
    function wp_unslash($v) { return $v; }
    function sanitize_title($v) { return sanitize_key($v); }
    function esc_url_raw($v, $protocols = null) { return preg_match('~^https?://~', $v) ? $v : ''; }
    function esc_url($v) { return htmlspecialchars($v, ENT_QUOTES); }
    function esc_html($v) { return htmlspecialchars((string)$v, ENT_QUOTES); }
    function esc_attr($v) { return esc_html($v); }
    function wp_kses_post($v) { return $v; }
    function wpautop($v) { return '<p>' . $v . '</p>'; }
    function wp_json_encode($v, $flags = 0) { return json_encode($v, $flags); }
    function get_option($key, $default = false) { return $GLOBALS['advent_options'][$key] ?? $default; }
    function update_option($key, $value, $autoload = null) { $GLOBALS['advent_options'][$key] = $value; return true; }
    function current_user_can($cap) { return false; }
    function wp_timezone_string() { return 'Europe/Paris'; }
}
if (!class_exists('Parcs_HT_Advent')) require_once dirname(__DIR__) . '/includes/class-parcs-ht-advent.php';
if (!class_exists('Parcs_HT_Advent_Admin')) require_once dirname(__DIR__) . '/includes/class-parcs-ht-advent-admin.php';
if (!class_exists('Parcs_HT_Advent_UX')) require_once dirname(__DIR__) . '/includes/class-parcs-ht-advent-ux.php';
function ai_check($condition, $message) { if (!$condition) throw new RuntimeException($message); }
function ai_call($method, ...$args) {
    $m = new ReflectionMethod('Parcs_HT_Advent_Admin', $method);
    if (PHP_VERSION_ID < 80100) $m->setAccessible(true);
    return $m->invokeArgs(null, $args);
}
$headers = ai_call('csv_headers');
ai_check(count($headers) === 150, 'Schema must support the 150-column source');
foreach (array('mds', 'fds') as $park) {
    update_option(Parcs_HT_Defaults::OPTION, array('site_type'=>$park));
    $campaign = Parcs_HT_Advent::default_campaign('2026', $park, '2026');
    $campaign['statut_campagne'] = 'active';
    $campaign['heure_ouverture_globale'] = '09:00';
    $campaign['reglement_complet_fr'] = 'Règlement provisoire';
    $campaign['reglement_quotidien_fr'] = 'Rappel provisoire';
    $campaign['partners']['demo'] = Parcs_HT_Advent::sanitize_partner(array('partenaire_id'=>'demo','nom'=>'Partenaire test','description_fr'=>'Ancienne description','logo_url'=>'https://example.test/old.png','logo_source'=>'wordpress'));
    $campaign['contents']['jour_01']['partenaire_id'] = 'demo';
    $campaign['contents']['jour_01']['date_publication'] = '2026-12-01';
    $campaign['contents']['jour_01']['visuel_url'] = 'https://example.test/day.png';
    $campaign['contents']['jour_01']['statut'] = 'pret';
    $base = array('schema_version'=>'3','parc_code'=>$park,'campagne_id'=>'2026');
    $partner = array_merge($base, array('type_ligne'=>'PARTENAIRE','partenaire_id'=>'demo','description_fr'=>"Présentation ; été\nDeuxième ligne",'hashtags'=>'#TestPartenaire','instagram_handle'=>'compte_test','facebook_slug'=>'page_test','site_url'=>'https://example.test/','logo_url'=>'https://example.test/new.png'));
    // Full-width CSV, BOM, quoted semicolon and multiline UTF-8, through the real reader.
    $path = tempnam(__DIR__, 'advent');
    $file = fopen($path, 'w'); fwrite($file, "\xEF\xBB\xBF"); fputcsv($file, $headers, ';', '"', '');
    fputcsv($file, array_map(static function ($key) use ($partner) { return $partner[$key] ?? ''; }, $headers), ';', '"', ''); fclose($file);
    $rows = ai_call('read_csv', $path); unlink($path);
    ai_check(!is_wp_error($rows), 'CSV UTF-8 reader');
    $report = ai_call('analyze_import', $rows, $campaign);
    ai_check(!$report['errors'], 'Valid import: ' . json_encode($report['errors']));
    $next = $report['candidate'];
    ai_check($next['partners']['demo']['description_fr'] === $partner['description_fr'], 'Description changed');
    ai_check($next['partners']['demo']['logo_url'] === 'https://example.test/old.png', 'Media protected by default');
    ai_check($report['diff'][0]['before']['description_fr'] === 'Ancienne description', 'Before/after diff');
    $empty = ai_call('analyze_import', array(array_merge($base,array('partenaire_id'=>'demo','description_fr'=>''))), $next);
    ai_check($empty['candidate']['partners']['demo'] === $next['partners']['demo'], 'Absent and empty fields preserved');
    $tr = array_merge($base,array('reference_id'=>'demo','champ'=>'description_fr','langue'=>'en','texte'=>'English description'));
    $with_translation = ai_call('analyze_import',array($tr),$next)['candidate'];
    $tr['texte'] = '';
    ai_check(ai_call('analyze_import',array($tr),$with_translation)['candidate']['translations'][0]['texte'] === 'English description', 'Empty translation preserved');
    $tr['texte'] = '[VIDER]';
    ai_check(ai_call('analyze_import',array($tr),$with_translation)['candidate']['translations'][0]['texte'] === '', 'Explicit translation clear');
    $repeat = ai_call('analyze_import', $rows, $next);
    ai_check($repeat['counts']['modify'] === 0, 'Idempotent reimport');
    foreach (array('facebook','instagram') as $network) {
        $post = Parcs_HT_Advent::social_post($next, $next['contents']['jour_01'], $network);
        ai_check(strpos($post, $partner['description_fr']) !== false && strpos($post, '#TestPartenaire') !== false, 'Description and hashtags in social text');
        ai_check(strpos($post, $network === 'instagram' ? '@compte_test' : 'facebook.com/page_test') !== false, 'Confirmed social account');
        ai_check(strpos($post, 'Rappel provisoire') === false, 'Provisional rules excluded from social');
    }
    $media = ai_call('analyze_import', $rows, $next, true);
    ai_check($media['candidate']['partners']['demo']['logo_url'] === 'https://example.test/new.png', 'Explicit media replacement');
    $clear = array_merge($base, array('partenaire_id'=>'demo','description_fr'=>'[VIDER]','instagram_handle'=>'[VIDER]','facebook_slug'=>'[VIDER]','logo_url'=>''));
    $cleared = ai_call('analyze_import', array($clear), $next, true)['candidate'];
    ai_check($cleared['partners']['demo']['description_fr'] === '' && $cleared['partners']['demo']['logo_url'] === 'https://example.test/old.png', 'Explicit clear and empty media preservation');
    $post = Parcs_HT_Advent::social_post($cleared, $cleared['contents']['jour_01'], 'instagram');
    ai_check(strpos($post, '@') === false && strpos($post, 'Partenaire test') !== false, 'No invented handle for unconfirmed account');
    $override = $next['partners']['demo']; $override['instagram_url_override'] = 'https://instagram.com/confirmed/';
    ai_check(Parcs_HT_Advent::partner_social_url($override, 'instagram') === $override['instagram_url_override'], 'URL override priority');
    foreach (array(array('parc_code'=>$park === 'mds' ? 'fds' : 'mds'),array('campagne_id'=>'2027'),array('schema_version'=>'3oops'),array('logo_url'=>'[VIDER]')) as $bad) {
        ai_check(ai_call('analyze_import', array(array_merge($partner,$bad)), $next)['errors'], 'Reject invalid context / media clear');
    }
    ai_check(ai_call('analyze_import', array($partner,$partner), $next)['errors'], 'Duplicate entities rejected');
    $status = ai_call('analyze_import', array(array_merge($base,array('contenu_id'=>'jour_01','statut'=>'publie')),array_merge($base,array('statut_campagne'=>'brouillon')),array_merge($base,array('resultat_id'=>'resultat_jour_01','statut_resultat'=>'publie'))),$next)['candidate'];
    ai_check($status['statut_campagne'] === 'active' && $status['contents']['jour_01']['statut'] === 'pret' && $status['results']['resultat_jour_01']['statut_resultat'] === 'brouillon', 'All publication statuses preserved');
    $store = Parcs_HT_Advent::empty_store();
    $store['campaigns']['2027'] = Parcs_HT_Advent::default_campaign('2027',$park,'2027');
    $store['campaigns']['2026'] = $next;
    Parcs_HT_Advent::save_store($store);
    $loaded = Parcs_HT_Advent::campaign('2026',true);
    $normalized_report = ai_call('analyze_import', $rows, $loaded);
    $store['campaigns']['2026'] = $normalized_report['candidate'];
    Parcs_HT_Advent::save_store($store);
    ai_check(wp_json_encode(Parcs_HT_Advent::campaign('2026', true)) === wp_json_encode($normalized_report['candidate']), 'Exact normalized readback for apply handler');
    ai_check($loaded['partners']['demo']['description_fr'] === $partner['description_fr'], 'Description survives save/reload');
    ai_check(Parcs_HT_Advent::campaign('2027',true)['partners'] === array(), 'Other year untouched');
    $ux_method = new ReflectionMethod('Parcs_HT_Advent_UX', 'public_payload');
    if (PHP_VERSION_ID < 80100) $ux_method->setAccessible(true);
    $ux_payload = $ux_method->invoke(null);
    ai_check($ux_payload['campaigns']['2026']['dailyText'] === '' && $ux_payload['campaigns']['2026']['rulesUrl'] === '', 'No provisional rules in public JavaScript payload');
    ai_check(Parcs_HT_Advent::render_rules('fr',array('campagne'=>'2026')) === '', 'Active campaign cannot expose unapproved rules');
    $next['rules_approved_hash'] = Parcs_HT_Advent::rules_hash($next);
    $store['campaigns']['2026'] = $next; Parcs_HT_Advent::save_store($store);
    ai_check(strpos(Parcs_HT_Advent::render_rules('fr',array('campagne'=>'2026')), 'Règlement provisoire') !== false, 'Explicit approval allows rules');
    $edited = ai_call('analyze_import',array(array_merge($base,array('reglement_complet_fr'=>'Nouveau brouillon'))),$next)['candidate'];
    ai_check($edited['rules_approved_hash'] === $next['rules_approved_hash'] && !Parcs_HT_Advent::rules_approved($edited), 'Import preserves approval record but changed rules require validation');
    $translated = ai_call('analyze_import',array(array_merge($base,array('reference_id'=>'2026','champ'=>'reglement_complet_fr','langue'=>'en','texte'=>'Draft'))),$next)['candidate'];
    ai_check(!Parcs_HT_Advent::rules_approved($translated), 'Rule translations also invalidate approval');
    Parcs_HT_Advent::set_preview_datetime('2026-12-01','12:00');
    $renderer = new ReflectionMethod('Parcs_HT_Advent','render_day_detail'); if (PHP_VERSION_ID < 80100) $renderer->setAccessible(true);
    $html = $renderer->invoke(null,$next,$next['contents']['jour_01'],'fr');
    ai_check(strpos($html,'Présentation ; été') !== false && strpos($html,'https://example.test/old.png') !== false && strpos($html,'instagram.com/compte_test') !== false, 'Day renderer displays partner text, logo and social link');
    $day = $next['contents']['jour_01']; $day['intro_partenaire_fr'] = 'Présentation spécifique';
    ai_check(strpos(Parcs_HT_Advent::social_post($next,$day),'Présentation spécifique') !== false, 'Day-specific intro remains authoritative');
    echo "[OK] $park: CSV, reimport, media, publication, rules, persistence and public rendering\n";
}
foreach (array("schema_version;parc_code;campagne_id;description_fr;description_fr\n3;fds;2026;a;b\n", "schema_version;parc_code;campagne_id;typo\n3;fds;2026;x\n", "schema_version;parc_code;campagne_id\n3;fds;2026;extra\n") as $invalid_csv) {
    $path = tempnam(__DIR__, 'advent'); file_put_contents($path,$invalid_csv);
    ai_check(is_wp_error(ai_call('read_csv',$path)), 'Reject malformed or ambiguous CSV'); unlink($path);
}
echo 'Export columns: ' . count($headers) . " (legacy 150-column subsets supported).\n";
