<?php

if (!defined('ABSPATH')) { exit; }

/**
 * Assembleur des shortcodes publics composés.
 *
 * Chaque bloc conserve son propre moteur et son propre shortcode. Les shortcodes
 * complets ne font qu'assembler les blocs existants afin d'éviter toute duplication
 * de logique entre statut, calendrier et tarifs.
 */
final class Parcs_HT_Shortcode_Composer {
    private static $instance = 0;

    public static function init() {
        add_shortcode('parc_horaires_tarifs', array(__CLASS__, 'shortcode_page'));
        add_shortcode('parc_groupes_horaires_tarifs', array(__CLASS__, 'shortcode_groups'));

        foreach (array('fr','en','de') as $language) {
            add_shortcode('parc_horaires_tarifs_' . $language, static function ($atts = array()) use ($language) {
                return Parcs_HT_Shortcode_Composer::render_page($language, is_array($atts) ? $atts : array());
            });
            add_shortcode('parc_groupes_horaires_tarifs_' . $language, static function ($atts = array()) use ($language) {
                return Parcs_HT_Shortcode_Composer::render_groups($language, is_array($atts) ? $atts : array());
            });
        }
    }

    public static function shortcode_page($atts = array()) {
        return self::render_page(Parcs_HT_Schedule::language(), is_array($atts) ? $atts : array());
    }

    public static function shortcode_groups($atts = array()) {
        return self::render_groups(Parcs_HT_Schedule::language(), is_array($atts) ? $atts : array());
    }

    private static function language($language) {
        return in_array($language, array('fr','en','de'), true) ? $language : 'fr';
    }

    private static function child($base, $language) {
        return do_shortcode('[' . $base . '_' . self::language($language) . ']');
    }

    private static function text($language, $fr, $en, $de) {
        return $language === 'en' ? $en : ($language === 'de' ? $de : $fr);
    }

    /**
     * Shortcode public complet : il assemble simplement les trois shortcodes
     * autonomes qui fonctionnent déjà séparément.
     */
    public static function render_page($language, $atts = array()) {
        unset($atts);
        $language = self::language($language);
        self::$instance++;
        $id = 'parcs-ht-composed-page-' . self::$instance;

        $today = self::child('parc_horaires_aujourdhui', $language);
        $calendar = self::child('parc_calendrier', $language);
        $tariffs = self::child('parc_tableau_tarifs', $language);

        return '<div id="' . esc_attr($id) . '" class="parcs-ht-page parcs-ht-composed-page" data-htp-lang="' . esc_attr($language) . '">' .
            $today . $calendar . $tariffs .
            '</div>';
    }

    /**
     * Shortcode groupes : même principe. Le composant ne réinterprète ni les
     * horaires ni les tarifs ; il affiche les shortcodes autonomes dans deux onglets.
     */
    public static function render_groups($language, $atts = array()) {
        unset($atts);
        $language = self::language($language);
        self::$instance++;
        $id = 'parcs-ht-composed-groups-' . self::$instance;

        $tariffs = self::child('parc_tarifs_groupes', $language);
        $calendar = self::child('parc_calendrier', $language);

        ob_start(); ?>
        <div id="<?php echo esc_attr($id); ?>" class="parcs-ht-group-portal parcs-ht-composed-groups" data-group-portal>
            <div class="parcs-ht-group-portal-tabs" role="tablist">
                <button type="button" class="is-active" data-group-main-tab="tariffs" aria-selected="true"><?php echo esc_html(self::text($language, 'Tarifs groupes', 'Group rates', 'Gruppentarife')); ?></button>
                <button type="button" data-group-main-tab="hours" aria-selected="false"><?php echo esc_html(self::text($language, 'Horaires d’ouverture', 'Opening hours', 'Öffnungszeiten')); ?></button>
            </div>
            <div data-group-main-panel="tariffs"><?php echo $tariffs; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- rendu d'un shortcode interne déjà échappé. ?></div>
            <div data-group-main-panel="hours" hidden><?php echo $calendar; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- rendu d'un shortcode interne déjà échappé. ?></div>
        </div>
        <style>
        #<?php echo esc_attr($id); ?>{background:transparent}
        #<?php echo esc_attr($id); ?> .parcs-ht-group-portal-tabs{display:flex;flex-wrap:nowrap;align-items:center;gap:8px;margin:0 0 16px;padding:2px 0 5px;overflow-x:auto;overflow-y:hidden;-webkit-overflow-scrolling:touch}
        #<?php echo esc_attr($id); ?> .parcs-ht-group-portal-tabs button{flex:0 0 auto;min-height:44px;border:1px solid var(--htp-primary,#006757);background:transparent;color:var(--htp-primary,#006757);border-radius:999px;padding:9px 16px;font:inherit;font-size:15px;font-weight:800;line-height:1.15;white-space:nowrap;cursor:pointer}
        #<?php echo esc_attr($id); ?> .parcs-ht-group-portal-tabs button.is-active{background:var(--htp-primary,#006757);border-color:var(--htp-primary,#006757);color:#fff}
        </style>
        <script>
        (function(){
            var root=document.getElementById(<?php echo wp_json_encode($id); ?>);if(!root)return;
            root.querySelectorAll('[data-group-main-tab]').forEach(function(btn){
                btn.addEventListener('click',function(){
                    var key=btn.getAttribute('data-group-main-tab');
                    root.querySelectorAll('[data-group-main-tab]').forEach(function(other){var on=other===btn;other.classList.toggle('is-active',on);other.setAttribute('aria-selected',on?'true':'false');});
                    root.querySelectorAll('[data-group-main-panel]').forEach(function(panel){panel.hidden=panel.getAttribute('data-group-main-panel')!==key;});
                });
            });
        }());
        </script>
        <?php return ob_get_clean();
    }
}
