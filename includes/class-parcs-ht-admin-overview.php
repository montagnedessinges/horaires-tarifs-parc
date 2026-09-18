<?php

if (!defined('ABSPATH')) { exit; }

/** Tableau de bord léger de l'administration. */
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

    /** Compatibilité si la couche de navigation 1.17.2 n'est pas chargée. */
    public static function redirect_default_entry() {
        if (!is_admin() || !current_user_can('manage_options') || !class_exists('Parcs_HT_Admin')) return;
        $page = isset($_GET['page']) ? sanitize_key(wp_unslash($_GET['page'])) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- navigation en lecture seule.
        if ($page !== Parcs_HT_Admin::PAGE) return;
        if (isset($_GET['tab']) || isset($_GET['advent_fragment'])) return; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- navigation en lecture seule.
        $args = array('page'=>self::PAGE);
        $season = isset($_GET['season']) ? sanitize_text_field(wp_unslash($_GET['season'])) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- navigation en lecture seule.
        if (preg_match('/^20\\d{2}$/', $season)) $args['season'] = $season;
        wp_safe_redirect(add_query_arg($args, admin_url('admin.php')));
        exit;
    }

    public static function canonical_back_link() {
        if (!current_user_can('manage_options') || !class_exists('Parcs_HT_Admin')) return;
        $page = isset($_GET['page']) ? sanitize_key(wp_unslash($_GET['page'])) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- navigation en lecture seule.
        if ($page !== Parcs_HT_Admin::PAGE) return;
        $year = isset($_GET['season']) ? sanitize_text_field(wp_unslash($_GET['season'])) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- navigation en lecture seule.
        $args = array('page'=>self::PAGE);
        if (preg_match('/^20\\d{2}$/', $year)) $args['season'] = $year;
        echo '<div class="notice notice-info inline"><p><a class="button" href="' . esc_url(add_query_arg($args, admin_url('admin.php'))) . '">← Retour à la vue d’ensemble</a> <span style="margin-left:8px">Vous êtes dans un écran détaillé de l’extension.</span></p></div>';
    }

    private static function selected_year($all) {
        $requested = isset($_GET['season']) ? sanitize_text_field(wp_unslash($_GET['season'])) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- sélection d'affichage uniquement.
        if ($requested !== '' && isset($all['seasons'][$requested]) && preg_match('/^20\\d{2}$/', $requested)) return $requested;
        if (class_exists('Parcs_HT_Defaults')) {
            $settings = Parcs_HT_Defaults::settings();
            $active = (string)($settings['active_season_year'] ?? '');
            if ($active !== '' && isset($all['seasons'][$active])) return $active;
        }
        foreach (array_keys((array)($all['seasons'] ?? array())) as $year) {
            if (preg_match('/^20\\d{2}$/', (string)$year)) return (string)$year;
        }
        return '';
    }

    private static function main_url($year, $tab) {
        $args = array('page'=>Parcs_HT_Admin::PAGE, 'tab'=>(string)$tab);
        if ($year !== '') $args['season'] = $year;
        return add_query_arg($args, admin_url('admin.php'));
    }

    private static function admin_url_for($page, $year = '') {
        $args = array('page'=>$page);
        if ($year !== '') $args['season'] = $year;
        return add_query_arg($args, admin_url('admin.php'));
    }

    private static function content_url() {
        return add_query_arg(array('page'=>Parcs_HT_Public_Content::PAGE), admin_url('admin.php'));
    }

    private static function cards($year) {
        return array(
            'Préparer l’année' => array(
                array('title'=>'Administration générale', 'text'=>'Parc, saisons, liens, publication annuelle et apparence globale.', 'url'=>self::admin_url_for('parcs-ht-general', $year)),
                array('title'=>'Horaires & calendrier', 'text'=>'Horaires habituels et calendrier de l’année sélectionnée.', 'url'=>self::admin_url_for('parcs-ht-schedule', $year)),
                array('title'=>'Périodes & événements', 'text'=>'Périodes repères, événements, exceptions et accès limité.', 'url'=>self::admin_url_for('parcs-ht-periods', $year)),
                array('title'=>'Tarifs visiteurs', 'text'=>'Individuels, tarifs réduits, moyens de paiement et offres.', 'url'=>self::admin_url_for('parcs-ht-tariffs', $year)),
            ),
            'Groupes' => array(
                array('title'=>'Espace Groupes', 'text'=>'Tarifs groupes, devis groupes et guides pédagogiques réunis dans une même rubrique.', 'url'=>self::admin_url_for('parcs-ht-groups', $year)),
            ),
            'Communication' => array(
                array('title'=>'Communication', 'text'=>'Pop-up et Calendrier de l’Avent. Chaque campagne Avent garde son propre shortcode.', 'url'=>self::admin_url_for('parcs-ht-communication')),
                array('title'=>'Contenus & traductions', 'text'=>'Textes publics, boutons et liens FR / EN / DE.', 'url'=>self::content_url()),
            ),
            'Contrôle & outils' => array(
                array('title'=>'Aperçu', 'text'=>'Vérifier les shortcodes dans les trois langues.', 'url'=>self::admin_url_for('parcs-ht-preview', $year)),
                array('title'=>'Mises à jour', 'text'=>'Version installée, version disponible et mise à jour automatique.', 'url'=>self::admin_url_for('parcs-ht-updates')),
                array('title'=>'Shortcodes', 'text'=>'Retrouver tous les codes à intégrer dans les pages.', 'url'=>self::admin_url_for('parcs-ht-shortcodes', $year)),
            ),
        );
    }

    private static function automatic_state($year) {
        if (!class_exists('Parcs_HT_Public_Visibility')) return 'manual';
        $state = Parcs_HT_Public_Visibility::scheduled_state($year);
        return in_array($state, array('manual','on','off'), true) ? $state : 'manual';
    }

    private static function module_labels() {
        return array(
            'calendar_visible'=>'Calendrier public',
            'retail_tariffs_visible'=>'Tarifs visiteurs',
            'groups_schedule_visible'=>'Horaires groupes',
            'group_quotes_enabled'=>'Devis groupes',
            'group_tariffs_visible'=>'Tarifs groupes',
        );
    }

    private static function manual_state($year, $season, $key) {
        if (array_key_exists($key, $season)) return (string)$season[$key] === '1';
        if ($key === 'calendar_visible') return (string)($season['published'] ?? '0') === '1';
        if ($key === 'retail_tariffs_visible' && class_exists('Parcs_HT_Display_Policy')) return Parcs_HT_Display_Policy::retail_year_is_visible($year, $season);
        if ($key === 'group_quotes_enabled' && class_exists('Parcs_HT_Group_Tariff_Settings')) return Parcs_HT_Group_Tariff_Settings::quote_enabled($year);
        if ($key === 'group_tariffs_visible' && class_exists('Parcs_HT_Group_Tariff_Settings')) return in_array($year, Parcs_HT_Group_Tariff_Settings::public_years(), true);
        return false;
    }

    private static function status_rows($year, $season) {
        $automatic = self::automatic_state($year);
        $out = array();
        foreach (self::module_labels() as $key => $label) {
            $manual = self::manual_state($year, $season, $key);
            $effective = $automatic === 'on' ? true : ($automatic === 'off' ? false : $manual);
            $out[] = array('key'=>$key, 'label'=>$label, 'on'=>$effective, 'manual'=>$manual, 'source'=>$automatic === 'manual' ? 'manuel' : 'automatique');
        }
        return $out;
    }

    private static function date_label($value) {
        $value = (string)$value;
        if (!preg_match('/^(\\d{4})-(\\d{2})-(\\d{2})$/', $value, $m)) return 'non définie';
        return $m[3] . '/' . $m[2] . '/' . $m[1];
    }

    private static function update_state() {
        $latest_version = '';
        $error = '';
        $last_check = 0;
        if (class_exists('Parcs_HT_Updater')) {
            $latest = Parcs_HT_Updater::latest_version();
            if (is_wp_error($latest)) $error = $latest->get_error_message();
            else $latest_version = (string)$latest;
            $last_check = Parcs_HT_Updater::last_check();
        }
        return array(
            'latest'=>$latest_version,
            'error'=>$error,
            'last_check'=>$last_check,
            'available'=>$latest_version !== '' && version_compare($latest_version, PARCS_HT_VERSION, '>'),
        );
    }

    private static function update_panel() {
        if (!current_user_can('update_plugins')) return;
        $state = self::update_state();
        $check_url = wp_nonce_url(admin_url('admin-post.php?action=parcs_ht_check_updates_1172'), 'parcs_ht_check_updates_1172');
        $plugin = plugin_basename(PARCS_HT_FILE);
        $update_url = wp_nonce_url(self_admin_url('update.php?action=upgrade-plugin&plugin=' . rawurlencode($plugin)), 'upgrade-plugin_' . $plugin);
        ?>
        <section class="htp-overview-update <?php echo $state['available'] ? 'has-update' : ''; ?>">
            <div>
                <h2>Mise à jour de l’extension</h2>
                <p>Installée : <strong><?php echo esc_html(PARCS_HT_VERSION); ?></strong> · Disponible : <strong><?php echo esc_html($state['latest'] !== '' ? $state['latest'] : '—'); ?></strong></p>
                <p>État : <strong><?php echo esc_html($state['error'] !== '' ? 'vérification impossible' : ($state['available'] ? 'mise à jour disponible' : ($state['latest'] !== '' ? 'à jour' : 'non vérifié'))); ?></strong><?php if ($state['last_check']) : ?> · dernière vérification <?php echo esc_html(wp_date('d/m/Y H:i', $state['last_check'])); ?><?php endif; ?></p>
                <?php if ($state['error'] !== '') : ?><small><?php echo esc_html($state['error']); ?></small><?php endif; ?>
            </div>
            <div class="htp-overview-update-actions">
                <a class="button" href="<?php echo esc_url($check_url); ?>">Vérifier maintenant</a>
                <?php if ($state['available']) : ?><a class="button button-primary" href="<?php echo esc_url($update_url); ?>">Mettre à jour</a><?php endif; ?>
                <a class="button-link" href="<?php echo esc_url(self::admin_url_for('parcs-ht-updates')); ?>">Réglages des mises à jour</a>
            </div>
        </section>
        <?php
    }

    private static function activation_form($year, $season) {
        if ($year === '' || !class_exists('Parcs_HT_Admin_General')) return;
        ?>
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="htp-overview-activation-form">
            <input type="hidden" name="action" value="parcs_ht_save_general_publication">
            <input type="hidden" name="season_year" value="<?php echo esc_attr($year); ?>">
            <?php wp_nonce_field('parcs_ht_save_general_publication'); ?>
            <div class="htp-overview-activation-grid">
                <?php foreach (self::module_labels() as $key => $label) : $on = self::manual_state($year, $season, $key); ?>
                    <label><span><?php echo esc_html($label); ?></span><select name="publication[<?php echo esc_attr($key); ?>]"><option value="1" <?php selected($on); ?>>OUI</option><option value="0" <?php selected(!$on); ?>>NON</option></select></label>
                <?php endforeach; ?>
                <label><span>Activation automatique</span><input type="date" name="publication[public_display_from]" value="<?php echo esc_attr((string)($season['public_display_from'] ?? '')); ?>"></label>
                <label><span>Désactivation automatique</span><input type="date" name="publication[public_display_until]" value="<?php echo esc_attr((string)($season['public_display_until'] ?? '')); ?>"></label>
            </div>
            <p><button type="submit" class="button button-primary">Enregistrer les activations de <?php echo esc_html($year); ?></button> <a class="button-link" href="<?php echo esc_url(self::admin_url_for('parcs-ht-general', $year)); ?>">Réglages complets dans Administration générale</a></p>
        </form>
        <?php
    }

    public static function page() {
        if (!current_user_can('manage_options')) return;
        $all = Parcs_HT_Defaults::all_settings();
        $year = self::selected_year($all);
        $season = ($year !== '' && isset($all['seasons'][$year]) && is_array($all['seasons'][$year])) ? $all['seasons'][$year] : array();
        $years = array();
        foreach (array_keys((array)($all['seasons'] ?? array())) as $candidate) if (preg_match('/^20\\d{2}$/', (string)$candidate)) $years[] = (string)$candidate;
        sort($years, SORT_NUMERIC);
        $automatic = $year !== '' ? self::automatic_state($year) : 'manual';
        ?>
        <div class="wrap htp-overview">
            <h1>Gestion du parc — vue d’ensemble</h1>
            <p class="description">Le tableau de bord résume ce qui est actif et donne accès aux rubriques sans charger toute l’administration sur cet écran.</p>

            <?php if (isset($_GET['publication_updated'])) : /* phpcs:ignore WordPress.Security.NonceVerification.Recommended -- message visuel uniquement. */ ?><div class="notice notice-success is-dismissible"><p>Les activations de l’année ont été enregistrées.</p></div><?php endif; ?>
            <?php if (isset($_GET['checked'])) : /* phpcs:ignore WordPress.Security.NonceVerification.Recommended -- message visuel uniquement. */ ?><div class="notice notice-success is-dismissible"><p>La vérification des mises à jour a été relancée.</p></div><?php endif; ?>

            <?php if ($years) : ?>
                <nav class="htp-overview-years" aria-label="Saisons">
                    <strong>Année administrée :</strong>
                    <?php foreach ($years as $candidate) : $url = add_query_arg(array('page'=>self::PAGE, 'season'=>$candidate), admin_url('admin.php')); ?>
                        <a class="button <?php echo $candidate === $year ? 'button-primary' : ''; ?>" href="<?php echo esc_url($url); ?>"><?php echo esc_html($candidate); ?></a>
                    <?php endforeach; ?>
                </nav>
            <?php endif; ?>

            <?php self::update_panel(); ?>

            <?php if ($year !== '') : ?>
                <section class="htp-overview-status">
                    <div>
                        <h2>État public de <?php echo esc_html($year); ?></h2>
                        <p>Saison : <strong><?php echo esc_html(self::date_label($season['season_start'] ?? '')); ?></strong> → <strong><?php echo esc_html(self::date_label($season['season_end'] ?? '')); ?></strong></p>
                        <p>Activation automatique : <strong><?php echo esc_html(self::date_label($season['public_display_from'] ?? '')); ?></strong><br>Désactivation automatique : <strong><?php echo esc_html(self::date_label($season['public_display_until'] ?? '')); ?></strong></p>
                        <p class="htp-overview-auto-state">État actuel : <strong><?php echo esc_html($automatic === 'on' ? 'année activée automatiquement' : ($automatic === 'off' ? 'année désactivée automatiquement' : 'interrupteurs manuels')); ?></strong></p>
                    </div>
                    <div class="htp-overview-status-list">
                        <?php foreach (self::status_rows($year, $season) as $row) : ?>
                            <span class="htp-overview-status-pill <?php echo $row['on'] ? 'is-on' : 'is-off'; ?>"><i aria-hidden="true"></i><?php echo esc_html($row['label']); ?> : <?php echo $row['on'] ? 'affiché' : 'masqué'; ?> <small>(<?php echo esc_html($row['source']); ?>)</small></span>
                        <?php endforeach; ?>
                    </div>
                </section>
                <details class="htp-overview-activations">
                    <summary>Modifier les activations de <?php echo esc_html($year); ?></summary>
                    <p class="description">Ces champs utilisent la même action d’enregistrement que l’Administration générale : il n’existe pas de deuxième stockage.</p>
                    <?php self::activation_form($year, $season); ?>
                </details>
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
        .htp-overview{max-width:1240px}.htp-overview-years{display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin:18px 0}.htp-overview-update{display:grid;grid-template-columns:minmax(300px,1fr) auto;gap:18px;align-items:center;background:#fff;border:1px solid #dcdcde;border-radius:10px;padding:18px 20px;margin:18px 0}.htp-overview-update.has-update{border-color:#dba617}.htp-overview-update h2{margin:0 0 8px}.htp-overview-update p{margin:4px 0}.htp-overview-update small{display:block;color:#646970;margin-top:6px}.htp-overview-update-actions{display:flex;align-items:center;gap:10px;flex-wrap:wrap;justify-content:flex-end}.htp-overview-status{display:grid;grid-template-columns:minmax(270px,.9fr) minmax(300px,2fr);gap:20px;align-items:start;background:#fff;border:1px solid #dcdcde;border-radius:10px;padding:18px 20px;margin:18px 0 12px}.htp-overview-status h2{margin:0 0 8px}.htp-overview-status p{margin:0 0 8px;color:#646970;line-height:1.55}.htp-overview-auto-state{padding-top:4px}.htp-overview-status-list{display:flex;flex-wrap:wrap;gap:8px}.htp-overview-status-pill{display:inline-flex;align-items:center;gap:7px;padding:7px 10px;border-radius:999px;background:#f0f0f1;font-weight:600}.htp-overview-status-pill small{font-weight:400;opacity:.78}.htp-overview-status-pill i{width:9px;height:9px;border-radius:50%;background:#8c8f94}.htp-overview-status-pill.is-on{background:#edfaef;color:#176b2c}.htp-overview-status-pill.is-on i{background:#00a32a}.htp-overview-activations{background:#fff;border:1px solid #dcdcde;border-radius:10px;padding:0 18px;margin:0 0 26px}.htp-overview-activations>summary{cursor:pointer;font-weight:700;padding:15px 0}.htp-overview-activation-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:12px}.htp-overview-activation-grid label{display:flex;flex-direction:column;gap:5px}.htp-overview-activation-grid label>span{font-weight:600}.htp-overview-section{margin:0 0 28px}.htp-overview-section>h2{margin:0 0 12px}.htp-overview-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(245px,1fr));gap:12px}.htp-overview-card{display:flex;min-height:128px;flex-direction:column;gap:8px;padding:18px;border:1px solid #dcdcde;border-radius:10px;background:#fff;color:#1d2327;text-decoration:none;box-shadow:0 1px 1px rgba(0,0,0,.02)}.htp-overview-card:hover,.htp-overview-card:focus{border-color:#2271b1;box-shadow:0 0 0 1px #2271b1}.htp-overview-card strong{font-size:16px}.htp-overview-card span{color:#50575e;line-height:1.45}.htp-overview-card em{margin-top:auto;color:#2271b1;font-style:normal;font-weight:600}@media(max-width:900px){.htp-overview-activation-grid{grid-template-columns:1fr 1fr}}@media(max-width:782px){.htp-overview-update,.htp-overview-status{grid-template-columns:1fr}.htp-overview-update-actions{justify-content:flex-start}.htp-overview-grid,.htp-overview-activation-grid{grid-template-columns:1fr}}
        </style>
        <?php
    }
}
