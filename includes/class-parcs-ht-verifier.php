<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Vérification événementielle de la configuration et des rendus publics.
 * Aucun contrôle quotidien : après enregistrement, après changement de version,
 * ou à la demande d'un administrateur.
 */
final class Parcs_HT_Verifier {
    const RESULT_OPTION = 'parcs_ht_verification_result';
    const VERSION_OPTION = 'parcs_ht_verified_plugin_version';

    public static function init() {
        add_action('admin_init', array(__CLASS__, 'maybe_verify_version_once'));
        add_action('admin_post_parcs_ht_run_verification', array(__CLASS__, 'manual_verification'));
        add_action('admin_notices', array(__CLASS__, 'admin_notice'));
    }

    public static function maybe_verify_version_once() {
        if (!current_user_can('manage_options')) return;
        $last = (string) get_option(self::VERSION_OPTION, '');
        if ($last === PARCS_HT_VERSION) return;
        self::run(Parcs_HT_Defaults::settings(), 'mise à jour de l’extension');
        update_option(self::VERSION_OPTION, PARCS_HT_VERSION, false);
    }

    public static function run($settings, $context = 'manuel') {
        $settings = is_array($settings) ? $settings : Parcs_HT_Defaults::settings();
        $errors = array();
        $warnings = array();
        $checks = array();

        $audit = Parcs_HT_Schedule::audit_season($settings);
        $errors = array_merge($errors, (array)($audit['errors'] ?? array()));
        $warnings = array_merge($warnings, (array)($audit['warnings'] ?? array()));
        $checks[] = array('name'=>'Configuration annuelle','ok'=>empty($audit['errors']));

        self::check_slots($settings, $errors, $warnings);
        $checks[] = array('name'=>'Créneaux et dernières entrées','ok'=>!self::has_prefix($errors, 'Créneau'));

        self::check_public_renderers($errors, $warnings);
        $checks[] = array('name'=>'Accueil et bloc Aujourd’hui','ok'=>!self::has_prefix($errors, 'Affichage'));

        self::check_popups($settings, $errors, $warnings);
        $checks[] = array('name'=>'Pop-up FR / EN / DE','ok'=>!self::has_prefix($errors, 'Pop-up'));

        $result = array(
            'ok'=>empty($errors),
            'checked_at'=>time(),
            'context'=>sanitize_text_field($context),
            'version'=>defined('PARCS_HT_VERSION') ? PARCS_HT_VERSION : '',
            'errors'=>array_values(array_unique($errors)),
            'warnings'=>array_values(array_unique($warnings)),
            'checks'=>$checks,
        );
        update_option(self::RESULT_OPTION, $result, false);
        return $result;
    }

    private static function check_slots($settings, &$errors, &$warnings) {
        $rows = array();
        foreach ((array)($settings['regular_periods'] ?? array()) as $i=>$row) $rows[] = array('label'=>'horaire classique '.($i+1),'row'=>$row);
        foreach ((array)($settings['exceptions'] ?? array()) as $i=>$row) {
            if ((string)($row['type'] ?? 'hours') === 'hours') $rows[] = array('label'=>'horaire exceptionnel '.($i+1),'row'=>$row);
        }
        foreach ($rows as $item) {
            $row = (array)$item['row'];
            if ((string)($row['enabled'] ?? '0') !== '1') continue;
            $label = $item['label'];
            $o1 = self::minutes($row['open'] ?? ''); $c1 = self::minutes($row['close'] ?? '');
            if ($o1 === null || $c1 === null || $o1 >= $c1) {
                $errors[] = 'Créneau invalide dans '.$label.' : ouverture et fermeture du créneau 1 doivent être cohérentes.';
                continue;
            }
            $o2 = self::minutes($row['open2'] ?? ''); $c2 = self::minutes($row['close2'] ?? '');
            if (($o2 === null) !== ($c2 === null)) $errors[] = 'Créneau incomplet dans '.$label.' : le créneau 2 doit avoir une ouverture et une fermeture.';
            if ($o2 !== null && $c2 !== null) {
                if ($o2 >= $c2) $errors[] = 'Créneau invalide dans '.$label.' : fermeture du créneau 2 antérieure à son ouverture.';
                if ($o2 < $c1) $errors[] = 'Créneau en chevauchement dans '.$label.' : le créneau 2 commence avant la fin du créneau 1.';
            }
            foreach (array(1=>array($o1,$c1,'last_entry_minutes'),2=>array($o2,$c2,'last_entry_minutes2')) as $n=>$data) {
                if ($data[0] === null || $data[1] === null) continue;
                $delay = isset($row[$data[2]]) && $row[$data[2]] !== '' ? (int)$row[$data[2]] : null;
                if ($delay !== null && ($delay < 0 || $delay >= ($data[1]-$data[0]))) $warnings[] = 'Dernière entrée inhabituelle dans '.$label.' (créneau '.$n.').';
            }
        }
    }

    private static function check_public_renderers(&$errors, &$warnings) {
        foreach (array('fr','en','de') as $lang) {
            $home = do_shortcode('[parc_horaire_accueil_'.$lang.']');
            $today = do_shortcode('[parc_horaires_aujourdhui_'.$lang.']');
            $status = do_shortcode('[parc_statut_'.$lang.']');
            $hour = do_shortcode('[parc_horaire_'.$lang.']');
            if (strpos($home, 'data-htp-component="home-opening"') === false) $errors[] = 'Affichage accueil '.$lang.' : composant horaire introuvable.';
            if (strpos($today, 'data-htp-component="today"') === false) $errors[] = 'Affichage Horaires & Tarifs '.$lang.' : bloc Aujourd’hui introuvable.';
            if (strpos($status, 'data-htp-component="header-status"') === false || strpos($hour, 'data-htp-component="header-hour"') === false) $errors[] = 'Affichage en-tête '.$lang.' : statut ou horaire introuvable.';
            if (strpos($home, 'data-htp-lang="'.$lang.'"') === false || strpos($today, 'data-htp-lang="'.$lang.'"') === false) $warnings[] = 'Affichage '.$lang.' : vérifier le marquage de langue des composants publics.';
        }
    }

    private static function check_popups($settings, &$errors, &$warnings) {
        $sources = array();
        foreach ((array)($settings['alerts'] ?? array()) as $i=>$row) {
            if ((string)($row['enabled'] ?? '0') === '1') $sources[] = array('label'=>'alerte '.($i+1),'title'=>$row['title'] ?? array(),'message'=>$row['message'] ?? array());
        }
        foreach ((array)($settings['exceptions'] ?? array()) as $i=>$row) {
            if ((string)($row['enabled'] ?? '0') === '1' && (string)($row['show_popup'] ?? '0') === '1') $sources[] = array('label'=>'exception '.($i+1),'title'=>$row['title'] ?? array(),'message'=>$row['message'] ?? array(),'context'=>$row['context'] ?? array());
        }
        foreach ((array)($settings['special_periods'] ?? array()) as $i=>$row) {
            if ((string)($row['enabled'] ?? '0') === '1' && (string)($row['show_popup'] ?? '0') === '1') $sources[] = array('label'=>'événement '.($i+1),'title'=>$row['popup_title'] ?? ($row['title'] ?? array()),'message'=>$row['popup_message'] ?? ($row['message'] ?? array()));
        }
        foreach ($sources as $source) {
            foreach (array('fr','en','de') as $lang) {
                $title = trim((string)(is_array($source['title']) ? ($source['title'][$lang] ?? '') : ''));
                $message = trim((string)(is_array($source['message']) ? ($source['message'][$lang] ?? '') : ''));
                $context = trim((string)(isset($source['context']) && is_array($source['context']) ? ($source['context'][$lang] ?? '') : ''));
                if ($title === '' && $message === '' && $context === '') $errors[] = 'Pop-up '.$source['label'].' : aucun contenu en '.strtoupper($lang).'.';
            }
        }
        if (function_exists('qtranxf_getLanguage')) {
            $warnings[] = 'Pop-up : langue pilotée par qTranslate-XT (langue de la page).';
        } else {
            $warnings[] = 'Pop-up : qTranslate-XT non détecté lors du contrôle ; fallback sur la locale WordPress.';
        }
    }

    public static function manual_verification() {
        if (!current_user_can('manage_options')) wp_die('Accès refusé.');
        check_admin_referer('parcs_ht_run_verification');
        self::run(Parcs_HT_Defaults::settings(), 'test manuel');
        wp_safe_redirect(add_query_arg(array('page'=>'parcs-horaires-tarifs','tab'=>'htp-preview','verification'=>'1'), admin_url('admin.php')));
        exit;
    }

    public static function result() {
        $result = get_option(self::RESULT_OPTION, array());
        return is_array($result) ? $result : array();
    }

    public static function admin_notice() {
        if (!current_user_can('manage_options')) return;
        if (!isset($_GET['page']) || sanitize_key(wp_unslash($_GET['page'])) !== 'parcs-horaires-tarifs') return;
        $result = self::result();
        $url = wp_nonce_url(admin_url('admin-post.php?action=parcs_ht_run_verification'), 'parcs_ht_run_verification');
        if (!$result) {
            echo '<div class="notice notice-info"><p><strong>Vérification Horaires & Tarifs :</strong> aucun contrôle enregistré. <a class="button button-small" href="'.esc_url($url).'">Lancer le test</a></p></div>';
            return;
        }
        $ok = !empty($result['ok']);
        $class = $ok ? 'notice-success' : 'notice-error';
        $title = $ok ? 'Tous les contrôles sont OK.' : 'Une anomalie a été détectée.';
        echo '<div class="notice '.$class.'"><p><strong>Vérification Horaires & Tarifs :</strong> '.esc_html($title).' Dernier contrôle : '.esc_html(wp_date('d/m/Y H:i', (int)($result['checked_at'] ?? 0))).' · '.esc_html((string)($result['context'] ?? '')).' <a class="button button-small" href="'.esc_url($url).'">Relancer le test</a></p>';
        if (!$ok && !empty($result['errors'])) echo '<ul><li>'.implode('</li><li>', array_map('esc_html', array_slice((array)$result['errors'],0,8))).'</li></ul>';
        echo '</div>';
    }

    private static function minutes($value) {
        if ($value === '' || $value === null) return null;
        if (!preg_match('/^(\d{1,2}):(\d{2})$/', (string)$value, $m)) return null;
        $h=(int)$m[1]; $min=(int)$m[2];
        if ($h>23 || $min>59) return null;
        return $h*60+$min;
    }

    private static function has_prefix($items, $prefix) {
        foreach ((array)$items as $item) if (strpos((string)$item, $prefix) === 0) return true;
        return false;
    }
}
