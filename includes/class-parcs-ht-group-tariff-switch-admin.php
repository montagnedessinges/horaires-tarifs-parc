<?php

if (!defined('ABSPATH')) { exit; }

/** Interface et purge de cache pour la bascule commerciale des tarifs groupes. */
final class Parcs_HT_Group_Tariff_Switch_Admin {
    const CRON_HOOK = 'parcs_ht_group_tariff_switch_cache_purge';

    public static function init() {
        add_action('admin_enqueue_scripts', array(__CLASS__, 'assets'), 130);
        add_action('wp_ajax_parcs_ht_save_group_tariff_switch', array(__CLASS__, 'save'));
        add_action(self::CRON_HOOK, array(__CLASS__, 'purge_cache'), 10, 1);
        add_action('admin_notices', array(__CLASS__, 'notices'));
    }

    private static function selected_year() {
        $requested = isset($_GET['season']) ? sanitize_text_field(wp_unslash($_GET['season'])) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- sélection de lecture seule.
        $settings = Parcs_HT_Defaults::settings($requested);
        return (string)($settings['active_season_year'] ?? $requested);
    }

    public static function assets($hook) {
        if ($hook !== 'toplevel_page_parcs-horaires-tarifs' || !current_user_can('manage_options')) return;
        $year = self::selected_year();
        if (!preg_match('/^20\d{2}$/', $year) || !class_exists('Parcs_HT_Group_Tariff_Settings')) return;
        $display = Parcs_HT_Group_Tariff_Settings::settings($year);
        $readiness = Parcs_HT_Group_Tariff_Settings::readiness($year);
        wp_enqueue_script(
            'parcs-ht-admin-group-tariff-switch',
            PARCS_HT_URL . 'assets/admin-group-tariff-switch.js',
            array('jquery','parcs-ht-admin-groups'),
            PARCS_HT_VERSION,
            true
        );
        wp_add_inline_script(
            'parcs-ht-admin-group-tariff-switch',
            'window.ParcsHTGroupTariffSwitch=' . wp_json_encode(array(
                'year'=>$year,
                'nonce'=>wp_create_nonce('parcs_ht_group_tariff_switch'),
                'displayFrom'=>(string)($display['display_from'] ?? ''),
                'readiness'=>$readiness,
            )) . ';',
            'before'
        );
    }

    public static function save() {
        if (!current_user_can('manage_options')) wp_send_json_error(array('message'=>'Accès refusé.'), 403);
        check_ajax_referer('parcs_ht_group_tariff_switch', 'nonce');
        $year = isset($_POST['year']) ? sanitize_text_field(wp_unslash($_POST['year'])) : '';
        $date = isset($_POST['display_from']) ? sanitize_text_field(wp_unslash($_POST['display_from'])) : '';
        if (!preg_match('/^20\d{2}$/', $year)) wp_send_json_error(array('message'=>'Année invalide.'), 400);
        if ($date !== '' && !preg_match('/^20\d{2}-\d{2}-\d{2}$/', $date)) wp_send_json_error(array('message'=>'Date de bascule invalide.'), 400);
        $all = Parcs_HT_Defaults::all_settings();
        if (empty($all['seasons'][$year]) || !is_array($all['seasons'][$year])) wp_send_json_error(array('message'=>'Cette saison n’existe pas.'), 400);
        if (!Parcs_HT_Group_Tariff_Settings::save_display_from($year, $date)) wp_send_json_error(array('message'=>'WordPress n’a pas confirmé la date de bascule.'), 500);

        self::schedule($year, $date);
        if (class_exists('Parcs_HT_Save_Integrity')) Parcs_HT_Save_Integrity::store_daily_snapshot($year, 'Bascule commerciale des tarifs groupes');
        self::purge_cache($year);
        $readiness = Parcs_HT_Group_Tariff_Settings::readiness($year);
        wp_send_json_success(array(
            'message'=>$date === '' ? 'Date de bascule supprimée. Le comportement annuel standard est conservé.' : 'Bascule des tarifs groupes enregistrée pour le ' . date_i18n('d/m/Y', strtotime($date)) . '.',
            'displayFrom'=>(string)(Parcs_HT_Group_Tariff_Settings::settings($year)['display_from'] ?? ''),
            'readiness'=>$readiness,
        ));
    }

    private static function schedule($year, $date) {
        wp_clear_scheduled_hook(self::CRON_HOOK, array($year));
        if ($date === '') return;
        $all = Parcs_HT_Defaults::all_settings();
        $timezone = Parcs_HT_Schedule::timezone($all);
        try {
            $tz = new DateTimeZone($timezone);
            $switch = new DateTimeImmutable($date . ' 00:01:00', $tz);
            $now = new DateTimeImmutable('now', $tz);
        } catch (Exception $e) {
            return;
        }
        if ($switch <= $now) return;
        wp_schedule_single_event($switch->getTimestamp(), self::CRON_HOOK, array($year));
    }

    public static function purge_cache($year = '') {
        unset($year);
        do_action('litespeed_purge_all');
    }

    public static function notices() {
        if (!current_user_can('manage_options') || !class_exists('Parcs_HT_Group_Tariff_Settings')) return;
        $page = isset($_GET['page']) ? sanitize_key(wp_unslash($_GET['page'])) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- affichage d’un diagnostic uniquement.
        if ($page !== 'parcs-horaires-tarifs') return;
        $store = Parcs_HT_Group_Tariff_Settings::store();
        foreach ((array)($store['seasons'] ?? array()) as $year => $row) {
            if (!is_array($row) || empty($row['display_from'])) continue;
            $readiness = Parcs_HT_Group_Tariff_Settings::readiness((string)$year);
            foreach ((array)($readiness['warnings'] ?? array()) as $warning) {
                echo '<div class="notice notice-warning"><p><strong>Tarifs groupes ' . esc_html((string)$year) . ' :</strong> ' . esc_html((string)$warning) . '</p></div>';
            }
        }
    }
}
