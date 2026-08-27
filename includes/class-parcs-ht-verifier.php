<?php

if (!defined('ABSPATH')) { exit; }

/** Vérification événementielle et validation d'administration. */
final class Parcs_HT_Verifier {
    const RESULT_OPTION = 'parcs_ht_verification_result';
    const VERSION_OPTION = 'parcs_ht_verified_plugin_version';

    public static function init() {
        add_action('admin_init', array(__CLASS__, 'maybe_verify_version_once'));
        add_action('admin_post_parcs_ht_run_verification', array(__CLASS__, 'manual_verification'));
        add_action('admin_notices', array(__CLASS__, 'admin_notice'));
        add_action('admin_enqueue_scripts', array(__CLASS__, 'admin_assets'), 60);
        add_filter('pre_update_option_' . Parcs_HT_Defaults::OPTION, array(__CLASS__, 'save_languages'), 30, 3);
    }

    public static function active_languages($settings = null) {
        if (!is_array($settings)) $settings = Parcs_HT_Defaults::all_settings();
        $langs = isset($settings['languages']) && is_array($settings['languages']) ? array_values(array_intersect(array('fr','en','de'), array_map('strval', $settings['languages']))) : array('fr','en','de');
        if (!in_array('fr', $langs, true)) array_unshift($langs, 'fr');
        return array_values(array_unique($langs));
    }

    public static function save_languages($new_value, $old_value, $option) {
        unset($option);
        if (!is_array($new_value)) return $new_value;
        if (!isset($_POST['parcs_ht_languages_present'])) {
            if (is_array($old_value) && isset($old_value['languages'])) $new_value['languages'] = $old_value['languages'];
            return $new_value;
        }
        $posted = isset($_POST['parcs_ht_languages']) && is_array($_POST['parcs_ht_languages']) ? array_map('sanitize_key', wp_unslash($_POST['parcs_ht_languages'])) : array();
        $langs = array('fr');
        foreach (array('en','de') as $lang) if (in_array($lang, $posted, true)) $langs[] = $lang;
        $new_value['languages'] = $langs;
        return $new_value;
    }

    public static function admin_assets($hook) {
        if ($hook !== 'toplevel_page_parcs-horaires-tarifs') return;
        $settings = Parcs_HT_Defaults::all_settings();
        wp_enqueue_script('parcs-ht-admin-validation', PARCS_HT_URL . 'assets/admin-validation.js', array('parcs-ht-admin'), PARCS_HT_VERSION, true);
        wp_add_inline_script('parcs-ht-admin-validation', 'window.ParcsHTValidation=' . wp_json_encode(array('languages'=>self::active_languages($settings))) . ';', 'before');
    }

    public static function maybe_verify_version_once() {
        if (!current_user_can('manage_options')) return;
        $last = (string)get_option(self::VERSION_OPTION, '');
        if ($last === PARCS_HT_VERSION) return;
        self::run(Parcs_HT_Defaults::settings(), 'mise à jour de l’extension');
        update_option(self::VERSION_OPTION, PARCS_HT_VERSION, false);
    }

    public static function run($settings, $context = 'manuel') {
        $settings = is_array($settings) ? $settings : Parcs_HT_Defaults::settings();
        $errors=array(); $warnings=array(); $checks=array();
        $audit=Parcs_HT_Schedule::audit_season($settings);
        $errors=array_merge($errors,(array)($audit['errors']??array()));
        $warnings=array_merge($warnings,(array)($audit['warnings']??array()));
        $checks[]=array('name'=>'Configuration annuelle','ok'=>empty($audit['errors']));
        self::check_required_titles($settings,$errors);
        self::check_slots($settings,$errors,$warnings);
        $checks[]=array('name'=>'Créneaux, titres et dernières entrées','ok'=>!self::has_prefix($errors,array('Créneau','Titre')));
        self::check_public_renderers($settings,$errors,$warnings);
        $checks[]=array('name'=>'Accueil et bloc Aujourd’hui','ok'=>!self::has_prefix($errors,array('Affichage')));
        self::check_popups($settings,$errors,$warnings);
        $checks[]=array('name'=>'Pop-up langues publiques','ok'=>!self::has_prefix($errors,array('Pop-up')));
        $result=array('ok'=>empty($errors),'checked_at'=>time(),'context'=>sanitize_text_field($context),'version'=>defined('PARCS_HT_VERSION')?PARCS_HT_VERSION:'','errors'=>array_values(array_unique($errors)),'warnings'=>array_values(array_unique($warnings)),'checks'=>$checks);
        update_option(self::RESULT_OPTION,$result,false); return $result;
    }

    private static function row_name($kind,$row,$index) {
        $title='';
        foreach (array('title','context') as $key) if ($title==='' && isset($row[$key]['fr'])) $title=trim((string)$row[$key]['fr']);
        if ($title==='' && !empty($row['internal_label'])) $title=trim((string)$row['internal_label']);
        $dates=''; if(!empty($row['start'])||!empty($row['end']))$dates=trim((string)($row['start']??'').' → '.(string)($row['end']??''),' →');
        return $kind.' #'.($index+1).($title!==''?' « '.$title.' »':'').($dates!==''?' ('.$dates.')':'');
    }

    private static function check_required_titles($settings,&$errors) {
        $langs=self::active_languages($settings);
        foreach ((array)($settings['special_periods']??array()) as $i=>$row) {
            if ((string)($row['enabled']??'0')!=='1') continue;
            $name=self::row_name('Période / événement',$row,$i); $titles=(array)($row['title']??array());
            if(trim((string)($titles['fr']??''))==='')$errors[]='Titre obligatoire manquant : '.$name.' → titre FR.';
            $public=(string)($row['show_on_calendar']??'1')==='1'||(string)($row['show_popup']??'0')==='1';
            if($public)foreach($langs as $lang)if($lang!=='fr'&&trim((string)($titles[$lang]??''))==='')$errors[]='Titre public manquant : '.$name.' → '.strtoupper($lang).' est une langue active.';
        }
        foreach ((array)($settings['exceptions']??array()) as $i=>$row) {
            if ((string)($row['enabled']??'0')!=='1') continue;
            $name=self::row_name('Exception',$row,$i); $titles=(array)($row['title']??array());
            if(trim((string)($titles['fr']??''))==='')$errors[]='Titre obligatoire manquant : '.$name.' → titre FR.';
            $public=(string)($row['show_public_marker']??'1')==='1'||(string)($row['show_popup']??'0')==='1';
            if($public)foreach($langs as $lang)if($lang!=='fr'&&trim((string)($titles[$lang]??''))==='')$errors[]='Titre public manquant : '.$name.' → '.strtoupper($lang).' est une langue active.';
        }
    }

    private static function check_slots($settings,&$errors,&$warnings) {
        $groups=array('horaire classique'=>(array)($settings['regular_periods']??array()),'horaire exceptionnel'=>(array)($settings['exceptions']??array()));
        foreach($groups as $kind=>$rows)foreach($rows as $i=>$row){if((string)($row['enabled']??'0')!=='1'||($kind==='horaire exceptionnel'&&(string)($row['type']??'hours')!=='hours'))continue;$label=self::row_name($kind,$row,$i);
            $o1=self::minutes($row['open']??'');$c1=self::minutes($row['close']??'');if($o1===null||$c1===null||$o1>=$c1){$errors[]='Créneau 1 invalide : '.$label.'.';continue;}
            $o2=self::minutes($row['open2']??'');$c2=self::minutes($row['close2']??'');if(($o2===null)!==($c2===null))$errors[]='Créneau 2 incomplet : '.$label.'.';if($o2!==null&&$c2!==null){if($o2>=$c2)$errors[]='Créneau 2 invalide : '.$label.'.';if($o2<$c1)$errors[]='Créneaux en chevauchement : '.$label.'.';}
            foreach(array(1=>array($o1,$c1,'last_entry_minutes_slot1'),2=>array($o2,$c2,'last_entry_minutes_slot2')) as $n=>$d){if($d[0]===null||$d[1]===null)continue;$delay=array_key_exists($d[2],$row)&&$row[$d[2]]!==''?(int)$row[$d[2]]:(array_key_exists('last_entry_minutes',$row)?(int)$row['last_entry_minutes']:null);if($delay!==null&&($delay<0||$delay>=($d[1]-$d[0])))$warnings[]='Dernière entrée à vérifier : '.$label.' → créneau '.$n.' ('.$delay.' min).';}
        }
    }

    private static function check_public_renderers($settings,&$errors,&$warnings) {
        foreach(self::active_languages($settings) as $lang){$home=do_shortcode('[parc_horaire_accueil_'.$lang.']');$today=do_shortcode('[parc_horaires_aujourdhui_'.$lang.']');$status=do_shortcode('[parc_statut_'.$lang.']');$hour=do_shortcode('[parc_horaire_'.$lang.']');if(strpos($home,'data-htp-component="home-opening"')===false)$errors[]='Affichage accueil '.strtoupper($lang).' : composant introuvable.';if(strpos($today,'data-htp-component="today"')===false)$errors[]='Affichage Horaires & Tarifs '.strtoupper($lang).' : bloc Aujourd’hui introuvable.';if(strpos($status,'data-htp-component="header-status"')===false||strpos($hour,'data-htp-component="header-hour"')===false)$errors[]='Affichage en-tête '.strtoupper($lang).' : statut ou horaire introuvable.';}
    }

    private static function check_popups($settings,&$errors,&$warnings) {
        $langs=self::active_languages($settings);$sources=array();
        foreach((array)($settings['alerts']??array()) as $i=>$row)if((string)($row['enabled']??'0')==='1')$sources[]=array('label'=>self::row_name('Alerte',$row,$i),'title'=>$row['title']??array(),'message'=>$row['message']??array());
        foreach((array)($settings['exceptions']??array()) as $i=>$row)if((string)($row['enabled']??'0')==='1'&&(string)($row['show_popup']??'0')==='1')$sources[]=array('label'=>self::row_name('Exception',$row,$i),'title'=>$row['title']??array(),'message'=>$row['message']??array(),'context'=>$row['context']??array());
        foreach((array)($settings['special_periods']??array()) as $i=>$row)if((string)($row['enabled']??'0')==='1'&&(string)($row['show_popup']??'0')==='1')$sources[]=array('label'=>self::row_name('Période / événement',$row,$i),'title'=>$row['popup_title']??($row['title']??array()),'message'=>$row['popup_message']??($row['message']??array()));
        foreach($sources as $s)foreach($langs as $lang){$title=trim((string)(is_array($s['title'])?($s['title'][$lang]??''):''));$message=trim((string)(is_array($s['message'])?($s['message'][$lang]??''):''));$context=trim((string)(isset($s['context'])&&is_array($s['context'])?($s['context'][$lang]??''):''));if($title===''&&$message===''&&$context==='')$errors[]='Pop-up '.$s['label'].' : aucun contenu en '.strtoupper($lang).'.';}
        $warnings[]=function_exists('qtranxf_getLanguage')?'Pop-up : langue de page pilotée par qTranslate-XT.':'Pop-up : qTranslate-XT non détecté ; fallback sur la locale WordPress.';
    }

    public static function manual_verification(){if(!current_user_can('manage_options'))wp_die('Accès refusé.');check_admin_referer('parcs_ht_run_verification');self::run(Parcs_HT_Defaults::settings(),'test manuel');wp_safe_redirect(add_query_arg(array('page'=>'parcs-horaires-tarifs','tab'=>'htp-preview','verification'=>'1'),admin_url('admin.php')));exit;}
    public static function result(){$r=get_option(self::RESULT_OPTION,array());return is_array($r)?$r:array();}
    public static function admin_notice(){if(!current_user_can('manage_options')||!isset($_GET['page'])||sanitize_key(wp_unslash($_GET['page']))!=='parcs-horaires-tarifs')return;$r=self::result();$url=wp_nonce_url(admin_url('admin-post.php?action=parcs_ht_run_verification'),'parcs_ht_run_verification');if(!$r){echo '<div class="notice notice-info"><p><strong>Vérification :</strong> aucun contrôle enregistré. <a class="button button-small" href="'.esc_url($url).'">Lancer le test</a></p></div>';return;}$ok=!empty($r['ok']);echo '<div class="notice '.($ok?'notice-success':'notice-error').'"><p><strong>Vérification :</strong> '.esc_html($ok?'Tous les contrôles sont OK.':'Une anomalie a été détectée.').' Dernier contrôle : '.esc_html(wp_date('d/m/Y H:i',(int)($r['checked_at']??0))).' · '.esc_html((string)($r['context']??'')).' <a class="button button-small" href="'.esc_url($url).'">Relancer le test</a></p>';if(!$ok&&!empty($r['errors']))echo '<ul><li>'.implode('</li><li>',array_map('esc_html',array_slice((array)$r['errors'],0,12))).'</li></ul>';echo '</div>';}
    private static function minutes($v){if($v===''||$v===null)return null;if(!preg_match('/^(\d{1,2}):(\d{2})$/',(string)$v,$m))return null;$h=(int)$m[1];$min=(int)$m[2];return($h>23||$min>59)?null:$h*60+$min;}
    private static function has_prefix($items,$prefixes){foreach((array)$items as $item)foreach((array)$prefixes as $prefix)if(strpos((string)$item,$prefix)===0)return true;return false;}
}
