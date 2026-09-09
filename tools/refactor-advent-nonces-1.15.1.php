<?php
$root = dirname(__DIR__);
$path = $root . '/includes/class-parcs-ht-advent-admin.php';
$content = file_get_contents($path);
if ($content === false) exit(1);

function replace_exact($content, $from, $to, $label) {
    $count = 0;
    $content = str_replace($from, $to, $content, $count);
    if ($count !== 1) {
        fwrite(STDERR, "Expected one replacement for {$label}, got {$count}.\n");
        exit(1);
    }
    return $content;
}

$oldHelper = <<<'TXT'
    private static function campaign_id_from_request($source = 'get') {
        if ($source === 'post') {
            return isset($_POST['campaign_id']) ? sanitize_key(wp_unslash($_POST['campaign_id'])) : '';
        }
        return isset($_GET['campaign']) ? sanitize_key(wp_unslash($_GET['campaign'])) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Sélection de campagne en lecture seule.
    }
TXT;
$newHelper = <<<'TXT'
    private static function campaign_id_from_request() {
        return isset($_GET['campaign']) ? sanitize_key(wp_unslash($_GET['campaign'])) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Sélection de campagne en lecture seule.
    }
TXT;
$content = replace_exact($content, $oldHelper, $newHelper, 'GET-only campaign helper');

$content = replace_exact(
    $content,
    "        wp_nonce_field(\$action . '_' . \$campaign_id);",
    "        wp_nonce_field(\$action);",
    'shared form nonce'
);

$nonceForms = array(
    "wp_nonce_field('parcs_ht_advent_csv_template_' . \$campaign['campagne_id']);" => "wp_nonce_field('parcs_ht_advent_csv_template');",
    "wp_nonce_field('parcs_ht_advent_import_csv_' . \$campaign['campagne_id']);" => "wp_nonce_field('parcs_ht_advent_import_csv');",
    "wp_nonce_field('parcs_ht_advent_apply_import_' . \$campaign['campagne_id']);" => "wp_nonce_field('parcs_ht_advent_apply_import');",
);
foreach ($nonceForms as $from => $to) {
    $content = replace_exact($content, $from, $to, $from);
}

$handlers = array(
    'save_campaign' => 'parcs_ht_advent_save_campaign',
    'save_content' => 'parcs_ht_advent_save_content',
    'save_partner' => 'parcs_ht_advent_save_partner',
    'save_result' => 'parcs_ht_advent_save_result',
    'csv_template' => 'parcs_ht_advent_csv_template',
    'import_csv' => 'parcs_ht_advent_import_csv',
    'apply_import' => 'parcs_ht_advent_apply_import',
);
foreach ($handlers as $method => $nonce) {
    $from = "    public static function {$method}() {\n        self::require_admin();\n        \$campaign_id = self::campaign_id_from_request('post');\n        check_admin_referer('{$nonce}_' . \$campaign_id);";
    $to = "    public static function {$method}() {\n        self::require_admin();\n        check_admin_referer('{$nonce}');\n        \$campaign_id = isset(\$_POST['campaign_id']) ? sanitize_key(wp_unslash(\$_POST['campaign_id'])) : '';";
    $content = replace_exact($content, $from, $to, $method . ' nonce ordering');
}

file_put_contents($path, $content);
@unlink(__FILE__);
@unlink($root . '/.github/workflows/refactor-advent-nonces-1.15.1.yml');
echo "Advent nonce architecture refactored.\n";
