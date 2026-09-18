<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * GitHub updater for the canonical repository.
 *
 * Le dépôt est public depuis 1.17.2 : la détection et le téléchargement des
 * releases publiques fonctionnent sans clé. Une clé facultative reste prise
 * en charge pour conserver la compatibilité avec un dépôt privé ultérieur.
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
    const CHECKSUM_ASSET_NAME = 'horaires-tarifs-parc.zip.sha256';
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
    }

    public static function filter_update($update, $plugin_data, $plugin_file, $locales) {
        unset($plugin_data, $locales);
        if ($plugin_file !== plugin_basename(PARCS_HT_FILE)) return $update;
        $release = self::latest_release();
        if (is_wp_error($release) || !$release) return false;
        $version = self::release_version($release);
        if ($version === '' || !version_compare($version, PARCS_HT_VERSION, '>')) return false;
        $asset = self::release_asset($release);
        if (!$asset) return false;
        $package = self::asset_download_url($asset);
        if ($package === '') return false;
        return array(
            'id' => self::UPDATE_URI,
            'slug' => self::SLUG,
            'version' => $version,
            'url' => isset($release['html_url']) ? esc_url_raw($release['html_url']) : self::UPDATE_URI,
            'package' => esc_url_raw($package),
            'tested' => '',
            'requires_php' => '7.4',
        );
    }

    public static function filter_auto_update($update, $item) {
        if (!is_object($item)) return $update;
        $slug = isset($item->slug) ? (string)$item->slug : '';
        $plugin = isset($item->plugin) ? (string)$item->plugin : '';
        if ($slug !== self::SLUG && $plugin !== plugin_basename(PARCS_HT_FILE)) return $update;
        if (!self::auto_update_enabled()) return $update;
        return true;
    }

    public static function auto_update_enabled() {
        return get_option(self::AUTO_UPDATE_OPTION, '0') === '1';
    }

    public static function set_auto_update($enabled) {
        update_option(self::AUTO_UPDATE_OPTION, $enabled ? '1' : '0', false);
    }

    public static function store_token($token) {
        $token = is_string($token) ? trim($token) : '';
        if ($token === '') return false;
        return update_option(self::TOKEN_OPTION, self::encrypt_token($token), false);
    }

    public static function latest_version() {
        $release = self::latest_release();
        if (is_wp_error($release)) return $release;
        return $release ? self::release_version($release) : '';
    }

    public static function last_check() {
        return (int)get_option(self::LAST_CHECK_OPTION, 0);
    }

    public static function plugin_information($result, $action, $args) {
        if ($action !== 'plugin_information' || empty($args->slug) || $args->slug !== self::SLUG) return $result;
        $release = self::latest_release();
        if (is_wp_error($release) || !$release) return $result;
        $asset = self::release_asset($release);
        $version = self::release_version($release);
        $package = $asset ? self::asset_download_url($asset) : '';
        if (!$asset || $version === '' || $package === '') return $result;
        $body = isset($release['body']) ? (string)$release['body'] : '';
        return (object)array(
            'name'          => PARCS_HT_DISPLAY_NAME,
            'slug'          => self::SLUG,
            'version'       => $version,
            'author'        => 'Tanguy Huriez – Montagne des Singes',
            'homepage'      => self::UPDATE_URI,
            'requires'      => '6.0',
            'requires_php'  => '7.4',
            'download_link' => esc_url_raw($package),
            'sections'      => array(
                'description' => 'Horaires, calendrier interactif, exceptions, alertes et tarifs multilingues pour les parcs.',
                'changelog' => $body !== '' ? nl2br(esc_html($body)) : 'Voir la release GitHub pour le détail de cette version.',
            ),
        );
    }

    /**
     * Télécharge uniquement les assets de CE dépôt afin de conserver le contrôle
     * SHA-256, même lorsque WordPress pourrait télécharger directement le ZIP.
     */
    public static function pre_download($reply, $package, $upgrader, $hook_extra) {
        unset($upgrader, $hook_extra);
        if ($reply !== false || !self::is_own_release_asset_url($package)) return $reply;

        $tmp = wp_tempnam(self::ASSET_NAME);
        if (!$tmp) return new WP_Error('parcs_ht_github_tempfile', 'Impossible de créer le fichier temporaire pour la mise à jour.');

        $response = self::download_to_file($package, $tmp, 300);
        if (is_wp_error($response)) {
            @unlink($tmp);
            return $response;
        }
        if (!file_exists($tmp) || filesize($tmp) <= 0) {
            @unlink($tmp);
            return new WP_Error('parcs_ht_github_download', 'Le package GitHub téléchargé est vide.');
        }

        $verified = self::verify_download_checksum($tmp);
        if (is_wp_error($verified)) {
            @unlink($tmp);
            return $verified;
        }
        return $tmp;
    }

    private static function download_to_file($url, $filename, $timeout) {
        $is_api = self::is_release_asset_api_url($url);
        $args = array(
            'timeout' => (int)$timeout,
            'redirection' => $is_api ? 0 : 5,
            'stream' => true,
            'filename' => $filename,
            'headers' => self::headers($is_api),
        );
        $response = wp_remote_get($url, $args);
        if (is_wp_error($response)) return $response;
        $code = (int)wp_remote_retrieve_response_code($response);

        if ($code >= 200 && $code < 300) return true;
        if ($is_api && $code >= 300 && $code < 400) {
            $location = wp_remote_retrieve_header($response, 'location');
            if (!$location || !wp_http_validate_url($location)) return new WP_Error('parcs_ht_github_redirect', 'GitHub n’a pas fourni de lien de téléchargement valide.');
            $download = wp_remote_get($location, array('timeout'=>(int)$timeout,'redirection'=>3,'stream'=>true,'filename'=>$filename));
            if (is_wp_error($download)) return $download;
            $download_code = (int)wp_remote_retrieve_response_code($download);
            if ($download_code >= 200 && $download_code < 300) return true;
            return new WP_Error('parcs_ht_github_download', sprintf('Le package GitHub n’a pas pu être téléchargé (HTTP %d).', $download_code));
        }
        return new WP_Error('parcs_ht_github_download', sprintf('GitHub a refusé le téléchargement du package (HTTP %d).', $code));
    }

    private static function latest_release() {
        if (self::$release_loaded) return self::$release;
        self::$release_loaded = true;

        global $pagenow;
        if ($pagenow === 'update-core.php' && isset($_GET['force-check']) && current_user_can('update_plugins')) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- comportement standard de l'écran WordPress.
            delete_site_transient(self::RELEASE_CACHE_KEY);
        }

        $cached = get_site_transient(self::RELEASE_CACHE_KEY);
        if (is_array($cached)) {
            if (!empty($cached['_parcs_ht_error'])) {
                self::$release = new WP_Error((string)($cached['code'] ?? 'parcs_ht_github_release'), (string)($cached['message'] ?? 'Impossible de lire la dernière release GitHub.'));
            } else {
                self::$release = $cached;
            }
            return self::$release;
        }

        $url = sprintf('https://api.github.com/repos/%s/%s/releases/latest', self::OWNER, self::REPO);
        update_option(self::LAST_CHECK_OPTION, time(), false);
        $response = wp_remote_get($url, array('timeout'=>6,'headers'=>self::headers(false)));
        if (is_wp_error($response)) {
            self::$release = $response;
            self::cache_release_error($response->get_error_code(), $response->get_error_message());
            self::report_updater_error('github_release_network', 'La vérification GitHub a échoué.', $response->get_error_message());
            return self::$release;
        }

        $code = (int)wp_remote_retrieve_response_code($response);
        if ($code !== 200) {
            self::$release = new WP_Error('parcs_ht_github_release', sprintf('Impossible de lire la dernière release GitHub (HTTP %d).', $code));
            self::cache_release_error(self::$release->get_error_code(), self::$release->get_error_message());
            self::report_updater_error('github_release_http', 'La vérification GitHub a échoué.', 'HTTP ' . $code);
            return self::$release;
        }

        $data = json_decode(wp_remote_retrieve_body($response), true);
        if (!is_array($data) || empty($data['tag_name'])) {
            self::$release = new WP_Error('parcs_ht_github_release', 'Réponse GitHub invalide pour la dernière release.');
            self::cache_release_error(self::$release->get_error_code(), self::$release->get_error_message());
            self::report_updater_error('github_release_invalid', 'GitHub a renvoyé une release invalide.');
            return self::$release;
        }

        set_site_transient(self::RELEASE_CACHE_KEY, $data, self::RELEASE_CACHE_SECONDS);
        self::$release = $data;
        return self::$release;
    }

    private static function cache_release_error($code, $message) {
        set_site_transient(self::RELEASE_CACHE_KEY, array('_parcs_ht_error'=>1,'code'=>(string)$code,'message'=>(string)$message), self::RELEASE_ERROR_CACHE_SECONDS);
    }

    private static function report_updater_error($code, $message, $details = '') {
        if (class_exists('Parcs_HT_Health')) Parcs_HT_Health::report_runtime_error($code, $message, $details);
    }

    private static function release_version($release) {
        return !is_array($release) || empty($release['tag_name']) ? '' : ltrim((string)$release['tag_name'], "vV \t\n\r\0\x0B");
    }

    private static function release_asset($release) {
        if (empty($release['assets']) || !is_array($release['assets'])) return false;
        foreach ($release['assets'] as $asset) {
            if (is_array($asset) && ($asset['name'] ?? '') === self::ASSET_NAME && (!empty($asset['browser_download_url']) || !empty($asset['url']))) return $asset;
        }
        return false;
    }

    private static function checksum_asset($release) {
        if (empty($release['assets']) || !is_array($release['assets'])) return false;
        foreach ($release['assets'] as $asset) {
            if (is_array($asset) && ($asset['name'] ?? '') === self::CHECKSUM_ASSET_NAME && (!empty($asset['browser_download_url']) || !empty($asset['url']))) return $asset;
        }
        return false;
    }

    private static function asset_download_url($asset) {
        if (!is_array($asset)) return '';
        // Avec une clé, l'URL API conserve la compatibilité avec un dépôt privé.
        if (self::has_token() && !empty($asset['url'])) return (string)$asset['url'];
        if (!empty($asset['browser_download_url'])) return (string)$asset['browser_download_url'];
        return !empty($asset['url']) ? (string)$asset['url'] : '';
    }

    private static function verify_download_checksum($path) {
        $release = self::latest_release();
        if (is_wp_error($release) || !is_array($release)) return new WP_Error('parcs_ht_checksum_release', 'Impossible de vérifier l’intégrité de la mise à jour : release GitHub indisponible.');
        $asset = self::checksum_asset($release);
        if (!$asset) return new WP_Error('parcs_ht_checksum_missing', 'La release GitHub ne contient pas le fichier SHA-256 attendu.');
        $url = self::asset_download_url($asset);
        if ($url === '') return new WP_Error('parcs_ht_checksum_missing', 'Le lien SHA-256 de la release est indisponible.');

        $is_api = self::is_release_asset_api_url($url);
        $response = wp_remote_get($url, array('timeout'=>15,'redirection'=>$is_api ? 0 : 5,'headers'=>self::headers($is_api)));
        if (is_wp_error($response)) return $response;
        $code = (int)wp_remote_retrieve_response_code($response);
        $body = '';
        if ($code >= 200 && $code < 300) {
            $body = (string)wp_remote_retrieve_body($response);
        } elseif ($is_api && $code >= 300 && $code < 400) {
            $location = wp_remote_retrieve_header($response, 'location');
            if (!$location || !wp_http_validate_url($location)) return new WP_Error('parcs_ht_checksum_redirect', 'GitHub n’a pas fourni de lien SHA-256 valide.');
            $download = wp_remote_get($location, array('timeout'=>15,'redirection'=>3));
            if (is_wp_error($download)) return $download;
            $download_code = (int)wp_remote_retrieve_response_code($download);
            if ($download_code < 200 || $download_code >= 300) return new WP_Error('parcs_ht_checksum_download', 'Le fichier SHA-256 de la release n’a pas pu être téléchargé.');
            $body = (string)wp_remote_retrieve_body($download);
        }

        if (!preg_match('/\\b([a-f0-9]{64})\\b/i', $body, $matches)) return new WP_Error('parcs_ht_checksum_invalid', 'Le fichier SHA-256 de la release est invalide.');
        $expected = strtolower($matches[1]);
        $actual = strtolower((string)hash_file('sha256', $path));
        if ($actual === '' || !hash_equals($expected, $actual)) {
            if (class_exists('Parcs_HT_Health')) Parcs_HT_Health::report_runtime_error('update_checksum', 'La mise à jour GitHub a été bloquée : empreinte SHA-256 incorrecte.');
            return new WP_Error('parcs_ht_checksum_mismatch', 'La mise à jour a été bloquée car son empreinte SHA-256 est incorrecte.');
        }
        return true;
    }

    public static function has_token() {
        return self::token() !== '';
    }

    public static function token_source() {
        if (defined('PARCS_HT_GITHUB_TOKEN') && trim((string)PARCS_HT_GITHUB_TOKEN) !== '') return 'constant';
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
            $token = (string)PARCS_HT_GITHUB_TOKEN;
        } else {
            $env = getenv('PARCS_HT_GITHUB_TOKEN');
            if (is_string($env) && trim($env) !== '') {
                $token = $env;
            } else {
                $stored = get_option(self::TOKEN_OPTION, '');
                if (is_string($stored)) $token = self::decrypt_token($stored);
            }
        }
        $token = apply_filters('parcs_ht_github_token', $token);
        return is_string($token) ? trim($token) : '';
    }

    private static function encrypt_token($token) {
        if (!function_exists('openssl_encrypt') || !defined('AUTH_KEY') || trim((string)AUTH_KEY) === '') return $token;
        try {
            $key = hash('sha256', (string)AUTH_KEY, true);
            $iv = random_bytes(12);
            $tag = '';
            $cipher = openssl_encrypt($token, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag);
            return $cipher === false ? $token : 'enc:' . base64_encode($iv . $tag . $cipher);
        } catch (Exception $e) {
            return $token;
        }
    }

    private static function decrypt_token($value) {
        if (strpos($value, 'enc:') !== 0) return $value;
        if (!function_exists('openssl_decrypt') || !defined('AUTH_KEY')) return '';
        $raw = base64_decode(substr($value, 4), true);
        if ($raw === false || strlen($raw) < 29) return '';
        $iv = substr($raw, 0, 12);
        $tag = substr($raw, 12, 16);
        $cipher = substr($raw, 28);
        $key = hash('sha256', (string)AUTH_KEY, true);
        $plain = openssl_decrypt($cipher, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag);
        return is_string($plain) ? $plain : '';
    }

    private static function headers($binary) {
        $headers = array(
            'X-GitHub-Api-Version' => '2022-11-28',
            'User-Agent' => 'Horaires-Tarifs-Parc/' . PARCS_HT_VERSION,
            'Accept' => $binary ? 'application/octet-stream' : 'application/vnd.github+json',
        );
        $token = self::token();
        if ($token !== '') $headers['Authorization'] = 'Bearer ' . $token;
        return $headers;
    }

    private static function is_release_asset_api_url($url) {
        if (!is_string($url) || $url === '') return false;
        return strpos($url, sprintf('https://api.github.com/repos/%s/%s/releases/assets/', self::OWNER, self::REPO)) === 0;
    }

    private static function is_public_release_download_url($url) {
        if (!is_string($url) || $url === '') return false;
        $prefix = sprintf('https://github.com/%s/%s/releases/download/', self::OWNER, self::REPO);
        if (strpos($url, $prefix) !== 0) return false;
        $path = wp_parse_url($url, PHP_URL_PATH);
        return is_string($path) && basename($path) === self::ASSET_NAME;
    }

    private static function is_own_release_asset_url($url) {
        return self::is_release_asset_api_url($url) || self::is_public_release_download_url($url);
    }
}
