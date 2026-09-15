<?php

if (!defined('ABSPATH')) { exit; }

/** Import/export CSV des données horaires et calendrier d'une saison. */
final class Parcs_HT_Schedule_CSV {
    const MAX_BYTES = 2097152;

    public static function init() {
        add_action('admin_post_parcs_ht_schedule_csv_template', array(__CLASS__, 'download_template'));
        add_action('admin_post_parcs_ht_schedule_csv_import', array(__CLASS__, 'import_csv'));
    }

    public static function render_controls($year) {
        if (!current_user_can('manage_options')) return;
        $year = preg_match('/^20\d{2}$/', (string)$year) ? (string)$year : '';
        if ($year === '') return;
        $template_url = wp_nonce_url(
            add_query_arg(array('action'=>'parcs_ht_schedule_csv_template','season_year'=>$year), admin_url('admin-post.php')),
            'parcs_ht_schedule_csv_template_' . $year
        );
        ?>
        <div class="htp-subsection htp-schedule-csv">
            <h3>Import CSV — horaires & calendrier</h3>
            <p class="description">Importez en une fois les périodes d’ouverture, exceptions, jours fériés, périodes repères et événements de la saison <?php echo esc_html($year); ?>. Seules les catégories présentes dans le CSV sont remplacées ; les autres réglages restent inchangés.</p>
            <p><a class="button" href="<?php echo esc_url($template_url); ?>">Télécharger le modèle CSV</a></p>
            <label class="htp-field"><span>Fichier CSV</span><input type="file" name="schedule_csv" accept=".csv,text/csv,text/plain" required form="htp-schedule-csv-form"></label>
            <p><button class="button button-primary" type="submit" form="htp-schedule-csv-form">Importer dans <?php echo esc_html($year); ?></button></p>
            <p class="description">Types disponibles : <code>regular</code>, <code>exception_hours</code>, <code>exception_closed</code>, <code>holiday</code>, <code>period</code>, <code>school_holiday</code>, <code>event</code>.</p>
        </div>
        <?php
    }

    public static function render_external_form($year) {
        if (!current_user_can('manage_options')) return;
        $year = preg_match('/^20\d{2}$/', (string)$year) ? (string)$year : '';
        if ($year === '') return;
        ?>
        <form id="htp-schedule-csv-form" method="post" enctype="multipart/form-data" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <input type="hidden" name="action" value="parcs_ht_schedule_csv_import">
            <input type="hidden" name="season_year" value="<?php echo esc_attr($year); ?>">
            <?php wp_nonce_field('parcs_ht_schedule_csv_import_' . $year); ?>
        </form>
        <?php
    }

    public static function download_template() {
        if (!current_user_can('manage_options')) wp_die('Accès refusé.');
        $year = isset($_GET['season_year']) ? sanitize_text_field(wp_unslash($_GET['season_year'])) : '';
        if (!preg_match('/^20\d{2}$/', $year)) wp_die('Année invalide.');
        check_admin_referer('parcs_ht_schedule_csv_template_' . $year);
        nocache_headers();
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="horaires-calendrier-' . $year . '-modele.csv"');
        $out = fopen('php://output', 'w');
        if (!$out) exit;
        fwrite($out, "\xEF\xBB\xBF");
        fputcsv($out, self::headers(), ';');
        fputcsv($out, array('regular','1','Haute saison',$year.'-07-01',$year.'-08-31','1,2,3,4,5,6,7','09:30','18:00','','','30','#9AAA8B','100','','','','','','','','1'), ';');
        fputcsv($out, array('exception_closed','1','Fermeture exceptionnelle',$year.'-11-15',$year.'-11-15','','','','','','','#ef7b5b','200','Fermeture exceptionnelle','','','Fermeture exceptionnelle','','','Parc fermé ce jour','','','1'), ';');
        fputcsv($out, array('holiday','1','Jour férié',$year.'-05-01',$year.'-05-01','','','','','','','','','','','','','','','','','','1'), ';');
        fputcsv($out, array('event','1','Animation spéciale',$year.'-10-20',$year.'-10-20','','','','','','','#e7c55b','','','','','Animation spéciale','Special event','Sonderveranstaltung','Informations à compléter','','','1'), ';');
        fclose($out);
        exit;
    }

    private static function headers() {
        return array('type','enabled','label','start','end','weekdays','open','close','open2','close2','last_entry_minutes','color','priority','context_fr','context_en','context_de','title_fr','title_en','title_de','message_fr','message_en','message_de','show_on_calendar');
    }

    private static function delimiter($line) {
        $scores = array(';'=>substr_count($line,';'), ','=>substr_count($line,','), "\t"=>substr_count($line,"\t"));
        arsort($scores);
        $delimiter = (string)key($scores);
        return $scores[$delimiter] > 0 ? $delimiter : ';';
    }

    private static function date_value($value) {
        $value = trim((string)$value);
        if ($value === '') return '';
        return preg_match('/^20\d{2}-\d{2}-\d{2}$/', $value) ? $value : false;
    }

    private static function time_value($value) {
        $value = trim((string)$value);
        if ($value === '') return '';
        return preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $value) ? $value : false;
    }

    private static function flag($value, $default = '1') {
        $value = strtolower(trim((string)$value));
        if ($value === '') return $default;
        return in_array($value, array('1','true','oui','yes','on'), true) ? '1' : '0';
    }

    private static function translations($row, $prefix) {
        return array(
            'fr'=>sanitize_text_field((string)($row[$prefix.'_fr'] ?? '')),
            'en'=>sanitize_text_field((string)($row[$prefix.'_en'] ?? '')),
            'de'=>sanitize_text_field((string)($row[$prefix.'_de'] ?? '')),
        );
    }

    public static function import_csv() {
        if (!current_user_can('manage_options')) wp_die('Accès refusé.');
        $year = isset($_POST['season_year']) ? sanitize_text_field(wp_unslash($_POST['season_year'])) : '';
        if (!preg_match('/^20\d{2}$/', $year)) wp_die('Année invalide.');
        check_admin_referer('parcs_ht_schedule_csv_import_' . $year);
        if (!isset($_FILES['schedule_csv']) || !is_array($_FILES['schedule_csv'])) wp_die('Aucun fichier CSV reçu.');
        $file = $_FILES['schedule_csv']; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- fichier contrôlé ci-dessous.
        if ((int)($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) wp_die('Erreur lors de l’envoi du fichier CSV.');
        if ((int)($file['size'] ?? 0) < 1 || (int)$file['size'] > self::MAX_BYTES) wp_die('Le fichier CSV est vide ou trop volumineux.');
        $tmp = isset($file['tmp_name']) ? (string)$file['tmp_name'] : '';
        if ($tmp === '' || !is_uploaded_file($tmp)) wp_die('Fichier CSV invalide.');

        $handle = fopen($tmp, 'r');
        if (!$handle) wp_die('Impossible de lire le CSV.');
        $first = fgets($handle);
        if ($first === false) { fclose($handle); wp_die('CSV vide.'); }
        $first = preg_replace('/^\xEF\xBB\xBF/', '', $first);
        $delimiter = self::delimiter($first);
        rewind($handle);
        $header = fgetcsv($handle, 0, $delimiter);
        if (!is_array($header)) { fclose($handle); wp_die('En-tête CSV invalide.'); }
        $header = array_map(static function($v){ return sanitize_key(trim((string)$v)); }, $header);
        foreach (array('type','start') as $required) if (!in_array($required, $header, true)) { fclose($handle); wp_die('Colonne obligatoire absente : ' . esc_html($required)); }

        $regular = array(); $exceptions = array(); $holidays = array(); $special = array();
        $seen = array(); $errors = array(); $line = 1;
        while (($values = fgetcsv($handle, 0, $delimiter)) !== false) {
            $line++;
            if (!array_filter($values, static function($v){ return trim((string)$v) !== ''; })) continue;
            $row = array();
            foreach ($header as $i=>$key) if ($key !== '') $row[$key] = isset($values[$i]) ? trim((string)$values[$i]) : '';
            $type = sanitize_key((string)($row['type'] ?? ''));
            if (!in_array($type, array('regular','exception_hours','exception_closed','holiday','period','school_holiday','event'), true)) { $errors[] = 'Ligne '.$line.' : type inconnu.'; continue; }
            $start = self::date_value($row['start'] ?? '');
            $end = self::date_value(($row['end'] ?? '') !== '' ? $row['end'] : ($row['start'] ?? ''));
            if ($start === false || $end === false || $start === '' || $end === '') { $errors[] = 'Ligne '.$line.' : date invalide.'; continue; }
            if ($end < $start) { $errors[] = 'Ligne '.$line.' : la date de fin précède le début.'; continue; }
            $enabled = self::flag($row['enabled'] ?? '1');
            $label = sanitize_text_field((string)($row['label'] ?? ''));
            $color = sanitize_hex_color((string)($row['color'] ?? '')) ?: ($type === 'event' ? '#e7c55b' : '#9AAA8B');
            $seen[$type] = true;

            if ($type === 'regular') {
                $open = self::time_value($row['open'] ?? ''); $close = self::time_value($row['close'] ?? '');
                $open2 = self::time_value($row['open2'] ?? ''); $close2 = self::time_value($row['close2'] ?? '');
                if ($open === false || $close === false || $open === '' || $close === '' || $open2 === false || $close2 === false) { $errors[] = 'Ligne '.$line.' : horaires invalides.'; continue; }
                $days = array_values(array_intersect(array('1','2','3','4','5','6','7'), preg_split('/\s*[,|]\s*/', (string)($row['weekdays'] ?? '1,2,3,4,5,6,7'))));
                if (!$days) $days = array('1','2','3','4','5','6','7');
                $regular[] = array('enabled'=>$enabled,'label'=>$label,'start'=>$start,'end'=>$end,'weekdays'=>$days,'open'=>$open,'close'=>$close,'open2'=>$open2,'close2'=>$close2,'last_entry_minutes'=>preg_match('/^\d{1,4}$/',(string)($row['last_entry_minutes']??''))?(string)(int)$row['last_entry_minutes']:'','color'=>$color);
                continue;
            }

            if ($type === 'exception_hours' || $type === 'exception_closed') {
                $open = self::time_value($row['open'] ?? ''); $close = self::time_value($row['close'] ?? '');
                $open2 = self::time_value($row['open2'] ?? ''); $close2 = self::time_value($row['close2'] ?? '');
                if ($type === 'exception_hours' && ($open === false || $close === false || $open === '' || $close === '')) { $errors[] = 'Ligne '.$line.' : horaires exceptionnels invalides.'; continue; }
                $exceptions[] = array('enabled'=>$enabled,'type'=>$type==='exception_closed'?'closed':'hours','label'=>$label,'start'=>$start,'end'=>$end,'open'=>$open===false?'':$open,'close'=>$close===false?'':$close,'open2'=>$open2===false?'':$open2,'close2'=>$close2===false?'':$close2,'last_entry_minutes'=>preg_match('/^\d{1,4}$/',(string)($row['last_entry_minutes']??''))?(string)(int)$row['last_entry_minutes']:'','priority'=>preg_match('/^\d+$/',(string)($row['priority']??''))?(string)(int)$row['priority']:'100','apply_domain_rules'=>'1','show_public_marker'=>'1','context'=>self::translations($row,'context'),'title'=>self::translations($row,'title'),'message'=>self::translations($row,'message'),'show_popup'=>'0','popup_show_dates'=>'0','popup_show_hours'=>'0','popup_mode'=>'auto','popup_title'=>array('fr'=>'','en'=>'','de'=>''),'popup_message'=>array('fr'=>'','en'=>'','de'=>''),'popup_button_label'=>array('fr'=>'','en'=>'','de'=>''),'popup_button_url'=>array('fr'=>'','en'=>'','de'=>''),'popup_show_button'=>'0','popup_lead_mode'=>'days_before','popup_days_before'=>'14','popup_start'=>'','popup_end'=>'');
                continue;
            }

            if ($type === 'holiday') {
                $holidays[] = array('enabled'=>$enabled,'label'=>$label,'date'=>$start);
                continue;
            }

            $kind = $type === 'event' ? 'event' : ($type === 'school_holiday' ? 'school_holiday' : 'other');
            $special[] = array('enabled'=>$enabled,'kind'=>$kind,'internal_label'=>$label,'title'=>self::translations($row,'title'),'start'=>$start,'end'=>$end,'color'=>$kind==='event'?'#e7c55b':$color,'icon'=>'star','display_mode'=>$kind==='event'?'spot':'spot','message'=>self::translations($row,'message'),'button_label'=>array('fr'=>'','en'=>'','de'=>''),'button_url'=>array('fr'=>'','en'=>'','de'=>''),'show_button'=>'0','show_on_calendar'=>self::flag($row['show_on_calendar'] ?? '1'),'skip_domain_rules'=>'0','show_popup'=>'0','popup_lead_mode'=>'days_before','popup_days_before'=>'14','popup_start'=>'','popup_end'=>'','popup_title'=>array('fr'=>'','en'=>'','de'=>''),'popup_message'=>array('fr'=>'','en'=>'','de'=>''),'popup_button_label'=>array('fr'=>'','en'=>'','de'=>''),'popup_button_url'=>array('fr'=>'','en'=>'','de'=>''),'popup_show_button'=>'0','popup_image_url'=>'');
        }
        fclose($handle);
        if ($errors) wp_die(esc_html(implode("\n", array_slice($errors,0,20))));

        $all = Parcs_HT_Defaults::all_settings();
        if (!isset($all['seasons'][$year]) || !is_array($all['seasons'][$year])) wp_die('Saison introuvable.');
        if (class_exists('Parcs_HT_Health')) Parcs_HT_Health::store_revision($all, $year, 'Avant import CSV horaires/calendrier');
        $season =& $all['seasons'][$year];
        if (isset($seen['regular'])) $season['regular_periods'] = $regular;
        if (isset($seen['exception_hours']) || isset($seen['exception_closed'])) $season['exceptions'] = $exceptions;
        if (isset($seen['holiday'])) $season['public_holidays'] = $holidays;
        if (isset($seen['period']) || isset($seen['school_holiday']) || isset($seen['event'])) {
            $season['special_periods'] = $special;
            $season['school_holidays'] = array();
            foreach ($special as $item) if ($item['enabled']==='1' && $item['kind']==='school_holiday') $season['school_holidays'][] = array('enabled'=>'1','label'=>$item['internal_label'],'start'=>$item['start'],'end'=>$item['end']);
        }
        update_option(Parcs_HT_Defaults::OPTION, $all, false);
        wp_safe_redirect(add_query_arg(array('page'=>Parcs_HT_Admin::PAGE,'season'=>$year,'tab'=>'htp-regular','csv_imported'=>1), admin_url('admin.php')));
        exit;
    }
}
