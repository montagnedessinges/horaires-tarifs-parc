<?php

if (!defined('ABSPATH')) { exit; }

/**
 * Socle IA / Google 1.18.0.
 *
 * Cette classe ne remplace ni SEOPress ni les moteurs métier existants. Elle expose
 * uniquement les données propres au parc que l'extension connaît déjà mieux que les
 * autres composants : horaires saisonniers, exceptions et réponses officielles.
 */
final class Parcs_HT_AI_Google {
    const OPTION = 'parcs_ht_ai_google';
    const PAGE = 'parcs-ht-ai-google';
    const STORE_VERSION = 1;

    public static function init() {
        add_action('admin_menu', array(__CLASS__, 'menu'), 45);
        add_action('admin_post_parcs_ht_save_ai_google', array(__CLASS__, 'save'));
        add_action('wp_head', array(__CLASS__, 'render_structured_data'), 80);
        add_filter('do_shortcode_tag', array(__CLASS__, 'append_visit_rules'), 90, 4);
    }

    private static function triple($fr, $en, $de) {
        return array('fr'=>(string)$fr, 'en'=>(string)$en, 'de'=>(string)$de);
    }

    private static function defaults() {
        return array(
            'store_version'=>self::STORE_VERSION,
            'schema_enabled'=>'1',
            'rules_visible'=>'1',
            'identity'=>array(
                'street'=>'',
                'postal_code'=>'',
                'city'=>'',
                'country'=>'FR',
                'phone'=>'',
                'email'=>'',
                'latitude'=>'',
                'longitude'=>'',
                'logo_url'=>'',
                'image_url'=>'',
                'same_as'=>array(),
            ),
            'knowledge'=>array(
                array(
                    'id'=>'visitor-feeding',
                    'enabled'=>'1',
                    'category'=>'visit_rules',
                    'category_label'=>self::triple('Règles de visite','Visiting rules','Besuchsregeln'),
                    'question'=>self::triple('Peut-on nourrir les singes ?','Can visitors feed the monkeys?','Dürfen Besucher die Affen füttern?'),
                    'answer'=>self::triple(
                        'Les visiteurs ne nourrissent pas les singes. Les nourrissages sont assurés par les animateurs nature lors des nourrissages commentés.',
                        'Visitors do not feed the monkeys. Feedings are carried out by the park’s nature guides during scheduled feeding talks.',
                        'Besucher füttern die Affen nicht. Die Fütterungen werden von den Naturführern des Parks während der kommentierten Fütterungen durchgeführt.'
                    ),
                    'keywords'=>array('popcorn','pop-corn','nourrir','donner à manger','feed monkeys','feeding monkeys','Affen füttern'),
                    'last_verified'=>'2026-09-29',
                ),
            ),
        );
    }

    public static function settings() {
        $defaults = self::defaults();
        $saved = get_option(self::OPTION, array());
        if (!is_array($saved)) return $defaults;

        $out = $defaults;
        foreach (array('store_version','schema_enabled','rules_visible') as $key) {
            if (array_key_exists($key, $saved)) $out[$key] = $saved[$key];
        }
        if (isset($saved['identity']) && is_array($saved['identity'])) {
            $out['identity'] = array_replace($defaults['identity'], $saved['identity']);
        }
        if (isset($saved['knowledge']) && is_array($saved['knowledge'])) {
            $out['knowledge'] = $saved['knowledge'];
        }
        return $out;
    }

    public static function menu() {
        if (!class_exists('Parcs_HT_Admin')) return;
        add_submenu_page(
            Parcs_HT_Admin::PAGE,
            'IA & Google',
            'IA & Google',
            'manage_options',
            self::PAGE,
            array(__CLASS__, 'page')
        );
    }

    private static function clean_url_list($value) {
        $rows = is_array($value) ? $value : preg_split('/\r\n|\r|\n/', (string)$value);
        $out = array();
        foreach ((array)$rows as $row) {
            $url = esc_url_raw(trim((string)$row));
            if ($url !== '') $out[] = $url;
        }
        return array_values(array_unique($out));
    }

    private static function clean_translation($value) {
        $value = is_array($value) ? $value : array();
        $out = array();
        foreach (array('fr','en','de') as $lang) {
            $out[$lang] = sanitize_textarea_field((string)($value[$lang] ?? ''));
        }
        return $out;
    }

    public static function save() {
        if (!current_user_can('manage_options')) wp_die('Accès refusé.');
        check_admin_referer('parcs_ht_save_ai_google');

        $current = self::settings();
        $raw = array();
        if (isset($_POST['ai_google']) && is_array($_POST['ai_google'])) {
            // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Conteneur déslashé une seule fois ; chaque valeur est ensuite assainie avec son sanitizer adapté avant écriture.
            $raw = wp_unslash($_POST['ai_google']);
        }
        $identity_raw = isset($raw['identity']) && is_array($raw['identity']) ? $raw['identity'] : array();

        $identity = array(
            'street'=>sanitize_text_field((string)($identity_raw['street'] ?? '')),
            'postal_code'=>sanitize_text_field((string)($identity_raw['postal_code'] ?? '')),
            'city'=>sanitize_text_field((string)($identity_raw['city'] ?? '')),
            'country'=>strtoupper(substr(sanitize_text_field((string)($identity_raw['country'] ?? 'FR')), 0, 2)),
            'phone'=>sanitize_text_field((string)($identity_raw['phone'] ?? '')),
            'email'=>sanitize_email((string)($identity_raw['email'] ?? '')),
            'latitude'=>sanitize_text_field((string)($identity_raw['latitude'] ?? '')),
            'longitude'=>sanitize_text_field((string)($identity_raw['longitude'] ?? '')),
            'logo_url'=>esc_url_raw((string)($identity_raw['logo_url'] ?? '')),
            'image_url'=>esc_url_raw((string)($identity_raw['image_url'] ?? '')),
            'same_as'=>self::clean_url_list($identity_raw['same_as'] ?? array()),
        );

        $knowledge = array();
        $rows = isset($raw['knowledge']) && is_array($raw['knowledge']) ? $raw['knowledge'] : array();
        foreach ($rows as $index => $row) {
            if (!is_array($row)) continue;
            $id = sanitize_key((string)($row['id'] ?? ''));
            if ($id === '') $id = 'official-answer-' . ((int)$index + 1);
            $question = self::clean_translation($row['question'] ?? array());
            $answer = self::clean_translation($row['answer'] ?? array());
            if (trim($question['fr']) === '' && trim($answer['fr']) === '') continue;
            $knowledge[] = array(
                'id'=>$id,
                'enabled'=>isset($row['enabled']) && (string)$row['enabled'] === '1' ? '1' : '0',
                'category'=>'visit_rules',
                'category_label'=>self::triple('Règles de visite','Visiting rules','Besuchsregeln'),
                'question'=>$question,
                'answer'=>$answer,
                'keywords'=>array_values(array_filter(array_map('sanitize_text_field', preg_split('/\r\n|\r|\n|,/', (string)($row['keywords'] ?? ''))))),
                'last_verified'=>preg_match('/^20\d{2}-\d{2}-\d{2}$/', (string)($row['last_verified'] ?? '')) ? (string)$row['last_verified'] : wp_date('Y-m-d'),
            );
        }
        if (!$knowledge) $knowledge = $current['knowledge'];

        $saved = array(
            'store_version'=>self::STORE_VERSION,
            'schema_enabled'=>isset($raw['schema_enabled']) && (string)$raw['schema_enabled'] === '1' ? '1' : '0',
            'rules_visible'=>isset($raw['rules_visible']) && (string)$raw['rules_visible'] === '1' ? '1' : '0',
            'identity'=>$identity,
            'knowledge'=>$knowledge,
        );
        update_option(self::OPTION, $saved, false);
        do_action('litespeed_purge_all');
        wp_safe_redirect(add_query_arg(array('page'=>self::PAGE, 'updated'=>'1'), admin_url('admin.php')));
        exit;
    }

    private static function seopress_pro_detected() {
        return defined('SEOPRESS_PRO_VERSION') || defined('SEOPRESS_PRO_DIR_PATH') || class_exists('SEOPress_Pro');
    }

    private static function current_language() {
        return class_exists('Parcs_HT_Schedule') ? Parcs_HT_Schedule::language() : 'fr';
    }

    private static function translated($value, $language, $fallback = '') {
        if (class_exists('Parcs_HT_Schedule')) return Parcs_HT_Schedule::translation($value, $language, $fallback);
        return is_array($value) ? (string)($value[$language] ?? $value['fr'] ?? $fallback) : (string)$value;
    }

    private static function page_is_relevant() {
        if (is_front_page()) return true;
        if (!is_singular()) return false;
        global $post;
        if (!$post || empty($post->post_content)) return false;
        $bases = array('parc_horaires_tarifs','parc_horaires_aujourdhui','parc_calendrier','parc_tableau_tarifs');
        foreach ($bases as $base) {
            foreach (array('', '_fr', '_en', '_de') as $suffix) {
                if (has_shortcode($post->post_content, $base . $suffix)) return true;
            }
        }
        return false;
    }

    private static function public_season($year, $season) {
        if (!is_array($season)) return false;
        if (class_exists('Parcs_HT_Public_Visibility')) {
            $state = Parcs_HT_Public_Visibility::scheduled_state((string)$year);
            if ($state === 'on') return true;
            if ($state === 'off') return false;
        }
        if (array_key_exists('calendar_visible', $season)) return (string)$season['calendar_visible'] === '1';
        return (string)($season['published'] ?? '0') === '1';
    }

    private static function schema_day_urls($days) {
        $map = array(
            '1'=>'https://schema.org/Monday',
            '2'=>'https://schema.org/Tuesday',
            '3'=>'https://schema.org/Wednesday',
            '4'=>'https://schema.org/Thursday',
            '5'=>'https://schema.org/Friday',
            '6'=>'https://schema.org/Saturday',
            '7'=>'https://schema.org/Sunday',
        );
        $out = array();
        foreach ((array)$days as $day) {
            $key = (string)$day;
            if (isset($map[$key])) $out[] = $map[$key];
        }
        return array_values(array_unique($out));
    }

    private static function valid_time($value) {
        $value = trim((string)$value);
        return preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $value) ? $value : '';
    }

    private static function opening_hours() {
        if (!class_exists('Parcs_HT_Defaults')) return array(array(), array());
        $all = Parcs_HT_Defaults::all_settings();
        $regular = array();
        $special = array();
        foreach ((array)($all['seasons'] ?? array()) as $year => $season) {
            if (!self::public_season($year, $season)) continue;
            $season_start = (string)($season['season_start'] ?? '');
            $season_end = (string)($season['season_end'] ?? '');
            foreach ((array)($season['regular_periods'] ?? array()) as $row) {
                if (!is_array($row) || (string)($row['enabled'] ?? '0') !== '1') continue;
                $opens = self::valid_time($row['open'] ?? '');
                $closes = self::valid_time($row['close'] ?? '');
                $days = self::schema_day_urls($row['weekdays'] ?? array());
                if ($opens === '' || $closes === '' || !$days) continue;
                $base = array('@type'=>'OpeningHoursSpecification','dayOfWeek'=>$days,'opens'=>$opens,'closes'=>$closes);
                $from = (string)($row['start'] ?? $season_start);
                $through = (string)($row['end'] ?? $season_end);
                if (preg_match('/^20\d{2}-\d{2}-\d{2}$/', $from)) $base['validFrom'] = $from;
                if (preg_match('/^20\d{2}-\d{2}-\d{2}$/', $through)) $base['validThrough'] = $through;
                $regular[] = $base;
                $opens2 = self::valid_time($row['open2'] ?? '');
                $closes2 = self::valid_time($row['close2'] ?? '');
                if ($opens2 !== '' && $closes2 !== '') {
                    $second = $base;
                    $second['opens'] = $opens2;
                    $second['closes'] = $closes2;
                    $regular[] = $second;
                }
            }
            foreach ((array)($season['exceptions'] ?? array()) as $row) {
                if (!is_array($row) || (string)($row['enabled'] ?? '0') !== '1') continue;
                $from = (string)($row['start'] ?? '');
                $through = (string)($row['end'] ?? $from);
                if (!preg_match('/^20\d{2}-\d{2}-\d{2}$/', $from) || !preg_match('/^20\d{2}-\d{2}-\d{2}$/', $through)) continue;
                if ((string)($row['type'] ?? 'hours') === 'closed') {
                    $special[] = array('@type'=>'OpeningHoursSpecification','validFrom'=>$from,'validThrough'=>$through,'opens'=>'00:00','closes'=>'00:00');
                    continue;
                }
                $opens = self::valid_time($row['open'] ?? '');
                $closes = self::valid_time($row['close'] ?? '');
                if ($opens !== '' && $closes !== '') {
                    $special[] = array('@type'=>'OpeningHoursSpecification','validFrom'=>$from,'validThrough'=>$through,'opens'=>$opens,'closes'=>$closes);
                }
                $opens2 = self::valid_time($row['open2'] ?? '');
                $closes2 = self::valid_time($row['close2'] ?? '');
                if ($opens2 !== '' && $closes2 !== '') {
                    $special[] = array('@type'=>'OpeningHoursSpecification','validFrom'=>$from,'validThrough'=>$through,'opens'=>$opens2,'closes'=>$closes2);
                }
            }
        }
        return array($regular, $special);
    }

    private static function entity() {
        $settings = self::settings();
        $identity = (array)($settings['identity'] ?? array());
        $park_settings = class_exists('Parcs_HT_Defaults') ? Parcs_HT_Defaults::settings() : array();
        $general = (array)($park_settings['general'] ?? array());
        $language = self::current_language();
        $name = self::translated($general['park_name'] ?? array(), $language, get_bloginfo('name'));
        if ($name === '') $name = get_bloginfo('name');

        $types = array('TouristAttraction');
        $has_address = trim((string)($identity['street'] ?? '')) !== '' && trim((string)($identity['city'] ?? '')) !== '';
        if ($has_address) $types[] = 'LocalBusiness';

        $entity = array(
            '@type'=>$types,
            '@id'=>home_url('/#parc'),
            'name'=>$name,
            'url'=>home_url('/'),
        );
        if (!empty($identity['phone'])) $entity['telephone'] = (string)$identity['phone'];
        if (!empty($identity['email'])) $entity['email'] = (string)$identity['email'];
        if (!empty($identity['logo_url'])) $entity['logo'] = (string)$identity['logo_url'];
        if (!empty($identity['image_url'])) $entity['image'] = (string)$identity['image_url'];
        if (!empty($identity['same_as']) && is_array($identity['same_as'])) $entity['sameAs'] = array_values($identity['same_as']);
        if ($has_address || !empty($identity['postal_code']) || !empty($identity['country'])) {
            $address = array('@type'=>'PostalAddress');
            if (!empty($identity['street'])) $address['streetAddress'] = (string)$identity['street'];
            if (!empty($identity['postal_code'])) $address['postalCode'] = (string)$identity['postal_code'];
            if (!empty($identity['city'])) $address['addressLocality'] = (string)$identity['city'];
            if (!empty($identity['country'])) $address['addressCountry'] = (string)$identity['country'];
            $entity['address'] = $address;
        }
        if (is_numeric($identity['latitude'] ?? null) && is_numeric($identity['longitude'] ?? null)) {
            $entity['geo'] = array('@type'=>'GeoCoordinates','latitude'=>(float)$identity['latitude'],'longitude'=>(float)$identity['longitude']);
        }
        list($regular, $special) = self::opening_hours();
        if ($regular) $entity['openingHoursSpecification'] = $regular;
        if ($special) $entity['specialOpeningHoursSpecification'] = $special;
        return $entity;
    }

    public static function render_structured_data() {
        if (is_admin() || is_feed() || !self::page_is_relevant()) return;
        $settings = self::settings();
        if ((string)($settings['schema_enabled'] ?? '1') !== '1') return;
        if (self::seopress_pro_detected()) return;
        if (!apply_filters('parcs_ht_ai_google_emit_schema', true)) return;

        $graph = array('@context'=>'https://schema.org','@graph'=>array(self::entity()));
        echo "\n<script type=\"application/ld+json\" class=\"parcs-ht-structured-data\">";
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wp_json_encode produit du JSON valide dans un script application/ld+json.
        echo wp_json_encode($graph, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        echo "</script>\n";
    }

    private static function language_from_tag($tag) {
        foreach (array('fr','en','de') as $language) {
            if (substr((string)$tag, -3) === '_' . $language) return $language;
        }
        return self::current_language();
    }

    public static function render_visit_rules($language = '') {
        $settings = self::settings();
        if ((string)($settings['rules_visible'] ?? '1') !== '1') return '';
        $language = in_array($language, array('fr','en','de'), true) ? $language : self::current_language();
        $items = array();
        $category = '';
        foreach ((array)($settings['knowledge'] ?? array()) as $row) {
            if (!is_array($row) || (string)($row['enabled'] ?? '0') !== '1' || (string)($row['category'] ?? '') !== 'visit_rules') continue;
            $question = self::translated($row['question'] ?? array(), $language, '');
            $answer = self::translated($row['answer'] ?? array(), $language, '');
            if ($question === '' || $answer === '') continue;
            if ($category === '') $category = self::translated($row['category_label'] ?? array(), $language, 'Règles de visite');
            $items[] = array('question'=>$question,'answer'=>$answer);
        }
        if (!$items) return '';
        if ($category === '') $category = array('fr'=>'Règles de visite','en'=>'Visiting rules','de'=>'Besuchsregeln')[$language];

        $html = '<section class="parcs-ht-official-info" data-htp-component="official-info"><details><summary>' . esc_html($category) . '</summary><div class="parcs-ht-official-info-content">';
        foreach ($items as $item) {
            $html .= '<article><h3>' . esc_html($item['question']) . '</h3><p>' . esc_html($item['answer']) . '</p></article>';
        }
        return $html . '</div></details></section>';
    }

    public static function append_visit_rules($output, $tag, $attr, $m) {
        unset($attr, $m);
        if (is_admin()) return $output;
        $allowed = array('parc_horaires_tarifs','parc_horaires_tarifs_fr','parc_horaires_tarifs_en','parc_horaires_tarifs_de');
        if (!in_array((string)$tag, $allowed, true)) return $output;
        static $printed = false;
        if ($printed) return $output;
        $printed = true;
        return $output . self::render_visit_rules(self::language_from_tag($tag));
    }

    private static function field($label, $name, $value, $type = 'text', $description = '') {
        echo '<label class="htp-ai-field"><span>' . esc_html($label) . '</span><input type="' . esc_attr($type) . '" name="ai_google[identity][' . esc_attr($name) . ']" value="' . esc_attr((string)$value) . '">';
        if ($description !== '') echo '<small>' . esc_html($description) . '</small>';
        echo '</label>';
    }

    private static function translation_fields($name, $values, $label, $textarea = false) {
        $values = is_array($values) ? $values : array();
        echo '<fieldset class="htp-ai-translations"><legend>' . esc_html($label) . '</legend>';
        foreach (array('fr'=>'FR','en'=>'EN','de'=>'DE') as $lang => $short) {
            echo '<label><span>' . esc_html($short) . '</span>';
            if ($textarea) {
                echo '<textarea rows="3" name="ai_google[knowledge][0][' . esc_attr($name) . '][' . esc_attr($lang) . ']">' . esc_textarea((string)($values[$lang] ?? '')) . '</textarea>';
            } else {
                echo '<input type="text" name="ai_google[knowledge][0][' . esc_attr($name) . '][' . esc_attr($lang) . ']" value="' . esc_attr((string)($values[$lang] ?? '')) . '">';
            }
            echo '</label>';
        }
        echo '</fieldset>';
    }

    public static function page() {
        if (!current_user_can('manage_options')) return;
        $settings = self::settings();
        $identity = (array)($settings['identity'] ?? array());
        $row = isset($settings['knowledge'][0]) && is_array($settings['knowledge'][0]) ? $settings['knowledge'][0] : self::defaults()['knowledge'][0];
        $public = (string)get_option('blog_public', '1') === '1';
        ?>
        <div class="wrap htp-ai-google-admin">
            <h1>IA & Google</h1>
            <p class="description">Complète uniquement les données propres au parc. SEOPress continue de gérer les titres, métadonnées, réseaux sociaux et sitemaps.</p>
            <?php if (isset($_GET['updated'])) : /* phpcs:ignore WordPress.Security.NonceVerification.Recommended -- message visuel uniquement. */ ?><div class="notice notice-success is-dismissible"><p>Les réglages IA & Google ont été enregistrés.</p></div><?php endif; ?>
            <?php if (self::seopress_pro_detected()) : ?><div class="notice notice-warning"><p>SEOPress PRO a été détecté : la sortie JSON-LD de Gestion du parc est mise en pause automatiquement afin d’éviter un doublon potentiel. Vérifiez les schémas SEOPress avant de forcer une autre stratégie.</p></div><?php endif; ?>

            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <input type="hidden" name="action" value="parcs_ht_save_ai_google">
                <?php wp_nonce_field('parcs_ht_save_ai_google'); ?>

                <section class="htp-ai-card">
                    <h2>Données structurées</h2>
                    <p>Les horaires et fermetures exceptionnelles sont lus directement depuis les saisons existantes. Aucun horaire n’est ressaisi ici.</p>
                    <label class="htp-ai-toggle"><input type="checkbox" name="ai_google[schema_enabled]" value="1" <?php checked((string)($settings['schema_enabled'] ?? '1'), '1'); ?>> Générer les données structurées du parc sur l’accueil et les pages Horaires / Tarifs</label>
                    <p class="description">Le type <code>TouristAttraction</code> est toujours utilisé. <code>LocalBusiness</code> n’est ajouté que lorsqu’une adresse exploitable est renseignée.</p>
                </section>

                <section class="htp-ai-card">
                    <h2>Identité complémentaire du parc</h2>
                    <p>Ne renseignez que ce qui n’est pas déjà une source de vérité ailleurs. Le nom et l’URL sont récupérés automatiquement depuis WordPress / Gestion du parc.</p>
                    <div class="htp-ai-grid">
                        <?php self::field('Adresse', 'street', $identity['street'] ?? ''); ?>
                        <?php self::field('Code postal', 'postal_code', $identity['postal_code'] ?? ''); ?>
                        <?php self::field('Ville', 'city', $identity['city'] ?? ''); ?>
                        <?php self::field('Pays (code ISO)', 'country', $identity['country'] ?? 'FR'); ?>
                        <?php self::field('Téléphone public', 'phone', $identity['phone'] ?? '', 'text'); ?>
                        <?php self::field('E-mail public', 'email', $identity['email'] ?? '', 'email'); ?>
                        <?php self::field('Latitude', 'latitude', $identity['latitude'] ?? '', 'text'); ?>
                        <?php self::field('Longitude', 'longitude', $identity['longitude'] ?? '', 'text'); ?>
                        <?php self::field('Logo public', 'logo_url', $identity['logo_url'] ?? '', 'url'); ?>
                        <?php self::field('Image principale', 'image_url', $identity['image_url'] ?? '', 'url'); ?>
                    </div>
                    <label class="htp-ai-wide"><span>Profils officiels / sameAs — une URL par ligne</span><textarea rows="4" name="ai_google[identity][same_as]"><?php echo esc_textarea(implode("\n", (array)($identity['same_as'] ?? array()))); ?></textarea></label>
                </section>

                <section class="htp-ai-card">
                    <h2>Base de connaissances officielle — Règles de visite</h2>
                    <p>Cette première base prépare la future FAQ sans dupliquer les informations. Les mots-clés internes ne sont jamais affichés publiquement.</p>
                    <input type="hidden" name="ai_google[knowledge][0][id]" value="<?php echo esc_attr((string)($row['id'] ?? 'visitor-feeding')); ?>">
                    <input type="hidden" name="ai_google[knowledge][0][enabled]" value="1">
                    <?php self::translation_fields('question', $row['question'] ?? array(), 'Question'); ?>
                    <?php self::translation_fields('answer', $row['answer'] ?? array(), 'Réponse officielle', true); ?>
                    <label class="htp-ai-wide"><span>Mots-clés internes</span><textarea rows="2" name="ai_google[knowledge][0][keywords]"><?php echo esc_textarea(implode(', ', (array)($row['keywords'] ?? array()))); ?></textarea><small>Ex. popcorn, pop-corn, nourrir. Ils servent à la recherche interne future et ne sont pas injectés dans le HTML public.</small></label>
                    <label class="htp-ai-field"><span>Dernière vérification</span><input type="date" name="ai_google[knowledge][0][last_verified]" value="<?php echo esc_attr((string)($row['last_verified'] ?? wp_date('Y-m-d'))); ?>"></label>
                    <label class="htp-ai-toggle"><input type="checkbox" name="ai_google[rules_visible]" value="1" <?php checked((string)($settings['rules_visible'] ?? '1'), '1'); ?>> Afficher discrètement « Règles de visite » sous la page complète Horaires & Tarifs</label>
                    <p class="description">Le contenu est rendu côté serveur dans le HTML initial, à l’intérieur d’un accordéon fermé par défaut. Il reste accessible à un humain qui choisit de l’ouvrir.</p>
                </section>

                <section class="htp-ai-card">
                    <h2>Contrôles d’indexation</h2>
                    <ul class="htp-ai-status">
                        <li><strong>Visibilité WordPress :</strong> <?php echo $public ? 'indexation autorisée' : 'indexation désactivée dans Réglages → Lecture'; ?></li>
                        <li><strong>SEOPress PRO :</strong> <?php echo self::seopress_pro_detected() ? 'détecté — JSON-LD du parc suspendu' : 'non détecté'; ?></li>
                        <li><strong>robots.txt :</strong> <a href="<?php echo esc_url(home_url('/robots.txt')); ?>" target="_blank" rel="noopener">ouvrir le fichier public</a></li>
                    </ul>
                    <p class="description">Gestion du parc ne force pas Googlebot ni OAI-SearchBot dans robots.txt : un fichier statique, un pare-feu ou un CDN peut imposer ses propres règles. Ce contrôle doit être vérifié sur le site après installation.</p>
                </section>

                <?php submit_button('Enregistrer IA & Google'); ?>
            </form>
        </div>
        <style>
        .htp-ai-google-admin{max-width:1180px}.htp-ai-card{background:#fff;border:1px solid #dcdcde;border-radius:10px;padding:20px;margin:18px 0}.htp-ai-card h2{margin-top:0}.htp-ai-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px}.htp-ai-field,.htp-ai-wide,.htp-ai-translations label{display:flex;flex-direction:column;gap:6px}.htp-ai-field>span,.htp-ai-wide>span,.htp-ai-translations legend{font-weight:600}.htp-ai-field input,.htp-ai-wide textarea,.htp-ai-translations input,.htp-ai-translations textarea{width:100%;max-width:none}.htp-ai-wide,.htp-ai-translations{margin-top:14px}.htp-ai-translations{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:10px;border:0;padding:0}.htp-ai-translations legend{grid-column:1/-1}.htp-ai-translations label span{font-size:12px;font-weight:600;color:#646970}.htp-ai-toggle{display:block;margin:12px 0}.htp-ai-status{margin-bottom:0}@media(max-width:900px){.htp-ai-grid,.htp-ai-translations{grid-template-columns:1fr}}
        </style>
        <?php
    }
}
