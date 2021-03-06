<?php

class Display_Yelp_Widget_Admin {

    private $settings;
    protected static $instance = null;

    public function __construct() {
        $this->settings = get_option('display_yelp_widget_settings', array());

        // Load plugin menu
        add_action('admin_menu', array($this, 'add_plugin_menu'));
        // Settings link
        add_filter("plugin_action_links_" . plugin_basename(DISPLAY_YELP_WIDGET_PATH . DISPLAY_YELP_WIDGET_BASENAME), array($this, 'add_settings_link'));

        // Load admin style sheet
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_styles'));

        // Register settings
        add_action('admin_init', array($this, 'register_settings'));

        // Register activation hook
        register_activation_hook(DISPLAY_YELP_WIDGET_BASE_FILE, array($this, 'activate'));
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
            wp_enqueue_style(DISPLAY_YELP_WIDGET_SLUG . '-admin-styles', DISPLAY_YELP_WIDGET_CSS_URL . 'style-admin.css', array(), DISPLAY_YELP_WIDGET_VERSION);
        }
    }

    /**
     * Register uninstall hook on activation
     *
     * @since 1.1.0
     */
    public function activate() {
        register_uninstall_hook(DISPLAY_YELP_WIDGET_BASE_FILE, array($this, 'uninstall'));
        /**
         * Initialize default settings
         *
         * @since 1.1.0
         */
        $this->init_default_settings();
    }

    /**
     * Delete options on uninstall
     *
     * @since 1.1.0
     */
    public function uninstall() {
        unregister_setting('display_yelp_widget', 'display_yelp_widget_settings');
        delete_option('display_yelp_widget_settings');
    }

    /**
     * Add plugin in Tools menu
     *
     * @since 1.0.0
     */
    public function add_plugin_menu() {
        $this->plugin_screen_hook_suffix = add_options_page(
                __('Display Yelp Widget', DISPLAY_YELP_WIDGET_SLUG)
                , __('Display Yelp Widget', DISPLAY_YELP_WIDGET_SLUG)
                , 'manage_options'
                , DISPLAY_YELP_WIDGET_SLUG
                , array($this, 'load_admin_page')
        );
    }

    /**
     * Add link in Plugins screen
     *
     * @since 1.0.0
     */
    public function add_settings_link($links) {
        $settings_link = '<a href="' . esc_url(get_admin_url(null, 'options-general.php?page=' . DISPLAY_YELP_WIDGET_SLUG)) . '">' . "Settings" . '</a>';
        array_unshift($links, $settings_link);
        return $links;
    }

    /**
     * Load settings page
     *
     * @since 1.0.0
     */
    public function load_admin_page() {
        include_once(DISPLAY_YELP_WIDGET_VIEWS_PATH . 'admin.php');
    }

    /**
     * Register settings
     *
     * @since 1.0.0
     */
    public function register_settings() {

        register_setting('display_yelp_widget', 'display_yelp_widget_settings');

        add_settings_section(
                'display_yelp_widget_general_section'
                , __('Settings', DISPLAY_YELP_WIDGET_SLUG)
                , ''
                , 'display_yelp_widget'
        );

        add_settings_field(
                'api_key'
                , __('API Key', DISPLAY_YELP_WIDGET_SLUG)
                , array($this, 'api_key_setting_callback')
                , 'display_yelp_widget'
                , 'display_yelp_widget_general_section'
        );

        add_settings_field(
                'style'
                , __('Stylesheet output type', DISPLAY_YELP_WIDGET_SLUG)
                , array($this, 'style_setting_callback')
                , 'display_yelp_widget'
                , 'display_yelp_widget_general_section'
        );
    }

    /**
     * API Key callback
     *
     * @since 1.0.0
     */
    function api_key_setting_callback() {
        ?>
        <p>
            <input type='text' style='width:400px;' name='display_yelp_widget_settings[api_key]' value='<?php echo trim($this->settings['api_key']); ?>'>
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
    function style_setting_callback() {
        ?>        
        <p>
            <input type="radio" name='display_yelp_widget_settings[style]' id="yel-widget-no-style-y" value="yelp" <?php checked("yelp", $this->settings['style'], true); ?>>
            <label for="yel-widget-no-style-y">Yelp style</label>
        </p>
        <p>
            <input type="radio" name='display_yelp_widget_settings[style]' id="yel-widget-no-style-n" value="custom" <?php checked("custom", $this->settings['style'], true); ?>>
            <label for="yel-widget-no-style-n">Custom colors</label>
        </p>
        <p>
            <input type="radio" name='display_yelp_widget_settings[style]' id="yel-widget-no-style-n" value="none" <?php checked("none", $this->settings['style'], true); ?>>
            <label for="yel-widget-no-style-n">No style</label>
        </p>
        <p class="description">
            Yelp style uses Yelp colors; Custom colors disables all color scheme options but keeps formatting; No style allows user to completely customize widget output.
        </p>
        <?php
    }

    /**
     * Sets default settings on Plugin installation if they do not exist already
     *
     * @since 1.2.0
     */
    public function init_default_settings() {

        $display_yelp_widget_settings = array(
            'api_key' => '',
            'style' => 'yelp'
        );

        if (!get_option('display_yelp_widget_settings')) {
            add_option('display_yelp_widget_settings', $display_yelp_widget_settings);
        } else {
            $display_yelp_widget_settings = wp_parse_args(get_option('display_yelp_widget_settings'), $display_yelp_widget_settings);
            update_option('display_yelp_widget_settings', $display_yelp_widget_settings);
        }
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
        $plugin_data = get_plugin_data(DISPLAY_YELP_WIDGET_PATH . $plugin_slug . '.php');

        return $plugin_data;
    }

}

Display_Yelp_Widget_Admin::init();
