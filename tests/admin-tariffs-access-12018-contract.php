<?php

$root = getenv('PLUGIN_ROOT') ?: dirname(__DIR__);
$navigation = file_get_contents($root . '/includes/class-parcs-ht-admin-navigation.php');

function htp_12018_assert($condition, $message) {
    if (!$condition) {
        fwrite(STDERR, "FAIL: {$message}\n");
        exit(1);
    }
}

htp_12018_assert(
    strpos($navigation, "'Tarifs', 'Tarifs', 'manage_options', self::TARIFFS_PAGE") !== false,
    'page hub Tarifs absente du menu'
);
htp_12018_assert(
    strpos($navigation, "if (in_array($slug, $hidden_pages, true)) continue;") === false,
    'les pages visiteurs/CSE sont retirées du registre de sous-menus WordPress'
);
htp_12018_assert(
    strpos($navigation, 'li:has(> a[href*="page=parcs-ht-tariffs"])') !== false
    && strpos($navigation, 'li:has(> a[href*="page=parcs-ht-cse"])') !== false,
    'masquage visuel des anciennes entrées sans retirer les routes'
);
htp_12018_assert(
    strpos($navigation, 'self::UPDATES_PAGE, self::TARIFFS_PAGE') !== false,
    'les styles des cartes ne sont pas chargés sur le hub Tarifs'
);

fwrite(STDOUT, "OK admin-tariffs-access-12018-contract\n");
