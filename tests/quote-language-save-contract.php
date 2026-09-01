<?php

$root = dirname(__DIR__);
$file = file_get_contents($root . '/includes/class-parcs-ht-quote-languages.php');

$required = array(
    "wp_unslash(\$_POST['forms'])",
    "sanitize_form_shortcode",
    "update_option(Parcs_HT_Defaults::OPTION, \$all, false)",
    "get_option(Parcs_HT_Defaults::OPTION, array())",
    "['quote_page']['form_shortcodes'] = \$clean",
    "do_action('litespeed_purge_all')",
    "WordPress n’a pas confirmé l’enregistrement des formulaires de devis",
);

foreach ($required as $marker) {
    if (strpos($file, $marker) === false) {
        fwrite(STDERR, "Missing quote-language save marker: {$marker}\n");
        exit(1);
    }
}

if (strpos($file, "update_option(self::OPTION, \$clean, false)") !== false) {
    fwrite(STDERR, "Quote language settings must not write to the legacy standalone option.\n");
    exit(1);
}

if (strpos($file, "map_deep(wp_unslash(\$_POST['forms'])") !== false) {
    fwrite(STDERR, "The language shortcode form must not be pre-transformed before per-field validation.\n");
    exit(1);
}

if (strpos($file, "if (\$value === null)") === false || strpos($file, "wp_die('Le shortcode du formulaire '") === false) {
    fwrite(STDERR, "Invalid CF7 shortcodes must fail explicitly instead of silently restoring the fallback form.\n");
    exit(1);
}

echo "Quote language save contract OK\n";
