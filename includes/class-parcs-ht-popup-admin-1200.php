<?php

if (!defined('ABSPATH')) { exit; }

/**
 * Administration des pop-up visuels autonomes — 1.20.0.
 */
final class Parcs_HT_Popup_Admin_1200 {
    const PAGE = 'parcs-ht-popup-1179';
    const SAVE_ACTION = 'parcs_ht_popup_1200_save';

    public static function init() {
        add_action('admin_menu', array(__CLASS__, 'override_popup_page'), 100);
        add_action('admin_enqueue_scripts', array(__CLASS__, 'assets'), 100);
        add_action('admin_post_' . self::SAVE_ACTION, array(__CLASS__, 'save'));
    }

    public static function override_popup_page() {
        remove_action('admin_page_' . self::PAGE, array('Parcs_HT_Admin_Communication_1179', 'popup_page'));
        add_action('admin_page_' . self::PAGE, array(__CLASS__, 'render_page'));
    }

    public static function assets($hook) {
        $page = isset($_GET['page']) ? sanitize_key(wp_unslash($_GET['page'])) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- lecture de contexte admin uniquement.

        if ($hook === 'admin_page_' . self::PAGE || $page === self::PAGE) {
            wp_dequeue_style('parcs-ht-admin-communication-1179');
            wp_dequeue_script('parcs-ht-admin-communication-1179');
            wp_enqueue_media();
            wp_enqueue_style(
                'parcs-ht-popup-admin-1200',
                PARCS_HT_URL . 'assets/popup-admin-1200.css',
                array(),
                PARCS_HT_VERSION
            );
            wp_enqueue_script(
                'parcs-ht-popup-admin-1200',
                PARCS_HT_URL . 'assets/popup-admin-1200.js',
                array('jquery'),
                PARCS_HT_VERSION,
                true
            );
            return;
        }

        if ($page === 'parcs-ht-periods') {
            wp_enqueue_style(
                'parcs-ht-popup-admin-1200',
                PARCS_HT_URL . 'assets/popup-admin-1200.css',
                array(),
                PARCS_HT_VERSION
            );
            wp_enqueue_script(
                'parcs-ht-popup-admin-1200',
                PARCS_HT_URL . 'assets/popup-admin-1200.js',
                array('jquery'),
                PARCS_HT_VERSION,
                true
            );
        }
    }

    public static function render_page() {
        if (!current_user_can('manage_options')) return;

        $store = Parcs_HT_Popup_1200::store();
        $popups = isset($store['popups']) && is_array($store['popups']) ? $store['popups'] : array();
        ?>
        <div class="wrap htp-1200">
            <div class="htp-1200-title">
                <div>
                    <h1>Pop-up</h1>
                    <p class="description">Les pop-up sont maintenant autonomes : une image par langue, un lien facultatif et aucun contenu dupliqué depuis les événements ou les exceptions.</p>
                </div>
                <span>Interface 1.20.0</span>
            </div>

            <?php self::communication_nav(); ?>
            <?php self::notices($store); ?>

            <section class="htp-1200-info">
                <strong>Format conseillé : 1080 × 1080 px.</strong>
                <span>L’image est affichée entièrement, sans recadrage. La largeur choisie concerne l’ordinateur ; sur mobile, le pop-up s’adapte automatiquement à l’écran.</span>
            </section>

            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" data-popup1200-form>
                <input type="hidden" name="action" value="<?php echo esc_attr(self::SAVE_ACTION); ?>">
                <?php wp_nonce_field(self::SAVE_ACTION); ?>

                <div class="htp-1200-toolbar">
                    <div>
                        <strong><?php echo esc_html((string)count($popups)); ?> pop-up<?php echo count($popups) > 1 ? 's' : ''; ?></strong>
                        <span>Un seul pop-up est affiché à la fois : la priorité la plus élevée est utilisée.</span>
                    </div>
                    <button type="button" class="button button-primary" data-popup1200-add>Ajouter un pop-up</button>
                </div>

                <div class="htp-1200-list" data-popup1200-list>
                    <?php foreach ($popups as $index=>$popup) self::popup_row($index, $popup); ?>
                </div>

                <template data-popup1200-template><?php self::popup_row('__INDEX__', Parcs_HT_Popup_1200::default_popup(), true); ?></template>

                <div class="htp-1200-save">
                    <button type="submit" class="button button-primary button-large">Enregistrer les pop-up</button>
                </div>
            </form>
        </div>
        <?php
    }

    private static function communication_nav() {
        $popup_url = add_query_arg(array('page'=>self::PAGE), admin_url('admin.php'));
        $advent_url = add_query_arg(array('page'=>'parcs-ht-advent-1179'), admin_url('admin.php'));
        echo '<nav class="htp-1200-nav" aria-label="Communication">';
        echo '<a class="button button-primary" href="' . esc_url($popup_url) . '">Pop-up</a>';
        echo '<a class="button" href="' . esc_url($advent_url) . '">Calendrier de l’Avent</a>';
        echo '</nav>';
    }

    private static function notices($store) {
        // phpcs:disable WordPress.Security.NonceVerification.Recommended -- messages visuels uniquement.
        if (isset($_GET['updated'])) {
            echo '<div class="notice notice-success is-dismissible"><p>Les pop-up ont été enregistrés.</p></div>';
        }
        if (isset($_GET['adjusted'])) {
            echo '<div class="notice notice-warning is-dismissible"><p>Une date de fin antérieure à la date de début a été ajustée automatiquement.</p></div>';
        }
        // phpcs:enable WordPress.Security.NonceVerification.Recommended

        $migration_count = (int)($store['migration_count'] ?? 0);
        if ($migration_count > 0 && get_option('parcs_ht_popups_1200_migration_notice_seen', '') !== '1') {
            echo '<div class="notice notice-info"><p><strong>Migration 1.20.0 :</strong> ' . esc_html((string)$migration_count) . ' ancien(s) pop-up ont été conservés comme brouillons désactivés. Vérifiez les images FR / EN / DE avant de les publier.</p></div>';
            update_option('parcs_ht_popups_1200_migration_notice_seen', '1', false);
        }
    }

    private static function popup_row($index, $popup, $template = false) {
        $popup = array_replace_recursive(Parcs_HT_Popup_1200::default_popup(), is_array($popup) ? $popup : array());
        $base = 'popups[' . $index . ']';
        $name = trim((string)$popup['internal_name']);
        if ($name === '') $name = 'Nouveau pop-up';
        ?>
        <article class="htp-1200-card<?php echo $template ? ' is-template' : ''; ?>" data-popup1200-row data-popup1200-index="<?php echo esc_attr((string)$index); ?>">
            <input type="hidden" name="<?php echo esc_attr($base . '[id]'); ?>" value="<?php echo esc_attr((string)$popup['id']); ?>" data-popup1200-id>

            <div class="htp-1200-card-head">
                <div class="htp-1200-summary">
                    <strong data-popup1200-summary><?php echo esc_html($name); ?></strong>
                    <span><?php echo (string)$popup['enabled'] === '1' ? ((string)$popup['published'] === '1' ? 'Activé · publié' : 'Activé · brouillon') : 'Désactivé'; ?></span>
                </div>
                <div class="htp-1200-head-actions">
                    <button type="button" class="button" data-popup1200-duplicate>Dupliquer</button>
                    <button type="button" class="button-link-delete" data-popup1200-remove>Supprimer</button>
                </div>
            </div>

            <div class="htp-1200-grid htp-1200-grid-main">
                <label>
                    <span>Nom interne</span>
                    <input type="text" name="<?php echo esc_attr($base . '[internal_name]'); ?>" value="<?php echo esc_attr((string)$popup['internal_name']); ?>" data-popup1200-name>
                </label>
                <label class="htp-1200-toggle">
                    <span>Activation</span>
                    <span><input type="hidden" name="<?php echo esc_attr($base . '[enabled]'); ?>" value="0"><input type="checkbox" name="<?php echo esc_attr($base . '[enabled]'); ?>" value="1" <?php checked((string)$popup['enabled'], '1'); ?> data-popup1200-enabled> Activer ce pop-up</span>
                </label>
                <label>
                    <span>Statut</span>
                    <select name="<?php echo esc_attr($base . '[published]'); ?>" data-popup1200-published>
                        <option value="0" <?php selected((string)$popup['published'], '0'); ?>>Brouillon</option>
                        <option value="1" <?php selected((string)$popup['published'], '1'); ?>>Publié</option>
                    </select>
                </label>
                <label>
                    <span>Priorité</span>
                    <input type="number" min="0" max="9999" name="<?php echo esc_attr($base . '[priority]'); ?>" value="<?php echo esc_attr((string)$popup['priority']); ?>">
                    <small>Plus le nombre est élevé, plus le pop-up est prioritaire.</small>
                </label>
            </div>

            <div class="htp-1200-grid htp-1200-grid-schedule">
                <label>
                    <span>Début d’affichage</span>
                    <input type="datetime-local" name="<?php echo esc_attr($base . '[start]'); ?>" value="<?php echo esc_attr((string)$popup['start']); ?>">
                </label>
                <label>
                    <span>Fin d’affichage</span>
                    <input type="datetime-local" name="<?php echo esc_attr($base . '[end]'); ?>" value="<?php echo esc_attr((string)$popup['end']); ?>">
                </label>
                <label>
                    <span>Réapparition</span>
                    <select name="<?php echo esc_attr($base . '[reappear_mode]'); ?>" data-popup1200-reappear>
                        <option value="once" <?php selected((string)$popup['reappear_mode'], 'once'); ?>>Une seule fois sur cet appareil</option>
                        <option value="hours" <?php selected((string)$popup['reappear_mode'], 'hours'); ?>>Réafficher après X heures</option>
                    </select>
                </label>
                <label data-popup1200-hours-wrap>
                    <span>Réafficher après</span>
                    <div class="htp-1200-inline-number"><input type="number" min="1" max="8760" name="<?php echo esc_attr($base . '[reappear_hours]'); ?>" value="<?php echo esc_attr((string)$popup['reappear_hours']); ?>"> <span>heures</span></div>
                </label>
            </div>

            <div class="htp-1200-grid htp-1200-grid-size">
                <label>
                    <span>Taille du pop-up sur ordinateur</span>
                    <select name="<?php echo esc_attr($base . '[size_preset]'); ?>" data-popup1200-size>
                        <option value="small" <?php selected((string)$popup['size_preset'], 'small'); ?>>Petit — 480 px</option>
                        <option value="medium" <?php selected((string)$popup['size_preset'], 'medium'); ?>>Moyen — 620 px</option>
                        <option value="large" <?php selected((string)$popup['size_preset'], 'large'); ?>>Grand — 800 px</option>
                        <option value="custom" <?php selected((string)$popup['size_preset'], 'custom'); ?>>Personnalisé</option>
                    </select>
                </label>
                <label data-popup1200-custom-width>
                    <span>Largeur personnalisée</span>
                    <div class="htp-1200-inline-number"><input type="number" min="320" max="1200" name="<?php echo esc_attr($base . '[custom_width]'); ?>" value="<?php echo esc_attr((string)$popup['custom_width']); ?>"> <span>px</span></div>
                </label>
                <div class="htp-1200-size-help">
                    <strong>Image conseillée : 1080 × 1080 px</strong>
                    <span>Aucun recadrage : le visuel reste entier sur ordinateur et mobile.</span>
                </div>
            </div>

            <div class="htp-1200-languages">
                <?php foreach (array('fr'=>'FR','en'=>'EN','de'=>'DE') as $lang=>$label) self::language_media($base, $lang, $label, $popup['media'][$lang] ?? array()); ?>
            </div>

            <div class="htp-1200-preview-actions">
                <label>Langue de l’aperçu
                    <select data-popup1200-preview-lang>
                        <option value="fr">FR</option>
                        <option value="en">EN</option>
                        <option value="de">DE</option>
                    </select>
                </label>
                <button type="button" class="button" data-popup1200-preview>Prévisualiser</button>
            </div>
        </article>
        <?php
    }

    private static function language_media($base, $lang, $label, $media) {
        $media = array_replace(
            array('image_id'=>0,'legacy_image_url'=>'','link_url'=>'','alt'=>''),
            is_array($media) ? $media : array()
        );
        $image_id = (int)$media['image_id'];
        $preview = '';
        if ($image_id > 0) {
            $url = wp_get_attachment_image_url($image_id, 'medium_large');
            if (is_string($url)) $preview = $url;
        }
        if ($preview === '' && !empty($media['legacy_image_url'])) $preview = esc_url_raw((string)$media['legacy_image_url']);
        $field = $base . '[media][' . $lang . ']';
        ?>
        <fieldset class="htp-1200-language" data-popup1200-lang="<?php echo esc_attr($lang); ?>">
            <legend><?php echo esc_html($label); ?></legend>
            <div class="htp-1200-media" data-popup1200-media>
                <input type="hidden" name="<?php echo esc_attr($field . '[image_id]'); ?>" value="<?php echo esc_attr((string)$image_id); ?>" data-popup1200-image-id>
                <input type="hidden" name="<?php echo esc_attr($field . '[legacy_image_url]'); ?>" value="<?php echo esc_attr((string)$media['legacy_image_url']); ?>" data-popup1200-legacy-url>
                <div class="htp-1200-media-preview<?php echo $preview !== '' ? ' has-image' : ''; ?>" data-popup1200-media-preview>
                    <?php if ($preview !== '') : ?><img src="<?php echo esc_url($preview); ?>" alt=""><?php else : ?><span>Aucune image</span><?php endif; ?>
                </div>
                <div class="htp-1200-media-actions">
                    <button type="button" class="button" data-popup1200-media-select>Choisir dans la médiathèque</button>
                    <button type="button" class="button" data-popup1200-media-clear <?php echo $preview === '' ? 'hidden' : ''; ?>>Retirer</button>
                </div>
                <small>Format conseillé : 1080 × 1080 px. Le texte destiné au visiteur peut être intégré directement au visuel de cette langue.</small>
            </div>
            <label>
                <span>Lien au clic <em>(facultatif)</em></span>
                <input type="url" name="<?php echo esc_attr($field . '[link_url]'); ?>" value="<?php echo esc_attr((string)$media['link_url']); ?>" placeholder="https://…">
                <small>Si un lien est renseigné, toute l’image devient cliquable.</small>
            </label>
            <label>
                <span>Texte alternatif</span>
                <input type="text" name="<?php echo esc_attr($field . '[alt]'); ?>" value="<?php echo esc_attr((string)$media['alt']); ?>">
                <small>Décrivez brièvement l’information visible sur l’image pour l’accessibilité.</small>
            </label>
        </fieldset>
        <?php
    }

    public static function save() {
        if (!current_user_can('manage_options')) wp_die('Accès refusé.');
        check_admin_referer(self::SAVE_ACTION);

        $raw = isset($_POST['popups']) && is_array($_POST['popups']) ? wp_unslash($_POST['popups']) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- nettoyage champ par champ ci-dessous.
        $clean = array();
        $adjusted = false;
        $position = 0;

        foreach ($raw as $row) {
            if (!is_array($row)) continue;
            $popup = Parcs_HT_Popup_1200::default_popup();

            $id = sanitize_key((string)($row['id'] ?? ''));
            if ($id === '') $id = sanitize_key(wp_generate_uuid4());

            $popup['id'] = $id;
            $popup['internal_name'] = sanitize_text_field((string)($row['internal_name'] ?? ''));
            if ($popup['internal_name'] === '') $popup['internal_name'] = 'Pop-up ' . (++$position);
            else $position++;

            $popup['enabled'] = (isset($row['enabled']) && (string)$row['enabled'] === '1') ? '1' : '0';
            $popup['published'] = (isset($row['published']) && (string)$row['published'] === '1') ? '1' : '0';
            $popup['start'] = self::sanitize_datetime($row['start'] ?? '');
            $popup['end'] = self::sanitize_datetime($row['end'] ?? '');
            if ($popup['start'] !== '' && $popup['end'] !== '' && $popup['end'] < $popup['start']) {
                $popup['end'] = $popup['start'];
                $adjusted = true;
            }

            $popup['priority'] = max(0, min(9999, (int)($row['priority'] ?? 10)));
            $popup['reappear_mode'] = (string)($row['reappear_mode'] ?? '') === 'once' ? 'once' : 'hours';
            $popup['reappear_hours'] = max(1, min(8760, (int)($row['reappear_hours'] ?? 24)));
            $preset = (string)($row['size_preset'] ?? 'medium');
            $popup['size_preset'] = in_array($preset, array('small','medium','large','custom'), true) ? $preset : 'medium';
            $popup['custom_width'] = max(320, min(1200, (int)($row['custom_width'] ?? 620)));

            foreach (array('fr','en','de') as $lang) {
                $media = isset($row['media'][$lang]) && is_array($row['media'][$lang]) ? $row['media'][$lang] : array();
                $popup['media'][$lang] = array(
                    'image_id' => absint($media['image_id'] ?? 0),
                    'legacy_image_url' => esc_url_raw((string)($media['legacy_image_url'] ?? '')),
                    'link_url' => esc_url_raw((string)($media['link_url'] ?? '')),
                    'alt' => sanitize_text_field((string)($media['alt'] ?? '')),
                );
            }

            $clean[] = $popup;
        }

        Parcs_HT_Popup_1200::save_popups($clean);

        $args = array('page'=>self::PAGE, 'updated'=>'1');
        if ($adjusted) $args['adjusted'] = '1';
        wp_safe_redirect(add_query_arg($args, admin_url('admin.php')));
        exit;
    }

    private static function sanitize_datetime($value) {
        $value = trim((string)$value);
        return preg_match('/^20\d{2}-\d{2}-\d{2}T\d{2}:\d{2}$/', $value) ? $value : '';
    }
}
