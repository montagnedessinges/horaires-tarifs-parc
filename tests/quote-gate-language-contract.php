<?php

$root = dirname(__DIR__);
require_once __DIR__ . '/release-contract.php';

$version = release_contract_plugin_version($root);
$gate = file_get_contents($root . '/includes/class-parcs-ht-quote-gate.php');
$admin = file_get_contents($root . '/includes/class-parcs-ht-admin-groups.php');
$js = file_get_contents($root . '/assets/admin-groups.js');
$languages = file_get_contents($root . '/includes/class-parcs-ht-quote-languages.php');
$quote_save = file_get_contents($root . '/includes/class-parcs-ht-quote-page-save.php');

release_contract_require_all($gate, array(
    'closed_message_fr', 'closed_message_en', 'closed_message_de',
    'unavailable_message_fr', 'unavailable_message_en', 'unavailable_message_de',
), 'Quote gate languages');

release_contract_require_all($admin, array(
    "'closed_message_' . \$lang",
    "'unavailable_message_' . \$lang",
), 'Quote admin language save');

release_contract_require_all($js, array(
    'closed_message_fr','closed_message_en','closed_message_de',
    'unavailable_message_fr','unavailable_message_en','unavailable_message_de',
), 'Quote admin language UI');

release_contract_require_all($gate, array(
    "'fr'=>'FR'",
    "'en'=>'EN'",
    "'de'=>'DE'",
    "self::localized_message(\$s, 'closed', \$language)",
    "self::localized_message(\$s, 'unavailable', \$language)",
), 'Public quote language selection');

release_contract_transition(
    $version,
    '1.15.13',
    static function () {},
    static function () use ($gate) {
        release_contract_require_all($gate, array(
            'public static function status_for_date($date)',
            '$closed = true;',
            'checkdate($month, $day, $year)',
            'Parcs_HT_Schedule::resolve_day',
            "return array('valid'=>false, 'tariffs'=>false, 'closed'=>true, 'year'=>'')",
        ), 'Fail-closed group quote date status');
        release_contract_forbid($gate, array(
            'fail-open quote date default' => '$closed = false;',
        ), 'Fail-closed group quote date status');
    }
);

release_contract_require_all($languages, array(
    'Parcs_HT_Defaults::OPTION',
    "'form_shortcodes'",
    'maybe_migrate_legacy',
), 'Unified quote language settings');

release_contract_forbid($languages, array(
    'legacy standalone option write' => 'update_option(self::OPTION, $clean',
), 'Unified quote language settings');

release_contract_transition(
    $version,
    '1.10.0',
    static function () use ($quote_save) {
        release_contract_require_all($quote_save, array(
            'admin_post_parcs_ht_save',
            'mark_explicit_empty_lists',
            "['quote_page'][\$list] = null",
            "'form_shortcodes'",
        ), 'Legacy quote page deletion protection');
    },
    static function () use ($quote_save) {
        release_contract_require_all($quote_save, array(
            'admin_post_parcs_ht_save',
            'mark_explicit_empty_lists',
            'respect_explicit_list_deletions',
            'posted_settings',
            'wp_verify_nonce',
            "wp_unslash(\$_POST['settings'])",
            'wp_slash($posted_settings)',
            "'form_shortcodes'",
        ), 'Quote page deletion protection 1.10+');

        release_contract_require_regex($quote_save, array(
            'missing list is marked deleted before merge' => '/if\s*\(\s*!array_key_exists\(\$list,\s*\$quote_page\)\s*\)\s*\{[\s\S]*?\$quote_page\[\$list\]\s*=\s*null\s*;/',
            'deleted list is forced empty after merge' => '/if\s*\(\s*!array_key_exists\(\$list,\s*\$posted_quote\)\s*\|\|\s*\$posted_quote\[\$list\]\s*===\s*null\s*\|\|\s*!is_array\(\$posted_quote\[\$list\]\)\s*\)\s*\{[\s\S]*?\$new_value\[\'quote_page\'\]\[\$list\]\s*=\s*array\(\)\s*;/',
        ), 'Quote page deletion protection 1.10+');
    }
);

echo "Quote gate language contract OK for {$version}\n";
