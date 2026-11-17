<?php
/** Runs only in the disposable CI WordPress database, via wp eval-file. */
if (!defined('ABSPATH') || !defined('WP_CLI') || !WP_CLI) exit(1);
$phase = getenv('HTP_EXPORT_PHASE');
$scenario = getenv('HTP_EXPORT_SCENARIO') ?: 'mds';
$directory = getenv('HTP_EXPORT_SNAPSHOTS') ?: '/tmp/htp-export-snapshots';
function export_wp_expect($ok, $message) { if (!$ok) throw new RuntimeException($message); }
function export_wp_call($method, $args) {
    $r = new ReflectionMethod('Parcs_HT_Shortcodes', $method);
    $r->setAccessible(true);
    return $r->invokeArgs(null, $args);
}
if ($phase === 'seed') {
    $all = Parcs_HT_Defaults::get();
    $all['site_type'] = $scenario === 'fds' ? 'fds' : 'mds';
    $all['active_season_year'] = '2026';
    $all['general']['year'] = '2026';
    $all['general']['tickets_url'] = array_fill_keys(array('fr','en','de'), 'https://example.test/tickets');
    $all['seasons'] = array();
    foreach (array('2026','2027') as $year) {
        $season = Parcs_HT_Defaults::empty_season($year);
        $season['published'] = '1';
        $season['calendar_visible'] = '1';
        $season['retail_tariffs_visible'] = $scenario === 'groups-only' ? '0' : '1';
        $season['group_tariffs_visible'] = '1';
        $season['groups_schedule_visible'] = '1';
        $season['public_display_from'] = '';
        $season['public_display_until'] = '';
        $season['season_start'] = $year . '-01-01';
        $season['season_end'] = $year . '-12-31';
        $season['regular_periods'] = array(array('enabled'=>'1','start'=>$year.'-01-01','end'=>$year.'-12-31','weekdays'=>array('1','2','3','4','5','6','7'),'open'=>'09:00','close'=>'18:00'));
        $tariffs = array('columns'=>array(), 'print'=>array('enabled'=>'1','pdf_enabled'=>'1'));
        foreach (array('individual','reduced','groups') as $kind) {
            $tariffs['columns'][$kind] = array(array('id'=>'price','visible'=>'1','label'=>array_fill_keys(array('fr','en','de'),'Price')));
            $tariffs[$kind] = array(array('id'=>'row-'.$kind,'enabled'=>'1','label'=>array_fill_keys(array('fr','en','de'),'TEST-'.$kind.'-'.$year),'price'=>'12','cells'=>array('price'=>array('value'=>'12'))));
        }
        $season['tariffs'] = $tariffs;
        $all['seasons'][$year] = $season;
    }
    $all['tariffs'] = $all['seasons']['2026']['tariffs'];
    update_option(Parcs_HT_Defaults::OPTION, $all, false);
    echo "Seeded ".$scenario."\n";
    return;
}
wp_set_current_user(0);
require_once PARCS_HT_DIR . 'includes/class-parcs-ht-shortcodes.php';
$before = get_option(Parcs_HT_Defaults::OPTION);
export_wp_expect(count(Parcs_HT_Public_Visibility::calendar_years()) === 2, 'Fixture calendars not published');
export_wp_expect(count(Parcs_HT_Public_Visibility::retail_years()) === ($scenario === 'groups-only' ? 0 : 2), 'Fixture retail visibility incorrect');
$results = array();
$exports_seen = 0;
foreach (array('fr','en','de') as $lang) {
    foreach (array('parc_horaires_tarifs','parc_calendrier','parc_tableau_tarifs','parc_tarifs_groupes','parc_groupes_horaires_tarifs') as $base) {
        $tag = $base.'_'.$lang;
        export_wp_expect(shortcode_exists($tag), 'Missing shortcode '.$tag);
        $html = do_shortcode('['.$tag.']');
        export_wp_expect(strlen($html) > 50, 'Empty rendered page '.$tag);
        if ($base === 'parc_horaires_tarifs') {
            export_wp_expect(strpos($html,'parcs-ht-calendar-grid') !== false, 'Missing calendar');
            if ($scenario !== 'groups-only') export_wp_expect(strpos($html,'TEST-individual-2026') !== false && strpos($html,'TEST-individual-2027') !== false, 'Missing retail years');
        }
        $pattern = '~<div class="(?:parcs-ht-schedule-export|parcs-ht-tariff-export-actions|parcs-ht-tariff-ui__export)">.*?</div>~s';
        $clean = preg_replace($pattern, '', $html, -1, $count);
        $exports_seen += $count;
        if ($phase === 'candidate') export_wp_expect($count === 0, 'Public export controls remain in '.$tag);
        $results[$tag] = $clean;
    }
    foreach (array('Parcs_HT_Tariff_Public_Fixes','Parcs_HT_Tariff_Shared_1168') as $class) {
        $r = new ReflectionMethod($class, 'display_call');
        $r->setAccessible(true);
        $result = $r->invoke(null,'export_actions',array(array('print'=>array('enabled'=>'1','pdf_enabled'=>'1')),$lang,'2026'));
        if ($phase === 'candidate') export_wp_expect($result === '', 'Reflection helper still returns controls');
    }
    // Exercise unchanged PDF generators with real WordPress and published settings.
    $settings = Parcs_HT_Defaults::settings();
    $schedule = export_wp_call('schedule_export_context',array($settings,$lang));
    $tariffs = export_wp_call('tariff_export_context',array($settings,$lang));
    foreach (array(export_wp_call('build_schedule_pdf',array($schedule)),export_wp_call('build_tariff_pdf',array($tariffs,false))) as $pdf) {
        export_wp_expect(strpos($pdf,'%PDF-') === 0 && strlen($pdf) > 500, 'PDF generation failed');
    }
}
foreach (array('tariffs_print','tariffs_pdf','schedule_pdf') as $endpoint) {
    export_wp_expect(has_action('admin_post_parcs_ht_'.$endpoint) !== false && has_action('admin_post_nopriv_parcs_ht_'.$endpoint) !== false, 'Existing export route lost');
}
export_wp_expect(get_option(Parcs_HT_Defaults::OPTION) === $before, 'Rendering modified saved settings');
if (!is_dir($directory)) wp_mkdir_p($directory);
$file = $directory.'/'.$scenario.'.json';
if ($phase === 'baseline') {
    export_wp_expect($exports_seen > 0, 'Baseline did not exercise export buttons');
    file_put_contents($file, wp_json_encode($results));
} else {
    $expected = json_decode(file_get_contents($file),true);
    foreach ($results as $key=>$html) export_wp_expect(isset($expected[$key]) && $expected[$key] === $html, 'HTML changed outside export controls: '.$scenario.' '.$key);
}
echo "PASS ".$phase." ".$scenario.": 15 shortcode renders, reflection calls, PDF builders, routes and data isolation\n";
