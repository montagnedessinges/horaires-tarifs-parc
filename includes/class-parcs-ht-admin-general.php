<?php

if (!defined('ABSPATH')) { exit; }

/** Administration générale 1.17.1. */
final class Parcs_HT_Admin_General {
    const PAGE = 'parcs-ht-general';

    public static function init() {
        add_action('admin_menu', array(__CLASS__, 'menu'), 35);
        add_action('admin_init', array(__CLASS__, 'redirect_default_entry'), 10);
        add_action('admin_post_parcs_ht_save_general_publication', array(__CLASS__, 'save_publication'));
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
     * L’entrée principale et les retours des actions de saison ouvrent la nouvelle
     * Administration générale. Une URL détaillée munie de `tab` reste inchangée.
     */
    public static function redirect_default_entry() {
        if (!is_admin() || !current_user_can('manage_options') || !class_exists('Parcs_HT_Admin')) return;
        $page = isset($_GET['page']) ? sanitize_key(wp_unslash($_GET['page'])) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- navigation en lecture seule.
        if ($page !== Parcs_HT_Admin::PAGE) return;
        if (isset($_GET['tab']) || isset($_GET['advent_fragment'])) return; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- navigation en lecture seule.

        $args = array('page'=>self::PAGE);
        $season = isset($_GET['season']) ? sanitize_text_field(wp_unslash($_GET['season'])) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- navigation en lecture seule.
        if (preg_match('/^20\d{2}$/', $season)) $args['season'] = $season;
        foreach (array('duplicated','deleted','updated','preserved') as $notice) {
            if (isset($_GET[$notice])) $args[$notice] = '1'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- indicateur visuel uniquement.
        }
        wp_safe_redirect(add_query_arg($args, admin_url('admin.php')));
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

    private static function clean_date($value) {
        $value = trim((string)$value);
        return preg_match('/^20\d{2}-\d{2}-\d{2}$/', $value) ? $value : '';
    }

    private static function date_label($value) {
        $value = self::clean_date($value);
        if ($value === '') return 'non définie';
        list($year, $month, $day) = explode('-', $value);
        return $day . '/' . $month . '/' . $year;
    }

    private static function automatic_state($year) {
        if (!class_exists('Parcs_HT_Public_Visibility')) return 'manual';
        $state = Parcs_HT_Public_Visibility::scheduled_state($year);
        return in_array($state, array('manual','on','off'), true) ? $state : 'manual';
    }

    private static function manual_module_state($year, $season, $key) {
        if (array_key_exists($key, $season)) return (string)$season[$key] === '1';
        if ($key === 'calendar_visible') return (string)($season['published'] ?? '0') === '1';
        if ($key === 'retail_tariffs_visible' && class_exists('Parcs_HT_Display_Policy')) return Parcs_HT_Display_Policy::retail_year_is_visible($year, $season);
        if ($key === 'group_quotes_enabled' && class_exists('Parcs_HT_Group_Tariff_Settings')) return Parcs_HT_Group_Tariff_Settings::quote_enabled($year);
        if ($key === 'group_tariffs_visible' && class_exists('Parcs_HT_Group_Tariff_Settings')) return in_array($year, Parcs_HT_Group_Tariff_Settings::public_years(), true);
        return false;
    }

    private static function module_labels() {
        return array(
            'calendar_visible'=>'Afficher le calendrier public',
            'retail_tariffs_visible'=>'Afficher les tarifs visiteurs',
            'groups_schedule_visible'=>'Afficher les horaires aux groupes',
            'group_quotes_enabled'=>'Activer les devis groupes',
            'group_tariffs_visible'=>'Afficher les tarifs groupes sur le site',
        );
    }

    private static function status_rows($year, $season) {
        $automatic = self::automatic_state($year);
        $out = array();
        foreach (self::module_labels() as $key => $label) {
            $manual = self::manual_module_state($year, $season, $key);
            $effective = $automatic === 'on' ? true : ($automatic === 'off' ? false : $manual);
            $out[] = array('label'=>$label, 'on'=>$effective, 'source'=>$automatic === 'manual' ? 'manuel' : 'automatique');
        }
        return $out;
    }

    public static function save_publication() {
        if (!current_user_can('manage_options')) wp_die('Accès refusé.');
        check_admin_referer('parcs_ht_save_general_publication');

        $year = isset($_POST['season_year']) ? sanitize_text_field(wp_unslash($_POST['season_year'])) : '';
        if (!preg_match('/^20\d{2}$/', $year)) wp_die('Année invalide.');
        $all = Parcs_HT_Defaults::all_settings();
        if (!isset($all['seasons'][$year]) || !is_array($all['seasons'][$year])) wp_die('Saison introuvable.');
        $raw = isset($_POST['publication']) && is_array($_POST['publication']) ? map_deep(wp_unslash($_POST['publication']), 'sanitize_text_field') : array();

        foreach (array_keys(self::module_labels()) as $key) {
            $all['seasons'][$year][$key] = isset($raw[$key]) && (string)$raw[$key] === '1' ? '1' : '0';
        }
        $all['seasons'][$year]['published'] = $all['seasons'][$year]['calendar_visible'];
        $all['seasons'][$year]['public_display_from'] = self::clean_date($raw['public_display_from'] ?? '');
        $all['seasons'][$year]['public_display_until'] = self::clean_date($raw['public_display_until'] ?? '');

        update_option(Parcs_HT_Defaults::OPTION, $all, false);
        do_action('litespeed_purge_all');
        wp_safe_redirect(add_query_arg(array('page'=>self::PAGE, 'season'=>$year, 'publication_updated'=>'1'), admin_url('admin.php')));
        exit;
    }

    private static function render_seasons($all, $year) {
        $years = array();
        foreach (array_keys((array)($all['seasons'] ?? array())) as $candidate) if (preg_match('/^20\d{2}$/', (string)$candidate)) $years[] = (string)$candidate;
        sort($years, SORT_NUMERIC);
        ?>
        <section class="htp-general-card htp-general-seasons-card">
            <div class="htp-general-card-head"><div><h2>Saisons</h2><p>Choisissez l’année à préparer. Les actions utilisent les mêmes sécurités que l’écran historique.</p></div></div>
            <div class="htp-general-season-list">
                <?php foreach ($years as $candidate) :
                    $candidate_season = isset($all['seasons'][$candidate]) && is_array($all['seasons'][$candidate]) ? $all['seasons'][$candidate] : array();
                    $is_public = false;
                    foreach (array_keys(self::module_labels()) as $key) if (self::manual_module_state($candidate, $candidate_season, $key)) { $is_public = true; break; }
                    ?>
                    <div class="htp-general-season <?php echo $candidate === $year ? 'is-current' : ''; ?>">
                        <a class="htp-general-season-main" href="<?php echo esc_url(self::overview_url($candidate)); ?>"><strong><?php echo esc_html($candidate); ?></strong><span><?php echo $is_public ? 'au moins un module activé' : 'préparation / tout masqué'; ?></span></a>
                        <div class="htp-general-season-actions">
                            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>"><input type="hidden" name="action" value="parcs_ht_duplicate_season"><input type="hidden" name="season_year" value="<?php echo esc_attr($candidate); ?>"><?php wp_nonce_field('parcs_ht_duplicate_season_' . $candidate); ?><button class="button button-small" type="submit">Préparer l’année suivante</button></form>
                            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>"><input type="hidden" name="action" value="parcs_ht_delete_season"><input type="hidden" name="season_year" value="<?php echo esc_attr($candidate); ?>"><?php wp_nonce_field('parcs_ht_delete_season_' . $candidate); ?><button class="button-link-delete" type="submit">Supprimer</button></form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="htp-general-add-season">
                <input type="hidden" name="action" value="parcs_ht_add_season">
                <?php wp_nonce_field('parcs_ht_add_season'); ?>
                <label><span>Nouvelle année</span><input type="number" min="2020" max="2100" name="season_year" placeholder="<?php echo esc_attr((string)((int)wp_date('Y') + 1)); ?>" required></label>
                <button type="submit" class="button">Ajouter une saison</button>
            </form>
        </section>
        <?php
    }

    private static function render_publication($year, $season) {
        if ($year === '') return;
        $automatic = self::automatic_state($year);
        ?>
        <section class="htp-general-card htp-general-publication">
            <div class="htp-general-card-head"><div><h2>Publication de l’année sélectionnée — <?php echo esc_html($year); ?></h2><p>Avant la date d’activation automatique, chaque interrupteur reste indépendant. La date de désactivation est prioritaire.</p></div></div>

            <div class="htp-general-status-list">
                <?php foreach (self::status_rows($year, $season) as $row) : ?><span class="htp-general-status <?php echo $row['on'] ? 'is-on' : 'is-off'; ?>"><i aria-hidden="true"></i><?php echo esc_html($row['label']); ?> : <?php echo $row['on'] ? 'affiché' : 'masqué'; ?> <small>(<?php echo esc_html($row['source']); ?>)</small></span><?php endforeach; ?>
            </div>

            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="htp-general-publication-form">
                <input type="hidden" name="action" value="parcs_ht_save_general_publication">
                <input type="hidden" name="season_year" value="<?php echo esc_attr($year); ?>">
                <?php wp_nonce_field('parcs_ht_save_general_publication'); ?>
                <div class="htp-general-publication-grid">
                    <?php foreach (self::module_labels() as $key => $label) : $on = self::manual_module_state($year, $season, $key); ?>
                        <label class="htp-general-publication-field"><span><?php echo esc_html($label); ?></span><select name="publication[<?php echo esc_attr($key); ?>]"><option value="1" <?php selected($on); ?>>OUI — activé</option><option value="0" <?php selected(!$on); ?>>NON — désactivé</option></select></label>
                    <?php endforeach; ?>
                    <label class="htp-general-publication-field"><span>Activer automatiquement toute l’année à partir du</span><input type="date" name="publication[public_display_from]" value="<?php echo esc_attr(self::clean_date($season['public_display_from'] ?? '')); ?>"><small>À cette date, les cinq modules sont affichés même si un interrupteur est sur NON.</small></label>
                    <label class="htp-general-publication-field"><span>Désactiver automatiquement toute l’année à partir du</span><input type="date" name="publication[public_display_until]" value="<?php echo esc_attr(self::clean_date($season['public_display_until'] ?? '')); ?>"><small>À cette date, les cinq modules sont masqués, même si un interrupteur est sur OUI.</small></label>
                </div>
                <p class="description">État automatique actuel : <strong><?php echo esc_html($automatic === 'on' ? 'année activée automatiquement' : ($automatic === 'off' ? 'année désactivée automatiquement' : 'interrupteurs manuels')); ?></strong>. Activation : <?php echo esc_html(self::date_label($season['public_display_from'] ?? '')); ?> · Désactivation : <?php echo esc_html(self::date_label($season['public_display_until'] ?? '')); ?>.</p>
                <?php submit_button('Enregistrer la publication de ' . $year); ?>
            </form>
        </section>
        <?php
    }

    public static function page() {
        if (!current_user_can('manage_options')) return;
        $all = Parcs_HT_Defaults::all_settings();
        $year = self::selected_year($all);
        $season = ($year !== '' && isset($all['seasons'][$year]) && is_array($all['seasons'][$year])) ? $all['seasons'][$year] : array();
        ?>
        <div class="wrap htp-general-admin">
            <h1>Administration générale</h1>
            <p class="description">Gérez ici uniquement les éléments transversaux. Les horaires, tarifs, groupes, devis, guides, pop-up et Calendrier de l’Avent restent dans leurs catégories dédiées.</p>

            <?php if (isset($_GET['park_updated'])) : /* phpcs:ignore WordPress.Security.NonceVerification.Recommended -- message visuel uniquement. */ ?><div class="notice notice-success is-dismissible"><p>Les informations générales du parc ont été enregistrées.</p></div><?php endif; ?>
            <?php if (isset($_GET['appearance_updated'])) : /* phpcs:ignore WordPress.Security.NonceVerification.Recommended -- message visuel uniquement. */ ?><div class="notice notice-success is-dismissible"><p>L’apparence globale a été enregistrée.</p></div><?php endif; ?>
            <?php if (isset($_GET['publication_updated'])) : /* phpcs:ignore WordPress.Security.NonceVerification.Recommended -- message visuel uniquement. */ ?><div class="notice notice-success is-dismissible"><p>La publication de l’année <?php echo esc_html($year); ?> a été enregistrée.</p></div><?php endif; ?>
            <?php if (isset($_GET['duplicated'])) : /* phpcs:ignore WordPress.Security.NonceVerification.Recommended -- message visuel uniquement. */ ?><div class="notice notice-success is-dismissible"><p>La nouvelle saison brouillon a été préparée. Ses cinq modules publics restent désactivés par défaut.</p></div><?php endif; ?>

            <?php if (class_exists('Parcs_HT_Admin_General_Park')) Parcs_HT_Admin_General_Park::render($all, $year); ?>
            <?php self::render_seasons($all, $year); ?>
            <?php self::render_publication($year, $season); ?>
            <?php if (class_exists('Parcs_HT_Global_Appearance_Admin')) Parcs_HT_Global_Appearance_Admin::render($all, $year); ?>

            <p class="htp-general-legacy"><a href="<?php echo esc_url(self::detailed_url($year, 'htp-general')); ?>">Ouvrir les réglages détaillés historiques</a> — conservés temporairement comme filet de sécurité pendant la refonte 1.17.x.</p>
        </div>
        <style>
        .htp-general-admin{max-width:1240px}.htp-general-card{margin:16px 0;padding:20px;background:#fff;border:1px solid #dcdcde;border-radius:10px}.htp-general-card-head{display:flex;justify-content:space-between;align-items:flex-start;gap:16px;margin-bottom:16px}.htp-general-card-head h2{margin:0 0 6px}.htp-general-card-head p{margin:0;color:#646970}.htp-general-season-list{display:grid;grid-template-columns:repeat(auto-fit,minmax(230px,1fr));gap:10px}.htp-general-season{border:1px solid #dcdcde;border-radius:8px;padding:12px;background:#fff}.htp-general-season.is-current{border-color:#2271b1;box-shadow:0 0 0 1px #2271b1}.htp-general-season-main{display:flex;flex-direction:column;gap:3px;color:#1d2327;text-decoration:none}.htp-general-season-main span{font-size:12px;color:#646970}.htp-general-season-actions{display:flex;align-items:center;gap:10px;flex-wrap:wrap;margin-top:10px}.htp-general-season-actions form{margin:0}.htp-general-add-season{display:flex;align-items:end;gap:10px;flex-wrap:wrap;margin-top:16px;padding-top:16px;border-top:1px solid #dcdcde}.htp-general-add-season label{display:flex;flex-direction:column;gap:5px;font-weight:600}.htp-general-status-list{display:flex;flex-wrap:wrap;gap:8px;margin-bottom:18px}.htp-general-status{display:inline-flex;align-items:center;gap:7px;padding:7px 10px;border-radius:999px;background:#f0f0f1;font-weight:600}.htp-general-status small{font-weight:400;opacity:.78}.htp-general-status i{width:9px;height:9px;border-radius:50%;background:#8c8f94}.htp-general-status.is-on{background:#edfaef;color:#176b2c}.htp-general-status.is-on i{background:#00a32a}.htp-general-publication-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px}.htp-general-publication-field{display:flex;flex-direction:column;gap:6px;padding:12px;background:#f6f7f7;border-radius:8px}.htp-general-publication-field>span{font-weight:600}.htp-general-publication-field small{color:#646970}.htp-general-legacy{text-align:right}.htp-general-legacy a{text-decoration:none}@media(max-width:900px){.htp-general-card-head{flex-direction:column}.htp-general-publication-grid{grid-template-columns:1fr}}
        </style>
        <?php
    }
}
