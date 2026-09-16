<?php

if (!defined('ABSPATH')) { exit; }

final class Parcs_HT_Group_Quotes {
    const OPTION = 'parcs_ht_group_quotes';
    const PAGE = 'parcs-ht-group-quotes';

    public static function init() {
        add_action('admin_menu', array(__CLASS__, 'menu'));
        add_action('admin_post_parcs_ht_save_group_quotes', array(__CLASS__, 'save'));
        add_filter('wpcf7_form_elements', array(__CLASS__, 'form_assets'));
        add_filter('wpcf7_posted_data', array(__CLASS__, 'canonicalize_posted_data'), 20, 1);
        add_filter('wpcf7_validate', array(__CLASS__, 'validate_quote'), 20, 2);
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
            'enabled'=>'1','form_id'=>'','visit_field'=>'visite','group_field'=>'groupedevis',
            'school_value'=>'Groupe','disability_value'=>'Groupe en situation de handicap',
            'binding_version'=>2,
            'tariff_bindings'=>array(),
            'tariff_binding'=>self::legacy_binding_defaults(),
            'seasons'=>array('2026'=>array('child'=>'6','adult'=>'8.50','disability'=>'6','companion'=>'6','free_adult_children'=>'10')),
        );
    }

    public static function settings($public = true) {
        $saved = get_option(self::OPTION, array());
        if (!is_array($saved)) $saved = array();
        $settings = array_replace_recursive(self::defaults(), $saved);
        $settings['enabled'] = '1';
        if (!isset($settings['tariff_bindings']) || !is_array($settings['tariff_bindings'])) $settings['tariff_bindings'] = array();
        if (!isset($settings['tariff_binding']) || !is_array($settings['tariff_binding'])) $settings['tariff_binding'] = self::legacy_binding_defaults();
        if (!isset($settings['seasons']) || !is_array($settings['seasons'])) $settings['seasons'] = array();
        if (!$public) return $settings;

        $all = class_exists('Parcs_HT_Defaults') ? Parcs_HT_Defaults::all_settings() : array();
        $years = array_unique(array_merge(
            array_keys($settings['seasons']),
            array_keys((array)($all['seasons'] ?? array()))
        ));
        foreach ($years as $year) {
            $year = (string)$year;
            if (!preg_match('/^20\d{2}$/', $year)) continue;
            $row = self::published_season($year, $settings);
            if ($row) {
                $settings['seasons'][$year] = $row;
                continue;
            }
            if (!isset($settings['seasons'][$year]) || !is_array($settings['seasons'][$year])) $settings['seasons'][$year] = array();
            $settings['seasons'][$year]['published'] = '0';
        }
        return $settings;
    }

    private static function stable_binding($binding) {
        if (!is_array($binding) || !class_exists('Parcs_HT_Tariff_Identities')) return null;
        if (!Parcs_HT_Tariff_Identities::is_column_id($binding['column_id'] ?? '')) return null;
        foreach (array('child','adult','disability','companion') as $role) {
            if (!Parcs_HT_Tariff_Identities::is_row_id($binding[$role . '_row_id'] ?? '')) return null;
        }
        $binding['free_adult_children'] = (string)max(1, (int)($binding['free_adult_children'] ?? 10));
        $binding['free_adult_round_threshold'] = (string)max(1, min((int)$binding['free_adult_children'], (int)($binding['free_adult_round_threshold'] ?? 5)));
        return $binding;
    }

    private static function translated_label($value) {
        if (!is_array($value)) return '';
        foreach (array('fr','en','de') as $lang) {
            $text = trim((string)($value[$lang] ?? ''));
            if ($text !== '') return $text;
        }
        return '';
    }

    private static function numeric_price($value) {
        $value = html_entity_decode((string)$value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $value = str_replace(array("\xc2\xa0", ' ', '€'), '', $value);
        $value = str_replace(',', '.', $value);
        return preg_match('/([0-9]+(?:\.[0-9]+)?)/', $value, $match) ? (float)$match[1] : null;
    }

    private static function price_from_row($row, $column_id) {
        if (!is_array($row) || (string)($row['enabled'] ?? '1') !== '1') return null;
        if (!isset($row['cells'][$column_id]) || !is_array($row['cells'][$column_id])) return null;
        return self::numeric_price($row['cells'][$column_id]['value'] ?? '');
    }

    private static function binding_matches_year($year, $binding) {
        $binding = self::stable_binding($binding);
        if (!$binding) return false;
        $all = get_option(Parcs_HT_Defaults::OPTION, array());
        if (!is_array($all) || empty($all['seasons'][$year]) || !is_array($all['seasons'][$year])) return false;
        $tariffs = isset($all['seasons'][$year]['tariffs']) && is_array($all['seasons'][$year]['tariffs']) ? $all['seasons'][$year]['tariffs'] : array();
        $rows = isset($tariffs['groups']) && is_array($tariffs['groups']) ? array_values($tariffs['groups']) : array();
        $columns = isset($tariffs['columns']['groups']) && is_array($tariffs['columns']['groups']) ? array_values($tariffs['columns']['groups']) : array();
        if (!$rows || !$columns || !Parcs_HT_Tariff_Identities::column_exists($columns, $binding['column_id'])) return false;
        foreach (array('child','adult','disability','companion') as $role) {
            $row = Parcs_HT_Tariff_Identities::row_by_id($rows, $binding[$role . '_row_id']);
            if (!$row || self::price_from_row($row, $binding['column_id']) === null) return false;
        }
        return true;
    }

    private static function legacy_binding_for_year($year, $settings) {
        $all = get_option(Parcs_HT_Defaults::OPTION, array());
        if (!is_array($all) || empty($all['seasons'][$year]) || !is_array($all['seasons'][$year])) return null;
        $tariffs = isset($all['seasons'][$year]['tariffs']) && is_array($all['seasons'][$year]['tariffs']) ? $all['seasons'][$year]['tariffs'] : array();
        $rows = isset($tariffs['groups']) && is_array($tariffs['groups']) ? array_values($tariffs['groups']) : array();
        $columns = isset($tariffs['columns']['groups']) && is_array($tariffs['columns']['groups']) ? array_values($tariffs['columns']['groups']) : array();
        if (!$rows || !$columns) return null;

        $legacy = isset($settings['tariff_binding']) && is_array($settings['tariff_binding']) ? $settings['tariff_binding'] : self::legacy_binding_defaults();
        $legacy_column = sanitize_key((string)($legacy['column'] ?? 'price'));
        $column_id = '';
        foreach ($columns as $column) {
            if (!is_array($column)) continue;
            $candidate = sanitize_key((string)($column['id'] ?? ''));
            if (!Parcs_HT_Tariff_Identities::is_column_id($candidate)) continue;
            if ($legacy_column === '' || $legacy_column === 'price' || $candidate === $legacy_column) {
                $column_id = $candidate;
                break;
            }
        }
        if ($column_id === '') return null;

        $binding = array(
            'column_id'=>$column_id,
            'free_adult_children'=>(string)max(1, (int)($legacy['free_adult_children'] ?? 10)),
            'free_adult_round_threshold'=>(string)max(1, (int)($legacy['free_adult_round_threshold'] ?? 5)),
        );
        $binding['free_adult_round_threshold'] = (string)min((int)$binding['free_adult_children'], (int)$binding['free_adult_round_threshold']);

        foreach (array('child','adult','disability','companion') as $role) {
            $index = isset($legacy[$role . '_row']) ? (int)$legacy[$role . '_row'] : -1;
            $expected = trim((string)($legacy[$role . '_label'] ?? ''));
            $matched = null;
            if ($index >= 0 && isset($rows[$index]) && is_array($rows[$index])) {
                $label = self::translated_label($rows[$index]['label'] ?? array());
                if ($expected === '' || $label === $expected) $matched = $rows[$index];
            }
            if (!$matched && $expected !== '') {
                foreach ($rows as $row) {
                    if (is_array($row) && self::translated_label($row['label'] ?? array()) === $expected) {
                        $matched = $row;
                        break;
                    }
                }
            }
            $id = sanitize_key((string)(is_array($matched) ? ($matched['id'] ?? '') : ''));
            if (!Parcs_HT_Tariff_Identities::is_row_id($id)) return null;
            $binding[$role . '_row_id'] = $id;
        }

        return self::binding_matches_year($year, $binding) ? $binding : null;
    }

    public static function binding_for_year($year, $settings = null) {
        if ($settings === null) $settings = self::settings(false);
        $year = (string)$year;
        $binding = self::stable_binding($settings['tariff_bindings'][$year] ?? null);
        if ($binding && self::binding_matches_year($year, $binding)) return $binding;

        $legacy = self::legacy_binding_for_year($year, $settings);
        if ($legacy) return $legacy;

        $bindings = isset($settings['tariff_bindings']) && is_array($settings['tariff_bindings']) ? $settings['tariff_bindings'] : array();
        $previous = array();
        $future = array();
        foreach ($bindings as $candidate_year => $candidate_binding) {
            if (!preg_match('/^20\d{2}$/', (string)$candidate_year)) continue;
            $candidate = self::stable_binding($candidate_binding);
            if (!$candidate || !self::binding_matches_year($year, $candidate)) continue;
            if ((int)$candidate_year < (int)$year) $previous[(int)$candidate_year] = $candidate;
            elseif ((int)$candidate_year > (int)$year) $future[(int)$candidate_year] = $candidate;
        }
        if ($previous) {
            krsort($previous, SORT_NUMERIC);
            return reset($previous);
        }
        if ($future) {
            ksort($future, SORT_NUMERIC);
            return reset($future);
        }
        return null;
    }

    private static function year_from_date($value) {
        return preg_match('/^(20\d{2})-\d{2}-\d{2}$/', trim((string)$value), $match) ? $match[1] : '';
    }

    private static function quote_enabled_for_year($year, $season, $settings = null) {
        if (!is_array($season)) return false;
        if (array_key_exists('group_quotes_enabled', $season)) return (string)$season['group_quotes_enabled'] === '1';

        // Compatibilité des installations créées avant les interrupteurs annuels :
        // l'état du devis est déduit uniquement du stockage du devis et d'une liaison
        // réellement compatible avec la grille cible, jamais du statut de publication publique.
        $saved = get_option(self::OPTION, array());
        if (!is_array($saved)) $saved = array();
        if (isset($saved['tariff_bindings'][$year]) && self::stable_binding($saved['tariff_bindings'][$year]) && self::binding_matches_year($year, $saved['tariff_bindings'][$year])) return true;
        $legacy = isset($saved['seasons'][$year]) && is_array($saved['seasons'][$year]) ? $saved['seasons'][$year] : array();
        if ($legacy) {
            $valid = true;
            foreach (array('child','adult','disability','companion') as $key) {
                if (!array_key_exists($key, $legacy) || self::numeric_price($legacy[$key]) === null) { $valid = false; break; }
            }
            if ($valid) return true;
        }
        if ($settings === null) $settings = self::settings(false);
        return self::binding_for_year($year, $settings) !== null;
    }

    private static function published_season($year, $settings = null) {
        if ($settings === null) $settings = self::settings(false);
        if ($year === '') return null;
        $all = get_option(Parcs_HT_Defaults::OPTION, array());
        if (!is_array($all) || empty($all['seasons'][$year]) || !is_array($all['seasons'][$year])) return null;
        $season = $all['seasons'][$year];
        if (!self::quote_enabled_for_year($year, $season, $settings)) return null;
        $tariffs = isset($season['tariffs']) && is_array($season['tariffs']) ? $season['tariffs'] : array();
        $rows = isset($tariffs['groups']) && is_array($tariffs['groups']) ? array_values($tariffs['groups']) : array();
        $columns = isset($tariffs['columns']['groups']) && is_array($tariffs['columns']['groups']) ? array_values($tariffs['columns']['groups']) : array();
        if (!$rows || !$columns || !class_exists('Parcs_HT_Tariff_Identities')) return null;

        $binding = self::binding_for_year($year, $settings);
        if (!$binding || !Parcs_HT_Tariff_Identities::column_exists($columns, $binding['column_id'])) return null;
        $result = array(
            'published'=>'1',
            'free_adult_children'=>$binding['free_adult_children'],
            'free_adult_round_threshold'=>$binding['free_adult_round_threshold'],
        );
        foreach (array('child','adult','disability','companion') as $role) {
            $row = Parcs_HT_Tariff_Identities::row_by_id($rows, $binding[$role . '_row_id']);
            $price = self::price_from_row($row, $binding['column_id']);
            if ($price === null || $price < 0) return null;
            $result[$role] = (string)$price;
        }
        return $result;
    }

    public static function season_for_year($year) {
        return self::published_season((string)$year, self::settings(false));
    }

    private static function number_value($value) {
        $value = str_replace(',', '.', trim((string)$value));
        return is_numeric($value) && (float)$value >= 0 ? (float)$value : 0.0;
    }

    private static function euro($value) { return number_format((float)$value, 2, ',', ' ') . ' €'; }

    public static function complimentary_adults($children, $adults, $ratio = 10, $threshold = 5) {
        $children = max(0, (int)$children);
        $adults = max(0, (int)$adults);
        $ratio = max(1, (int)$ratio);
        $threshold = max(1, min($ratio, (int)$threshold));
        $base = (int)floor($children / $ratio);
        $remainder = $children % $ratio;
        if ($remainder >= $threshold) $base++;
        return min($adults, $base);
    }

    public static function canonicalize_posted_data($data) {
        if (!is_array($data)) return $data;
        $settings = self::settings(false);
        $visit_field = (string)$settings['visit_field'];
        $group_field = (string)$settings['group_field'];
        if (!array_key_exists($visit_field, $data) || !array_key_exists($group_field, $data)) return $data;
        $year = self::year_from_date($data[$visit_field]);
        $row = self::published_season($year, $settings);
        if (!$row) {
            foreach (array('devisannee','tarifenfant','tarifadulte','tarifhandicap','tarifaccompagnateur','nbrprixenfants','nbrprixadultes','totalprixscolaire','totalprixhandicape') as $field) $data[$field] = '';
            return $data;
        }
        $data['devisannee']=$year;
        $data['tarifenfant']=self::euro($row['child']);
        $data['tarifadulte']=self::euro($row['adult']);
        $data['tarifhandicap']=self::euro($row['disability']);
        $data['tarifaccompagnateur']=self::euro($row['companion']);
        $type = is_array($data[$group_field]) ? implode('', $data[$group_field]) : (string)$data[$group_field];
        if ($type === (string)$settings['school_value']) {
            $children=self::number_value($data['nbrenfants']??0); $adults=self::number_value($data['nbradultes']??0);
            $free=self::complimentary_adults($children,$adults,(int)$row['free_adult_children'],(int)$row['free_adult_round_threshold']);
            $paying=max(0,$adults-$free);
            $children_total=$children*(float)$row['child']; $adults_total=$paying*(float)$row['adult'];
            $data['nbradultgratuit']=(string)(int)$free; $data['nbradultpayant']=(string)(int)$paying;
            $data['nbrprixenfants']=self::euro($children_total); $data['nbrprixadultes']=self::euro($adults_total);
            $data['totalprixscolaire']=self::euro($children_total+$adults_total); $data['totalprixhandicape']='';
        } elseif ($type === (string)$settings['disability_value']) {
            $people=self::number_value($data['nbrpersohandicape']??0); $companions=self::number_value($data['nbraccompa']??0);
            $data['nbradultgratuit']='0'; $data['nbradultpayant']='0'; $data['nbrprixenfants']=''; $data['nbrprixadultes']=''; $data['totalprixscolaire']='';
            $data['totalprixhandicape']=self::euro(($people*(float)$row['disability'])+($companions*(float)$row['companion']));
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
        if ($year === '' || self::published_season($year, $settings)) return $result;
        foreach ((array)$tags as $tag) {
            if (is_object($tag) && isset($tag->name) && (string)$tag->name === $visit_field) {
                $result->invalidate($tag, 'Les tarifs groupes pour ' . $year . ' ne sont pas encore disponibles. Merci de revenir ultérieurement.');
                break;
            }
        }
        return $result;
    }

    public static function menu() { add_submenu_page(Parcs_HT_Admin::PAGE,'Tarifs des devis groupes','Tarifs devis groupes','manage_options',self::PAGE,array(__CLASS__,'page')); }
    public static function page() {
        if(!current_user_can('manage_options'))return; ?>
        <div class="wrap"><h1>Tarifs des devis groupes</h1><div class="notice notice-info inline"><p>Les devis utilisent les identifiants permanents du module <strong>Groupes → Tarifs</strong>. Cette page est conservée uniquement pour compatibilité technique.</p></div><p><a class="button button-primary" href="<?php echo esc_url(add_query_arg(array('page'=>Parcs_HT_Admin::PAGE,'tab'=>'htp-tariffs-groups'),admin_url('admin.php'))); ?>">Ouvrir les tarifs groupes</a></p></div><?php
    }
    public static function save() { if(!current_user_can('manage_options'))wp_die('Accès refusé.');check_admin_referer('parcs_ht_save_group_quotes');wp_safe_redirect(add_query_arg(array('page'=>self::PAGE),admin_url('admin.php')));exit; }

    public static function form_assets($html) {
        $settings=self::settings(false); $field=(string)$settings['visit_field'];
        if(strpos($html,'name="'.$field.'"')!==false||strpos($html,"name='".$field."'")!==false)self::assets();
        return$html;
    }

    public static function assets() {
        if(wp_script_is('parcs-ht-group-quotes','enqueued'))return;
        $settings=self::settings(false); $all=Parcs_HT_Defaults::all_settings(); $seasons=array();
        foreach((array)($all['seasons']??array()) as $year=>$season){if(!is_array($season))continue;$row=self::published_season((string)$year,$settings);if($row)$seasons[$year]=array_intersect_key($row,array_flip(array('published','child','adult','disability','companion','free_adult_children','free_adult_round_threshold')));}
        wp_enqueue_script('parcs-ht-group-quotes',PARCS_HT_URL.'assets/group-quotes.js',array('jquery'),PARCS_HT_VERSION,true);
        wp_add_inline_script('parcs-ht-group-quotes','window.ParcsHTGroupQuotes='.wp_json_encode(array('formId'=>'','visitField'=>(string)$settings['visit_field'],'groupField'=>(string)$settings['group_field'],'schoolValue'=>(string)$settings['school_value'],'disabilityValue'=>(string)$settings['disability_value'],'seasons'=>$seasons,'unavailableMessage'=>'Les tarifs groupes ne sont pas encore disponibles pour cette année. Merci de revenir ultérieurement.')).';','before');
    }
}
