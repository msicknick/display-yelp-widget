<?php

class Yelp_Widget_Admin {

    private $settings;
    protected static $instance = null;

    public function __construct() {
        $this->settings = get_option('yelp_widget_settings', array());

        // Load plugin menu
        add_action('admin_menu', array($this, 'add_plugin_menu'));
        // Settings link
        add_filter("plugin_action_links_" . plugin_basename(YELP_WIDGET_PATH . YELP_WIDGET_BASENAME), array($this, 'add_settings_link'));

        // Load admin style sheet
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_styles'));
        
        // Register settings
        add_action('admin_init', array($this, 'register_settings'));             
        
        // Register activation hook
        register_activation_hook(YELP_WIDGET_BASE_FILE, array($this,'activate') );
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
     * Delete options on uninstall
     *
     * @since 1.1.0
     */
    public function uninstall() {
        unregister_setting('yelp_widget', 'yelp_widget_settings');
        delete_option('yelp_widget_settings');
    }

    /**
     * Register uninstall hook on activation
     *
     * @since 1.1.0
     */
    public function activate() {    
        register_uninstall_hook(YELP_WIDGET_BASE_FILE, array($this, 'uninstall'));
    }

    /**
     * Add plugin in Tools menu
     *
     * @since 1.0.0
     */
    public function add_plugin_menu() {
        $this->plugin_screen_hook_suffix = add_options_page(
                __('Yelp Widget', YELP_WIDGET_SLUG)
                , __('Yelp Widget', YELP_WIDGET_SLUG)
                , 'manage_options'
                , YELP_WIDGET_SLUG
                , array($this, 'load_admin_page')
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
        
        register_setting( 'yelp_widget', 'yelp_widget_settings' );
        
        add_settings_section(
            'yelp_widget_general_section',
            __( 'Settings', YELP_WIDGET_SLUG ),
            '',
            'yelp_widget'
        );

        add_settings_field(
            'api_key'
                , __('API Key', 'yelp_plugin'),
            array($this, 'api_key_setting_callback'),
            'yelp_widget',
            'yelp_widget_general_section'
        );

        add_settings_field(
            'no_style'
                , __('Disable stylesheet output', 'yelp_plugin'),
            array($this, 'no_style_setting_callback'),
            'yelp_widget',
            'yelp_widget_general_section'
        );
      
    }   
    

    function yelp_widget_settings_section_callback() {
        echo __('This Section Description', YELP_WIDGET_SLUG);
    }

    /**
     * API Key callback
     *
     * @since 1.0.0
     */

    function api_key_setting_callback() {
        ?>
        <p>
                <input type='text' style='width:400px;' name='yelp_widget_settings[api_key]' value=' <?php echo $this->settings['api_key']; ?>'>
        </p>
        <p class="description">
            An API key is required for this plugin to work. <a href='https://www.yelp.com/developers/documentation/v3/authentication'>Follow these instructions to aquire one.</a>
        </p>
        <?php
    }

    /**
     * API Key callback
     *
     * @since 1.0.0
     */
    function no_style_setting_callback() {
        ?>        
            <p>
                <input type="radio" name='yelp_widget_settings[no_style]' id="yel-widget-no-style-y" value="Y" <?php checked("Y", $this->settings['no_style'], true); ?>>
                <label for="yel-widget-no-style-y">Yes</label>
            </p>
            <p>
                <input type="radio" name='yelp_widget_settings[no_style]' id="yel-widget-no-style-n" value="N" <?php checked("N", $this->settings['no_style'], true); ?>>
                <label for="yel-widget-no-style-n">No</label>
            </p>
            <p class="description">
                Disabling the custom stylesheet will allow your site's theme to direct the font, colors, and backgrounds.
            </p>
        <?php
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
