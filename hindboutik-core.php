<?php
/**
 * Plugin Name: HindBoutik Core
 * Plugin URI: https://hindboutik.com
 * Description: Plugin généraliste regroupant tous les mu-plugins et snippets de HindBoutik. Remplace ACF par des méta natives WordPress.
 * Version: 1.3.0
 * Author: Webpixelia
 * Author URI: https://webpixelia.com
 * Text Domain: hindboutik-core
 * Domain Path: /languages
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 *
 * @package HindBoutik\Core
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

// Plugin version.
define('HINDBOUTIK_CORE_VERSION', '1.3.0');

// Plugin paths.
define('HINDBOUTIK_CORE_DIR', plugin_dir_path(__FILE__));
define('HINDBOUTIK_CORE_URL', plugin_dir_url(__FILE__));
define('HINDBOUTIK_CORE_BASENAME', plugin_basename(__FILE__));

// Autoloader PSR-4.
require_once __DIR__ . '/app/Core/Autoloader.php';
HindBoutik\Core\Autoloader::register();

// Global helper functions.
require_once __DIR__ . '/includes/helpers.php';

// Run the plugin.
HindBoutik\Core\Plugin::instance();