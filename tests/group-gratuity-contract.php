<?php

$root = getenv('PLUGIN_ROOT') ?: dirname(__DIR__);
define('ABSPATH', $root . '/');
require_once $root . '/includes/class-parcs-ht-group-quotes.php';

function group_gratuity_check($condition, $message) {
    if (!$condition) { fwrite(STDERR, '[FAIL] ' . $message . PHP_EOL); exit(1); }
    echo '[OK] ' . $message . PHP_EOL;
}

$cases = array(
    array(0, 4, 0, '0 enfant = 0 adulte gratuit'),
    array(9, 4, 1, '9 enfants = 1 adulte gratuit avec arrondi à partir de 5'),
    array(10, 4, 1, '10 enfants = 1 adulte gratuit'),
    array(14, 4, 1, '14 enfants = 1 adulte gratuit'),
    array(15, 4, 2, '15 enfants = 2 adultes gratuits'),
    array(26, 5, 3, '26 enfants = 3 adultes gratuits'),
    array(26, 2, 2, 'la gratuité ne dépasse jamais le nombre réel d’adultes'),
);
foreach ($cases as $case) {
    $actual = Parcs_HT_Group_Quotes::complimentary_adults($case[0], $case[1], 10, 5);
    group_gratuity_check($actual === $case[2], $case[3] . ' (obtenu ' . $actual . ')');
}

echo "Group gratuity contract: OK\n";
