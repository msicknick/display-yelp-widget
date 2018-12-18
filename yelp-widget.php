<?php

/*
 *  Plugin Name:  Yelp Widget
 *  Plugin URI:   https://github.com/msicknick/yelp-widget/
 *  Description:  Displays your business's Yelp rating and reviews.
 *  Version:      1.0.0
 *  Author:       Magda Sicknick
 *  Author URI:   https://www.msicknick.com/
 *  License:      GPLv3
 *  License URI:  https://www.gnu.org/licenses/gpl-3.0.html
 *  Text Domain:  yelp-widget
 */

/** 
 * Exit if accessed directly
 * @since 1.0.0
 */
if (!defined('ABSPATH')) {
    exit;
}

/**
 * DEFINE PATHS
 */
define('YELP_WIDGET_VERSION', "1.0.0");
define('YELP_WIDGET_SLUG', "yelp-widget");
define('YELP_WIDGET_BASENAME', plugin_basename(YELP_WIDGET_SLUG . '.php'));

/**
 * DEFINE PATHS
 */
define('YELP_WIDGET_PATH', plugin_dir_path(__FILE__));
define('YELP_WIDGET_VIEWS_PATH', YELP_WIDGET_PATH . 'views/');
define('YELP_WIDGET_INCLUDES_PATH', YELP_WIDGET_PATH . 'includes/');

/**
 * DEFINE URLS
 */
define('YELP_WIDGET_URL', plugin_dir_url(__FILE__));
define('YELP_WIDGET_JS_URL', YELP_WIDGET_URL . 'assets/js/');
define('YELP_WIDGET_CSS_URL', YELP_WIDGET_URL . 'assets/css/');
define('YELP_WIDGET_IMAGES_URL', YELP_WIDGET_URL . 'assets/images/');
define('YELP_WIDGET_GITHUB_URL', 'https://github.com/msicknick/');

/**
 * FRONT END
 */
if (!class_exists('Yelp_Widget') && file_exists(YELP_WIDGET_PATH . '/includes/yelp-widget.php')) {
    require_once YELP_WIDGET_PATH . '/includes/yelp-widget.php';
}
if (is_admin()) {
    require_once YELP_WIDGET_PATH . '/includes/yelp-widget-admin.php';    
}