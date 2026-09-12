<?php

if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

$settings = get_option('parcs_ht_settings', array());
$delete = is_array($settings) && (string)($settings['general']['delete_data_on_uninstall'] ?? '0') === '1';
if (!$delete) return;

foreach (array(
    'parcs_ht_settings',
    'parcs_ht_settings_backup_pre_1_1_0',
    'parcs_ht_has_popup_source',
    'parcs_ht_export_revision',
    'parcs_ht_github_token',
    'parcs_ht_github_auto_update',
    'parcs_ht_github_last_check',
    'parcs_ht_last_health_notification',
    'parcs_ht_runtime_errors',
    'parcs_ht_settings_revisions',
    'parcs_ht_group_quotes',
    'parcs_ht_quote_language_shortcodes',
    'parcs_ht_quote_gate',
    'parcs_ht_group_tariff_settings',
    'parcs_ht_tariff_id_registry',
    'parcs_ht_pedagogical_guides',
    'parcs_ht_guide_appearance',
    'parcs_ht_pedagogical_guide_ids',
    'parcs_ht_pedagogical_guide_stats_meta',
    'parcs_ht_pedagogical_guide_stats_db_version',
    'parcs_ht_advent',
    'parcs_ht_advent_appearance',
    'parcs_ht_advent_ux',
) as $option) {
    delete_option($option);
}
delete_site_transient('parcs_ht_github_latest_release');

global $wpdb;
$guide_stats_table = $wpdb->prefix . 'parcs_ht_guide_clicks';
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Suppression volontaire de la table dédiée uniquement lorsque l'option de suppression des données est activée.
$wpdb->query("DROP TABLE IF EXISTS `{$guide_stats_table}`");

$uploads = wp_upload_dir();
$directory = trailingslashit($uploads['basedir']).'horaires-tarifs-parc-exports';
if (is_dir($directory)) {
    foreach ((array)glob(trailingslashit($directory).'*') as $file) {
        if (is_file($file)) wp_delete_file($file);
    }
    foreach (array('.htaccess','index.html') as $file) {
        $path = trailingslashit($directory).$file;
        if (is_file($path)) wp_delete_file($path);
    }
    @rmdir($directory);
}
