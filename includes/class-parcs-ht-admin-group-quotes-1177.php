<?php

if (!defined('ABSPATH')) { exit; }

/** Écran métier Devis groupes 1.17.7. */
final class Parcs_HT_Admin_Group_Quotes_1177 {
    const PAGE = 'parcs-ht-group-quotes-1177';

    public static function init() {
        add_action('admin_init', array(__CLASS__, 'route_legacy'), 2);
        add_action('admin_menu', array(__CLASS__, 'menu'), 62);
        add_action('admin_enqueue_scripts', array(__CLASS__, 'assets'), 70);
        add_action('admin_post_parcs_ht_save_quote_binding_1177', array(__CLASS__, 'save_binding'));
        add_action('admin_post_parcs_ht_save_quote_forms_1177', array(__CLASS__, 'save_forms'));
        add_action('admin_post_parcs_ht_save_quote_gate_1177', array(__CLASS__, 'save_gate'));
        add_action('admin_post_parcs_ht_save_quote_engine_1177', array(__CLASS__, 'save_engine'));
    }

    private static function valid_year($year) {
        $year = trim((string)$year);
        return preg_match('/^20\d{2}$/', $year) ? $year : '';
    }

    private static function requested_year() {
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- sélection d'écran en lecture seule.
        return isset($_GET['season']) ? self::valid_year(sanitize_text_field(wp_unslash($_GET['season']))) : '';
    }

    private static function selected_year($all = null) {
        if ($all === null) $all = Parcs_HT_Defaults::all_settings();
        $requested = self::requested_year();
        if ($requested !== '' && isset($all['seasons'][$requested])) return $requested;
        $years = array_keys((array)($all['seasons'] ?? array()));
        $years = array_values(array_filter(array_map('strval', $years), static function ($year) {
            return (bool)preg_match('/^20\d{2}$/', $year);
        }));
        sort($years, SORT_NUMERIC);
        $current = wp_date('Y');
        if (in_array($current, $years, true)) return $current;
        return $years ? (string)end($years) : '';
    }

    private static function page_url($year = '') {
        $args = array('page'=>self::PAGE);
        if (self::valid_year($year) !== '') $args['season'] = $year;
        return add_query_arg($args, admin_url('admin.php'));
    }

    public static function route_legacy() {
        if (!is_admin() || !current_user_can('manage_options')) return;
        // phpcs:disable WordPress.Security.NonceVerification.Recommended -- routage en lecture seule.
        $page = isset($_GET['page']) ? sanitize_key(wp_unslash($_GET['page'])) : '';
        $tab = isset($_GET['tab']) ? sanitize_key(wp_unslash($_GET['tab'])) : '';
        $legacy = isset($_GET['legacy_quote']) && sanitize_text_field(wp_unslash($_GET['legacy_quote'])) === '1';
        // phpcs:enable WordPress.Security.NonceVerification.Recommended
        if ($legacy) return;

        $is_old_tab = class_exists('Parcs_HT_Admin') && $page === Parcs_HT_Admin::PAGE && $tab === 'htp-quote';
        $is_old_page = class_exists('Parcs_HT_Group_Quotes') && $page === Parcs_HT_Group_Quotes::PAGE;
        $is_language_page = class_exists('Parcs_HT_Quote_Languages') && $page === Parcs_HT_Quote_Languages::PAGE;
        $is_gate_page = class_exists('Parcs_HT_Quote_Gate') && $page === Parcs_HT_Quote_Gate::PAGE;
        if (!$is_old_tab && !$is_old_page && !$is_language_page && !$is_gate_page) return;

        wp_safe_redirect(self::page_url(self::requested_year()));
        exit;
    }

    public static function menu() {
        // Page technique volontairement masquée du sous-menu principal : elle reste
        // accessible depuis la famille Groupes, comme prévu par l'architecture 1.17.2.
        add_submenu_page(null, 'Devis groupes', 'Devis groupes', 'manage_options', self::PAGE, array(__CLASS__, 'page'));
    }

    public static function assets($hook) {
        if ($hook !== 'admin_page_' . self::PAGE) return;
        wp_enqueue_style('parcs-ht-admin-quotes-1177', PARCS_HT_URL . 'assets/admin-quotes-1177.css', array(), PARCS_HT_VERSION);
    }

    private static function canonical_tariffs($year) {
        $raw = get_option(Parcs_HT_Defaults::OPTION, array());
        if (!is_array($raw) || empty($raw['seasons'][$year]) || !is_array($raw['seasons'][$year])) return array();
        $tariffs = $raw['seasons'][$year]['tariffs'] ?? array();
        return is_array($tariffs) ? $tariffs : array();
    }

    private static function rows($year) {
        $tariffs = self::canonical_tariffs($year);
        return isset($tariffs['groups']) && is_array($tariffs['groups']) ? $tariffs['groups'] : array();
    }

    private static function columns($year) {
        $tariffs = self::canonical_tariffs($year);
        return isset($tariffs['columns']['groups']) && is_array($tariffs['columns']['groups']) ? $tariffs['columns']['groups'] : array();
    }

    private static function row_label($row) {
        if (!is_array($row)) return '';
        $label = isset($row['label']) && is_array($row['label']) ? $row['label'] : array();
        return class_exists('Parcs_HT_Schedule') ? Parcs_HT_Schedule::translation($label, 'fr', '') : (string)($label['fr'] ?? '');
    }

    private static function column_label($column) {
        if (!is_array($column)) return '';
        $label = isset($column['label']) && is_array($column['label']) ? $column['label'] : array();
        return class_exists('Parcs_HT_Schedule') ? Parcs_HT_Schedule::translation($label, 'fr', '') : (string)($label['fr'] ?? '');
    }

    private static function status_badge($ok, $yes = 'OK', $no = 'À vérifier') {
        return '<span class="htp-1177-status ' . ($ok ? 'is-ok' : 'is-warning') . '">' . esc_html($ok ? $yes : $no) . '</span>';
    }

    private static function family_navigation($year) {
        $group_url = add_query_arg(array('page'=>Parcs_HT_Admin_Group_Tariffs::PAGE, 'season'=>$year), admin_url('admin.php'));
        $guide_url = class_exists('Parcs_HT_Pedagogical_Guides')
            ? add_query_arg(array('page'=>Parcs_HT_Pedagogical_Guides::PAGE), admin_url('admin.php'))
            : admin_url('admin.php');
        echo '<nav class="htp-1177-family" aria-label="Rubrique Groupes">';
        echo '<a class="button" href="' . esc_url($group_url) . '">Tarifs groupes</a>';
        echo '<a class="button button-primary" href="' . esc_url(self::page_url($year)) . '">Devis groupes</a>';
        echo '<a class="button" href="' . esc_url($guide_url) . '">Guides pédagogiques</a>';
        echo '</nav>';
    }

    private static function redirect_saved($year, $notice) {
        $url = add_query_arg(array('saved'=>sanitize_key($notice)), self::page_url($year));
        wp_safe_redirect($url);
        exit;
    }

    private static function notice() {
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- message de confirmation uniquement.
        $notice = isset($_GET['saved']) ? sanitize_key(wp_unslash($_GET['saved'])) : '';
        $messages = array(
            'binding'=>'La liaison des tarifs avec le devis a été enregistrée.',
            'forms'=>'Les formulaires Contact Form 7 ont été enregistrés.',
            'gate'=>'Les règles d’accès au devis ont été enregistrées.',
            'engine'=>'Les champs techniques du devis ont été enregistrés.',
        );
        if (!isset($messages[$notice])) return;
        echo '<div class="notice notice-success is-dismissible"><p>' . esc_html($messages[$notice]) . '</p></div>';
    }

    public static function page() {
        if (!current_user_can('manage_options')) return;
        $all = Parcs_HT_Defaults::all_settings();
        $year = self::selected_year($all);
        if ($year === '') {
            echo '<div class="wrap"><h1>Devis groupes</h1><p>Aucune saison annuelle n’est disponible.</p></div>';
            return;
        }

        $season = isset($all['seasons'][$year]) && is_array($all['seasons'][$year]) ? $all['seasons'][$year] : array();
        $raw_enabled = (string)($season['group_quotes_enabled'] ?? '0') === '1';
        $effective_enabled = class_exists('Parcs_HT_Public_Visibility')
            ? Parcs_HT_Public_Visibility::module_visible($year, 'group_quotes_enabled', $raw_enabled)
            : $raw_enabled;
        $grid_ready = class_exists('Parcs_HT_Public_Visibility')
            ? Parcs_HT_Public_Visibility::group_tariff_grid_ready($year)
            : !empty(self::rows($year));
        $quotes = Parcs_HT_Group_Quotes::settings(false);
        $binding = Parcs_HT_Group_Quotes::binding_for_year($year, $quotes);
        $resolved = Parcs_HT_Group_Quotes::season_for_year($year);
        $forms = class_exists('Parcs_HT_Quote_Languages') ? Parcs_HT_Quote_Languages::settings() : array('fr'=>'','en'=>'','de'=>'');
        $gate = class_exists('Parcs_HT_Quote_Gate') ? Parcs_HT_Quote_Gate::settings() : array();
        $rows = self::rows($year);
        $columns = self::columns($year);
        $overview = class_exists('Parcs_HT_Admin_Overview')
            ? add_query_arg(array('page'=>Parcs_HT_Admin_Overview::PAGE, 'season'=>$year), admin_url('admin.php'))
            : admin_url('admin.php');
        $legacy = add_query_arg(array('page'=>Parcs_HT_Admin::PAGE, 'tab'=>'htp-quote', 'season'=>$year, 'legacy_quote'=>'1'), admin_url('admin.php'));
        $content = class_exists('Parcs_HT_Public_Content')
            ? add_query_arg(array('page'=>Parcs_HT_Public_Content::PAGE), admin_url('admin.php'))
            : admin_url('admin.php');
        ?>
        <div class="wrap htp-1177-quotes">
            <h1>Devis groupes</h1>
            <p class="description">Le devis utilise exclusivement les tarifs groupes et la liaison de l’année sélectionnée. Aucune autre année n’est utilisée en secours.</p>
            <?php self::notice(); self::family_navigation($year); ?>
            <?php if (class_exists('Parcs_HT_Admin_Navigation')) Parcs_HT_Admin_Navigation::render_year_context(self::PAGE, $year); ?>

            <section class="htp-1177-card">
                <div class="htp-1177-card-head"><div><h2>État du devis <?php echo esc_html($year); ?></h2><p>Diagnostic de la chaîne complète avant l’affichage public.</p></div><a class="button" href="<?php echo esc_url($overview); ?>">Activation annuelle</a></div>
                <div class="htp-1177-status-grid">
                    <div><strong>Activation annuelle</strong><?php echo self::status_badge($effective_enabled, 'Active', 'Inactive'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- HTML construit localement. ?><small><?php echo esc_html($raw_enabled === $effective_enabled ? 'État manuel effectif' : 'État ajusté par les dates automatiques'); ?></small></div>
                    <div><strong>Grille tarifs groupes</strong><?php echo self::status_badge($grid_ready, 'Prête', 'Incomplète'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- HTML construit localement. ?><small>Source : tarifs.groups de <?php echo esc_html($year); ?></small></div>
                    <div><strong>Liaison tarifs / devis</strong><?php echo self::status_badge((bool)$binding, 'Valide', 'À configurer'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- HTML construit localement. ?><small>Identifiants permanents de la même année</small></div>
                    <div><strong>Devis automatique</strong><?php echo self::status_badge((bool)$resolved, 'Disponible', 'Indisponible'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- HTML construit localement. ?><small>Activation + grille + liaison</small></div>
                </div>
                <?php if ($grid_ready && !$resolved) : ?><div class="notice notice-warning inline"><p><strong>Les tarifs groupes sont bien disponibles.</strong> Si le devis est indisponible, le problème vient de l’activation annuelle ou de la liaison du devis, pas de la grille tarifaire.</p></div><?php endif; ?>
            </section>

            <?php self::binding_section($year, $rows, $columns, $binding); ?>
            <?php self::forms_section($year, $forms); ?>
            <?php self::gate_section($year, $gate); ?>
            <?php self::engine_section($year, $quotes); ?>

            <section class="htp-1177-card">
                <h2>Contenu et présentation</h2>
                <p>Les textes publics communs FR / EN / DE restent centralisés dans « Contenus & traductions ». Les blocs historiques avancés de la page de devis sont conservés pendant la refonte afin de ne perdre aucune donnée.</p>
                <p><a class="button" href="<?php echo esc_url($content); ?>">Contenus & traductions</a> <a class="button" href="<?php echo esc_url($legacy); ?>">Éditeur avancé historique</a></p>
            </section>
        </div>
        <?php
    }

    private static function binding_section($year, $rows, $columns, $binding) {
        $binding = is_array($binding) ? $binding : array();
        ?>
        <section class="htp-1177-card">
            <h2>Liaison des tarifs avec le devis</h2>
            <p class="description">Chaque rôle pointe vers une ligne permanente de la grille Groupes <?php echo esc_html($year); ?>. Renommer ou déplacer une ligne ne casse pas le calcul.</p>
            <?php if (!$rows || !$columns) : ?>
                <div class="notice notice-warning inline"><p>La grille Groupes de cette année ne contient pas encore toutes les données nécessaires.</p></div>
            <?php else : ?>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <input type="hidden" name="action" value="parcs_ht_save_quote_binding_1177">
                <input type="hidden" name="year" value="<?php echo esc_attr($year); ?>">
                <?php wp_nonce_field('parcs_ht_save_quote_binding_1177'); ?>
                <div class="htp-1177-grid">
                    <label><span>Colonne de prix</span><select name="column_id" required><?php foreach ($columns as $column) : $id = sanitize_key((string)($column['id'] ?? '')); if ($id === '') continue; ?><option value="<?php echo esc_attr($id); ?>" <?php selected((string)($binding['column_id'] ?? ''), $id); ?>><?php echo esc_html(self::column_label($column) ?: $id); ?></option><?php endforeach; ?></select></label>
                    <?php foreach (array('child'=>'Enfant / scolaire','adult'=>'Adulte','disability'=>'Personne en situation de handicap','companion'=>'Accompagnateur') as $role=>$label) : ?>
                        <label><span><?php echo esc_html($label); ?></span><select name="<?php echo esc_attr($role . '_row_id'); ?>" required><?php foreach ($rows as $row) : $id = sanitize_key((string)($row['id'] ?? '')); if ($id === '') continue; ?><option value="<?php echo esc_attr($id); ?>" <?php selected((string)($binding[$role . '_row_id'] ?? ''), $id); ?>><?php echo esc_html(self::row_label($row) ?: $id); ?></option><?php endforeach; ?></select></label>
                    <?php endforeach; ?>
                    <label><span>1 adulte gratuit pour X enfants</span><input type="number" min="1" step="1" name="free_adult_children" value="<?php echo esc_attr((string)($binding['free_adult_children'] ?? '10')); ?>"></label>
                    <label><span>Arrondi supérieur à partir de X enfants restants</span><input type="number" min="1" step="1" name="free_adult_round_threshold" value="<?php echo esc_attr((string)($binding['free_adult_round_threshold'] ?? '5')); ?>"></label>
                </div>
                <?php submit_button('Enregistrer la liaison', 'primary', 'submit', false); ?>
            </form>
            <?php endif; ?>
        </section>
        <?php
    }

    private static function forms_section($year, $forms) {
        ?>
        <section class="htp-1177-card">
            <h2>Formulaires Contact Form 7</h2>
            <p class="description">Chaque langue peut utiliser son propre formulaire. Un champ vide conserve le formulaire général comme secours.</p>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <input type="hidden" name="action" value="parcs_ht_save_quote_forms_1177"><input type="hidden" name="year" value="<?php echo esc_attr($year); ?>">
                <?php wp_nonce_field('parcs_ht_save_quote_forms_1177'); ?>
                <div class="htp-1177-grid">
                    <?php foreach (array('fr'=>'Français','en'=>'Anglais','de'=>'Allemand') as $lang=>$label) : ?><label><span><?php echo esc_html($label); ?></span><input type="text" class="large-text code" name="forms[<?php echo esc_attr($lang); ?>]" value="<?php echo esc_attr((string)($forms[$lang] ?? '')); ?>" placeholder='[contact-form-7 id="..."]'></label><?php endforeach; ?>
                </div>
                <?php submit_button('Enregistrer les formulaires', 'secondary', 'submit', false); ?>
            </form>
        </section>
        <?php
    }

    private static function gate_section($year, $gate) {
        $gate = is_array($gate) ? $gate : array();
        ?>
        <section class="htp-1177-card">
            <h2>Accès au devis</h2>
            <p class="description">Le message d’indisponibilité concerne le devis automatique. Il ne doit jamais déclarer que les tarifs groupes sont absents si la grille de l’année est publiée.</p>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <input type="hidden" name="action" value="parcs_ht_save_quote_gate_1177"><input type="hidden" name="year" value="<?php echo esc_attr($year); ?>">
                <?php wp_nonce_field('parcs_ht_save_quote_gate_1177'); ?>
                <p><label><input type="checkbox" name="enabled" value="1" <?php checked((string)($gate['enabled'] ?? '1'), '1'); ?>> Choisir la date avant d’afficher le formulaire complet</label></p>
                <details><summary>Date où le parc est fermé</summary><div class="htp-1177-details"><p><label><input type="checkbox" name="closed_enabled" value="1" <?php checked((string)($gate['closed_enabled'] ?? '1'), '1'); ?>> Afficher un avertissement sans bloquer le devis</label></p><?php foreach (array('fr','en','de') as $lang) : ?><label><span><?php echo esc_html(strtoupper($lang)); ?></span><textarea rows="3" name="closed_message_<?php echo esc_attr($lang); ?>"><?php echo esc_textarea((string)($gate['closed_message_' . $lang] ?? '')); ?></textarea></label><?php endforeach; ?><label><span>Contact</span><input type="text" name="closed_contact" value="<?php echo esc_attr((string)($gate['closed_contact'] ?? '')); ?>"></label></div></details>
                <details><summary>Devis automatique indisponible</summary><div class="htp-1177-details"><p><label><input type="checkbox" name="unavailable_enabled" value="1" <?php checked((string)($gate['unavailable_enabled'] ?? '1'), '1'); ?>> Afficher un message lorsque le devis ne peut réellement pas être calculé</label></p><?php foreach (array('fr','en','de') as $lang) : ?><label><span><?php echo esc_html(strtoupper($lang)); ?></span><textarea rows="3" name="unavailable_message_<?php echo esc_attr($lang); ?>"><?php echo esc_textarea((string)($gate['unavailable_message_' . $lang] ?? '')); ?></textarea></label><?php endforeach; ?><label><span>Contact</span><input type="text" name="unavailable_contact" value="<?php echo esc_attr((string)($gate['unavailable_contact'] ?? '')); ?>"></label></div></details>
                <?php submit_button('Enregistrer l’accès au devis', 'secondary', 'submit', false); ?>
            </form>
        </section>
        <?php
    }

    private static function engine_section($year, $settings) {
        $settings = is_array($settings) ? $settings : array();
        ?>
        <section class="htp-1177-card">
            <details><summary><strong>Options techniques avancées</strong></summary><div class="htp-1177-details">
                <p class="description">Ces valeurs correspondent aux noms de champs Contact Form 7 et aux valeurs utilisées pour déclencher les deux calculs historiques. Ne les modifiez que si le formulaire CF7 change.</p>
                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                    <input type="hidden" name="action" value="parcs_ht_save_quote_engine_1177"><input type="hidden" name="year" value="<?php echo esc_attr($year); ?>">
                    <?php wp_nonce_field('parcs_ht_save_quote_engine_1177'); ?>
                    <div class="htp-1177-grid">
                        <label><span>Champ date de visite</span><input type="text" name="visit_field" value="<?php echo esc_attr((string)($settings['visit_field'] ?? 'visite')); ?>"></label>
                        <label><span>Champ type de groupe</span><input type="text" name="group_field" value="<?php echo esc_attr((string)($settings['group_field'] ?? 'groupedevis')); ?>"></label>
                        <label><span>Valeur groupe scolaire / extrascolaire</span><input type="text" name="school_value" value="<?php echo esc_attr((string)($settings['school_value'] ?? 'Groupe')); ?>"></label>
                        <label><span>Valeur groupe en situation de handicap</span><input type="text" name="disability_value" value="<?php echo esc_attr((string)($settings['disability_value'] ?? 'Groupe en situation de handicap')); ?>"></label>
                    </div>
                    <?php submit_button('Enregistrer les options techniques', 'secondary', 'submit', false); ?>
                </form>
            </div></details>
        </section>
        <?php
    }

    public static function save_binding() {
        if (!current_user_can('manage_options')) wp_die('Accès refusé.');
        check_admin_referer('parcs_ht_save_quote_binding_1177');
        $year = isset($_POST['year']) ? self::valid_year(sanitize_text_field(wp_unslash($_POST['year']))) : '';
        if ($year === '') wp_die('Année invalide.');
        $rows = self::rows($year); $columns = self::columns($year);
        $column_id = isset($_POST['column_id']) ? sanitize_key(wp_unslash($_POST['column_id'])) : '';
        if (!Parcs_HT_Tariff_Identities::column_exists($columns, $column_id)) wp_die('La colonne tarifaire sélectionnée n’existe pas dans cette année.');

        $binding = array('column_id'=>$column_id);
        foreach (array('child','adult','disability','companion') as $role) {
            $key = $role . '_row_id';
            $id = isset($_POST[$key]) ? sanitize_key(wp_unslash($_POST[$key])) : '';
            if (!Parcs_HT_Tariff_Identities::row_by_id($rows, $id)) wp_die('Un tarif lié n’existe pas dans la grille de cette année.');
            $binding[$key] = $id;
        }
        $ratio = max(1, isset($_POST['free_adult_children']) ? absint($_POST['free_adult_children']) : 10);
        $threshold = max(1, isset($_POST['free_adult_round_threshold']) ? absint($_POST['free_adult_round_threshold']) : 5);
        $binding['free_adult_children'] = (string)$ratio;
        $binding['free_adult_round_threshold'] = (string)min($ratio, $threshold);

        $settings = Parcs_HT_Group_Quotes::settings(false);
        if (!isset($settings['tariff_bindings']) || !is_array($settings['tariff_bindings'])) $settings['tariff_bindings'] = array();
        $settings['tariff_bindings'][$year] = $binding;
        $settings['binding_version'] = 3;
        update_option(Parcs_HT_Group_Quotes::OPTION, $settings, false);
        $stored = Parcs_HT_Group_Quotes::binding_for_year($year, Parcs_HT_Group_Quotes::settings(false));
        if (!$stored || wp_json_encode($stored) !== wp_json_encode($binding)) wp_die('WordPress n’a pas confirmé la liaison de cette année.');
        if (class_exists('Parcs_HT_Save_Integrity')) Parcs_HT_Save_Integrity::store_daily_snapshot($year, 'Liaison tarifs / devis groupe 1.17.7');
        self::redirect_saved($year, 'binding');
    }

    private static function clean_cf7($value) {
        $value = trim(sanitize_text_field((string)$value));
        if ($value === '') return '';
        return preg_match('/^\[contact-form-7(?:\s+[^\]]*)?\s*\/?\]$/i', $value) ? $value : null;
    }

    public static function save_forms() {
        if (!current_user_can('manage_options')) wp_die('Accès refusé.');
        check_admin_referer('parcs_ht_save_quote_forms_1177');
        $year = isset($_POST['year']) ? self::valid_year(sanitize_text_field(wp_unslash($_POST['year']))) : '';
        $raw = isset($_POST['forms']) && is_array($_POST['forms']) ? wp_unslash($_POST['forms']) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- nettoyé ci-dessous.
        $clean = array('fr'=>'','en'=>'','de'=>'');
        foreach ($clean as $lang=>$unused) {
            $value = self::clean_cf7($raw[$lang] ?? '');
            if ($value === null) wp_die('Le shortcode Contact Form 7 ' . esc_html(strtoupper($lang)) . ' n’est pas valide.');
            $clean[$lang] = $value;
        }
        $all = get_option(Parcs_HT_Defaults::OPTION, array());
        if (!is_array($all)) wp_die('Réglages principaux indisponibles.');
        if (!isset($all['quote_page']) || !is_array($all['quote_page'])) $all['quote_page'] = array();
        $all['quote_page']['form_shortcodes'] = $clean;
        update_option(Parcs_HT_Defaults::OPTION, $all, false);
        do_action('litespeed_purge_all');
        self::redirect_saved($year, 'forms');
    }

    public static function save_gate() {
        if (!current_user_can('manage_options')) wp_die('Accès refusé.');
        check_admin_referer('parcs_ht_save_quote_gate_1177');
        $year = isset($_POST['year']) ? self::valid_year(sanitize_text_field(wp_unslash($_POST['year']))) : '';
        $out = array(
            'enabled'=>isset($_POST['enabled']) ? '1' : '0',
            'closed_enabled'=>isset($_POST['closed_enabled']) ? '1' : '0',
            'closed_contact'=>isset($_POST['closed_contact']) ? sanitize_text_field(wp_unslash($_POST['closed_contact'])) : '',
            'unavailable_enabled'=>isset($_POST['unavailable_enabled']) ? '1' : '0',
            'unavailable_contact'=>isset($_POST['unavailable_contact']) ? sanitize_text_field(wp_unslash($_POST['unavailable_contact'])) : '',
        );
        foreach (array('fr','en','de') as $lang) {
            $closed = 'closed_message_' . $lang;
            $unavailable = 'unavailable_message_' . $lang;
            $out[$closed] = isset($_POST[$closed]) ? sanitize_textarea_field(wp_unslash($_POST[$closed])) : '';
            $out[$unavailable] = isset($_POST[$unavailable]) ? sanitize_textarea_field(wp_unslash($_POST[$unavailable])) : '';
        }
        update_option(Parcs_HT_Quote_Gate::OPTION, $out, false);
        do_action('litespeed_purge_all');
        self::redirect_saved($year, 'gate');
    }

    public static function save_engine() {
        if (!current_user_can('manage_options')) wp_die('Accès refusé.');
        check_admin_referer('parcs_ht_save_quote_engine_1177');
        $year = isset($_POST['year']) ? self::valid_year(sanitize_text_field(wp_unslash($_POST['year']))) : '';
        $settings = Parcs_HT_Group_Quotes::settings(false);
        $settings['visit_field'] = isset($_POST['visit_field']) ? sanitize_key(wp_unslash($_POST['visit_field'])) : 'visite';
        $settings['group_field'] = isset($_POST['group_field']) ? sanitize_key(wp_unslash($_POST['group_field'])) : 'groupedevis';
        $settings['school_value'] = isset($_POST['school_value']) ? sanitize_text_field(wp_unslash($_POST['school_value'])) : 'Groupe';
        $settings['disability_value'] = isset($_POST['disability_value']) ? sanitize_text_field(wp_unslash($_POST['disability_value'])) : 'Groupe en situation de handicap';
        update_option(Parcs_HT_Group_Quotes::OPTION, $settings, false);
        self::redirect_saved($year, 'engine');
    }
}
