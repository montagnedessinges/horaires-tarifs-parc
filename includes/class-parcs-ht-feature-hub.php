<?php

if (!defined('ABSPATH')) { exit; }

/**
 * Complète uniquement le tableau de l’onglet Shortcodes avec les modules 1.10.
 * Aucun nouvel onglet ni sous-menu n’est créé ici.
 */
final class Parcs_HT_Feature_Hub {
    public static function init() {
        add_action('admin_footer_toplevel_page_' . Parcs_HT_Admin::PAGE, array(__CLASS__, 'extend_shortcodes_table'));
    }

    public static function extend_shortcodes_table() {
        if (!current_user_can('manage_options')) return;
        ?>
        <script>
        (function(){
            var body=document.querySelector('#htp-shortcodes table tbody');
            if(!body || body.querySelector('[data-htp-110-shortcodes]')) return;
            var rows=[
                ['Tarifs groupes uniquement','parc_tarifs_groupes'],
                ['Guides pédagogiques','parc_guides_pedagogiques']
            ];
            rows.forEach(function(row,index){
                var tr=document.createElement('tr');
                if(index===0) tr.setAttribute('data-htp-110-shortcodes','1');
                var th=document.createElement('th');
                th.textContent=row[0];
                tr.appendChild(th);
                ['fr','en','de'].forEach(function(lang){
                    var td=document.createElement('td');
                    var code=document.createElement('code');
                    code.textContent='['+row[1]+'_'+lang+']';
                    td.appendChild(code);
                    tr.appendChild(td);
                });
                body.appendChild(tr);
            });
        }());
        </script>
        <?php
    }
}
