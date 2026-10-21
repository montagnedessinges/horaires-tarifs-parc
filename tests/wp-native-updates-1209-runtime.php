<?php
/**
 * WordPress updates integration runtime contract for 1.20.9.
 * No external GitHub network call: simulates a valid official release.
 */
$root = getenv('PLUGIN_ROOT') ?: dirname(__DIR__);
if (!defined('ABSPATH')) define('ABSPATH', '/');
if (!defined('MINUTE_IN_SECONDS')) define('MINUTE_IN_SECONDS', 60);
if (!defined('PARCS_HT_FILE')) define('PARCS_HT_FILE', $root . '/horaires-tarifs-parc.php');
if (!defined('PARCS_HT_VERSION')) define('PARCS_HT_VERSION', '1.20.9');
if (!defined('PARCS_HT_DISPLAY_NAME')) define('PARCS_HT_DISPLAY_NAME', 'Gestion du parc');

$GLOBALS['native_hooks'] = array();
$GLOBALS['native_release_cache'] = array(
    'tag_name'=>'v1.20.10',
    'html_url'=>'https://github.com/montagnedessinges/horaires-tarifs-parc/releases/tag/v1.20.10',
    'assets'=>array(
        array('name'=>'horaires-tarifs-parc.zip','browser_download_url'=>'https://github.com/montagnedessinges/horaires-tarifs-parc/releases/download/v1.20.10/horaires-tarifs-parc.zip'),
        array('name'=>'horaires-tarifs-parc.zip.sha256','browser_download_url'=>'https://github.com/montagnedessinges/horaires-tarifs-parc/releases/download/v1.20.10/horaires-tarifs-parc.zip.sha256')
    ),
);
function add_filter($name,$callable,$priority=10,$args=1) {
    $GLOBALS['native_hooks'][$name]=array('callback'=>$callable,'priority'=>$priority,'args'=>$args);
}
function plugin_basename($path){return 'horaires-tarifs-parc/horaires-tarifs-parc.php';}
function get_site_transient($key){return $key==='parcs_ht_github_latest_release' ? $GLOBALS['native_release_cache'] : false;}
function get_option($key, $default=''){return $default;}
function apply_filters($key, $value){return $value;}
function is_wp_error($x){return false;}
function esc_url_raw($url){return $url;}
function fail_native($message){fwrite(STDERR,"[FAIL] ".$message.PHP_EOL);exit(1);}
function check_native($ok,$msg){if(!$ok)fail_native($msg);echo "[OK] ".$msg.PHP_EOL;}
require_once $root . '/includes/class-parcs-ht-updater.php';
Parcs_HT_Updater::init();
check_native(isset($GLOBALS['native_hooks']['update_plugins_github.com']),'custom Update URI filter active');
check_native(isset($GLOBALS['native_hooks']['pre_set_site_transient_update_plugins']),'native WP update-list hook active');
check_native(isset($GLOBALS['native_hooks']['auto_update_plugin']),'WordPress auto-update integration preserved');

$other = 'another-plugin/example.php';
$plugin = 'horaires-tarifs-parc/horaires-tarifs-parc.php';
$transient = (object)array(
    'checked'=>array($plugin=>'1.20.9',$other=>'2.0.0'),
    'no_update'=>array($plugin=>(object)array('new_version'=>'1.20.9')),
    'response'=>array($other=>(object)array('new_version'=>'3.0.0')),
);
$updated = Parcs_HT_Updater::sync_native_updates($transient);
check_native(isset($updated->response[$plugin]),'GitHub release inserted in Extensions / Updates');
check_native($updated->response[$plugin]->new_version==='1.20.10','WordPress recognizes newer plugin version');
check_native($updated->response[$plugin]->plugin===$plugin,'native plugin filesystem key preserved');
check_native($updated->response[$plugin]->slug==='horaires-tarifs-parc','WordPress slug correct');
check_native($updated->response[$plugin]->package==='https://github.com/montagnedessinges/horaires-tarifs-parc/releases/download/v1.20.10/horaires-tarifs-parc.zip','native update uses verified-release asset URL');
check_native(!isset($updated->no_update[$plugin]),'old no_update entry removed');
check_native(isset($updated->response[$other]),'other plugin updates untouched');

$already=(object)array('checked'=>array($plugin=>'1.20.10'),'response'=>array(),'no_update'=>array());
$already=Parcs_HT_Updater::sync_native_updates($already);
check_native(empty($already->response),'no update if already installed');

$ignored=(object)array('checked'=>array($other=>'1.0.0'),'response'=>array(),'no_update'=>array());
check_native(Parcs_HT_Updater::sync_native_updates($ignored)===$ignored,'no update if plugin absent');

$existing=(object)array('checked'=>array($plugin=>'1.20.9'),'response'=>array($plugin=>(object)array('new_version'=>'1.21.0')),'no_update'=>array());
$existing=Parcs_HT_Updater::sync_native_updates($existing);
check_native($existing->response[$plugin]->new_version==='1.21.0','WordPress core existing update remains authoritative');

echo "Native WordPress update integration 1.20.9 OK\n";