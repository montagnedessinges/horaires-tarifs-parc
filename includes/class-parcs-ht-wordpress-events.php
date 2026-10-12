<?php

if (!defined('ABSPATH')) { exit; }

/** Read-only adapter: WordPress owns event dates, titles and permalinks. */
final class Parcs_HT_WordPress_Events {
    const OPTION = 'parcs_ht_wordpress_events';
    private static $cache = array();
    private static $ids = null;
    private static $saving_appearance = false;

    public static function init() {
        add_action('admin_post_parcs_ht_wordpress_events_save', array(__CLASS__, 'save'));
        add_action('add_meta_boxes', array(__CLASS__, 'register_metabox'));
        add_action('save_post', array(__CLASS__, 'save_metabox'), 10, 2);
        add_action('save_post', array(__CLASS__, 'invalidate'), 20);
        add_action('before_delete_post', array(__CLASS__, 'invalidate'));
        foreach (array('added_post_meta','updated_post_meta','deleted_post_meta') as $hook) {
            add_action($hook, array(__CLASS__, 'meta_changed'), 20, 4);
        }
    }

    public static function settings() {
        $saved = get_option(self::OPTION, array());
        return array_replace(array('enabled'=>'1', 'post_type'=>'evenement'), is_array($saved) ? $saved : array());
    }

    public static function date($raw) {
        if (!is_scalar($raw)) return '';
        $raw = trim((string)$raw);
        if (preg_match('/^(\d{4})(\d{2})(\d{2})$/', $raw, $m) || preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $raw, $m)) {
            return checkdate((int)$m[2], (int)$m[3], (int)$m[1]) ? $m[1].'-'.$m[2].'-'.$m[3] : '';
        }
        return '';
    }

    public static function invalidate($post_id) {
        if (get_post_type($post_id) !== self::settings()['post_type']) return;
        self::purge();
    }

    private static function purge() {
        self::$cache = array();
        self::$ids = null;
        update_option('parcs_ht_export_revision', (int)get_option('parcs_ht_export_revision', 1) + 1, false);
        do_action('litespeed_purge_all');
    }

    public static function meta_changed($meta_id, $post_id, $key, $value) {
        unset($meta_id, $value);
        if (self::$saving_appearance && strpos((string)$key, '_parcs_ht_event_calendar_') === 0) return;
        if (in_array($key, array('date_debut','date_fin','_parcs_ht_event_calendar_enabled','_parcs_ht_event_calendar_color','_parcs_ht_event_calendar_marker'), true)) self::invalidate($post_id);
    }

    /**
     * Presentation preferences are private post meta: the theme keeps its own
     * ACF date_debut / date_fin, WP_Query and homepage widgets untouched.
     */
    public static function appearance($post_id) {
        $visible = (string)get_post_meta($post_id, '_parcs_ht_event_calendar_enabled', true);
        $color = (string)get_post_meta($post_id, '_parcs_ht_event_calendar_color', true);
        $marker = (string)get_post_meta($post_id, '_parcs_ht_event_calendar_marker', true);
        if (!preg_match('/^#[a-fA-F0-9]{6}$/D', $color)) $color = '#e7c55b';
        if (!in_array($marker, array('auto','star','dot'), true)) $marker = 'auto';
        return array('enabled'=>$visible !== '0', 'color'=>$color, 'marker'=>$marker);
    }

    public static function register_metabox($post_type) {
        if ((string)$post_type !== (string)self::settings()['post_type']) return;
        add_meta_box('parcs-ht-event-calendar-options', 'Affichage dans le calendrier', array(__CLASS__, 'render_metabox'), $post_type, 'side', 'default');
    }

    public static function render_metabox($post) {
        $style = self::appearance($post->ID);
        wp_nonce_field('parcs_ht_event_calendar_save', 'parcs_ht_event_calendar_nonce');
        ?>
        <div id="parcs-ht-event-calendar-options-fields">
            <p><label><input type="checkbox" name="parcs_ht_event_calendar_enabled" value="1" <?php checked($style['enabled'], true); ?>> Afficher dans le calendrier du parc</label></p>
            <p><label for="parcs-ht-event-calendar-color"><strong>Couleur du marqueur</strong></label><br>
                <input type="color" id="parcs-ht-event-calendar-color" name="parcs_ht_event_calendar_color" value="<?php echo esc_attr($style['color']); ?>"></p>
            <p><label for="parcs-ht-event-calendar-marker"><strong>Type de marqueur</strong></label><br>
                <select name="parcs_ht_event_calendar_marker" id="parcs-ht-event-calendar-marker" style="width:100%">
                    <option value="auto" <?php selected($style['marker'], 'auto'); ?>>Automatique selon la durée</option>
                    <option value="star" <?php selected($style['marker'], 'star'); ?>>Grande étoile</option>
                    <option value="dot" <?php selected($style['marker'], 'dot'); ?>>Petit point</option>
                </select></p>
            <p class="description">Automatique : étoile pour une journée, petits points pour plusieurs jours. N'affecte pas la page événement ni les blocs de la page d'accueil.</p>
            <p><strong>Aperçu :</strong> <span id="parcs-ht-event-calendar-preview" style="display:inline-flex;gap:6px;align-items:center;vertical-align:middle"></span></p>
        </div>
        <script>
        (function(){
            var root=document.getElementById('parcs-ht-event-calendar-options-fields'); if(!root)return;
            var color=root.querySelector('[name="parcs_ht_event_calendar_color"]');
            var marker=root.querySelector('[name="parcs_ht_event_calendar_marker"]');
            var preview=document.getElementById('parcs-ht-event-calendar-preview');
            function update(){
                preview.textContent='';
                var mode=marker.value, shade=color.value||'#e7c55b';
                var sample=document.createElement('span');
                sample.style.cssText='display:inline-flex;align-items:center;justify-content:center;background:'+shade+';color:#242424;border-radius:50%;font-weight:700;line-height:1';
                if(mode==='dot'){sample.style.width='9px';sample.style.height='9px';sample.setAttribute('aria-label','Petit point');}
                else {sample.style.width='23px';sample.style.height='23px';sample.textContent='★';}
                preview.appendChild(sample);
                if(mode==='auto') preview.appendChild(document.createTextNode(' / • sur plusieurs jours'));
            }
            color.addEventListener('input',update);marker.addEventListener('change',update);update();
        })();
        </script>
        <?php
    }

    public static function save_metabox($post_id, $post) {
        if (!is_object($post) || $post->post_type !== self::settings()['post_type']) return;
        if (!isset($_POST['parcs_ht_event_calendar_nonce'])) return;
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        if (function_exists('wp_is_post_revision') && wp_is_post_revision($post_id)) return;
        if (!current_user_can('edit_post', $post_id)) return;
        $nonce_raw = wp_unslash($_POST['parcs_ht_event_calendar_nonce']);
        if (!is_string($nonce_raw)) return;
        $nonce = sanitize_text_field($nonce_raw);
        if (!wp_verify_nonce($nonce, 'parcs_ht_event_calendar_save')) return;
        $visible = isset($_POST['parcs_ht_event_calendar_enabled']) ? '1' : '0';
        $color_raw = isset($_POST['parcs_ht_event_calendar_color']) ? wp_unslash($_POST['parcs_ht_event_calendar_color']) : '';
        $color = is_string($color_raw) ? sanitize_text_field($color_raw) : '';
        if (!preg_match('/^#[a-fA-F0-9]{6}$/D', $color)) $color = '#e7c55b';
        $marker_raw = isset($_POST['parcs_ht_event_calendar_marker']) ? wp_unslash($_POST['parcs_ht_event_calendar_marker']) : 'auto';
        $marker = is_string($marker_raw) ? sanitize_key($marker_raw) : 'auto';
        if (!in_array($marker, array('auto','star','dot'), true)) $marker = 'auto';
        self::$saving_appearance = true;
        try {
            update_post_meta($post_id, '_parcs_ht_event_calendar_enabled', $visible);
            update_post_meta($post_id, '_parcs_ht_event_calendar_color', $color);
            update_post_meta($post_id, '_parcs_ht_event_calendar_marker', $marker);
        } finally {
            self::$saving_appearance = false;
        }
        // The existing save_post hook invalidates caches once after this handler.
    }

    public static function rows($year, $language) {
        $year = (string)$year;
        if (!preg_match('/^20\d{2}$/', $year) || !in_array($language, array('fr','en','de'), true)) return array();
        $config = self::settings();
        $type = get_post_type_object($config['post_type']);
        if ((string)$config['enabled'] !== '1' || !$type || empty($type->publicly_queryable)) return array();
        $key = $year.'|'.$language;
        if (isset(self::$cache[$key])) return self::$cache[$key];
        // IDs only: no article content, excerpt, image or flexible ACF blocks are projected.
        if (self::$ids === null) {
            $wpml = has_filter('wpml_object_id');
            if ($wpml) do_action('wpml_switch_language', 'all');
            try {
                $query = new WP_Query(array(
                    'post_type'=>$config['post_type'], 'post_status'=>'publish', 'has_password'=>false,
                    'fields'=>'ids', 'posts_per_page'=>-1, 'no_found_rows'=>true,
                    'orderby'=>'ID', 'order'=>'ASC', 'suppress_filters'=>false,
                    'lang'=>'', 'meta_key'=>'date_debut',
                    'update_post_meta_cache'=>false, 'update_post_term_cache'=>false,
                ));
                self::$ids = $query->posts;
            } finally {
                if ($wpml) do_action('wpml_switch_language', null);
            }
        }
        $rows = array();
        $seen = array();
        foreach (self::$ids as $source_id) {
            $id = (int)$source_id;
            if (function_exists('pll_get_post')) {
                $id = (int)pll_get_post($id, $language);
            } elseif (has_filter('wpml_object_id')) {
                $id = (int)apply_filters('wpml_object_id', $id, $config['post_type'], false, $language);
            }
            if (!$id || isset($seen[$id])) continue;
            $seen[$id] = true;
            if (get_post_status($id) !== 'publish' || get_post_field('post_password', $id, 'raw') !== '') continue;
            $start = self::date(get_post_meta($id, 'date_debut', true));
            $raw_end = get_post_meta($id, 'date_fin', true);
            $end = $raw_end === '' ? $start : self::date($raw_end);
            if ($start === '' || $end === '' || $end < $start || $end < $year.'-01-01' || $start > $year.'-12-31') continue;
            $appearance = self::appearance($id);
            if (!$appearance['enabled']) continue;
            $title = (string)get_post_field('post_title', $id, 'raw');
            if (function_exists('qtranxf_use')) $title = qtranxf_use($language, $title, false, true);
            $title = sanitize_text_field($title);
            $url = get_permalink($id);
            if (function_exists('qtranxf_convertURL')) $url = qtranxf_convertURL($url, $language);
            elseif (has_filter('wpml_permalink')) $url = apply_filters('wpml_permalink', $url, $language, true);
            $url = esc_url_raw($url);
            if ($title === '' || $url === '') continue;
            $rows[] = array(
                'source_post_id'=>$id, 'enabled'=>'1', 'kind'=>'event',
                'start'=>$start, 'end'=>$end, 'title'=>array($language=>$title),
                'color'=>$appearance['color'], 'icon'=>'star',
                'display_mode'=>($appearance['marker']==='star' ? 'spot' : (($appearance['marker']==='dot' || $end > $start) ? 'long' : 'spot')),
                'show_on_calendar'=>'1', 'show_button'=>'1',
                'button_label'=>array($language=>array('fr'=>'Voir l’événement','en'=>'View event','de'=>'Veranstaltung ansehen')[$language]),
                'button_url'=>array($language=>$url),
            );
        }
        self::$cache[$key] = $rows;
        return $rows;
    }

    public static function season($season, $year, $language = '') {
        $languages = $language === '' ? array('fr','en','de') : array($language);
        $imported = array();
        foreach ($languages as $lang) {
            foreach (self::rows($year, $lang) as $row) {
                $id = $row['source_post_id'];
                if (!isset($imported[$id])) $imported[$id] = $row;
                else foreach (array('title','button_label','button_url') as $field) $imported[$id][$field] = array_merge($imported[$id][$field], $row[$field]);
            }
        }
        $manual = (array)($season['special_periods'] ?? array());
        // Only reference periods survive; historical manual events remain stored but are never projected.
        $manual = array_values(array_filter($manual, static function($row) { return is_array($row) && !isset($row['source_post_id']) && (string)($row['kind'] ?? 'event') !== 'event'; }));
        $season['special_periods'] = array_merge($manual, array_values($imported));
        return $season;
    }

    public static function admin_form($year) {
        $config = self::settings();
        ?>
        <section class="htp-card">
            <h2>Événements WordPress</h2>
            <p>Les événements publiés alimentent le calendrier avec leur titre, leurs dates et leur lien. Aucun contenu d’article ni pop-up n’est importé. Créez et modifiez vos événements uniquement dans WordPress. Les anciennes saisies manuelles sont conservées en historique, sans affichage.</p>
            <?php if (get_post_type_object($config['post_type'])) : ?>
            <p><a class="button" href="<?php echo esc_url(add_query_arg('post_type', $config['post_type'], admin_url('edit.php'))); ?>">Gérer les événements dans WordPress</a></p>
            <?php endif; ?>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <input type="hidden" name="action" value="parcs_ht_wordpress_events_save">
                <input type="hidden" name="season" value="<?php echo esc_attr($year); ?>">
                <?php wp_nonce_field('parcs_ht_wordpress_events_save'); ?>
                <label><input type="checkbox" name="enabled" value="1" <?php checked($config['enabled'], '1'); ?>> Afficher les événements WordPress dans le calendrier</label>
                <label> Source <select name="post_type">
                    <option value="evenement" <?php selected($config['post_type'], 'evenement'); ?>>Événements (evenement)</option>
                    <?php foreach (get_post_types(array('public'=>true), 'objects') as $type) : if ($type->name === 'evenement' || $type->name === 'attachment') continue; ?>
                    <option value="<?php echo esc_attr($type->name); ?>" <?php selected($config['post_type'], $type->name); ?>><?php echo esc_html($type->label); ?></option>
                    <?php endforeach; ?>
                </select></label>
                <?php submit_button('Enregistrer la source', 'secondary', 'submit', false); ?>
            </form>
            <p class="description">Champs de dates : date_debut et date_fin. Une fin vide correspond à un événement d’un jour. Les dates invalides sont ignorées. Réglage propre à ce site, commun à toutes les années.</p>
        </section>
        <?php
    }

    public static function save() {
        if (!current_user_can('manage_options')) wp_die('Accès refusé.');
        check_admin_referer('parcs_ht_wordpress_events_save');
        $type = isset($_POST['post_type']) ? sanitize_key(wp_unslash($_POST['post_type'])) : '';
        $object = get_post_type_object($type);
        if ($type !== 'evenement' && (!$object || empty($object->publicly_queryable) || $type === 'attachment')) wp_die('Source invalide.');
        $config = array('post_type'=>$type, 'enabled'=>isset($_POST['enabled']) ? '1' : '0');
        update_option(self::OPTION, $config, false);
        self::purge();
        $year = isset($_POST['season']) ? sanitize_text_field(wp_unslash($_POST['season'])) : '';
        wp_safe_redirect(add_query_arg(array('page'=>'parcs-ht-periods','season'=>$year), admin_url('admin.php')));
        exit;
    }
}
