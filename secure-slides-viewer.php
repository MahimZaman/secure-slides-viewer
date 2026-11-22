<?php

/**
 * Plugin Name: Secure Slides Viewer
 * Plugin URI: https://github.com/MahimZaman/secure-slides-viewer
 * Description: Upload PPT/PDF slides and display them securely.
 * Version: 1.0
 * Author: Mahim Zaman (Condixia)
 * Author URI: https://condixia.com
 * Requires Plugins: sfwd-lms
 */

if (!defined('ABSPATH')) exit;

define('SSV_PATH', plugin_dir_path(__FILE__));
define('SSV_URL', plugin_dir_url(__FILE__));
define('SSV_DIR', __FILE__);
define('SSV_VERSION', time());

require_once SSV_PATH . 'includes/class-ssv-loader.php';

$loader = new SSV_Loader();
$loader->run();
