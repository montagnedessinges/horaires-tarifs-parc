<?php

$root = getenv('PLUGIN_ROOT');
if (!is_string($root) || $root === '') {
    $root = dirname(__DIR__);
}

$main_path = $root . '/horaires-tarifs-parc.php';
$updater_path = $root . '/includes/class-parcs-ht-updater.php';
$readme_path = $root . '/readme.txt';

$main = file_get_contents($main_path);
$updater = file_get_contents($updater_path);
if (!is_string($main) || !is_string($updater)) {
    fwrite(STDERR, "Unable to read release metadata sources.\n");
    exit(1);
}

if (!preg_match('/^ \* Version:\s*([0-9]+(?:\.[0-9]+)+)$/m', $main, $header_match)) {
    fwrite(STDERR, "Plugin header version is missing.\n");
    exit(1);
}
if (!preg_match("/define\('PARCS_HT_VERSION',\s*'([0-9]+(?:\.[0-9]+)+)'\);/", $main, $constant_match)) {
    fwrite(STDERR, "PARCS_HT_VERSION is missing.\n");
    exit(1);
}
if ($header_match[1] !== $constant_match[1]) {
    fwrite(STDERR, "Plugin header and PARCS_HT_VERSION differ.\n");
    exit(1);
}

if (!preg_match("/define\('PARCS_HT_DISPLAY_NAME',\s*'([^']+)'\);/", $main, $name_match)) {
    fwrite(STDERR, "PARCS_HT_DISPLAY_NAME is missing.\n");
    exit(1);
}
if (strpos($updater, "'name'          => PARCS_HT_DISPLAY_NAME,") === false) {
    fwrite(STDERR, "Updater metadata does not use the canonical display name.\n");
    exit(1);
}

if (file_exists($readme_path)) {
    $readme = file_get_contents($readme_path);
    if (!is_string($readme) || !preg_match('/^Stable tag:\s*([0-9]+(?:\.[0-9]+)+)$/m', $readme, $readme_match)) {
        fwrite(STDERR, "Stable tag is missing from readme.txt.\n");
        exit(1);
    }
    if ($readme_match[1] !== $header_match[1]) {
        fwrite(STDERR, "Stable tag and plugin version differ.\n");
        exit(1);
    }
    if (strpos($readme, '=== ' . $name_match[1] . ' ===') !== 0) {
        fwrite(STDERR, "Readme title and canonical display name differ.\n");
        exit(1);
    }
}

if (strpos($updater, "const ASSET_NAME = 'horaires-tarifs-parc.zip';") === false
    || strpos($updater, "const CHECKSUM_ASSET_NAME = 'horaires-tarifs-parc.zip.sha256';") === false) {
    fwrite(STDERR, "Expected release assets are not declared by the updater.\n");
    exit(1);
}

if (version_compare($header_match[1], '1.17.2', '>=')) {
    foreach (array(
        'browser_download_url',
        'is_public_release_download_url',
        'verify_download_checksum',
        "if (\$token !== '') \$headers['Authorization'] = 'Bearer ' . \$token;",
    ) as $needle) {
        if (strpos($updater, $needle) === false) {
            fwrite(STDERR, "Public updater contract missing: {$needle}.\n");
            exit(1);
        }
    }

    foreach (array(
        "if (\$token === '') { self::\$release = false",
        "!self::has_token()",
        'parcs_ht_github_token_missing',
        'mises à jour privées GitHub',
    ) as $forbidden) {
        if (strpos($updater, $forbidden) !== false) {
            fwrite(STDERR, "Obsolete private-repository updater gate found: {$forbidden}.\n");
            exit(1);
        }
    }
}

echo "Release metadata contract passed for version {$header_match[1]}.\n";
