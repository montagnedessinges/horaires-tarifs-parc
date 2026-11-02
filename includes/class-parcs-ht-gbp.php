<?php
if (!defined('ABSPATH')) { exit; }

/**
 * Google Business Profile : une fiche connectée par installation WordPress.
 * Désactivé par défaut, sans modification du calendrier ni envoi sans consentement.
 * L'identifiant OAuth et le secret sont spécifiques au projet Google Cloud du parc.
 */
final class Parcs_HT_GBP {
    const OPTION = 'parcs_ht_gbp_connection';
    const CRON = 'parcs_ht_gbp_hourly_sync';
    const QUEUED = 'parcs_ht_gbp_queued_sync';
    const PAGE = 'parcs-ht-google-maps';
    const API = 'https://mybusinessbusinessinformation.googleapis.com/v1/';

    public static function init() {
        add_action('admin_menu', array(__CLASS__,'menu'), 75);
        add_action('admin_post_parcs_ht_gbp_save', array(__CLASS__,'save'));
        add_action('admin_post_parcs_ht_gbp_connect', array(__CLASS__,'connect'));
        add_action('admin_post_parcs_ht_gbp_callback', array(__CLASS__,'callback'));
        add_action('admin_post_parcs_ht_gbp_disconnect', array(__CLASS__,'disconnect'));
        add_action('admin_post_parcs_ht_gbp_sync', array(__CLASS__,'manual_sync'));
        add_action(self::CRON, array(__CLASS__,'automatic_sync'));
        add_action(self::QUEUED, array(__CLASS__,'automatic_sync'));
        add_action('init', array(__CLASS__,'ensure_schedule'));
        add_action('updated_option', array(__CLASS__,'calendar_updated'), 30, 3);
    }

    private static function option() {
        $data = get_option(self::OPTION, array());
        return is_array($data) ? $data : array();
    }

    private static function write($data) {
        update_option(self::OPTION, $data, false);
    }

    private static function redirect($notice = '') {
        $url = add_query_arg(array('page'=>self::PAGE), admin_url('admin.php'));
        if ($notice !== '') $url = add_query_arg('gbp_notice', sanitize_key($notice), $url);
        wp_safe_redirect($url); exit;
    }

    private static function guard($action) {
        if (!current_user_can('manage_options')) wp_die('Accès refusé.');
        check_admin_referer('parcs_ht_gbp_'.$action);
    }

    public static function menu() {
        add_submenu_page('parcs-horaires-tarifs','Synchronisation Google Maps','Google Maps','manage_options',self::PAGE,array(__CLASS__,'page'));
    }

    public static function ensure_schedule() {
        if (!empty(self::option()['enabled']) && !wp_next_scheduled(self::CRON)) {
            wp_schedule_event(time() + 300, 'hourly', self::CRON);
        }
    }

    public static function calendar_updated($option, $old, $value) {
        unset($old,$value);
        if ($option !== Parcs_HT_Defaults::OPTION || empty(self::option()['enabled'])) return;
        if (!wp_next_scheduled(self::QUEUED)) wp_schedule_single_event(time() + 90, self::QUEUED);
    }

    public static function save() {
        self::guard('save');
        // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Nonce validé dans guard('save') ; chaque champ est validé séparément ci-dessous.
        $input = isset($_POST['gbp']) && is_array($_POST['gbp']) ? wp_unslash($_POST['gbp']) : array();
        $data = self::option();
        $id = trim(sanitize_text_field((string)($input['client_id'] ?? '')));
        if ($id !== '' && $id !== ($data['client_id'] ?? '')) {
            $data['client_id'] = $id;
            unset($data['refresh_token']);
            delete_transient('parcs_ht_gbp_access');
            $data['enabled'] = false;
        }
        $secret = trim((string)($input['client_secret'] ?? ''));
        if ($secret !== '') {
            // Champ de mot de passe non prérempli ; ne jamais afficher ni journaliser le secret.
            $data['client_secret'] = $secret;
            unset($data['refresh_token']);
            delete_transient('parcs_ht_gbp_access');
            $data['enabled'] = false;
        }
        $location = sanitize_text_field((string)($input['location'] ?? ''));
        if ($location !== '') {
            if (!preg_match('~^locations/[0-9]+$~', $location)) self::redirect('invalid');
            $list = self::locations();
            if (is_wp_error($list)) {
                self::record_error($list);
                self::redirect('error');
            }
            $allowed = array_column($list, 'name');
            if (!in_array($location, $allowed, true)) self::redirect('invalid');
            $data['location'] = $location;
        }
        // Nouveau consentement explicite requis après l'association de la fiche.
        $enable = isset($input['enabled']) && (string)$input['enabled'] === '1';
        $data['enabled'] = $enable && !empty($data['refresh_token']) && !empty($data['location']);
        self::write($data);
        if (!$data['enabled']) {
            wp_clear_scheduled_hook(self::CRON);
            wp_clear_scheduled_hook(self::QUEUED);
        } else {
            self::ensure_schedule();
            if (!wp_next_scheduled(self::QUEUED)) wp_schedule_single_event(time()+90, self::QUEUED);
        }
        self::redirect('saved');
    }

    private static function callback_url() {
        return admin_url('admin-post.php?action=parcs_ht_gbp_callback');
    }

    public static function connect() {
        self::guard('connect');
        $data = self::option();
        if (empty($data['client_id']) || empty($data['client_secret'])) self::redirect('credentials');
        $state = wp_generate_password(48, false, false);
        set_transient('parcs_ht_gbp_oauth_'.get_current_user_id(), $state, 10 * MINUTE_IN_SECONDS);
        $params = array(
            'client_id'=>$data['client_id'], 'redirect_uri'=>self::callback_url(),
            'response_type'=>'code', 'scope'=>'https://www.googleapis.com/auth/business.manage',
            'access_type'=>'offline', 'prompt'=>'consent', 'state'=>$state,
        );
        wp_redirect('https://accounts.google.com/o/oauth2/v2/auth?'.http_build_query($params, '', '&', PHP_QUERY_RFC3986)); exit;
    }

    public static function callback() {
        if (!current_user_can('manage_options')) wp_die('Accès refusé.');
        $state = isset($_GET['state']) ? sanitize_text_field(wp_unslash($_GET['state'])) : '';
        $expected = get_transient('parcs_ht_gbp_oauth_'.get_current_user_id());
        delete_transient('parcs_ht_gbp_oauth_'.get_current_user_id());
        if (!$expected || !is_string($state) || !hash_equals($expected,$state)) self::redirect('invalid');
        if (!empty($_GET['error'])) self::redirect('denied');
        $code = isset($_GET['code']) ? sanitize_text_field(wp_unslash($_GET['code'])) : '';
        if ($code === '') self::redirect('denied');
        $data = self::option();
        $reply = wp_remote_post('https://oauth2.googleapis.com/token', array(
            'timeout'=>20,
            'body'=>array(
                'code'=>$code,'client_id'=>$data['client_id'] ?? '',
                'client_secret'=>$data['client_secret'] ?? '',
                'redirect_uri'=>self::callback_url(), 'grant_type'=>'authorization_code',
            ),
        ));
        if (is_wp_error($reply) || wp_remote_retrieve_response_code($reply) !== 200) {
            self::record_error(new WP_Error('gbp_oauth','Google a refusé l’autorisation OAuth.'));
            self::redirect('error');
        }
        $tokens = json_decode(wp_remote_retrieve_body($reply), true);
        if (empty($tokens['refresh_token'])) {
            self::record_error(new WP_Error('gbp_oauth','Jeton de renouvellement absent. Vérifiez le consentement OAuth.'));
            self::redirect('error');
        }
        $data['refresh_token'] = (string)$tokens['refresh_token'];
        $data['enabled'] = false; // Sélection de la bonne fiche et activation ensuite.
        $data['location'] = '';
        $data['last_error'] = '';
        self::write($data);
        delete_transient('parcs_ht_gbp_access');
        self::redirect('connected');
    }

    public static function disconnect() {
        self::guard('disconnect');
        $data = self::option();
        unset($data['refresh_token'], $data['location'], $data['last_error']);
        $data['enabled'] = false;
        self::write($data);
        delete_transient('parcs_ht_gbp_access');
        wp_clear_scheduled_hook(self::CRON);
        wp_clear_scheduled_hook(self::QUEUED);
        self::redirect('disconnected');
    }

    private static function token() {
        $cached = get_transient('parcs_ht_gbp_access');
        if (is_string($cached) && $cached !== '') return $cached;
        $data = self::option();
        if (empty($data['refresh_token']) || empty($data['client_id']) || empty($data['client_secret'])) {
            return new WP_Error('gbp_auth','La connexion Google doit être autorisée.');
        }
        $reply = wp_remote_post('https://oauth2.googleapis.com/token', array(
            'timeout'=>20,
            'body'=>array(
                'client_id'=>$data['client_id'],
                'client_secret'=>$data['client_secret'],
                'refresh_token'=>$data['refresh_token'],
                'grant_type'=>'refresh_token',
            ),
        ));
        if (is_wp_error($reply)) return $reply;
        if (wp_remote_retrieve_response_code($reply) !== 200) return new WP_Error('gbp_token','Google refuse le renouvellement du jeton. Reconnectez la fiche.');
        $response = json_decode(wp_remote_retrieve_body($reply), true);
        if (empty($response['access_token'])) return new WP_Error('gbp_token','La réponse OAuth ne contient pas de jeton.');
        $ttl = max(60,min(3300,(int)($response['expires_in'] ?? 3600)-120));
        set_transient('parcs_ht_gbp_access', (string)$response['access_token'], $ttl);
        return (string)$response['access_token'];
    }

    private static function api($method, $path, $body = null, $params = array()) {
        $token = self::token();
        if (is_wp_error($token)) return $token;
        if (!preg_match('~^(accounts/-/locations|locations/[0-9]+)$~',$path)) {
            return new WP_Error('gbp_path','Chemin Google non autorisé.');
        }
        $url = self::API.$path;
        if ($params) $url = add_query_arg($params, $url);
        $args = array(
            'method'=>$method, 'timeout'=>25,
            'headers'=>array('Authorization'=>'Bearer '.$token, 'Accept'=>'application/json'),
        );
        if ($body !== null) {
            $args['headers']['Content-Type'] = 'application/json';
            $args['body'] = wp_json_encode($body);
        }
        $reply = wp_remote_request($url, $args);
        if (is_wp_error($reply)) return $reply;
        $status = (int)wp_remote_retrieve_response_code($reply);
        if ($status < 200 || $status > 299) {
            // Pas de réponse brute : elle pourrait divulguer des données privées.
            return new WP_Error('gbp_http','Google Business Profile a répondu avec le code HTTP '.$status.'.');
        }
        $payload = json_decode(wp_remote_retrieve_body($reply),true);
        return is_array($payload) ? $payload : array();
    }

    private static function locations() {
        $out = array();
        $page_token = '';
        for ($page=0; $page<10; $page++) {
            $params = array('readMask'=>'name,title','pageSize'=>100);
            if ($page_token !== '') $params['pageToken'] = $page_token;
            $result = self::api('GET', 'accounts/-/locations', null, $params);
            if (is_wp_error($result)) return $result;
            foreach ((array)($result['locations'] ?? array()) as $item) {
                if (preg_match('~^locations/[0-9]+$~', (string)($item['name'] ?? ''))) $out[] = $item;
            }
            $page_token = (string)($result['nextPageToken'] ?? '');
            if ($page_token === '') return $out;
        }
        return new WP_Error('gbp_pagination','La liste des fiches Google est trop longue.');
    }

    /**
     * Conserver les exceptions Google situées hors de la fenêtre gérée.
     * Évite de détruire les futures exceptions saisies directement dans Google.
     */
    private static function preserve_future_specials($remote, $plan) {
        $date = (string)$plan['date'];
        $limit = (new DateTimeImmutable($date, new DateTimeZone('UTC')))
            ->modify('+'.(int)$plan['horizon_days'].' days')->format('Y-m-d');
        $kept = array();
        foreach ((array)($remote['specialHours']['specialHourPeriods'] ?? array()) as $period) {
            if (!is_array($period)) continue;
            $part = $period['startDate'] ?? array();
            if (!is_array($part) || empty($part['year']) || empty($part['month']) || empty($part['day'])) continue;
            $when = sprintf('%04d-%02d-%02d',(int)$part['year'],(int)$part['month'],(int)$part['day']);
            if ($when >= $limit) $kept[] = $period;
        }
        return array_merge((array)$plan['specialHours']['specialHourPeriods'],$kept);
    }

    private static function record_error($error) {
        $data = self::option();
        $data['last_error'] = is_wp_error($error) ? $error->get_error_message() : (string)$error;
        $data['last_checked'] = current_time('mysql');
        self::write($data);
    }

    public static function automatic_sync() {
        self::sync(false);
    }

    public static function manual_sync() {
        self::guard('sync');
        $result = self::sync(true);
        self::redirect(is_wp_error($result) ? 'error' : 'synced');
    }

    public static function sync($force = false) {
        $data = self::option();
        if (empty($data['enabled']) || empty($data['refresh_token']) || empty($data['location'])) {
            return new WP_Error('gbp_off','Synchronisation désactivée ou fiche non connectée.');
        }
        if (get_transient('parcs_ht_gbp_busy')) return new WP_Error('gbp_busy','Une synchronisation est déjà en cours.');
        set_transient('parcs_ht_gbp_busy','1',180);
        try {
            $all = Parcs_HT_Defaults::all_settings();
            $tz = new DateTimeZone(Parcs_HT_Schedule::timezone($all));
            $date = wp_date('Y-m-d', null, $tz);
            $plan = Parcs_HT_GBP_Plan::build($all, $date);
            if (is_wp_error($plan)) { self::record_error($plan); return $plan; }
            $hash = hash('sha256', wp_json_encode($plan));
            if (!$force && $hash === ($data['last_hash'] ?? '') && (time()-(int)($data['last_timestamp'] ?? 0)) < 21600) {
                return array('skipped'=>true,'mode'=>$plan['mode']);
            }
            $location = (string)$data['location'];
            $remote = self::api('GET', $location, null, array('readMask'=>'name,openInfo,regularHours,specialHours,metadata'));
            if (is_wp_error($remote)) { self::record_error($remote); return $remote; }
            $current = (string)($remote['openInfo']['status'] ?? '');
            if ($current === 'CLOSED_PERMANENTLY') {
                $error = new WP_Error('gbp_permanent','La fiche est fermée définitivement sur Google : aucune réouverture automatique.');
                self::record_error($error); return $error;
            }
            if ($plan['mode'] === 'temporary_closed') {
                if ($current !== 'CLOSED_TEMPORARILY') {
                    $write = self::api('PATCH',$location,array('openInfo'=>array('status'=>'CLOSED_TEMPORARILY')),array('updateMask'=>'openInfo.status'));
                    if (is_wp_error($write)) { self::record_error($write); return $write; }
                }
            } else {
                if ($current === 'CLOSED_TEMPORARILY') {
                    $reopen = self::api('PATCH',$location,array('openInfo'=>array('status'=>'OPEN')),array('updateMask'=>'openInfo.status'));
                    if (is_wp_error($reopen)) { self::record_error($reopen); return $reopen; }
                }
                // Envoi limité strictement aux horaires, jamais aux autres champs de la fiche.
                $body = array(
                    'regularHours'=>$plan['regularHours'],
                    'specialHours'=>array('specialHourPeriods'=>self::preserve_future_specials($remote,$plan)),
                );
                $mask = array('updateMask'=>'regularHours,specialHours');
                $validation = self::api('PATCH', $location, $body, array_merge($mask,array('validateOnly'=>'true')));
                if (is_wp_error($validation)) { self::record_error($validation); return $validation; }
                $write = self::api('PATCH', $location, $body, $mask);
                if (is_wp_error($write)) { self::record_error($write); return $write; }
            }
            $verify = self::api('GET',$location,null,array('readMask'=>'name,openInfo,regularHours,specialHours,metadata'));
            if (is_wp_error($verify)) { self::record_error($verify); return $verify; }
            $status = (string)($verify['openInfo']['status'] ?? '');
            $expected = $plan['mode'] === 'temporary_closed' ? 'CLOSED_TEMPORARILY' : 'OPEN';
            if ($status !== $expected) {
                $error = new WP_Error('gbp_verify','Google ne confirme pas le statut demandé après envoi.');
                self::record_error($error); return $error;
            }
            // Google peut encore examiner les modifications avant leur affichage public.
            $data = self::option();
            $data['last_checked'] = current_time('mysql');
            $data['last_timestamp'] = time();
            $data['last_hash'] = $hash;
            $data['last_mode'] = $expected;
            $data['last_pending'] = !empty($verify['metadata']['hasPendingEdits']);
            $data['last_error'] = '';
            self::write($data);
            return array('status'=>$expected,'pending'=>$data['last_pending']);
        } finally {
            delete_transient('parcs_ht_gbp_busy');
        }
    }

    private static function form_begin($action) {
        echo '<form method="post" action="'.esc_url(admin_url('admin-post.php')).'">';
        echo '<input type="hidden" name="action" value="'.esc_attr('parcs_ht_gbp_'.$action).'">';
        wp_nonce_field('parcs_ht_gbp_'.$action);
    }

    public static function page() {
        if (!current_user_can('manage_options')) return;
        $data = self::option();
        $connected = !empty($data['refresh_token']);
        $all = Parcs_HT_Defaults::all_settings();
        $tz = new DateTimeZone(Parcs_HT_Schedule::timezone($all));
        $today = wp_date('Y-m-d', null, $tz);
        $plan = Parcs_HT_GBP_Plan::build($all, $today);
        echo '<div class="wrap"><h1>Synchronisation Google Maps</h1>';
        echo '<p>Le calendrier Horaires &amp; calendrier reste la source unique. Ce module ne modifie jamais vos saisons, tarifs ou événements.</p>';
        if (isset($_GET['gbp_notice'])) {
            $notices = array(
                'saved'=>'Réglages enregistrés.','connected'=>'Compte connecté. Choisissez maintenant la fiche du parc.',
                'synced'=>'Demande de synchronisation envoyée à Google. Vérifiez son statut public.',
                'disconnected'=>'Connexion locale supprimée.','credentials'=>'Renseignez les identifiants OAuth avant de connecter Google.',
                'denied'=>'Autorisation Google non accordée.','invalid'=>'Sélection ou état de connexion invalide.',
                'error'=>'Google a refusé une opération. Consultez le détail ci-dessous.'
            );
            $key = sanitize_key(wp_unslash($_GET['gbp_notice']));
            if (isset($notices[$key])) echo '<div class="notice notice-info"><p>'.esc_html($notices[$key]).'</p></div>';
        }
        echo '<h2>Calendrier utilisé</h2><p>Date locale : <strong>'.esc_html($today).'</strong> — ';
        if (is_wp_error($plan)) echo esc_html($plan->get_error_message());
        else echo ($plan['mode'] === 'temporary_closed' ? 'Fermeture saisonnière' : 'Ouvert avec horaires exceptionnels calculés sur 35 jours');
        echo '</p>';
        if (!is_wp_error($plan) && $plan['mode'] === 'open') {
            echo '<p>'.count($plan['regularHours']['periods']).' créneaux hebdomadaires et '.count($plan['specialHours']['specialHourPeriods']).' créneaux exceptionnels projetés.</p>';
        }
        echo '<hr><h2>1. Identifiants Google Cloud OAuth 2.0</h2>';
        echo '<p>Google Business Profile nécessite un projet autorisé et un identifiant OAuth « Application Web » (pas une simple clé API). Adresse de redirection autorisée :</p>';
        echo '<p><code>'.esc_html(self::callback_url()).'</code></p>';
        self::form_begin('save');
        echo '<table class="form-table"><tr><th><label for="gbp_client_id">ID client OAuth</label></th><td><input type="text" class="regular-text" id="gbp_client_id" name="gbp[client_id]" value="'.esc_attr($data['client_id'] ?? '').'" autocomplete="off"></td></tr>';
        echo '<tr><th><label for="gbp_client_secret">Secret client OAuth</label></th><td><input type="password" class="regular-text" id="gbp_client_secret" name="gbp[client_secret]" autocomplete="new-password"><p class="description">Laisser vide pour conserver le secret déjà enregistré.</p></td></tr>';
        if ($connected) {
            $locations = self::locations();
            echo '<tr><th><label for="gbp_location">Fiche du parc</label></th><td>';
            if (is_wp_error($locations)) {
                echo '<p>'.esc_html($locations->get_error_message()).'</p>';
            } else {
                echo '<select id="gbp_location" name="gbp[location]"><option value="">Sélectionner la fiche exacte</option>';
                foreach ($locations as $location) {
                    echo '<option value="'.esc_attr($location['name']).'" '.selected($location['name'],($data['location'] ?? ''),false).'>'.esc_html($location['title'] ?? $location['name']).' ('.esc_html($location['name']).')</option>';
                }
                echo '</select>';
            }
            echo '</td></tr>';
        }
        echo '<tr><th>Automatisation</th><td><label><input type="checkbox" name="gbp[enabled]" value="1" '.checked(!empty($data['enabled']),true,false).'> Activer les envois automatiques à Google</label><p class="description">Désactivé par défaut. Nécessite un compte connecté et une fiche sélectionnée. Les tâches WP-Cron dépendent du trafic si aucune vraie tâche système n’est configurée.</p></td></tr></table>';
        submit_button('Enregistrer les réglages');
        echo '</form>';
        echo '<hr><h2>2. Connexion Google</h2>';
        if (!$connected) {
            self::form_begin('connect');
            submit_button('Autoriser Google Business Profile','secondary');
            echo '</form>';
        } else {
            echo '<p>Compte Google autorisé. La connexion reste propre à ce site WordPress.</p>';
            self::form_begin('disconnect');
            submit_button('Déconnecter Google','secondary');
            echo '</form>';
        }
        echo '<hr><h2>3. Suivi de la synchronisation</h2>';
        echo '<p>État : <strong>'.(!empty($data['enabled']) ? 'Activée' : 'Désactivée').'</strong>. ';
        echo 'Dernière vérification : '.esc_html($data['last_checked'] ?? 'Aucune').'. ';
        echo 'Dernier statut demandé : '.esc_html($data['last_mode'] ?? 'Aucun').'.</p>';
        if (!empty($data['last_pending'])) echo '<p>Google indique des modifications en attente d’examen : leur affichage public n’est pas confirmé.</p>';
        if (!empty($data['last_error'])) echo '<div class="notice notice-error"><p>'.esc_html($data['last_error']).'</p></div>';
        if (!empty($data['enabled']) && $connected && !empty($data['location'])) {
            self::form_begin('sync');
            submit_button('Synchroniser maintenant','secondary');
            echo '</form>';
        }
        echo '<p><a href="https://developers.google.com/my-business/content/overview" target="_blank" rel="noopener noreferrer">Conditions d’accès et documentation Google Business Profile</a></p></div>';
    }
}
