<?php

$root = dirname(__DIR__);
$gate = file_get_contents($root . '/includes/class-parcs-ht-quote-gate.php');
$admin = file_get_contents($root . '/includes/class-parcs-ht-admin-groups.php');
$js = file_get_contents($root . '/assets/admin-groups.js');
$languages = file_get_contents($root . '/includes/class-parcs-ht-quote-languages.php');
$quote_save = file_get_contents($root . '/includes/class-parcs-ht-quote-page-save.php');

$required_gate = array(
    'closed_message_fr', 'closed_message_en', 'closed_message_de',
    'unavailable_message_fr', 'unavailable_message_en', 'unavailable_message_de',
);

foreach ($required_gate as $key) {
    if (strpos($gate, $key) === false) {
        fwrite(STDERR, "Missing quote gate key: {$key}\n");
        exit(1);
    }
}

foreach (array("'closed_message_' . \$lang", "'unavailable_message_' . \$lang") as $admin_marker) {
    if (strpos($admin, $admin_marker) === false) {
        fwrite(STDERR, "Missing admin save marker: {$admin_marker}\n");
        exit(1);
    }
}

foreach (array('closed_message_fr','closed_message_en','closed_message_de','unavailable_message_fr','unavailable_message_en','unavailable_message_de') as $js_key) {
    if (strpos($js, $js_key) === false) {
        fwrite(STDERR, "Missing admin UI key: {$js_key}\n");
        exit(1);
    }
}

foreach (array("'fr'=>'FR'", "'en'=>'EN'", "'de'=>'DE'") as $language_marker) {
    if (strpos($gate, $language_marker) === false) {
        fwrite(STDERR, "Missing language editor marker: {$language_marker}\n");
        exit(1);
    }
}

if (strpos($gate, "self::localized_message(\$s, 'closed', \$language)") === false || strpos($gate, "self::localized_message(\$s, 'unavailable', \$language)") === false) {
    fwrite(STDERR, "Public quote messages are not selected by language.\n");
    exit(1);
}

foreach (array('Parcs_HT_Defaults::OPTION', "'form_shortcodes'", 'maybe_migrate_legacy') as $marker) {
    if (strpos($languages, $marker) === false) {
        fwrite(STDERR, "Quote language settings are not unified with main settings: {$marker}\n");
        exit(1);
    }
}

if (strpos($languages, 'update_option(self::OPTION, $clean') !== false) {
    fwrite(STDERR, "Quote language settings still write to the legacy standalone option.\n");
    exit(1);
}

foreach (array('admin_post_parcs_ht_save', 'mark_explicit_empty_lists', "['quote_page'][$list] = null", "'form_shortcodes'") as $marker) {
    if (strpos($quote_save, $marker) === false) {
        fwrite(STDERR, "Quote page deletion protection is incomplete: {$marker}\n");
        exit(1);
    }
}

echo "Quote gate language contract OK\n";
