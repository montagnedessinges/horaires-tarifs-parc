<?php

$root = dirname(__DIR__);

function read_required($path) {
    $content = file_get_contents($path);
    if ($content === false) {
        fwrite(STDERR, "Unable to read {$path}\n");
        exit(1);
    }
    return $content;
}

function write_required($path, $content) {
    if (file_put_contents($path, $content) === false) {
        fwrite(STDERR, "Unable to write {$path}\n");
        exit(1);
    }
}

function replace_once($content, $from, $to, $label) {
    $count = 0;
    $result = str_replace($from, $to, $content, $count);
    if ($count !== 1) {
        fwrite(STDERR, "Expected one replacement for {$label}, got {$count}.\n");
        exit(1);
    }
    return $result;
}

// 1. Make the Advent workspace a first-class WordPress admin area and remove
// the old JavaScript shortcode injection path.
$oldAdminPath = $root . '/includes/class-parcs-ht-advent-admin-v2.php';
$newAdminPath = $root . '/includes/class-parcs-ht-advent-admin.php';
$admin = read_required($oldAdminPath);

$oldMenu = <<<'PHP'
    public static function menu() {
        add_submenu_page(
            Parcs_HT_Admin::PAGE,
            'Calendrier de l’Avent',
            'Calendrier de l’Avent',
            'manage_options',
            self::PAGE,
            array(__CLASS__, 'page')
        );
    }
PHP;
$newMenu = <<<'PHP'
    public static function menu() {
        add_menu_page(
            'Calendrier de l’Avent',
            'Calendrier de l’Avent',
            'manage_options',
            self::PAGE,
            array(__CLASS__, 'page'),
            'dashicons-calendar-alt',
            32
        );
    }
PHP;
$admin = replace_once($admin, $oldMenu, $newMenu, 'Advent top-level menu');

$oldAssetsTail = <<<'PHP'
        if ($hook === 'toplevel_page_parcs-horaires-tarifs') {
            wp_enqueue_script('parcs-ht-advent-shortcodes-admin', PARCS_HT_URL . 'assets/advent-shortcodes-admin.js', array(), PARCS_HT_VERSION, true);
        }
PHP;
$admin = replace_once($admin, $oldAssetsTail, '', 'obsolete shortcode injection enqueue');

$oldHeading = <<<'PHP'
            <h1>Calendrier de l’Avent</h1>
            <p class="description">Prototype schéma <?php echo esc_html((string)Parcs_HT_Advent::SCHEMA_VERSION); ?>. Les données restent isolées par installation et campagne.</p>
PHP;
$newHeading = <<<'PHP'
            <h1>Calendrier de l’Avent <a class="page-title-action" href="<?php echo esc_url(add_query_arg(array('page'=>Parcs_HT_Admin::PAGE), admin_url('admin.php'))); ?>">Horaires du parc</a></h1>
            <p class="description">Schéma <?php echo esc_html((string)Parcs_HT_Advent::SCHEMA_VERSION); ?> · campagnes, contenus, partenaires et résultats restent isolés par installation.</p>
PHP;
$admin = replace_once($admin, $oldHeading, $newHeading, 'Advent page heading');
write_required($newAdminPath, $admin);
unlink($oldAdminPath);

// 2. Bootstrap the canonical admin filename.
$mainPath = $root . '/horaires-tarifs-parc.php';
$main = read_required($mainPath);
$main = replace_once(
    $main,
    "require_once PARCS_HT_DIR . 'includes/class-parcs-ht-advent-admin-v2.php';",
    "require_once PARCS_HT_DIR . 'includes/class-parcs-ht-advent-admin.php';",
    'canonical Advent admin filename'
);
write_required($mainPath, $main);

// 3. Integrate the Advent workspace natively in the main extension navigation
// and make the Shortcodes section read the central registry directly.
$coreAdminPath = $root . '/includes/class-parcs-ht-admin.php';
$core = read_required($coreAdminPath);
$regularTab = <<<'PHP'
                <button type="button" class="nav-tab htp-admin-tab" role="tab" aria-selected="false" data-htp-admin-tab="htp-regular">Horaires & calendrier</button>
PHP;
$regularWithAdvent = $regularTab . <<<'PHP'
                <a class="nav-tab htp-advent-admin-link" href="<?php echo esc_url(add_query_arg(array('page'=>Parcs_HT_Advent_Admin::PAGE), admin_url('admin.php'))); ?>">Calendrier de l’Avent</a>
PHP;
$core = replace_once($core, $regularTab, $regularWithAdvent, 'native main-admin Advent link');

$newShortcodes = <<<'PHP'

    private static function shortcodes_section() {
        $shortcodes = class_exists('Parcs_HT_Shortcode_Registry') ? Parcs_HT_Shortcode_Registry::public_rows() : array();
        ?>
        <section id="htp-shortcodes" class="htp-card">
            <h2>Shortcodes</h2>
            <p>Tous les shortcodes utilisables sont listés ci-dessous. Copiez celui dont vous avez besoin. La version « Automatique » suit la langue de la page ; les versions FR, EN et DE forcent la langue.</p>
            <table class="widefat striped">
                <thead><tr><th>Module</th><th>Automatique</th><th>FR</th><th>EN</th><th>DE</th></tr></thead>
                <tbody>
                    <?php foreach ($shortcodes as $row) :
                        $codes = isset($row['shortcodes']) && is_array($row['shortcodes']) ? $row['shortcodes'] : array();
                        ?>
                        <tr>
                            <th><?php echo esc_html((string)($row['label'] ?? '')); ?></th>
                            <td><code><?php echo esc_html((string)($codes['auto'] ?? '')); ?></code></td>
                            <td><code><?php echo esc_html((string)($codes['fr'] ?? '')); ?></code></td>
                            <td><code><?php echo esc_html((string)($codes['en'] ?? '')); ?></code></td>
                            <td><code><?php echo esc_html((string)($codes['de'] ?? '')); ?></code></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <p>Les alertes configurées dans l’onglet « Alertes » s’affichent automatiquement en pop-up sur le site. Les shortcodes d’alerte restent disponibles pour compatibilité ou affichage manuel.</p>
        </section>
        <?php
    }

    private static function updates_section($settings) {
PHP;
$pattern = '~\n    private static function shortcodes_section\(\) \{.*?\n    private static function updates_section\(\$settings\) \{\n~s';
$count = 0;
$core = preg_replace($pattern, $newShortcodes, $core, 1, $count);
if ($count !== 1) {
    fwrite(STDERR, "Unable to replace hardcoded Shortcodes section.\n");
    exit(1);
}
write_required($coreAdminPath, $core);

// 4. Remove the runtime DOM injection completely.
$obsoleteJs = $root . '/assets/advent-shortcodes-admin.js';
if (file_exists($obsoleteJs)) unlink($obsoleteJs);

// 5. Strengthen the contract so this architecture cannot silently regress.
$testPath = $root . '/tests/advent-contract.php';
$test = read_required($testPath);
$test = replace_once($test, "$admin_path = $root . '/includes/class-parcs-ht-advent-admin-v2.php';", "$admin_path = $root . '/includes/class-parcs-ht-advent-admin.php';", 'test canonical admin path');
$test = replace_once($test, "$admin = file_get_contents($admin_path);", "$admin = file_get_contents($admin_path);\n$core_admin = file_get_contents($root . '/includes/class-parcs-ht-admin.php');", 'test core admin load');
$test = replace_once($test, "$shortcodes_js = file_get_contents($root . '/assets/advent-shortcodes-admin.js');\n", '', 'remove obsolete JS fixture');
$test = replace_once($test, "advent_check(strpos($main, 'Version: 1.15.0') !== false && strpos($main, \"PARCS_HT_VERSION', '1.15.0\") !== false, 'prototype uses version 1.15.0');", "advent_check(strpos($main, 'Version: 1.15.1') !== false && strpos($main, \"PARCS_HT_VERSION', '1.15.1\") !== false, 'admin architecture cleanup uses version 1.15.1');", 'test version');
$test = replace_once($test, "advent_check(strpos($main, 'class-parcs-ht-advent.php') !== false && strpos($main, 'class-parcs-ht-advent-admin-v2.php') !== false, 'Advent public and admin modules are bootstrapped');\nadvent_check(!file_exists($root . '/includes/class-parcs-ht-advent-admin.php'), 'obsolete Advent admin implementation is removed');", "advent_check(strpos($main, 'class-parcs-ht-advent.php') !== false && strpos($main, 'class-parcs-ht-advent-admin.php') !== false, 'Advent public and canonical admin modules are bootstrapped');\nadvent_check(!file_exists($root . '/includes/class-parcs-ht-advent-admin-v2.php'), 'obsolete Advent v2 admin filename is removed');\nadvent_check(strpos($admin, 'add_menu_page(') !== false && strpos($admin, 'add_submenu_page(') === false, 'Advent is a first-class WordPress admin menu');\nadvent_check(strpos($core_admin, 'Parcs_HT_Advent_Admin::PAGE') !== false && strpos($core_admin, 'Calendrier de l’Avent') !== false, 'main park admin links natively to the Advent workspace');\nadvent_check(strpos($core_admin, 'Parcs_HT_Shortcode_Registry::public_rows()') !== false, 'central Shortcodes tab is rendered from the registry');\nadvent_check(!file_exists($root . '/assets/advent-shortcodes-admin.js'), 'obsolete Advent shortcode DOM injection is removed');", 'test admin architecture');
$test = replace_once($test, "advent_check(strpos($shortcodes_js, 'parc_calendrier_avent') !== false && strpos($shortcodes_js, 'parc_reglement_avent') !== false, 'central Shortcodes tab exposes both Advent blocks');", "advent_check(strpos($core_admin, \"$codes['auto']\") !== false && strpos($registry, \"'parc_calendrier_avent'\") !== false && strpos($registry, \"'parc_reglement_avent'\") !== false, 'central Shortcodes tab exposes Advent from the registry without a DOM overlay');", 'test native shortcode listing');
write_required($testPath, $test);

// 6. Document the release and the audited cause rather than hiding it.
$changelogPath = $root . '/CHANGELOG.md';
$changelog = read_required($changelogPath);
$releaseNotes = <<<'MD'
# Historique des versions

## 1.15.1
- Refonte structurelle de l’accès au Calendrier de l’Avent dans l’administration : le module dispose désormais de son propre menu WordPress de premier niveau et d’un accès natif depuis la navigation de « Horaires du parc ».
- Suppression de l’injection JavaScript qui ajoutait après coup les shortcodes Avent à la table d’administration. La page Shortcodes est désormais alimentée directement par le registre central, source unique de vérité.
- Renommage du contrôleur admin Avent vers le fichier canonique `class-parcs-ht-advent-admin.php` ; l’ancien nom technique `-v2` est supprimé.
- Conservation du moteur Avent schema_version 3, des données existantes, des shortcodes publics, de la sécurité serveur et de l’isolation MDS/FDS sans réinitialisation de campagne.
- Renforcement du test de contrat pour bloquer toute régression vers un sous-menu masqué ou une surcouche DOM JavaScript.

## 1.15.0
- Premier prototype fonctionnel du Calendrier de l’Avent selon le cadrage 0.7 / schema_version 3 : campagnes, 24 journées, teasings sociaux, partenaires, résultats, grand jeu, règlement dynamique et import CSV de test.
- Ajout des shortcodes `[parc_calendrier_avent]` et `[parc_reglement_avent]` avec variantes FR / EN / DE et aperçu date + heure.
- Validation serveur des ouvertures, résultats, indices et mot mystère ; le formulaire final n’est rendu qu’après autorisation serveur.

MD;
if (strpos($changelog, "# Historique des versions\n") !== 0) {
    fwrite(STDERR, "Unexpected changelog header.\n");
    exit(1);
}
$changelog = $releaseNotes . substr($changelog, strlen("# Historique des versions\n\n"));
write_required($changelogPath, $changelog);

$auditPath = $root . '/docs/calendrier-avent/AUDIT-ADMIN-1.15.0.md';
$audit = <<<'MD'
# Audit administration — Calendrier de l’Avent 1.15.0

Date : 9 septembre 2026

## Constat

Le moteur Avent et sa page d’administration étaient bien présents dans la 1.15.0, mais leur point d’entrée n’était pas cohérent avec l’interface réellement utilisée dans « Horaires du parc ».

La page Avent était enregistrée uniquement comme sous-menu WordPress, alors que la navigation principale de l’extension ne contenait aucune entrée visible vers cet espace. En parallèle, les deux shortcodes Avent étaient ajoutés à la table « Shortcodes » par une surcouche JavaScript après rendu de la page.

Cette architecture pouvait donc donner l’impression que le module n’existait pas, même si son code était chargé.

## Cause

Les tests 1.15.0 validaient principalement la présence des classes, fonctions, chaînes de sécurité et shortcodes. Ils ne protégeaient pas suffisamment l’architecture réelle de navigation de l’administration.

## Correction structurelle 1.15.1

- le Calendrier de l’Avent devient un menu WordPress de premier niveau ;
- « Horaires du parc » contient un lien PHP natif vers cet espace ;
- la page Shortcodes lit directement le registre central ;
- `assets/advent-shortcodes-admin.js` est supprimé ;
- le contrôleur admin porte le nom canonique `class-parcs-ht-advent-admin.php` ;
- les tests bloquent le retour à l’ancien sous-menu seul ou à une injection DOM.

Aucune donnée de campagne MDS/FDS n’est créée en dur et aucune campagne existante n’est réinitialisée par cette correction.
MD;
write_required($auditPath, $audit);

$readmePath = $root . '/docs/calendrier-avent/README.md';
$readme = read_required($readmePath);
$oldState = <<<'MD'
## État réel du plugin

- Version publiée actuelle au moment de ce cadrage : **1.14.0**.
- La version 1.14.0 ne contient **pas** encore le module Calendrier de l’Avent.
- Il n’existe actuellement ni menu d’administration Avent, ni shortcode `[parc_calendrier_avent]` ou `[parc_reglement_avent]` dans la version de production.
- Ne jamais considérer le module comme livré tant qu’une future version contenant réellement le code n’est pas publiée dans les Releases GitHub avec son ZIP de production.
MD;
$newState = <<<'MD'
## État réel du plugin

- La version **1.15.0** a introduit le premier prototype fonctionnel du Calendrier de l’Avent selon le cadrage 0.7 / `schema_version = 3`.
- L’audit du 9 septembre 2026 a identifié un défaut d’architecture de navigation dans cette première release : le module existait mais son accès administrateur était insuffisamment visible, et la table Shortcodes utilisait une injection JavaScript dédiée.
- La correction structurelle **1.15.1** remplace ces mécanismes par une navigation WordPress native et une table Shortcodes alimentée directement par le registre central. Voir `AUDIT-ADMIN-1.15.0.md`.
- Une version n’est considérée comme livrée que lorsqu’elle est publiée dans GitHub Releases avec son ZIP de production vérifié.
MD;
$readme = replace_once($readme, $oldState, $newState, 'README plugin state');
write_required($readmePath, $readme);

// One-time refactor helpers must not survive in the product source.
@unlink(__FILE__);
@unlink($root . '/.github/workflows/refactor-advent-admin-1.15.1.yml');

fwrite(STDOUT, "Advent admin architecture refactor applied.\n");
