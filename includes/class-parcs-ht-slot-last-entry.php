<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Compatibilité 1.8.2 : ajoute une dernière entrée propre au créneau 2 sans
 * restructurer les lignes d'horaires historiques. Le champ historique
 * last_entry_minutes reste celui du créneau 1 ; last_entry_minutes2 est ajouté
 * de façon additive et conservé lors de toutes les sauvegardes.
 */
final class Parcs_HT_Slot_Last_Entry {
    private static $posted_slot2 = array();
    private static $save_year = '';

    public static function init() {
        add_action('admin_post_parcs_ht_save', array(__CLASS__, 'prepare_save'), 5);
        add_action('admin_footer', array(__CLASS__, 'admin_fields'), 99);
        add_action('wp_footer', array(__CLASS__, 'enqueue_frontend_patch'), 5);
    }

    public static function prepare_save() {
        if (!current_user_can('manage_options')) return;

        self::$save_year = isset($_POST['season_year']) ? sanitize_text_field(wp_unslash($_POST['season_year'])) : '';
        $raw = isset($_POST['settings']) && is_array($_POST['settings']) ? wp_unslash($_POST['settings']) : array();
        self::$posted_slot2 = array('regular_periods'=>array(), 'exceptions'=>array());

        foreach (array('regular_periods','exceptions') as $list) {
            foreach ((array)($raw[$list] ?? array()) as $index=>$row) {
                if (!is_array($row) || !array_key_exists('last_entry_minutes2', $row)) continue;
                self::$posted_slot2[$list][(string)$index] = self::clean_minutes((string)$row['last_entry_minutes2']);
            }
        }

        add_filter('pre_update_option_' . Parcs_HT_Defaults::OPTION, array(__CLASS__, 'preserve_slot2'), 10, 3);
    }

    public static function preserve_slot2($new_value, $old_value, $option) {
        unset($option);
        if (!is_array($new_value)) return $new_value;
        $old_value = is_array($old_value) ? $old_value : array();

        // Toute sauvegarde, même d'un autre onglet, conserve le nouveau champ.
        foreach ((array)($new_value['seasons'] ?? array()) as $year=>&$season) {
            if (!is_array($season)) continue;
            foreach (array('regular_periods','exceptions') as $list) {
                if (!isset($season[$list]) || !is_array($season[$list])) continue;
                foreach ($season[$list] as $index=>&$row) {
                    if (!is_array($row)) continue;
                    $old = $old_value['seasons'][$year][$list][$index]['last_entry_minutes2'] ?? null;
                    if ($old !== null && !array_key_exists('last_entry_minutes2', $row)) {
                        $row['last_entry_minutes2'] = self::clean_minutes((string)$old);
                    }
                }
                unset($row);
            }
        }
        unset($season);

        // Si les horaires ont été envoyés, les valeurs saisies prennent le dessus.
        $year = self::$save_year;
        if ($year !== '' && isset($new_value['seasons'][$year])) {
            foreach (self::$posted_slot2 as $list=>$values) {
                foreach ($values as $index=>$value) {
                    if (isset($new_value['seasons'][$year][$list][$index]) && is_array($new_value['seasons'][$year][$list][$index])) {
                        $new_value['seasons'][$year][$list][$index]['last_entry_minutes2'] = $value;
                    }
                }
            }
        }
        return $new_value;
    }

    private static function clean_minutes($value) {
        $value = trim((string)$value);
        if ($value === '') return '';
        return (string) max(0, min(1440, (int)$value));
    }

    public static function admin_fields() {
        if (!is_admin() || !current_user_can('manage_options')) return;
        if (!isset($_GET['page']) || sanitize_key(wp_unslash($_GET['page'])) !== Parcs_HT_Admin::PAGE) return;

        $year = isset($_GET['season']) ? sanitize_text_field(wp_unslash($_GET['season'])) : '';
        $settings = Parcs_HT_Defaults::settings($year);
        $slot2 = array('regular_periods'=>array(), 'exceptions'=>array());
        foreach (array('regular_periods','exceptions') as $list) {
            foreach ((array)($settings[$list] ?? array()) as $index=>$row) {
                $slot2[$list][(string)$index] = isset($row['last_entry_minutes2']) ? (string)$row['last_entry_minutes2'] : '';
            }
        }
        ?>
        <script>
        (function(){
            'use strict';
            var stored=<?php echo wp_json_encode($slot2); ?>;
            function labelText(input,text){var label=input&&input.closest('label');var span=label&&label.querySelector(':scope > span');if(span)span.textContent=text;}
            function processInput(input){
                if(!input||input.dataset.htpSlotEntryReady==='1')return;
                var name=input.getAttribute('name')||'';
                var match=name.match(/^settings\[(regular_periods|exceptions)\]\[([^\]]+)\]\[last_entry_minutes\]$/);
                if(!match)return;
                input.dataset.htpSlotEntryReady='1';
                labelText(input,'Dernière entrée créneau 1 (minutes)');
                var base=input.closest('label');if(!base||!base.parentNode)return;
                var label=document.createElement('label');label.className='htp-field';
                var span=document.createElement('span');span.textContent='Dernière entrée créneau 2 (minutes, facultatif)';
                var second=document.createElement('input');second.type='number';second.min='0';second.max='1440';
                second.name=name.replace('[last_entry_minutes]','[last_entry_minutes2]');
                var map=stored[match[1]]||{};second.value=Object.prototype.hasOwnProperty.call(map,match[2])?map[match[2]]:'';
                label.appendChild(span);label.appendChild(second);base.insertAdjacentElement('afterend',label);
            }
            function scan(root){(root||document).querySelectorAll('input[name$="[last_entry_minutes]"]').forEach(processInput);}
            var global=document.querySelector('input[name="settings[general][last_entry_minutes]"]');
            if(global)labelText(global,'Dernière entrée par défaut (minutes avant fermeture)');
            scan(document);
            new MutationObserver(function(mutations){mutations.forEach(function(m){m.addedNodes.forEach(function(node){if(node.nodeType===1){if(node.matches&&node.matches('input[name$="[last_entry_minutes]"]'))processInput(node);scan(node);}});});}).observe(document.body,{childList:true,subtree:true});
        }());
        </script>
        <?php
    }

    public static function enqueue_frontend_patch() {
        if (!wp_script_is('parcs-ht-frontend', 'enqueued')) return;
        wp_enqueue_script(
            'parcs-ht-slot-last-entry',
            PARCS_HT_URL . 'assets/slot-last-entry.js',
            array('parcs-ht-frontend'),
            PARCS_HT_VERSION,
            true
        );
    }
}
