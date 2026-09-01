<?php

/**
 * Helpers for release-aware regression contracts.
 *
 * Tests should validate behaviours/invariants, not one exact historical line.
 * When an implementation changes in a new release, use release_contract_transition()
 * to keep the old contract for older versions and the new contract for newer ones.
 */

function release_contract_plugin_version($root) {
    $main = file_get_contents($root . '/horaires-tarifs-parc.php');
    if (!is_string($main) || !preg_match('/Version:\s*([0-9]+(?:\.[0-9]+)+)/', $main, $matches)) {
        fwrite(STDERR, "Unable to determine plugin version for release contract.\n");
        exit(1);
    }
    return $matches[1];
}

function release_contract_at_least($current, $minimum) {
    return version_compare($current, $minimum, '>=');
}

function release_contract_require_all($content, $markers, $context) {
    foreach ($markers as $label => $marker) {
        if (is_int($label)) {
            $label = $marker;
        }
        if (strpos($content, $marker) === false) {
            fwrite(STDERR, "{$context}: missing {$label}\n");
            exit(1);
        }
    }
}

function release_contract_require_regex($content, $patterns, $context) {
    foreach ($patterns as $label => $pattern) {
        if (preg_match($pattern, $content) !== 1) {
            fwrite(STDERR, "{$context}: missing behaviour {$label}\n");
            exit(1);
        }
    }
}

function release_contract_forbid($content, $markers, $context) {
    foreach ($markers as $label => $marker) {
        if (is_int($label)) {
            $label = $marker;
        }
        if (strpos($content, $marker) !== false) {
            fwrite(STDERR, "{$context}: obsolete behaviour still present {$label}\n");
            exit(1);
        }
    }
}

function release_contract_transition($current, $introduced_in, $legacy_check, $current_check) {
    if (release_contract_at_least($current, $introduced_in)) {
        $current_check();
        return;
    }
    $legacy_check();
}
