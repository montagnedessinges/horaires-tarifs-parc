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
            var table=section?section.querySelector('table'):null;
            if(!table)return;
            var rows=<?php echo wp_json_encode($rows); ?>;
            var thead=table.querySelector('thead');
            var body=table.querySelector('tbody');
            if(!thead||!body)return;

            thead.innerHTML='<tr><th>Module</th><th>Automatique</th><th>FR</th><th>EN</th><th>DE</th></tr>';
            body.innerHTML='';

            function shortcodeCell(value){
                var td=document.createElement('td');
                var code=document.createElement('code');
                code.textContent=value||'';
                td.appendChild(code);
                return td;
            }

            rows.forEach(function(row){
                var tr=document.createElement('tr');
                var th=document.createElement('th');
                th.textContent=row.label;
                tr.appendChild(th);
                ['auto','fr','en','de'].forEach(function(lang){
                    tr.appendChild(shortcodeCell((row.shortcodes&&row.shortcodes[lang])||''));
                });
                body.appendChild(tr);
            });

            var intro=section.querySelector('h2 + p');
            if(intro)intro.textContent='Tous les shortcodes disponibles sont listés automatiquement depuis le registre central de l’extension. La version « Automatique » détecte la langue du site ; les variantes FR, EN et DE forcent la langue.';
        }());
        </script>
        <?php
    }
}
