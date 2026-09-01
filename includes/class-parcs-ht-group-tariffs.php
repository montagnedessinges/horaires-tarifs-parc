<?php

if (!defined('ABSPATH')) { exit; }

/**
 * Shortcode dédié à l'affichage des tarifs groupes.
 *
 * Il réutilise volontairement le tableau tarifaire existant afin que les prix,
 * colonnes, langues et saisons restent gérés depuis une seule source de vérité.
 */
final class Parcs_HT_Group_Tariffs {
    public static function init() {
        add_shortcode('parc_tarifs_groupes', array(__CLASS__, 'shortcode'));
        foreach (array('fr','en','de') as $language) {
            add_shortcode('parc_tarifs_groupes_' . $language, static function ($atts = array()) use ($language) {
                return Parcs_HT_Group_Tariffs::render($language, is_array($atts) ? $atts : array());
            });
        }
    }

    public static function shortcode($atts = array()) {
        return self::render(Parcs_HT_Schedule::language(), is_array($atts) ? $atts : array());
    }

    private static function render($language, $atts) {
        $language = in_array($language, array('fr','en','de'), true) ? $language : 'fr';
        $source = '[parc_tableau_tarifs_' . $language . ']';
        $html = do_shortcode($source);
        if (trim($html) === '') return '';

        $instance = 'parcs-ht-group-tariffs-' . wp_rand(1000, 999999);
        $show_heading = !isset($atts['titre']) || (string)$atts['titre'] !== '0';
        $labels = array(
            'fr'=>array('title'=>'Tarifs groupes','fallback'=>'Les tarifs groupes ne sont pas disponibles pour le moment.'),
            'en'=>array('title'=>'Group rates','fallback'=>'Group rates are not available at the moment.'),
            'de'=>array('title'=>'Gruppentarife','fallback'=>'Die Gruppentarife sind derzeit nicht verfügbar.'),
        );

        ob_start();
        ?>
        <section id="<?php echo esc_attr($instance); ?>" class="parcs-ht-group-tariffs-only" data-htp-group-tariffs>
            <?php if ($show_heading) : ?><h2 class="parcs-ht-group-tariffs-title"><?php echo esc_html($labels[$language]['title']); ?></h2><?php endif; ?>
            <div class="parcs-ht-group-tariffs-source"><?php echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Sortie d'un shortcode interne déjà échappée. ?></div>
            <p class="parcs-ht-group-tariffs-empty" data-htp-group-tariffs-empty hidden><?php echo esc_html($labels[$language]['fallback']); ?></p>
        </section>
        <script>
        (function(){
            var root=document.getElementById(<?php echo wp_json_encode($instance); ?>); if(!root)return;
            function activate(){
                var tariff=root.querySelector('.parcs-ht-tariffs'); if(!tariff)return;
                var groupTab=tariff.querySelector('[data-htp-tariff-tab="groups"]');
                var groupPanel=tariff.querySelector('[id$="-panel-groups"]');
                tariff.querySelectorAll('.parcs-ht-tariff-tabs').forEach(function(el){el.hidden=true;});
                tariff.querySelectorAll('.parcs-ht-tariff-panel').forEach(function(panel){
                    if(panel!==groupPanel){panel.hidden=true;panel.setAttribute('aria-hidden','true');}
                });
                if(groupTab && typeof groupTab.click==='function') groupTab.click();
                if(groupPanel){groupPanel.hidden=false;groupPanel.removeAttribute('aria-hidden');}
                var heading=tariff.querySelector('.parcs-ht-tariff-heading'); if(heading) heading.hidden=true;
                var payment=tariff.querySelector('.parcs-ht-payment-strip'); if(payment) payment.hidden=true;
                if(!groupPanel || !groupPanel.querySelector('.parcs-ht-price-list')) {
                    var empty=root.querySelector('[data-htp-group-tariffs-empty]'); if(empty) empty.hidden=false;
                }
            }
            activate();
            if(document.readyState==='loading') document.addEventListener('DOMContentLoaded',activate,{once:true});
            setTimeout(activate,0);
        }());
        </script>
        <?php
        return ob_get_clean();
    }
}
