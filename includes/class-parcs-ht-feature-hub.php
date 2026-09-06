<?php

if (!defined('ABSPATH')) { exit; }

/** Synchronise l’onglet Shortcodes avec le registre central. */
final class Parcs_HT_Feature_Hub {
    public static function init() {
        add_action('admin_footer_toplevel_page_' . Parcs_HT_Admin::PAGE, array(__CLASS__, 'sync_shortcodes_table'));
    }

    public static function sync_shortcodes_table() {
        if (!current_user_can('manage_options') || !class_exists('Parcs_HT_Shortcode_Registry')) return;
        $rows = Parcs_HT_Shortcode_Registry::public_rows();
        ?>
        <script>
        (function(){
            var section=document.getElementById('htp-shortcodes');
            var body=section?section.querySelector('table tbody'):null;
            if(!body)return;
            var rows=<?php echo wp_json_encode($rows); ?>;
            body.innerHTML='';
            rows.forEach(function(row){
                var tr=document.createElement('tr');
                var th=document.createElement('th'); th.textContent=row.label; tr.appendChild(th);
                ['fr','en','de'].forEach(function(lang){
                    var td=document.createElement('td'),code=document.createElement('code');
                    code.textContent=(row.shortcodes&&row.shortcodes[lang])||'';
                    td.appendChild(code); tr.appendChild(td);
                });
                body.appendChild(tr);
            });
            var intro=section.querySelector('h2 + p');
            if(intro)intro.textContent='Tous les shortcodes disponibles sont listés automatiquement depuis le registre central de l’extension.';
        }());
        </script>
        <?php
    }
}
