<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Supervision légère : diagnostic annuel, notifications anti-spam, historique
 * de sauvegarde et intégration à l'outil Santé du site.
 */
final class Parcs_HT_Health {
    const CRON_HOOK = 'parcs_ht_daily_health_check';
    const NOTIFICATION_OPTION = 'parcs_ht_last_health_notification';
    const RUNTIME_ERRORS_OPTION = 'parcs_ht_runtime_errors';
    const REVISIONS_OPTION = 'parcs_ht_settings_revisions';
    const MAX_REVISIONS = 10;
    const REMINDER_SECONDS = 7 * DAY_IN_SECONDS;

    public static function init() {
        add_action(self::CRON_HOOK, array(__CLASS__, 'daily_check'));
        add_filter('site_status_tests', array(__CLASS__, 'site_health_tests'));
        add_filter('debug_information', array(__CLASS__, 'debug_information'));
        if (!wp_next_scheduled(self::CRON_HOOK)) {
            wp_schedule_event(time() + HOUR_IN_SECONDS, 'daily', self::CRON_HOOK);
        }
    }

    public static function deactivate() {
        $timestamp = wp_next_scheduled(self::CRON_HOOK);
        if ($timestamp) wp_unschedule_event($timestamp, self::CRON_HOOK);
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
        $revisions = array_slice($revisions, 0, self::MAX_REVISIONS);
        update_option(self::REVISIONS_OPTION, $revisions, false);
    }

    public static function revisions() {
        $revisions = get_option(self::REVISIONS_OPTION, array());
        return is_array($revisions) ? $revisions : array();
    }

    public static function notify_audit($settings, $context = 'save') {
        if (!is_array($settings)) return false;
        $general = is_array($settings['general'] ?? null) ? $settings['general'] : array();
        if ((string)($general['health_notifications_enabled'] ?? '1') !== '1') return false;
        $report = Parcs_HT_Schedule::audit_season($settings);
        $problems = array_merge((array)$report['errors'], (array)$report['warnings']);
        $timezone = Parcs_HT_Schedule::timezone($settings);
        $today = wp_date('Y-m-d', null, new DateTimeZone($timezone));
        $season_end = (string)($general['season_end'] ?? $settings['season_end'] ?? '');
        $future_published = false;
        foreach ((array)($settings['seasons'] ?? array()) as $season) {
            if (is_array($season) && (string)($season['published'] ?? '0') === '1' && (string)($season['season_end'] ?? '') > $season_end) { $future_published = true; break; }
        }
        if ($season_end !== '' && !$future_published) {
            try {
                $end_date = new DateTimeImmutable($season_end, new DateTimeZone($timezone));
                $today_date = new DateTimeImmutable($today, new DateTimeZone($timezone));
                $days_left = (int)$today_date->diff($end_date)->format('%r%a');
                if ($days_left < 0) $problems[] = 'La saison publiée est terminée et aucune saison suivante publiée n’est disponible.';
                elseif ($days_left <= 30) $problems[] = 'La saison se termine dans '.$days_left.' jour(s) et aucune saison suivante n’est encore publiée.';
            } catch (Exception $e) {}
        }
        if (!$problems) {
            delete_option(self::NOTIFICATION_OPTION);
            return false;
        }
        $park = Parcs_HT_Schedule::translation($general['park_name'] ?? array(), 'fr', get_bloginfo('name'));
        $year = (string)($settings['active_season_year'] ?? $general['year'] ?? '');
        $subject = sprintf('[%s] Problème détecté dans le planning%s', $park ?: get_bloginfo('name'), $year !== '' ? ' '.$year : '');
        $lines = array(
            'Le contrôle automatique de l’extension Horaires & Tarifs Parc a détecté un problème.',
            '',
        );
        foreach (array_slice($problems, 0, 25) as $problem) $lines[] = '• '.$problem;
        if (count($problems) > 25) $lines[] = '• '.(count($problems) - 25).' autre(s) problème(s) sont visibles dans l’administration.';
        $lines[] = '';
        $lines[] = 'Contexte : '.sanitize_text_field($context);
        $lines[] = 'Vérifier : '.admin_url('admin.php?page=parcs-horaires-tarifs&season='.rawurlencode($year).'&tab=htp-preview');
        return self::send_once($subject, implode("\n", $lines), $general, $problems);
    }

    public static function report_runtime_error($code, $message, $details = '') {
        $code = sanitize_key($code);
        $message = sanitize_text_field($message);
        if ($code === '' || $message === '') return;
        $errors = get_option(self::RUNTIME_ERRORS_OPTION, array());
        if (!is_array($errors)) $errors = array();
        $errors[$code] = array(
            'message'=>$message,
            'details'=>sanitize_textarea_field($details),
            'time'=>time(),
        );
        uasort($errors, static function ($a, $b) { return (int)($b['time'] ?? 0) - (int)($a['time'] ?? 0); });
        update_option(self::RUNTIME_ERRORS_OPTION, array_slice($errors, 0, 20, true), false);

        $settings = Parcs_HT_Defaults::settings();
        $general = (array)($settings['general'] ?? array());
        if ((string)($general['health_notifications_enabled'] ?? '1') !== '1') return;
        $subject = '['.get_bloginfo('name').'] Erreur Horaires & Tarifs Parc';
        self::send_once($subject, $message."\n\n".$details."\n\n".admin_url('site-health.php'), $general, array($code, $message));
    }

    private static function send_once($subject, $body, $general, $fingerprint_data) {
        $fingerprint = hash('sha256', wp_json_encode($fingerprint_data));
        $last = get_option(self::NOTIFICATION_OPTION, array());
        if (is_array($last) && ($last['fingerprint'] ?? '') === $fingerprint && time() - (int)($last['sent_at'] ?? 0) < self::REMINDER_SECONDS) return false;
        $email = sanitize_email((string)($general['health_notification_email'] ?? ''));
        if ($email === '') $email = sanitize_email((string)get_option('admin_email'));
        if ($email === '') return false;
        $sent = wp_mail($email, wp_specialchars_decode($subject, ENT_QUOTES), $body);
        if ($sent) {
            update_option(self::NOTIFICATION_OPTION, array('fingerprint'=>$fingerprint,'sent_at'=>time(),'email'=>$email), false);
        }
        return (bool)$sent;
    }

    public static function daily_check() {
        $settings = Parcs_HT_Defaults::settings();
        self::notify_audit($settings, 'contrôle quotidien');
    }

    public static function site_health_tests($tests) {
        if (!isset($tests['direct']) || !is_array($tests['direct'])) $tests['direct'] = array();
        $tests['direct']['parcs_ht_schedule'] = array(
            'label'=>'Horaires & Tarifs Parc',
            'test'=>array(__CLASS__, 'site_health_result'),
        );
        return $tests;
    }

    public static function site_health_result() {
        $settings = Parcs_HT_Defaults::settings();
        $report = Parcs_HT_Schedule::audit_season($settings);
        $uploads = wp_upload_dir();
        $writable = empty($uploads['error']) && wp_is_writable($uploads['basedir']);
        $problems = array_merge((array)$report['errors'], (array)$report['warnings']);
        if (!$writable) $problems[] = 'Le dossier des médias n’est pas accessible en écriture pour le cache PDF.';
        if (defined('DISABLE_WP_CRON') && DISABLE_WP_CRON) $problems[] = 'WP-Cron est désactivé : vérifiez qu’une tâche cron serveur le remplace pour les contrôles et PDF automatiques.';
        if (class_exists('Parcs_HT_Updater') && !Parcs_HT_Updater::has_token()) $problems[] = 'La clé GitHub de lecture n’est pas configurée ; les mises à jour privées ne seront pas détectées.';
        $runtime = get_option(self::RUNTIME_ERRORS_OPTION, array());
        if (is_array($runtime)) {
            foreach ($runtime as $error) {
                if (time() - (int)($error['time'] ?? 0) <= 7 * DAY_IN_SECONDS) $problems[] = (string)($error['message'] ?? 'Erreur récente de l’extension.');
            }
        }
        return array(
            'label'=>$problems ? 'Le planning du parc demande une vérification' : 'Le planning du parc est cohérent',
            'status'=>$problems ? 'recommended' : 'good',
            'badge'=>array('label'=>'Horaires du parc','color'=>'blue'),
            'description'=>'<p>'.esc_html($problems ? implode(' ', array_slice($problems, 0, 5)) : 'Le diagnostic annuel, le cache PDF et les règles prioritaires ne signalent aucun problème.').'</p>',
            'actions'=>'<p><a href="'.esc_url(admin_url('admin.php?page=parcs-horaires-tarifs&tab=htp-preview')).'">Ouvrir le diagnostic du planning</a></p>',
            'test'=>'parcs_ht_schedule',
        );
    }

    public static function debug_information($info) {
        $settings = Parcs_HT_Defaults::settings();
        $report = Parcs_HT_Schedule::audit_season($settings);
        $last = get_option(self::NOTIFICATION_OPTION, array());
        $info['parcs-ht'] = array(
            'label'=>'Horaires & Tarifs Parc',
            'description'=>'État technique de l’extension, sans donnée sensible.',
            'fields'=>array(
                'version'=>array('label'=>'Version','value'=>defined('PARCS_HT_VERSION') ? PARCS_HT_VERSION : ''),
                'season'=>array('label'=>'Saison active','value'=>(string)($settings['active_season_year'] ?? '')),
                'audit'=>array('label'=>'Diagnostic','value'=>$report['valid'] && !$report['warnings'] ? 'OK' : count($report['errors']).' erreur(s), '.count($report['warnings']).' avertissement(s)'),
                'cron'=>array('label'=>'Contrôle quotidien','value'=>wp_next_scheduled(self::CRON_HOOK) ? 'Planifié' : 'Non planifié'),
                'github'=>array('label'=>'Accès GitHub','value'=>class_exists('Parcs_HT_Updater') ? (Parcs_HT_Updater::has_token() ? 'Configuré ('.Parcs_HT_Updater::token_source().')' : 'Non configuré') : 'Non chargé'),
                'notification'=>array('label'=>'Dernier e-mail','value'=>is_array($last) && !empty($last['sent_at']) ? wp_date('Y-m-d H:i', (int)$last['sent_at']) : 'Aucun'),
            ),
        );
        return $info;
    }
}
