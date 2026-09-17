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
        add_action('admin_init', array(__CLASS__, 'redirect_default_entry'), 20);
        add_action('admin_notices', array(__CLASS__, 'canonical_back_link'));
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

    /**
     * Le clic sur le menu principal ouvre l'interface simple. Les liens vers un
     * onglet ou une saison restent sur l'écran canonique historique.
     */
    public static function redirect_default_entry() {
        if (!is_admin() || !current_user_can('manage_options') || !class_exists('Parcs_HT_Admin')) return;
        $page = isset($_GET['page']) ? sanitize_key(wp_unslash($_GET['page'])) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- navigation en lecture seule.
        if ($page !== Parcs_HT_Admin::PAGE) return;
        if (isset($_GET['tab']) || isset($_GET['season']) || isset($_GET['advent_fragment'])) return; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- navigation en lecture seule.
        wp_safe_redirect(add_query_arg(array('page'=>self::PAGE), admin_url('admin.php')));
        exit;
    }

    /** Repère visuel lorsque l'on travaille dans un écran détaillé. */
    public static function canonical_back_link() {
        if (!current_user_can('manage_options') || !class_exists('Parcs_HT_Admin')) return;
        $page = isset($_GET['page']) ? sanitize_key(wp_unslash($_GET['page'])) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- navigation en lecture seule.
        if ($page !== Parcs_HT_Admin::PAGE) return;
        $year = isset($_GET['season']) ? sanitize_text_field(wp_unslash($_GET['season'])) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- navigation en lecture seule.
        $args = array('page'=>self::PAGE);
        if (preg_match('/^20\d{2}$/', $year)) $args['season'] = $year;
        echo '<div class="notice notice-info inline"><p><a class="button" href="' . esc_url(add_query_arg($args, admin_url('admin.php'))) . '">← Retour à la vue d’ensemble</a> <span style="margin-left:8px">Vous êtes dans les réglages détaillés de l’extension.</span></p></div>';
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
                array('title'=>'Contenus & traductions', 'text'=>'Textes publics, boutons et liens FR / EN / DE, avec recherche rapide.', 'url'=>self::content_url()),
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

    private static function automatic_state($year) {
        if (!class_exists('Parcs_HT_Public_Visibility')) return 'manual';
        $state = Parcs_HT_Public_Visibility::scheduled_state($year);
        return in_array($state, array('manual','on','off'), true) ? $state : 'manual';
    }

    private static function status_rows($year, $season) {
        $rows = array(
            'calendar_visible'=>'Calendrier public',
            'retail_tariffs_visible'=>'Tarifs visiteurs',
            'groups_schedule_visible'=>'Horaires groupes',
            'group_quotes_enabled'=>'Devis groupes',
            'group_tariffs_visible'=>'Tarifs groupes',
        );
        $automatic = self::automatic_state($year);
        $out = array();
        foreach ($rows as $key => $label) {
            $fallback = false;
            if ($key === 'calendar_visible') $fallback = (string)($season['published'] ?? '0') === '1';
            $manual = array_key_exists($key, $season) ? (string)$season[$key] === '1' : $fallback;
            $effective = $automatic === 'on' ? true : ($automatic === 'off' ? false : $manual);
            $source = $automatic === 'manual' ? 'manuel' : 'automatique';
            $out[] = array('label'=>$label, 'on'=>$effective, 'source'=>$source);
        }
        return $out;
    }

    private static function date_label($value) {
        $value = (string)$value;
        if (!preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $value, $m)) return 'non définie';
        return $m[3] . '/' . $m[2] . '/' . $m[1];
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
        $automatic = $year !== '' ? self::automatic_state($year) : 'manual';
        ?>
        <div class="wrap htp-overview">
            <h1>Gestion du parc — vue d’ensemble</h1>
            <p class="description">Choisissez l’année puis ouvrez uniquement la rubrique à modifier. Les réglages restent enregistrés dans leurs moteurs historiques : cette page sert de point d’entrée simplifié.</p>

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
                    <div>
                        <h2>État public de <?php echo esc_html($year); ?></h2>
                        <p>Activation automatique : <strong><?php echo esc_html(self::date_label($season['public_display_from'] ?? '')); ?></strong><br>Désactivation automatique : <strong><?php echo esc_html(self::date_label($season['public_display_until'] ?? '')); ?></strong></p>
                        <p class="htp-overview-auto-state">État actuel : <strong><?php echo $automatic === 'on' ? 'année activée automatiquement' : ($automatic === 'off' ? 'année désactivée automatiquement' : 'interrupteurs manuels'); ?></strong></p>
                    </div>
                    <div class="htp-overview-status-list">
                        <?php foreach (self::status_rows($year, $season) as $row) : ?>
                            <span class="htp-overview-status-pill <?php echo $row['on'] ? 'is-on' : 'is-off'; ?>"><i aria-hidden="true"></i><?php echo esc_html($row['label']); ?> : <?php echo $row['on'] ? 'affiché' : 'masqué'; ?> <small>(<?php echo esc_html($row['source']); ?>)</small></span>
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
        .htp-overview{max-width:1240px}.htp-overview-years{display:flex;gap:8px;flex-wrap:wrap;margin:18px 0}.htp-overview-status{display:grid;grid-template-columns:minmax(270px,.9fr) minmax(300px,2fr);gap:20px;align-items:start;background:#fff;border:1px solid #dcdcde;border-radius:10px;padding:18px 20px;margin:18px 0 26px}.htp-overview-status h2{margin:0 0 8px}.htp-overview-status p{margin:0 0 8px;color:#646970;line-height:1.55}.htp-overview-auto-state{padding-top:4px}.htp-overview-status-list{display:flex;flex-wrap:wrap;gap:8px}.htp-overview-status-pill{display:inline-flex;align-items:center;gap:7px;padding:7px 10px;border-radius:999px;background:#f0f0f1;font-weight:600}.htp-overview-status-pill small{font-weight:400;opacity:.78}.htp-overview-status-pill i{width:9px;height:9px;border-radius:50%;background:#8c8f94}.htp-overview-status-pill.is-on{background:#edfaef;color:#176b2c}.htp-overview-status-pill.is-on i{background:#00a32a}.htp-overview-section{margin:0 0 28px}.htp-overview-section>h2{margin:0 0 12px}.htp-overview-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(245px,1fr));gap:12px}.htp-overview-card{display:flex;min-height:128px;flex-direction:column;gap:8px;padding:18px;border:1px solid #dcdcde;border-radius:10px;background:#fff;color:#1d2327;text-decoration:none;box-shadow:0 1px 1px rgba(0,0,0,.02)}.htp-overview-card:hover,.htp-overview-card:focus{border-color:#2271b1;box-shadow:0 0 0 1px #2271b1}.htp-overview-card strong{font-size:16px}.htp-overview-card span{color:#50575e;line-height:1.45}.htp-overview-card em{margin-top:auto;color:#2271b1;font-style:normal;font-weight:600}@media(max-width:782px){.htp-overview-status{grid-template-columns:1fr}.htp-overview-grid{grid-template-columns:1fr}}
        </style>
        <?php
    }
}
