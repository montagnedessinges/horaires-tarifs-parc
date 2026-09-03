<?php

if (!defined('ABSPATH')) { exit; }

final class Parcs_HT_Admin_Groups {
    public static function init() {
        add_action('admin_menu', array(__CLASS__, 'cleanup_submenus'), 99);
        add_action('admin_head', array(__CLASS__, 'hide_guides_submenu'));
        add_action('admin_enqueue_scripts', array(__CLASS__, 'assets'), 120);
        add_action('wp_ajax_parcs_ht_save_quote_season_rates', array(__CLASS__, 'save_quote_rates'));
        add_action('wp_ajax_parcs_ht_save_quote_tariff_binding', array(__CLASS__, 'save_quote_tariff_binding'));
        add_action('wp_ajax_parcs_ht_save_quote_language_forms', array(__CLASS__, 'save_quote_language_forms'));
        add_action('wp_ajax_parcs_ht_save_quote_gate_settings', array(__CLASS__, 'save_quote_gate_settings'));
    }

    public static function cleanup_submenus() {
        if (!class_exists('Parcs_HT_Admin')) return;
        if (class_exists('Parcs_HT_Group_Quotes')) remove_submenu_page(Parcs_HT_Admin::PAGE, Parcs_HT_Group_Quotes::PAGE);
        if (class_exists('Parcs_HT_Quote_Languages')) remove_submenu_page(Parcs_HT_Admin::PAGE, Parcs_HT_Quote_Languages::PAGE);
        if (class_exists('Parcs_HT_Quote_Gate')) remove_submenu_page(Parcs_HT_Admin::PAGE, Parcs_HT_Quote_Gate::PAGE);
        // La page Guides reste enregistrée afin que WordPress conserve son hook/capability.
        // Elle est seulement masquée visuellement du sous-menu via hide_guides_submenu().
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
        $year = isset($_GET['season']) ? sanitize_text_field(wp_unslash($_GET['season'])) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Sélection d’aperçu en lecture seule.
        $settings = Parcs_HT_Defaults::settings($year);
        $year = (string)($settings['active_season_year'] ?? $year);
        $quotes = class_exists('Parcs_HT_Group_Quotes') ? Parcs_HT_Group_Quotes::settings(false) : array();
        $forms = class_exists('Parcs_HT_Quote_Languages') ? Parcs_HT_Quote_Languages::settings() : array('fr'=>'','en'=>'','de'=>'');
        $gate = class_exists('Parcs_HT_Quote_Gate') ? Parcs_HT_Quote_Gate::settings() : array();
        $binding = isset($quotes['tariff_binding']) && is_array($quotes['tariff_binding']) ? $quotes['tariff_binding'] : array();
        $tariffs = isset($settings['tariffs']) && is_array($settings['tariffs']) ? $settings['tariffs'] : array();
        $columns = array();
        foreach ((array)($tariffs['columns']['groups'] ?? array()) as $column) {
            if (!is_array($column)) continue;
            $id = sanitize_key($column['id'] ?? '');
            if ($id === '') continue;
            $columns[] = array('id'=>$id, 'label'=>self::translation($column['label'] ?? array()) ?: $id);
        }
        $rows = array();
        foreach ((array)($tariffs['groups'] ?? array()) as $index => $row) {
            if (!is_array($row)) continue;
            $rows[] = array('index'=>(string)$index, 'label'=>self::translation($row['label'] ?? array()) ?: ('Ligne ' . ((int)$index + 1)));
        }
        wp_enqueue_script('parcs-ht-admin-groups', PARCS_HT_URL . 'assets/admin-groups.js', array('jquery','parcs-ht-admin'), PARCS_HT_VERSION, true);
        wp_add_inline_script('parcs-ht-admin-groups', 'window.ParcsHTAdminGroups=' . wp_json_encode(array(
            'year' => $year,
            'nonce' => wp_create_nonce('parcs_ht_quote_season_rates'),
            'binding_nonce' => wp_create_nonce('parcs_ht_quote_tariff_binding'),
            'forms_nonce' => wp_create_nonce('parcs_ht_quote_language_forms'),
            'gate_nonce' => wp_create_nonce('parcs_ht_quote_gate_settings'),
            'forms' => $forms,
            'gate' => $gate,
            'binding' => $binding,
            'tariff_columns' => $columns,
            'tariff_rows' => $rows,
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
        $settings = Parcs_HT_Group_Quotes::settings(false);
        $column = isset($_POST['column']) ? sanitize_key(wp_unslash($_POST['column'])) : 'price';
        if ($column === '') $column = 'price';
        $binding = array('column'=>$column);
        $tariffs = Parcs_HT_Defaults::settings($year);
        $rows = (array)($tariffs['tariffs']['groups'] ?? array());
        foreach (array('child','adult','disability','companion') as $role) {
            $key = $role . '_row';
            $index = isset($_POST[$key]) ? absint($_POST[$key]) : 0;
            if (!isset($rows[$index]) || !is_array($rows[$index])) wp_send_json_error(array('message'=>'Une ligne tarifaire sélectionnée n’existe plus.'), 400);
            $binding[$key] = (string)$index;
            $binding[$role . '_label'] = self::translation($rows[$index]['label'] ?? array());
        }
        $binding['free_adult_children'] = (string)max(1, isset($_POST['free_adult_children']) ? absint($_POST['free_adult_children']) : 10);
        $settings['tariff_binding'] = $binding;
        update_option(Parcs_HT_Group_Quotes::OPTION, $settings, false);
        wp_send_json_success(array('message'=>'Liaison avec le tableau Tarifs groupes enregistrée.'));
    }

    public static function save_quote_rates() {
        if (!current_user_can('manage_options')) wp_send_json_error(array('message'=>'Accès refusé.'), 403);
        check_ajax_referer('parcs_ht_quote_season_rates', 'nonce');
        wp_send_json_error(array('message'=>'Cette ancienne grille n’est plus utilisée. Modifiez désormais Tarifs > Groupes.'), 410);
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
