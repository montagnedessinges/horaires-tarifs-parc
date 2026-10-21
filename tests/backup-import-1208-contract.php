<?php
/**
 * Contrat et tests unitaires sans WordPress pour le module d'import/export 1.20.8.
 * Exécution : php tests/backup-import-1208-contract.php
 */
$root = getenv('PLUGIN_ROOT') ?: dirname(__DIR__);
$src = file_get_contents($root . '/includes/class-parcs-ht-backup-import.php');
$main = file_get_contents($root . '/horaires-tarifs-parc.php');
$overview = file_get_contents($root . '/includes/class-parcs-ht-admin-overview.php');
if (!is_string($src) || !is_string($main) || !is_string($overview)) exit(1);
function check_1208($ok, $message) {
    echo ($ok ? '[OK] ' : '[FAIL] ') . $message . PHP_EOL;
    if (!$ok) exit(1);
}
check_1208(strpos($main, "class-parcs-ht-backup-import.php") !== false, 'module included');
check_1208(strpos($main, 'Parcs_HT_Backup_Import::init();') !== false, 'admin endpoints wired');
check_1208(preg_match('/Version:\s*([0-9.]+)/', $main, $version) === 1 && version_compare($version[1], '1.20.8', '>='), 'version is at least 1.20.8');
check_1208(strpos($overview, 'Parcs_HT_Backup_Import::render_panel($year)') !== false, 'dashboard control wired');
check_1208(strpos($src, "check_admin_referer(\$nonce)") !== false, 'nonces enforced');
check_1208(strpos($src, "current_user_can('manage_options')") !== false, 'admins only');
check_1208(strpos($src, "is_uploaded_file(\$tmp)") !== false, 'verified uploads');
check_1208(strpos($src, "const MAX_UPLOAD_BYTES") !== false, 'file size cap');
check_1208(strpos($src, "Le CSV provient d’un autre parc.") !== false, 'other park rejected');
check_1208(strpos($src, "le CSV correspond à une autre année.") !== false || strpos($src,"Le CSV correspond à une autre année.")!==false, 'other year rejected');
check_1208(strpos($src, "'parcs_ht_group_quotes'") !== false, 'quote configuration exported');
check_1208(strpos($src, "'parcs_ht_group_quote_submissions'") === false, 'personal quote submissions excluded');
check_1208(strpos($src, "'parcs_ht_runtime_errors'") === false, 'runtime logs excluded');
check_1208(strpos($src, "Parcs_HT_Backup_Import") !== false, 'class present');
check_1208(strpos($src, "ZipArchive") !== false, 'ZIP global support');
check_1208(strpos($src, "'parcs_ht_backup_import_revisions'") !== false, 'pre-import revisions');

define('ABSPATH', '/');
function wp_json_encode($v, $flags=0) { return json_encode($v, $flags); }
require_once $root . '/includes/class-parcs-ht-backup-import.php';

$original = array(
    'tariffs'=>array(
        'individual'=>array(
            array('id'=>'adulte', 'cells'=>array('price'=>array('onsite'=>'12,50', 'online'=>'12')), 'label'=>array('fr'=>'Adulte', 'de'=>'Erwachsene'))
        ),
        'reduced'=>array(),
        'columns'=>array('individual'=>array('price'), 'reduced'=>array(), 'groups'=>array('private'=>1)),
        'payment_items'=>array(array('enabled'=>'0','text'=>'Cartes bancaires')),
        'notes'=>array('fr'=>"l'enfant / spécial \"test\""),
        'empty'=>array(),
        'boolean'=>true
    )
);
$flat=Parcs_HT_Backup_Import::flatten($original);
$restored=Parcs_HT_Backup_Import::inflate($flat);
check_1208($original === $restored, 'typed nested CSV rows roundtrip');
check_1208(Parcs_HT_Backup_Import::inflate(array(array('/tariffs/notes/fr','invalid-json'))) === false, 'malformed JSON rejected');
check_1208(Parcs_HT_Backup_Import::inflate(array(array('/a','1'),array('/a','2'))) === false, 'duplicate path rejected');
check_1208(Parcs_HT_Backup_Import::inflate(array(array('/a','1'),array('/a/b','2'))) === false, 'parent/child collision rejected');
check_1208(Parcs_HT_Backup_Import::inflate(array(array('/a/~3','1'))) === false, 'invalid pointer escaping rejected');
$settings=array('site_type'=>'fds','seasons'=>array(
    '2026'=>array('regular_periods'=>array(array('open'=>'09:00')), 'tariffs'=>array('individual'=>array(array('id'=>'old')))),
    '2027'=>array('regular_periods'=>array(array('open'=>'10:00')),'tariffs'=>array('individual'=>array(array('id'=>'visitor')),'reduced'=>array(),'groups'=>array(array('id'=>'group')),'columns'=>array('individual'=>array(),'reduced'=>array(),'groups'=>array(array('id'=>'column'))))),
));
$disp=array('seasons'=>array('2026'=>array('published'=>'1'),'2027'=>array('published'=>'0')));
$update=Parcs_HT_Backup_Import::merge_section(array('scope'=>'visitors','year'=>'2027','data'=>array('tariffs'=>array(
    'individual'=>array(array('id'=>'changed')), 'reduced'=>array(),'columns'=>array('individual'=>array(),'reduced'=>array()),'payment_items'=>array()
))), $settings, $disp);
check_1208(is_array($update), 'visitor merge accepted');
check_1208($update['settings']['seasons']['2027']['tariffs']['individual'][0]['id'] === 'changed', 'visitor row replaced');
check_1208($update['settings']['seasons']['2027']['tariffs']['groups'][0]['id'] === 'group', 'group rows preserved');
check_1208($update['settings']['seasons']['2027']['tariffs']['columns']['groups'][0]['id'] === 'column', 'group columns preserved');
check_1208($update['settings']['seasons']['2026'] === $settings['seasons']['2026'], 'another year not changed');
check_1208($update['display'] === $disp, 'group appearance preserved');
$group=Parcs_HT_Backup_Import::merge_section(array('scope'=>'groups','year'=>'2027','data'=>array(
    'tariffs'=>array('groups'=>array(array('id'=>'group-new')),'columns'=>array('groups'=>array(array('id'=>'group-price')))),
    'group_display'=>array('published'=>'0','intro'=>array('fr'=>'Nouveau')),
    'quote_binding'=>array('binding'=>array('child_row_id'=>'child2027'), 'legacy'=>array())
)), $settings, $disp, array('tariff_bindings'=>array('2026'=>array('adult_row_id'=>'adult2026'))));
check_1208($group['settings']['seasons']['2027']['tariffs']['groups'][0]['id'] === 'group-new', 'group rows replaced');
check_1208($group['settings']['seasons']['2027']['tariffs']['individual'][0]['id'] === 'visitor', 'visitor rows preserved');
check_1208($group['display']['seasons']['2026']['published'] === '1', 'other year group publication unchanged');
check_1208($group['display']['seasons']['2027']['published'] === '0', 'group publication status imported');
check_1208($group['quotes']['tariff_bindings']['2027']['child_row_id'] === 'child2027', '2027 quote binding replaced');
check_1208($group['quotes']['tariff_bindings']['2026']['adult_row_id'] === 'adult2026', '2026 quote binding preserved');
echo "Backup/import-export 1.20.8 contract: OK\n";