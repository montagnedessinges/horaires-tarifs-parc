<?php

if (!defined('ABSPATH')) { exit; }

/**
 * Architecture d'administration 1.17.2+.
 *
 * Cette couche organise la navigation sans remplacer les moteurs historiques.
 * Les écrans métier sont migrés progressivement dans les versions suivantes ;
 * les ponts ci-dessous gardent leurs URLs canoniques pendant cette transition.
 */
final class Parcs_HT_Admin_Navigation {
    const GROUPS_PAGE = 'parcs-ht-groups';
    const COMMUNICATION_PAGE = 'parcs-ht-communication';
    const PERIODS_PAGE = 'parcs-ht-periods';
    const UPDATES_PAGE = 'parcs-ht-updates';

    private static $bridges = array(
        'parcs-ht-tariffs'    => 'htp-tariffs',
        'parcs-ht-preview'    => 'htp-preview',
        'parcs-ht-shortcodes' => 'htp-shortcodes',
    );

    public static function init() {
        add_action('admin_menu', array(__CLASS__, 'menu'), 60);
        add_action('admin_menu', array(__CLASS__, 'order_menu'), 999);
        add_action('admin_init', array(__CLASS__, 'route'), 1);
        add_action('admin_post_parcs_ht_check_updates_1172', array(__CLASS__, 'check_updates'));
        add_action('admin_post_parcs_ht_save_update_preferences_1172', array(__CLASS__, 'save_update_preferences'));
        add_action('admin_head', array(__CLASS__, 'admin_head'));
    }

    private static function legacy_url($tab, $year = '') {
        if (!class_exists('Parcs_HT_Admin')) return admin_url('admin.php');
        $args = array('page'=>Parcs_HT_Admin::PAGE, 'tab'=>$tab);
        if (preg_match('/^20\d{2}$/', (string)$year)) $args['season'] = (string)$year;
        return add_query_arg($args, admin_url('admin.php'));
    }

    private static function requested_year() {
        $year = isset($_GET['season']) ? sanitize_text_field(wp_unslash($_GET['season'])) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- navigation en lecture seule.
        return preg_match('/^20\d{2}$/', $year) ? $year : '';
    }

    private static function redirect($url) {
        wp_safe_redirect($url);
        exit;
    }

    /** Affiche le contexte d'année commun aux écrans métier annuels. */
    public static function render_year_context($page, $current_year = '') {
        if (!current_user_can('manage_options')) return;
        $all = Parcs_HT_Defaults::all_settings();
        $seasons = isset($all['seasons']) && is_array($all['seasons']) ? $all['seasons'] : array();
        if (!$seasons) return;
        echo '<nav class="htp-1173-year-context" aria-label="Année administrée"><strong>Année administrée :</strong>';
        foreach ($seasons as $year => $season) {
            unset($season);
            $year = (string)$year;
            if (!preg_match('/^20\d{2}$/', $year)) continue;
            $args = array('page'=>(string)$page, 'season'=>$year);
            $class = $year === (string)$current_year ? 'button button-primary is-current' : 'button';
            echo '<a class="' . esc_attr($class) . '" href="' . esc_url(add_query_arg($args, admin_url('admin.php'))) . '">' . esc_html($year) . '</a>';
        }
        echo '</nav>';
    }

    /**
     * Priorité 1 : l'entrée principale ouvre toujours la Vue d'ensemble avant
     * les anciens redirects de compatibilité de l'Administration générale.
     */
    public static function route() {
        if (!is_admin() || !current_user_can('manage_options') || !class_exists('Parcs_HT_Admin')) return;
        $page = isset($_GET['page']) ? sanitize_key(wp_unslash($_GET['page'])) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- navigation en lecture seule.
        $tab = isset($_GET['tab']) ? sanitize_key(wp_unslash($_GET['tab'])) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- navigation en lecture seule.
        $year = self::requested_year();

        if ($page === Parcs_HT_Admin::PAGE && $tab === '') {
            $args = array('page'=>class_exists('Parcs_HT_Admin_Overview') ? Parcs_HT_Admin_Overview::PAGE : 'parcs-ht-overview');
            if ($year !== '') $args['season'] = $year;
            foreach (array('duplicated','deleted','updated','preserved','update-check') as $notice) {
                if (isset($_GET[$notice])) $args[$notice] = '1'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- indicateur visuel uniquement.
            }
            self::redirect(add_query_arg($args, admin_url('admin.php')));
        }

        if ($page === Parcs_HT_Admin::PAGE && $tab === 'htp-general' && class_exists('Parcs_HT_Admin_General')) {
            $args = array('page'=>Parcs_HT_Admin_General::PAGE);
            if ($year !== '') $args['season'] = $year;
            self::redirect(add_query_arg($args, admin_url('admin.php')));
        }

        // 1.17.3 : les anciens liens vers l'onglet Horaires arrivent sur le nouvel écran dédié.
        if ($page === Parcs_HT_Admin::PAGE && $tab === 'htp-regular' && class_exists('Parcs_HT_Admin_Schedule')) {
            $args = array('page'=>Parcs_HT_Admin_Schedule::PAGE);
            if ($year !== '') $args['season'] = $year;
            foreach (array('updated','csv_imported') as $notice) if (isset($_GET[$notice])) $args[$notice] = '1'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- indicateur visuel uniquement.
            self::redirect(add_query_arg($args, admin_url('admin.php')));
        }

        if ($page === Parcs_HT_Admin::PAGE && $tab === 'htp-updates') {
            self::redirect(add_query_arg(array('page'=>self::UPDATES_PAGE), admin_url('admin.php')));
        }

        if (isset(self::$bridges[$page])) {
            self::redirect(self::legacy_url(self::$bridges[$page], $year));
        }
    }

    public static function menu() {
        if (!class_exists('Parcs_HT_Admin')) return;
        $parent = Parcs_HT_Admin::PAGE;

        add_submenu_page($parent, 'Horaires & calendrier', 'Horaires & calendrier', 'manage_options', 'parcs-ht-schedule', class_exists('Parcs_HT_Admin_Schedule') ? array('Parcs_HT_Admin_Schedule', 'page') : array(__CLASS__, 'bridge_fallback'));
        add_submenu_page($parent, 'Périodes, événements et exceptions', 'Périodes & événements', 'manage_options', self::PERIODS_PAGE, array(__CLASS__, 'periods_page'));
        add_submenu_page($parent, 'Tarifs visiteurs', 'Tarifs visiteurs', 'manage_options', 'parcs-ht-tariffs', array(__CLASS__, 'bridge_fallback'));
        add_submenu_page($parent, 'Groupes', 'Groupes', 'manage_options', self::GROUPS_PAGE, array(__CLASS__, 'groups_page'));
        add_submenu_page($parent, 'Communication', 'Communication', 'manage_options', self::COMMUNICATION_PAGE, array(__CLASS__, 'communication_page'));
        add_submenu_page($parent, 'Aperçu', 'Aperçu', 'manage_options', 'parcs-ht-preview', array(__CLASS__, 'bridge_fallback'));
        add_submenu_page($parent, 'Mises à jour', 'Mises à jour', 'update_plugins', self::UPDATES_PAGE, array(__CLASS__, 'updates_page'));
        add_submenu_page($parent, 'Shortcodes', 'Shortcodes', 'manage_options', 'parcs-ht-shortcodes', array(__CLASS__, 'bridge_fallback'));
    }

    /** Range le sous-menu dans l'ordre fonctionnel retenu. */
    public static function order_menu() {
        if (!class_exists('Parcs_HT_Admin')) return;
        global $submenu;
        $parent = Parcs_HT_Admin::PAGE;
        if (empty($submenu[$parent]) || !is_array($submenu[$parent])) return;

        $items = array();
        foreach ($submenu[$parent] as $item) {
            if (isset($item[2]) && $item[2] === $parent) continue;
            $items[] = $item;
        }

        $content_slug = class_exists('Parcs_HT_Public_Content') ? Parcs_HT_Public_Content::PAGE : 'parcs-ht-public-content';
        $order = array(
            class_exists('Parcs_HT_Admin_Overview') ? Parcs_HT_Admin_Overview::PAGE : 'parcs-ht-overview',
            class_exists('Parcs_HT_Admin_General') ? Parcs_HT_Admin_General::PAGE : 'parcs-ht-general',
            'parcs-ht-schedule',
            self::PERIODS_PAGE,
            'parcs-ht-tariffs',
            self::GROUPS_PAGE,
            self::COMMUNICATION_PAGE,
            $content_slug,
            'parcs-ht-preview',
            self::UPDATES_PAGE,
            'parcs-ht-shortcodes',
        );
        $rank = array_flip($order);
        usort($items, static function ($a, $b) use ($rank) {
            $ra = isset($rank[$a[2]]) ? $rank[$a[2]] : 999;
            $rb = isset($rank[$b[2]]) ? $rank[$b[2]] : 999;
            if ($ra === $rb) return 0;
            return $ra < $rb ? -1 : 1;
        });
        $submenu[$parent] = $items;
    }

    public static function bridge_fallback() {
        if (!current_user_can('manage_options')) return;
        echo '<div class="wrap"><h1>Gestion du parc</h1><p>Redirection vers la rubrique demandée…</p></div>';
    }

    private static function landing_header($title, $description) {
        echo '<div class="wrap htp-1172-landing"><h1>' . esc_html($title) . '</h1><p class="description">' . esc_html($description) . '</p>';
    }

    private static function card($title, $text, $url) {
        echo '<a class="htp-1172-card" href="' . esc_url($url) . '"><strong>' . esc_html($title) . '</strong><span>' . esc_html($text) . '</span><em>Ouvrir →</em></a>';
    }

    public static function periods_page() {
        if (!current_user_can('manage_options')) return;
        $year = self::requested_year();
        self::landing_header('Périodes, événements et exceptions', 'Accédez directement au réglage voulu. Chaque moteur reste indépendant pendant la refonte progressive.');
        echo '<div class="htp-1172-grid">';
        self::card('Périodes & événements', 'Vacances, périodes repères, jours fériés et événements.', self::legacy_url('htp-holidays', $year));
        self::card('Exceptions', 'Fermetures et horaires exceptionnels.', self::legacy_url('htp-exceptions', $year));
        self::card('Accès limité', 'Interruption temporaire d’une zone sans fermer le parc.', self::legacy_url('htp-domain', $year));
        echo '</div></div>';
    }

    public static function groups_page() {
        if (!current_user_can('manage_options')) return;
        $year = self::requested_year();
        self::landing_header('Groupes', 'Les tarifs groupes, les devis et les guides pédagogiques sont regroupés ici, tout en restant des écrans séparés.');
        echo '<div class="htp-1172-grid">';
        self::card('Tarifs groupes', 'Tarifs, moyens de paiement, informations et apparence propres aux groupes.', self::legacy_url('htp-tariffs-groups', $year));
        self::card('Devis groupes', 'Disponibilité, formulaire et règles de devis.', self::legacy_url('htp-quote', $year));
        self::card('Guides pédagogiques', 'Documents scolaires, langues, cycles et statistiques.', self::legacy_url('htp-guides', $year));
        echo '</div></div>';
    }

    public static function communication_page() {
        if (!current_user_can('manage_options')) return;
        $year = self::requested_year();
        self::landing_header('Communication', 'Les outils de communication sont regroupés sans mélanger leurs données. Le Calendrier de l’Avent reste une campagne indépendante avec son propre shortcode.');
        echo '<div class="htp-1172-grid">';
        self::card('Pop-up', 'Alertes et communication publique du parc.', self::legacy_url('htp-alerts', $year));
        self::card('Calendrier de l’Avent', 'Campagnes indépendantes, contenus, partenaires et shortcodes propres.', self::legacy_url('htp-advent', ''));
        echo '</div></div>';
    }

    public static function check_updates() {
        if (!current_user_can('update_plugins')) wp_die('Accès refusé.');
        check_admin_referer('parcs_ht_check_updates_1172');
        if (class_exists('Parcs_HT_Updater')) Parcs_HT_Updater::clear_cache();
        if (!function_exists('wp_update_plugins')) require_once ABSPATH . 'wp-admin/includes/update.php';
        wp_update_plugins();
        self::redirect(add_query_arg(array('page'=>self::UPDATES_PAGE, 'checked'=>'1'), admin_url('admin.php')));
    }

    public static function save_update_preferences() {
        if (!current_user_can('update_plugins')) wp_die('Accès refusé.');
        check_admin_referer('parcs_ht_save_update_preferences_1172');
        if (class_exists('Parcs_HT_Updater')) {
            $enabled = isset($_POST['auto_update']) && sanitize_text_field(wp_unslash($_POST['auto_update'])) === '1';
            Parcs_HT_Updater::set_auto_update($enabled);
        }
        self::redirect(add_query_arg(array('page'=>self::UPDATES_PAGE, 'saved'=>'1'), admin_url('admin.php')));
    }

    public static function updates_page() {
        if (!current_user_can('update_plugins')) return;
        $latest = class_exists('Parcs_HT_Updater') ? Parcs_HT_Updater::latest_version() : '';
        $error = is_wp_error($latest) ? $latest->get_error_message() : '';
        $latest_version = is_wp_error($latest) ? '' : (string)$latest;
        $available = $latest_version !== '' && version_compare($latest_version, PARCS_HT_VERSION, '>');
        $last_check = class_exists('Parcs_HT_Updater') ? Parcs_HT_Updater::last_check() : 0;
        $auto = class_exists('Parcs_HT_Updater') && Parcs_HT_Updater::auto_update_enabled();
        $check_url = wp_nonce_url(admin_url('admin-post.php?action=parcs_ht_check_updates_1172'), 'parcs_ht_check_updates_1172');
        $plugin = plugin_basename(PARCS_HT_FILE);
        $update_url = wp_nonce_url(self_admin_url('update.php?action=upgrade-plugin&plugin=' . rawurlencode($plugin)), 'upgrade-plugin_' . $plugin);
        ?>
        <div class="wrap htp-1172-updates">
            <h1>Mises à jour</h1>
            <p class="description">Le dépôt GitHub est public : aucune clé n’est nécessaire pour détecter ou installer une release publique.</p>
            <?php if (isset($_GET['checked'])) : /* phpcs:ignore WordPress.Security.NonceVerification.Recommended -- message visuel uniquement. */ ?><div class="notice notice-success is-dismissible"><p>La vérification a été relancée.</p></div><?php endif; ?>
            <?php if (isset($_GET['saved'])) : /* phpcs:ignore WordPress.Security.NonceVerification.Recommended -- message visuel uniquement. */ ?><div class="notice notice-success is-dismissible"><p>Le réglage de mise à jour automatique a été enregistré.</p></div><?php endif; ?>
            <div class="htp-1172-update-card">
                <div><span>Version installée</span><strong><?php echo esc_html(PARCS_HT_VERSION); ?></strong></div>
                <div><span>Dernière version disponible</span><strong><?php echo esc_html($latest_version !== '' ? $latest_version : '—'); ?></strong></div>
                <div><span>État</span><strong><?php echo esc_html($error !== '' ? 'Vérification impossible' : ($available ? 'Mise à jour disponible' : ($latest_version !== '' ? 'À jour' : 'Non vérifié'))); ?></strong></div>
                <div><span>Dernière vérification</span><strong><?php echo $last_check ? esc_html(wp_date('d/m/Y H:i', $last_check)) : '—'; ?></strong></div>
            </div>
            <?php if ($error !== '') : ?><div class="notice notice-warning inline"><p><?php echo esc_html($error); ?></p></div><?php endif; ?>
            <p class="htp-1172-actions"><a class="button" href="<?php echo esc_url($check_url); ?>">Vérifier maintenant</a><?php if ($available) : ?> <a class="button button-primary" href="<?php echo esc_url($update_url); ?>">Mettre à jour vers <?php echo esc_html($latest_version); ?></a><?php endif; ?></p>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="htp-1172-auto-form">
                <input type="hidden" name="action" value="parcs_ht_save_update_preferences_1172">
                <?php wp_nonce_field('parcs_ht_save_update_preferences_1172'); ?>
                <input type="hidden" name="auto_update" value="0">
                <label><input type="checkbox" name="auto_update" value="1" <?php checked($auto); ?>> Installer automatiquement les nouvelles versions de cette extension</label>
                <?php submit_button('Enregistrer', 'secondary', 'submit', false); ?>
            </form>
            <?php if (class_exists('Parcs_HT_Updater') && Parcs_HT_Updater::has_token()) : ?><p class="description">Une clé GitHub optionnelle est actuellement configurée. Elle n’est pas nécessaire tant que le dépôt reste public.</p><?php endif; ?>
        </div>
        <?php
    }

    public static function admin_head() {
        $page = isset($_GET['page']) ? sanitize_key(wp_unslash($_GET['page'])) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- sélection d’écran en lecture seule.
        $allowed = array(self::GROUPS_PAGE, self::COMMUNICATION_PAGE, self::PERIODS_PAGE, self::UPDATES_PAGE);
        if (class_exists('Parcs_HT_Admin_General')) $allowed[] = Parcs_HT_Admin_General::PAGE;
        if (!in_array($page, $allowed, true)) return;
        ?>
        <style>
        .htp-1172-landing,.htp-1172-updates{max-width:1180px}.htp-1172-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(250px,1fr));gap:14px;margin-top:22px}.htp-1172-card{display:flex;min-height:130px;flex-direction:column;gap:8px;padding:18px;border:1px solid #dcdcde;border-radius:10px;background:#fff;color:#1d2327;text-decoration:none}.htp-1172-card:hover,.htp-1172-card:focus{border-color:#2271b1;box-shadow:0 0 0 1px #2271b1}.htp-1172-card strong{font-size:16px}.htp-1172-card span{color:#50575e;line-height:1.45}.htp-1172-card em{margin-top:auto;color:#2271b1;font-style:normal;font-weight:600}.htp-1172-update-card{display:grid;grid-template-columns:repeat(4,minmax(150px,1fr));gap:12px;margin:20px 0}.htp-1172-update-card>div{display:flex;flex-direction:column;gap:6px;padding:16px;border:1px solid #dcdcde;border-radius:8px;background:#fff}.htp-1172-update-card span{color:#646970}.htp-1172-update-card strong{font-size:17px}.htp-1172-actions{margin:18px 0}.htp-1172-auto-form{display:flex;gap:14px;align-items:center;flex-wrap:wrap;padding:16px;border:1px solid #dcdcde;border-radius:8px;background:#fff}.htp-global-field>span:first-child{display:block!important;visibility:visible!important;opacity:1!important;color:#1d2327!important;font-weight:600!important;line-height:1.4!important;margin-bottom:2px}.htp-global-field .wp-picker-container{display:block!important}@media(max-width:782px){.htp-1172-update-card{grid-template-columns:1fr}}
        </style>
        <?php
    }
}
