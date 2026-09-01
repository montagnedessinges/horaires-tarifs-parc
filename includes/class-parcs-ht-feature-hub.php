<?php

if (!defined('ABSPATH')) { exit; }

/**
 * Point d'entrée visible pour les fonctionnalités ajoutées à partir de la 1.10.
 */
final class Parcs_HT_Feature_Hub {
    const PAGE = 'parcs-ht-guides-groupes';

    public static function init() {
        add_action('admin_menu', array(__CLASS__, 'menu'), 30);
        add_action('admin_footer_toplevel_page_' . Parcs_HT_Admin::PAGE, array(__CLASS__, 'extend_shortcodes_table'));
    }

    public static function menu() {
        add_submenu_page(
            Parcs_HT_Admin::PAGE,
            'Guides & groupes',
            'Guides & groupes',
            'manage_options',
            self::PAGE,
            array(__CLASS__, 'page')
        );
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
                var th=document.createElement('th'); th.textContent=row[0]; tr.appendChild(th);
                ['fr','en','de'].forEach(function(lang){
                    var td=document.createElement('td');
                    var code=document.createElement('code');
                    code.textContent='['+row[1]+'_'+lang+']';
                    td.appendChild(code); tr.appendChild(td);
                });
                body.appendChild(tr);
            });
        }());
        </script>
        <?php
    }

    public static function page() {
        if (!current_user_can('manage_options')) return;

        $guides_url = add_query_arg(
            array('page' => Parcs_HT_Pedagogical_Guides::PAGE),
            admin_url('admin.php')
        );
        ?>
        <div class="wrap">
            <h1>Guides & groupes</h1>
            <p>Les nouveaux modules de l’extension sont regroupés ici pour être directement accessibles après une mise à jour.</p>

            <div class="card" style="max-width:100%;margin-top:20px;">
                <h2>Tarifs groupes uniquement</h2>
                <p>Ce shortcode reprend automatiquement les tarifs du groupe « Groupes » déjà configurés dans l’onglet Tarifs. Il n’y a donc aucune double saisie.</p>
                <table class="widefat striped" style="max-width:900px;">
                    <thead><tr><th>Langue</th><th>Shortcode</th></tr></thead>
                    <tbody>
                        <tr><td>Automatique</td><td><code>[parc_tarifs_groupes]</code></td></tr>
                        <tr><td>Français</td><td><code>[parc_tarifs_groupes_fr]</code></td></tr>
                        <tr><td>English</td><td><code>[parc_tarifs_groupes_en]</code></td></tr>
                        <tr><td>Deutsch</td><td><code>[parc_tarifs_groupes_de]</code></td></tr>
                    </tbody>
                </table>
                <p><strong>Option :</strong> utilisez <code>titre="0"</code> pour masquer le titre du bloc, par exemple <code>[parc_tarifs_groupes_fr titre="0"]</code>.</p>
            </div>

            <div class="card" style="max-width:100%;margin-top:20px;">
                <h2>Guides pédagogiques</h2>
                <p>La bibliothèque permet de créer les catégories/cycles, choisir les langues, ajouter les PDF et couvertures, définir l’ordre et les statuts « Disponible », « Nouveau » ou « À venir ».</p>
                <p><a class="button button-primary" href="<?php echo esc_url($guides_url); ?>">Gérer les guides pédagogiques</a></p>
                <table class="widefat striped" style="max-width:900px;">
                    <thead><tr><th>Langue</th><th>Shortcode</th></tr></thead>
                    <tbody>
                        <tr><td>Automatique</td><td><code>[parc_guides_pedagogiques]</code></td></tr>
                        <tr><td>Français</td><td><code>[parc_guides_pedagogiques_fr]</code></td></tr>
                        <tr><td>English</td><td><code>[parc_guides_pedagogiques_en]</code></td></tr>
                        <tr><td>Deutsch</td><td><code>[parc_guides_pedagogiques_de]</code></td></tr>
                    </tbody>
                </table>
            </div>

            <div class="notice notice-info inline" style="margin-top:20px;">
                <p>Ces deux modules sont indépendants du module « Devis groupe » existant : ils ajoutent un affichage public des tarifs groupes et une bibliothèque de ressources pédagogiques.</p>
            </div>
        </div>
        <?php
    }
}
