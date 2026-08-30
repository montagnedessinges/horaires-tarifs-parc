<?php

if (!defined('ABSPATH')) { exit; }

final class Parcs_HT_Admin_Groups {
    public static function init() {
        add_action('admin_menu', array(__CLASS__, 'cleanup_submenus'), 99);
        add_action('admin_enqueue_scripts', array(__CLASS__, 'assets'), 120);
        add_action('wp_ajax_parcs_ht_save_quote_season_rates', array(__CLASS__, 'save_quote_rates'));
    }

    public static function cleanup_submenus() {
        if (class_exists('Parcs_HT_Admin') && class_exists('Parcs_HT_Group_Quotes')) {
            remove_submenu_page(Parcs_HT_Admin::PAGE, Parcs_HT_Group_Quotes::PAGE);
        }
    }

    public static function assets($hook) {
        if ($hook !== 'toplevel_page_parcs-horaires-tarifs' || !current_user_can('manage_options')) return;
        $year = isset($_GET['season']) ? sanitize_text_field(wp_unslash($_GET['season'])) : '';
        $settings = Parcs_HT_Defaults::settings($year);
        $year = (string)($settings['active_season_year'] ?? $year);
        $quotes = class_exists('Parcs_HT_Group_Quotes') ? Parcs_HT_Group_Quotes::settings() : array();
        $row = isset($quotes['seasons'][$year]) && is_array($quotes['seasons'][$year]) ? $quotes['seasons'][$year] : array();
        wp_enqueue_script('parcs-ht-admin-groups', PARCS_HT_URL . 'assets/admin-groups.js', array('jquery','parcs-ht-admin'), PARCS_HT_VERSION, true);
        wp_add_inline_script('parcs-ht-admin-groups', 'window.ParcsHTAdminGroups=' . wp_json_encode(array(
            'year' => $year,
            'nonce' => wp_create_nonce('parcs_ht_quote_season_rates'),
            'rates' => array(
                'published' => (string)($row['published'] ?? '0'),
                'child' => (string)($row['child'] ?? ''),
                'adult' => (string)($row['adult'] ?? ''),
                'disability' => (string)($row['disability'] ?? ''),
                'companion' => (string)($row['companion'] ?? ''),
                'free_adult_children' => (string)($row['free_adult_children'] ?? '10'),
            ),
        )) . ';', 'before');
    }

    public static function save_quote_rates() {
        if (!current_user_can('manage_options')) wp_send_json_error(array('message'=>'Accès refusé.'), 403);
        check_ajax_referer('parcs_ht_quote_season_rates', 'nonce');
        $year = isset($_POST['year']) ? sanitize_text_field(wp_unslash($_POST['year'])) : '';
        if (!preg_match('/^20\d{2}$/', $year)) wp_send_json_error(array('message'=>'Année invalide.'), 400);
        $all = Parcs_HT_Defaults::all_settings();
        if (empty($all['seasons'][$year]) || !is_array($all['seasons'][$year])) wp_send_json_error(array('message'=>'Cette saison n’existe pas.'), 400);
        $settings = Parcs_HT_Group_Quotes::settings();
        $row = array('published'=>isset($_POST['published']) && (string)$_POST['published']==='1' ? '1' : '0');
        foreach (array('child','adult','disability','companion') as $key) {
            $value = isset($_POST[$key]) ? str_replace(',', '.', sanitize_text_field(wp_unslash($_POST[$key]))) : '';
            if ($value === '' || !is_numeric($value) || (float)$value < 0) wp_send_json_error(array('message'=>'Tous les tarifs doivent être renseignés avec une valeur positive ou nulle.'), 400);
            $row[$key] = (string)(float)$value;
        }
        $ratio = isset($_POST['free_adult_children']) ? absint($_POST['free_adult_children']) : 0;
        if ($ratio < 1) wp_send_json_error(array('message'=>'La règle de gratuité doit être au minimum de 1 enfant.'), 400);
        $row['free_adult_children'] = (string)$ratio;
        if (!isset($settings['seasons']) || !is_array($settings['seasons'])) $settings['seasons'] = array();
        $settings['seasons'][$year] = $row;
        update_option(Parcs_HT_Group_Quotes::OPTION, $settings, false);
        wp_send_json_success(array('message'=>'Tarifs du devis ' . $year . ' enregistrés.'));
    }
}
