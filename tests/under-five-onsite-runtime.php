<?php
define('ABSPATH', __DIR__);
function sanitize_key($v) { return preg_replace('/[^a-z0-9_\-]/', '', strtolower($v)); }
function remove_accents($v) { return strtr($v, array('é'=>'e','è'=>'e','à'=>'a')); }
function wp_strip_all_tags($v) { return strip_tags($v); }
function esc_attr($v) { return htmlspecialchars($v, ENT_QUOTES); }
function esc_html($v) { return htmlspecialchars($v, ENT_QUOTES); }
function esc_url($v) { return $v; }
class Parcs_HT_Schedule {
    public static function translation($v, $lang, $fallback = '') { return $v[$lang] ?? ($v['fr'] ?? $fallback); }
}
require dirname(__DIR__) . '/includes/class-parcs-ht-tariff-display.php';
require dirname(__DIR__) . '/includes/class-parcs-ht-tariff-public-fixes.php';
function invoke_private($class, $method, $args) {
    $m = new ReflectionMethod($class, $method); $m->setAccessible(true); return $m->invokeArgs(null, $args);
}
function verify($condition, $message) { if (!$condition) throw new RuntimeException($message); }
$adult = array('enabled'=>'1','label'=>array('fr'=>'Adulte'),'cells'=>array('web'=>array('value'=>'12 €')));
foreach (array('Moins de 5 ans','Under 5 years','Unter 5 Jahren') as $age) {
    foreach (array('Gratuit','Free','Kostenlos','0 €') as $free) {
        foreach (array('web','desk','both','legacy') as $source) {
            $child = array('enabled'=>'1','label'=>array('fr'=>'Enfant'),'subtitle'=>array('fr'=>$age),'cells'=>array());
            if ($source === 'legacy') $child['price'] = $free;
            else {
                $child['cells'][$source === 'desk' ? 'desk' : 'web'] = array('value'=>$free,'url'=>'https://tickets.example');
                if ($source === 'both') $child['cells']['desk'] = array('value'=>$free);
            }
            $data = array('individual'=>array($adult,$child),'columns'=>array('individual'=>array(
                array('id'=>'web','label'=>array('fr'=>'En ligne'),'visible'=>'1'),
                array('id'=>'desk','label'=>array('fr'=>'Sur place'),'visible'=>'1')
            )));
            $before = $data;
            $normal = invoke_private('Parcs_HT_Tariff_Display','normalize_for_display',array($data));
            $fixed = invoke_private('Parcs_HT_Tariff_Public_Fixes','fix_tariff_data',array($normal));
            verify($data === $before, 'Source modified');
            verify($fixed['individual'][0] === $normal['individual'][0], 'Adult changed');
            verify($fixed['individual'][1]['cells']['online']['value'] === '', 'Online free remains');
            $html = invoke_private('Parcs_HT_Tariff_Display','price_table',array($fixed,'individual','fr',array('tickets_url'=>'https://tickets.example')));
            preg_match_all('/<article.*?<\/article>/s', $html, $matches);
            $child_html = $matches[0][1];
            verify(substr_count($child_html, '<b>') === 1, 'Duplicate free value');
            verify(strpos($child_html, 'is-onsite') !== false && strpos($child_html, 'is-single') !== false, 'Missing single onsite style');
            verify(strpos($child_html, '>Sur place</span>') !== false, 'Missing channel');
            verify(strpos($child_html, '<a ') === false, 'Child has purchase link');
            verify(strpos($html, 'En ligne') !== false && strpos($html, '12 €') !== false, 'Adult missing');
        }
    }
}
foreach (array(array('Moins de 5 ans','6 €'),array('Moins de 15 ans','Gratuit'),array('De 5 à 14 ans','Gratuit')) as $case) {
    $row = array('subtitle'=>array('fr'=>$case[0]),'cells'=>array('online'=>array('value'=>$case[1])));
    $data = array('individual'=>array($row));
    $fixed = invoke_private('Parcs_HT_Tariff_Public_Fixes','fix_tariff_data',array($data));
    verify($fixed['individual'][0] === $row, 'Unrelated row changed');
}
echo "Under-five onsite rendering: 48 scenarios and 3 exclusions passed.\n";
