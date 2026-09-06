<?php

$root = getenv('PLUGIN_ROOT') ?: dirname(__DIR__);
$ids = file_get_contents($root . '/includes/class-parcs-ht-tariff-identities.php');
$quotes = file_get_contents($root . '/includes/class-parcs-ht-group-quotes.php');
$admin = file_get_contents($root . '/includes/class-parcs-ht-admin-groups.php');
$admin_js = file_get_contents($root . '/assets/admin-groups.js');
$main = file_get_contents($root . '/horaires-tarifs-parc.php');

function tariff_identity_check($condition, $message) {
    if (!$condition) { fwrite(STDERR, '[FAIL] ' . $message . PHP_EOL); exit(1); }
    echo '[OK] ' . $message . PHP_EOL;
}

tariff_identity_check(strpos($ids, "'^tariff_row_[0-9]{6,}$'") !== false || strpos($ids, '/^tariff_row_[0-9]{6,}$/') !== false, 'row IDs use permanent tariff_row_NNNNNN format');
tariff_identity_check(strpos($ids, "'^tariff_col_[0-9]{6,}$'") !== false || strpos($ids, '/^tariff_col_[0-9]{6,}$/') !== false, 'column IDs use permanent tariff_col_NNNNNN format');
tariff_identity_check(strpos($ids, "'next_row'") !== false && strpos($ids, "'used_rows'") !== false && strpos($ids, "'next_col'") !== false && strpos($ids, "'used_cols'") !== false, 'registry keeps monotonic counters and tombstones');
tariff_identity_check(strpos($ids, "pre_update_option_") !== false && strpos($ids, 'preserve_ids_on_update') !== false, 'IDs are preserved during tariff saves');
tariff_identity_check(strpos($ids, 'ensure_existing_ids') !== false && strpos($main, 'Parcs_HT_Tariff_Identities::init()') !== false, 'existing tariffs are migrated automatically');
tariff_identity_check(strpos($admin_js, 'data-htp-tariff-id-badge') !== false && strpos($admin_js, 'data-htp-tariff-col-id-badge') !== false, 'row and column IDs are visible in administration');
tariff_identity_check(strpos($admin_js, 'ID attribué à l’enregistrement') !== false, 'new IDs are server-assigned instead of editable by the administrator');
tariff_identity_check(strpos($admin, "'column_id'") !== false && strpos($admin, "'_row_id'") !== false, 'quote binding saves permanent row and column IDs');
tariff_identity_check(strpos($quotes, 'row_by_id') !== false && strpos($quotes, 'column_exists') !== false, 'quote runtime resolves tariff identities by ID');
tariff_identity_check(strpos($quotes, 'private static function resolve_row') === false, 'legacy runtime row index/label resolver is removed');
tariff_identity_check(strpos($quotes, "isset(\$row['price'])") === false, 'quote runtime no longer falls back to legacy row price');
tariff_identity_check(strpos($admin_js, "clone.querySelector('[data-htp-tariff-row-id-input]')") !== false, 'manual row duplication clears the identity before save');

echo "Tariff identities contract: OK\n";
