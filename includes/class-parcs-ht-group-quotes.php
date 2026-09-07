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
            // Conservé uniquement pour migration/retour arrière. Le moteur 1.13+ ne l'utilise jamais pour calculer un devis.
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
        foreach ($settings['seasons'] as $year => &$row) {
            if (!is_array($row)) $row = array();
            $row['published'] = class_exists('Parcs_HT_Group_Tariff_Settings') && Parcs_HT_Group_Tariff_Settings::is_published((string)$year) ? '1' : '0';
        }
        unset($row);
        return $settings;
    }

    public static function binding_for_year($year, $settings = null) {
        if ($settings === null) $settings = self::settings(false);
        $binding = isset($settings['tariff_bindings'][$year]) && is_array($settings['tariff_bindings'][$year]) ? $settings['tariff_bindings'][$year] : array();
        if (!class_exists('Parcs_HT_Tariff_Identities')) return null;
        if (!Parcs_HT_Tariff_Identities::is_column_id($binding['column_id'] ?? '')) return null;
        foreach (array('child','adult','disability','companion') as $role) {
            if (!Parcs_HT_Tariff_Identities::is_row_id($binding[$role . '_row_id'] ?? '')) return null;
        }
        $binding['free_adult_children'] = (string)max(1, (int)($binding['free_adult_children'] ?? 10));
        $binding['free_adult_round_threshold'] = (string)max(1, min((int)$binding['free_adult_children'], (int)($binding['free_adult_round_threshold'] ?? 5)));
        return $binding;
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

    private static function price_from_row($row, $column_id) {
        if (!is_array($row) || (string)($row['enabled'] ?? '1') !== '1') return null;
        if (!isset($row['cells'][$column_id]) || !is_array($row['cells'][$column_id])) return null;
        return self::numeric_price($row['cells'][$column_id]['value'] ?? '');
    }

    /**
     * Diagnostic complet d'une année de devis. Cette méthode et le moteur de
     * calcul lisent exactement la même grille canonique et la même liaison.
     */
    public static function readiness($year, $settings = null) {
        $year = (string)$year;
        $out = array(
            'ready'=>false,'year'=>$year,'message'=>'Configuration incomplète.',
            'season_exists'=>false,'season_published'=>false,'groups_published'=>false,
            'rows_present'=>false,'columns_present'=>false,'binding_valid'=>false,'column_valid'=>false,
            'roles'=>array('child'=>false,'adult'=>false,'disability'=>false,'companion'=>false),
            'prices'=>array(),
        );
        if (!preg_match('/^20\d{2}$/', $year)) { $out['message'] = 'Année invalide.'; return $out; }
        if ($settings === null) $settings = self::settings(false);
        $all = get_option(Parcs_HT_Defaults::OPTION, array());
        if (!is_array($all) || empty($all['seasons'][$year]) || !is_array($all['seasons'][$year])) { $out['message'] = 'Saison absente.'; return $out; }
        $out['season_exists'] = true;
        $season = $all['seasons'][$year];
        $out['season_published'] = (string)($season['published'] ?? '0') === '1';
        if (!$out['season_published']) { $out['message'] = 'La saison est encore en brouillon.'; return $out; }
        $out['groups_published'] = class_exists('Parcs_HT_Group_Tariff_Settings') && Parcs_HT_Group_Tariff_Settings::is_published($year);
        if (!$out['groups_published']) { $out['message'] = 'Les tarifs groupes ne sont pas publiés.'; return $out; }

        $tariffs = isset($season['tariffs']) && is_array($season['tariffs']) ? $season['tariffs'] : array();
        $rows = isset($tariffs['groups']) && is_array($tariffs['groups']) ? array_values($tariffs['groups']) : array();
        $columns = isset($tariffs['columns']['groups']) && is_array($tariffs['columns']['groups']) ? array_values($tariffs['columns']['groups']) : array();
        $out['rows_present'] = !empty($rows);
        $out['columns_present'] = !empty($columns);
        if (!$rows) { $out['message'] = 'Aucune ligne de tarifs groupes.'; return $out; }
        if (!$columns) { $out['message'] = 'Aucune colonne de prix groupes.'; return $out; }
        if (!class_exists('Parcs_HT_Tariff_Identities')) { $out['message'] = 'Registre des identifiants tarifaires indisponible.'; return $out; }

        $binding = self::binding_for_year($year, $settings);
        $out['binding_valid'] = is_array($binding);
        if (!$binding) { $out['message'] = 'La liaison avec le devis n’est pas complète.'; return $out; }
        $out['column_valid'] = Parcs_HT_Tariff_Identities::column_exists($columns, $binding['column_id']);
        if (!$out['column_valid']) { $out['message'] = 'La colonne de prix liée au devis n’existe plus.'; return $out; }

        foreach (array('child','adult','disability','companion') as $role) {
            $row = Parcs_HT_Tariff_Identities::row_by_id($rows, $binding[$role . '_row_id']);
            if (!$row) { $out['message'] = 'Une ligne liée au devis n’existe plus : ' . $role . '.'; return $out; }
            $price = self::price_from_row($row, $binding['column_id']);
            if ($price === null || $price < 0) { $out['message'] = 'Un prix lié au devis est vide ou invalide : ' . $role . '.'; return $out; }
            $out['roles'][$role] = true;
            $out['prices'][$role] = (string)$price;
        }
        if ((int)$binding['free_adult_children'] < 1 || (int)$binding['free_adult_round_threshold'] < 1 || (int)$binding['free_adult_round_threshold'] > (int)$binding['free_adult_children']) {
            $out['message'] = 'La règle de gratuité scolaire est invalide.';
            return $out;
        }
        $out['ready'] = true;
        $out['message'] = 'Devis prêt pour ' . $year . '.';
        $out['free_adult_children'] = $binding['free_adult_children'];
        $out['free_adult_round_threshold'] = $binding['free_adult_round_threshold'];
        return $out;
    }

    /** Retourne uniquement les données canoniques utilisables par le calcul. */
    public static function canonical_season($year, $settings = null) {
        $status = self::readiness($year, $settings);
        if (empty($status['ready'])) return null;
        return array(
            'published'=>'1',
            'child'=>$status['prices']['child'],
            'adult'=>$status['prices']['adult'],
            'disability'=>$status['prices']['disability'],
            'companion'=>$status['prices']['companion'],
            'free_adult_children'=>$status['free_adult_children'],
            'free_adult_round_threshold'=>$status['free_adult_round_threshold'],
        );
    }

    private static function number_value($value) {
        $value = str_replace(',', '.', trim((string)$value));
        return is_numeric($value) && (float)$value >= 0 ? (float)$value : 0.0;
    }

    private static function euro($value) { return number_format((float)$value, 2, ',', ' ') . ' €'; }

    /**
     * Règle scolaire : 1 gratuité par tranche, avec arrondi à la tranche
     * supérieure lorsque le reliquat atteint le seuil configuré.
     * Exemple officiel actuel : ratio 10, seuil 5 => 26 enfants = 3 gratuités.
     */
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
        $row = self::canonical_season($year, $settings);
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
        if ($year === '' || self::canonical_season($year, $settings)) return $result;
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
        foreach((array)($all['seasons']??array()) as $year=>$season){if(!is_array($season))continue;$row=self::canonical_season((string)$year,$settings);if($row)$seasons[$year]=array_intersect_key($row,array_flip(array('published','child','adult','disability','companion','free_adult_children','free_adult_round_threshold')));}
        wp_enqueue_script('parcs-ht-group-quotes',PARCS_HT_URL.'assets/group-quotes.js',array('jquery'),PARCS_HT_VERSION,true);
        wp_add_inline_script('parcs-ht-group-quotes','window.ParcsHTGroupQuotes='.wp_json_encode(array('formId'=>'','visitField'=>(string)$settings['visit_field'],'groupField'=>(string)$settings['group_field'],'schoolValue'=>(string)$settings['school_value'],'disabilityValue'=>(string)$settings['disability_value'],'seasons'=>$seasons,'unavailableMessage'=>'Les tarifs groupes ne sont pas encore disponibles pour cette année. Merci de revenir ultérieurement.')).';','before');
    }
}
