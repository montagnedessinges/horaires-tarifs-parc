<?php
/**
 * Test real admin save handler methods with minimal WordPress function stubs.
 * Covers the exact admin-post callback paths missing from the 1.20.12 tests.
 * Not a replacement for a staging integration test with WordPress/CF7 installed.
 */
if (!defined('ABSPATH')) define('ABSPATH', __DIR__ . '/');
$GLOBALS['htp_test_options'] = array();
$GLOBALS['htp_redirect'] = '';
final class HTP_Quote_Test_Redirect extends RuntimeException {}
final class Parcs_HT_Defaults { const OPTION = 'parcs_ht_settings'; }
function current_user_can($cap) { return $cap === 'manage_options'; }
function check_admin_referer($name) {
    if (!in_array($name, array('parcs_ht_save_quote_gate_1177', 'parcs_ht_save_quote_gate'), true)) {
        throw new RuntimeException('Unexpected nonce action');
    }
}
function sanitize_text_field($value) { return is_scalar($value) ? trim((string)$value) : ''; }
function sanitize_textarea_field($value) { return sanitize_text_field($value); }
function sanitize_key($value) { return preg_replace('/[^a-z0-9_\-]/', '', strtolower((string)$value)); }
function wp_unslash($value) {
    if (is_array($value)) return array_map('wp_unslash', $value);
    return is_scalar($value) ? stripslashes((string)$value) : '';
}
function get_option($name, $default = false) {
    return array_key_exists($name, $GLOBALS['htp_test_options']) ? $GLOBALS['htp_test_options'][$name] : $default;
}
function update_option($name, $value, $autoload = null) {
    $GLOBALS['htp_test_options'][$name] = $value;
    return true;
}
function do_action($name) { unset($name); }
function wp_die($message) { throw new RuntimeException((string)$message); }
function admin_url($path = '') { return 'https://example.test/wp-admin/' . ltrim($path, '/'); }
function add_query_arg($args, $url) {
    return $url . (strpos($url, '?') === false ? '?' : '&') . http_build_query($args);
}
function wp_safe_redirect($url) {
    $GLOBALS['htp_redirect'] = $url;
    throw new HTP_Quote_Test_Redirect('redirect reached');
}
function htp_assert($ok, $message) {
    if (!$ok) { fwrite(STDERR, 'FAIL: ' . $message . PHP_EOL); exit(1); }
}
$root = getenv('PLUGIN_ROOT') ?: dirname(__DIR__);
require_once $root . '/includes/class-parcs-ht-quote-gate.php';
require_once $root . '/includes/class-parcs-ht-admin-group-quotes-1177.php';

function test_post_save($method, $post, $label) {
    $_POST = $post;
    $GLOBALS['htp_redirect'] = '';
    try {
        $method();
        throw new RuntimeException('No redirect after ' . $label);
    } catch (HTP_Quote_Test_Redirect $exception) {
        unset($exception);
    }
    htp_assert(strpos($GLOBALS['htp_redirect'], 'admin.php?') !== false,
        $label . ': handler did not persist and redirect');
    $saved = get_option(Parcs_HT_Quote_Gate::OPTION, array());
    htp_assert(is_array($saved), $label . ': saved options must be an array');
    return $saved;
}

$post = array(
    'year'=>'2026', 'enabled'=>'1', 'closed_enabled'=>'1', 'allow_closed_group_quotes'=>'0',
    'closed_date_exceptions'=>array(
        array('start'=>'2026-12-11', 'end'=>''),
        array('start'=>'2026-12-24','end'=>'2026-12-31'),
        array('start'=>'2026-02-30','end'=>'2026-03-02'),
        array('start'=>'2026-12-31','end'=>'2026-12-01'),
    ),
    'blocked_message_fr'=>'Cette date est fermée.',
    'blocked_message_en'=>'Closed for groups.',
    'blocked_message_de'=>'Keine Gruppen.',
);
$saved = test_post_save(array('Parcs_HT_Admin_Group_Quotes_1177','save_gate'), $post, 'modern admin-post');
htp_assert($saved['allow_closed_group_quotes']==='0', 'modern handler must persist No');
htp_assert(count($saved['closed_date_exceptions'])===2, 'invalid ranges must be discarded');
htp_assert($saved['closed_date_exceptions'][0]['end']==='2026-12-11', 'single-day range must normalize');
htp_assert($saved['blocked_message_fr']==='Cette date est fermée.', 'custom language message must persist');
htp_assert(Parcs_HT_Quote_Gate::settings()['allow_closed_group_quotes']==='0', 'saved policy must be readable');

$post['allow_closed_group_quotes'] = '1';
$post['closed_date_exceptions'] = array(array('start'=>'2026-11-11','end'=>'2026-11-12'));
$saved = test_post_save(array('Parcs_HT_Quote_Gate','save'), $post, 'legacy admin-post');
htp_assert($saved['allow_closed_group_quotes']==='1', 'legacy handler must persist Yes');
htp_assert(count($saved['closed_date_exceptions'])===1, 'legacy handler must persist exception');

$post['allow_closed_group_quotes'] = array('0');
$post['closed_date_exceptions'] = 'invalid-scalar';
$saved = test_post_save(array('Parcs_HT_Admin_Group_Quotes_1177','save_gate'), $post, 'malformed form');
htp_assert($saved['allow_closed_group_quotes']==='1', 'malformed policy input must safely default to Yes');
htp_assert($saved['closed_date_exceptions']===array(), 'malformed date ranges must be safely rejected');
echo "OK: modern and legacy WordPress admin-post saves, options, dates and malformed POST.\n";
