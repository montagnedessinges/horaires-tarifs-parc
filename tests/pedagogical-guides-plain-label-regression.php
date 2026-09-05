<?php

$root = getenv('PLUGIN_ROOT') ?: dirname(__DIR__);
$php = file_get_contents($root . '/includes/class-parcs-ht-pedagogical-guides.php');
$css = file_get_contents($root . '/assets/pedagogical-guides.css');

function guide_plain_label_contract($condition, $message) {
    if (!$condition) { fwrite(STDERR, $message . PHP_EOL); exit(1); }
    echo '[OK] ' . $message . PHP_EOL;
}

guide_plain_label_contract(strpos($php, '<div class="parcs-ht-guides-label">') !== false, 'Guide shortcode renders its public label as ordinary text inside the shortcode');
guide_plain_label_contract(strpos($php, '<header class="parcs-ht-guides-head">') === false, 'Guide shortcode no longer renders a header element for its public label');
guide_plain_label_contract(strpos($php, '<h2><?php echo esc_html($u[\'resources\']);?></h2>') === false, 'Guide shortcode no longer renders the public label as an h2');
guide_plain_label_contract(strpos($css, '.parcs-ht-guides-label{') !== false && strpos($css, 'font:inherit') !== false, 'Guide label inherits normal page text styling');
