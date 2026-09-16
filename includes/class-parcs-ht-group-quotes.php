<?php

if (!defined('ABSPATH')) { exit; }

/**
 * Moteur des devis groupes.
 *
 * Principe depuis 1.15.18 : une date sélectionne exactement une année.
 * Cette année possède son propre interrupteur de devis et sa propre grille.
 * Aucune année précédente ou future n'est jamais utilisée comme repli.
 */
final class Parcs_HT_Group_Quotes {
    const OPTION = 'parcs_ht_group_quotes';
    const STATE_OPTION = 'parcs_ht_group_quote_years_v2';
    const STATE_VERSION = 1;
    const PAGE = 'parcs-ht-group-quotes';

    public static function init() {
        add_action('admin_menu', array(__CLASS__, 'menu'));
        add_action('admin_post_parcs_ht_save_group_quotes', array(__CLASS__, 'save'));
        add_filter('wpcf7_form_elements', array(__CLASS__, 'form_assets'));
        add_filter('wpcf7_posted_data', array(__CLASS__, 'canonicalize_posted_data'), 20, 1);
        add_filter('wpcf7_validate', array(__CLASS__, 'validate_quote'), 20, 2);
        add_action('updated_option', array(__CLASS__, 'sync_admin_year_activation'), 20, 3);
    }

    public static function legacy_binding_defaults() {
        return array(
            'column'=>'price',
            'child_row'=>'2','child_label'=>'Scolaire / extrascolaire',
            'adult_row'=>'0','adult_label'=>'Adulte',
            'disability_row'=>'3','disability_label'=>'Personne en situation de handicap et accompagnateur',
            'companion_row'=>'3','companion_label'=>'Personne en situation de handicap et accompagnateur',
            'free_adult_children'=>'10',
            'free_adult_round_threshold'=>'5',
        );
    }

    public static function defaults() {
        return array(
            'enabled'=>'1',
            'form_id'=>'',
            'visit_field'=>'visite',
            'group_field'=>'groupedevis',
            'school_value'=>'Groupe',
            'disability_value'=>'Groupe en situation de handicap',
            'binding_version'=>3,
            'tariff_bindings'=>array(),
            'tariff_binding'=>self::legacy_binding_defaults(),
            'seasons'=>array(),
        );
    }

    private static function raw_quote_settings() {
        $saved = get_option(self::OPTION, array());
        if (!is_array($saved)) $saved = array();
        $settings = array_replace_recursive(self::defaults(), $saved);
        $settings['enabled'] = '1';
        if (!is_array($settings['tariff_bindings'] ?? null)) $settings['tariff_bindings'] = array();
        if (!is_array($settings['tariff_binding'] ?? null)) $settings['tariff_binding'] = self::legacy_binding_defaults();
        if (!is_array($settings['seasons'] ?? null)) $settings['seasons'] = array();
        return $settings;
    }

    private static function canonical_all() {
        $all = get_option(Parcs_HT_Defaults::OPTION, array());
        return is_array($all) ? $all : array();
    }

    private static function valid_year($year) {
        return preg_match('/^20\d{2}$/', (string)$year) ? (string)$year : '';
    }

    private static function numeric_price($value) {
        $value = html_entity_decode((string)$value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $value = str_replace(array("\xc2\xa0", ' ', '€'), '', $value);
        $value = str_replace(',', '.', $value);
        return preg_match('/^-?[0-9]+(?:\.[0-9]+)?$/', $value) ? (float)$value : null;
    }

    private static function price_from_row($row, $column_id) {
        if (!is_array($row) || (string)($row['enabled'] ?? '1') !== '1') return null;
        if (!isset($row['cells'][$column_id]) || !is_array($row['cells'][$column_id])) return null;
        $price = self::numeric_price($row['cells'][$column_id]['value'] ?? '');
        return $price !== null && $price >= 0 ? $price : null;
    }

    private static function stable_binding($binding) {
        if (!is_array($binding) || !class_exists('Parcs_HT_Tariff_Identities')) return null;
        if (!Parcs_HT_Tariff_Identities::is_column_id($binding['column_id'] ?? '')) return null;
        foreach (array('child','adult','disability','companion') as $role) {
            if (!Parcs_HT_Tariff_Identities::is_row_id($binding[$role . '_row_id'] ?? '')) return null;
        }
        $ratio = max(1, (int)($binding['free_adult_children'] ?? 10));
        $threshold = max(1, min($ratio, (int)($binding['free_adult_round_threshold'] ?? 5)));
        $binding['free_adult_children'] = (string)$ratio;
        $binding['free_adult_round_threshold'] = (string)$threshold;
        return $binding;
    }

    private static function year_tariffs($year) {
        $all = self::canonical_all();
        $season = $all['seasons'][$year] ?? array();
        if (!is_array($season)) return array('season'=>array(),'rows'=>array(),'columns'=>array());
        $tariffs = is_array($season['tariffs'] ?? null) ? $season['tariffs'] : array();
        $rows = is_array($tariffs['groups'] ?? null) ? array_values($tariffs['groups']) : array();
        $columns = is_array($tariffs['columns']['groups'] ?? null) ? array_values($tariffs['columns']['groups']) : array();
        return array('season'=>$season,'rows'=>$rows,'columns'=>$columns);
    }

    private static function binding_matches_year($year, $binding) {
        $binding = self::stable_binding($binding);
        if (!$binding) return false;
        $grid = self::year_tariffs($year);
        if (!$grid['rows'] || !$grid['columns']) return false;
        if (!Parcs_HT_Tariff_Identities::column_exists($grid['columns'], $binding['column_id'])) return false;
        foreach (array('child','adult','disability','companion') as $role) {
            $row = Parcs_HT_Tariff_Identities::row_by_id($grid['rows'], $binding[$role . '_row_id']);
            if (!$row || self::price_from_row($row, $binding['column_id']) === null) return false;
        }
        return true;
    }

    private static function row_label($row) {
        $labels = is_array($row['label'] ?? null) ? $row['label'] : array();
        $parts = array();
        foreach (array('fr','en','de') as $lang) {
            $text = trim((string)($labels[$lang] ?? ''));
            if ($text !== '') $parts[] = $text;
        }
        $text = strtolower(implode(' ', $parts));
        return function_exists('remove_accents') ? strtolower(remove_accents($text)) : $text;
    }

    private static function find_role_row($rows, $patterns) {
        foreach ($rows as $row) {
            if (!is_array($row) || (string)($row['enabled'] ?? '1') !== '1') continue;
            $label = self::row_label($row);
            foreach ($patterns as $pattern) if (preg_match($pattern, $label)) return $row;
        }
        return null;
    }

    private static function row_from_legacy_target_year($rows, $legacy, $role) {
        $index = isset($legacy[$role . '_row']) ? (int)$legacy[$role . '_row'] : -1;
        $expected = trim((string)($legacy[$role . '_label'] ?? ''));
        if ($index >= 0 && isset($rows[$index]) && is_array($rows[$index])) {
            if ($expected === '' || stripos(self::row_label($rows[$index]), strtolower(function_exists('remove_accents') ? remove_accents($expected) : $expected)) !== false) return $rows[$index];
        }
        if ($expected !== '') {
            $needle = strtolower(function_exists('remove_accents') ? remove_accents($expected) : $expected);
            foreach ($rows as $row) if (is_array($row) && strpos(self::row_label($row), $needle) !== false) return $row;
        }
        return null;
    }

    private static function derive_binding_for_year($year, $settings) {
        $grid = self::year_tariffs($year);
        $rows = $grid['rows'];
        $columns = $grid['columns'];
        if (!$rows || !$columns || !class_exists('Parcs_HT_Tariff_Identities')) return null;

        $roles = array();
        $roles['child'] = self::find_role_row($rows, array('/scolaire|extrascolaire|school|schul/', '/enfant|child|kind/'));
        $roles['adult'] = self::find_role_row($rows, array('/adulte|adult|erwachs/'));
        $roles['disability'] = self::find_role_row($rows, array('/handicap|disabil|behinder/'));
        $roles['companion'] = self::find_role_row($rows, array('/accompagn|companion|begleit/'));

        $legacy = is_array($settings['tariff_binding'] ?? null) ? $settings['tariff_binding'] : self::legacy_binding_defaults();
        foreach (array('child','adult','disability','companion') as $role) if (!$roles[$role]) $roles[$role] = self::row_from_legacy_target_year($rows, $legacy, $role);
        if (!$roles['companion'] && $roles['disability']) $roles['companion'] = $roles['disability'];
        foreach ($roles as $row) if (!is_array($row)) return null;

        $column_ids = array();
        $legacy_column = sanitize_key((string)($legacy['column'] ?? ''));
        foreach ($columns as $column) {
            if (!is_array($column)) continue;
            $id = sanitize_key((string)($column['id'] ?? ''));
            if (!Parcs_HT_Tariff_Identities::is_column_id($id)) continue;
            if ($legacy_column !== '' && $legacy_column !== 'price' && $id === $legacy_column) array_unshift($column_ids, $id);
            elseif (!in_array($id, $column_ids, true)) $column_ids[] = $id;
        }

        foreach ($column_ids as $column_id) {
            $valid = true;
            foreach ($roles as $row) if (self::price_from_row($row, $column_id) === null) { $valid = false; break; }
            if (!$valid) continue;
            return array(
                'column_id'=>$column_id,
                'child_row_id'=>(string)$roles['child']['id'],
                'adult_row_id'=>(string)$roles['adult']['id'],
                'disability_row_id'=>(string)$roles['disability']['id'],
                'companion_row_id'=>(string)$roles['companion']['id'],
                'free_adult_children'=>(string)max(1, (int)($legacy['free_adult_children'] ?? 10)),
                'free_adult_round_threshold'=>(string)max(1, min(max(1, (int)($legacy['free_adult_children'] ?? 10)), (int)($legacy['free_adult_round_threshold'] ?? 5))),
            );
        }
        return null;
    }

    /** Liaison strictement locale à une année. Aucun héritage inter-années. */
    public static function binding_for_year($year, $settings = null) {
        $year = self::valid_year($year);
        if ($year === '') return null;
        if ($settings === null) $settings = self::raw_quote_settings();
        $exact = self::stable_binding($settings['tariff_bindings'][$year] ?? null);
        if ($exact && self::binding_matches_year($year, $exact)) return $exact;
        return self::derive_binding_for_year($year, $settings);
    }

    private static function legacy_year_evidence($year, $legacy_settings) {
        $exact = self::stable_binding($legacy_settings['tariff_bindings'][$year] ?? null);
        if ($exact && self::binding_matches_year($year, $exact)) return true;
        $legacy = is_array($legacy_settings['seasons'][$year] ?? null) ? $legacy_settings['seasons'][$year] : array();
        if (!$legacy) return false;
        foreach (array('child','adult','disability','companion') as $key) if (!array_key_exists($key, $legacy) || self::numeric_price($legacy[$key]) === null) return false;
        return true;
    }

    private static function migrate_state() {
        $existing = get_option(self::STATE_OPTION, array());
        if (is_array($existing) && (int)($existing['version'] ?? 0) >= self::STATE_VERSION && is_array($existing['years'] ?? null)) return $existing;

        $legacy = self::raw_quote_settings();
        $all = self::canonical_all();
        $current_year = (string)wp_date('Y');
        $state = array('version'=>self::STATE_VERSION, 'years'=>array());
        foreach ((array)($all['seasons'] ?? array()) as $year => $season) {
            $year = self::valid_year($year);
            if ($year === '' || !is_array($season)) continue;
            $enabled = false;
            if ((string)($season['group_quotes_enabled'] ?? '0') === '1') {
                $enabled = true;
            } elseif ((int)$year <= (int)$current_year && self::legacy_year_evidence($year, $legacy)) {
                $enabled = true;
            }
            $state['years'][$year] = array('enabled'=>$enabled ? '1' : '0');
        }
        update_option(self::STATE_OPTION, $state, false);
        return $state;
    }

    private static function state() {
        return self::migrate_state();
    }

    public static function quote_enabled_for_year($year) {
        $year = self::valid_year($year);
        if ($year === '') return false;
        $state = self::state();
        return (string)($state['years'][$year]['enabled'] ?? '0') === '1';
    }

    /**
     * Synchronise l'état annuel à partir de la valeur réellement persistée dans l'option.
     * Aucun accès à $_POST : le changement est détecté en comparant l'ancienne et la nouvelle option.
     * Une mise à jour de 2027 ne peut donc jamais modifier 2026.
     */
    public static function sync_admin_year_activation($option, $old_value, $new_value) {
        if ($option !== Parcs_HT_Defaults::OPTION || !is_array($old_value) || !is_array($new_value)) return;
        $new_seasons = is_array($new_value['seasons'] ?? null) ? $new_value['seasons'] : array();
        $old_seasons = is_array($old_value['seasons'] ?? null) ? $old_value['seasons'] : array();
        $state = self::state();
        $changed = false;

        foreach ($new_seasons as $year => $season) {
            $year = self::valid_year($year);
            if ($year === '' || !is_array($season) || !array_key_exists('group_quotes_enabled', $season)) continue;
            $new_enabled = (string)$season['group_quotes_enabled'] === '1' ? '1' : '0';
            $old_season = isset($old_seasons[$year]) && is_array($old_seasons[$year]) ? $old_seasons[$year] : array();
            $old_has_value = array_key_exists('group_quotes_enabled', $old_season);
            $old_enabled = $old_has_value && (string)$old_season['group_quotes_enabled'] === '1' ? '1' : '0';
            if ($old_has_value && $old_enabled === $new_enabled) continue;
            $state['years'][$year] = array('enabled'=>$new_enabled);
            $changed = true;
        }

        if ($changed) {
            $state['version'] = self::STATE_VERSION;
            update_option(self::STATE_OPTION, $state, false);
        }
    }

    private static function resolved_season($year, $settings = null) {
        $year = self::valid_year($year);
        if ($year === '' || !self::quote_enabled_for_year($year)) return null;
        if ($settings === null) $settings = self::raw_quote_settings();
        $grid = self::year_tariffs($year);
        if (!$grid['rows'] || !$grid['columns']) return null;
        $binding = self::binding_for_year($year, $settings);
        if (!$binding) return null;

        $result = array(
            'published'=>'1',
            'free_adult_children'=>$binding['free_adult_children'],
            'free_adult_round_threshold'=>$binding['free_adult_round_threshold'],
        );
        foreach (array('child','adult','disability','companion') as $role) {
            $row = Parcs_HT_Tariff_Identities::row_by_id($grid['rows'], $binding[$role . '_row_id']);
            $price = self::price_from_row($row, $binding['column_id']);
            if ($price === null) return null;
            $result[$role] = (string)$price;
        }
        return $result;
    }

    public static function season_for_year($year) {
        return self::resolved_season((string)$year, self::raw_quote_settings());
    }

    public static function settings($public = true) {
        $settings = self::raw_quote_settings();
        if (!$public) return $settings;
        $settings['seasons'] = array();
        $all = self::canonical_all();
        foreach ((array)($all['seasons'] ?? array()) as $year => $season) {
            unset($season);
            $year = self::valid_year($year);
            if ($year === '') continue;
            $row = self::resolved_season($year, $settings);
            $settings['seasons'][$year] = $row ?: array('published'=>'0');
        }
        return $settings;
    }

    private static function year_from_date($value) {
        return preg_match('/^(20\d{2})-\d{2}-\d{2}$/', trim((string)$value), $match) ? $match[1] : '';
    }

    private static function integer_value($value) {
        $value = trim((string)$value);
        return preg_match('/^\d+$/', $value) ? (int)$value : 0;
    }

    private static function euro($value) {
        return number_format((float)$value, 2, ',', ' ') . ' €';
    }

    public static function complimentary_adults($children, $adults, $ratio = 10, $threshold = 5) {
        $children = max(0, (int)$children);
        $adults = max(0, (int)$adults);
        $ratio = max(1, (int)$ratio);
        $threshold = max(1, min($ratio, (int)$threshold));
        $base = (int)floor($children / $ratio);
        if (($children % $ratio) >= $threshold) $base++;
        return min($adults, $base);
    }

    public static function canonicalize_posted_data($data) {
        if (!is_array($data)) return $data;
        $settings = self::settings(false);
        $visit_field = (string)$settings['visit_field'];
        $group_field = (string)$settings['group_field'];
        if (!array_key_exists($visit_field, $data) || !array_key_exists($group_field, $data)) return $data;
        $year = self::year_from_date($data[$visit_field]);
        $row = self::resolved_season($year, $settings);
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
            $children = self::integer_value($data['nbrenfants'] ?? 0);
            $adults = self::integer_value($data['nbradultes'] ?? 0);
            $free = self::complimentary_adults($children, $adults, (int)$row['free_adult_children'], (int)$row['free_adult_round_threshold']);
            $paying = max(0, $adults - $free);
            $children_total = $children * (float)$row['child'];
            $adults_total = $paying * (float)$row['adult'];
            $data['nbradultgratuit'] = (string)$free;
            $data['nbradultpayant'] = (string)$paying;
            $data['nbrprixenfants'] = self::euro($children_total);
            $data['nbrprixadultes'] = self::euro($adults_total);
            $data['totalprixscolaire'] = self::euro($children_total + $adults_total);
            $data['totalprixhandicape'] = '';
        } elseif ($type === (string)$settings['disability_value']) {
            $people = self::integer_value($data['nbrpersohandicape'] ?? 0);
            $companions = self::integer_value($data['nbraccompa'] ?? 0);
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
        $settings = self::settings(false);
        $visit_field = (string)$settings['visit_field'];
        $group_field = (string)$settings['group_field'];
        if (!array_key_exists($visit_field, $data) || !array_key_exists($group_field, $data)) return $result;
        $year = self::year_from_date($data[$visit_field]);
        if ($year !== '' && self::resolved_season($year, $settings)) return $result;
        foreach ((array)$tags as $tag) {
            if (is_object($tag) && isset($tag->name) && (string)$tag->name === $visit_field) {
                $result->invalidate($tag, 'Les tarifs groupes pour ' . ($year ?: 'cette année') . ' ne sont pas encore disponibles. Merci de revenir ultérieurement.');
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
        ?>
        <div class="wrap"><h1>Tarifs des devis groupes</h1><div class="notice notice-info inline"><p>Chaque année utilise exclusivement sa propre grille du module <strong>Groupes → Tarifs</strong> et son interrupteur « Activer les devis groupes ».</p></div><p><a class="button button-primary" href="<?php echo esc_url(add_query_arg(array('page'=>Parcs_HT_Admin::PAGE,'tab'=>'htp-tariffs-groups'),admin_url('admin.php'))); ?>">Ouvrir les tarifs groupes</a></p></div>
        <?php
    }

    public static function save() {
        if (!current_user_can('manage_options')) wp_die('Accès refusé.');
        check_admin_referer('parcs_ht_save_group_quotes');
        wp_safe_redirect(add_query_arg(array('page'=>self::PAGE), admin_url('admin.php')));
        exit;
    }

    public static function form_assets($html) {
        $settings = self::settings(false);
        $field = (string)$settings['visit_field'];
        if (strpos($html, 'name="' . $field . '"') !== false || strpos($html, "name='" . $field . "'") !== false) self::assets();
        return $html;
    }

    public static function assets() {
        if (wp_script_is('parcs-ht-group-quotes', 'enqueued')) return;
        $settings = self::settings(false);
        $all = self::canonical_all();
        $seasons = array();
        foreach ((array)($all['seasons'] ?? array()) as $year => $season) {
            unset($season);
            $year = self::valid_year($year);
            if ($year === '') continue;
            $row = self::resolved_season($year, $settings);
            if ($row) $seasons[$year] = array_intersect_key($row, array_flip(array('published','child','adult','disability','companion','free_adult_children','free_adult_round_threshold')));
        }
        wp_enqueue_script('parcs-ht-group-quotes', PARCS_HT_URL . 'assets/group-quotes.js', array('jquery'), PARCS_HT_VERSION, true);
        wp_add_inline_script('parcs-ht-group-quotes', 'window.ParcsHTGroupQuotes=' . wp_json_encode(array(
            'formId'=>'',
            'visitField'=>(string)$settings['visit_field'],
            'groupField'=>(string)$settings['group_field'],
            'schoolValue'=>(string)$settings['school_value'],
            'disabilityValue'=>(string)$settings['disability_value'],
            'seasons'=>$seasons,
            'unavailableMessage'=>'Les tarifs groupes ne sont pas encore disponibles pour cette année. Merci de revenir ultérieurement.',
        )) . ';', 'before');
    }
}
