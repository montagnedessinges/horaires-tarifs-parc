<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Aperçus administratifs des vrais shortcodes publics.
 *
 * Les aperçus ne sont plus tous calculés au chargement de la page. Un seul
 * shortcode et une seule langue sont rendus à la demande dans une iframe
 * d’administration isolée, ce qui évite de charger les 36 rendus inutiles.
 */
final class Parcs_HT_Admin_Shortcode_Preview {
    const SCREEN = 'toplevel_page_parcs-horaires-tarifs';
    const ACTION = 'parcs_ht_shortcode_preview_frame';
    const NONCE_ACTION = 'parcs_ht_shortcode_preview';

    public static function init() {
        add_action('admin_enqueue_scripts', array(__CLASS__, 'assets'), 30);
        add_action('wp_ajax_' . self::ACTION, array(__CLASS__, 'frame'));
    }

    public static function assets($hook) {
        if ($hook !== self::SCREEN || !class_exists('Parcs_HT_Shortcode_Registry')) return;

        wp_enqueue_style(
            'parcs-ht-admin-shortcode-preview',
            PARCS_HT_URL . 'assets/admin-shortcode-preview.css',
            array('parcs-ht-admin'),
            PARCS_HT_VERSION
        );
        wp_enqueue_script(
            'parcs-ht-admin-shortcode-preview',
            PARCS_HT_URL . 'assets/admin-shortcode-preview.js',
            array('parcs-ht-admin'),
            PARCS_HT_VERSION,
            true
        );

        $rows = array_values(array_filter(Parcs_HT_Shortcode_Registry::public_rows(), static function ($row) {
            return !empty($row['preview']);
        }));
        $season = isset($_GET['season']) ? sanitize_text_field(wp_unslash($_GET['season'])) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Sélection d'aperçu en lecture seule.
        if (!preg_match('/^20\d{2}$/', $season)) $season = '';

        wp_add_inline_script(
            'parcs-ht-admin-shortcode-preview',
            'window.ParcsHTShortcodePreview=' . wp_json_encode(array(
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'action' => self::ACTION,
                'nonce' => wp_create_nonce(self::NONCE_ACTION),
                'rows' => $rows,
                'season' => $season,
                'defaultDate' => wp_date('Y-m-d'),
                'defaultTime' => wp_date('H:i'),
            )) . ';',
            'before'
        );
    }

    private static function requested_string($key) {
        return isset($_GET[$key]) ? sanitize_text_field(wp_unslash($_GET[$key])) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Requête d'aperçu protégée par nonce et capacité.
    }

    private static function preview_timestamp_ms($date, $time, $timezone) {
        if (!preg_match('/^20\d{2}-\d{2}-\d{2}$/', $date) || !preg_match('/^\d{2}:\d{2}$/', $time)) return null;
        try {
            $zone = new DateTimeZone($timezone ?: 'Europe/Paris');
            $moment = DateTimeImmutable::createFromFormat('!Y-m-d H:i', $date . ' ' . $time, $zone);
            if (!$moment || $moment->format('Y-m-d H:i') !== $date . ' ' . $time) return null;
            return $moment->getTimestamp() * 1000;
        } catch (Exception $exception) {
            return null;
        }
    }

    private static function guide_inline_css() {
        if (!class_exists('Parcs_HT_Guide_Appearance')) return '';
        $s = Parcs_HT_Guide_Appearance::settings();
        return '.parcs-ht-guides{'
            . '--htp-guide-card-bg:' . esc_attr($s['card_background']) . ';'
            . '--htp-guide-text:' . esc_attr($s['text_color']) . ';'
            . '--htp-guide-title:' . esc_attr($s['title_color']) . ';'
            . '--htp-guide-primary-bg:' . esc_attr($s['primary_button_background']) . ';'
            . '--htp-guide-primary-text:' . esc_attr($s['primary_button_text']) . ';'
            . '--htp-guide-secondary:' . esc_attr($s['secondary_button_color']) . ';'
            . '--htp-guide-category:' . esc_attr($s['category_color']) . ';'
            . '}';
    }

    public static function frame() {
        if (!current_user_can('manage_options')) wp_die('Accès refusé.', '', array('response' => 403));
        check_ajax_referer(self::NONCE_ACTION);

        $base = sanitize_key(self::requested_string('base'));
        $language = sanitize_key(self::requested_string('lang'));
        $definitions = Parcs_HT_Shortcode_Registry::definitions();
        if (!isset($definitions[$base]) || empty($definitions[$base]['preview'])) wp_die('Shortcode inconnu.', '', array('response' => 404));
        if (!in_array($language, Parcs_HT_Shortcode_Registry::languages(), true)) $language = 'fr';

        $season = self::requested_string('season');
        if (!preg_match('/^20\d{2}$/', $season)) $season = '';
        $settings = Parcs_HT_Defaults::settings($season);
        $timezone = isset($settings['timezone']) ? (string)$settings['timezone'] : 'Europe/Paris';
        $preview_ms = self::preview_timestamp_ms(self::requested_string('date'), self::requested_string('time'), $timezone);
        $background = self::requested_string('background');
        if (!preg_match('/^#[0-9a-f]{6}$/i', $background)) $background = '#ffffff';

        $payload = array(
            'settings' => Parcs_HT_Schedule::public_settings($settings),
            'dictionary' => Parcs_HT_Schedule::dictionaries(),
        );
        $html = Parcs_HT_Shortcode_Registry::render_preview($base, $language);

        nocache_headers();
        header('Content-Type: text/html; charset=' . get_option('blog_charset'));
        ?>
<!doctype html>
<html lang="<?php echo esc_attr($language); ?>">
<head>
<meta charset="<?php bloginfo('charset'); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="stylesheet" href="<?php echo esc_url(PARCS_HT_URL . 'assets/frontend.css?ver=' . rawurlencode(PARCS_HT_VERSION)); ?>">
<link rel="stylesheet" href="<?php echo esc_url(PARCS_HT_URL . 'assets/pedagogical-guides.css?ver=' . rawurlencode(PARCS_HT_VERSION)); ?>">
<style>html,body{margin:0;padding:0;background:<?php echo esc_html($background); ?>}body{padding:24px;box-sizing:border-box}.htp-preview-frame-empty{margin:0;color:#646970;font:14px/1.5 -apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif}<?php echo self::guide_inline_css(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Valeurs de couleurs échappées dans guide_inline_css(). ?></style>
<script>window.ParcsHTPData=<?php echo wp_json_encode($payload); ?>;<?php if ($preview_ms !== null) : ?>(function(){var RealDate=Date,fixed=<?php echo (int)$preview_ms; ?>;class PreviewDate extends RealDate{constructor(){var a=Array.prototype.slice.call(arguments);if(!a.length){super(fixed);}else{super(...a);}}static now(){return fixed;}}PreviewDate.UTC=RealDate.UTC;PreviewDate.parse=RealDate.parse;window.Date=PreviewDate;}());<?php endif; ?></script>
</head>
<body>
<?php if (trim((string)$html) === '') : ?><p class="htp-preview-frame-empty">Aucun rendu avec les données actuellement enregistrées.</p><?php else : echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- HTML produit par les moteurs publics internes. ?><?php endif; ?>
<script src="<?php echo esc_url(PARCS_HT_URL . 'assets/frontend.js?ver=' . rawurlencode(PARCS_HT_VERSION)); ?>"></script>
<script>(function(){function send(){try{parent.postMessage({type:'parcs-ht-preview-size',height:Math.max(document.documentElement.scrollHeight,document.body.scrollHeight)},location.origin);}catch(e){}}window.addEventListener('load',send);document.addEventListener('click',function(){setTimeout(send,30);});if(window.ResizeObserver){new ResizeObserver(send).observe(document.body);}setTimeout(send,100);setTimeout(send,500);}());</script>
</body>
</html>
        <?php
        exit;
    }
}
