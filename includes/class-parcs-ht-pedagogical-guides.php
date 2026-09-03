<?php

if (!defined('ABSPATH')) { exit; }

/** Bibliothèque de guides pédagogiques multilingues, enregistrée par saison. */
final class Parcs_HT_Pedagogical_Guides {
    const OPTION = 'parcs_ht_pedagogical_guides';
    const PAGE = 'parcs-ht-pedagogical-guides';
    const STORE_VERSION = 2;

    private static $pending_duplicate = null;

    public static function init() {
        add_action('admin_menu', array(__CLASS__, 'menu'));
        add_action('admin_post_parcs_ht_save_pedagogical_guides', array(__CLASS__, 'save'));
        add_action('admin_enqueue_scripts', array(__CLASS__, 'admin_assets'));
        add_action('admin_footer', array(__CLASS__, 'embedded_admin_panel'), 5);
        add_action('admin_post_parcs_ht_duplicate_season', array(__CLASS__, 'prepare_season_duplicate'), 5);
        add_action('updated_option', array(__CLASS__, 'complete_season_duplicate'), 20, 3);
        add_action('wp_enqueue_scripts', array(__CLASS__, 'register_assets'));
        add_shortcode('parc_guides_pedagogiques', array(__CLASS__, 'shortcode'));
        foreach (array('fr','en','de') as $language) {
            add_shortcode('parc_guides_pedagogiques_' . $language, static function ($atts = array()) use ($language) {
                return Parcs_HT_Pedagogical_Guides::render($language, is_array($atts) ? $atts : array());
            });
        }
    }

    public static function defaults() {
        return array(
            'cycles'=>array(
                'cycle1'=>array('enabled'=>'1','title'=>array('fr'=>'Cycle 1 – Maternelle','en'=>'Cycle 1 – Nursery school','de'=>'Zyklus 1 – Kindergarten'),'order'=>10),
                'cycle2'=>array('enabled'=>'1','title'=>array('fr'=>'Cycle 2 – Élémentaire','en'=>'Cycle 2 – Primary school','de'=>'Zyklus 2 – Grundschule'),'order'=>20),
                'cycle3'=>array('enabled'=>'0','title'=>array('fr'=>'Cycle 3','en'=>'Cycle 3','de'=>'Zyklus 3'),'order'=>30),
                'multi'=>array('enabled'=>'1','title'=>array('fr'=>'Multiniveaux','en'=>'Multi-level','de'=>'Mehrstufig'),'order'=>40),
            ),
            'guides'=>array(),
        );
    }

    private static function clean_translations($value, $textarea = false) {
        $out = array('fr'=>'','en'=>'','de'=>'');
        $value = is_array($value) ? $value : array();
        foreach ($out as $lang => $unused) {
            $raw = (string)($value[$lang] ?? '');
            $out[$lang] = $textarea ? sanitize_textarea_field(wp_unslash($raw)) : sanitize_text_field(wp_unslash($raw));
        }
        return $out;
    }

    private static function normalize_library($saved) {
        if (!is_array($saved) || empty($saved)) return self::defaults();
        $out = array('cycles'=>array(),'guides'=>array());
        foreach ((array)($saved['cycles'] ?? array()) as $id => $cycle) {
            $id = sanitize_key($id);
            if ($id === '' || !is_array($cycle)) continue;
            $out['cycles'][$id] = array(
                'enabled'=>(string)($cycle['enabled'] ?? '1') === '1' ? '1' : '0',
                'title'=>self::clean_translations($cycle['title'] ?? array()),
                'order'=>(int)($cycle['order'] ?? 0),
            );
        }
        if (!$out['cycles']) $out['cycles'] = self::defaults()['cycles'];
        foreach ((array)($saved['guides'] ?? array()) as $guide) {
            if (!is_array($guide)) continue;
            $languages = array_values(array_intersect(array('fr','de','en'), array_map('sanitize_key', (array)($guide['languages'] ?? array('fr')))));
            if (!$languages) $languages = array('fr');
            $status = sanitize_key($guide['status'] ?? 'available');
            if (!in_array($status, array('available','new','coming'), true)) $status = 'available';
            $out['guides'][] = array(
                'enabled'=>(string)($guide['enabled'] ?? '0') === '1' ? '1' : '0',
                'cycle'=>sanitize_key($guide['cycle'] ?? ''),
                'languages'=>$languages,
                'status'=>$status,
                'title'=>self::clean_translations($guide['title'] ?? array()),
                'description'=>self::clean_translations($guide['description'] ?? array(), true),
                'pdf_url'=>esc_url_raw((string)($guide['pdf_url'] ?? '')),
                'cover_url'=>esc_url_raw((string)($guide['cover_url'] ?? '')),
                'order'=>(int)($guide['order'] ?? 0),
            );
        }
        return $out;
    }

    private static function store() {
        $saved = get_option(self::OPTION, array());
        if (is_array($saved) && isset($saved['seasons']) && is_array($saved['seasons'])) {
            return array('version'=>self::STORE_VERSION, 'seasons'=>$saved['seasons']);
        }

        $legacy = self::normalize_library($saved);
        $seasons = array();
        if (class_exists('Parcs_HT_Defaults')) {
            $all = Parcs_HT_Defaults::all_settings();
            foreach (array_keys((array)($all['seasons'] ?? array())) as $year) $seasons[(string)$year] = $legacy;
        }
        return array('version'=>self::STORE_VERSION, 'seasons'=>$seasons);
    }

    private static function current_year() {
        if (class_exists('Parcs_HT_Defaults')) {
            $settings = Parcs_HT_Defaults::settings();
            $year = (string)($settings['active_season_year'] ?? '');
            if (preg_match('/^20\d{2}$/', $year)) return $year;
        }
        return (string)wp_date('Y');
    }

    public static function settings($year = '') {
        $year = (string)$year;
        if (!preg_match('/^20\d{2}$/', $year)) $year = self::current_year();
        $store = self::store();
        return isset($store['seasons'][$year]) ? self::normalize_library($store['seasons'][$year]) : self::defaults();
    }

    public static function menu() {
        // Page de secours enregistrée mais masquée : l'interface normale est l'onglet Groupes > Guides pédagogiques.
        add_submenu_page(null, 'Guides pédagogiques', 'Guides pédagogiques', 'manage_options', self::PAGE, array(__CLASS__, 'page'));
    }

    public static function register_assets() {
        wp_register_style('parcs-ht-pedagogical-guides', PARCS_HT_URL . 'assets/pedagogical-guides.css', array(), PARCS_HT_VERSION);
    }

    public static function admin_assets($hook) {
        if ($hook !== 'toplevel_page_parcs-horaires-tarifs' && $hook !== 'admin_page_' . self::PAGE) return;
        wp_enqueue_media();
        wp_enqueue_style('parcs-ht-pedagogical-guides-admin', PARCS_HT_URL . 'assets/pedagogical-guides-admin.css', array(), PARCS_HT_VERSION);
        wp_enqueue_script('jquery-ui-sortable');
    }

    private static function sanitize_posted_library($raw) {
        $raw = is_array($raw) ? $raw : array();
        $out = array('cycles'=>array(),'guides'=>array());
        $used = array();
        foreach ((array)($raw['cycles'] ?? array()) as $key => $cycle) {
            if (!is_array($cycle)) continue;
            $id = sanitize_key($cycle['id'] ?? $key);
            if ($id === '') $id = 'categorie';
            $base = $id; $n = 2;
            while (isset($used[$id])) $id = $base . '-' . $n++;
            $used[$id] = true;
            $title = self::clean_translations($cycle['title'] ?? array());
            if (!array_filter($title)) continue;
            $out['cycles'][$id] = array('enabled'=>!empty($cycle['enabled'])?'1':'0','title'=>$title,'order'=>(int)($cycle['order'] ?? 0));
        }
        if (!$out['cycles']) $out['cycles'] = self::defaults()['cycles'];

        $allowed = array_keys($out['cycles']);
        foreach ((array)($raw['items'] ?? array()) as $item) {
            if (!is_array($item)) continue;
            $cycle = sanitize_key($item['cycle'] ?? '');
            if (!in_array($cycle, $allowed, true)) $cycle = $allowed[0];
            $status = sanitize_key($item['status'] ?? 'available');
            if (!in_array($status, array('available','new','coming'), true)) $status = 'available';
            $languages = array();
            foreach (array('fr','de','en') as $lang) if (!empty($item['languages'][$lang])) $languages[] = $lang;
            if (!$languages) $languages = array('fr');
            $title = self::clean_translations($item['title'] ?? array());
            if (!array_filter($title) && empty($item['pdf_url']) && $status !== 'coming') continue;
            $out['guides'][] = array(
                'enabled'=>!empty($item['enabled'])?'1':'0',
                'cycle'=>$cycle,
                'languages'=>$languages,
                'status'=>$status,
                'title'=>$title,
                'description'=>self::clean_translations($item['description'] ?? array(), true),
                'pdf_url'=>esc_url_raw((string)($item['pdf_url'] ?? '')),
                'cover_url'=>esc_url_raw((string)($item['cover_url'] ?? '')),
                'order'=>(int)($item['order'] ?? 0),
            );
        }
        return $out;
    }

    public static function save() {
        if (!current_user_can('manage_options')) wp_die('Accès refusé.');
        check_admin_referer('parcs_ht_save_pedagogical_guides');
        $year = isset($_POST['season_year']) ? sanitize_text_field(wp_unslash($_POST['season_year'])) : '';
        if (!preg_match('/^20\d{2}$/', $year)) wp_die('Année invalide.');
        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Chaque valeur imbriquée est validée et nettoyée selon son type.
        $raw = isset($_POST['guides']) && is_array($_POST['guides']) ? wp_unslash($_POST['guides']) : array();
        $store = self::store();
        $store['seasons'][$year] = self::sanitize_posted_library($raw);
        update_option(self::OPTION, $store, false);
        wp_safe_redirect(add_query_arg(array('page'=>Parcs_HT_Admin::PAGE,'season'=>$year,'tab'=>'htp-guides','guides-updated'=>'1'), admin_url('admin.php')));
        exit;
    }

    public static function prepare_season_duplicate() {
        if (!current_user_can('manage_options')) return;
        $year = isset($_POST['season_year']) ? sanitize_text_field(wp_unslash($_POST['season_year'])) : '';
        if (!preg_match('/^20\d{2}$/', $year)) return;
        $nonce = isset($_POST['_wpnonce']) ? sanitize_text_field(wp_unslash($_POST['_wpnonce'])) : '';
        if (!wp_verify_nonce($nonce, 'parcs_ht_duplicate_season_' . $year)) return;
        $all = Parcs_HT_Defaults::all_settings();
        if (!isset($all['seasons'][$year])) return;
        $target = (int)$year + 1;
        while (isset($all['seasons'][(string)$target]) && $target <= 2100) $target++;
        if ($target <= 2100) self::$pending_duplicate = array('source'=>$year,'target'=>(string)$target);
    }

    public static function complete_season_duplicate($option, $old_value, $new_value) {
        if ($option !== Parcs_HT_Defaults::OPTION || !self::$pending_duplicate || !is_array($new_value)) return;
        $source = self::$pending_duplicate['source'];
        $target = self::$pending_duplicate['target'];
        self::$pending_duplicate = null;
        if (isset($old_value['seasons'][$target]) || !isset($new_value['seasons'][$target])) return;
        $store = self::store();
        if (isset($store['seasons'][$source])) {
            $store['seasons'][$target] = $store['seasons'][$source];
            update_option(self::OPTION, $store, false);
        }
    }

    private static function tr($values,$language,$fallback='') {
        $values=is_array($values)?$values:array();
        $v=trim((string)($values[$language]??'')); if($v!=='')return $v;
        $v=trim((string)($values['fr']??'')); if($v!=='')return $v;
        foreach(array('de','en') as $l){$v=trim((string)($values[$l]??''));if($v!=='')return $v;}
        return $fallback;
    }

    private static function language_meta($language) {
        $all=array(
            'fr'=>array('flag'=>'🇫🇷','fr'=>'Français','en'=>'French','de'=>'Französisch'),
            'de'=>array('flag'=>'🇩🇪','fr'=>'Allemand','en'=>'German','de'=>'Deutsch'),
            'en'=>array('flag'=>'🇬🇧','fr'=>'Anglais','en'=>'English','de'=>'Englisch'),
        );
        return $all[$language] ?? array('flag'=>'','fr'=>$language,'en'=>$language,'de'=>$language);
    }

    public static function shortcode($atts=array()) {
        return self::render(Parcs_HT_Schedule::language(), is_array($atts)?$atts:array());
    }

    public static function render($language,$atts=array()) {
        $language=in_array($language,array('fr','en','de'),true)?$language:'fr';
        wp_enqueue_style('parcs-ht-pedagogical-guides');
        $s=self::settings();
        $guides=array_values(array_filter($s['guides'],static function($g){return is_array($g)&&(string)($g['enabled']??'0')==='1';}));
        usort($guides,static function($a,$b)use($language){$ap=in_array($language,(array)($a['languages']??array()),true)?0:1;$bp=in_array($language,(array)($b['languages']??array()),true)?0:1;return $ap!==$bp?$ap<=>$bp:((int)($a['order']??0)<=>(int)($b['order']??0));});
        $cycles=array_filter($s['cycles'],static function($c){return is_array($c)&&(string)($c['enabled']??'0')==='1';});
        foreach(array_keys($cycles) as $cid){$has=false;foreach($guides as $g)if(($g['cycle']??'')===$cid){$has=true;break;}if(!$has)unset($cycles[$cid]);}
        uasort($cycles,static function($a,$b){return((int)($a['order']??0))<=>((int)($b['order']??0));});
        $filter=sanitize_key($atts['cycle']??''); if($filter!==''&&isset($cycles[$filter]))$cycles=array($filter=>$cycles[$filter]); if(!$cycles)return'';
        $ui=array('fr'=>array('resources'=>'Ressources pédagogiques','new'=>'Nouveau','coming'=>'À venir','read'=>'Consulter','download'=>'Télécharger le PDF'),'en'=>array('resources'=>'Teaching resources','new'=>'New','coming'=>'Coming soon','read'=>'View','download'=>'Download PDF'),'de'=>array('resources'=>'Pädagogische Materialien','new'=>'Neu','coming'=>'Demnächst','read'=>'Ansehen','download'=>'PDF herunterladen'));
        $u=$ui[$language]; $id='parcs-ht-guides-'.wp_rand(1000,999999);
        ob_start(); ?>
        <section id="<?php echo esc_attr($id); ?>" class="parcs-ht-guides" data-htp-guides><header class="parcs-ht-guides-head"><h2><?php echo esc_html($u['resources']); ?></h2></header><div class="parcs-ht-guide-cycle-nav" role="tablist">
        <?php $first=true; foreach($cycles as $cid=>$cycle): $cg=array_values(array_filter($guides,static function($g)use($cid){return($g['cycle']??'')===$cid;})); $langs=array(); foreach($cg as $g)foreach((array)($g['languages']??array())as$l)$langs[$l]=true; ?>
            <button type="button" class="parcs-ht-guide-cycle-button<?php echo $first?' is-active':'';?>" data-guide-cycle-button="<?php echo esc_attr($cid); ?>" aria-selected="<?php echo $first?'true':'false';?>"><span><?php echo esc_html(self::tr($cycle['title'],$language)); ?></span><span class="parcs-ht-guide-cycle-flags"><?php foreach(array_keys($langs)as$l){$m=self::language_meta($l);echo '<span title="'.esc_attr($m[$language]).'">'.esc_html($m['flag']).'</span>';}?></span></button>
        <?php $first=false; endforeach; ?></div>
        <?php $first=true; foreach($cycles as $cid=>$cycle): $cg=array_values(array_filter($guides,static function($g)use($cid){return($g['cycle']??'')===$cid;})); ?>
            <div class="parcs-ht-guide-cycle-panel" data-guide-cycle-panel="<?php echo esc_attr($cid); ?>" <?php if(!$first)echo 'hidden';?>><h3><?php echo esc_html(self::tr($cycle['title'],$language)); ?></h3><div class="parcs-ht-guide-grid">
            <?php foreach($cg as $g): $status=(string)($g['status']??'available'); $pdf=trim((string)($g['pdf_url']??'')); ?>
                <article class="parcs-ht-guide-card<?php echo $status==='coming'?' is-coming':'';?>"><div class="parcs-ht-guide-cover"><?php if(!empty($g['cover_url'])):?><img src="<?php echo esc_url($g['cover_url']); ?>" alt="" loading="lazy"><?php else:?><span class="parcs-ht-guide-book" aria-hidden="true">📘</span><?php endif;?><?php if($status==='new'):?><span class="parcs-ht-guide-status is-new"><?php echo esc_html($u['new']); ?></span><?php elseif($status==='coming'):?><span class="parcs-ht-guide-status is-coming"><?php echo esc_html($u['coming']); ?></span><?php endif;?></div><div class="parcs-ht-guide-body"><div class="parcs-ht-guide-languages"><?php foreach((array)($g['languages']??array())as$l){$m=self::language_meta($l);echo '<span class="parcs-ht-guide-language">'.esc_html($m['flag'].' '.$m[$language]).'</span>';}?></div><h4><?php echo esc_html(self::tr($g['title'],$language)); ?></h4><?php $desc=self::tr($g['description'],$language);if($desc!==''):?><p><?php echo nl2br(esc_html($desc)); ?></p><?php endif;?><?php if($status!=='coming'&&$pdf!==''):?><div class="parcs-ht-guide-actions"><a class="parcs-ht-guide-primary" href="<?php echo esc_url($pdf); ?>" target="_blank" rel="noopener"><?php echo esc_html($u['read']); ?></a><a class="parcs-ht-guide-secondary" href="<?php echo esc_url($pdf); ?>" download><?php echo esc_html($u['download']); ?></a></div><?php endif;?></div></article>
            <?php endforeach; ?></div></div>
        <?php $first=false; endforeach; ?></section>
        <script>(function(){var r=document.getElementById(<?php echo wp_json_encode($id); ?>);if(!r)return;r.querySelectorAll('[data-guide-cycle-button]').forEach(function(b){b.addEventListener('click',function(){var id=b.dataset.guideCycleButton;r.querySelectorAll('[data-guide-cycle-button]').forEach(function(x){var on=x===b;x.classList.toggle('is-active',on);x.setAttribute('aria-selected',on?'true':'false');});r.querySelectorAll('[data-guide-cycle-panel]').forEach(function(p){p.hidden=p.dataset.guideCyclePanel!==id;});});});}());</script>
        <?php return ob_get_clean();
    }

    private static function lang_fields($base,$values,$textarea=false) {
        $values=is_array($values)?$values:array();
        foreach(array('fr'=>'FR','en'=>'EN','de'=>'DE')as$lang=>$label): ?>
            <label class="htp-guide-lang-field"><span><?php echo esc_html($label);?></span><?php if($textarea):?><textarea name="<?php echo esc_attr($base.'['.$lang.']');?>" rows="2"><?php echo esc_textarea($values[$lang]??'');?></textarea><?php else:?><input type="text" name="<?php echo esc_attr($base.'['.$lang.']');?>" value="<?php echo esc_attr($values[$lang]??'');?>"><?php endif;?></label>
        <?php endforeach;
    }

    private static function admin_content($year, $embedded = false) {
        $s=self::settings($year); ?>
        <div class="htp-guides-admin<?php echo $embedded?' htp-guides-admin-embedded':'';?>">
            <?php if ($embedded): ?><h2>Guides pédagogiques — <?php echo esc_html($year); ?></h2><?php else: ?><h1>Guides pédagogiques — <?php echo esc_html($year); ?></h1><?php endif; ?>
            <p class="description">Les guides sont enregistrés avec cette année. Quand vous préparez l’année suivante par duplication, les catégories et documents sont copiés à l’identique.</p>
            <?php if(isset($_GET['guides-updated'])): /* phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Message de confirmation uniquement. */ ?><div class="notice notice-success inline"><p>Les guides pédagogiques ont été enregistrés.</p></div><?php endif; ?>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php'));?>">
                <input type="hidden" name="action" value="parcs_ht_save_pedagogical_guides">
                <input type="hidden" name="season_year" value="<?php echo esc_attr($year); ?>">
                <?php wp_nonce_field('parcs_ht_save_pedagogical_guides'); ?>
                <section class="htp-guide-admin-card"><div class="htp-guide-admin-head"><h3>Catégories</h3><button type="button" class="button" data-add-cycle>Ajouter une catégorie</button></div><p class="description">Vous pouvez renommer, masquer, réordonner ou supprimer une catégorie. Cycle 3 est préparé mais masqué par défaut.</p><div data-cycle-list><?php foreach($s['cycles']as$id=>$c)self::cycle_admin_row($id,$c);?></div></section>
                <section class="htp-guide-admin-card"><div class="htp-guide-admin-head"><h3>Documents</h3><button type="button" class="button" data-add-guide>Ajouter un guide</button></div><div data-guide-list><?php foreach($s['guides']as$i=>$g)self::guide_admin_row($i,$g,$s['cycles']);?></div></section>
                <?php submit_button('Enregistrer les guides'); ?>
            </form>
            <template class="htp-cycle-template"><?php self::cycle_admin_row('__INDEX__',array('enabled'=>'0','title'=>array('fr'=>'Nouvelle catégorie','en'=>'New category','de'=>'Neue Kategorie'),'order'=>0));?></template>
            <template class="htp-guide-template"><?php self::guide_admin_row('__INDEX__',array('enabled'=>'0','cycle'=>array_key_first($s['cycles']),'languages'=>array('fr'),'status'=>'coming','title'=>array(),'description'=>array(),'pdf_url'=>'','cover_url'=>'','order'=>0),$s['cycles']);?></template>
        </div>
        <?php self::admin_script();
    }

    private static function admin_script() { ?>
        <script>(function($){var root=$('#htp-guides');if(!root.length)root=$('.htp-guides-admin').last();var cl=root.find('[data-cycle-list]'),gl=root.find('[data-guide-list]');root.on('click','[data-add-cycle]',function(){var i='cat-'+Date.now();cl.append(root.find('.htp-cycle-template').html().replaceAll('__INDEX__',i));});root.on('click','[data-add-guide]',function(){var i=Date.now();gl.append(root.find('.htp-guide-template').html().replaceAll('__INDEX__',i));});root.on('click','[data-remove-cycle]',function(){$(this).closest('[data-cycle-row]').remove();});root.on('click','[data-remove-guide]',function(){$(this).closest('[data-guide-row]').remove();});root.on('click','[data-media-field]',function(){var b=$(this),t=b.siblings('input[type=url]'),type=b.data('media-field'),f=wp.media({title:type==='pdf'?'Choisir un PDF':'Choisir une image',multiple:false,library:type==='pdf'?{type:'application/pdf'}:{type:'image'}});f.on('select',function(){t.val(f.state().get('selection').first().toJSON().url);});f.open();});if($.fn.sortable){cl.sortable({items:'[data-cycle-row]',handle:'[data-cycle-handle]',update:function(){cl.children().each(function(i){$(this).find('[data-cycle-order]').val((i+1)*10);});}});gl.sortable({items:'[data-guide-row]',handle:'[data-guide-handle]',update:function(){gl.children().each(function(i){$(this).find('[data-guide-order]').val((i+1)*10);});}});}})(jQuery);</script>
    <?php }

    public static function embedded_admin_panel() {
        if (!current_user_can('manage_options')) return;
        $screen = function_exists('get_current_screen') ? get_current_screen() : null;
        if (!$screen || $screen->id !== 'toplevel_page_' . Parcs_HT_Admin::PAGE) return;
        $year = isset($_GET['season']) ? sanitize_text_field(wp_unslash($_GET['season'])) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Sélection d'année en lecture seule.
        if (!preg_match('/^20\d{2}$/', $year)) $year = self::current_year();
        ?><section id="htp-guides" class="htp-card" hidden><?php self::admin_content($year, true); ?></section><script>(function(){var p=document.getElementById('htp-guides'),r=document.querySelector('.htp-admin');if(p&&r)r.appendChild(p);}());</script><?php
    }

    public static function page() {
        if(!current_user_can('manage_options')) return;
        $year = isset($_GET['season']) ? sanitize_text_field(wp_unslash($_GET['season'])) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Sélection d'année en lecture seule.
        if (!preg_match('/^20\d{2}$/', $year)) $year = self::current_year();
        ?><div class="wrap"><?php self::admin_content($year, false); ?></div><?php
    }

    private static function cycle_admin_row($id,$c) {
        $c=wp_parse_args($c,array('enabled'=>'1','title'=>array(),'order'=>0)); $base='guides[cycles]['.$id.'];' ;
        $base='guides[cycles]['.$id.']'; ?>
        <article class="htp-guide-cycle-admin" data-cycle-row><div class="htp-guide-row-head"><button type="button" class="button" data-cycle-handle>↕</button><label><input type="checkbox" name="<?php echo esc_attr($base.'[enabled]');?>" value="1" <?php checked($c['enabled'],'1');?>> Afficher</label><input type="hidden" name="<?php echo esc_attr($base.'[id]');?>" value="<?php echo esc_attr($id);?>"><label class="htp-guide-order-field"><span>Ordre d’affichage</span><input type="number" class="small-text" data-cycle-order name="<?php echo esc_attr($base.'[order]');?>" value="<?php echo (int)$c['order'];?>"></label><button type="button" class="button-link-delete" data-remove-cycle>Supprimer</button></div><?php self::lang_fields($base.'[title]',$c['title']);?></article>
        <?php
    }

    private static function guide_admin_row($i,$g,$cycles) {
        $g=wp_parse_args($g,array('enabled'=>'1','cycle'=>array_key_first($cycles),'languages'=>array('fr'),'status'=>'available','title'=>array(),'description'=>array(),'pdf_url'=>'','cover_url'=>'','order'=>0)); $base='guides[items]['.$i.']'; ?>
        <article class="htp-guide-admin-row" data-guide-row><div class="htp-guide-row-head"><button type="button" class="button" data-guide-handle>↕</button><label><input type="checkbox" name="<?php echo esc_attr($base.'[enabled]');?>" value="1" <?php checked($g['enabled'],'1');?>> Afficher</label><select name="<?php echo esc_attr($base.'[cycle]');?>"><?php foreach($cycles as$cid=>$c):?><option value="<?php echo esc_attr($cid);?>" <?php selected($g['cycle'],$cid);?>><?php echo esc_html(self::tr($c['title'],'fr',$cid));?></option><?php endforeach;?></select><select name="<?php echo esc_attr($base.'[status]');?>"><option value="available" <?php selected($g['status'],'available');?>>Disponible</option><option value="new" <?php selected($g['status'],'new');?>>Nouveau</option><option value="coming" <?php selected($g['status'],'coming');?>>À venir</option></select><label class="htp-guide-order-field"><span>Ordre d’affichage</span><input type="number" class="small-text" data-guide-order name="<?php echo esc_attr($base.'[order]');?>" value="<?php echo (int)$g['order'];?>"></label><button type="button" class="button-link-delete" data-remove-guide>Supprimer</button></div><div class="htp-guide-languages-admin"><strong>Langue :</strong><?php foreach(array('fr'=>'🇫🇷 Français','de'=>'🇩🇪 Deutsch','en'=>'🇬🇧 English')as$lang=>$label):?><label><input type="checkbox" name="<?php echo esc_attr($base.'[languages]['.$lang.']');?>" value="1" <?php checked(in_array($lang,(array)$g['languages'],true));?>> <?php echo esc_html($label);?></label><?php endforeach;?></div><div class="htp-guide-admin-grid"><div><h4>Titre</h4><?php self::lang_fields($base.'[title]',$g['title']);?></div><div><h4>Description</h4><?php self::lang_fields($base.'[description]',$g['description'],true);?></div></div><div class="htp-guide-media-grid"><label>PDF <span><input type="url" name="<?php echo esc_attr($base.'[pdf_url]');?>" value="<?php echo esc_attr($g['pdf_url']);?>"><button type="button" class="button" data-media-field="pdf">Choisir</button></span></label><label>Couverture <span><input type="url" name="<?php echo esc_attr($base.'[cover_url]');?>" value="<?php echo esc_attr($g['cover_url']);?>"><button type="button" class="button" data-media-field="image">Choisir</button></span></label></div></article>
        <?php
    }
}
