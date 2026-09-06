<?php

if (!defined('ABSPATH')) { exit; }

final class Parcs_HT_Admin_Groups {
    public static function init() {
        add_action('admin_menu', array(__CLASS__, 'cleanup_submenus'), 99);
        add_action('admin_head', array(__CLASS__, 'hide_guides_submenu'));
        add_action('admin_enqueue_scripts', array(__CLASS__, 'assets'), 120);
        add_action('wp_ajax_parcs_ht_save_quote_season_rates', array(__CLASS__, 'save_quote_rates'));
        add_action('wp_ajax_parcs_ht_save_quote_tariff_binding', array(__CLASS__, 'save_quote_tariff_binding'));
        add_action('wp_ajax_parcs_ht_save_group_tariff_settings', array(__CLASS__, 'save_group_tariff_settings'));
        add_action('wp_ajax_parcs_ht_save_quote_language_forms', array(__CLASS__, 'save_quote_language_forms'));
        add_action('wp_ajax_parcs_ht_save_quote_gate_settings', array(__CLASS__, 'save_quote_gate_settings'));
    }

    public static function cleanup_submenus() {
        if (!class_exists('Parcs_HT_Admin')) return;
        if (class_exists('Parcs_HT_Group_Quotes')) remove_submenu_page(Parcs_HT_Admin::PAGE, Parcs_HT_Group_Quotes::PAGE);
        if (class_exists('Parcs_HT_Quote_Languages')) remove_submenu_page(Parcs_HT_Admin::PAGE, Parcs_HT_Quote_Languages::PAGE);
        if (class_exists('Parcs_HT_Quote_Gate')) remove_submenu_page(Parcs_HT_Admin::PAGE, Parcs_HT_Quote_Gate::PAGE);
    }

    public static function hide_guides_submenu() {
        if (!current_user_can('manage_options') || !class_exists('Parcs_HT_Pedagogical_Guides')) return;
        $href = 'admin.php?page=' . Parcs_HT_Pedagogical_Guides::PAGE;
        ?>
        <script>
        document.addEventListener('DOMContentLoaded', function () {
            var links = document.querySelectorAll('#toplevel_page_parcs-horaires-tarifs .wp-submenu a[href="<?php echo esc_js($href); ?>"]');
            links.forEach(function (link) {
                var item = link.closest('li');
                if (item) item.style.display = 'none';
            });
        });
        </script>
        <?php
    }

    private static function translation($value) {
        if (!is_array($value)) return '';
        foreach (array('fr','en','de') as $lang) {
            $text = trim((string)($value[$lang] ?? ''));
            if ($text !== '') return $text;
        }
        return '';
    }

    public static function assets($hook) {
        if ($hook !== 'toplevel_page_parcs-horaires-tarifs' || !current_user_can('manage_options')) return;
        $year = isset($_GET['season']) ? sanitize_text_field(wp_unslash($_GET['season'])) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Sélection en lecture seule.
        $settings = Parcs_HT_Defaults::settings($year);
        $year = (string)($settings['active_season_year'] ?? $year);
        $quotes = class_exists('Parcs_HT_Group_Quotes') ? Parcs_HT_Group_Quotes::settings(false) : array();
        $forms = class_exists('Parcs_HT_Quote_Languages') ? Parcs_HT_Quote_Languages::settings() : array('fr'=>'','en'=>'','de'=>'');
        $gate = class_exists('Parcs_HT_Quote_Gate') ? Parcs_HT_Quote_Gate::settings() : array();
        $binding = class_exists('Parcs_HT_Group_Quotes') ? Parcs_HT_Group_Quotes::binding_for_year($year, $quotes) : null;
        $identities = class_exists('Parcs_HT_Tariff_Identities') ? Parcs_HT_Tariff_Identities::identity_snapshot($settings) : array();
        $group_identity = isset($identities['groups']) && is_array($identities['groups']) ? $identities['groups'] : array('rows'=>array(),'columns'=>array());
        $group_settings = class_exists('Parcs_HT_Group_Tariff_Settings') ? Parcs_HT_Group_Tariff_Settings::settings($year) : array();

        wp_enqueue_script('parcs-ht-admin-groups', PARCS_HT_URL . 'assets/admin-groups.js', array('jquery','parcs-ht-admin'), PARCS_HT_VERSION, true);
        wp_add_inline_script('parcs-ht-admin-groups', 'window.ParcsHTAdminGroups=' . wp_json_encode(array(
            'year' => $year,
            'nonce' => wp_create_nonce('parcs_ht_quote_season_rates'),
            'binding_nonce' => wp_create_nonce('parcs_ht_quote_tariff_binding'),
            'group_tariff_nonce' => wp_create_nonce('parcs_ht_group_tariff_settings'),
            'forms_nonce' => wp_create_nonce('parcs_ht_quote_language_forms'),
            'gate_nonce' => wp_create_nonce('parcs_ht_quote_gate_settings'),
            'forms' => $forms,
            'gate' => $gate,
            'binding' => is_array($binding) ? $binding : array(),
            'tariff_columns' => $group_identity['columns'],
            'tariff_rows' => $group_identity['rows'],
            'tariff_identities' => $identities,
            'group_tariff' => $group_settings,
            'guides_url' => class_exists('Parcs_HT_Pedagogical_Guides') ? add_query_arg(array('page'=>Parcs_HT_Pedagogical_Guides::PAGE), admin_url('admin.php')) : '',
        )) . ';', 'before');
    }

    public static function save_quote_tariff_binding() {
        if (!current_user_can('manage_options')) wp_send_json_error(array('message'=>'Accès refusé.'), 403);
        check_ajax_referer('parcs_ht_quote_tariff_binding', 'nonce');
        $year = isset($_POST['year']) ? sanitize_text_field(wp_unslash($_POST['year'])) : '';
        if (!preg_match('/^20\d{2}$/', $year)) wp_send_json_error(array('message'=>'Année invalide.'), 400);
        $all = Parcs_HT_Defaults::all_settings();
        if (empty($all['seasons'][$year]) || !is_array($all['seasons'][$year])) wp_send_json_error(array('message'=>'Cette saison n’existe pas.'), 400);
        if (!class_exists('Parcs_HT_Tariff_Identities')) wp_send_json_error(array('message'=>'Le registre des identifiants tarifaires est indisponible.'), 500);

        $tariffs = Parcs_HT_Defaults::settings($year);
        $tariffs = isset($tariffs['tariffs']) && is_array($tariffs['tariffs']) ? $tariffs['tariffs'] : array();
        $rows = (array)($tariffs['groups'] ?? array());
        $columns = (array)($tariffs['columns']['groups'] ?? array());
        $column_id = isset($_POST['column_id']) ? sanitize_key(wp_unslash($_POST['column_id'])) : '';
        if (!Parcs_HT_Tariff_Identities::column_exists($columns, $column_id)) wp_send_json_error(array('message'=>'La colonne tarifaire sélectionnée n’existe plus.'), 400);

        $binding = array('column_id'=>$column_id);
        foreach (array('child','adult','disability','companion') as $role) {
            $key = $role . '_row_id';
            $id = isset($_POST[$key]) ? sanitize_key(wp_unslash($_POST[$key])) : '';
            if (!Parcs_HT_Tariff_Identities::row_by_id($rows, $id)) wp_send_json_error(array('message'=>'Un tarif lié au devis n’existe plus. Sélectionnez un nouveau tarif.'), 400);
            $binding[$key] = $id;
        }
        $ratio = max(1, isset($_POST['free_adult_children']) ? absint($_POST['free_adult_children']) : 10);
        $threshold = max(1, isset($_POST['free_adult_round_threshold']) ? absint($_POST['free_adult_round_threshold']) : 5);
        $binding['free_adult_children'] = (string)$ratio;
        $binding['free_adult_round_threshold'] = (string)min($ratio, $threshold);

        $settings = Parcs_HT_Group_Quotes::settings(false);
        if (!isset($settings['tariff_bindings']) || !is_array($settings['tariff_bindings'])) $settings['tariff_bindings'] = array();
        $settings['tariff_bindings'][$year] = $binding;
        $settings['binding_version'] = 2;
        update_option(Parcs_HT_Group_Quotes::OPTION, $settings, false);
        wp_send_json_success(array('message'=>'Liaison du devis enregistrée pour ' . $year . '.'));
    }

    public static function save_group_tariff_settings() {
        if (!current_user_can('manage_options')) wp_send_json_error(array('message'=>'Accès refusé.'), 403);
        check_ajax_referer('parcs_ht_group_tariff_settings', 'nonce');
        $year = isset($_POST['year']) ? sanitize_text_field(wp_unslash($_POST['year'])) : '';
        if (!preg_match('/^20\d{2}$/', $year)) wp_send_json_error(array('message'=>'Année invalide.'), 400);
        $all = Parcs_HT_Defaults::all_settings();
        if (empty($all['seasons'][$year]) || !is_array($all['seasons'][$year])) wp_send_json_error(array('message'=>'Cette saison n’existe pas.'), 400);
        if (!class_exists('Parcs_HT_Group_Tariff_Settings')) wp_send_json_error(array('message'=>'Le module des tarifs groupes est indisponible.'), 500);

        $raw = array(
            'published'=>isset($_POST['published']) ? sanitize_text_field(wp_unslash($_POST['published'])) : '0',
            'show_heading'=>isset($_POST['show_heading']) ? sanitize_text_field(wp_unslash($_POST['show_heading'])) : '0',
            'show_future_notice'=>isset($_POST['show_future_notice']) ? sanitize_text_field(wp_unslash($_POST['show_future_notice'])) : '0',
            'future_year'=>isset($_POST['future_year']) ? sanitize_text_field(wp_unslash($_POST['future_year'])) : '',
            'show_quote_button'=>isset($_POST['show_quote_button']) ? sanitize_text_field(wp_unslash($_POST['show_quote_button'])) : '0',
            'title'=>array(),'intro'=>array(),'future_notice'=>array(),'button_label'=>array(),'button_url'=>array(),
        );
        foreach (array('fr','en','de') as $lang) {
            foreach (array('title','intro','future_notice','button_label','button_url') as $field) {
                $key = $field . '_' . $lang;
                $raw[$field][$lang] = isset($_POST[$key]) ? wp_unslash($_POST[$key]) : '';
            }
        }
        if (!Parcs_HT_Group_Tariff_Settings::save($year, $raw)) wp_send_json_error(array('message'=>'WordPress n’a pas confirmé l’enregistrement des réglages groupes.'), 500);
        do_action('litespeed_purge_all');
        wp_send_json_success(array('message'=>'Affichage des tarifs groupes enregistré pour ' . $year . '.'));
    }

    public static function save_quote_rates() {
        if (!current_user_can('manage_options')) wp_send_json_error(array('message'=>'Accès refusé.'), 403);
        check_ajax_referer('parcs_ht_quote_season_rates', 'nonce');
        wp_send_json_error(array('message'=>'Cette ancienne grille n’est plus utilisée. Modifiez désormais Groupes → Tarifs.'), 410);
    }

    public static function save_quote_language_forms() {
        if (!current_user_can('manage_options')) wp_send_json_error(array('message'=>'Accès refusé.'), 403);
        check_ajax_referer('parcs_ht_quote_language_forms', 'nonce');
        $clean = Parcs_HT_Quote_Languages::defaults();
        foreach ($clean as $lang => $unused) {
            $value = isset($_POST[$lang]) ? trim(sanitize_text_field(wp_unslash($_POST[$lang]))) : '';
            if ($value !== '' && !preg_match('/^\[contact-form-7(?:\s+[^\]]*)?\s*\/?\]$/i', $value)) {
                wp_send_json_error(array('message'=>'Le shortcode ' . strtoupper($lang) . ' n’est pas un shortcode Contact Form 7 valide.'), 400);
            }
            $clean[$lang] = $value;
        }
        update_option(Parcs_HT_Quote_Languages::OPTION, $clean, false);
        wp_send_json_success(array('message'=>'Formulaires FR / EN / DE enregistrés.'));
    }

    public static function save_quote_gate_settings() {
        if (!current_user_can('manage_options')) wp_send_json_error(array('message'=>'Accès refusé.'), 403);
        check_ajax_referer('parcs_ht_quote_gate_settings', 'nonce');
        $out = array(
            'enabled' => isset($_POST['enabled']) && (string)$_POST['enabled'] === '1' ? '1' : '0',
            'closed_enabled' => isset($_POST['closed_enabled']) && (string)$_POST['closed_enabled'] === '1' ? '1' : '0',
            'closed_contact' => isset($_POST['closed_contact']) ? sanitize_text_field(wp_unslash($_POST['closed_contact'])) : '',
            'unavailable_enabled' => isset($_POST['unavailable_enabled']) && (string)$_POST['unavailable_enabled'] === '1' ? '1' : '0',
            'unavailable_contact' => isset($_POST['unavailable_contact']) ? sanitize_text_field(wp_unslash($_POST['unavailable_contact'])) : '',
        );
        foreach (array('fr','en','de') as $lang) {
            $closed_key = 'closed_message_' . $lang;
            $unavailable_key = 'unavailable_message_' . $lang;
            $out[$closed_key] = isset($_POST[$closed_key]) ? sanitize_textarea_field(wp_unslash($_POST[$closed_key])) : '';
            $out[$unavailable_key] = isset($_POST[$unavailable_key]) ? sanitize_textarea_field(wp_unslash($_POST[$unavailable_key])) : '';
        }
        update_option(Parcs_HT_Quote_Gate::OPTION, $out, false);
        wp_send_json_success(array('message'=>'Réglages d’accès au devis enregistrés.'));
    }
}
