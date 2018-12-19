<?php

/*
 *  Plugin Name:  Display Yelp Widget
 *  Plugin URI:   https://github.com/msicknick/display-yelp-widget/
 *  Description:  Displays your business's Yelp rating and reviews.
 *  Version:      1.2.0
 *  Author:       Magda Sicknick
 *  Author URI:   https://www.msicknick.com/
 *  License:      GPLv3
 *  License URI:  https://www.gnu.org/licenses/gpl-3.0.html
 *  Text Domain:  display-yelp-widget
 */

/** 
 * Exit if accessed directly
 * @since 1.0.0
 */
if (!defined('ABSPATH')) {
    exit;
}

/**
 * DEFINE CONSTANTS
 */
define('DISPLAY_YELP_WIDGET_VERSION', "1.2.0");
define('DISPLAY_YELP_WIDGET_SLUG', "display-yelp-widget");
define('DISPLAY_YELP_WIDGET_BASENAME', plugin_basename(DISPLAY_YELP_WIDGET_SLUG . '.php'));

/**
 * DEFINE PATHS
 */ 
define('DISPLAY_YELP_WIDGET_BASE_FILE', __FILE__ );
define('DISPLAY_YELP_WIDGET_PATH', plugin_dir_path(__FILE__));
define('DISPLAY_YELP_WIDGET_VIEWS_PATH', DISPLAY_YELP_WIDGET_PATH . 'views/');
define('DISPLAY_YELP_WIDGET_INCLUDES_PATH', DISPLAY_YELP_WIDGET_PATH . 'includes/');

/**
 * DEFINE URLS
 */
define('DISPLAY_YELP_WIDGET_URL', plugin_dir_url(__FILE__));
define('DISPLAY_YELP_WIDGET_JS_URL', DISPLAY_YELP_WIDGET_URL . 'assets/js/');
define('DISPLAY_YELP_WIDGET_CSS_URL', DISPLAY_YELP_WIDGET_URL . 'assets/css/');
define('DISPLAY_YELP_WIDGET_IMAGES_URL', DISPLAY_YELP_WIDGET_URL . 'assets/images/');
define('DISPLAY_YELP_WIDGET_GITHUB_URL', 'https://github.com/msicknick/');

/**
 * FRONT END
 */

require_once DISPLAY_YELP_WIDGET_PATH . '/includes/display-yelp-widget.php';

if (is_admin()) {
    require_once DISPLAY_YELP_WIDGET_PATH . '/includes/display-yelp-widget-admin.php';    
}

