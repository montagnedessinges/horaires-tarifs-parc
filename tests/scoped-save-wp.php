<?php
/** WordPress integration test: run via wp eval-file with HTP_CASE env variable. */
if (!defined('ABSPATH')) exit(1);
$case = getenv('HTP_CASE') ?: '';
if (!defined('WP_ADMIN')) define('WP_ADMIN', true);
wp_set_current_user(1);
function htp_expect($ok, $message) {
    if (!$ok) throw new RuntimeException($message);
}
function htp_option() {
    return get_option(Parcs_HT_Defaults::OPTION, array());
}
function htp_save($workspace, $settings) {
    $_POST = array(
        'action'=>'parcs_ht_save',
        'season_year'=>'2026',
        'htp_active_tab'=>'htp-holidays',
        'htp_save_active'=>'1',
        'parcs_ht_11711_complete'=>'1',
        'parcs_ht_11711_workspace'=>$workspace,
        '_wpnonce'=>wp_create_nonce('parcs_ht_save'),
        'settings'=>$settings,
    );
    $_REQUEST = $_POST;
    Parcs_HT_Admin_Save_11711::guard_native_post();
    Parcs_HT_Admin::save();
}
switch ($case) {
case 'seed':
    $all = htp_option();
    if (!is_array($all)) $all = array();
    if (!isset($all['seasons']) || !is_array($all['seasons'])) $all['seasons'] = array();
    // A blank WordPress installation does not necessarily create 2026 by default.
    if (!isset($all['seasons']['2026'])) $all['seasons']['2026'] = Parcs_HT_Defaults::empty_season('2026');
    if (!isset($all['quote_page']) || !is_array($all['quote_page'])) $all['quote_page'] = array();
    if (!isset($all['general']) || !is_array($all['general'])) $all['general'] = array();
    $all['seasons']['2027'] = Parcs_HT_Defaults::empty_season('2027');
    $all['seasons']['2027']['season_start'] = '2027-04-12';
    $all['seasons']['2027']['season_end'] = '2027-10-31';
    $all['seasons']['2026']['regular_periods'] = array(array('enabled'=>'1','start'=>'2026-03-01','end'=>'2026-11-30','open'=>'09:00','close'=>'17:00'));
    $all['seasons']['2026']['exceptions'] = array(array('enabled'=>'1','type'=>'closed','start'=>'2026-10-31','end'=>'2026-10-31'));
    $all['quote_page']['intro'] = array('fr'=>'DEvis inchangé','en'=>'Keep quote','de'=>'Zitat');
    $all['alerts'] = array(array('enabled'=>'1','title'=>array('fr'=>'Notification inchangée')));
    $all['general']['park_name'] = array('fr'=>'Parc test inchangé','en'=>'Test','de'=>'Test');
    $all['seasons']['2026']['tariffs'] = array(
        'individual'=>array(array('enabled'=>'1','label'=>array('fr'=>'Visiteur témoin'),'price'=>'17')),
        'reduced'=>array(),
        'groups'=>array(array('enabled'=>'1','label'=>array('fr'=>'Groupe témoin'),'price'=>'14')),
        'columns'=>array('individual'=>array(),'reduced'=>array(),'groups'=>array()),
    );
    $all['tariffs'] = $all['seasons']['2026']['tariffs'];
    update_option(Parcs_HT_Defaults::OPTION, $all, false);
    update_option('parcs_ht_cse_settings', array('2026'=>array('contact_email'=>'test@example.org')), false);
    update_option('parcs_ht_github_auto_update', '1', false);
    echo "Fixture installed\n";
    break;
case 'save-periods':
    htp_save('periods', array(
        '_complete'=>array('holidays'=>'1','exceptions'=>'1','domain_rules'=>'1'),
        'general'=>array('holiday_border_width'=>'5'),
        'special_periods'=>array(),
        'public_holidays'=>array(),
        'exceptions'=>array(array('enabled'=>'1','type'=>'closed','start'=>'2026-12-24','end'=>'2026-12-24')),
        'domain_rules'=>array(),
    ));
    break;
case 'assert-periods':
    $o=htp_option();
    htp_expect(($o['seasons']['2026']['exceptions'][0]['start']??'')==='2026-12-24','Period save failed');
    htp_expect(($o['seasons']['2026']['regular_periods'][0]['open']??'')==='09:00','Period save changed regular hours');
    htp_expect(($o['quote_page']['intro']['fr']??'')==='DEvis inchangé','Period save changed quote');
    htp_expect(($o['alerts'][0]['title']['fr']??'')==='Notification inchangée','Period save changed popup');
    htp_expect(($o['seasons']['2027']['season_start']??'')==='2027-04-12','Period save changed next year');
    htp_expect(($o['general']['park_name']['fr']??'')==='Parc test inchangé','Period save changed park name');
    htp_expect(($o['general']['holiday_border_width']??'')==='5','Period settings were not saved');
    htp_expect((get_option('parcs_ht_cse_settings')['2026']['contact_email']??'')==='test@example.org','CSE was changed');
    htp_expect(get_option('parcs_ht_github_auto_update')==='1','Saving periods disabled the GitHub updater');
    echo "Periods save isolated OK\n";
    break;
case 'save-retail':
    htp_save('retail',array(
        '_complete'=>array('tariffs'=>'1'),
        'tariffs'=>array('individual'=>array(array('enabled'=>'1','label'=>array('fr'=>'Visiteur tarif nouveau','en'=>'New visitor','de'=>'Besucher'),'price'=>'20')),
            'reduced'=>array(),'columns'=>array('individual'=>array(),'reduced'=>array()))
    ));
    break;
case 'assert-retail':
    $o=htp_option();
    $t=$o['seasons']['2026']['tariffs']??array();
    htp_expect(($t['individual'][0]['label']['fr']??'')==='Visiteur tarif nouveau','Retail save did not update visitors');
    htp_expect(($t['groups'][0]['label']['fr']??'')==='Groupe témoin','Retail save modified groups');
    htp_expect(($o['seasons']['2026']['exceptions'][0]['start']??'')==='2026-12-24','Retail save changed exceptions');
    htp_expect(($o['seasons']['2027']['season_start']??'')==='2027-04-12','Retail save changed other year');
    htp_expect(($o['quote_page']['intro']['fr']??'')==='DEvis inchangé','Retail save changed quote');
    echo "Retail save isolated OK\n";
    break;
case 'save-groups':
    htp_save('groups',array(
        '_complete'=>array('tariffs'=>'1'),
        'tariffs'=>array('groups'=>array(array('enabled'=>'1','label'=>array('fr'=>'Groupe nouveau','en'=>'Group','de'=>'Gruppe'),'price'=>'18')),
            'columns'=>array('groups'=>array()))
    ));
    break;
case 'assert-groups':
    $o=htp_option();$t=$o['seasons']['2026']['tariffs']??array();
    htp_expect(($t['groups'][0]['label']['fr']??'')==='Groupe nouveau','Groups save did not update groups');
    htp_expect(($t['individual'][0]['label']['fr']??'')==='Visiteur tarif nouveau','Groups save changed visitor tariffs');
    htp_expect(($o['general']['park_name']['fr']??'')==='Parc test inchangé','Groups save changed park name');
    htp_expect(($o['quote_page']['intro']['fr']??'')==='DEvis inchangé','Groups save changed quote');
    htp_expect(get_option('parcs_ht_github_auto_update')==='1','Saving groups disabled the GitHub updater');
    $daily=get_option(Parcs_HT_Save_Integrity::REVISIONS_OPTION,array());
    htp_expect(count((array)$daily)<=1,'Multiple complete snapshots taken on same day');
    $legacy=get_option(Parcs_HT_Health::REVISIONS_OPTION,array());
    htp_expect(count((array)$legacy)<=1,'Legacy revisions exploded during scoped saves');
    echo "Groups save isolated and snapshots bounded OK; peak memory ".round(memory_get_peak_usage(true)/1048576,1)." MiB\n";
    break;
default:
    throw new RuntimeException('Unknown HTP_CASE: '.$case);
}
