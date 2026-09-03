<?php
/**
 * Plugin Name: Horaires et tarifs du parc
 * Description: Horaires, calendrier interactif, exceptions, alertes et tarifs multilingues pour les parcs.
 * Version: 1.11.4
 * Update URI: https://github.com/montagnedessinges/horaires-tarifs-parc
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Author: Tanguy Huriez – Montagne des Singes
 * Text Domain: horaires-tarifs-parc
 */

if (!defined('ABSPATH')) { exit; }

define('PARCS_HT_VERSION', '1.11.4');
define('PARCS_HT_FILE', __FILE__);
define('PARCS_HT_DIR', plugin_dir_path(__FILE__));
define('PARCS_HT_URL', plugin_dir_url(__FILE__));

require_once PARCS_HT_DIR . 'includes/class-parcs-ht-defaults.php';
require_once PARCS_HT_DIR . 'includes/class-parcs-ht-schedule.php';
require_once PARCS_HT_DIR . 'includes/class-parcs-ht-bootstrap.php';
require_once PARCS_HT_DIR . 'includes/class-parcs-ht-slot-last-entry.php';
require_once PARCS_HT_DIR . 'includes/class-parcs-ht-http-ssl.php';
require_once PARCS_HT_DIR . 'includes/class-parcs-ht-tariff-seasons.php';
require_once PARCS_HT_DIR . 'includes/class-parcs-ht-season-status.php';
require_once PARCS_HT_DIR . 'includes/class-parcs-ht-group-quotes.php';
require_once PARCS_HT_DIR . 'includes/class-parcs-ht-quote-languages.php';
require_once PARCS_HT_DIR . 'includes/class-parcs-ht-quote-gate.php';
require_once PARCS_HT_DIR . 'includes/class-parcs-ht-quote-page-save.php';
require_once PARCS_HT_DIR . 'includes/class-parcs-ht-admin-groups.php';
require_once PARCS_HT_DIR . 'includes/class-parcs-ht-group-tariffs.php';
require_once PARCS_HT_DIR . 'includes/class-parcs-ht-pedagogical-guides.php';
require_once PARCS_HT_DIR . 'includes/class-parcs-ht-feature-hub.php';
Parcs_HT_HTTP_SSL::init();
Parcs_HT_Tariff_Seasons::init();
Parcs_HT_Season_Status::init();
Parcs_HT_Quote_Page_Save::init();

register_activation_hook(__FILE__, array('Parcs_HT_Defaults', 'activate'));
register_deactivation_hook(__FILE__, array('Parcs_HT_Defaults', 'deactivate'));

add_action('plugins_loaded', array('Parcs_HT_Bootstrap', 'init'));
