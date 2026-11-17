<?php
define('ABSPATH', __DIR__);
function esc_attr($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
$root = getenv('PLUGIN_ROOT') ?: dirname(__DIR__);
require_once $root . '/includes/class-parcs-ht-shortcodes.php';
require_once $root . '/includes/class-parcs-ht-tariff-display.php';
require_once $root . '/includes/class-parcs-ht-tariff-public-fixes.php';
require_once $root . '/includes/class-parcs-ht-tariff-shared-1168.php';
function export_controls_call($class, $method, $args) {
    $reflection = new ReflectionMethod($class, $method);
    $reflection->setAccessible(true);
    return $reflection->invokeArgs(null, $args);
}
foreach (array('fr','en','de') as $language) {
    foreach (array('2026','2027') as $year) {
        $tariffs = array('print'=>array('enabled'=>'1','pdf_enabled'=>'1'));
        foreach (array('Parcs_HT_Tariff_Shared_1168','Parcs_HT_Tariff_Public_Fixes') as $class) {
            if (export_controls_call($class, 'display_call', array('export_actions', array($tariffs,$language,$year))) !== '') throw new RuntimeException('Public tariff controls remain');
        }
        if (export_controls_call('Parcs_HT_Shortcodes','tariff_export_actions',array($tariffs,$language,$year)) !== '') throw new RuntimeException('Legacy tariff controls remain');
        if (export_controls_call('Parcs_HT_Shortcodes','schedule_export_button',array($language)) !== '') throw new RuntimeException('Calendar controls remain');
        $calendar = export_controls_call('Parcs_HT_Shortcodes','calendar',array('calendar-test',$language,''));
        if (strpos($calendar,'parcs-ht-calendar-grid') === false || strpos($calendar,'data-htp-next') === false || strpos($calendar,'parcs_ht_schedule_pdf') !== false) throw new RuntimeException('Calendar skeleton changed');
    }
}
foreach (array('schedule_pdf_endpoint','tariffs_pdf_endpoint','tariffs_print_endpoint','pregenerate_exports') as $method) {
    if (!(new ReflectionMethod('Parcs_HT_Shortcodes',$method))->isPublic()) throw new RuntimeException('Export endpoint removed');
}
echo "Public export controls: real reflection callers and calendar render OK.\n";
