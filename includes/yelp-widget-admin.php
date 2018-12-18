<?php

class Yelp_Widget_Admin {

    private $settings;
    protected static $instance = null;

    public function __construct() {
        $this->settings = get_option('yelp_plugin_settings', array());

        // Load plugin menu
        add_action('admin_menu', array($this, 'add_plugin_menu'));
        // Settings link
        add_filter("plugin_action_links_" . plugin_basename(YELP_WIDGET_PATH . YELP_WIDGET_BASENAME), array($this, 'add_settings_link'));

        // Load admin style sheet
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_styles'));
        
        // Register settings
        add_action('admin_init', array($this, 'register_settings'));
    }

    /**
     * Get instance of class
     * 
     * @since 1.0.0
     */
    public static function get_instance() {
        if (null == self::$instance) {
            self::$instance = new self;
        }

        return self::$instance;
    }

    /**
     * Initialize class
     *
     * @since 1.0.0
     */
    public function init() {
        self::get_instance();
    }

    /**
     * Load CSS files
     *
     * @since 1.0.0
     */
    public function enqueue_admin_styles() {
        if (!isset($this->plugin_screen_hook_suffix)) {
            return;
        }

        $screen = get_current_screen();
        if ($this->plugin_screen_hook_suffix == $screen->id) {
            wp_enqueue_style(YELP_WIDGET_SLUG . '-admin-styles', YELP_WIDGET_CSS_URL . 'style-admin.css', array(), YELP_WIDGET_VERSION);            
        }
    }
    
    /**
     * Add plugin in Tools menu
     *
     * @since 1.0.0
     */
    public function add_plugin_menu() {
        $this->plugin_screen_hook_suffix = add_options_page(
                __('Yelp Widget', YELP_WIDGET_SLUG), __('Yelp Widget', YELP_WIDGET_SLUG), 'manage_options', YELP_WIDGET_SLUG, array($this, 'load_admin_page')
        );
    }

    /**
     * Add link in Plugins screen
     *
     * @since 1.0.0
     */
    public function add_settings_link($links) {
        $settings_link = '<a href="' . esc_url(get_admin_url(null, 'options-general.php?page=' . YELP_WIDGET_SLUG)) . '">' . "Settings" . '</a>';
        array_unshift($links, $settings_link);
        return $links;
    }

    /**
     * Load settings page
     *
     * @since 1.0.0
     */
    public function load_admin_page() {
        include_once(YELP_WIDGET_VIEWS_PATH . 'admin.php');
    }

    /**
     * Register settings
     *
     * @since 1.0.0
     */
    public function register_settings() {

        register_setting('yelp_plugin_group'
                , 'yelp_plugin_settings'
        );

        add_settings_section(
                'yelp_plugin_section'
                , ''
                , ''
                , 'yelp_plugin_group'
        );

        add_settings_field(
                'api_key', __('API Key', 'yelp_plugin')
                , array($this, 'api_key_render')
                , 'yelp_plugin_group'
                , 'yelp_plugin_section'
        );
    }
    
    /**
     * API Key callback
     *
     * @since 1.0.0
     */

    function api_key_render() {
        printf(
        "<input type='text' style='width:400px;' name='yelp_plugin_settings[api_key]' value='" . $this->settings['api_key'] . "'>"
        );
    }

    /**
     * Get plugin info
     *
     * @since 1.0.0
     * @param string $plugin_slug
     * @return array
     */
    public static function plugin_info($plugin_slug) {
        add_filter('extra_plugin_headers', create_function('', 'return array("GitHub Plugin URI","Twitter");'));
        $plugin_data = get_plugin_data(YELP_WIDGET_PATH . $plugin_slug . '.php');

        return $plugin_data;
    }

}

Yelp_Widget_Admin::init();
