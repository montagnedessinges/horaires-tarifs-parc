<?php

$root = dirname(__DIR__);
$core = file_get_contents($root . '/assets/frontend-i18n.js');
$advent = file_get_contents($root . '/assets/advent.js');
$guides = file_get_contents($root . '/includes/class-parcs-ht-pedagogical-guides.php');

if (!is_string($core) || !is_string($advent) || !is_string($guides)) {
    fwrite(STDERR, "Unable to read Analytics metadata sources.\n");
    exit(1);
}

$required_core = array(
    "event:'calendar_date_select'",
    "event:'event_cta_click'",
    "event:'document_download'",
    "event:'tariff_section_select'",
    "event:'ticket_cta_click'",
    "event:'special_offer_click'",
    "event:'quote_cta_click'",
    "event:'quote_date_selected'",
    "view_event:'quote_form_open'",
    "success_event:'generate_lead'",
    "quote_type:type",
    "group_size:size",
    "return '1_20'",
    "return '21_50'",
    "return '51_100'",
    "return '101_plus'",
);
foreach ($required_core as $marker) {
    if (strpos($core, $marker) === false) {
        fwrite(STDERR, "Missing core metadata marker: {$marker}\n");
        exit(1);
    }
}

$required_advent = array(
    "event:'advent_day_open'",
    "event:'advent_social_click'",
    "submit_event:'advent_word_attempt'",
    "view_event:'advent_word_result'",
    "success_event:'advent_entry_submit'",
    "campaign_id",
    "content_id",
);
foreach ($required_advent as $marker) {
    if (strpos($advent, $marker) === false) {
        fwrite(STDERR, "Missing Advent metadata marker: {$marker}\n");
        exit(1);
    }
}

$required_guides = array(
    'data-guide-id=',
    'data-guide-action=',
    'data-guide-season=',
    'data-guide-lang=',
    'data-cycle=',
);
foreach ($required_guides as $marker) {
    if (strpos($guides, $marker) === false) {
        fwrite(STDERR, "Missing guide metadata marker: {$marker}\n");
        exit(1);
    }
}

$combined = $core . "\n" . $advent;
$forbidden_transport = array('dataLayer.push', 'gtag(', 'googletagmanager.com', 'google-analytics.com', 'sendBeacon(');
foreach ($forbidden_transport as $marker) {
    if (strpos($combined, $marker) !== false) {
        fwrite(STDERR, "Direct analytics transport is forbidden: {$marker}\n");
        exit(1);
    }
}

$forbidden_personal_fields = array('emailform', 'telephone', 'adresse', 'organisme', '[name="nom"]', '[name="postal"]', '[name="city"]', '[name="country"]');
foreach ($forbidden_personal_fields as $marker) {
    if (strpos($combined, $marker) !== false) {
        fwrite(STDERR, "Personal field must not be exposed to analytics metadata: {$marker}\n");
        exit(1);
    }
}

fwrite(STDOUT, "Analytics metadata contract passed.\n");
