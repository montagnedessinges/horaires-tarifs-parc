<?php

if (!defined('ABSPATH')) { exit; }

/**
 * Vue d'ensemble légère de l'administration.
 *
 * Elle ne duplique aucun réglage : chaque carte renvoie vers l'écran canonique
 * concerné. L'objectif est de réduire le temps passé à chercher un réglage sans
 * modifier la structure de données ni les moteurs existants.
 */
final class Parcs_HT_Admin_Overview {
    const PAGE = 'parcs-ht-overview';

    public static function init() {
        add_action('admin_menu', array(__CLASS__, 'menu'), 36);
    }

    public static function menu() {
        if (!class_exists('Parcs_HT_Admin')) return;
        add_submenu_page(
            Parcs_HT_Admin::PAGE,
            'Vue d’ensemble',
            'Vue d’ensemble',
            'manage_options',
            self::PAGE,
            array(__CLASS__, 'page')
        );
    }

    private static function selected_year($all) {
        $requested = isset($_GET['season']) ? sanitize_text_field(wp_unslash($_GET['season'])) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- sélection d'affichage uniquement.
        if ($requested !== '' && isset($all['seasons'][$requested]) && preg_match('/^20\d{2}$/', $requested)) return $requested;
        if (class_exists('Parcs_HT_Defaults')) {
            $settings = Parcs_HT_Defaults::settings();
            $active = (string)($settings['active_season_year'] ?? '');
            if ($active !== '' && isset($all['seasons'][$active])) return $active;
        }
        foreach (array_keys((array)($all['seasons'] ?? array())) as $year) {
            if (preg_match('/^20\d{2}$/', (string)$year)) return (string)$year;
        }
        return '';
    }

    private static function main_url($year, $tab) {
        $args = array('page'=>Parcs_HT_Admin::PAGE, 'tab'=>(string)$tab);
        if ($year !== '') $args['season'] = $year;
        return add_query_arg($args, admin_url('admin.php'));
    }

    private static function content_url() {
        return add_query_arg(array('page'=>Parcs_HT_Public_Content::PAGE), admin_url('admin.php'));
    }

    private static function cards($year) {
        return array(
            'Préparer l’année' => array(
                array('title'=>'Année & publication', 'text'=>'Choisir la saison, les modules visibles et les dates d’activation automatique.', 'url'=>self::main_url($year, 'htp-general')),
                array('title'=>'Horaires & calendrier', 'text'=>'Horaires habituels, périodes d’ouverture et calendrier.', 'url'=>self::main_url($year, 'htp-regular')),
                array('title'=>'Périodes & événements', 'text'=>'Vacances, périodes repères, événements et jours particuliers.', 'url'=>self::main_url($year, 'htp-holidays')),
                array('title'=>'Exceptions', 'text'=>'Fermetures et horaires exceptionnels.', 'url'=>self::main_url($year, 'htp-exceptions')),
                array('title'=>'Accès limité', 'text'=>'Interruption temporaire d’une zone sans fermer le parc.', 'url'=>self::main_url($year, 'htp-domain')),
            ),
            'Tarifs & groupes' => array(
                array('title'=>'Tarifs visiteurs', 'text'=>'Individuels, tarifs réduits, moyens de paiement et offres.', 'url'=>self::main_url($year, 'htp-tariffs')),
                array('title'=>'Tarifs groupes', 'text'=>'Tarifs groupes, moyens de paiement, informations et apparence.', 'url'=>self::main_url($year, 'htp-tariffs-groups')),
                array('title'=>'Devis groupes', 'text'=>'Formulaires, règles de devis et informations de réservation.', 'url'=>self::main_url($year, 'htp-quote')),
                array('title'=>'Guides pédagogiques', 'text'=>'Documents scolaires, langues, cycles et statistiques.', 'url'=>self::main_url($year, 'htp-guides')),
            ),
            'Contenus & communication' => array(
                array('title'=>'Contenus & traductions', 'text'=>'Textes génériques publics, boutons et liens FR / EN / DE.', 'url'=>self::content_url()),
                array('title'=>'Pop-up', 'text'=>'Alertes automatiques et leur apparence.', 'url'=>self::main_url($year, 'htp-alerts')),
                array('title'=>'Calendrier de l’Avent', 'text'=>'Campagnes, contenus, partenaires, résultats et micro-textes.', 'url'=>self::main_url($year, 'htp-advent')),
            ),
            'Contrôle & outils' => array(
                array('title'=>'Aperçu', 'text'=>'Vérifier les shortcodes dans les trois langues.', 'url'=>self::main_url($year, 'htp-preview')),
                array('title'=>'Shortcodes', 'text'=>'Retrouver tous les codes à intégrer dans les pages.', 'url'=>self::main_url($year, 'htp-shortcodes')),
                array('title'=>'Mises à jour', 'text'=>'État de l’extension et vérification des nouvelles versions.', 'url'=>self::main_url($year, 'htp-updates')),
            ),
        );
    }

    private static function status_rows($year, $season) {
        $rows = array(
            'calendar_visible'=>'Calendrier public',
            'retail_tariffs_visible'=>'Tarifs visiteurs',
            'groups_schedule_visible'=>'Horaires groupes',
            'group_quotes_enabled'=>'Devis groupes',
            'group_tariffs_visible'=>'Tarifs groupes',
        );
        $out = array();
        foreach ($rows as $key => $label) {
            $fallback = false;
            if ($key === 'calendar_visible') $fallback = (string)($season['published'] ?? '0') === '1';
            $on = array_key_exists($key, $season) ? (string)$season[$key] === '1' : $fallback;
            $out[] = array('label'=>$label, 'on'=>$on);
        }
        return $out;
    }

    public static function page() {
        if (!current_user_can('manage_options')) return;
        $all = Parcs_HT_Defaults::all_settings();
        $year = self::selected_year($all);
        $season = ($year !== '' && isset($all['seasons'][$year]) && is_array($all['seasons'][$year])) ? $all['seasons'][$year] : array();
        $years = array();
        foreach (array_keys((array)($all['seasons'] ?? array())) as $candidate) {
            if (preg_match('/^20\d{2}$/', (string)$candidate)) $years[] = (string)$candidate;
        }
        sort($years, SORT_NUMERIC);
        ?>
        <div class="wrap htp-overview">
            <h1>Gestion du parc — vue d’ensemble</h1>
            <p class="description">Un accès rapide aux réglages existants. Cette page ne crée pas de seconde configuration : chaque carte ouvre la rubrique canonique correspondante.</p>

            <?php if ($years) : ?>
                <nav class="htp-overview-years" aria-label="Saisons">
                    <?php foreach ($years as $candidate) :
                        $url = add_query_arg(array('page'=>self::PAGE, 'season'=>$candidate), admin_url('admin.php'));
                        ?>
                        <a class="button <?php echo $candidate === $year ? 'button-primary' : ''; ?>" href="<?php echo esc_url($url); ?>"><?php echo esc_html($candidate); ?></a>
                    <?php endforeach; ?>
                </nav>
            <?php endif; ?>

            <?php if ($year !== '') : ?>
                <section class="htp-overview-status">
                    <div><h2>État de l’année <?php echo esc_html($year); ?></h2><p>Ces indicateurs reprennent les activations enregistrées. Les dates automatiques peuvent ensuite modifier la visibilité effective sur le site.</p></div>
                    <div class="htp-overview-status-list">
                        <?php foreach (self::status_rows($year, $season) as $row) : ?>
                            <span class="htp-overview-status-pill <?php echo $row['on'] ? 'is-on' : 'is-off'; ?>"><i aria-hidden="true"></i><?php echo esc_html($row['label']); ?> : <?php echo $row['on'] ? 'activé' : 'désactivé'; ?></span>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php endif; ?>

            <?php foreach (self::cards($year) as $section => $cards) : ?>
                <section class="htp-overview-section">
                    <h2><?php echo esc_html($section); ?></h2>
                    <div class="htp-overview-grid">
                        <?php foreach ($cards as $card) : ?>
                            <a class="htp-overview-card" href="<?php echo esc_url($card['url']); ?>">
                                <strong><?php echo esc_html($card['title']); ?></strong>
                                <span><?php echo esc_html($card['text']); ?></span>
                                <em>Ouvrir →</em>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php endforeach; ?>
        </div>
        <style>
        .htp-overview{max-width:1240px}.htp-overview-years{display:flex;gap:8px;flex-wrap:wrap;margin:18px 0}.htp-overview-status{display:grid;grid-template-columns:minmax(240px,.8fr) minmax(300px,2fr);gap:20px;align-items:start;background:#fff;border:1px solid #dcdcde;border-radius:10px;padding:18px 20px;margin:18px 0 26px}.htp-overview-status h2{margin:0 0 6px}.htp-overview-status p{margin:0;color:#646970}.htp-overview-status-list{display:flex;flex-wrap:wrap;gap:8px}.htp-overview-status-pill{display:inline-flex;align-items:center;gap:7px;padding:7px 10px;border-radius:999px;background:#f0f0f1;font-weight:600}.htp-overview-status-pill i{width:9px;height:9px;border-radius:50%;background:#8c8f94}.htp-overview-status-pill.is-on{background:#edfaef;color:#176b2c}.htp-overview-status-pill.is-on i{background:#00a32a}.htp-overview-section{margin:0 0 28px}.htp-overview-section>h2{margin:0 0 12px}.htp-overview-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(245px,1fr));gap:12px}.htp-overview-card{display:flex;min-height:128px;flex-direction:column;gap:8px;padding:18px;border:1px solid #dcdcde;border-radius:10px;background:#fff;color:#1d2327;text-decoration:none;box-shadow:0 1px 1px rgba(0,0,0,.02)}.htp-overview-card:hover,.htp-overview-card:focus{border-color:#2271b1;box-shadow:0 0 0 1px #2271b1}.htp-overview-card strong{font-size:16px}.htp-overview-card span{color:#50575e;line-height:1.45}.htp-overview-card em{margin-top:auto;color:#2271b1;font-style:normal;font-weight:600}@media(max-width:782px){.htp-overview-status{grid-template-columns:1fr}.htp-overview-grid{grid-template-columns:1fr}}
        </style>
        <?php
    }
}
