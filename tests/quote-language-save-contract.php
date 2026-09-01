<?php

$root = dirname(__DIR__);
require_once __DIR__ . '/release-contract.php';

$version = release_contract_plugin_version($root);
$file = file_get_contents($root . '/includes/class-parcs-ht-quote-languages.php');

release_contract_transition(
    $version,
    '1.10.0',
    static function () use ($file) {
        release_contract_require_all($file, array(
            "wp_unslash(\$_POST['forms'])",
            'sanitize_form_shortcode',
            "update_option(Parcs_HT_Defaults::OPTION, \$all, false)",
            "get_option(Parcs_HT_Defaults::OPTION, array())",
            "['quote_page']['form_shortcodes'] = \$clean",
        ), 'Legacy quote language save');
    },
    static function () use ($file) {
        release_contract_require_all($file, array(
            "wp_unslash(\$_POST['forms'])",
            'sanitize_form_shortcode',
            'Parcs_HT_Defaults::OPTION',
            "['quote_page']['form_shortcodes'] = \$clean",
            "do_action('litespeed_purge_all')",
            "WordPress n’a pas confirmé l’enregistrement des formulaires de devis",
            "if (\$value === null)",
            "wp_die('Le shortcode du formulaire '",
        ), 'Quote language save 1.10+');

        release_contract_require_regex($file, array(
            'reads unified settings before save' => '/get_option\(\s*Parcs_HT_Defaults::OPTION\s*,\s*array\(\)\s*\)/',
            'stores language shortcodes in unified quote page' => '/\$all\s*\[\s*[\'\"]quote_page[\'\"]\s*\]\s*\[\s*[\'\"]form_shortcodes[\'\"]\s*\]\s*=\s*\$clean\s*;/',
            'writes unified settings option' => '/update_option\(\s*Parcs_HT_Defaults::OPTION\s*,\s*\$all\s*,\s*false\s*\)/',
        ), 'Quote language save 1.10+');
    }
);

release_contract_forbid($file, array(
    'legacy standalone option write' => "update_option(self::OPTION, \$clean, false)",
    'unsafe bulk pre-transform' => "map_deep(wp_unslash(\$_POST['forms'])",
), 'Quote language save');

echo "Quote language save contract OK for {$version}\n";
