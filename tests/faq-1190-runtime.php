<?php
// Exercise real handlers with in-memory WordPress options and deterministic HTTP.
define('ABSPATH', __DIR__ . '/');
define('PARCS_HT_VERSION', '1.19.0');
define('PARCS_HT_URL', 'https://example.org/plugin/');
define('PARCS_HT_DIR', (getenv('PLUGIN_ROOT') ?: dirname(__DIR__)) . '/');
class FAQRedirect extends Exception {}
class WP_Error {}
$GLOBALS['options'] = array('parcs_ht_settings'=>array('2026'=>array('tariffs'=>123), '2027'=>array('hours'=>'untouched')));
$GLOBALS['transients'] = array();
$GLOBALS['allowed'] = true;
$GLOBALS['nonce_ok'] = true;
$GLOBALS['http_mode'] = 'ok';
$GLOBALS['requests'] = array();
$GLOBALS['writes'] = array();
function current_user_can($cap) { return $GLOBALS['allowed']; }
function check_admin_referer($action) { if (!$GLOBALS['nonce_ok']) throw new Exception('nonce'); }
function wp_die($message) { throw new Exception($message); }
function wp_unslash($v) { return $v; }
function sanitize_text_field($v) { return trim(strip_tags($v)); }
function sanitize_textarea_field($v) { return sanitize_text_field($v); }
function sanitize_key($v) { return preg_replace('/[^a-z0-9_-]/', '', strtolower($v)); }
function wp_kses_post($v) { return strip_tags($v, '<p><strong><a>'); }
function wp_strip_all_tags($v) { return strip_tags($v); }
function remove_accents($v) { return strtr($v, array('é'=>'e','è'=>'e','ê'=>'e','É'=>'E','à'=>'a')); }
function esc_url_raw($v) { return $v; }
function wp_parse_url($v, $part = -1) { return parse_url($v, $part); }
function wp_http_validate_url($v) { return filter_var($v, FILTER_VALIDATE_URL); }
function get_option($k, $default = false) { return $GLOBALS['options'][$k] ?? $default; }
function update_option($k, $v, $autoload = false) { $GLOBALS['writes'][] = $k; if (($GLOBALS['fail_write'] ?? '') !== $k) $GLOBALS['options'][$k] = $v; }
function set_transient($k, $v, $ttl) { $GLOBALS['transients'][$k] = $v; }
function get_transient($k) { return $GLOBALS['transients'][$k] ?? false; }
function delete_transient($k) { unset($GLOBALS['transients'][$k]); }
function get_current_user_id() { return $GLOBALS['user_id'] ?? 1; }
function admin_url($p) { return 'https://example.org/wp-admin/' . $p; }
function add_query_arg($args, $url) { return $url . '?' . http_build_query($args); }
function wp_safe_redirect($url) { throw new FAQRedirect($url); }
function do_action($name) {}
function wp_generate_password($len, $special = true, $extra = true) { return str_repeat('x', $len); }
function wp_json_encode($v) { return json_encode($v); }
function is_wp_error($v) { return $v instanceof WP_Error; }
function wp_remote_retrieve_response_code($v) { return $v['response']['code']; }
function wp_remote_retrieve_body($v) { return $v['body'] ?? ''; }
function wp_remote_retrieve_header($v, $key) { return $v['headers'][$key] ?? ''; }
function faq_record($id = 'MDS-TEST-001', $status = 'Validé') {
    return array('ID stable'=>$id, 'Question canonique FR'=>'Question ?', 'Réponse courte FR'=>'Réponse.', 'Statut'=>$status, 'Usage / visibilité'=>'FAQ publique', 'Catégorie'=>'Visite');
}
function http_result($args) {
    $body = json_decode($args['body'], true);
    $data = array('ok'=>true, 'schema_version'=>1, 'spreadsheet_id'=>$body['spreadsheet_id'], 'tab'=>$body['tab'], 'park_code'=>$body['park_code'], 'records'=>array(faq_record($body['park_code'] . '-TEST-001')));
    switch ($GLOBALS['http_mode']) {
        case 'network': return new WP_Error();
        case 'invalid': return array('response'=>array('code'=>200), 'body'=>'<html>Login</html>');
        case 'denied': $data['ok'] = false; break;
        case 'wrong_source': $data['spreadsheet_id'] = 'wrong'; break;
        case 'wrong_park': $data['records'] = array(faq_record('FDS-TEST-001')); break;
        case 'duplicate': $data['records'][] = $data['records'][0]; break;
        case 'empty': $data['records'] = array(); break;
        case 'blocked': $data['records'] = array(faq_record('MDS-TEST-001', 'À valider')); break;
        case 'concurrent': $GLOBALS['options']['parcs_ht_faq']['show_search'] = '0'; break;
    }
    return array('response'=>array('code'=>200), 'body'=>json_encode($data));
}
function wp_safe_remote_post($url, $args) {
    $GLOBALS['requests'][] = array('POST', $url, $args);
    if (in_array($GLOBALS['http_mode'], array('redirect','evil_redirect'), true)) {
        $GLOBALS['response_args'] = $args;
        return array('response'=>array('code'=>302), 'headers'=>array('location'=>$GLOBALS['http_mode'] === 'redirect' ? 'https://script.googleusercontent.com/macros/echo?user_content_key=abc' : 'https://attacker.example/steal'));
    }
    return http_result($args);
}
function wp_safe_remote_get($url, $args) {
    $GLOBALS['requests'][] = array('GET', $url, $args);
    return http_result($GLOBALS['response_args']);
}
function esc_html($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function esc_attr($v) { return esc_html($v); }
function esc_textarea($v) { return esc_html($v); }
function esc_url($v) { return esc_html($v); }
function checked($a, $b) { if ($a === $b) echo 'checked'; }
function selected($a, $b) { if ($a === $b) echo 'selected'; }
function wp_nonce_field($v) { echo '<input type="hidden" name="_wpnonce" value="test">'; }
function wp_enqueue_script($handle, $url, $deps, $version, $footer) { $GLOBALS['assets'][] = $handle; }
function wp_enqueue_style($handle, $url, $deps, $version) { $GLOBALS['assets'][] = $handle; }
function did_action($action) { return false; }
function shortcode_atts($defaults, $atts, $tag) { return array_replace($defaults, $atts); }
function wpautop($v) { return '<p>' . $v . '</p>'; }
function wp_date($format, $time) { return date($format, $time); }
$root = getenv('PLUGIN_ROOT') ?: dirname(__DIR__);
require $root . '/includes/class-parcs-ht-faq.php';
function ensure($ok, $message) { if (!$ok) throw new Exception($message); }
function call_handler($method, $post, $success = true) {
    $_POST = $post;
    try { Parcs_HT_FAQ::$method(); throw new Exception('Missing redirect'); }
    catch (FAQRedirect $e) { ensure((strpos($e->getMessage(), 'faq_error=1') === false) === $success, $method . ': ' . $e->getMessage() . ' ' . json_encode($GLOBALS['transients'])); }
}
$annual = get_option('parcs_ht_settings');
$google = array('sheet_url'=>'https://docs.google.com/spreadsheets/d/' . str_repeat('a', 30) . '/edit', 'endpoint'=>'https://script.google.com/macros/s/deployment/exec', 'secret'=>str_repeat('s',64), 'tab'=>'Montagne des Singes', 'park_code'=>'MDS', '_complete'=>'1');
call_handler('save_connection', array('google'=>$google));
ensure(Parcs_HT_FAQ::settings()['enabled'] === '0', 'Connection must not enable FAQ');
call_handler('save_settings', array('faq'=>array('_complete'=>'1', 'enabled'=>'1', 'show_search'=>'1', 'show_categories'=>'1')));
$original = get_option('parcs_ht_faq');
call_handler('save_settings', array(), false);
ensure(get_option('parcs_ht_faq') === $original, 'Incomplete display form mutated data');
$candidate = $google; $candidate['sheet_url'] = str_replace(str_repeat('a',30),str_repeat('b',30),$candidate['sheet_url']);
foreach (array('network','invalid','denied','wrong_source','wrong_park','duplicate','empty','evil_redirect') as $mode) {
    $GLOBALS['http_mode'] = $mode;
    call_handler('save_connection', array('google'=>$candidate), false);
    ensure(get_option('parcs_ht_faq') === $original, 'Failed candidate changed current source: ' . $mode);
}
$GLOBALS['http_mode'] = 'redirect';
$candidate['secret'] = '';
call_handler('save_connection', array('google'=>$candidate));
ensure(get_option('parcs_ht_faq')['google']['secret'] === $google['secret'], 'Blank secret must preserve key');
$last = end($GLOBALS['requests']);
ensure($last[0] === 'GET' && !isset($last[2]['body']) && $last[2]['redirection'] === 0, 'Redirect leaked POST secret');
$before_requests = count($GLOBALS['requests']);
$bad = $google; $bad['endpoint'] = 'https://script.google.com.evil.example/macros/s/x/exec';
call_handler('save_connection', array('google'=>$bad), false);
$bad = $google; unset($bad['_complete']);
call_handler('save_connection', array('google'=>$bad), false);
ensure(count($GLOBALS['requests']) === $before_requests, 'Invalid request reached network');
$GLOBALS['http_mode'] = 'concurrent';
call_handler('save_connection', array('google'=>$google));
ensure(get_option('parcs_ht_faq')['show_search'] === '0', 'Connection erased concurrent display edit');
$GLOBALS['http_mode'] = 'ok';
call_handler('check_google', array());
$preview = get_transient('parcs_ht_faq_preview_1');
$before_import = get_option('parcs_ht_faq');
ensure(empty($before_import['items']), 'Preview imported without approval');
$GLOBALS['fail_write'] = 'parcs_ht_faq_revisions';
call_handler('apply_import', array('selected'=>array('MDS-TEST-001')), false);
ensure(get_option('parcs_ht_faq') === $before_import, 'Import continued after failed backup');
$GLOBALS['fail_write'] = '';
call_handler('apply_import', array('selected'=>array('MDS-TEST-001')));
ensure(count(get_option('parcs_ht_faq')['items']) === 1, 'Selected import failed');
ensure(count(get_option('parcs_ht_faq_revisions')) === 1, 'Backup missing');
$request_count = count($GLOBALS['requests']);
ensure(strpos(Parcs_HT_FAQ::render('fr'), 'Réponse.') !== false, 'FR answer missing from initial HTML');
ensure(Parcs_HT_FAQ::render('en') === '' && Parcs_HT_FAQ::render('de') === '', 'Missing translation fell back to French');
ensure(count($GLOBALS['requests']) === $request_count, 'Public page contacted Google');
ob_start(); Parcs_HT_FAQ::page(); $admin_html = ob_get_clean();
ensure(strpos($admin_html, 'Copier le script') !== false && strpos($admin_html, 'spreadsheets.readonly') !== false, 'Bundled setup missing from admin');
ensure(strpos($admin_html, $google['secret']) === false, 'Saved secret leaked in admin HTML');
ensure(substr_count($admin_html, '<form ') === substr_count($admin_html, '</form>'), 'Unbalanced admin forms');

$GLOBALS['http_mode'] = 'blocked';
call_handler('check_google', array());
call_handler('apply_import', array('selected'=>array('MDS-TEST-001')), false);
ensure(get_option('parcs_ht_faq')['items'][0]['status'] === 'Validé', 'Blocked row overwrote public answer');
$GLOBALS['http_mode'] = 'ok';
$fds = $google; $fds['park_code'] = 'FDS'; $fds['tab'] = 'Forêt des Singes';
call_handler('save_connection', array('google'=>$fds));
set_transient('parcs_ht_faq_preview_1', $preview, 1800);
call_handler('apply_import', array('selected'=>array('MDS-TEST-001')), false);
call_handler('check_google', array());
ensure(get_transient('parcs_ht_faq_preview_1')['rows'][0]['id'] === 'FDS-TEST-001', 'FDS source unsupported');
$revision = get_option('parcs_ht_faq_revisions')[0];
call_handler('restore_revision', array('revision_id'=>$revision['id']));
ensure(get_option('parcs_ht_faq')['google']['park_code'] === 'FDS', 'Restore overwrote connection');
ensure(get_option('parcs_ht_settings') === $annual, 'Annual storage mutated');
ensure(!array_diff($GLOBALS['writes'], array('parcs_ht_faq','parcs_ht_faq_revisions')), 'Unexpected option writes');
foreach (array('save_settings','save_connection','check_google','apply_import','restore_revision') as $method) {
    foreach (array('allowed','nonce_ok') as $guard) {
        $GLOBALS[$guard] = false; $write_count = count($GLOBALS['writes']);
        try { Parcs_HT_FAQ::$method(); throw new Exception('Authorization accepted'); }
        catch (Exception $e) { ensure(!($e instanceof FAQRedirect) && $e->getMessage() !== 'Authorization accepted', 'Authorization guard failed'); }
        ensure(count($GLOBALS['writes']) === $write_count, 'Unauthorized write'); $GLOBALS[$guard] = true;
    }
}
echo "FAQ runtime OK: source validation, redirect security, isolated saves, imports and backups.\n";
