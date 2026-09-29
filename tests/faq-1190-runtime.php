<?php
// Exercise FAQ 1.19.1 writes without touching annual storage.
define('ABSPATH', __DIR__ . '/');
define('PARCS_HT_VERSION', '1.19.1');
define('PARCS_HT_URL', 'https://example.org/plugin/');
define('PARCS_HT_DIR', (getenv('PLUGIN_ROOT') ?: dirname(__DIR__)) . '/');

class FAQRedirect extends Exception {}
$GLOBALS['options'] = array('parcs_ht_settings'=>array('2026'=>array('tariffs'=>123), '2027'=>array('hours'=>'untouched')));
$GLOBALS['transients'] = array();
$GLOBALS['writes'] = array();
$GLOBALS['allowed'] = true;
$GLOBALS['nonce_ok'] = true;
$GLOBALS['fail_write'] = '';

function current_user_can($cap) { return $GLOBALS['allowed']; }
function check_admin_referer($action) { if (!$GLOBALS['nonce_ok']) throw new Exception('nonce'); }
function wp_die($message) { throw new Exception($message); }
function wp_unslash($v) { return $v; }
function sanitize_text_field($v) { return trim(strip_tags((string)$v)); }
function sanitize_textarea_field($v) { return sanitize_text_field($v); }
function sanitize_key($v) { return preg_replace('/[^a-z0-9_-]/', '', strtolower((string)$v)); }
function wp_kses_post($v) { return strip_tags((string)$v, '<p><strong><a>'); }
function wp_strip_all_tags($v) { return strip_tags((string)$v); }
function remove_accents($v) { return strtr((string)$v, array('é'=>'e','è'=>'e','ê'=>'e','É'=>'E','à'=>'a','À'=>'A')); }
function esc_url_raw($v) { return (string)$v; }
function wp_parse_url($v, $part = -1) { return parse_url($v, $part); }
function get_option($k, $default = false) { return array_key_exists($k, $GLOBALS['options']) ? $GLOBALS['options'][$k] : $default; }
function update_option($k, $v, $autoload = false) { $GLOBALS['writes'][] = $k; if ($GLOBALS['fail_write'] !== $k) $GLOBALS['options'][$k] = $v; }
function set_transient($k, $v, $ttl) { $GLOBALS['transients'][$k] = $v; }
function get_transient($k) { return array_key_exists($k, $GLOBALS['transients']) ? $GLOBALS['transients'][$k] : false; }
function delete_transient($k) { unset($GLOBALS['transients'][$k]); }
function get_current_user_id() { return 1; }
function admin_url($p) { return 'https://example.org/wp-admin/' . $p; }
function add_query_arg($args, $url) { return $url . '?' . http_build_query($args); }
function wp_safe_redirect($url) { throw new FAQRedirect($url); }
function do_action($name) {}
function wp_generate_password($len, $special = true, $extra = true) { return str_repeat('x', $len); }
function wp_json_encode($v) { return json_encode($v); }
function esc_html($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function esc_attr($v) { return esc_html($v); }
function esc_url($v) { return esc_html($v); }
function checked($a, $b) { if ($a === $b) echo 'checked'; }
function selected($a, $b) { if ($a === $b) echo 'selected'; }
function wp_nonce_field($v) { echo '<input type="hidden" name="_wpnonce" value="test">'; }
function wp_enqueue_script($handle, $url, $deps, $version, $footer) {}
function wp_enqueue_style($handle, $url, $deps, $version) {}
function did_action($action) { return false; }
function shortcode_atts($defaults, $atts, $tag) { return array_replace($defaults, $atts); }
function wpautop($v) { return '<p>' . $v . '</p>'; }
function wp_date($format, $time) { return date($format, $time); }

$root = getenv('PLUGIN_ROOT') ?: dirname(__DIR__);
require $root . '/includes/class-parcs-ht-faq.php';
require $root . '/includes/class-parcs-ht-faq-csv-1191.php';

function ensure($ok, $message) { if (!$ok) throw new Exception($message); }
function call_static($class, $method, $post, $success = true) {
    $_POST = $post;
    try { call_user_func(array($class, $method)); throw new Exception('Missing redirect'); }
    catch (FAQRedirect $e) {
        $failed = strpos($e->getMessage(), 'faq_csv_error=1') !== false || strpos($e->getMessage(), 'faq_error=1') !== false;
        ensure($failed !== $success, $class . '::' . $method . ': ' . $e->getMessage());
    }
}

$annual = get_option('parcs_ht_settings');
call_static('Parcs_HT_FAQ', 'save_settings', array('faq'=>array('_complete'=>'1','enabled'=>'1','show_search'=>'1','show_categories'=>'1')));
ensure(get_option('parcs_ht_settings') === $annual, 'Display save mutated annual storage');

$item = array(
    'id'=>'MDS-TEST-001', 'priority'=>'P1', 'category'=>'Visite',
    'category_label'=>array('fr'=>'Visite','en'=>'Visit','de'=>'Besuch'),
    'question'=>array('fr'=>'Question ?','en'=>'','de'=>''),
    'variants'=>array('fr'=>array('test'),'en'=>array(),'de'=>array()),
    'answer'=>array('fr'=>'Réponse.','en'=>'','de'=>''),
    'visibility'=>'FAQ publique', 'dynamic'=>'0', 'source_url'=>'', 'public_url'=>'',
    'link_label'=>array('fr'=>'','en'=>'','de'=>''), 'response_mode'=>'direct',
    'verified'=>'29/09/2026', 'status'=>'Validé', 'notes'=>'', 'enabled'=>'1', 'remote_order'=>0,
);
$preview = array('created_at'=>time(),'park_code'=>'MDS','source_name'=>'faq.csv','counts'=>array('new'=>1),'rows'=>array(array('id'=>'MDS-TEST-001','change'=>'new','publishable'=>true,'item'=>$item)));
set_transient('parcs_ht_faq_csv_preview_1', $preview, 1800);
$before_import = get_option('parcs_ht_faq');
$GLOBALS['fail_write'] = 'parcs_ht_faq_revisions';
call_static('Parcs_HT_FAQ_CSV_1191', 'apply_csv', array('selected'=>array('MDS-TEST-001')), false);
ensure(get_option('parcs_ht_faq') === $before_import, 'Import continued after failed revision');
$GLOBALS['fail_write'] = '';
set_transient('parcs_ht_faq_csv_preview_1', $preview, 1800);
call_static('Parcs_HT_FAQ_CSV_1191', 'apply_csv', array('selected'=>array('MDS-TEST-001')));
ensure(count(get_option('parcs_ht_faq')['items']) === 1, 'CSV import failed');
ensure(count(get_option('parcs_ht_faq_revisions')) === 1, 'CSV revision missing');
ensure(get_option('parcs_ht_settings') === $annual, 'CSV import mutated annual storage');
ensure(strpos(Parcs_HT_FAQ::render('fr'), 'Réponse.') !== false, 'FR answer missing from HTML');
ensure(Parcs_HT_FAQ::render('en') === '' && Parcs_HT_FAQ::render('de') === '', 'Missing translation fell back to French');

$blocked = $item; $blocked['status'] = 'À valider'; $blocked['enabled'] = '0'; $blocked['answer']['fr'] = 'Ne doit pas remplacer.';
set_transient('parcs_ht_faq_csv_preview_1', array('rows'=>array(array('id'=>'MDS-TEST-001','change'=>'blocked','publishable'=>false,'item'=>$blocked))), 1800);
call_static('Parcs_HT_FAQ_CSV_1191', 'apply_csv', array('selected'=>array('MDS-TEST-001')), false);
ensure(get_option('parcs_ht_faq')['items'][0]['answer']['fr'] === 'Réponse.', 'Blocked row overwrote public answer');

$revision = get_option('parcs_ht_faq_revisions')[0];
call_static('Parcs_HT_FAQ', 'restore_revision', array('revision_id'=>$revision['id']));
ensure(get_option('parcs_ht_settings') === $annual, 'Revision restore mutated annual storage');
ensure(!array_diff($GLOBALS['writes'], array('parcs_ht_faq','parcs_ht_faq_revisions')), 'Unexpected option writes');

foreach (array(array('Parcs_HT_FAQ','save_settings'), array('Parcs_HT_FAQ_CSV_1191','apply_csv'), array('Parcs_HT_FAQ','restore_revision')) as $target) {
    foreach (array('allowed','nonce_ok') as $guard) {
        $GLOBALS[$guard] = false;
        $count = count($GLOBALS['writes']);
        try { call_user_func($target); throw new Exception('Authorization accepted'); }
        catch (Throwable $e) { ensure(!($e instanceof FAQRedirect) && $e->getMessage() !== 'Authorization accepted', 'Authorization guard failed'); }
        ensure(count($GLOBALS['writes']) === $count, 'Unauthorized write');
        $GLOBALS[$guard] = true;
    }
}

echo "FAQ runtime OK: CSV apply, backups, public rendering and annual-storage isolation.\n";
