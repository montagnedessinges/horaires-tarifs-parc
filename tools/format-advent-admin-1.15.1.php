<?php
$root = dirname(__DIR__);
$path = $root . '/includes/class-parcs-ht-admin.php';
$content = file_get_contents($path);
if ($content === false) exit(1);
$changes = 0;
$content = str_replace('</button>                <a class="nav-tab htp-advent-admin-link"', "</button>\n                <a class=\"nav-tab htp-advent-admin-link\"", $content, $count);
$changes += $count;
$content = str_replace('private static function updates_section($settings) {        $has_token', "private static function updates_section(\$settings) {\n        \$has_token", $content, $count);
$changes += $count;
if ($changes !== 2) {
    fwrite(STDERR, "Expected 2 formatting fixes, got {$changes}.\n");
    exit(1);
}
file_put_contents($path, $content);
@unlink(__FILE__);
@unlink($root . '/.github/workflows/format-advent-admin-1.15.1.yml');
echo "Formatting cleanup applied.\n";
