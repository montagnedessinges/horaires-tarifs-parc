<?php

if (!defined('ABSPATH')) { exit; }

/** Calendrier de l'Avent réutilisable par installation WordPress et par année. */
final class Parcs_HT_Advent {
    const OPTION = 'parcs_ht_advent_campaigns';
    const PAGE = 'parcs-ht-advent';
    const DB_VERSION = 1;

    private static $assets_added = false;
    private static $instance = 0;

    public static function init() {
        add_shortcode('parc_calendrier_avent', array(__CLASS__, 'shortcode'));
        foreach (array('fr','en','de') as $language) {
            add_shortcode('parc_calendrier_avent_' . $language, static function ($atts = array()) use ($language) {
                return Parcs_HT_Advent::render($language, is_array($atts) ? $atts : array(), false);
            });
        }

        add_action('wp_ajax_parcs_ht_advent_state', array(__CLASS__, 'ajax_state'));
        add_action('wp_ajax_nopriv_parcs_ht_advent_state', array(__CLASS__, 'ajax_state'));
        add_action('wp_ajax_parcs_ht_advent_day', array(__CLASS__, 'ajax_day'));
        add_action('wp_ajax_nopriv_parcs_ht_advent_day', array(__CLASS__, 'ajax_day'));
        add_action('wp_ajax_parcs_ht_advent_word', array(__CLASS__, 'ajax_word'));
        add_action('wp_ajax_nopriv_parcs_ht_advent_word', array(__CLASS__, 'ajax_word'));
        add_action('wp_ajax_parcs_ht_advent_submit', array(__CLASS__, 'ajax_submit'));
        add_action('wp_ajax_nopriv_parcs_ht_advent_submit', array(__CLASS__, 'ajax_submit'));

        if (is_admin()) {
            add_action('admin_menu', array(__CLASS__, 'menu'));
            add_action('admin_enqueue_scripts', array(__CLASS__, 'admin_assets'), 40);
            add_action('admin_post_parcs_ht_advent_create_campaign', array(__CLASS__, 'create_campaign'));
            add_action('admin_post_parcs_ht_advent_save_campaign', array(__CLASS__, 'save_campaign'));
            add_action('admin_post_parcs_ht_advent_save_day', array(__CLASS__, 'save_day'));
            add_action('admin_post_parcs_ht_advent_save_partners', array(__CLASS__, 'save_partners'));
            add_action('admin_post_parcs_ht_advent_save_teasers', array(__CLASS__, 'save_teasers'));
            add_action('admin_post_parcs_ht_advent_export', array(__CLASS__, 'export_entries'));
        }
    }

    public static function activate() {
        global $wpdb;
        if (!$wpdb) return;
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        $table = self::table_name();
        $charset = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE {$table} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            campaign_year varchar(4) NOT NULL,
            created_at datetime NOT NULL,
            first_name varchar(190) NOT NULL DEFAULT '',
            last_name varchar(190) NOT NULL DEFAULT '',
            email varchar(190) NOT NULL DEFAULT '',
            phone varchar(80) NOT NULL DEFAULT '',
            address varchar(255) NOT NULL DEFAULT '',
            postal_code varchar(40) NOT NULL DEFAULT '',
            city varchar(190) NOT NULL DEFAULT '',
            country varchar(190) NOT NULL DEFAULT '',
            newsletter tinyint(1) NOT NULL DEFAULT 0,
            consent tinyint(1) NOT NULL DEFAULT 0,
            PRIMARY KEY (id),
            KEY campaign_year (campaign_year),
            KEY email (email)
        ) {$charset};";
        dbDelta($sql);
        update_option('parcs_ht_advent_db_version', self::DB_VERSION, false);
    }

    private static function table_name() {
        global $wpdb;
        return $wpdb->prefix . 'parcs_ht_advent_entries';
    }

    private static function translations($value, $textarea = false) {
        $value = is_array($value) ? $value : array();
        $out = array('fr'=>'','en'=>'','de'=>'');
        foreach ($out as $language => $unused) {
            unset($unused);
            $raw = isset($value[$language]) ? (string)$value[$language] : '';
            $out[$language] = $textarea ? sanitize_textarea_field($raw) : sanitize_text_field($raw);
        }
        return $out;
    }

    private static function translation($value, $language, $fallback = '') {
        $value = is_array($value) ? $value : array();
        if (!empty($value[$language])) return (string)$value[$language];
        if (!empty($value['fr'])) return (string)$value['fr'];
        foreach (array('en','de') as $fallback_language) {
            if (!empty($value[$fallback_language])) return (string)$value[$fallback_language];
        }
        return (string)$fallback;
    }

    private static function clean_handle($value) {
        $value = ltrim(trim((string)$value), '@');
        return (string)preg_replace('/[^A-Za-z0-9._\-]/', '', $value);
    }

    private static function clean_datetime($value) {
        $value = str_replace('T', ' ', trim((string)$value));
        if ($value === '') return '';
        return preg_match('/^20\d{2}-\d{2}-\d{2} \d{2}:\d{2}$/', $value) ? $value : '';
    }

    private static function clean_year($value) {
        $value = sanitize_text_field((string)$value);
        return preg_match('/^20\d{2}$/', $value) ? $value : '';
    }

    private static function request_language($value) {
        $language = sanitize_key((string)$value);
        return in_array($language, array('fr','en','de'), true) ? $language : 'fr';
    }

    private static function default_post_template() {
        return "📅 JOUR {day}/24\n🎁 À gagner aujourd’hui : {prize_line}\n➡️ 1 gagnant Instagram + 1 gagnant Facebook\n{partner_description}\n\n❓ QUESTION DU JOUR :\n{question}\n{choices}\n\n📝 Pour participer :\n– Répondre en commentaire 💬\n– Être abonné(e) à {park_handle}\n{partner_follow_line}{friend_tag_line}\n📅 Bonne réponse + gagnants annoncés demain\n➡️ en commentaire + en story 📲\n\n📌 Règlement complet du jeu :\n👉 {regulation_url}\n{mystery_block}\n{hashtags}";
    }

    private static function default_result_template() {
        return "✅ La bonne réponse était : {correct_answer}\n\n{answer_explanation}\n\nGagnant Instagram : {winner_instagram}\nGagnant Facebook : {winner_facebook}\n\n{partner_thanks}\n{eligibility_reminder}\n➡️ La nouvelle question du jour est déjà en ligne, vous pouvez participer dès maintenant ! 🎄✨\n\n{hashtags}";
    }

    private static function day_defaults($year, $day) {
        return array(
            'day'=>(string)(int)$day,
            'enabled'=>'1',
            'open_at'=>sprintf('%s-12-%02d 00:00', $year, (int)$day),
            'image_url'=>'',
            'image_alt'=>array('fr'=>'','en'=>'','de'=>''),
            'title'=>array('fr'=>'','en'=>'','de'=>''),
            'question'=>array('fr'=>'','en'=>'','de'=>''),
            'game_type'=>'qcm',
            'choices'=>array(
                'A'=>array('fr'=>'','en'=>'','de'=>''),
                'B'=>array('fr'=>'','en'=>'','de'=>''),
                'C'=>array('fr'=>'','en'=>'','de'=>''),
                'D'=>array('fr'=>'','en'=>'','de'=>''),
            ),
            'correct_choice'=>'',
            'explanation'=>array('fr'=>'','en'=>'','de'=>''),
            'partner_id'=>'',
            'prize'=>array('fr'=>'','en'=>'','de'=>''),
            'hashtags'=>'',
            'instagram_post_url'=>'',
            'facebook_post_url'=>'',
            'result_at'=>'',
            'winner_instagram'=>'',
            'winner_facebook'=>'',
            'has_clue'=>'0',
            'clue_letter'=>'',
            'clue_position'=>'',
            'clue_note'=>array('fr'=>'','en'=>'','de'=>''),
            'show_mystery_reminder'=>'1',
        );
    }

    public static function campaign_defaults($year) {
        $year = self::clean_year($year);
        if ($year === '') $year = (string)wp_date('Y');
        $days = array();
        for ($day = 1; $day <= 24; $day++) $days[(string)$day] = self::day_defaults($year, $day);
        $post = array('fr'=>self::default_post_template(),'en'=>'','de'=>'');
        $result = array('fr'=>self::default_result_template(),'en'=>'','de'=>'');
        return array(
            'year'=>$year,
            'published'=>'0',
            'title'=>array('fr'=>'Calendrier de l’Avent','en'=>'Advent Calendar','de'=>'Adventskalender'),
            'intro'=>array('fr'=>'','en'=>'','de'=>''),
            'start_at'=>$year . '-12-01 00:00',
            'end_at'=>$year . '-12-24 23:59',
            'timezone'=>'Europe/Paris',
            'park_instagram'=>'',
            'park_facebook'=>'',
            'park_instagram_url'=>'',
            'park_facebook_url'=>'',
            'hashtags'=>'',
            'regulation_url'=>'',
            'require_friend_tag'=>'1',
            'mystery_reminder_every_day'=>'1',
            'mystery_icon_url'=>'',
            'mystery_word'=>'',
            'mystery_text'=>array(
                'fr'=>'Repérez les indices cachés dans les visuels du calendrier. Ils vous permettront de reconstituer le mot mystère et de participer au tirage du gros lot.',
                'en'=>'Look for the clues hidden in the calendar visuals. They will help you rebuild the mystery word and enter the grand-prize draw.',
                'de'=>'Sucht nach den Hinweisen in den Kalenderbildern. Damit könnt ihr das Lösungswort zusammensetzen und an der Verlosung des Hauptpreises teilnehmen.',
            ),
            'final_open_at'=>$year . '-12-24 00:00',
            'final_close_at'=>$year . '-12-26 23:59',
            'reveal_at'=>$year . '-12-27 00:00',
            'final_intro'=>array(
                'fr'=>'Vous avez trouvé le mot mystère ? Saisissez-le pour accéder au formulaire du grand tirage.',
                'en'=>'Found the mystery word? Enter it to access the grand-prize form.',
                'de'=>'Habt ihr das Lösungswort gefunden? Gebt es ein, um zum Formular für die Hauptverlosung zu gelangen.',
            ),
            'final_closed'=>array(
                'fr'=>'Les participations au grand jeu sont terminées.',
                'en'=>'Entries for the grand game are now closed.',
                'de'=>'Die Teilnahme am großen Gewinnspiel ist beendet.',
            ),
            'final_winner'=>array('fr'=>'','en'=>'','de'=>''),
            'gdpr_text'=>array(
                'fr'=>'J’accepte que mes données soient utilisées pour gérer ma participation au jeu.',
                'en'=>'I agree that my data may be used to manage my participation in the game.',
                'de'=>'Ich stimme zu, dass meine Daten zur Verwaltung meiner Teilnahme am Gewinnspiel verwendet werden.',
            ),
            'allow_multiple'=>'0',
            'field_phone'=>'0',
            'field_address'=>'0',
            'field_newsletter'=>'0',
            'post_template_instagram'=>$post,
            'post_template_facebook'=>$post,
            'result_template_instagram'=>$result,
            'result_template_facebook'=>$result,
            'partners'=>array(),
            'teasers'=>array(),
            'days'=>$days,
        );
    }

    public static function store() {
        $saved = get_option(self::OPTION, array());
        if (!is_array($saved)) $saved = array();
        if (!isset($saved['campaigns']) || !is_array($saved['campaigns'])) $saved['campaigns'] = array();
        $saved['version'] = 1;
        return $saved;
    }

    public static function campaign($year) {
        $year = self::clean_year($year);
        if ($year === '') return null;
        $store = self::store();
        if (!isset($store['campaigns'][$year]) || !is_array($store['campaigns'][$year])) return null;
        return array_replace_recursive(self::campaign_defaults($year), $store['campaigns'][$year]);
    }

    public static function campaigns() {
        $out = array();
        $store = self::store();
        foreach ($store['campaigns'] as $year => $campaign) {
            $year = self::clean_year($year);
            if ($year === '' || !is_array($campaign)) continue;
            $out[$year] = array_replace_recursive(self::campaign_defaults($year), $campaign);
        }
        ksort($out, SORT_NUMERIC);
        return $out;
    }

    private static function save_campaign_data($year, $campaign) {
        $store = self::store();
        $store['campaigns'][(string)$year] = $campaign;
        ksort($store['campaigns'], SORT_NUMERIC);
        update_option(self::OPTION, $store, false);
        do_action('litespeed_purge_all');
    }

    public static function shortcode($atts = array()) {
        return self::render(Parcs_HT_Schedule::language(), is_array($atts) ? $atts : array(), false);
    }

    public static function render($language, $atts = array(), $preview = false) {
        unset($atts);
        $language = self::request_language($language);
        self::enqueue_front_assets();
        self::$instance++;
        $id = 'parcs-ht-advent-' . self::$instance;
        $preview_attr = $preview && current_user_can('manage_options') ? ' data-htp-advent-preview="1"' : '';
        return '<section id="' . esc_attr($id) . '" class="parcs-ht-advent" data-htp-advent data-lang="' . esc_attr($language) . '"' . $preview_attr . '>' .
            '<div class="parcs-ht-advent-loading" data-advent-loading aria-live="polite">' . esc_html($language === 'de' ? 'Kalender wird geladen…' : ($language === 'en' ? 'Loading calendar…' : 'Chargement du calendrier…')) . '</div>' .
            '<div data-advent-content></div></section>';
    }

    public static function render_preview($language) {
        return self::render($language, array(), true);
    }

    public static function enqueue_front_assets() {
        wp_enqueue_style('parcs-ht-advent', PARCS_HT_URL . 'assets/advent.css', array(), PARCS_HT_VERSION);
        wp_enqueue_script('parcs-ht-advent', PARCS_HT_URL . 'assets/advent.js', array(), PARCS_HT_VERSION, true);
        if (self::$assets_added) return;
        self::$assets_added = true;
        wp_add_inline_script('parcs-ht-advent', 'window.ParcsHTAdventConfig=' . wp_json_encode(array(
            'ajaxUrl'=>admin_url('admin-ajax.php'),
            'nonce'=>wp_create_nonce('parcs_ht_advent_public'),
        )) . ';', 'before');
    }

    private static function timezone($campaign) {
        $name = !empty($campaign['timezone']) ? (string)$campaign['timezone'] : 'Europe/Paris';
        try { return new DateTimeZone($name); }
        catch (Exception $exception) { unset($exception); return new DateTimeZone('Europe/Paris'); }
    }

    private static function effective_now($campaign, $preview_date = '', $preview_time = '') {
        $timezone = self::timezone($campaign);
        if (current_user_can('manage_options') && preg_match('/^20\d{2}-\d{2}-\d{2}$/', $preview_date)) {
            $time = preg_match('/^\d{2}:\d{2}$/', $preview_time) ? $preview_time : '12:00';
            return new DateTimeImmutable($preview_date . ' ' . $time . ':00', $timezone);
        }
        return new DateTimeImmutable(wp_date('Y-m-d H:i:s', null, $timezone), $timezone);
    }

    private static function datetime($campaign, $value) {
        $value = self::clean_datetime($value);
        if ($value === '') return null;
        try { return new DateTimeImmutable($value, self::timezone($campaign)); }
        catch (Exception $exception) { unset($exception); return null; }
    }

    private static function campaign_for_request($preview_date, $posted_year) {
        $year = '';
        if (preg_match('/^(20\d{2})-/', (string)$preview_date, $match)) $year = $match[1];
        $posted_year = self::clean_year($posted_year);
        if ($posted_year !== '') $year = $posted_year;
        if ($year === '') $year = (string)wp_date('Y');
        $campaign = self::campaign($year);
        if (!$campaign) return null;
        if ((string)$campaign['published'] !== '1' && !current_user_can('manage_options')) return null;
        return $campaign;
    }

    private static function phase($campaign, DateTimeImmutable $now) {
        $start = self::datetime($campaign, $campaign['start_at']);
        $end = self::datetime($campaign, $campaign['end_at']);
        $final_open = self::datetime($campaign, $campaign['final_open_at']);
        $final_close = self::datetime($campaign, $campaign['final_close_at']);
        $reveal = self::datetime($campaign, $campaign['reveal_at']);
        if ($start && $now < $start) return 'teaser';
        if ($final_open && $final_close && $now >= $final_open && $now <= $final_close) return 'final';
        if ($end && $now <= $end) return 'calendar';
        if ($reveal && $now >= $reveal) return 'archive';
        if ($end && $now > $end) return 'waiting';
        return 'calendar';
    }

    private static function day_unlocked($campaign, $day, DateTimeImmutable $now) {
        $row = isset($campaign['days'][(string)$day]) ? $campaign['days'][(string)$day] : null;
        if (!is_array($row) || (string)($row['enabled'] ?? '0') !== '1') return false;
        $open = self::datetime($campaign, $row['open_at'] ?? '');
        return !$open || $now >= $open;
    }

    private static function partner($campaign, $id) {
        $id = sanitize_key((string)$id);
        foreach ((array)($campaign['partners'] ?? array()) as $partner) {
            if (is_array($partner) && sanitize_key((string)($partner['id'] ?? '')) === $id) return $partner;
        }
        return null;
    }

    private static function social_url($network, $handle, $override = '') {
        $override = esc_url_raw((string)$override);
        if ($override !== '') return $override;
        $handle = self::clean_handle($handle);
        if ($handle === '') return '';
        if ($network === 'facebook') return 'https://www.facebook.com/' . $handle;
        return 'https://www.instagram.com/' . $handle . '/';
    }

    private static function teaser_for_now($campaign, DateTimeImmutable $now, $language) {
        $selected = null;
        foreach ((array)($campaign['teasers'] ?? array()) as $teaser) {
            if (!is_array($teaser) || (string)($teaser['enabled'] ?? '1') !== '1') continue;
            $at = self::datetime($campaign, $teaser['at'] ?? '');
            if (!$at || $at > $now) continue;
            if (!$selected || $at > $selected['at_object']) {
                $teaser['at_object'] = $at;
                $selected = $teaser;
            }
        }
        if (!$selected) return '';
        $html = '<article class="parcs-ht-advent-teaser">';
        if (!empty($selected['image_url'])) $html .= '<img src="' . esc_url($selected['image_url']) . '" alt="">';
        $title = self::translation($selected['title'] ?? array(), $language, '');
        $text = self::translation($selected['text'] ?? array(), $language, '');
        if ($title !== '') $html .= '<h3>' . esc_html($title) . '</h3>';
        if ($text !== '') $html .= '<p>' . nl2br(esc_html($text)) . '</p>';
        if (!empty($selected['url'])) {
            $button = self::translation($selected['button'] ?? array(), $language, 'En savoir plus');
            $html .= '<a class="parcs-ht-advent-button" href="' . esc_url($selected['url']) . '">' . esc_html($button) . '</a>';
        }
        return $html . '</article>';
    }

    private static function public_request_values() {
        $preview_date = isset($_POST['preview_date']) ? sanitize_text_field(wp_unslash($_POST['preview_date'])) : '';
        $preview_time = isset($_POST['preview_time']) ? sanitize_text_field(wp_unslash($_POST['preview_time'])) : '';
        if (!current_user_can('manage_options')) {
            $preview_date = '';
            $preview_time = '';
        }
        $language = isset($_POST['language']) ? self::request_language(wp_unslash($_POST['language'])) : 'fr';
        $year = isset($_POST['year']) ? self::clean_year(wp_unslash($_POST['year'])) : '';
        return array($preview_date,$preview_time,$language,$year);
    }

    public static function ajax_state() {
        check_ajax_referer('parcs_ht_advent_public', 'nonce');
        list($preview_date,$preview_time,$language,$posted_year) = self::public_request_values();
        $campaign = self::campaign_for_request($preview_date, $posted_year);
        if (!$campaign) {
            $message = $language === 'de' ? 'Für dieses Jahr ist keine Adventskalender-Kampagne verfügbar.' : ($language === 'en' ? 'No Advent Calendar campaign is available for this year.' : 'Aucune campagne de Calendrier de l’Avent n’est disponible pour cette année.');
            wp_send_json_success(array('phase'=>'none','message'=>$message));
        }
        $now = self::effective_now($campaign, $preview_date, $preview_time);
        $phase = self::phase($campaign, $now);
        $days = array();
        for ($day=1; $day<=24; $day++) {
            $row = $campaign['days'][(string)$day] ?? self::day_defaults($campaign['year'], $day);
            $unlocked = self::day_unlocked($campaign, $day, $now);
            $days[] = array(
                'day'=>$day,
                'enabled'=>(string)($row['enabled'] ?? '0') === '1',
                'unlocked'=>$unlocked,
                'hasClue'=>$unlocked && (string)($row['has_clue'] ?? '0') === '1',
            );
        }
        $reveal = self::datetime($campaign, $campaign['reveal_at']);
        $revealed = $reveal && $now >= $reveal;
        wp_send_json_success(array(
            'phase'=>$phase,
            'year'=>$campaign['year'],
            'title'=>self::translation($campaign['title'], $language, 'Calendrier de l’Avent'),
            'intro'=>self::translation($campaign['intro'], $language, ''),
            'days'=>$days,
            'teaserHtml'=>$phase === 'teaser' ? self::teaser_for_now($campaign, $now, $language) : '',
            'revealed'=>$revealed,
            'mysteryWord'=>$revealed ? (string)$campaign['mystery_word'] : '',
            'mysteryIcon'=>$revealed ? esc_url_raw((string)$campaign['mystery_icon_url']) : '',
            'clues'=>$revealed ? self::revealed_clues($campaign) : array(),
            'finalIntro'=>self::translation($campaign['final_intro'], $language, ''),
            'finalClosed'=>self::translation($campaign['final_closed'], $language, ''),
            'finalWinner'=>$revealed ? self::translation($campaign['final_winner'], $language, '') : '',
        ));
    }

    private static function revealed_clues($campaign) {
        $out = array();
        foreach ((array)$campaign['days'] as $day => $row) {
            if (!is_array($row) || (string)($row['has_clue'] ?? '0') !== '1') continue;
            $letter = strtoupper(trim((string)($row['clue_letter'] ?? '')));
            $position = absint($row['clue_position'] ?? 0);
            if ($letter === '' || $position < 1) continue;
            $out[] = array('day'=>(int)$day,'letter'=>$letter,'position'=>$position);
        }
        usort($out, static function ($a, $b) { return $a['position'] <=> $b['position']; });
        return $out;
    }

    public static function ajax_day() {
        check_ajax_referer('parcs_ht_advent_public', 'nonce');
        list($preview_date,$preview_time,$language,$posted_year) = self::public_request_values();
        $campaign = self::campaign_for_request($preview_date, $posted_year);
        $day = isset($_POST['day']) ? absint($_POST['day']) : 0;
        if (!$campaign || $day < 1 || $day > 24) wp_send_json_error(array('message'=>'Journée indisponible.'), 404);
        $now = self::effective_now($campaign, $preview_date, $preview_time);
        if (!self::day_unlocked($campaign, $day, $now)) wp_send_json_error(array('message'=>'Cette case n’est pas encore ouverte.'), 403);
        $row = $campaign['days'][(string)$day];
        wp_send_json_success(array('html'=>self::day_html($campaign, $row, $language, $now)));
    }

    private static function clue_icon_html($campaign) {
        $url = esc_url_raw((string)($campaign['mystery_icon_url'] ?? ''));
        if ($url === '') return '<span aria-hidden="true">🔎</span>';
        return '<img class="parcs-ht-advent-clue-icon" src="' . esc_url($url) . '" alt="" aria-hidden="true">';
    }

    private static function day_html($campaign, $row, $language, DateTimeImmutable $now) {
        $day = (int)($row['day'] ?? 0);
        $question = self::translation($row['question'] ?? array(), $language, '');
        $title = self::translation($row['title'] ?? array(), $language, '');
        $prize = self::translation($row['prize'] ?? array(), $language, '');
        $partner = self::partner($campaign, $row['partner_id'] ?? '');
        $result_at = self::datetime($campaign, $row['result_at'] ?? '');
        $result_visible = $result_at && $now >= $result_at;
        $reveal = self::datetime($campaign, $campaign['reveal_at']);
        $revealed = $reveal && $now >= $reveal;
        ob_start(); ?>
        <article class="parcs-ht-advent-day-detail">
            <header>
                <span class="parcs-ht-advent-day-kicker"><?php echo esc_html(($language==='de'?'TAG ':($language==='en'?'DAY ':'JOUR ')) . $day . '/24'); ?></span>
                <?php if ($title !== '') : ?><h3><?php echo esc_html($title); ?></h3><?php endif; ?>
            </header>
            <?php if (!empty($row['image_url'])) : ?>
                <figure class="parcs-ht-advent-visual"><img src="<?php echo esc_url($row['image_url']); ?>" alt="<?php echo esc_attr(self::translation($row['image_alt'] ?? array(), $language, '')); ?>"></figure>
            <?php endif; ?>
            <?php if ($prize !== '') : ?><p class="parcs-ht-advent-prize"><strong>🎁 <?php echo esc_html($prize); ?></strong></p><?php endif; ?>
            <?php if ($partner) : ?>
                <div class="parcs-ht-advent-partner">
                    <?php if (!empty($partner['logo_url'])) : ?><img src="<?php echo esc_url($partner['logo_url']); ?>" alt=""><?php endif; ?>
                    <div>
                        <strong><?php echo esc_html((string)($partner['name'] ?? '')); ?></strong>
                        <?php $description = self::translation($partner['description'] ?? array(), $language, ''); if ($description !== '') : ?><p><?php echo nl2br(esc_html($description)); ?></p><?php endif; ?>
                        <div class="parcs-ht-advent-partner-links">
                            <?php $instagram_url = self::social_url('instagram', $partner['instagram'] ?? '', $partner['instagram_url'] ?? ''); if ($instagram_url !== '') : ?><a href="<?php echo esc_url($instagram_url); ?>" target="_blank" rel="noopener">Instagram</a><?php endif; ?>
                            <?php $facebook_url = self::social_url('facebook', $partner['facebook'] ?? '', $partner['facebook_url'] ?? ''); if ($facebook_url !== '') : ?><a href="<?php echo esc_url($facebook_url); ?>" target="_blank" rel="noopener">Facebook</a><?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            <?php if ($question !== '') : ?>
                <div class="parcs-ht-advent-question">
                    <h4><?php echo esc_html($language==='de'?'FRAGE DES TAGES':($language==='en'?'QUESTION OF THE DAY':'QUESTION DU JOUR')); ?></h4>
                    <p><?php echo nl2br(esc_html($question)); ?></p>
                    <?php echo self::choices_html($row, $language); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- HTML interne, chaque libellé est échappé dans choices_html. ?>
                </div>
            <?php endif; ?>
            <div class="parcs-ht-advent-actions">
                <?php if (!empty($row['instagram_post_url'])) : ?><a class="parcs-ht-advent-button" href="<?php echo esc_url($row['instagram_post_url']); ?>" target="_blank" rel="noopener">Instagram</a><?php endif; ?>
                <?php if (!empty($row['facebook_post_url'])) : ?><a class="parcs-ht-advent-button" href="<?php echo esc_url($row['facebook_post_url']); ?>" target="_blank" rel="noopener">Facebook</a><?php endif; ?>
            </div>
            <?php if ((string)($row['has_clue'] ?? '0') === '1') : ?>
                <p class="parcs-ht-advent-clue-hint"><?php echo self::clue_icon_html($campaign); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- URL échappée dans clue_icon_html. ?> <?php echo esc_html($language==='de'?'Ein Hinweis versteckt sich in diesem Bild.':($language==='en'?'A mystery-word clue is hidden in this visual.':'Un indice du mot mystère est caché dans ce visuel.')); ?></p>
            <?php endif; ?>
            <?php if ($result_visible) echo self::result_html($row, $language); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- rendu interne entièrement échappé. ?>
            <?php if ($revealed && (string)($row['has_clue'] ?? '0') === '1') : ?>
                <div class="parcs-ht-advent-clue-solution">
                    <?php echo self::clue_icon_html($campaign); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- URL échappée dans clue_icon_html. ?>
                    <strong><?php echo esc_html(strtoupper((string)$row['clue_letter']) . absint($row['clue_position'])); ?></strong>
                    <?php $note = self::translation($row['clue_note'] ?? array(), $language, ''); if ($note !== '') : ?><p><?php echo nl2br(esc_html($note)); ?></p><?php endif; ?>
                </div>
            <?php endif; ?>
        </article>
        <?php
        return ob_get_clean();
    }

    private static function choices_html($row, $language) {
        if (!in_array((string)($row['game_type'] ?? ''), array('qcm','true_false'), true)) return '';
        $html = '<ol class="parcs-ht-advent-choices">';
        foreach (array('A','B','C','D') as $letter) {
            $text = self::translation($row['choices'][$letter] ?? array(), $language, '');
            if ($text === '') continue;
            $html .= '<li><strong>' . esc_html($letter) . '.</strong> ' . esc_html($text) . '</li>';
        }
        return $html . '</ol>';
    }

    private static function correct_answer_text($row, $language) {
        $choice = strtoupper(trim((string)($row['correct_choice'] ?? '')));
        if ($choice !== '' && isset($row['choices'][$choice])) {
            $text = self::translation($row['choices'][$choice], $language, '');
            if ($text !== '') return $choice . ' – ' . $text;
        }
        return $choice;
    }

    private static function result_html($row, $language) {
        $answer = self::correct_answer_text($row, $language);
        $explanation = self::translation($row['explanation'] ?? array(), $language, '');
        ob_start(); ?>
        <div class="parcs-ht-advent-result">
            <h4><?php echo esc_html($language==='de'?'ERGEBNIS':($language==='en'?'RESULT':'RÉSULTAT')); ?></h4>
            <?php if ($answer !== '') : ?><p><strong><?php echo esc_html($answer); ?></strong></p><?php endif; ?>
            <?php if ($explanation !== '') : ?><p><?php echo nl2br(esc_html($explanation)); ?></p><?php endif; ?>
            <div class="parcs-ht-advent-winners">
                <?php if (!empty($row['winner_instagram'])) : ?><p>Instagram : <strong><?php echo esc_html($row['winner_instagram']); ?></strong></p><?php endif; ?>
                <?php if (!empty($row['winner_facebook'])) : ?><p>Facebook : <strong><?php echo esc_html($row['winner_facebook']); ?></strong></p><?php endif; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    public static function ajax_word() {
        check_ajax_referer('parcs_ht_advent_public', 'nonce');
        list($preview_date,$preview_time,$language,$posted_year) = self::public_request_values();
        $campaign = self::campaign_for_request($preview_date, $posted_year);
        if (!$campaign) wp_send_json_error(array('message'=>'Grand jeu indisponible.'), 404);
        $now = self::effective_now($campaign, $preview_date, $preview_time);
        $open = self::datetime($campaign, $campaign['final_open_at']);
        $close = self::datetime($campaign, $campaign['final_close_at']);
        if (!$open || !$close || $now < $open || $now > $close) wp_send_json_error(array('message'=>'Le grand jeu n’est pas ouvert.'), 403);
        $word = isset($_POST['word']) ? sanitize_text_field(wp_unslash($_POST['word'])) : '';
        $normalize = static function ($value) {
            $value = remove_accents(strtoupper(trim((string)$value)));
            return (string)preg_replace('/[^A-Z0-9]/', '', $value);
        };
        $expected = $normalize($campaign['mystery_word']);
        if ($expected === '' || $normalize($word) === '' || !hash_equals($expected, $normalize($word))) {
            wp_send_json_error(array('message'=>'Ce mot ne correspond pas. Vérifiez les indices du calendrier.'), 400);
        }
        $token = self::entry_token($campaign['year'], time() + 1800);
        wp_send_json_success(array('html'=>self::final_form_html($campaign, $token, $language)));
    }

    private static function entry_token($year, $expires) {
        $payload = (string)$year . '|' . (int)$expires;
        return base64_encode($payload . '|' . hash_hmac('sha256', $payload, wp_salt('nonce'))); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode -- encodage d'un jeton signé, pas d'un secret.
    }

    private static function verify_entry_token($token, $year) {
        $decoded = base64_decode((string)$token, true); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode -- décodage du jeton signé interne.
        if (!is_string($decoded)) return false;
        $parts = explode('|', $decoded);
        if (count($parts) !== 3 || (string)$parts[0] !== (string)$year || !ctype_digit((string)$parts[1])) return false;
        if ((int)$parts[1] < time()) return false;
        $payload = $parts[0] . '|' . $parts[1];
        return hash_equals(hash_hmac('sha256', $payload, wp_salt('nonce')), (string)$parts[2]);
    }

    private static function form_labels($language) {
        if ($language === 'de') return array('first'=>'Vorname','last'=>'Name','email'=>'E-Mail','phone'=>'Telefon','address'=>'Adresse','postal'=>'Postleitzahl','city'=>'Ort','country'=>'Land','newsletter'=>'Ich möchte Neuigkeiten des Parks erhalten.','honeypot'=>'Nicht ausfüllen','submit'=>'Teilnahme bestätigen');
        if ($language === 'en') return array('first'=>'First name','last'=>'Last name','email'=>'Email','phone'=>'Phone','address'=>'Address','postal'=>'Postcode','city'=>'City','country'=>'Country','newsletter'=>'I would like to receive news from the park.','honeypot'=>'Do not fill in','submit'=>'Submit my entry');
        return array('first'=>'Prénom','last'=>'Nom','email'=>'E-mail','phone'=>'Téléphone','address'=>'Adresse','postal'=>'Code postal','city'=>'Ville','country'=>'Pays','newsletter'=>'Je souhaite recevoir les actualités du parc.','honeypot'=>'Ne pas remplir','submit'=>'Valider ma participation');
    }

    private static function final_form_html($campaign, $token, $language) {
        $labels = self::form_labels($language);
        $gdpr = self::translation($campaign['gdpr_text'], $language, '');
        ob_start(); ?>
        <form class="parcs-ht-advent-entry" data-advent-entry>
            <input type="hidden" name="token" value="<?php echo esc_attr($token); ?>">
            <input type="hidden" name="year" value="<?php echo esc_attr($campaign['year']); ?>">
            <label><?php echo esc_html($labels['first']); ?> <input type="text" name="first_name" required maxlength="190"></label>
            <label><?php echo esc_html($labels['last']); ?> <input type="text" name="last_name" required maxlength="190"></label>
            <label><?php echo esc_html($labels['email']); ?> <input type="email" name="email" required maxlength="190"></label>
            <?php if ((string)$campaign['field_phone'] === '1') : ?><label><?php echo esc_html($labels['phone']); ?> <input type="text" name="phone" maxlength="80"></label><?php endif; ?>
            <?php if ((string)$campaign['field_address'] === '1') : ?>
                <label><?php echo esc_html($labels['address']); ?> <input type="text" name="address" maxlength="255"></label>
                <label><?php echo esc_html($labels['postal']); ?> <input type="text" name="postal_code" maxlength="40"></label>
                <label><?php echo esc_html($labels['city']); ?> <input type="text" name="city" maxlength="190"></label>
                <label><?php echo esc_html($labels['country']); ?> <input type="text" name="country" maxlength="190"></label>
            <?php endif; ?>
            <label class="parcs-ht-advent-consent"><input type="checkbox" name="consent" value="1" required> <?php echo esc_html($gdpr); ?></label>
            <?php if ((string)$campaign['field_newsletter'] === '1') : ?><label class="parcs-ht-advent-consent"><input type="checkbox" name="newsletter" value="1"> <?php echo esc_html($labels['newsletter']); ?></label><?php endif; ?>
            <label class="parcs-ht-advent-hp" aria-hidden="true"><?php echo esc_html($labels['honeypot']); ?> <input type="text" name="website" tabindex="-1" autocomplete="off"></label>
            <button class="parcs-ht-advent-button" type="submit"><?php echo esc_html($labels['submit']); ?></button>
            <p data-advent-entry-status aria-live="polite"></p>
        </form>
        <?php
        return ob_get_clean();
    }

    public static function ajax_submit() {
        check_ajax_referer('parcs_ht_advent_public', 'nonce');
        $year = isset($_POST['year']) ? self::clean_year(wp_unslash($_POST['year'])) : '';
        $campaign = self::campaign($year);
        if (!$campaign || (string)$campaign['published'] !== '1') wp_send_json_error(array('message'=>'Participation indisponible.'), 404);
        $honeypot = isset($_POST['website']) ? sanitize_text_field(wp_unslash($_POST['website'])) : '';
        if ($honeypot !== '') wp_send_json_error(array('message'=>'Participation refusée.'), 400);
        $token = isset($_POST['token']) ? sanitize_text_field(wp_unslash($_POST['token'])) : '';
        if (!self::verify_entry_token($token, $year)) wp_send_json_error(array('message'=>'Votre validation a expiré. Saisissez de nouveau le mot mystère.'), 403);
        $now = self::effective_now($campaign);
        $open = self::datetime($campaign, $campaign['final_open_at']);
        $close = self::datetime($campaign, $campaign['final_close_at']);
        if (!$open || !$close || $now < $open || $now > $close) wp_send_json_error(array('message'=>'Les participations sont fermées.'), 403);

        $email = isset($_POST['email']) ? sanitize_email(wp_unslash($_POST['email'])) : '';
        $first = isset($_POST['first_name']) ? sanitize_text_field(wp_unslash($_POST['first_name'])) : '';
        $last = isset($_POST['last_name']) ? sanitize_text_field(wp_unslash($_POST['last_name'])) : '';
        $consent = isset($_POST['consent']) && sanitize_text_field(wp_unslash($_POST['consent'])) === '1';
        if ($email === '' || !is_email($email) || $first === '' || $last === '' || !$consent) wp_send_json_error(array('message'=>'Merci de compléter les champs obligatoires.'), 400);

        $phone = isset($_POST['phone']) ? sanitize_text_field(wp_unslash($_POST['phone'])) : '';
        $address = isset($_POST['address']) ? sanitize_text_field(wp_unslash($_POST['address'])) : '';
        $postal_code = isset($_POST['postal_code']) ? sanitize_text_field(wp_unslash($_POST['postal_code'])) : '';
        $city = isset($_POST['city']) ? sanitize_text_field(wp_unslash($_POST['city'])) : '';
        $country = isset($_POST['country']) ? sanitize_text_field(wp_unslash($_POST['country'])) : '';
        $newsletter = isset($_POST['newsletter']) && sanitize_text_field(wp_unslash($_POST['newsletter'])) === '1' ? 1 : 0;

        global $wpdb;
        $table = self::table_name();
        if ((string)$campaign['allow_multiple'] !== '1') {
            $existing = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$table} WHERE campaign_year=%s AND email=%s LIMIT 1", $year, $email)); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- nom de table interne, valeurs préparées.
            if ($existing) wp_send_json_error(array('message'=>'Une participation a déjà été enregistrée avec cette adresse e-mail.'), 409);
        }
        $ok = $wpdb->insert($table, array(
            'campaign_year'=>$year,
            'created_at'=>current_time('mysql'),
            'first_name'=>$first,
            'last_name'=>$last,
            'email'=>$email,
            'phone'=>$phone,
            'address'=>$address,
            'postal_code'=>$postal_code,
            'city'=>$city,
            'country'=>$country,
            'newsletter'=>$newsletter,
            'consent'=>1,
        ), array('%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%d','%d'));
        if ($ok === false) wp_send_json_error(array('message'=>'La participation n’a pas pu être enregistrée.'), 500);
        wp_send_json_success(array('message'=>'Votre participation au grand tirage est bien enregistrée.'));
    }

    public static function menu() {
        if (!class_exists('Parcs_HT_Admin')) return;
        add_submenu_page(Parcs_HT_Admin::PAGE, 'Calendrier de l’Avent', 'Calendrier de l’Avent', 'manage_options', self::PAGE, array(__CLASS__, 'admin_page'));
    }

    public static function admin_assets($hook) {
        $page = isset($_GET['page']) ? sanitize_key(wp_unslash($_GET['page'])) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- sélection de page en lecture seule.
        if ($page === self::PAGE) {
            wp_enqueue_media();
            wp_enqueue_style('parcs-ht-admin-advent', PARCS_HT_URL . 'assets/admin-advent.css', array(), PARCS_HT_VERSION);
            wp_enqueue_script('parcs-ht-admin-advent', PARCS_HT_URL . 'assets/admin-advent.js', array('jquery'), PARCS_HT_VERSION, true);
        }
        if ($hook === 'toplevel_page_parcs-horaires-tarifs') {
            self::enqueue_front_assets();
            wp_enqueue_script('parcs-ht-admin-advent-preview', PARCS_HT_URL . 'assets/admin-advent-preview.js', array('parcs-ht-advent','parcs-ht-admin-shortcode-preview'), PARCS_HT_VERSION, true);
            wp_enqueue_script('parcs-ht-admin-advent-shortcodes', PARCS_HT_URL . 'assets/admin-advent-shortcodes.js', array(), PARCS_HT_VERSION, true);
        }
    }

    private static function admin_year() {
        $campaigns = self::campaigns();
        $year = isset($_GET['advent_year']) ? self::clean_year(wp_unslash($_GET['advent_year'])) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- sélection en lecture seule.
        if ($year !== '' && isset($campaigns[$year])) return $year;
        if (!$campaigns) return '';
        $keys = array_keys($campaigns);
        return (string)end($keys);
    }

    public static function admin_page() {
        if (!current_user_can('manage_options')) return;
        $campaigns = self::campaigns();
        $year = self::admin_year();
        $campaign = $year !== '' ? $campaigns[$year] : null;
        ?>
        <div class="wrap parcs-ht-advent-admin">
            <h1>Calendrier de l’Avent</h1>
            <p>Chaque installation WordPress possède ses propres campagnes, partenaires, réseaux et contenus. Le shortcode reste identique d’une année à l’autre.</p>
            <div class="parcs-ht-advent-admin-years">
                <?php foreach ($campaigns as $campaign_year => $row) : ?>
                    <a class="button <?php echo $campaign_year === $year ? 'button-primary' : ''; ?>" href="<?php echo esc_url(add_query_arg(array('page'=>self::PAGE,'advent_year'=>$campaign_year), admin_url('admin.php'))); ?>"><?php echo esc_html($campaign_year . ((string)$row['published'] === '1' ? '' : ' · brouillon')); ?></a>
                <?php endforeach; ?>
            </div>
            <form class="parcs-ht-advent-create" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <input type="hidden" name="action" value="parcs_ht_advent_create_campaign">
                <?php wp_nonce_field('parcs_ht_advent_create_campaign'); ?>
                <input type="number" name="year" min="2020" max="2100" placeholder="2026" required>
                <button class="button" type="submit">Créer une campagne</button>
            </form>
            <?php if (!$campaign) : ?><div class="notice notice-info inline"><p>Créez une première campagne pour commencer.</p></div></div><?php return; endif; ?>
            <div class="notice notice-info inline"><p><strong>Shortcode :</strong> <code>[parc_calendrier_avent]</code> — l’onglet Aperçu peut simuler n’importe quelle date et heure sans mettre le shortcode sur le site.</p></div>
            <?php self::campaign_form($campaign); self::partners_form($campaign); self::teasers_form($campaign); self::days_forms($campaign); ?>
        </div>
        <?php
    }

    private static function translation_fields($name, $values, $label, $textarea = false) {
        echo '<fieldset class="parcs-ht-advent-trans"><legend>' . esc_html($label) . '</legend>';
        foreach (array('fr'=>'FR','en'=>'EN','de'=>'DE') as $language => $language_label) {
            $value = is_array($values) && isset($values[$language]) ? (string)$values[$language] : '';
            echo '<label><span>' . esc_html($language_label) . '</span>';
            if ($textarea) echo '<textarea name="' . esc_attr($name) . '[' . esc_attr($language) . ']" rows="4">' . esc_textarea($value) . '</textarea>';
            else echo '<input type="text" name="' . esc_attr($name) . '[' . esc_attr($language) . ']" value="' . esc_attr($value) . '">';
            echo '</label>';
        }
        echo '</fieldset>';
    }

    private static function campaign_form($campaign) { ?>
        <form class="parcs-ht-advent-panel" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <h2>Réglages généraux — <?php echo esc_html($campaign['year']); ?></h2>
            <input type="hidden" name="action" value="parcs_ht_advent_save_campaign">
            <input type="hidden" name="year" value="<?php echo esc_attr($campaign['year']); ?>">
            <?php wp_nonce_field('parcs_ht_advent_save_campaign_' . $campaign['year']); ?>
            <div class="parcs-ht-advent-grid">
                <label><span>Publication</span><span><input type="checkbox" name="published" value="1" <?php checked($campaign['published'],'1'); ?>> Campagne publiée</span></label>
                <label><span>Début du calendrier</span><input type="datetime-local" name="start_at" value="<?php echo esc_attr(str_replace(' ','T',$campaign['start_at'])); ?>"></label>
                <label><span>Fin du calendrier</span><input type="datetime-local" name="end_at" value="<?php echo esc_attr(str_replace(' ','T',$campaign['end_at'])); ?>"></label>
                <label><span>Fuseau horaire</span><input type="text" name="timezone" value="<?php echo esc_attr($campaign['timezone']); ?>"></label>
            </div>
            <?php self::translation_fields('title',$campaign['title'],'Titre public'); self::translation_fields('intro',$campaign['intro'],'Introduction',true); ?>
            <h3>Réseaux du parc</h3>
            <div class="parcs-ht-advent-grid">
                <label><span>@ Instagram</span><input type="text" name="park_instagram" value="<?php echo esc_attr($campaign['park_instagram']); ?>"></label>
                <label><span>@ / identifiant Facebook</span><input type="text" name="park_facebook" value="<?php echo esc_attr($campaign['park_facebook']); ?>"></label>
                <label><span>URL Instagram personnalisée</span><input type="url" name="park_instagram_url" value="<?php echo esc_attr($campaign['park_instagram_url']); ?>"></label>
                <label><span>URL Facebook personnalisée</span><input type="url" name="park_facebook_url" value="<?php echo esc_attr($campaign['park_facebook_url']); ?>"></label>
                <label><span>Hashtags par défaut</span><input type="text" name="hashtags" value="<?php echo esc_attr($campaign['hashtags']); ?>"></label>
                <label><span>Règlement complet</span><input type="url" name="regulation_url" value="<?php echo esc_attr($campaign['regulation_url']); ?>"></label>
            </div>
            <h3>Générateur de publications</h3>
            <p><label><input type="checkbox" name="require_friend_tag" value="1" <?php checked($campaign['require_friend_tag'],'1'); ?>> Ajouter la règle « identifier quelqu’un »</label> &nbsp; <label><input type="checkbox" name="mystery_reminder_every_day" value="1" <?php checked($campaign['mystery_reminder_every_day'],'1'); ?>> Rappeler le grand jeu chaque jour</label></p>
            <?php
            self::translation_fields('post_template_instagram',$campaign['post_template_instagram'],'Modèle du post Instagram',true);
            self::translation_fields('post_template_facebook',$campaign['post_template_facebook'],'Modèle du post Facebook',true);
            self::translation_fields('result_template_instagram',$campaign['result_template_instagram'],'Modèle du résultat Instagram',true);
            self::translation_fields('result_template_facebook',$campaign['result_template_facebook'],'Modèle du résultat Facebook',true);
            ?>
            <p class="description">Variables : {day}, {prize_line}, {partner_description}, {question}, {choices}, {park_handle}, {partner_follow_line}, {friend_tag_line}, {regulation_url}, {mystery_block}, {hashtags}, {correct_answer}, {answer_explanation}, {winner_instagram}, {winner_facebook}, {partner_thanks}, {eligibility_reminder}.</p>
            <h3>Grand jeu du mot mystère</h3>
            <div class="parcs-ht-advent-grid">
                <label><span>Mot mystère</span><input type="text" name="mystery_word" value="<?php echo esc_attr($campaign['mystery_word']); ?>" autocomplete="off"></label>
                <label><span>Pictogramme / loupe</span><span><input type="url" name="mystery_icon_url" value="<?php echo esc_attr($campaign['mystery_icon_url']); ?>" data-advent-media-url><button type="button" class="button" data-advent-media>Choisir</button></span></label>
                <label><span>Ouverture grand jeu</span><input type="datetime-local" name="final_open_at" value="<?php echo esc_attr(str_replace(' ','T',$campaign['final_open_at'])); ?>"></label>
                <label><span>Fermeture grand jeu</span><input type="datetime-local" name="final_close_at" value="<?php echo esc_attr(str_replace(' ','T',$campaign['final_close_at'])); ?>"></label>
                <label><span>Révélation des indices</span><input type="datetime-local" name="reveal_at" value="<?php echo esc_attr(str_replace(' ','T',$campaign['reveal_at'])); ?>"></label>
            </div>
            <?php self::translation_fields('mystery_text',$campaign['mystery_text'],'Explication du grand jeu',true); self::translation_fields('final_intro',$campaign['final_intro'],'Introduction du formulaire final',true); self::translation_fields('final_closed',$campaign['final_closed'],'Message après clôture',true); self::translation_fields('final_winner',$campaign['final_winner'],'Annonce du grand gagnant',true); self::translation_fields('gdpr_text',$campaign['gdpr_text'],'Consentement RGPD',true); ?>
            <div class="parcs-ht-advent-grid">
                <label><span><input type="checkbox" name="allow_multiple" value="1" <?php checked($campaign['allow_multiple'],'1'); ?>> Plusieurs participations par e-mail</span></label>
                <label><span><input type="checkbox" name="field_phone" value="1" <?php checked($campaign['field_phone'],'1'); ?>> Demander le téléphone</span></label>
                <label><span><input type="checkbox" name="field_address" value="1" <?php checked($campaign['field_address'],'1'); ?>> Demander l’adresse</span></label>
                <label><span><input type="checkbox" name="field_newsletter" value="1" <?php checked($campaign['field_newsletter'],'1'); ?>> Proposer la newsletter</span></label>
            </div>
            <p><button class="button button-primary" type="submit">Enregistrer la campagne</button> <a class="button" href="<?php echo esc_url(wp_nonce_url(add_query_arg(array('action'=>'parcs_ht_advent_export','year'=>$campaign['year']), admin_url('admin-post.php')), 'parcs_ht_advent_export_' . $campaign['year'])); ?>">Exporter les participations CSV</a></p>
        </form>
    <?php }

    private static function partners_form($campaign) { ?>
        <form class="parcs-ht-advent-panel" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" data-advent-partners-form>
            <h2>Partenaires</h2>
            <p>Un jour peut rester sans partenaire externe. Les liens de profil sont générés depuis les identifiants lorsque les URL personnalisées sont vides.</p>
            <input type="hidden" name="action" value="parcs_ht_advent_save_partners"><input type="hidden" name="year" value="<?php echo esc_attr($campaign['year']); ?>"><input type="hidden" name="partners_json" data-advent-json>
            <?php wp_nonce_field('parcs_ht_advent_save_partners_' . $campaign['year']); ?>
            <div data-advent-partner-list><?php foreach ((array)$campaign['partners'] as $partner) self::partner_admin_row($partner); ?></div>
            <p><button type="button" class="button" data-advent-add-partner>Ajouter un partenaire</button> <button type="submit" class="button button-primary">Enregistrer les partenaires</button></p>
        </form>
    <?php }

    private static function partner_admin_row($partner) { ?>
        <div class="parcs-ht-advent-repeat" data-advent-partner>
            <button type="button" class="button-link-delete" data-advent-remove>Supprimer</button>
            <div class="parcs-ht-advent-grid">
                <label>ID interne<input data-key="id" value="<?php echo esc_attr($partner['id'] ?? ''); ?>"></label>
                <label>Nom<input data-key="name" value="<?php echo esc_attr($partner['name'] ?? ''); ?>"></label>
                <label>@ Instagram<input data-key="instagram" value="<?php echo esc_attr($partner['instagram'] ?? ''); ?>"></label>
                <label>@ / identifiant Facebook<input data-key="facebook" value="<?php echo esc_attr($partner['facebook'] ?? ''); ?>"></label>
                <label>URL Instagram<input data-key="instagram_url" value="<?php echo esc_attr($partner['instagram_url'] ?? ''); ?>"></label>
                <label>URL Facebook<input data-key="facebook_url" value="<?php echo esc_attr($partner['facebook_url'] ?? ''); ?>"></label>
                <label>Site<input data-key="website" value="<?php echo esc_attr($partner['website'] ?? ''); ?>"></label>
                <label>Logo URL<input data-key="logo_url" value="<?php echo esc_attr($partner['logo_url'] ?? ''); ?>"></label>
                <label>Hashtags<input data-key="hashtags" value="<?php echo esc_attr($partner['hashtags'] ?? ''); ?>"></label>
            </div>
            <div class="parcs-ht-advent-grid">
                <label>Description FR<textarea data-key="description_fr"><?php echo esc_textarea($partner['description']['fr'] ?? ''); ?></textarea></label>
                <label>Description EN<textarea data-key="description_en"><?php echo esc_textarea($partner['description']['en'] ?? ''); ?></textarea></label>
                <label>Description DE<textarea data-key="description_de"><?php echo esc_textarea($partner['description']['de'] ?? ''); ?></textarea></label>
            </div>
        </div>
    <?php }

    private static function teasers_form($campaign) { ?>
        <form class="parcs-ht-advent-panel" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" data-advent-teasers-form>
            <h2>Teasing avant lancement</h2>
            <input type="hidden" name="action" value="parcs_ht_advent_save_teasers"><input type="hidden" name="year" value="<?php echo esc_attr($campaign['year']); ?>"><input type="hidden" name="teasers_json" data-advent-json>
            <?php wp_nonce_field('parcs_ht_advent_save_teasers_' . $campaign['year']); ?>
            <div data-advent-teaser-list><?php foreach ((array)$campaign['teasers'] as $teaser) self::teaser_admin_row($teaser); ?></div>
            <p><button type="button" class="button" data-advent-add-teaser>Ajouter un teaser</button> <button type="submit" class="button button-primary">Enregistrer les teasers</button></p>
        </form>
    <?php }

    private static function teaser_admin_row($teaser) { ?>
        <div class="parcs-ht-advent-repeat" data-advent-teaser>
            <button type="button" class="button-link-delete" data-advent-remove>Supprimer</button>
            <div class="parcs-ht-advent-grid">
                <label><span><input type="checkbox" data-key="enabled" <?php checked((string)($teaser['enabled'] ?? '1'),'1'); ?>> Actif</span></label>
                <label>Date / heure<input type="datetime-local" data-key="at" value="<?php echo esc_attr(str_replace(' ','T',$teaser['at'] ?? '')); ?>"></label>
                <label>Image URL<input data-key="image_url" value="<?php echo esc_attr($teaser['image_url'] ?? ''); ?>"></label>
                <label>Lien<input data-key="url" value="<?php echo esc_attr($teaser['url'] ?? ''); ?>"></label>
            </div>
            <?php foreach (array('fr'=>'FR','en'=>'EN','de'=>'DE') as $language=>$label) : ?>
                <div class="parcs-ht-advent-grid"><label>Titre <?php echo esc_html($label); ?><input data-key="title_<?php echo esc_attr($language); ?>" value="<?php echo esc_attr($teaser['title'][$language] ?? ''); ?>"></label><label>Texte <?php echo esc_html($label); ?><textarea data-key="text_<?php echo esc_attr($language); ?>"><?php echo esc_textarea($teaser['text'][$language] ?? ''); ?></textarea></label><label>Bouton <?php echo esc_html($label); ?><input data-key="button_<?php echo esc_attr($language); ?>" value="<?php echo esc_attr($teaser['button'][$language] ?? ''); ?>"></label></div>
            <?php endforeach; ?>
        </div>
    <?php }

    private static function days_forms($campaign) {
        echo '<section class="parcs-ht-advent-panel"><h2>Les 24 journées</h2><p>Chaque journée s’enregistre séparément. Les quatre textes réseaux sont recalculés après l’enregistrement.</p>';
        foreach (range(1,24) as $day) {
            $row = $campaign['days'][(string)$day] ?? self::day_defaults($campaign['year'], $day);
            self::day_form($campaign, $row);
        }
        echo '</section>';
    }

    private static function choice_fields($day) {
        foreach (array('A','B','C','D') as $letter) {
            foreach (array('fr'=>'FR','en'=>'EN','de'=>'DE') as $language=>$label) {
                echo '<label>Réponse ' . esc_html($letter . ' ' . $label) . '<input name="choice_' . esc_attr($letter . '_' . $language) . '" value="' . esc_attr($day['choices'][$letter][$language] ?? '') . '"></label>';
            }
        }
    }

    private static function day_form($campaign, $day) {
        $number = (int)$day['day'];
        $post_instagram = self::generate_social_text($campaign, $day, 'instagram', false, 'fr');
        $post_facebook = self::generate_social_text($campaign, $day, 'facebook', false, 'fr');
        $result_instagram = self::generate_social_text($campaign, $day, 'instagram', true, 'fr');
        $result_facebook = self::generate_social_text($campaign, $day, 'facebook', true, 'fr');
        ?>
        <details class="parcs-ht-advent-day-admin" <?php echo $number === 1 ? 'open' : ''; ?>>
            <summary>Jour <?php echo esc_html($number); ?><?php echo (string)$day['has_clue'] === '1' ? ' · 🔎 indice' : ''; ?></summary>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <input type="hidden" name="action" value="parcs_ht_advent_save_day"><input type="hidden" name="year" value="<?php echo esc_attr($campaign['year']); ?>"><input type="hidden" name="day" value="<?php echo esc_attr($number); ?>">
                <?php wp_nonce_field('parcs_ht_advent_save_day_' . $campaign['year'] . '_' . $number); ?>
                <div class="parcs-ht-advent-grid">
                    <label><span><input type="checkbox" name="enabled" value="1" <?php checked($day['enabled'],'1'); ?>> Journée active</span></label>
                    <label>Ouverture<input type="datetime-local" name="open_at" value="<?php echo esc_attr(str_replace(' ','T',$day['open_at'])); ?>"></label>
                    <label>Type<select name="game_type"><option value="qcm" <?php selected($day['game_type'],'qcm'); ?>>QCM</option><option value="true_false" <?php selected($day['game_type'],'true_false'); ?>>Vrai / Faux</option><option value="observation" <?php selected($day['game_type'],'observation'); ?>>Observation / à deviner</option><option value="other" <?php selected($day['game_type'],'other'); ?>>Autre</option></select></label>
                    <label>Visuel 4:5 (URL)<span><input type="url" name="image_url" value="<?php echo esc_attr($day['image_url']); ?>" data-advent-media-url><button type="button" class="button" data-advent-media>Choisir</button></span></label>
                </div>
                <?php self::translation_fields('title',$day['title'],'Titre court'); self::translation_fields('image_alt',$day['image_alt'],'Texte alternatif'); self::translation_fields('question',$day['question'],'Question / consigne',true); ?>
                <div class="parcs-ht-advent-grid"><?php self::choice_fields($day); ?><label>Bonne réponse<select name="correct_choice"><option value="">—</option><?php foreach (array('A','B','C','D') as $letter) : ?><option value="<?php echo esc_attr($letter); ?>" <?php selected($day['correct_choice'],$letter); ?>><?php echo esc_html($letter); ?></option><?php endforeach; ?></select></label></div>
                <?php self::translation_fields('explanation',$day['explanation'],'Explication de la réponse',true); self::translation_fields('prize',$day['prize'],'Lot du jour'); ?>
                <div class="parcs-ht-advent-grid">
                    <label>Partenaire<select name="partner_id"><option value="">Aucun / parc</option><?php foreach ((array)$campaign['partners'] as $partner) : ?><option value="<?php echo esc_attr($partner['id']); ?>" <?php selected($day['partner_id'],$partner['id']); ?>><?php echo esc_html($partner['name']); ?></option><?php endforeach; ?></select></label>
                    <label>Hashtags supplémentaires<input name="hashtags" value="<?php echo esc_attr($day['hashtags']); ?>"></label>
                    <label>URL du post Instagram<input type="url" name="instagram_post_url" value="<?php echo esc_attr($day['instagram_post_url']); ?>"></label>
                    <label>URL du post Facebook<input type="url" name="facebook_post_url" value="<?php echo esc_attr($day['facebook_post_url']); ?>"></label>
                    <label>Résultats visibles à partir de<input type="datetime-local" name="result_at" value="<?php echo esc_attr(str_replace(' ','T',$day['result_at'])); ?>"></label>
                    <label>Gagnant Instagram<input name="winner_instagram" value="<?php echo esc_attr($day['winner_instagram']); ?>"></label>
                    <label>Gagnant Facebook<input name="winner_facebook" value="<?php echo esc_attr($day['winner_facebook']); ?>"></label>
                </div>
                <fieldset class="parcs-ht-advent-clue-admin"><legend>Indice du mot mystère</legend>
                    <p><label><input type="checkbox" name="has_clue" value="1" <?php checked($day['has_clue'],'1'); ?>> Cette journée contient un indice</label> &nbsp; <label><input type="checkbox" name="show_mystery_reminder" value="1" <?php checked($day['show_mystery_reminder'],'1'); ?>> Afficher le rappel du grand jeu</label></p>
                    <div class="parcs-ht-advent-grid"><label>Lettre<input name="clue_letter" maxlength="3" value="<?php echo esc_attr($day['clue_letter']); ?>"></label><label>Position<input type="number" min="1" max="99" name="clue_position" value="<?php echo esc_attr($day['clue_position']); ?>"></label></div>
                    <?php self::translation_fields('clue_note',$day['clue_note'],'Explication révélée après clôture',true); ?>
                </fieldset>
                <p><button class="button button-primary" type="submit">Enregistrer le jour <?php echo esc_html($number); ?></button></p>
                <div class="parcs-ht-advent-copy-grid">
                    <?php self::copy_box('Post Instagram',$post_instagram,'Copier le post Instagram'); self::copy_box('Post Facebook',$post_facebook,'Copier le post Facebook'); self::copy_box('Résultat Instagram',$result_instagram,'Copier le résultat Instagram'); self::copy_box('Résultat Facebook',$result_facebook,'Copier le résultat Facebook'); ?>
                </div>
            </form>
        </details>
        <?php
    }

    private static function copy_box($label, $text, $button) {
        echo '<label>' . esc_html($label) . '<textarea readonly rows="14" data-advent-copy-source>' . esc_textarea($text) . '</textarea><button type="button" class="button" data-advent-copy>' . esc_html($button) . '</button></label>';
    }

    private static function save_redirect($year) {
        wp_safe_redirect(add_query_arg(array('page'=>self::PAGE,'advent_year'=>$year,'updated'=>'1'), admin_url('admin.php')));
        exit;
    }

    public static function create_campaign() {
        if (!current_user_can('manage_options')) wp_die('Accès refusé.');
        check_admin_referer('parcs_ht_advent_create_campaign');
        $year = isset($_POST['year']) ? self::clean_year(wp_unslash($_POST['year'])) : '';
        if ($year === '') wp_die('Année invalide.');
        $store = self::store();
        if (!isset($store['campaigns'][$year])) {
            $store['campaigns'][$year] = self::campaign_defaults($year);
            update_option(self::OPTION, $store, false);
        }
        self::save_redirect($year);
    }

    public static function save_campaign() {
        if (!current_user_can('manage_options')) wp_die('Accès refusé.');
        $year = isset($_POST['year']) ? self::clean_year(wp_unslash($_POST['year'])) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- année sanitizée nécessaire à la clé de nonce dynamique vérifiée juste après.
        check_admin_referer('parcs_ht_advent_save_campaign_' . $year);
        $campaign = self::campaign($year);
        if (!$campaign) wp_die('Campagne absente.');
        $campaign['published'] = isset($_POST['published']) ? '1' : '0';
        $campaign['start_at'] = isset($_POST['start_at']) ? self::clean_datetime(sanitize_text_field(wp_unslash($_POST['start_at']))) : '';
        $campaign['end_at'] = isset($_POST['end_at']) ? self::clean_datetime(sanitize_text_field(wp_unslash($_POST['end_at']))) : '';
        $campaign['timezone'] = isset($_POST['timezone']) ? sanitize_text_field(wp_unslash($_POST['timezone'])) : 'Europe/Paris';
        foreach (array('title'=>false,'intro'=>true,'mystery_text'=>true,'final_intro'=>true,'final_closed'=>true,'final_winner'=>true,'gdpr_text'=>true,'post_template_instagram'=>true,'post_template_facebook'=>true,'result_template_instagram'=>true,'result_template_facebook'=>true) as $key=>$textarea) {
            $raw = isset($_POST[$key]) && is_array($_POST[$key]) ? map_deep(wp_unslash($_POST[$key]), 'sanitize_textarea_field') : array();
            $campaign[$key] = self::translations($raw, $textarea);
        }
        $campaign['park_instagram'] = isset($_POST['park_instagram']) ? self::clean_handle(sanitize_text_field(wp_unslash($_POST['park_instagram']))) : '';
        $campaign['park_facebook'] = isset($_POST['park_facebook']) ? self::clean_handle(sanitize_text_field(wp_unslash($_POST['park_facebook']))) : '';
        $campaign['park_instagram_url'] = isset($_POST['park_instagram_url']) ? esc_url_raw(wp_unslash($_POST['park_instagram_url'])) : '';
        $campaign['park_facebook_url'] = isset($_POST['park_facebook_url']) ? esc_url_raw(wp_unslash($_POST['park_facebook_url'])) : '';
        $campaign['hashtags'] = isset($_POST['hashtags']) ? sanitize_text_field(wp_unslash($_POST['hashtags'])) : '';
        $campaign['regulation_url'] = isset($_POST['regulation_url']) ? esc_url_raw(wp_unslash($_POST['regulation_url'])) : '';
        foreach (array('require_friend_tag','mystery_reminder_every_day','allow_multiple','field_phone','field_address','field_newsletter') as $key) $campaign[$key] = isset($_POST[$key]) ? '1' : '0';
        $campaign['mystery_icon_url'] = isset($_POST['mystery_icon_url']) ? esc_url_raw(wp_unslash($_POST['mystery_icon_url'])) : '';
        $campaign['mystery_word'] = isset($_POST['mystery_word']) ? sanitize_text_field(wp_unslash($_POST['mystery_word'])) : '';
        foreach (array('final_open_at','final_close_at','reveal_at') as $key) $campaign[$key] = isset($_POST[$key]) ? self::clean_datetime(sanitize_text_field(wp_unslash($_POST[$key]))) : '';
        self::save_campaign_data($year, $campaign);
        self::save_redirect($year);
    }

    public static function save_day() {
        if (!current_user_can('manage_options')) wp_die('Accès refusé.');
        $year = isset($_POST['year']) ? self::clean_year(wp_unslash($_POST['year'])) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- année sanitizée nécessaire à la clé de nonce dynamique vérifiée juste après.
        $number = isset($_POST['day']) ? absint($_POST['day']) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- numéro nécessaire à la clé de nonce dynamique vérifiée juste après.
        check_admin_referer('parcs_ht_advent_save_day_' . $year . '_' . $number);
        $campaign = self::campaign($year);
        if (!$campaign || $number < 1 || $number > 24) wp_die('Journée invalide.');
        $day = $campaign['days'][(string)$number] ?? self::day_defaults($year, $number);
        $day['day'] = (string)$number;
        $day['enabled'] = isset($_POST['enabled']) ? '1' : '0';
        $day['open_at'] = isset($_POST['open_at']) ? self::clean_datetime(sanitize_text_field(wp_unslash($_POST['open_at']))) : '';
        $game_type = isset($_POST['game_type']) ? sanitize_key(wp_unslash($_POST['game_type'])) : 'other';
        $day['game_type'] = in_array($game_type, array('qcm','true_false','observation','other'), true) ? $game_type : 'other';
        $day['image_url'] = isset($_POST['image_url']) ? esc_url_raw(wp_unslash($_POST['image_url'])) : '';
        foreach (array('title'=>false,'image_alt'=>false,'question'=>true,'explanation'=>true,'prize'=>false,'clue_note'=>true) as $key=>$textarea) {
            $raw = isset($_POST[$key]) && is_array($_POST[$key]) ? map_deep(wp_unslash($_POST[$key]), 'sanitize_textarea_field') : array();
            $day[$key] = self::translations($raw, $textarea);
        }
        foreach (array('A','B','C','D') as $letter) {
            foreach (array('fr','en','de') as $language) {
                $field = 'choice_' . $letter . '_' . $language;
                $day['choices'][$letter][$language] = isset($_POST[$field]) ? sanitize_text_field(wp_unslash($_POST[$field])) : '';
            }
        }
        $choice = isset($_POST['correct_choice']) ? strtoupper(sanitize_text_field(wp_unslash($_POST['correct_choice']))) : '';
        $day['correct_choice'] = in_array($choice, array('A','B','C','D'), true) ? $choice : '';
        $day['partner_id'] = isset($_POST['partner_id']) ? sanitize_key(wp_unslash($_POST['partner_id'])) : '';
        $day['hashtags'] = isset($_POST['hashtags']) ? sanitize_text_field(wp_unslash($_POST['hashtags'])) : '';
        $day['instagram_post_url'] = isset($_POST['instagram_post_url']) ? esc_url_raw(wp_unslash($_POST['instagram_post_url'])) : '';
        $day['facebook_post_url'] = isset($_POST['facebook_post_url']) ? esc_url_raw(wp_unslash($_POST['facebook_post_url'])) : '';
        $day['result_at'] = isset($_POST['result_at']) ? self::clean_datetime(sanitize_text_field(wp_unslash($_POST['result_at']))) : '';
        $day['winner_instagram'] = isset($_POST['winner_instagram']) ? sanitize_text_field(wp_unslash($_POST['winner_instagram'])) : '';
        $day['winner_facebook'] = isset($_POST['winner_facebook']) ? sanitize_text_field(wp_unslash($_POST['winner_facebook'])) : '';
        $day['has_clue'] = isset($_POST['has_clue']) ? '1' : '0';
        $day['show_mystery_reminder'] = isset($_POST['show_mystery_reminder']) ? '1' : '0';
        $day['clue_letter'] = isset($_POST['clue_letter']) ? strtoupper(sanitize_text_field(wp_unslash($_POST['clue_letter']))) : '';
        $day['clue_position'] = isset($_POST['clue_position']) ? (string)absint($_POST['clue_position']) : '';
        $campaign['days'][(string)$number] = $day;
        self::save_campaign_data($year, $campaign);
        self::save_redirect($year);
    }

    public static function save_partners() {
        if (!current_user_can('manage_options')) wp_die('Accès refusé.');
        $year = isset($_POST['year']) ? self::clean_year(wp_unslash($_POST['year'])) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- année sanitizée nécessaire à la clé de nonce dynamique vérifiée juste après.
        check_admin_referer('parcs_ht_advent_save_partners_' . $year);
        $campaign = self::campaign($year);
        if (!$campaign) wp_die('Campagne absente.');
        $json = isset($_POST['partners_json']) ? sanitize_textarea_field(wp_unslash($_POST['partners_json'])) : '[]';
        $rows = json_decode($json, true);
        $partners = array();
        foreach (is_array($rows) ? array_slice($rows,0,100) : array() as $row) {
            if (!is_array($row)) continue;
            $name = sanitize_text_field((string)($row['name'] ?? ''));
            $id = sanitize_key((string)($row['id'] ?? ''));
            if ($id === '' && $name !== '') $id = sanitize_title($name);
            if ($id === '' || $name === '') continue;
            $partners[] = array(
                'id'=>$id,
                'name'=>$name,
                'description'=>array(
                    'fr'=>sanitize_textarea_field((string)($row['description_fr'] ?? '')),
                    'en'=>sanitize_textarea_field((string)($row['description_en'] ?? '')),
                    'de'=>sanitize_textarea_field((string)($row['description_de'] ?? '')),
                ),
                'instagram'=>self::clean_handle($row['instagram'] ?? ''),
                'facebook'=>self::clean_handle($row['facebook'] ?? ''),
                'instagram_url'=>esc_url_raw((string)($row['instagram_url'] ?? '')),
                'facebook_url'=>esc_url_raw((string)($row['facebook_url'] ?? '')),
                'website'=>esc_url_raw((string)($row['website'] ?? '')),
                'logo_url'=>esc_url_raw((string)($row['logo_url'] ?? '')),
                'hashtags'=>sanitize_text_field((string)($row['hashtags'] ?? '')),
            );
        }
        $campaign['partners'] = $partners;
        self::save_campaign_data($year, $campaign);
        self::save_redirect($year);
    }

    public static function save_teasers() {
        if (!current_user_can('manage_options')) wp_die('Accès refusé.');
        $year = isset($_POST['year']) ? self::clean_year(wp_unslash($_POST['year'])) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- année sanitizée nécessaire à la clé de nonce dynamique vérifiée juste après.
        check_admin_referer('parcs_ht_advent_save_teasers_' . $year);
        $campaign = self::campaign($year);
        if (!$campaign) wp_die('Campagne absente.');
        $json = isset($_POST['teasers_json']) ? sanitize_textarea_field(wp_unslash($_POST['teasers_json'])) : '[]';
        $rows = json_decode($json, true);
        $teasers = array();
        foreach (is_array($rows) ? array_slice($rows,0,50) : array() as $row) {
            if (!is_array($row)) continue;
            $at = self::clean_datetime(sanitize_text_field((string)($row['at'] ?? '')));
            if ($at === '') continue;
            $title = array(); $text = array(); $button = array();
            foreach (array('fr','en','de') as $language) {
                $title[$language] = sanitize_text_field((string)($row['title_' . $language] ?? ''));
                $text[$language] = sanitize_textarea_field((string)($row['text_' . $language] ?? ''));
                $button[$language] = sanitize_text_field((string)($row['button_' . $language] ?? ''));
            }
            $teasers[] = array(
                'enabled'=>!empty($row['enabled']) ? '1' : '0',
                'at'=>$at,
                'image_url'=>esc_url_raw((string)($row['image_url'] ?? '')),
                'url'=>esc_url_raw((string)($row['url'] ?? '')),
                'title'=>$title,
                'text'=>$text,
                'button'=>$button,
            );
        }
        $campaign['teasers'] = $teasers;
        self::save_campaign_data($year, $campaign);
        self::save_redirect($year);
    }

    private static function network_reference($campaign, $partner, $network, $park = false) {
        if ($park) {
            $handle = self::clean_handle($network === 'facebook' ? $campaign['park_facebook'] : $campaign['park_instagram']);
            return $handle !== '' ? '@' . $handle : self::translation($campaign['title'], 'fr', 'le parc');
        }
        if (!$partner) return '';
        $handle = self::clean_handle($network === 'facebook' ? ($partner['facebook'] ?? '') : ($partner['instagram'] ?? ''));
        return $handle !== '' ? '@' . $handle : (string)($partner['name'] ?? '');
    }

    private static function token_values($campaign, $day, $language, $network) {
        $partner = self::partner($campaign, $day['partner_id'] ?? '');
        $park_reference = self::network_reference($campaign, null, $network, true);
        $partner_reference = self::network_reference($campaign, $partner, $network, false);
        $prize = self::translation($day['prize'], $language, '');
        $prize_line = $prize;
        if ($partner && $partner_reference !== '') $prize_line .= ' · ' . $partner_reference;

        $choices = '';
        foreach (array('A','B','C','D') as $letter) {
            $text = self::translation($day['choices'][$letter] ?? array(), $language, '');
            if ($text !== '') $choices .= $letter . '. ' . $text . "\n";
        }
        $choices = rtrim($choices);
        $partner_description = $partner ? self::translation($partner['description'] ?? array(), $language, '') : '';
        $partner_follow = $partner_reference !== '' ? '– Être abonné(e) à ' . $partner_reference . " ⭐\n" : '';
        $friend = (string)$campaign['require_friend_tag'] === '1' ? "– 👥 Identifier quelqu’un avec qui tu aimerais venir !\n" : '';
        $mystery = '';
        if ((string)$campaign['mystery_reminder_every_day'] === '1' || (string)($day['show_mystery_reminder'] ?? '0') === '1' || (string)($day['has_clue'] ?? '0') === '1') {
            $mystery = "\n🔎 GRAND JEU DU MOT MYSTÈRE\n" . self::translation($campaign['mystery_text'], $language, '');
            if ((string)($day['has_clue'] ?? '0') === '1') $mystery .= "\n🔍 Un indice est caché dans le visuel du jour !";
            $mystery .= "\n";
        }
        $hashtags = trim(trim((string)$campaign['hashtags']) . ' ' . ($partner ? trim((string)($partner['hashtags'] ?? '')) : '') . ' ' . trim((string)($day['hashtags'] ?? '')));
        $partner_thanks = $partner ? '🙏 Merci à notre partenaire du jour : ' . $partner_reference : '';
        $eligibility = $partner_reference !== '' ? '(Rappel : pour recevoir votre lot, vous devez être abonné(e) à ' . $park_reference . ' et ' . $partner_reference . '.)' : '(Rappel : pour recevoir votre lot, vous devez être abonné(e) à ' . $park_reference . '.)';
        return array(
            '{day}'=>(string)$day['day'],
            '{prize_line}'=>$prize_line,
            '{partner_description}'=>$partner_description,
            '{question}'=>self::translation($day['question'], $language, ''),
            '{choices}'=>$choices,
            '{park_handle}'=>$park_reference,
            '{partner_follow_line}'=>$partner_follow,
            '{friend_tag_line}'=>$friend,
            '{regulation_url}'=>(string)$campaign['regulation_url'],
            '{mystery_block}'=>$mystery,
            '{hashtags}'=>$hashtags,
            '{correct_answer}'=>self::correct_answer_text($day, $language),
            '{answer_explanation}'=>self::translation($day['explanation'], $language, ''),
            '{winner_instagram}'=>(string)$day['winner_instagram'],
            '{winner_facebook}'=>(string)$day['winner_facebook'],
            '{partner_thanks}'=>$partner_thanks,
            '{eligibility_reminder}'=>$eligibility,
        );
    }

    public static function generate_social_text($campaign, $day, $network = 'instagram', $result = false, $language = 'fr') {
        $network = $network === 'facebook' ? 'facebook' : 'instagram';
        $key = ($result ? 'result_template_' : 'post_template_') . $network;
        $fallback = $result ? self::default_result_template() : self::default_post_template();
        $template = self::translation($campaign[$key] ?? array(), $language, $fallback);
        $text = strtr($template, self::token_values($campaign, $day, $language, $network));
        $lines = preg_split('/\R/', $text);
        if (!is_array($lines)) return trim($text);
        $clean = array();
        foreach ($lines as $line) {
            $line = rtrim($line);
            if ($line === '' && $clean && end($clean) === '') continue;
            $clean[] = $line;
        }
        return trim(implode("\n", $clean));
    }

    public static function export_entries() {
        if (!current_user_can('manage_options')) wp_die('Accès refusé.');
        $year = isset($_GET['year']) ? self::clean_year(wp_unslash($_GET['year'])) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- année sanitizée nécessaire à la clé de nonce dynamique vérifiée juste après.
        check_admin_referer('parcs_ht_advent_export_' . $year);
        if ($year === '') wp_die('Année invalide.');
        global $wpdb;
        $table = self::table_name();
        $rows = $wpdb->get_results($wpdb->prepare("SELECT created_at,first_name,last_name,email,phone,address,postal_code,city,country,newsletter,consent FROM {$table} WHERE campaign_year=%s ORDER BY id ASC", $year), ARRAY_A); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- nom de table interne, valeur préparée.
        nocache_headers();
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="calendrier-avent-' . $year . '-participations.csv"');
        $output = fopen('php://output', 'w');
        if ($output === false) wp_die('Export indisponible.');
        fputcsv($output, array('Date','Prénom','Nom','E-mail','Téléphone','Adresse','Code postal','Ville','Pays','Newsletter','Consentement'));
        foreach ((array)$rows as $row) fputcsv($output, $row);
        fclose($output);
        exit;
    }
}
