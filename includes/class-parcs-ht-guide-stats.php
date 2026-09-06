<?php

if (!defined('ABSPATH')) { exit; }

/** Comptage anonyme des clics sur les guides pédagogiques et registre permanent de leurs identifiants. */
final class Parcs_HT_Guide_Stats {
    const ID_OPTION = 'parcs_ht_pedagogical_guide_ids';
    const META_OPTION = 'parcs_ht_pedagogical_guide_stats_meta';
    const DB_VERSION_OPTION = 'parcs_ht_pedagogical_guide_stats_db_version';
    const DB_VERSION = '1';

    public static function init() {
        add_action('admin_init', array(__CLASS__, 'maybe_install'), 2);
        add_action('admin_init', array(__CLASS__, 'ensure_store_ids'), 3);
        add_action('updated_option', array(__CLASS__, 'after_guides_option_update'), 30, 3);
        add_action('added_option', array(__CLASS__, 'after_guides_option_add'), 30, 2);
        add_action('admin_post_parcs_ht_track_guide_click', array(__CLASS__, 'handle_click'));
        add_action('admin_post_nopriv_parcs_ht_track_guide_click', array(__CLASS__, 'handle_click'));
    }

    public static function is_valid_id($id) {
        return (bool)preg_match('/^guide_[0-9]{6,}$/', (string)$id);
    }

    private static function id_number($id) {
        return self::is_valid_id($id) ? (int)substr((string)$id, 6) : 0;
    }

    private static function id_state() {
        $raw = get_option(self::ID_OPTION, array());
        $state = array(
            'next' => max(1, (int)(is_array($raw) ? ($raw['next'] ?? 1) : 1)),
            'used' => is_array($raw) && isset($raw['used']) && is_array($raw['used']) ? $raw['used'] : array(),
        );

        $meta = get_option(self::META_OPTION, array());
        foreach ((array)(is_array($meta) ? ($meta['guides'] ?? array()) : array()) as $id => $unused) {
            if (self::is_valid_id($id)) $state['used'][$id] = 1;
        }

        if (class_exists('Parcs_HT_Pedagogical_Guides')) {
            $store = get_option(Parcs_HT_Pedagogical_Guides::OPTION, array());
            foreach ((array)(is_array($store) ? ($store['seasons'] ?? array()) : array()) as $library) {
                foreach ((array)(is_array($library) ? ($library['guides'] ?? array()) : array()) as $guide) {
                    $id = sanitize_key(is_array($guide) ? ($guide['id'] ?? '') : '');
                    if (self::is_valid_id($id)) $state['used'][$id] = 1;
                }
            }
        }

        foreach (array_keys($state['used']) as $id) {
            $number = self::id_number($id);
            if ($number >= $state['next']) $state['next'] = $number + 1;
        }
        return $state;
    }

    private static function save_id_state($state) {
        update_option(self::ID_OPTION, array(
            'next' => max(1, (int)($state['next'] ?? 1)),
            'used' => is_array($state['used'] ?? null) ? $state['used'] : array(),
        ), false);
    }

    public static function claim_id($candidate = '') {
        $candidate = sanitize_key((string)$candidate);
        if (!self::is_valid_id($candidate)) return self::allocate_id();

        $state = self::id_state();
        $state['used'][$candidate] = 1;
        $number = self::id_number($candidate);
        if ($number >= $state['next']) $state['next'] = $number + 1;
        self::save_id_state($state);
        return $candidate;
    }

    public static function allocate_id() {
        $state = self::id_state();
        do {
            $id = 'guide_' . str_pad((string)$state['next'], 6, '0', STR_PAD_LEFT);
            $state['next']++;
        } while (isset($state['used'][$id]));
        $state['used'][$id] = 1;
        self::save_id_state($state);
        return $id;
    }

    public static function is_reserved_id($id) {
        $id = sanitize_key((string)$id);
        if (!self::is_valid_id($id)) return false;
        $state = self::id_state();
        return isset($state['used'][$id]);
    }

    public static function clone_library_with_new_ids($library) {
        $library = is_array($library) ? $library : array('guides'=>array());
        foreach ((array)($library['guides'] ?? array()) as $index => $guide) {
            if (!is_array($guide)) continue;
            $guide['id'] = self::allocate_id();
            $library['guides'][$index] = $guide;
        }
        return $library;
    }

    public static function ensure_store_ids() {
        if (!class_exists('Parcs_HT_Pedagogical_Guides')) return;
        $store = get_option(Parcs_HT_Pedagogical_Guides::OPTION, array());
        if (!is_array($store) || !isset($store['seasons']) || !is_array($store['seasons'])) return;

        $changed = false;
        $seen = array();
        foreach ($store['seasons'] as $year => $library) {
            if (!is_array($library)) continue;
            foreach ((array)($library['guides'] ?? array()) as $index => $guide) {
                if (!is_array($guide)) continue;
                $id = sanitize_key($guide['id'] ?? '');
                if (!self::is_valid_id($id) || isset($seen[$id])) {
                    $id = self::allocate_id();
                    $store['seasons'][$year]['guides'][$index]['id'] = $id;
                    $changed = true;
                } else {
                    self::claim_id($id);
                }
                $seen[$id] = true;
            }
        }

        if ($changed) update_option(Parcs_HT_Pedagogical_Guides::OPTION, $store, false);
        else self::sync_metadata_from_store($store);
    }

    private static function clean_title($title) {
        $title = is_array($title) ? $title : array();
        return array(
            'fr' => sanitize_text_field((string)($title['fr'] ?? '')),
            'en' => sanitize_text_field((string)($title['en'] ?? '')),
            'de' => sanitize_text_field((string)($title['de'] ?? '')),
        );
    }

    private static function meta_store() {
        $meta = get_option(self::META_OPTION, array());
        return array(
            'version' => 1,
            'guides' => is_array($meta) && isset($meta['guides']) && is_array($meta['guides']) ? $meta['guides'] : array(),
        );
    }

    public static function sync_metadata_from_store($store) {
        $store = is_array($store) ? $store : array();
        $meta = self::meta_store();
        $active = array();
        $now = time();

        foreach ((array)($store['seasons'] ?? array()) as $year => $library) {
            if (!preg_match('/^20\\d{2}$/', (string)$year) || !is_array($library)) continue;
            foreach ((array)($library['guides'] ?? array()) as $guide) {
                if (!is_array($guide)) continue;
                $id = sanitize_key($guide['id'] ?? '');
                if (!self::is_valid_id($id)) continue;
                $active[$id] = true;
                $previous = is_array($meta['guides'][$id] ?? null) ? $meta['guides'][$id] : array();
                $years = array_values(array_unique(array_merge((array)($previous['years'] ?? array()), array((string)$year))));
                sort($years, SORT_STRING);
                $meta['guides'][$id] = array(
                    'title' => self::clean_title($guide['title'] ?? array()),
                    'cycle' => sanitize_key($guide['cycle'] ?? ''),
                    'created_at' => (int)($previous['created_at'] ?? $now),
                    'deleted_at' => 0,
                    'years' => $years,
                    'last_year' => (string)$year,
                );
            }
        }

        foreach ($meta['guides'] as $id => $guide_meta) {
            if (isset($active[$id]) || !is_array($guide_meta)) continue;
            if (empty($guide_meta['deleted_at'])) $meta['guides'][$id]['deleted_at'] = $now;
        }
        update_option(self::META_OPTION, $meta, false);
    }

    public static function after_guides_option_update($option, $old_value, $new_value) {
        unset($old_value);
        if (!class_exists('Parcs_HT_Pedagogical_Guides') || $option !== Parcs_HT_Pedagogical_Guides::OPTION || !is_array($new_value)) return;
        self::sync_metadata_from_store($new_value);
    }

    public static function after_guides_option_add($option, $value) {
        if (!class_exists('Parcs_HT_Pedagogical_Guides') || $option !== Parcs_HT_Pedagogical_Guides::OPTION || !is_array($value)) return;
        self::sync_metadata_from_store($value);
    }

    private static function table_name() {
        global $wpdb;
        return $wpdb->prefix . 'parcs_ht_guide_clicks';
    }

    public static function maybe_install() {
        if ((string)get_option(self::DB_VERSION_OPTION, '') === self::DB_VERSION) return;
        global $wpdb;
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        $table = self::table_name();
        $charset = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE {$table} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            guide_id varchar(32) NOT NULL,
            season_year smallint(5) unsigned NOT NULL,
            click_date date NOT NULL,
            action_type varchar(12) NOT NULL,
            language char(2) NOT NULL,
            clicks bigint(20) unsigned NOT NULL DEFAULT 0,
            PRIMARY KEY  (id),
            UNIQUE KEY guide_event (guide_id, season_year, click_date, action_type, language),
            KEY season_date (season_year, click_date)
        ) {$charset};";
        dbDelta($sql);
        update_option(self::DB_VERSION_OPTION, self::DB_VERSION, false);
    }

    public static function tracking_token($id, $click_type, $language, $year) {
        $payload = implode('|', array(sanitize_key($id), sanitize_key($click_type), sanitize_key($language), (string)$year));
        return hash_hmac('sha256', $payload, wp_salt('auth'));
    }

    private static function no_content() {
        if (function_exists('status_header')) status_header(204);
        exit;
    }

    public static function handle_click() {
        $id = isset($_POST['guide_id']) ? sanitize_key(wp_unslash($_POST['guide_id'])) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Événement public signé, sans donnée personnelle.
        $click_type = isset($_POST['click_type']) ? sanitize_key(wp_unslash($_POST['click_type'])) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing
        $language = isset($_POST['language']) ? sanitize_key(wp_unslash($_POST['language'])) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing
        $year = isset($_POST['season_year']) ? sanitize_text_field(wp_unslash($_POST['season_year'])) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing
        $token = isset($_POST['token']) ? sanitize_text_field(wp_unslash($_POST['token'])) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing

        if (!self::is_valid_id($id) || !self::is_reserved_id($id)) self::no_content();
        if (!in_array($click_type, array('view','download'), true)) self::no_content();
        if (!in_array($language, array('fr','de','en'), true)) self::no_content();
        if (!preg_match('/^20\\d{2}$/', $year)) self::no_content();
        $expected = self::tracking_token($id, $click_type, $language, $year);
        if ($token === '' || !hash_equals($expected, $token)) self::no_content();

        self::maybe_install();
        global $wpdb;
        $table = self::table_name();
        $date = wp_date('Y-m-d');
        $sql = $wpdb->prepare(
            "INSERT INTO {$table} (guide_id, season_year, click_date, action_type, language, clicks)
             VALUES (%s, %d, %s, %s, %s, 1)
             ON DUPLICATE KEY UPDATE clicks = clicks + 1",
            $id,
            (int)$year,
            $date,
            $click_type,
            $language
        );
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared -- Agrégat atomique préparé ; aucun objet WordPress n'est mis en cache pour cette table dédiée.
        $wpdb->query($sql);
        self::no_content();
    }

    private static function display_title($meta, $id) {
        $title = is_array($meta) ? (array)($meta['title'] ?? array()) : array();
        foreach (array('fr','en','de') as $lang) {
            $value = trim((string)($title[$lang] ?? ''));
            if ($value !== '') return $value;
        }
        return $id;
    }

    private static function aggregate_rows($year, $range) {
        global $wpdb;
        $table = self::table_name();
        $fields = "guide_id,
            SUM(CASE WHEN action_type='view' THEN clicks ELSE 0 END) AS views,
            SUM(CASE WHEN action_type='download' THEN clicks ELSE 0 END) AS downloads,
            SUM(CASE WHEN language='fr' THEN clicks ELSE 0 END) AS fr_clicks,
            SUM(CASE WHEN language='de' THEN clicks ELSE 0 END) AS de_clicks,
            SUM(CASE WHEN language='en' THEN clicks ELSE 0 END) AS en_clicks";

        if ($range === 'all') {
            $sql = "SELECT {$fields} FROM {$table} GROUP BY guide_id";
        } elseif ($range === '7' || $range === '30') {
            $days = (int)$range;
            $since = wp_date('Y-m-d', time() - (($days - 1) * DAY_IN_SECONDS));
            $sql = $wpdb->prepare("SELECT {$fields} FROM {$table} WHERE season_year = %d AND click_date >= %s GROUP BY guide_id", (int)$year, $since);
        } else {
            $sql = $wpdb->prepare("SELECT {$fields} FROM {$table} WHERE season_year = %d GROUP BY guide_id", (int)$year);
        }

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared -- Lecture agrégée d'une table statistique dédiée, sans cache d'objet WordPress.
        $rows = $wpdb->get_results($sql, ARRAY_A);
        $out = array();
        foreach ((array)$rows as $row) {
            $id = sanitize_key($row['guide_id'] ?? '');
            if (!self::is_valid_id($id)) continue;
            $out[$id] = array(
                'views'=>(int)($row['views'] ?? 0),
                'downloads'=>(int)($row['downloads'] ?? 0),
                'fr'=>(int)($row['fr_clicks'] ?? 0),
                'de'=>(int)($row['de_clicks'] ?? 0),
                'en'=>(int)($row['en_clicks'] ?? 0),
            );
        }
        return $out;
    }

    public static function render_admin_panel($year) {
        if (!current_user_can('manage_options')) return;
        self::maybe_install();
        $year = preg_match('/^20\\d{2}$/', (string)$year) ? (string)$year : (string)wp_date('Y');
        $range = isset($_GET['guide_stats_range']) ? sanitize_key(wp_unslash($_GET['guide_stats_range'])) : '30'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Filtre de lecture uniquement.
        if (!in_array($range, array('7','30','season','all'), true)) $range = '30';

        $counts = self::aggregate_rows($year, $range);
        $meta_store = self::meta_store();
        $meta_guides = $meta_store['guides'];
        $active_ids = array();
        if (class_exists('Parcs_HT_Pedagogical_Guides')) {
            $library = Parcs_HT_Pedagogical_Guides::settings($year);
            foreach ((array)($library['guides'] ?? array()) as $guide) {
                $id = sanitize_key(is_array($guide) ? ($guide['id'] ?? '') : '');
                if (self::is_valid_id($id)) $active_ids[$id] = true;
            }
        }

        $ids = array_keys($counts);
        foreach ($meta_guides as $id => $guide_meta) {
            if (!self::is_valid_id($id) || !is_array($guide_meta)) continue;
            if ($range === 'all' || in_array($year, array_map('strval', (array)($guide_meta['years'] ?? array())), true) || isset($active_ids[$id])) $ids[] = $id;
        }
        $ids = array_values(array_unique($ids));

        $rows = array();
        foreach ($ids as $id) {
            $m = is_array($meta_guides[$id] ?? null) ? $meta_guides[$id] : array();
            $c = $counts[$id] ?? array('views'=>0,'downloads'=>0,'fr'=>0,'de'=>0,'en'=>0);
            $rows[] = array(
                'id'=>$id,
                'title'=>self::display_title($m, $id),
                'views'=>(int)$c['views'],
                'downloads'=>(int)$c['downloads'],
                'total'=>(int)$c['views'] + (int)$c['downloads'],
                'fr'=>(int)$c['fr'],
                'de'=>(int)$c['de'],
                'en'=>(int)$c['en'],
                'deleted'=>!empty($m['deleted_at']) && !isset($active_ids[$id]),
            );
        }
        usort($rows, static function ($a, $b) {
            if ($a['total'] !== $b['total']) return $b['total'] <=> $a['total'];
            return strcasecmp($a['title'], $b['title']);
        });

        $labels = array('7'=>'7 jours','30'=>'30 jours','season'=>'Saison '.$year,'all'=>'Toutes saisons');
        ?>
        <section class="htp-guide-admin-card htp-guide-stats">
            <div class="htp-guide-admin-head"><h3>Statistiques des guides</h3></div>
            <p class="description">Comptage anonyme des clics sur « Consulter » et « Télécharger le PDF ». L’extension n’enregistre ni adresse IP ni cookie pour ces statistiques. Le suivi commence avec l’activation de ce module et ne reconstitue pas les clics antérieurs.</p>
            <p class="htp-guide-stats-ranges"><?php foreach ($labels as $key => $label): $url=add_query_arg(array('page'=>Parcs_HT_Admin::PAGE,'season'=>$year,'tab'=>'htp-guides','guide_stats_range'=>$key),admin_url('admin.php')); ?><a class="button<?php echo $range===$key?' button-primary':'';?>" href="<?php echo esc_url($url);?>"><?php echo esc_html($label);?></a> <?php endforeach;?></p>
            <table class="widefat striped">
                <thead><tr><th>Guide</th><th>Consulter</th><th>Télécharger</th><th>Total</th><th>FR</th><th>DE</th><th>EN</th><th>État</th></tr></thead>
                <tbody>
                <?php if (!$rows):?><tr><td colspan="8">Aucun clic enregistré pour cette période.</td></tr><?php else: foreach ($rows as $row):?>
                    <tr><td><strong><?php echo esc_html($row['title']);?></strong><br><code><?php echo esc_html($row['id']);?></code></td><td><?php echo (int)$row['views'];?></td><td><?php echo (int)$row['downloads'];?></td><td><strong><?php echo (int)$row['total'];?></strong></td><td><?php echo (int)$row['fr'];?></td><td><?php echo (int)$row['de'];?></td><td><?php echo (int)$row['en'];?></td><td><?php echo esc_html($row['deleted']?'Supprimé':'Actif');?></td></tr>
                <?php endforeach; endif;?>
                </tbody>
            </table>
        </section>
        <?php
    }
}
