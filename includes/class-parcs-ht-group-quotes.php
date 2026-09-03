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

    public static function defaults() {
        return array(
            'enabled'=>'1','form_id'=>'','visit_field'=>'visite','group_field'=>'groupedevis',
            'school_value'=>'Groupe','disability_value'=>'Groupe en situation de handicap',
            'tariff_binding'=>array(
                'column'=>'price',
                'child_row'=>'2','child_label'=>'Scolaire / extrascolaire',
                'adult_row'=>'0','adult_label'=>'Adulte',
                'disability_row'=>'3','disability_label'=>'Personne en situation de handicap et accompagnateur',
                'companion_row'=>'3','companion_label'=>'Personne en situation de handicap et accompagnateur',
                'free_adult_children'=>'10',
            ),
            // Conservé uniquement pour migration/retour arrière. Ces montants ne sont plus utilisés en 1.11+.
            'seasons'=>array('2026'=>array('child'=>'6','adult'=>'8.50','disability'=>'6','companion'=>'6','free_adult_children'=>'10')),
        );
    }

    public static function settings($public = true) {
        $saved = get_option(self::OPTION, array());
        if (!is_array($saved)) $saved = array();
        $settings = array_replace_recursive(self::defaults(), $saved);
        $settings['enabled'] = '1';
        if (!isset($settings['tariff_binding']) || !is_array($settings['tariff_binding'])) $settings['tariff_binding'] = self::defaults()['tariff_binding'];
        if (!isset($settings['seasons']) || !is_array($settings['seasons'])) $settings['seasons'] = array();
        if (!$public) return $settings;
        $all = Parcs_HT_Defaults::all_settings();
        foreach ($settings['seasons'] as $year => &$row) {
            if (!is_array($row)) $row = array();
            $season = $all['seasons'][$year] ?? array();
            $row['published'] = is_array($season) && (string)($season['published'] ?? '0') === '1' ? '1' : '0';
        }
        unset($row);
        return $settings;
    }

    private static function year_from_date($value) {
        return preg_match('/^(20\d{2})-\d{2}-\d{2}$/', trim((string)$value), $match) ? $match[1] : '';
    }

    private static function numeric_price($value) {
        $value = html_entity_decode((string)$value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $value = str_replace(array("\xc2\xa0", ' ', '€'), '', $value);
        $value = str_replace(',', '.', $value);
        return preg_match('/([0-9]+(?:\.[0-9]+)?)/', $value, $match) ? (float)$match[1] : null;
    }

    private static function row_label($row) {
        if (!is_array($row) || !isset($row['label']) || !is_array($row['label'])) return '';
        return trim((string)($row['label']['fr'] ?? ''));
    }

    private static function resolve_row($rows, $binding, $role) {
        $index = isset($binding[$role . '_row']) ? (int)$binding[$role . '_row'] : -1;
        $expected = trim((string)($binding[$role . '_label'] ?? ''));
        if ($index >= 0 && isset($rows[$index]) && is_array($rows[$index]) && ($expected === '' || self::row_label($rows[$index]) === $expected)) return $rows[$index];
        if ($expected !== '') foreach ($rows as $row) if (is_array($row) && self::row_label($row) === $expected) return $row;
        return ($index >= 0 && isset($rows[$index]) && is_array($rows[$index])) ? $rows[$index] : null;
    }

    private static function price_from_row($row, $column) {
        if (!is_array($row) || (string)($row['enabled'] ?? '1') !== '1') return null;
        if (isset($row['cells'][$column]['value'])) return self::numeric_price($row['cells'][$column]['value']);
        if ($column === 'price' && isset($row['price'])) return self::numeric_price($row['price']);
        return null;
    }

    private static function published_season($year, $settings = null) {
        if ($settings === null) $settings = self::settings();
        if ($year === '') return null;
        $all = get_option(Parcs_HT_Defaults::OPTION, array());
        if (!is_array($all) || empty($all['seasons'][$year]) || !is_array($all['seasons'][$year])) return null;
        $season = $all['seasons'][$year];
        if ((string)($season['published'] ?? '0') !== '1') return null;
        $tariffs = isset($season['tariffs']) && is_array($season['tariffs']) ? $season['tariffs'] : array();
        $rows = isset($tariffs['groups']) && is_array($tariffs['groups']) ? array_values($tariffs['groups']) : array();
        if (!$rows) return null;
        $binding = $settings['tariff_binding'];
        $column = sanitize_key($binding['column'] ?? 'price');
        if ($column === '') $column = 'price';
        $result = array('published'=>'1','free_adult_children'=>(string)max(1, (int)($binding['free_adult_children'] ?? 10)));
        foreach (array('child','adult','disability','companion') as $role) {
            $price = self::price_from_row(self::resolve_row($rows, $binding, $role), $column);
            if ($price === null || $price < 0) return null;
            $result[$role] = (string)$price;
        }
        return $result;
    }

    private static function number_value($value) {
        $value = str_replace(',', '.', trim((string)$value));
        return is_numeric($value) && (float)$value >= 0 ? (float)$value : 0.0;
    }

    private static function euro($value) { return number_format((float)$value, 2, ',', ' ') . ' €'; }

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
        $data['devisannee']=$year;
        $data['tarifenfant']=self::euro($row['child']);
        $data['tarifadulte']=self::euro($row['adult']);
        $data['tarifhandicap']=self::euro($row['disability']);
        $data['tarifaccompagnateur']=self::euro($row['companion']);
        $type = is_array($data[$group_field]) ? implode('', $data[$group_field]) : (string)$data[$group_field];
        if ($type === (string)$settings['school_value']) {
            $children=self::number_value($data['nbrenfants']??0); $adults=self::number_value($data['nbradultes']??0);
            $ratio=max(1,(int)$row['free_adult_children']); $free=min($adults,floor($children/$ratio)); $paying=max(0,$adults-$free);
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

    public static function menu() { add_submenu_page(Parcs_HT_Admin::PAGE,'Tarifs des devis groupes','Tarifs devis groupes','manage_options',self::PAGE,array(__CLASS__,'page')); }
    public static function page() {
        if(!current_user_can('manage_options'))return; ?>
        <div class="wrap"><h1>Tarifs des devis groupes</h1><div class="notice notice-info inline"><p>Depuis la version 1.11.0, les devis utilisent directement le tableau <strong>Tarifs → Groupes</strong>. Cette page est conservée uniquement pour compatibilité technique.</p></div><p><a class="button button-primary" href="<?php echo esc_url(add_query_arg(array('page'=>Parcs_HT_Admin::PAGE,'tab'=>'htp-tariffs'),admin_url('admin.php'))); ?>">Ouvrir les tarifs groupes</a></p></div><?php
    }
    public static function save() { if(!current_user_can('manage_options'))wp_die('Accès refusé.');check_admin_referer('parcs_ht_save_group_quotes');wp_safe_redirect(add_query_arg(array('page'=>self::PAGE),admin_url('admin.php')));exit; }

    public static function form_assets($html) {
        $settings=self::settings(false); $field=(string)$settings['visit_field'];
        if(strpos($html,'name="'.$field.'"')!==false||strpos($html,"name='".$field."'")!==false)self::assets();
        return$html;
    }

    public static function assets() {
        if(wp_script_is('parcs-ht-group-quotes','enqueued'))return;
        $settings=self::settings(); $all=Parcs_HT_Defaults::all_settings(); $seasons=array();
        foreach((array)($all['seasons']??array()) as $year=>$season){if(!is_array($season)||(string)($season['published']??'0')!=='1')continue;$row=self::published_season((string)$year,$settings);if($row)$seasons[$year]=array_intersect_key($row,array_flip(array('published','child','adult','disability','companion','free_adult_children')));}
        wp_enqueue_script('parcs-ht-group-quotes',PARCS_HT_URL.'assets/group-quotes.js',array('jquery'),PARCS_HT_VERSION,true);
        wp_add_inline_script('parcs-ht-group-quotes','window.ParcsHTGroupQuotes='.wp_json_encode(array('formId'=>'','visitField'=>(string)$settings['visit_field'],'groupField'=>(string)$settings['group_field'],'schoolValue'=>(string)$settings['school_value'],'disabilityValue'=>(string)$settings['disability_value'],'seasons'=>$seasons,'unavailableMessage'=>'Les tarifs groupes ne sont pas encore disponibles pour cette année. Merci de revenir ultérieurement.')).';','before');
    }
}
