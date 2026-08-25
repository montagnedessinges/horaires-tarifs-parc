<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Renforce les appels HTTPS GitHub de l'extension sans désactiver SSL.
 *
 * Certains hébergements utilisent un magasin de certificats système incomplet
 * ou intercepté. WordPress embarque son propre bundle de CA ; on le force ici
 * uniquement pour les hôtes GitHub utilisés par le mécanisme de mise à jour.
 */
final class Parcs_HT_HTTP_SSL {
    public static function init() {
        add_filter('http_request_args', array(__CLASS__, 'force_wordpress_ca_bundle'), 999, 2);
    }

    public static function force_wordpress_ca_bundle($args, $url) {
        $host = wp_parse_url($url, PHP_URL_HOST);
        if (!is_string($host) || !self::is_github_host($host)) {
            return $args;
        }

        $bundle = ABSPATH . WPINC . '/certificates/ca-bundle.crt';
        if (!is_readable($bundle)) {
            return $args;
        }

        // La vérification SSL reste impérativement active.
        $args['sslverify'] = true;
        $args['sslcertificates'] = $bundle;

        return $args;
    }

    private static function is_github_host($host) {
        $host = strtolower(trim($host, '.'));
        $allowed = array(
            'github.com',
            'api.github.com',
            'raw.githubusercontent.com',
            'objects.githubusercontent.com',
            'release-assets.githubusercontent.com',
        );

        if (in_array($host, $allowed, true)) {
            return true;
        }

        return substr($host, -18) === '.githubusercontent.com';
    }
}
