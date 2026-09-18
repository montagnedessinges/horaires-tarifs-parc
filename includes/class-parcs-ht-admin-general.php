<?php

if (!defined('ABSPATH')) { exit; }

/**
 * Administration générale 1.17.1.
 *
 * Cette première étape expose les informations transversales sans déplacer ni
 * supprimer les moteurs historiques. Les réglages détaillés restent canoniques
 * dans Parcs_HT_Admin tant que leur migration vers cette page n'est pas validée.
 */
final class Parcs_HT_Admin_General {
    const PAGE = 'parcs-ht-general';

    public static function init() {
        add_action('admin_menu', array(__CLASS__, 'menu'), 35);
        add_action('admin_init', array(__CLASS__, 'redirect_default_entry'), 10);
    }

    public static function menu() {
        if (!class_exists('Parcs_HT_Admin')) return;
        add_submenu_page(
            Parcs_HT_Admin::PAGE,
            'Administration générale',
            'Administration générale',
            'manage_options',
            self::PAGE,
            array(__CLASS__, 'page')
        );
    }

    /**
     * L'entrée principale ouvre désormais l'administration générale. Les URLs
     * détaillées existantes restent intactes et continuent d'ouvrir l'ancien
     * écran canonique pendant la refonte progressive 1.17.x.
     */
    public static function redirect_default_entry() {
        if (!is_admin() || !current_user_can('manage_options') || !class_exists('Parcs_HT_Admin')) return;
        $page = isset($_GET['page']) ? sanitize_key(wp_unslash($_GET['page'])) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- navigation en lecture seule.
        if ($page !== Parcs_HT_Admin::PAGE) return;
        if (isset($_GET['tab']) || isset($_GET['season']) || isset($_GET['advent_fragment'])) return; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- navigation en lecture seule.
        wp_safe_redirect(add_query_arg(array('page'=>self::PAGE), admin_url('admin.php')));
        exit;
    }

    private static function selected_year($all) {
        $requested = isset($_GET['season']) ? sanitize_text_field(wp_unslash($_GET['season'])) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- sélection d'affichage uniquement.
        if ($requested !== '' && preg_match('/^20\d{2}$/', $requested) && isset($all['seasons'][$requested])) return $requested;

        $settings = Parcs_HT_Defaults::settings();
        $active = (string)($settings['active_season_year'] ?? '');
        if ($active !== '' && isset($all['seasons'][$active])) return $active;

        foreach (array_keys((array)($all['seasons'] ?? array())) as $year) {
            if (preg_match('/^20\d{2}$/', (string)$year)) return (string)$year;
        }
        return '';
    }

    private static function detailed_url($year, $tab = 'htp-general') {
        $args = array('page'=>Parcs_HT_Admin::PAGE, 'tab'=>$tab);
        if ($year !== '') $args['season'] = $year;
        return add_query_arg($args, admin_url('admin.php'));
    }

    private static function overview_url($year = '') {
        $args = array('page'=>self::PAGE);
        if ($year !== '') $args['season'] = $year;
        return add_query_arg($args, admin_url('admin.php'));
    }

    private static function translated_value($value) {
        if (!is_array($value)) return trim((string)$value);
        foreach (array('fr','en','de') as $lang) {
            if (!empty($value[$lang])) return trim((string)$value[$lang]);
        }
        return '';
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
            $fallback = $key === 'calendar_visible' && (string)($season['published'] ?? '0') === '1';
            $manual = array_key_exists($key, $season) ? (string)$season[$key] === '1' : $fallback;
            $effective = $automatic === 'on' ? true : ($automatic === 'off' ? false : $manual);
            $out[] = array(
                'label'=>$label,
                'on'=>$effective,
                'source'=>$automatic === 'manual' ? 'manuel' : 'automatique',
            );
        }
        return $out;
    }

    private static function date_label($value) {
        $value = trim((string)$value);
        if (!preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $value, $m)) return 'non définie';
        return $m[3] . '/' . $m[2] . '/' . $m[1];
    }

    private static function color_swatch($label, $value) {
        $value = sanitize_hex_color((string)$value);
        if (!$value) $value = '#ffffff';
        echo '<span class="htp-general-swatch"><i style="background:' . esc_attr($value) . '"></i><span><strong>' . esc_html($label) . '</strong><small>' . esc_html($value) . '</small></span></span>';
    }

    public static function page() {
        if (!current_user_can('manage_options')) return;

        $all = Parcs_HT_Defaults::all_settings();
        $year = self::selected_year($all);
        $season = ($year !== '' && isset($all['seasons'][$year]) && is_array($all['seasons'][$year])) ? $all['seasons'][$year] : array();
        $settings = Parcs_HT_Defaults::settings($year);
        $general = isset($settings['general']) && is_array($settings['general']) ? $settings['general'] : array();
        $years = array();
        foreach (array_keys((array)($all['seasons'] ?? array())) as $candidate) {
            if (preg_match('/^20\d{2}$/', (string)$candidate)) $years[] = (string)$candidate;
        }
        sort($years, SORT_NUMERIC);
        $automatic = $year !== '' ? self::automatic_state($year) : 'manual';
        $park_name = self::translated_value($general['park_name'] ?? '');
        $timezone = (string)($settings['timezone'] ?? 'Europe/Paris');
        ?>
        <div class="wrap htp-general-admin">
            <h1>Administration générale</h1>
            <p class="description">Gérez ici les éléments communs du parc et de l’année sélectionnée. Les autres catégories restent séparées pour éviter les modifications trop larges et les régressions.</p>

            <?php if ($years) : ?>
                <nav class="htp-general-years" aria-label="Saisons">
                    <strong>Année sélectionnée :</strong>
                    <?php foreach ($years as $candidate) : ?>
                        <a class="button <?php echo $candidate === $year ? 'button-primary' : ''; ?>" href="<?php echo esc_url(self::overview_url($candidate)); ?>"><?php echo esc_html($candidate); ?></a>
                    <?php endforeach; ?>
                </nav>
            <?php endif; ?>

            <div class="htp-general-grid">
                <section class="htp-general-card">
                    <div class="htp-general-card-head"><div><h2>Parc</h2><p>Informations réellement communes à toute l’installation.</p></div><a class="button" href="<?php echo esc_url(self::detailed_url($year, 'htp-general')); ?>">Modifier</a></div>
                    <dl class="htp-general-facts">
                        <div><dt>Nom du parc</dt><dd><?php echo esc_html($park_name !== '' ? $park_name : 'Non renseigné'); ?></dd></div>
                        <div><dt>Fuseau horaire</dt><dd><?php echo esc_html($timezone); ?></dd></div>
                        <div><dt>Début de saison</dt><dd><?php echo esc_html(self::date_label($season['season_start'] ?? '')); ?></dd></div>
                        <div><dt>Fin de saison</dt><dd><?php echo esc_html(self::date_label($season['season_end'] ?? '')); ?></dd></div>
                    </dl>
                </section>

                <section class="htp-general-card">
                    <div class="htp-general-card-head"><div><h2>Saisons</h2><p>Les années restent indépendantes et aucune année n’est codée en dur.</p></div><a class="button" href="<?php echo esc_url(self::detailed_url($year, 'htp-general')); ?>">Gérer les saisons</a></div>
                    <div class="htp-general-season-list">
                        <?php foreach ($years as $candidate) :
                            $candidate_season = isset($all['seasons'][$candidate]) && is_array($all['seasons'][$candidate]) ? $all['seasons'][$candidate] : array();
                            $is_current = $candidate === $year;
                            $has_public = false;
                            foreach (array('calendar_visible','retail_tariffs_visible','groups_schedule_visible','group_quotes_enabled','group_tariffs_visible') as $visibility_key) {
                                if ((string)($candidate_season[$visibility_key] ?? '0') === '1') { $has_public = true; break; }
                            }
                            ?>
                            <a href="<?php echo esc_url(self::overview_url($candidate)); ?>" class="htp-general-season <?php echo $is_current ? 'is-current' : ''; ?>"><strong><?php echo esc_html($candidate); ?></strong><span><?php echo $has_public ? 'au moins un module actif' : 'préparation / brouillon'; ?></span></a>
                        <?php endforeach; ?>
                    </div>
                </section>
            </div>

            <?php if ($year !== '') : ?>
                <section class="htp-general-card htp-general-publication">
                    <div class="htp-general-card-head">
                        <div><h2>Publication de l’année sélectionnée — <?php echo esc_html($year); ?></h2><p>Les cinq activations restent indépendantes avant la bascule automatique.</p></div>
                        <a class="button button-primary" href="<?php echo esc_url(self::detailed_url($year, 'htp-general')); ?>">Modifier la publication</a>
                    </div>
                    <div class="htp-general-auto">
                        <div><span>Activation automatique</span><strong><?php echo esc_html(self::date_label($season['public_display_from'] ?? '')); ?></strong></div>
                        <div><span>Désactivation automatique</span><strong><?php echo esc_html(self::date_label($season['public_display_until'] ?? '')); ?></strong></div>
                        <div><span>État actuel</span><strong><?php echo esc_html($automatic === 'on' ? 'année activée automatiquement' : ($automatic === 'off' ? 'année désactivée automatiquement' : 'interrupteurs manuels')); ?></strong></div>
                    </div>
                    <div class="htp-general-status-list">
                        <?php foreach (self::status_rows($year, $season) as $row) : ?>
                            <span class="htp-general-status <?php echo $row['on'] ? 'is-on' : 'is-off'; ?>"><i aria-hidden="true"></i><?php echo esc_html($row['label']); ?> : <?php echo $row['on'] ? 'affiché' : 'masqué'; ?> <small>(<?php echo esc_html($row['source']); ?>)</small></span>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php endif; ?>

            <section class="htp-general-card">
                <div class="htp-general-card-head"><div><h2>Apparence globale</h2><p>Référentiel visuel commun. Les couleurs métier des horaires, états, événements et accès limité restent propres à leurs catégories.</p></div><a class="button" href="<?php echo esc_url(self::detailed_url($year, 'htp-general')); ?>">Modifier l’apparence</a></div>
                <div class="htp-general-swatches">
                    <?php self::color_swatch('Principale', $general['primary_color'] ?? '#006757'); ?>
                    <?php self::color_swatch('Secondaire', $general['secondary_color'] ?? '#31ad81'); ?>
                    <?php self::color_swatch('Accent', $general['accent_color'] ?? '#ef7b5b'); ?>
                    <?php self::color_swatch('Mise en valeur', $general['highlight_color'] ?? '#e7c55b'); ?>
                    <?php self::color_swatch('Texte', $general['body_text_color'] ?? '#1d2327'); ?>
                    <?php self::color_swatch('Bordure', $general['border_color'] ?? '#dcdcde'); ?>
                </div>
                <p class="description">Cette première étape réutilise les valeurs déjà enregistrées. Aucun réglage existant n’est déplacé, supprimé ou réinitialisé.</p>
            </section>

            <p class="htp-general-legacy"><a href="<?php echo esc_url(self::detailed_url($year, 'htp-general')); ?>">Ouvrir les réglages détaillés historiques</a> — conservés pendant la refonte pour sécuriser la migration.</p>
        </div>
        <style>
        .htp-general-admin{max-width:1240px}.htp-general-years{display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin:18px 0 22px}.htp-general-years>strong{margin-right:4px}.htp-general-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px}.htp-general-card{margin:0 0 16px;padding:20px;background:#fff;border:1px solid #dcdcde;border-radius:10px}.htp-general-card-head{display:flex;justify-content:space-between;align-items:flex-start;gap:16px;margin-bottom:16px}.htp-general-card-head h2{margin:0 0 6px}.htp-general-card-head p{margin:0;color:#646970}.htp-general-facts{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px;margin:0}.htp-general-facts div,.htp-general-auto>div{padding:12px;background:#f6f7f7;border-radius:8px}.htp-general-facts dt,.htp-general-auto span{display:block;color:#646970;font-size:12px;margin-bottom:4px}.htp-general-facts dd{margin:0;font-weight:600}.htp-general-season-list{display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:8px}.htp-general-season{display:flex;flex-direction:column;gap:3px;padding:12px;border:1px solid #dcdcde;border-radius:8px;text-decoration:none;color:#1d2327;background:#fff}.htp-general-season.is-current{border-color:#2271b1;box-shadow:0 0 0 1px #2271b1}.htp-general-season span{font-size:12px;color:#646970}.htp-general-publication{margin-top:16px}.htp-general-auto{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:10px;margin-bottom:14px}.htp-general-status-list{display:flex;flex-wrap:wrap;gap:8px}.htp-general-status{display:inline-flex;align-items:center;gap:7px;padding:7px 10px;border-radius:999px;background:#f0f0f1;font-weight:600}.htp-general-status small{font-weight:400;opacity:.78}.htp-general-status i{width:9px;height:9px;border-radius:50%;background:#8c8f94}.htp-general-status.is-on{background:#edfaef;color:#176b2c}.htp-general-status.is-on i{background:#00a32a}.htp-general-swatches{display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:10px}.htp-general-swatch{display:flex;align-items:center;gap:10px;padding:10px;border:1px solid #dcdcde;border-radius:8px}.htp-general-swatch>i{width:32px;height:32px;border-radius:7px;border:1px solid rgba(0,0,0,.14);flex:0 0 32px}.htp-general-swatch strong,.htp-general-swatch small{display:block}.htp-general-swatch small{color:#646970;margin-top:2px}.htp-general-legacy{margin:4px 0 24px}@media(max-width:782px){.htp-general-grid,.htp-general-facts,.htp-general-auto{grid-template-columns:1fr}.htp-general-card-head{display:block}.htp-general-card-head .button{margin-top:12px}}
        </style>
        <?php
    }
}
