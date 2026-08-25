<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * GitHub updater for the private canonical repository.
 *
 * Authentication is never embedded in plugin files. Administrators may
 * store a repository-scoped read-only token in WordPress, while advanced
 * installations can still provide PARCS_HT_GITHUB_TOKEN from wp-config.php
 * or through the parcs_ht_github_token filter.
 */
final class Parcs_HT_Updater {
    const OWNER = 'montagnedessinges';
    const REPO = 'horaires-tarifs-parc';
    const SLUG = 'horaires-tarifs-parc';
    const UPDATE_URI = 'https://github.com/montagnedessinges/horaires-tarifs-parc';
    const RELEASE_CACHE_KEY = 'parcs_ht_github_latest_release';
    const RELEASE_CACHE_SECONDS = 15 * MINUTE_IN_SECONDS;
    const RELEASE_ERROR_CACHE_SECONDS = 5 * MINUTE_IN_SECONDS;
    const ASSET_NAME = 'horaires-tarifs-parc.zip';
    const TOKEN_OPTION = 'parcs_ht_github_token';
    const AUTO_UPDATE_OPTION = 'parcs_ht_github_auto_update';
    const LAST_CHECK_OPTION = 'parcs_ht_github_last_check';

    private static $release = null;
    private static $release_loaded = false;

    public static function init() {
        add_filter('update_plugins_github.com', array(__CLASS__, 'filter_update'), 10, 4);
        add_filter('plugins_api', array(__CLASS__, 'plugin_information'), 20, 3);
        add_filter('upgrader_pre_download', array(__CLASS__, 'pre_download'), 10, 4);
        add_filter('auto_update_plugin', array(__CLASS__, 'filter_auto_update'), 20, 2);
        add_action('admin_notices', array(__CLASS__, 'admin_notice'));
    }

    public static function filter_update($update, $plugin_data, $plugin_file, $locales) {
        unset($plugin_data, $locales);

        if ($plugin_file !== plugin_basename(PARCS_HT_FILE)) {
            return $update;
        }

        $release = self::latest_release();
        if (is_wp_error($release) || !$release) {
            return false;
        }

        $version = self::release_version($release);
        if ($version === '' || !version_compare($version, PARCS_HT_VERSION, '>')) {
            return false;
        }

        $asset = self::release_asset($release);
        if (!$asset) {
            return false;
        }

        return array(
            'id'           => self::UPDATE_URI,
            'slug'         => self::SLUG,
            'version'      => $version,
            'url'          => isset($release['html_url']) ? esc_url_raw($release['html_url']) : self::UPDATE_URI,
            'package'      => esc_url_raw($asset['url']),
            'tested'       => '',
            'requires_php' => '7.4',
        );
    }



    public static function filter_auto_update($update, $item) {
        if (!is_object($item)) {
            return $update;
        }

        $slug = isset($item->slug) ? (string) $item->slug : '';
        $plugin = isset($item->plugin) ? (string) $item->plugin : '';
        if ($slug !== self::SLUG && $plugin !== plugin_basename(PARCS_HT_FILE)) {
            return $update;
        }

        if (!self::auto_update_enabled() || !self::has_token()) {
            return $update;
        }

        return true;
    }

    public static function auto_update_enabled() {
        return get_option(self::AUTO_UPDATE_OPTION, '0') === '1';
    }

    public static function set_auto_update($enabled) {
        update_option(self::AUTO_UPDATE_OPTION, $enabled ? '1' : '0', false);
    }

    public static function latest_version() {
        $release = self::latest_release();
        if (is_wp_error($release)) {
            return $release;
        }
        if (!$release) {
            return '';
        }
        return self::release_version($release);
    }

    public static function last_check() {
        return (int) get_option(self::LAST_CHECK_OPTION, 0);
    }

    public static function plugin_information($result, $action, $args) {
        if ($action !== 'plugin_information' || empty($args->slug) || $args->slug !== self::SLUG) {
            return $result;
        }

        $release = self::latest_release();
        if (is_wp_error($release) || !$release) {
            return $result;
        }

        $asset = self::release_asset($release);
        $version = self::release_version($release);
        if (!$asset || $version === '') {
            return $result;
        }

        $body = isset($release['body']) ? (string) $release['body'] : '';

        return (object) array(
            'name'          => 'Horaires et tarifs du parc',
            'slug'          => self::SLUG,
            'version'       => $version,
            'author'        => 'Tanguy Huriez – Montagne des Singes',
            'homepage'      => self::UPDATE_URI,
            'requires'      => '6.0',
            'requires_php'  => '7.4',
            'download_link' => esc_url_raw($asset['url']),
            'sections'      => array(
                'description' => 'Horaires, calendrier interactif, exceptions, alertes et tarifs multilingues pour les parcs.',
                'changelog'   => $body !== '' ? nl2br(esc_html($body)) : 'Voir la release GitHub pour le détail de cette version.',
            ),
        );
    }

    /**
     * Securely downloads a private GitHub release asset.
     *
     * The Authorization header is only sent to api.github.com. When GitHub
     * returns a signed redirect URL, the second request is intentionally made
     * without Authorization so the token is never forwarded to the asset host.
     */
    public static function pre_download($reply, $package, $upgrader, $hook_extra) {
        unset($upgrader, $hook_extra);

        if ($reply !== false || !self::is_release_asset_api_url($package)) {
            return $reply;
        }

        $token = self::token();
        if ($token === '') {
            return new WP_Error(
                'parcs_ht_github_token_missing',
                'La mise à jour Horaires & Tarifs Parc nécessite la clé GitHub privée configurée sur ce site.'
            );
        }

        $tmp = wp_tempnam(self::ASSET_NAME);
        if (!$tmp) {
            return new WP_Error('parcs_ht_github_tempfile', 'Impossible de créer le fichier temporaire pour la mise à jour.');
        }

        $response = wp_remote_get($package, array(
            'timeout'     => 60,
            'redirection' => 0,
            'stream'      => true,
            'filename'    => $tmp,
            'headers'     => self::headers(true),
        ));

        if (is_wp_error($response)) {
            @unlink($tmp);
            return $response;
        }

        $code = (int) wp_remote_retrieve_response_code($response);
        if ($code >= 200 && $code < 300 && file_exists($tmp) && filesize($tmp) > 0) {
            return $tmp;
        }

        if ($code >= 300 && $code < 400) {
            $location = wp_remote_retrieve_header($response, 'location');
            @unlink($tmp);

            if (!$location || !wp_http_validate_url($location)) {
                return new WP_Error('parcs_ht_github_redirect', 'GitHub n’a pas fourni de lien de téléchargement valide.');
            }

            $tmp = wp_tempnam(self::ASSET_NAME);
            if (!$tmp) {
                return new WP_Error('parcs_ht_github_tempfile', 'Impossible de créer le fichier temporaire pour la mise à jour.');
            }

            // Never send the GitHub token to the signed asset host.
            $download = wp_remote_get($location, array(
                'timeout'     => 300,
                'redirection' => 3,
                'stream'      => true,
                'filename'    => $tmp,
            ));

            if (is_wp_error($download)) {
                @unlink($tmp);
                return $download;
            }

            $download_code = (int) wp_remote_retrieve_response_code($download);
            if ($download_code >= 200 && $download_code < 300 && file_exists($tmp) && filesize($tmp) > 0) {
                return $tmp;
            }

            @unlink($tmp);
            return new WP_Error('parcs_ht_github_download', 'Le package GitHub n’a pas pu être téléchargé.');
        }

        @unlink($tmp);
        return new WP_Error(
            'parcs_ht_github_download',
            sprintf('GitHub a refusé le téléchargement du package (HTTP %d).', $code)
        );
    }

    public static function admin_notice() {
        if (!current_user_can('update_plugins') || self::token() !== '') {
            return;
        }

        if (!function_exists('get_current_screen')) {
            return;
        }

        $screen = get_current_screen();
        if (!$screen || !in_array($screen->id, array('plugins', 'update-core', 'toplevel_page_parcs-horaires-tarifs'), true)) {
            return;
        }

        $url = admin_url('admin.php?page=parcs-horaires-tarifs&tab=htp-updates');
        echo '<div class="notice notice-warning"><p><strong>Horaires &amp; Tarifs Parc :</strong> les mises à jour privées GitHub sont prêtes, mais ce site n’a pas encore de clé d’accès. <a href="' . esc_url($url) . '">Configurer la clé GitHub dans l’extension</a>.</p></div>';
    }

    private static function latest_release() {
        if (self::$release_loaded) {
            return self::$release;
        }
        self::$release_loaded = true;

        $token = self::token();
        if ($token === '') {
            self::$release = false;
            return self::$release;
        }

        if (isset($_GET['force-check']) && current_user_can('update_plugins')) {
            delete_site_transient(self::RELEASE_CACHE_KEY);
        }

        $cached = get_site_transient(self::RELEASE_CACHE_KEY);
        if (is_array($cached)) {
            if (!empty($cached['_parcs_ht_error'])) {
                self::$release = new WP_Error(
                    (string)($cached['code'] ?? 'parcs_ht_github_release'),
                    (string)($cached['message'] ?? 'Impossible de lire la dernière release GitHub.')
                );
            } else {
                self::$release = $cached;
            }
            return self::$release;
        }

        $url = sprintf('https://api.github.com/repos/%s/%s/releases/latest', self::OWNER, self::REPO);
        update_option(self::LAST_CHECK_OPTION, time(), false);
        $response = wp_remote_get($url, array(
            // Une panne GitHub ne doit jamais bloquer longtemps l'administration WordPress.
            'timeout' => 6,
            'headers' => self::headers(false),
        ));

        if (is_wp_error($response)) {
            self::$release = $response;
            self::cache_release_error($response->get_error_code(), $response->get_error_message());
            return self::$release;
        }

        $code = (int) wp_remote_retrieve_response_code($response);
        if ($code !== 200) {
            self::$release = new WP_Error(
                'parcs_ht_github_release',
                sprintf('Impossible de lire la dernière release GitHub (HTTP %d).', $code)
            );
            self::cache_release_error(self::$release->get_error_code(), self::$release->get_error_message());
            return self::$release;
        }

        $data = json_decode(wp_remote_retrieve_body($response), true);
        if (!is_array($data) || empty($data['tag_name'])) {
            self::$release = new WP_Error('parcs_ht_github_release', 'Réponse GitHub invalide pour la dernière release.');
            self::cache_release_error(self::$release->get_error_code(), self::$release->get_error_message());
            return self::$release;
        }

        set_site_transient(self::RELEASE_CACHE_KEY, $data, self::RELEASE_CACHE_SECONDS);
        self::$release = $data;
        return self::$release;
    }


    private static function cache_release_error($code, $message) {
        set_site_transient(self::RELEASE_CACHE_KEY, array(
            '_parcs_ht_error' => 1,
            'code' => (string) $code,
            'message' => (string) $message,
        ), self::RELEASE_ERROR_CACHE_SECONDS);
    }

    private static function release_version($release) {
        if (!is_array($release) || empty($release['tag_name'])) {
            return '';
        }
        return ltrim((string) $release['tag_name'], "vV \t\n\r\0\x0B");
    }

    private static function release_asset($release) {
        if (empty($release['assets']) || !is_array($release['assets'])) {
            return false;
        }

        foreach ($release['assets'] as $asset) {
            if (!is_array($asset) || ($asset['name'] ?? '') !== self::ASSET_NAME || empty($asset['url'])) {
                continue;
            }
            return $asset;
        }

        return false;
    }

    public static function has_token() {
        return self::token() !== '';
    }

    public static function token_source() {
        if (defined('PARCS_HT_GITHUB_TOKEN') && trim((string) PARCS_HT_GITHUB_TOKEN) !== '') return 'constant';
        $env = getenv('PARCS_HT_GITHUB_TOKEN');
        if (is_string($env) && trim($env) !== '') return 'environment';
        $stored = get_option(self::TOKEN_OPTION, '');
        if (is_string($stored) && trim($stored) !== '') return 'wordpress';
        return 'none';
    }

    public static function clear_cache() {
        self::$release = null;
        self::$release_loaded = false;
        delete_site_transient(self::RELEASE_CACHE_KEY);
        delete_site_transient('update_plugins');
    }

    private static function token() {
        $token = '';
        if (defined('PARCS_HT_GITHUB_TOKEN')) {
            $token = (string) PARCS_HT_GITHUB_TOKEN;
        } else {
            $env = getenv('PARCS_HT_GITHUB_TOKEN');
            if (is_string($env) && trim($env) !== '') {
                $token = $env;
            } else {
                $stored = get_option(self::TOKEN_OPTION, '');
                if (is_string($stored)) $token = $stored;
            }
        }

        /**
         * Allows a server-side secret manager to override the configured token.
         */
        $token = apply_filters('parcs_ht_github_token', $token);
        return is_string($token) ? trim($token) : '';
    }

    private static function headers($binary) {
        $headers = array(
            'Authorization'        => 'Bearer ' . self::token(),
            'X-GitHub-Api-Version' => '2022-11-28',
            'User-Agent'           => 'Horaires-Tarifs-Parc/' . PARCS_HT_VERSION,
            'Accept'               => $binary ? 'application/octet-stream' : 'application/vnd.github+json',
        );
        return $headers;
    }

    private static function is_release_asset_api_url($url) {
        if (!is_string($url) || $url === '') {
            return false;
        }

        $prefix = sprintf('https://api.github.com/repos/%s/%s/releases/assets/', self::OWNER, self::REPO);
        return strpos($url, $prefix) === 0;
    }
}
