<?php
// Disposable WordPress only: exercise nonce/capability, backups, stale reports and readback.
if (!defined('ABSPATH')) throw new RuntimeException('Run with wp eval-file on a disposable site.');
wp_set_current_user(1);
function advent_wp_check($ok, $message) { if (!$ok) throw new RuntimeException($message); }
class Advent_Test_Redirect extends RuntimeException {}
add_filter('wp_redirect', static function ($url) { throw new Advent_Test_Redirect($url); });
$analyze = new ReflectionMethod('Parcs_HT_Advent_Admin', 'analyze_import');
if (PHP_VERSION_ID < 80100) $analyze->setAccessible(true);
foreach (array('fds','mds') as $park) {
    update_option(Parcs_HT_Defaults::OPTION, array('site_type'=>$park));
    $store = Parcs_HT_Advent::empty_store();
    $store['campaigns']['2026'] = Parcs_HT_Advent::default_campaign('2026',$park,'2026');
    $store['campaigns']['2027'] = Parcs_HT_Advent::default_campaign('2027',$park,'2027');
    Parcs_HT_Advent::save_store($store);
    $current = Parcs_HT_Advent::campaign('2026', true);
    $rows = array(array('schema_version'=>'3','campagne_id'=>'2026','parc_code'=>$park,'partenaire_id'=>'demo','nom'=>'Test','description_fr'=>'Première description'));
    foreach (array('Première description','Deuxième description','Deuxième description') as $description) {
        $rows[0]['description_fr'] = $description;
        $report = $analyze->invoke(null, $rows, $current);
        advent_wp_check(!$report['errors'], 'Valid report');
        $token = 'test' . $park;
        $key = 'parcs_ht_advent_import_' . get_current_user_id() . '_' . $token;
        // Use the canonical key, including its current prefix.
        $key_method = new ReflectionMethod('Parcs_HT_Advent_Admin', 'import_transient_key');
        if (PHP_VERSION_ID < 80100) $key_method->setAccessible(true);
        $key = $key_method->invoke(null,$token);
        set_transient($key,$report,1800);
        $_POST = array('campaign_id'=>'2026','import_token'=>$token);
        $_REQUEST['_wpnonce'] = wp_create_nonce('parcs_ht_advent_apply_import');
        $before = Parcs_HT_Advent::store();
        try { Parcs_HT_Advent_Admin::apply_import(); throw new RuntimeException('Expected redirect'); }
        catch (Advent_Test_Redirect $redirect) { advent_wp_check(strpos($redirect->getMessage(),'advent_notice=imported') !== false, 'Import success redirect'); }
        $current = Parcs_HT_Advent::campaign('2026',true);
        advent_wp_check($current['partners']['demo']['description_fr'] === $description, 'Real handler persisted description');
        advent_wp_check(Parcs_HT_Advent::store()['campaigns']['2027'] === $before['campaigns']['2027'], 'Real handler preserves other year');
        advent_wp_check(get_transient($key) === false, 'Applied token deleted');
        global $wpdb;
        $names = $wpdb->get_col($wpdb->prepare("SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE %s", $wpdb->esc_like('parcs_ht_advent_backup_2026_') . '%'));
        $found = false;
        foreach ($names as $name) if (get_option($name) === $before) $found = true;
        advent_wp_check($found, 'Exact preimport backup exists');
    }
    $report = $analyze->invoke(null,$rows,$current);
    set_transient($key,$report,1800);
    $changed = Parcs_HT_Advent::store(); $changed['campaigns']['2026']['nom_campagne'] = 'Concurrent edit';
    Parcs_HT_Advent::save_store($changed);
    try { Parcs_HT_Advent_Admin::apply_import(); throw new RuntimeException('Expected stale redirect'); }
    catch (Advent_Test_Redirect $redirect) { advent_wp_check(strpos($redirect->getMessage(),'advent_notice=import_stale') !== false, 'Stale report rejected'); }
    advent_wp_check(Parcs_HT_Advent::campaign('2026',true)['nom_campagne'] === 'Concurrent edit', 'Concurrent edit preserved');
    echo "[OK] $park real apply handler, backup, repeated save, year isolation, stale protection\n";
}
