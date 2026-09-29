<?php

if (!defined('ABSPATH')) { exit; }

/**
 * Unification FAQ 1.19.3.
 *
 * Les règles de visite destinées au public sont désormais gérées exclusivement
 * par la FAQ importée en CSV. L'ancienne base IA & Google est conservée pour
 * compatibilité des données, mais n'est plus une seconde source publique.
 */
final class Parcs_HT_FAQ_Unified_1193 {
    public static function init() {
        if (class_exists('Parcs_HT_AI_Google')) {
            remove_filter('do_shortcode_tag', array('Parcs_HT_AI_Google', 'append_visit_rules'), 90);
        }

        if (!is_admin()) return;
        add_action('admin_notices', array(__CLASS__, 'notice'));
        add_action('admin_footer', array(__CLASS__, 'replace_legacy_knowledge_panel'), 90);
    }

    private static function on_ai_google_page() {
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Sélection d'écran en lecture seule.
        $page = isset($_GET['page']) ? sanitize_key(wp_unslash($_GET['page'])) : '';
        return class_exists('Parcs_HT_AI_Google') && $page === Parcs_HT_AI_Google::PAGE;
    }

    public static function notice() {
        if (!current_user_can('manage_options') || !self::on_ai_google_page()) return;
        $faq_url = add_query_arg(array('page'=>Parcs_HT_FAQ::PAGE), admin_url('admin.php'));
        echo '<div class="notice notice-info"><p><strong>FAQ unifiée :</strong> les questions visiteurs et les règles de visite sont maintenant gérées dans <a href="' . esc_url($faq_url) . '">Gestion du parc → FAQ</a> à partir du CSV de l’onglet du parc. IA & Google reste réservé aux données structurées et à l’identité complémentaire.</p></div>';
    }

    public static function replace_legacy_knowledge_panel() {
        if (!current_user_can('manage_options') || !self::on_ai_google_page()) return;
        $faq_url = add_query_arg(array('page'=>Parcs_HT_FAQ::PAGE), admin_url('admin.php'));
        ?>
        <script>
        (function () {
            'use strict';
            var cards = document.querySelectorAll('.htp-ai-card');
            var faqUrl = <?php echo wp_json_encode($faq_url); ?>;
            Array.prototype.forEach.call(cards, function (card) {
                var heading = card.querySelector('h2');
                if (!heading || heading.textContent.indexOf('Base de connaissances officielle') === -1) return;
                card.innerHTML = ''
                    + '<h2>Questions visiteurs et règles de visite</h2>'
                    + '<p>Cette ancienne base n’est plus éditée ici afin d’éviter deux sources de vérité.</p>'
                    + '<p>Toutes les questions publiques — notamment « Peut-on nourrir les singes ? » et « Peut-on toucher ou caresser les singes ? » — doivent être gérées dans l’onglet <strong>Montagne des Singes</strong> du Google Sheet, puis importées avec le même CSV dans la FAQ.</p>'
                    + '<p><a class="button button-primary" href="' + faqUrl.replace(/&/g, '&amp;').replace(/"/g, '&quot;') + '">Ouvrir Gestion du parc → FAQ</a></p>';
            });
        }());
        </script>
        <?php
    }
}
