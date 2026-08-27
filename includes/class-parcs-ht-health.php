<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Santé technique et historique de sécurité.
 * Depuis 1.9.0, aucun contrôle quotidien n'est planifié : la vérification
 * fonctionnelle est gérée par Parcs_HT_Verifier après enregistrement,
 * après changement de version ou à la demande.
 */
final class Parcs_HT_Health {
    const CRON_HOOK = 'parcs_ht_daily_health_check'; // conservé uniquement pour nettoyer les anciens cron.
    const RUNTIME_ERRORS_OPTION = 'parcs_ht_runtime_errors';
    const REVISIONS_OPTION = 'parcs_ht_settings_revisions';
    const MAX_REVISIONS = 10;

    public static function init() {
        self::remove_legacy_daily_cron();
        add_filter('site_status_tests', array(__CLASS__, 'site_health_tests'));
        add_filter('debug_information', array(__CLASS__, 'debug_information'));
    }

    public static function deactivate() {
        self::remove_legacy_daily_cron();
    }

    private static function remove_legacy_daily_cron() {
        $timestamp = wp_next_scheduled(self::CRON_HOOK);
        while ($timestamp) {
            wp_unschedule_event($timestamp, self::CRON_HOOK);
            $timestamp = wp_next_scheduled(self::CRON_HOOK);
        }
    }

    public static function store_revision($settings, $year, $reason = 'Enregistrement') {
        if (!is_array($settings) || empty($settings)) return;
        $revisions = get_option(self::REVISIONS_OPTION, array());
        if (!is_array($revisions)) $revisions = array();
        $snapshot = array(
            'created_at'=>time(),
            'year'=>(string)$year,
            'user_id'=>function_exists('get_current_user_id') ? (int)get_current_user_id() : 0,
            'reason'=>sanitize_text_field($reason),
            'settings'=>$settings,
        );
        $last = $revisions ? reset($revisions) : null;
        if (is_array($last) && isset($last['settings']) && hash('sha256', wp_json_encode($last['settings'])) === hash('sha256', wp_json_encode($settings))) return;
        array_unshift($revisions, $snapshot);
        update_option(self::REVISIONS_OPTION, array_slice($revisions, 0, self::MAX_REVISIONS), false);
    }

    public static function revisions() {
        $revisions = get_option(self::REVISIONS_OPTION, array());
        return is_array($revisions) ? $revisions : array();
    }

    /**
     * Compatibilité avec le code d'enregistrement historique : ce point d'entrée
     * déclenche désormais la vérification événementielle, sans e-mail quotidien.
     */
    public static function notify_audit($settings, $context = 'save') {
        if (class_exists('Parcs_HT_Verifier')) {
            $result = Parcs_HT_Verifier::run($settings, $context);
            return empty($result['ok']);
        }
        return false;
    }

    public static function report_runtime_error($code, $message, $details = '') {
        $code = sanitize_key($code);
        $message = sanitize_text_field($message);
        if ($code === '' || $message === '') return;
        $errors = get_option(self::RUNTIME_ERRORS_OPTION, array());
        if (!is_array($errors)) $errors = array();
        $errors[$code] = array('message'=>$message,'details'=>sanitize_textarea_field($details),'time'=>time());
        uasort($errors, static function ($a,$b) { return (int)($b['time'] ?? 0) - (int)($a['time'] ?? 0); });
        update_option(self::RUNTIME_ERRORS_OPTION, array_slice($errors, 0, 20, true), false);
    }

    public static function site_health_tests($tests) {
        if (!isset($tests['direct']) || !is_array($tests['direct'])) $tests['direct'] = array();
        $tests['direct']['parcs_ht_schedule'] = array('label'=>'Horaires & Tarifs Parc','test'=>array(__CLASS__, 'site_health_result'));
        return $tests;
    }

    public static function site_health_result() {
        $settings = Parcs_HT_Defaults::settings();
        $audit = Parcs_HT_Schedule::audit_season($settings);
        $verification = class_exists('Parcs_HT_Verifier') ? Parcs_HT_Verifier::result() : get_option('parcs_ht_verification_result', array());
        $problems = array_merge((array)($audit['errors'] ?? array()), (array)($audit['warnings'] ?? array()));
        if (is_array($verification) && empty($verification['ok'])) $problems = array_merge($problems, (array)($verification['errors'] ?? array()));
        $uploads = wp_upload_dir();
        if (!empty($uploads['error']) || !wp_is_writable($uploads['basedir'])) $problems[] = 'Le dossier des médias n’est pas accessible en écriture pour le cache PDF.';
        if (class_exists('Parcs_HT_Updater') && !Parcs_HT_Updater::has_token()) $problems[] = 'La clé GitHub de lecture n’est pas configurée ; les mises à jour privées ne seront pas détectées.';
        $runtime = get_option(self::RUNTIME_ERRORS_OPTION, array());
        if (is_array($runtime)) foreach ($runtime as $error) if (time()-(int)($error['time'] ?? 0) <= 7*DAY_IN_SECONDS) $problems[] = (string)($error['message'] ?? 'Erreur récente de l’extension.');
        return array(
            'label'=>$problems ? 'Horaires & Tarifs Parc demande une vérification' : 'Horaires & Tarifs Parc est cohérent',
            'status'=>$problems ? 'recommended' : 'good',
            'badge'=>array('label'=>'Horaires du parc','color'=>'blue'),
            'description'=>'<p>'.esc_html($problems ? implode(' ', array_slice(array_unique($problems),0,5)) : 'Le dernier contrôle événementiel et le diagnostic de la saison ne signalent aucun problème.').'</p>',
            'actions'=>'<p><a href="'.esc_url(admin_url('admin.php?page=parcs-horaires-tarifs&tab=htp-preview')).'">Ouvrir le diagnostic et les tests</a></p>',
            'test'=>'parcs_ht_schedule',
        );
    }

    public static function debug_information($info) {
        $settings = Parcs_HT_Defaults::settings();
        $verification = get_option('parcs_ht_verification_result', array());
        $info['parcs-ht'] = array(
            'label'=>'Horaires & Tarifs Parc',
            'description'=>'État technique de l’extension, sans donnée sensible.',
            'fields'=>array(
                'version'=>array('label'=>'Version','value'=>defined('PARCS_HT_VERSION') ? PARCS_HT_VERSION : ''),
                'season'=>array('label'=>'Saison active','value'=>(string)($settings['active_season_year'] ?? '')),
                'verification'=>array('label'=>'Dernière vérification','value'=>is_array($verification) && !empty($verification['checked_at']) ? ((!empty($verification['ok']) ? 'OK' : 'Anomalie').' — '.wp_date('Y-m-d H:i',(int)$verification['checked_at'])) : 'Jamais'),
                'daily'=>array('label'=>'Contrôle quotidien','value'=>'Désactivé depuis 1.9.0'),
                'github'=>array('label'=>'Accès GitHub','value'=>class_exists('Parcs_HT_Updater') ? (Parcs_HT_Updater::has_token() ? 'Configuré ('.Parcs_HT_Updater::token_source().')' : 'Non configuré') : 'Non chargé'),
            ),
        );
        return $info;
    }
}
