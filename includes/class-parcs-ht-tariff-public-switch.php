<?php

if (!defined('ABSPATH')) { exit; }

/**
 * Sépare la publication d'une grille tarifaire de sa date d'affichage public.
 * Une grille publiée peut donc déjà être utilisée par le devis de son année
 * sans être montrée dans les shortcodes publics avant la date de bascule.
 */
final class Parcs_HT_Tariff_Public_Switch {
    const OPTION = 'parcs_ht_tariff_public_switch';
    const PAGE = 'parcs-ht-tariff-public-switch';

    public static function init() {
        if (is_admin()) {
            add_action('admin_menu', array(__CLASS__, 'menu'));
            add_action('admin_post_parcs_ht_save_tariff_public_switch', array(__CLASS__, 'save'));
        }
    }

    public static function settings() {
        $saved = get_option(self::OPTION, array());
        $saved = is_array($saved) ? $saved : array();
        $switches = isset($saved['switches']) && is_array($saved['switches']) ? $saved['switches'] : array();
        $clean = array();
        foreach ($switches as $year => $at) {
            $year = (string)$year;
            if (!preg_match('/^20\d{2}$/', $year)) continue;
            $at = self::clean_datetime($at);
            if ($at !== '') $clean[$year] = $at;
        }
        ksort($clean, SORT_NUMERIC);
        return array('switches'=>$clean);
    }

    private static function clean_datetime($value) {
        $value = trim((string)$value);
        if ($value === '') return '';
        $value = str_replace('T', ' ', $value);
        return preg_match('/^20\d{2}-\d{2}-\d{2} \d{2}:\d{2}$/', $value) ? $value : '';
    }

    private static function timezone($settings) {
        $name = isset($settings['timezone']) ? (string)$settings['timezone'] : 'Europe/Paris';
        try { return new DateTimeZone($name !== '' ? $name : 'Europe/Paris'); }
        catch (Exception $e) { return new DateTimeZone('Europe/Paris'); }
    }

    private static function now($settings, $override = null) {
        $timezone = self::timezone($settings);
        if ($override instanceof DateTimeInterface) {
            $copy = new DateTimeImmutable($override->format('Y-m-d H:i:s'), $override->getTimezone());
            return $copy->setTimezone($timezone);
        }
        if (is_string($override) && trim($override) !== '') {
            try { return new DateTimeImmutable(trim($override), $timezone); }
            catch (Exception $e) { /* use site time below */ }
        }
        return new DateTimeImmutable(wp_date('Y-m-d H:i:s', null, $timezone), $timezone);
    }

    private static function published_years($settings) {
        $years = array();
        foreach ((array)($settings['seasons'] ?? array()) as $year => $season) {
            if (!preg_match('/^20\d{2}$/', (string)$year) || !is_array($season)) continue;
            if ((string)($season['published'] ?? '0') !== '1') continue;
            $years[] = (string)$year;
        }
        sort($years, SORT_NUMERIC);
        return $years;
    }

    /**
     * Année tarifaire visible publiquement à un instant donné.
     *
     * Priorités :
     * 1. dernière bascule explicitement atteinte vers une saison publiée ;
     * 2. saison publiée contenant la date courante ;
     * 3. année civile courante si elle est publiée ;
     * 4. dernière année publiée passée ;
     * 5. première année publiée future.
     *
     * Une saison future publiée ne devient donc jamais visible simplement parce
     * qu'elle est l'année la plus élevée.
     */
    public static function public_year($settings, $override_now = null) {
        if (!is_array($settings)) return '';
        $years = self::published_years($settings);
        if (!$years) return '';

        $now = self::now($settings, $override_now);
        $today = $now->format('Y-m-d');
        $current_year = $now->format('Y');
        $switches = self::settings()['switches'];
        $switched = '';

        foreach ($switches as $target_year => $at) {
            if (!in_array((string)$target_year, $years, true)) continue;
            try { $moment = new DateTimeImmutable($at, self::timezone($settings)); }
            catch (Exception $e) { continue; }
            if ($moment <= $now && ($switched === '' || (int)$target_year > (int)$switched)) $switched = (string)$target_year;
        }
        if ($switched !== '') return $switched;

        foreach ((array)$settings['seasons'] as $year => $season) {
            if (!in_array((string)$year, $years, true) || !is_array($season)) continue;
            $start = trim((string)($season['season_start'] ?? ''));
            $end = trim((string)($season['season_end'] ?? ''));
            if ($start !== '' && $end !== '' && $today >= $start && $today <= $end) return (string)$year;
        }

        if (in_array($current_year, $years, true)) return $current_year;
        $past = array_values(array_filter($years, static function ($year) use ($current_year) { return (int)$year < (int)$current_year; }));
        if ($past) return (string)end($past);
        return (string)$years[0];
    }

    public static function switch_for_year($year) {
        $year = (string)$year;
        $switches = self::settings()['switches'];
        return isset($switches[$year]) ? (string)$switches[$year] : '';
    }

    public static function menu() {
        if (!class_exists('Parcs_HT_Admin')) return;
        add_submenu_page(
            Parcs_HT_Admin::PAGE,
            'Bascule publique des tarifs',
            'Bascule tarifs',
            'manage_options',
            self::PAGE,
            array(__CLASS__, 'page')
        );
    }

    public static function page() {
        if (!current_user_can('manage_options')) return;
        $all = Parcs_HT_Defaults::all_settings();
        $settings = self::settings();
        $public_year = self::public_year($all);
        ?>
        <div class="wrap">
            <h1>Bascule publique des tarifs</h1>
            <p>La publication d'une saison autorise son utilisation métier. La date ci-dessous détermine uniquement quand les shortcodes publics de tarifs basculent sur la nouvelle année.</p>
            <div class="notice notice-info inline"><p><strong>Année tarifaire actuellement visible :</strong> <?php echo esc_html($public_year !== '' ? $public_year : 'aucune'); ?></p></div>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <input type="hidden" name="action" value="parcs_ht_save_tariff_public_switch">
                <?php wp_nonce_field('parcs_ht_save_tariff_public_switch'); ?>
                <table class="widefat striped" style="max-width:900px">
                    <thead><tr><th>Saison cible</th><th>Statut</th><th>Date et heure de bascule publique</th><th>État devis groupes</th></tr></thead>
                    <tbody>
                    <?php foreach ((array)($all['seasons'] ?? array()) as $year => $season) :
                        if (!preg_match('/^20\d{2}$/', (string)$year) || !is_array($season)) continue;
                        $published = (string)($season['published'] ?? '0') === '1';
                        $at = isset($settings['switches'][$year]) ? str_replace(' ', 'T', $settings['switches'][$year]) : '';
                        $quote = class_exists('Parcs_HT_Group_Quotes') && method_exists('Parcs_HT_Group_Quotes', 'readiness') ? Parcs_HT_Group_Quotes::readiness((string)$year) : null;
                        ?>
                        <tr>
                            <td><strong><?php echo esc_html($year); ?></strong></td>
                            <td><?php echo esc_html($published ? 'Publiée' : 'Brouillon'); ?></td>
                            <td><input type="datetime-local" name="switches[<?php echo esc_attr($year); ?>]" value="<?php echo esc_attr($at); ?>"></td>
                            <td><?php
                                if (!is_array($quote)) echo '—';
                                elseif (!empty($quote['ready'])) echo '<strong style="color:#008a20">PRÊT</strong>';
                                else echo '<strong style="color:#b32d2e">BLOQUÉ</strong> — ' . esc_html((string)($quote['message'] ?? 'Configuration incomplète'));
                            ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                <p class="description">Laisser le champ vide signifie : aucune bascule anticipée pour cette année. La saison future publiée ne sera alors pas affichée avant qu'elle devienne l'année courante ou qu'une autre règle active la sélection.</p>
                <?php submit_button('Enregistrer les dates de bascule'); ?>
            </form>
        </div>
        <?php
    }

    public static function save() {
        if (!current_user_can('manage_options')) wp_die('Accès refusé.');
        check_admin_referer('parcs_ht_save_tariff_public_switch');
        $all = Parcs_HT_Defaults::all_settings();
        $raw = isset($_POST['switches']) && is_array($_POST['switches']) ? wp_unslash($_POST['switches']) : array();
        $clean = array();
        foreach ((array)($all['seasons'] ?? array()) as $year => $season) {
            unset($season);
            $year = (string)$year;
            if (!preg_match('/^20\d{2}$/', $year)) continue;
            $at = isset($raw[$year]) ? self::clean_datetime(sanitize_text_field((string)$raw[$year])) : '';
            if ($at !== '') $clean[$year] = $at;
        }
        update_option(self::OPTION, array('switches'=>$clean), false);
        do_action('litespeed_purge_all');
        wp_safe_redirect(add_query_arg(array('page'=>self::PAGE,'updated'=>'1'), admin_url('admin.php')));
        exit;
    }
}
