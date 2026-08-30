<?php

if (!defined('ABSPATH')) {
    exit;
}

final class Parcs_HT_Group_Quotes {
    const OPTION = 'parcs_ht_group_quotes';
    const PAGE = 'parcs-ht-group-quotes';

    public static function init() {
        add_action('admin_menu', array(__CLASS__, 'menu'));
        add_action('admin_post_parcs_ht_save_group_quotes', array(__CLASS__, 'save'));
        add_action('wp_enqueue_scripts', array(__CLASS__, 'assets'), 30);
    }

    public static function defaults() {
        return array(
            'enabled' => '1',
            'form_id' => '',
            'visit_field' => 'visite',
            'group_field' => 'groupedevis',
            'school_value' => 'Groupe',
            'disability_value' => 'Groupe en situation de handicap',
            'seasons' => array(
                '2026' => array(
                    'published' => '1',
                    'child' => '6',
                    'adult' => '8.50',
                    'disability' => '6',
                    'companion' => '6',
                    'free_adult_children' => '10',
                ),
            ),
        );
    }

    public static function settings() {
        $saved = get_option(self::OPTION, array());
        if (!is_array($saved)) $saved = array();
        $settings = array_replace_recursive(self::defaults(), $saved);
        $settings['enabled'] = '1';
        return $settings;
    }

    public static function menu() {
        add_submenu_page(
            Parcs_HT_Admin::PAGE,
            'Tarifs des devis groupes',
            'Tarifs devis groupes',
            'manage_options',
            self::PAGE,
            array(__CLASS__, 'page')
        );
    }

    public static function page() {
        if (!current_user_can('manage_options')) return;
        $settings = self::settings();
        $seasons = (array)($settings['seasons'] ?? array());
        $years = array_keys($seasons);
        $current = (string)wp_date('Y');
        if (!in_array($current, $years, true)) $years[] = $current;
        $next = (string)(((int)$current) + 1);
        if (!in_array($next, $years, true)) $years[] = $next;
        sort($years, SORT_STRING);
        ?>
        <div class="wrap">
            <h1>Tarifs des devis groupes</h1>
            <p>Le moteur de calcul est actif automatiquement lorsqu’un formulaire Contact Form 7 contient les champs « Date de visite » et « Type de groupe » configurés ci-dessous. Aucun bouton d’activation n’est nécessaire.</p>
            <?php if (isset($_GET['updated'])) : ?><div class="notice notice-success is-dismissible"><p>Les réglages des devis groupes ont été enregistrés.</p></div><?php endif; ?>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <input type="hidden" name="action" value="parcs_ht_save_group_quotes">
                <?php wp_nonce_field('parcs_ht_save_group_quotes'); ?>
                <table class="form-table" role="presentation">
                    <tr><th scope="row">Détection du formulaire</th><td><p class="description">La détection est automatique : le moteur ne dépend plus de l’ID technique Contact Form 7, qui peut changer lors d’une duplication ou d’une migration.</p></td></tr>
                    <tr><th scope="row">Champs techniques</th><td>
                        <label>Date de visite <input name="visit_field" value="<?php echo esc_attr((string)$settings['visit_field']); ?>"></label><br>
                        <label>Type de groupe <input name="group_field" value="<?php echo esc_attr((string)$settings['group_field']); ?>"></label>
                    </td></tr>
                    <tr><th scope="row">Valeurs du type de groupe</th><td>
                        <label>Groupe scolaire <input class="regular-text" name="school_value" value="<?php echo esc_attr((string)$settings['school_value']); ?>"></label><br>
                        <label>Situation de handicap <input class="regular-text" name="disability_value" value="<?php echo esc_attr((string)$settings['disability_value']); ?>"></label>
                    </td></tr>
                </table>
                <h2>Tarifs par année de visite</h2>
                <p>Une année non publiée n’est jamais remplacée silencieusement par les tarifs d’une autre année.</p>
                <table class="widefat striped" style="max-width:1100px">
                    <thead><tr><th>Année</th><th>Disponible</th><th>Enfant</th><th>Adulte</th><th>Handicap</th><th>Accompagnateur</th><th>1 adulte gratuit / enfants</th></tr></thead>
                    <tbody>
                    <?php foreach ($years as $year) : $row = isset($seasons[$year]) && is_array($seasons[$year]) ? $seasons[$year] : array(); ?>
                        <tr>
                            <td><strong><?php echo esc_html($year); ?></strong><input type="hidden" name="seasons[<?php echo esc_attr($year); ?>][year]" value="<?php echo esc_attr($year); ?>"></td>
                            <td><input type="checkbox" name="seasons[<?php echo esc_attr($year); ?>][published]" value="1" <?php checked((string)($row['published'] ?? '0'), '1'); ?>></td>
                            <td><input type="number" min="0" step="0.01" name="seasons[<?php echo esc_attr($year); ?>][child]" value="<?php echo esc_attr((string)($row['child'] ?? '')); ?>" style="width:90px"> €</td>
                            <td><input type="number" min="0" step="0.01" name="seasons[<?php echo esc_attr($year); ?>][adult]" value="<?php echo esc_attr((string)($row['adult'] ?? '')); ?>" style="width:90px"> €</td>
                            <td><input type="number" min="0" step="0.01" name="seasons[<?php echo esc_attr($year); ?>][disability]" value="<?php echo esc_attr((string)($row['disability'] ?? '')); ?>" style="width:90px"> €</td>
                            <td><input type="number" min="0" step="0.01" name="seasons[<?php echo esc_attr($year); ?>][companion]" value="<?php echo esc_attr((string)($row['companion'] ?? '')); ?>" style="width:90px"> €</td>
                            <td><input type="number" min="1" step="1" name="seasons[<?php echo esc_attr($year); ?>][free_adult_children]" value="<?php echo esc_attr((string)($row['free_adult_children'] ?? '10')); ?>" style="width:80px"></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                <?php submit_button('Enregistrer les tarifs des devis groupes'); ?>
            </form>
        </div>
        <?php
    }

    public static function save() {
        if (!current_user_can('manage_options')) wp_die('Accès refusé.');
        check_admin_referer('parcs_ht_save_group_quotes');
        $out = array(
            'enabled' => '1',
            'form_id' => '',
            'visit_field' => isset($_POST['visit_field']) ? sanitize_key(wp_unslash($_POST['visit_field'])) : 'visite',
            'group_field' => isset($_POST['group_field']) ? sanitize_key(wp_unslash($_POST['group_field'])) : 'groupedevis',
            'school_value' => isset($_POST['school_value']) ? sanitize_text_field(wp_unslash($_POST['school_value'])) : 'Groupe',
            'disability_value' => isset($_POST['disability_value']) ? sanitize_text_field(wp_unslash($_POST['disability_value'])) : 'Groupe en situation de handicap',
            'seasons' => array(),
        );
        $rows = isset($_POST['seasons']) && is_array($_POST['seasons']) ? wp_unslash($_POST['seasons']) : array();
        foreach ($rows as $year => $row) {
            $year = preg_replace('/[^0-9]/', '', (string)$year);
            if (!preg_match('/^20\d{2}$/', $year) || !is_array($row)) continue;
            $clean = array('published' => isset($row['published']) ? '1' : '0');
            foreach (array('child','adult','disability','companion') as $key) {
                $value = isset($row[$key]) ? str_replace(',', '.', (string)$row[$key]) : '';
                $clean[$key] = is_numeric($value) && (float)$value >= 0 ? (string)(float)$value : '';
            }
            $ratio = isset($row['free_adult_children']) ? (int)$row['free_adult_children'] : 10;
            $clean['free_adult_children'] = (string)max(1, $ratio);
            if ($clean['published'] === '1') {
                foreach (array('child','adult','disability','companion') as $key) {
                    if ($clean[$key] === '') $clean['published'] = '0';
                }
            }
            $out['seasons'][$year] = $clean;
        }
        update_option(self::OPTION, $out, false);
        wp_safe_redirect(add_query_arg(array('page'=>self::PAGE,'updated'=>'1'), admin_url('admin.php')));
        exit;
    }

    public static function assets() {
        $settings = self::settings();
        wp_enqueue_script('parcs-ht-group-quotes', PARCS_HT_URL . 'assets/group-quotes.js', array('jquery'), PARCS_HT_VERSION, true);
        wp_add_inline_script('parcs-ht-group-quotes', 'window.ParcsHTGroupQuotes=' . wp_json_encode(array(
            'formId' => '',
            'visitField' => (string)$settings['visit_field'],
            'groupField' => (string)$settings['group_field'],
            'schoolValue' => (string)$settings['school_value'],
            'disabilityValue' => (string)$settings['disability_value'],
            'seasons' => (array)$settings['seasons'],
            'unavailableMessage' => 'Les tarifs groupes ne sont pas encore disponibles pour cette année.',
        )) . ';', 'before');
    }
}
