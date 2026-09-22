<?php

if (!defined('ABSPATH')) { exit; }

/**
 * Sauvegardes d'administration 1.17.11.
 *
 * La 1.17.10 reconstruisait globalement les formulaires en JSON avant de
 * réécrire $_POST. La 1.17.11 revient au POST WordPress natif et garde une
 * protection simple contre les requêtes tronquées : un marqueur est ajouté en
 * toute fin du formulaire au moment du submit. S'il n'arrive pas jusqu'au
 * serveur, aucune écriture n'est autorisée.
 *
 * Les écrans encore basés sur Parcs_HT_Admin::save() conservent ce sanitizer
 * canonique, mais leur redirection de retour est désormais directe vers leur
 * écran métier au lieu de repasser par un ancien onglet intermédiaire.
 */
final class Parcs_HT_Admin_Save_11711 {
    const COMPLETE_FIELD = 'parcs_ht_11711_complete';
    const WORKSPACE_FIELD = 'parcs_ht_11711_workspace';

    public static function init() {
        add_action('admin_enqueue_scripts', array(__CLASS__, 'assets'), 99);
        add_action('admin_post_parcs_ht_save', array(__CLASS__, 'guard_native_post'), 1);
        add_filter('wp_redirect', array(__CLASS__, 'rewrite_legacy_workspace_redirect'), 20, 2);
    }

    private static function valid_year($year) {
        $year = trim((string)$year);
        return preg_match('/^20\d{2}$/', $year) ? $year : '';
    }

    /**
     * Lit uniquement les métadonnées scalaires nécessaires pour identifier le
     * flux admin-post et sa destination. Le nonce canonique est vérifié dans
     * guard_native_post() avant toute lecture des réglages ou toute écriture ;
     * le filtre de redirection s'exécute ensuite dans ce même flux validé.
     */
    private static function post_value($key) {
        // phpcs:disable WordPress.Security.NonceVerification.Missing -- métadonnées de routage uniquement ; aucune donnée métier n'est lue ni écrite ici.
        if (!isset($_POST[$key]) || is_array($_POST[$key])) return '';
        $value = sanitize_text_field(wp_unslash($_POST[$key]));
        // phpcs:enable WordPress.Security.NonceVerification.Missing
        return $value;
    }

    private static function request_is_canonical_save() {
        return self::post_value('action') === 'parcs_ht_save';
    }

    private static function workspace() {
        $workspace = sanitize_key(self::post_value(self::WORKSPACE_FIELD));
        return in_array($workspace, array('periods','retail','groups','popup'), true) ? $workspace : '';
    }

    public static function assets() {
        if (!current_user_can('manage_options')) return;
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- sélection d'écran en lecture seule.
        $page = isset($_GET['page']) ? sanitize_key(wp_unslash($_GET['page'])) : '';
        if ($page !== 'parcs-horaires-tarifs' && strpos($page, 'parcs-ht-') !== 0) return;
        wp_enqueue_script(
            'parcs-ht-admin-save-11711',
            PARCS_HT_URL . 'assets/admin-save-11711.js',
            array(),
            PARCS_HT_VERSION,
            true
        );
    }

    /**
     * Bloque un POST tronqué avant que le sanitizer historique puisse prendre
     * l'absence de champs pour une suppression volontaire.
     */
    public static function guard_native_post() {
        if (!self::request_is_canonical_save()) return;
        if (!current_user_can('manage_options')) wp_die('Accès refusé.');

        check_admin_referer('parcs_ht_save');

        if (self::post_value(self::COMPLETE_FIELD) !== '1') {
            wp_die(
                'Le formulaire n’a pas été reçu complètement par WordPress/PHP. Aucune donnée n’a été modifiée. Rechargez la page puis réessayez.',
                'Enregistrement interrompu',
                array('response'=>400, 'back_link'=>true)
            );
        }

        if (!isset($_POST['settings']) || !is_array($_POST['settings'])) {
            wp_die(
                'Les réglages attendus sont absents de la requête. Aucune donnée n’a été modifiée.',
                'Enregistrement interrompu',
                array('response'=>400, 'back_link'=>true)
            );
        }

        $year = self::valid_year(self::post_value('season_year'));
        if ($year === '') return;

        $all = get_option(Parcs_HT_Defaults::OPTION, array());
        if (!is_array($all) || empty($all['seasons'][$year]) || !is_array($all['seasons'][$year])) {
            wp_die('Année invalide ou introuvable. Aucune donnée n’a été modifiée.');
        }
    }

    private static function target_page($workspace) {
        if ($workspace === 'periods' && class_exists('Parcs_HT_Admin_Periods')) return Parcs_HT_Admin_Periods::PAGE;
        if ($workspace === 'retail' && class_exists('Parcs_HT_Admin_Retail_Tariffs')) return Parcs_HT_Admin_Retail_Tariffs::PAGE;
        if ($workspace === 'groups' && class_exists('Parcs_HT_Admin_Group_Tariffs')) return Parcs_HT_Admin_Group_Tariffs::PAGE;
        if ($workspace === 'popup' && class_exists('Parcs_HT_Admin_Communication_1179')) return Parcs_HT_Admin_Communication_1179::POPUP_PAGE;
        return '';
    }

    /**
     * Parcs_HT_Admin::save() reste l'unique sanitizer pour les écrans qui
     * l'utilisent encore. On ne change que sa destination de retour afin
     * d'éviter le détour ancien écran -> routeur -> nouvel écran.
     */
    public static function rewrite_legacy_workspace_redirect($location, $status) {
        unset($status);
        if (!self::request_is_canonical_save()) return $location;
        if (self::post_value(self::COMPLETE_FIELD) !== '1') return $location;

        $workspace = self::workspace();
        $page = self::target_page($workspace);
        if ($page === '') return $location;

        $args = array('page'=>$page, 'updated'=>'1');
        $year = self::valid_year(self::post_value('season_year'));
        if ($workspace !== 'popup' && $year !== '') $args['season'] = $year;

        $query = wp_parse_url((string)$location, PHP_URL_QUERY);
        if (is_string($query) && $query !== '') {
            $old_args = array();
            parse_str($query, $old_args);
            if (!empty($old_args['preserved'])) $args['preserved'] = '1';
        }

        return add_query_arg($args, admin_url('admin.php'));
    }
}
