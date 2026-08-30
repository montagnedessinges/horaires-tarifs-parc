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
        add_filter('wpcf7_posted_data', array(__CLASS__, 'canonicalize_posted_data'), 20, 1);
        add_filter('wpcf7_validate', array(__CLASS__, 'validate_quote'), 20, 2);
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

    private static function year_from_date($value) {
        $value = trim((string)$value);
        return preg_match('/^(20\d{2})-\d{2}-\d{2}$/', $value, $match) ? $match[1] : '';
    }

    private static function published_season($year, $settings = null) {
        if ($settings === null) $settings = self::settings();
        if ($year === '' || empty($settings['seasons'][$year]) || !is_array($settings['seasons'][$year])) return null;
        $row = $settings['seasons'][$year];
        if ((string)($row['published'] ?? '0') !== '1') return null;
        foreach (array('child','adult','disability','companion') as $key) {
            if (!isset($row[$key]) || !is_numeric($row[$key]) || (float)$row[$key] < 0) return null;
        }
        return $row;
    }

    private static function number_value($value) {
        $value = str_replace(',', '.', trim((string)$value));
        return is_numeric($value) && (float)$value >= 0 ? (float)$value : 0.0;
    }

    private static function euro($value) {
        return number_format((float)$value, 2, ',', ' ') . ' €';
    }

    public static function canonicalize_posted_data($data) {
        if (!is_array($data)) return $data;
        $settings = self::settings();
        $visit_field = (string)$settings['visit_field'];
        $group_field = (string)$settings['group_field'];
        if (!array_key_exists($visit_field, $data) || !array_key_exists($group_field, $data)) return $data;

        $year = self::year_from_date($data[$visit_field]);
        $row = self::published_season($year, $settings);
        if (!$row) {
            foreach (array('devisannee','tarifenfant','tarifadulte','tarifhandicap','tarifaccompagnateur','nbrprixenfants','nbrprixadultes','totalprixscolaire','totalprixhandicape') as $field) $data[$field] = '';
            return $data;
        }

        $data['devisannee'] = $year;
        $data['tarifenfant'] = self::euro($row['child']);
        $data['tarifadulte'] = self::euro($row['adult']);
        $data['tarifhandicap'] = self::euro($row['disability']);
        $data['tarifaccompagnateur'] = self::euro($row['companion']);

        $type = is_array($data[$group_field]) ? implode('', $data[$group_field]) : (string)$data[$group_field];
        if ($type === (string)$settings['school_value']) {
            $children = self::number_value($data['nbrenfants'] ?? 0);
            $adults = self::number_value($data['nbradultes'] ?? 0);
            $ratio = max(1, (int)($row['free_adult_children'] ?? 10));
            $free = min($adults, floor($children / $ratio));
            $paying = max(0, $adults - $free);
            $children_total = $children * (float)$row['child'];
            $adults_total = $paying * (float)$row['adult'];
            $data['nbradultgratuit'] = (string)(int)$free;
            $data['nbradultpayant'] = (string)(int)$paying;
            $data['nbrprixenfants'] = self::euro($children_total);
            $data['nbrprixadultes'] = self::euro($adults_total);
            $data['totalprixscolaire'] = self::euro($children_total + $adults_total);
            $data['totalprixhandicape'] = '';
        } elseif ($type === (string)$settings['disability_value']) {
            $people = self::number_value($data['nbrpersohandicape'] ?? 0);
            $companions = self::number_value($data['nbraccompa'] ?? 0);
            $data['nbradultgratuit'] = '0';
            $data['nbradultpayant'] = '0';
            $data['nbrprixenfants'] = '';
            $data['nbrprixadultes'] = '';
            $data['totalprixscolaire'] = '';
            $data['totalprixhandicape'] = self::euro(($people * (float)$row['disability']) + ($companions * (float)$row['companion']));
        }
        return $data;
    }

    public static function validate_quote($result, $tags) {
        if (!class_exists('WPCF7_Submission')) return $result;
        $submission = WPCF7_Submission::get_instance();
        if (!$submission) return $result;
        $data = $submission->get_posted_data();
        if (!is_array($data)) return $result;
        $settings = self::settings();
        $visit_field = (string)$settings['visit_field'];
        $group_field = (string)$settings['group_field'];
        if (!array_key_exists($visit_field, $data) || !array_key_exists($group_field, $data)) return $result;
        $year = self::year_from_date($data[$visit_field]);
        if ($year === '' || self::published_season($year, $settings)) return $result;
        foreach ((array)$tags as $tag) {
            if (is_object($tag) && isset($tag->name) && (string)$tag->name === $visit_field) {
                $result->invalidate($tag, 'Les tarifs groupes pour ' . $year . ' ne sont pas encore disponibles. Merci de revenir ultérieurement.');
                break;
            }
        }
        return $result;
    }

    public static function menu() {
        add_submenu_page(Parcs_HT_Admin::PAGE, 'Tarifs des devis groupes', 'Tarifs devis groupes', 'manage_options', self::PAGE, array(__CLASS__, 'page'));
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
            <p>Une seule grille de tarifs groupes est utilisée par année. La date de visite choisie dans Contact Form 7 sélectionne automatiquement la bonne année ; le visiteur ne choisit jamais lui-même une grille tarifaire.</p>
            <?php if (isset($_GET['updated'])) : ?><div class="notice notice-success is-dismissible"><p>Les réglages des devis groupes ont été enregistrés.</p></div><?php endif; ?>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <input type="hidden" name="action" value="parcs_ht_save_group_quotes">
                <?php wp_nonce_field('parcs_ht_save_group_quotes'); ?>
                <table class="form-table" role="presentation">
                    <tr><th scope="row">Détection du formulaire</th><td><p class="description">Automatique à partir des champs de date et de type de groupe. Aucun ID Contact Form 7 fixe n’est nécessaire.</p></td></tr>
                    <tr><th scope="row">Champs techniques</th><td><label>Date de visite <input name="visit_field" value="<?php echo esc_attr((string)$settings['visit_field']); ?>"></label><br><label>Type de groupe <input name="group_field" value="<?php echo esc_attr((string)$settings['group_field']); ?>"></label></td></tr>
                    <tr><th scope="row">Valeurs du type de groupe</th><td><label>Groupe scolaire <input class="regular-text" name="school_value" value="<?php echo esc_attr((string)$settings['school_value']); ?>"></label><br><label>Situation de handicap <input class="regular-text" name="disability_value" value="<?php echo esc_attr((string)$settings['disability_value']); ?>"></label></td></tr>
                </table>
                <h2>Tarifs par année de visite</h2>
                <p>Si une année n’est pas disponible, le devis est bloqué : les tarifs d’une autre année ne sont jamais utilisés en secours.</p>
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
        $out = array('enabled'=>'1','form_id'=>'','visit_field'=>isset($_POST['visit_field']) ? sanitize_key(wp_unslash($_POST['visit_field'])) : 'visite','group_field'=>isset($_POST['group_field']) ? sanitize_key(wp_unslash($_POST['group_field'])) : 'groupedevis','school_value'=>isset($_POST['school_value']) ? sanitize_text_field(wp_unslash($_POST['school_value'])) : 'Groupe','disability_value'=>isset($_POST['disability_value']) ? sanitize_text_field(wp_unslash($_POST['disability_value'])) : 'Groupe en situation de handicap','seasons'=>array());
        $rows = isset($_POST['seasons']) && is_array($_POST['seasons']) ? wp_unslash($_POST['seasons']) : array();
        foreach ($rows as $year => $row) {
            $year = preg_replace('/[^0-9]/', '', (string)$year);
            if (!preg_match('/^20\d{2}$/', $year) || !is_array($row)) continue;
            $clean = array('published' => isset($row['published']) ? '1' : '0');
            foreach (array('child','adult','disability','companion') as $key) {
                $value = isset($row[$key]) ? str_replace(',', '.', (string)$row[$key]) : '';
                $clean[$key] = is_numeric($value) && (float)$value >= 0 ? (string)(float)$value : '';
            }
            $clean['free_adult_children'] = (string)max(1, isset($row['free_adult_children']) ? (int)$row['free_adult_children'] : 10);
            if ($clean['published'] === '1') foreach (array('child','adult','disability','companion') as $key) if ($clean[$key] === '') $clean['published'] = '0';
            $out['seasons'][$year] = $clean;
        }
        update_option(self::OPTION, $out, false);
        wp_safe_redirect(add_query_arg(array('page'=>self::PAGE,'updated'=>'1'), admin_url('admin.php')));
        exit;
    }

    public static function assets() {
        $settings = self::settings();
        wp_enqueue_script('parcs-ht-group-quotes', PARCS_HT_URL . 'assets/group-quotes.js', array('jquery'), PARCS_HT_VERSION, true);
        wp_add_inline_script('parcs-ht-group-quotes', 'window.ParcsHTGroupQuotes=' . wp_json_encode(array('formId'=>'','visitField'=>(string)$settings['visit_field'],'groupField'=>(string)$settings['group_field'],'schoolValue'=>(string)$settings['school_value'],'disabilityValue'=>(string)$settings['disability_value'],'seasons'=>(array)$settings['seasons'],'unavailableMessage'=>'Les tarifs groupes ne sont pas encore disponibles pour cette année. Merci de revenir ultérieurement.')) . ';', 'before');
    }
}
