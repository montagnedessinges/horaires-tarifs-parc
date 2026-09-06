from pathlib import Path
import re

admin_path = Path('includes/class-parcs-ht-admin.php')
admin = admin_path.read_text(encoding='utf-8')

static_function = r'''    private static function shortcodes_section() {
        $shortcodes = array(
            array('label'=>'Page complète','auto'=>'[parc_horaires_tarifs]','fr'=>'[parc_horaires_tarifs_fr]','en'=>'[parc_horaires_tarifs_en]','de'=>'[parc_horaires_tarifs_de]'),
            array('label'=>'Horaire du jour','auto'=>'[parc_horaires_aujourdhui]','fr'=>'[parc_horaires_aujourdhui_fr]','en'=>'[parc_horaires_aujourdhui_en]','de'=>'[parc_horaires_aujourdhui_de]'),
            array('label'=>'Calendrier interactif','auto'=>'[parc_calendrier]','fr'=>'[parc_calendrier_fr]','en'=>'[parc_calendrier_en]','de'=>'[parc_calendrier_de]'),
            array('label'=>'Tableau des tarifs','auto'=>'[parc_tableau_tarifs]','fr'=>'[parc_tableau_tarifs_fr]','en'=>'[parc_tableau_tarifs_en]','de'=>'[parc_tableau_tarifs_de]'),
            array('label'=>'Tarifs groupes uniquement','auto'=>'[parc_tarifs_groupes]','fr'=>'[parc_tarifs_groupes_fr]','en'=>'[parc_tarifs_groupes_en]','de'=>'[parc_tarifs_groupes_de]'),
            array('label'=>'Alerte de fermeture','auto'=>'[parc_fermeture_exceptionnelle]','fr'=>'[parc_fermeture_exceptionnelle_fr]','en'=>'[parc_fermeture_exceptionnelle_en]','de'=>'[parc_fermeture_exceptionnelle_de]'),
            array('label'=>'Texte horaire dynamique pour l’en-tête','auto'=>'[parc_horaire]','fr'=>'[parc_horaire_fr]','en'=>'[parc_horaire_en]','de'=>'[parc_horaire_de]'),
            array('label'=>'Statut OUVERT / FERMÉ pour l’en-tête','auto'=>'[parc_statut]','fr'=>'[parc_statut_fr]','en'=>'[parc_statut_en]','de'=>'[parc_statut_de]'),
            array('label'=>'Horaire d’accueil','auto'=>'[parc_horaire_accueil]','fr'=>'[parc_horaire_accueil_fr]','en'=>'[parc_horaire_accueil_en]','de'=>'[parc_horaire_accueil_de]'),
            array('label'=>'Devis groupe autour du formulaire Contact Form 7','auto'=>'[parc_devis_groupe]','fr'=>'[parc_devis_groupe_fr]','en'=>'[parc_devis_groupe_en]','de'=>'[parc_devis_groupe_de]'),
            array('label'=>'Alias compatible du module Devis groupe','auto'=>'[parc_devis]','fr'=>'[parc_devis_fr]','en'=>'[parc_devis_en]','de'=>'[parc_devis_de]'),
            array('label'=>'Guides pédagogiques','auto'=>'[parc_guides_pedagogiques]','fr'=>'[parc_guides_pedagogiques_fr]','en'=>'[parc_guides_pedagogiques_en]','de'=>'[parc_guides_pedagogiques_de]'),
        );
        ?>
        <section id="htp-shortcodes" class="htp-card">
            <h2>Shortcodes</h2>
            <p>Tous les shortcodes utilisables sont listés ci-dessous. Copiez celui dont vous avez besoin. La version « Automatique » suit la langue de la page ; les versions FR, EN et DE forcent la langue.</p>
            <table class="widefat striped">
                <thead><tr><th>Module</th><th>Automatique</th><th>FR</th><th>EN</th><th>DE</th></tr></thead>
                <tbody>
                    <?php foreach ($shortcodes as $row) : ?>
                        <tr>
                            <th><?php echo esc_html($row['label']); ?></th>
                            <td><code><?php echo esc_html($row['auto']); ?></code></td>
                            <td><code><?php echo esc_html($row['fr']); ?></code></td>
                            <td><code><?php echo esc_html($row['en']); ?></code></td>
                            <td><code><?php echo esc_html($row['de']); ?></code></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <p>Les alertes configurées dans l’onglet « Alertes » s’affichent automatiquement en pop-up sur le site. Les shortcodes d’alerte restent disponibles pour compatibilité ou affichage manuel.</p>
        </section>
        <?php
    }
'''

pattern = re.compile(r"    private static function shortcodes_section\(\) \{.*?^    \}\n\n    private static function updates_section", re.S | re.M)
replacement = static_function + "\n    private static function updates_section"
admin_new, count = pattern.subn(replacement, admin, count=1)
if count != 1:
    raise SystemExit(f'Expected one shortcodes_section() block, found {count}.')
admin_path.write_text(admin_new, encoding='utf-8')

main_path = Path('horaires-tarifs-parc.php')
main = main_path.read_text(encoding='utf-8')
main = main.replace("require_once PARCS_HT_DIR . 'includes/class-parcs-ht-feature-hub.php';\n", '')
main = main.replace("        Parcs_HT_Feature_Hub::init();\n", '')
main = main.replace('Version: 1.13.2', 'Version: 1.13.3')
main = main.replace("define('PARCS_HT_VERSION', '1.13.2');", "define('PARCS_HT_VERSION', '1.13.3');")
main_path.write_text(main, encoding='utf-8')

test_path = Path('tests/shortcode-registry-contract.php')
test = r'''<?php

$root = getenv('PLUGIN_ROOT') ?: dirname(__DIR__);
$registry = file_get_contents($root . '/includes/class-parcs-ht-shortcode-registry.php');
$admin = file_get_contents($root . '/includes/class-parcs-ht-admin.php');
$preview = file_get_contents($root . '/includes/class-parcs-ht-admin-shortcode-preview.php');
$preview_js = file_get_contents($root . '/assets/admin-shortcode-preview.js');
$bootstrap = file_get_contents($root . '/includes/class-parcs-ht-bootstrap.php');
$main = file_get_contents($root . '/horaires-tarifs-parc.php');

function shortcode_registry_check($condition, $message) {
    if (!$condition) { fwrite(STDERR, '[FAIL] ' . $message . PHP_EOL); exit(1); }
    echo '[OK] ' . $message . PHP_EOL;
}

$required = array(
    'parc_horaires_tarifs','parc_horaires_aujourdhui','parc_calendrier','parc_tableau_tarifs','parc_tarifs_groupes',
    'parc_fermeture_exceptionnelle','parc_horaire','parc_statut','parc_horaire_accueil','parc_devis_groupe','parc_devis','parc_guides_pedagogiques',
);

foreach ($required as $shortcode) {
    shortcode_registry_check(strpos($registry, "'" . $shortcode . "'") !== false, 'runtime registry contains ' . $shortcode);
    foreach (array('', '_fr', '_en', '_de') as $suffix) {
        $literal = '[' . $shortcode . $suffix . ']';
        shortcode_registry_check(strpos($admin, $literal) !== false, 'static Shortcodes page contains ' . $literal);
    }
}

shortcode_registry_check(strpos($admin, '<th>Automatique</th>') !== false, 'static Shortcodes page has the automatic-language column');
shortcode_registry_check(strpos($admin, 'Parcs_HT_Shortcode_Registry::public_rows()') === false, 'Shortcodes page does not depend on the runtime registry');
shortcode_registry_check(strpos($main, 'class-parcs-ht-feature-hub.php') === false && strpos($main, 'Parcs_HT_Feature_Hub::init()') === false, 'legacy Shortcodes overlay is no longer loaded');
shortcode_registry_check(!file_exists($root . '/includes/class-parcs-ht-feature-hub.php'), 'legacy Shortcodes overlay file is removed');
shortcode_registry_check(strpos($preview, 'Parcs_HT_Shortcode_Registry::public_rows()') !== false && strpos($preview, 'Parcs_HT_Shortcode_Registry::render_preview') !== false, 'Aperçu remains generated from the runtime registry and real renderers');
shortcode_registry_check(strpos($preview_js, "['fr','en','de']") !== false && strpos($preview_js, 'data-htp-preview-lang-button') !== false, 'each preview can still switch between FR EN DE');
shortcode_registry_check(strpos($bootstrap, 'Parcs_HT_Shortcode_Registry::definitions()') !== false, 'runtime bootstrap still derives from the central registry');

echo "Static Shortcodes page contract: OK\n";
'''
test_path.write_text(test, encoding='utf-8')

readme_path = Path('readme.txt')
if readme_path.exists():
    readme = readme_path.read_text(encoding='utf-8')
    readme = re.sub(r'^Stable tag:\s*.*$', 'Stable tag: 1.13.3', readme, count=1, flags=re.M)
    readme_path.write_text(readme, encoding='utf-8')

changelog_path = Path('CHANGELOG.md')
changelog = changelog_path.read_text(encoding='utf-8')
entry = """# Historique des versions\n\n## 1.13.3\n- Remplacement direct de l’ancienne page « Shortcodes » dans l’administration : aucune surcouche JavaScript ni réécriture après affichage.\n- La page contient statiquement les 48 shortcodes utilisables : 12 modules, chacun en version Automatique, FR, EN et DE.\n- Ajout explicite des shortcodes Tarifs groupes, Horaire d’accueil et Guides pédagogiques qui manquaient dans l’ancienne page.\n- Suppression du composant `Parcs_HT_Feature_Hub` qui servait uniquement à remplacer l’ancienne table après son rendu.\n- Le registre central reste utilisé pour l’exécution des shortcodes et les aperçus, mais la page de référence des shortcodes n’en dépend plus.\n- Aucun réglage, horaire, tarif, devis, formulaire ou donnée des parcs n’est modifié.\n\n"""
if changelog.startswith('# Historique des versions'):
    changelog = entry + changelog[len('# Historique des versions\n\n'):]
else:
    changelog = entry + changelog
changelog_path.write_text(changelog, encoding='utf-8')
