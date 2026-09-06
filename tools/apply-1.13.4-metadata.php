<?php

$root = dirname(__DIR__);

$pluginPath = $root . '/horaires-tarifs-parc.php';
$plugin = file_get_contents($pluginPath);
if ($plugin === false) { fwrite(STDERR, "Cannot read plugin file\n"); exit(1); }
$plugin = str_replace('Version: 1.13.3', 'Version: 1.13.4', $plugin, $countHeader);
$plugin = str_replace("define('PARCS_HT_VERSION', '1.13.3');", "define('PARCS_HT_VERSION', '1.13.4');", $plugin, $countConst);
if ($countHeader !== 1 || $countConst !== 1) { fwrite(STDERR, "Unexpected plugin version state\n"); exit(1); }
file_put_contents($pluginPath, $plugin);

$changelogPath = $root . '/CHANGELOG.md';
$changelog = file_get_contents($changelogPath);
if ($changelog === false || strpos($changelog, "# Historique des versions\n") !== 0) { fwrite(STDERR, "Unexpected changelog state\n"); exit(1); }
if (strpos($changelog, "## 1.13.4\n") === false) {
    $entry = <<<'MD'

## 1.13.4
- Correction du shortcode `[parc_tarifs_groupes]` et de ses variantes FR / EN / DE lorsqu’une installation possède encore des tarifs groupes enregistrés avec l’ancien format de données.
- Le rendu public n’exige plus qu’une colonne tarifaire possède déjà un identifiant permanent `tariff_col_...` : les anciennes colonnes `price`, cellules `cells['price']` et valeurs historiques `row['price']` restent lisibles.
- Une ancienne ligne tarifaire sans champ `enabled` reste affichable ; une ligne explicitement désactivée reste masquée.
- Le format actuel à identifiants permanents reste prioritaire et inchangé. Ces identifiants restent utilisés pour les liaisons métier du moteur de devis, mais ne sont plus une condition artificielle pour afficher les tarifs groupes.
- Aucun tarif ni réglage WordPress n’est réécrit par ce correctif : il s’agit uniquement d’une compatibilité de lecture du shortcode public.
- Ajout d’un test d’exécution réel couvrant notamment une ligne « Senior » au format historique et une grille actuelle à identifiants permanents. Ce test est aussi exécuté sur les sources nettoyées destinées au ZIP de production.
- Incident et cause technique documentés dans `AUDIT-2026-09-06-SHORTCODE-TARIFS-GROUPES.md`.
MD;
    $changelog = "# Historique des versions\n" . $entry . substr($changelog, strlen("# Historique des versions\n"));
    file_put_contents($changelogPath, $changelog);
}

$contextPath = $root . '/CHATGPT-CONTEXT.md';
$context = file_get_contents($contextPath);
if ($context === false) { fwrite(STDERR, "Cannot read context\n"); exit(1); }
$rule = <<<'MD'

### Compatibilité du shortcode Tarifs groupes

Le shortcode public `[parc_tarifs_groupes]` et ses variantes de langue doivent rester capables d’afficher des tarifs groupes déjà enregistrés dans les anciens formats de données (`price`, `cells['price']`, prix au niveau de la ligne), même si les identifiants permanents `tariff_row_...` / `tariff_col_...` n’ont pas encore été persistés par une migration d’administration. Les identifiants permanents restent la référence pour les liaisons métier du devis, mais ils ne doivent pas être une condition préalable au simple affichage public d’un tarif existant. Ne jamais réécrire silencieusement les tarifs du parc uniquement pour rendre ce shortcode affichable. Le test `tests/group-tariff-shortcode-runtime.php` protège ce comportement.
MD;
if (strpos($context, '### Compatibilité du shortcode Tarifs groupes') === false) {
    $needle = "\n## Principe d’architecture\n";
    if (strpos($context, $needle) === false) { fwrite(STDERR, "Context insertion point missing\n"); exit(1); }
    $context = str_replace($needle, $rule . $needle, $context, $n);
    if ($n !== 1) { fwrite(STDERR, "Unexpected context insertion count\n"); exit(1); }
    file_put_contents($contextPath, $context);
}

foreach (array($pluginPath, $changelogPath, $contextPath) as $path) {
    if (!is_file($path) || filesize($path) === 0) { fwrite(STDERR, "Invalid generated file: $path\n"); exit(1); }
}

echo "1.13.4 metadata prepared\n";
